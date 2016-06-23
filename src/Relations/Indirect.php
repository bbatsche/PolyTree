<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\LockedRelationship;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Indirect Relations
 *
 * Shared functionality for ancestor and descendant relations.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
abstract class Indirect extends BelongsToMany
{
    /** @var bool By default, do not allow this relationship to be modified in any way. */
    protected $locked = true;

    /**
     * Create a new indirect relationship instance.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     * @param string $foreignKey Column name that points back to this node.
     * @param string $otherKey   Column name that points to the other nodes in this relationship.
     */
    public function __construct(Node $node, $foreignKey, $otherKey)
    {
        $table = $node->getAncestryTable();
        $query = $node->newQuery();

        parent::__construct($query, $node, $table, $foreignKey, $otherKey);
    }

    /**
     * Unlock this relationship and allow it to be modified.
     *
     * @return self
     */
    public function unlock()
    {
        $this->locked = false;

        return $this;
    }

    /**
     * Lock this relationship and prevent any modifications.
     *
     * @return self
     */
    public function lock()
    {
        $this->locked = true;

        return $this;
    }

    /**
     * Is the relationship currently locked?
     *
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * Add a node to the indirect ancestry
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     * @param array $attributes
     * @param bool $touch
     *
     * @return void
     */
    public function attach($node, array $attributes = [], $touch = true)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        return parent::attach($node);
    }

    /**
     * Remove a node from the indirect ancestry.
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first.
     *
     * @param int|array $ids
     * @param bool $touch
     *
     * @return int Number of nodes detatched
     */
    public function detach($ids = [], $touch = true)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        return parent::detach($id, $touch);
    }

    /**
     * Generate a query builder object that joins the ancestor ids of $parent with the descendant ids of $child
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracts\Node $child
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForJoinedNodes(Node $parent, Node $child)
    {
        $query = $this->newPivotStatement()
            ->addSelect($this->getTable().'.'.$parent->getAncestorKeyName())
            ->addSelect('joined.'.$child->getDescendantKeyName());

        $query->join($this->getTable().' AS joined', function($join) use ($parent, $child)
        {
            $join->where('joined.'.$child->getAncestorKeyName(), '=', $child->getKey());
            $join->where($this->getTable().'.'.$parent->getDescendantKeyName(), '=', $parent->getKey());
        });

        $query->whereNotExists(function($q)
        {
            $grammar = $this->getBaseQuery()->getGrammar();

            $table = $grammar->wrapTable($this->getTable());

            $ancestorCol   = $grammar->wrap($this->parent->getAncestorKeyName());
            $descendantCol = $grammar->wrap($this->parent->getDescendantKeyName());

            $q->select($this->newPivotStatement()->raw(1))
                ->from($this->getTable().' AS inner')
                ->whereRaw("inner.$ancestorCol = $table.$ancestorCol")
                ->whereRaw("inner.$descendantCol = joined.$descendantCol");
        });

        return $query;
    }

    /**
     * Generate a query that joins the id of $parent with the descendant ids of $child.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracst\Node $child
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForChildDescendants(Node $parent, Node $child)
    {
        // Select all nodes that descend from $child...
        $query = $this->newPivotStatement()->where($child->getAncestorKeyName(), $child->getKey());
        // ...that aren't already descendants of $parent
        $query->whereNotIn($child->getDescendantKeyName(), function($q) use ($parent)
        {
            $q->select($parent->getDescendantKeyName())->from($this->getTable())
                ->where($parent->getAncestorKeyName(), $parent->getKey());
        });

        $query->selectRaw('?', [$parent->getKey()]);
        $query->addSelect($child->getDescendantKeyName());

        return $query;
    }

    /**
     * Generate a query that joins the id of $child with the ancestor ids of $parent
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracst\Node $child
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForParentAncestors(Node $parent, Node $child)
    {
        // Select all nodes that are ancestors of $parent...
        $query = $this->newPivotStatement()->where($parent->getDescendantKeyName(), $parent->getKey());
        // ...that aren't already ancestors of $child
        $query->whereNotIn($parent->getAncestorKeyName(), function($q) use ($child)
        {
            $q->select($child->getAncestorKeyName())->from($this->getTable())
                ->where($child->getDescendantKeyName(), $child->getKey());
        });

        $query->addSelect($parent->getAncestorKeyName());
        $query->selectRaw('?', [$child->getKey()]);

        return $query;
    }

    /**
     * Merge the ancestors of $parent and descendants of $child
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracst\Node $child
     * @return void
     */
    public function attachAncestry(Node $parent, Node $child)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        $grammar = $this->getBaseQuery()->getGrammar();

        $joinedNodesQ     = $this->getQueryForJoinedNodes($parent, $child);
        $childDescendantQ = $this->getQueryForChildDescendants($parent, $child);
        $parentAncestorQ  = $this->getQueryForParentAncestors($parent, $child);

        $fullSelect = $joinedNodesQ->unionAll($childDescendantQ)->unionAll($parentAncestorQ);

        // Insert into table...
        // ... (columns) ...
        // ... union'd select statements
        $insertSql = 'INSERT INTO ' . $grammar->wrapTable($this->getTable()) .
            ' (' . $grammar->columnize([$parent->getAncestorKeyName(), $child->getDescendantKeyName()]) . ') ' .
                $fullSelect->toSql();

        $insert = $this->newPivotStatement()->raw($insertSql);

        $this->getBaseQuery()->useWritePdo()->getConnection()->statement($insert, $fullSelect->getBindings());

        return true;
    }
}
