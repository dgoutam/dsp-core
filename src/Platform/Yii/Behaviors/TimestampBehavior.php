<?php
/**
 * TimestampBehavior.php
 *
 * @copyright Copyright (c) 2012 DreamFactory Software, Inc.
 * @link      http://www.dreamfactory.com DreamFactory Software, Inc.
 * @author    Jerry Ablan <jerryablan@dreamfactory.com>
 *
 * @filesource
 */
namespace Platform\Yii\Behaviors;

use Kisma\Core\Utility\Option;

/**
 * TimestampBehavior
 * Allows you to define time stamp fields in models and have them automatically updated.
 */
class TimestampBehavior extends BaseDspModelBehavior
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string The default date/time format
	 */
	const DEFAULT_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	//********************************************************************************
	//* Members
	//********************************************************************************

	/**
	 * @var string|array The optional name of the create date column
	 */
	protected $_createdColumn = null;
	/**
	 * @var string|array The optional name of the created by user id column
	 */
	protected $_createdByColumn = null;
	/**
	 * @var string|array The optional name of the last modified date column
	 */
	protected $_lastModifiedColumn = null;
	/**
	 * @var string|array The optional name of the last modified by user id column
	 */
	protected $_lastModifiedByColumn = null;
	/**
	 * @var string The date/time format to use if not using $dateTimeFunction
	 */
	protected $_dateTimeFormat = self::DEFAULT_DATE_TIME_FORMAT;
	/**
	 * @var callback The date/time with which function to stamp records
	 */
	protected $_dateTimeFunction = null;
	/**
	 * @var int|callable
	 */
	protected $_currentUserId = null;

	//********************************************************************************
	//*  Methods
	//********************************************************************************

	/**
	 * Timestamps row
	 *
	 * @param \CModelEvent $event
	 */
	public function beforeValidate( $event )
	{
		$_model = $event->sender;
		$_timestamp = $this->_timestamp();
		$_userId = $this->getCurrentUserId();

		//	Handle lmod stamp
		$this->_stampRow(
			$this->_lastModifiedColumn,
			$_timestamp,
			$_model
		);

		$this->_stampRow(
			$this->_lastModifiedByColumn,
			$_userId,
			$_model
		);

		//	Handle created stamp
		if ( $event->sender->isNewRecord )
		{
			$this->_stampRow(
				$this->_createdColumn,
				$_timestamp,
				$_model
			);

			$this->_stampRow(
				$this->_createdByColumn,
				$_userId,
				$_model
			);
		}

		parent::beforeValidate( $event );
	}

	/**
	 * @param int|callable $currentUserId
	 *
	 * @return $this
	 */
	public function setCurrentUserId( $currentUserId )
	{
		$this->_currentUserId = $currentUserId;

		return $this;
	}

	/**
	 * @return int|callable
	 */
	public function getCurrentUserId()
	{
		if ( !empty( $this->_currentUserId ) )
		{
			return is_callable( $this->_currentUserId ) ? call_user_func( $this->_currentUserId, $this ) : $this->_currentUserId;
		}

		return null;
	}

	/**
	 * Sets lmod date(s) and saves
	 * Will optionally touch other columns. You can pass in a single column name or an array of columns.
	 * This is useful for updating not only the lmod column but a last login date for example.
	 * Only the columns that have been touched are updated. If no columns are updated, no database action is performed.
	 *
	 * @param mixed $additionalColumns The single column name or array of columns to touch in addition to configured lmod column
	 * @param bool  $update            If true, the row will be updated
	 *
	 * @return boolean
	 */
	public function touch( $additionalColumns = null, $update = false )
	{
		/** @var \BaseDspModel $_model */
		$_model = $this->getOwner();

		//	Any other columns to touch?
		$_updated = $this->_stampRow( array_merge( Option::clean( $additionalColumns ), array( $this->_lastModifiedColumn ) ), $this->_timestamp(), $_model );

		//	Only update if and what we've touched or wanted...
		return
			false !== $update || !empty( $_updated ) ? $_model->update( $_updated ) : true;
	}

	/**
	 * @param string|array $createdByColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setCreatedByColumn( $createdByColumn )
	{
		$this->_createdByColumn = $createdByColumn;

		return $this;
	}

	/**
	 * @return string|array
	 */
	public function getCreatedByColumn()
	{
		return $this->_createdByColumn;
	}

	/**
	 * @param string|array $createdColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setCreatedColumn( $createdColumn )
	{
		$this->_createdColumn = $createdColumn;

		return $this;
	}

	/**
	 * @return string|array
	 */
	public function getCreatedColumn()
	{
		return $this->_createdColumn;
	}

	/**
	 * @param callback $dateTimeFunction
	 *
	 * @throws \InvalidArgumentException
	 * @return TimestampBehavior
	 */
	public function setDateTimeFunction( $dateTimeFunction )
	{
		if ( !is_callable( $dateTimeFunction ) )
		{
			throw new \InvalidArgumentException( 'The "dateTimeFunction" you specified is not "callable".' );
		}

		$this->_dateTimeFunction = $dateTimeFunction;

		return $this;
	}

	/**
	 * @return callback
	 */
	public function getDateTimeFunction()
	{
		return $this->_dateTimeFunction;
	}

	/**
	 * @param string|array $lastModifiedByColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setLastModifiedByColumn( $lastModifiedByColumn )
	{
		$this->_lastModifiedByColumn = $lastModifiedByColumn;

		return $this;
	}

	/**
	 * @return string|array
	 */
	public function getLastModifiedByColumn()
	{
		return $this->_lastModifiedByColumn;
	}

	/**
	 * @param string|array $lastModifiedColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setLastModifiedColumn( $lastModifiedColumn )
	{
		$this->_lastModifiedColumn = $lastModifiedColumn;

		return $this;
	}

	/**
	 * @return string|array
	 */
	public function getLastModifiedColumn()
	{
		return $this->_lastModifiedColumn;
	}

	/**
	 * @param string $dateTimeFormat
	 *
	 * @return TimestampBehavior
	 */
	public function setDateTimeFormat( $dateTimeFormat )
	{
		$this->_dateTimeFormat = $dateTimeFormat;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDateTimeFormat()
	{
		return $this->_dateTimeFormat;
	}

	/**
	 * Stamps a column(s) with a value
	 *
	 * @param string|array   $columns The name, or an array, of possible column names
	 * @param mixed          $value   The value to stamp
	 * @param \CActiveRecord $model   The target model
	 *
	 * @return array
	 */
	protected function _stampRow( $columns, $value, $model )
	{
		$_updated = array();

		if ( !empty( $columns ) )
		{
			foreach ( Option::clean( $columns ) as $_column )
			{
				if ( $model->setAttribute( $_column, $value ) )
				{
					$_updated[] = $_column;
				}
			}
		}

		return $_updated;
	}

	/**
	 * @return bool|string
	 */
	protected function _timestamp()
	{
		if ( is_callable( $this->_dateTimeFunction ) )
		{
			return call_user_func( $this->_dateTimeFunction, $this->_dateTimeFormat );
		}

		return date( $this->_dateTimeFormat ? : static::DEFAULT_DATE_TIME_FORMAT );
	}
}