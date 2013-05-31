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

use Platform\Utility\RestRequest;
use Platform\Utility\Utilities;

/**
 * AppLocalFileSvc.php
 * A service to handle application-specific file storage accessed through the REST API.
 */
class AppLocalFileSvc extends LocalFileSvc
{
	/**
	 * @param array $config
	 */
	public function __construct( $config )
	{
		// Validate storage setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			$config['storage_name'] = 'applications';
		}

		parent::__construct( $config );
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		parent::__destruct();
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
		if ( false !== array_search( 'app_name', $parameters ) )
		{
			$swagger[] = array(
				"paramType"     => "path",
				"name"          => "app_name",
				"description"   => "Name of the application to operate on.",
				"dataType"      => "String",
				"required"      => true,
				"allowMultiple" => false
			);
		}

		return array_merge( $swagger, parent::swaggerParameters( $parameters, $method ) );
	}

	/**
	 * @param string $service
	 * @param string $description
	 *
	 * @return array
	 */
	public static function swaggerForFiles( $service, $description = '' )
	{
		$swagger = array(
			array(
				'path'        => '/' . $service,
				'description' => $description,
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "List root application folders",
						"notes"          => "List the root folders for locally stored applications.",
						"responseClass"  => "array",
						"nickname"       => "getAppFolders",
						"parameters"     => array(),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $service . '/{app_name}/',
				'description' => $description,
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "List root folders and files",
						"notes"          => "Use the available parameters to limit information returned.",
						"responseClass"  => "array",
						"nickname"       => "getRoot",
						"parameters"     => static::swaggerParameters(
							array(
								 'app_name',
								 'folders_only',
								 'files_only',
								 'full_tree',
								 'zip'
							)
						),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $service . '/{app_name}/{folder}/',
				'description' => 'Operations for folders.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "List folders and files in the given folder.",
						"notes"          => "Use the 'folders_only' or 'files_only' parameters to limit returned listing.",
						"responseClass"  => "array",
						"nickname"       => "getFoldersAndFiles",
						"parameters"     => static::swaggerParameters(
							array(
								 'app_name',
								 'folder',
								 'folders_only',
								 'files_only',
								 'full_tree',
								 'zip'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Create one or more folders and/or files from posted data.",
						"notes"          => "Post data as an array of folders and/or files.",
						"responseClass"  => "array",
						"nickname"       => "createFoldersAndFiles",
						"parameters"     => static::swaggerParameters(
							array(
								 'app_name',
								 'folder',
								 'url',
								 'extract',
								 'clean',
								 'check_exist'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update one or more folders and/or files",
						"notes"          => "Post data as an array of folders and/or files.",
						"responseClass"  => "array",
						"nickname"       => "updateFoldersAndFiles",
						"parameters"     => static::swaggerParameters(
							array(
								 'app_name',
								 'folder',
								 'url',
								 'extract',
								 'clean',
								 'check_exist'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete one or more folders and/or files",
						"notes"          => "Use the 'ids' or 'filter' parameter to limit resources that are deleted.",
						"responseClass"  => "array",
						"nickname"       => "deleteFoldersAndFiles",
						"parameters"     => static::swaggerParameters( array( 'app_name', 'folder' ) ),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $service . '/{app_name}/{folder}/{file}',
				'description' => 'Operations for a single file.',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Download the given file or properties about the file.",
						"notes"          => "Use the 'properties' parameter (optionally add 'content' for base64 content) to list properties of the file.",
						"responseClass"  => "array",
						"nickname"       => "getFile",
						"parameters"     => static::swaggerParameters(
							array(
								 'app_name',
								 'folder',
								 'file',
								 'properties',
								 'content',
								 'download'
							)
						),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "PUT",
						"summary"        => "Update content of the given file",
						"notes"          => "Post data should be an array of fields for a single record",
						"responseClass"  => "array",
						"nickname"       => "updateFile",
						"parameters"     => static::swaggerParameters( array( 'app_name', 'folder', 'file' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete the given file",
						"notes"          => "Delete the given file out of the storage.",
						"responseClass"  => "array",
						"nickname"       => "deleteFile",
						"parameters"     => static::swaggerParameters( array( 'app_name', 'folder', 'file' ) ),
						"errorResponses" => array()
					),
				)
			),
		);

		return $swagger;
	}

	// REST interface implementation

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function _handleResource()
	{
		switch ( $this->_action )
		{
			case static::Get:
				$this->checkPermission( 'read' );
				if ( empty( $this->_resource ) )
				{
					// list app folders only for now
					return $this->getFolderContent( '', false, true, false );
				}
				break;

			case static::Post:
				$this->checkPermission( 'create' );
				if ( empty( $this->_resource ) )
				{
					// for application management at root directory,
					throw new \Exception( "Application service root directory is not available for file creation." );
				}
				break;

			case static::Put:
			case static::Patch:
			case static::Merge:
				$this->checkPermission( 'update' );
				if ( empty( $this->_resource ) || ( ( 1 === count( $this->_resourceArray ) ) && empty( $this->_resourceArray[0] ) ) )
				{
					// for application management at root directory,
					throw new \Exception( "Application service root directory is not available for file updates." );
				}
				break;

			case static::Delete:
				$this->checkPermission( 'delete' );
				if ( empty( $this->_resource ) )
				{
					// for application management at root directory,
					throw new \Exception( "Application service root directory is not available for file deletes." );
				}
				$more = ( isset( $this->_resourceArray[1] ) ? $this->_resourceArray[1] : '' );
				if ( empty( $more ) )
				{
					// dealing only with application root here
					$content = RestRequest::getPostDataAsArray();
					if ( empty( $content ) )
					{
						throw new \Exception( "Application root directory is not available for delete. Use the system API to delete the app." );
					}
				}
				break;
		}

		return parent::_handleResource();
	}
}
