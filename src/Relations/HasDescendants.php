<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

class HasDescendants extends Indirect
{
    public function __construct(Node $node)
    {
        $foreignKey = $node->getAncestorKeyName();
        $otherKey   = $node->getDescendantKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    public function attach($child, array $attributes = [], $touch = true)
    {
        // Is $child already an ancestor of this node? If so, attachment would cause a cycle
        if ($child->hasDescendants()->newPivotStatementForId($this->parent->getKey())->count() > 0) {
            throw new CycleException();
        }

        if ($this->attachAncestry($this->parent, $child)) {
            parent::attach($child);
        }
    }
}
