<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Debug extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $matches  = [];

	    $subject = '35fa85bdf03cfd3d5469b190a37281b3-2015-05-29_11-13-lightweight.zip';

        preg_match('#[A-Za-z0-9]+-(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2}).+#', $subject, $matches);

        $backtrace = backtrace();

        blame($backtrace);

        //debug_assert(false);

        debug_full([]);
    }
}

