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
}
