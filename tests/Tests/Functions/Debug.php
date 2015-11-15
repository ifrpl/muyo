<?php

namespace Tests\TestCase\Functions;

require_once 'autoload.php';

class Debug extends \PHPUnit_Framework_TestCase
{

    /**
     * variable as reference error handling
     */
    public function test01()
    {
        $sdaa = 0;

        array_shift(array_keys([1, 2]));
    }


    public function test02()
    {
        try
        {
			$ret1 = backtrace(3);

            debug_assert(false);
        }
        catch (\Exception $e)
        {

        }

        debug_full([]);
    }
}

