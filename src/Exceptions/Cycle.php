<?php

namespace BeBat\PolyTree\Exceptions;

/**
 * Cycle Exception.
 *
 * A given node cannot be attached as its own ancestor or descendant as this would create a cycle in the graph/tree
 *
 * @package BeBat\PolyTree
 * @subpackage Exceptions
 *
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
class Cycle extends \Exception
{
    public function __construct()
    {
        parent::__construct('Attempting to create a cycle! PolyTree nodes cannot be their own ancestor or descendant.');
    }
}
