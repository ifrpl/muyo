<?php

namespace IFR\Main;

/**
 * @package App
 *
 * @property int id
 */
abstract class Model implements \Iterator
{
	const SETTING_SET       = 'set';
	const SETTING_GET       = 'get';

	const SETTING_CALLBACK	= 'callback';
    const SETTING_DEFAULT   = 'default';
    const SETTING_FORM_TYPE = 'formType';
    const SETTING_FORM_ATTRIBS = 'formAttribs';
    const SETTING_HIDDEN    = 'hidden';
    const SETTING_LABEL     = 'label';
    const SETTING_MULTI_OPTIONS = 'multiOptions';
    const SETTING_REQUIRED  = 'required';
	const SETTING_TITLE     = 'title';
	const SETTING_TYPE      = 'type';
    const SETTING_VIRTUAL   = 'virtual';

    const TYPE_HIDDEN   = 'hidden';
    const TYPE_ID       = 'id';

    const TYPE_INT      = 'int';

	const TYPE_BOOLEAN      = 'bool';


	const TYPE_DATE         = 'date';
	const TYPE_DATETIME     = 'datetime';
	const TYPE_TIMESTAMP    = 'timestamp';
	const TYPE_TIME         = 'time';

    const FORM_TYPE_SELECT = 'select';
	const FORM_TYPE_HIDDEN = 'hidden';

    const COL_ID = 'id';


	const SERIALIZATION_MODEL   = 'model';
	const SERIALIZATION_ID      = 'id';

    /**
     * @deprerated
     */
    const INVALID_SELECT_VALUE_LABEL = '#INVALID!';

    /**
     * @deprerated
     */
    const INVALID_REF_LABEL          = '#REF!';


	/**
	 * @var array field type identifiers
	 * @see $this->settingEmptyEqNull after modification
	 */
	static $types = array(
		'select', //TODO: refactor to enum
		'date','datetime','time',
		'float',
		'int',self::TYPE_ID,
		'bool','boolean',
		'currency','monetary',
		'string','text','email','host',
		'country',
		'object',
		'array'
	);

	/**
	 * Name of primary key
	 * @var string|array
	 */
	protected $_primaryKey = null;

	/**
	 * Data of model
	 * @var array
	 */
	protected $_data = array();

	/**
	 * DataGrid settings
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * We're gonna moving $_settings here in the future (settings should be used for currently used settings).
	 * @var array
	 */
	protected $_settingsDefault = array();

	/**
	 * DataGrid settings corresponding to joined columns
	 *
	 * @var array array[column_alias==setting_name] = setting_value
	 */
	protected $_settingsJoined = array();

	/**
	 * @var array
	 */
	private $_settingsTable = array();

	/**
	 * Define cols which should be protected before access
	 */
	protected $_protected = array();

	/**
	 * @var array
	 */
	private $changeRecordData = array();

	/**
	 * @var array
	 */
	private $_validationErrors = array();

	/**
	 * @var bool Unserilize models on load
	 */
	private $_unserilizeModels = true;

	/**
	 * Return row object for current id
	 * @return $this
	 */
	abstract public function getRow();

	/**
	 * @return $this
	 */
	abstract public function debug();

	abstract protected function isColumnSetLocally($name);
	abstract public function clearColumns($clearPK = false);
	abstract public function getAlias();

	public static function getConstants($prefix)
	{
		$r = new \ReflectionClass(get_called_class());

		$constants = $r->getConstants();

		$keys = array_flip(
			array_filter(
				array_keys($constants),
				function($key) use($prefix)
				{
					return 0 === strpos($key, $prefix);
				}
			)
		);

		return array_intersect_key($constants,$keys);
	}

	/**
	 * @param array|int|null $options
	 */
	public function __construct($options = null, $init = true)
	{
		if($init)
		{
			$this->init();
		}

		if ( is_array($options) )
		{
			$this->fromArray($options);
		}
		elseif( !is_null($options) )
		{
			$this->{$this->getPrimaryKey()} = $options;
			$this->getRow();
		}
	}

