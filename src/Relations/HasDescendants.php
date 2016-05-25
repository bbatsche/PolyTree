<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;

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
        parent::attach($child);

        return static::attachAncestry($this->parent, $child, $this);
    }
}
