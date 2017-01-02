<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Model;
use BeBat\PolyTree\Relations\HasChildren;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test behavior of HasChildren relationship.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class HasChildrenTest extends TestCase
{
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
        $parentNode = Mockery::mock(Model::class . '[getParentKeyName,getChildKeyName]');

        $parentNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name');
        $parentNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name');

        $relation = new HasChildren($parentNode);

        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is parent', $relation->getForeignKey())->endsWith('.parent_key_name');
        verify('other key is child',    $relation->getOtherKey())->endsWith('.child_key_name');
    }
}
