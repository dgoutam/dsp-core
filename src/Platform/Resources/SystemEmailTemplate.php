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
namespace Platform\Resources;

use Swagger\Annotations as SWG;

/**
 * SystemEmailTemplate
 * DSP system administration manager
 *
 * @SWG\Resource(
 *   resourcePath="/system"
 * )
 *
 * @SWG\Model(id="EmailTemplates",
 *   @SWG\Property(name="record",type="Array",items="$ref:EmailTemplate",description="Array of system email template records.")
 * )
 * @SWG\Model(id="EmailTemplate",
 *   @SWG\Property(name="id",type="int",description="Identifier of this email template."),
 *   @SWG\Property(name="name",type="string",description="Displayable name of this email template."),
 *   @SWG\Property(name="description",type="string",description="Description of this email template."),
 *   @SWG\Property(name="to",type="Array",items="$ref:EmailAddress",description="Single or multiple receiver addresses."),
 *   @SWG\Property(name="cc",type="Array",items="$ref:EmailAddress",description="Optional CC receiver addresses."),
 *   @SWG\Property(name="bcc",type="Array",items="$ref:EmailAddress",description="Optional BCC receiver addresses."),
 *   @SWG\Property(name="subject",type="string",description="Text only subject line."),
 *   @SWG\Property(name="body_text",type="string",description="Text only version of the body."),
 *   @SWG\Property(name="body_html",type="string",description="Escaped HTML version of the body."),
 *   @SWG\Property(name="from",type="EmailAddress",description="Required sender name and email."),
 *   @SWG\Property(name="reply_to",type="EmailAddress",description="Optional reply to name and email."),
 *   @SWG\Property(name="defaults",type="Array",items="$ref:string",description="Array of default name value pairs for template replacement."),
 *   @SWG\Property(name="created_date",type="string",description="Date this email template was created."),
 *   @SWG\Property(name="created_by_id",type="int",description="User Id of who created this email template."),
 *   @SWG\Property(name="last_modified_date",type="string",description="Date this email template was last modified."),
 *   @SWG\Property(name="last_modified_by_id",type="int",description="User Id of who last modified this email template.")
 * )
 *
 */
class SystemEmailTemplate extends SystemResource
{
	//*************************************************************************
	//	Constants
	//*************************************************************************


	//*************************************************************************
	//	Members
	//*************************************************************************


	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new SystemEmailTemplate
	 *
	 *
	 */
	public function __construct( $resource_array = array() )
	{
		$config = array(
			'service_name'=> 'system',
			'name'        => 'Email Template',
			'api_name'    => 'email_template',
			'type'        => 'System',
			'description' => 'System email template administration.',
			'is_active'   => true,
		);

		parent::__construct( $config, $resource_array );
	}

	// Resource interface implementation

	// REST interface implementation

	/**
	 *
	 *   @SWG\Api(
	 *     path="/system/email_template", description="Operations for email template administration.",
	 *     @SWG\Operations(
	 *       @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve multiple email templates.",
	 *         notes="Use the 'ids' or 'filter' parameter to limit records that are returned. Use the 'fields' and 'related' parameters to limit properties returned for each record. By default, all fields and no relations are returned for all records.",
	 *         responseClass="EmailTemplates", nickname="getEmailTemplates",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the records to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="filter", description="SQL-like filter to limit the records to retrieve.",
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
	 *         httpMethod="POST", summary="Create one or more email templates.",
	 *         notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="createEmailTemplates",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to create.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="EmailTemplates"
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
	 *         httpMethod="PUT", summary="Update one or more email templates.",
	 *         notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="updateEmailTemplates",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="EmailTemplates"
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
	 *         httpMethod="DELETE", summary="Delete one or more email templates.",
	 *         notes="Use 'ids' or post data should be a single record or an array of records (shown) containing an id. By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="deleteEmailTemplates",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the records to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to delete.",
	 *             paramType="body", required="false", allowMultiple=false, dataType="EmailTemplates"
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
	 *   @SWG\Api(
	 *     path="/system/email_template/{id}", description="Operations for individual email template administration.",
	 *     @SWG\Operations(
	 *       @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve one application by identifier.",
	 *         notes="Use the 'fields' and/or 'related' parameter to limit properties that are returned. By default, all fields and no relations are returned.",
	 *         responseClass="EmailTemplate", nickname="getEmailTemplate",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
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
	 *         httpMethod="PUT", summary="Update one email template.",
	 *         notes="Post data should be an array of fields for a single record. Use the 'fields' and/or 'related' parameter to return more properties. By default, the id is returned.",
	 *         responseClass="Success", nickname="updateEmailTemplate",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="EmailTemplate"
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
	 *         httpMethod="DELETE", summary="Delete one email template.",
	 *         notes="Use the 'fields' and/or 'related' parameter to return deleted properties. By default, the id is returned.",
	 *         responseClass="Success", nickname="deleteEmailTemplate",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
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
	 */
	protected function _handleAction()
	{
		return parent::_handleAction();
	}

}
