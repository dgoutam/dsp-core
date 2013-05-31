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
namespace Platform\Resources;

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Exceptions\NotFoundException;
use Platform\Services\SystemManager;
use Platform\Utility\DataFormat;
use Platform\Utility\RestRequest;
use Platform\Utility\SqlDbUtilities;
use Platform\Utility\Utilities;

/**
 * SystemResource
 * DSP system administration manager
 *
 */
class SystemResource extends RestResource
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const SYSTEM_TABLE_PREFIX = 'df_sys_';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_resourceArray;
	/**
	 * @var int
	 */
	protected $_resourceId;
	/**
	 * @var string
	 */
	protected $_relatedResource;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new SystemResource instance
	 *
	 */
	public function __construct( $config = array(), $resource_array = array() )
	{
		parent::__construct( $config );

		$this->_resourceArray = $resource_array;
	}

	// Service interface implementation

	/**
	 * @param string $apiName
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setApiName( $apiName )
	{
		throw new \Exception( 'SystemResource API name can not be changed.' );
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setType( $type )
	{
		throw new \Exception( 'SystemResource type can not be changed.' );
	}

	/**
	 * @param string $description
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setDescription( $description )
	{
		throw new \Exception( 'SystemResource description can not be changed.' );
	}

	/**
	 * @param boolean $isActive
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setIsActive( $isActive )
	{
		throw new \Exception( 'SystemResource active flag can not be changed.' );
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setName( $name )
	{
		throw new \Exception( 'SystemResource name can not be changed.' );
	}

	/**
	 * @param int $resourceArray
	 */
	public function setResourceArray( $resourceArray )
	{
		$this->_resourceArray = $resourceArray;
	}

	// REST interface implementation

	/**
	 * Apply the commonly used REST path members to the class
	 */
	protected function _detectResourceMembers()
	{
		parent::_detectResourceMembers();

		$this->_resourceId = ( isset( $this->_resourceArray[1] ) ) ? $this->_resourceArray[1] : '';
	}

	/**
	 *
	 * @throws BadRequestException
	 * @return array|bool
	 */
	protected function _handleAction()
	{
		// Most requests contain 'returned fields' parameter, all by default
		$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '*' );
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

		switch ( $this->_action )
		{
			case self::Get:
				if ( empty( $this->_resourceId ) )
				{
					$ids = Utilities::getArrayValue( 'ids', $_REQUEST, '' );
					if ( !empty( $ids ) )
					{
						$result = static::retrieveRecordsByIds( $this->_apiName, $ids, $fields, $extras );
					}
					else
					{ // get by filter or all
						$data = RestRequest::getPostDataAsArray();
						if ( !empty( $data ) )
						{ // complex filters or large numbers of ids require post
							$ids = Utilities::getArrayValue( 'ids', $data, '' );
							if ( !empty( $ids ) )
							{
								$result = static::retrieveRecordsByIds( $this->_apiName, $ids, $fields, $extras );
							}
							else
							{
								$records = Utilities::getArrayValue( 'record', $data, null );
								if ( empty( $records ) )
								{
									// xml to array conversion leaves them in plural wrapper
									$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
								}
								if ( !empty( $records ) )
								{
									// passing records to have them updated with new or more values, id field required
									// for single record and no id field given, get records matching given fields
									$result = static::retrieveRecords( $this->_apiName, $records, $fields, $extras );
								}
								else
								{ // if not specified use filter
									$filter = Utilities::getArrayValue( 'filter', $data, '' );
									$limit = intval( Utilities::getArrayValue( 'limit', $data, 0 ) );
									$order = Utilities::getArrayValue( 'order', $data, '' );
									$offset = intval( Utilities::getArrayValue( 'offset', $data, 0 ) );
									$include_count = Utilities::boolval( Utilities::getArrayValue( 'include_count', $data, false ) );
									$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $data, false ) );
									$result = static::retrieveRecordsByFilter(
										$this->_apiName,
										$fields,
										$filter,
										$limit,
										$order,
										$offset,
										$include_count,
										$include_schema,
										$extras
									);
								}
							}
						}
						else
						{
							$filter = Utilities::getArrayValue( 'filter', $_REQUEST, '' );
							$limit = intval( Utilities::getArrayValue( 'limit', $_REQUEST, 0 ) );
							$order = Utilities::getArrayValue( 'order', $_REQUEST, '' );
							$offset = intval( Utilities::getArrayValue( 'offset', $_REQUEST, 0 ) );
							$include_count = Utilities::boolval( Utilities::getArrayValue( 'include_count', $_REQUEST, false ) );
							$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $_REQUEST, false ) );
							$result = static::retrieveRecordsByFilter(
								$this->_apiName,
								$fields,
								$filter,
								$limit,
								$order,
								$offset,
								$include_count,
								$include_schema,
								$extras
							);
						}
					}
				}
				else
				{
					// single entity by id
					$result = static::retrieveRecordById( $this->_apiName, $this->_resourceId, $fields, $extras );
				}
				break;
			case self::Post:
				$data = RestRequest::getPostDataAsArray();
				$records = Utilities::getArrayValue( 'record', $data, array() );
				if ( empty( $records ) )
				{
					// xml to array conversion leaves them in plural wrapper
					$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
				}
				if ( empty( $records ) )
				{
					if ( empty( $data ) )
					{
						throw new BadRequestException( 'No record in POST create request.' );
					}
					$result = static::createRecord( $this->_apiName, $data, $fields, $extras );
				}
				else
				{
					$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
					if ( !isset( $rollback ) )
					{
						$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
					}
					$result = static::createRecords( $this->_apiName, $records, $rollback, $fields, $extras );
				}
				break;
			case self::Put:
			case self::Patch:
			case self::Merge:
				$data = RestRequest::getPostDataAsArray();
				if ( empty( $this->_resourceId ) )
				{
					$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
					if ( !isset( $rollback ) )
					{
						$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
					}
					$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
					if ( empty( $ids ) )
					{
						$ids = Utilities::getArrayValue( 'ids', $data, '' );
					}
					if ( !empty( $ids ) )
					{
						$result = static::updateRecordsByIds( $this->_apiName, $ids, $data, $rollback, $fields, $extras );
					}
					else
					{
						$records = Utilities::getArrayValue( 'record', $data, null );
						if ( empty( $records ) )
						{
							// xml to array conversion leaves them in plural wrapper
							$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
						}
						if ( empty( $records ) )
						{
							if ( empty( $data ) )
							{
								throw new BadRequestException( 'No record in PUT update request.' );
							}
							$result = static::updateRecord( $this->_apiName, $data, $fields, $extras );
						}
						else
						{
							$result = static::updateRecords( $this->_apiName, $records, $rollback, $fields, $extras );
						}
					}
				}
				else
				{
					$result = static::updateRecordById( $this->_apiName, $this->_resourceId, $data, $fields, $extras );
				}
				break;
			case self::Delete:
				if ( empty( $this->_resourceId ) )
				{
					$data = RestRequest::getPostDataAsArray();
					$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
					if ( empty( $ids ) )
					{
						$ids = Utilities::getArrayValue( 'ids', $data, '' );
					}
					if ( !empty( $ids ) )
					{
						$result = static::deleteRecordsByIds( $this->_apiName, $ids, $fields, $extras );
					}
					else
					{
						$records = Utilities::getArrayValue( 'record', $data, null );
						if ( empty( $records ) )
						{
							// xml to array conversion leaves them in plural wrapper
							$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
						}
						if ( empty( $records ) )
						{
							if ( empty( $data ) )
							{
								throw new BadRequestException( "Id list or record containing Id field required to delete $this->_apiName records." );
							}
							$result = static::deleteRecord( $this->_apiName, $data, $fields, $extras );
						}
						else
						{
							$result = static::deleteRecords( $this->_apiName, $records, $fields, $extras );
						}
					}
				}
				else
				{
					$result = static::deleteRecordById( $this->_apiName, $this->_resourceId, $fields, $extras );
				}
				break;
			default:
				return false;
		}

		return $result;
	}

	//-------- System Records Operations ---------------------
	// records is an array of field arrays

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @throws BadRequestException
	 * @throws InternalServerErrorException
	 * @return array
	 */
	protected static function createRecordLow( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record to create.' );
		}
		try
		{
			// create DB record
			/** @var \BaseDspSystemModel $obj */
			$obj = SystemManager::getNewModel( $table );
			$obj->setAttributes( $record );
			$obj->save();
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to create $table.\n{$ex->getMessage()}" );
		}

		try
		{
			$id = $obj->primaryKey;
			if ( empty( $id ) )
			{
				Log::error( 'Failed to get primary key from created user: ' . print_r( $obj, true ) );
				throw new InternalServerErrorException( "Failed to get primary key from created user." );
			}

			// after record create
			$obj->setRelated( $record, $id );

			$primaryKey = $obj->tableSchema->primaryKey;
			if ( empty( $return_fields ) && empty( $extras ) )
			{
				$data = array( $primaryKey => $id );
			}
			else
			{
				// get returnables
				$obj->refresh();
				$return_fields = $obj->getRetrievableAttributes( $return_fields );
				$data = $obj->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relations = $obj->relations();
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new BadRequestException( "Invalid relation '$extraName' requested." );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $obj->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							/**
							 * @var \BaseDspSystemModel[] $relatedRecords
							 */
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							/**
							 * @var \BaseDspSystemModel $relatedRecords
							 */
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}
			}

			return $data;
		}
		catch ( BadRequestException $ex )
		{
			// need to delete the above table entry and clean up
			if ( isset( $obj ) && !$obj->getIsNewRecord() )
			{
				$obj->delete();
			}
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function createRecords( $table, $records, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( empty( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{ // isArrayNumeric($records)
			// conversion from xml can pull single record out of array format
			$records = array( $records );
		}
		UserSession::checkSessionPermission( 'create', 'system', $table );
		// todo implement rollback
		$out = array();
		foreach ( $records as $record )
		{
			try
			{
				$out[] = static::createRecordLow( $table, $record, $return_fields, $extras );
			}
			catch ( \Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function createRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		UserSession::checkSessionPermission( 'create', 'system', $table );

		return static::createRecordLow( $table, $record, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws NotFoundException
	 * @throws \Exception
	 * @throws BadRequestException
	 * @throws InternalServerErrorException
	 * @return array
	 */
	protected static function updateRecordLow( $table, $id, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record to create.' );
		}
		if ( empty( $id ) )
		{
			throw new BadRequestException( "Identifying field 'id' can not be empty for update request." );
		}
		/** @var \BaseDspSystemModel $model */
		$model = SystemManager::getResourceModel( $table );
		/** @var \BaseDspSystemModel $obj */
		$obj = $model->findByPk( $id );
		if ( !$obj )
		{
			throw new NotFoundException( "Failed to find the $table resource identified by '$id'." );
		}

		$primaryKey = $obj->tableSchema->primaryKey;
		$record = DataFormat::removeOneFromArray( $primaryKey, $record );

		try
		{
			$obj->setAttributes( $record );
			$obj->save();
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update user.\n{$ex->getMessage()}" );
		}

		try
		{
			// after record create
			$obj->setRelated( $record, $id );

			if ( empty( $return_fields ) && empty( $extras ) )
			{
				$data = array( $primaryKey => $id );
			}
			else
			{
				// get returnables
				$obj->refresh();
				$return_fields = $model->getRetrievableAttributes( $return_fields );
				$data = $obj->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relations = $obj->relations();
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new BadRequestException( "Invalid relation '$extraName' requested." );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $obj->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							/**
							 * @var \BaseDspSystemModel[] $relatedRecords
							 */
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							/**
							 * @var \BaseDspSystemModel $relatedRecords
							 */
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}
			}

			return $data;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function updateRecords( $table, $records, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( empty( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// conversion from xml can pull single record out of array format
			$records = array( $records );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );
		$out = array();
		foreach ( $records as $record )
		{
			try
			{
				// todo this needs to use $model->getPrimaryKey()
				$id = Utilities::getArrayValue( 'id', $record, '' );
				$out[] = static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
			}
			catch ( \Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function updateRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( empty( $record ) )
		{
			throw new BadRequestException( 'There is no record in the request.' );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );
		// todo this needs to use $model->getPrimaryKey()
		$id = Utilities::getArrayValue( 'id', $record, '' );

		return static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param        $record
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function updateRecordsByIds( $table, $id_list, $record, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( empty( $record ) )
		{
			throw new BadRequestException( 'There is no record in the request.' );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		$out = array();
		foreach ( $ids as $id )
		{
			try
			{
				$out[] = static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
			}
			catch ( \Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function updateRecordById( $table, $id, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		if ( empty( $record ) )
		{
			throw new BadRequestException( 'There is no record in the request.' );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );

		return static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws NotFoundException
	 * @throws \Exception
	 * @throws BadRequestException
	 * @throws InternalServerErrorException
	 * @return array
	 */
	protected static function deleteRecordLow( $table, $id, $return_fields = '', $extras = array() )
	{
		if ( empty( $id ) )
		{
			throw new BadRequestException( "Identifying field 'id' can not be empty for delete request." );
		}
		/** @var \BaseDspSystemModel $model */
		$model = SystemManager::getResourceModel( $table );
		$obj = $model->findByPk( $id );
		if ( !$obj )
		{
			throw new NotFoundException( "Failed to find the $table resource identified by '$id'." );
		}
		try
		{
			$obj->delete();
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to delete $table.\n{$ex->getMessage()}" );
		}

		try
		{
			$return_fields = $model->getRetrievableAttributes( $return_fields );
			$data = $obj->getAttributes( $return_fields );
			if ( !empty( $extras ) )
			{
				$relations = $obj->relations();
				$relatedData = array();
				foreach ( $extras as $extra )
				{
					$extraName = $extra['name'];
					if ( !isset( $relations[$extraName] ) )
					{
						throw new BadRequestException( "Invalid relation '$extraName' requested." );
					}
					$extraFields = $extra['fields'];
					$relatedRecords = $obj->getRelated( $extraName, true );
					if ( is_array( $relatedRecords ) )
					{
						/**
						 * @var \BaseDspSystemModel[] $relatedRecords
						 */
						// an array of records
						$tempData = array();
						if ( !empty( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
							foreach ( $relatedRecords as $relative )
							{
								$tempData[] = $relative->getAttributes( $relatedFields );
							}
						}
					}
					else
					{
						/**
						 * @var \BaseDspSystemModel $relatedRecords
						 */
						$tempData = null;
						if ( isset( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
							$tempData = $relatedRecords->getAttributes( $relatedFields );
						}
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
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function deleteRecords( $table, $records, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $records ) )
		{
			throw new BadRequestException( 'There are no record sets in the request.' );
		}
		if ( !isset( $records[0] ) )
		{
			// conversion from xml can pull single record out of array format
			$records = array( $records );
		}
		$out = array();
		foreach ( $records as $record )
		{
			if ( !empty( $record ) )
			{
				throw new BadRequestException( 'There are no fields in the record set.' );
			}
			$id = Utilities::getArrayValue( 'id', $record, '' );
			try
			{
				$out[] = static::deleteRecordLow( $table, $id, $return_fields, $extras );
			}
			catch ( \Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function deleteRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $record ) )
		{
			throw new BadRequestException( 'There are no fields in the record.' );
		}
		$id = Utilities::getArrayValue( 'id', $record, '' );

		return static::deleteRecordById( $table, $id, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function deleteRecordsByIds( $table, $id_list, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		UserSession::checkSessionPermission( 'delete', 'system', $table );
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		$out = array();
		foreach ( $ids as $id )
		{
			try
			{
				$out[] = static::deleteRecordLow( $table, $id, $return_fields, $extras );
			}
			catch ( \Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @return array
	 */
	public static function deleteRecordById( $table, $id, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		UserSession::checkSessionPermission( 'delete', 'system', $table );

		return static::deleteRecordLow( $table, $id, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @return array
	 * @throws BadRequestException
	 */
	public static function retrieveRecords( $table, $records, $return_fields = '', $extras = array() )
	{
		if ( isset( $records[0] ) )
		{
			// an array of records
			$ids = array();
			foreach ( $records as $key => $record )
			{
				$id = Utilities::getArrayValue( 'id', $record, '' );
				if ( empty( $id ) )
				{
					throw new BadRequestException( "Identifying field 'id' can not be empty for retrieve record [$key] request." );
				}
				$ids[] = $id;
			}
			$idList = implode( ',', $ids );

			return static::retrieveRecordsByIds( $table, $idList, $return_fields, $extras );
		}
		else
		{
			// single record
			$id = Utilities::getArrayValue( 'id', $records, '' );

			return static::retrieveRecordById( $table, $id, $return_fields, $extras );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function retrieveRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		$id = Utilities::getArrayValue( 'id', $record, '' );

		return static::retrieveRecordById( $table, $id, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param string $return_fields
	 * @param string $filter
	 * @param int    $limit
	 * @param string $order
	 * @param int    $offset
	 * @param bool   $include_count
	 * @param bool   $include_schema
	 * @param array  $extras
	 *
	 * @throws BadRequestException
	 * @throws \Exception
	 * @return array
	 */
	public static function retrieveRecordsByFilter( $table, $return_fields = '', $filter = '',
													$limit = 0, $order = '', $offset = 0,
													$include_count = false, $include_schema = false,
													$extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		UserSession::checkSessionPermission( 'read', 'system', $table );
		/** @var \BaseDspSystemModel $model */
		$model = SystemManager::getResourceModel( $table );
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();

		try
		{
			$command = new \CDbCriteria();
			//$command->select = $return_fields;
			if ( !empty( $filter ) )
			{
				$command->condition = $filter;
			}
			if ( !empty( $order ) )
			{
				$command->order = $order;
			}
			if ( $offset > 0 )
			{
				$command->offset = $offset;
			}
			if ( $limit > 0 )
			{
				$command->limit = $limit;
			}
			else
			{
				// todo impose a limit to protect server
			}
			/**
			 * @var \BaseDspSystemModel[] $records
			 */
			$records = $model->findAll( $command );
			$out = array();
			foreach ( $records as $record )
			{
				$data = $record->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new BadRequestException( "Invalid relation '$extraName' requested." );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $record->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							/**
							 * @var \BaseDspSystemModel[] $relatedRecords
							 */
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							/**
							 * @var \BaseDspSystemModel $relatedRecords
							 */
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}

				$out[] = $data;
			}

			$results = array( 'record' => $out );
			if ( $include_count || $include_schema )
			{
				// count total records
				if ( $include_count )
				{
					$count = $model->count( $command );
					$results['meta']['count'] = intval( $count );
				}
				// count total records
				if ( $include_schema )
				{
					$results['meta']['schema'] = SqlDbUtilities::describeTable( \Yii::app()->db, $model->tableName(), static::SYSTEM_TABLE_PREFIX );
				}
			}

			return $results;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws NotFoundException
	 * @throws BadRequestException
	 * @throws \Exception
	 * @return array
	 */
	public static function retrieveRecordsByIds( $table, $id_list, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		UserSession::checkSessionPermission( 'read', 'system', $table );
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		/** @var \BaseDspSystemModel $model */
		$model = SystemManager::getResourceModel( $table );
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();

		try
		{
			/**
			 * @var \BaseDspSystemModel[] $records
			 */
			$records = $model->findAllByPk( $ids );
			if ( empty( $records ) )
			{
				throw new NotFoundException( "No $table resources with ids '$id_list' could be found" );
			}
			foreach ( $records as $record )
			{
				$pk = $record->primaryKey;
				$key = array_search( $pk, $ids );
				if ( false === $key )
				{
					throw new \Exception( 'Bad returned data from query' );
				}
				$data = $record->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new BadRequestException( "Invalid relation '$extraName' requested." );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $record->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							/**
							 * @var \BaseDspSystemModel[] $relatedRecords
							 */
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							/**
							 * @var \BaseDspSystemModel $relatedRecords
							 */
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}

				$ids[$key] = $data;
			}
			foreach ( $ids as $key => $id )
			{
				if ( !is_array( $id ) )
				{
					$message = "A $table resource with id '$id' could not be found.";
					$ids[$key] = array( 'error' => array( 'message' => $message, 'code' => 404 ) );
				}
			}

			return array( 'record' => $ids );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error retrieving $table records.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws NotFoundException
	 * @throws BadRequestException
	 * @throws \Exception
	 * @return array
	 */
	public static function retrieveRecordById( $table, $id, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		UserSession::checkSessionPermission( 'read', 'system', $table );
		/** @var \BaseDspSystemModel $model */
		$model = SystemManager::getResourceModel( $table );
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();
		$record = $model->findByPk( $id );
		if ( null === $record )
		{
			throw new NotFoundException( 'Record not found.' );
		}
		try
		{
			$data = $record->getAttributes( $return_fields );
			if ( !empty( $extras ) )
			{
				$relatedData = array();
				foreach ( $extras as $extra )
				{
					$extraName = $extra['name'];
					if ( !isset( $relations[$extraName] ) )
					{
						throw new BadRequestException( "Invalid relation '$extraName' requested." );
					}
					$extraFields = $extra['fields'];
					$relatedRecords = $record->getRelated( $extraName, true );
					if ( is_array( $relatedRecords ) )
					{
						/**
						 * @var \BaseDspSystemModel[] $relatedRecords
						 */
						// an array of records
						$tempData = array();
						if ( !empty( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
							foreach ( $relatedRecords as $relative )
							{
								$tempData[] = $relative->getAttributes( $relatedFields );
							}
						}
					}
					else
					{
						/**
						 * @var \BaseDspSystemModel $relatedRecords
						 */
						$tempData = null;
						if ( isset( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
							$tempData = $relatedRecords->getAttributes( $relatedFields );
						}
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
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error retrieving $table records.\n{$ex->getMessage()}" );
		}
	}
}
