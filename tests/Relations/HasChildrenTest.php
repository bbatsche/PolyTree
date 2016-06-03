<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Relations\HasChildren;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class HasChildrenTest extends TestCase
{
    protected $directRel;
    protected $mockNode;

    protected function setUp()
    {
        $this->directRel = Mockery::mock('overload:BeBat\PolyTree\Relations\Direct');
        $this->mockNode  = Mockery::mock('BeBat\PolyTree\Contracts\Node');

        $this->mockNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name')->byDefault();
        $this->mockNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name')->byDefault();
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testAttach()
    {
        $mockConnection  = Mockery::mock('Illuminate\Database\ConnectionInterface');
        $mockDescendants = Mockery::mock('BeBat\PolyTree\Relations\HasDescendants');
        $childNode       = Mockery::mock('BeBat\PolyTree\Contracts\Node');

        $this->directRel->shouldReceive('getBaseQuery->getConnection')->andReturn($mockConnection);
        $this->directRel->shouldReceive('getParent->hasDescendants')->andReturn($mockDescendants);

        // The real heart of our test case; the order here is absolutely key
        // 1. Begin a transaction
        // 2. Attach the direct relationship
        // 3. Unlock the ancestry
        // 4. Attach the ancestry relationship
        // 5. Lock the ancestry
        // 6. Commit all our changes in the DB
        $mockConnection->shouldReceive('beginTransaction')->withNoArgs()->once()->globally()->ordered();
        $this->directRel->shouldReceive('attach')
            ->with($childNode, ['attr' => 'value'], 'doTouch')->once()->globally()->ordered();
        $mockDescendants->shouldReceive('unlock')->withNoArgs()->once()->globally()->ordered();
        $mockDescendants->shouldReceive('attach')->with($childNode)->once()->globally()->ordered();
        $mockDescendants->shouldReceive('lock')->withNoArgs()->once()->globally()->ordered();
        $mockConnection->shouldReceive('commit')->withNoArgs()->once()->globally()->ordered();

        $relation = new HasChildren($this->mockNode);

        // attach() doesn't return anything (and really, we don't care)
        // but PHPUnit is unhappy if there aren't *any* assertions
        verify($relation->attach($childNode, ['attr' => 'value'], 'doTouch'))->isEmpty();
    }
}
