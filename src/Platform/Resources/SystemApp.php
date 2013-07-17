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

use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;
use Platform\Utility\DataFormat;
use Platform\Utility\FileUtilities;
use Platform\Utility\ServiceHandler;
use Platform\Utility\SwaggerUtilities;
use Platform\Utility\Utilities;
use Swagger\Annotations as SWG;

/**
 * SystemApp
 * DSP system administration manager
 *
 * @SWG\Resource(
 *   resourcePath="/system"
 * )
 *
 * @SWG\Model(id="Apps",
 *   @SWG\Property(name="record",type="Array",items="$ref:App",description="Array of system application records.")
 * )
 * @SWG\Model(id="App",
 *   @SWG\Property(name="id",type="int",description="Identifier of this application."),
 *   @SWG\Property(name="name",type="string",description="Displayable name of this application."),
 *   @SWG\Property(name="api_name",type="string",description="Name of the application to use in API transactions."),
 *   @SWG\Property(name="description",type="string",description="Description of this application."),
 *   @SWG\Property(name="is_active",type="boolean",description="Is this system application active for use."),
 *   @SWG\Property(name="url",type="string",description="URL for accessing this application."),
 *   @SWG\Property(name="is_url_external",type="boolean",description="True when this application is hosted elsewhere."),
 *   @SWG\Property(name="imported_url",type="string",description="If imported, the url of where the code originated."),
 *   @SWG\Property(name="storage_service_id",type="string",description="If locally stored, the storage service identifier."),
 *   @SWG\Property(name="storage_container",type="string",description="If locally stored, the container of the storage service."),
 *   @SWG\Property(name="requires_fullscreen",type="boolean",description="True when this app needs to hide launchpad."),
 *   @SWG\Property(name="allow_fullscreen_toggle",type="boolean",description="True to allow launchpad access via toggle."),
 *   @SWG\Property(name="toggle_location",type="string",description="Screen location for toggle placement."),
 *   @SWG\Property(name="requires_plugin",type="boolean",description="True when the app relies on a browser plugin."),
 *   @SWG\Property(name="roles_default_app",type="Array",items="$ref:string",description="Related roles by Role.default_app_id."),
 *   @SWG\Property(name="users_default_app",type="Array",items="$ref:string",description="Related users by User.default_app_id."),
 *   @SWG\Property(name="app_groups",type="Array",items="$ref:string",description="Related groups by app to group assignment."),
 *   @SWG\Property(name="roles",type="Array",items="$ref:string",description="Related roles by app to role assignment."),
 *   @SWG\Property(name="services",type="Array",items="$ref:string",description="Related services by app to service assignment."),
 *   @SWG\Property(name="created_date",type="string",description="Date this application was created."),
 *   @SWG\Property(name="created_by_id",type="int",description="User Id of who created this application."),
 *   @SWG\Property(name="last_modified_date",type="string",description="Date this application was last modified."),
 *   @SWG\Property(name="last_modified_by_id",type="int",description="User Id of who last modified this application.")
 * )
 *
 */
