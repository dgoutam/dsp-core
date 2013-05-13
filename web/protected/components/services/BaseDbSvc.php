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
use Swagger\Annotations as SWG;

/**
 * BaseDbSvc
 * A base service class to handle generic db services accessed through the REST API.
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.1",
 *   basePath="http://localhost/rest",
 *   resourcePath="/{sql_db}"
 * )
 *
 * @SWG\Model(id="Records",
 *   @SWG\Property(name="record",type="Array",items="$ref:Record",description="Array of records of the given resource."),
 *   @SWG\Property(name="meta",type="MetaData",description="Available meta data for the response.")
 * )
 * @SWG\Model(id="Record",
 *   @SWG\Property(name="field",type="Array",items="$ref:string",description="Example field name-value pairs."),
 *   @SWG\Property(name="related",type="Array",items="$ref:string",description="Example related records.")
 * )
 *
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

	/**
	 * @SWG\Api(
	 *   path="/{sql_db}", description="Operations available for SQL database tables.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="List resources available for database schema.",
	 *       notes="See listed operations for each resource available.",
	 *       responseClass="Resources", nickname="getResources"
	 *     )
	 *   )
	 * )
	 * @SWG\Api(
	 *   path="/{sql_db}/{table_name}", description="Operations for table records administration.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve multiple records.",
	 *         notes="Use the 'ids' or 'filter' parameter to limit resources that are returned. Use the 'fields' and 'related' parameters to limit properties returned for each resource. By default, all fields and no relations are returned for all resources.",
	 *         responseClass="Records", nickname="getRecords",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the resources to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="filter", description="SQL-like filter to limit the resources to retrieve.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="limit", description="Set to limit the filter results.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="int"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="order", description="SQL-like order containing field and direction for filter results.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="offset", description="Set to offset the filter results to a particular record count.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="int"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="include_count", description="Include the total number of filter results.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="include_schema", description="Include the schema of the table queried.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 *       @SWG\Operation(
	 *         httpMethod="POST", summary="Create one or more records.",
	 *         notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="createRecords",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to create.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Records"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 *       @SWG\Operation(
	 *         httpMethod="PUT", summary="Update one or more records.",
	 *         notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="updateRecords",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Records"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 *       @SWG\Operation(
	 *         httpMethod="DELETE", summary="Delete one or more records.",
	 *         notes="Use 'ids' or post data should be a single record or an array of records (shown) containing an id. By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="deleteRecords",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the resources to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to delete.",
	 *             paramType="body", required="false", allowMultiple=false, dataType="Records"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *   )
	 * )
	 *
	 * @SWG\Api(
	 *   path="/{sql_db}/{table_name}/{id}", description="Operations for single record administration.",
	 *   @SWG\Operations(
	 *       @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve one record by identifier.",
	 *         notes="Use the 'fields' and/or 'related' parameter to limit properties that are returned. By default, all fields and no relations are returned.",
	 *         responseClass="Record", nickname="getRecord",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the resource to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 *       @SWG\Operation(
	 *         httpMethod="PUT", summary="Update one record by identifier.",
	 *         notes="Post data should be an array of fields for a single record. Use the 'fields' and/or 'related' parameter to return more properties. By default, the id is returned.",
	 *         responseClass="Success", nickname="updateUser",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the resource to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Apps"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 *       @SWG\Operation(
	 *         httpMethod="DELETE", summary="Delete one record by identifier.",
	 *         notes="Use the 'fields' and/or 'related' parameter to return deleted properties. By default, the id is returned.",
	 *         responseClass="Success", nickname="deleteUser",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="table_name", description="Name of the table to perform operations on.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the resource to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 *         @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *     )
	 *   )
	 *
	 * @return array|bool
	 * @throws Exception
	 */
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
