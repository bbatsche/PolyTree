<?php

namespace BeBat\PolyTree;

use BeBat\PolyTree\Contracts\Node as NodeInterface;
use BeBat\PolyTree\Traits\Node as NodeTrait;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent implements NodeInterface
{
    use NodeTrait;

    public function parents()
    {
        return $this->hasParents();
    }

    public function children()
    {
        return $this->hasChildren();
    }

    public function descendants()
    {
        return $this->hasDescendants();
    }

    public function ancestors()
    {
        return $this->hasAncestors();
    }
}
