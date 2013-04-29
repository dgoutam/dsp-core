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

/**
 * SchemaSvc
 * A service to handle SQL database schema-related services accessed through the REST API.
 */
class SchemaSvc extends RestService
{

	// Members

	/**
	 * @var
	 */
	protected $tableName;

	/**
	 * @var
	 */
	protected $fieldName;

	/**
	 * @var CDbConnection
	 */
	protected $_sqlConn;

	/**
	 * @var boolean
	 */
	protected $_isNative = false;

	/**
	 * Create a new SchemaSvc
	 *
	 * @param array $config
	 * @param bool  $native
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $config, $native = false )
	{
		parent::__construct( $config );

		$this->_isNative = $native;
		if ( $native )
		{
			$this->_sqlConn = Yii::app()->db;
			$this->_driverType = DbUtilities::getDbDriverType( $this->_sqlConn );
		}
		else
		{
			$type = Utilities::getArrayValue( 'storage_type', $config, '' );
			$credentials = Utilities::getArrayValue( 'credentials', $config, array() );
			$dsn = Utilities::getArrayValue( 'dsn', $credentials, '' );
			$user = Utilities::getArrayValue( 'user', $credentials, '' );
			$pwd = Utilities::getArrayValue( 'pwd', $credentials, '' );
			if ( empty( $dsn ) )
			{
				throw new InvalidArgumentException( 'DB connection string (DSN) can not be empty.' );
			}
			if ( empty( $user ) )
			{
				throw new InvalidArgumentException( 'DB admin name can not be empty.' );
			}
			if ( empty( $pwd ) )
			{
				throw new InvalidArgumentException( 'DB admin password can not be empty.' );
			}

			// create pdo connection, activate later
			Utilities::markTimeStart( 'DB_TIME' );
			$this->_sqlConn = new CDbConnection( $dsn, $user, $pwd );
			$this->_driverType = DbUtilities::getDbDriverType( $this->_sqlConn );
			switch ( $this->_driverType )
			{
				case DbUtilities::DRV_MYSQL:
					$this->_sqlConn->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );
					$this->_sqlConn->setAttribute( 'charset', 'utf8' );
					break;
				case DbUtilities::DRV_SQLSRV:
					$this->_sqlConn->setAttribute( constant( 'PDO::SQLSRV_ATTR_DIRECT_QUERY' ), true );
					$this->_sqlConn->setAttribute( "MultipleActiveResultSets", false );
					$this->_sqlConn->setAttribute( "ReturnDatesAsStrings", true );
					$this->_sqlConn->setAttribute( "CharacterSet", "UTF-8" );
					break;
			}
			Utilities::markTimeStop( 'DB_TIME' );
		}

		$attributes = Utilities::getArrayValue( 'parameters', $config, array() );
		if ( !empty( $attributes ) && is_array( $attributes ) )
		{
			foreach ( $attributes as $key => $value )
			{
				$this->_sqlConn->setAttribute( $key, $value );
			}
		}
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		if ( !$this->_isNative )
		{
			unset( $this->_sqlConn );
		}
	}

	/**
	 * Swagger output for common api parameters
	 *
	 * @param        $parameters
	 * @param string $method
	 *
	 * @return array
	 */
	public static function swaggerParameters( $parameters, $method = '' )
	{
		$swagger = array();
		foreach ( $parameters as $param )
		{
			switch ( $param )
			{
				case 'table_name':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the table to perform operations on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'field_name':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the table field/column to perform operations on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'include_schema':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Include the schema of the table queried.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'tables':
					$swagger[] = array(
						"paramType"     => "body",
						"name"          => $param,
						"description"   => "Array of table definitions.",
						"dataType"      => "Tables",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'fields':
					$swagger[] = array(
						"paramType"     => "body",
						"name"          => $param,
						"description"   => "Array of field definitions.",
						"dataType"      => "Fields",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'field_props':
					$swagger[] = array(
						"paramType"     => "body",
						"name"          => $param,
						"description"   => "Array of field properties.",
						"dataType"      => "FieldSchema",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
			}
		}

		return $swagger;
	}

	// Controller based methods

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		$apis = array(
			array(
				'path'        => '/' . $this->_apiName,
				'description' => $this->_description,
				'operations'  => array(
					array(
						"httpMethod"     => "POST",
						"summary"        => "Create one or more tables",
						"notes"          => "Post data should be a single table definition or an array of table definitions",
						"responseClass"  => "array",
						"nickname"       => "createTables",
						"parameters"     => static::swaggerParameters( array('tables') ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one or more tables",
						"notes"          => "Post data should be a single table definition or an array of table definitions",
						"responseClass"  => "array",
						"nickname"       => "updateTables",
						"parameters"     => static::swaggerParameters( array('tables') ),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $this->_apiName . '/{table_name}',
				'description' => 'Operations for per table administration.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve table definition for the given table",
						"notes"          => "This describes the table, its fields and relations to other tables.",
						"responseClass"  => "TableSchema",
						"nickname"       => "describeTable",
						"parameters"     => static::swaggerParameters( array( 'table_name' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Create one or more fields in the given table",
						"notes"          => "Post data should be an array of field properties for a single record or an array of fields",
						"responseClass"  => "array",
						"nickname"       => "createFields",
						"parameters"     => static::swaggerParameters( array( 'table_name','fields' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one or more fields in the given table",
						"notes"          => "Post data should be an array of field properties for a single record or an array of fields",
						"responseClass"  => "array",
						"nickname"       => "updateFields",
						"parameters"     => static::swaggerParameters( array( 'table_name','fields' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete (aka drop) the given table",
						"notes"          => "Careful, this drops the database table and all of its contents.",
						"responseClass"  => "array",
						"nickname"       => "deleteTable",
						"parameters"     => static::swaggerParameters( array( 'table_name' ) ),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $this->_apiName . '/{table_name}/{field_name}',
				'description' => 'Operations for single record administration.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve the definition of the given field for the given table",
						"notes"          => "This describes the field and its properties.",
						"responseClass"  => "FieldSchema",
						"nickname"       => "describeField",
						"parameters"     => static::swaggerParameters( array( 'table_name', 'field_name' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one record by identifier",
						"notes"          => "Post data should be an array of field properties for the given field",
						"responseClass"  => "array",
						"nickname"       => "updateField",
						"parameters"     => static::swaggerParameters( array( 'table_name', 'field_name', 'field_props' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "DELETE (aka DROP) the given field FROM the given TABLE",
						"notes"          => "Careful, this drops the database table field/column and all of its contents.",
						"responseClass"  => "array",
						"nickname"       => "deleteField",
						"parameters"     => static::swaggerParameters( array( 'table_name', 'field_name' ) ),
						"errorResponses" => array()
					),
				)
			),
		);
		$apis = array_merge( parent::getSwaggerApis(), $apis );

		return $apis;
	}

	public function getSwaggerModels()
	{
		$models = array(
			"Tables" => array(
				"id" => "Tables",
				"properties" => array(
					"table" => array(
						"type" => "array",
						"items" => array( '$ref' => "TableSchema"),
						"description" => "An array of table definitions."
					),
				)
			),
			"TableSchema" => array(
				"id" => "TableSchema",
				"properties" => array(
					"name" => array(
						"type" => "string",
						"description" => "Identifier/Name for the table."
					),
					"label" => array(
						"type" => "string",
						"description" => "Displayable singular name for the table."
					),
					"plural" => array(
						"type" => "string",
						"description" => "Displayable plural name for the table."
					),
					"primary_key" => array(
						"type" => "string",
						"description" => "Field(s), if any, that represent the primary key of each record."
					),
					"name_field" => array(
						"type" => "string",
						"description" => "Field(s), if any, that represent the name of each record."
					),
					"field" => array(
						"type" => "array",
						"items" => array( '$ref' => "FieldSchema"),
						"description" => "An array of available fields in each record."
					),
					"related" => array(
						"type" => "array",
						"items" => array( '$ref' => "Related"),
						"description" => "An array of available relationships to other tables."
					),
				)
			),
			"Fields" => array(
				"id" => "Fields",
				"properties" => array(
					"field" => array(
						"type" => "array",
						"items" => array( '$ref' => "FieldSchema"),
						"description" => "An array of field definitions."
					),
				)
			),
			"FieldSchema" => array(
				"id" => "FieldSchema",
				"properties" => array(
					"name" => array(
						"type" => "string",
						"description" => "The API name of the field."
					),
					"label" => array(
						"type" => "string",
						"description" => "The displayable label for the field."
					),
					"type" => array(
						"type" => "string",
						"description" => "The DSP abstract data type for this field."
					),
					"db_type" => array(
						"type" => "string",
						"description" => "The native database type used for this field."
					),
					"length" => array(
						"type" => "integer",
						"description" => "The maximum length allowed (in characters for string, displayed for numbers)."
					),
					"precision" => array(
						"type" => "integer",
						"description" => "Total number of places for numbers."
					),
					"scale" => array(
						"type" => "integer",
						"description" => "Number of decimal places allowed for numbers."
					),
					"default" => array(
						"type" => "string",
						"description" => "Default value for this field."
					),
					"required" => array(
						"type" => "boolean",
						"description" => "Is a value required for record creation."
					),
					"allow_null" => array(
						"type" => "boolean",
						"description" => "Is null allowed as a value."
					),
					"fixed_length" => array(
						"type" => "boolean",
						"description" => "Is the length fixed (not variable)."
					),
					"supports_multibyte" => array(
						"type" => "boolean",
						"description" => "Does the data type support multibyte characters."
					),
					"auto_increment" => array(
						"type" => "boolean",
						"description" => "Does the integer field value increment upon new record creation."
					),
					"is_primary_key" => array(
						"type" => "boolean",
						"description" => "Is this field used as/part of the primary key."
					),
					"is_foreign_key" => array(
						"type" => "boolean",
						"description" => "Is this field used as a foreign key."
					),
					"ref_table" => array(
						"type" => "string",
						"description" => "For foreign keys, the referenced table name."
					),
					"ref_fields" => array(
						"type" => "string",
						"description" => "For foreign keys, the referenced table field name."
					),
					"validation" => array(
						"type" => "string",
						"description" => "Comma-delimited list of validations to be performed on this field."
					),
					"values" => array(
						"type" => "array",
						"item" => array( '$ref' => "string"),
						"description" => "Selectable string values for picklist validation."
					),
				)
			),
			"Related" => array(
				"id" => "Related",
				"properties" => array(
					"name" => array(
						"type" => "string",
						"description" => "Name of the relationship."
					),
					"type" => array(
						"type" => "string",
						"description" => "Relationship type - belongs_to, has_many, many_many."
					),
					"ref_table" => array(
						"type" => "string",
						"description" => "The table name that is referenced by the relationship."
					),
					"ref_field" => array(
						"type" => "string",
						"description" => "The field name that is referenced by the relationship."
					),
					"join" => array(
						"type" => "string",
						"description" => "The intermediate joining table used for many_many relationships."
					),
					"field" => array(
						"type" => "string",
						"description" => "The current table field that is used in the relationship."
					),
				)
			),
		);
		$models = array_merge( parent::getSwaggerModels(), $models );

		return $models;
	}

	/**
	 * @return array
	 */
	public function actionGet()
	{
		$this->detectCommonParams();
		if ( empty( $this->tableName ) )
		{
			$result = $this->describeDatabase();
			$result = array( 'resource' => $result );
		}
		else
		{
			if ( empty( $this->fieldName ) )
			{
				$result = $this->describeTable( $this->tableName );
			}
			else
			{
				$result = $this->describeField( $this->tableName, $this->fieldName );
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		if ( empty( $this->tableName ) )
		{
			$tables = Utilities::getArrayValue( 'table', $data, '' );
			if ( empty( $tables ) )
			{
				// temporary, layer created from xml to array conversion
				$tables = ( isset( $data['tables']['table'] ) ) ? $data['tables']['table'] : '';
			}
			if ( empty( $tables ) )
			{
				// could be a single table definition
				return $this->createTable( $data );
			}
			$result = $this->createTables( $tables );

			return array( 'table' => $result );
		}
		else
		{
			if ( empty( $this->fieldName ) )
			{
				// create fields in existing table
				$fields = Utilities::getArrayValue( 'field', $data, '' );
				if ( empty( $fields ) )
				{
					// temporary, layer created from xml to array conversion
					$fields = ( isset( $data['fields']['field'] ) ) ? $data['fields']['field'] : '';
				}
				if ( empty( $fields ) )
				{
					// could be a single field definition
					return $this->createField( $this->tableName, $data );
				}
				$result = $this->createFields( $this->tableName, $fields );

				return array( 'field' => $result );
			}
			else
			{
				// create new field indices?
				throw new Exception( 'No new field resources currently supported.' );
			}
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		if ( empty( $this->tableName ) )
		{
			$tables = Utilities::getArrayValue( 'table', $data, '' );
			if ( empty( $tables ) )
			{
				// temporary, layer created from xml to array conversion
				$tables = ( isset( $data['tables']['table'] ) ) ? $data['tables']['table'] : '';
			}
			if ( empty( $tables ) )
			{
				// could be a single table definition
				return $this->updateTable( $data );
			}
			$result = $this->updateTables( $tables );

			return array( 'table' => $result );
		}
		else
		{
			if ( empty( $this->fieldName ) )
			{
				// create fields in existing table
				$fields = Utilities::getArrayValue( 'field', $data, '' );
				if ( empty( $fields ) )
				{
					// temporary, layer created from xml to array conversion
					$fields = ( isset( $data['fields']['field'] ) ) ? $data['fields']['field'] : '';
				}
				if ( empty( $fields ) )
				{
					// could be a single field definition
					return $this->updateField( $this->tableName, '', $data );
				}
				$result = $this->updateFields( $this->tableName, $fields );

				return array( 'field' => $result );
			}
			else
			{
				// create new field in existing table
				if ( empty( $data ) )
				{
					throw new Exception( 'No data in schema create request.' );
				}

				return $this->updateField( $this->tableName, $this->fieldName, $data );
			}
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		if ( empty( $this->tableName ) )
		{
			$tables = Utilities::getArrayValue( 'table', $data, '' );
			if ( empty( $tables ) )
			{
				// temporary, layer created from xml to array conversion
				$tables = ( isset( $data['tables']['table'] ) ) ? $data['tables']['table'] : '';
			}
			if ( empty( $tables ) )
			{
				// could be a single table definition
				return $this->updateTable( $data );
			}
			$result = $this->updateTables( $tables );

			return array( 'table' => $result );
		}
		else
		{
			if ( empty( $this->fieldName ) )
			{
				// create fields in existing table
				$fields = Utilities::getArrayValue( 'field', $data, '' );
				if ( empty( $fields ) )
				{
					// temporary, layer created from xml to array conversion
					$fields = ( isset( $data['fields']['field'] ) ) ? $data['fields']['field'] : '';
				}
				if ( empty( $fields ) )
				{
					// could be a single field definition
					return $this->updateField( $this->tableName, '', $data );
				}
				$result = $this->updateFields( $this->tableName, $fields );

				return array( 'field' => $result );
			}
			else
			{
				// create new field in existing table
				if ( empty( $data ) )
				{
					throw new Exception( 'No data in schema create request.' );
				}

				return $this->updateField( $this->tableName, $this->fieldName, $data );
			}
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->detectCommonParams();
		if ( !empty( $this->tableName ) )
		{
			if ( !empty( $this->fieldName ) )
			{
				$this->deleteField( $this->tableName, $this->fieldName );

				return array( 'field' => $this->fieldName );
			}
			else
			{
				$this->deleteTable( $this->tableName );

				return array( 'table' => $this->tableName );
			}
		}
		else
		{
			throw new Exception( 'Invalid format for DELETE Table request.' );
		}
	}

	/**
	 *
	 */
	protected function detectCommonParams()
	{
		$resource = Utilities::getArrayValue( 'resource', $_GET, '' );
		$resource = ( !empty( $resource ) ) ? explode( '/', $resource ) : array();
		$this->tableName = ( isset( $resource[0] ) ) ? $resource[0] : '';
		$this->fieldName = ( isset( $resource[1] ) ) ? $resource[1] : '';
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function describeDatabase()
	{
		$this->checkPermission( 'read' );
		$exclude = '';
		if ( $this->_isNative )
		{
			// check for system tables
			$exclude = SystemManager::SYSTEM_TABLE_PREFIX;
		}
		try
		{
			return DbUtilities::describeDatabase( $this->_sqlConn, '', $exclude );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error describing database tables.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table_list
	 *
	 * @return array|string
	 * @throws Exception
	 */
	public function describeTables( $table_list )
	{
		$tables = array_map( 'trim', explode( ',', trim( $table_list, ',' ) ) );
		// check for system tables and deny
		$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
		foreach ( $tables as $table )
		{
			if ( $this->_isNative )
			{
				if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
				{
					throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
				}
			}
			$this->checkPermission( 'read', $table );
		}
		try
		{
			return DbUtilities::describeTables( $this->_sqlConn, $tables );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error describing database tables '$table_list'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 *
	 * @return array
	 * @throws Exception
	 */
	public function describeTable( $table )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
		$this->checkPermission( 'read', $table );
		try
		{
			return DbUtilities::describeTable( $this->_sqlConn, $table );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error describing database table '$table'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 * @param $field
	 *
	 * @return array
	 * @throws Exception
	 */
	public function describeField( $table, $field )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
		$this->checkPermission( 'read', $table );
		try
		{
			return DbUtilities::describeField( $this->_sqlConn, $table, $field );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error describing database table '$table' field '$field'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param      $tables
	 * @param bool $allow_merge
	 *
	 * @throws Exception
	 * @return array
	 */
	public function createTables( $tables, $allow_merge = false )
	{
		if ( !isset( $tables ) || empty( $tables ) )
		{
			throw new Exception( 'There are no table sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( isset( $tables[0] ) )
			{
				foreach ( $tables as $table )
				{
					$name = Utilities::getArrayValue( 'name', $table, '' );
					if ( 0 === substr_compare( $name, $sysPrefix, 0, strlen( $sysPrefix ) ) )
					{
						throw new Exception( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created.", ErrorCodes::BAD_REQUEST );
					}
				}
			}
			else
			{ // single table
				$name = Utilities::getArrayValue( 'name', $tables, '' );
				if ( 0 === substr_compare( $name, $sysPrefix, 0, strlen( $sysPrefix ) ) )
				{
					throw new Exception( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created.", ErrorCodes::BAD_REQUEST );
				}
			}
		}
		$this->checkPermission( 'create' );

		return DbUtilities::createTables( $this->_sqlConn, $tables, $allow_merge );
	}

	/**
	 * @param $table
	 *
	 * @return array
	 * @throws Exception
	 */
	public function createTable( $table )
	{
		$result = $this->createTables( $table );

		return Utilities::getArrayValue( 0, $result, array() );
	}

	/**
	 * @param $table
	 * @param $fields
	 *
	 * @throws Exception
	 * @return array
	 */
	public function createFields( $table, $fields )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
		$this->checkPermission( 'create', $table );
		try
		{
			$names = DbUtilities::createFields( $this->_sqlConn, $table, $fields );
			return DbUtilities::describeFields( $this->_sqlConn, $table, $names );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error creating database fields for table '$table'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 * @param $data
	 *
	 * @throws Exception
	 * @return array
	 */
	public function createField( $table, $data )
	{
		$result = $this->createFields( $table, $data );

		return Utilities::getArrayValue( 0, $result, array() );
	}

	/**
	 * @param $tables
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function updateTables( $tables )
	{
		if ( !isset( $tables ) || empty( $tables ) )
		{
			throw new Exception( 'There are no table sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( isset( $tables[0] ) )
			{
				foreach ( $tables as $table )
				{
					$name = Utilities::getArrayValue( 'name', $table, '' );
					if ( 0 === substr_compare( $name, $sysPrefix, 0, strlen( $sysPrefix ) ) )
					{
						throw new Exception( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created.", ErrorCodes::BAD_REQUEST );
					}
				}
			}
			else
			{ // single table
				$name = Utilities::getArrayValue( 'name', $tables, '' );
				if ( 0 === substr_compare( $name, $sysPrefix, 0, strlen( $sysPrefix ) ) )
				{
					throw new Exception( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created.", ErrorCodes::BAD_REQUEST );
				}
			}
		}
		$this->checkPermission( 'update' );

		return DbUtilities::createTables( $this->_sqlConn, $tables, true );
	}

	/**
	 * @param $table
	 *
	 * @return array
	 * @throws Exception
	 */
	public function updateTable( $table )
	{
		$result = $this->updateTables( $table );

		return Utilities::getArrayValue( 0, $result, array() );
	}

	/**
	 * @param $table
	 * @param $fields
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateFields( $table, $fields )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
		$this->checkPermission( 'update', $table );
		try
		{
			$names = DbUtilities::createFields( $this->_sqlConn, $table, $fields, true );
			return DbUtilities::describeFields( $this->_sqlConn, $table, $names );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating database table '$table'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 * @param $field
	 * @param $data
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateField( $table, $field, $data )
	{
		if ( !empty( $field ) )
		{
			$data['name'] = $field;
		}
		$result = $this->updateFields( $table, $data );

		return Utilities::getArrayValue( 0, $result, array() );
	}

	/**
	 * @param $table
	 *
	 * @throws Exception
	 */
	public function deleteTable( $table )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
		$this->checkPermission( 'delete', $table );
		DbUtilities::dropTable( $this->_sqlConn, $table );
	}

	/**
	 * @param $table
	 * @param $field
	 *
	 * @throws Exception
	 */
	public function deleteField( $table, $field )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
		$this->checkPermission( 'delete', $table );
		DbUtilities::dropField( $this->_sqlConn, $table, $field );
	}

}
