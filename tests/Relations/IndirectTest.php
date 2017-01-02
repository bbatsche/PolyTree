<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Exceptions\LockedRelationship as LockedException;
use BeBat\PolyTree\Model;
use BeBat\PolyTree\Test\TestIndirectRelation as Indirect;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test behavior of Indirect relationship.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class IndirectTest extends TestCase
{
    /** @var BeBat\PolyTree\Relations\Indirect */
    protected $relation;

    /** @var BeBat\PolyTree\Contracts\Node */
    protected $node;

    /**
     * Create the SUT.
     */
    public function setUp()
    {
        $this->node = Mockery::mock(Model::class)->makePartial();

        $this->relation = new Indirect($this->node, 'foreign_key', 'other_key');
    }

    /**
     * Check mock expectations.
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Check lock() and unlock() methods.
     */
    public function testLocks()
    {
        verify('relation is locked by default', $this->relation->isLocked())->isTrue();

        verify('unlock is chainable',      $this->relation->unlock())->sameAs($this->relation);
        verify('relation is now unlocked', $this->relation->isLocked())->isFalse();

        verify('lock is chainable',      $this->relation->lock())->sameAs($this->relation);
        verify('relation is now locked', $this->relation->isLocked())->isTrue();
    }

    /**
     * Test that attach() will throw an exception.
     */
    public function testAttachThrowsLockedException()
    {
        $this->setExpectedException(LockedException::class);

        $this->relation->attach('test');
    }

    /**
     * Test that detach() will throw an exception.
     */
    public function testDetachThrowsLockedException()
    {
        $this->setExpectedException(LockedException::class);

        $this->relation->detach('test');
    }

    /**
     * Test that attachAncetry() will throw an exception.
     */
    public function testAttachAncestryThrowsLockedException()
    {
        $this->setExpectedException(LockedException::class);

        $this->relation->attachAncestry($this->node, $this->node);
    }
}
