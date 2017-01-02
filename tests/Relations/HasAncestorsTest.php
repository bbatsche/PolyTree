<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Exceptions\Cycle as CycleException;
use BeBat\PolyTree\Model;
use BeBat\PolyTree\Relations\HasAncestors;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test behavior of HasAncestors relationship.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class HasAncestorsTest extends TestCase
{
    /**
     * Mocked child node.
     *
     * @var BeBat\PolyTree\Model
     */
    protected $childNode;

    /**
     * Mocked parent node.
     *
     * @var BeBat\PolyTree\Model
     */
    protected $parentNode;

    /**
     * Mocked HasAncestors relationship.
     *
     * We use a partial mock so we can test part of the SUT while mocking other parts.
     *
     * @var BeBat\PolyTree\Relations\HasAncestors
     */
    protected $relation;

    /**
     * Mocked object with a count() method that returns 0.
     *
     * @var Mockery\MockInterface
     */
    protected $zeroCount;

    /**
     * Mocked object with a count() method that returns 1.
     *
     * @var Mockery\MockInterface
     */
    protected $oneCount;

    /**
     * Create our mock objects and SUT.
     */
    public function setUp()
    {
        $mockedFunctions = [
            'getDescendantKeyName',
            'getAncestorKeyName',
            'getKey',
        ];

        $this->zeroCount = Mockery::mock('zeroCount');
        $this->oneCount  = Mockery::mock('oneCount');

        $this->childNode  = Mockery::mock(Model::class . '[' . implode(',', $mockedFunctions) . ']');
        $this->parentNode = Mockery::mock(Model::class);

        $this->zeroCount->shouldReceive('count')->andReturn(0);
        $this->oneCount->shouldReceive('count')->andReturn(1);

        $this->childNode->shouldReceive('getDescendantKeyName')->withNoArgs()->andReturn('descendant_key_name');
        $this->childNode->shouldReceive('getAncestorKeyName')->withNoArgs()->andReturn('ancestor_key_name');
        $this->childNode->shouldReceive('getKey')->withNoArgs()->andReturn('child_key');

        $this->parentNode->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key');
        $this->parentNode->shouldReceive('hasAncestors->newPivotStatementForId')
            ->andReturn($this->zeroCount)->byDefault();

        // Mock newPivotStatementForId in SUT so we can control whether this node already has an ancestor
        $relationMock = HasAncestors::class . '[newPivotStatementForId,attachAncestry]';

        $this->relation = Mockery::mock($relationMock, [$this->childNode]);

        $this->relation->shouldReceive('newPivotStatementForId')->andReturn($this->zeroCount)->byDefault();
        $this->relation->shouldNotReceive('attachAncestry');
    }

    /**
     * Check mock expectations.
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Test that parameters are forwarded to the parent constructor correctly.
     */
    public function testConstructor()
    {
        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is descendant', $this->relation->getForeignKey())->endsWith('.descendant_key_name');
        verify('other key is ancestor',     $this->relation->getOtherKey())->endsWith('.ancestor_key_name');
    }

    /**
     * Test that attach() throws an exception if a cycle would be created.
     */
    public function testAttachThrowsCycleException()
    {
        $this->parentNode->shouldReceive('hasAncestors->newPivotStatementForId')->andReturn($this->oneCount)->once();

        $this->setExpectedException(CycleException::class);

        $this->relation->attach($this->parentNode);
    }

    /**
     * Test that attach() does nothing else if an ancestory already exists.
     */
    public function testAttachDoesNothingForExistingAncestor()
    {
        $this->relation->shouldReceive('newPivotStatementForId')->andReturn($this->oneCount)->once();

        verify($this->relation->attach($this->parentNode))->isEmpty();
    }
}
