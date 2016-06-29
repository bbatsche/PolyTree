<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

/**
 * Has Parents Relation
 *
 * Represents a many-to-many relationship between a node and its parent nodes.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
class HasParents extends Direct
{
    /**
     * Create a new parents relationship instance.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     */
    public function __construct(Node $node)
    {
        $foreignKey = $node->getChildKeyName();
        $otherKey   = $node->getParentKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    /**
     * Attach a parent node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param array $attributes
     * @param bool $touch
     *
     * @return void
     */
    public function attach($parent, array $attributes = [], $touch = true)
    {
        $connection = $this->getBaseQuery()->getConnection();

        $connection->beginTransaction();

        if (is_array($parent) || $parent instanceof \Traversable) {
            foreach ($parent as $node) {
                $this->attach($node, $attributes, $touch);
            }

            $connection->commit();

            return;
        }

        if (!$parent instanceof Node) {
            $parent = $this->parent->replicate()->setAttribute($this->parent->getKeyName(), $parent)->syncOriginal();
        }

        $ancestors = $this->getParent()->hasAncestors();

        $ancestors->unlock();
        $ancestors->attach($parent);
        $ancestors->lock();

        parent::attach($parent, $attributes, $touch);

        $connection->commit();
    }

    /**
     * Detach a parent node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param bool $touch
     *
     * @return The number of records deleted.
     */
    public function detach($parent = [], $touch = true)
    {
        $count = 0;

        $connection = $this->getBaseQuery()->getConnection();

        $connection->beginTransaction();

        if ($parent == []) {
            throw new \Exception('Not quite ready to handle this yet.');
        }

        if (is_array($parent) | $parent instanceof \Traversable) {
            foreach ($parent as $node) {
                $count += $this->detach($node, $touch);
            }

            $connection->commit();

            return $count;
        }

        if (!$parent instanceof Node) {
            $parent = $this->parent->replicate()->setAttribute($this->parent->getKeyName(), $parent)->syncOriginal();
        }

        $count += parent::detach($parent, $touch);

        $descendants = $this->parent->hasDescendants();

        $descendants->unlock();
        $count += $descendants->detach($parent);
        $descendants->lock();

        $connection->commit();

        return $count;
    }
}
