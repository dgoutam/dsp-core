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

use DreamFactory\Platform\Enums\PlatformServiceTypes;
use Platform\Utility\SwaggerUtilities;
use Swagger\Annotations as SWG;

/**
 * SystemService
 * DSP system administration manager
 *
 * @SWG\Resource(
 *   resourcePath="/system"
 * )
 *
 * @SWG\Model(id="Services",
 * @SWG\Property(name="record",type="Array",items="$ref:Service",description="Array of system service records.")
 * )
 * @SWG\Model(id="Service",
 * @SWG\Property(name="id",type="int",description="Identifier of this service."),
 * @SWG\Property(name="name",type="string",description="Displayable name of this service."),
 * @SWG\Property(name="api_name",type="string",description="Name of the service to use in API transactions."),
 * @SWG\Property(name="description",type="string",description="Description of this service."),
 * @SWG\Property(name="is_active",type="boolean",description="True if this service is active for use."),
 * @SWG\Property(name="is_system",type="boolean",description="True if this service is a default system service."),
 * @SWG\Property(name="type",type="string",description="One of the supported service types."),
 * @SWG\Property(name="storage_name",type="string",description="The local or remote storage name (i.e. root folder)."),
 * @SWG\Property(name="storage_type",type="string",description="They supported storage service type."),
 * @SWG\Property(name="credentials",type="string",description="Any credentials data required by the service."),
 * @SWG\Property(name="native_format",type="string",description="The format of the returned data of the service."),
 * @SWG\Property(name="base_url",type="string",description="The base URL for remote web services."),
 * @SWG\Property(name="parameters",type="string",description="Additional URL parameters required by the service."),
 * @SWG\Property(name="headers",type="string",description="Additional headers required by the service."),
 * @SWG\Property(name="apps",type="Array",items="$ref:App",description="Related apps by app to service assignment."),
 * @SWG\Property(name="roles",type="Array",items="$ref:Role",description="Related roles by service to role assignment."),
 * @SWG\Property(name="created_date",type="string",description="Date this service was created."),
 * @SWG\Property(name="created_by_id",type="int",description="User Id of who created this service."),
 * @SWG\Property(name="last_modified_date",type="string",description="Date this service was last modified."),
 * @SWG\Property(name="last_modified_by_id",type="int",description="User Id of who last modified this service.")
 * )
 *
 */
class SystemService extends SystemResource
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new SystemService
	 *
	 * @param array $resource_array
	 */
	public function __construct( $resource_array = array() )
	{
		$config = array(
			'service_name' => 'system',
			'name'         => 'Service',
			'api_name'     => 'service',
			'type'         => 'System',
			'description'  => 'System service administration.',
			'is_active'    => true,
		);

		parent::__construct( $config, $resource_array );
	}

	// Resource interface implementation

	// REST interface implementation

	/**
	 *
	 * @SWG\Api(
	 *             path="/system/service", description="Operations for service administration.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *             httpMethod="GET", summary="Retrieve multiple services.",
	 *             notes="Use the 'ids' or 'filter' parameter to limit records that are returned. Use the 'fields' and 'related' parameters to limit properties returned for each record. By default, all fields and no relations are returned for all records.",
	 *             responseClass="Services", nickname="getServices",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the records to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="filter", description="SQL-like filter to limit the records to retrieve.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="limit", description="Set to limit the filter results.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="int"
	 *           ),
	 * @SWG\Parameter(
	 *             name="order", description="SQL-like order containing field and direction for filter results.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="offset", description="Set to offset the filter results to a particular record count.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="int"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="include_count", description="Include the total number of filter results.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *           ),
	 * @SWG\Parameter(
	 *             name="include_schema", description="Include the schema of the table queried.",
	 *             paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="POST", summary="Create one or more services.",
	 *             notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *             responseClass="Success", nickname="createServices",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to create.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Services"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="PUT", summary="Update one or more services.",
	 *             notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *             responseClass="Success", nickname="updateServices",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Services"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="DELETE", summary="Delete one or more services.",
	 *             notes="Use 'ids' or post data should be a single record or an array of records (shown) containing an id. By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *             responseClass="Success", nickname="deleteServices",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the records to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to delete.",
	 *             paramType="body", required="false", allowMultiple=false, dataType="Services"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *     )
	 *   )
	 *
	 * @SWG\Api(
	 *             path="/system/service/{id}", description="Operations for individual service administration.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *             httpMethod="GET", summary="Retrieve one service by identifier.",
	 *             notes="Use the 'fields' and/or 'related' parameter to limit properties that are returned. By default, all fields and no relations are returned.",
	 *             responseClass="Service", nickname="getService",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="PUT", summary="Update one service.",
	 *             notes="Post data should be an array of fields for a single record. Use the 'fields' and/or 'related' parameter to return more properties. By default, the id is returned.",
	 *             responseClass="Success", nickname="updateService",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Service"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       ),
	 * @SWG\Operation(
	 *             httpMethod="DELETE", summary="Delete one service.",
	 *             notes="Use the 'fields' and/or 'related' parameter to return deleted properties. By default, the id is returned.",
	 *             responseClass="Success", nickname="deleteService",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="fields", description="Comma-delimited list of field names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 * @SWG\Parameter(
	 *             name="related", description="Comma-delimited list of related names to retrieve for each record.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *     )
	 *   )
	 *
	 * @return array|bool
	 */
	protected function _handleAction()
	{
		return parent::_handleAction();
	}

	/**
	 * @param mixed $results
	 */
	protected function _postProcess( $results = null )
	{
		if ( static::Get != $this->_action )
		{
			// clear swagger cache upon any service changes.
			SwaggerUtilities::clearCache();
		}

		parent::_postProcess( $results );
	}
}
