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

use Swagger\Annotations as SWG;

/**
 * NoSqlDbSvc.php
 * A service to handle NoSQL (schema-less) database services accessed through the REST API.
 *
 * @SWG\Resource(
 *   resourcePath="/{nosql_db}"
 * )
 * @SWG\Api(
 *   path="/{nosql_db}", description="Operations available for NoSQL database tables.",
 *   @SWG\Operations(
 *     @SWG\Operation(
 *       httpMethod="GET", summary="List all tables.",
 *       notes="List the names of the available tables in this storage. Use 'include_properties' to include any properties of the tables.",
 *       responseClass="NoSqlTables", nickname="getTables",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="include_properties", description="Return all properties of the tables, if any.",
 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
 *         ),
 *         @SWG\Parameter(
 *           name="ids", description="Comma-delimited list of the identifiers of the resources to retrieve.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="filter", description="SQL-like filter to limit the resources to retrieve.",
 *           paramType="query", required="false", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="limit", description="Set to limit the filter results.",
 *           paramType="query", required="false", allowMultiple=false, dataType="int"
 *         ),
 *         @SWG\Parameter(
 *           name="include_count", description="Include the total number of filter results.",
 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="POST", summary="Create one or more tables.",
 *       notes="Post data should be a single table definition or an array of table definitions.",
 *       responseClass="NoSqlTables", nickname="createTables",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="tables", description="Array of tables to create.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlTables"
 *         ),
 *         @SWG\Parameter(
 *           name="check_exist", description="If true, the request fails when the table to create already exists.",
 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="PATCH", summary="Update properties of one or more tables.",
 *       notes="Post data should be a single table definition or an array of table definitions.",
 *       responseClass="NoSqlTables", nickname="updateTables",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="tables", description="Array of tables to create.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlTables"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="DELETE", summary="Delete one or more tables.",
 *       notes="Post data should be a single table definition or an array of table definitions.",
 *       responseClass="NoSqlTables", nickname="deleteTables",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="tables", description="Array of tables to delete.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlTables"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     )
 *   )
 * )
 *
 * @SWG\Api(
 *   path="/{nosql_db}/{table_name}", description="Operations for table records administration.",
 *   @SWG\Operations(
 *     @SWG\Operation(
 *       httpMethod="GET", summary="Retrieve multiple records.",
 *       notes="Use the 'ids' or 'filter' parameter to limit resources that are returned. Use the 'fields' parameter to limit properties returned for each resource. By default, all fields are returned for all resources.",
 *       responseClass="NoSqlRecords", nickname="getRecords",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="include_properties", description="Return any properties or metadata available for the table.",
 *           paramType="query", required="false", allowMultiple=true, dataType="boolean"
 *         ),
 *         @SWG\Parameter(
 *           name="ids", description="Comma-delimited list of the identifiers of the resources to retrieve.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="filter", description="SQL-like filter to limit the resources to retrieve.",
 *           paramType="query", required="false", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="limit", description="Set to limit the filter results.",
 *           paramType="query", required="false", allowMultiple=false, dataType="int"
 *         ),
 *         @SWG\Parameter(
 *           name="order", description="SQL-like order containing field and direction for filter results.",
 *           paramType="query", required="false", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="include_count", description="Include the total number of filter results.",
 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="POST", summary="Create one or more records.",
 *       notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' to return more info.",
 *       responseClass="NoSqlRecords", nickname="createRecords",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="Data containing name-value pairs of records to create.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlRecords"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="PUT", summary="Update (replace) one or more records.",
 *       notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' to return more info.",
 *       responseClass="NoSqlRecords", nickname="updateRecords",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="Data containing name-value pairs of records to update.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlRecords"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="PATCH", summary="Update (merge) one or more records.",
 *       notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' to return more info.",
 *       responseClass="NoSqlRecords", nickname="mergeRecords",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="Data containing name-value pairs of records to update.",
 *           paramType="body", required="true", allowMultiple=false, dataType="Table"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="DELETE", summary="Delete one or more records.",
 *       notes="Use 'ids' or post data should be a single record or an array of records (shown) containing an id. By default, only the id property of the record is returned on success, use 'fields' to return more info.",
 *       responseClass="NoSqlRecords", nickname="deleteRecords",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="ids", description="Comma-delimited list of the identifiers of the resources to retrieve.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="Data containing name-value pairs of records to delete.",
 *           paramType="body", required="false", allowMultiple=false, dataType="NoSqlRecords"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     )
 *   )
 * )
 *
 * @SWG\Api(
 *   path="/{nosql_db}/{table_name}/{id}", description="Operations for single record administration.",
 *   @SWG\Operations(
 *     @SWG\Operation(
 *       httpMethod="GET", summary="Retrieve one record by identifier.",
 *       notes="Use the 'fields' parameter to limit properties that are returned. By default, all fields are returned.",
 *       responseClass="NoSqlRecord", nickname="getRecord",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="id", description="Identifier of the resource to retrieve.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="properties_only", description="Return just the properties of the record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="boolean"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table or record does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="POST", summary="Create one record by identifier.",
 *       notes="Post data should be an array of fields for a single record. Use the 'fields' parameter to return more properties. By default, the id is returned.",
 *       responseClass="NoSqlRecord", nickname="createRecord",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="id", description="Identifier of the resource to create.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="Data containing name-value pairs of records to create.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlRecord"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="PUT", summary="Update (replace) one record by identifier.",
 *       notes="Post data should be an array of fields for a single record. Use the 'fields' parameter to return more properties. By default, the id is returned.",
 *       responseClass="NoSqlRecord", nickname="updateRecord",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="id", description="Identifier of the resource to retrieve.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="Data containing name-value pairs of records to update.",
 *           paramType="body", required="true", allowMultiple=false, dataType="NoSqlRecord"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table or record does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="PATCH", summary="Update (merge) one record by identifier.",
 *       notes="Post data should be an array of fields for a single record. Use the 'fields' parameter to return more properties. By default, the id is returned.",
 *       responseClass="NoSqlRecord", nickname="mergeRecord",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="The name of the table you want to update.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="id", description="Identifier of the resource to update.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="record", description="An array of record properties.",
 *           paramType="body", required="true", allowMultiple=false, dataType="Table"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table or record does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     ),
 *     @SWG\Operation(
 *       httpMethod="DELETE", summary="Delete one record by identifier.",
 *       notes="Use the 'fields' parameter to return deleted properties. By default, the id is returned.",
 *       responseClass="NoSqlRecord", nickname="deleteRecord",
 *       @SWG\Parameters(
 *         @SWG\Parameter(
 *           name="table_name", description="Name of the table to perform operations on.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="id", description="Identifier of the resource to retrieve.",
 *           paramType="path", required="true", allowMultiple=false, dataType="string"
 *         ),
 *         @SWG\Parameter(
 *           name="fields", description="Comma-delimited list of field names to retrieve for each record.",
 *           paramType="query", required="false", allowMultiple=true, dataType="string"
 *         )
 *       ),
 *       @SWG\ErrorResponses(
 *         @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
 *         @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
 *         @SWG\ErrorResponse(code="404", reason="Not Found - Requested table or record does not exist."),
 *         @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
 *       )
 *     )
 *   )
 * )
 *
 * @SWG\Model(id="NoSqlTables",
 *   @SWG\Property(name="table",type="Array",items="$ref:NoSqlTable",description="Array of tables and their properties.")
 * )
 * @SWG\Model(id="NoSqlTable",
 *   @SWG\Property(name="name",type="string",description="Name of the table.")
 * )
 *
 * @SWG\Model(id="NoSqlRecords",
 *   @SWG\Property(name="record",type="Array",items="$ref:NoSqlRecord",description="Array of records of the given resource."),
 *   @SWG\Property(name="meta",type="MetaData",description="Available meta data for the response.")
 * )
 * @SWG\Model(id="NoSqlRecord",
 *   @SWG\Property(name="field",type="Array",items="$ref:string",description="Example field name-value pairs.")
 * )
 *
 */
abstract class NoSqlDbSvc extends BaseDbSvc
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * General method for creating a pseudo-random identifier
	 * @param string $table Name of the table where the item will be stored
	 *
	 * @return string
	 */
	protected static function createItemId( $table )
	{
		$_randomTime = abs( time() );
		if ( $_randomTime == 0 )
		{
			$_randomTime = 1;
		}
		$_random1 = rand( 1, $_randomTime );
		$_random2 = rand( 1, 2000000000 );
		$_generateId = strtolower( md5( $_random1 . $table . $_randomTime . $_random2 ) );
		$_randSmall = rand( 10, 99 );

		return $_generateId . $_randSmall;
	}
}
