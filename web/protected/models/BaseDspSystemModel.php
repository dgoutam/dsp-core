<?php
use Kisma\Core\Exceptions\StorageException;

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
		return array(
			array( 'created_by_id, last_modified_by_id', 'numerical', 'integerOnly' => true ),
		);
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

//	/**
//	 * {@InheritDoc}
//	 */
//	public function afterFind()
//	{
//		parent::afterFind();
//
//		//	Correct data type
//		$this->id = intval( $this->id );
//
//		if ( isset( $this->created_by_id ) )
//		{
//			$this->created_by_id = intval( $this->created_by_id );
//		}
//		if ( isset( $this->last_modified_by_id ) )
//		{
//			$this->last_modified_by_id = intval( $this->last_modified_by_id );
//		}
//	}

	/**
	 * @param string $requested
	 *
	 * @param array  $columns Additional columns to add
	 *
	 * @return array
	 */
	public function getRetrievableAttributes( $requested, $columns = array() )
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

		//	Remove the bogusness
		$_columns = explode( ',', $requested );

		foreach ( $_columns as $_index => $_column )
		{
			if ( false !== stripos( $_column, 'password' ) || false !== stripos( $_column, 'security' ) || false !== stripos( $_column, 'confirm_' ) )
			{
				unset( $_columns[$_index] );
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
	 * @throws Kisma\Core\Exceptions\StorageException
	 * @throws Exception
	 * @return void
	 */
	protected function assignManyToOne( $one_id, $many_table, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new Exception( "The id can not be empty.", ErrorCodes::BAD_REQUEST );
		}

		$_entity = SystemManager::getNewResource( $many_table );
		$_entityPk = $_entity->getTableSchema()->primaryKey;
		$_entityTable = $_entity->tableName();
		$_lmodId = SessionManager::getCurrentUserId();

		//	Unassign existing rows
		try
		{
			$_sql
				= <<<SQL
UPDATE {$_entityTable} SET
	{$many_field} = null,
	last_modified_date = now(),
	last_modified_by_id = :last_modified_by_id
WHERE
	{$many_field} = :old_id
SQL;

			if ( false === static::execute( $_sql, array( ':old_id' => $one_id, ':last_modified_by_id' => $_lmodId ) ) )
			{
				throw new StorageException( 'Error clearing relationship assignments.' );
			}

			if ( empty( $many_records ) )
			{
				return;
			}
		}
		catch ( Exception $_ex )
		{
			throw new StorageException( 'Exception clearing relationship assignments: ' . $_ex->getMessage() );
		}

		//	Assign requested rows
		$_idList = array();

		foreach ( $many_records as $_row )
		{
			$_idList[] = $_row->{$_entityPk};
		}

		try
		{
			$_sql
				= <<<SQL
UPDATE {$_entityTable} SET
	{$many_field} = :new_id
	last_modified_date = now(),
	last_modified_by_id = :last_modified_by_id
WHERE
	{$_entityPk} in (:id_list)
SQL;

			if ( false === static::execute( $_sql, array( ':new_id' => $one_id, ':last_modified_by_id' => $_lmodId, ':id_list' => implode( ',', $_idList ) ) ) )
			{
				throw new StorageException( 'Error setting relationship assignments.' );
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

}