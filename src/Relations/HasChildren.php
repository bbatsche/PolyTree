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

    public function attach($child, array $attributes = [], $touch = true)
    {
        if (!$child instanceof Node) {
            throw new \Exception("We're not quite ready to deal with this situation yet");
        }

        $connection = $this->getBaseQuery()->getConnection();

        $connection->beginTransaction();

        parent::attach($child, $attributes, $touch);

        $descendants = $this->getParent()->hasDescendants();

        $descendants->unlock();
        $descendants->attach($child);
        $descendants->lock();

        $connection->commit();
    }
}
