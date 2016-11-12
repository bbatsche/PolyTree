<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\LockedRelationship;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;

/**
 * Indirect Relations.
 *
 * Shared functionality for ancestor and descendant relations.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 *
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
     * @param string                         $foreignKey Column name that points back to this node
     * @param string                         $otherKey   Column name that points to the other nodes in this relationship
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
     * Add a node to the indirect ancestry.
     *
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     * @param array                          $attributes
     * @param bool                           $touch
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first
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
     *
     * @param int|array $ids
     * @param bool      $touch
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first
     *
     * @return int Number of nodes detached
     */
    public function detach($ids = [], $touch = true)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        return parent::detach($ids, $touch);
    }

    /**
     * Generate a query builder object that joins the ancestor ids of $parent with the descendant ids of $child.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracts\Node $child
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForJoinedNodes(Node $parent, Node $child)
    {
        $query = $this->newPivotStatement()
            ->addSelect($this->getTable() . '.' . $parent->getAncestorKeyName())
            ->addSelect('joined.' . $child->getDescendantKeyName());

        $query->join($this->getTable() . ' AS joined', function ($join) use ($parent, $child) {
            $join->where('joined.' . $child->getAncestorKeyName(), '=', $child->getKey());
            $join->where($this->getTable() . '.' . $parent->getDescendantKeyName(), '=', $parent->getKey());
        });

        $query->whereNotExists(function ($q) {
            $grammar = $this->getBaseQuery()->getGrammar();

            $table = $grammar->wrapTable($this->getTable());

            $ancestorCol   = $grammar->wrap($this->parent->getAncestorKeyName());
            $descendantCol = $grammar->wrap($this->parent->getDescendantKeyName());

            $q->select($this->newPivotStatement()->raw(1))
                ->from($this->getTable() . ' AS inner')
                ->whereRaw("inner.$ancestorCol = $table.$ancestorCol")
                ->whereRaw("inner.$descendantCol = joined.$descendantCol");
        });

        return $query;
    }

    /**
     * Generate a query that joins the id of $parent with the descendant ids of $child.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracts\Node $child
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForParentDescendants(Node $parent, Node $child)
    {
        // Select all nodes that descend from $child...
        $query = $this->newPivotStatement()->where($child->getAncestorKeyName(), $child->getKey());
        // ...that aren't already descendants of $parent
        $query->whereNotIn($child->getDescendantKeyName(), function ($q) use ($parent) {
            $q->select($parent->getDescendantKeyName())->from($this->getTable())
                ->where($parent->getAncestorKeyName(), $parent->getKey());
        });

        $query->selectRaw('?', [$parent->getKey()]);
        $query->addSelect($child->getDescendantKeyName());

        return $query;
    }

    /**
     * Generate a query that joins the id of $child with the ancestor ids of $parent.
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracts\Node $child
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQueryForChildAncestors(Node $parent, Node $child)
    {
        // Select all nodes that are ancestors of $parent...
        $query = $this->newPivotStatement()->where($parent->getDescendantKeyName(), $parent->getKey());
        // ...that aren't already ancestors of $child
        $query->whereNotIn($parent->getAncestorKeyName(), function ($q) use ($child) {
            $q->select($child->getAncestorKeyName())->from($this->getTable())
                ->where($child->getDescendantKeyName(), $child->getKey());
        });

        $query->addSelect($parent->getAncestorKeyName());
        $query->selectRaw('?', [$child->getKey()]);

        return $query;
    }

    /**
     * Merge the ancestors of $parent and descendants of $child.
     *
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracts\Node $child
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first
     */
    public function attachAncestry(Node $parent, Node $child)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        $grammar = $this->getBaseQuery()->getGrammar();

        $joinedNodesQ      = $this->getQueryForJoinedNodes($parent, $child);
        $parentDescendantQ = $this->getQueryForParentDescendants($parent, $child);
        $childAncestorQ    = $this->getQueryForChildAncestors($parent, $child);

        $fullSelect = $joinedNodesQ->unionAll($parentDescendantQ)->unionAll($childAncestorQ);

        // Insert into table...
        // ... (columns) ...
        // ... union'd select statements
        $insertSql = 'INSERT INTO ' . $grammar->wrapTable($this->getTable()) .
            ' (' . $grammar->columnize([$parent->getAncestorKeyName(), $child->getDescendantKeyName()]) . ') ' .
            $fullSelect->toSql();

        $insert = $this->newPivotStatement()->raw($insertSql);

        $this->getBaseQuery()->useWritePdo()->getConnection()->statement($insert, $fullSelect->getBindings());
    }

    /**
     * Appends a condition to $query for finding nodes that are either
     * descendants of $parent or descendants of $parent's ancestors.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \BeBat\PolyTree\Contracts\Node     $parent
     */
    public function appendParentAncestorCondition(Builder $query, Node $parent)
    {
        $query->where(function ($parentQ) use ($parent) {
            $parentQ->orWhere($parent->getAncestorKeyName(), $parent->getKey());
            $parentQ->orWhereIn($parent->getAncestorKeyName(), function ($ancestorQ) use ($parent) {
                $ancestorQ->select($parent->getAncestorKeyName())->from($this->getTable())
                    ->where($parent->getDescendantKeyName(), $parent->getKey());
            });
        });
    }

    /**
     * Appends a condition to $query for finding nodes that are either
     * ancestors of $child or ancestors of $child's descendants.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \BeBat\PolyTree\Contracts\Node     $child
     */
    public function appendChildDescendantCondition(Builder $query, Node $child)
    {
        $query->where(function ($childQ) use ($child) {
            $childQ->orWhere($child->getDescendantKeyName(), $child->getKey());
            $childQ->orWhereIn($child->getDescendantKeyName(), function ($descendantQ) use ($child) {
                $descendantQ->select($child->getDescendantKeyName())->from($this->getTable())
                    ->where($child->getAncestorKeyName(), $child->getKey());
            });
        });
    }

    /**
     * Appends a condition to $query for filtering out nodes that are either
     * $parent's direct children or descendants of those children.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \BeBat\PolyTree\Contracts\Node     $parent
     */
    public function appendParentsChildrenNegation(Builder $query, Node $parent)
    {
        $parentsChildrenQ = $parent->hasChildren()->getBaseQuery()->select($parent->getKeyName());

        $query->whereNotIn($parent->getDescendantKeyName(), $parentsChildrenQ);
        $query->whereNotIn($parent->getDescendantKeyName(), function ($childDescendantQ) use ($parent, $parentsChildrenQ) {
            $childDescendantQ->select($parent->getDescendantKeyName())->from($this->getTable())
                ->whereIn($parent->getAncestorKeyName(), $parentsChildrenQ);
        });
    }

    /**
     * Appends a condition to $query for filtering out nodes that are either
     * $child's direct parents or ancestors of those parents.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \BeBat\PolyTree\Contracts\Node     $child
     */
    public function appendChildsParentsNegation(Builder $query, Node $child)
    {
        $childsParentsQ = $child->hasParents()->getBaseQuery()->select($child->getKeyName());

        $query->whereNotIn($child->getAncestorKeyName(), $childsParentsQ);
        $query->whereNotIn($child->getAncestorKeyName(), function ($parentAncestorQ) use ($child, $childsParentsQ) {
            $parentAncestorQ->select($child->getAncestorKeyName())->from($this->getTable())
                ->whereIn($child->getDescendantKeyName(), $childsParentsQ);
        });
    }

    /**
     * Remove $parent's ancestors from $child, and $child's descendants from $parent.
     *
     *
     * @param \BeBat\PolyTree\Contracts\Node $parent
     * @param \BeBat\PolyTree\Contracts\Node $child
     *
     * @throws \BeBat\PolyTree\Exceptions\LockedRelationship if this relationship has not been unlocked first
     *
     * @return int Number of rows deleted
     */
    public function detachAncestry(Node $parent, Node $child)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        $delQuery = $this->newPivotStatement();

        $this->appendParentAncestorCondition($delQuery, $parent);
        $this->appendChildDescendantCondition($delQuery, $child);
        $this->appendParentsChildrenNegation($delQuery, $parent);
        $this->appendChildsParentsNegation($delQuery, $child);

        return $delQuery->delete();
    }
}
