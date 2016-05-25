<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

abstract class Direct extends BelongsToMany
{
    public function __construct(Node $node, $foreignKey, $otherKey)
    {
        $table = $node->getRelationsTable();
        $query = $node->newQuery();

        parent::__construct($query, $node, $table, $foreignKey, $otherKey);
    }
}
