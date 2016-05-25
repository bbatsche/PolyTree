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
}
