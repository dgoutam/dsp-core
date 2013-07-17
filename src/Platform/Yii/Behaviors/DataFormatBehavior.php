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

use Kisma\Core\Utility\Scalar;

/**
 * BaseDspModelBehavior.php
 * If attached to a model, fields are formatted per your configuration. Also provides a default sort for a model
 */
class DataFormatBehavior extends BaseDspModelBehavior
{
	//********************************************************************************
	//* Member
	//********************************************************************************

	/***
	 * Holds the default/configured formats for use when populating fields
	 *
	 * array(
	 *     'event' => array(                //    The event to apply format in
	 *         'dataType' => <format>        //    The format for the display
	 *         'method' => <function>        //    The function to call for formatting
	 *     ),                                //        Send array(object,method) for class methods
	 *     'event' => array(                //    The event to apply format in
	 *         'dataType' => <format>        //    The format for the display
	 *         'method' => <function>        //    The function to call for formatting
	 *     ),                                //        Send array(object,method) for class methods
	 *     ...
	 *
	 * @var array
	 */
	protected $_dateFormat
		= array(
			'afterFind'     => array(
				'date'     => 'm/d/Y',
				'datetime' => 'm/d/Y H:i:s',
			),
			'afterValidate' => array(
				'date'     => 'Y-m-d',
				'datetime' => 'Y-m-d H:i:s',
			),
		);

	/**
	 * @var string The default sort order
	 */
	protected $_defaultSort;

	//*************************************************************************
	//* Handlers
	//*************************************************************************

	/**
	 * Apply any formats
	 *
	 * @param \CModelEvent event parameter
	 *
	 * @return bool|void
	 */
	public function beforeValidate( $event )
	{
		return $this->_handleEvent( __FUNCTION__, $event );
	}

	/**
	 * Apply any formats
	 *
	 * @param \CEvent $event
	 *
	 * @return bool|void
	 */
	public function afterValidate( $event )
	{
		return $this->_handleEvent( __FUNCTION__, $event );
	}

	/**
	 * Apply any formats
	 *
	 * @param \CEvent $event
	 *
	 * @return bool|void
	 */
	public function beforeFind( $event )
	{
		//	Is a default sort defined?
		if ( $this->_defaultSort )
		{
			//	Is a sort defined?
			$_criteria = $event->sender->getDbCriteria();
			/** @var $_model \CActiveRecord */
			$_model = $event->sender;

			//	No sort? Set the default
			if ( !$_criteria->order )
			{
				$_model->getDbCriteria()->mergeWith(
					new \CDbCriteria(
						array(
							'order' => $this->_defaultSort,
						)
					)
				);
			}
		}

		return $this->_handleEvent( __FUNCTION__, $event );
	}

	/**
	 * Apply any formats
	 *
	 * @param \CEvent $event
	 *
	 * @return bool|void
	 */
	public function afterFind( $event )
	{
		return $this->_handleEvent( __FUNCTION__, $event );
	}

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string $which
	 * @param string $type
	 *
	 * @return string
	 */
	public function getFormat( $which = 'afterFind', $type = 'date' )
	{
		return Scalar::nvl( $this->_dateFormat[$which][$type], 'm/d/Y' );
	}

	/**
	 * Sets a format
	 *
	 * @param string $which
	 * @param string $type
	 * @param string $format
	 */
	public function setFormat( $which = 'afterValidate', $type = 'date', $format = 'm/d/Y' )
	{
		if ( !isset( $this->_dateFormat[$which] ) )
		{
			$this->_dateFormat[$which] = array();
		}

		$this->_dateFormat[$which][$type] = $format;
	}

	/**
	 * @param string $defaultSort
	 *
	 * @return DataFormatBehavior
	 */
	public function setDefaultSort( $defaultSort )
	{
		$this->_defaultSort = $defaultSort;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultSort()
	{
		return $this->_defaultSort;
	}

	/**
	 * Applies the requested format to the value and returns it.
	 * Override this method to apply additional format types.
	 *
	 * @param \CDbColumnSchema $column
	 * @param mixed            $value
	 * @param string           $which
	 *
	 * @return mixed
	 */
	protected function _applyFormat( $column, $value, $which = 'view' )
	{
		$_result = null;

		//	Apply formats
		switch ( $column->dbType )
		{
			case 'date':
			case 'datetime':
			case 'timestamp':
				//	Handle blanks
				if ( null != $value && $value != '0000-00-00' && $value != '0000-00-00 00:00:00' )
				{
					$_result = date( $this->getFormat( $which, $column->dbType ), strtotime( $value ) );
				}
				break;

			default:
				$_result = $value;
				break;
		}

		return $_result;
	}

	/**
	 * Process the data and apply formats
	 *
	 * @param string  $which
	 * @param \CEvent $event
	 *
	 * @return bool
	 */
	protected function _handleEvent( $which, \CEvent $event )
	{
		static $_schema;
		static $_schemaFor;

		$_model = $event->sender;

		//	Cache for multi event speed
		if ( $_schemaFor != get_class( $_model ) )
		{
			$_schema = $_model->getMetaData()->columns;
			$_schemaFor = get_class( $_model );
		}

		//	Not for us? Pass it through...
		if ( isset( $this->_dateFormat[$which] ) )
		{
			//	Is it safe?
			if ( !$_schema )
			{
				$_model->addError( null, 'Cannot read schema for data formatting' );

				return false;
			}

			//	Scoot through and update values...
			foreach ( $_schema as $_name => $_column )
			{
				if ( !empty( $_name ) && $_model->hasAttribute( $_name ) && isset( $_schema[$_name], $this->_dateFormat[$which][$_column->dbType] ) )
				{
					$_value = $this->_applyFormat( $_column, $_model->getAttribute( $_name ), $which );
					$_model->setAttribute( $_name, $_value );
				}
			}
		}

		//	Papa don't preach...
		return parent::$which( $event );
	}
}