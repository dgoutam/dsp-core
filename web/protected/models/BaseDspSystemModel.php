<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
use Platform\Exceptions\BadRequestException;
use Platform\Resources\UserSession;
use Platform\Services\SystemManager;
use Platform\Utility\Utilities;
use Platform\Yii\Utility\Pii;

/**
 * BaseDspSystemModel.php
 * A base class for DSP system models
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
 */
abstract class BaseDspSystemModel extends BaseDspModel
{
	/**
	 * @return string the system database table name prefix
	 */
	public static function tableNamePrefix()
	{
		return 'df_sys_';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array( //array( 'created_by_id, last_modified_by_id', 'numerical', 'integerOnly' => true ),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'created_by'       => array(self::BELONGS_TO, 'User', 'created_by_id'),
			'last_modified_by' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$_criteria = new CDbCriteria;

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'created_date', $this->created_date, true );
		$_criteria->compare( 'last_modified_date', $this->last_modified_date, true );
		$_criteria->compare( 'created_by_id', $this->created_by_id );
		$_criteria->compare( 'last_modified_by_id', $this->last_modified_by_id );

		return new CActiveDataProvider(
			$this,
			array(
				'criteria' => $_criteria,
			)
		);
	}

	/**
	 * @return bool
	 */
	protected function beforeValidate()
	{
		try
		{
			$this->last_modified_by_id = $_userId = UserSession::getCurrentUserId();

			if ( $this->isNewRecord )
			{
				$this->created_by_id = $_userId;
			}
		}
		catch ( Exception $_ex )
		{
		}

		return parent::beforeValidate();
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
			return array('id');
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
	 * @throws Exception
	 * @throws Platform\Exceptions\BadRequestException
	 * @return void
	 */
	protected function assignManyToOne( $one_id, $many_table, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new BadRequestException( "The id can not be empty." );
		}
		try
		{
			$manyObj = SystemManager::getNewModel( $many_table );
			$pkField = $manyObj->tableSchema->primaryKey;
			$many_table = static::tableNamePrefix() . $many_table;
			// use query builder
			$command = Pii::db()->createCommand();
			$command->select( "$pkField,$many_field" );
			$command->from( $many_table );
			$command->where( "$many_field = :oid" );
			$maps = $command->queryAll( true, array(':oid' => $one_id) );
			$toDelete = array();
			foreach ( $maps as $map )
			{
				$id = Utilities::getArrayValue( $pkField, $map, '' );
				$found = false;
				foreach ( $many_records as $key => $item )
				{
					$assignId = Utilities::getArrayValue( $pkField, $item, '' );
					if ( $id == $assignId )
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
				// simple update to null request
				$command->reset();
				$rows = $command->update( $many_table, array($many_field => null), array('in', $pkField, $toDelete) );
				if ( 0 >= $rows )
				{
//					throw new Exception( "Record update failed for table '$many_table'." );
				}
			}
			if ( !empty( $many_records ) )
			{
				$toAdd = array();
				foreach ( $many_records as $item )
				{
					$itemId = Utilities::getArrayValue( $pkField, $item, '' );
					if ( !empty( $itemId ) )
					{
						$toAdd[] = $itemId;
					}
				}
				if ( !empty( $toAdd ) )
				{
					// simple update to null request
					$command->reset();
					$rows = $command->update( $many_table, array($many_field => $one_id), array('in', $pkField, $toAdd) );
					if ( 0 >= $rows )
					{
//						throw new Exception( "Record update failed for table '$many_table'." );
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
	 * @throws Platform\Exceptions\BadRequestException
	 * @return void
	 */
	protected function assignManyToOneByMap( $one_id, $many_table, $map_table, $one_field, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new BadRequestException( "The id can not be empty." );
		}
		$map_table = static::tableNamePrefix() . $map_table;
		try
		{
			$manyObj = SystemManager::getNewModel( $many_table );
			$pkManyField = $manyObj->tableSchema->primaryKey;
			$pkMapField = 'id';
			// use query builder
			$command = Pii::db()->createCommand();
			$command->select( $pkMapField . ',' . $many_field );
			$command->from( $map_table );
			$command->where( "$one_field = :id" );
			$maps = $command->queryAll( true, array(':id' => $one_id) );
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
				// simple delete request
				$command->reset();
				$rows = $command->delete( $map_table, array('in', $pkMapField, $toDelete) );
				if ( 0 >= $rows )
				{
//					throw new Exception( "Record delete failed for table '$map_table'." );
				}
			}
			if ( !empty( $many_records ) )
			{
				foreach ( $many_records as $item )
				{
					$itemId = Utilities::getArrayValue( $pkManyField, $item, '' );
					$record = array($many_field => $itemId, $one_field => $one_id);
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
}