<?php

namespace Tests\TestCase\Model;


/**
 * Field and settings management
 *  - accessors
 *  - serialization
 *  - default, unique properties
 *
 * Class c00
 * @package Tests\TestCase
 */
class c00 extends \PHPUnit_Framework_TestCase
{
    private $model = null;

    private $valuesSet = [
        [
            'field_00' => 123,
            'field_01' => 123,
            'field_02' => 123,
            'field_03' => 'qwerty',
            'field_04' => 'qwerty'
        ]
    ];

    public function __construct()
    {
        $this->model = new \Tests\Model\Mysql\M00();
    }

    protected function getColumnsWithProperty($property)
    {
        $columns = $this->model->getColumnsDefinition();

        $columns = array_filter($columns, function($def) use($property){

            if(isset($def[$property]) && $def[$property])
            {
                return true;
            }

            return false;
        });

        assertNotEquals(0, count($columns), "No column with property '$property'");

        return $columns;
    }

    /**
     * Fields access
     */
    public function test00()
    {
        foreach($this->model->getColumnsDefinition() as $column => $def)
        {
            try
            {
                $this->model->{$column};
            }
            catch(\Exception $e)
            {
                assert(false, "Could not get field '$column''");
            }

            $value = $this->valuesSet[0][$column];

            try
            {
                $this->model->{$column} = $this->valuesSet[0][$column];

                assertEquals($value, $this->model->{$column}, "Value is different for '$column'");
            }
            catch(\Exception $e)
            {
                assert(false, "Could not set value '$value' for column '$column''");
            }

        }
    }

    /**
     * Settings access
     */
    public function test01()
    {
        foreach($this->model->getColumnsDefinition() as $column => $def)
        {
            assertTrue(null != $this->model->getSetting($column), "Setting does not exist fo '$column'");
        }
    }

    /**
     * Serialization
     */
    public function test02()
	{
        foreach($this->valuesSet as $values)
        {
            $this->model->fromArray($values);

            foreach($values as $column => $value)
            {
                assertEquals($value, $this->model->{$column}, "fromArray, value is different for '$column'");
            }

            $tmp = $this->model->toArray();

            foreach($this->model->getColumnsDefinition() as $column => $def)
            {
                assertEquals($values[$column], $tmp[$column], "toArray, value is different for '$column'");
            }

        }
	}

    /**
     * Default
     */
    public function test03()
    {
        $columns = $this->getColumnsWithProperty('default');
        foreach($columns as $column => $def)
        {
            assertEquals($def['default'], $this->model->{$column}, "Default value for '$column'");
        }
    }

    /**
     * Unique
     */
    public function test04()
    {
        $columns = $this->getColumnsWithProperty('unique');
        foreach($columns as $column => $def)
        {
            assertEquals('text', $def['type'], "Unique column '$column' of type '{$def['type']}'' not managed");

            $this->model->{$column} = '';

            assertEquals(null, $this->model->{$column}, "Invalid default value for unique field '$column'");
        }
    }
}



