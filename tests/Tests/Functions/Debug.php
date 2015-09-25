<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Debug extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        try
        {
            debug_assert(false);
        }
        catch (\Exception $e)
        {

        }

        debug_full([]);
    }
}

