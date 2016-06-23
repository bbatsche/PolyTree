<?php

namespace BeBat\PolyTree\Exceptions;

/**
 * Locked Relationship Exception
 *
 * By default, indirect relationships are "locked" so that they cannot be accidentally modified.
 * They must be manually "unlocked" before attach or detach can be used.
 *
 * @package BeBat\PolyTree
 * @subpackage Exceptions
 * @author Ben Batschelet <ben.batschelet@gmail.com>
 * @copyright 2016 Ben Batschelet
 * @license https://github.com/bbatsche/PolyTree/blob/master/LICENSE.md MIT License
 */
class LockedRelationship extends \Exception
{
    public function __construct()
    {
        parent::__construct('Attempting to modify an indirect relationship! PolyTree relationships can only be modified through direct parent or child relations');
    }
}
