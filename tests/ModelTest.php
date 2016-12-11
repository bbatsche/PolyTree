<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Relations;
use PHPUnit_Framework_TestCase as TestCase;

class ModelTest extends TestCase
{
    public function testRelationshipAliases()
    {
        $model = new TestModel();

        verify($model->parents())->isInstanceOf(Relations\HasParents::class);
        verify($model->children())->isInstanceOf(Relations\HasChildren::class);
        verify($model->ancestors())->isInstanceOf(Relations\HasAncestors::class);
        verify($model->descendants())->isInstanceOf(Relations\HasDescendants::class);
    }
}