class SystemApp extends SystemResource
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
	 * Creates a new SystemResource instance
	 *
	 *
	 */
	public function __construct( $resource_array = array() )
	{
		$config = array(
			'service_name'=> 'system',
			'name'        => 'Application',
			'api_name'    => 'app',
			'type'        => 'System',
			'description' => 'System application administration.',
			'is_active'   => true,
		);

		parent::__construct( $config, $resource_array );
	}

	// Service interface implementation

	// REST interface implementation

	/**
	 *
	 *   @SWG\Api(
	 *     path="/system/app", description="Operations for application administration.",
	 *     @SWG\Operations(
	 *       @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve multiple applications.",
	 *         notes="Use the 'ids' or 'filter' parameter to limit records that are returned. Use the 'fields' and 'related' parameters to limit properties returned for each record. By default, all fields and no relations are returned for all records.",
	 *         responseClass="Apps", nickname="getApps",
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
	 *         httpMethod="POST", summary="Create one or more applications.",
	 *         notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="createApps",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to create.",
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
	 *         httpMethod="PUT", summary="Update one or more applications.",
	 *         notes="Post data should be a single record or an array of records (shown). By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="updateApps",
	 *         @SWG\Parameters(
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
	 *         httpMethod="DELETE", summary="Delete one or more applications.",
	 *         notes="Use 'ids' or post data should be a single record or an array of records (shown) containing an id. By default, only the id property of the record is returned on success, use 'fields' and 'related' to return more info.",
	 *         responseClass="Success", nickname="deleteApps",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="ids", description="Comma-delimited list of the identifiers of the records to retrieve.",
	 *             paramType="query", required="false", allowMultiple=true, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to delete.",
	 *             paramType="body", required="false", allowMultiple=false, dataType="Apps"
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
	 *     path="/system/app/{id}", description="Operations for individual application administration.",
	 *     @SWG\Operations(
	 *       @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve one application by identifier.",
	 *         notes="Use the 'fields' and/or 'related' parameter to limit properties that are returned. By default, all fields and no relations are returned.",
	 *         responseClass="App", nickname="getApp",
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
	 *         httpMethod="PUT", summary="Update one application.",
	 *         notes="Post data should be an array of fields for a single record. Use the 'fields' and/or 'related' parameter to return more properties. By default, the id is returned.",
	 *         responseClass="Success", nickname="updateApp",
	 *         @SWG\Parameters(
	 *           @SWG\Parameter(
	 *             name="id", description="Identifier of the record to retrieve.",
	 *             paramType="path", required="true", allowMultiple=false, dataType="string"
	 *           ),
	 *           @SWG\Parameter(
	 *             name="record", description="Data containing name-value pairs of records to update.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="App"
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
	 *         httpMethod="DELETE", summary="Delete one application.",
	 *         notes="Use the 'fields' and/or 'related' parameter to return deleted properties. By default, the id is returned.",
	 *         responseClass="Success", nickname="deleteApp",
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
	 * @throws \Exception
	 */
	protected function _handleAction()
	{
		switch ( $this->_action )
		{
			case self::Get:
				if ( !empty( $this->_resourceId ) )
				{
					$asPkg = FilterInput::request( 'pkg', false, FILTER_VALIDATE_BOOLEAN );
					if ( $asPkg )
					{
						$includeFiles = FilterInput::request( 'include_files', false, FILTER_VALIDATE_BOOLEAN  );
						$includeServices = FilterInput::request( 'include_services', false, FILTER_VALIDATE_BOOLEAN  );
						$includeSchema = FilterInput::request( 'include_schema', false, FILTER_VALIDATE_BOOLEAN  );
						$includeData = FilterInput::request( 'include_data', false, FILTER_VALIDATE_BOOLEAN  );
						return static::exportAppAsPackage( $this->_resourceId, $includeFiles, $includeServices, $includeSchema, $includeData );
					}
				}
				break;
			case self::Post:
				// you can import an application package file, local or remote, or from zip, but nothing else
				$fileUrl = FilterInput::request( 'url', '' );
				if ( !empty( $fileUrl ) )
				{
					if ( 0 === strcasecmp( 'dfpkg', FileUtilities::getFileExtension( $fileUrl ) ) )
					{
						// need to download and extract zip file and move contents to storage
						$filename = FileUtilities::importUrlFileToTemp( $fileUrl );
						try
						{
							return static::importAppFromPackage( $filename, $fileUrl );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to import application package $fileUrl.\n{$ex->getMessage()}" );
						}
					}
					$name = FilterInput::request( 'name', '' );
					// from repo or remote zip file
					if ( !empty( $name ) && ( 0 === strcasecmp( 'zip', FileUtilities::getFileExtension( $fileUrl ) ) ) )
					{
						// need to download and extract zip file and move contents to storage
						$filename = FileUtilities::importUrlFileToTemp( $fileUrl );
						try
						{
							return static::importAppFromZip( $name, $filename );
							// todo save url for later updates
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to import application package $fileUrl.\n{$ex->getMessage()}" );
						}
					}
				}
				if ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
				{
					// older html multi-part/form-data post, single or multiple files
					$files = $_FILES['files'];
					if ( is_array( $files['error'] ) )
					{
						throw new \Exception( "Only a single application package file is allowed for import." );
					}
					$filename = $files['name'];
					$error = $files['error'];
					if ( $error !== UPLOAD_ERR_OK )
					{
						throw new \Exception( "Failed to import application package $filename.\n$error" );
					}
					$tmpName = $files['tmp_name'];
					$contentType = $files['type'];
					if ( 0 === strcasecmp( 'dfpkg', FileUtilities::getFileExtension( $filename ) ) )
					{
						try
						{
							// need to extract zip file and move contents to storage
							return static::importAppFromPackage( $tmpName );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to import application package $filename.\n{$ex->getMessage()}" );
						}
					}
					if ( !empty( $name ) && FileUtilities::isZipContent( $contentType ) )
					{
						try
						{
							// need to extract zip file and move contents to storage
							return static::importAppFromZip( $name, $tmpName );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to import application package $filename.\n{$ex->getMessage()}" );
						}
					}
				}
				break;
		}

		return parent::_handleAction();
	}

	//-------- System Records Operations ---------------------
	// records is an array of field arrays

	/**
	 * @param string $app_id
	 * @param bool   $include_files
	 * @param bool   $include_services
	 * @param bool   $include_schema
	 * @param bool   $include_data
	 *
	 * @throws \Exception
	 * @return null
	 */
	public static function exportAppAsPackage( $app_id,
											   $include_files = false,
											   $include_services = false,
											   $include_schema = false,
											   $include_data = false )
	{
		UserSession::checkSessionPermission( 'read', 'system', 'app' );
		$model = \App::model();
		if ( $include_services || $include_schema )
		{
			$model->with( 'app_service_relations.service' );
		}
		$app = $model->findByPk( $app_id );
		if ( null === $app )
		{
			throw new \Exception( "No database entry exists for this application with id '$app_id'." );
		}
		$fields = array(
			'api_name',
			'name',
			'description',
			'is_active',
			'url',
			'is_url_external',
			'import_url',
			'requires_fullscreen',
			'requires_plugin'
		);
		$record = $app->getAttributes( $fields );
		$app_root = Option::get( $record, 'api_name' );

		try
		{
			$zip = new \ZipArchive();
			$tempDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
			$zipFileName = $tempDir . $app_root . '.dfpkg';
			if ( true !== $zip->open( $zipFileName, \ZipArchive::CREATE ) )
			{
				throw new \Exception( 'Can not create package file for this application.' );
			}

			// add database entry file
			if ( !$zip->addFromString( 'description.json', json_encode( $record ) ) )
			{
				throw new \Exception( "Can not include description in package file." );
			}
			if ( $include_services || $include_schema )
			{
				/**
				 * @var \Service[] $serviceRelations
				 */
				$serviceRelations = $app->getRelated( 'app_service_relations' );
				if ( !empty( $serviceRelations ) )
				{
					$services = array();
					$schemas = array();
					$serviceFields = array(
						'name',
						'api_name',
						'description',
						'is_active',
						'type',
						'is_system',
						'storage_name',
						'storage_type',
						'credentials',
						'native_format',
						'base_url',
						'parameters',
						'headers',
					);
					foreach ( $serviceRelations as $relation )
					{
						/** @var \Service $service  */
						$service = $relation->getRelated( 'service' );
						if ( !empty( $service ) )
						{
							if ( $include_services )
							{
								if ( !DataFormat::boolval( $service->getAttribute( 'is_system' ) ) )
								{
									// get service details to restore with app
									$temp = $service->getAttributes( $serviceFields );
									$services[] = $temp;
								}
							}
							if ( $include_schema )
							{
								$component = $relation->getAttribute( 'component' );
								if ( !empty( $component ) )
								{
									// service is probably a db, export table schema if possible
									$serviceName = $service->getAttribute( 'api_name' );
									$serviceType = $service->getAttribute( 'type' );
									switch ( strtolower( $serviceType ) )
									{
										case 'local sql db schema':
										case 'remote sql db schema':
											/** @var $db \Platform\Services\SchemaSvc */
											$db = ServiceHandler::getServiceObject( $serviceName );
											$describe = $db->describeTables( implode( ',', $component ) );
											$temp = array(
												'api_name' => $serviceName,
												'table'    => $describe
											);
											$schemas[] = $temp;
											break;
									}
								}
							}
						}
					}
					if ( !empty( $services ) && !$zip->addFromString( 'services.json', json_encode( $services ) ) )
					{
						throw new \Exception( "Can not include services in package file." );
					}
					if ( !empty( $schemas ) && !$zip->addFromString( 'schema.json', json_encode( array( 'service' => $schemas ) ) ) )
					{
						throw new \Exception( "Can not include database schema in package file." );
					}
				}
			}
			$isExternal = DataFormat::boolval( Option::get( $record, 'is_url_external', false ) );
			if ( !$isExternal && $include_files )
			{
				// add files
				$_storageServiceId = Option::get( $record, 'storage_service_id' );
				/** @var $_service \Platform\Services\BaseFileSvc */
				if ( empty( $_storageServiceId ) )
				{
					$_service = ServiceHandler::getServiceObject( 'app' );
					$_container = 'applications';
				}
				else
				{
					$_service = ServiceHandler::getServiceObjectById( $_storageServiceId );
					$_container = Option::get( $record, 'storage_container' );
				}
				if ( empty( $_container ) )
				{
					if ( $_service->containerExists( $app_root ) )
					{
						$_service->getFolderAsZip( $app_root, '', $zip, $zipFileName, true );
					}
				}
				else
				{
					if ( $_service->folderExists( $_container, $app_root ) )
					{
						$_service->getFolderAsZip( $_container, $app_root, $zip, $zipFileName, true );
					}
				}
			}
			$zip->close();

			$fd = fopen( $zipFileName, "r" );
			if ( $fd )
			{
				$fsize = filesize( $zipFileName );
				$path_parts = pathinfo( $zipFileName );
				header( "Content-type: application/zip" );
				header( "Content-Disposition: filename=\"" . $path_parts["basename"] . "\"" );
				header( "Content-length: $fsize" );
				header( "Cache-control: private" ); //use this to open files directly
				while ( !feof( $fd ) )
				{
					$buffer = fread( $fd, 2048 );
					echo $buffer;
				}
			}
			fclose( $fd );
			unlink( $zipFileName );
			return null;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $pkg_file
	 * @param string $import_url
	 *
	 * @throws \Exception
	 * @return array
	 */
	public static function importAppFromPackage( $pkg_file, $import_url = '' )
	{
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $pkg_file ) )
		{
			throw new \Exception( 'Error opening zip file.' );
		}
		$data = $zip->getFromName( 'description.json' );
		if ( false === $data )
		{
			throw new \Exception( 'No application description file in this package file.' );
		}
		$record = DataFormat::jsonToArray( $data );
		if ( !empty( $import_url ) )
		{
			$record['import_url'] = $import_url;
		}
		try
		{
			$returnData = static::createRecord( 'app', $record, 'id,api_name' );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Could not create the application.\n{$ex->getMessage()}" );
		}
		$id = Option::get( $returnData, 'id' );
		$zip->deleteName( 'description.json' );
		try
		{
			$data = $zip->getFromName( 'services.json' );
			if ( false !== $data )
			{
				$data = DataFormat::jsonToArray( $data );
				try
				{
					$result = static::createRecords( 'service', $data, true );
					// clear swagger cache upon any service changes.
					SwaggerUtilities::clearCache();
				}
				catch ( \Exception $ex )
				{
					throw new \Exception( "Could not create the services.\n{$ex->getMessage()}" );
				}
				$zip->deleteName( 'services.json' );
			}
			$data = $zip->getFromName( 'schema.json' );
			if ( false !== $data )
			{
				$data = DataFormat::jsonToArray( $data );
				$services = Option::get( $data, 'service' );
				if ( !empty( $services ) )
				{
					foreach ( $services as $schemas )
					{
						$serviceName = Option::get( $schemas, 'api_name' );
						$db = ServiceHandler::getServiceObject( $serviceName );
						$tables = Option::get( $schemas, 'table' );
						if ( !empty( $tables ) )
						{
							/** @var $db \Platform\Services\SchemaSvc */
							$result = $db->createTables( $tables, true );
							if ( isset( $result[0]['error'] ) )
							{
								$msg = $result[0]['error']['message'];
								throw new \Exception( "Could not create the database tables for this application.\n$msg" );
							}
						}
					}
				}
				else
				{
					// single or multiple tables for one service
					$tables = Option::get( $data, 'table' );
					if ( !empty( $tables ) )
					{
						$serviceName = Option::get( $data, 'api_name' );
						if ( empty( $serviceName ) )
						{
							$serviceName = 'schema'; // for older packages
						}
						/** @var $db \Platform\Services\SchemaSvc */
						$db = ServiceHandler::getServiceObject( $serviceName );
						$result = $db->createTables( $tables, true );
						if ( isset( $result[0]['error'] ) )
						{
							$msg = $result[0]['error']['message'];
							throw new \Exception( "Could not create the database tables for this application.\n$msg" );
						}
					}
					else
					{
						// single table with no wrappers - try default schema service
						$table = Option::get( $data, 'name' );
						if ( !empty( $table ) )
						{
							$serviceName = 'schema';
							/** @var $db \Platform\Services\SchemaSvc */
							$db = ServiceHandler::getServiceObject( $serviceName );
							$result = $db->createTables( $data, true );
							if ( isset( $result['error'] ) )
							{
								$msg = $result['error']['message'];
								throw new \Exception( "Could not create the database tables for this application.\n$msg" );
							}
						}
					}
				}
				$zip->deleteName( 'schema.json' );
			}
			$data = $zip->getFromName( 'data.json' );
			if ( false !== $data )
			{
				$data = DataFormat::jsonToArray( $data );
				$services = Option::get( $data, 'service' );
				if ( !empty( $services ) )
				{
					foreach ( $services as $service )
					{
						$serviceName = Option::get( $service, 'api_name' );
						$db = ServiceHandler::getServiceObject( $serviceName );
						$tables = Option::get( $data, 'table' );
						foreach ( $tables as $table )
						{
							$tableName = Option::get( $table, 'name' );
							$records = Option::get( $table, 'record' );
							/** @var $db \Platform\Services\BaseDbSvc */
							$result = $db->createRecords( $tableName, $records );
							if ( isset( $result['record'][0]['error'] ) )
							{
								$msg = $result['record'][0]['error']['message'];
								throw new \Exception( "Could not insert the database entries for table '$tableName'' for this application.\n$msg" );
							}
						}
					}
				}
				else
				{
					// single or multiple tables for one service
					$tables = Option::get( $data, 'table' );
					if ( !empty( $tables ) )
					{
						$serviceName = Option::get( $data, 'api_name' );
						if ( empty( $serviceName ) )
						{
							$serviceName = 'db'; // for older packages
						}
						$db = ServiceHandler::getServiceObject( $serviceName );
						foreach ( $tables as $table )
						{
							$tableName = Option::get( $table, 'name' );
							$records = Option::get( $table, 'record' );
							/** @var $db \Platform\Services\BaseDbSvc */
							$result = $db->createRecords( $tableName, $records );
							if ( isset( $result['record'][0]['error'] ) )
							{
								$msg = $result['record'][0]['error']['message'];
								throw new \Exception( "Could not insert the database entries for table '$tableName'' for this application.\n$msg" );
							}
						}
					}
					else
					{
						// single table with no wrappers - try default database service
						$tableName = Option::get( $data, 'name' );
						if ( !empty( $tableName ) )
						{
							$serviceName = 'db';
							$db = ServiceHandler::getServiceObject( $serviceName );
							$records = Option::get( $data, 'record' );
							/** @var $db \Platform\Services\BaseDbSvc */
							$result = $db->createRecords( $tableName, $records );
							if ( isset( $result['record'][0]['error'] ) )
							{
								$msg = $result['record'][0]['error']['message'];
								throw new \Exception( "Could not insert the database entries for table '$tableName'' for this application.\n$msg" );
							}
						}
					}
				}
				$zip->deleteName( 'data.json' );
			}
		}
		catch ( \Exception $ex )
		{
			// delete db record
			// todo anyone else using schema created?
			static::deleteRecordById( 'app', $id );
			throw $ex;
		}

		// extract the rest of the zip file into storage
		$_storageServiceId = Option::get( $record, 'storage_service_id' );
		$_apiName = Option::get( $record, 'api_name' );

		/** @var $_service \Platform\Services\BaseFileSvc */
		if ( empty( $_storageServiceId ) )
		{
			$_service = ServiceHandler::getServiceObject( 'app' );
			$_container = 'applications';
		}
		else
		{
			$_service = ServiceHandler::getServiceObjectById( $_storageServiceId );
			$_container = Option::get( $record, 'storage_container' );
		}
		if ( empty( $_container ) )
		{
			$_service->extractZipFile( $_apiName, '', $zip, false, $_apiName . '/' );
		}
		else
		{
			$_service->extractZipFile( $_container, '', $zip );
		}

		return $returnData;
	}

	/**
	 * @param $name
	 * @param $zip_file
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function importAppFromZip( $name, $zip_file )
	{
		$record = array( 'api_name' => $name, 'name' => $name, 'is_url_external' => 0, 'url' => '/index.html' );
		try
		{
			$result = static::createRecord( 'app', $record );
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Could not create the database entry for this application.\n{$ex->getMessage()}" );
		}
		$id = ( isset( $result['id'] ) ) ? $result['id'] : '';

		$zip = new \ZipArchive();
		if ( true === $zip->open( $zip_file ) )
		{
			// extract the rest of the zip file into storage
			$dropPath = $zip->getNameIndex( 0 );
			$dropPath = substr( $dropPath, 0, strpos( $dropPath, '/' ) ) . '/';

			$_storageServiceId = Option::get( $record, 'storage_service_id' );
			$_apiName = Option::get( $record, 'api_name' );

			/** @var $_service \Platform\Services\BaseFileSvc */
			if ( empty( $_storageServiceId ) )
			{
				$_service = ServiceHandler::getServiceObject( 'app' );
				$_container = 'applications';
			}
			else
			{
				$_service = ServiceHandler::getServiceObjectById( $_storageServiceId );
				$_container = Option::get( $record, 'storage_container' );
			}
			if ( empty( $_container ) )
			{
				$_service->extractZipFile( $_apiName, '', $zip, false, $dropPath );
			}
			else
			{
				$_service->extractZipFile( $_container, $_apiName, $zip, false, $dropPath );
			}

			return $result;
		}
		else
		{
			throw new \Exception( 'Error opening zip file.' );
		}
	}

}
