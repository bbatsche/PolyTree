<?php

namespace BeBat\PolyTree\Relations;

use BeBat\PolyTree\Contracts\Node;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Direct Relations
 *
 * Shared functionality for parent and child relations.
 *
 * @package BeBat\PolyTree
 * @subpackage Relations
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
abstract class Direct extends BelongsToMany
{
    /**
     * Create a new direct relationship instance.
     *
     * @param \BeBat\PolyTree\Contracts\Node $node
     * @param string $foreignKey Column name that points back to this node.
     * @param string $otherKey   Column name that points to the other nodes in this relationship.
     */
    public function __construct(Node $node, $foreignKey, $otherKey)
    {
        $table = $node->getRelationsTable();
        $query = $node->newQuery();

        parent::__construct($query, $node, $table, $foreignKey, $otherKey);
    }
}
