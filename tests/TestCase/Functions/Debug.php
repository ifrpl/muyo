<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Debug extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $ret = backtrace();

        //debug_assert(false);

        debug_full([]);
    }
}

