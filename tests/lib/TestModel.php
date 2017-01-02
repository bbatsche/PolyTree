<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Model as PolyTreeModel;

/**
 * Concrete model for use in testing.
 *
 * @package BeBat\PolyTree
 * @subpackage Test
 */
class TestModel extends PolyTreeModel
{
    public $incrementing = false;
    public $timestamps   = false;

    protected $table     = 'nodes';

    protected $guarded = [];
}
