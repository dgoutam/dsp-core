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
namespace Platform\Services;

use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;
use Platform\Utility\RestRequest;
use Platform\Utility\SqlDbUtilities;
use Platform\Utility\Utilities;
use Platform\Yii\Utility\Pii;
use Swagger\Annotations as SWG;

/**
 * SchemaSvc
 * A service to handle SQL database schema-related services accessed through the REST API.
 *
 * @SWG\Resource(
 *   resourcePath="/{sql_schema}"
 * )
 *
 * @SWG\Model(id="Tables",
 * @SWG\Property(name="table",type="Array",items="$ref:TableSchema",description="An array of table definitions.")
 * )
 * @SWG\Model(id="TableSchema",
 * @SWG\Property(name="name",type="string",description="Identifier/Name for the table."),
 * @SWG\Property(name="label",type="Array",items="$ref:EmailAddress",description="Displayable singular name for the table."),
 * @SWG\Property(name="plural",type="Array",items="$ref:EmailAddress",description="Displayable plural name for the table."),
 * @SWG\Property(name="primary_key",type="string",description="Field(s), if any, that represent the primary key of each record."),
 * @SWG\Property(name="name_field",type="string",description="Field(s), if any, that represent the name of each record."),
 * @SWG\Property(name="field",type="Array",items="$ref:FieldSchema",description="An array of available fields in each record."),
 * @SWG\Property(name="related",type="Array",items="$ref:RelatedSchema",description="An array of available relationships to other tables.")
 * )
 * @SWG\Model(id="Fields",
 * @SWG\Property(name="field",type="Array",items="$ref:FieldSchema",description="An array of field definitions.")
 * )
 * @SWG\Model(id="FieldSchema",
 * @SWG\Property(name="name",type="string",description="The API name of the field."),
 * @SWG\Property(name="label",type="string",description="The displayable label for the field."),
 * @SWG\Property(name="type",type="string",description="The DSP abstract data type for this field."),
 * @SWG\Property(name="db_type",type="string",description="The native database type used for this field."),
 * @SWG\Property(name="length",type="int",description="The maximum length allowed (in characters for string, displayed for numbers)."),
 * @SWG\Property(name="precision",type="int",description="Total number of places for numbers."),
 * @SWG\Property(name="scale",type="int",description="Number of decimal places allowed for numbers."),
 * @SWG\Property(name="default",type="string",description="Default value for this field."),
 * @SWG\Property(name="required",type="boolean",description="Is a value required for record creation."),
 * @SWG\Property(name="allow_null",type="boolean",description="Is null allowed as a value."),
 * @SWG\Property(name="fixed_length",type="boolean",description="Is the length fixed (not variable)."),
 * @SWG\Property(name="supports_multibyte",type="boolean",description="Does the data type support multibyte characters."),
 * @SWG\Property(name="auto_increment",type="boolean",description="Does the integer field value increment upon new record creation."),
 * @SWG\Property(name="is_primary_key",type="boolean",description="Is this field used as/part of the primary key."),
 * @SWG\Property(name="is_foreign_key",type="boolean",description="Is this field used as a foreign key."),
 * @SWG\Property(name="ref_table",type="string",description="For foreign keys, the referenced table name."),
 * @SWG\Property(name="ref_fields",type="string",description="For foreign keys, the referenced table field name."),
 * @SWG\Property(name="validation",type="Array",items="$ref:string",description="validations to be performed on this field."),
 * @SWG\Property(name="values",type="Array",items="$ref:string",description="Selectable string values for picklist validation.")
 * )
 * @SWG\Model(id="Relateds",
 * @SWG\Property(name="related",type="Array",items="$ref:RelatedSchema",description="An array of relationship definitions.")
 * )
 * @SWG\Model(id="RelatedSchema",
 * @SWG\Property(name="name",type="string",description="Name of the relationship."),
 * @SWG\Property(name="type",type="string",description="Relationship type - belongs_to, has_many, many_many."),
 * @SWG\Property(name="ref_table",type="string",description="The table name that is referenced by the relationship."),
 * @SWG\Property(name="ref_field",type="string",description="The field name that is referenced by the relationship."),
 * @SWG\Property(name="join",type="string",description="The intermediate joining table used for many_many relationships."),
 * @SWG\Property(name="field",type="string",description="The current table field that is used in the relationship.")
 * )
 *
 *
 */
