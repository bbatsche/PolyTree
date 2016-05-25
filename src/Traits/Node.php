<?php

namespace BeBat\PolyTree\Traits;

use BeBat\PolyTree\Relations\HasAncestors;
use BeBat\PolyTree\Relations\HasDescendants;

use BeBat\PolyTree\Relations\HasChildren;
use BeBat\PolyTree\Relations\HasParents;

trait Node
{
    protected $relationsTable = '';
    protected $ancestryTable  = '';
    protected $parentKey      = '';
    protected $childKey       = '';
    protected $ancestorKey    = '';
    protected $descendantKey  = '';

    public function getRelationsTable()
    {
        return $this->relationsTable ?: snake_case(class_basename($this)) . '_relations';
    }

    public function getAncestryTable()
    {
        return $this->ancestryTable ?: snake_case(class_basename($this)) . '_ancestry';
    }

    public function getParentKeyName()
    {
        return $this->parentKey ?: 'parent_' . snake_case(class_basename($this)) . '_id';
    }

    public function getChildKeyName()
    {
        return $this->childKey ?: 'child_' . snake_case(class_basename($this)) . '_id';
    }

    public function getAncestorKeyName()
    {
        return $this->ancestorKey ?: 'ancestor_' . snake_case(class_basename($this)) . '_id';
    }

    public function getDescendantKeyName()
    {
        return $this->descendantKey ?: 'descendant_' . snake_case(class_basename($this)) . '_id';
    }

    public function hasChildren()
    {
        return new HasChildren($this);
    }

    public function hasParents()
    {
        return new HasParents($this);
    }

    public function hasDescendants()
    {
        return new HasDescendants($this);
    }

    public function hasAncestors()
    {
        return new HasAncestors($this);
    }
}
