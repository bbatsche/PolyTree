<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\HasChildren;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class HasChildrenTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testConstructor()
    {
        $parentNode = Mockery::mock('BeBat\PolyTree\Model[getParentKeyName,getChildKeyName]');

        $parentNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name');
        $parentNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name');

        $relation = new HasChildren($parentNode);

        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is parent', $relation->getForeignKey())->endsWith('.parent_key_name');
        verify('other key is child',    $relation->getOtherKey())->endsWith('.child_key_name');
    }
}
