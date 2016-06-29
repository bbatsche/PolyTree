<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

/**
 * Has Children Relation
 *
 * Represents a many-to-many relationship between a node and its child nodes.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
class HasChildren extends Direct
{
    /**
     * Create a new children relationship instance.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     */
    public function __construct(Node $node)
    {
        $foreignKey = $node->getParentKeyName();
        $otherKey   = $node->getChildKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    /**
     * Attach a child node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $child
     * @param array $attributes
     * @param string $touch
     * @return void
     */
    public function attach($child, array $attributes = [], $touch = true)
    {
        $connection = $this->getBaseQuery()->getConnection();

        $connection->beginTransaction();

        if (is_array($child) || $child instanceof \Traversable) {
            foreach ($child as $node) {
                $this->attach($node, $attributes, $touch);
            }

            $connection->commit();

            return;
        }

        if (!$child instanceof Node) {
            $child = $this->parent->replicate()->setAttribute($this->parent->getKeyName(), $child)->syncOriginal();
        }

        $descendants = $this->getParent()->hasDescendants();

        $descendants->unlock();
        $descendants->attach($child);
        $descendants->lock();

        parent::attach($child, $attributes, $touch);

        $connection->commit();
    }

    /**
     * Detach a child node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $child
     * @param bool $touch
     *
     * @return int The number of records deleted.
     */
    public function detach($child = [], $touch = true)
    {
        $count = 0;

        $connection = $this->getBaseQuery()->getConnection();

        $connection->beginTransaction();

        if ($child == []) {
            throw new \Exception('Not quite ready to handle this yet.');
        }

        if (is_array($child) | $child instanceof \Traversable) {
            foreach ($child as $node) {
                $count += $this->detach($node, $touch);
            }

            $connection->commit();

            return $count;
        }

        if (!$child instanceof Node) {
            $child = $this->parent->replicate()->setAttribute($this->parent->getKeyName(), $child)->syncOriginal();
        }

        $count += parent::detach($child, $touch);

        $descendants = $this->parent->hasDescendants();

        $descendants->unlock();
        $count += $descendants->detach($child);
        $descendants->lock();

        $connection->commit();

        return $count;
    }
}
