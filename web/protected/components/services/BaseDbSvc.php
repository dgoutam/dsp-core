<?php

/**
 * BaseDbSvc.php
 * A base service class to handle generic db services accessed through the REST API.
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2009-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
abstract class BaseDbSvc extends BaseService
{

	// Members

	/**
	 * @var
	 */
	protected $tableName;

	/**
	 * @var
	 */
	protected $recordId;

	/**
	 * Create a new BaseDbSvc
	 *
	 * @param array $config
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
	}

	/**
	 * Swagger output for common api parameters
	 *
	 * @param        $parameters
	 * @param string $method
	 *
	 * @return array
	 */
	public static function swaggerParameters( $parameters, $method = '' )
	{
		$swagger = array();
		foreach ( $parameters as $param )
		{
			switch ( $param )
			{
				case 'table_name':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the table to perform operations on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'field_name':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the table field/column to perform operations on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'id':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Identifier of the resource to retrieve.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'ids':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Comma-delimited list of the identifiers of the resources to retrieve.",
						"dataType"      => "String",
						"required"      => false,
						"allowMultiple" => true
					);
					break;
				case 'filter':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "SQL-like filter to limit the resources to retrieve.",
						"dataType"      => "String",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'order':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "SQL-like order containing field and direction for filter results.",
						"dataType"      => "String",
						"required"      => false,
						"allowMultiple" => true
					);
					break;
				case 'limit':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Set to limit the filter results.",
						"dataType"      => "int",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'include_count':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Include the total number of filter results.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'include_schema':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Include the schema of the table queried.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'fields':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Comma-delimited list of field names to retrieve for each record.",
						"dataType"      => "String",
						"required"      => false,
						"allowMultiple" => true
					);
					break;
				case 'related':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Comma-delimited list of related names to retrieve for each record.",
						"dataType"      => "string",
						"required"      => false,
						"allowMultiple" => true
					);
					break;
				case 'record':
					$swagger[] = array(
						"paramType"     => "body",
						"name"          => $param,
						"description"   => "Array of record properties.",
						"dataType"      => "array",
						"required"      => true,
						"allowMultiple" => true
					);
					break;
			}
		}

		return $swagger;
	}

	/**
	 * @param string $service
	 * @param string $description
	 *
	 * @return array
	 */
	public static function swaggerPerDb( $service, $description = '' )
	{
		$swagger = array(
			array(
				'path'        => '/' . $service,
				'description' => $description,
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "List tables available in the database service",
						"notes"          => "Use the table names in available record operations.",
						"responseClass"  => "array",
						"nickname"       => "getTables",
						"parameters"     => array(),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $service . '/{table_name}',
				'description' => 'Operations for per table administration.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve multiple records",
						"notes"          => "Use the 'ids' or 'filter' parameter to limit records that are returned.",
						"responseClass"  => "array",
						"nickname"       => "getRecords",
						"parameters"     => static::swaggerParameters(
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
						"parameters"     => static::swaggerParameters(
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
						"parameters"     => static::swaggerParameters(
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
						"parameters"     => static::swaggerParameters(
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
				'path'        => '/' . $service . '/{table_name}/{id}',
				'description' => 'Operations for single record administration.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve one record by identifier",
						"notes"          => "Use the 'fields' and/or 'related' parameter to limit properties that are returned.",
						"responseClass"  => "array",
						"nickname"       => "getRecord",
						"parameters"     => static::swaggerParameters( array( 'table_name', 'id', 'fields', 'related' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one record by identifier",
						"notes"          => "Post data should be an array of fields for a single record",
						"responseClass"  => "array",
						"nickname"       => "updateRecord",
						"parameters"     => static::swaggerParameters(
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
						"parameters"     => static::swaggerParameters( array( 'table_name', 'id', 'fields', 'related' ) ),
						"errorResponses" => array()
					),
				)
			),
		);

		return $swagger;
	}

	// Controller based methods

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionSwagger()
	{
		try
		{
			$this->detectCommonParams();

			$result = parent::actionSwagger();
			$resources = static::swaggerPerDb( $this->_apiName, $this->_description );
			$result['apis'] = $resources;

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @throws Exception
	 * @return array
	 */
	public function actionGet()
	{
		$this->detectCommonParams();
		switch ( strtolower( $this->tableName ) )
		{
			case '':
				$result = array( 'resource' => array() );
				break;
			default:
				$this->validateTableAccess( $this->tableName, 'read' );
				// Most requests contain 'returned fields' parameter, all by default
				$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '*' );
				$extras = $this->gatherExtrasFromRequest();
				$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
				if ( empty( $this->recordId ) )
				{
					$ids = Utilities::getArrayValue( 'ids', $_REQUEST, '' );
					if ( !empty( $ids ) )
					{
						$result = $this->retrieveRecordsByIds( $this->tableName, $ids, $id_field, $fields, $extras );
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
								$result = $this->retrieveRecordsByIds( $this->tableName, $ids, $id_field, $fields, $extras );
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
									$result = $this->retrieveRecords( $this->tableName, $records, $id_field, $fields, $extras );
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
										$this->tableName,
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
								$this->tableName,
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
					$result = $this->retrieveRecordById( $this->tableName, $this->recordId, $id_field, $fields, $extras );
				}
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( strtolower( $this->tableName ) )
		{
			case '':
				// batch support for multiple tables
				throw new Exception( 'Multi-table batch request not yet implemented.' );
				break;
			default:
				$this->validateTableAccess( $this->tableName, 'create' );
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
					$result = $this->createRecord( $this->tableName, $data, $fields, $extras );
				}
				else
				{
					$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
					if ( !isset( $rollback ) )
					{
						$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
					}
					$result = $this->createRecords( $this->tableName, $records, $rollback, $fields, $extras );
					$result = array( 'record' => $result );
				}
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( strtolower( $this->tableName ) )
		{
			case '':
				// batch support for multiple tables
				throw new Exception( 'Multi-table batch request not yet implemented.' );
				break;
			default:
				$this->validateTableAccess( $this->tableName, 'update' );
				// Most requests contain 'returned fields' parameter
				$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
				$extras = $this->gatherExtrasFromRequest();
				$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
				if ( empty( $id_field ) )
				{
					$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
				}
				if ( empty( $this->recordId ) )
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
						$result = $this->updateRecordsByIds( $this->tableName, $ids, $data, $id_field, $rollback, $fields, $extras );
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
							$result = $this->updateRecordsByFilter( $this->tableName, $filter, $data, $fields, $extras );
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
								$result = $this->updateRecord( $this->tableName, $data, $id_field, $fields, $extras );
							}
							else
							{
								$result = $this->updateRecords( $this->tableName, $records, $id_field, $rollback, $fields, $extras );
								$result = array( 'record' => $result );
							}
						}
					}
				}
				else
				{
					$result = $this->updateRecordById( $this->tableName, $data, $this->recordId, $id_field, $fields, $extras );
				}
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( strtolower( $this->tableName ) )
		{
			case '':
				// batch support for multiple tables
				throw new Exception( 'Multi-table batch request not yet implemented.' );
				break;
			default:
				$this->validateTableAccess( $this->tableName, 'update' );
				// Most requests contain 'returned fields' parameter
				$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
				$extras = $this->gatherExtrasFromRequest();
				$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
				if ( empty( $id_field ) )
				{
					$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
				}
				if ( empty( $this->recordId ) )
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
						$result = $this->updateRecordsByIds( $this->tableName, $ids, $data, $id_field, $rollback, $fields, $extras );
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
							$result = $this->updateRecordsByFilter( $this->tableName, $filter, $data, $fields, $extras );
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
									throw new Exception( 'No record in MERGE update request.', ErrorCodes::BAD_REQUEST );
								}
								$result = $this->updateRecord( $this->tableName, $data, $id_field, $fields, $extras );
							}
							else
							{
								$result = $this->updateRecords( $this->tableName, $records, $id_field, $rollback, $fields, $extras );
								$result = array( 'record' => $result );
							}
						}
					}
				}
				else
				{
					$result = $this->updateRecordById( $this->tableName, $data, $this->recordId, $id_field, $fields, $extras );
				}
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( strtolower( $this->tableName ) )
		{
			case '':
				// batch support for multiple tables
				throw new Exception( 'Multi-table batch request not yet implemented.', ErrorCodes::BAD_REQUEST );
				break;
			default:
				$this->validateTableAccess( $this->tableName, 'delete' );
				// Most requests contain 'returned fields' parameter
				$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
				$extras = $this->gatherExtrasFromRequest();
				$id_field = Utilities::getArrayValue( 'id_field', $_REQUEST, '' );
				if ( empty( $id_field ) )
				{
					$id_field = Utilities::getArrayValue( 'id_field', $data, '' );
				}
				if ( empty( $this->recordId ) )
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
						$result = $this->deleteRecordsByIds( $this->tableName, $ids, $id_field, $rollback, $fields, $extras );
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
							$result = $this->deleteRecordsByFilter( $this->tableName, $filter, $fields, $extras );
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
								$result = $this->deleteRecord( $this->tableName, $data, $id_field, $fields, $extras );
							}
							else
							{
								$result = $this->deleteRecords( $this->tableName, $records, $id_field, $rollback, $fields, $extras );
								$result = array( 'record' => $result );
							}
						}
					}
				}
				else
				{
					$result = $this->deleteRecordById( $this->tableName, $this->recordId, $id_field, $fields, $extras );
				}
				break;
		}

		return $result;
	}

	protected function gatherExtrasFromRequest()
	{

		return array();
	}

	/**
	 *
	 */
	protected function detectCommonParams()
	{
		$resource = Utilities::getArrayValue( 'resource', $_GET, '' );
		$resource = ( !empty( $resource ) ) ? explode( '/', $resource ) : array();
		$this->tableName = ( isset( $resource[0] ) ) ? $resource[0] : '';
		$this->recordId = ( isset( $resource[1] ) ) ? $resource[1] : '';
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
