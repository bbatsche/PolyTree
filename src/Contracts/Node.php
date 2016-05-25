<?php

namespace BeBat\PolyTree\Contracts;

interface Node
{
    public function getRelationsTable();
    public function getAncestryTable();

    public function getParentKeyName();
    public function getChildKeyName();
    public function getAncestorKeyName();
    public function getDescendantKeyName();

    public function hasParents();
    public function hasChildren();
    public function hasAncestors();
    public function hasDescendants();
}
