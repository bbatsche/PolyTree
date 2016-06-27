<?php

namespace BeBat\PolyTree\Test;

use BeBat\PolyTree\Model as PolyTreeModel;

class TestModel extends PolyTreeModel
{
    protected $table = 'nodes';
    protected $relationsTable = 'relations';
    protected $ancestryTable  = 'ancestry';

    protected $parentKey     = 'parent_id';
    protected $childKey      = 'child_id';
    protected $ancestorKey   = 'ancestor_id';
    protected $descendantKey = 'descendant_id';

    protected $guarded = [];

    public $incrementing = false;
}
