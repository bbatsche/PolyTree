<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

class HasParents extends Direct
{
    public function __construct(Node $node)
    {
        $foreignKey = $node->getChildKeyName();
        $otherKey   = $node->getParentKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    public function attach($parent, array $attributes = [], $touch = true)
    {
        if (!$parent instanceof Node) {
            throw new \Exception("We're not quite ready to deal with this situation yet");
        }

        $connection = $this->getBaseQuery()->getConnection();

        $connection->beginTransaction();

        parent::attach($parent, $attributes, $touch);

        $ancestors = $this->getParent()->hasAncestors();

        $ancestors->unlock();
        $ancestors->attach($parent);
        $ancestors->lock();

        $connection->commit();
    }
}
