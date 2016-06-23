<?php

namespace BeBat\PolyTree;

use BeBat\PolyTree\Contracts\Node as NodeInterface;
use BeBat\PolyTree\Traits\Node as NodeTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * A Node Model
 *
 * This is a convenience class that ties together the Node Interface and Trait, so that uses can simply
 * extend this class in their models, rather than implementing the interface and using the trait.
 * It also declares some standard convention functions for accessing the node relationships.
 *
 * @package BeBat\PolyTree
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
abstract class Model extends Eloquent implements NodeInterface
{
    use NodeTrait;

    /**
     * Define a relationship to this node's parent nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasParents
     */
    public function parents()
    {
        return $this->hasParents();
    }

    /**
     * Define a relationship to this node's child nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasChildren
     */
    public function children()
    {
        return $this->hasChildren();
    }

    /**
     * Define a relationship to this node's descendant nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasDescendants
     */
    public function descendants()
    {
        return $this->hasDescendants();
    }

    /**
     * Define a relationship to this node's ancestor nodes.
     *
     * @return \BeBat\PolyTree\Relations\HasAncestors
     */
    public function ancestors()
    {
        return $this->hasAncestors();
    }
}
