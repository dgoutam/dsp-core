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
namespace DreamFactory\Platform\Services;

use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\FilterInput;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Interfaces\FileServiceLike;
use DreamFactory\Common\Utility\DataFormat;
use DreamFactory\Platform\Utility\FileUtilities;
use DreamFactory\Platform\Utility\RestData;
use Swagger\Annotations as SWG;

/**
 * BaseFileSvc
 * Base File Storage Service giving REST access to file storage.
 *
 * @SWG\Resource(
 *   resourcePath="/{file}"
 * )
 *
 * @SWG\Model(id="Containers",
 * @SWG\Property(name="container",type="Array",items="$ref:Container",description="An array of containers.")
 * )
 * @SWG\Model(id="Container",
 * @SWG\Property(name="name",type="string",description="Identifier/Name for the container."),
 * @SWG\Property(name="path",type="string",description="Same as name for the container."),
 * @SWG\Property(name="last_modified",type="string",description="A GMT date timestamp of when the container was last modified."),
 * @SWG\Property(name="_property_",type="string",description="Storage type specific properties."),
 * @SWG\Property(name="metadata",type="Array",items="$ref:string",description="An array of name-value pairs.")
 * )
 * @SWG\Model(id="FoldersAndFiles",
 * @SWG\Property(name="name",type="string",description="Identifier/Name for the current folder, localized to requested folder resource."),
 * @SWG\Property(name="path",type="string",description="Full path of the folder, from the service including container."),
 * @SWG\Property(name="container",type="string",description="Container for the current folder."),
 * @SWG\Property(name="last_modified",type="string",description="A GMT date timestamp of when the folder was last modified."),
 * @SWG\Property(name="_property_",type="string",description="Storage type specific properties."),
 * @SWG\Property(name="metadata",type="Array",items="$ref:string",description="An array of name-value pairs."),
 * @SWG\Property(name="folder",type="Array",items="$ref:Folder",description="An array of contained folders."),
 * @SWG\Property(name="file",type="Array",items="$ref:File",description="An array of contained files.")
 * )
 * @SWG\Model(id="Folder",
 * @SWG\Property(name="name",type="string",description="Identifier/Name for the folder, localized to requested folder resource."),
 * @SWG\Property(name="path",type="string",description="Full path of the folder, from the service including container."),
 * @SWG\Property(name="last_modified",type="string",description="A GMT date timestamp of when the folder was last modified."),
 * @SWG\Property(name="_property_",type="string",description="Storage type specific properties."),
 * @SWG\Property(name="metadata",type="Array",items="$ref:string",description="An array of name-value pairs.")
 * )
 * @SWG\Model(id="File",
 * @SWG\Property(name="name",type="string",description="Identifier/Name for the file, localized to requested folder resource."),
 * @SWG\Property(name="path",type="string",description="Full path of the file, from the service including container."),
 * @SWG\Property(name="content_type",type="string",description="The media type of the content of the file."),
 * @SWG\Property(name="content_length",type="string",description="Size of the file in bytes."),
 * @SWG\Property(name="last_modified",type="string",description="A GMT date timestamp of when the file was last modified."),
 * @SWG\Property(name="_property_",type="string",description="Storage type specific properties."),
 * @SWG\Property(name="metadata",type="Array",items="$ref:string",description="An array of name-value pairs.")
 * )
 *
 */
