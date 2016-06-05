<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Relations\HasParents;
use BeBat\PolyTree\Relations\HasChildren;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Special handling for HasChildren::attach() and HasParents::attach()
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DirectRelationAttachTest extends TestCase
{
    protected $directRel;
    protected $childNode;

    protected function setUp()
    {
        $this->directRel       = Mockery::mock('overload:BeBat\PolyTree\Relations\Direct');
        $this->parentNode      = Mockery::mock('BeBat\PolyTree\Contracts\Node');
        $this->childNode       = Mockery::mock('BeBat\PolyTree\Contracts\Node');
        $this->mockConnection  = Mockery::mock('Illuminate\Database\ConnectionInterface');
        $this->mockAncestors   = Mockery::mock('BeBat\PolyTree\Relations\HasAncestors');
        $this->mockDescendants = Mockery::mock('BeBat\PolyTree\Relations\HasDescendants');

        $this->directRel->shouldReceive('getBaseQuery->getConnection')->andReturn($this->mockConnection);
        $this->directRel->shouldReceive('getParent->hasAncestors')->andReturn($this->mockAncestors);
        $this->directRel->shouldReceive('getParent->hasDescendants')->andReturn($this->mockDescendants);

        $this->childNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name');
        $this->childNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name');
        $this->parentNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name');
        $this->parentNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name');
    }

    protected function tearDown()
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
        $this->mockAncestors->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->mockAncestors->shouldReceive('attach')->with($this->parentNode)->once()->globally()->ordered();
        $this->mockAncestors->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasParents($this->childNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($this->parentNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }

    public function testHasChildrenAttach()
    {
        // The real heart of our test case; the order here is absolutely key
        // 1. Begin a transaction
        // 2. Attach the direct relationship
        // 3. Unlock the ancestry
        // 4. Attach the ancestry relationship
        // 5. Lock the ancestry
        // 6. Commit all our changes in the DB
        $this->mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with($this->childNode, ['attr' => 'value'], 'doTouch')->once()->globally()->ordered();
        $this->mockDescendants->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $this->mockDescendants->shouldReceive('attach')->with($this->childNode)->once()->globally()->ordered();
        $this->mockDescendants->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $this->mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasChildren($this->parentNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($this->childNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }
}
