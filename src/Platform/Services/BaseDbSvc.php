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
use Platform\Exceptions\BadRequestException;
use Platform\Utility\DataFormat;
use Platform\Utility\RestRequest;
use Platform\Utility\Utilities;

/**
 * BaseDbSvc
 *
 * A base service class to handle generic db services accessed through the REST API.
 */
abstract class BaseDbSvc extends RestService
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var int|string
	 */
	protected $_resourceId;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	// REST interface implementation

	protected function _handleResource()
	{
		switch ( $this->_resource )
		{
			case '':
				switch ( $this->_action )
				{
					case self::Get:
						return $this->_listResources();
						break;
					default:
						return false;
				}
				break;
			case 'admin':
				return $this->_handleAdmin();
				break;
			default:
				switch ( $this->_action )
				{
					case self::Get:
						$this->validateTableAccess( $this->_resource, 'read' );
						// Most requests contain 'returned fields' parameter, all by default
						$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '*' );
						$extras = $this->gatherExtrasFromRequest();
						$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
						if ( empty( $this->_resourceId ) )
						{
							$ids = Utilities::getArrayValue( 'ids', $_REQUEST, '' );
							if ( !empty( $ids ) )
							{
								$result = $this->retrieveRecordsByIds( $this->_resource, $ids, $id_field, $fields, $extras );
								$result = array( 'record' => $result );
							}
							else
							{
								$data = RestRequest::getPostDataAsArray();
								if ( !empty( $data ) )
								{ // complex filters or large numbers of ids require post
									$ids = Utilities::getArrayValue( 'ids', $data, '' );
									if ( empty( $id_field ) )
									{
										$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
									}
									if ( !empty( $ids ) )
									{
										$result = $this->retrieveRecordsByIds( $this->_resource, $ids, $id_field, $fields, $extras );
										$result = array( 'record' => $result );
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
											$result = $this->retrieveRecords( $this->_resource, $records, $id_field, $fields, $extras );
											$result = array( 'record' => $result );
										}
										else
										{
											$filter = Utilities::getArrayValue( 'filter', $data, '' );
											$result = $this->retrieveRecordsByFilter( $this->_resource, $filter, $fields, $extras );
											if ( isset( $result['meta'] ) )
											{
												$meta = $result['meta'];
												unset( $result['meta'] );
												$result = array( 'record' => $result, 'meta' => $meta );
											}
											else
											{
												$result = array( 'record' => $result );
											}
										}
									}
								}
								else
								{
									$filter = Utilities::getArrayValue( 'filter', $_REQUEST, '' );
									$result = $this->retrieveRecordsByFilter( $this->_resource, $filter, $fields, $extras );
									if ( isset( $result['meta'] ) )
									{
										$meta = $result['meta'];
										unset( $result['meta'] );
										$result = array( 'record' => $result, 'meta' => $meta );
									}
									else
									{
										$result = array( 'record' => $result );
									}
								}
							}
						}
						else
						{
							// single entity by id
							$result = $this->retrieveRecordById( $this->_resource, $this->_resourceId, $id_field, $fields, $extras );
						}
						break;
					case self::Post:
						$data = RestRequest::getPostDataAsArray();
						$this->validateTableAccess( $this->_resource, 'create' );
						// Most requests contain 'returned fields' parameter
						$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
						$extras = $this->gatherExtrasFromRequest();
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
								throw new BadRequestException( 'No record in POST create request.' );
							}
							$result = $this->createRecord( $this->_resource, $data, $fields, $extras );
						}
						else
						{
							$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
							if ( !isset( $rollback ) )
							{
								$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
							}
							$result = $this->createRecords( $this->_resource, $records, $rollback, $fields, $extras );
							$result = array( 'record' => $result );
						}
						break;
					case self::Put:
						$data = RestRequest::getPostDataAsArray();
						$this->validateTableAccess( $this->_resource, 'update' );
						// Most requests contain 'returned fields' parameter
						$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
						$extras = $this->gatherExtrasFromRequest();
						$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
						if ( empty( $id_field ) )
						{
							$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
						}
						if ( empty( $this->_resourceId ) )
						{
							$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
							if ( !isset( $rollback ) )
							{
								$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
							}
							$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
							if ( empty( $ids ) )
							{
								$ids = Utilities::getArrayValue( 'ids', $data, '' );
							}
							if ( !empty( $ids ) )
							{
								$result = $this->updateRecordsByIds( $this->_resource, $ids, $data, $id_field, $rollback, $fields, $extras );
								$result = array( 'record' => $result );
							}
							else
							{
								$filter = ( isset( $_REQUEST['filter'] ) ) ? $_REQUEST['filter'] : null;
								if ( !isset( $filter ) )
								{
									$filter = Utilities::getArrayValue( 'filter', $data, null );
								}
								if ( isset( $filter ) )
								{
									$result = $this->updateRecordsByFilter( $this->_resource, $filter, $data, $fields, $extras );
									$result = array( 'record' => $result );
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
										$result = $this->updateRecord( $this->_resource, $data, $id_field, $fields, $extras );
									}
									else
									{
										$result = $this->updateRecords( $this->_resource, $records, $id_field, $rollback, $fields, $extras );
										$result = array( 'record' => $result );
									}
								}
							}
						}
						else
						{
							$result = $this->updateRecordById( $this->_resource, $data, $this->_resourceId, $id_field, $fields, $extras );
						}
						break;
					case self::Patch:
					case self::Merge:
						$data = RestRequest::getPostDataAsArray();
						$this->validateTableAccess( $this->_resource, 'update' );
						// Most requests contain 'returned fields' parameter
						$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
						$extras = $this->gatherExtrasFromRequest();
						$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
						if ( empty( $id_field ) )
						{
							$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
						}
						if ( empty( $this->_resourceId ) )
						{
							$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
							if ( !isset( $rollback ) )
							{
								$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
							}
							$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
							if ( empty( $ids ) )
							{
								$ids = Utilities::getArrayValue( 'ids', $data, '' );
							}
							if ( !empty( $ids ) )
							{
								$result = $this->mergeRecordsByIds( $this->_resource, $ids, $data, $id_field, $rollback, $fields, $extras );
								$result = array( 'record' => $result );
							}
							else
							{
								$filter = ( isset( $_REQUEST['filter'] ) ) ? $_REQUEST['filter'] : null;
								if ( !isset( $filter ) )
								{
									$filter = Utilities::getArrayValue( 'filter', $data, null );
								}
								if ( isset( $filter ) )
								{
									$result = $this->mergeRecordsByFilter( $this->_resource, $filter, $data, $fields, $extras );
									$result = array( 'record' => $result );
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
										$result = $this->mergeRecord( $this->_resource, $data, $id_field, $fields, $extras );
									}
									else
									{
										$result = $this->mergeRecords( $this->_resource, $records, $id_field, $rollback, $fields, $extras );
										$result = array( 'record' => $result );
									}
								}
							}
						}
						else
						{
							$result = $this->mergeRecordById( $this->_resource, $data, $this->_resourceId, $id_field, $fields, $extras );
						}
						break;
					case self::Delete:
						$data = RestRequest::getPostDataAsArray();
						$this->validateTableAccess( $this->_resource, 'delete' );
						// Most requests contain 'returned fields' parameter
						$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
						$extras = $this->gatherExtrasFromRequest();
						$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
						if ( empty( $id_field ) )
						{
							$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
						}
						if ( empty( $this->_resourceId ) )
						{
							$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
							if ( !isset( $rollback ) )
							{
								$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
							}
							$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
							if ( empty( $ids ) )
							{
								$ids = Utilities::getArrayValue( 'ids', $data, '' );
							}
							if ( !empty( $ids ) )
							{
								$result = $this->deleteRecordsByIds( $this->_resource, $ids, $id_field, $rollback, $fields, $extras );
								$result = array( 'record' => $result );
							}
							else
							{
								$filter = ( isset( $_REQUEST['filter'] ) ) ? $_REQUEST['filter'] : null;
								if ( !isset( $filter ) )
								{
									$filter = Utilities::getArrayValue( 'filter', $data, null );
								}
								if ( isset( $filter ) )
								{
									$result = $this->deleteRecordsByFilter( $this->_resource, $filter, $fields, $extras );
									$result = array( 'record' => $result );
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
											throw new BadRequestException( 'No record in DELETE request.' );
										}
										$result = $this->deleteRecord( $this->_resource, $data, $id_field, $fields, $extras );
									}
									else
									{
										$result = $this->deleteRecords( $this->_resource, $records, $id_field, $rollback, $fields, $extras );
										$result = array( 'record' => $result );
									}
								}
							}
						}
						else
						{
							$result = $this->deleteRecordById( $this->_resource, $this->_resourceId, $id_field, $fields, $extras );
						}
						break;
					default:
						return false;
				}
				break;
		}

		return $result;
	}

	protected function _handleAdmin()
	{
		switch ( $this->_action )
		{
			case self::Get:
				$this->validateTableAccess( $this->_resource, 'read' );
				if ( empty( $this->_resourceId ) )
				{
					$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
					if ( empty( $ids ) )
					{
						$data = RestRequest::getPostDataAsArray();
						$ids = Utilities::getArrayValue( 'ids', $data, '' );
					}

					$result = $this->getTables( $ids );
					$result = array( 'table' => $result );
				}
				else
				{
					// single table by name
					$result = $this->getTable( $this->_resourceId );
				}
				break;
			case self::Post:
				$data = RestRequest::getPostDataAsArray();
				$this->validateTableAccess( $this->_resource, 'create' );
				if ( empty( $this->_resourceId ) )
				{
					$tables = Utilities::getArrayValue( 'table', $data, null );
					if ( empty( $tables ) )
					{
						// xml to array conversion leaves them in plural wrapper
						$tables = ( isset( $data['tables']['table'] ) ) ? $data['tables']['table'] : null;
					}
					if ( empty( $tables ) )
					{
						$_name = Option::get( $data, 'name' );
						if ( empty( $_name ) )
						{
							throw new BadRequestException( 'No table name in POST create request.' );
						}
						$result = $this->createTable( $_name, $data );
					}
					else
					{
						$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
						if ( !isset( $rollback ) )
						{
							$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
						}
						$result = $this->createTables( $tables, $rollback );
						$result = array( 'table' => $result );
					}
				}
				else
				{
					// single table by name
					$result = $this->createTable( $this->_resourceId, $data );
				}
				break;
			case self::Put:
			case self::Patch:
			case self::Merge:
				$data = RestRequest::getPostDataAsArray();
				$this->validateTableAccess( $this->_resource, 'update' );
				if ( empty( $this->_resourceId ) )
				{
					$tables = Utilities::getArrayValue( 'table', $data, null );
					if ( empty( $tables ) )
					{
						// xml to array conversion leaves them in plural wrapper
						$tables = ( isset( $data['tables']['table'] ) ) ? $data['tables']['table'] : null;
					}
					if ( empty( $tables ) )
					{
						$_name = Option::get( $data, 'name' );
						if ( empty( $_name ) )
						{
							throw new BadRequestException( 'No table name in POST create request.' );
						}
						$result = $this->updateTable( $_name, $data );
					}
					else
					{
						$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
						if ( !isset( $rollback ) )
						{
							$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
						}
						$result = $this->updateTables( $tables, $rollback );
						$result = array( 'table' => $result );
					}
				}
				else
				{
					$result = $this->updateTable( $this->_resourceId, $data );
				}
				break;
			case self::Delete:
				$data = RestRequest::getPostDataAsArray();
				$this->validateTableAccess( $this->_resource, 'delete' );
				if ( empty( $this->_resourceId ) )
				{
					$rollback = ( isset( $_REQUEST['rollback'] ) ) ? DataFormat::boolval( $_REQUEST['rollback'] ) : null;
					if ( !isset( $rollback ) )
					{
						$rollback = DataFormat::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
					}
					$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
					if ( empty( $ids ) )
					{
						$ids = Utilities::getArrayValue( 'ids', $data, '' );
					}
					if ( !empty( $ids ) )
					{
						$result = $this->deleteTables( $ids, $rollback );
						$result = array( 'table' => $result );
					}
					else
					{
						$tables = Utilities::getArrayValue( 'table', $data, null );
						if ( empty( $tables ) )
						{
							// xml to array conversion leaves them in plural wrapper
							$tables = ( isset( $data['tables']['table'] ) ) ? $data['tables']['table'] : null;
						}
						if ( empty( $tables ) )
						{
							$_name = Option::get( $data, 'name' );
							if ( empty( $_name ) )
							{
								throw new BadRequestException( 'No table name in DELETE request.' );
							}
							$result = $this->deleteTable( $_name );
						}
						else
						{
							$result = $this->deleteTables( $tables, $rollback );
							$result = array( 'table' => $result );
						}
					}
				}
				else
				{
					$result = $this->deleteTable( $this->_resourceId );
				}
				break;
			default:
				return false;
		}

		return $result;
	}

	/**
	 *
	 */
	protected function _detectResourceMembers()
	{
		parent::_detectResourceMembers();

		$this->_resourceId = ( isset( $this->_resourceArray[1] ) ) ? $this->_resourceArray[1] : '';
	}

	protected function gatherExtrasFromRequest()
	{
		return array();
	}

	/**
	 * @param string $table
	 * @param string $access
	 *
	 * @throws BadRequestException
	 */
	protected function validateTableAccess( $table, $access = 'read' )
	{
		if ( empty( $table ) )
		{
			throw new BadRequestException( 'Table name can not be empty.' );
		}
		// finally check that the current user has privileges to access this table
		$this->checkPermission( $access, $table );
	}

	// Handle administrative options, table add, delete, etc

	/**
	 * Get multiple tables and their properties
	 *
	 * @param array $tables Table names
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function getTables( $tables = array() );

	/**
	 * Get any properties related to the table
	 *
	 * @param string $table Table name
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function getTable( $table );

	/**
	 * Create one or more tables by array of table properties
	 * @param array $tables
	 *
	 * @throws \Exception
	 */
	abstract public function createTables( $tables = array() );

	/**
	 * Create a single table by name, additional properties
	 *
	 * @param string $table
	 * @param array  $properties
	 *
	 * @throws \Exception
	 */
	abstract public function createTable( $table, $properties = array() );

	/**
	 * Update properties related to the table
	 *
	 * @param array $tables
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function updateTables( $tables = array() );

	/**
	 * Update properties related to the table
	 *
	 * @param string $table Table name
	 * @param array  $properties
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function updateTable( $table, $properties = array() );

	/**
	 * Delete multiple tables and all of their contents
	 *
	 * @param array $tables
	 * @param bool  $check_empty
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function deleteTables( $tables = array(), $check_empty = false );

	/**
	 * Delete the table and all of its contents
	 *
	 * @param string $table
	 * @param bool   $check_empty
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function deleteTable( $table, $check_empty = false );

	// Handle table record operations

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
	abstract public function createRecords( $table, $records, $rollback = false, $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function createRecord( $table, $record, $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function updateRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() );

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
	abstract public function updateRecord( $table, $record, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function updateRecordsByFilter( $table, $record, $filter = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id_list
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function updateRecordsByIds( $table, $record, $id_list, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function updateRecordById( $table, $record, $id, $id_field = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function mergeRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() );

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
	abstract public function mergeRecord( $table, $record, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function mergeRecordsByFilter( $table, $record, $filter = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $record
	 * @param        $id_list
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function mergeRecordsByIds( $table, $record, $id_list, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function mergeRecordById( $table, $record, $id, $id_field = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function deleteRecords( $table, $records, $id_field = '', $rollback = false, $fields = '', $extras = array() );

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
	abstract public function deleteRecord( $table, $record, $id_field = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function deleteRecordsByFilter( $table, $filter, $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $id_field
	 * @param bool   $rollback
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function deleteRecordsByIds( $table, $id_list, $id_field = '', $rollback = false, $fields = '', $extras = array() );

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
	abstract public function deleteRecordById( $table, $id, $id_field = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param mixed  $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function retrieveRecordsByFilter( $table, $filter, $fields = '', $extras = array()	);

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function retrieveRecords( $table, $records, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function retrieveRecord( $table, $record, $id_field = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @return array
	 */
	abstract public function retrieveRecordsByIds( $table, $id_list, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function retrieveRecordById( $table, $id, $id_field = '', $fields = '', $extras = array() );

}
