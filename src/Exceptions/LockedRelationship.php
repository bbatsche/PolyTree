<?php

namespace BeBat\PolyTree\Exceptions;

class LockedRelationship extends \Exception
{
    public function __construct()
    {
        parent::__construct('Attempting to modify an indirect relationship! PolyTree relationships can only be modified through direct parent or child relations');
    }
}
