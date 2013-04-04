<?php

/**
 * ApplicationSvc.php
 * A service to handle application-specific file storage accessed through the REST API.
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
class ApplicationSvc extends BaseFileSvc
{
	/**
	 * @param array $config
	 * @param bool  $native
	 */
	public function __construct( $config, $native = false )
	{
		// Validate storage setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			$config['storage_name'] = 'applications';
		}

		parent::__construct( $config, $native );
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

		return array_merge( $swagger, parent::swaggerParameters( $parameters, $method ));
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
						"notes"          => "DELETE the given FILE FROM the STORAGE.",
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

	// Controller based methods

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionGet()
	{
		$this->checkPermission( 'read' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// list app folders only for now
			return $this->fileRestHandler->getFolderContent( '', false, true, false );
		}

		return parent::actionGet();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->checkPermission( 'create' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file creation." );
		}

		return parent::actionPost();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->checkPermission( 'update' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		if ( empty( $path_array ) || ( ( 1 === count( $path_array ) ) && empty( $path_array[0] ) ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file updates." );
		}

		return parent::actionPut();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->checkPermission( 'update' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file updates." );
		}

		return parent::actionMerge();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->checkPermission( 'delete' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file deletes." );
		}
		$more = ( isset( $path_array[1] ) ? $path_array[1] : '' );
		if ( empty( $more ) )
		{
			// dealing only with application root here
			$content = Utilities::getPostDataAsArray();
			if ( empty( $content ) )
			{
				throw new Exception( "Application root directory is not available for delete. Use the system API to delete the app." );
			}
		}

		return parent::actionDelete();
	}

}
