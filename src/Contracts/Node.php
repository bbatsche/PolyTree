<?php

namespace BeBat\PolyTree\Contracts;

interface Node
{
    public function setRelationsTable($table = null);
    public function setAncestryTable($table = null);

    public function getRelationsTable();
    public function getAncestryTable();

    public function setParentKeyName($column = null);
    public function setChildKeyName($column = null);
    public function setAncestorKeyName($column = null);
    public function setDescendantKeyName($column = null);

    public function getParentKeyName();
    public function getChildKeyName();
    public function getAncestorKeyName();
    public function getDescendantKeyName();

    public function hasParents();
    public function hasChildren();
    public function hasAncestors();
    public function hasDescendants();
}
