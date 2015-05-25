<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


class InsertIn
{
	const PRIORITY_LOW = 'LOW_PRIORITY';
	const PRIORITY_DELAYED = 'DELAYED';
	const PRIORITY_HIGH = 'HIGH_PRIORITY';

	/** @var array */
	private $insertColumnsQuoted;

	/** @var array */
	private $insertValuesQuoted;

	/** @var array|null */
	private $updateColumnsQuoted;

	/** @var bool|null */
	private $ignoreIfExists;

	/** @var string|null */
	private $priority;

	/** @var string|array|null */
	private $partitionNamesQuoted;

	/** @var string */
	private $tableNameQuoted;

	/**
	 * @return array
	 */
	public function getInsertColumnsQuoted()
	{
		return $this->insertColumnsQuoted;
	}

	/**
	 * @param array $insertColumnsQuoted
	 *
	 * @return $this
	 */
	public function setInsertColumnsQuoted($insertColumnsQuoted)
	{
		$this->insertColumnsQuoted = $insertColumnsQuoted;
		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getUpdatePairsQuoted()
	{
		return $this->updateColumnsQuoted;
	}

	/**
	 * @param array|null $updateColumnsQuoted
	 *
	 * @return $this
	 */
	public function setUpdateColumnsQuoted($updateColumnsQuoted)
	{
		$this->updateColumnsQuoted = $updateColumnsQuoted;
		return $this;
	}

	/**
	 * @return bool|null
	 */
	public function getIgnoreIfExists()
	{
		return $this->ignoreIfExists;
	}

	/**
	 * @param bool|null $ignoreIfExists
	 *
	 * @return $this
	 */
	public function setIgnoreIfExists($ignoreIfExists)
	{
		if( null!==$ignoreIfExists || !debug_assert( is_bool($ignoreIfExists) ) )
		{
			$ignoreIfExists = null;
		}
		$this->ignoreIfExists = $ignoreIfExists;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param null|string $priority
	 *
	 * @return $this
	 */
	public function setPriority($priority)
	{
		if( null!==$priority && !debug_assert_array_contains(
				[self::PRIORITY_LOW,self::PRIORITY_DELAYED,self::PRIORITY_HIGH],
				$priority
			)
		)
		{
			$priority = null;
		}
		$this->priority = $priority;
		return $this;
	}

	/**
	 * @return array|null|string
	 */
	public function getPartitionNamesQuoted()
	{
		return $this->partitionNamesQuoted;
	}

	/**
	 * @param array|null|string $partitionNamesQuoted
	 *
	 * @return $this
	 */
	public function setPartitionNamesQuoted($partitionNamesQuoted)
	{
		$this->partitionNamesQuoted = $partitionNamesQuoted;
		if( null!==$partitionNamesQuoted && !debug_assert( array_all( arrayize($partitionNamesQuoted), is_type_dg('string') ) ) ) //TODO: validation of partitions
		{
			$partitionNamesQuoted = null;
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTableNameQuoted()
	{
		return $this->tableNameQuoted;
	}

	/**
	 * @param string $tableNameQuoted
	 *
	 * @return $this
	 */
	public function setTableNameQuoted($tableNameQuoted)
	{
		$this->tableNameQuoted = $tableNameQuoted;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getInsertValuesQuoted()
	{
		return $this->insertValuesQuoted;
	}

	/**
	 * @param array $insertValuesQuoted
	 *
	 * @return $this
	 */
	public function setInsertValuesQuoted($insertValuesQuoted)
	{
		$this->insertValuesQuoted = $insertValuesQuoted;
		return $this;
	}
}

class InsertOut
{
	/** @var string */
	public $sql;
}

class Insert
{
	const IGNORE_TRUE = 'IGNORE';
	const IGNORE_FALSE = '';

	/** @var InsertIn */
	public $in;
	/** @var InsertOut */
	public $out;

	public function call()
	{
		$this->out->sql = 'INSERT'
			. ( $this->in->getPriority()===null ? '' : ' '.$this->in->getPriority() )
			. ( $this->in->getIgnoreIfExists()===null ? '' : ' '.$this->in->getIgnoreIfExists() )
			. ' INTO '.$this->in->getTableNameQuoted()
			. ( $this->in->getPartitionNamesQuoted()===null ? '' : ' PARTITION('.implode(',',$this->in->getPartitionNamesQuoted()).')' )
			. ' ('.implode( ',', $this->in->getInsertColumnsQuoted() ).')'
			. ' VALUES '.implode(',',array_map_val($this->in->getInsertValuesQuoted(),function($row){ return '('.implode(',',$row).')'; }))
			. ( $this->in->getUpdatePairsQuoted()===null
				? ''
				: implode(',',array_map_val($this->in->getUpdatePairsQuoted(),function($key,$value){ return $key.'='.$value; })) )
		;
	}

	public function delegate()
	{
		return function()
		{
			$this->call();
		};
	}
}