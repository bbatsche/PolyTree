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
}

class Indirect extends IndirectBase {}
