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

use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\FilterInput;
use Platform\Exceptions\BadRequestException;
use Platform\Utility\DataFormat;
use WindowsAzure\Table\TableRestProxy;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\Models\EdmType;
use WindowsAzure\Table\Models\Property;
use WindowsAzure\Table\Models\GetTableResult;
use WindowsAzure\Table\Models\QueryTablesResult;
use WindowsAzure\Table\Models\GetEntityResult;
use WindowsAzure\Table\Models\QueryEntitiesOptions;
use WindowsAzure\Table\Models\QueryEntitiesResult;
use WindowsAzure\Table\Models\InsertEntityResult;
use WindowsAzure\Table\Models\UpdateEntityResult;
use WindowsAzure\Table\Models\BatchOperations;
use WindowsAzure\Table\Models\BatchResult;
use WindowsAzure\Table\Models\Filters\QueryStringFilter;

/**
 * WindowsAzureTablesSvc.php
 *
 * A service to handle Windows Azure Tables NoSQL (schema-less) database
 * services accessed through the REST API.
 */
class WindowsAzureTablesSvc extends NoSqlDbSvc
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var TableRestProxy|null
	 */
	protected $_dbConn = null;
	/**
	 * @var string
	 */
	protected $_defaultPartitionKey = 'df_service';
	/**
	 * @var boolean
	 */
	protected $_defaultSimpleFormat = true;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new WindowsAzureTablesSvc
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
		$_name = Option::get( $_credentials, 'account_name' );
		if ( empty( $_name ) )
		{
			throw new \Exception( 'WindowsAzure storage name can not be empty.' );
		}

		$_key = Option::get( $_credentials, 'account_key' );
		if ( empty( $_key ) )
		{
			throw new \Exception( 'WindowsAzure storage key can not be empty.' );
		}

		// set up a default partition key
		$_parameters = Option::get( $config, 'parameters' );
		$_partitionKey = Option::get( $_parameters, 'partition_key' );
		if ( empty( $_partitionKey ) )
		{
			// use API name as the default partition key,
			// it can be overridden by individual get/set methods
			$_partitionKey = Option::get( $config, 'api_name' );
		}
		if ( !empty( $_partitionKey ) )
		{
			$this->_defaultPartitionKey = $_partitionKey;
		}

		// reply in simple format by default
		$_simpleFormat = Option::get( $_parameters, 'simple_format' );
		if ( !empty( $_simpleFormat ) )
		{
			$this->_defaultSimpleFormat = DataFormat::boolval( $_simpleFormat );
		}

		try
		{
			$_connectionString = "DefaultEndpointsProtocol=https;AccountName=$_name;AccountKey=$_key";
			$this->_dbConn = ServicesBuilder::getInstance()->createTableService( $_connectionString );
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( 'Unexpected Windows Azure Table Service \Exception: ' . $ex->getMessage() );
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
	 */
	protected function validateTableAccess( $table, $access = 'read' )
	{
		parent::validateTableAccess( $table, $access );

	}

	protected function gatherExtrasFromRequest()
	{
		$_extras = parent::gatherExtrasFromRequest();

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
			/** @var QueryTablesResult $result */
			$result = $this->_dbConn->queryTables();
			/** @var GetTableResult[] $tables */
			$tables = $result->getTables();
			$out = array();
			foreach ( $tables as $table )
			{
				$out[] = array( 'name' => $table );
			}

			return array( 'resource' => $out );
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to list tables of Windows Azure Tables service.\n" . $ex->getMessage() );
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
			/** @var QueryTablesResult $result */
			$result = $this->_dbConn->queryTables();
			/** @var GetTableResult[] $tables */
			$tables = $result->getTables();
			$out = array();
			foreach ( $tables as $table )
			{
				$out[] = array( 'name' => $table );
			}

			return $out;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to list tables of Windows Azure Tables service.\n" . $ex->getMessage() );
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
		return array( 'name' => $table );
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
				$this->_dbConn->createTable( $_name );
				$_out[] = array( 'name' => $_name );
			}

			return $_out;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to create table on Windows Azure Tables service.\n" . $ex->getMessage() );
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
			$this->_dbConn->createTable( $table );
			return array( 'name' => $table );
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to create table on Windows Azure Tables service.\n" . $ex->getMessage() );
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
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to update table on Windows Azure Tables service.\n" . $ex->getMessage() );
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
		throw new \Exception( "Failed to update table '$table' on Windows Azure Tables service." );
//		return array( 'name' => $table );
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
				$this->_dbConn->deleteTable( $_name );
				$_out[] = array( 'name' => $_name );
			}

			return $_out;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to delete tables from Windows Azure Tables service.\n" . $ex->getMessage() );
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
			$this->_dbConn->deleteTable( $table );
			return array( 'name' => $table );
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to delete table '$table' from Windows Azure Tables service.\n" . $ex->getMessage() );
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
			// Create list of batch operation.
			$operations = new BatchOperations();

			$_out = array();
			foreach ( $records as $record )
			{
				$entity = static::parseRecordToEntity( $record );
				$_id = $entity->getRowKey();
				if (empty($_id))
				{
					$_id = static::createItemId( $table );
					$entity->setRowKey( $_id );
				}
				if (!$entity->getPartitionKey())
				{
					$entity->setPartitionKey( $this->_defaultPartitionKey );
				}
				$_out[] = array( 'RowKey' => $_id );

				// Add operation to list of batch operations.
				$operations->addInsertEntity( $table, $entity );
			}

			/** @var BatchResult $results */
			$results = $this->_dbConn->batch( $operations );

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return $_out;
			}

			/** @var InsertEntityResult $result */
			foreach ($results->getEntries() as $key => $result)
			{
				$_out[$key] = static::parseEntityToRecord($result->getEntity(), array(), $fields);
			}

			return $_out;
		}
		catch ( ServiceException $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to create items in '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
			$entity = static::parseRecordToEntity( $record );
			$_id = $entity->getRowKey();
			if (empty($_id))
			{
				$_id = static::createItemId( $table );
				$entity->setRowKey( $_id );
			}
			if (!$entity->getPartitionKey())
			{
				$entity->setPartitionKey( $this->_defaultPartitionKey );
			}

			/** @var InsertEntityResult $result */
			$result = $this->_dbConn->insertEntity( $table, $entity );
			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return array( 'RowKey' => $_id );
			}

			return static::parseEntityToRecord( $result->getEntity(), array(), $fields );
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to create item in '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
	 * @throws BadRequestException
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
			// Create list of batch operation.
			$operations = new BatchOperations();

			$_out = array();
			foreach ( $records as $key => $record )
			{
				$_id = Option::get($record, 'rowkey');
				if (empty($_id))
				{
					throw new BadRequestException("No identifier 'RowKey' exist in record index '$key'.");
				}
				$entity = static::parseRecordToEntity($record);
				if (!$entity->getPartitionKey())
				{
					$entity->setPartitionKey( $this->_defaultPartitionKey );
				}
				$_out[] = array( 'RowKey' => $_id );

				// Add operation to list of batch operations.
				$operations->addUpdateEntity( $table, $entity );
			}

			/** @var BatchResult $results */
			$results = $this->_dbConn->batch( $operations );

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return $_out;
			}

			/** @var UpdateEntityResult $result */
			foreach ($results->getEntries() as $result)
			{
				// not much good in here
			}

			return $this->retrieveRecords($table, $records, $id_field = '', $fields = '', $extras);
		}
		catch ( ServiceException $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to update items in '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
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
			$entity = static::parseRecordToEntity($record);
			if (!$entity->getPartitionKey())
			{
				$entity->setPartitionKey( $this->_defaultPartitionKey );
			}

			/** @var UpdateEntityResult $result */
			$result = $this->_dbConn->updateEntity( $table, $entity );

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return array( 'RowKey' => $entity->getRowKey() );
			}

			// get a new copy with properties
			return $this->retrieveRecordById($table, $_id, '', $fields, $extras);
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
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
//			$rows = $this->_dbConn->updateItem( $table, $record, $filter );

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
	 * @throws BadRequestException
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
			// Create list of batch operation.
			$operations = new BatchOperations();

			$entity = static::parseRecordToEntity($record);
			if (!$entity->getPartitionKey())
			{
				$entity->setPartitionKey( $this->_defaultPartitionKey );
			}
			$_out = array();
			foreach ( $ids as $key => $_id )
			{
				if (empty($id))
				{
					throw new BadRequestException("No identifier exist in identifier index $key.");
				}

				$entity->setRowKey($id);

				// Add operation to list of batch operations.
				$operations->addUpdateEntity( $table, $entity );
				$_out[] = array( 'RowKey' => $_id );
			}

			/** @var BatchResult $results */
			$results = $this->_dbConn->batch( $operations );

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return $_out;
			}

			/** @var UpdateEntityResult $result */
			foreach ($results->getEntries() as $result)
			{
				// not much good in here
			}

			return $this->retrieveRecordsByIds($table, $id_list, $id_field = '', $fields = '', $extras);
		}
		catch ( ServiceException $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to update items in '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
	 * @throws BadRequestException
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
			$entity = static::parseRecordToEntity($record);
			$entity->setRowKey( $id );
			if (!$entity->getPartitionKey())
			{
				$entity->setPartitionKey( $this->_defaultPartitionKey );
			}

			/** @var UpdateEntityResult $result */
			$result = $this->_dbConn->updateEntity( $table, $entity );

			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				return array( 'RowKey' => $entity->getRowKey() );
			}

			// get a new copy with properties
			return $this->retrieveRecordById($table, $id, '', $fields, $extras);
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
	 * @throws BadRequestException
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
	 * @throws BadRequestException
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
	 * @throws BadRequestException
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
	 * @throws BadRequestException
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
	 * @throws BadRequestException
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
			// Create list of batch operation.
			$operations = new BatchOperations();

			$_outMore = array();
			if ( !( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) ))
			{
				$_outMore = $this->retrieveRecords($table, $records, $id_field = '', $fields = '', $extras);
			}
			$_out = array();
			foreach ( $records as $key => $record )
			{
				$_id = Option::get($record, 'rowkey');
				if (empty($_id))
				{
					throw new BadRequestException("No identifier 'RowKey' exist in record index '$key'.");
				}
				$_partKey = Option::get($record, 'partitionkey');
				if (empty($_partKey))
				{
					$_partKey = $this->_defaultPartitionKey;
				}
				$_out[] = array( 'RowKey' => $_id );

				// Add operation to list of batch operations.
				$operations->addDeleteEntity( $table, $_partKey, $_id );
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
		catch ( ServiceException $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to delete items from '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
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

		$this->_dbConn->deleteEntity( $table, $this->_defaultPartitionKey, $_id );

		return $result;
	}

	/**
	 * @param        $table
	 * @param        $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
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
//			$id = $this->_dbConn->deleteItem( $table, $filter );

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
	 * @throws BadRequestException
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
					throw new BadRequestException("No identifier exist in identifier number $key.");
				}
				$_out[] = array('RowKey' => $id);

				// Add operation to list of batch operations.
				$operations->addDeleteEntity( $table, $this->_defaultPartitionKey, $id );
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
		catch ( ServiceException $ex )
		{
			if ( $rollback )
			{
			}
			throw new \Exception( "Failed to delete items from '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
			$result = array( 'RowKey' => $id );
			if ( empty( $fields ) || ( 0 === strcasecmp( 'RowKey', $fields ) ) )
			{
				$result = $this->retrieveRecordById($table, $id, $id_field, $fields, $extras);
			}
			$this->_dbConn->deleteEntity( $table, $this->_defaultPartitionKey, $id );

			return $result;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to delete item from '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param string $fields
	 * @param string $filter
	 * @param int    $limit
	 * @param string $order
	 * @param int    $offset
	 * @param bool   $include_count
	 * @param bool   $include_schema
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function retrieveRecordsByFilter( $table, $filter = '', $fields = '', $extras = array() )
	{
		$table = $this->correctTableName( $table );

		$this->checkConnection();

		$_options = new QueryEntitiesOptions();
		$_options->setSelectFields(array());
		if ( !empty( $fields ) && ( '*' != $fields ) )
		{
			$fields = array_map( 'trim', explode( ',', trim( $fields, ',' ) ) );
			$_options->setSelectFields($fields);
		}
		$limit = intval( Option::get($extras, 'limit', 0 ) );
		if ($limit > 0)
		{
			$_options->setTop($limit);
		}

//		$filter = "PartitionKey eq '$_partKey'";
//		$filter = "Location eq 'Office' and DueDate lt '2012-11-5'";
		if (!empty($filter))
		{
			$_query = new QueryStringFilter($filter);
			$_options->setFilter($_query);
		}

		try
		{
			/** @var QueryEntitiesResult $result */
			$result = $this->_dbConn->queryEntities( $table, $_options );

			/** @var Entity[] $entities */
			$entities = $result->getEntities();

			$_out = array();
			foreach ( $entities as $entity )
			{
				$_out[] = static::parseEntityToRecord($entity);
			}

			return $_out;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to filter items from '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
					$_partKey = $this->_defaultPartitionKey;
				}
				/** @var GetEntityResult $result */
				$result = $this->_dbConn->getEntity( $table, $_partKey, $_id );
				$entity = $result->getEntity();

				$_out[] = static::parseEntityToRecord($entity, array(), $fields);
			}

			return $_out;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
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
			$_partKey = $this->_defaultPartitionKey;
		}
		try
		{
			/** @var GetEntityResult $result */
			$result = $this->_dbConn->getEntity( $table, $_partKey, $_id );
			$entity = $result->getEntity();

			return static::parseEntityToRecord($entity, array(), $fields);
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to get item '$table/$_id' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
			$_out = array();
			foreach ( $ids as $id )
			{
				/** @var GetEntityResult $result */
				$result = $this->_dbConn->getEntity( $table, $this->_defaultPartitionKey, $id );
				$entity = $result->getEntity();

				$_out[] = static::parseEntityToRecord($entity, array(), $fields);
			}

			return $_out;
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
			/** @var GetEntityResult $result */
			$result = $this->_dbConn->getEntity( $table, $this->_defaultPartitionKey, $id );
			$entity = $result->getEntity();

			return static::parseEntityToRecord($entity, array(), $fields);
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( "Failed to get item '$table/$id' on Windows Azure Tables service.\n" . $ex->getMessage() );
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
