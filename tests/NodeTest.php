<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Relations;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test methods built into the Node trait.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class NodeTest extends TestCase
{
    /** @var BeBat\PolyTree\Test\TestModel */
    protected $model;

    /**
     * Instantiate our test model and hard code its table name.
     */
    protected function setUp()
    {
        $this->model = new TestModel();
        $this->model->setTable('nodes');
    }

    /**
     * Test methods for getting & setting related table names.
     */
    public function testTableNames()
    {
        verify('generates a relation table name',  $this->model->getRelationsTable())->equals('node_relations');
        verify('generates an ancestry table name', $this->model->getAncestryTable())->equals('node_ancestry');

        $this->model->setRelationsTable('overriden_relations');
        $this->model->setAncestryTable('overriden_ancestry');

        verify('uses hard coded relation table name', $this->model->getRelationsTable())->equals('overriden_relations');
        verify('uses hard coded ancestry table name', $this->model->getAncestryTable())->equals('overriden_ancestry');
    }

    /**
     * Test methods for getting & setting related table column names.
     */
    public function testColumNames()
    {
        verify('generates parent key name',     $this->model->getParentKeyname())->equals('parent_node_id');
        verify('generates child key name',      $this->model->getChildKeyName())->equals('child_node_id');
        verify('generates ancestor key name',   $this->model->getAncestorKeyName())->equals('ancestor_node_id');
        verify('generates descendant key name', $this->model->getDescendantKeyName())->equals('descendant_node_id');

        $this->model->setParentKeyName('overriden_parent_id');
        $this->model->setChildKeyName('overriden_child_id');
        $this->model->setAncestorKeyName('overriden_ancestor_id');
        $this->model->setDescendantKeyName('overriden_descendant_id');

        verify('uses had coded parent key name',     $this->model->getParentKeyname())->equals('overriden_parent_id');
        verify('uses had coded child key name',      $this->model->getChildKeyName())->equals('overriden_child_id');
        verify('uses had coded ancestor key name',   $this->model->getAncestorKeyName())->equals('overriden_ancestor_id');
        verify('uses had coded descendant key name', $this->model->getDescendantKeyName())->equals('overriden_descendant_id');
    }

    /**
     * Test methods for getting underlying relationships.
     */
    public function testRelationMethods()
    {
        verify($this->model->hasParents())->isInstanceOf(Relations\HasParents::class);
        verify($this->model->hasChildren())->isInstanceOf(Relations\HasChildren::class);
        verify($this->model->hasAncestors())->isInstanceOf(Relations\HasAncestors::class);
        verify($this->model->hasDescendants())->isInstanceOf(Relations\HasDescendants::class);
    }
}