	/**
	 * @param string $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->propertySet( $name, $value );
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->propertyGet( $name );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->propertyExists($name);
	}

	/**
	 * @return array
	 */
	public function __sleep()
	{
		return array('_data', '_settings');
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	public function init()
	{
	}

	/**
	 * @param string $name
	 * @param array $setting
	 * @param mixed $defaultValue
	 */
	protected function schemaColumnApplyDefault(&$name, &$setting, &$defaultValue)
	{
		if( $name == $this->getPrimaryKey())
		{
			array_set_default($setting, self::SETTING_TYPE, self::TYPE_INT);
			array_set_default($setting, self::SETTING_FORM_TYPE, self::TYPE_HIDDEN);
		}

	}

	/**
	 * @param string $name
	 * @param array $settings
	 * @param mixed $defaultValue
	 * @return $this
	 */
	protected function schemaColumnSet($name,$settings,$defaultValue=null)
	{
		debug_enforce(is_string($name) && is_array($settings), 'Invalid parameters') ;

		if(is_null($defaultValue) && array_key_exists('default', $settings))
		{
			$defaultValue = array_get_unset($settings,'default');
		}

		$this->schemaColumnApplyDefault($name,$settings,$defaultValue);

		$this->addSetting($name,$settings);
		$this->settingDefaultSet($name,$settings);
		if( !$this->getSetting( $name, self::SETTING_VIRTUAL ) )
		{
			$this->recordColumnSet( $name, $defaultValue );
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return array
	 */
	protected function schemaColumnGet($name)
	{
		return $this->getSetting($name);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	protected function schemaColumnExists($name)
	{
		return $this->settingExists($name);
	}

	/**
	 * @param array $columns
	 * @return $this
	 * @see schemaColumnSet
	 */
	protected function schemaColumnsSet($columns)
	{
		foreach($columns as $name => $data)
		{
			$default = array_get_unset($data,'default');
			$this->schemaColumnSet($name,$data,$default);
		}
		return $this;
	}

	/**
	 * FIXME: doesn't care about default value.
	 * @return array
	 */
	protected function schemaColumnsGet()
	{
		return $this->settingsDefaultGet();
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function _recordAccess($name)
	{
		return debug_assert(is_string($name) && !empty($name),'Invalid column name');
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function recordColumnGet($name)
	{
		return $this->_recordAccess($name) && isset($this->_data[$name]) ? $this->_data[$name] : null;
	}

	/**
	 * @param string $name
	 * @param $value
	 * @return $this
	 */
	public function recordColumnSet($name, $value)
	{
		if( $this->_recordAccess($name) )
		{
			$this->_data[$name] = $value;
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function recordColumnExists($name)
	{
		return array_key_exists($name,$this->_data);
	}

	/**
	 * @return array
	 */
	public function recordColumnsGet()
	{
		$data = array();
		foreach($this->_data as $name => $value)
		{
			if( !$this->recordColumnProtected($name) )
			{
				$data[$name] = $value;
			}
		}
		return $data;
	}

	/**
	 * @param array $array
	 * @return $this
	 */
	public function recordColumnsSet($array)
	{
		$this->_data = $array;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function recordColumnCurrent()
	{
		return current($this->_data);
	}

	/**
	 * @return string
	 */
	public function recordColumnKey()
	{
		return key($this->_data);
	}

	/**
	 * @return mixed
	 */
	public function recordColumnNext()
	{
		return next($this->_data);
	}

	public function recordColumnRewind()
	{
		reset($this->_data);
	}

	/**
	 * @param string $propertyName
	 *
	 * @return string
	 */
	protected function getMethodSufixForProperty($propertyName)
	{
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
	}

	/**
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->_primaryKey;
	}

	/**
	 * @return bool
	 */
	public function recordExists()
	{
		$name = $this->getPrimaryKey();
		if( $this->propertyExists( $name ) )
		{
			$val = $this->propertyGet( $name );
			$ret = !empty( $val );
		}
		else
		{
			$ret = false;
		}
		return $ret;
	}

	/**
	 * Set new settings array
	 *
	 * @param array $settings
	 * @return $this
	 */
	public function setSettings(array $settings)
	{
		$this->_settings = $settings;
		return $this;
	}

	/**
	 * Merge new settings array with current settings array
	 *
	 * @param array $settings
	 * @return $this
	 */
	public function addSettings(array $settings)
	{
		$this->_settings = array_merge($this->_settings, $settings);
		return $this;
	}

	/**
	 * Add setting array by $name or merge if $name exists in settings array
	 *
	 * @param string $name
	 * @param mixed $settings
	 * @param string $key
	 * @return $this
	 */
	public function addSetting($name, $settings, $key = null)
	{
		$value = $this->settingExists($name,$key) ? $this->getSetting($name, $key) : null;

		if(is_array($settings))
		{
			$settings = array_merge((array)$value, $settings);
		}

		if($key)
		{
			$this->_settings[$name][$key] = $settings;
		}
		else
		{
			$this->_settings[$name] = $settings;
		}

		return $this;
	}

	/**
	 * @param string $name_after
	 * @param string $name_setting
	 * @param array  $setting
	 * @throws \Exception
	 * @return $this
	 */
	public function addSettingAfter($name_after, $name_setting, $setting)
	{
		$s = $this->getSettings();
		$this->setSettings(array());
		$modified = false;
		foreach($s as $on => $os)
		{
			$this->addSetting($on, $os);
			if ($on === $name_after)
			{
				$this->addSetting($name_setting, $setting);
				$modified = true;
			}
		}
		if (!$modified)
		{
			throw new \Exception( "Tried to insert setting ${name_setting} after ${name_after}, which doesn't exists" );
		}
		return $this;
	}

	/**
	 * Get settings data that has been set manually.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return $this->_settings;
	}

	/**
	 * @param string|array $key
	 * @param mixed $value
	 * @return $this
	 * @throws \Exception
	 */
	public function settingsSet( $key, $value )
	{
		arrayize($key);
		debug_enforce( array_all_dg(is_type_dg('string')), "Invalid setting key ".var_dump_human_compact($key) );
		$current = &$this->_settings;
		while( !empty($key) )
		{
			debug_enforce_type( $current, 'array' );
			$part = array_shift( $key );
			$current = &$current[$part];
		}
		$current = $value;
		return $this;
	}

	/**
	 * @param string|array $key
	 * @return mixed
	 * @throws \Exception
	 */
	public function settingsGet( $key=[] )
	{
		arrayize($key);
		debug_enforce( array_all_dg(is_type_dg('string')), "Invalid setting key ".var_dump_human_compact($key) );
		$current = &$this->_settings;
		while( !empty($key) )
		{
			debug_enforce_type( $current, 'array' );
			$part = array_shift( $key );
			$current = &$current[$part];
		}
		return $current;
	}

	public function getSettingsJoined()
	{
		return $this->_settingsJoined;
	}

	/**
	 * @return array
	 */
	public function settingsDefaultGet()
	{
		return $this->_settingsDefault;
	}

	/**
	 * @param string $name
	 *
	 * @return array
	 */
	public function settingDefaultGet($name)
	{
		return $this->_settingsDefault[$name];
	}

	/**
	 * @param string $name
	 * @param array $setting
	 *
	 * @return $this
	 */
	public function settingDefaultSet($name, $setting)
	{
		$this->_settingsDefault[$name] = $setting;
		return $this;
	}

	/**
	 * Get settings that have corresponding (internal or external) columns set.
	 *
	 * @return array
	 */
	public function getSettingsSetGlobally()
	{
		$ret = array();
		foreach( $this->getSettings() as $name => $value )
		{
			if( $this->isColumnSetLocally($name) )
			{
				$ret[$name] = $value;
			}
		}
		foreach( $this->_settingsJoined as $alias => $value )
		{
			if( debug_assert(!array_key_exists($alias,$ret),'Joined setting with the same alias defined as local one.') )
			{
				$ret[$alias] = $value;
			}
		}
		return $ret;
	}

	/**
	 * @param string $name Checks if native setting exists
	 * @param string|null $key
	 *
	 * @return bool
	 */
	public function settingExists($name, $key=null)
	{
		if( is_string($name) )
		{
			$ret = array_key_exists($name,$this->_settings);

			if( $ret && $key !== null )
			{
				$ret = array_key_exists( $key, $this->_settings[ $name ] );
			}
		}
		else
		{
			$ret = false;
		}
		return $ret;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function settingExistsJoined($name)
	{
		return array_key_exists($name,$this->_settingsJoined);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function settingExistsGlobally($name)
	{
		return $this->settingExists($name) || $this->settingExistsJoined($name);
	}

	/**
	 * Get settings data by $name or null if $name not exists in settings array
	 *
	 * @param string $name
	 * @param null|string $key
	 * @return mixed
	 */
	public function getSetting($name, $key = null)
	{
		if( isset($this->_settings[$name]) )
		{
			if($key)
			{
				if(isset($this->_settings[$name][$key]))
				{
					return $this->_settings[$name][$key];
				}
			}
			else
			{
				return $this->_settings[$name];
			}
		}
		return null;
	}

	/**
	 * Get joined settings data by $name or null if $name not exists in joined settings array
	 *
	 * @param string $name
	 * @param null|string $key
	 * @return mixed
	 */
	public function getSettingJoined($name, $key = null)
	{
		if( debug_assert($this->settingExistsJoined($name),'Tried to retrieve not existing joined setting.') )
		{
			if($key)
			{
				if(isset($this->_settingsJoined[$name][$key]))
				{
					return $this->_settingsJoined[$name][$key];
				}
			}
			else
			{
				return $this->_settingsJoined[$name];
			}
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param null|string $key
	 * @return mixed
	 */
	public function getSettingGlobal($name, $key = null)
	{
		if( $this->settingExists($name) )
		{
			return $this->getSetting($name,$key);
		}
		else
		{
			return $this->getSettingJoined($name,$key);
		}
	}

	/**
	 * @param string $name
	 */
	public function removeSetting($name)
	{
		if( debug_assert(array_key_exists($name, $this->_settings)) )
		{
			unset($this->_settings[$name]);
		}
	}

	/**
	 * @param string $column
	 * @return mixed|null
	 */
	public function getDefaultValueForColumn($column)
	{
		if( $this->settingExists($column) )
		{
			$ret = $this->getSetting($column, 'default');
		}
		else
		{
			$ret = null;
		}
		return $ret;
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function isRequiredValue($column)
	{
		if( $this->settingExists($column) )
		{
			$ret = (bool) $this->getSetting($column, 'required');
		}
		else
		{
			$ret = false;
		}
		return $ret;
	}

	/**
	 * Get element label from settings array or default value
	 *
	 * @param string $elementName
	 * @return string
	 */
	public function getElementLabel($elementName)
	{
		if($label = $this->getSetting($elementName, 'label'))
		{
			return (string)$label;
		}
		return ucwords(str_replace('_', ' ', $elementName));
	}

	/**
	 * Get element type from settings array, if not exist type is "string"
	 *
	 * @param string $elementName
	 * @return string
	 */
	public function getElementType($elementName)
	{
		if( $this->settingExists($elementName) ? $type = $this->getSetting($elementName, 'type') : false )
		{
			return (string)$type;
		}
		return 'string';
	}

	/**
	 * Is element hidden
	 *
	 * @param $elementName
	 * @return bool
	 */
	public function isElementHidden($elementName)
	{
		if( $this->settingExists($elementName) ? $settings = $this->getSetting($elementName) : false)
		{
			if(isset($settings[self::SETTING_HIDDEN]) && $settings[self::SETTING_HIDDEN] == true)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $column
	 * @param mixed $value
	 * @return $this
	 */
	public function propertySet( $column, $value )
	{
		debug_enforce( !empty($column), "Cannot set value of empty property" );
		if( $column == self::COL_ID && $this->getPrimaryKey())
		{
			$column = $this->getPrimaryKey();
		}
		if(
			debug_assert(
				$this->schemaColumnExists( $column ),
				"Schema required to set ".var_dump_human_compact($column)." in ".var_dump_human_compact(get_called_class())
			)
		)
		{
			if( $this->settingExists( $column, self::SETTING_SET ) )
			{
				call_user_func(
					$this->getSetting( $column, self::SETTING_SET ),
					$value
				);
			}
			else
			{
				$method = 'set' . $this->getMethodSufixForProperty($column);
				if ( method_exists($this, $method) )
				{
					$this->$method($value);
				}
				else
				{
					$this->recordColumnSet( $column, $value );
				}
			}
		}
		return $this;
	}

	/**
	 * @param string|callable $column
	 * @param mixed $value
	 * @return callable
	 */
	public function propertySetDg( $column, $value )
	{
		if( is_string($column) )
		{
			debug_enforce( $this->schemaColumnExists( $column ), "Cannot set non-existant column ".var_dump_human_compact($column) );
			$column = return_dg( $column );
		}
		else
		{
			debug_enforce_type( $column, 'callable' );
		}
		return function()use($column,$value)
		{
			$args = func_get_args();
			return $this->propertySet(
				call_user_func_array( $column, $args ),
				call_user_func_array( $value, $args )
			);
		};
	}

	/**
	 * @param string $column
	 * @return mixed
	 */
	public function propertyGet( $column )
	{
		debug_enforce( !empty($column), "Cannot get name of empty property" );
		if( $column == self::COL_ID && $this->getPrimaryKey() )
		{
			$column = $this->getPrimaryKey();
		}
		if(
			debug_assert(
				$this->schemaColumnExists( $column ),
				"Schema required to get ".var_dump_human_compact($column)." in ".var_dump_human_compact(get_called_class())
			)
		)
		{
			if( $this->settingExists( $column, self::SETTING_GET ) )
			{
				$ret = call_user_func( $this->getSetting( $column, self::SETTING_GET ) );
			}
			else
			{
				$method = 'get' . $this->getMethodSufixForProperty($column);
				if( method_exists($this, $method) )
				{
					$ret = $this->$method();
				}
				else
				{
					if( $this->recordColumnExists($column) )
					{
						$ret = $this->recordColumnGet($column);
					}
					else
					{
						$ret = $this->getDefaultValueForColumn( $column );
					}
				}
			}
		}
		else
		{
			$ret = null;
		}
		return $ret;
	}

	/**
	 * @param string|callable $column
	 * @return mixed
	 * @throws \Exception
	 */
	public function propertyGetDg($column)
	{
		if( is_string($column) )
		{
			debug_enforce( $this->schemaColumnExists($column), "Cannot set non-existant column ".var_dump_human_compact($column) );
			$column = return_dg( $column );
		}
		else
		{
			debug_enforce_type( $column, 'callable' );
		}
		return function()use($column)
		{
			$args = func_get_args();
			return $this->propertyGet(
				call_user_func_array( $column, $args )
			);
		};
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function propertyExists($column)
	{
		debug_enforce( !empty($column), "Cannot check if empty property exists" );
		if( $column == self::COL_ID && $this->getPrimaryKey())
		{
			$column = $this->getPrimaryKey();
		}

		// Watch out for this incompatible change
		return $this->schemaColumnExists($column);
	}

	/**
	 * @param string|callable $column
	 * @return callable
	 */
	public function propertyExistsDg($column)
	{
		if( is_string($column) )
		{
			$column = return_dg( $column );
		}
		return function()use($column)
		{
			$args = func_get_args();
			return $this->propertyExists(
				call_user_func_array( $column, $args )
			);
		};
	}

	/**
	 * Same as $this->$column = $value
	 * @param string $column
	 * @param mixed $value
	 * @return $this
	 */
	public function store($column, $value)
	{
		$this->$column = $value;
		return $this;
	}

	/**
	 * Same as $target = $this->column
	 *
	 * @param string $column
	 * @param mixed &$target
	 *
	 * @return $this
	 */
	public function read($column, &$target)
	{
		$target = $this->$column;
		return $this;
	}

	/**
	 * @static
	 * @param bool $resetSettings
	 * @return static
	 */
	public static function find($resetSettings = false)
	{
		/** @var Model $ret */
		$ret = new static(null, !$resetSettings);
		if( $resetSettings )
		{
			$ret->setSettings(array());
		}
		return $ret;
	}

	/**
	 * @param $column
	 * @return bool
	 */
	private function _changedColumnAccess($column)
	{
		return
			debug_assert(
				is_string($column) && !empty($column)
				,'Invalid changed column name'
			)
			&&
			array_key_exists($column,$this->changeRecordData)
			/*
			debug_assert(
				array_key_exists($column,$this->changeRecordData),
				"Trying to retrieve original record {$column} value but no stored change exists"
			)*/
		;
	}

	/**
	 * @return $this
	 */
	public function changedColumnsReset()
	{
		$this->changeRecordData = $this->recordColumnsGet();
		return $this;
	}

	/**
	 * Returns whether record has been change since the last save.
	 *
	 * @param string $column
	 * @return bool
	 */
	public function changedColumnIs($column)
	{
		if( !$this->recordExists() )
		{
			return true;
		}
		elseif( $this->_recordAccess($column) && $this->_changedColumnAccess($column) )
		{
			return $this->recordColumnGet($column) != $this->changeRecordData[$column];
		}
		else
		{
			return true;
		}
	}

	/**
	 * Return original value of the record
	 * @param string $column
	 *
	 * @return mixed
	 */
	public function changedColumnGet($column)
	{
		if( $this->recordExists() && $this->_changedColumnAccess($column) )
		{
			return $this->changeRecordData[$column];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Return original values of model
	 *
	 * @return array
	 */
	public function changedColumnsGet()
	{
		return $this->changeRecordData;
	}

	/**
	 * @return array
	 */
	public function changedColumnsDiffGet()
	{
		return array_chain(
			$this->recordColumnsGet(),
			array_filter_key_dg(
				function ($val, $name)
				{
					return $this->changedColumnIs($name);
				}
			),
			array_map_val_dg(
				function ($val, $name)
				{
					$valueFrom = $this->changedColumnGet($name);
					$valueTo = $val;

					return [ $valueFrom, $valueTo ];
				}
			)
		);
	}

	/**
	 * @param array $row
	 * @return Model
	 */
	protected function modelFactory($row)
	{
		/** @var Model $model */
		$model = new static();

        try
        {
            $model->unserializeContent($row);
            return $model;
        }
        catch(\Exception $e)
        {
            $class = get_class($model);
            \IFR\Main\Logger::error(new \Exception("Unable to unserialize object '{$class}", 0, $e));
        }

        return null;
	}

	/**
	 * @param array $row
	 * @return Model
	 */
	public static function modelFactory_s($row)
	{
		/** @var Model $model */
		$model = new static();
		return $model->modelFactory($row);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function recordColumnProtected($name)
	{
		return in_array($name, $this->_protected);
	}

	public function settingsClear()
	{
		$this->_settings = array();
	}

	/**
	 * @param string $name
	 * @param array $value
	 * @return $this
	 */
	protected function settingJoin($name, $value)
	{
		if( debug_assert(!array_key_exists($name,$this->_settingsJoined),"Tried to join setting `{$name}` which already exists.") )
		{
			$this->_settingsJoined[$name] = $value;
		}
	}

	/**
	 * @param Model $from
	 */
	protected function settingsJoin(Model $from)
	{
		foreach( $from->_settingsJoined as $name => $value )
		{ // join external from externals perspective
			$this->settingJoin($name, $value);
		}

		$fromAlias = $from->getAlias();
		foreach( $from->getColumns() as $descriptor )
		{ // join local from externals perspective
			$table = $descriptor[0];
			$column = $descriptor[1];
			$alias = $descriptor[2];

			if( $table === $fromAlias )
			{
				if( $alias )
				{ // has alias, just join
					if( $from->settingExists($alias) )
					{
						$this->settingJoin($alias,$from->getSetting($alias));
					}
					elseif( $from->settingExists($column) )
					{
						$this->settingJoin($alias,$from->getSetting($column));
					}
				}
				elseif( $from->settingExists($column) )
				{ // needs to be aliased
					$this->settingJoin("{$table}.{$column}",$from->getSetting($column));
				}
			}
		}
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @param bool $unique
	 * @return bool
	 */
	public function settingIsNull($type, $value, $unique = null)
	{
		return (!in_array($type, ['bool','boolean','string','text']) || $unique) && $value === '';
	}

	/**
	 * @param bool|null $on
	 *
	 * @return $this
	 */
	public function settingsNewInterface($on = true)
	{
		$this->clearColumns();
		if( debug_assert( true === $on,"Turning off new setting interface not supported yet") )
		{
			if( empty($this->_settingsDefault) )
			{
				$default = self::get(false)->getSettings();
				debug_assert(0 === count(array_diff_key($this->getSettings(),$default)),"Trying to use new settings interface on model with modified settings");
				$this->_settingsDefault = $default;
				$this->setSettings(array());
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function settingsTableGet()
	{
		return $this->_settingsTable;
	}

	/**
	 * @param array $settings
	 * @return $this;
	 */
	public function settingsTableSet($settings = array())
	{
		$this->_settingsTable = $settings;
		return $this;
	}

	/**
	 * @param string $key
	 * @return null
	 */
	public function settingTableGet( $key )
	{
		$settings = $this->settingsTableGet();
		if( debug_assert( array_key_exists($key,$settings), "Setting ".var_dump_human_compact($key)." doesn't exists in ".var_dump_human_compact(array_keys($settings)) ) )
		{
			$ret = $settings[ $key ];
		}
		else
		{
			$ret = null;
		}
		return $ret;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function settingTableSet($key, $value)
	{
		$this->_settingsTable[ $key ] = $value;
		return $this;
	}

	public function serialize()
	{
		$content = array(
			self::SERIALIZATION_MODEL => get_class($this),
		);

		$value = $this->_getValueByType($this->{self::COL_ID}, self::TYPE_ID);
		if(!is_null($value))
		{
			$content[self::SERIALIZATION_ID] = $value;
		}

		return $content;
	}

	static public function unserialize($data)
	{
		debug_enforce(isset($data[self::SERIALIZATION_MODEL]));

		debug_enforce(class_exists($data[self::SERIALIZATION_MODEL]));

		$instance = call_user_func(
			[
				$data[self::SERIALIZATION_MODEL],
				'getById'
			],
			isset($data[self::SERIALIZATION_ID]) ? $data[self::SERIALIZATION_ID] : 0
		);

		return $instance;
	}

	/**
	 * @return array
	 */
	public function serializeContent()
	{
		$array = array();
		foreach($this->recordColumnsGet() as $key => $value)
		{
			if( !$this->recordColumnProtected($key) )
			{
				$array[$key] = $value;
			}
		}

		$data = array();
		foreach($array as $key => $value)
		{
			if($value instanceof Model)
			{
				$value = $value->serialize();
			}

			if(is_array($value) || is_object($value))
			{
				$value = serialize($value);
			}

			if( $this->settingExists($key) ? $setting = $this->getSetting($key) : false )
			{
				$type = 'text';
				if( isset($setting['type']) )
				{
					$type = $setting['type'];
				}

				$value = $this->_getValueByType($value, $type);

				if( isset($setting['unique']) && $setting['unique'] == true && empty($value) )
				{
					$value = null;
				}

				if(is_null($value) && isset($setting['default']))
				{
					$value = $setting['default'];
				}
			}

			$data[$key] = $value;
		}
		return $data;
	}

	/**
	 * @param $value
	 * @param $type
	 *
	 * @return bool|float|int|mixed|null
	 */
	protected function _getValueByType($value, $type)
	{
		if( !is_null($value) )
		{
			switch( $type )
			{
				case self::TYPE_ID:
					if( array_contains( array('',null), $value, true ) )
					{
						$value = null;
					}
					else
					{
						$value = intval( $value );
					}
				break;
				case "bool":
				case "boolean":
					$value = (bool) $value;
				break;
				case "int":
					$value = (int) $value;
					break;
				case "float":
					$value = (float) $value;
				break;
				case "object":
				case "array":
					if(is_object($value) || is_array($value))
					{
						$value = serialize($value);
					}
				break;
				case "date":
					if($value == '0000-00-00' || $value == '')
					{
						$value = null;
					}
				break;
				case "time":
					if($value == '00:00:00' || $value == '')
					{
						$value = null;
					}
				break;
				case "datetime":
					if($value == '0000-00-00 00:00:00' || $value == '')
					{
						$value = null;
					}
				break;
				case "text":
				default:
					$value = (string) $value;
				break;
			}
		}

		return $value;
	}

	/**
	 * Unserlize models on load
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function setUnserializeModels($value = true)
	{
		$this->_unserilizeModels = $value;
		return $this;
	}

	/**
	 * @param array $data
	 */
	public function unserializeContent($data)
	{
		$array = array();
		foreach($data as $key => $value)
		{
			if( !in_array($key, $this->_protected) )
			{
				$array[$key] = $value;
			}
		}

		foreach($array as $key => $value)
		{
			if( $this->settingExists($key) ? $setting = $this->getSetting($key) : false )
			{
				$type = 'text';
				if( isset($setting['type']) )
				{
					$type = $setting['type'];
				}

				if( !is_null($value) )
				{
					switch($type)
					{
						case "bool":
						case "boolean":
							$value = (bool) $value;
							break;
						case "int":
							$value = (int) $value;
							break;
						case "float":
							$value = (float) $value;
							break;
						case "object":
						case "array":
							if(!is_object($value) && !is_array($value))
							{
								$value = unserialize($value);
							}
							break;
						case "date":
							if($value == '0000-00-00' || $value == '')
							{
								$value = null;
							}
							break;
						case "time":
							if($value == '00:00:00' || $value == '')
							{
								$value = null;
							}
							break;
						case "datetime":
							if($value == '0000-00-00 00:00:00' || $value == '')
							{
								$value = null;
							}
							break;
						case "text":
						default:
							$value = (string) $value;
							break;
					}
				}

				if( isset($setting['unique']) && $setting['unique'] == true && empty($value) )
				{
					$value = null;
				}
			}

			if($this->_unserilizeModels && is_array($value) && array_key_exists('model', $value))
			{
				$class = $value['model'];

				if(array_key_exists('data', $value))
				{
					/** @var Model $value */
					$obj = new $class();
					$obj->unserializeContent($value['data']);
					$value = $obj;
				}
				elseif(array_key_exists(self::COL_ID, $value))
				{
					/** @var Model $value */
					$value = $class::getById($value[self::COL_ID]);
				}

			}

			$this->recordColumnSet($key,$value);
		}
	}

	/**
	 * @param array $array
	 * @param bool $strict Populate only existing data array
	 * @return $this
	 */
	public function fromArray(array $array, $strict = false)
	{
		foreach($array as $key => $value)
		{
			if( !$strict || ($strict && $this->recordColumnExists($key)) )
			{
				if( $this->settingExistsGlobally($key) &&
					$this->settingIsNull($this->getSettingGlobal($key,'type'), $value, $this->getSettingGlobal($key,'unique')) )
				{
					$value = null;
				}
				$this->recordColumnSet($key,$value);
			}
		}
//		$this->changedColumnsReset();
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$data = array();
		foreach($this->recordColumnsGet() as $name => $value)
		{
			if( !$this->recordColumnProtected($name) )
			{
				$data[$name] = $value;
			}
		}

		return $data;
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		$class = get_called_class();
		if( $this->recordExists() )
		{
			$data = var_dump_human_compact( $this->recordColumnsGet() );
			return $class.'{'.$data.'}';
		}
		else
		{
			$query = '';
			return $class.'{'.$query.'}';
		}
	}

	/**
	 * @return string
	 */
	public function toJson()
	{
		return \Zend_Json::encode($this->toArray());
	}

	/**
	 * @param callable $interceptor
	 * @return $this
	 */
	public function tap( $interceptor )
	{
		$interceptor($this);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		return $this->recordColumnCurrent();
	}

	/**
	 * @return mixed|void
	 */
	public function next()
	{
		return $this->recordColumnNext();
	}

	/**
	 * @return mixed
	 */
	public function key()
	{
		return $this->recordColumnKey();
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$key = $this->key();
		return $this->recordColumnExists( $key );
	}

	public function rewind()
	{
		$this->recordColumnRewind();
	}

    public function getDereferencedSettingName($name)
    {
        $type = $this->getSetting($name, self::SETTING_TYPE);
        debug_enforce(self::TYPE_ID == $type);

        return str_replace('_id', '', $name);
    }

}