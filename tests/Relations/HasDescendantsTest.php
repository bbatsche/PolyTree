<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\HasDescendants;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class HasDescendantsTest extends TestCase
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

        $this->parentNode = Mockery::mock('BeBat\PolyTree\Model['.implode(',', $mockedFunctions).']');
        $this->childNode  = Mockery::mock('BeBat\PolyTree\Model');

        $this->zeroCount->shouldReceive('count')->andReturn(0);
        $this->oneCount->shouldReceive('count')->andReturn(1);

        $this->parentNode->shouldReceive('getDescendantKeyName')->withNoArgs()->andReturn('descendant_key_name');
        $this->parentNode->shouldReceive('getAncestorKeyName')->withNoArgs()->andReturn('ancestor_key_name');
        $this->parentNode->shouldReceive('getKey')->withNoArgs()->andReturn('parent_key');

        $this->childNode->shouldReceive('getKey')->withNoArgs()->andReturn('child_key');
        $this->childNode->shouldReceive('hasDescendants->newPivotStatementForId')
            ->andReturn($this->zeroCount)->byDefault();

        // Mock newPivotStatementForId in SUT so we can control whether this node already has a descendant
        $relationMock = 'BeBat\PolyTree\Relations\HasDescendants[newPivotStatementForId,attachAncestry]';
        $this->relation = Mockery::mock($relationMock, [$this->parentNode]);

        $this->relation->shouldReceive('newPivotStatementForId')->andReturn($this->zeroCount)->byDefault();
        $this->relation->shouldNotReceive('attachAncestry');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testConstructor()
    {
        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is ancestor', $this->relation->getForeignKey())->endsWith('.ancestor_key_name');
        verify('other key is descendant', $this->relation->getOtherKey())->endsWith('.descendant_key_name');
    }

    public function testAttachThrowsCycleException()
    {
        $this->childNode->shouldReceive('hasDescendants->newPivotStatementForId')->andReturn($this->oneCount)->once();

        $this->setExpectedException('BeBat\PolyTree\Exceptions\Cycle');

        $this->relation->attach($this->childNode);
    }

    public function testAttachDoesNothingForExistingDescendant()
    {
        $this->relation->shouldReceive('newPivotStatementForId')->andReturn($this->oneCount)->once();

        verify($this->relation->attach($this->childNode))->isEmpty();
    }
}
