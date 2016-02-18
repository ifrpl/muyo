<?php


namespace IFR\Main\Model\Db;

abstract class Sqlite
	extends Mysql
{
    /** @var \Zend_Db_Adapter_Pdo_Sqlite  $pdo */
    static protected $adapter = null;

    public function __construct($options = null, $init = true)
    {
        $name = explode("\\", get_class($this));
        $this->_table = strtolower(array_pop($name));

        parent::__construct($options, $init);
    }

    /**
     * @return \Zend_Db_Adapter_Pdo_Abstract
     */
    public function getDb()
	{
        if(null == self::$adapter)
        {
            self::$adapter = new \Zend_Db_Adapter_Pdo_Sqlite([
                'dbname' => $this->getDbFilePath()
            ]);
        }

		return self::$adapter;
	}

    abstract protected function getDbFilePath();

}