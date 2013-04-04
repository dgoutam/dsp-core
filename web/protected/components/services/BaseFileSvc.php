<?php

/**
 * BaseFileSvc.php
 * Base File Storage Service giving REST access to file storage.
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
class BaseFileSvc extends BaseService
{
	/**
	 * @var FileManager|BlobFileManager
	 */
	protected $fileRestHandler;

	/**
	 * @param array $config
	 * @param bool  $native
	 *
	 * @throws Exception
	 */
	public function __construct( $config, $native = false )
	{
		parent::__construct( $config );

		// Validate storage setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			throw new Exception( "Error creating common file service. No storage name given." );
		}
		if ( $native )
		{
			$this->fileRestHandler = new FileManager( $store_name );
		}
		else
		{
			$this->fileRestHandler = new BlobFileManager( $config, $store_name );
		}
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		unset( $this->fileRestHandler );
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function streamFile( $path )
	{
		$this->fileRestHandler->streamFile( $path );
	}

	/**
	 * @param $folder
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function folderExists( $folder )
	{
		// applications are defined as a top level folder in the storage
		return $this->fileRestHandler->folderExists( $folder );
	}

	/**
	 * @param string $app_root
	 * @param bool   $is_public
	 * @param array  $properties
	 * @param bool   $check_exist
	 *
	 */
	public function createFolder( $app_root, $is_public = true, $properties = array(), $check_exist = true )
	{
		$this->fileRestHandler->createFolder( $app_root, $is_public, $properties, $check_exist );
	}

	/**
	 * @param $path
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function fileExists( $path )
	{
		return $this->fileRestHandler->fileExists( $path );
	}

	/**
	 * @param      $path
	 * @param      $content
	 * @param bool $content_is_base
	 * @param bool $check_exist
	 */
	public function writeFile( $path, $content, $content_is_base = false, $check_exist = false )
	{
		$this->fileRestHandler->writeFile( $path, $content, $content_is_base, $check_exist );
	}

	/**
	 * @param $folder
	 *
	 * @throws Exception
	 */
	public function deleteFolder( $folder )
	{
		// check if an application (folder) exists with that name
		if ( $this->fileRestHandler->folderExists( $folder ) )
		{
			$this->fileRestHandler->deleteFolder( $folder, true );
		}
	}

	/**
	 * @param        $path
	 * @param null   $zip
	 * @param string $zipFileName
	 * @param bool   $overwrite
	 *
	 * @return string
	 */
	public function getFolderAsZip( $path, $zip = null, $zipFileName = '', $overwrite = false )
	{
		return $this->fileRestHandler->getFolderAsZip( $path, $zip, $zipFileName, $overwrite );
	}

	/**
	 * @param        $path
	 * @param        $zip
	 * @param bool   $clean
	 * @param string $drop_path
	 *
	 * @return array
	 */
	public function extractZipFile( $path, $zip, $clean = false, $drop_path = '' )
	{
		return $this->fileRestHandler->extractZipFile( $path, $zip, $clean, $drop_path );
	}

	/**
	 * @param        $dest_path
	 * @param        $dest_name
	 * @param        $source_file
	 * @param string $contentType
	 * @param bool   $extract
	 * @param bool   $clean
	 * @param bool   $check_exist
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function handleFile( $dest_path, $dest_name, $source_file, $contentType = '',
								   $extract = false, $clean = false, $check_exist = false )
	{
		$ext = FileUtilities::getFileExtension( $source_file );
		if ( empty( $contentType ) )
		{
			$contentType = FileUtilities::determineContentType( $ext, '', $source_file );
		}
		if ( ( FileUtilities::isZipContent( $contentType ) || ( 'zip' === $ext ) ) && $extract )
		{
			// need to extract zip file and move contents to storage
			$zip = new ZipArchive();
			if ( true === $zip->open( $source_file ) )
			{
				return $this->fileRestHandler->extractZipFile( $dest_path, $zip, $clean );
			}
			else
			{
				throw new Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$name = ( empty( $dest_name ) ? basename( $source_file ) : $dest_name );
			$fullPathName = $dest_path . $name;
			$this->fileRestHandler->moveFile( $fullPathName, $source_file, $check_exist );

			return array( 'file' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
		}
	}

	/**
	 * @param        $dest_path
	 * @param        $dest_name
	 * @param        $content
	 * @param string $contentType
	 * @param bool   $extract
	 * @param bool   $clean
	 * @param bool   $check_exist
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function handleFileContent( $dest_path, $dest_name, $content, $contentType = '',
										  $extract = false, $clean = false, $check_exist = false )
	{
		$ext = FileUtilities::getFileExtension( $dest_name );
		if ( empty( $contentType ) )
		{
			$contentType = FileUtilities::determineContentType( $ext, $content );
		}
		if ( ( FileUtilities::isZipContent( $contentType ) || ( 'zip' === $ext ) ) && $extract )
		{
			// need to extract zip file and move contents to storage
			$tempDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
			$tmpName = $tempDir . $dest_name;
			file_put_contents( $tmpName, $content );
			$zip = new ZipArchive();
			if ( true === $zip->open( $tmpName ) )
			{
				return $this->fileRestHandler->extractZipFile( $dest_path, $zip, $clean );
			}
			else
			{
				throw new Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$fullPathName = $dest_path . $dest_name;
			$this->fileRestHandler->writeFile( $fullPathName, $content, false, $check_exist );

			return array( 'file' => array( array( 'name' => $dest_name, 'path' => $fullPathName ) ) );
		}
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
				case 'folder':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the folder to operate on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'file':
					$swagger[] = array(
						"paramType"     => "path",
						"name"          => $param,
						"description"   => "Name of the file to operate on.",
						"dataType"      => "String",
						"required"      => true,
						"allowMultiple" => false
					);
					break;
				case 'properties':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Return properties of the folder or file.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'content':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Return the content as base64 of the file, only applies when 'properties' is true.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'download':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Prompt the user to download the file from the browser.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'folders_only':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Only include folders in the folder listing.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'files_only':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Only include files in the folder listing.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'full_tree':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "List the contents of sub-folders as well.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'zip':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Return the zipped content of the folder.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'url':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "URL path of the file to upload.",
						"dataType"      => "string",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'extract':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Extract an uploaded zip file into the folder.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'clean':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Option when 'extract' is true, clean the current folder before extracting files and folders.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
					);
					break;
				case 'check_exist':
					$swagger[] = array(
						"paramType"     => "query",
						"name"          => $param,
						"description"   => "Check if the file or folder exists before attempting to create or update.",
						"dataType"      => "boolean",
						"required"      => false,
						"allowMultiple" => false
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
	public static function swaggerForFiles( $service, $description = '' )
	{
		$swagger = array(
			array(
				'path'        => '/' . $service,
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
				'path'        => '/' . $service . '/{folder}/',
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
						"parameters"     => static::swaggerParameters( array( 'folder' ) ),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/' . $service . '/{folder}/{file}',
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
						"parameters"     => static::swaggerParameters( array( 'folder', 'file' ) ),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Delete the given file",
						"notes"          => "DELETE the given FILE FROM the STORAGE.",
						"responseClass"  => "array",
						"nickname"       => "deleteFile",
						"parameters"     => static::swaggerParameters( array( 'folder', 'file' ) ),
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
			$result = parent::actionSwagger();
			$resources = static::swaggerForFiles( $this->_apiName, $this->_description );
			$result['apis'] = $resources;

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionGet()
	{
		$this->checkPermission( 'read' );
		$result = array();
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// list from root
			// or if ending in '/' then resource is a folder
			try
			{
				if ( isset( $_REQUEST['properties'] ) )
				{
					// just properties of the directory itself
					$result = $this->fileRestHandler->getFolderProperties( $path );
				}
				else
				{
					$asZip = Utilities::boolval( Utilities::getArrayValue( 'zip', $_REQUEST, false ) );
					if ( $asZip )
					{
						$zipFileName = $this->fileRestHandler->getFolderAsZip( $path );
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
						Yii::app()->end();
					}
					else
					{
						$full_tree = ( isset( $_REQUEST['full_tree'] ) ) ? true : false;
						$include_files = true;
						$include_folders = true;
						if ( isset( $_REQUEST['files_only'] ) )
						{
							$include_folders = false;
						}
						elseif ( isset( $_REQUEST['folders_only'] ) )
						{
							$include_files = false;
						}
						$result = $this->fileRestHandler->getFolderContent( $path, $include_files, $include_folders, $full_tree );
					}
				}
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to retrieve folder content for '$path'.\n{$ex->getMessage()}", $ex->getCode() );
			}
		}
		else
		{
			// resource is a file
			try
			{
				if ( isset( $_REQUEST['properties'] ) )
				{
					// just properties of the file itself
					$content = Utilities::boolval( Utilities::getArrayValue( 'content', $_REQUEST, false ) );
					$result = $this->fileRestHandler->getFileProperties( $path, $content );
				}
				else
				{
					$download = Utilities::boolval( Utilities::getArrayValue( 'download', $_REQUEST, false ) );
					if ( $download )
					{
						// stream the file, exits processing
						$this->fileRestHandler->downloadFile( $path );
					}
					else
					{
						// stream the file, exits processing
						$this->fileRestHandler->streamFile( $path );
					}
				}
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to retrieve file '$path''.\n{$ex->getMessage()}" );
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->checkPermission( 'create' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		$result = array();
		// possible file handling parameters
		$extract = Utilities::boolval( Utilities::getArrayValue( 'extract', $_REQUEST, false ) );
		$clean = Utilities::boolval( Utilities::getArrayValue( 'clean', $_REQUEST, false ) );
		$checkExist = Utilities::boolval( Utilities::getArrayValue( 'check_exist', $_REQUEST, true ) );
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// if ending in '/' then create files or folders in the directory
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				// html5 single posting for file create
				$name = $_SERVER['HTTP_X_FILE_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in create file $fullPathName." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create file $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				// html5 single posting for folder create
				$name = $_SERVER['HTTP_X_FOLDER_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->fileRestHandler->createFolder( $fullPathName, true, $content, true );
					$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create folder $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multi-part/form-data post, single or multiple files
				$files = $_FILES['files'];
				if ( !is_array( $files['error'] ) )
				{
					// single file
					$name = $files['name'];
					$fullPathName = $path . $name;
					$error = $files['error'];
					if ( $error == UPLOAD_ERR_OK )
					{
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					else
					{
						$result = array(
							'code'    => 500,
							'message' => "Failed to create file $fullPathName.\n$error"
						);
					}
				}
				else
				{
					$out = array();
					//$files = Utilities::reorgFilePostArray($files);
					foreach ( $files['error'] as $key => $error )
					{
						$name = $files['name'][$key];
						$fullPathName = $path . $name;
						if ( $error == UPLOAD_ERR_OK )
						{
							$tmpName = $files['tmp_name'][$key];
							$contentType = $files['type'][$key];
							$tmp = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
						}
						else
						{
							$out[$key]['error'] = array(
								'code'    => 500,
								'message' => "Failed to create file $fullPathName.\n$error"
							);
						}
					}
					$result = array( 'file' => $out );
				}
			}
			else
			{
				// possibly xml or json post either of files or folders to create, copy or move
				$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
				if ( !empty( $fileUrl ) )
				{
					// upload a file from a url, could be expandable zip
					$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
					try
					{
						$result = $this->handleFile( $path, '', $tmpName, '', $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					try
					{
						$data = Utilities::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->fileRestHandler->createFolder( $path );
							$result = array( 'folder' => array( array( 'path' => $path ) ) );
						}
						else
						{
							$out = array( 'folder' => array(), 'file' => array() );
							$folders = Utilities::getArrayValue( 'folder', $data, null );
							if ( empty( $folders ) )
							{
								$folders = ( isset( $data['folders']['folder'] ) ? $data['folders']['folder'] : null );
							}
							if ( !empty( $folders ) )
							{
								if ( !isset( $folders[0] ) )
								{
									// single folder, make into array
									$folders = array( $folders );
								}
								foreach ( $folders as $key => $folder )
								{
									$name = Utilities::getArrayValue( 'name', $folder, '' );
									if ( isset( $folder['source_path'] ) )
									{
										// copy or move
										$srcPath = $folder['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name . '/';
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->copyFolder( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $folder, false ) );
											if ( $deleteSource )
											{
												$this->fileRestHandler->deleteFolder( $srcPath, true );
											}
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									else
									{
										$fullPathName = $path . $name;
										$content = Utilities::getArrayValue( 'content', $folder, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $folder, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->createFolder( $fullPathName, true, $content );
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$files = Utilities::getArrayValue( 'file', $data, null );
							if ( empty( $files ) )
							{
								$files = ( isset( $data['files']['file'] ) ? $data['files']['file'] : null );
							}
							if ( !empty( $files ) )
							{
								if ( !isset( $files[0] ) )
								{
									// single file, make into array
									$files = array( $files );
								}
								foreach ( $files as $key => $file )
								{
									$name = Utilities::getArrayValue( 'name', $file, '' );
									if ( isset( $file['source_path'] ) )
									{
										// copy or move
										$srcPath = $file['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->copyFile( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $file, false ) );
											if ( $deleteSource )
											{
												$this->fileRestHandler->deleteFile( $srcPath );
											}
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									elseif ( isset( $file['content'] ) )
									{
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										$content = Utilities::getArrayValue( 'content', $file, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $file, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										try
										{
											$this->fileRestHandler->writeFile( $fullPathName, $content );
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$result = $out;
						}
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to create folders or files from request.\n{$ex->getMessage()}" );
					}
				}
			}
		}
		else
		{
			// if ending in file name, create the file or folder
			$name = substr( $path, strripos( $path, '/' ) + 1 );
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
				if ( 0 !== strcasecmp( $name, $x_file_name ) )
				{
					throw new Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in write file $path to storage." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create file $path.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
				if ( 0 !== strcasecmp( $name, $x_folder_name ) )
				{
					throw new Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->fileRestHandler->createFolder( $path, true, $content );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to create file $path.\n{$ex->getMessage()}" );
				}
				$result = array( 'folder' => array( array( 'name' => $name, 'path' => $path ) ) );
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multipart/form-data post, should be single file
				$files = $_FILES['files'];
				//$files = Utilities::reorgFilePostArray($files);
				if ( 1 < count( $files['error'] ) )
				{
					throw new Exception( "Multiple files uploaded to a single REST resource '$name'." );
				}
				$name = $files['name'][0];
				$fullPathName = $path;
				$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
				$error = $files['error'][0];
				if ( UPLOAD_ERR_OK == $error )
				{
					$tmpName = $files["tmp_name"][0];
					$contentType = $files['type'][0];
					try
					{
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to create file $fullPathName.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					throw new Exception( "Failed to upload file $name.\n$error" );
				}
			}
			else
			{
				// possibly xml or json post either of file or folder to create, copy or move
				try
				{
					$data = Utilities::getPostDataAsArray();
					error_log( print_r( $data, true ) );
					//$this->addFiles($path, $data['files']);
					$result = array();
				}
				catch ( Exception $ex )
				{

				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->checkPermission( 'update' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		$result = array();
		// possible file handling parameters
		$extract = Utilities::boolval( Utilities::getArrayValue( 'extract', $_REQUEST, false ) );
		$clean = Utilities::boolval( Utilities::getArrayValue( 'clean', $_REQUEST, false ) );
		$checkExist = false;
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// if ending in '/' then create files or folders in the directory
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				// html5 single posting for file create
				$name = $_SERVER['HTTP_X_FILE_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in update file $fullPathName." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				// html5 single posting for folder create
				$name = $_SERVER['HTTP_X_FOLDER_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->fileRestHandler->createFolder( $fullPathName, true, $content, true );
					$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multi-part/form-data post, single or multiple files
				$files = $_FILES['files'];
				if ( !is_array( $files['error'] ) )
				{
					// single file
					$name = $files['name'];
					$fullPathName = $path . $name;
					$error = $files['error'];
					if ( $error == UPLOAD_ERR_OK )
					{
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					else
					{
						$result = array(
							'code'    => 500,
							'message' => "Failed to update file $fullPathName.\n$error"
						);
					}
				}
				else
				{
					$out = array();
					//$files = Utilities::reorgFilePostArray($files);
					foreach ( $files['error'] as $key => $error )
					{
						$name = $files['name'][$key];
						$fullPathName = $path . $name;
						if ( $error == UPLOAD_ERR_OK )
						{
							$tmpName = $files['tmp_name'][$key];
							$contentType = $files['type'][$key];
							$tmp = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
						}
						else
						{
							$out[$key]['error'] = array(
								'code'    => 500,
								'message' => "Failed to update file $fullPathName.\n$error"
							);
						}
					}
					$result = array( 'file' => $out );
				}
			}
			else
			{
				$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
				if ( !empty( $fileUrl ) )
				{
					// upload a file from a url, could be expandable zip
					$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
					try
					{
						$result = $this->handleFile( $path, '', $tmpName, '', $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					try
					{
						$data = Utilities::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->fileRestHandler->createFolder( $path );
							$result = array( 'folder' => array( array( 'path' => $path ) ) );
						}
						else
						{
							$out = array( 'folder' => array(), 'file' => array() );
							$folders = Utilities::getArrayValue( 'folder', $data, null );
							if ( empty( $folders ) )
							{
								$folders = ( isset( $data['folders']['folder'] ) ? $data['folders']['folder'] : null );
							}
							if ( !empty( $folders ) )
							{
								if ( !isset( $folders[0] ) )
								{
									// single folder, make into array
									$folders = array( $folders );
								}
								foreach ( $folders as $key => $folder )
								{
									$name = Utilities::getArrayValue( 'name', $folder, '' );
									if ( isset( $folder['source_path'] ) )
									{
										// copy or move
										$srcPath = $folder['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name . '/';
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->copyFolder( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $folder, false ) );
											if ( $deleteSource )
											{
												$this->fileRestHandler->deleteFolder( $srcPath, true );
											}
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									else
									{
										$fullPathName = $path . $name;
										$content = Utilities::getArrayValue( 'content', $folder, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $folder, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->createFolder( $fullPathName, true, $content );
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$files = Utilities::getArrayValue( 'file', $data, null );
							if ( empty( $files ) )
							{
								$files = ( isset( $data['files']['file'] ) ? $data['files']['file'] : null );
							}
							if ( !empty( $files ) )
							{
								if ( !isset( $files[0] ) )
								{
									// single file, make into array
									$files = array( $files );
								}
								foreach ( $files as $key => $file )
								{
									$name = Utilities::getArrayValue( 'name', $file, '' );
									if ( isset( $file['source_path'] ) )
									{
										// copy or move
										$srcPath = $file['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->copyFile( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $file, false ) );
											if ( $deleteSource )
											{
												$this->fileRestHandler->deleteFile( $srcPath );
											}
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									elseif ( isset( $file['content'] ) )
									{
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										$content = Utilities::getArrayValue( 'content', $file, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $file, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										try
										{
											$this->fileRestHandler->writeFile( $fullPathName, $content );
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$result = $out;
						}
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
			}
		}
		else
		{
			// if ending in file name, update the file or folder
			$name = substr( $path, strripos( $path, '/' ) + 1 );
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
				if ( 0 !== strcasecmp( $name, $x_file_name ) )
				{
					throw new Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in write file $path to storage." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $path.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
				if ( 0 !== strcasecmp( $name, $x_folder_name ) )
				{
					throw new Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->fileRestHandler->updateFolderProperties( $path, $content );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $path.\n{$ex->getMessage()}" );
				}
				$result = array( 'folder' => array( array( 'name' => $name, 'path' => $path ) ) );
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multipart/form-data post, should be single file
				$files = $_FILES['files'];
				//$files = Utilities::reorgFilePostArray($files);
				if ( 1 < count( $files['error'] ) )
				{
					throw new Exception( "Multiple files uploaded to a single REST resource '$name'." );
				}
				$name = $files['name'][0];
				$fullPathName = $path;
				$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
				$error = $files['error'][0];
				if ( UPLOAD_ERR_OK == $error )
				{
					$tmpName = $files["tmp_name"][0];
					$contentType = $files['type'][0];
					try
					{
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					throw new Exception( "Failed to upload file $name.\n$error" );
				}
			}
			else
			{
				// possibly xml or json post either of file or folder to create, copy or move
				try
				{
					$data = Utilities::getPostDataAsArray();
					error_log( print_r( $data, true ) );
					//$this->addFiles($path, $data['files']);
					$result = array();
				}
				catch ( Exception $ex )
				{

				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->checkPermission( 'update' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		$result = array();
		// possible file handling parameters
		$extract = Utilities::boolval( Utilities::getArrayValue( 'extract', $_REQUEST, false ) );
		$clean = Utilities::boolval( Utilities::getArrayValue( 'clean', $_REQUEST, false ) );
		$checkExist = false;
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// if ending in '/' then create files or folders in the directory
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				// html5 single posting for file create
				$name = $_SERVER['HTTP_X_FILE_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in update file $fullPathName." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				// html5 single posting for folder create
				$name = $_SERVER['HTTP_X_FOLDER_NAME'];
				$fullPathName = $path . $name;
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->fileRestHandler->createFolder( $fullPathName, true, $content, true );
					$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $fullPathName.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multi-part/form-data post, single or multiple files
				$files = $_FILES['files'];
				if ( !is_array( $files['error'] ) )
				{
					// single file
					$name = $files['name'];
					$fullPathName = $path . $name;
					$error = $files['error'];
					if ( $error == UPLOAD_ERR_OK )
					{
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					else
					{
						$result = array(
							'code'    => 500,
							'message' => "Failed to update file $fullPathName.\n$error"
						);
					}
				}
				else
				{
					$out = array();
					//$files = Utilities::reorgFilePostArray($files);
					foreach ( $files['error'] as $key => $error )
					{
						$name = $files['name'][$key];
						$fullPathName = $path . $name;
						if ( $error == UPLOAD_ERR_OK )
						{
							$tmpName = $files['tmp_name'][$key];
							$contentType = $files['type'][$key];
							$tmp = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
						}
						else
						{
							$out[$key]['error'] = array(
								'code'    => 500,
								'message' => "Failed to update file $fullPathName.\n$error"
							);
						}
					}
					$result = array( 'file' => $out );
				}
			}
			else
			{
				// possibly xml or json post either of files or folders to create, copy or move
				$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
				if ( !empty( $fileUrl ) )
				{
					// upload a file from a url, could be expandable zip
					$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
					try
					{
						$result = $this->handleFile( $path, '', $tmpName, '', $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					try
					{
						$data = Utilities::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->fileRestHandler->createFolder( $path );
							$result = array( 'folder' => array( array( 'path' => $path ) ) );
						}
						else
						{
							$out = array( 'folder' => array(), 'file' => array() );
							$folders = Utilities::getArrayValue( 'folder', $data, null );
							if ( empty( $folders ) )
							{
								$folders = ( isset( $data['folders']['folder'] ) ? $data['folders']['folder'] : null );
							}
							if ( !empty( $folders ) )
							{
								if ( !isset( $folders[0] ) )
								{
									// single folder, make into array
									$folders = array( $folders );
								}
								foreach ( $folders as $key => $folder )
								{
									$name = Utilities::getArrayValue( 'name', $folder, '' );
									if ( isset( $folder['source_path'] ) )
									{
										// copy or move
										$srcPath = $folder['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name . '/';
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->copyFolder( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $folder, false ) );
											if ( $deleteSource )
											{
												$this->fileRestHandler->deleteFolder( $srcPath, true );
											}
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									else
									{
										$fullPathName = $path . $name;
										$content = Utilities::getArrayValue( 'content', $folder, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $folder, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->createFolder( $fullPathName, true, $content );
										}
										catch ( Exception $ex )
										{
											$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$files = Utilities::getArrayValue( 'file', $data, null );
							if ( empty( $files ) )
							{
								$files = ( isset( $data['files']['file'] ) ? $data['files']['file'] : null );
							}
							if ( !empty( $files ) )
							{
								if ( !isset( $files[0] ) )
								{
									// single file, make into array
									$files = array( $files );
								}
								foreach ( $files as $key => $file )
								{
									$name = Utilities::getArrayValue( 'name', $file, '' );
									if ( isset( $file['source_path'] ) )
									{
										// copy or move
										$srcPath = $file['source_path'];
										if ( empty( $name ) )
										{
											$name = FileUtilities::getNameFromPath( $srcPath );
										}
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										try
										{
											$this->fileRestHandler->copyFile( $fullPathName, $srcPath, true );
											$deleteSource = Utilities::boolval( Utilities::getArrayValue( 'delete_source', $file, false ) );
											if ( $deleteSource )
											{
												$this->fileRestHandler->deleteFile( $srcPath );
											}
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
									elseif ( isset( $file['content'] ) )
									{
										$fullPathName = $path . $name;
										$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
										$content = Utilities::getArrayValue( 'content', $file, '' );
										$isBase64 = Utilities::boolval( Utilities::getArrayValue( 'is_base64', $file, false ) );
										if ( $isBase64 )
										{
											$content = base64_decode( $content );
										}
										try
										{
											$this->fileRestHandler->writeFile( $fullPathName, $content );
										}
										catch ( Exception $ex )
										{
											$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
										}
									}
								}
							}
							$result = $out;
						}
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
					}
				}
			}
		}
		else
		{
			// if ending in file name, create the file or folder
			$name = substr( $path, strripos( $path, '/' ) + 1 );
			if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
			{
				$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
				if ( 0 !== strcasecmp( $name, $x_file_name ) )
				{
					throw new Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostData();
					if ( empty( $content ) )
					{
						// empty post?
						error_log( "Empty content in write file $path to storage." );
					}
					$contentType = Utilities::getArrayValue( 'CONTENT_TYPE', $_SERVER, '' );
					$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
					$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update file $path.\n{$ex->getMessage()}" );
				}
			}
			elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
			{
				$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
				if ( 0 !== strcasecmp( $name, $x_folder_name ) )
				{
					throw new Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
				}
				try
				{
					$content = Utilities::getPostDataAsArray();
					$this->fileRestHandler->updateFolderProperties( $path, $content );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to update folder $path.\n{$ex->getMessage()}" );
				}
				$result = array( 'folder' => array( array( 'name' => $name, 'path' => $path ) ) );
			}
			elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
			{
				// older html multipart/form-data post, should be single file
				$files = $_FILES['files'];
				//$files = Utilities::reorgFilePostArray($files);
				if ( 1 < count( $files['error'] ) )
				{
					throw new Exception( "Multiple files uploaded to a single REST resource '$name'." );
				}
				$name = $files['name'][0];
				$fullPathName = $path;
				$path = substr( $path, 0, strripos( $path, '/' ) + 1 );
				$error = $files['error'][0];
				if ( UPLOAD_ERR_OK == $error )
				{
					$tmpName = $files["tmp_name"][0];
					$contentType = $files['type'][0];
					try
					{
						$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
					}
					catch ( Exception $ex )
					{
						throw new Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
					}
				}
				else
				{
					throw new Exception( "Failed to upload file $name.\n$error" );
				}
			}
			else
			{
				// possibly xml or json post either of file or folder to create, copy or move
				try
				{
					$data = Utilities::getPostDataAsArray();
					error_log( print_r( $data, true ) );
					//$this->addFiles($path, $data['files']);
					$result = array();
				}
				catch ( Exception $ex )
				{

				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->checkPermission( 'delete' );
		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path ) ) ? explode( '/', $path ) : array();
		if ( empty( $path ) || empty( $path_array[count( $path_array ) - 1] ) )
		{
			// delete directory of files and the directory itself
			$force = Utilities::boolval( Utilities::getArrayValue( 'force', $_REQUEST, false ) );
			// multi-file or folder delete via post data
			try
			{
				$content = Utilities::getPostDataAsArray();
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to delete storage folders.\n{$ex->getMessage()}" );
			}
			if ( empty( $content ) )
			{
				if ( empty( $path ) )
				{
					throw new Exception( "Empty file or folder path given for storage delete." );
				}
				try
				{
					$this->fileRestHandler->deleteFolder( $path, $force );
					$result = array( 'folder' => array( array( 'path' => $path ) ) );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to delete storage folder '$path'.\n{$ex->getMessage()}" );
				}
			}
			else
			{
				try
				{
					$out = array();
					if ( isset( $content['file'] ) )
					{
						$files = $content['file'];
						$out['file'] = $this->fileRestHandler->deleteFiles( $files, $path );
					}
					if ( isset( $content['folder'] ) )
					{
						$folders = $content['folder'];
						$out['folder'] = $this->fileRestHandler->deleteFolders( $folders, $path, $force );
					}
					$result = $out;
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Failed to delete storage folders.\n{$ex->getMessage()}" );
				}
			}
		}
		else
		{
			// delete file from permanent storage
			try
			{
				$this->fileRestHandler->deleteFile( $path );
				$result = array( 'file' => array( array( 'path' => $path ) ) );
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to delete storage file '$path'.\n{$ex->getMessage()}" );
			}
		}

		return $result;
	}
}
