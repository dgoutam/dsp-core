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

use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Utility\DataFormat;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;

/**
 * CouchDbSvc.php
 *
 * A service to handle Amazon Web Services DynamoDb NoSQL (schema-less) database
 * services accessed through the REST API.
 */
class CouchDbSvc extends NoSqlDbSvc
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * Default record identifier field
	 */
	const DEFAULT_ID_FIELD = '_id';
	/**
	 * Define record revision field
	 */
	const REV_FIELD = '_rev';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var \couchClient|null
	 */
	protected $_dbConn = null;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new CouchDbSvc
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
		if ( empty( $_dsn ) )
		{
			$_dsn = 'http://localhost:5984';
		}

		try
		{
			$this->_dbConn = new \couchClient( $_dsn, 'default' );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Unexpected CouchDb Service Exception:\n{$ex->getMessage()}" );
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
		$this->checkConnection();
		$this->_dbConn->useDatabase( $name );

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

	protected function _gatherExtrasFromRequest( $post_data = null )
	{
		$_extras = parent::_gatherExtrasFromRequest( $post_data );
		// rollback all db changes in a transaction, if applicable
		$_rollback = FilterInput::request( 'rollback', false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		if ( empty( $_rollback ) && !empty( $post_data ) )
		{
			$_rollback = Option::getBool( $post_data, 'rollback' );
		}
		$_extras['rollback'] = $_rollback;

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
			$tables = $this->_dbConn->listDatabases();
			$out = array();
			foreach ( $tables as $table )
			{
				if ( '_' != substr( $table, 0, 1 ) )
				{
					$out[] = array( 'name' => $table );
				}
			}

			return array( 'resource' => $out );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to list containers of CouchDb service.\n" . $ex->getMessage() );
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
				$tables = $this->_dbConn->listDatabases();
				foreach ( $tables as $_key => $_table )
				{
					if ( '_' == substr( $_table, 0, 1 ) )
					{
						unset( $tables[$_key] );
					}
				}
				$tables = array_values( $tables );
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
				$_out[] = $this->getTable( $_table );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to list containers of CouchDb service.\n" . $ex->getMessage() );
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
		$this->correctTableName( $table );
		$_result = $this->_dbConn->asArray()->getDatabaseInfos();
		$_result['name'] = $table;

		return $_result;
	}

	/**
	 * @param array  $properties
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function createTable( $properties = array() )
	{
		$_name = Option::get( $properties, 'name' );
		if ( empty( $_name ) )
		{
			throw new BadRequestException( "No 'name' field in data." );
		}

		try
		{
			$this->correctTableName( $_name );
			$_result = $this->_dbConn->asArray()->createDatabase();

			// $_result['ok'] = true

			return array( 'name' => $_name );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create a container on CouchDb service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * Get any properties related to the table
	 *
	 * @param array  $properties
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

		$this->correctTableName( $_name );

//		throw new InternalServerErrorException( "Failed to update table '$_name' on CouchDb service." );
		return array( 'name' => $_name );
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
					throw new BadRequestException( "No 'name' field in data." );
				}
				$_out[] = $this->deleteTable( $_name, $check_empty );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete tables from CouchDb service.\n" . $ex->getMessage() );
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
			$this->correctTableName( $table );
			$_result = $this->_dbConn->asArray()->deleteDatabase();

			// $_result['ok'] = true

			return array( 'name' => $table );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete table '$table' from CouchDb service.\n" . $ex->getMessage() );
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

		$this->correctTableName( $table );
		$_rollback = Option::getBool( $extras, 'rollback', false );
		try
		{
			$result = $this->_dbConn->asArray()->storeDocs( $records, $_rollback );
			$_out = static::cleanRecords( $result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				return $this->retrieveRecords( $table, $_out, '', $fields, $extras );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create items in '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		try
		{
			$result = $this->_dbConn->asArray()->storeDoc( (object)$record );
			$_out = static::cleanRecord( $result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				return $this->retrieveRecord( $table, $_out, '', $fields, $extras );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to create item in '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		$_rollback = Option::getBool( $extras, 'rollback', false );
		try
		{
			$result = $this->_dbConn->asArray()->storeDocs( $records, $_rollback );
			$_out = static::cleanRecords( $result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				// merge in rev info
				$_out = static::recordArrayMerge( $records, $_out );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update items in '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		try
		{
			$result = $this->_dbConn->asArray()->storeDoc( (object)$record );
			$_out = static::cleanRecord( $result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				$_out = static::recordArrayMerge( $record, $_out );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to update item in '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$table = $this->correctTableName( $table );

		// retrieve records to get latest rev and id
		$_results = $this->retrieveRecordsByFilter( $table, $filter, '', $extras );
		// make sure record doesn't contain identifiers
		unset( $record[static::DEFAULT_ID_FIELD] );
		unset( $record[static::REV_FIELD] );

		$_updates = array();
		foreach ( $_results as $result )
		{
			$_updates[] = array_merge( $result, $record );
		}

		return $this->updateRecords( $table, $_updates, $fields, $extras );
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
		$table = $this->correctTableName( $table );

		// retrieve records to get latest rev and id
		$_results = $this->retrieveRecordsByIds( $table, $id_list, '', $extras );
		// make sure record doesn't contain identifiers
		unset( $record[static::DEFAULT_ID_FIELD] );
		unset( $record[static::REV_FIELD] );

		$_updates = array();
		foreach ( $_results as $_result )
		{
			$_updates[] = array_merge( $_result, $record );
		}

		return $this->updateRecords( $table, $_updates, $fields, $extras );
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

		// retrieve record to get latest rev and id
		$_result = $this->retrieveRecordById( $table, $id, '', $extras );
		// make sure record doesn't contain identifiers
		unset( $record[static::DEFAULT_ID_FIELD] );
		unset( $record[static::REV_FIELD] );

		$_update = array_merge( $_result, $record );

		return $this->updateRecord( $table, $_update, '', true, $fields, $extras );
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
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record possibly passed in without wrapper array
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$_rollback = Option::getBool( $extras, 'rollback', false );
		try
		{
			// get all fields of each record
			$_merges = $this->retrieveRecords( $table, $records, '*', $extras );
			// merge in changes from $records to $_merges
			$_merges = static::recordArrayMerge( $_merges, $records );
			// write back the changes
			$_result = $this->_dbConn->asArray()->storeDocs( $_merges, $_rollback );
			$_out = static::cleanRecords( $_result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				// merge in rev updates
				$_merges = static::recordArrayMerge( $_merges, $_out );
				$_out = static::cleanRecords( $_merges, $fields );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update items in '$table' on CouchDb service.\n" . $ex->getMessage() );
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
	public function mergeRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		try
		{
			// get all fields of record
			$_merge = $this->retrieveRecord( $table, $record, '*', $extras );
			// merge in changes from $record to $_merge
			$_merge = array_merge( $_merge, $record );
			// write back the changes
			$_result = $this->_dbConn->asArray()->storeDoc( (object)$_merge );
			$_out = static::cleanRecord( $_result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				// merge in rev updates
				$_merge[static::REV_FIELD] = Option::get( $_out, static::REV_FIELD );

				return static::cleanRecord( $_merge, $fields );
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update item in '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$table = $this->correctTableName( $table );
		try
		{
			// get all fields of each record
			$_merges = $this->retrieveRecordsByFilter( $table, $filter, '*', $extras );
			// merge in changes from $records to $_merges
			unset( $record[static::DEFAULT_ID_FIELD] );
			unset( $record[static::REV_FIELD] );
			foreach ( $_merges as $_key => $_merge )
			{
				$_merges[$_key] = array_merge( $_merge, $record );
			}
			// write back the changes
			$result = $this->_dbConn->asArray()->storeDocs( $_merges, true );
			$_out = static::cleanRecords( $result, $fields );
			if ( static::_requireMoreFields( $fields ) )
			{
				// merge in rev updates
				$_merges = static::recordArrayMerge( $_merges, $_out );

				return static::cleanRecords( $_merges, $fields );
			}

			return $_out;
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
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function mergeRecordsByIds( $table, $record, $id_list, $fields = null, $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( "No record fields were passed in the request." );
		}
		$table = $this->correctTableName( $table );

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for 'id_field' can not be empty for update request." );
		}

		$_ids = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		$_updates = array();
		foreach ( $_ids as $_key => $_id )
		{
			if ( empty( $_id ) )
			{
				throw new BadRequestException( "No identifier exist in identifier index $_key." );
			}

			$_updates[] = array_merge( $record, array( static::DEFAULT_ID_FIELD => $_id ) );
		}

		return $this->mergeRecords( $table, $_updates, $fields, $extras );
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
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		if ( empty( $id ) )
		{
			throw new BadRequestException( "No identifier exist in record." );
		}

		$_update = array_merge( $record, array( static::DEFAULT_ID_FIELD => $id ) );

		return $this->mergeRecord( $table, $_update, $fields, $extras );
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
		$_rollback = Option::getBool( $extras, 'rollback', false );
		try
		{
			$_out = array();
			if ( static::_requireMoreFields( $fields ) )
			{
				$_out = $this->retrieveRecords( $table, $records, $fields, $extras );
			}

			$result = $this->_dbConn->asArray()->deleteDocs( $records, $_rollback );
			if ( empty( $_out ) )
			{
				$_out = static::cleanRecords( $result, $fields );;
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$table = $this->correctTableName( $table );
		try
		{
			$_out = array();
			if ( static::_requireMoreFields( $fields ) )
			{
				$_out = $this->retrieveRecord( $table, $record, $fields, $extras );
			}
			$result = $this->_dbConn->asArray()->deleteDoc( (object)$record );
			if ( empty( $_out ) )
			{
				$_out = static::cleanRecord( $result, $fields );;
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on CouchDb service.\n" . $ex->getMessage() );
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

		$table = $this->correctTableName( $table );
		try
		{
			$_records = $this->retrieveRecordsByFilter( $table, $filter, $fields, $extras );
			$results = $this->_dbConn->asArray()->deleteDocs( $_records, true );

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
			throw new BadRequestException( "Identifying values for 'id_field' can not be empty for update request." );
		}

		$table = $this->correctTableName( $table );
		$_rollback = Option::getBool( $extras, 'rollback', false );
		try
		{
			// get the returnable fields first, then issue delete
			$_records = $this->retrieveRecordsByIds( $table, $id_list, $fields, $extras );
			$result = $this->_dbConn->deleteDocs( $_records, $_rollback );

			return $_records;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		try
		{
			$_record = $this->retrieveRecordById( $table, $id, $fields, $extras );
			$result = $this->_dbConn->asArray()->deleteDoc( (object)$_record );

			return $_record;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to delete item from '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		$table = $this->correctTableName( $table );

		$_moreFields = static::_requireMoreFields( $fields );
		try
		{
			// todo how to filter here?
			$result = $this->_dbConn->asArray()->include_docs( $_moreFields )->getAllDocs();
			$_rows = Option::get( $result, 'rows' );
			$_out = static::cleanRecords( $_rows, $fields, $_moreFields );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to filter items from '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		$ids = array();
		foreach ( $records as $key => $record )
		{
			$_id = Option::get( $record, static::DEFAULT_ID_FIELD );
			if ( empty( $_id ) )
			{
				throw new BadRequestException( "Identifying field '_id' can not be empty for retrieve record index '$key' request." );
			}
			$ids[] = $_id;
		}

		$_moreFields = static::_requireMoreFields( $fields );
		try
		{
			$result = $this->_dbConn->asArray()->include_docs( $_moreFields )->keys( $ids )->getAllDocs();
			$_rows = Option::get( $result, 'rows' );
			$_out = static::cleanRecords( $_rows, $fields, $_moreFields );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get items from '$table' on CouchDb service.\n" . $ex->getMessage() );
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
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}

		$table = $this->correctTableName( $table );
		$_id = Option::get( $record, static::DEFAULT_ID_FIELD );
		if ( empty( $_id ) )
		{
			throw new BadRequestException( "Identifying field '_id' can not be empty for retrieve record request." );
		}

		try
		{
			$result = $this->_dbConn->asArray()->getDoc( $_id );

			return static::cleanRecord( $result, $fields );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get item '$table/$_id' on CouchDb service.\n" . $ex->getMessage() );
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
		$ids = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		$table = $this->correctTableName( $table );

		$_moreFields = static::_requireMoreFields( $fields );
		try
		{
			$result = $this->_dbConn->asArray()->include_docs( $_moreFields )->keys( $ids )->getAllDocs();
			$_rows = Option::get( $result, 'rows' );
			$_out = static::cleanRecords( $_rows, $fields, $_moreFields );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get items from '$table' on CouchDb service.\n" . $ex->getMessage() );
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
			return array();
		}
		$table = $this->correctTableName( $table );

		try
		{
			$result = $this->_dbConn->asArray()->getDoc( $id );

			return static::cleanRecord( $result, $fields );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to get item '$table/$id' on CouchDb service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param array        $record
	 * @param string|array $include List of keys to include in the output record
	 *
	 * @return array
	 */
	protected static function cleanRecord( $record = array(), $include = '*' )
	{
		if ( '*' !== $include )
		{
			$_id = Option::get( $record, static::DEFAULT_ID_FIELD );
			if ( empty( $_id ) )
			{
				$_id = Option::get( $record, 'id' );
			}
			$_rev = Option::get( $record, static::REV_FIELD );
			if ( empty( $_rev ) )
			{
				$_rev = Option::get( $record, 'rev' );
				if ( empty( $_rev ) )
				{
					$_rev = Option::getDeep( $record, 'value', 'rev' );
				}
			}
			$_out = array( static::DEFAULT_ID_FIELD => $_id, static::REV_FIELD => $_rev );

			if ( empty( $include ) )
			{
				return $_out;
			}
			if ( !is_array( $include ) )
			{
				$include = array_map( 'trim', explode( ',', trim( $include, ',' ) ) );
			}
			foreach ( $include as $key )
			{
				if ( 0 == strcasecmp( $key, static::DEFAULT_ID_FIELD ) || 0 == strcasecmp( $key, static::REV_FIELD ) )
				{
					continue;
				}
				$_out[$key] = Option::get( $record, $key );
			}

			return $_out;
		}

		return $record;
	}

	protected static function cleanRecords( $records, $include, $use_doc = false )
	{
		$_out = array();
		foreach ( $records as $_record )
		{
			if ( $use_doc )
			{
				$_record = Option::get( $_record, 'doc', $_record );
			}
			$_out[] = static::cleanRecord( $_record, $include );
		}

		return $_out;
	}
}
