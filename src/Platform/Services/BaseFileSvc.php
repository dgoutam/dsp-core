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

use Aws\CloudFront\Exception\Exception;
use Kisma\Core\Utility\Option;
use Platform\Interfaces\FileServiceLike;
use Platform\Utility\DataFormat;
use Platform\Utility\FileUtilities;
use Platform\Utility\RestRequest;
use Swagger\Annotations as SWG;

/**
 * BaseFileSvc
 * Base File Storage Service giving REST access to file storage.
 *
 * @SWG\Resource(
 *   resourcePath="/{file}"
 * )
 *
 * @SWG\Model(id="FoldersAndFiles",
 *   @SWG\Property(name="folder",type="Array",items="$ref:Folder",description="An array of folders."),
 *   @SWG\Property(name="file",type="Array",items="$ref:File",description="An array of files.")
 * )
 * @SWG\Model(id="Folders",
 *   @SWG\Property(name="folder",type="Array",items="$ref:Folder",description="An array of folders.")
 * )
 * @SWG\Model(id="Folder",
 *   @SWG\Property(name="name",type="string",description="Identifier/Name for the folder."),
 *   @SWG\Property(name="path",type="string",description="Path of the folder localized to requested folder resource."),
 *   @SWG\Property(name="last_modified",type="string",description="A GMT date timestamp of when the folder was last modified.")
 * )
 * @SWG\Model(id="Files",
 *   @SWG\Property(name="file",type="Array",items="$ref:File",description="An array of files.")
 * )
 * @SWG\Model(id="File",
 *   @SWG\Property(name="name",type="string",description="Identifier/Name for the file."),
 *   @SWG\Property(name="path",type="string",description="Path of the file localized to requested folder resource."),
 *   @SWG\Property(name="content_type",type="string",description="The media type of the content of the file."),
 *   @SWG\Property(name="size",type="string",description="Size of the file in bytes."),
 *   @SWG\Property(name="last_modified",type="string",description="A GMT date timestamp of when the file was last modified.")
 * )
 *
 */
