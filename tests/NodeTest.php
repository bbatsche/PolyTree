<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Traits\Node as NodeTrait;
use BeBat\PolyTree\Contracts\Node as NodeInterface;
use Illuminate\Database\Eloquent\Model as BaseModel;
use PHPUnit_Framework_TestCase as TestCase;

class NodeTest extends TestCase
{
    protected $model;

    protected function setUp()
    {
        $this->model = new TestModel();
    }

    public function testTableNames()
    {
        verify('generates a relation table name',  $this->model->getRelationsTable())->equals('test_model_relations');
        verify('generates an ancestry table name', $this->model->getAncestryTable())->equals('test_model_ancestry');

        $this->model->setRelationsTable('overriden_relations');
        $this->model->setAncestryTable('overriden_ancestry');

        verify('uses hard coded relation table name', $this->model->getRelationsTable())->equals('overriden_relations');
        verify('uses hard coded ancestry table name', $this->model->getAncestryTable())->equals('overriden_ancestry');
    }

    public function testColumNames()
    {
        verify('generates parent key name',     $this->model->getParentKeyname())->equals('parent_test_model_id');
        verify('generates child key name',      $this->model->getChildKeyName())->equals('child_test_model_id');
        verify('generates ancestor key name',   $this->model->getAncestorKeyName())->equals('ancestor_test_model_id');
        verify('generates descendant key name', $this->model->getDescendantKeyName())->equals('descendant_test_model_id');

        $this->model->setParentKeyName('overriden_parent_id');
        $this->model->setChildKeyName('overriden_child_id');
        $this->model->setAncestorKeyName('overriden_ancestor_id');
        $this->model->setDescendantKeyName('overriden_descendant_id');

        verify('uses had coded parent key name',     $this->model->getParentKeyname())->equals('overriden_parent_id');
        verify('uses had coded child key name',      $this->model->getChildKeyName())->equals('overriden_child_id');
        verify('uses had coded ancestor key name',   $this->model->getAncestorKeyName())->equals('overriden_ancestor_id');
        verify('uses had coded descendant key name', $this->model->getDescendantKeyName())->equals('overriden_descendant_id');
    }

    public function testRelationMethods()
    {
        verify($this->model->hasParents())->isInstanceOf('BeBat\PolyTree\Relations\HasParents');
        verify($this->model->hasChildren())->isInstanceOf('BeBat\PolyTree\Relations\HasChildren');
        verify($this->model->hasAncestors())->isInstanceOf('BeBat\PolyTree\Relations\HasAncestors');
        verify($this->model->hasDescendants())->isInstanceOf('BeBat\PolyTree\Relations\HasDescendants');
    }
}

class TestModel extends BaseModel implements NodeInterface
{
    use NodeTrait;
}
