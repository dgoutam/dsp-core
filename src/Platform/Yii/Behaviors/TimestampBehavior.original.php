<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Platform\Yii\Behaviors;

use Kisma\Core\Utility\Option;

/**
 * TimestampBehavior.php
 * Allows you to define time stamp fields in models and have them automatically updated.
 *
 * Defines two "built-in" behaviors: DataFormat and TimeStamp
 *  - DataFormat automatically formats date/time values for the target database platform (MySQL, Oracle, etc.)
 *  - TimeStamp automatically updates create_date and lmod_date columns in tables upon save.
 *
 * @property string $createdColumn    The name of the column that holds your create date
 * @property string $createdByColumn  The name of the column that holds your creating user
 * @property string $lmodColumn       The name of the column that holds your last modified date
 * @property string $lmodByColumn     The name of the column that holds your last modifying user
 * @property string $dateTimeFunction The name of the function to use to set dates. Defaults to date('Y-m-d H:i:s').
 */
class TimestampBehavior extends BaseDspModelBehavior
{
	//********************************************************************************
	//* Members
	//********************************************************************************

	/**
	 * @var string The optional name of the create date column
	 */
	protected $_createdColumn = null;
	/**
	 * @var string The optional name of the created by user id column
	 */
	protected $_createdByColumn = null;
	/**
	 * @var string The optional name of the last modified date column
	 */
	protected $_lastModifiedColumn = null;
	/**
	 * @var string The optional name of the last modified by user id column
	 */
	protected $_lastModifiedByColumn = null;
	/**
	 * @var string The date/time format to use if not using $dateTimeFunction
	 */
	protected $_dateTimeFormat = 'Y-m-d H:i:s';
	/**
	 * @var string The date/time with which function to stamp records
	 */
	protected $_dateTimeFunction = null;
	/**
	 * @var int|callable
	 */
	protected $_currentUserId = null;

	//********************************************************************************
	//*  Handlers
	//********************************************************************************

	/**
	 * Timestamps row
	 *
	 * @param \CModelEvent $event
	 */
	public function beforeValidate( $event )
	{
		$_id = null;
		$_model = $event->sender;

		try
		{
			$_id = $this->getCurrentUserId();
		}
		catch ( \Exception $_ex )
		{
		}

		//	Handle created stamp
		if ( $_model->isNewRecord )
		{
			if ( $this->_createdColumn && $_model->hasAttribute( $this->_createdColumn ) )
			{
				$this->owner->setAttribute(
					$this->_createdColumn,
					( null === $this->_dateTimeFunction ) ? date( $this->_dateTimeFormat ) : eval( 'return ' . $this->_dateTimeFunction . ';' )
				);
			}

			if ( $_id && $this->_createdByColumn && $_model->hasAttribute( $this->_createdByColumn ) && !$_model->getAttribute( $this->_createdByColumn ) )
			{
				$this->owner->setAttribute( $this->_createdByColumn, $_id );
			}
		}

		//	Handle lmod stamp
		if ( $this->_lastModifiedColumn && $_model->hasAttribute( $this->_lastModifiedColumn ) )
		{
			$this->owner->setAttribute(
				$this->_lastModifiedColumn,
				( null === $this->_dateTimeFunction ) ? date( $this->_dateTimeFormat ) : eval( 'return ' . $this->_dateTimeFunction . ';' )
			);
		}

		//	Handle user id stamp
		if ( $_id && $this->_lastModifiedByColumn && $_model->hasAttribute( $this->_lastModifiedByColumn ) &&
			 !$_model->getAttribute( $this->_lastModifiedByColumn )
		)
		{
			$this->owner->setAttribute( $this->_lastModifiedByColumn, $_id );
		}

		parent::beforeValidate( $event );
	}

	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Sets lmod date(s) and saves
	 * Will optionally touch other columns. You can pass in a single column name or an array of columns.
	 * This is useful for updating not only the lmod column but a last login date for example.
	 * Only the columns that have been touched are updated. If no columns are updated, no database action is performed.
	 *
	 * @param mixed $additionalColumns The single column name or array of columns to touch in addition to configured lmod column
	 *
	 * @return boolean
	 */
	public function touch( $additionalColumns = null )
	{
		$_updateList = array();
		$_touchValue = ( null === $this->_dateTimeFunction ) ? date( $this->_dateTimeFormat ) : eval( 'return ' . $this->_dateTimeFunction . ';' );

		//	Any other columns to touch?
		if ( null !== $additionalColumns )
		{
			foreach ( Option::clean( $additionalColumns ) as $_attribute )
			{
				if ( $this->owner->hasAttribute( $_attribute ) )
				{
					$this->owner->setAttribute( $_attribute, $_touchValue );
					$_updateList[] = $_attribute;
				}
			}
		}

		if ( $this->_lastModifiedColumn && $this->owner->hasAttribute( $this->_lastModifiedColumn ) )
		{
			$this->owner->setAttribute( $this->_lastModifiedColumn, $_touchValue );
			$_updateList[] = $this->_lastModifiedColumn;
		}

		//	Only update if and what we've touched...
		return count( $_updateList ) ? $this->owner->update( $_updateList ) : true;
	}

	/**
	 * @param string $createdByColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setCreatedByColumn( $createdByColumn )
	{
		$this->_createdByColumn = $createdByColumn;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreatedByColumn()
	{
		return $this->_createdByColumn;
	}

	/**
	 * @param string $createdColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setCreatedColumn( $createdColumn )
	{
		$this->_createdColumn = $createdColumn;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreatedColumn()
	{
		return $this->_createdColumn;
	}

	/**
	 * @param string $dateTimeFunction
	 *
	 * @return TimestampBehavior
	 */
	public function setDateTimeFunction( $dateTimeFunction )
	{
		$this->_dateTimeFunction = $dateTimeFunction;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDateTimeFunction()
	{
		return $this->_dateTimeFunction;
	}

	/**
	 * @param string $lastModifiedByColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setLastModifiedByColumn( $lastModifiedByColumn )
	{
		$this->_lastModifiedByColumn = $lastModifiedByColumn;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastModifiedByColumn()
	{
		return $this->_lastModifiedByColumn;
	}

	/**
	 * @param string $lastModifiedColumn
	 *
	 * @return TimestampBehavior
	 */
	public function setLastModifiedColumn( $lastModifiedColumn )
	{
		$this->_lastModifiedColumn = $lastModifiedColumn;

		return $this;
	}

	/**
	 * @return string
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
			/** @noinspection PhpParamsInspection */
			if ( is_callable( $this->_currentUserId ) )
			{
				$_id = call_user_func( $this->_currentUserId, $this );
			}
			else
			{
				$_id = $this->_currentUserId;
			}
		}

		return null;
	}
}