<?php

namespace IFR\Main;

class App{

	static private $_instance = null;

	public static function get()
	{
		if(null == self::$_instance)
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

    public static function isProd()
    {
        return ENV_PRODUCTION == getCurrentEnv();
    }

    public function loadConfig($filePath)
    {
        $config = $this->_loadConfig($filePath, true);

        $localConfig = $this->_loadConfig($filePath . ".local");

        $config->merge($localConfig);

        return $config;
    }

    public function loadFile($filePath)
    {
        foreach([$filePath, $filePath . '.local'] as $filePath)
        {
            if (file_exists($filePath))
            {
                require_once $filePath;
            }
        }
    }

    private function _loadConfig($fullpath, $write = false)
    {
        if (!file_exists($fullpath))
        {
            return Zend_Config([], $write);
        }

        $extArray = explode(".", trim(strtolower($fullpath)));

        do
        {
            $ext = array_pop($extArray);
        }
        while($ext == "local");

        switch($ext)
        {
            case 'ini':
                return new Zend_Config_Ini($fullpath, null, $write);
            case 'xml':
                return new Zend_Config_Xml($fullpath, null, $write);
            case 'php':
                $data = include $fullpath;
                return new Zend_Config($data, $write);
            default:
                throw new Zend_Config_Exception("Invalid '$ext' format for config file");
        }
    }

	/**
	 * @param array $config
	 * @param bool $createDbVersion
	 * @return Zend_Db_Adapter_Pdo_Mysql
	 */
	public function getMysqlDb($config, $createDbVersion = false)
	{
		try {

			/** @var Zend_Db_Adapter_Pdo_Mysql $db */
			$db = \Zend_Db::factory( 'PDO_MYSQL',
				array(
					'dbname'   => $config[ 'db_name' ],
					'hostname' => $config[ 'db_host' ],
					'password' => $config[ 'db_password' ],
					'username' => $config[ 'db_user' ],
					'charset'  => 'utf8'
				) );
		}
		catch( \Exception $e ) {
			$this->fail( 'Could not connect to db: ' . $e->getMessage() );
		}

		if ( $createDbVersion ) {

			$tables = $db->listTables();
			if ( ! in_array( 'db_version', $tables ) ) {
				$db->exec( <<<SQL
	CREATE TABLE IF NOT EXISTS `db_version` (
		`module` CHAR(50) DEFAULT NULL,
		`version` int(11) DEFAULT 0
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL
				);
			}

			$table = $db->describeTable( 'db_version' );
			if ( ! array_key_exists( 'module', $table ) ) {
				$db->exec( <<<SQL
	ALTER TABLE `db_version`
		ADD COLUMN `module` CHAR(50) DEFAULT NULL FIRST
SQL
				);
				$db->update( 'db_version',
				array(
					'module' => 'space'
				) );
			}

			$result = $db->fetchAll( 'SHOW KEYS IN db_version WHERE Column_name = "module"' );
			if(empty($result))
			{
				$db->exec("ALTER TABLE `db_version`
					ADD UNIQUE INDEX `module` (`module`);
				");
			}

		}

		return $db;

	}

	/**
	 * @param array $config
	 *
	 * @return MongoDB
	 */
	public function getMongoDb($config)
	{
		if( class_exists( 'MongoClient' ) )
		{
			$class = 'MongoClient';
		}
		else
		{
			$class = 'Mongo';
			debug_enforce(
				class_exists( $class ),
				"No 'Mongo' class available. Make sure you've installed php mongo extension correctly."
			);
		}

		$confOptions = array();

		if (!empty($config["mongo_username"])) {
			$confOptions["username"] = $config["mongo_username"];
		}

		if (!empty($config["mongo_password"])) {
			$confOptions["password"] = $config["mongo_password"];
		}

		/** @var $_conn Mongo */
		$_conn = new $class(
			'mongodb://'.$config['mongo_host'],
			$confOptions
		);
		$dbMongo = $_conn->selectDB($config["db_name"]);

		$dbMongo->listCollections();
		return $dbMongo;
	}

	public function fail($msg=null)
	{
		logger_log( 'Error:' );
		if( $msg )
		{
			logger_log( $msg, LOG_ERR );
		}
		exit(1);
	}


}
