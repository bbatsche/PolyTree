<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;

/**
 * Has Ancestors Relation
 *
 * Represents a many-to-many relationship between a node and its ancestor nodes.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
class HasAncestors extends Indirect
{
    /**
     * Create a new ancestors relationship instance.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     */
    public function __construct(Node $node)
    {
        $foreignKey = $node->getDescendantKeyName();
        $otherKey   = $node->getAncestorKeyName();

        parent::__construct($node, $foreignKey, $otherKey);
    }

    /**
     * Attach an ancestor node.
     *
     * @throws \BeBat\PolyTree\Exceptions\CycleException if $parent is an existing descendant of this node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param array $attributes
     * @param bool $touch
     *
     * @return void
     */
    public function attach($parent, array $attributes = [], $touch = true)
    {
        // Is $parent already a descendant of this node? If so, attachment would cause a cycle
        if ($parent->hasAncestors()->newPivotStatementForId($this->parent->getKey())->count() > 0) {
            throw new CycleException();
        }

        if ($this->newPivotStatementForId($parent->getKey())->count() > 0) {
            return;
        }

        $this->attachAncestry($parent, $this->parent);

        parent::attach($parent);
    }

    /**
     * Detach an ancestor node.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param bool $touch
     *
     * @return int Number of records deleted.
     */
    public function detach($parent = [], $touch = true)
    {
        $count = 0;

        if ($this->newPivotStatementForId($parent->getKey())->count == 0) {
            return $count;
        }

        $count += parent::detach($parent);

        $count += $this->detachAncestry($parent, $this->parent);

        return $count;
    }
}
