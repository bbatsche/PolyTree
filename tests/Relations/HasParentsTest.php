<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Model;
use BeBat\PolyTree\Relations\HasParents;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test behavior of HasParents relationship.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class HasParentsTest extends TestCase
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
        $childNode = Mockery::mock(Model::class . '[getParentKeyName,getChildKeyName]');

        $childNode->shouldReceive('getParentKeyName')->withNoArgs()->andReturn('parent_key_name');
        $childNode->shouldReceive('getChildKeyName')->withNoArgs()->andReturn('child_key_name');

        $relation = new HasParents($childNode);

        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is child', $relation->getForeignKey())->endsWith('.child_key_name');
        verify('other key is parent',  $relation->getOtherKey())->endsWith('.parent_key_name');
    }
}
