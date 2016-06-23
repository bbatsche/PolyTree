<?php

namespace BeBat\PolyTree\Traits;

use BeBat\PolyTree\Relations\HasAncestors;
use BeBat\PolyTree\Relations\HasChildren;
use BeBat\PolyTree\Relations\HasDescendants;
use BeBat\PolyTree\Relations\HasParents;

/**
 * Node Trait
 *
 * Default implementation of PolyTree node functionality for an Eloquent model.
 *
 * @package BeBat\PolyTree
 * @subpackage Traits
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
trait Node
{
    /** @var string Hard coded name of table storing this node's direct relationships. */
    protected $relationsTable;

    /** @var string Hard coded name of table storing this node's indirect ancestry. */
    protected $ancestryTable;

    /** @var string Hard coded column name that points to this node's parent nodes. */
    protected $parentKey;

    /** @var string Hard coded column name that points to this node's child nodes. */
    protected $childKey;

    /** @var string Hard coded column name that points to this node's ancestor nodes. */
    protected $ancestorKey;

    /** @var string Hard coded column name that points to this node's descendant nodes.- */
    protected $descendantKey;

    /**
     * Override the generated table name for storing this node's direct relationships.
     *
     * Calling with no parameters will reset the value back to the default.
     *
     * @param string $table
     * @return void
     */
    public function setRelationsTable($table = null)
    {
        $this->relationsTable = $table;
    }

    /**
     * Get the table name for storing this node's direct relationships.
     *
     * Default value is [class_name]_relations
     *
     * @return string
     */
    public function getRelationsTable()
    {
        return $this->relationsTable ?: snake_case(class_basename($this)) . '_relations';
    }

    /**
     * Override the generated table name for storing this node's indirect ancestry.
     *
     * Calling with no parameters will reset the value back to the default.
     *
     * @param string $table
     * @return void
     */
    public function setAncestryTable($table = null)
    {
        $this->ancestryTable = $table;
    }

    /**
     * Get the table name for storing this node's indirect ancestry.
     *
     * Default value is [class_name]_ancestry
     *
     * @return string
     */
    public function getAncestryTable()
    {
        return $this->ancestryTable ?: snake_case(class_basename($this)) . '_ancestry';
    }

    /**
     * Override the generated column name that points to this node's parent nodes.
     *
     * Calling with no parameters will reset the value back to its default.
     *
     * @param string $column
     * @return void
     */
    public function setParentKeyName($column = null)
    {
        $this->parentKey = $column;
    }

    /**
     * Get the column name that points to this node's parent nodes.
     *
     * Default value is parent_[class_name]_id
     *
     * @return string
     */
    public function getParentKeyName()
    {
        return $this->parentKey ?: 'parent_' . snake_case(class_basename($this)) . '_id';
    }

    /**
     * Override the generated colum name that points to this node's child nodes.
     *
     * Calling with no parameters will reset the value back to its default.
     *
     * @param string $column
     * @return void
     */
    public function setChildKeyName($column = null)
    {
        $this->childKey = $column;
    }

    /**
     * Get the colum name that points to this node's child nodes.
     *
     * Default value is child_[class_name]_id
     *
     * @return string
     */
    public function getChildKeyName()
    {
        return $this->childKey ?: 'child_' . snake_case(class_basename($this)) . '_id';
    }

    /**
     * Override the generated column name that points to this node's ancestor nodes.
     *
     * Calling with no parameters will reset the value back to its default.
     *
     * @param string $column
     * @return void
     */
    public function setAncestorKeyName($column = null)
    {
        $this->ancestorKey = $column;
    }

    /**
     * Get the colum name that points to this node's ancestor nodes.
     *
     * Default value is ancestor_[class_name]_id
     *
     * @return string
     */
    public function getAncestorKeyName()
    {
        return $this->ancestorKey ?: 'ancestor_' . snake_case(class_basename($this)) . '_id';
    }

    /**
     * Override the generated column name that points to this node's descendant nodes.
     *
     * Calling with no parameters will reset the value back to its default.
     *
     * @param string $column
     * @return void
     */
    public function setDescendantKeyName($column = null)
    {
        $this->descendantKey = $column;
    }

    /**
     * Get the colum name that points to this node's descendant nodes.
     *
     * Default value is descendant_[class_name]_id
     *
     * @return string
     */
    public function getDescendantKeyName()
    {
        return $this->descendantKey ?: 'descendant_' . snake_case(class_basename($this)) . '_id';
    }

    /**
     * Define a relationship to this node's parent nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasParents
     */
    public function hasParents()
    {
        return new HasParents($this);
    }

    /**
     * Define a relationship to this node's child nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasChildren
     */
    public function hasChildren()
    {
        return new HasChildren($this);
    }

    /**
     * Define a relationship to this node's ancestor nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasAncestors
     */
    public function hasAncestors()
    {
        return new HasAncestors($this);
    }

    /**
     * Define a relationship to this node's descendant nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasDescendants
     */
    public function hasDescendants()
    {
        return new HasDescendants($this);
    }
}
