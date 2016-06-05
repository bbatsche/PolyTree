<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\Indirect as IndirectBase;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class IndirectTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testLocks()
    {
        $node = Mockery::mock('BeBat\PolyTree\Model')->makePartial();

        $relation = new Indirect($node, 'foreign_key', 'other_key');

        verify('relation is locked by default', $relation->isLocked())->isTrue();
        verify('unlock is chainable', $relation->unLock())->sameAs($relation);
        verify('relation is now unlocked', $relation->isLocked())->isFalse();
        verify('lock is chainable', $relation->lock())->sameAs($relation);
        verify('relation is now locked', $relation->isLocked())->isTrue();
    }

    public function testAttachThrowsLockedException()
    {
        $node = Mockery::mock('BeBat\PolyTree\Model')->makePartial();

        $relation = new Indirect($node, 'foreign_key', 'other_key');

        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $relation->attach('test');
    }

    public function testDetachThrowsLockedException()
    {
        $node = Mockery::mock('BeBat\PolyTree\Model')->makePartial();

        $relation = new Indirect($node, 'foreign_key', 'other_key');

        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $relation->detach('test');
    }

    public function testAttachAncestryThrowsLockedException()
    {
        $parent = Mockery::mock('BeBat\PolyTree\Model');
        $child  = Mockery::mock('BeBat\PolyTree\Model');

        // Yes we're mocking the supposed SUT. Because we're passing the mocked SUT to the REAL SUT.
        $relation = Mockery::mock('BeBat\PolyTree\Relations\Indirect');

        $relation->shouldReceive('isLocked')->withNoArgs()->andReturn(true)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        IndirectBase::attachAncestry($parent, $child, $relation);
    }

    public function testAttachAncestryThrowsCycleExceptionForChild()
    {
        $parent = Mockery::mock('BeBat\PolyTree\Model');
        $child  = Mockery::mock('BeBat\PolyTree\Model');

        // Yes we're mocking the supposed SUT. Because we're passing the mocked SUT to the REAL SUT.
        $relation = Mockery::mock('BeBat\PolyTree\Relations\Indirect');

        $mockPivot = Mockery::mock('Pivot');

        $relation->shouldReceive('isLocked')->withNoArgs()->andReturn(false)->once();

        $parent->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key')->once();

        $child->shouldReceive('hasDescendants->newPivotStatementForId')->with('parent_key')->andReturn($mockPivot)->once();

        $mockPivot->shouldReceive('count')->withNoArgs()->andReturn(1)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\Cycle');

        IndirectBase::attachAncestry($parent, $child, $relation);
    }

    public function testAttachAncestryThrowsCycleExceptionForParent()
    {
        $parent = Mockery::mock('BeBat\PolyTree\Model');
        $child  = Mockery::mock('BeBat\PolyTree\Model');

        // Yes we're mocking the supposed SUT. Because we're passing the mocked SUT to the REAL SUT.
        $relation = Mockery::mock('BeBat\PolyTree\Relations\Indirect');

        $mockPivot = Mockery::mock('Pivot');

        $relation->shouldReceive('isLocked')->withNoArgs()->andReturn(false)->once();

        $parent->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key')->once();
        $child->shouldReceive('getKey')->withNoArgs()->andReturn('child_key')->once();

        $parent->shouldReceive('hasAncestors->newPivotStatementForId')->with('child_key')->andReturn($mockPivot)->once();
        $child->shouldReceive('hasDescendants->newPivotStatementForId')->with('parent_key')->andReturn($mockPivot)->once();

        $mockPivot->shouldReceive('count')->withNoArgs()->andReturn(0, 1)->twice();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\Cycle');

        IndirectBase::attachAncestry($parent, $child, $relation);
    }

    public function testAttachAncestryDoesNothingForChild()
    {
        $parent = Mockery::mock('BeBat\PolyTree\Model');
        $child  = Mockery::mock('BeBat\PolyTree\Model');

        // Yes we're mocking the supposed SUT. Because we're passing the mocked SUT to the REAL SUT.
        $relation = Mockery::mock('BeBat\PolyTree\Relations\Indirect');

        $mockPivot = Mockery::mock('Pivot');

        $relation->shouldReceive('isLocked')->withNoArgs()->andReturn(false)->once();

        $parent->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key')->twice();
        $child->shouldReceive('getKey')->withNoArgs()->andReturn('child_key')->once();

        $parent->shouldReceive('hasAncestors->newPivotStatementForId')->with('child_key')->andReturn($mockPivot)->once();
        $child->shouldReceive('hasDescendants->newPivotStatementForId')->with('parent_key')->andReturn($mockPivot)->once();

        $child->shouldReceive('hasAncestors->newPivotStatementForId')->with('parent_key')->andReturn($mockPivot)->once();

        $mockPivot->shouldReceive('count')->withNoArgs()->andReturn(0, 0, 1)->times(3);

        verify(IndirectBase::attachAncestry($parent, $child, $relation))->isEmpty();
    }

    public function testAttachAncestryDoesNothingForParent()
    {
        $parent = Mockery::mock('BeBat\PolyTree\Model');
        $child  = Mockery::mock('BeBat\PolyTree\Model');

        // Yes we're mocking the supposed SUT. Because we're passing the mocked SUT to the REAL SUT.
        $relation = Mockery::mock('BeBat\PolyTree\Relations\Indirect');

        $mockPivot = Mockery::mock('Pivot');

        $relation->shouldReceive('isLocked')->withNoArgs()->andReturn(false)->once();

        $parent->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key')->twice();
        $child->shouldReceive('getKey')->withNoArgs()->andReturn('child_key')->twice();

        $parent->shouldReceive('hasAncestors->newPivotStatementForId')->with('child_key')->andReturn($mockPivot)->once();
        $child->shouldReceive('hasDescendants->newPivotStatementForId')->with('parent_key')->andReturn($mockPivot)->once();

        $child->shouldReceive('hasAncestors->newPivotStatementForId')->with('parent_key')->andReturn($mockPivot)->once();
        $parent->shouldReceive('hasDescendants->newPivotStatementForId')->with('child_key')->andReturn($mockPivot)->once();

        $mockPivot->shouldReceive('count')->withNoArgs()->andReturn(0, 0, 0, 1)->times(4);

        verify(IndirectBase::attachAncestry($parent, $child, $relation))->isEmpty();
    }
}

class Indirect extends IndirectBase {}
