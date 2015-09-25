<?php

namespace Tests\Model\Array_;

class M01 extends \Lib_Model_Array
{
    public function init()
    {
        parent::init();

        $this->schemaColumnsSet([
            'field0' => [],
            'field1' => []
        ]);

        $this->field0 = new M00();
        $this->field1 = [
            new M00(),
            new M00()
        ];
    }

}