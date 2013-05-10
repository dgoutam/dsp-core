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
 * SqlDbSvc.php
 * A service to handle SQL database services accessed through the REST API.
 */
class SqlDbSvc extends BaseDbSvc
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var CDbConnection
	 */
	protected $_sqlConn;
	/**
	 * @var boolean
	 */
	protected $_isNative = false;
	/**
	 * @var array
	 */
	protected $_fieldCache;
	/**
	 * @var array
	 */
	protected $_relatedCache;
	/**
	 * @var integer
	 */
	protected $_driverType = DbUtilities::DRV_OTHER;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @return int
	 */
	public function getDriverType()
	{
		return $this->_driverType;
	}

	/**
	 * Create a new SqlDbSvc
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
			$storageType = Utilities::getArrayValue( 'type', $config, '' );
			$attributes = Utilities::getArrayValue( 'parameters', $config, array() );
			$credentials = Utilities::getArrayValue( 'credentials', $config, array() );
			$dsn = Utilities::getArrayValue( 'dsn', $credentials, '' );
			// Validate other parameters
			if ( empty( $dsn ) )
			{
				throw new InvalidArgumentException( 'DB connection string (DSN) can not be empty.' );
			}
			$user = Utilities::getArrayValue( 'user', $credentials, '' );
			if ( empty( $user ) )
			{
				throw new InvalidArgumentException( 'DB admin name can not be empty.' );
			}
			$pwd = Utilities::getArrayValue( 'pwd', $credentials, '' );
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
//                $this->_sqlConn->setAttribute(constant('PDO::SQLSRV_ATTR_DIRECT_QUERY'), true);
//                $this->_sqlConn->setAttribute("MultipleActiveResultSets", false);
//                $this->_sqlConn->setAttribute("ReturnDatesAsStrings", true);
					$this->_sqlConn->setAttribute( "CharacterSet", "UTF-8" );
					break;
			}
			Utilities::markTimeStop( 'DB_TIME' );
		}

		if ( !empty( $attributes ) && is_array( $attributes ) )
		{
			foreach ( $attributes as $key => $value )
			{
				$this->_sqlConn->setAttribute( $key, $value );
			}
		}
		$this->_fieldCache = array();
		$this->_relatedCache = array();
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		if ( !$this->_isNative && isset( $this->_sqlConn ) )
		{
			try
			{
				$this->_sqlConn->active = false;
				$this->_sqlConn = null;
			}
			catch ( PDOException $ex )
			{
				error_log( "Failed to disconnect from database.\n{$ex->getMessage()}" );
			}
			catch ( Exception $ex )
			{
				error_log( "Failed to disconnect from database.\n{$ex->getMessage()}" );
			}
		}
	}

	/**
	 * @throws Exception
	 */
	protected function checkConnection()
	{
		if ( !isset( $this->_sqlConn ) )
		{
			throw new Exception( 'Database driver has not been initialized.' );
		}
		try
		{
			Utilities::markTimeStart( 'DB_TIME' );

			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}

			Utilities::markTimeStop( 'DB_TIME' );
		}
		catch ( PDOException $ex )
		{
			throw new Exception( "Failed to connect to database.\n{$ex->getMessage()}" );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to connect to database.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param $name
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function correctTableName( $name )
	{
		return DbUtilities::correctTableName( $this->_sqlConn, $name );
	}

	/**
	 * @param string $table
	 * @param string $access
	 *
	 * @throws Exception
	 */
	protected function validateTableAccess( $table, $access = 'read' )
	{
		parent::validateTableAccess( $table, $access );

		if ( $this->_isNative )
		{
			// check for system tables and deny
			$sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
			if ( 0 === substr_compare( $table, $sysPrefix, 0, strlen( $sysPrefix ) ) )
			{
				throw new Exception( "Table '$table' not found.", ErrorCodes::NOT_FOUND );
			}
		}
	}

	/**
	 * @return array
	 */
	protected function gatherExtrasFromRequest()
	{
		$extras = array();
		$related = Utilities::getArrayValue( 'related', $_REQUEST, '' );
		if ( !empty( $related ) )
		{
			$related = array_map( 'trim', explode( ',', $related ) );
			foreach ( $related as $relative )
			{
				$extraFields = Utilities::getArrayValue( $relative . '_fields', $_REQUEST, '*' );
				$extraOrder = Utilities::getArrayValue( $relative . '_order', $_REQUEST, '' );
				$extras[] = array( 'name' => $relative, 'fields' => $extraFields, 'order' => $extraOrder );
			}
		}

		return $extras;
	}

	// REST service implementation

	/**
	 * @throws Exception
	 * @return array
	 */
	protected function _listResources()
	{
		$exclude = '';
		if ( $this->_isNative )
		{
			// check for system tables
			$exclude = SystemManager::SYSTEM_TABLE_PREFIX;
		}
		try
		{
			$result = DbUtilities::describeDatabase( $this->_sqlConn, '', $exclude );
			return array( 'resource' => $result );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error describing database tables.\n{$ex->getMessage()}" );
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
	 * @throws Exception
	 * @return array
	 */
	public function createRecords( $table, $records, $rollback = false, $fields = '', $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( !isset( $records[0] ) )
		{
			// single record possibly passed in without wrapper array
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		try
		{
			$fieldInfo = $this->describeTableFields( $table );
			$relatedInfo = $this->describeTableRelated( $table );
			$idField = DbUtilities::getPrimaryKeyFieldFromDescribe( $fieldInfo );
			$command = $this->_sqlConn->createCommand();
			$ids = array();
			$errors = array();
			if ( $rollback )
			{
//                $this->_sqlConn->beginTransaction();
			}
			$count = count( $records );
			foreach ( $records as $key => $record )
			{
				try
				{
					$parsed = $this->parseRecord( $record, $fieldInfo );
					if ( 0 >= count( $parsed ) )
					{
						throw new Exception( "No valid fields were passed in the record [$key] request.", ErrorCodes::BAD_REQUEST );
					}
					// simple insert request
					$command->reset();
					$rows = $command->insert( $table, $parsed );
					if ( 0 >= $rows )
					{
						throw new Exception( "Record insert failed for table '$table'." );
					}
					$id = $this->_sqlConn->lastInsertID;
					$this->updateRelations( $table, $record, $id, $relatedInfo );
					$ids[$key] = $id;
				}
				catch ( Exception $ex )
				{
					if ( $rollback )
					{
//                        $this->_sqlConn->rollBack();
						throw $ex;
					}
					$errors[$key] = $ex->getMessage();
				}
			}
			if ( $rollback )
			{
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
			}

			$results = array();
			if ( empty( $fields ) || ( 0 === strcasecmp( $idField, $fields ) ) )
			{
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $ids[$i] )
						?
						array( $idField => $ids[$i] )
						:
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}
			else
			{
				if ( '*' !== $fields )
				{
					$fields = Utilities::addOnceToList( $fields, $idField );
				}
				$temp = $this->retrieveRecordsByIds( $table, implode( ',', $ids ), $idField, $fields, $extras );
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $ids[$i] )
						?
						$temp[$i]
						: // todo bad assumption
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}

			return $results;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function createRecord( $table, $record, $fields = '', $extras = array() )
	{
		if ( empty( $record ) || !is_array( $record ) )
		{
			throw new Exception( 'There are no record fields in the request.', ErrorCodes::BAD_REQUEST );
		}

		$table = $this->correctTableName( $table );
		try
		{
			$fieldInfo = $this->describeTableFields( $table );
			$relatedInfo = $this->describeTableRelated( $table );
			$idField = DbUtilities::getPrimaryKeyFieldFromDescribe( $fieldInfo );
			$parsed = $this->parseRecord( $record, $fieldInfo );
			if ( 0 >= count( $parsed ) )
			{
				throw new Exception( "No valid fields were passed in the record request.", ErrorCodes::BAD_REQUEST );
			}

			// simple insert request
			$command = $this->_sqlConn->createCommand();
			$rows = $command->insert( $table, $parsed );
			if ( 0 >= $rows )
			{
				throw new Exception( "Record insert failed for table '$table'." );
			}
			$id = $this->_sqlConn->lastInsertID;
			$this->updateRelations( $table, $record, $id, $relatedInfo );
			if ( empty( $fields ) || ( 0 === strcasecmp( $idField, $fields ) ) )
			{
				return array( array( $idField => $id ) );
			}
			else
			{
				if ( '*' !== $fields )
				{
					$fields = Utilities::addOnceToList( $fields, $idField );
				}

				return $this->retrieveRecordById( $table, $id, $idField, $fields, $extras );
			}
		}
		catch ( Exception $ex )
		{
			throw $ex;
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
	 * @throws Exception
	 * @return array
	 */
	public function updateRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record possibly passed in without wrapper array
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		try
		{
			$fieldInfo = $this->describeTableFields( $table );
			$relatedInfo = $this->describeTableRelated( $table );
			if ( empty( $id_field ) )
			{
				$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $fieldInfo );
				if ( empty( $id_field ) )
				{
					throw new Exception( "Identifying field can not be empty." );
				}
			}
			$command = $this->_sqlConn->createCommand();
			$ids = array();
			$errors = array();
			if ( $rollback )
			{
//                $this->_sqlConn->beginTransaction();
			}
			$count = count( $records );
			foreach ( $records as $key => $record )
			{
				try
				{
					$id = Utilities::getArrayValue( $id_field, $record, '' );
					if ( empty( $id ) )
					{
						throw new Exception( "Identifying field '$id_field' can not be empty for update record [$key] request." );
					}
					$record = Utilities::removeOneFromArray( $id_field, $record );
					$parsed = $this->parseRecord( $record, $fieldInfo, true );
					if ( 0 >= count( $parsed ) )
					{
						throw new Exception( "No valid fields were passed in the record [$key] request." );
					}
					// simple update request
					$command->reset();
					$rows = $command->update( $table, $parsed, array( 'in', $id_field, $id ) );
					$ids[$key] = $id;
					$this->updateRelations( $table, $record, $id, $relatedInfo );
				}
				catch ( Exception $ex )
				{
					if ( $rollback )
					{
//                        $this->_sqlConn->rollBack();
						throw $ex;
					}
					$errors[$key] = $ex->getMessage();
				}
			}
			if ( $rollback )
			{
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
			}

			$results = array();
			// todo figure out primary key
			if ( empty( $fields ) || ( 0 === strcasecmp( $id_field, $fields ) ) )
			{
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $ids[$i] )
						?
						array( $id_field => $ids[$i] )
						:
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}
			else
			{
				if ( '*' !== $fields )
				{
					$fields = Utilities::addOnceToList( $fields, $id_field );
				}
				$temp = $this->retrieveRecordsByIds( $table, implode( ',', $ids ), $id_field, $fields, $extras );
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $ids[$i] )
						?
						$temp[$i]
						: // todo bad assumption
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}

			return $results;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $idField
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateRecord( $table, $record, $idField = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record.', ErrorCodes::BAD_REQUEST );
		}

		$records = array( $record );
		$results = $this->updateRecords( $table, $records, $idField, false, $fields, $extras );

		return $results[0];
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateRecordsByFilter( $table, $record, $filter = '', $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record.', ErrorCodes::BAD_REQUEST );
		}
		$table = $this->correctTableName( $table );
		try
		{
			$fieldInfo = $this->describeTableFields( $table );
			$relatedInfo = $this->describeTableRelated( $table );
			// simple update request
			$parsed = $this->parseRecord( $record, $fieldInfo, true );
			if ( empty( $parsed ) )
			{
				throw new Exception( "No valid field values were passed in the request." );
			}
			// parse filter
			$command = $this->_sqlConn->createCommand();
			$rows = $command->update( $table, $parsed, $filter );
			// todo how to update relations here?

			$results = array();
			if ( !empty( $fields ) )
			{
				$results = $this->retrieveRecordsByFilter( $table, $fields, $filter, 0, '', 0, false, false, $extras );
			}

			return $results;
		}
		catch ( Exception $ex )
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
	 * @throws Exception
	 * @return array
	 */
	public function updateRecordsByIds( $table, $record, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		if ( !is_array( $record ) || empty( $record ) )
		{
			throw new Exception( "No record fields were passed in the request." );
		}
		$table = $this->correctTableName( $table );
		try
		{
			$fieldInfo = $this->describeTableFields( $table );
			$relatedInfo = $this->describeTableRelated( $table );
			if ( empty( $id_field ) )
			{
				$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $fieldInfo );
				if ( empty( $id_field ) )
				{
					throw new Exception( "Identifying field can not be empty." );
				}
			}
			if ( empty( $id_list ) )
			{
				throw new Exception( "Identifying values for '$id_field' can not be empty for update request." );
			}
			$record = Utilities::removeOneFromArray( $id_field, $record );
			// simple update request
			$parsed = $this->parseRecord( $record, $fieldInfo, true );
			if ( empty( $parsed ) )
			{
				throw new Exception( "No valid field values were passed in the request." );
			}
			$ids = array_map( 'trim', explode( ',', trim( $id_list, ',' ) ) );
			$outIds = array();
			$errors = array();
			$count = count( $ids );
			$command = $this->_sqlConn->createCommand();

			if ( $rollback )
			{
//                $this->_sqlConn->beginTransaction();
			}
			foreach ( $ids as $key => $id )
			{
				try
				{
					if ( empty( $id ) )
					{
						throw new Exception( "Identifying field '$id_field' can not be empty for update record request." );
					}
					// simple update request
					$command->reset();
					$rows = $command->update( $table, $parsed, array( 'in', $id_field, $id ) );
					$this->updateRelations( $table, $record, $id, $relatedInfo );
					$outIds[$key] = $id;
				}
				catch ( Exception $ex )
				{
					error_log( $ex->getMessage() );
					if ( $rollback )
					{
//                        $this->_sqlConn->rollBack();
						throw $ex;
					}
					$errors[$key] = $ex->getMessage();
				}
			}
			if ( $rollback )
			{
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
			}
			$results = array();
			// todo figure out primary key
			if ( empty( $fields ) || ( 0 === strcasecmp( $id_field, $fields ) ) )
			{
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $outIds[$i] )
						?
						array( $id_field => $outIds[$i] )
						:
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}
			else
			{
				if ( '*' !== $fields )
				{
					$fields = Utilities::addOnceToList( $fields, $id_field );
				}
				$temp = $this->retrieveRecordsByIds( $table, implode( ',', $ids ), $id_field, $fields, $extras );
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $outIds[$i] )
						?
						$temp[$i]
						: // todo bad assumption
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}

			return $results;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id
	 * @param string $idField
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateRecordById( $table, $record, $id, $idField = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record.', ErrorCodes::BAD_REQUEST );
		}
		$results = $this->updateRecordsByIds( $table, $record, $id, $idField, false, $fields, $extras );

		return $results[0];
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param        $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array|string
	 */
	public function deleteRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		if ( !is_array( $records ) || empty( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( !isset( $records[0] ) )
		{
			// single record
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$ids = array();
		$field_info = $this->describeTableFields( $table );
		if ( empty( $id_field ) )
		{
			$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $field_info );
			if ( empty( $id_field ) )
			{
				throw new Exception( "Identifying field can not be empty." );
			}
		}
		foreach ( $records as $key => $record )
		{
			$id = Utilities::getArrayValue( $id_field, $record, '' );
			if ( empty( $id ) )
			{
				throw new Exception( "Identifying field '$id_field' can not be empty for retrieve record [$key] request." );
			}
			$ids[] = $id;
		}
		$idList = implode( ',', $ids );

		return $this->deleteRecordsByIds( $table, $idList, $id_field, $rollback, $fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $idField
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function deleteRecord( $table, $record, $idField = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record.', ErrorCodes::BAD_REQUEST );
		}
		$records = array( $record );
		$results = $this->deleteRecords( $table, $records, $idField, false, $fields, $extras );

		return $results[0];
	}

	/**
	 * @param        $table
	 * @param        $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function deleteRecordsByFilter( $table, $filter, $fields = '', $extras = array() )
	{
		if ( empty( $filter ) )
		{
			throw new Exception( "Filter for delete request can not be empty." );
		}
		$table = $this->correctTableName( $table );
		try
		{
			$command = $this->_sqlConn->createCommand();
			$results = array();
			// get the returnable fields first, then issue delete
			if ( !empty( $fields ) )
			{
				$results = $this->retrieveRecordsByFilter( $table, $fields, $filter, 0, '', 0, false, false, $extras );
			}

			// parse filter
			$command->reset();
			$rows = $command->delete( $table, $filter );

			return $results;
		}
		catch ( Exception $ex )
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
	 * @throws Exception
	 * @return array
	 */
	public function deleteRecordsByIds( $table, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() )
	{
		$table = $this->correctTableName( $table );
		try
		{
			$field_info = $this->describeTableFields( $table );
			if ( empty( $id_field ) )
			{
				$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $field_info );
				if ( empty( $id_field ) )
				{
					throw new Exception( "Identifying field can not be empty." );
				}
			}
			if ( empty( $id_list ) )
			{
				throw new Exception( "Identifying values for '$id_field' can not be empty for update request." );
			}

			$ids = array_map( 'trim', explode( ',', $id_list ) );
			$errors = array();
			$count = count( $ids );
			$command = $this->_sqlConn->createCommand();

			// get the returnable fields first, then issue delete
			$outResults = array();
			if ( !( empty( $fields ) || ( 0 === strcasecmp( $id_field, $fields ) ) ) )
			{
				if ( '*' !== $fields )
				{
					$fields = Utilities::addOnceToList( $fields, $id_field );
				}
				$outResults = $this->retrieveRecordsByIds( $table, implode( ',', $ids ), $id_field, $fields, $extras );
			}

			if ( $rollback )
			{
//                $this->_sqlConn->beginTransaction();
			}
			foreach ( $ids as $key => $id )
			{
				try
				{
					if ( empty( $id ) )
					{
						throw new Exception( "Identifying field '$id_field' can not be empty for delete record request." );
					}
					// simple delete request
					$command->reset();
					$rows = $command->delete( $table, array( 'in', $id_field, $id ) );
					if ( 0 >= $rows )
					{
						throw new Exception( "Record with $id_field '$id' not found in table '$table'.", ErrorCodes::NOT_FOUND );
					}
					$ids[$key] = $id;
				}
				catch ( Exception $ex )
				{
					if ( $rollback )
					{
//                        $this->_sqlConn->rollBack();
						throw $ex;
					}
					$errors[$key] = $ex->getMessage();
				}
			}
			if ( $rollback )
			{
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
			}
			$results = array();
			if ( empty( $fields ) || ( 0 === strcasecmp( $id_field, $fields ) ) )
			{
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $ids[$i] )
						?
						array( $id_field => $ids[$i] )
						:
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}
			else
			{
				for ( $i = 0; $i < $count; $i++ )
				{
					$results[$i] = ( isset( $ids[$i] )
						?
						$outResults[$i]
						: // todo bad assumption
						( isset( $errors[$i] ) ? $errors[$i] : null ) );
				}
			}

			return $results;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $idField
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function deleteRecordById( $table, $id, $idField = '', $fields = '', $extras = array() )
	{
		$results = $this->deleteRecordsByIds( $table, $id, $idField, false, $fields, $extras );

		return $results[0];
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
	 * @throws Exception
	 * @return array
	 */
	public function retrieveRecordsByFilter( $table, $fields = '', $filter = '',
											 $limit = 0, $order = '', $offset = 0,
											 $include_count = false, $include_schema = false,
											 $extras = array() )
	{
		$table = $this->correctTableName( $table );
		try
		{
			// parse filter
			$availFields = $this->describeTableFields( $table );
			$relations = $this->describeTableRelated( $table );
			$result = $this->parseFieldsForSqlSelect( $fields, $availFields );
			$bindings = $result['bindings'];
			$fields = $result['fields'];
			if ( empty( $fields ) )
			{
				$fields = '*';
			}
			$limit = intval( $limit );
			$offset = intval( $offset );

			// use query builder
			$command = $this->_sqlConn->createCommand();
			$command->select( $fields );
			$command->from( $table );
			if ( !empty( $filter ) )
			{
				$command->where( $filter );
			}
			if ( !empty( $order ) )
			{
				$command->order( $order );
			}
			if ( $offset > 0 )
			{
				$command->offset( $offset );
			}
			if ( $limit > 0 )
			{
				$command->limit( $limit );
			}
			else
			{
				// todo impose a limit to protect server
			}

			$this->checkConnection();
			Utilities::markTimeStart( 'DB_TIME' );
			$reader = $command->query();
			$data = array();
			$dummy = array();
			foreach ( $bindings as $binding )
			{
				$reader->bindColumn( $binding['name'], $dummy[$binding['name']], $binding['type'] );
			}
			$reader->setFetchMode( PDO::FETCH_BOUND );
			$count = 0;
			while ( false !== $reader->read() )
			{
				$temp = array();
				foreach ( $bindings as $binding )
				{
					$temp[$binding['name']] = $dummy[$binding['name']];
				}
				if ( !empty( $extras ) )
				{
					$temp = $this->retrieveRelatedRecords( $relations, $temp, $extras );
				}
				$data[$count++] = $temp;
			}

			if ( $include_count || $include_schema )
			{
				// count total records
				if ( $include_count )
				{
					$command->reset();
					$command->select( '(COUNT(*)) as ' . $this->_sqlConn->quoteColumnName( 'count' ) );
					$command->from( $table );
					if ( !empty( $filter ) )
					{
						$command->where( $filter );
					}
					$data['meta']['count'] = intval( $command->queryScalar() );
				}
				// count total records
				if ( $include_schema )
				{
					$data['meta']['schema'] = DbUtilities::describeTable( $this->_sqlConn, $table );
				}
			}
			Utilities::markTimeStop( 'DB_TIME' );

//            error_log('retrievefilter: ' . PHP_EOL . print_r($data, true));

			return $data;
		}
		catch ( Exception $ex )
		{
			Utilities::markTimeStop( 'DB_TIME' );
			error_log( 'retrievefilter: ' . $ex->getMessage() . PHP_EOL . $filter );
			/*
            $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
            if (isset($GLOBALS['DB_DEBUG'])) {
                error_log($msg . "\n$query");
            }
            */
			throw $ex;
		}
	}

	/**
	 * @param string $table
	 * @param array  $records
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function retrieveRecords( $table, $records, $id_field = '', $fields = '', $extras = array() )
	{
		if ( empty( $records ) || !is_array( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// single record
			$records = array( $records );
		}

		$table = $this->correctTableName( $table );
		$field_info = $this->describeTableFields( $table );
		if ( empty( $id_field ) )
		{
			$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $field_info );
			if ( empty( $id_field ) )
			{
				throw new Exception( "Identifying field can not be empty." );
			}
		}
		$ids = array();
		foreach ( $records as $key => $record )
		{
			$id = Utilities::getArrayValue( $id_field, $record, '' );
			if ( empty( $id ) )
			{
				throw new Exception( "Identifying field '$id_field' can not be empty for retrieve record [$key] request." );
			}
			$ids[] = $id;
		}
		$idList = implode( ',', $ids );

		return $this->retrieveRecordsByIds( $table, $idList, $id_field, $fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function retrieveRecord( $table, $record, $id_field = '', $fields = '', $extras = array() )
	{
		if ( !isset( $record ) || empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record.', ErrorCodes::BAD_REQUEST );
		}
		$results = $this->retrieveRecords( $table, $record, $id_field, $fields, $extras );

		return $results[0];
	}

	/**
	 * @param string $table
	 * @param string $id_list - comma delimited list of ids
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public function retrieveRecordsByIds( $table, $id_list, $id_field = '', $fields = '', $extras = array() )
	{
		if ( empty( $id_list ) )
		{
			return array();
		}
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		$table = $this->correctTableName( $table );
		try
		{
			$availFields = $this->describeTableFields( $table );
			$relations = $this->describeTableRelated( $table );
			if ( empty( $id_field ) )
			{
				$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $availFields );
				if ( empty( $id_field ) )
				{
					throw new Exception( "Identifying field can not be empty." );
				}
			}
			if ( !empty( $fields ) && ( '*' !== $fields ) )
			{
				// add id field to field list
				$fields = Utilities::addOnceToList( $fields, $id_field, ',' );
			}
			$result = $this->parseFieldsForSqlSelect( $fields, $availFields );
			$bindings = $result['bindings'];
			$fields = $result['fields'];
			// use query builder
			$command = $this->_sqlConn->createCommand();
			$command->select( $fields );
			$command->from( $table );
			$command->where( array( 'in', $id_field, $ids ) );

			$this->checkConnection();
			Utilities::markTimeStart( 'DB_TIME' );
			$reader = $command->query();
			$data = array();
			$dummy = array();
			foreach ( $bindings as $binding )
			{
				$reader->bindColumn( $binding['name'], $dummy[$binding['name']], $binding['type'] );
			}
			$reader->setFetchMode( PDO::FETCH_BOUND );
			$count = 0;
			while ( false !== $reader->read() )
			{
				$temp = array();
				foreach ( $bindings as $binding )
				{
					$temp[$binding['name']] = $dummy[$binding['name']];
				}
				if ( !empty( $extras ) )
				{
					$temp = $this->retrieveRelatedRecords( $relations, $temp, $extras );
				}
				$data[$count++] = $temp;
			}

			// order returned data by received ids, fill in error for those not found
			$results = array();
			foreach ( $ids as $id )
			{
				$foundRecord = null;
				foreach ( $data as $record )
				{
					if ( isset( $record[$id_field] ) && ( $record[$id_field] == $id ) )
					{
						$foundRecord = $record;
						break;
					}
				}
				$results[] = ( isset( $foundRecord )
					? $foundRecord
					:
					( "Could not find record for id = '$id'" ) );
			}

			Utilities::markTimeStop( 'DB_TIME' );

			return $results;
		}
		catch ( Exception $ex )
		{
			Utilities::markTimeStop( 'DB_TIME' );
			/*
            $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
            if (isset($GLOBALS['DB_DEBUG'])) {
                error_log($msg . "\n$query");
            }
            */
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
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
			$availFields = $this->describeTableFields( $table );
			$relations = $this->describeTableRelated( $table );
			if ( empty( $id_field ) )
			{
				$id_field = DbUtilities::getPrimaryKeyFieldFromDescribe( $availFields );
				if ( empty( $id_field ) )
				{
					throw new Exception( 'Identifying field can not be empty.', ErrorCodes::BAD_REQUEST );
				}
			}
			if ( !empty( $fields ) && ( '*' !== $fields ) )
			{
				// add id field to field list
				$fields = Utilities::addOnceToList( $fields, $id_field, ',' );
			}
			$result = $this->parseFieldsForSqlSelect( $fields, $availFields );
			$bindings = $result['bindings'];
			$fields = $result['fields'];
			// use query builder
			$command = $this->_sqlConn->createCommand();
			$command->select( $fields );
			$command->from( $table );
			$command->where( "$id_field = :id", array( ':id' => $id ) );

			$this->checkConnection();
			Utilities::markTimeStart( 'DB_TIME' );
			$reader = $command->query();
			$data = array();
			$dummy = array();
			foreach ( $bindings as $binding )
			{
				$reader->bindColumn( $binding['name'], $dummy[$binding['name']], $binding['type'] );
			}
			$reader->setFetchMode( PDO::FETCH_BOUND );
			if ( false !== $reader->read() )
			{
				foreach ( $bindings as $binding )
				{
					$data[$binding['name']] = $dummy[$binding['name']];
				}
				if ( !empty( $extras ) )
				{
					$data = $this->retrieveRelatedRecords( $relations, $data, $extras );
				}
			}
			else
			{
				throw new Exception( "Could not find record for id = '$id'", ErrorCodes::NOT_FOUND );
			}

			Utilities::markTimeStop( 'DB_TIME' );

			return $data;
		}
		catch ( Exception $ex )
		{
			Utilities::markTimeStop( 'DB_TIME' );
			/*
            $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
            if (isset($GLOBALS['DB_DEBUG'])) {
                error_log($msg . "\n$query");
            }
            */
			throw $ex;
		}
	}

	// Helper methods

	/**
	 * @param $name
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function describeTableFields( $name )
	{
		if ( isset( $this->_fieldCache[$name] ) )
		{
			return $this->_fieldCache[$name];
		}

		$fields = DbUtilities::describeTableFields( $this->_sqlConn, $name );
		$this->_fieldCache[$name] = $fields;

		return $fields;
	}

	/**
	 * @param $name
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function describeTableRelated( $name )
	{
		if ( isset( $this->_relatedCache[$name] ) )
		{
			return $this->_relatedCache[$name];
		}

		$relations = DbUtilities::describeTableRelated( $this->_sqlConn, $name );
		$relatives = array();
		foreach ( $relations as $relation )
		{
			$how = Utilities::getArrayValue( 'name', $relation, '' );
			$relatives[$how] = $relation;
		}
		$this->_relatedCache[$name] = $relatives;

		return $relatives;
	}

	/**
	 * @param      $record
	 * @param      $avail_fields
	 * @param bool $for_update
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function parseRecord( $record, $avail_fields, $for_update = false )
	{
		$parsed = array();
		$record = Utilities::array_key_lower( $record );
		$keys = array_keys( $record );
		$values = array_values( $record );
		foreach ( $avail_fields as $field_info )
		{
			$name = mb_strtolower( $field_info['name'] );
			$type = $field_info['type'];
			$dbType = $field_info['db_type'];
			$pos = array_search( $name, $keys );
			if ( false !== $pos )
			{
				$fieldVal = $values[$pos];
				// due to conversion from XML to array, null or empty xml elements have the array value of an empty array
				if ( is_array( $fieldVal ) && empty( $fieldVal ) )
				{
					$fieldVal = null;
				}
				// overwrite some undercover fields
				if ( Utilities::getArrayValue( 'auto_increment', $field_info, false ) )
				{
					unset( $keys[$pos] );
					unset( $values[$pos] );
					continue; // should I error this?
				}
				if ( Utilities::isInList( Utilities::getArrayValue( 'validation', $field_info, '' ), 'api_read_only', ',' ) )
				{
					unset( $keys[$pos] );
					unset( $values[$pos] );
					continue; // should I error this?
				}
				if ( is_null( $fieldVal ) && !$field_info['allow_null'] )
				{
					if ( $for_update )
					{
						continue;
					} // todo throw away nulls for now
					throw new Exception( "Field '$name' can not be NULL.", ErrorCodes::BAD_REQUEST );
				}
				else
				{
					if ( !is_null( $fieldVal ) )
					{
						switch ( $this->_driverType )
						{
							case DbUtilities::DRV_SQLSRV:
								switch ( $dbType )
								{
									case 'bit':
										$fieldVal = ( Utilities::boolval( $fieldVal ) ? 1 : 0 );
										break;
								}
								break;
							case DbUtilities::DRV_MYSQL:
								switch ( $dbType )
								{
									case 'tinyint(1)':
										$fieldVal = ( Utilities::boolval( $fieldVal ) ? 1 : 0 );
										break;
								}
								break;
						}
						switch ( $type )
						{
							case 'integer':
								if ( !is_int( $fieldVal ) )
								{
									if ( ( '' === $fieldVal ) && $field_info['allow_null'] )
									{
										$fieldVal = null;
									}
									elseif ( !( ctype_digit( $fieldVal ) ) )
									{
										throw new Exception( "Field '$name' must be a valid integer.", ErrorCodes::BAD_REQUEST );
									}
									else
									{
										$fieldVal = intval( $fieldVal );
									}
								}
								break;
							default:
						}
					}
				}
				$parsed[$name] = $fieldVal;
				unset( $keys[$pos] );
				unset( $values[$pos] );
			}
			else
			{
				// check specific fields
				switch ( $type )
				{
					case 'timestamp_on_create':
					case 'timestamp_on_update':
					case 'user_id_on_create':
					case 'user_id_on_update':
						break;
					default:
						// if field is required, kick back error
						if ( $field_info['required'] && !$for_update )
						{
							throw new Exception( "Required field '$name' can not be NULL.", ErrorCodes::BAD_REQUEST );
						}
						break;
				}
			}
			// add or override for specific fields
			switch ( $type )
			{
				case 'timestamp_on_create':
					if ( !$for_update )
					{
						switch ( $this->_driverType )
						{
							case DbUtilities::DRV_SQLSRV:
								$parsed[$name] = new CDbExpression( '(SYSDATETIMEOFFSET())' );
								break;
							case DbUtilities::DRV_MYSQL:
								$parsed[$name] = new CDbExpression( '(NOW())' );
								break;
						}
					}
					break;
				case 'timestamp_on_update':
					switch ( $this->_driverType )
					{
						case DbUtilities::DRV_SQLSRV:
							$parsed[$name] = new CDbExpression( '(SYSDATETIMEOFFSET())' );
							break;
						case DbUtilities::DRV_MYSQL:
							$parsed[$name] = new CDbExpression( '(NOW())' );
							break;
					}
					break;
				case 'user_id_on_create':
					if ( !$for_update )
					{
						$userId = UserSession::getCurrentUserId();
						if ( isset( $userId ) )
						{
							$parsed[$name] = $userId;
						}
					}
					break;
				case 'user_id_on_update':
					$userId = UserSession::getCurrentUserId();
					if ( isset( $userId ) )
					{
						$parsed[$name] = $userId;
					}
					break;
			}
		}

		return $parsed;
	}

	/**
	 * @param $table
	 * @param $record
	 * @param $id
	 * @param $avail_relations
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function updateRelations( $table, $record, $id, $avail_relations )
	{
		$record = Utilities::array_key_lower( $record );
		$keys = array_keys( $record );
		$values = array_values( $record );
		foreach ( $avail_relations as $relationInfo )
		{
			$name = mb_strtolower( $relationInfo['name'] );
			$pos = array_search( $name, $keys );
			if ( false !== $pos )
			{
				$relations = $values[$pos];
				$relationType = $relationInfo['type'];
				switch ( $relationType )
				{
					case 'belongs_to':
						/*
                    "name": "role_by_role_id",
                    "type": "belongs_to",
                    "ref_table": "role",
                    "ref_field": "id",
                    "field": "role_id"
                    */
						// todo handle this?
						break;
					case 'has_many':
						/*
                    "name": "users_by_last_modified_by_id",
                    "type": "has_many",
                    "ref_table": "user",
                    "ref_field": "last_modified_by_id",
                    "field": "id"
                    */
						$relatedTable = $relationInfo['ref_table'];
						$relatedField = $relationInfo['ref_field'];
						$this->assignManyToOne( $table, $id, $relatedTable, $relatedField, $relations );
						break;
					case 'many_many':
						/*
                    "name": "roles_by_user",
                    "type": "many_many",
                    "ref_table": "role",
                    "ref_field": "id",
                    "join": "user(default_app_id,role_id)"
                    */
						$relatedTable = $relationInfo['ref_table'];
						$join = $relationInfo['join'];
						$joinTable = substr( $join, 0, strpos( $join, '(' ) );
						$other = explode( ',', substr( $join, strpos( $join, '(' ) + 1, -1 ) );
						$joinLeftField = trim( $other[0] );
						$joinRightField = trim( $other[1] );
						$this->assignManyToOneByMap(
							$table,
							$id,
							$relatedTable,
							$joinTable,
							$joinLeftField,
							$joinRightField,
							$relations
						);
						break;
					default:
						throw new Exception( 'Invalid relationship type detected.', ErrorCodes::INTERNAL_SERVER_ERROR );
						break;
				}
				unset( $keys[$pos] );
				unset( $values[$pos] );
			}
		}
	}

	/**
	 * @param array $record
	 *
	 * @return string
	 */
	protected function parseRecordForSqlInsert( $record )
	{
		$values = '';
		foreach ( $record as $key => $value )
		{
			$fieldVal = ( is_null( $value ) ) ? "NULL" : $this->_sqlConn->quoteValue( $value );
			$values .= ( !empty( $values ) ) ? ',' : '';
			$values .= $fieldVal;
		}

		return $values;
	}

	/**
	 * @param array $record
	 *
	 * @return string
	 */
	protected function parseRecordForSqlUpdate( $record )
	{
		$out = '';
		foreach ( $record as $key => $value )
		{
			$fieldVal = ( is_null( $value ) ) ? "NULL" : $this->_sqlConn->quoteValue( $value );
			$out .= ( !empty( $values ) ) ? ',' : '';
			$out .= "$key = $fieldVal";
		}

		return $out;
	}

	/**
	 * @param        $fields
	 * @param        $avail_fields
	 * @param bool   $as_quoted_string
	 * @param string $prefix
	 * @param string $fields_as
	 *
	 * @return string
	 */
	protected function parseFieldsForSqlSelect( $fields, $avail_fields, $as_quoted_string = false, $prefix = '', $fields_as = '' )
	{
		if ( empty( $fields ) || ( '*' === $fields ) )
		{
			$fields = DbUtilities::listAllFieldsFromDescribe( $avail_fields );
		}
		$field_arr = array_map( 'trim', explode( ',', $fields ) );
		$as_arr = array_map( 'trim', explode( ',', $fields_as ) );
		if ( !$as_quoted_string )
		{
			// yii will not quote anything if any of the fields are expressions
		}
		$outString = '';
		$outArray = array();
		$bindArray = array();
		for ( $i = 0, $size = sizeof( $field_arr ); $i < $size; $i++ )
		{
			$field = $field_arr[$i];
			$as = ( isset( $as_arr[$i] ) ? $as_arr[$i] : '' );
			$context = ( empty( $prefix ) ? $field : $prefix . '.' . $field );
			$out_as = ( empty( $as ) ? $field : $as );
			if ( $as_quoted_string )
			{
				$context = $this->_sqlConn->quoteColumnName( $context );
				$out_as = $this->_sqlConn->quoteColumnName( $out_as );
			}
			// find the type
			$field_info = DbUtilities::getFieldFromDescribe( $field, $avail_fields );
			$dbType = ( isset( $field_info ) ) ? $field_info['db_type'] : '';
			$type = ( isset( $field_info ) ) ? $field_info['type'] : '';
			switch ( $type )
			{
				case 'boolean':
					$bindArray[] = array( 'name' => $field, 'type' => PDO::PARAM_BOOL );
					break;
				case 'integer':
					$bindArray[] = array( 'name' => $field, 'type' => PDO::PARAM_INT );
					break;
				default:
					$bindArray[] = array( 'name' => $field, 'type' => PDO::PARAM_STR );
					break;
			}
			// todo fix special cases - maybe after retrieve
			switch ( $dbType )
			{
				case 'datetime':
				case 'datetimeoffset':
					switch ( $this->_driverType )
					{
						case DbUtilities::DRV_SQLSRV:
							if ( !$as_quoted_string )
							{
								$context = $this->_sqlConn->quoteColumnName( $context );
								$out_as = $this->_sqlConn->quoteColumnName( $out_as );
							}
							$out = "(CONVERT(nvarchar(30), $context, 127)) AS $out_as";
							break;
						default:
							$out = $context;
							break;
					}
					break;
				default :
					$out = $context;
					if ( !empty( $as ) )
					{
						$out .= ' AS ' . $out_as;
					}
					break;
			}

			$outArray[] = $out;
		}

		return array( 'fields' => $outArray, 'bindings' => $bindArray );
	}

	/**
	 * @param        $fields
	 * @param        $avail_fields
	 * @param string $prefix
	 *
	 * @return string
	 * @throws Exception
	 */
	public function parseOutFields( $fields, $avail_fields, $prefix = 'INSERTED' )
	{
		if ( empty( $fields ) )
		{
			return '';
		}

		$out_str = '';
		$field_arr = array_map( 'trim', explode( ',', $fields ) );
		foreach ( $field_arr as $field )
		{
			// find the type
			if ( false === DbUtilities::findFieldFromDescribe( $field, $avail_fields ) )
			{
				throw new Exception( "Invalid field '$field' selected for output." );
			}
			if ( !empty( $out_str ) )
			{
				$out_str .= ', ';
			}
			$out_str .= $prefix . '.' . $this->_sqlConn->quoteColumnName( $field );
		}

		return $out_str;
	}

	// generic assignments

	/**
	 * @param $relations
	 * @param $data
	 * @param $extras
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function retrieveRelatedRecords( $relations, $data, $extras )
	{
		if ( !empty( $extras ) )
		{
			$relatedData = array();
			foreach ( $extras as $extra )
			{
				$extraName = $extra['name'];
				if ( !isset( $relations[$extraName] ) )
				{
					throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
				}
				$relation = $relations[$extraName];
				$relationType = $relation['type'];
				$relatedTable = $relation['ref_table'];
				$relatedField = $relation['ref_field'];
				$extraFields = $extra['fields'];
				switch ( $relationType )
				{
					case 'belongs_to':
						$field = $relation['field'];
						$fieldVal = Utilities::getArrayValue( $field, $data );
						$relatedRecords = $this->retrieveRecordsByFilter( $relatedTable, $extraFields, "$relatedField = '$fieldVal'" );
						if ( !empty( $relatedRecords ) )
						{
							$tempData = $relatedRecords[0];
						}
						else
						{
							$tempData = null;
						}
						break;
					case 'has_many':
						$field = $relation['field'];
						$fieldVal = Utilities::getArrayValue( $field, $data );
						$tempData = $this->retrieveRecordsByFilter( $relatedTable, $extraFields, "$relatedField = '$fieldVal'" );
						break;
					case 'many_many':
						$field = $relation['field'];
						$fieldVal = Utilities::getArrayValue( $field, $data );
						$join = $relation['join'];
						$joinTable = substr( $join, 0, strpos( $join, '(' ) );
						$other = explode( ',', substr( $join, strpos( $join, '(' ) + 1, -1 ) );
						$joinLeftField = trim( $other[0] );
						$joinRightField = trim( $other[1] );
						$joinData = $this->retrieveRecordsByFilter( $joinTable, $joinRightField, "$joinLeftField = '$fieldVal'" );
						$tempData = array();
						if ( !empty( $joinData ) )
						{
							$relatedIds = array();
							foreach ( $joinData as $record )
							{
								$relatedIds[] = $record[$joinRightField];
							}
							if ( !empty( $relatedIds ) )
							{
								$relatedIds = implode( ',', $relatedIds );
								$tempData = $this->retrieveRecordsByIds( $relatedTable, $relatedIds, $relatedField, $extraFields );
							}
						}
						break;
					default:
						throw new Exception( 'Invalid relationship type detected.', ErrorCodes::INTERNAL_SERVER_ERROR );
						break;
				}
				$relatedData[$extraName] = $tempData;
			}
			if ( !empty( $relatedData ) )
			{
				$data = array_merge( $data, $relatedData );
			}
		}

		return $data;

	}

	/**
	 * @param string $one_table
	 * @param string $one_id
	 * @param string $many_table
	 * @param string $many_field
	 * @param array  $many_records
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function assignManyToOne( $one_table, $one_id, $many_table, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new Exception( "The $one_table id can not be empty.", ErrorCodes::BAD_REQUEST );
		}
		try
		{
			$manyFields = $this->describeTableFields( $many_table );
			$pkField = DbUtilities::getPrimaryKeyFieldFromDescribe( $manyFields );
			$oldMany = $this->retrieveRecordsByFilter( $many_table, "$pkField,$many_field", $many_field . " = '$one_id'" );
			foreach ( $oldMany as $oldKey => $old )
			{
				$oldId = Utilities::getArrayValue( $pkField, $old );
				foreach ( $many_records as $key => $item )
				{
					$id = Utilities::getArrayValue( $pkField, $item, '' );
					if ( $id == $oldId )
					{
						// found it, keeping it, so remove it from the list, as this becomes adds
						unset( $many_records[$key] );
						unset( $oldMany[$oldKey] );
						continue;
					}
				}
			}
			// reset arrays
			$many_records = array_values( $many_records );
			$oldMany = array_values( $oldMany );
			if ( !empty( $oldMany ) )
			{
				// un-assign any left over old ones
				$ids = array();
				foreach ( $oldMany as $item )
				{
					$ids[] = Utilities::getArrayValue( $pkField, $item, '' );
				}
				if ( !empty( $ids ) )
				{
					$ids = implode( ',', $ids );
					$this->updateRecordsByIds( $many_table, array( $many_field => null ), $ids, $pkField );
				}
			}
			if ( !empty( $many_records ) )
			{
				// assign what is leftover
				$ids = array();
				foreach ( $many_records as $item )
				{
					$ids[] = Utilities::getArrayValue( $pkField, $item, '' );
				}
				if ( !empty( $ids ) )
				{
					$ids = implode( ',', $ids );
					$this->updateRecordsByIds( $many_table, array( $many_field => $one_id ), $ids, $pkField );
				}
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating many to one assignment.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param       $one_table
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
	protected function assignManyToOneByMap( $one_table, $one_id, $many_table, $map_table, $one_field, $many_field, $many_records = array() )
	{
		if ( empty( $one_id ) )
		{
			throw new Exception( "The $one_table id can not be empty.", ErrorCodes::BAD_REQUEST );
		}
		try
		{
			$manyFields = $this->describeTableFields( $many_table );
			$pkManyField = DbUtilities::getPrimaryKeyFieldFromDescribe( $manyFields );
			$mapFields = $this->describeTableFields( $map_table );
			$pkMapField = DbUtilities::getPrimaryKeyFieldFromDescribe( $mapFields );
			$maps = $this->retrieveRecordsByFilter( $map_table, $pkMapField . ',' . $many_field, "$one_field = '$one_id'" );
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
				$this->deleteRecordsByIds( $map_table, implode( ',', $toDelete ), $pkMapField );
			}
			if ( !empty( $many_records ) )
			{
				$maps = array();
				foreach ( $many_records as $item )
				{
					$itemId = Utilities::getArrayValue( $pkManyField, $item, '' );
					$maps[] = array( $many_field => $itemId, $one_field => $one_id );
				}
				$this->createRecords( $map_table, $maps );
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating many to one map assignment.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * Handle raw SQL Azure requests
	 */
	protected function batchSqlQuery( $query, $bindings = array() )
	{
		if ( empty( $query ) )
		{
			throw new Exception( '[NOQUERY]: No query string present in request.' );
		}
		$this->checkConnection();
		try
		{
			Utilities::markTimeStart( 'DB_TIME' );

			$command = $this->_sqlConn->createCommand( $query );
			$reader = $command->query();
			$dummy = null;
			foreach ( $bindings as $binding )
			{
				$reader->bindColumn( $binding['name'], $dummy, $binding['type'] );
			}

			$data = array();
			$rowData = array();
			while ( $row = $reader->read() )
			{
				$rowData[] = $row;
			}
			if ( 1 == count( $rowData ) )
			{
				$rowData = $rowData[0];
			}
			$data[] = $rowData;

			// Move to the next result and get results
			while ( $reader->nextResult() )
			{
				$rowData = array();
				while ( $row = $reader->read() )
				{
					$rowData[] = $row;
				}
				if ( 1 == count( $rowData ) )
				{
					$rowData = $rowData[0];
				}
				$data[] = $rowData;
			}

			Utilities::markTimeStop( 'DB_TIME' );

			return $data;
		}
		catch ( Exception $ex )
		{
			error_log( 'batchquery: ' . $ex->getMessage() . PHP_EOL . $query );
			Utilities::markTimeStop( 'DB_TIME' );
			/*
                $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
                if (isset($GLOBALS['DB_DEBUG'])) {
                    error_log($msg . "\n$query");
                }
*/
			throw $ex;
		}
	}

	/**
	 * Handle SQL Db requests with output as array
	 */
	public function singleSqlQuery( $query, $params = null )
	{
		if ( empty( $query ) )
		{
			throw new Exception( '[NOQUERY]: No query string present in request.' );
		}
		$this->checkConnection();
		try
		{
			Utilities::markTimeStart( 'DB_TIME' );

			$command = $this->_sqlConn->createCommand( $query );
			if ( isset( $params ) && !empty( $params ) )
			{
				$data = $command->queryAll( true, $params );
			}
			else
			{
				$data = $command->queryAll();
			}

			Utilities::markTimeStop( 'DB_TIME' );

			return $data;
		}
		catch ( Exception $ex )
		{
			error_log( 'singlequery: ' . $ex->getMessage() . PHP_EOL . $query . PHP_EOL . print_r( $params, true ) );
			Utilities::markTimeStop( 'DB_TIME' );
			/*
                    $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
                    if (isset($GLOBALS['DB_DEBUG'])) {
                        error_log($msg . "\n$query");
                    }
*/
			throw $ex;
		}
	}

	/**
	 * Handle SQL Db requests with output as array
	 */
	public function singleSqlExecute( $query, $params = null )
	{
		if ( empty( $query ) )
		{
			throw new Exception( '[NOQUERY]: No query string present in request.' );
		}
		$this->checkConnection();
		try
		{
			Utilities::markTimeStart( 'DB_TIME' );

			$command = $this->_sqlConn->createCommand( $query );
			if ( isset( $params ) && !empty( $params ) )
			{
				$data = $command->execute( $params );
			}
			else
			{
				$data = $command->execute();
			}

			Utilities::markTimeStop( 'DB_TIME' );

			return $data;
		}
		catch ( Exception $ex )
		{
			error_log( 'singleexecute: ' . $ex->getMessage() . PHP_EOL . $query . PHP_EOL . print_r( $params, true ) );
			Utilities::markTimeStop( 'DB_TIME' );
			/*
                    $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
                    if (isset($GLOBALS['DB_DEBUG'])) {
                        error_log($msg . "\n$query");
                    }
*/
			throw $ex;
		}
	}

}
