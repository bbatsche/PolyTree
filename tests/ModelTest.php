<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Model as BaseModel;
use PHPUnit_Framework_TestCase as TestCase;

class ModelTest extends TestCase
{
    public function testRelationshipAliases()
    {
        $model = new TestWrapperModel;

        verify($model->parents())->isInstanceOf('BeBat\PolyTree\Relations\HasParents');
        verify($model->children())->isInstanceOf('BeBat\PolyTree\Relations\HasChildren');
        verify($model->ancestors())->isInstanceOf('BeBat\PolyTree\Relations\HasAncestors');
        verify($model->descendants())->isInstanceOf('BeBat\PolyTree\Relations\HasDescendants');
    }
}

class TestWrapperModel extends BaseModel {}
