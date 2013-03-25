<?php
use DreamFactory\Platform\Enums\PlatformError;
use DreamFactory\Yii\Models\BaseModel;

/**
 * BaseDspSystemModel.php
 * A base class for DSP system models
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
 * Base Columns:
 *
 * @property integer $id
 * @property string  $created_date
 * @property string  $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * Base Relations:
 *
 * @property User    $created_by
 * @property User    $last_modified_by
 *
 *
 * Behavior Methods
 * @method TimestampBehavior setCurrentUserId( $currentUserId )
 */
abstract class BaseDspSystemModel extends BaseModel
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const ALL_ATTRIBUTES = '*';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Initialize
	 */
	public function init()
	{
		parent::init();

		try
		{
			//	Set the current user id for stamping
			$this->setCurrentUserId( \SessionManager::getCurrentUserId() );
		}
		catch ( \Exception $_ex )
		{
		}
	}

	/**
	 * @return string the system database table name prefix
	 */
	public static function tableNamePrefix()
	{
		return 'df_sys_';
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'created_by'       => array( self::BELONGS_TO, 'User', 'created_by_id' ),
			'last_modified_by' => array( self::BELONGS_TO, 'User', 'last_modified_by_id' ),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$_criteria = new \CDbCriteria;

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'created_date', $this->created_date, true );
		$_criteria->compare( 'last_modified_date', $this->last_modified_date, true );
		$_criteria->compare( 'created_by_id', $this->created_by_id );
		$_criteria->compare( 'last_modified_by_id', $this->last_modified_by_id );

		return new \CActiveDataProvider(
			$this,
			array(
				 'criteria' => $_criteria,
			)
		);
	}

	/**
	 * @param string $requested Comma-delimited list of requested fields
	 *
	 * @param array  $columns   Additional columns to add
	 *
	 * @param array  $hidden    Columns to hide from requested
	 *
	 * @return array
	 */
	public function getRetrievableAttributes( $requested, $columns = array(), $hidden = array() )
	{
		if ( empty( $requested ) )
		{
			// primary keys only
			return array( 'id' );
		}

		if ( static::ALL_ATTRIBUTES == $requested )
		{
			return array_merge(
				array(
					 'id',
					 'created_date',
					 'created_by_id',
					 'last_modified_date',
					 'last_modified_by_id'
				),
				$columns
			);
		}

		//	Remove the hidden fields
		$_columns = explode( ',', $requested );

		if ( !empty( $hidden ) )
		{
			foreach ( $_columns as $_index => $_column )
			{
				foreach ( $hidden as $_hide )
				{
					if ( 0 == strcasecmp( $_column, $_hide ) )
					{
						unset( $_columns[$_index] );
					}
				}
			}
		}

		return $_columns;
	}

	/**
	 * @param array $values
	 * @param int   $id
	 */
	public function setRelated( $values, $id )
	{
		/*
		$relations = $obj->relations();
		foreach ($relations as $key=>$related) {
			if (isset($record[$key])) {
				switch ($related[0]) {
				case CActiveRecord::HAS_MANY:
					$this->assignManyToOne($table, $id, $related[1], $related[2], $record[$key]);
					break;
				case CActiveRecord::MANY_MANY:
					$this->assignManyToOneByMap($table, $id, $related[1], 'app_to_role', 'role_id', 'app_id', $record[$key]);
					break;
				}
			}
		}
		*/
	}

	// generic assignments

	/**
	 * @param string $one_id
	 * @param string $many_table
	 * @param string $many_field
	 * @param array  $many_records
	 *
	 * @throws InvalidArgumentException
	 * @throws Exception
	 * @return void
	 */
	protected function assignManyToOne( $one_id, $many_table, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new InvalidArgumentException( 'The id can not be empty.', PlatformError::BadRequest );
		}

		try
		{
			$manyModel = SystemManager::getResourceModel( $many_table );
			$manyObj = SystemManager::getNewResource( $many_table );
			$pkField = $manyObj->tableSchema->primaryKey;
			$oldMany = $manyModel->findAll( $many_field . ' = :oid', array( ':oid' => $one_id ) );
			foreach ( $oldMany as $old )
			{
				$oldId = $old->primaryKey;
				$found = false;
				foreach ( $many_records as $key => $item )
				{
					$id = Utilities::getArrayValue( $pkField, $item, '' );
					if ( $id == $oldId )
					{
						// found it, keeping it, so remove it from the list, as this becomes adds
						unset( $many_records[$key] );
						$found = true;
						continue;
					}
				}
				if ( !$found )
				{
					$old->setAttribute( $many_field, null );
					$old->save();
					continue;
				}
			}
			if ( !empty( $many_records ) )
			{
				// add what is leftover
				foreach ( $many_records as $item )
				{
					$id = Utilities::getArrayValue( $pkField, $item, '' );
					$assigned = $manyModel->findByPk( $id );
					if ( $assigned )
					{
						$assigned->setAttribute( $many_field, $one_id );
						$assigned->save();
					}
				}
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating many to one assignment.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param       $one_id
	 * @param       $many_table
	 * @param       $map_table
	 * @param       $one_field
	 * @param       $many_field
	 * @param array $many_records
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function assignManyToOneByMap( $one_id, $many_table, $map_table, $one_field, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new Exception( "The id can not be empty.", ErrorCodes::BAD_REQUEST );
		}
		$map_table = SystemManager::SYSTEM_TABLE_PREFIX . $map_table;
		try
		{
			$manyObj = SystemManager::getNewResource( $many_table );
			$pkManyField = $manyObj->tableSchema->primaryKey;
			$pkMapField = 'id';
			// use query builder
			$command = Yii::app()->db->createCommand();
			$command->select( $pkMapField . ',' . $many_field );
			$command->from( $map_table );
			$command->where( "$one_field = '$one_id'" );
			$maps = $command->queryAll();
			$toDelete = array();
			foreach ( $maps as $map )
			{
				$manyId = Utilities::getArrayValue( $many_field, $map, '' );
				$id = Utilities::getArrayValue( $pkMapField, $map, '' );
				$found = false;
				foreach ( $many_records as $key => $item )
				{
					$assignId = Utilities::getArrayValue( $pkManyField, $item, '' );
					if ( $assignId == $manyId )
					{
						// found it, keeping it, so remove it from the list, as this becomes adds
						unset( $many_records[$key] );
						$found = true;
						continue;
					}
				}
				if ( !$found )
				{
					$toDelete[] = $id;
					continue;
				}
			}
			if ( !empty( $toDelete ) )
			{
				$command->reset();

				foreach ( $toDelete as $key => $id )
				{
					// simple delete request
					$command->reset();
					$rows = $command->delete( $map_table, array( 'in', $pkMapField, $id ) );
				}
			}
			if ( !empty( $many_records ) )
			{
				foreach ( $many_records as $item )
				{
					$itemId = Utilities::getArrayValue( $pkManyField, $item, '' );
					$record = array( $many_field => $itemId, $one_field => $one_id );
					// simple update request
					$command->reset();
					$rows = $command->insert( $map_table, $record );
					if ( 0 >= $rows )
					{
						throw new Exception( "Record insert failed for table '$map_table'." );
					}
				}
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating many to one map assignment.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

//	/**
//	 * @return bool
//	 */
//	protected function beforeValidate()
//	{
//		try
//		{
//			$_userId = SessionManager::getCurrentUserId();
//
//			if ( $this->isNewRecord )
//			{
//				$this->created_by_id = $_userId;
//			}
//
//			$this->last_modified_by_id = $_userId;
//		}
//		catch ( Exception $_ex )
//		{
//		}
//
//		return parent::beforeValidate();
//	}
}