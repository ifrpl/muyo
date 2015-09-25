<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Singleton extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $instance = \IFR\Main\Lib::get();
    }
}