abstract class BaseFileSvc extends BasePlatformRestService implements FileServiceLike
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string Storage container name
	 */
	protected $_container = null;

	/**
	 * @var string Full folder path of the resource
	 */
	protected $_folderPath = null;

	/**
	 * @var string Full file path of the resource
	 */
	protected $_filePath = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Setup container and paths
	 */
	protected function _detectResourceMembers()
	{
		parent::_detectResourceMembers();

		$this->_container = Option::get( $this->_resourceArray, 0 );
		if ( !empty( $this->_container ) )
		{
			$_temp = substr( $this->_resourcePath, strlen( $this->_container . '/' ) );
			if ( false !== $_temp )
			{
				// ending in / is folder
				if ( '/' == substr( $_temp, -1, 1 ) )
				{
					if ( '/' !== $_temp )
					{
						$this->_folderPath = $_temp;
					}
				}
				else
				{
					$this->_folderPath = dirname( $_temp ) . '/';
					$this->_filePath = $_temp;
				}
			}
		}
	}

	protected function _listResources()
	{
		$result = $this->listContainers();

		return array( 'resource' => $result );
	}

	/**
	 * @SWG\Api(
	 *   path="/{file}", description="Operations available for File Storage Service.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List all containers.",
	 *       notes="List the names of the available containers in this storage. Use 'include_properties' to include any properties of the containers.",
	 *       responseClass="Containers", nickname="getContainers",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="include_properties", description="Return all properties of the container, if any.",
	 *           paramType="query", required=false, allowMultiple=false, dataType="boolean", defaultValue=false
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="POST", summary="Create one or more containers.",
	 *       notes="Post data should be a single container definition or an array of container definitions.",
	 *       responseClass="Containers", nickname="createContainers",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="data", description="Array of containers to create.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Containers"
	 *         ),
	 * @SWG\Parameter(
	 *           name="check_exist", description="If true, the request fails when the container to create already exists.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="DELETE", summary="Delete one or more containers.",
	 *       notes="Post data should be a single container definition or an array of container definitions.",
	 *       responseClass="Containers", nickname="deleteContainers",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="data", description="Array of containers to delete.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Containers"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @SWG\Api(
	 *   path="/{file}/{container}/", description="Operations on containers.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List the container's properties, including folders and files.",
	 *       notes="Use 'include_properties' to get properties of the container. Use the 'include_folders' and/or 'include_files' to return a listing.",
	 *       responseClass="FoldersAndFiles", nickname="getContainer",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container you want to retrieve the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_properties", description="Return all properties of the container, if any.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_folders", description="Include folders in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=true
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_files", description="Include files in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=true
	 *         ),
	 * @SWG\Parameter(
	 *           name="full_tree", description="List the contents of all sub-folders as well.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="zip", description="Return the zipped content of the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="POST", summary="Add folders and/or files to the container.",
	 *       notes="Post data as an array of folders and/or files.",
	 *       responseClass="FoldersAndFiles", nickname="createContainer",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container you want to put the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="url", description="The full URL of the file to upload.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="extract", description="Extract an uploaded zip file into the container.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="clean", description="Option when 'extract' is true, clean the current folder before extracting files and folders.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="check_exist", description="If true, the request fails when the file or folder to create already exists.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="data", description="Array of folders and/or files.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="FoldersAndFiles"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="PATCH", summary="Update properties of the container.",
	 *       notes="Post data as an array of container properties.",
	 *       responseClass="Container", nickname="updateContainerProperties",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container you want to put the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="data", description="An array of container properties.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Container"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="DELETE", summary="Delete the container or folders and/or files from the container.",
	 *       notes="Careful, this deletes the requested container and all of its contents, unless there are posted specific folders and/or files.",
	 *       responseClass="FoldersAndFiles", nickname="deleteContainer",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container you want to delete from.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="data", description="An array of folders and/or files to delete from the container.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="FoldersAndFiles"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @SWG\Api(
	 *   path="/{file}/{container}/{folder_path}/", description="Operations on folders.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List the folder's properties, or sub-folders and files.",
	 *       notes="Use with no parameters to get properties of the folder or use the 'include_folders' and/or 'include_files' to return a listing.",
	 *       responseClass="FoldersAndFiles", nickname="getFolder",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container from which you want to retrieve contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="folder_path", description="The path of the folder you want to retrieve. This can be a sub-folder, with each level separated by a '/'",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_properties", description="Return all properties of the folder, if any.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_folders", description="Include folders in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=true
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_files", description="Include files in the returned listing.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=true
	 *         ),
	 * @SWG\Parameter(
	 *           name="full_tree", description="List the contents of all sub-folders as well.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="zip", description="Return the zipped content of the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container or folder does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="POST", summary="Create one or more sub-folders and/or files.",
	 *       notes="Post data as an array of folders and/or files. Folders are created if they do not exist",
	 *       responseClass="FoldersAndFiles", nickname="createFolder",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container where you want to put the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="folder_path", description="The path of the folder where you want to put the contents. This can be a sub-folder, with each level separated by a '/'",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="url", description="The full URL of the file to upload.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="extract", description="Extract an uploaded zip file into the folder.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="clean", description="Option when 'extract' is true, clean the current folder before extracting files and folders.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="check_exist", description="If true, the request fails when the file or folder to create already exists.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="data", description="Array of folders and/or files.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="FoldersAndFiles"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="PATCH", summary="Update folder properties.",
	 *       notes="Post data as an array of folder properties.",
	 *       responseClass="Folder", nickname="updateFolderProperties",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container where you want to put the contents.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="folder_path", description="The path of the folder you want to update. This can be a sub-folder, with each level separated by a '/'",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="data", description="Array of folder properties.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="FoldersAndFiles"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container or folder does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="DELETE", summary="Delete one or more sub-folders and/or files.",
	 *       notes="Careful, this deletes the requested folder and all of its contents, unless there are posted specific sub-folders and/or files.",
	 *       responseClass="FoldersAndFiles", nickname="deleteFolder",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="The name of the container where the folder exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="folder_path", description="The path of the folder where you want to delete contents. This can be a sub-folder, with each level separated by a '/'",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="data", description="Array of folder and files to delete.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="FoldersAndFiles"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @SWG\Api(
	 *   path="/{file}/{container}/{file_path}", description="Operations on individual files.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="Download the file contents and/or its properties.",
	 *       notes="By default, the file is streamed to the browser. Use the 'download' parameter to prompt for download.
	 *              Use the 'include_properties' parameter (optionally add 'content' to include base64 content) to list properties of the file.",
	 *       responseClass="File", nickname="getFile",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="Name of the container where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="file_path", description="Path and name of the file to retrieve.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="include_properties", description="Return properties of the file.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="content", description="Return the content as base64 of the file, only applies when 'include_properties' is true.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         ),
	 * @SWG\Parameter(
	 *           name="download", description="Prompt the user to download the file from the browser.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean", defaultValue=false
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container, folder, or file does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="POST", summary="Create a new file.",
	 *       notes="Post data should be the contents of the file or an object with file properties.",
	 *       responseClass="File", nickname="createFile",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="Name of the container where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="file_path", description="Path and name of the file to create.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="check_exist", description="If true, the request fails when the file to create already exists.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="boolean"
	 *         ),
	 * @SWG\Parameter(
	 *           name="properties", description="Properties of the file.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="File"
	 *         ),
	 * @SWG\Parameter(
	 *           name="content", description="The content of the file.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="string"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container or folder does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="PUT", summary="Update content of the file.",
	 *       notes="Post data should be the contents of the file.",
	 *       responseClass="File", nickname="updateFile",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="Name of the container where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="file_path", description="Path and name of the file to update.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="content", description="The content of the file.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="string"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container, folder, or file does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="PATCH", summary="Update properties of the file.",
	 *       notes="Post data should be the file properties.",
	 *       responseClass="File", nickname="updateFileProperties",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="Name of the container where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="file_path", description="Path and name of the file to update.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="properties", description="Properties of the file.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="File"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container, folder, or file does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 * @SWG\Operation(
	 *       httpMethod="DELETE", summary="Delete the file.",
	 *       notes="Careful, this removes the given file from the storage.",
	 *       responseClass="File", nickname="deleteFile",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *           name="container", description="Name of the container where the file exists.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         ),
	 * @SWG\Parameter(
	 *           name="file_path", description="Path and name of the file to delete.",
	 *           paramType="path", required="true", allowMultiple=false, dataType="string"
	 *         )
	 *       ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="404", reason="Not Found - Requested container, folder, or file does not exist."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
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
		switch ( $this->_action )
		{
			case self::Get:
				$this->checkPermission( 'read', $this->_container );
				if ( empty( $this->_container ) )
				{
					// no resource
					$includeProperties = FilterInput::request( 'include_properties', false, FILTER_VALIDATE_BOOLEAN );
					if ( !$includeProperties )
					{
						return $this->_listResources();
					}
					$result = $this->listContainers( true );
					$result = array( 'container' => $result );
				}
				else if ( empty( $this->_folderPath ) )
				{
					// resource is a container
					$includeProperties = FilterInput::request( 'include_properties', false, FILTER_VALIDATE_BOOLEAN );
					$includeFiles = FilterInput::request( 'include_files', true, FILTER_VALIDATE_BOOLEAN );
					$includeFolders = FilterInput::request( 'include_folders', true, FILTER_VALIDATE_BOOLEAN );
					$fullTree = FilterInput::request( 'full_tree', false, FILTER_VALIDATE_BOOLEAN );
					$asZip = FilterInput::request( 'zip', false, FILTER_VALIDATE_BOOLEAN );
					if ( $asZip )
					{
						$zipFileName = $this->getFolderAsZip( $this->_container, '' );
						$fd = fopen( $zipFileName, "r" );
						if ( $fd )
						{
							header( 'Content-type: application/zip' );
							header( 'Content-Disposition: filename=" ' . basename( $zipFileName ) . '"' );
							header( 'Content-length: ' . filesize( $zipFileName ) );
							header( 'Cache-control: private' ); //use this to open files directly
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
						$result = $this->getContainer( $this->_container, $includeFiles, $includeFolders, $fullTree, $includeProperties );
					}
				}
				else if ( empty( $this->_filePath ) )
				{
					// resource is a folder
					$includeProperties = FilterInput::request( 'include_properties', false, FILTER_VALIDATE_BOOLEAN );
					$includeFiles = FilterInput::request( 'include_files', true, FILTER_VALIDATE_BOOLEAN );
					$includeFolders = FilterInput::request( 'include_folders', true, FILTER_VALIDATE_BOOLEAN );
					$fullTree = FilterInput::request( 'full_tree', false, FILTER_VALIDATE_BOOLEAN );
					$asZip = FilterInput::request( 'zip', false, FILTER_VALIDATE_BOOLEAN );
					if ( $asZip )
					{
						$zipFileName = $this->getFolderAsZip( $this->_container, $this->_folderPath );
						$fd = fopen( $zipFileName, "r" );
						if ( $fd )
						{
							header( 'Content-type: application/zip' );
							header( 'Content-Disposition: filename=" ' . basename( $zipFileName ) . '"' );
							header( 'Content-length: ' . filesize( $zipFileName ) );
							header( 'Cache-control: private' ); //use this to open files directly
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
						$result = $this->getFolder( $this->_container, $this->_folderPath, $includeFiles, $includeFolders, $fullTree, $includeProperties );
					}
				}
				else
				{
					// resource is a file
					$includeProperties = FilterInput::request( 'include_properties', false, FILTER_VALIDATE_BOOLEAN );
					if ( $includeProperties )
					{
						// just properties of the file itself
						$content = FilterInput::request( 'content', false, FILTER_VALIDATE_BOOLEAN );
						$result = $this->getFileProperties( $this->_container, $this->_filePath, $content );
					}
					else
					{
						$download = FilterInput::request( 'download', false, FILTER_VALIDATE_BOOLEAN );
						// stream the file, exits processing
						$this->streamFile( $this->_container, $this->_filePath, $download );
						$result = null; // output handled by file handler
					}
				}
				break;
			case self::Post:
			case self::Put:
				$this->checkPermission( 'create', $this->_container );
				if ( empty( $this->_container ) )
				{
					// create one or more containers
					$checkExist = FilterInput::request( 'check_exist', false, FILTER_VALIDATE_BOOLEAN );
					$data = RestData::getPostDataAsArray();
					$containers = Option::get( $data, 'container' );
					if ( empty( $containers ) )
					{
						$containers = Option::getDeep( $data, 'containers', 'container' );
					}
					if ( !empty( $containers ) )
					{
						$result = $this->createContainers( $containers, $checkExist );
						$result = array( 'container' => $result );
					}
					else
					{
						$result = $this->createContainer( $data, $checkExist );
					}
				}
				else if ( empty( $this->_folderPath ) || empty( $this->_filePath ) )
				{
					// create folders and files
					// possible file handling parameters
					$extract = FilterInput::request( 'extract', false, FILTER_VALIDATE_BOOLEAN );
					$clean = FilterInput::request( 'clean', false, FILTER_VALIDATE_BOOLEAN );
					$checkExist = FilterInput::request( 'check_exist', false, FILTER_VALIDATE_BOOLEAN );
					$fileNameHeader = FilterInput::server( 'HTTP_X_FILE_NAME' );
					$folderNameHeader = FilterInput::server( 'HTTP_X_FOLDER_NAME' );
					$fileUrl = FilterInput::request( 'url', '', FILTER_SANITIZE_URL );
					if ( !empty( $fileNameHeader ) )
					{
						// html5 single posting for file create
						$content = RestData::getPostData();
						$contentType = FilterInput::server( 'CONTENT_TYPE', '' );
						$result = $this->_handleFileContent(
							$this->_folderPath,
							$fileNameHeader,
							$content,
							$contentType,
							$extract,
							$clean,
							$checkExist
						);
					}
					elseif ( !empty( $folderNameHeader ) )
					{
						// html5 single posting for folder create
						$fullPathName = $this->_folderPath . $folderNameHeader;
						$content = RestData::getPostDataAsArray();
						$this->createFolder( $this->_container, $fullPathName, $content );
						$result = array( 'folder' => array( array( 'name' => $folderNameHeader, 'path' => $this->_container . '/' . $fullPathName ) ) );
					}
					elseif ( !empty( $fileUrl ) )
					{
						// upload a file from a url, could be expandable zip
						$tmpName = FileUtilities::importUrlFileToTemp( $fileUrl );
						$result = $this->_handleFile(
							$this->_folderPath,
							'',
							$tmpName,
							'',
							$extract,
							$clean,
							$checkExist
						);
					}
					elseif ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
					{
						// older html multi-part/form-data post, single or multiple files
						$files = FileUtilities::rearrangePostedFiles( $_FILES['files'] );
						$result = $this->_handleFolderContentFromFiles( $files, $extract, $clean, $checkExist );
					}
					else
					{
						// possibly xml or json post either of files or folders to create, copy or move
						$data = RestData::getPostDataAsArray();
						if ( empty( $data ) )
						{
							// create folder from resource path
							$this->createFolder( $this->_container, $this->_folderPath );
							$result = array( 'folder' => array( array( 'path' => $this->_container . '/' . $this->_folderPath ) ) );
						}
						else
						{
							$result = $this->_handleFolderContentFromData( $data, $extract, $clean, $checkExist );
						}
					}
				}
				else
				{
					// create the file
					// possible file handling parameters
					$extract = FilterInput::request( 'extract', false, FILTER_VALIDATE_BOOLEAN );
					$clean = FilterInput::request( 'clean', false, FILTER_VALIDATE_BOOLEAN );
					$checkExist = FilterInput::request( 'check_exist', false, FILTER_VALIDATE_BOOLEAN );
					$name = basename( $this->_filePath );
					$path = dirname( $this->_filePath );
					$files = Option::get( $_FILES, 'files' );
					if ( empty( $files ) )
					{
						$contentType = Option::get( $_SERVER, 'CONTENT_TYPE', '' );
						// direct load from posted data as content
						// or possibly xml or json post of file properties create, copy or move
						$content = RestData::getPostData();
						$result = $this->_handleFileContent(
							$path,
							$name,
							$content,
							$contentType,
							$extract,
							$clean,
							$checkExist
						);
					}
					else
					{
						// older html multipart/form-data post, should be single file
						$files = FileUtilities::rearrangePostedFiles( $_FILES['files'] );
						if ( 1 < count( $files ) )
						{
							throw new \Exception( "Multiple files uploaded to a single REST resource '$name'." );
						}
						$file = Option::get( $files, 0 );
						if ( empty( $file ) )
						{
							throw new \Exception( "No file uploaded to REST resource '$name'." );
						}
						$error = $file['error'];
						if ( UPLOAD_ERR_OK == $error )
						{
							$tmpName = $file["tmp_name"];
							$contentType = $file['type'];
							$result = $this->_handleFile(
								$path,
								$name,
								$tmpName,
								$contentType,
								$extract,
								$clean,
								$checkExist
							);
						}
						else
						{
							throw new \Exception( "Failed to upload file $name.\n$error" );
						}
					}
				}
				break;
			case self::Patch:
			case self::Merge:
				static::checkPermission( 'update', $this->_container );
				if ( empty( $this->_container ) )
				{
					// nothing?
					$result = array();
				}
				else if ( empty( $this->_folderPath ) )
				{
					// update container properties
					$content = RestData::getPostDataAsArray();
					$this->updateContainerProperties( $this->_container, $content );
					$result = array( 'container' => array( 'name' => $this->_container ) );
				}
				else if ( empty( $this->_filePath ) )
				{
					// update folder properties
					$content = RestData::getPostDataAsArray();
					$this->updateFolderProperties( $this->_container, $this->_folderPath, $content );
					$result = array(
						'folder' => array(
							'name' => basename( $this->_folderPath ),
							'path' => $this->_container . '/' . $this->_folderPath
						)
					);
				}
				else
				{
					// update file properties?
					$content = RestData::getPostDataAsArray();
					$this->updateFileProperties( $this->_container, $this->_filePath, $content );
					$result = array(
						'file' => array(
							'name' => basename( $this->_filePath ),
							'path' => $this->_container . '/' . $this->_filePath
						)
					);
				}
				break;
			case self::Delete:
				static::checkPermission( 'delete', $this->_container );
				$force = FilterInput::request( 'force', false, FILTER_VALIDATE_BOOLEAN );
				$content = RestData::getPostDataAsArray();
				if ( empty( $this->_container ) )
				{
					$containers = Option::get( $content, 'container' );
					if ( empty( $containers ) )
					{
						$containers = Option::getDeep( $content, 'containers', 'container' );
					}
					if ( !empty( $containers ) )
					{
						// delete multiple containers
						$result = $this->deleteContainers( $containers, $force );
						$result = array( 'container' => $result );
					}
					else
					{
						$_name = Option::get( $content, 'name', trim( Option::get( $content, 'path' ), '/' ) );
						if ( empty( $_name ) )
						{
							throw new BadRequestException( 'No name found for container in delete request.' );
						}
						$this->deleteContainer( $_name, $force );
						$result = array( 'name' => $_name, 'path' => $_name );
					}
				}
				else if ( empty( $this->_folderPath ) )
				{
					// delete whole container
					// or just folders and files from the container
					if ( empty( $content ) )
					{
						$this->deleteContainer( $this->_container, $force );
						$result = array( 'name' => $this->_container );
					}
					else
					{
						$result = $this->_deleteFolderContent( $content, '', $force );
					}
				}
				else if ( empty( $this->_filePath ) )
				{
					// delete directory of files and the directory itself
					// multi-file or folder delete via post data
					if ( empty( $content ) )
					{
						$this->deleteFolder( $this->_container, $this->_folderPath, $force );
						$result = array( 'folder' => array( array( 'path' => $this->_container . '/' . $this->_folderPath ) ) );
					}
					else
					{
						$result = $this->_deleteFolderContent( $content, $this->_folderPath, $force );
					}
				}
				else
				{
					// delete file from permanent storage
					$this->deleteFile( $this->_container, $this->_filePath );
					$result = array( 'file' => array( array( 'path' => $this->_container . '/' . $this->_filePath ) ) );
				}
				break;
			default:
				return false;
		}

		return $result;
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
	 * @throws \Exception
	 * @return array
	 */
	protected function _handleFile( $dest_path, $dest_name, $source_file, $contentType = '',
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
			$zip = new \ZipArchive();
			if ( true === $zip->open( $source_file ) )
			{
				return $this->extractZipFile( $this->_container, $dest_path, $zip, $clean );
			}
			else
			{
				throw new \Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$name = ( empty( $dest_name ) ? basename( $source_file ) : $dest_name );
			$fullPathName = FileUtilities::fixFolderPath( $dest_path ) . $name;
			$this->moveFile( $this->_container, $fullPathName, $source_file, $check_exist );

			return array( 'file' => array( array( 'name' => $name, 'path' => $this->_container . '/' . $fullPathName ) ) );
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
	 * @throws \Exception
	 * @return array
	 */
	protected function _handleFileContent( $dest_path, $dest_name, $content, $contentType = '',
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
			$zip = new \ZipArchive();
			if ( true === $zip->open( $tmpName ) )
			{
				return $this->extractZipFile( $this->_container, $dest_path, $zip, $clean );
			}
			else
			{
				throw new \Exception( 'Error opening temporary zip file.' );
			}
		}
		else
		{
			$fullPathName = FileUtilities::fixFolderPath( $dest_path ) . $dest_name;
			$this->writeFile( $this->_container, $fullPathName, $content, false, $check_exist );

			return array( 'file' => array( array( 'name' => $dest_name, 'path' => $this->_container . '/' . $fullPathName ) ) );
		}
	}

	/**
	 * @param      $files
	 * @param bool $extract
	 * @param bool $clean
	 * @param bool $checkExist
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function _handleFolderContentFromFiles( $files, $extract = false, $clean = false, $checkExist = false )
	{
		$out = array();
		$err = array();
		foreach ( $files as $key => $file )
		{
			$name = $file['name'];
			$error = $file['error'];
			if ( $error == UPLOAD_ERR_OK )
			{
				$tmpName = $file['tmp_name'];
				$contentType = $file['type'];
				$tmp = $this->_handleFile(
					$this->_folderPath,
					$name,
					$tmpName,
					$contentType,
					$extract,
					$clean,
					$checkExist
				);
				$out[$key] = ( isset( $tmp['file'] ) ? $tmp['file'] : array() );
			}
			else
			{
				$err[] = $name;
			}
		}
		if ( !empty( $err ) )
		{
			$msg = 'Failed to upload the following files to folder ' . $this->_folderPath . ': ' . implode( ', ', $err );
			throw new \Exception( $msg );
		}

		return array( 'file' => $out );
	}

	protected function _handleFolderContentFromData( $data, $extract = false, $clean = false, $checkExist = false )
	{
		$out = array( 'folder' => array(), 'file' => array() );
		$folders = Option::get( $data, 'folder' );
		if ( empty( $folders ) )
		{
			$folders = Option::getDeep( $data, 'folders', 'folder' );
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
				$srcPath = Option::get( $folder, 'source_path' );
				if ( !empty( $srcPath ) )
				{
					$srcContainer = Option::get( $folder, 'source_container', $this->_container );
					// copy or move
					if ( empty( $name ) )
					{
						$name = FileUtilities::getNameFromPath( $srcPath );
					}
					$fullPathName = $this->_folderPath . $name . '/';
					$out['folder'][$key] = array( 'name' => $name, 'path' => $this->_container . '/' . $fullPathName );
					try
					{
						$this->copyFolder( $this->_container, $fullPathName, $srcContainer, $srcPath, true );
						$deleteSource = DataFormat::boolval( Option::get( $folder, 'delete_source', false ) );
						if ( $deleteSource )
						{
							$this->deleteFolder( $this->_container, $srcPath, true );
						}
					}
					catch ( \Exception $ex )
					{
						$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
					}
				}
				else
				{
					$fullPathName = $this->_folderPath . $name;
					$content = Option::get( $folder, 'content', '' );
					$isBase64 = DataFormat::boolval( Option::get( $folder, 'is_base64', false ) );
					if ( $isBase64 )
					{
						$content = base64_decode( $content );
					}
					$out['folder'][$key] = array( 'name' => $name, 'path' => $this->_container . '/' . $fullPathName );
					try
					{
						$this->createFolder( $this->_container, $fullPathName, true, $content );
					}
					catch ( \Exception $ex )
					{
						$out['folder'][$key]['error'] = array( 'message' => $ex->getMessage() );
					}
				}
			}
		}
		$files = Option::get( $data, 'file' );
		if ( empty( $files ) )
		{
			$files = Option::getDeep( $data, 'files', 'file' );
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
				$srcPath = Option::get( $file, 'source_path' );
				if ( !empty( $srcPath ) )
				{
					// copy or move
					$srcContainer = Option::get( $file, 'source_container', $this->_container );
					if ( empty( $name ) )
					{
						$name = FileUtilities::getNameFromPath( $srcPath );
					}
					$fullPathName = $this->_folderPath . $name;
					$out['file'][$key] = array( 'name' => $name, 'path' => $this->_container . '/' . $fullPathName );
					try
					{
						$this->copyFile( $this->_container, $fullPathName, $srcContainer, $srcPath, true );
						$deleteSource = DataFormat::boolval( Option::get( $file, 'delete_source', false ) );
						if ( $deleteSource )
						{
							$this->deleteFile( $this->_container, $srcPath );
						}
					}
					catch ( \Exception $ex )
					{
						$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
					}
				}
				elseif ( isset( $file['content'] ) )
				{
					$fullPathName = $this->_folderPath . $name;
					$out['file'][$key] = array( 'name' => $name, 'path' => $this->_container . '/' . $fullPathName );
					$content = Option::get( $file, 'content', '' );
					$isBase64 = DataFormat::boolval( Option::get( $file, 'is_base64', false ) );
					if ( $isBase64 )
					{
						$content = base64_decode( $content );
					}
					try
					{
						$this->writeFile( $this->_container, $fullPathName, $content );
					}
					catch ( \Exception $ex )
					{
						$out['file'][$key]['error'] = array( 'message' => $ex->getMessage() );
					}
				}
			}
		}

		return $out;
	}

	/**
	 * @param array  $data    Array of sub-folder and file paths that are relative to the root folder
	 * @param string $root    root folder from which to delete
	 * @param  bool  $force
	 *
	 * @return array
	 */
	protected function _deleteFolderContent( $data, $root = '', $force = false )
	{
		$out = array( 'folder' => array(), 'file' => array() );
		$folders = Option::get( $data, 'folder' );
		if ( empty( $folders ) )
		{
			$folders = Option::getDeep( $data, 'folders', 'folder' );
		}
		if ( !empty( $folders ) )
		{
			if ( !isset( $folders[0] ) )
			{
				// single folder, make into array
				$folders = array( $folders );
			}
			$out['folder'] = $this->deleteFolders( $this->_container, $folders, $root, $force );
		}
		$files = Option::get( $data, 'file' );
		if ( empty( $files ) )
		{
			$files = Option::getDeep( $data, 'files', 'file' );
		}
		if ( !empty( $files ) )
		{
			if ( !isset( $files[0] ) )
			{
				// single file, make into array
				$files = array( $files );
			}
			$out['files'] = $this->deleteFiles( $this->_container, $files, $root );
		}

		return $out;
	}

	/**
	 * @param        $container
	 * @param array  $folders Array of folder paths that are relative to the root directory
	 * @param string $root    directory from which to delete
	 * @param  bool  $force
	 *
	 * @return array
	 */
	abstract public function deleteFolders( $container, $folders, $root = '', $force = false );
}
