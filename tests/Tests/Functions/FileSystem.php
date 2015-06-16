<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class FileSystem extends \PHPUnit_Framework_TestCase
{
    /*
     * rglob
     */
    public function test()
    {
        $ret = rglob( ROOT_PATH . '/*.php');
        assert(1 < count($ret));
        assertEquals('autoload.php', basename($ret[0]));

        $ret = rglob( ROOT_PATH . '/*.php', 0, RGLOB_UP);
        assert(1 < count($ret));
        assertNotEquals('autoload.php', basename($ret[0]));
        assertEquals('autoload.php', basename($ret[count($ret) - 1]));

    }
}

