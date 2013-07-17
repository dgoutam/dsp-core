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
namespace Platform\Utility;

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;
use Platform\Interfaces\SqlDbDriverTypes;
use Platform\Yii\Utility\Pii;

/**
 * SqlDbUtilities
 * Generic database utilities
 */
class SqlDbUtilities implements SqlDbDriverTypes
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param \CDbConnection $db
	 *
	 * @return int
	 */
	public static function getDbDriverType( $db )
	{
		switch ( $db->driverName )
		{
			case 'mssql':
			case 'dblib':
			case 'sqlsrv':
				return static::DRV_SQLSRV;

			case 'mysqli':
			case 'mysql':
				return static::DRV_MYSQL;

			case 'sqlite':
			case 'sqlite2':
				return static::DRV_SQLITE;

			case 'oci':
				return static::DRV_OCSQL;

			case 'pgsql':
				return static::DRV_PGSQL;

			default:
				return static::DRV_OTHER;
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param                $name
	 *
	 * @throws BadRequestException
	 * @throws NotFoundException
	 * @return string
	 */
	public static function correctTableName( $db, $name )
	{
		if ( empty( $name ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}

		$tables = $db->schema->getTableNames();

		// make search case insensitive
		foreach ( $tables as $table )
		{
			if ( 0 == strcasecmp( $table, $name ) )
			{
				return $table;
			}
		}

		Log::error( 'Unknown table "' . $name . '" requested.' );

		throw new NotFoundException( "Table '$name' does not exist in the database." );
	}

	/**
	 * @param \CDbConnection $db
	 * @param                $name
	 *
	 * @return bool
	 * @throws BadRequestException
	 */
	public static function doesTableExist( $db, $name )
	{
		if ( empty( $name ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}

		$tables = Pii::db()->getSchema()->getTableNames();

		//	make search case insensitive
		foreach ( $tables as $table )
		{
			if ( 0 == strcasecmp( $table, $name ) )
			{
				return true;
			}
		}

		Log::notice( 'Table "' . $name . '" does not exist.' );

		return false;
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $include
	 * @param string         $exclude
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function listTables( $db, $include = '', $exclude = '' )
	{
		// todo need to assess schemas in ms sql and load them separately.
		try
		{
			$names = $db->schema->getTableNames();
			$includeArray = array_map( 'trim', explode( ',', strtolower( $include ) ) );
			$excludeArray = array_map( 'trim', explode( ',', strtolower( $exclude ) ) );
			$temp = array();
			foreach ( $names as $name )
			{
				if ( !empty( $include ) )
				{
					if ( false === array_search( strtolower( $name ), $includeArray ) )
					{
						continue;
					}
				}
				elseif ( !empty( $exclude ) )
				{
					if ( false !== array_search( strtolower( $name ), $excludeArray ) )
					{
						continue;
					}
				}
				$temp[] = $name;
			}
			$names = $temp;
			natcasesort( $names );

			return array_values( $names );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to list database tables.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $include_prefix
	 * @param string         $exclude_prefix
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function describeDatabase( $db, $include_prefix = '', $exclude_prefix = '' )
	{
		// todo need to assess schemas in ms sql and load them separately.
		try
		{
			$names = $db->schema->getTableNames();
			$temp = array();
			foreach ( $names as $name )
			{
				if ( !empty( $include_prefix ) )
				{
					if ( 0 != substr_compare( $name, $include_prefix, 0, strlen( $include_prefix ), true ) )
					{
						continue;
					}
				}
				elseif ( !empty( $exclude_prefix ) )
				{
					if ( 0 == substr_compare( $name, $exclude_prefix, 0, strlen( $exclude_prefix ), true ) )
					{
						continue;
					}
				}
				$temp[] = $name;
			}
			$names = $temp;
			natcasesort( $names );
			$labels = static::getLabels(
				array( 'and', "field=''", array( 'in', 'table', $names ) ),
				array(),
				'table,label,plural'
			);
			$tables = array();
			foreach ( $names as $name )
			{
				$label = '';
				$plural = '';
				foreach ( $labels as $each )
				{
					if ( 0 == strcasecmp( $name, $each['table'] ) )
					{
						$label = Option::get( $each, 'label' );
						$plural = Option::get( $each, 'plural' );
						break;
					}
				}
				if ( empty( $label ) )
				{
					$label = Utilities::labelize( $name );
				}
				if ( empty( $plural ) )
				{
					$plural = Utilities::pluralize( $label );
				}
				$tables[] = array( 'name' => $name, 'label' => $label, 'plural' => $plural );
			}

			return $tables;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query database schema.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param null           $names
	 * @param string         $remove_prefix
	 *
	 * @throws \Exception
	 * @return array|string
	 */
	public static function describeTables( $db, $names = null, $remove_prefix = '' )
	{
		try
		{
			$out = array();
			foreach ( $names as $table )
			{
				$out[] = static::describeTable( $db, $table, $remove_prefix );
			}

			return $out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query database schema.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $name
	 * @param string         $remove_prefix
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function describeTable( $db, $name, $remove_prefix = '' )
	{
		$name = static::correctTableName( $db, $name );
		try
		{
			$table = $db->schema->getTable( $name );
			if ( !$table )
			{
				throw new NotFoundException( "Table '$name' does not exist in the database." );
			}
			$query = $db->quoteColumnName( 'table' ) . ' = :tn';
			$labels = static::getLabels( $query, array( ':tn' => $name ) );
			$labels = static::reformatFieldLabelArray( $labels );
			$labelInfo = Option::get( $labels, '', array() );
			$publicName = $table->name;
			if ( !empty( $remove_prefix ) )
			{
				if ( substr( $publicName, 0, strlen( $remove_prefix ) ) == $remove_prefix )
				{
					$publicName = substr( $publicName, strlen( $remove_prefix ), strlen( $publicName ) );
				}
			}
			$label = Option::get( $labelInfo, 'label' );
			if ( empty( $label ) )
			{
				$label = Utilities::labelize( $publicName );
			}
			$plural = Option::get( $labelInfo, 'plural' );
			if ( empty( $plural ) )
			{
				$plural = Utilities::pluralize( $label );
			}
			$name_field = Option::get( $labelInfo, 'name_field' );

			return array(
				'name'        => $publicName,
				'label'       => $label,
				'plural'      => $plural,
				'primary_key' => $table->primaryKey,
				'name_field'  => $name_field,
				'field'       => static::describeTableFields( $db, $name, $labels ),
				'related'     => static::describeTableRelated( $db, $name )
			);
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query database schema.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param                $name
	 * @param array          $labels
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function describeTableFields( $db, $name, $labels = array() )
	{
		$name = static::correctTableName( $db, $name );
		$table = $db->schema->getTable( $name );
		if ( !$table )
		{
			throw new NotFoundException( "Table '$name' does not exist in the database." );
		}
		try
		{
			if ( empty( $labels ) )
			{
				$query = $db->quoteColumnName( 'table' ) . ' = :tn';
				$labels = static::getLabels( $query, array( ':tn' => $name ) );
				$labels = static::reformatFieldLabelArray( $labels );
			}
			$fields = array();
			foreach ( $table->columns as $column )
			{
				$labelInfo = Option::get( $labels, $column->name, array() );
				$field = static::describeFieldInternal( $column, $table->foreignKeys, $labelInfo );
				$fields[] = $field;
			}

			return $fields;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query table schema.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $table_name
	 * @param array          $field_names
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function describeFields( $db, $table_name, $field_names )
	{
		$table_name = static::correctTableName( $db, $table_name );
		$table = $db->schema->getTable( $table_name );
		if ( !$table )
		{
			throw new NotFoundException( "Table '$table_name' does not exist in the database." );
		}
		$field = array();
		try
		{
			foreach ( $table->columns as $column )
			{
				if ( false === array_search( $column->name, $field_names ) )
				{
					continue;
				}
				$query = $db->quoteColumnName( 'table' ) . ' = :tn and ' . $db->quoteColumnName( 'field' ) . ' = :fn';
				$labels = static::getLabels( $query, array( ':tn' => $table_name, ':fn' => $column->name ) );
				$labelInfo = Option::get( $labels, 0, array() );
				$field[] = static::describeFieldInternal( $column, $table->foreignKeys, $labelInfo );
				break;
			}
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query table schema.\n{$ex->getMessage()}" );
		}

		if ( empty( $field ) )
		{
			throw new NotFoundException( "No fields not found in table '$table_name'." );
		}

		return $field;
	}

	/**
	 * @param \CDbConnection $db
	 * @param                $table_name
	 * @param                $field_name
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function describeField( $db, $table_name, $field_name )
	{
		$table_name = static::correctTableName( $db, $table_name );
		$table = $db->schema->getTable( $table_name );
		if ( !$table )
		{
			throw new NotFoundException( "Table '$table_name' does not exist in the database." );
		}
		$field = array();
		try
		{
			foreach ( $table->columns as $column )
			{
				if ( 0 != strcasecmp( $column->name, $field_name ) )
				{
					continue;
				}
				$query = $db->quoteColumnName( 'table' ) . ' = :tn and ' . $db->quoteColumnName( 'field' ) . ' = :fn';
				$labels = static::getLabels( $query, array( ':tn' => $table_name, ':fn' => $field_name ) );
				$labelInfo = Option::get( $labels, 0, array() );
				$field = static::describeFieldInternal( $column, $table->foreignKeys, $labelInfo );
				break;
			}
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query table schema.\n{$ex->getMessage()}" );
		}

		if ( empty( $field ) )
		{
			throw new NotFoundException( "Field '$field_name' not found in table '$table_name'." );
		}

		return $field;
	}

	/**
	 * @param \CDbColumnSchema $column
	 * @param array            $foreign_keys
	 * @param array            $label_info
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function describeFieldInternal( $column, $foreign_keys, $label_info )
	{
		try
		{
			$label = Option::get( $label_info, 'label', '' );
			if ( empty( $label ) )
			{
				$label = Utilities::labelize( $column->name );
			}
			$validation = Option::get( $label_info, 'validation', '' );
			$picklist = Option::get( $label_info, 'picklist', '' );
			$picklist = ( !empty( $picklist ) ) ? explode( "/n", $picklist ) : array();
			$refTable = '';
			$refFields = '';
			if ( 1 == $column->isForeignKey )
			{
				$referenceTo = Option::get( $foreign_keys, $column->name );
				$refTable = Option::get( $referenceTo, 0, '' );
				$refFields = Option::get( $referenceTo, 1, '' );
			}

			return array(
				'name'               => $column->name,
				'label'              => $label,
				'type'               => static::determineDfType( $column, $label_info ),
				'data_type'          => $column->type,
				'db_type'            => $column->dbType,
				'length'             => intval( $column->size ),
				'precision'          => intval( $column->precision ),
				'scale'              => intval( $column->scale ),
				'default'            => $column->defaultValue,
				'required'           => static::determineRequired( $column ),
				'allow_null'         => $column->allowNull,
				'fixed_length'       => static::determineIfFixedLength( $column->dbType ),
				'supports_multibyte' => static::determineMultiByteSupport( $column->dbType ),
				'auto_increment'     => $column->autoIncrement,
				'is_primary_key'     => $column->isPrimaryKey,
				'is_foreign_key'     => $column->isForeignKey,
				'ref_table'          => $refTable,
				'ref_fields'         => $refFields,
				'validation'         => $validation,
				'values'             => $picklist
			);
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to query table schema.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param                $parent_table
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function describeTableRelated( $db, $parent_table )
	{
		$names = $db->schema->getTableNames();
		natcasesort( $names );
		$names = array_values( $names );
		$related = array();
		foreach ( $names as $name )
		{
			$table = $db->schema->getTable( $name );
			$fks = $fks2 = $table->foreignKeys;
			foreach ( $fks as $key => $value )
			{
				$refTable = Option::get( $value, 0, '' );
				$refField = Option::get( $value, 1, '' );
				if ( 0 == strcasecmp( $refTable, $parent_table ) )
				{
					// other, must be has_many or many_many
					$relationName = Utilities::pluralize( $name ) . '_by_' . $key;
					$related[] = array(
						'name'      => $relationName,
						'type'      => 'has_many',
						'ref_table' => $name,
						'ref_field' => $key,
						'field'     => $refField
					);
					// if other has many relationships exist, we can say these are related as well
					foreach ( $fks2 as $key2 => $value2 )
					{
						$tmpTable = Option::get( $value2, 0, '' );
						$tmpField = Option::get( $value2, 1, '' );
						if ( ( 0 != strcasecmp( $key, $key2 ) ) && // not same key
							 ( 0 != strcasecmp( $tmpTable, $name ) ) && // not self-referencing table
							 ( 0 != strcasecmp( $parent_table, $name ) )
						)
						{ // not same as parent, i.e. via reference back to self
							// not the same key
							$relationName = Utilities::pluralize( $tmpTable ) . '_by_' . $name;
							$related[] = array(
								'name'      => $relationName,
								'type'      => 'many_many',
								'ref_table' => $tmpTable,
								'ref_field' => $tmpField,
								'join'      => "$name($key,$key2)",
								'field'     => $refField
							);
						}
					}
				}
				if ( 0 == strcasecmp( $name, $parent_table ) )
				{
					// self, get belongs to relations
					$relationName = $refTable . '_by_' . $key;
					$related[] = array(
						'name'      => $relationName,
						'type'      => 'belongs_to',
						'ref_table' => $refTable,
						'ref_field' => $refField,
						'field'     => $key
					);
				}
			}
		}

		return $related;
	}

	/**
	 * @param            $column
	 * @param null|array $label_info
	 *
	 * @return string
	 */
	protected static function determineDfType( $column, $label_info = null )
	{
		$_simpleType = strstr( $column->dbType, '(', true );
		$_simpleType = ( $_simpleType ) ? strtolower( $_simpleType ) : $column->dbType;
		switch ( $_simpleType )
		{
			case 'bool':
				return 'boolean';

			case 'double':
				return 'float';

			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'bigint':
			case 'integer':
				if ( $column->isPrimaryKey && $column->autoIncrement )
				{
					return 'id';
				}
				$userIdOnUpdate = Option::get( $label_info, 'user_id_on_update' );
				if ( isset( $userIdOnUpdate ) )
				{
					return ( DataFormat::boolval( $userIdOnUpdate ) ) ? 'user_id_on_update' : 'user_id_on_create';
				}
				$userId = Option::get( $label_info, 'user_id' );
				if ( isset( $userId ) )
				{
					return 'user_id';
				}
				if ( $column->isForeignKey )
				{
					return 'reference';
				}
				if ( $column->size == 1 )
				{
					return 'boolean';
				}

				return 'integer';
				break;

			case 'binary':
			case 'varbinary':
				return 'binary';
				break;

			// string types
			case 'string':
			case 'char':
			case 'varchar':
			case 'nchar':
			case 'nvarchar':
				return 'string';
				break;

			case 'datetime2':
				return 'datetime';

			case 'datetimeoffset':
			case 'timestamp':
				$timestampOnUpdate = Option::get( $label_info, 'timestamp_on_update' );
				if ( isset( $timestampOnUpdate ) )
				{
					return ( DataFormat::boolval( $timestampOnUpdate ) ) ? 'timestamp_on_update' : 'timestamp_on_create';
				}
				break;
		}

		return $_simpleType;
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 */
	protected static function determineMultiByteSupport( $type )
	{
		switch ( $type )
		{
			case 'nchar':
			case 'nvarchar':
				return true;
			// todo mysql shows these are varchar with a utf8 character set, not in column data
			default:
				return false;
		}
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 */
	protected static function determineIfFixedLength( $type )
	{
		switch ( $type )
		{
			case 'char':
			case 'nchar':
			case 'binary':
				return true;
			default:
				return false;
		}
	}

	/**
	 * @param $column
	 *
	 * @return bool
	 */
	protected static function determineRequired( $column )
	{
		if ( ( 1 == $column->allowNull ) || ( isset( $column->defaultValue ) ) || ( 1 == $column->autoIncrement ) )
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $avail_fields
	 *
	 * @return string
	 */
	public static function listAllFieldsFromDescribe( $avail_fields )
	{
		$out = '';
		foreach ( $avail_fields as $field_info )
		{
			if ( !empty( $out ) )
			{
				$out .= ',';
			}
			$out .= $field_info['name'];
		}

		return $out;
	}

	/**
	 * @param $field_name
	 * @param $avail_fields
	 *
	 * @return null
	 */
	public static function getFieldFromDescribe( $field_name, $avail_fields )
	{
		foreach ( $avail_fields as $field_info )
		{
			if ( 0 == strcasecmp( $field_name, $field_info['name'] ) )
			{
				return $field_info;
			}
		}

		return null;
	}

	/**
	 * @param $field_name
	 * @param $avail_fields
	 *
	 * @return bool|int|string
	 */
	public static function findFieldFromDescribe( $field_name, $avail_fields )
	{
		foreach ( $avail_fields as $key => $field_info )
		{
			if ( 0 == strcasecmp( $field_name, $field_info['name'] ) )
			{
				return $key;
			}
		}

		return false;
	}

	/**
	 * @param $avail_fields
	 *
	 * @return string
	 */
	public static function getPrimaryKeyFieldFromDescribe( $avail_fields )
	{
		foreach ( $avail_fields as $field_info )
		{
			if ( $field_info['is_primary_key'] )
			{
				return $field_info['name'];
			}
		}

		return '';
	}

	/**
	 * @param     $field
	 * @param int $driver_type
	 *
	 * @throws \Exception
	 * @return array|string
	 */
	protected static function buildColumnType( $field, $driver_type = SqlDbUtilities::DRV_MYSQL )
	{
		if ( empty( $field ) )
		{
			throw new BadRequestException( "No field given." );
		}

		try
		{
			$sql = Option::get( $field, 'sql' );
			if ( !empty( $sql ) )
			{
				// raw sql definition, just pass it on
				return $sql;
			}
			$type = Option::get( $field, 'type' );
			if ( empty( $type ) )
			{
				throw new BadRequestException( "Invalid schema detected - no type element." );
			}
			/* abstract types handled by yii directly for each driver type

                pk: a generic primary key type, will be converted into int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY for MySQL;
                string: string type, will be converted into varchar(255) for MySQL;
                text: text type (long string), will be converted into text for MySQL;
                integer: integer type, will be converted into int(11) for MySQL;
                float: floating number type, will be converted into float for MySQL;
                decimal: decimal number type, will be converted into decimal for MySQL;
                datetime: datetime type, will be converted into datetime for MySQL;
                timestamp: timestamp type, will be converted into timestamp for MySQL;
                time: time type, will be converted into time for MySQL;
                date: date type, will be converted into date for MySQL;
                binary: binary data type, will be converted into blob for MySQL;
                boolean: boolean type, will be converted into tinyint(1) for MySQL;
                money: money/currency type, will be converted into decimal(19,4) for MySQL.
            */

			if ( ( 0 == strcasecmp( 'id', $type ) ) || ( 0 == strcasecmp( 'pk', $type ) ) )
			{
				return 'pk'; // simple primary key
			}

			$allowNull = Option::getBool( $field, 'allow_null', true );
			$length = Option::get( $field, 'length' );
			if ( !isset( $length ) )
			{
				$length = Option::get( $field, 'size' ); // alias
			}
			$default = Option::get( $field, 'default' );
			$quoteDefault = false;
			$isPrimaryKey = Option::getBool( $field, 'is_primary_key', false );

			switch ( strtolower( $type ) )
			{
				// some types need massaging, some need other required properties
				case "reference":
					$definition = 'int';
					break;
				case "timestamp_on_create":
					$definition = 'timestamp';
					$default = 0; // override
					$allowNull = ( isset( $field['allow_null'] ) ) ? $allowNull : false;
					break;
				case "timestamp_on_update":
					$definition = 'timestamp';
					$default = 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'; // override
					$allowNull = ( isset( $field['allow_null'] ) ) ? $allowNull : false;
					break;
				case "user_id":
				case "user_id_on_create":
				case "user_id_on_update":
					$definition = 'int';
					$allowNull = ( isset( $field['allow_null'] ) ) ? $allowNull : false;
					break;
				// numbers
				case 'bool': // alias
				case 'boolean': // alias
					$definition = 'boolean';
					// convert to bit 0 or 1
					$default = ( isset( $default ) ) ? intval( DataFormat::boolval( $default ) ) : $default;
					break;
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
				case 'integer':
					$definition = ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( 'mediumint' == $type ) ) ? 'int' : $type;
					if ( isset( $length ) )
					{
						if ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length <= 255 ) && ( $length > 0 ) )
						{
							$definition .= '(' . intval( $length ) . ')'; // sets the viewable length
						}
					}
					// convert to int
					$default = ( isset( $default ) ) ? intval( $default ) : $default;
					break;
				case 'decimal':
				case 'numeric': // alias
				case 'number': // alias
				case 'percent': // alias
					$definition = 'decimal';
					if ( !isset( $length ) )
					{
						$length = Option::get( $field, 'precision' ); // alias
					}
					if ( isset( $length ) )
					{
						$length = intval( $length );
						if ( ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length > 65 ) ) ||
							 ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $length > 38 ) )
						)
						{
							throw new BadRequestException( "Decimal precision '$length' is out of valid range." );
						}
						$scale = Option::get( $field, 'scale' );
						if ( empty( $scale ) )
						{
							$scale = Option::get( $field, 'decimals' ); // alias
						}
						if ( !empty( $scale ) )
						{
							if ( ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $scale > 30 ) ) ||
								 ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $scale > 18 ) ) ||
								 ( $scale > $length )
							)
							{
								throw new BadRequestException( "Decimal scale '$scale' is out of valid range." );
							}
							$definition .= "($length,$scale)";
						}
						else
						{
							$definition .= "($length)";
						}
					}
					// convert to float
					$default = ( isset( $default ) ) ? floatval( $default ) : $default;
					break;
				case 'float':
				case 'double':
					$definition = ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) ) ? 'float' : $type;
					if ( !isset( $length ) )
					{
						$length = Option::get( $field, 'precision' ); // alias
					}
					if ( isset( $length ) )
					{
						$length = intval( $length );
						if ( ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length > 53 ) ) ||
							 ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $length > 38 ) )
						)
						{
							throw new BadRequestException( "Decimal precision '$length' is out of valid range." );
						}
						$scale = Option::get( $field, 'scale' );
						if ( empty( $scale ) )
						{
							$scale = Option::get( $field, 'decimals' ); // alias
						}
						if ( !empty( $scale ) && !( SqlDbUtilities::DRV_SQLSRV == $driver_type ) )
						{
							if ( ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $scale > 30 ) ) ||
								 ( $scale > $length )
							)
							{
								throw new BadRequestException( "Decimal scale '$scale' is out of valid range." );
							}
							$definition .= "($length,$scale)";
						}
						else
						{
							$definition .= "($length)";
						}
					}
					// convert to float
					$default = ( isset( $default ) ) ? floatval( $default ) : $default;
					break;
				case 'money':
				case 'smallmoney':
					$definition = ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) ) ? $type : 'money'; // let yii handle it
					// convert to float
					$default = ( isset( $default ) ) ? floatval( $default ) : $default;
					break;
				// string types
				case 'string':
				case 'binary':
				case 'varbinary':
				case 'char':
				case 'varchar':
				case 'nchar':
				case 'nvarchar':
					$fixed = Option::getBool( $field, 'fixed_length', false );
					$national = Option::getBool( $field, 'supports_multibyte', false );
					if ( 0 == strcasecmp( 'string', $type ) )
					{
						if ( $fixed )
						{
							$type = ( $national ) ? 'nchar' : 'char';
						}
						else
						{
							$type = ( $national ) ? 'nvarchar' : 'varchar';
						}
						if ( !isset( $length ) )
						{
							$length = 255;
						}
					}
					elseif ( 0 == strcasecmp( 'binary', $type ) )
					{
						$type = ( $fixed ) ? 'binary' : 'varbinary';
						if ( !isset( $length ) )
						{
							$length = 255;
						}
					}
					$definition = $type;
					switch ( $type )
					{
						case 'varbinary':
						case 'varchar':
							if ( isset( $length ) )
							{
								$length = intval( $length );
								if ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $length > 8000 ) )
								{
									$length = 'max';
								}
								if ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length > 65535 ) )
								{
									// max allowed is really dependent number of string columns
									throw new BadRequestException( "String length '$length' is out of valid range." );
								}
								$definition .= "($length)";
							}
							break;
						case 'binary':
						case 'char':
							if ( isset( $length ) )
							{
								$length = intval( $length );
								if ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $length > 8000 ) )
								{
									throw new BadRequestException( "String length '$length' is out of valid range." );
								}
								if ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length > 255 ) )
								{
									throw new BadRequestException( "String length '$length' is out of valid range." );
								}
								$definition .= "($length)";
							}
							break;
						case 'nvarchar':
							if ( isset( $length ) )
							{
								$length = intval( $length );
								if ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $length > 4000 ) )
								{
									$length = 'max';
								}
								if ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length > 65535 ) )
								{
									// max allowed is really dependent number of string columns
									throw new BadRequestException( "String length '$length' is out of valid range." );
								}
								$definition .= "($length)";
							}
							break;
						case 'nchar':
							if ( isset( $length ) )
							{
								$length = intval( $length );
								if ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) && ( $length > 4000 ) )
								{
									throw new BadRequestException( "String length '$length' is out of valid range." );
								}
								if ( ( SqlDbUtilities::DRV_MYSQL == $driver_type ) && ( $length > 255 ) )
								{
									throw new BadRequestException( "String length '$length' is out of valid range." );
								}
								$definition .= "($length)";
							}
							break;
					}
					$quoteDefault = true;
					break;
				case 'text':
					$definition = ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) ) ? 'varchar(max)' : 'text'; // microsoft recommended
					$quoteDefault = true;
					break;
				case 'blob':
					$definition = ( ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) ) ? 'varbinary(max)' : 'blob'; // microsoft recommended
					$quoteDefault = true;
					break;
				case 'datetime':
					$definition = ( SqlDbUtilities::DRV_SQLSRV == $driver_type ) ? 'datetime2' : 'datetime'; // microsoft recommends
					break;
				default:
					// blind copy of column type
					$definition = $type;
			}

			// additional properties
			if ( !DataFormat::boolval( $allowNull ) )
			{
				$definition .= ' NOT NULL';
			}
			if ( isset( $default ) )
			{
				if ( $quoteDefault )
				{
					$default = "'" . $default . "'";
				}
				$definition .= ' DEFAULT ' . $default;
			}
			if ( $isPrimaryKey )
			{
				$definition .= ' PRIMARY KEY';
			}

			return $definition;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string               $table_name
	 * @param array                $fields
	 * @param bool                 $allow_update
	 * @param null|\CDbTableSchema $schema
	 *
	 * @throws \Exception
	 * @return string
	 */
	protected static function buildTableFields( $table_name, $fields, $allow_update = true, $schema = null )
	{
		if ( empty( $fields ) )
		{
			throw new BadRequestException( "No fields given." );
		}
		$columns = array();
		$alter_columns = array();
		$references = array();
		$indexes = array();
		$labels = array();
		$primaryKey = '';
		if ( isset( $schema ) )
		{
			$primaryKey = $schema->primaryKey;
		}
		if ( !isset( $fields[0] ) )
		{
			$fields = array( $fields );
		}
		foreach ( $fields as $field )
		{
			try
			{
				$name = Option::get( $field, 'name' );
				if ( empty( $name ) )
				{
					throw new BadRequestException( "Invalid schema detected - no name element." );
				}
				$type = Option::get( $field, 'type', '' );
				$colSchema = ( isset( $schema ) ) ? $schema->getColumn( $name ) : null;
				$isAlter = false;
				if ( isset( $colSchema ) )
				{
					if ( !$allow_update )
					{
						throw new BadRequestException( "Field '$name' already exists in table '$table_name'." );
					}
					if ( ( ( 0 == strcasecmp( 'id', $type ) ) || ( 0 == strcasecmp( 'pk', $type ) ) ||
						   Option::getBool( $field, 'is_primary_key', false ) ) &&
						 ( $colSchema->isPrimaryKey )
					)
					{
						// don't try to alter
					}
					else
					{
						$definition = static::buildColumnType( $field );
						if ( !empty( $definition ) )
						{
							$alter_columns[$name] = $definition;
						}
					}
					$isAlter = true;
					// todo manage type changes, data migration?
				}
				else
				{
					$definition = static::buildColumnType( $field );
					if ( !empty( $definition ) )
					{
						$columns[$name] = $definition;
					}
				}

				// extra checks
				if ( empty( $type ) )
				{
					// raw definition, just pass it on
					if ( $isAlter )
					{
						// may need to clean out references, etc?
					}
					continue;
				}

				$temp = array();
				if ( ( 0 == strcasecmp( 'id', $type ) ) || ( 0 == strcasecmp( 'pk', $type ) ) )
				{
					if ( !empty( $primaryKey ) && ( 0 != strcasecmp( $primaryKey, $name ) ) )
					{
						throw new BadRequestException( "Designating more than one column as a primary key is not allowed." );
					}
					$primaryKey = $name;
				}
				elseif ( Option::getBool( $field, 'is_primary_key', false ) )
				{
					if ( !empty( $primaryKey ) && ( 0 != strcasecmp( $primaryKey, $name ) ) )
					{
						throw new BadRequestException( "Designating more than one column as a primary key is not allowed." );
					}
					$primaryKey = $name;
				}
				elseif ( ( 0 == strcasecmp( 'reference', $type ) ) ||
						 Option::getBool( $field, 'is_foreign_key', false )
				)
				{
					// special case for references because the table referenced may not be created yet
					$refTable = Option::get( $field, 'ref_table' );
					if ( empty( $refTable ) )
					{
						throw new BadRequestException( "Invalid schema detected - no table element for reference type of $name." );
					}
					$refColumns = Option::get( $field, 'ref_fields', 'id' );
					$refOnDelete = Option::get( $field, 'ref_on_delete' );
					$refOnUpdate = Option::get( $field, 'ref_on_update' );

					// will get to it later, $refTable may not be there
					$keyName = 'fk_' . $table_name . '_' . $name;
					if ( !$isAlter || !$colSchema->isForeignKey )
					{
						$references[] = array(
							'name'       => $keyName,
							'table'      => $table_name,
							'column'     => $name,
							'ref_table'  => $refTable,
							'ref_fields' => $refColumns,
							'delete'     => $refOnDelete,
							'update'     => $refOnUpdate
						);
					}
				}
				elseif ( ( 0 == strcasecmp( 'user_id_on_create', $type ) ) )
				{ // && static::is_local_db()
					// special case for references because the table referenced may not be created yet
					$temp['user_id_on_update'] = false;
					$keyName = 'fk_' . $table_name . '_' . $name;
					if ( !$isAlter || !$colSchema->isForeignKey )
					{
						$references[] = array(
							'name'       => $keyName,
							'table'      => $table_name,
							'column'     => $name,
							'ref_table'  => 'df_sys_user',
							'ref_fields' => 'id',
							'delete'     => null,
							'update'     => null
						);
					}
				}
				elseif ( ( 0 == strcasecmp( 'user_id_on_update', $type ) ) )
				{ // && static::is_local_db()
					// special case for references because the table referenced may not be created yet
					$temp['user_id_on_update'] = true;
					$keyName = 'fk_' . $table_name . '_' . $name;
					if ( !$isAlter || !$colSchema->isForeignKey )
					{
						$references[] = array(
							'name'       => $keyName,
							'table'      => $table_name,
							'column'     => $name,
							'ref_table'  => 'df_sys_user',
							'ref_fields' => 'id',
							'delete'     => null,
							'update'     => null
						);
					}
				}
				elseif ( ( 0 == strcasecmp( 'user_id', $type ) ) )
				{ // && static::is_local_db()
					// special case for references because the table referenced may not be created yet
					$temp['user_id'] = true;
					$keyName = 'fk_' . $table_name . '_' . $name;
					if ( !$isAlter || !$colSchema->isForeignKey )
					{
						$references[] = array(
							'name'       => $keyName,
							'table'      => $table_name,
							'column'     => $name,
							'ref_table'  => 'df_sys_user',
							'ref_fields' => 'id',
							'delete'     => null,
							'update'     => null
						);
					}
				}
				elseif ( ( 0 == strcasecmp( 'timestamp_on_create', $type ) ) )
				{
					$temp['timestamp_on_update'] = false;
				}
				elseif ( ( 0 == strcasecmp( 'timestamp_on_update', $type ) ) )
				{
					$temp['timestamp_on_update'] = true;
				}
				// regardless of type
				if ( Option::getBool( $field, 'is_unique', false ) )
				{
					// will get to it later, create after table built
					$keyName = 'undx_' . $table_name . '_' . $name;
					$indexes[] = array(
						'name'   => $keyName,
						'table'  => $table_name,
						'column' => $name,
						'unique' => true,
						'drop'   => $isAlter
					);
				}
				elseif ( Option::getBool( $field, 'is_index', false ) )
				{
					// will get to it later, create after table built
					$keyName = 'ndx_' . $table_name . '_' . $name;
					$indexes[] = array(
						'name'   => $keyName,
						'table'  => $table_name,
						'column' => $name,
						'drop'   => $isAlter
					);
				}

				$picklist = '';
				$values = Option::get( $field, 'value' );
				if ( empty( $values ) )
				{
					$values = Option::getDeep( $field, 'values', 'value' );
				}
				if ( !empty( $values ) )
				{
					foreach ( $values as $value )
					{
						if ( !empty( $picklist ) )
						{
							$picklist .= "\r";
						}
						$picklist .= $value;
					}
				}
				if ( !empty( $picklist ) )
				{
					$temp['picklist'] = $picklist;
				}

				// labels
				$label = Option::get( $field, 'label' );
				if ( !empty( $label ) )
				{
					$temp['label'] = $label;
				}

				$validation = Option::get( $field, 'validation' );
				if ( !empty( $validation ) )
				{
					$temp['validation'] = $validation;
				}

				if ( !empty( $temp ) )
				{
					$temp['table'] = $table_name;
					$temp['field'] = $name;
					$labels[] = $temp;
				}
			}
			catch ( \Exception $ex )
			{
				throw $ex;
			}
		}

		return array(
			'columns'       => $columns,
			'alter_columns' => $alter_columns,
			'references'    => $references,
			'indexes'       => $indexes,
			'labels'        => $labels
		);
	}

	/**
	 * @param \CDbConnection $db
	 * @param array          $extras
	 *
	 * @return array
	 */
	protected static function createFieldExtras( $db, $extras )
	{
		$command = $db->createCommand();
		$references = Option::get( $extras, 'references', array() );
		if ( !empty( $references ) )
		{
			foreach ( $references as $reference )
			{
				$command->reset();
				$name = $reference['name'];
				$table = $reference['table'];
				$drop = Option::getBool( $reference, 'drop', false );
				if ( $drop )
				{
					try
					{
						$command->dropForeignKey( $name, $table );
					}
					catch ( \Exception $ex )
					{
						\Yii::log( $ex->getMessage() );
					}
				}
				// add new reference
				$refTable = Option::get( $reference, 'ref_table' );
				if ( !empty( $refTable ) )
				{
					if ( ( 0 == strcasecmp( 'df_sys_user', $refTable ) ) && ( $db != Pii::db() ) )
					{
						// using user id references from a remote db
						continue;
					}
					$rows = $command->addForeignKey(
						$name,
						$table,
						$reference['column'],
						$refTable,
						$reference['ref_fields'],
						$reference['delete'],
						$reference['update']
					);
				}
			}
		}
		$indexes = Option::get( $extras, 'indexes' );
		if ( !empty( $indexes ) )
		{
			foreach ( $indexes as $index )
			{
				$command->reset();
				$name = $index['name'];
				$table = $index['table'];
				$drop = Option::getBool( $index, 'drop', false );
				if ( $drop )
				{
					try
					{
						$command->dropIndex( $name, $table );
					}
					catch ( \Exception $ex )
					{
						\Yii::log( $ex->getMessage() );
					}
				}
				$unique = Option::getBool( $index, 'unique', false );
				$rows = $command->createIndex( $name, $table, $index['column'], $unique );
			}
		}
		$labels = Option::get( $extras, 'labels', array() );
		static::setLabels( $labels );
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $table_name
	 * @param array          $fields
	 * @param bool           $allow_update
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function createFields( $db, $table_name, $fields, $allow_update = true )
	{
		if ( empty( $table_name ) )
		{
			throw new BadRequestException( "Table schema received does not have a valid name." );
		}
		// does it already exist
		if ( !static::doesTableExist( $db, $table_name ) )
		{
			throw new NotFoundException( "Update schema called on a table with name '$table_name' that does not exist in the database." );
		}

		$schema = $db->schema->getTable( $table_name );
		try
		{
			$names = array();
			$results = static::buildTableFields( $table_name, $fields, $allow_update, $schema );
			$command = $db->createCommand();
			$columns = Option::get( $results, 'columns', array() );
			foreach ( $columns as $name => $definition )
			{
				$command->reset();
				$command->addColumn( $table_name, $name, $definition );
				$names[] = $name;
			}
			$columns = Option::get( $results, 'alter_columns', array() );
			foreach ( $columns as $name => $definition )
			{
				$command->reset();
				$command->alterColumn( $table_name, $name, $definition );
				$names[] = $name;
			}
			static::createFieldExtras( $db, $results );

			// refresh the schema that we just added
			\Yii::app()->getCache()->flush();
			$db->schema->refresh();

			return $names;
		}
		catch ( \Exception $ex )
		{
			Log::error( 'Exception creating fields: ' . $ex->getMessage() );
			throw $ex;
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $table_name
	 * @param array          $data
	 * @param bool           $return_labels_refs
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function createTable( $db, $table_name, $data, $return_labels_refs = false, $checkExist = true )
	{
		if ( empty( $table_name ) )
		{
			throw new BadRequestException( "Table schema received does not have a valid name." );
		}

		// does it already exist
		if ( true === $checkExist && static::doesTableExist( $db, $table_name ) )
		{
			throw new BadRequestException( "A table with name '$table_name' already exist in the database." );
		}

		$fields = Option::get( $data, 'field' );
		if ( empty( $fields ) )
		{
			$fields = Option::getDeep( $data, 'fields', 'field' );
		}

		if ( empty( $fields ) )
		{
			throw new BadRequestException( "No valid fields exist in the received table schema." );
		}

		if ( !isset( $fields[0] ) )
		{
			$fields = array( $fields );
		}

		try
		{
			$results = static::buildTableFields( $table_name, $fields );
			$columns = Option::get( $results, 'columns' );

			if ( empty( $columns ) )
			{
				throw new BadRequestException( "No valid fields exist in the received table schema." );
			}

			$command = $db->createCommand();
			$command->createTable( $table_name, $columns );

			$labels = Option::get( $results, 'labels', array() );
			// add table labels
			$label = Option::get( $data, 'label', '' );
			$plural = Option::get( $data, 'plural', '' );
			if ( !empty( $label ) || !empty( $plural ) )
			{
				$labels[] = array(
					'table'  => $table_name,
					'field'  => '',
					'label'  => $label,
					'plural' => $plural
				);
			}
			$results['labels'] = $labels;
			if ( $return_labels_refs )
			{
				return $results;
			}

			static::createFieldExtras( $db, $results );

			return array( 'name' => $table_name );
		}
		catch ( \Exception $ex )
		{
			Log::error( 'Exception creating table: ' . $ex->getMessage() );
			throw $ex;
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $table_name
	 * @param array          $data
	 * @param bool           $return_labels_refs
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function updateTable( $db, $table_name, $data, $return_labels_refs = false )
	{
		if ( empty( $table_name ) )
		{
			throw new BadRequestException( "Table schema received does not have a valid name." );
		}
		// does it already exist
		if ( !static::doesTableExist( $db, $table_name ) )
		{
			throw new BadRequestException( "Update schema called on a table with name '$table_name' that does not exist in the database." );
		}

		// is there a name update
		$newName = Option::get( $data, 'new_name', '' );
		if ( !empty( $newName ) )
		{
			// todo change table name, has issue with references
		}

		// update column types
		$fields = Option::get( $data, 'field' );
		if ( empty( $fields ) )
		{
			$fields = Option::getDeep( $data, 'fields', 'field' );
		}
		try
		{
			$command = $db->createCommand();
			$labels = array();
			$references = array();
			$indexes = array();
			if ( !empty( $fields ) )
			{
				$schema = $db->schema->getTable( $table_name );
				$results = static::buildTableFields( $table_name, $fields, true, $schema );
				$columns = Option::get( $results, 'columns', array() );
				foreach ( $columns as $name => $definition )
				{
					$command->reset();
					$command->addColumn( $table_name, $name, $definition );
				}
				$columns = Option::get( $results, 'alter_columns', array() );
				foreach ( $columns as $name => $definition )
				{
					$command->reset();
					$command->alterColumn( $table_name, $name, $definition );
				}

				$labels = Option::get( $results, 'labels', array() );
				$references = Option::get( $results, 'references', array() );
				$indexes = Option::get( $results, 'indexes', array() );
			}
			// add table labels
			$label = Option::get( $data, 'label', '' );
			$plural = Option::get( $data, 'plural', '' );
			if ( !empty( $label ) || !empty( $plural ) )
			{
				$labels[] = array(
					'table'  => $table_name,
					'field'  => '',
					'label'  => $label,
					'plural' => $plural
				);
			}

			$results = array( 'references' => $references, 'indexes' => $indexes, 'labels' => $labels );
			if ( $return_labels_refs )
			{
				return $results;
			}

			static::createFieldExtras( $db, $results );

			return array( 'name' => $table_name );
		}
		catch ( \Exception $ex )
		{
			Log::error( 'Exception updating table: ' . $ex->getMessage() );
			throw $ex;
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param array          $tables
	 * @param bool           $allow_merge
	 * @param bool           $rollback
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function createTables( $db, $tables, $allow_merge = false, $rollback = false )
	{
		// refresh the schema so we have the latest
		\Yii::app()->getCache()->flush();
		$db->schema->refresh();
		$references = array();
		$indexes = array();
		$labels = array();
		$out = array();
		$count = 0;
		$created = array();

		if ( isset( $tables[0] ) )
		{
			foreach ( $tables as $table )
			{
				try
				{
					if ( null === ( $name = Option::get( $table, 'name' ) ) )
					{
						throw new BadRequestException( "Table schema received does not have a valid name." );
					}

					//	Does it already exist
					if ( static::doesTableExist( $db, $name ) )
					{
						if ( $allow_merge )
						{
							$results = static::updateTable( $db, $name, $table, true );
						}
						else
						{
							throw new BadRequestException( "A table with name '$name' already exist in the database." );
						}
					}
					else
					{
						$results = static::createTable( $db, $name, $table, true, false );

						if ( $rollback )
						{
							$created[] = $name;
						}
					}
					$labels = array_merge( $labels, Option::get( $results, 'labels', array() ) );
					$references = array_merge( $references, Option::get( $results, 'references', array() ) );
					$indexes = array_merge( $indexes, Option::get( $results, 'indexes', array() ) );
					$out[$count] = array( 'name' => $name );
				}
				catch ( \Exception $ex )
				{
					if ( $rollback )
					{
						// delete any created tables
						throw $ex;
					}
					$out[$count] = array(
						'error' => array(
							'message' => $ex->getMessage(),
							'code'    => $ex->getCode()
						)
					);
				}
				$count++;
			}

			//	Flush the cache and refresh the schema that we just added
			\Yii::app()->getCache()->flush();
			$db->schema->refresh();
			$results = array( 'references' => $references, 'indexes' => $indexes, 'labels' => $labels );
			static::createFieldExtras( $db, $results );
		}
		else
		{ // single table, references must already be present
			try
			{
				$name = Option::get( $tables, 'name', '' );
				if ( empty( $name ) )
				{
					throw new BadRequestException( "Table schema received does not have a valid name." );
				}
				// does it already exist
				if ( static::doesTableExist( $db, $name ) )
				{
					if ( $allow_merge )
					{
						$results = static::updateTable( $db, $name, $tables, false );
					}
					else
					{
						throw new BadRequestException( "A table with name '$name' already exist in the database." );
					}
				}
				else
				{
					$results = static::createTable( $db, $name, $tables, false );
				}
				$out[] = $results;
			}
			catch ( \Exception $ex )
			{
				throw $ex;
			}
		}

		// refresh the schema that we just added
		\Yii::app()->getCache()->flush();
		$db->schema->refresh();

		return $out;
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $table_name
	 *
	 * @throws \Exception
	 */
	public static function dropTable( $db, $table_name )
	{
		if ( empty( $table_name ) )
		{
			throw new BadRequestException( "Table name received is empty." );
		}
		// does it exist
		if ( !static::doesTableExist( $db, $table_name ) )
		{
			throw new NotFoundException( "A table with name '$table_name' does not exist in the database." );
		}
		try
		{
			$command = $db->createCommand();
			$command->dropTable( $table_name );
			$where = Pii::db()->quoteColumnName( 'table' ) . ' = :tn';
			static::removeLabels( $where, array( ':tn' => $table_name ) );

			// refresh the schema that we just added
			\Yii::app()->getCache()->flush();
			$db->schema->refresh();
		}
		catch ( \Exception $ex )
		{
			Log::error( 'Exception dropping table: ' . $ex->getMessage() );
			throw $ex;
		}
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $table_name
	 * @param string         $field_name
	 *
	 * @throws \Exception
	 */
	public static function dropField( $db, $table_name, $field_name )
	{
		if ( empty( $table_name ) )
		{
			throw new BadRequestException( "Table name received is empty." );
		}
		// does it already exist
		if ( !static::doesTableExist( $db, $table_name ) )
		{
			throw new NotFoundException( "A table with name '$table_name' does not exist in the database." );
		}
		try
		{
			$command = $db->createCommand();
			$command->dropColumn( $table_name, $field_name );
			/** @var \CDbConnection $_dbLocal Local connection */
			$_dbLocal = Pii::db();
			$where = $_dbLocal->quoteColumnName( 'table' ) . ' = :tn';
			$where .= ' and ' . $_dbLocal->quoteColumnName( 'field' ) . ' = :fn';
			static::removeLabels( $where, array( ':tn' => $table_name, ':fn' => $field_name ) );

			// refresh the schema that we just added
			\Yii::app()->getCache()->flush();
			$db->schema->refresh();
		}
		catch ( \Exception $ex )
		{
			error_log( $ex->getMessage() );
			throw $ex;
		}
	}

	/**
	 * @param string | array $where
	 * @param array          $params
	 * @param string         $select
	 *
	 * @return array
	 */
	public static function getLabels( $where, $params = array(), $select = '*' )
	{
		$_db = Pii::db();
		$labels = array();
		if ( static::doesTableExist( $_db, 'df_sys_schema_extras' ) )
		{
			$command = $_db->createCommand();
			$command->select( $select );
			$command->from( 'df_sys_schema_extras' );
			$command->where( $where, $params );
			$labels = $command->queryAll();
		}

		return $labels;
	}

	/**
	 * @param $labels
	 *
	 * @return void
	 */
	public static function setLabels( $labels )
	{
		$_db = Pii::db();
		if ( !empty( $labels ) && static::doesTableExist( $_db, 'df_sys_schema_extras' ) )
		{
			// todo batch this for speed
			$command = $_db->createCommand();
			foreach ( $labels as $each )
			{
//                $service_id = Option::get($label, 'service_id');
				$table = Option::get( $each, 'table' );
				$field = Option::get( $each, 'field' );
				$where = $_db->quoteColumnName( 'table' ) . " = '$table'";
				$where .= ' and ' . $_db->quoteColumnName( 'field' ) . " = '$field'";
				$command->reset();
				$command->select( '(COUNT(*)) as ' . $_db->quoteColumnName( 'count' ) );
				$command->from( 'df_sys_schema_extras' );
				$command->where( $where );
				$count = intval( $command->queryScalar() );
				$command->reset();
				if ( 0 >= $count )
				{
					$rows = $command->insert( 'df_sys_schema_extras', $each );
				}
				else
				{
					$rows = $command->update( 'df_sys_schema_extras', $each, $where );
				}
			}
		}
	}

	/**
	 * @param      $where
	 * @param null $params
	 */
	public static function removeLabels( $where, $params = null )
	{
		$_db = Pii::db();
		if ( static::doesTableExist( $_db, 'df_sys_schema_extras' ) )
		{
			$command = $_db->createCommand();
			$command->delete( 'df_sys_schema_extras', $where, $params );
		}
	}

	/**
	 * @param $original
	 *
	 * @return array
	 */
	public static function reformatFieldLabelArray( $original )
	{
		$new = array();
		foreach ( $original as $label )
		{
			$field = Option::get( $label, 'field', '' );
			$new[$field] = $label;
		}

		return $new;
	}
}
