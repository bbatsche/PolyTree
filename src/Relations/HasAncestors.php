<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;
use BeBat\PolyTree\Exceptions\LockedRelationship;

class HasAncestors extends Indirect
{
    public function __construct(Node $node)
    {
        $foreignKey = $node->getDescendantKeyName();
        $otherKey   = $node->getAncestorKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    public function attach($parent, array $attributes = [], $touch = true)
    {
        $descendantKey = $this->parent->getDescendantKeyName();
        $ancestorKey   = $this->parent->getAncestorKeyName();

        $grammar = $this->getBaseQuery()->getGrammar();

        $child = $this->parent;

        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        // Is $parent already a descendant of this (child) node? If so, this would cause a cycle
        if ($child->hasDescendants()->newPivotStatementForId($parent->getKey())->count() > 0) {
            throw new CycleException();
        }

        // Is parent already an ancestor to this node? If so, our work here is done!
        if ($this->newPivotStatementForId($parent->getKey())->count() > 0) {
            return;
        }

        // Select all nodes that descend from child...
        $childDescendantQ = $this->newPivotStatement()->where($ancestorKey, $child->getKey());
        // ...that don't already descend from parent
        $childDescendantQ->whereNotIn($descendantKey, function ($q) use ($descendantKey, $ancestorKey, $parent)
        {
            $q->select($descendantKey)->from($this->table)->where($ancestorKey, $parent->getKey());
        });

        $childDescendantQ->selectRaw('? as ' . $grammar->wrap($ancestorKey), [$parent->getKey()]);
        $childDescendantQ->addSelect($descendantKey);

        // Select all nodes that are ancestors of parent...
        $parentAncestorQ = $this->newPivotStatement()->where($descendantKey, $parent->getKey());
        // ...that aren't already ancestors of child
        $parentAncestorQ->whereNotIn($ancestorKey, function ($q) use ($descendantKey, $ancestorKey)
        {
            $q->select($ancestorKey)->from($this->table)->where($descendantKey, $this->parent->getKey());
        });

        $parentAncestorQ->addSelect($ancestorKey);
        $parentAncestorQ->selectRaw('? as ' . $grammar->wrap($descendantKey), [$child->getKey()]);

        $childDescendantQ->unionAll($parentAncestorQ);

        parent::attach($parent);

        $insert = $this->newPivotStatement()->raw('INSERT INTO ' . $grammar->wrapTable($this->table) . ' ' . $childDescendantQ->toSql());

        $this->getBaseQuery()->useWritePdo()->getConnection()->statement($insert, $childDescendantQ->getBindings());
    }
}
