<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\LockedRelationship;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

abstract class Indirect extends BelongsToMany
{
    protected $locked = true;

    public function __construct(Node $node, $foreignKey, $otherKey)
    {
        $table = $node->getAncestryTable();
        $query = $node->newQuery();

        parent::__construct($query, $node, $table, $foreignKey, $otherKey);
    }

    public function unlock()
    {
        $this->locked = false;

        return $this;
    }

    public function lock()
    {
        $this->locked = true;

        return $this;
    }

    public function isLocked()
    {
        return $this->locked;
    }

    public function attach($node, array $attributes = [], $touch = true)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        return parent::attach($node);
    }

    public function detach($ids = [], $touch = true)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        return parent::detach($id, $touch);
    }

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

    public function attachAncestry(Node $parent, Node $child)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        // Is $parent already an ancestor of $child or is $child already a descendant of $parent? If so, our work here is done! (again, these two queries should be the same)
        if ($child->hasAncestors()->newPivotStatementForId($parent->getKey())->count() > 0 ||
            $parent->hasDescendants()->newPivotStatementForId($child->getKey())->count() > 0
        ) {
            return false;
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
