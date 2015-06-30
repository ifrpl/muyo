<?php

namespace Tests\Model\Mysql;

class M00 extends Abstract_
{
	public function getColumnsDefinition()
	{
		return [
            'field_00' => [
				'type'    => 'int',
			],
            'field_01' => [
                'type'    => 'int',
                'default' => 456
            ],

			'field_02' => [
				'type'    => 'text',
			],
            'field_03' => [
                'type'    => 'text',
                'default' => 'azerty'
            ],
            'field_04' => [
                'type'    => 'text',
                'unique' => true
            ],
		];
	}

}