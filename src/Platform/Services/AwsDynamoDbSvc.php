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
namespace Platform\Services;

use Aws\Common\Enum\Region;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Enum\ComparisonOperator;
use Aws\DynamoDb\Enum\KeyType;
use Aws\DynamoDb\Enum\ReturnValue;
use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Model\Attribute;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Kisma\Core\Utility\Option;

/**
 * AwsDynamoDbSvc.php
 *
 * A service to handle Amazon Web Services DynamoDb NoSQL (schema-less) database
 * services accessed through the REST API.
 */
class AwsDynamoDbSvc extends NoSqlDbSvc
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	const DEFAULT_REGION = Region::US_WEST_1;

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var DynamoDbClient|null
	 */
	protected $_dbConn = null;
	/**
	 * @var array
	 */
	protected $_defaultCreateTable
		= array(
			'AttributeDefinitions'  => array(
				array(
					'AttributeName' => 'id',
					'AttributeType' => Type::S
				)
			),
			'KeySchema'             => array(
				array(
					'AttributeName' => 'id',
					'KeyType'       => KeyType::HASH
				)
			),
			'ProvisionedThroughput' => array(
				'ReadCapacityUnits'  => 10,
				'WriteCapacityUnits' => 20
			)
		);

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new AwsDynamoDbSvc
	 *
	 * @param array $config
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		$_credentials = Option::get( $config, 'credentials' );
		$_parameters = Option::get( $config, 'parameters' );

		// old way
		$_accessKey = Option::get( $_credentials, 'access_key' );
		$_secretKey = Option::get( $_credentials, 'secret_key' );
		if ( !empty( $_accessKey ) )
		{
			// old way, replace with 'key'
			$_credentials['key'] = $_accessKey;
		}

		if ( !empty( $_secretKey ) )
		{
			// old way, replace with 'key'
			$_credentials['secret'] = $_secretKey;
		}

		$_region = Option::get( $_credentials, 'region' );
		if ( empty( $_region ) )
		{
			// use a default region if not present
			$_credentials['region'] = static::DEFAULT_REGION;
		}

		// set up a default partition key
		if ( null !== ( $_table = Option::get( $_parameters, 'default_create_table' ) ) )
		{
			$this->_defaultCreateTable = $_table;
		}

		try
		{
			$this->_dbConn = DynamoDbClient::factory( $_credentials );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Amazon DynamoDb Service Exception:\n{$ex->getMessage()}" );
		}
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		try
		{
			$this->_dbConn = null;
		}
		catch ( \Exception $ex )
		{
			error_log( "Failed to disconnect from database.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @throws \Exception
	 */
	protected function checkConnection()
	{
		if ( empty( $this->_dbConn ) )
		{
			throw new InternalServerErrorException( 'Database connection has not been initialized.' );
		}
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function correctTableName( $name )
	{
		return $name;
	}

	/**
	 * @param string $table
	 * @param string $access
	 *
	 * @throws \Exception
	 */
	protected function validateTableAccess( $table, $access = 'read' )
	{
		parent::validateTableAccess( $table, $access );
	}

	// REST service implementation

	protected function _getTablesAsArray()
	{
		$_out = array();
		do
		{
			$_result = $this->_dbConn->listTables(
				array(
					 'Limit'                   => 100, // arbitrary limit
					 'ExclusiveStartTableName' => isset( $_result ) ? $_result['LastEvaluatedTableName'] : null
				)
			);

			$_out = array_merge( $_out, $_result['TableNames'] );
		}
		while ( $_result['LastEvaluatedTableName'] );

		return $_out;
	}

	protected function _getKeyInfo( $table, $extras = null )
	{
		$_fields = Option::get( $extras, 'id_field' );
		if ( !empty( $_fields ) )
		{
			if ( !is_array( $_fields ) )
			{
				$_fields = array_map( 'trim', explode( ',', trim( $_fields, ',' ) ) );
			}
			$_types = Option::get( $extras, 'id_type', Type::S );
			if ( !is_array( $_types ) )
			{
				$_types = array_map( 'trim', explode( ',', trim( $_types, ',' ) ) );
			}
			$_keyTypes = Option::get( $extras, 'id_key_type', KeyType::HASH );
			if ( !is_array( $_keyTypes ) )
			{
				$_keyTypes = array_map( 'trim', explode( ',', trim( $_keyTypes, ',' ) ) );
			}
		}
		else
		{
			$_result = $this->getTable( $table );
			$_keys = Option::get( $_result, 'KeySchema', array() );
			$_definitions = Option::get( $_result, 'AttributeDefinitions', array() );
			$_fields = array();
			$_types = array();
			$_keyTypes = array();
			foreach ( $_keys as $_key )
			{
				$_name = Option::get( $_key, 'AttributeName' );
				$_fields[] = $_name;
				$_keyTypes[] = Option::get( $_key, 'KeyType' );
				foreach ( $_definitions as $_type )
				{
					if ( 0 == strcmp( $_name, Option::get( $_type, 'AttributeName' ) ) )
					{
						$_types[] = Option::get( $_type, 'AttributeType' );
					}
				}
			}
		}

		return array( 'fields' => $_fields, 'types' => $_types, 'key_type' => $_keyTypes );
	}

	/**
	 * @throws \Exception
	 * @return array
	 */
	protected function _listResources()
	{
		$_result = $this->_getTablesAsArray();
		$_out = array();
		foreach ( $_result as $_table )
		{
			$_out[] = array( 'name' => $_table, 'TableName' => $_table );
		}

		return array( 'resource' => $_out );
	}

	/**
	 *
	 * @param array $tables
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function getTables( $tables = array() )
	{
		if ( empty( $tables ) )
		{
			$tables = $this->_getTablesAsArray();
		}
		else
		{
			if ( !is_array( $tables ) )
			{
				$tables = array_map( 'trim', explode( ',', trim( $tables, ',' ) ) );
			}
		}

		$_out = array();
		foreach ( $tables as $_table )
		{
			if ( is_array( $_table ) )
			{
				$_table = Option::get( $_table, 'name' );
			}
			if ( empty( $_table ) )
			{
				throw new BadRequestException( "No 'name' field in data." );
			}
			try
			{
				$_out[] = $this->getTable( $_table );
			}
			catch ( \Exception $ex )
			{
				throw new InternalServerErrorException( "Failed to list tables of DynamoDb Tables service.\n" . $ex->getMessage() );
			}
		}

		return $_out;
	}

	/**
	 * Get any properties related to the table
	 *
	 * @param string $table Table name
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getTable( $table )
	{
		try
		{
			$_result = $this->_dbConn->describeTable(
				array(
					 'TableName' => $table
				)
			);

			// The result of an operation can be used like an array
			$_out = $_result['Table'];
			$_out['name'] = $table;

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to list tables of DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param array $properties
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function createTable( $properties = array() )
	{
		// generic, then AWS version
		$_name = Option::get( $properties, 'name', Option::get( $properties, 'TableName' ) );
		if ( empty( $_name ) )
		{
			throw new BadRequestException( "No 'name' field in data." );
		}

		try
		{
			$_properties = array_merge(
				array( 'TableName' => $_name ),
				$this->_defaultCreateTable,
				$properties
			);
			$_result = $this->_dbConn->createTable( $_properties );

			// Wait until the table is created and active
			$this->_dbConn->waitUntilTableExists(
				array(
					 'TableName' => $_name
				)
			);

			return array_merge( array( 'name' => $_name ), $_result['TableDescription'] );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create table on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * Get any properties related to the table
	 *
	 * @param array $properties
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function updateTable( $properties = array() )
	{
		$_name = Option::get( $properties, 'name' );
		if ( empty( $_name ) )
		{
			throw new BadRequestException( "No 'name' field in data." );
		}

		try
		{
			// Update the provisioned throughput capacity of the table
			$_properties = array_merge(
				array( 'TableName' => $_name ),
				$properties
			);
			$_result = $this->_dbConn->updateTable( $_properties );

			// Wait until the table is active again after updating
			$this->_dbConn->waitUntilTableExists(
				array(
					 'TableName' => $_name
				)
			);

			return array_merge( array( 'name' => $_name ), $_result['TableDescription'] );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update table on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param array $tables
	 * @param bool  $check_empty
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteTables( $tables = array(), $check_empty = false )
	{
		if ( !is_array( $tables ) )
		{
			// may be comma-delimited list of names
			$tables = array_map( 'trim', explode( ',', trim( $tables, ',' ) ) );
		}
		$_out = array();
		foreach ( $tables as $_table )
		{
			if ( is_array( $_table ) )
			{
				$_table = Option::get( $_table, 'name', Option::get( $_table, 'TableName' ) );
			}
			if ( empty( $_table ) )
			{
				throw new BadRequestException( "No 'name' field in data." );
			}
			try
			{
				$_out[] = $this->deleteTable( $_table );
			}
			catch ( \Exception $ex )
			{
				throw $ex;
			}
		}

		return $_out;
	}

	/**
	 * Delete the table and all of its contents
	 *
	 * @param string $table
	 * @param bool   $check_empty
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteTable( $table, $check_empty = false )
	{
		try
		{
			$_result = $this->_dbConn->deleteTable(
				array(
					 'TableName' => $table
				)
			);

			$this->_dbConn->waitUntilTableNotExists(
				array(
					 'TableName' => $table
				)
			);

			return array_merge( array( 'name' => $table ), $_result['TableDescription'] );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete table '$table' from DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	//-------- Table Records Operations ---------------------
	// records is an array of field arrays

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function createRecords( $table, $records, $fields = null, $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record possibly passed in without wrapper array
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_items = array();
		foreach ( $records as $_record )
		{
			if ( !$this->_containsIdFields( $_record, $_idField ) )
			{
				// can we auto create an id here?
				throw new BadRequestException( "Identifying field(s) not found in record." );
			}

			// Add operation to list of batch operations.
			$_items[] = array(
				'PutRequest' => array(
					'Item' => $this->_dbConn->formatAttributes( $_record )
				)
			);
		}

		try
		{
			$_result = $this->_dbConn->batchWriteItem(
				array(
					 'RequestItems' => array(
						 $table => $_items,
					 )
				)
			);

			// todo check $_result['UnprocessedItems'] for 'PutRequest'

			return static::cleanRecords( $records, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create items in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @throws BadRequestException
	 * @return array
	 */
	public function createRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		if ( !$this->_containsIdFields( $record, $_idField ) )
		{
			throw new BadRequestException( "Identifying field(s) not found in record." );
		}

		try
		{
			// simple insert request
			$_result = $this->_dbConn->putItem(
				array(
					 'TableName' => $table,
					 'Item'      => $this->_dbConn->formatAttributes( $record )
				)
			);

			$_out = Option::get( $_result, 'Attributes', array() );

			return static::cleanRecord( $record, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecords( $table, $records, $fields = null, $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record possibly passed in without wrapper array
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_items = array();
		foreach ( $records as $_record )
		{
			if ( !$this->_containsIdFields( $_record, $_idField ) )
			{
				throw new BadRequestException( "Identifying field(s) not found in record." );
			}

			// Add operation to list of batch operations.
			$_items[] = array(
				'PutRequest' => array(
					'Item' => $this->_dbConn->formatAttributes( $_record )
				)
			);
		}

		try
		{
			$_result = $this->_dbConn->batchWriteItem(
				array(
					 'RequestItems' => array(
						 $table => $_items,
					 )
				)
			);

			// todo check $_result['UnprocessedItems'] for 'PutRequest'

			return static::cleanRecords( $records, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update items in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @throws BadRequestException
	 * @return array
	 */
	public function updateRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		if ( !$this->_containsIdFields( $record, $_idField ) )
		{
			throw new BadRequestException( "Identifying field(s) not found in record." );
		}

		try
		{
			// simple insert request
			$_result = $this->_dbConn->putItem(
				array(
					 'TableName'    => $table,
					 'Item'         => $this->_dbConn->formatAttributes( $record ),
					 'ReturnValues' => ReturnValue::ALL_NEW
				)
			);

			$_out = Option::get( $_result, 'Attributes', array() );

			return static::cleanRecord( $record, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecordsByFilter( $table, $record, $filter = null, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		// slow, but workable for now, maybe faster than updating individuals
		$_records = $this->retrieveRecordsByFilter( $table, $filter, '', $extras );
		foreach ( $_records as $_ndx => $_record )
		{
			$_records[$_ndx] = array_merge( $_record, $record );
		}

		return $this->updateRecords( $table, $_records, $fields, $extras );
	}

	/**
	 * @param string $table
	 * @param array  $record
	 * @param string $id_list
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecordsByIds( $table, $record, $id_list, $fields = null, $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( "No record fields were passed in the request." );
		}

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for id_field can not be empty for update request." );
		}

		if ( !is_array( $id_list ) )
		{
			$id_list = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		}
		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_out = array();
		$_items = array();
		foreach ( $id_list as $_id )
		{
			$record[$_idField[0]] = $_id;
			// Add operation to list of batch operations.
			$_items[] = array(
				'PutRequest' => array(
					'Item' => $this->_dbConn->formatAttributes( $record )
				)
			);
			$_out[] = static::cleanRecord( $record, $fields, $_idField );
		}

		try
		{
			$_result = $this->_dbConn->batchWriteItem(
				array(
					 'RequestItems' => array(
						 $table => $_items,
					 )
				)
			);

			// todo check $_result['UnprocessedItems'] for 'PutRequest'

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecordById( $table, $record, $id, $fields = null, $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		if ( empty( $id ) )
		{
			throw new BadRequestException( "No identifier exist in record." );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		try
		{
			$record[$_idField[0]] = $id;
			// simple insert request
			$_result = $this->_dbConn->putItem(
				array(
					 'TableName'    => $table,
					 'Item'         => $this->_dbConn->formatAttributes( $record ),
					 'ReturnValues' => ReturnValue::ALL_NEW
				)
			);

			$_out = Option::get( $_result, 'Attributes', array() );

			return static::cleanRecord( $record, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecords( $table, $records, $fields = null, $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new BadRequestException( 'There are no records in the request.' );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_out = array();
		foreach ( $records as $_record )
		{
			if ( empty( $_record ) || !is_array( $_record ) )
			{
				throw new BadRequestException( 'There are no record fields in the request.' );
			}

			$_keys = static::_buildKey( $_idField, $_idType, $_record, true );
			try
			{
				// simple insert request
				$_result = $this->_dbConn->updateItem(
					array(
						 'TableName'        => $table,
						 'Key'              => $_keys,
						 'AttributeUpdates' => $this->_dbConn->formatAttributes( $_record, Attribute::FORMAT_UPDATE ),
						 'ReturnValues'     => ReturnValue::ALL_NEW
					)
				);

				$_temp = Option::get( $_result, 'Attributes', array() );

				$_out[] = static::cleanRecord( static::_unformatAttributes( $_temp ), $fields, $_idField );
			}
			catch ( \Exception $ex )
			{
				throw new InternalServerErrorException( "Failed to merge item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
			}
		}

		return $_out;
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_keys = static::_buildKey( $_idField, $_idType, $record, true );
		try
		{
			// simple insert request
			$_result = $this->_dbConn->updateItem(
				array(
					 'TableName'        => $table,
					 'Key'              => $_keys,
					 'AttributeUpdates' => $this->_dbConn->formatAttributes( $record, Attribute::FORMAT_UPDATE ),
					 'ReturnValues'     => ReturnValue::ALL_NEW
				)
			);

			$_out = Option::get( $_result, 'Attributes', array() );

			return static::cleanRecord( static::_unformatAttributes( $_out ), $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to merge item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecordsByFilter( $table, $record, $filter = null, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		// slow, but workable for now, maybe faster than merging individuals
		$_records = $this->retrieveRecordsByFilter( $table, $filter, '*', $extras );
		foreach ( $_records as $_ndx => $_record )
		{
			$_records[$_ndx] = array_merge( $_record, $record );
		}

		return $this->updateRecords( $table, $_records, $fields, $extras );
	}

	/**
	 * @param string $table
	 * @param array  $record
	 * @param mixed  $id_list
	 * @param mixed  $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecordsByIds( $table, $record, $id_list, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying field(s) values can not be empty." );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		if ( !is_array( $id_list ) )
		{
			$id_list = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		}
		$_out = array();
		$_updates = $this->_dbConn->formatAttributes( $record, Attribute::FORMAT_UPDATE );
		foreach ( $id_list as $_id )
		{
			$_temp = array( $_idField[0] => $_id );
			$_keys = static::_buildKey( $_idField, $_idType, $_temp );
			try
			{
				// simple insert request
				$_result = $this->_dbConn->updateItem(
					array(
						 'TableName'        => $table,
						 'Key'              => $_keys,
						 'AttributeUpdates' => $_updates,
						 'ReturnValues'     => ReturnValue::ALL_NEW
					)
				);

				$_temp = Option::get( $_result, 'Attributes', array() );

				$_out[] = static::cleanRecord( static::_unformatAttributes( $_temp ), $fields, $_idField );
			}
			catch ( \Exception $ex )
			{
				throw new InternalServerErrorException( "Failed to merge item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
			}
		}

		return $_out;
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecordById( $table, $record, $id, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		if ( empty( $id ) )
		{
			throw new BadRequestException( "Identifying field(s) values can not be empty." );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_temp = array( $_idField[0] => $id );
		$_keys = static::_buildKey( $_idField, $_idType, $_temp );
		try
		{
			// simple insert request
			$_result = $this->_dbConn->updateItem(
				array(
					 'TableName'        => $table,
					 'Key'              => $_keys,
					 'AttributeUpdates' => $this->_dbConn->formatAttributes( $record, Attribute::FORMAT_UPDATE ),
					 'ReturnValues'     => ReturnValue::ALL_NEW
				)
			);

			$_out = Option::get( $_result, 'Attributes', array() );

			return static::cleanRecord( static::_unformatAttributes( $_out ), $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to merge item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array|string
	 */
	public function deleteRecords( $table, $records, $fields = null, $extras = array() )
	{
		if ( !is_array( $records ) || empty( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_out = array();
		if ( static::_requireMoreFields( $fields, $_idField ) )
		{
			$_out = $this->retrieveRecords( $table, $records, $fields, $extras );
		}
		$_items = array();
		$_outIds = array();
		foreach ( $records as $_record )
		{
			// Add operation to list of batch operations.
			$_items[] = array(
				'DeleteRequest' => array(
					'Key' => static::_buildKey( $_idField, $_idType, $_record )
				)
			);
			$_outIds[] = static::recordsAsIds( $_record, $_idField );
		}

		try
		{
			$_result = $this->_dbConn->batchWriteItem(
				array(
					 'RequestItems' => array(
						 $table => $_items,
					 )
				)
			);

			// todo check $_result['UnprocessedItems'] for 'DeleteRequest'

			return ( empty( $_out ) ) ? $_outIds : $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		$_keys = static::_buildKey( $_idField, $_idType, $record );
		$_scanProperties = array(
			'TableName'    => $table,
			'Key'          => $_keys,
			'ReturnValues' => ReturnValue::ALL_OLD,
		);
		try
		{
			$_result = $this->_dbConn->deleteItem( $_scanProperties );
			$_out = Option::get( $_result, 'Attributes', array() );

			// Grab value from the result object like an array
			return static::cleanRecord( static::_unformatAttributes( $_out ), $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecordsByFilter( $table, $filter, $fields = null, $extras = array() )
	{
		if ( empty( $filter ) )
		{
			throw new BadRequestException( "Filter for delete request can not be empty." );
		}

		$_records = $this->retrieveRecordsByFilter( $table, $filter, '', $extras );

		return $this->deleteRecords( $table, $_records, $fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecordsByIds( $table, $id_list, $fields = null, $extras = array() )
	{
		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for id_field can not be empty for update request." );
		}

		if ( !is_array( $id_list ) )
		{
			$id_list = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		}
		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}

		$_out = array();
		if ( static::_requireMoreFields( $fields, $_idField ) )
		{
			$_out = $this->retrieveRecordsByIds( $table, $id_list, $fields, $extras );
		}
		$_items = array();
		$_outIds = array();
		foreach ( $id_list as $_id )
		{
			$_record = array( $_idField[0] => $_id );
			// Add operation to list of batch operations.
			$_items[] = array(
				'DeleteRequest' => array(
					'Key' => static::_buildKey( $_idField, $_idType, $_record )
				)
			);
			$_outIds[] = $_record;
		}

		try
		{
			$_result = $this->_dbConn->batchWriteItem(
				array(
					 'RequestItems' => array(
						 $table => $_items,
					 )
				)
			);

			// todo check $_result['UnprocessedItems'] for 'DeleteRequest'

			return ( empty( $_out ) ) ? $_outIds : $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecordById( $table, $id, $fields = null, $extras = array() )
	{
		if ( empty( $id ) )
		{
			throw new BadRequestException( "Identifying field(s) values can not be empty." );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		$_record = array( $_idField[0] => $id );
		$_keys = static::_buildKey( $_idField, $_idType, $_record );
		$_scanProperties = array(
			'TableName'    => $table,
			'Key'          => $_keys,
			'ReturnValues' => ReturnValue::ALL_OLD,
		);
		try
		{
			$_result = $this->_dbConn->deleteItem( $_scanProperties );
			$_out = Option::get( $_result, 'Attributes', array() );

			// Grab value from the result object like an array
			return static::cleanRecord( static::_unformatAttributes( $_out ), $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete item '$table/$id' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param string $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecordsByFilter( $table, $filter = null, $fields = null, $extras = array() )
	{
		$this->checkConnection();
		$table = $this->correctTableName( $table );

		$_scanProperties = array( 'TableName' => $table );
		$fields = static::_buildAttributesToGet( $fields );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributesToGet'] = $fields;
		}
		$_limit = Option::get( $extras, 'limit' );
		if ( $_limit > 0 )
		{
			$_scanProperties['Limit'] = $_limit;
		}
		if ( !empty( $filter ) )
		{
			$_parsedFilter = static::_buildFilterArray( $filter );
			$_scanProperties['ScanFilter'] = $_parsedFilter;
		}
		try
		{
			$_result = $this->_dbConn->scan( $_scanProperties );
			$_out = array();
			foreach ( $_result['Items'] as $_item )
			{
				$_out[] = static::_unformatAttributes( $_item );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to filter items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param array  $records
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecords( $table, $records, $fields = null, $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		$_keys = array();
		foreach ( $records as $_record )
		{
			$_keys[] = static::_buildKey(
				$_idField,
				$_idType,
				$_record
			);
		}
		$_scanProperties = array(
			'Keys'           => $_keys,
			'ConsistentRead' => true,
		);
		$fields = static::_buildAttributesToGet( $fields, $_idField );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributesToGet'] = $fields;
		}

		try
		{
			// Get multiple items by key in a BatchGetItem request
			$_result = $this->_dbConn->batchGetItem(
				array(
					 'RequestItems' => array(
						 $table => $_scanProperties
					 )
				)
			);

			$_items = $_result->getPath( "Responses/{$table}" );
			$_out = array();
			foreach ( $_items as $_item )
			{
				$_out[] = static::_unformatAttributes( $_item );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		$_keys = static::_buildKey(
			$_idField,
			$_idType,
			$record
		);
		$_scanProperties = array(
			'TableName'      => $table,
			'Key'            => $_keys,
			'ConsistentRead' => true,
		);
		$fields = static::_buildAttributesToGet( $fields, $_idField );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributesToGet'] = $fields;
		}

		try
		{
			$_result = $this->_dbConn->getItem( $_scanProperties );

			// Grab value from the result object like an array
			return static::_unformatAttributes( $_result['Item'] );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get item from table '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param string $id_list - comma delimited list of ids
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecordsByIds( $table, $id_list, $fields = null, $extras = array() )
	{
		if ( empty( $id_list ) )
		{
			return array();
		}
		if ( !is_array( $id_list ) )
		{
			$id_list = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		}
		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		$_keys = array();
		foreach ( $id_list as $id )
		{
			$_record = array( $_idField[0] => $id );
			$_keys[] = static::_buildKey( $_idField, $_idType, $_record );
		}
		$_scanProperties = array(
			'Keys'           => $_keys,
			'ConsistentRead' => true,
		);
		$fields = static::_buildAttributesToGet( $fields, $_idField );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributesToGet'] = $fields;
		}

		try
		{
			// Get multiple items by key in a BatchGetItem request
			$_result = $this->_dbConn->batchGetItem(
				array(
					 'RequestItems' => array(
						 $table => $_scanProperties
					 )
				)
			);

			$_items = $_result->getPath( "Responses/{$table}" );
			$_out = array();
			foreach ( $_items as $_item )
			{
				$_out[] = static::_unformatAttributes( $_item );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecordById( $table, $id, $fields = null, $extras = array() )
	{
		if ( empty( $id ) )
		{
			throw new BadRequestException( "Identifying field(s) values can not be empty." );
		}

		$table = $this->correctTableName( $table );
		$_info = $this->_getKeyInfo( $table, $extras );
		$_idField = $_info['fields'];
		$_idType = $_info['types'];
		if ( empty( $_idField ) )
		{
			throw new InternalServerErrorException( "Identifying field(s) could not be determined." );
		}
		$_record = array( $_idField[0] => $id );
		$_keys = static::_buildKey( $_idField, $_idType, $_record );
		$_scanProperties = array(
			'TableName'      => $table,
			'Key'            => $_keys,
			'ConsistentRead' => true,
		);
		$fields = static::_buildAttributesToGet( $fields, $_idField );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributesToGet'] = $fields;
		}

		try
		{
			$_result = $this->_dbConn->getItem( $_scanProperties );

			// Grab value from the result object like an array
			return static::_unformatAttributes( $_result['Item'] );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get item '$table/$id' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	protected static function _unformatValue( $value )
	{
		// represented as arrays, though there is only ever one item present
		foreach ( $value as $type => $actual )
		{
			switch ( $type )
			{
				case Type::S:
				case Type::B:
					return $actual;
				case Type::N:
					if ( intval( $actual ) == $actual )
					{
						return intval( $actual );
					}
					else
					{
						return floatval( $actual );
					}
				case Type::SS:
				case Type::BS:
					return $actual;
				case Type::NS:
					$_out = array();
					foreach ($actual as $_item)
					{
						if ( intval( $_item ) == $_item )
						{
							$_out[] = intval( $_item );
						}
						else
						{
							$_out[] = floatval( $_item );
						}
					}

					return $_out;
			}
		}

		return $value;
	}

	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected static function _unformatAttributes( $record )
	{
		$_out = array();
		foreach( $record as $_key => $_value )
		{
			$_out[$_key] = static::_unformatValue( $_value );
		}

		return $_out;
	}

	protected static function _buildAttributesToGet( $fields = null, $id_fields = null )
	{
		if ( '*' == $fields )
		{
			return null;
		}
		if ( empty( $fields ) )
		{
			if ( empty( $id_fields ) )
			{
				return null;
			}
			if ( !is_array( $id_fields ) )
			{
				$id_fields = array_map( 'trim', explode( ',', trim( $id_fields, ',' ) ) );
			}

			return $id_fields;
		}

		if ( !is_array( $fields ) )
		{
			$fields = array_map( 'trim', explode( ',', trim( $fields, ',' ) ) );
		}

		return $fields;
	}

	protected static function _buildKey( $fields, $types, &$record, $remove = false )
	{
		$_keys = array();
		foreach ( $fields as $_ndx => $_field )
		{
			$_value = Option::get( $record, $_field, null, $remove );
			if ( empty( $_value ) )
			{
				throw new BadRequestException( "Identifying field(s) not found in record." );
			}
			switch ( $types[$_ndx] )
			{
				case Type::N:
					$_value = array( Type::N => strval( $_value ) );
					break;
				default:
					$_value = array( Type::S => $_value );
			}
			$_keys[$_field] = $_value;
		}

		return $_keys;
	}

	/**
	 * @param string|array $filter Filter for querying records by
	 *
	 * @throws \DreamFactory\Platform\Exceptions\BadRequestException
	 * @return array
	 */
	protected static function _buildFilterArray( $filter )
	{
		if ( empty( $filter ) )
		{
			return array();
		}

		if ( is_array( $filter ) )
		{
			return $filter; // assume they know what they are doing
		}

		$_search = array( ' or ', ' and ', ' nor ' );
		$_replace = array( ' || ', ' && ', ' NOR ' );
		$filter = trim( str_ireplace( $_search, $_replace, $filter ) );

		// handle logical operators first
		$_ops = array_map( 'trim', explode( ' || ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_parts = array();
			foreach ( $_ops as $_op )
			{
				$_parts[] = static::_buildFilterArray( $_op );
			}

			return array( 'split' => $_parts );
		}

		$_ops = array_map( 'trim', explode( ' && ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_parts = array();
			foreach ( $_ops as $_op )
			{
				$_parts[] = static::_buildFilterArray( $_op );
			}

			return $_parts;
		}

		$_ops = array_map( 'trim', explode( ' NOR ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			throw new BadRequestException( ' NOR logical comparison not currently supported on DynamoDb.' );
		}

		// handle negation operator, i.e. starts with NOT?
		if ( 0 == substr_compare( $filter, 'not ', 0, 4, true ) )
		{
			throw new BadRequestException( ' NOT logical comparison not currently supported on DynamoDb.' );
		}

		// the rest should be comparison operators
		$_search = array( ' eq ', ' ne ', ' gte ', ' lte ', ' gt ', ' lt ', ' in ', ' between ', ' begins_with ', ' contains ', ' not_contains ', ' like ' );
		$_replace = array( ' = ', ' != ', ' >= ', ' <= ', ' > ', ' < ', ' IN ', ' BETWEEN ', ' BEGINS_WITH ', ' CONTAINS ', ' NOT_CONTAINS ', ' LIKE ' );
		$filter = trim( str_ireplace( $_search, $_replace, $filter ) );

		$_ops = array_map( 'trim', explode( ' = ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			if ( 0 == strcasecmp( 'null', $_ops[1] ) )
			{
				return array(
					$_ops[0] => array(
						'ComparisonOperator' => ComparisonOperator::NULL
					)
				);
			}

			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::EQ
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' != ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			if ( 0 == strcasecmp( 'null', $_ops[1] ) )
			{
				return array(
					$_ops[0] => array(
						'ComparisonOperator' => ComparisonOperator::NOT_NULL
					)
				);
			}

			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::NE
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' >= ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::GE
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' <= ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::LE
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' > ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::GT
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' < ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::LT
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' IN ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1], true );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::IN
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' BETWEEN ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1], true );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::BETWEEN
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' BEGINS_WITH ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::BEGINS_WITH
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' CONTAINS ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::CONTAINS
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' NOT_CONTAINS ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
			$_val = static::_determineValue( $_ops[1] );

			return array(
				$_ops[0] => array(
					'AttributeValueList' => $_val,
					'ComparisonOperator' => ComparisonOperator::NOT_CONTAINS
				)
			);
		}

		$_ops = array_map( 'trim', explode( ' LIKE ', $filter ) );
		if ( count( $_ops ) > 1 )
		{
//			WHERE name LIKE "%Joe%"	use CONTAINS "Joe"
//			WHERE name LIKE "Joe%"	use BEGINS_WITH "Joe"
//			WHERE name LIKE "%Joe"	not supported
			$_val = $_ops[1];
			$_type = Type::S;
			if ( trim( $_val, "'\"" ) === $_val )
			{
				$_type = Type::N;
			}

			$_val = trim( $_val, "'\"" );
			if ( '%' == $_val[strlen( $_val ) - 1] )
			{
				if ( '%' == $_val[0] )
				{
					return array(
						$_ops[0] => array(
							'AttributeValueList' => array( $_type => trim( $_val, '%' ) ),
							'ComparisonOperator' => ComparisonOperator::CONTAINS
						)
					);
				}
				else
				{
					throw new BadRequestException( 'ENDS_WITH currently not supported in DynamoDb.' );
				}
			}
			else
			{
				if ( '%' == $_val[0] )
				{
					return array(
						$_ops[0] => array(
							'AttributeValueList' => array( $_type => trim( $_val, '%' ) ),
							'ComparisonOperator' => ComparisonOperator::BEGINS_WITH
						)
					);
				}
				else
				{
					return array(
						$_ops[0] => array(
							'AttributeValueList' => array( $_type => trim( $_val, '%' ) ),
							'ComparisonOperator' => ComparisonOperator::CONTAINS
						)
					);
				}
			}
		}

		return $filter;
	}

	/**
	 * @param string $value
	 * @param bool   $multiple
	 *
	 * @return bool|float|int|string
	 */
	private static function _determineValue( $value, $multiple = false )
	{
		if ( trim( $value, "'\"" ) !== $value )
		{
			return array( array( Type::S => trim( $value, "'\"" ) ) ); // meant to be a string
		}

		if ( is_numeric( $value ) )
		{
			$value = ( $value == strval( intval( $value ) ) ) ? intval( $value ) : floatval( $value );

			return array( array( Type::N => $value ) );
		}

		if ( 0 == strcasecmp( $value, 'true' ) )
		{
			return array( array( Type::N => 1 ) );
		}

		if ( 0 == strcasecmp( $value, 'false' ) )
		{
			return array( array( Type::N => 0 ) );
		}

		return $value;
	}
}
