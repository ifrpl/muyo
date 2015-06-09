<?php

namespace Tests\Model\Array_;

class M00 extends \Lib_Model_Array
{
    public function init()
    {
        parent::init();

        $this->schemaColumnsSet([
            'field0' => [],
            'field1' => [],
            'field2' => [],
            'field3' => [],
            'field4' => [],
        ]);

        $this->field0 = 1;
        $this->field1 = 'a';
        $this->field2 = ['a', 'b'];
        $this->field3 = new \Object();
        $this->field4 = [
            new \Object(),
            new \Object()
        ];
    }

}