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
use Platform\Utility\DataFormat;
use Kisma\Core\Utility\Option;

/**
 * MongoDbSvc.php
 *
 * A service to handle MongoDb NoSQL (schema-less) database
 * services accessed through the REST API.
 */
class MongoDbSvc extends NoSqlDbSvc
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var \MongoDB|null
	 */
	protected $_dbConn = null;
	/**
	 * @var boolean
	 */
	protected $_defaultSimpleFormat = true;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new MongoDbSvc
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
		$_dsn = Option::get( $_credentials, 'dsn' );
		$_db = Option::get( $_credentials, 'db' );
		if ( empty( $_db ) )
		{
			throw new \Exception( "No MongoDb database selected in configuration." );
		}

		if ( empty( $_dsn ) )
		{
			$_dsn = 'mongodb://localhost:27017';
		}

		try
		{
			$_client = new \MongoClient( $_dsn );
			$this->_dbConn = $_client->selectDB( $_db );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Unexpected MongoDb Service Exception:\n{$ex->getMessage()}" );
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
	 * @return \MongoCollection|null
	 */
	public function selectTable( $name )
	{
		$this->checkConnection();
		$_coll = $this->_dbConn->selectCollection( $name );

		return $_coll;
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
			$_out = $this->_dbConn->getCollectionNames();

			return array( 'resource' => $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to list containers of MongoDb service.\n" . $ex->getMessage() );
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
			if ( empty( $tables ) )
			{
				$tables = $this->_dbConn->getCollectionNames();
			}

			$_out = array();
			foreach ( $tables as $table )
			{
				$_out[] = $this->getTable( $table );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to list containers of MongoDb service.\n" . $ex->getMessage() );
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
		$_coll = $this->selectTable( $table );
		$_out = array( 'name' => $_coll->getName() );
		$_out['indexes'] = $_coll->getIndexInfo();

		return $_out;
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
			foreach ( $tables as $table )
			{
				$_name = Option::get( $table, 'name' );
				if ( empty( $_name ) )
				{
					throw new \Exception( "No 'name' field in data." );
				}
				$_out[] = $this->createTable( $_name, $table );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create containers on MongoDb service.\n" . $ex->getMessage() );
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
			$result = $this->_dbConn->createCollection( $table );
			$_out = array( 'name' => $result->getName() );
			$_out['indexes'] = $result->getIndexInfo();

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create a container on MongoDb service.\n" . $ex->getMessage() );
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
			foreach ( $tables as $table )
			{
				$_name = Option::get( $table, 'name' );
				if ( empty( $_name ) )
				{
					throw new \Exception( "No 'name' field in data." );
				}
				$_out[] = $this->updateTable( $_name, $table );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update container on MongoDb service.\n" . $ex->getMessage() );
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
		$this->selectTable( $table );

//		throw new \Exception( "Failed to update table '$table' on MongoDb service." );
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
			foreach ( $tables as $table )
			{
				$_name = Option::get( $table, 'name' );
				if ( empty( $_name ) )
				{
					throw new \Exception( "No 'name' field in data." );
				}
				$_out[] = $this->deleteTable( $_name, $check_empty );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete tables from MongoDb service.\n" . $ex->getMessage() );
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
			$result = $this->_dbConn->dropCollection( $table );

			return array( 'name' => $table );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete table '$table' from MongoDb service.\n" . $ex->getMessage() );
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

		$_coll = $this->selectTable( $table );
		try
		{
			$result = $_coll->batchInsert( $records, array( 'continueOnError' => !$rollback ) );
			$_out = static::cleanRecords( $records, $fields );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create items in '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		$_coll = $this->selectTable( $table );
		$record = static::idToMongoId( $record );
		try
		{
			$result = $_coll->save( $record ); // same as insert if no _id
			$_out = static::cleanRecord( $record, $fields );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		$_coll = $this->selectTable( $table );
		$records = static::idsToMongoIds( $records );
		$_out = array();
		foreach ( $records as $_record )
		{
			try
			{
				$result = $_coll->save( $_record ); // same as update if _id
				$_out[] = static::cleanRecord( $_record, $fields );
			}
			catch ( \Exception $ex )
			{
				if ( $rollback )
				{
					throw new \Exception( "Failed to update items in '$table' on MongoDb service.\n" . $ex->getMessage() );
				}

				$_out[] = $ex->getMessage();
			}
		}

		return $_out;
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
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$_coll = $this->selectTable( $table );
		$record = static::idToMongoId( $record );
		try
		{
			$result = $_coll->save( $record ); // same as update if _id

			return static::cleanRecord( $record, $fields );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		unset( $record['_id'] ); // make sure the record has no identifier
		$_coll = $this->selectTable( $table );
		// build criteria from filter parameters
		$_criteria = array();
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->update( $_criteria, $record, array( 'multiple' => true ) );
			/** @var \MongoCursor $result */
			$result = $_coll->find( $_criteria, $_fieldArray );
			$_out = iterator_to_array( $result );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for '$id_field' can not be empty for update request." );
		}

		$_coll = $this->selectTable( $table );
		$_ids = static::idsToMongoIds( $id_list );
		// build criteria from filter parameters
		$_criteria = array( '_id' => array( '$in' => $_ids ) );
		$_fieldArray = static::buildFieldArray( $fields );
		unset( $record['_id'] ); // make sure the record has no identifier
		try
		{
			$result = $_coll->update( $_criteria, $record, array( 'multiple' => true ) );
			$_out = array();
			foreach ( $_ids as $_id )
			{
				$record['_id'] = $_id;
				$_out[] = static::cleanRecords( $record );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param array  $record
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
		if ( empty( $id ) )
		{
			throw new BadRequestException( "No identifier exist in record." );
		}

		$_coll = $this->selectTable( $table );
		$record['_id'] = static::idToMongoId( $id );
		try
		{
			$result = $_coll->save( $record );

			return static::cleanRecord( $record, $fields );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record possibly passed in without wrapper array
			$records = array( $records );
		}

		$_coll = $this->selectTable( $table );
		$records = static::idsToMongoIds( $records );
		$_fieldArray = static::buildFieldArray( $fields );
		$_out = array();
		foreach ( $records as $_record )
		{
			try
			{
				$_id = Option::get( $_record, '_id', null, true );
				if ( empty( $_id ) )
				{
					throw new BadRequestException( "Identifying field '_id' can not be empty for merge record request." );
				}
				$result = $_coll->findAndModify(
					array( '_id' => $_id ),
					$_record,
					$_fieldArray,
					array( 'new' => true )
				);

				$_out[] = static::mongoIdToId( $result );
			}
			catch ( \Exception $ex )
			{
				if ( $rollback )
				{
					throw new \Exception( "Failed to update items in '$table' on MongoDb service.\n" . $ex->getMessage() );
				}

				$_out[] = $ex->getMessage();
			}
		}

		return $_out;
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
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$_id = Option::get( $record, '_id', null, true );
		if ( empty( $_id ) )
		{
			throw new BadRequestException( "Identifying field '_id' can not be empty for merge record request." );
		}

		$_coll = $this->selectTable( $table );
		$_criteria = array( '_id' => static::idToMongoId( $_id ) );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->findAndModify(
				$_criteria,
				array( '$set' => $record ),
				$_fieldArray,
				array( 'new' => true )
			);

			return static::mongoIdToId( $result );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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
	public function mergeRecordsByFilter( $table, $record, $filter = '', $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		unset( $record['_id'] );
		$_coll = $this->selectTable( $table );
		// build criteria from filter parameters
		$_criteria = array();
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->update( $_criteria, array( '$set' => $record ), array( 'multiple' => true ) );
			/** @var \MongoCursor $result */
			$result = $_coll->find( $_criteria, $_fieldArray );
			$_out = iterator_to_array( $result );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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
	public function mergeRecordsByIds( $table, $record, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( "No record fields were passed in the request." );
		}
		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for '$id_field' can not be empty for update request." );
		}

		$_coll = $this->selectTable( $table );
		$_ids = static::idsToMongoIds( $id_list );
		$_criteria = array( '_id' => array( '$in' => $_ids ) );
		$_fieldArray = static::buildFieldArray( $fields );
		unset( $record['_id'] ); // make sure the record has no identifier

		try
		{
			$result = $_coll->update( $_criteria, array( '$set' => $record ), array( 'multiple' => true ) );
			if ( static::requireMoreFields( $fields ) )
			{
				/** @var \MongoCursor $result */
				$result = $_coll->find( $_criteria, $_fieldArray );
				$_out = iterator_to_array( $result );
			}
			else
			{
				$_out = static::idsAsRecords( $_ids );
			}

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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
	public function mergeRecordById( $table, $record, $id, $id_field = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		if ( empty( $id ) )
		{
			throw new BadRequestException( "No identifier exist in record." );
		}

		$_coll = $this->selectTable( $table );
		$_criteria = array( '_id' => static::idToMongoId( $id ) );
		$_fieldArray = static::buildFieldArray( $fields );
		unset( $record['_id'] );
		try
		{
			$result = $_coll->findAndModify(
				$_criteria,
				array( '$set' => $record ),
				$_fieldArray,
				array( 'new' => true )
			);

			return static::mongoIdToId( $result );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		$_coll = $this->selectTable( $table );
		$_ids = static::recordsAsIds( $records );
		$_criteria = array( '_id' => array( '$in' => static::idsToMongoIds( $_ids ) ) );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			if ( static::requireMoreFields( $fields ) )
			{
				/** @var \MongoCursor $result */
				$result = $_coll->find( $_criteria, $_fieldArray );
				$_out = iterator_to_array( $result );
			}
			else
			{
				$_out = static::idsAsRecords( $_ids );
			}

			$result = $_coll->remove( $_criteria );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete items from '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		$_coll = $this->selectTable( $table );
		$_criteria = static::idToMongoId( $record );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->findAndModify( $_criteria, null, $_fieldArray, array( 'remove' => true ) );

			return static::mongoIdToId( $result );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete item from '$table' on MongoDb service.\n" . $ex->getMessage() );
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
	public function deleteRecordsByFilter( $table, $filter, $fields = '', $extras = array() )
	{
		if ( empty( $filter ) )
		{
			throw new BadRequestException( "Filter for delete request can not be empty." );
		}

		$_coll = $this->selectTable( $table );
		try
		{
			$_records = $this->retrieveRecordsByFilter( $table, $filter, $fields, $extras );
			$results = $_coll->remove( $filter );

			return $_records;
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
		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for '$id_field' can not be empty for update request." );
		}

		$_coll = $this->selectTable( $table );
		$_ids = static::idsToMongoIds( $id_list );
		$_criteria = array( '_id' => array( '$in' => $_ids ) );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			if ( static::requireMoreFields( $fields ) )
			{
				/** @var \MongoCursor $result */
				$result = $_coll->find( $_criteria, $_fieldArray );
				$_out = iterator_to_array( $result );
			}
			else
			{
				$_out = static::idsAsRecords( $_ids );
			}

			$result = $_coll->remove( $_criteria );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete items from '$table' on MongoDb service.\n" . $ex->getMessage() );
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
		if ( empty( $id ) )
		{
			throw new BadRequestException( "No identifier exist in record." );
		}

		$_coll = $this->selectTable( $table );
		$_criteria = array( '_id' => static::idToMongoId( $id ) );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->findAndModify( $_criteria, null, $_fieldArray, array( 'remove' => true ) );

			return static::mongoIdToId( $result );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete item from '$table' on MongoDb service.\n" . $ex->getMessage() );
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
		$_coll = $this->selectTable( $table );
		$_fieldArray = static::buildFieldArray( $fields );
		$_criteria = array();
		try
		{
			/** @var \MongoCursor $result */
			$result = $_coll->find( $_criteria, $_fieldArray );
			$_out = iterator_to_array( $result );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to filter items from '$table' on MongoDb service.\n" . $ex->getMessage() );
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
	public function retrieveRecords( $table, $records, $id_field = '', $fields = '', $extras = array() )
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

		$_coll = $this->selectTable( $table );
		$_ids = static::idsToMongoIds( static::recordsAsIds( $records ) );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			/** @var \MongoCursor $result */
			$result = $_coll->find( array( '$in' => $_ids ), $_fieldArray );
			$_out = iterator_to_array( $result );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on MongoDb service.\n" . $ex->getMessage() );
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
	public function retrieveRecord( $table, $record, $id_field = '', $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$_coll = $this->selectTable( $table );
		$_id = Option::get( $record, '_id' );
		if ( empty( $_id ) )
		{
			throw new BadRequestException( "Identifying field '_id' can not be empty for retrieve record request." );
		}

		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->findOne( array( '_id' => new \MongoId( $_id ) ), $_fieldArray );

			return static::mongoIdToId( $result );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get item '$table/$_id' on MongoDb service.\n" . $ex->getMessage() );
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
	public function retrieveRecordsByIds( $table, $id_list, $id_field = '', $fields = '', $extras = array() )
	{
		if ( empty( $id_list ) )
		{
			return array();
		}

		$_coll = $this->selectTable( $table );
		$_ids = static::idsToMongoIds( $id_list );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			/** @var \MongoCursor $result */
			$result = $_coll->find( array( '_id' => array( '$in' => $_ids ) ), $_fieldArray );
			$_out = iterator_to_array( $result );

			return static::cleanRecords( $_out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on MongoDb service.\n" . $ex->getMessage() );
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

		$_coll = $this->selectTable( $table );
		$_fieldArray = static::buildFieldArray( $fields );
		try
		{
			$result = $_coll->findOne( array( '_id' => new \MongoId( $id ) ), $_fieldArray );

			return static::mongoIdToId( $result );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get item '$table/$id' on MongoDb service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string|array $include List of keys to include in the output record
	 *
	 * @return array
	 */
	protected static function buildFieldArray( $include = '*' )
	{
		if ( empty( $include ) )
		{
			return array( '_id' => true );
		}
		if ( '*' == $include )
		{
			return array();
		}

		if ( !is_array( $include ) )
		{
			$include = array_map( 'trim', explode( ',', trim( $include, ',' ) ) );
		}
		$_out = array( '_id' => true );
		foreach ( $include as $key )
		{
			if ( 0 == strcasecmp( $key, '_id' ) )
			{
				continue;
			}
			$_out[$key] = true;
		}

		return $_out;
	}

	/**
	 * @param array        $record
	 * @param string|array $include List of keys to include in the output record
	 *
	 * @return array
	 */
	protected static function cleanRecord( $record, $include = '*' )
	{
		if ( '*' !== $include )
		{
			$_id = Option::get( $record, '_id' );
			if ( empty( $_id ) )
			{
				$_id = Option::get( $record, 'id' );
			}
			$_out = array( '_id' => static::mongoIdToId( $_id ) );

			if ( !empty( $include ) )
			{
				if ( !is_array( $include ) )
				{
					$include = array_map( 'trim', explode( ',', trim( $include, ',' ) ) );
				}
				foreach ( $include as $key )
				{
					if ( 0 == strcasecmp( $key, '_id' ) )
					{
						continue;
					}
					$_out[$key] = Option::get( $record, $key );
				}
			}

			return $_out;
		}

		return static::mongoIdToId( $record );
	}

	protected static function cleanRecords( $records, $include = '*' )
	{
		$_out = array();
		foreach ( $records as $_record )
		{
			$_out[] = static::cleanRecord( $_record, $include );
		}

		return $_out;
	}

	protected static function mongoIdsToIds( $records )
	{
		foreach ( $records as $key => $_record )
		{
			$records[$key] = static::mongoIdToId( $_record );
		}

		return $records;
	}

	protected static function mongoIdToId( $record )
	{
		if ( !is_array( $record ) )
		{
			if ( is_object( $record ) )
			{
				/** $record \MongoId */
				$record = (string)$record;
			}
		}
		else
		{
			/** @var \MongoId $_id */
			$_id = Option::get( $record, '_id' );
			if ( is_object( $_id ) )
			{
				/** $_id \MongoId */
				$record['_id'] = (string)$_id;
			}
		}

		return $record;
	}

	protected static function idToMongoId( $record )
	{
		if ( !is_array( $record ) )
		{
			// single id
			try
			{
				$record = new \MongoId( $record );
			}
			catch ( \Exception $ex )
			{
				// obviously not a Mongo created Id, let it be
			}
		}
		else
		{
			// single record with fields
			$_id = Option::get( $record, '_id' );
			if ( !is_object( $_id ) )
			{
				try
				{
					$record['_id'] = new \MongoId( $_id );
				}
				catch ( \Exception $ex )
				{
					// obviously not a Mongo created Id, let it be
				}
			}
		}

		return $record;
	}

	protected static function idsToMongoIds( $records )
	{
		if ( !is_array( $records ) )
		{
			// comma delimited list of ids
			$records = array_map( 'trim', explode( ',', trim( $records, ',' ) ) );
		}

		foreach ( $records as $key => $_record )
		{
			$records[$key] = static::idToMongoId( $_record );
		}

		return $records;
	}

	protected static function recordsAsIds( $records, $id_field = '_id' )
	{
		$_ids = array();
		foreach ( $records as $_key => $_record )
		{
			$_id = Option::get( $_record, $id_field );
			if ( empty( $_id ) )
			{
				throw new BadRequestException( "Identifying field '$id_field' can not be empty for retrieve record index '$_key' request." );
			}
			$_ids[] = $_id;
		}

		return $_ids;
	}

	protected static function idsAsRecords( $ids, $id_field = '_id' )
	{
		$_out = array();
		foreach ( $ids as $_id )
		{
			$_out[] = array( $id_field => $_id );
		}

		return $_out;
	}

	protected static function requireMoreFields( $fields = null )
	{
		if ( empty( $fields ) )
		{
			return false;
		}
		if ( 0 === strcasecmp( '_id', $fields ) )
		{
			return false;
		}

		return true;
	}

	protected static function recordArrayMerge( $first_array, $second_array )
	{
		foreach ( $first_array as $_key => $_first )
		{
			$_firstId = Option::get( $_first, '_id' );
			foreach ( $second_array as $_second )
			{
				$_secondId = Option::get( $_second, '_id' );
				if ( $_firstId == $_secondId )
				{
					$first_array[$_key] = array_merge( $_first, $_second );
				}
			}
		}

		return $first_array;
	}
}
