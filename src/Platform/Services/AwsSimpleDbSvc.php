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
use Aws\SimpleDb\SimpleDbClient;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Utility\DataFormat;
use Platform\Utility\Utilities;
use Kisma\Core\Utility\Option;

/**
 * AwsSimpleDbSvc.php
 *
 * A service to handle Amazon Web Services SimpleDb NoSQL (schema-less) database
 * services accessed through the REST API.
 */
class AwsSimpleDbSvc extends NoSqlDbSvc
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	const DEFAULT_REGION = Region::US_WEST_1;
	/**
	 * Default record identifier field
	 */
	const DEFAULT_ID_FIELD = 'Name';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var SimpleDbClient|null
	 */
	protected $_dbConn = null;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new AwsSimpleDbSvc
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

		// reply in simplified blend format by default
		if ( null !== ( $_blendFormat = Option::get( $_parameters, 'blend_format' ) ) )
		{
			$this->_defaultBlendFormat = DataFormat::boolval( $_blendFormat );
		}

		try
		{
			$this->_dbConn = SimpleDbClient::factory( $_credentials );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Amazon SimpleDb Service Exception:\n{$ex->getMessage()}" );
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
		$_token = null;
		do
		{
			$_result = $this->_dbConn->listDomains(
				array(
					 'MxNumberOfDomains' => 100, // arbitrary limit
					 'NextToken'         => $_token
				)
			);
			$_domains = $_result['DomainNames'];
			$_token = $_result['NextToken'];

			if ( !empty( $_domains ) )
			{
				$_out = array_merge( $_out, $_domains );
			}
		}
		while ( $_token );

		return $_out;
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
			$_out[] = array( 'name' => $_table, 'DomainName' => $_table );
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
				throw new InternalServerErrorException( "Failed to list tables of SimpleDb Tables service.\n" . $ex->getMessage() );
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
			$_result = $this->_dbConn->domainMetadata(
				array(
					 'DomainName' => $table
				)
			);

			// The result of an operation can be used like an array
			$_out = array( 'name' => $table, 'DomainName' => $table );

			return array_merge( $_out, $_result->toArray() );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to list tables of SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_name = Option::get( $properties, 'name', Option::get( $properties, 'DomainName' ) );
		if ( empty( $_name ) )
		{
			throw new BadRequestException( "No 'name' field in data." );
		}

		try
		{
			$_properties = array_merge(
				array( 'DomainName' => $_name ),
				$properties
			);
			$_result = $this->_dbConn->createDomain( array( 'DomainName' => $_name ) );
			$_out = array( 'name' => $_name, 'DomainName' => $_name );

			return array_merge( $_out, $_result->toArray() );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create table on SimpleDb Tables service.\n" . $ex->getMessage() );
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

		throw new BadRequestException( "Update table operation is not supported on SimpleDb." );
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
				$_table = Option::get( $_table, 'name', Option::get( $_table, 'DomainName' ) );
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
			$_result = $this->_dbConn->deleteDomain(
				array(
					 'DomainName' => $table
				)
			);
			$_out = array( 'name' => $table, 'DomainName' => $table );

			return array_merge( $_out, $_result->toArray() );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete table '$table' from SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		$_items = array();
		foreach ( $records as $_record )
		{
			$_id = Option::get( $_record, $_idField, null, true );
			if ( empty( $_id ) )
			{
				$_id = static::createItemId( $table );
//				throw new BadRequestException( "Identifying field(s) not found in record." );
			}

			// Add operation to list of batch operations.
			$_items[] = array(
				'Name'       => $_id,
				'Attributes' => $this->_formatAttributes( $_record )
			);
		}

		try
		{
			$_result = $this->_dbConn->batchPutAttributes(
				array(
					 'DomainName' => $table,
					 'Items'      => $_items,
				)
			);

			return static::cleanRecords( $records, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create items in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}
		$_id = Option::get( $record, $_idField, null, true );
		if ( empty( $_id ) )
		{
			$_id = static::createItemId( $table );
//			throw new BadRequestException( "Identifying field(s) not found in record." );
		}

		try
		{
			// simple insert request
			$_result = $this->_dbConn->putAttributes(
				array(
					 'DomainName' => $table,
					 'ItemName'   => $_id,
					 'Attributes' => $this->_formatAttributes( $record )
				)
			);
			$_out = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $_id ) );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create item in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param array  $records
	 * @param mixed  $fields
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		$_items = array();
		foreach ( $records as $_record )
		{
			$_id = Option::get( $_record, $_idField, null, true );
			if ( empty( $_id ) )
			{
				throw new BadRequestException( "Identifying field(s) not found in record." );
			}

			// Add operation to list of batch operations.
			$_items[] = array(
				'Name'       => $_id,
				'Attributes' => $this->_formatAttributes( $_record, true )
			);
		}

		try
		{
			$_result = $this->_dbConn->batchPutAttributes(
				array(
					 'DomainName' => $table,
					 "Items"      => $_items,
				)
			);

			return static::cleanRecords( $records, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update items in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}
		$_id = Option::get( $record, $_idField, null, true );
		if ( empty( $_id ) )
		{
			throw new BadRequestException( "Identifying field(s) not found in record." );
		}

		try
		{
			// simple insert request
			$_result = $this->_dbConn->putAttributes(
				array(
					 'DomainName' => $table,
					 'ItemName'   => $_id,
					 'Attributes' => $this->_formatAttributes( $record, true ),
				)
			);
			$_out = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $_id ) );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update item in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		unset( $record[$_idField] ); // clear out any identifiers
		$_out = array();
		$_items = array();
		foreach ( $id_list as $_id )
		{
			// Add operation to list of batch operations.
			$_items[] = array(
				'Name'       => $_id,
				'Attributes' => $this->_formatAttributes( $record, true )
			);
			$_out[] = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $_id ) );
		}

		try
		{
			$_result = $this->_dbConn->batchPutAttributes(
				array(
					 'DomainName' => $table,
					 'Items'      => $_items,
				)
			);

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update items from '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		unset( $record[$_idField] );
		try
		{
			// simple insert request
			$_result = $this->_dbConn->putAttributes(
				array(
					 'DomainName' => $table,
					 'ItemName'   => $id,
					 'Attributes' => $this->_formatAttributes( $record, true )
				)
			);
			$_out = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $id ) );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update item in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
		}
	}

	/**
	 * @param string $table
	 * @param array  $records
	 * @param mixed  $fields
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		$_items = array();
		foreach ( $records as $_record )
		{
			$_id = Option::get( $_record, $_idField, null, true );
			if ( empty( $_id ) )
			{
				throw new BadRequestException( "Identifying field(s) not found in record." );
			}

			// Add operation to list of batch operations.
			$_items[] = array(
				'Name'       => $_id,
				'Attributes' => $this->_formatAttributes( $_record, true )
			);
		}

		try
		{
			$_result = $this->_dbConn->batchPutAttributes(
				array(
					 'DomainName' => $table,
					 "Items"      => $_items,
				)
			);

			return static::cleanRecords( $records, $fields, $_idField );
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to merge items in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
	public function mergeRecord( $table, $record, $fields = null, $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new BadRequestException( 'There are no record fields in the request.' );
		}

		$table = $this->correctTableName( $table );
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}
		$_id = Option::get( $record, $_idField, null, true );
		if ( empty( $_id ) )
		{
			throw new BadRequestException( "Identifying field(s) not found in record." );
		}

		try
		{
			// simple insert request
			$_result = $this->_dbConn->putAttributes(
				array(
					 'DomainName' => $table,
					 'ItemName'   => $_id,
					 'Attributes' => $this->_formatAttributes( $record, true ),
				)
			);
			$_out = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $_id ) );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to merge item in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
	public function mergeRecordsByIds( $table, $record, $id_list, $fields = null, $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new BadRequestException( "No record fields were passed in the request." );
		}

		if ( empty( $id_list ) )
		{
			throw new BadRequestException( "Identifying values for id_field can not be empty for merge request." );
		}

		if ( !is_array( $id_list ) )
		{
			$id_list = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
		}
		$table = $this->correctTableName( $table );
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		unset( $record[$_idField] ); // clear out any identifiers
		$_out = array();
		$_items = array();
		foreach ( $id_list as $_id )
		{
			// Add operation to list of batch operations.
			$_items[] = array(
				'Name'       => $_id,
				'Attributes' => $this->_formatAttributes( $record, true )
			);
			$_out[] = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $_id ) );
		}

		try
		{
			$_result = $this->_dbConn->batchPutAttributes(
				array(
					 'DomainName' => $table,
					 'Items'      => $_items,
				)
			);

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to merge items from '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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

		$table = $this->correctTableName( $table );
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		unset( $record[$_idField] );
		try
		{
			// simple insert request
			$_result = $this->_dbConn->putAttributes(
				array(
					 'DomainName' => $table,
					 'ItemName'   => $id,
					 'Attributes' => $this->_formatAttributes( $record, true )
				)
			);
			$_out = array_merge( static::cleanRecord( $record, $fields ), array( $_idField => $id ) );

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to merge item in '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
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
			$_id = Option::get( $_record, $_idField, null, true );
			if ( empty( $_id ) )
			{
				throw new BadRequestException( "Identifying field(s) not found in record." );
			}

			$_items[] = array(
				'Name' => $_id,
			);
			$_outIds[] = array( $_idField => $_id );
		}

		try
		{
			$_result = $this->_dbConn->batchDeleteAttributes(
				array(
					 'DomainName' => $table,
					 'Items'      => $_items
				)
			);

			return ( empty( $_out ) ) ? $_outIds : $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		$_id = Option::get( $record, $_idField, null, true );
		if ( empty( $_id ) )
		{
			throw new BadRequestException( "Identifying field(s) not found in record." );
		}

		$_out = array();
		if ( static::_requireMoreFields( $fields, $_idField ) )
		{
			$_out = $this->retrieveRecordById( $table, $_id, $fields, $extras );
		}
		$_scanProperties = array(
			'DomainName' => $table,
			'ItemName'   => $_id,
		);
		try
		{
			$_result = $this->_dbConn->deleteAttributes( $_scanProperties );

			// Grab value from the result object like an array
			return ( empty( $_out ) ) ? array( $_idField => $_id ) : $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete item '$table/$_id' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
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
			// Add operation to list of batch operations.
			$_items[] = array(
				'Name' => $_id,
			);
			$_outIds[] = array( $_idField => $_id );
		}

		try
		{
			$_result = $this->_dbConn->batchDeleteAttributes(
				array(
					 'DomainName' => $table,
					 'Items'      => $_items
				)
			);

			return ( empty( $_out ) ) ? $_outIds : $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete items from '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		$_out = array();
		if ( static::_requireMoreFields( $fields, $_idField ) )
		{
			$_out = $this->retrieveRecordById( $table, $id, $fields, $extras );
		}
		$_scanProperties = array(
			'DomainName' => $table,
			'ItemName'   => $id,
		);
		try
		{
			$_result = $this->_dbConn->deleteAttributes( $_scanProperties );

			// Grab value from the result object like an array
			return ( empty( $_out ) ) ? array( $_idField => $id ) : $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete item '$table/$id' on SimpleDb Tables service.\n" . $ex->getMessage() );
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

		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}

		$_select = 'select ';
		if ( empty( $fields ) )
		{
			$fields = $_idField;
		}
		$_select .= $fields . ' from ' . $table;

		if ( !empty( $filter ) )
		{
			$filter = static::_parseFilter( $filter );
			$_select .= ' where ' . $filter;
		}
		$_limit = Option::get( $extras, 'order' );
		if ( $_limit > 0 )
		{
			$_select .= ' order by ' . $_limit;
		}
		$_limit = Option::get( $extras, 'limit' );
		if ( $_limit > 0 )
		{
			$_select .= ' limit ' . $_limit;
		}

		try
		{
			$_result = $this->_dbConn->select( array( 'SelectExpression' => $_select ) );
			$_items = $_result['Items'];

			$_out = array();
			if ( !empty( $_items ) )
			{
				foreach ( $_items as $_item )
				{
					$_attributes = Option::get( $_item, 'Attributes' );
					$_name = Option::get( $_item, $_idField );
					$_out[] = array_merge(
						static::_unformatAttributes( $_attributes ),
						array( $_idField => $_name )
					);
				}
			}

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to filter items from '$table' on SimpleDb Tables service.\n" . $ex->getMessage() );
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

		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}
		$_ids = static::recordsAsIds( $records, $_idField );
		$_filter = "itemName() in ('" . implode( "','", $_ids ) . "')";

		return $this->retrieveRecordsByFilter( $table, $_filter, $fields, $extras );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}
		$_id = Option::get( $record, $_idField );
		$_scanProperties = array(
			'DomainName'     => $table,
			'ItemName'       => $_id,
			'ConsistentRead' => true,
		);
		$fields = static::_buildAttributesToGet( $fields, $_idField );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributeNames'] = $fields;
		}

		try
		{
			$_result = $this->_dbConn->getAttributes( $_scanProperties );
			$_out = array_merge(
				static::_unformatAttributes( $_result['Attributes'] ),
				array( $_idField => $_id )
			);

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get item '$table/$_id' on SimpleDb Tables service.\n" . $ex->getMessage() );
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
		$_filter = "itemName() in ('" . implode( "','", $id_list ) . "')";

		return $this->retrieveRecordsByFilter( $table, $_filter, $fields, $extras );
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
		$_idField = Option::get( $extras, 'id_field' );
		if ( empty( $_idField ) )
		{
			$_idField = static::DEFAULT_ID_FIELD;
		}
		$_scanProperties = array(
			'DomainName'     => $table,
			'ItemName'       => $id,
			'ConsistentRead' => true,
		);
		$fields = static::_buildAttributesToGet( $fields, $_idField );
		if ( !empty( $fields ) )
		{
			$_scanProperties['AttributeNames'] = $fields;
		}

		try
		{
			$_result = $this->_dbConn->getAttributes( $_scanProperties );
			$_out = array_merge(
				static::_unformatAttributes( $_result['Attributes'] ),
				array( $_idField => $id )
			);

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to get item '$table/$id' on SimpleDb Tables service.\n" . $ex->getMessage() );
		}
	}

	protected static function _formatValue( $value )
	{
		if (is_string($value)) return $value;
		if (is_array($value)) return '#DFJ#' . json_encode( $value );
		if (is_bool($value)) return '#DFB#' . strval( $value );
		if (is_float($value)) return '#DFF#' . strval( $value );
		if (is_int($value)) return '#DFI#' . strval( $value );

		return $value;
	}

	protected static function _unformatValue( $value )
	{
		if (0 == substr_compare( $value, '#DFJ#', 0, 5 )) return json_decode( substr( $value, 5 ) );
		if (0 == substr_compare( $value, '#DFB#', 0, 5 )) return (bool) substr( $value, 5);
		if (0 == substr_compare( $value, '#DFF#', 0, 5 )) return floatval( substr( $value, 5) );
		if (0 == substr_compare( $value, '#DFI#', 0, 5 )) return intval( substr( $value, 5) );

		return $value;
	}

	/**
	 * @param array $record
	 * @param bool  $replace
	 *
	 * @return array
	 */
	protected static function _formatAttributes( $record, $replace = false )
	{
		$_out = array();
		if ( !empty( $record ) )
		{
			foreach ( $record as $_name => $_value )
			{
				if ( Utilities::isArrayNumeric( $_value ) )
				{
					foreach ( $_value as $_key => $_part )
					{
						$_part = static::_formatValue( $_part );
						if ( 0 == $_key )
						{
							$_out[] = array( 'Name' => $_name, 'Value' => $_part, 'Replace' => $replace );
						}
						else
						{
							$_out[] = array( 'Name' => $_name, 'Value' => $_part );
						}
					}

				}
				else
				{
					$_value = static::_formatValue( $_value );
					$_out[] = array( 'Name' => $_name, 'Value' => $_value, 'Replace' => $replace );
				}
			}
		}

		return $_out;
	}

	/**
	 * @param array $record
	 *
	 * @return array
	 */
	protected static function _unformatAttributes( $record )
	{
		$_out = array();
		if ( !empty( $record ) )
		{
			foreach ( $record as $_attribute )
			{
				$_name = Option::get( $_attribute, 'Name' );
				if ( empty( $_name ) )
				{
					continue;
				}

				$_value = Option::get( $_attribute, 'Value' );
				if ( isset( $_out[$_name] ) )
				{
					$_temp = $_out[$_name];
					if ( is_array( $_temp ) )
					{
						$_temp[] = static::_unformatValue( $_value );
						$_value = $_temp;
					}
					else
					{
						$_value = array( $_temp, static::_unformatValue( $_value ) );
					}
				}
				else
				{
					$_value = static::_unformatValue( $_value );
				}
				$_out[$_name] = $_value;
			}
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

	/**
	 * @param string|array $filter Filter for querying records by
	 *
	 * @throws \DreamFactory\Platform\Exceptions\BadRequestException
	 * @return array
	 */
	protected static function _parseFilter( $filter )
	{
		if ( empty( $filter ) )
		{
			return $filter;
		}

		if ( is_array( $filter ) )
		{
			throw new BadRequestException( 'Filtering in array format is not currently supported on SimpleDb.' );
		}

		// handle logical operators first
		$_search = array( ' || ', ' && ' );
		$_replace = array( ' or ', ' and ' );
		$filter = trim( str_ireplace( $_search, $_replace, $filter ) );

		// the rest should be comparison operators
		$_search = array( ' eq ', ' ne ', ' gte ', ' lte ', ' gt ', ' lt ' );
		$_replace = array( ' = ', ' != ', ' >= ', ' <= ', ' > ', ' < ' );
		$filter = trim( str_ireplace( $_search, $_replace, $filter ) );

		// check for x = null
		$filter = str_ireplace( ' = null', ' is null', $filter );
		// check for x != null
		$filter = str_ireplace( ' != null', ' is not null', $filter );

		return $filter;
	}
}
