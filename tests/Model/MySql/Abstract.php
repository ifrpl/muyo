<?php

namespace Tests\Model\Mysql;

abstract class Abstract_ extends \Lib_Model_Db_Mysql
{
	/* @var \IFR\Main\App $app */
	protected $app = null;

	/* @var \Zend_Db_Adapter_Pdo_Mysql _db */
	private $_db = null;

	static protected $DEFAULT_COLUMNS = [
        'id' => [
            'type' 		=> 'id',
            'hidden'  	=> true,
            'visible' 	=> false,
        ]
	];

	/**
	 * Returns table columns definition
	 * @return array
	 */
	abstract public function getColumnsDefinition();

	public function init()
	{
		// Parent

		$fullClassName = get_class($this);
		$fullClassName = explode("\\", $fullClassName);

		$this->_table = strtolower($fullClassName[count($fullClassName) - 1]);

		parent::init();


		//

		$this->app = \IFR\Main\App::get();


		// PDO

		$config = getConfig('./configs/application.xml', $this->app->getEnv())->db->toArray();

		$this->_db = $this->app->getMysqlDb($config);


		// Build table

        $this->_db->exec("DROP TABLE IF EXISTS {$this->_table};");

        $filePath = sprintf('./resources/sql/%s.sql', $this->_table);
        assertTrue(file_exists($filePath), "SQL file '$filePath' does not exist");

        $this->_db->exec(file_get_contents($filePath));


		// Build settings

		$columns = array_merge(
			self::$DEFAULT_COLUMNS,
            $this->getColumnsDefinition()
		);

		foreach($columns as $name => $column)
		{
			$this
				->setColumns($name)
				->schemaColumnSet($name, $column);

		}

	}

	/**
	 * @return \Zend_Db_Adapter_Pdo_Mysql
	 */
	public function getDb()
	{
		return $this->_db;
	}
}