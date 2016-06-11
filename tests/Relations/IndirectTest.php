<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\Indirect as IndirectBase;
use BeBat\PolyTree\Test\TestModel;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class IndirectTest extends TestCase
{
    protected $parentNode;
    protected $childNode;
    protected $relation;

    protected $mockHasNoRelatives;
    protected $mockHasOneRelative;

    public function setUp()
    {
        $this->parentNode = Mockery::mock('BeBat\PolyTree\Model');
        $this->childNode  = Mockery::mock('BeBat\PolyTree\Model');

        // Yes we're mocking the supposed SUT. Because we're passing the mocked SUT to the REAL SUT.
        $this->relation = Mockery::mock('BeBat\PolyTree\Relations\Indirect');
        $this->relation->shouldReceive('isLocked')->withNoArgs()->andReturn(false)->byDefault();

        // Mock two nearly identical demeter chain for zero or one count.
        // See: https://github.com/padraic/mockery/issues/607
        $this->mockHasNoRelatives = Mockery::mock('noRelatives');
        $this->mockHasOneRelative = Mockery::mock('oneRelative');

        $this->mockHasNoRelatives->shouldReceive('newPivotStatementForId->count')->andReturn(0);
        $this->mockHasOneRelative->shouldReceive('newPivotStatementForId->count')->andReturn(1);

        // By default, each node has no ancestors or descendants
        // We will override these one by one in the test cases below
        $this->parentNode->shouldReceive('hasDescendants')->andReturn($this->mockHasNoRelatives)->byDefault();
        $this->parentNode->shouldReceive('hasAncestors')->andReturn($this->mockHasNoRelatives)->byDefault();
        $this->childNode->shouldReceive('hasDescendants')->andReturn($this->mockHasNoRelatives)->byDefault();
        $this->childNode->shouldReceive('hasAncestors')->andReturn($this->mockHasNoRelatives)->byDefault();

        $this->parentNode->shouldReceive('getKey')->andReturn('parent_key');
        $this->childNode->shouldReceive('getKey')->andReturn('child_key');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testLocks()
    {
        $node = new TestModel();

        $relation = new Indirect($node, 'foreign_key', 'other_key');

        verify('relation is locked by default', $relation->isLocked())->isTrue();

        verify('unlock is chainable',      $relation->unLock())->sameAs($relation);
        verify('relation is now unlocked', $relation->isLocked())->isFalse();

        verify('lock is chainable',      $relation->lock())->sameAs($relation);
        verify('relation is now locked', $relation->isLocked())->isTrue();
    }

    public function testAttachThrowsLockedException()
    {
        $node = new TestModel();

        $relation = new Indirect($node, 'foreign_key', 'other_key');

        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $relation->attach('test');
    }

    public function testDetachThrowsLockedException()
    {
        $node = new TestModel();

        $relation = new Indirect($node, 'foreign_key', 'other_key');

        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $relation->detach('test');
    }

    public function testAttachAncestryThrowsLockedException()
    {
        $this->relation->shouldReceive('isLocked')->withNoArgs()->andReturn(true)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        IndirectBase::attachAncestry($this->parentNode, $this->childNode, $this->relation);
    }

    /**
     * Test what happens if parent is a descendant of child; should throw an exception
     */
    public function testAttachAncestryThrowsCycleExceptionForChild()
    {
        $this->childNode->shouldReceive('hasDescendants')->andReturn($this->mockHasOneRelative)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\Cycle');

        IndirectBase::attachAncestry($this->parentNode, $this->childNode, $this->relation);
    }

    /**
     * Test what happens if child is an ancestor of parent; should throw an exception
     */
    public function testAttachAncestryThrowsCycleExceptionForParent()
    {
        $this->parentNode->shouldReceive('hasAncestors')->andReturn($this->mockHasOneRelative)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\Cycle');

        IndirectBase::attachAncestry($this->parentNode, $this->childNode, $this->relation);
    }

    /**
     * Test what happens if parent is an ancestor of child; should short circuit and do nothing
     */
    public function testAttachAncestryDoesNothingForChild()
    {
        $this->childNode->shouldReceive('hasAncestors')->andReturn($this->mockHasOneRelative)->once();

        verify(IndirectBase::attachAncestry($this->parentNode, $this->childNode, $this->relation))->isEmpty();
    }

    /**
     * Test what happens if child is a descendant of parent; should short circuit and do nothing
     */
    public function testAttachAncestryDoesNothingForParent()
    {
        $this->parentNode->shouldReceive('hasDescendants')->andReturn($this->mockHasOneRelative)->once();

        verify(IndirectBase::attachAncestry($this->parentNode, $this->childNode, $this->relation))->isEmpty();
    }
}

class Indirect extends IndirectBase {}
