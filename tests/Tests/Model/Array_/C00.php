<?php

namespace Tests\TestCase\Model\Array_;

require_once 'autoload.php';

/**
 * Serialization
 *
 * @package Tests\TestCase
 */
class C00 extends \PHPUnit_Framework_TestCase
{
    public function test0()
    {
        /* @var \IFR\Main\Model $model */
        $model = new \Tests\Model\Array_\M00();

        $data = $model->serializeContent();
    }

    public function test1()
    {
        /* @var \IFR\Main\Model $model */
        $model = new \Tests\Model\Array_\M01();

        $data = $model->serializeContent();
    }

}



