<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;

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
        parent::attach($parent);

        return static::attachAncestry($parent, $this->parent, $this);
    }
}
