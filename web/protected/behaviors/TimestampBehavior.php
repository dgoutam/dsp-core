<?php
/**
 * TimestampBehavior.php
 * Allows you to define time stamp fields in models and have them automatically updated.
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		try
		{
			$_id = SessionManager::getCurrentUserId();
		}
			//	No session yet, no user...
		catch ( Exception $_ex )
		{
			$_id = null;
		}

		//	Handle created stamp
		if ( $event->sender->isNewRecord )
		{
			if ( $this->_createdColumn && $event->sender->hasAttribute( $this->_createdColumn ) )
			{
				$this->owner->setAttribute(
					$this->_createdColumn,
					( null === $this->_dateTimeFunction ) ? date( $this->_dateTimeFormat ) : eval( 'return ' . $this->_dateTimeFunction . ';' )
				);
			}

			if ( $this->_createdByColumn && $event->sender->hasAttribute( $this->_createdByColumn ) && !$event->sender->getAttribute(
				$this->_createdByColumn
			)
			)
			{
				if ( !empty( $_id ) )
				{
					$this->owner->setAttribute( $this->_createdByColumn, $_id );
				}
			}
		}

		//	Handle lmod stamp
		if ( $this->_lastModifiedColumn && $event->sender->hasAttribute( $this->_lastModifiedColumn ) )
		{
			$this->owner->setAttribute(
				$this->_lastModifiedColumn,
				( null === $this->_dateTimeFunction ) ? date( $this->_dateTimeFormat ) : eval( 'return ' . $this->_dateTimeFunction . ';' )
			);
		}

		//	Handle user id stamp
		if ( $this->_lastModifiedByColumn && $event->sender->hasAttribute( $this->_lastModifiedByColumn ) && !$event->sender->getAttribute(
			$this->_lastModifiedByColumn
		)
		)
		{
			if ( !empty( $_id ) )
			{
				$this->owner->setAttribute( $this->_lastModifiedByColumn, $_id );
			}
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
		$_touchValue =
			( null === $this->_dateTimeFunction ) ? date( $this->_dateTimeFormat ) : eval( 'return ' . $this->_dateTimeFunction . ';' );
		$_updateList = array();

		//	Any other columns to touch?
		if ( null !== $additionalColumns )
		{
			foreach ( \Kisma\Core\Utility\Option::clean( $additionalColumns ) as $_attribute )
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
}