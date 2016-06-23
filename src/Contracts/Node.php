<?php

namespace BeBat\PolyTree\Contracts;

/**
 * Node Interface
 *
 * Any model which is part of a PolyTree must implement this interface.
 *
 * @package BeBat\PolyTree
 * @subpackage Contracts
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
interface Node
{
    /**
     * Set the table name for storing this node's direct relationships.
     *
     * @param string $table
     * @return void
     */
    public function setRelationsTable($table = null);

    /**
     * Set the table name for storing this node's indirect ancestry.
     *
     * @param string $table
     * @return void
     */
    public function setAncestryTable($table = null);

    /**
     * Get the table name for storing this node's direct relationships.
     *
     * @return string
     */
    public function getRelationsTable();

    /**
     * Get the table name for storing this node's indirect ancestry.
     *
     * @return string
     */
    public function getAncestryTable();

    /**
     * Set the column name that points to this node's parent nodes.
     *
     * @param string $column
     * @return void
     */
    public function setParentKeyName($column = null);

    /**
     * Set the column name that points to this node's child nodes.
     *
     * @param string $column
     * @return void
     */
    public function setChildKeyName($column = null);

    /**
     * Set the column name that points to this node's ancestor nodes.
     *
     * @param string $column
     * @return void
     */
    public function setAncestorKeyName($column = null);

    /**
     * Set the column name that points to this node's descendant nodes.
     *
     * @param string $column
     * @return void
     */
    public function setDescendantKeyName($column = null);

    /**
     * Get the column name that points to this node's parent nodes.
     *
     * @return string
     */
    public function getParentKeyName();

    /**
     * Get the column name that points to this node's child nodes.
     *
     * @return string
     */
    public function getChildKeyName();

    /**
     * Get the column name that points to this node's ancestor nodes.
     *
     * @return string
     */
    public function getAncestorKeyName();

    /**
     * Get the column name that points to this node's descendant nodes.
     *
     * @return string
     */
    public function getDescendantKeyName();

    /**
     * Define a relationship to this node's parent nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasParents
     */
    public function hasParents();

    /**
     * Define a relationship to this node's child nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasChildren
     */
    public function hasChildren();

    /**
     * Define a relationship to this node's ancestor nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasAncestors
     */
    public function hasAncestors();

    /**
     * Define a relationship to this node's descendant nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasDescendants
     */
    public function hasDescendants();
}
