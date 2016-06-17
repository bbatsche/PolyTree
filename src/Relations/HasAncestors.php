<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

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
        // Is $parent already a descendant of this node? If so, attachment would cause a cycle
        if ($parent->hasAncestors()->newPivotStatementForId($this->parent->getKey())->count() > 0) {
            throw new CycleException();
        }

        if ($this->attachAncestry($parent, $this->parent)) {
            parent::attach($parent);
        }
    }
}
