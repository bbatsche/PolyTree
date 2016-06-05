<?php

namespace BeBat\PolyTree\Test\Relations;

use BeBat\PolyTree\Relations\HasDescendants;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class HasDescendantsTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testConstructor()
    {
        $parentNode = Mockery::mock('BeBat\PolyTree\Model[getDescendantKeyName,getAncestorKeyName]');

        $parentNode->shouldReceive('getDescendantKeyName')->withNoArgs()->andReturn('descendant_key_name');
        $parentNode->shouldReceive('getAncestorKeyName')->withNoArgs()->andReturn('ancestor_key_name');

        $relation = new HasDescendants($parentNode);

        // Format is [table].[key_name] and we only care about the key name
        verify('foreign key is ancestor', $relation->getForeignKey())->endsWith('.ancestor_key_name');
        verify('other key is descendant', $relation->getOtherKey())->endsWith('.descendant_key_name');
    }
}
