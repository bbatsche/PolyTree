<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

class HasChildren extends Direct
{
    public function __construct(Node $node)
    {
        $foreignKey = $node->getParentKeyName();
        $otherKey   = $node->getChildKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }
}
