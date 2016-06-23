<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

/**
 * Had Descendants Relation
 *
 * Represents a many-to-may relationship between a node and its descendant nodes.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
class HasDescendants extends Indirect
{
    /**
     * Create a new descendants relationship instance.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     */
    public function __construct(Node $node)
    {
        $foreignKey = $node->getAncestorKeyName();
        $otherKey   = $node->getDescendantKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    /**
     * Attach a descendant node
     *
     * @throws \BeBat\PolyTree\Exceptions\CycleException if $child is an existing ancestor of this node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $child
     * @param array $attributes
     * @param bool $touch
     *
     * @return void
     */
    public function attach($child, array $attributes = [], $touch = true)
    {
        // Is $child already an ancestor of this node? If so, attachment would cause a cycle
        if ($child->hasDescendants()->newPivotStatementForId($this->parent->getKey())->count() > 0) {
            throw new CycleException();
        }

        if ($this->newPivotStatementForId($child->getKey())->count() > 0) {
            return;
        }

        $this->attachAncestry($this->parent, $child);

        parent::attach($child);
    }
}
