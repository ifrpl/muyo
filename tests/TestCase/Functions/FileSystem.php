#!/usr/bin/env php
<?php

namespace Tests\TestCase\Functions;


class FileSystem extends PHPUnit_Framework_TestCase
{
    /*
     * rglob
     */
    public function test()
    {
        $ret = rglob( ROOT_PATH . '/tests/*.php');
        assert(0 < count($ret));
    }
}