abstract class BaseFileSvc extends RestService implements FileServiceLike
{
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
	 * @throws \Exception
	 */
	protected function handleFile( $dest_path, $dest_name, $source_file, $contentType = '', $extract = false, $clean = false, $check_exist = false )
	{
		$ext = FileUtilities::getFileExtension( $source_file );
		if ( empty( $contentType ) )
		{
			$contentType = FileUtilities::determineContentType( $ext, '', $source_file );
		}
		if ( ( FileUtilities::isZipContent( $contentType ) || ( 'zip' === $ext ) ) && $extract )
		{
			// need to extract zip file and move contents to storage
			$zip = new \ZipArchive();
			if ( true === $zip->open( $source_file ) )
			{
				return $this->extractZipFile( $dest_path, $zip, $clean );
			}
			else
			{
				throw new \Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$name = ( empty( $dest_name ) ? basename( $source_file ) : $dest_name );
			$fullPathName = $dest_path . $name;
			$this->moveFile( $fullPathName, $source_file, $check_exist );

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
	 * @throws \Exception
	 */
	protected function handleFileContent( $dest_path, $dest_name, $content, $contentType = '', $extract = false, $clean = false, $check_exist = false )
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
			$zip = new \ZipArchive();
			if ( true === $zip->open( $tmpName ) )
			{
				return $this->extractZipFile( $dest_path, $zip, $clean );
			}
			else
			{
				throw new \Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$fullPathName = $dest_path . $dest_name;
			$this->writeFile( $fullPathName, $content, false, $check_exist );

			return array( 'file' => array( array( 'name' => $dest_name, 'path' => $fullPathName ) ) );
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/{file}", description="Operations available for File Management Service.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="List root folders and files.",
	 *       notes="Use the available parameters to limit information returned.",
	 *       responseClass="FoldersAndFiles", nickname="getRoot",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folders_only", description="Only include folders in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="files_only", description="Only include files in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="full_tree", description="List the contents of all sub-folders as well.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="zip", description="Return the zipped content of the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Create a root folder or file.",
	 *       notes="Post data should be a single table definition or an array of table definitions.",
	 *       responseClass="Resources", nickname="createTables"
	 *     )
	 *   )
	 * )
	 *
	 * @SWG\Api(
	 *   path="/{file}/{folder_name}/", description="Operations on folders.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="List folders and files in the given folder.",
	 *       notes="This can be a multi-level folder, with each level separated by a '/'. Use the 'folders_only' or 'files_only' parameters to limit the returned listing.",
	 *       responseClass="FoldersAndFiles", nickname="getFoldersAndFiles",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="The path of the folder you want to retrieve the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="folders_only", description="Only include folders in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="files_only", description="Only include files in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="full_tree", description="List the contents of all sub-folders as well.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="zip", description="Return the zipped content of the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Create one or more folders and/or files.",
	 *       notes="Post data as an array of folders and/or files.",
	 *       responseClass="FoldersAndFiles", nickname="createFoldersAndFiles",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="The path of the folder you want to retrieve the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="url", description="The full URL of the file to upload.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="extract", description="Extract an uploaded zip file into the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="clean", description="Option when 'extract' is true, clean the current folder before extracting files and folders.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="check_exist", description="If true, the request fails when the file or folder to create already exists.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *            @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *            @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *            @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="PUT", summary="Update one or more folders and/or files.",
	 *       notes="Post data as an array of folders and/or files.",
	 *       responseClass="FoldersAndFiles", nickname="updateFoldersAndFiles",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="The path of the folder you want to retrieve the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="url", description="The full URL of the file to upload.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="extract", description="Extract an uploaded zip file into the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="clean", description="Option when 'extract' is true, clean the current folder before extracting files and folders.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="check_exist", description="If true, the request fails when the file or folder to create already exists.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="DELETE", summary="Delete one or more folders and/or files.",
	 *       notes="Careful, this deletes a folder and all of its contents.",
	 *       responseClass="FoldersAndFiles", nickname="deleteFoldersAndFiles",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="The path of the folder you want to retrieve the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @SWG\Api(
	 *   path="/{file}/{folder_name}/{file_name}", description="Operations on a single file.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="Download the file and/or its properties.",
	 *       notes="Use the 'properties' parameter (optionally add 'content' for base64 content) to list properties of the file.",
	 *       responseClass="File", nickname="getFile",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="Name of the folder or folder path where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="file_name", description="Name of the file to perform operations on.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="properties", description="Return properties of the folder or file.",
	 *           paramType="query", required="true", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="content", description="Return the content as base64 of the file, only applies when 'properties' is true.",
	 *           paramType="query", required="true", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="download", description="Prompt the user to download the file from the browser.",
	 *           paramType="query", required="true", allowMultiple=false, dataType="boolean"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="PUT", summary="Update content and/or properties of the file.",
	 *       notes="Post data should be an array of field properties for the given field.",
	 *       responseClass="File", nickname="updateFile",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="Name of the folder or folder path where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="file_name", description="Name of the file to perform operations on.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="properties", description="Return properties of the folder or file.",
	 *           paramType="query", required="true", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="content", description="Return the content as base64 of the file, only applies when 'properties' is true.",
	 *           paramType="query", required="true", allowMultiple=false, dataType="boolean"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="download", description="Prompt the user to download the file from the browser.",
	 *           paramType="query", required="true", allowMultiple=false, dataType="boolean"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="DELETE", summary="Delete the file.",
	 *       notes="Careful, this removes the given file from the storage.",
	 *       responseClass="File", nickname="deleteFile",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="folder_name", description="Name of the folder or folder path where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="file_name", description="Name of the file to perform operations on.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _handleResource()
	{
		switch ($this->_action)
		{
			case self::Get:
				$this->checkPermission( 'read' );
				$result = array();
				if ( empty( $this->_resourcePath ) || empty( $this->_resourceArray[count( $this->_resourceArray ) - 1] ) )
				{
					// list from root
					// or if ending in '/' then resource is a folder
					try
					{
						if ( isset( $_REQUEST['properties'] ) )
						{
							// just properties of the directory itself
							$result = $this->getFolderProperties( $this->_resourcePath );
						}
						else
						{
							$asZip = DataFormat::boolval( Option::get( $_REQUEST, 'zip', false ) );
							if ( $asZip )
							{
								$zipFileName = $this->getFolderAsZip( $this->_resourcePath );
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
								$result = null;
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
								$result = $this->getFolderContent( $this->_resourcePath, $include_files, $include_folders, $full_tree );
							}
						}
					}
					catch ( \Exception $ex )
					{
						throw new \Exception( "Failed to retrieve folder content for '$this->_resourcePath'.\n{$ex->getMessage()}", $ex->getCode() );
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
							$content = DataFormat::boolval( Option::get( $_REQUEST, 'content', false ) );
							$result = $this->getFileProperties( $this->_resourcePath, $content );
						}
						else
						{
							$download = DataFormat::boolval( Option::get( $_REQUEST, 'download', false ) );
							if ( $download )
							{
								// stream the file, exits processing
								$this->downloadFile( $this->_resourcePath );
							}
							else
							{
								// stream the file, exits processing
								$this->streamFile( $this->_resourcePath );
							}
							$result = null;	// output handled by file handler
						}
					}
					catch ( \Exception $ex )
					{
						throw new \Exception( "Failed to retrieve file '$this->_resourcePath''.\n{$ex->getMessage()}" );
					}
				}
				break;
			case self::Post:
				$this->checkPermission( 'create' );
				$result = array();
				// possible file handling parameters
				$extract = DataFormat::boolval( Option::get( $_REQUEST, 'extract', false ) );
				$clean = DataFormat::boolval( Option::get( $_REQUEST, 'clean', false ) );
				$checkExist = DataFormat::boolval( Option::get( $_REQUEST, 'check_exist', true ) );
				if ( empty( $this->_resourcePath ) || empty( $this->_resourceArray[count( $this->_resourceArray ) - 1] ) )
				{
					// if ending in '/' then create files or folders in the directory
					if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
					{
						// html5 single posting for file create
						$name = $_SERVER['HTTP_X_FILE_NAME'];
						$fullPathName = $this->_resourcePath . $name;
						try
						{
							$content = RestRequest::getPostData();
							if ( empty( $content ) )
							{
								// empty post?
								error_log( "Empty content in create file $fullPathName." );
							}
							$contentType = Option::get( $_SERVER, 'CONTENT_TYPE', '' );
							$result = $this->handleFileContent( $this->_resourcePath, $name, $content, $contentType, $extract, $clean, $checkExist );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to create file $fullPathName.\n{$ex->getMessage()}" );
						}
					}
					elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
					{
						// html5 single posting for folder create
						$name = $_SERVER['HTTP_X_FOLDER_NAME'];
						$fullPathName = $this->_resourcePath . $name;
						try
						{
							$content = RestRequest::getPostDataAsArray();
							$this->createFolder( $fullPathName, true, $content, true );
							$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to create folder $fullPathName.\n{$ex->getMessage()}" );
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
							$fullPathName = $this->_resourcePath . $name;
							$error = $files['error'];
							if ( $error == UPLOAD_ERR_OK )
							{
								$tmpName = $files['tmp_name'];
								$contentType = $files['type'];
								$result = $this->handleFile( $this->_resourcePath, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							}
							else
							{
								throw new \Exception( "Failed to create file $fullPathName.\n$error" );
							}
						}
						else
						{
							$out = array();
							//$files = Utilities::reorgFilePostArray($files);
							foreach ( $files['error'] as $key => $error )
							{
								$name = $files['name'][$key];
								$fullPathName = $this->_resourcePath . $name;
								if ( $error == UPLOAD_ERR_OK )
								{
									$tmpName = $files['tmp_name'][$key];
									$contentType = $files['type'][$key];
									$tmp = $this->handleFile( $this->_resourcePath, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
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
						$fileUrl = Option::get( $_REQUEST, 'url', '' );
						if ( !empty( $fileUrl ) )
						{
							// upload a file from a url, could be expandable zip
							$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
							try
							{
								$result = $this->handleFile( $this->_resourcePath, '', $tmpName, '', $extract, $clean, $checkExist );
							}
							catch ( \Exception $ex )
							{
								throw new \Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
							}
						}
						else
						{
							try
							{
								$data = RestRequest::getPostDataAsArray();
								if ( empty( $data ) )
								{
									// create folder from resource path
									$this->createFolder( $this->_resourcePath );
									$result = array( 'folder' => array( array( 'path' => $this->_resourcePath ) ) );
								}
								else
								{
									$out = array( 'folder' => array(), 'file' => array() );
									$folders = Option::get( $data, 'folder', null );
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
											$name = Option::get( $folder, 'name', '' );
											if ( isset( $folder['source_path'] ) )
											{
												// copy or move
												$srcPath = $folder['source_path'];
												if ( empty( $name ) )
												{
													$name = FileUtilities::getNameFromPath( $srcPath );
												}
												$fullPathName = $this->_resourcePath . $name . '/';
												$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												try
												{
													$this->copyFolder( $fullPathName, $srcPath, true );
													$deleteSource = DataFormat::boolval( Option::get( $folder, 'delete_source', false ) );
													if ( $deleteSource )
													{
														$this->deleteFolder( $srcPath, true );
													}
												}
												catch ( \Exception $ex )
												{
													$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
											else
											{
												$fullPathName = $this->_resourcePath . $name;
												$content = Option::get( $folder, 'content', '' );
												$isBase64 = DataFormat::boolval( Option::get( $folder, 'is_base64', false ) );
												if ( $isBase64 )
												{
													$content = base64_decode( $content );
												}
												$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												try
												{
													$this->createFolder( $fullPathName, true, $content );
												}
												catch ( \Exception $ex )
												{
													$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
										}
									}
									$files = Option::get( $data, 'file', null );
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
											$name = Option::get( $file, 'name', '' );
											if ( isset( $file['source_path'] ) )
											{
												// copy or move
												$srcPath = $file['source_path'];
												if ( empty( $name ) )
												{
													$name = FileUtilities::getNameFromPath( $srcPath );
												}
												$fullPathName = $this->_resourcePath . $name;
												$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												try
												{
													$this->copyFile( $fullPathName, $srcPath, true );
													$deleteSource = DataFormat::boolval( Option::get( $file, 'delete_source', false ) );
													if ( $deleteSource )
													{
														$this->deleteFile( $srcPath );
													}
												}
												catch ( \Exception $ex )
												{
													$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
											elseif ( isset( $file['content'] ) )
											{
												$fullPathName = $this->_resourcePath . $name;
												$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												$content = Option::get( $file, 'content', '' );
												$isBase64 = DataFormat::boolval( Option::get( $file, 'is_base64', false ) );
												if ( $isBase64 )
												{
													$content = base64_decode( $content );
												}
												try
												{
													$this->writeFile( $fullPathName, $content );
												}
												catch ( \Exception $ex )
												{
													$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
										}
									}
									$result = $out;
								}
							}
							catch ( \Exception $ex )
							{
								throw new \Exception( "Failed to create folders or files from request.\n{$ex->getMessage()}" );
							}
						}
					}
				}
				else
				{
					// if ending in file name, create the file or folder
					$name = substr( $this->_resourcePath, strripos( $this->_resourcePath, '/' ) + 1 );
					if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
					{
						$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
						if ( 0 !== strcasecmp( $name, $x_file_name ) )
						{
							throw new \Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
						}
						try
						{
							$content = RestRequest::getPostData();
							if ( empty( $content ) )
							{
								// empty post?
								error_log( "Empty content in write file $this->_resourcePath to storage." );
							}
							$contentType = Option::get( $_SERVER, 'CONTENT_TYPE', '' );
							$path = substr( $this->_resourcePath, 0, strripos( $this->_resourcePath, '/' ) + 1 );
							$result = $this->handleFileContent( $path, $name, $content, $contentType, $extract, $clean, $checkExist );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to create file $this->_resourcePath.\n{$ex->getMessage()}" );
						}
					}
					elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
					{
						$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
						if ( 0 !== strcasecmp( $name, $x_folder_name ) )
						{
							throw new \Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
						}
						try
						{
							$content = RestRequest::getPostDataAsArray();
							$this->createFolder( $this->_resourcePath, true, $content );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to create file $this->_resourcePath.\n{$ex->getMessage()}" );
						}
						$result = array( 'folder' => array( array( 'name' => $name, 'path' => $this->_resourcePath ) ) );
					}
					elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
					{
						// older html multipart/form-data post, should be single file
						$files = $_FILES['files'];
						//$files = Utilities::reorgFilePostArray($files);
						if ( 1 < count( $files['error'] ) )
						{
							throw new \Exception( "Multiple files uploaded to a single REST resource '$name'." );
						}
						$name = $files['name'][0];
						$fullPathName = $this->_resourcePath;
						$path = substr( $this->_resourcePath, 0, strripos( $this->_resourcePath, '/' ) + 1 );
						$error = $files['error'][0];
						if ( UPLOAD_ERR_OK == $error )
						{
							$tmpName = $files["tmp_name"][0];
							$contentType = $files['type'][0];
							try
							{
								$result = $this->handleFile( $path, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							}
							catch ( \Exception $ex )
							{
								throw new \Exception( "Failed to create file $fullPathName.\n{$ex->getMessage()}" );
							}
						}
						else
						{
							throw new \Exception( "Failed to upload file $name.\n$error" );
						}
					}
					else
					{
						// possibly xml or json post either of file or folder to create, copy or move
						try
						{
							$data = RestRequest::getPostDataAsArray();
							error_log( print_r( $data, true ) );
							//$this->addFiles($path, $data['files']);
							$result = array();
						}
						catch ( \Exception $ex )
						{

						}
					}
				}
				break;
			case self::Put:
			case self::Patch:
			case self::Merge:
				$this->checkPermission( 'update' );
				$result = array();
				// possible file handling parameters
				$extract = DataFormat::boolval( Option::get( $_REQUEST, 'extract', false ) );
				$clean = DataFormat::boolval( Option::get( $_REQUEST, 'clean', false ) );
				$checkExist = false;
				if ( empty( $this->_resourcePath ) || empty( $this->_resourceArray[count( $this->_resourceArray ) - 1] ) )
				{
					// if ending in '/' then create files or folders in the directory
					if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
					{
						// html5 single posting for file create
						$name = $_SERVER['HTTP_X_FILE_NAME'];
						$fullPathName = $this->_resourcePath . $name;
						try
						{
							$content = RestRequest::getPostData();
							if ( empty( $content ) )
							{
								// empty post?
								error_log( "Empty content in update file $fullPathName." );
							}
							$contentType = Option::get( $_SERVER, 'CONTENT_TYPE', '' );
							$result = $this->handleFileContent( $this->_resourcePath, $name, $content, $contentType, $extract, $clean, $checkExist );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
						}
					}
					elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
					{
						// html5 single posting for folder create
						$name = $_SERVER['HTTP_X_FOLDER_NAME'];
						$fullPathName = $this->_resourcePath . $name;
						try
						{
							$content = RestRequest::getPostDataAsArray();
							$this->createFolder( $fullPathName, true, $content, true );
							$result = array( 'folder' => array( array( 'name' => $name, 'path' => $fullPathName ) ) );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to update folder $fullPathName.\n{$ex->getMessage()}" );
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
							$fullPathName = $this->_resourcePath . $name;
							$error = $files['error'];
							if ( $error == UPLOAD_ERR_OK )
							{
								$tmpName = $files['tmp_name'];
								$contentType = $files['type'];
								$result = $this->handleFile( $this->_resourcePath, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							}
							else
							{
								throw new \Exception( "Failed to update file $fullPathName.\n$error" );
							}
						}
						else
						{
							$out = array();
							//$files = Utilities::reorgFilePostArray($files);
							foreach ( $files['error'] as $key => $error )
							{
								$name = $files['name'][$key];
								$fullPathName = $this->_resourcePath . $name;
								if ( $error == UPLOAD_ERR_OK )
								{
									$tmpName = $files['tmp_name'][$key];
									$contentType = $files['type'][$key];
									$tmp = $this->handleFile( $this->_resourcePath, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
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
						$fileUrl = Option::get( $_REQUEST, 'url', '' );
						if ( !empty( $fileUrl ) )
						{
							// upload a file from a url, could be expandable zip
							$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
							try
							{
								$result = $this->handleFile( $this->_resourcePath, '', $tmpName, '', $extract, $clean, $checkExist );
							}
							catch ( \Exception $ex )
							{
								throw new \Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
							}
						}
						else
						{
							try
							{
								$data = RestRequest::getPostDataAsArray();
								if ( empty( $data ) )
								{
									// create folder from resource path
									$this->createFolder( $this->_resourcePath );
									$result = array( 'folder' => array( array( 'path' => $this->_resourcePath ) ) );
								}
								else
								{
									$out = array( 'folder' => array(), 'file' => array() );
									$folders = Option::get( $data, 'folder', null );
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
											$name = Option::get( $folder, 'name', '' );
											if ( isset( $folder['source_path'] ) )
											{
												// copy or move
												$srcPath = $folder['source_path'];
												if ( empty( $name ) )
												{
													$name = FileUtilities::getNameFromPath( $srcPath );
												}
												$fullPathName = $this->_resourcePath . $name . '/';
												$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												try
												{
													$this->copyFolder( $fullPathName, $srcPath, true );
													$deleteSource = DataFormat::boolval( Option::get( $folder, 'delete_source', false ) );
													if ( $deleteSource )
													{
														$this->deleteFolder( $srcPath, true );
													}
												}
												catch ( \Exception $ex )
												{
													$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
											else
											{
												$fullPathName = $this->_resourcePath . $name;
												$content = Option::get( $folder, 'content', '' );
												$isBase64 = DataFormat::boolval( Option::get( $folder, 'is_base64', false ) );
												if ( $isBase64 )
												{
													$content = base64_decode( $content );
												}
												$out['folder'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												try
												{
													$this->createFolder( $fullPathName, true, $content );
												}
												catch ( \Exception $ex )
												{
													$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
										}
									}
									$files = Option::get( $data, 'file', null );
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
											$name = Option::get( $file, 'name', '' );
											if ( isset( $file['source_path'] ) )
											{
												// copy or move
												$srcPath = $file['source_path'];
												if ( empty( $name ) )
												{
													$name = FileUtilities::getNameFromPath( $srcPath );
												}
												$fullPathName = $this->_resourcePath . $name;
												$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												try
												{
													$this->copyFile( $fullPathName, $srcPath, true );
													$deleteSource = DataFormat::boolval( Option::get( $file, 'delete_source', false ) );
													if ( $deleteSource )
													{
														$this->deleteFile( $srcPath );
													}
												}
												catch ( \Exception $ex )
												{
													$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
											elseif ( isset( $file['content'] ) )
											{
												$fullPathName = $this->_resourcePath . $name;
												$out['file'][$key] = array( 'name' => $name, 'path' => $fullPathName );
												$content = Option::get( $file, 'content', '' );
												$isBase64 = DataFormat::boolval( Option::get( $file, 'is_base64', false ) );
												if ( $isBase64 )
												{
													$content = base64_decode( $content );
												}
												try
												{
													$this->writeFile( $fullPathName, $content );
												}
												catch ( \Exception $ex )
												{
													$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
												}
											}
										}
									}
									$result = $out;
								}
							}
							catch ( \Exception $ex )
							{
								throw new \Exception( "Failed to update folders or files from request.\n{$ex->getMessage()}" );
							}
						}
					}
				}
				else
				{
					// if ending in file name, update the file or folder
					$name = substr( $this->_resourcePath, strripos( $this->_resourcePath, '/' ) + 1 );
					if ( isset( $_SERVER['HTTP_X_FILE_NAME'] ) && !empty( $_SERVER['HTTP_X_FILE_NAME'] ) )
					{
						$x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
						if ( 0 !== strcasecmp( $name, $x_file_name ) )
						{
							throw new \Exception( "Header file name '$x_file_name' mismatched with REST resource '$name'." );
						}
						try
						{
							$content = RestRequest::getPostData();
							if ( empty( $content ) )
							{
								// empty post?
								error_log( "Empty content in write file $this->_resourcePath to storage." );
							}
							$contentType = Option::get( $_SERVER, 'CONTENT_TYPE', '' );
							$this->_resourcePath = substr( $this->_resourcePath, 0, strripos( $this->_resourcePath, '/' ) + 1 );
							$result = $this->handleFileContent( $this->_resourcePath, $name, $content, $contentType, $extract, $clean, $checkExist );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to update file $this->_resourcePath.\n{$ex->getMessage()}" );
						}
					}
					elseif ( isset( $_SERVER['HTTP_X_FOLDER_NAME'] ) && !empty( $_SERVER['HTTP_X_FOLDER_NAME'] ) )
					{
						$x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
						if ( 0 !== strcasecmp( $name, $x_folder_name ) )
						{
							throw new \Exception( "Header folder name '$x_folder_name' mismatched with REST resource '$name'." );
						}
						try
						{
							$content = RestRequest::getPostDataAsArray();
							$this->updateFolderProperties( $this->_resourcePath, $content );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to update folder $this->_resourcePath.\n{$ex->getMessage()}" );
						}
						$result = array( 'folder' => array( array( 'name' => $name, 'path' => $this->_resourcePath ) ) );
					}
					elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
					{
						// older html multipart/form-data post, should be single file
						$files = $_FILES['files'];
						//$files = Utilities::reorgFilePostArray($files);
						if ( 1 < count( $files['error'] ) )
						{
							throw new \Exception( "Multiple files uploaded to a single REST resource '$name'." );
						}
						$name = $files['name'][0];
						$fullPathName = $this->_resourcePath;
						$this->_resourcePath = substr( $this->_resourcePath, 0, strripos( $this->_resourcePath, '/' ) + 1 );
						$error = $files['error'][0];
						if ( UPLOAD_ERR_OK == $error )
						{
							$tmpName = $files["tmp_name"][0];
							$contentType = $files['type'][0];
							try
							{
								$result = $this->handleFile( $this->_resourcePath, $name, $tmpName, $contentType, $extract, $clean, $checkExist );
							}
							catch ( \Exception $ex )
							{
								throw new \Exception( "Failed to update file $fullPathName.\n{$ex->getMessage()}" );
							}
						}
						else
						{
							throw new \Exception( "Failed to upload file $name.\n$error" );
						}
					}
					else
					{
						// possibly xml or json post either of file or folder to create, copy or move
						try
						{
							$data = RestRequest::getPostDataAsArray();
							error_log( print_r( $data, true ) );
							//$this->addFiles($this->_resourcePath, $data['files']);
							$result = array();
						}
						catch ( \Exception $ex )
						{

						}
					}
				}
				break;
			case self::Delete:
				$this->checkPermission( 'delete' );
				if ( empty( $this->_resourcePath ) || empty( $this->_resourceArray[count( $this->_resourceArray ) - 1] ) )
				{
					// delete directory of files and the directory itself
					$force = DataFormat::boolval( Option::get( $_REQUEST, 'force', false ) );
					// multi-file or folder delete via post data
					try
					{
						$content = RestRequest::getPostDataAsArray();
					}
					catch ( \Exception $ex )
					{
						throw new \Exception( "Failed to delete storage folders.\n{$ex->getMessage()}" );
					}
					if ( empty( $content ) )
					{
						if ( empty( $this->_resourcePath ) )
						{
							throw new \Exception( "Empty file or folder path given for storage delete." );
						}
						try
						{
							$this->deleteFolder( $this->_resourcePath, $force );
							$result = array( 'folder' => array( array( 'path' => $this->_resourcePath ) ) );
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to delete storage folder '$this->_resourcePath'.\n{$ex->getMessage()}" );
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
								$out['file'] = $this->deleteFiles( $files, $this->_resourcePath );
							}
							if ( isset( $content['folder'] ) )
							{
								$folders = $content['folder'];
								$out['folder'] = $this->deleteFolders( $folders, $this->_resourcePath, $force );
							}
							$result = $out;
						}
						catch ( \Exception $ex )
						{
							throw new \Exception( "Failed to delete storage folders.\n{$ex->getMessage()}" );
						}
					}
				}
				else
				{
					// delete file from permanent storage
					try
					{
						$this->deleteFile( $this->_resourcePath );
						$result = array( 'file' => array( array( 'path' => $this->_resourcePath ) ) );
					}
					catch ( \Exception $ex )
					{
						throw new \Exception( "Failed to delete storage file '$this->_resourcePath'.\n{$ex->getMessage()}" );
					}
				}
				break;
			default:
				return false;
		}

		return $result;
	}

	/**
	 * @throw \Exception
	 */
	abstract public function checkContainerForWrite();

	/**
	 * @param $path
	 *
	 * @return bool
	 * @throw \Exception
	 */
	abstract public function folderExists( $path );

	/**
	 * @param string $path
	 * @param bool   $include_files
	 * @param bool   $include_folders
	 * @param bool   $full_tree
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function getFolderContent( $path, $include_files = true, $include_folders = true, $full_tree = false );

	/**
	 * @param $path
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function getFolderProperties( $path );

	/**
	 * @param string $path
	 * @param array  $properties
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function createFolder( $path, $properties = array() );

	/**
	 * @param string $path
	 * @param string $properties
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function updateFolderProperties( $path, $properties = '' );

	/**
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function copyFolder( $dest_path, $src_path, $check_exist = false );

	/**
	 * @param string $path Folder path relative to the service root directory
	 * @param  bool  $force
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function deleteFolder( $path, $force = false );

	/**
	 * @param array  $folders Array of folder paths that are relative to the root directory
	 * @param string $root    directory from which to delete
	 * @param  bool  $force
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function deleteFolders( $folders, $root = '', $force = false );

	/**
	 * @param $path
	 *
	 * @return bool
	 * @throws \Exception
	 */
	abstract public function fileExists( $path );

	/**
	 * @param         $path
	 * @param  string $local_file
	 * @param  bool   $content_as_base
	 *
	 * @return string
	 * @throws \Exception
	 */
	abstract public function getFileContent( $path, $local_file = '', $content_as_base = true );

	/**
	 * @param       $path
	 * @param  bool $include_content
	 * @param  bool $content_as_base
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function getFileProperties( $path, $include_content = false, $content_as_base = true );

	/**
	 * @param string $path
	 *
	 * @return null
	 */
	abstract public function streamFile( $path );

	/**
	 * @param string $path
	 *
	 * @return null
	 */
	abstract public function downloadFile( $path );

	/**
	 * @param string  $path
	 * @param string  $content
	 * @param boolean $content_is_base
	 * @param bool    $check_exist
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function writeFile( $path, $content, $content_is_base = true, $check_exist = false );

	/**
	 * @param string $path
	 * @param string $local_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function moveFile( $path, $local_path, $check_exist = false );

	/**
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function copyFile( $dest_path, $src_path, $check_exist = false );

	/**
	 * @param string $path File path relative to the service root directory
	 *
	 * @return void
	 * @throws \Exception
	 */
	abstract public function deleteFile( $path );

	/**
	 * @param array  $files Array of file paths relative to root
	 * @param string $root
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function deleteFiles( $files, $root = '' );

	/**
	 * @param            $path
	 * @param \ZipArchive $zip
	 * @param bool       $clean
	 * @param string     $drop_path
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function extractZipFile( $path, $zip, $clean = false, $drop_path = '' );

	/**
	 * @param string          $path
	 * @param null|\ZipArchive $zip
	 * @param string          $zipFileName
	 * @param bool            $overwrite
	 *
	 * @return string Zip File Name created/updated
	 */
	abstract public function getFolderAsZip( $path, $zip = null, $zipFileName = '', $overwrite = false );

}
