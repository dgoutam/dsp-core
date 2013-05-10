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
 * BaseDbSvc
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

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		$apis = array(
			array(
				'path'        => '/' . $this->_apiName . '/{table_name}',
				'description' => 'Operations for per table administration.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve multiple records",
						"notes"          => "Use the 'ids' or 'filter' parameter to limit records that are returned.",
						"responseClass"  => "array",
						"nickname"       => "getRecords",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'ids',
								 'filter',
								 'limit',
								 'offset',
								 'order',
								 'include_count',
								 'include_schema',
								 'fields',
								 'related'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Create one or more records",
						"notes"          => "Post data should be an array of fields for a single record or an array of records",
						"responseClass"  => "array",
						"nickname"       => "createRecords",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'fields',
								 'related',
								 'record'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one or more records",
						"notes"          => "Post data should be an array of fields for a single record or an array of records",
						"responseClass"  => "array",
						"nickname"       => "updateRecords",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'fields',
								 'related',
								 'record'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete one or more records",
						"notes"          => "Use the 'ids' or 'filter' parameter to limit resources that are deleted.",
						"responseClass"  => "array",
						"nickname"       => "deleteRecords",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'ids',
								 'filter',
								 'fields',
								 'related'
							)
						),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $this->_apiName . '/{table_name}/{id}',
				'description' => 'Operations for single record administration.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve one record by identifier",
						"notes"          => "Use the 'fields' and/or 'related' parameter to limit properties that are returned.",
						"responseClass"  => "array",
						"nickname"       => "getRecord",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'id',
								 'fields',
								 'related'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one record by identifier",
						"notes"          => "Post data should be an array of fields for a single record",
						"responseClass"  => "array",
						"nickname"       => "updateRecord",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'id',
								 'fields',
								 'related',
								 'record'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete one record by identifier",
						"notes"          => "Use the 'fields' and/or 'related' parameter to return properties that are deleted.",
						"responseClass"  => "array",
						"nickname"       => "deleteRecord",
						"parameters"     => SwaggerUtilities::getParameters(
							array(
								 'table_name',
								 'id',
								 'fields',
								 'related'
							)
						),
						"errorResponses" => array()
					),
				)
			),
		);
		$apis = array_merge( parent::getSwaggerApis(), $apis );

		return $apis;
	}

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
								$data = Utilities::getPostDataAsArray();
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
											$limit = intval( Utilities::getArrayValue( 'limit', $data, 0 ) );
											$order = Utilities::getArrayValue( 'order', $data, '' );
											$offset = intval( Utilities::getArrayValue( 'offset', $data, 0 ) );
											$include_count = Utilities::boolval( Utilities::getArrayValue( 'include_count', $data, false ) );
											$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $data, false ) );
											$result = $this->retrieveRecordsByFilter(
												$this->_resource,
												$fields,
												$filter,
												$limit,
												$order,
												$offset,
												$include_count,
												$include_schema,
												$extras
											);
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
									$limit = intval( Utilities::getArrayValue( 'limit', $_REQUEST, 0 ) );
									$order = Utilities::getArrayValue( 'order', $_REQUEST, '' );
									$offset = intval( Utilities::getArrayValue( 'offset', $_REQUEST, 0 ) );
									$include_count = Utilities::boolval( Utilities::getArrayValue( 'include_count', $_REQUEST, false ) );
									$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $_REQUEST, false ) );
									$result = $this->retrieveRecordsByFilter(
										$this->_resource,
										$fields,
										$filter,
										$limit,
										$order,
										$offset,
										$include_count,
										$include_schema,
										$extras
									);
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
						$data = Utilities::getPostDataAsArray();
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
								throw new Exception( 'No record in POST create request.', ErrorCodes::BAD_REQUEST );
							}
							$result = $this->createRecord( $this->_resource, $data, $fields, $extras );
						}
						else
						{
							$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
							if ( !isset( $rollback ) )
							{
								$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
							}
							$result = $this->createRecords( $this->_resource, $records, $rollback, $fields, $extras );
							$result = array( 'record' => $result );
						}
						break;
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = Utilities::getPostDataAsArray();
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
											throw new Exception( 'No record in PUT update request.', ErrorCodes::BAD_REQUEST );
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
					case self::Delete:
						$data = Utilities::getPostDataAsArray();
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
											throw new Exception( 'No record in DELETE request.', ErrorCodes::BAD_REQUEST );
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
	 * @throws Exception
	 */
	protected function validateTableAccess( $table, $access = 'read' )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		// finally check that the current user has privileges to access this table
		$this->checkPermission( $access, $table );
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
	abstract public function createRecords( $table, $records, $rollback = false, $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 * @return array
	 */
	abstract public function deleteRecord( $table, $record, $id_field = '', $fields = '', $extras = array() );

	/**
	 * @param        $table
	 * @param        $filter
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 * @return array
	 */
	abstract public function deleteRecordById( $table, $id, $id_field = '', $fields = '', $extras = array() );

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
	abstract public function retrieveRecordsByFilter(
		$table,
		$fields = '',
		$filter = '',
		$limit = 0,
		$order = '',
		$offset = 0,
		$include_count = false,
		$include_schema = false,
		$extras = array()
	);

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $id_field
	 * @param string $fields
	 * @param array  $extras
	 *
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 * @return array
	 */
	abstract public function retrieveRecordById( $table, $id, $id_field = '', $fields = '', $extras = array() );

}
