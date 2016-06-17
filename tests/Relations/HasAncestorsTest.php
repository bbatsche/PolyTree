<?php

namespace BeBat\PolyTree\Test\Relations;

use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class HasAncestorsTest extends TestCase
{
    protected $childNode;
    protected $parentNode;
    protected $relation;

    protected $zeroCount;
    protected $oneCount;

    public function setUp()
    {
        $mockedFunctions = [
            'getDescendantKeyName',
            'getAncestorKeyName',
            'getKey'
        ];

        $this->zeroCount = Mockery::mock('zeroCount');
        $this->oneCount  = Mockery::mock('oneCount');

        $this->childNode  = Mockery::mock('BeBat\PolyTree\Model['.implode(',', $mockedFunctions).']');
        $this->parentNode = Mockery::mock('BeBat\PolyTree\Model');

        $this->zeroCount->shouldReceive('count')->andReturn(0);
        $this->oneCount->shouldReceive('count')->andReturn(1);

        $this->childNode->shouldReceive('getDescendantKeyName')->withNoArgs()->andReturn('descendant_key_name');
        $this->childNode->shouldReceive('getAncestorKeyName')->withNoArgs()->andReturn('ancestor_key_name');
        $this->childNode->shouldReceive('getKey')->withNoArgs()->andReturn('child_key');

        $this->parentNode->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key');
        $this->parentNode->shouldReceive('hasAncestors->newPivotStatementForId')
            ->andReturn($this->zeroCount)->byDefault();

        // Mock newPivotStatementForId in SUT so we can control whether this node already has an ancestor
        $relationMock = 'BeBat\PolyTree\Relations\HasAncestors[newPivotStatementForId]';
        $this->relation = Mockery::mock($relationMock, [$this->childNode]);

        $this->relation->shouldReceive('newPivotStatementForId')->andReturn($this->zeroCount)->byDefault();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testConstructor()
    {
        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is descendant', $this->relation->getForeignKey())->endsWith('.descendant_key_name');
        verify('other key is ancestor',     $this->relation->getOtherKey())->endsWith('.ancestor_key_name');
    }

    public function testThrowsCycleException()
    {
        $this->parentNode->shouldReceive('hasAncestors->newPivotStatementForId')->andReturn($this->oneCount)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\Cycle');

        $this->relation->attach($this->parentNode);
    }

    public function testDoesNothingForExistingAncestor()
    {
        $this->relation->shouldReceive('newPivotStatementForId')->andReturn($this->oneCount)->once();

        verify($this->relation->attach($this->parentNode))->isEmpty();
    }
}
