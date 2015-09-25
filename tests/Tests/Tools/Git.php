<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Git extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        \IFR\Main\Tools\Git::branchName();

        $backtrace = backtrace();
        \IFR\Main\Tools\Git::blame($backtrace);
    }
}

