<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Model as PolyTreeModel;

class TestModel extends PolyTreeModel
{
    public $incrementing = false;
    public $timestamps   = false;

    protected $table     = 'nodes';

    protected $guarded = [];
}
