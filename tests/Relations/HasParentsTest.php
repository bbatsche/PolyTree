<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\HasParents;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class HasParentsTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testConstructor()
    {
        $childNode = Mockery::mock('BeBat\PolyTree\Model[getParentKeyName,getChildKeyName]');

        $childNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name');
        $childNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name');

        $relation = new HasParents($childNode);

        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is child', $relation->getForeignKey())->endsWith('.child_key_name');
        verify('other key is parent',  $relation->getOtherKey())->endsWith('.parent_key_name');
    }
}
