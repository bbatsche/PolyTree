<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\HasAncestors;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class HasAncestorsTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testConstructor()
    {
        $childNode = Mockery::mock('BeBat\PolyTree\Model[getDescendantKeyName,getAncestorKeyName]');

        $childNode->shouldReceive('getDescendantKeyName')->withNoArgs()->andReturn('descendant_key_name');
        $childNode->shouldReceive('getAncestorKeyName')->withNoArgs()->andReturn('ancestor_key_name');

        $relation = new HasAncestors($childNode);

        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is descendant', $relation->getForeignKey())->endsWith('.descendant_key_name');
        verify('other key is ancestor',     $relation->getOtherKey())->endsWith('.ancestor_key_name');
    }
}
