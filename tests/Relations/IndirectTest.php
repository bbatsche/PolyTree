<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\Indirect as IndirectBase;
use BeBat\PolyTree\Test\TestModel;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class IndirectTest extends TestCase
{
    protected $relation;
    protected $node;

    public function setUp()
    {
        $this->node = Mockery::mock('BeBat\PolyTree\Model')->makePartial();

        $this->relation = new Indirect($this->node, 'foreign_key', 'other_key');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testLocks()
    {
        verify('relation is locked by default', $this->relation->isLocked())->isTrue();

        verify('unlock is chainable',      $this->relation->unLock())->sameAs($this->relation);
        verify('relation is now unlocked', $this->relation->isLocked())->isFalse();

        verify('lock is chainable',      $this->relation->lock())->sameAs($this->relation);
        verify('relation is now locked', $this->relation->isLocked())->isTrue();
    }

    public function testAttachThrowsLockedException()
    {
        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $this->relation->attach('test');
    }

    public function testDetachThrowsLockedException()
    {
        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $this->relation->detach('test');
    }

    public function testAttachAncestryThrowsLockedException()
    {
        $this->setExpectedException('BeBat\PolyTree\Exceptions\LockedRelationship');

        $this->relation->attachAncestry($this->node, $this->node);
    }
}

class Indirect extends IndirectBase {}
