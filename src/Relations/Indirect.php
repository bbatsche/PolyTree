<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use BeBat\PolyTree\Exceptions\Cycle as CycleException;
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

    public function attachAncestry(Node $parent, Node $child)
    {
        if ($this->isLocked()) {
            throw new LockedRelationship();
        }

        // Is $parent already a descendant of $child or is $child already an ancestor of $parent? If so, attachment would cause a cycle (logically, these two queries should be idential)
        if ($child->hasDescendants()->newPivotStatementForId($parent->getKey())->count() > 0 ||
            $parent->hasAncestors()->newPivotStatementForId($child->getKey())->count() > 0
        ) {
            throw new CycleException();
        }

        // Is $parent already an ancestor of $child or is $child already a descendant of $parent? If so, our work here is done! (again, these two queries should be the same)
        if ($child->hasAncestors()->newPivotStatementForId($parent->getKey())->count() > 0 ||
            $parent->hasDescendants()->newPivotStatementForId($child->getKey())->count() > 0
        ) {
            return false;
        }

        $grammar = $this->getBaseQuery()->getGrammar();

        $joinedNodes = $this->newPivotStatement()
            ->selectRaw($grammar->wrapTable($this->getTable()).'.'.$grammar->wrap($parent->getAncestorKeyName()))
            ->selectRaw('joined.'.$grammar->wrap($child->getDescendantKeyName()));

        $joinedNodes->join($this->getTable().' AS joined', function($join) use ($parent, $child, $grammar)
        {
            $join->where('joined.'.$child->getAncestorKeyName(), '=', $child->getKey());
            $join->where($this->getTable().'.'.$parent->getDescendantKeyName(), '=', $parent->getKey());
        });

        $joinedNodes->whereNotExists(function($q) use ($grammar)
        {
            $q->select($this->newPivotStatement()->raw(1))
                ->from($this->getTable().' AS inner')
                ->whereRaw('inner.'.$grammar->wrap($this->parent->getAncestorKeyName()).' = '.$grammar->wrapTable($this->getTable()).'.'.$grammar->wrap($this->parent->getAncestorKeyName()))
                ->whereRaw('inner.'.$grammar->wrap($this->parent->getDescendantKeyName()).' = joined.'.$grammar->wrap($this->parent->getDescendantKeyName()));
        });

        // Select all nodes that descend from $child...
        $childDescendantQ = $this->newPivotStatement()->where($child->getAncestorKeyName(), $child->getKey());
        // ...that aren't already descendants of $parent
        $childDescendantQ->whereNotIn($child->getDescendantKeyName(), function($q) use ($parent)
        {
            $q->select($parent->getDescendantKeyName())->from($this->getTable())
                ->where($parent->getAncestorKeyName(), $parent->getKey());
        });

        $childDescendantQ->selectRaw('? as ' . $grammar->wrap($parent->getAncestorKeyName()), [$parent->getKey()]);
        $childDescendantQ->addSelect($child->getDescendantKeyName());

        // Select all nodes that are ancestors of $parent...
        $parentAncestorQ = $this->newPivotStatement()->where($parent->getDescendantKeyName(), $parent->getKey());
        // ...that aren't already ancestors of $child
        $parentAncestorQ->whereNotIn($parent->getAncestorKeyName(), function($q) use ($child)
        {
            $q->select($child->getAncestorKeyName())->from($this->getTable())
                ->where($child->getDescendantKeyName(), $child->getKey());
        });

        $parentAncestorQ->addSelect($parent->getAncestorKeyName());
        $parentAncestorQ->selectRaw('? as ' . $grammar->wrap($child->getDescendantKeyName()), [$child->getKey()]);

        $fullSelect = $joinedNodes->unionAll($childDescendantQ)->unionAll($parentAncestorQ);

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
