<?php

/**
 * @package App
 *
 * @property int id
 */
abstract class Lib_Model implements Iterator
{
	const SETTING_TYPE='type';

	/**
	 * @var array field type identifiers
	 * @see $this->settingEmptyEqNull after modification
	 */
	static $types = array(
		'select', //TODO: refactor to enum
		'date','datetime','time',
		'float',
		'int','id',
		'bool','boolean',
		'currency','monetary',
		'string','text','email','host',
		'country',
		'object'
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
	 * Return row object for current id
	 * @return $this
	 */
	abstract public function getRow();

	/**
	 * @return $this
	 */
	abstract public function debug();

	/**
	 * @param array|int|null $options
	 */
	public function __construct($options = null)
	{
		$this->init();

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
		if( $name === $this->getPrimaryKey() )
		{
			array_set_default($setting,'type','int');
			array_set_default($setting,'hidden','true');
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
		if( debug_assert(is_string($name) && is_array($settings),'Invalid parameters') )
		{
			$this->schemaColumnApplyDefault($name,$settings,$defaultValue);

			$this->_data[$name] = $defaultValue;
			$this->addSetting($name,$settings);
			$this->settingDefaultSet($name,$settings);
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
		return
			debug_assert(is_string($name) && !empty($name),'Invalid column name')
//			&& debug_assert($this->recordColumnExists($name),"Column {$name} not exists in schema.")
		;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	protected function recordColumnGet($name)
	{
		return $this->_recordAccess($name) ? $this->_data[$name] : null;
	}

	/**
	 * @param string $name
	 * @param $value
	 * @return $this
	 */
	protected function recordColumnSet($name, $value)
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
	protected function recordColumnExists($name)
	{
		return array_key_exists($name,$this->_data);
	}

	/**
	 * @return array
	 */
	protected function recordColumnsGet()
	{
		return $this->_data;
	}

	/**
	 * @param $array
	 */
	protected function recordColumnsSet($array)
	{
		$this->_data = $array;
	}

	/**
	 * @return bool|array
	 */
	public function isValid()
	{
		$isValid = true;

		$form = $this->getForm();

		foreach($this->recordColumnsGet() as $column => $value)
		{
			$element = $form->getElement($column);
			if($element && !$element->isValid($value))
			{
				$isValid = false;
				$this->_validationErrors[$element->getId()] = $element->getErrors();
			}
		}

		return $isValid;
	}

	/**
	 * @return array
	 */
	public function getValidationErrors()
	{
		return $this->_validationErrors;
	}

	/**
	 * @return array
	 */
	public function getImportConfig()
	{
		return array();
	}

	/**
	 * @param App_Import $import
	 * @return string String contains MySQL statements for import
	 */
	public function getImportQuery($import)
	{
		return '';
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
	 * @param string $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		if( $name == 'id' && $this->getPrimaryKey())
		{
			$name = $this->getPrimaryKey();
		}

		$method = 'set' . $this->getMethodSufixForProperty($name);
		if ( method_exists($this, $method) )
		{
			$this->$method($value);
		}
		elseif( $this->recordColumnExists($name) )
		{
			$this->recordColumnSet($name,$value);
		}
		else
		{
			debug_assert( false,"Cannot set {$name} to {$value}." );
		}
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if( $name == 'id' && $this->getPrimaryKey())
		{
			$name = $this->getPrimaryKey();
		}

		$method = 'get' . $this->getMethodSufixForProperty($name);
		if( method_exists($this, $method) )
		{
			return $this->$method();
		}
		elseif( $this->recordColumnExists($name) )
		{
			return $this->recordColumnGet($name);
		}
		debug_assert( false, 'Unknown getter '.$name.' @'.get_called_class() );
		return null;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		if( $name == 'id' && $this->getPrimaryKey())
		{
			$name = $this->getPrimaryKey();
		}

		$exists = $this->recordColumnExists($name);
		$tmp = $exists ? $this->recordColumnGet($name) : null;
		if( !empty($tmp) )
		{
			return true;
		}
		else
		{
			$method = 'get' . $this->getMethodSufixForProperty($name);
			if(method_exists($this, $method))
			{
				$result = $this->__get($name);
				return !empty($result);
			}
		}

		return false;
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
		if($this->getPrimaryKey())
		{
			return isset($this->{$this->_primaryKey});
		}
		return false;
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
	 * @throws Lib_Exception
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
			throw new Lib_Exception("Tried to insert setting ${name_setting} after ${name_after}, which doesn't exists");
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
		$ret = array_key_exists($name,$this->_settings);

		if( $ret && $key !== null )
		{
			return array_key_exists( $key, $this->_settings[ $name ] );
		}
		else
		{
			return $ret;
		}
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
			if(isset($settings['hidden']) && $settings['hidden'] == true)
			{
				return true;
			}
		}
		return false;
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
		/** @var Lib_Model $ret */
		$ret = new static();
		if( $resetSettings )
		{
			$ret->setSettings(array());
		}
		return $ret;
	}

	/**
	 * @param $column
	 *
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
			debug_assert(
				array_key_exists($column,$this->changeRecordData),
				'Trying to retrieve original record value but no stored change exists.'
			)
		;
	}

	/**
	 * @return $this
	 */
	public function changedColumnsReset()
	{
		$this->changeRecordData = $this->_data;
		return $this;
	}

	/**
	 * Returns whether record has been change since the last save.
	 * @param string $column
	 *
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
	 * @param array $row
	 * @return Lib_Model
	 */
	protected function modelFactory($row)
	{
		/** @var Lib_Model $model */
		$model = new static();
		$model->unserializeContent($row);
		return $model;
	}

	/**
	 * @param array $row
	 * @return Lib_Model
	 */
	public static function modelFactory_s($row)
	{
		/** @var Lib_Model $model */
		$model = new static();
		return $model->modelFactory($row);
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
				if( $this->settingExistsGlobally($key) && $this->settingIsNull($this->getSettingGlobal($key,'type'),$value) )
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
	 * @param string $name
	 * @return bool
	 */
	private function recordColumnProtected($name)
	{
		return in_array($name, $this->_protected);
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
	 * TODO: move to external generator class
	 * @return App_Form_New
	 */
	public function getForm()
	{
		$form = new App_Form_New();

		$options = array('elements' => array());

		foreach($this->_settings as $column => $setting)
		{
			if( $this->recordColumnExists($column) )
			{
				if(!isset($setting['show_in']) || (isset($setting['show_in']) && in_array('form', $setting['show_in'])))
				{

					$settingColumn = array(
						'type' => 'text'
					);

					if(isset($setting['formOptions']))
					{
						$settingColumn['options'] = $setting['formOptions'];
					}
					elseif(!array_key_exists('options', $settingColumn))
					{
						$settingColumn['options'] = array();
					}

					$type = 'text';
					if(isset($setting['type']))
					{
						$type = $setting['type'];
					}
					if(isset($setting['label']))
					{
						$settingColumn['options']['label'] = $setting['label'];
					}
					elseif(isset($setting['title']))
					{
						$settingColumn['options']['label'] = $setting['title'];
					}

					if(isset($setting['options']['validators']))
					{
						$settingColumn['options']['validators'] = $setting['validators'];
					}
					if(isset($setting['filters']))
					{
						$settingColumn['options']['filters'] = $setting['filters'];
					}
					if(isset($setting['decorators']))
					{
						$settingColumn['options']['decorators'] = $setting['decorators'];
					}

					switch($type)
					{
						case "boolean":
						case "bool":
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'checkbox';
							break;
						case "array":
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'multiCheckbox';
							break;
						case "date":
							if(!isset($settingColumn['options']['validators']['date']))
							{
								$settingColumn['options']['validators']['date'] = array('Date',false,array('Y-m-d'));
							}
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'date';
							break;
						case "datetime":
							if(!isset($settingColumn['options']['validators']['date']))
							{
								$settingColumn['options']['validators']['date'] = array('Date',false,array('Y-m-d H:i:s'));
							}
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'datetime';
							break;
						case "time":
							if(!isset($settingColumn['options']['validators']['date']))
							{
								$settingColumn['options']['validators']['date'] = array('Date',false,array('H:i:s'));
							}
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'time';
							break;
						case "uint":
						case "int":
							@$settingColumn['options']['validators'][] = 'Int';
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'int';
							break;
						case "float":
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'float';
							break;
						case "email":
							@$settingColumn['options']['validators'][] = 'EmailAddress';
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'email';
							break;
						case "host":
							@$settingColumn['options']['validators'][] = 'Hostname';
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							$settingColumn['type'] = 'host';
							break;
						case "text":
							if(!isset($settingColumn['options']['decorators']))
							{
								$settingColumn['options']['decorators'] = $form->elementDecorators;
							}
							break;
						case "currency":
							$settingColumn['type'] = 'currency';
						break;
						case "country":
							$settingColumn['type'] = 'country';
						break;
						default:
							debug_assert(false !== array_search($type, self::$types), "Unknown Form Type `{$type}`");
						break;
					}

					if(isset($setting['formType']))
					{
						$settingColumn['type'] = $setting['formType'];
					}
					if(isset($setting['unique']) && $setting['unique'] == true)
					{
						if(!isset($settingColumn['options']['validators']))
						{
							$settingColumn['options']['validators'] = array();
						}
						if(!isset($settingColumn['options']['validators']['unique']))
						{
							$settingColumn['options']['validators']['unique'] = array('Db_RecordNotExistOrIsUnique', false, array(array(
								'table' => $this->getTable(),
								'field' => $column,
								'primary_key' => $this->getPrimaryKey()
							)));
						}
					}
					if(isset($setting['required']) && $setting['required'] == true)
					{
						$settingColumn['options']['required'] = true;
					}

					if(isset($setting['multiOptions']))
					{
						$multiOptions = $setting['multiOptions'];
						if($settingColumn['type'] == 'select' || (isset($settingColumn['formType']) && $settingColumn['formType'] == 'select'))
						{
							$multiOptions = array('' => 'LABEL_SELECT') + $multiOptions;
						}
						$settingColumn['options']['multiOptions'] = $multiOptions;

						if(isset($setting['otherMultioption']) && $setting['formType'] == 'multiCheckbox')
						{
							$settingColumn['options']['otherMultioption'] = $setting['otherMultioption'];
						}
					}

					if(isset($setting['hidden']) && $setting['hidden'] == true)
					{
						$settingColumn['options']['decorators'] = $form->hiddenDecorators;
						$settingColumn['type'] = 'hidden';
					}

					$options['elements'][$column] = $settingColumn;
				}

			}
		}
		$form->setOptions($options);
		$form->setDisableLoadDefaultDecorators(true);

		return $form;
	}

	/**
	 * @param string $column
	 * @return mixed|null
	 */
	public function getDefaultValueForColumn($column)
	{
		if( $this->settingExists($column) ? $setting = $this->getSetting($column, 'default_value') : false )
		{
			return $setting;
		}
		else
		{
			return null;
		}
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function isRequiredValue($column)
	{
		if( $this->settingExists($column) ? $setting = $this->getSetting($column, 'required') : false )
		{
			return (bool) $setting;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		return current($this->_data);
	}

	/**
	 * @return mixed|void
	 */
	public function next()
	{
		return next($this->_data);
	}

	/**
	 * @return mixed
	 */
	public function key()
	{
		return key($this->_data);
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$key = $this->key();
		return array_key_exists($key, $this->_data);
	}

	public function rewind()
	{
		reset($this->_data);
	}

	/**
	 * @return string
	 */
	public function toJson()
	{
		return Zend_Json::encode($this->toArray());
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
		if( debug_assert(!array_key_exists($name,$this->_settingsJoined),"Tried to join setting `{$name}` it already exists.") )
		{
			$this->_settingsJoined[$name] = $value;
		}
	}

	/**
	 * @param Lib_Model $from
	 */
	protected function settingsJoin($from)
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
						$this->settingJoin($column,$from->getSetting($column));
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
	 * @return array
	 */
	public function __sleep()
	{
		return array('_data');
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$class = get_called_class();
		if( $this->recordExists() )
		{
			$data = var_dump_human_compact($this->_data);
			return $class.'{'.$data.'}';
		}
		else
		{
			$query = $this instanceof App_Model_Db_Mysql ? $this->getSQL() : '';
			return $class.'{'.$query.'}';
		}
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @return bool
	 */
	public function settingIsNull($type,$value)
	{
		return (!in_array($type, array('bool','boolean','string','text'))) && $value === '';
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

	public function serialize()
	{
		$content = array(
			'model' => get_class($this),
		);

		$value = $this->_getValueByType($this->id, 'id');
		if(!is_null($value))
		{
			$content['id'] = $value;
		}

		return $content;
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
			if($value instanceof Lib_Model)
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
				case "id":
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
					if(is_object($value) || !is_array($value))
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

		return $value;
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

			if(is_array($value) && array_key_exists('model', $value))
			{
				$class = $value['model'];

				if(array_key_exists('data', $value))
				{
					/** @var Lib_Model $value */
					$obj = new $class();
					$obj->unserializeContent($value['data']);
					$value = $obj;
				}
				elseif(array_key_exists('id', $value))
				{
					/** @var Lib_Model $value */
					$value = $class::getById($value['id']);
				}

			}

			$this->recordColumnSet($key,$value);
		}
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
	 * @param string $export Name of deploy
	 * @param null|mixed $source
	 * @return Bvb_Grid
	 * @deprecated No place in models for it
	 */
	public function getDataTable($export = 'JqGrid', $source = null)
	{
		set_time_limit(0);

		if(is_null($source))
		{
			$source = new Lib_Grid_Source_Model($this);
		}

		$config = $this->getGridConfig();

		$id = Lib_Grid::buildId(
			  $export,
			  $source,
			  isset($config->bvbParams->id_prefix) ? $config->bvbParams->id_prefix : null
		);

		$requestParams = Zend_Controller_Front::getInstance()->getRequest()->getParams();
		if(isset($requestParams['q']) && $requestParams['q'] == $id && isset($requestParams['_exportTo']))
		{
			$requestParams['_exportTo'.$id] = $requestParams['_exportTo'];
		}

		/**
		 * @var Bvb_Grid $grid
		 */
		$grid = Bvb_Grid::factory($export, $config, $id, array(), $requestParams);
		if($export == 'JqGrid')
		{
			Lib_Grid::prepareDeploy($grid, $config, $source);
		}
		else {
			if($export == 'Pdf')
			{
				$config->export->pdf   = 'Pdf';
				$config->disableExport = false;
				Lib_Grid::prepareDeploy($grid, $config, $source);
			}
			else
			{

			}
		}

		return $grid;
	}

	/**
	 * @deprecated unify it with Lib_Grid
	 * @return Zend_Config
	 */
	public function getGridConfig()
	{
		$config = new Zend_Config(array(), true);
		if(Zend_Registry::isRegistered('config') && isset(Zend_Registry::get('config')->dataGrid))
		{
			$config->merge(Zend_Registry::get('config')->dataGrid);
		}
		$config->merge(new Zend_Config($this->_settings));

		foreach($config as $optionName => $optionValue)
		{
			if(!isset($optionValue->title) && isset($optionValue->label))
			{
				$config->$optionName->title = $optionValue->label;
				unset($config->$optionName->label);
			}
			if(isset($optionValue->visible) && !$optionValue->visible)
			{
				$config->$optionName->hidden = true;
			}

			if(isset($optionValue->type))
			{
				if(!isset($config->$optionName->jqg))
				{
					$config->$optionName->jqg = new Zend_Config(array(), true);
				}
				switch($optionValue->type)
				{
					case "select":
						$multiOptions = array();
						$multiOptions[''] = 'LABEL_ALL';
						$multiOptions += $config->$optionName->multiOptions->toArray();

						$multiOptions = array_map(function($key, $row){
							$translate = App_Translate::getInstance();
							return $key.':'.$translate->translate($row);
						}, array_keys($multiOptions), $multiOptions);
						ksort($multiOptions);

						$multiOptions = implode(';', $multiOptions);

						$config->$optionName->jqg->merge(new Zend_Config(array(
							'stype' => 'select',
							'searchoptions' => array(
								'sopt' => array(
									'eq'
								),
								'value' => $multiOptions
							),
							'searchType' => '='
						)), true);
						break;
					case "date":
						$config->$optionName->merge(new Zend_Config(array(
							'sorttype' => 'date',
							'format'   => array(
								'date',
								array(
									'date_format' => Zend_Date::DATE_MEDIUM
								)
							),
							'jqg'      => array(
								'searchoptions' => array(
									'dataInit' => new Zend_Json_Expr('function(el){
											jQuery(el).datepicker({
													dateFormat: "yy-mm-dd",
													onSelect: function(dateText, inst){
															jQuery(el).parents(".ui-jqgrid").find(".ui-jqgrid-btable").get(0).triggerToolbar();
													}
											});
									}')
								)
							)
						)), true);

						if(!isset($config->$optionName->defaultvalue))
						{
							$config->$optionName->defaultvalue = null;
						}
						break;
					case "datetime":
						$config->$optionName->merge(new Zend_Config(array(
							'sorttype' => 'date',
							'format'   => array(
								'date',
								array(
									'date_format' => Zend_Date::DATETIME_SHORT
								)
							),
							'jqg'      => array(
								'searchoptions' => array(
									'dataInit' => new Zend_Json_Expr('function(el){
											jQuery(el).datetimepicker({
													dateFormat: "yy-mm-dd",
													timeFormat: "hh:mm",
													onSelect: function(dateText, inst){
															jQuery(el).parents(".ui-jqgrid").find(".ui-jqgrid-btable").get(0).triggerToolbar();
													}
											});
									}')
								)
							)
						)), true);

						if(!isset($config->$optionName->defaultvalue))
						{
							$config->$optionName->defaultvalue = null;
						}
						break;
					case "time":
						$config->$optionName->merge(new Zend_Config(array(
							'sorttype' => 'date',
							'format'   => array(
								'date',
								array(
									'date_format' => Zend_Date::TIME_SHORT
								)
							),
							'jqg'      => array(
								'searchoptions' => array(
									'dataInit' => new Zend_Json_Expr('function(el){
											jQuery(el).timepicker({
													timeFormat: "hh:mm",
													onSelect: function(dateText, inst){
															jQuery(el).parents(".ui-jqgrid").find(".ui-jqgrid-btable").get(0).triggerToolbar();
													}
											});
									}')
								)
							)
						)), true);

						if(!isset($config->$optionName->defaultvalue))
						{
							$config->$optionName->defaultvalue = null;
						}
						break;
					case "boolean":
					case "bool":
						$multiOptions = array(
							'' => 'LABEL_ALL',
							'0' => 'LABEL_NO',
							'1' => 'LABEL_YES'
						);
						$multiOptions = array_map(function($key, $row){
							$translate = App_Translate::getInstance();
							return $key.':'.$translate->translate($row);
						}, array_keys($multiOptions), $multiOptions);

						$multiOptions = implode(';', $multiOptions);

						$config->$optionName->merge(new Zend_Config(array(
							'width' => 30,
							'align' => 'center',
							'jqg' => array(
								'stype' => 'select',
								'searchoptions' => array(
									'sopt' => array(
										'eq'
									),
									'value' => $multiOptions
								)
							),
							'searchType' => '='
						)), true);

						if(!isset($config->$optionName->helper) && !isset($config->$optionName->callback))
						{
							$config->$optionName->merge(new Zend_Config(array(
								'jqg' => array(
									'formatter' => 'checkbox'
								)
							)), true);
						}

						break;
					case "int":
						$config->$optionName->merge(new Zend_Config(array(
							'searchType' => '='
						)), true);

						break;
					default:
						debug_assert(false !== array_search($optionValue->type, self::$types), "Unknown Grid Cell Type `{$optionValue->type}`");
				}
			}

		}

		return $config;
	}

}