class SchemaSvc extends RestService
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var
	 */
	protected $_tableName;
	/**
	 * @var
	 */
	protected $_fieldName;
	/**
	 * @var \CDbConnection
	 */
	protected $_sqlConn;
	/**
	 * @var boolean
	 */
	protected $_isNative = false;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new SchemaSvc
	 *
	 * @param array $config
	 * @param bool  $native
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $config, $native = false )
	{
		parent::__construct( $config );

		if ( ( $this->_isNative = $native ) )
		{
			$this->_sqlConn = Pii::db();
			$this->_driverType = SqlDbUtilities::getDbDriverType( $this->_sqlConn );
		}
		else
		{
			$_credentials = Options::get( $config, 'credentials', array() );
			$_dsn = Options::get( $_credentials, 'dsn' );
			$_user = Options::get( $_credentials, 'user' );
			$_password = Options::get( $_credentials, 'pwd' );

			if ( empty( $_dsn ) )
			{
				throw new \InvalidArgumentException( 'DB connection string (DSN) can not be empty.' );
			}

			if ( empty( $_user ) )
			{
				throw new \InvalidArgumentException( 'DB admin name can not be empty.' );
			}

			if ( empty( $_password ) )
			{
				throw new \InvalidArgumentException( 'DB admin password can not be empty.' );
			}

			//	Create pdo connection, activate later
			Utilities::markTimeStart( 'DB_TIME' );

			$this->_sqlConn = new \CDbConnection( $_dsn, $_user, $_password );
			$this->_driverType = SqlDbUtilities::getDbDriverType( $this->_sqlConn );

			switch ( $this->_driverType )
			{
				case SqlDbUtilities::DRV_MYSQL:
					$this->_sqlConn->setAttribute( \PDO::ATTR_EMULATE_PREPARES, true );
					$this->_sqlConn->setAttribute( 'charset', 'utf8' );
					break;

				case SqlDbUtilities::DRV_SQLSRV:
					$this->_sqlConn->setAttribute( constant( 'PDO::SQLSRV_ATTR_DIRECT_QUERY' ), true );
					$this->_sqlConn->setAttribute( 'MultipleActiveResultSets', false );
					$this->_sqlConn->setAttribute( 'ReturnDatesAsStrings', true );
					$this->_sqlConn->setAttribute( 'CharacterSet', 'UTF-8' );
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
	 * @SWG\Api(
	 *             path="/{sql_schema}", description="Operations available for SQL DB Schemas.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *             httpMethod="GET", summary="List resources available for database schema.",
	 *             notes="See listed operations for each resource available.",
	 *             responseClass="Resources", nickname="getResources"
	 *     ),
	 * @SWG\Operation(
	 *             httpMethod="POST", summary="Create one or more tables.",
	 *             notes="Post data should be a single table definition or an array of table definitions.",
	 *             responseClass="Resources", nickname="createTables"
	 *     ),
	 * @SWG\Operation(
	 *             httpMethod="PUT", summary="Update one or more tables.",
	 *             notes="Post data should be a single table definition or an array of table definitions.",
	 *             responseClass="Resources", nickname="updateTables"
	 *     )
	 *   )
	 * )
	 * @SWG\Api(
	 *             path="/{sql_schema}/{table_name}", description="Operations for per table administration.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *             httpMethod="GET", summary="Retrieve table definition for the given table.",
	 *             notes="This describes the table, its fields and relations to other tables.",
	 *             responseClass="TableSchema", nickname="describeTable",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="POST", summary="Create one or more fields in the given table.",
	 *             notes="Post data should be an array of field properties for a single record or an array of fields.",
	 *             responseClass="Success", nickname="createFields",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Array of field definitions.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Fields"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="PUT", summary="Update one or more fields in the given table.",
	 *             notes="Post data should be an array of field properties for a single record or an array of fields.",
	 *             responseClass="Success", nickname="updateFields",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Array of field definitions.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Fields"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="DELETE", summary="Delete (aka drop) the given table.",
	 *             notes="Careful, this drops the database table and all of its contents.",
	 *             responseClass="Success", nickname="deleteTable",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *     )
	 *   )
	 *
	 * @SWG\Api(
	 *             path="/{sql_schema}/{table_name}/{field_name}", description="Operations for single field administration.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *             httpMethod="GET", summary="Retrieve the definition of the given field for the given table.",
	 *             notes="This describes the field and its properties.",
	 *             responseClass="FieldSchema", nickname="describeField",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="field_name", description="Name of the field to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="PUT", summary="Update one record by identifier.",
	 *             notes="Post data should be an array of field properties for the given field.",
	 *             responseClass="Success", nickname="updateField",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="field_name", description="Name of the field to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="field_props", description="Array of field properties.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="FieldSchema"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="DELETE", summary="DELETE (aka DROP) the given field FROM the given TABLE.",
	 *             notes="Careful, this drops the database table field/column and all of its contents.",
	 *             responseClass="Success", nickname="deleteField",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="field_name", description="Name of the field to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *     )
	 *   )
	 *
	 * @return array|bool
	 * @throws BadRequestException
	 */
	protected function _handleResource()
	{
		if ( empty( $this->_tableName ) )
		{
			switch ( $this->_action )
			{
				case self::Get:
					$result = $this->describeDatabase();

					return array( 'resource' => $result );
					break;
				case self::Post:
					$data = RestRequest::getPostDataAsArray();
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
					break;
				case self::Put:
				case self::Patch:
				case self::Merge:
					$data = RestRequest::getPostDataAsArray();
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
					break;
				case self::Delete:
					throw new BadRequestException( 'Invalid format for DELETE Table request.' );
					break;
				default:
					return false;
			}
		}
		else
		{
			if ( empty( $this->_fieldName ) )
			{
				switch ( $this->_action )
				{
					case self::Get:
						return $this->describeTable( $this->_tableName );
						break;
					case self::Post:
						$data = RestRequest::getPostDataAsArray();
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
							return $this->createField( $this->_tableName, $data );
						}
						$result = $this->createFields( $this->_tableName, $fields );

						return array( 'field' => $result );
						break;
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = RestRequest::getPostDataAsArray();
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
							return $this->updateField( $this->_tableName, '', $data );
						}
						$result = $this->updateFields( $this->_tableName, $fields );

						return array( 'field' => $result );
						break;
					case self::Delete:
						$this->deleteTable( $this->_tableName );

						return array( 'table' => $this->_tableName );
						break;
					default:
						return false;
				}
			}
			else
			{
				switch ( $this->_action )
				{
					case self::Get:
						return $this->describeField( $this->_tableName, $this->_fieldName );
						break;
					case self::Post:
						// create new field indices?
						throw new BadRequestException( 'No new field resources currently supported.' );
						break;
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = RestRequest::getPostDataAsArray();
						// create new field in existing table
						if ( empty( $data ) )
						{
							throw new BadRequestException( 'No data in schema create request.' );
						}

						return $this->updateField( $this->_tableName, $this->_fieldName, $data );
						break;
					case self::Delete:
						$this->deleteField( $this->_tableName, $this->_fieldName );

						return array( 'field' => $this->_fieldName );
						break;
					default:
						return false;
				}
			}
		}
	}

	/**
	 *
	 */
	protected function _detectResourceMembers()
	{
		$this->_tableName = ( isset( $this->_resourceArray[0] ) ) ? $this->_resourceArray[0] : '';
		$this->_fieldName = ( isset( $this->_resourceArray[1] ) ) ? $this->_resourceArray[1] : '';
	}

	/**
	 * @return array
	 * @throws \Exception
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
			return SqlDbUtilities::describeDatabase( $this->_sqlConn, '', $exclude );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error describing database tables.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table_list
	 *
	 * @return array|string
	 * @throws \Exception
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
					throw new NotFoundException( "Table '$table' not found." );
				}
			}
			$this->checkPermission( 'read', $table );
		}
		try
		{
			return SqlDbUtilities::describeTables( $this->_sqlConn, $tables );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error describing database tables '$table_list'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function describeTable( $table )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new NotFoundException( "Table '$table' not found." );
			}
		}
		$this->checkPermission( 'read', $table );
		try
		{
			return SqlDbUtilities::describeTable( $this->_sqlConn, $table );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error describing database table '$table'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 * @param $field
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function describeField( $table, $field )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new NotFoundException( "Table '$table' not found." );
			}
		}
		$this->checkPermission( 'read', $table );
		try
		{
			return SqlDbUtilities::describeField( $this->_sqlConn, $table, $field );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error describing database table '$table' field '$field'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param      $tables
	 * @param bool $allow_merge
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function createTables( $tables, $allow_merge = false )
	{
		if ( !isset( $tables ) || empty( $tables ) )
		{
			throw new BadRequestException( 'There are no table sets in the request.' );
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
						throw new BadRequestException( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created." );
					}
				}
			}
			else
			{ // single table
				$name = Utilities::getArrayValue( 'name', $tables, '' );
				if ( 0 === substr_compare( $name, $sysPrefix, 0, strlen( $sysPrefix ) ) )
				{
					throw new BadRequestException( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created." );
				}
			}
		}
		$this->checkPermission( 'create' );

		return SqlDbUtilities::createTables( $this->_sqlConn, $tables, $allow_merge );
	}

	/**
	 * @param $table
	 *
	 * @return array
	 * @throws \Exception
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
	 * @throws \Exception
	 * @return array
	 */
	public function createFields( $table, $fields )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new NotFoundException( "Table '$table' not found." );
			}
		}
		$this->checkPermission( 'create', $table );
		try
		{
			$names = SqlDbUtilities::createFields( $this->_sqlConn, $table, $fields );

			return SqlDbUtilities::describeFields( $this->_sqlConn, $table, $names );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error creating database fields for table '$table'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 * @param $data
	 *
	 * @throws \Exception
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
	 * @throws \Exception
	 */
	public function updateTables( $tables )
	{
		if ( !isset( $tables ) || empty( $tables ) )
		{
			throw new BadRequestException( 'There are no table sets in the request.' );
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
						throw new BadRequestException( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created." );
					}
				}
			}
			else
			{ // single table
				$name = Utilities::getArrayValue( 'name', $tables, '' );
				if ( 0 === substr_compare( $name, $sysPrefix, 0, strlen( $sysPrefix ) ) )
				{
					throw new BadRequestException( "Tables can not use the prefix '$sysPrefix'. '$name' can not be created." );
				}
			}
		}
		$this->checkPermission( 'update' );

		return SqlDbUtilities::createTables( $this->_sqlConn, $tables, true );
	}

	/**
	 * @param $table
	 *
	 * @return array
	 * @throws \Exception
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
	 * @throws \Exception
	 * @return array
	 */
	public function updateFields( $table, $fields )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new NotFoundException( "Table '$table' not found." );
			}
		}
		$this->checkPermission( 'update', $table );
		try
		{
			$names = SqlDbUtilities::createFields( $this->_sqlConn, $table, $fields, true );

			return SqlDbUtilities::describeFields( $this->_sqlConn, $table, $names );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error updating database table '$table'.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param $table
	 * @param $field
	 * @param $data
	 *
	 * @throws \Exception
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
	 * @throws \Exception
	 */
	public function deleteTable( $table )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new NotFoundException( "Table '$table' not found." );
			}
		}
		$this->checkPermission( 'delete', $table );
		SqlDbUtilities::dropTable( $this->_sqlConn, $table );
	}

	/**
	 * @param $table
	 * @param $field
	 *
	 * @throws \Exception
	 */
	public function deleteField( $table, $field )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new NotFoundException( "Table '$table' not found." );
			}
		}
		$this->checkPermission( 'delete', $table );
		SqlDbUtilities::dropField( $this->_sqlConn, $table, $field );
	}
}
