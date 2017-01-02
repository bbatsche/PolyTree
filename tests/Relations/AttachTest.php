<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Relations;
use BeBat\PolyTree\Test\TestModel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests for underlying relationship attach methods' behavior.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class AttachTest extends TestCase
{
    /**
     * Mocked direct relationship.
     *
     * Overloaded so we can listen to calls from extended classes.
     *
     * @var BeBat\PolyTree\Relations\Direct
     */
    protected $directRel;

    /**
     * Mocked indirect relations.
     *
     * Overloaded so we can listen to calls from extended classes.
     *
     * @var BeBat\PolyTree\Relations\Indirect
     */
    protected $indirectRel;

    /**  @var BeBat\PolyTree\Test\TestNode */
    protected $childNode;

    /** @var BeBat\PolyTree\Test\TestNode */
    protected $parentNode;

    /** @var BeBat\PolyTree\Contracts\Node */
    protected $mockNode;

    /**
     * Injectable mock so we can capture calls to underlying Illuminate query builder.
     *
     * @var Illuminate\Database\ConnectionInterface
     */
    protected $mockConnection;

    /**
     * Injectable mock so we can capture calls to the ancestor or descendant relationship.
     *
     * @var Mockery\MockInterface
     */
    protected $directAncestry;

    /**
     * Create our mock listeners & test nodes.
     */
    public function setUp()
    {
        $this->directRel   = Mockery::mock('overload:' . Relations\Direct::class);
        $this->indirectRel = Mockery::mock('overload:' . Relations\Indirect::class);

        $this->parentNode = new TestModel(['id' => 'parent_key']);
        $this->childNode  = new TestModel(['id' => 'child_key']);

        $this->mockConnection = Mockery::mock(ConnectionInterface::class);
        $this->directAncestry = Mockery::mock('IndirectRelation');

        $this->mockNode = Mockery::mock(Node::class)->makePartial();
        $this->mockNode->shouldReceive('getKey')->andReturn('mock_key');

        $this->mockNode->shouldReceive('hasAncestors->newPivotStatementForId->count')->andReturn(0);
        $this->mockNode->shouldReceive('hasDescendants->newPivotStatementForId->count')->andReturn(0);

        $this->directRel->shouldReceive('getBaseQuery->getConnection')->andReturn($this->mockConnection);
        $this->directRel->shouldReceive('getParent->hasAncestors')->andReturn($this->directAncestry);
        $this->directRel->shouldReceive('getParent->hasDescendants')->andReturn($this->directAncestry);

        $this->indirectRel->shouldReceive('newPivotStatementForId->count')->andReturn(0);
    }

    /**
     * Check mock expectations.
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Provide a test nodes as an array and as a Laravel collection.
     *
     * @return array
     */
    public function nodeCollectionProvider()
    {
        return [
            'array of nodes'     => [[new TestModel(['id' => 1]), new TestModel(['id' => 2])]],
            'Laravel collection' => [new Collection([new TestModel(['id' => 1]), new TestModel(['id' => 2])])],
        ];
    }

    /**
     * Test behavoir HasParents::attach() when passed a model.
     */
    public function testHasParentsAttach()
    {
        // Order here is absolutely key
        // 1. Begin a transaction
        // 2. Unlock the ancestry
        // 3. Attach the indirect relationship
        // 4. Lock the ancestry
        // 5. Attach the direct relationship
        // 6. Commit all our changes in the DB
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('attach')->with($this->parentNode)->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with($this->parentNode, ['attr' => 'value'], 'doTouch')->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new Relations\HasParents($this->childNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($this->parentNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }

    /**
     * Test behavior of HasChildren::attach() when passed a model.
     */
    public function testHasChildrenAttach()
    {
        // Order here is absolutely key
        // 1. Begin a transaction
        // 2. Unlock the ancestry
        // 3. Attach the indirect relationship
        // 4. Lock the ancestry
        // 5. Attach the direct relationship
        // 6. Commit all our changes in the DB
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('attach')->with($this->childNode)->once()->globally()->ordered();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with($this->childNode, ['attr' => 'value'], 'doTouch')->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new Relations\HasChildren($this->parentNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($this->childNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }

    /**
     * Test behavior of HastParents::attach() when passed an id.
     */
    public function testHasParentsAttachForId()
    {
        $validateAttachArgs = function ($node) {
            return $node instanceof Node && $node->getKey() == 'scalar_value';
        };

        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once();
        $this->directRel->shouldReceive('attach')
            ->with(Mockery::on($validateAttachArgs), Mockery::any(), Mockery::any())->once();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once();
        $this->directAncestry->shouldReceive('attach')->with(Mockery::on($validateAttachArgs))->once();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once();

        $relation = new Relations\HasParents($this->childNode);

        $relation->parent = $this->childNode;

        verify($relation->attach('scalar_value'))->isEmpty();
    }

    /**
     * Test behavior of HasChildren::attach() when passed an id.
     */
    public function testHasChildrenAttachForId()
    {
        $validateAttachArgs = function ($node) {
            return $node instanceof Node && $node->getKey() == 'scalar_value';
        };

        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once();
        $this->directRel->shouldReceive('attach')
            ->with(Mockery::on($validateAttachArgs), Mockery::any(), Mockery::any())->once();
        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->once();
        $this->directAncestry->shouldReceive('attach')->with(Mockery::on($validateAttachArgs))->once();
        $this->directAncestry->shouldReceive('lock')->withNoArgs()->once();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once();

        $relation = new Relations\HasChildren($this->parentNode);

        $relation->parent = $this->parentNode;

        verify($relation->attach('scalar_value'))->isEmpty();
    }

    /**
     * Test behavior of HasParents::attach() when passed multiple models.
     *
     * @param array|Illuminate\Support\Collection
     *
     * @dataProvider nodeCollectionProvider
     */
    public function testHasParentsAttachForMultiple($nodes)
    {
        // Once for the collection as a whole, and then once for each node
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->times(3);

        $this->directRel->shouldReceive('attach')->with($nodes[0], Mockery::any(), Mockery::any())->once();
        $this->directRel->shouldReceive('attach')->with($nodes[1], Mockery::any(), Mockery::any())->once();

        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->twice();

        $this->directAncestry->shouldReceive('attach')->with($nodes[0])->once();
        $this->directAncestry->shouldReceive('attach')->with($nodes[1])->once();

        $this->directAncestry->shouldReceive('lock')->withNoArgs()->twice();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->times(3);

        $relation = new Relations\HasParents($this->childNode);

        verify($relation->attach($nodes))->isEmpty();
    }

    /**
     * Test behavior of HasChildren::attach() when passed multiple models.
     *
     * @param array|Illuminate\Support\Collection
     *
     * @dataProvider nodeCollectionProvider
     */
    public function testHasChildrenAttachForMultiple($nodes)
    {
        // Once for the collection as a whole, and then once for each node
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->times(3);

        $this->directRel->shouldReceive('attach')->with($nodes[0], Mockery::any(), Mockery::any())->once();
        $this->directRel->shouldReceive('attach')->with($nodes[1], Mockery::any(), Mockery::any())->once();

        $this->directAncestry->shouldReceive('unlock')->withNoArgs()->twice();

        $this->directAncestry->shouldReceive('attach')->with($nodes[0])->once();
        $this->directAncestry->shouldReceive('attach')->with($nodes[1])->once();

        $this->directAncestry->shouldReceive('lock')->withNoArgs()->twice();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->times(3);

        $relation = new Relations\HasChildren($this->parentNode);

        verify($relation->attach($nodes))->isEmpty();
    }

    /**
     * Test behavoir of HasAncesstors::attach().
     */
    public function testHasAncestorsAttach()
    {
        $this->indirectRel->shouldReceive('attachAncestry')
            ->with($this->mockNode, $this->childNode)->once()->ordered();
        $this->indirectRel->shouldReceive('attach')->with($this->mockNode)->once()->ordered();

        $relation = new Relations\HasAncestors($this->childNode);

        $relation->parent = $this->childNode; // Normally done via Indirect constructor

        verify($relation->attach($this->mockNode))->isEmpty();
    }

    /**
     * Test behavior of HasDescendants::attach().
     */
    public function testHasDescendantsAttach()
    {
        $this->indirectRel->shouldReceive('attachAncestry')
            ->with($this->parentNode, $this->mockNode)->once()->ordered();
        $this->indirectRel->shouldReceive('attach')->with($this->mockNode)->once()->ordered();

        $relation = new Relations\HasDescendants($this->parentNode);

        $relation->parent = $this->parentNode; // Normally done via Indirect constructor

        verify($relation->attach($this->mockNode))->isEmpty();
    }
}
