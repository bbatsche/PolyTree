<?php

namespace BeBat\PolyTree\Exceptions;

class Cycle extends \Exception
{
    public function __construct()
    {
        parent::__construct('Attempting to create a cycle! PolyTree nodes cannot be their own ancestor or descendant.');
    }
}
