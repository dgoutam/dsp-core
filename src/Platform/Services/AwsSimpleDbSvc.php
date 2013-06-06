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

use Aws\SimpleDb\SimpleDbClient;
use Platform\Exceptions\BadRequestException;
use Platform\Utility\DataFormat;
use Kisma\Core\Utility\Option;

/**
 * AwsSimpleDbSvc.php
 *
 * A service to handle Amazon Web Services DynamoDb NoSQL (schema-less) database
 * services accessed through the REST API.
 */
class AwsSimpleDbSvc extends NoSqlDbSvc
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	const DEFAULT_REGION = 'us-east-1';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var SimpleDbClient|null
	 */
	protected $_dbConn = null;
	/**
	 * @var array
	 */
	protected $_defaultTableKey = array(
		array(
			'name' => 'id',
			'data_type' => 'S',
			'key_type' => 'HASH'
		)
	);
	/**
	 * @var boolean
	 */
	protected $_defaultSimpleFormat = true;

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
		$_accessKey = Option::get( $_credentials, 'access_key' );
		if ( empty( $_accessKey ) )
		{
			throw new \InvalidArgumentException( 'AWS access key can not be empty.' );
		}
		$_secretKey = Option::get( $_credentials, 'secret_key' );
		if ( empty( $_secretKey ) )
		{
			throw new \InvalidArgumentException( 'AWS secret key can not be empty.' );
		}
		$_region = Option::get( $_credentials, 'region' );
		if ( empty( $_region ) )
		{
			$_region = static::DEFAULT_REGION;
		}

		// set up a default partition key
		$_parameters = Option::get( $config, 'parameters' );
		$_key = Option::get( $_parameters, 'default_key' );
		if ( !empty( $_key ) )
		{
			$this->_defaultTableKey = $_key;
		}
		// reply in simple format by default
		$_simpleFormat = Option::get( $_parameters, 'simple_format' );
		if ( !empty( $_simpleFormat ) )
		{
			$this->_defaultSimpleFormat = DataFormat::boolval( $_simpleFormat );
		}

		try
		{
			$this->_dbConn = SimpleDbClient::factory(
				array(
					 'key'    => $_accessKey,
					 'secret' => $_secretKey,
					 'region' => $_region
				)
			);
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Unexpected Amazon DynamoDb Service Exception:\n{$ex->getMessage()}" );
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
		if ( !isset( $this->_dbConn ) )
		{
			throw new \Exception( 'Database connection has not been initialized.' );
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

	protected function gatherExtrasFromRequest()
	{
		$_extras = array();

//		$limit = intval( Utilities::getArrayValue( 'limit', $data, 0 ) );
//		$order = Utilities::getArrayValue( 'order', $data, '' );
//		$include_count = DataFormat::boolval( Utilities::getArrayValue( 'include_count', $data, false ) );

		$_extras['limit'] = intval( Option::get( $_REQUEST, 'limit', 0 ) );
		$_extras['order'] = Option::get( $_REQUEST, 'order', '' );
		$_extras['include_count'] = DataFormat::boolval( Option::get( $_REQUEST, 'include_count', false ) );

		return $_extras;
	}

	// REST service implementation

	/**
	 * @throws \Exception
	 * @return array
	 */
	protected function _listResources()
	{
		try
		{
			$iterator = $this->_dbConn->getIterator( 'ListTables' );

			$tables = $iterator->toArray();
			$out = array();
			foreach ( $tables as $table )
			{
				$out[] = array( 'name' => $table );
			}

			return array( 'resource' => $out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to list tables of DynamoDb Tables service.\n" . $ex->getMessage() );
		}
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
		try
		{
			$iterator = $this->_dbConn->getIterator( 'ListTables' );

			$tables = $iterator->toArray();
			$out = array();
			foreach ( $tables as $table )
			{
				$out[] = array( 'name' => $table );
			}

			return $out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to list tables of DynamoDb Tables service.\n" . $ex->getMessage() );
		}
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
		$result = $this->_dbConn->describeTable(
			array(
				 'TableName' => $table
			)
		);

		// The result of an operation can be used like an array
		return $result['Table'];
	}

	/**
	 * @param array $tables
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function createTables( $tables = array() )
	{
		try
		{
			$_out = array();
			foreach ($tables as $table)
			{
				$_name = Option::get($table, 'name');
				if (empty($_name))
				{
					throw new \Exception( "No 'name' field in data." );
				}
				$this->_dbConn->createTable(
					array(
						 'TableName'             => $_name,
						 'AttributeDefinitions'  => array(
							 array(
								 'AttributeName' => 'id',
								 'AttributeType' => 'N'
							 )
						 ),
						 'KeySchema'             => array(
							 array(
								 'AttributeName' => 'id',
								 'KeyType'       => 'HASH'
							 )
						 ),
						 'ProvisionedThroughput' => array(
							 'ReadCapacityUnits'  => 10,
							 'WriteCapacityUnits' => 20
						 )
					)
				);

				// Wait until the table is created and active
				$this->_dbConn->waitUntilTableExists(
					array(
						 'TableName' => $_name
					)
				);
				$_out[] = array( 'name' => $_name );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create table on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param array  $properties
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function createTable( $table, $properties = array() )
	{
		try
		{
			$this->_dbConn->createTable(
				array(
					 'TableName'             => $table,
					 'AttributeDefinitions'  => array(
						 array(
							 'AttributeName' => 'id',
							 'AttributeType' => 'N'
						 )
					 ),
					 'KeySchema'             => array(
						 array(
							 'AttributeName' => 'id',
							 'KeyType'       => 'HASH'
						 )
					 ),
					 'ProvisionedThroughput' => array(
						 'ReadCapacityUnits'  => 10,
						 'WriteCapacityUnits' => 20
					 )
				)
			);

			// Wait until the table is created and active
			$this->_dbConn->waitUntilTableExists(
				array(
					 'TableName' => $table
				)
			);
			return array( 'name' => $table );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create table on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param array $tables
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function updateTables( $tables = array() )
	{
		try
		{
			$_out = array();
			foreach ($tables as $table)
			{
				$_name = Option::get($table, 'name');
				if (empty($_name))
				{
					throw new \Exception( "No 'name' field in data." );
				}
//				$this->_dbConn->updateTable( $_name );
				$_out[] = array( 'name' => $_name );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update table on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * Get any properties related to the table
	 *
	 * @param string $table Table name
	 * @param array  $properties
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function updateTable( $table, $properties = array() )
	{
		// Update the provisioned throughput capacity of the table
		$this->_dbConn->updateTable(
			array(
				 'TableName'             => $table,
				 'ProvisionedThroughput' => array(
					 'ReadCapacityUnits'  => 15,
					 'WriteCapacityUnits' => 25
				 )
			)
		);

		// Wait until the table is active again after updating
		$this->_dbConn->waitUntilTableExists(
			array(
				 'TableName' => $table
			)
		);
//		throw new \Exception( "Failed to update table '$table' on DynamoDb Tables service." );
		return array( 'name' => $table );
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
		try
		{
			$_out = array();
			foreach ($tables as $table)
			{
				$_name = Option::get($table, 'name');
				if (empty($_name))
				{
					throw new \Exception( "No 'name' field in data." );
				}
				$this->_dbConn->deleteTable(
					array(
						 'TableName' => $_name
					)
				);

				$this->_dbConn->waitUntilTableNotExists(
					array(
						 'TableName' => $_name
					)
				);
				$_out[] = array( 'name' => $_name );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete tables from DynamoDb Tables service.\n" . $ex->getMessage() );
		}
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
			$this->_dbConn->deleteTable(
				array(
					 'TableName' => $table
				)
			);

			$this->_dbConn->waitUntilTableNotExists(
				array(
					 'TableName' => $table
				)
			);
			return array( 'name' => $table );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete table '$table' from DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	//-------- Table Records Operations ---------------------
	// records is an array of field arrays

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function createRecords( $table, $records, $rollback = false, $fields = '', $extras = array() )
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
		try
		{
			$_out = array();
			foreach ( $records as $record )
			{
				// Add operation to list of batch operations.
			}
			$response = $this->_dbConn->batchWriteItem(array(
													 "RequestItems" => array(
														 $table => array(
															 array(
																 "PutRequest" => array(
																	 "Item" => array(
																		 "ForumName"   => array(Type::STRING => "S3 Forum"),
																		 "Subject" => array(Type::STRING => "My sample question"),
																		 "Message"=> array(Type::STRING => "Message Text."),
																		 "KeywordTags"=>array(Type::STRING_SET => array("S3", "Bucket"))
																	 ))
															 ),
															 array(
																 "DeleteRequest" => array(
																	 "Key" => array(
																		 "ForumName" =>array(Type::STRING => "Some hash value"),
																		 "Subject" => array(Type::STRING => "Some range key")
																	 ))
															 )
														 )
													 )
												));

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return $_out;
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to create items in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
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
	public function createRecord( $table, $record, $fields = '', $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		try
		{
			// simple insert request
			// add id to properties

			$result = $this->_dbConn->putItem(
				array(
					 'TableName'              => $table,
					 'Item'                   => $this->_dbConn->formatAttributes( $record ),
					 'ReturnConsumedCapacity' => 'TOTAL'
				)
			);

			// The result will always contain ConsumedCapacityUnits
//		echo $result->getPath( 'ConsumedCapacity/CapacityUnits' ) . "\n";

			return array();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param        $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() )
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
		try
		{
			$_out = array();
			foreach ( $records as $key => $record )
			{
				$_id = Option::get($record, 'rowkey');
				if (empty($_id))
				{
					throw new BadRequestException("No identifier 'RowKey' exist in record index '$key'.");
				}
			}

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return $_out;
			}

			return $this->retrieveRecords($table, $records, $id_field = '', $fields = '', $extras);
		}
		catch ( \Exception $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to update items in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @throws BadRequestException
	 * @return array
	 */
	public function updateRecord( $table, $record, $id_field = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$_id = Option::get($record, 'rowkey');
		if (empty($_id))
		{
			throw new BadRequestException('No identifier exist in record.');
		}
		try
		{
			// add id to properties

			$result = $this->_dbConn->putItem(
				array(
					 'TableName'              => $table,
					 'Item'                   => $this->_dbConn->formatAttributes( $record ),
					 'ReturnConsumedCapacity' => 'TOTAL'
				)
			);

			return array();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
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
	public function updateRecordsByFilter( $table, $record, $filter = '', $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		$table = $this->correctTableName( $table );
		try
		{
			// parse filter
			$rows = $this->_dbConn->updateItem( $table, $record, $filter );

			$results = array();
			if ( !empty( $fields ) )
			{
				$results = $this->retrieveRecordsByFilter( $table, $filter, $fields, $extras );
			}

			return $results;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $table
	 * @param array  $record
	 * @param string $id_list
	 * @param string $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecordsByIds( $table, $record, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( "No record fields were passed in the request." );
		}
		$table = $this->correctTableName( $table );

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for '$id_field' can not be empty for update request." );
		}

		$ids = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );

		try
		{
			$_out = array();
			foreach ( $ids as $key => $_id )
			{
				if (empty($id))
				{
					throw new BadRequestException("No identifier exist in identifier index $key.");
				}

			}

			return $this->retrieveRecordsByIds($table, $id_list, $id_field = '', $fields = '', $extras);
		}
		catch ( \Exception $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to update items in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function updateRecordById( $table, $record, $id, $id_field = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		if (empty($id))
		{
			throw new BadRequestException("No identifier exist in record.");
		}
		try
		{
			// get a new copy with properties
			return $this->retrieveRecordById($table, $id, '', $fields, $extras);
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param        $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		// currently the same as update here
		return $this->updateRecords( $table, $records, $id_field, $rollback, $fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecord( $table, $record, $id_field = '', $fields = '', $extras = array() )
	{
		// currently the same as update here
		return $this->updateRecord( $table, $record, $id_field, $fields, $extras );
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
	public function mergeRecordsByFilter( $table, $record, $filter = '', $fields = '', $extras = array() )
	{
		// currently the same as update here
		return $this->updateRecordsByFilter( $table, $record, $filter, $fields, $extras );
	}

	/**
	 * @param string $table
	 * @param array  $record
	 * @param string $id_list
	 * @param string $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecordsByIds( $table, $record, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		// currently the same as update here
		return $this->updateRecordsByIds( $table, $record, $id_list, $id_field, $rollback, $fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecordById( $table, $record, $id, $id_field = '', $fields = '', $extras = array() )
	{
		// currently the same as update here
		return $this->updateRecordById( $table, $record, $id, $id_field, $fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param        $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array|string
	 */
	public function deleteRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() )
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
		try
		{
			$_outMore = array();
			if ( !( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) ))
			{
				$_outMore = $this->retrieveRecords($table, $records, $id_field = '', $fields = '', $extras);
			}
			$_out = array();
			foreach ( $records as $key => $record )
			{
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to delete items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecord( $table, $record, $id_field = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$_id = Option::get($record, 'rowkey');
		if (empty($_id))
		{
			throw new BadRequestException('No identifier exist in record.');
		}

		$result = array( 'RowKey' => $_id );
		if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
		{
			$result = $this->retrieveRecordById($table, $_id, $id_field, $fields, $extras);
		}

		$this->_dbConn->deleteEntity( $table, $this->_partitionKey, $_id );

		return $result;
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
	public function deleteRecordsByFilter( $table, $filter, $fields = '', $extras = array() )
	{
		if ( empty( $filter ) )
		{
			throw new BadRequestException( "Filter for delete request can not be empty." );
		}
		$table = $this->correctTableName( $table );
		try
		{
			$results = array();
			// get the returnable fields first, then issue delete
			if ( !empty( $fields ) )
			{
				$results = $this->retrieveRecordsByFilter( $table, $filter, $fields, $extras );
			}

			// parse filter
			$id = $this->_dbConn->deleteItem( $table, $filter );

			return $results;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param        $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecordsByIds( $table, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		$table = $this->correctTableName( $table );

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for '$id_field' can not be empty for update request." );
		}

		$ids = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );

		// get the returnable fields first, then issue delete
		$_outMore = array();
		if ( !( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) ))
		{
			$_outMore = $this->retrieveRecordsByIds($table, $id_list, $id_field = '', $fields = '', $extras);
		}

		try
		{
			// Create list of batch operation.
			$operations = new BatchOperations();

			$_out = array();
			foreach ( $ids as $key => $id )
			{
				if (empty($id))
				{
					throw new Exception("No identifier exist in identifier number $key.");
				}
				$_out[] = array('RowKey' => $id);

				// Add operation to list of batch operations.
				$operations->addDeleteEntity( $table, $this->_partitionKey, $id );
			}

			/** @var BatchResult $results */
			$results = $this->_dbConn->batch( $operations );

			foreach ($results->getEntries() as $result)
			{
				// not much good in here
			}

			if ( !empty( $_outMore ) )
			{
				return $_outMore;
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to delete items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function deleteRecordById( $table, $id, $id_field = '', $fields = '', $extras = array() )
	{
		try
		{
			$this->_dbConn->deleteItem(
				array(
					 'TableName' => $table,
					 'Key'       => array(
						 'id' => array( 'N' => $id )
					 )
				)
			);

			return array();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete item from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
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
	public function retrieveRecordsByFilter( $table, $filter = '', $fields = '', $extras = array() )
	{
		$table = $this->correctTableName( $table );

		$this->checkConnection();

		try
		{
			$iterator = $this->_dbConn->getIterator(
				'Scan',
				array(
					 'TableName'  => $table,
					 'ScanFilter' => array(
						 'error' => array(
							 'AttributeValueList' => array(
								 array( 'S' => 'overflow' )
							 ),
							 'ComparisonOperator' => 'CONTAINS'
						 ),
						 'time'  => array(
							 'AttributeValueList' => array(
								 array( 'N' => strtotime( '-15 minutes' ) )
							 ),
							 'ComparisonOperator' => 'GT'
						 )
					 )
				)
			);

			// Each item will contain the attributes we added
			foreach ( $iterator as $item )
			{
				// Grab the time number value
//				echo $item['time']['N'] . "\n";
			}

			return $iterator->toArray();
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to filter items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param array  $records
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecords( $table, $records, $id_field = 'id', $fields = '', $extras = array() )
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
		try
		{
			$_out = array();
			foreach ( $records as $key => $record )
			{
				$_id = Option::get($record, 'rowkey');
				if ( empty( $_id ) )
				{
					throw new BadRequestException( "Identifying field 'RowKey' can not be empty for retrieve record index '$key' request." );
				}
				$ids[] = $_id;
				$_partKey = Option::get($record, 'partitionkey');
				if (empty($_partKey))
				{
					$_partKey = $this->_partitionKey;
				}
				/** @var GetEntityResult $result */
				$result = $this->_dbConn->getEntity( $table, $_partKey, $_id );
				$entity = $result->getEntity();

				$_out[] = static::parseEntityToRecord($entity, array(), $fields);
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecord( $table, $record, $id_field = 'id', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		$table = $this->correctTableName( $table );
		$_id = Option::get($record, 'rowkey');
		if (empty($_id))
		{
			throw new BadRequestException( "Identifying field 'RowKey' can not be empty for retrieve record request." );
		}
		$_partKey = Option::get($record, 'partitionkey');
		if (empty($_partKey))
		{
			$_partKey = $this->_partitionKey;
		}
		try
		{
			/** @var GetEntityResult $result */
			$result = $this->_dbConn->getEntity( $table, $_partKey, $_id );
			$entity = $result->getEntity();

			return static::parseEntityToRecord($entity, array(), $fields);
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get item '$table/$_id' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param string $id_list - comma delimited list of ids
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecordsByIds( $table, $id_list, $id_field = 'id', $fields = '', $extras = array() )
	{
		if ( empty( $id_list ) )
		{
			return array();
		}
		$ids = array_map( 'trim', explode( ',', trim($id_list, ',') ) );
		$table = $this->correctTableName( $table );
		try
		{
			$_keys = array();

			// Build the array for the "Keys" parameter
			foreach ( $ids as $id )
			{
				$_keys[] = array(
					'id' => array( 'N' => $id )
				);
			}

			// Get multiple items by key in a BatchGetItem request
			$result = $this->_dbConn->batchGetItem(
				array(
					 'RequestItems' => array(
						 $table => array(
							 'Keys'           => $_keys,
							 'ConsistentRead' => true
						 )
					 )
				)
			);

			$_items = $result->getPath( "Responses/{$table}" );
			$_out = array();
			foreach ( $_items as $_item )
			{
				$_out[] = $_item['Item'];
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecordById( $table, $id, $id_field = '', $fields = '', $extras = array() )
	{
		if ( empty( $id ) )
		{
			return array();
		}
		$table = $this->correctTableName( $table );
		try
		{
			$result = $this->_dbConn->getItem(
				array(
					 'ConsistentRead' => true,
					 'TableName'      => $table,
					 'Key'            => array(
						 'id' => array( 'N' => $id )
					 )
				)
			);

			// Grab value from the result object like an array
			return $result['Item'];
//		echo $result->getPath( 'Item/id/N' ) . "\n";
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get item '$table/$id' on DynamoDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param array       $record
	 * @param null|Entity $entity
	 * @param array       $exclude List of keys to exclude from adding to Entity
	 *
	 * @return Entity
	 */
	protected static function parseRecordToEntity( $record = array(), $entity = null, $exclude = array() )
	{
		if ( empty( $entity ) )
		{
			$entity = new Entity();
		}
		foreach ( $record as $key => $value )
		{
			if (false === array_search( $key, $exclude ) )
			{
//				$entity->addProperty( $key, EdmType::STRING, $value );
				if ($entity->getProperty($key))
				{
					$entity->setPropertyValue( $key, $value );
				}
				else
				{
					$entity->addProperty( $key, null, $value );
				}
			}
		}

		return $entity;
	}

	/**
	 * @param null|Entity  $entity
	 * @param array        $record
	 * @param string|array $include List of keys to include in the output record
	 *
	 * @return array
	 */
	protected static function parseEntityToRecord( $entity = null, $record = array(), $include = '*' )
	{
		if ( !empty( $entity ) )
		{
			if ( empty( $include ) )
			{
				$record['RowKey'] = $entity->getRowKey();
			}
			elseif ( '*' == $include )
			{
				// return all properties
				/** @var Property[] $properties */
				$properties = $entity->getProperties();
				foreach ( $properties as $key => $property )
				{
					$record[$key] = $property->getValue();
				}
			}
			else
			{
				if ( !is_array( $include ) )
				{
					$include = array_map( 'trim', explode( ',', trim( $include, ',' ) ) );
				}
				foreach ( $include as $key )
				{
					$record[$key] = $entity->getPropertyValue( $key );
				}
			}
		}

		return $record;
	}
}
