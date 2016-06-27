<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Relations\HasParents;
use BeBat\PolyTree\Relations\HasChildren;
use BeBat\PolyTree\Relations\HasAncestors;
use BeBat\PolyTree\Relations\HasDescendants;
use BeBat\PolyTree\Test\TestModel;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Special handling for *::attach() methods
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AttachTest extends TestCase
{
    protected $directRel;
    protected $indirectRel;

    protected $parentNode;
    protected $childNode;

    protected $mockNode;

    protected $mockConnection;

    protected $directAncestry;

    public function setUp()
    {
        $this->directRel      = Mockery::mock('overload:BeBat\PolyTree\Relations\Direct');
        $this->indirectRel    = Mockery::mock('overload:BeBat\PolyTree\Relations\Indirect');

        $this->parentNode     = new TestModel(['id' => 'parent_key']);
        $this->childNode      = new TestModel(['id' => 'child_key']);

        $this->mockConnection = Mockery::mock('Illuminate\Database\ConnectionInterface');
        $this->directAncestry = Mockery::mock('IndirectRelation');

        $this->mockNode = Mockery::mock('BeBat\PolyTree\Model')->makePartial();
        $this->mockNode->shouldReceive('getKey')->andReturn('mock_key');

        $this->mockNode->shouldReceive('hasAncestors->newPivotStatementForId->count')->andReturn(0);
        $this->mockNode->shouldReceive('hasDescendants->newPivotStatementForId->count')->andReturn(0);

        $this->directRel->shouldReceive('getBaseQuery->getConnection')->andReturn($this->mockConnection);
        $this->directRel->shouldReceive('getParent->hasAncestors')->andReturn($this->directAncestry);
        $this->directRel->shouldReceive('getParent->hasDescendants')->andReturn($this->directAncestry);

        $this->indirectRel->shouldReceive('newPivotStatementForId->count')->andReturn(0);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testHasParentsAttach()
    {
        // Order here is absolutely key
        // 1. Begin a transaction
        // 2. Attach the direct relationship
        // 3. Unlock the ancestry
        // 4. Attach the ancestry relationship
        // 5. Lock the ancestry
        // 6. Commit all our changes in the DB
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with($this->parentNode, ['attr' => 'value'], 'doTouch')->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('attach')->with($this->parentNode)->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasParents($this->childNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($this->parentNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }

    public function testHasChildrenAttach()
    {
        // Order here is absolutely key
        // 1. Begin a transaction
        // 2. Attach the direct relationship
        // 3. Unlock the ancestry
        // 4. Attach the ancestry relationship
        // 5. Lock the ancestry
        // 6. Commit all our changes in the DB
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with($this->childNode, ['attr' => 'value'], 'doTouch')->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('attach')->with($this->childNode)->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasChildren($this->parentNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($this->childNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }

    public function testHasParentsAttachForId()
    {
        $validateAttachArgs = function ($node)
        {
            return $node instanceof Node && $node->getKey() == 'scalar_value';
        };

        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with(Mockery::on($validateAttachArgs), Mockery::any(), Mockery::any())->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('attach')
            ->with(Mockery::on($validateAttachArgs))->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasParents($this->childNode);
        $relation->parent = $this->childNode;

        verify($relation->attach('scalar_value'))->isEmpty();
    }

    public function testHasChildrenAttachForId()
    {
        $validateAttachArgs = function ($node)
        {
            return $node instanceof Node && $node->getKey() == 'scalar_value';
        };

        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with(Mockery::on($validateAttachArgs), Mockery::any(), Mockery::any())->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('attach')
            ->with(Mockery::on($validateAttachArgs))->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasChildren($this->parentNode);
        $relation->parent = $this->parentNode;

        verify($relation->attach('scalar_value'))->isEmpty();
    }

    public function testHasAncestorsAttach()
    {
        $this->indirectRel->shouldReceive('attachAncestry')
            ->with($this->mockNode, $this->childNode)->once()->ordered();
        $this->indirectRel->shouldReceive('attach')->with($this->mockNode)->once()->ordered();

        $relation = new HasAncestors($this->childNode);
        $relation->parent = $this->childNode; // Normally done via Indirect constructor

        verify($relation->attach($this->mockNode))->isEmpty();
    }

    public function testHasDescendantsAttach()
    {
        $this->indirectRel->shouldReceive('attachAncestry')
            ->with($this->parentNode, $this->mockNode)->once()->ordered();
        $this->indirectRel->shouldReceive('attach')->with($this->mockNode)->once()->ordered();

        $relation = new HasDescendants($this->parentNode);
        $relation->parent = $this->parentNode; // Normally done via Indirect constructor

        verify($relation->attach($this->mockNode))->isEmpty();
    }
}
