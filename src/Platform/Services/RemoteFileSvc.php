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

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;
use Platform\Interfaces\BlobServiceLike;
use Platform\Utility\FileUtilities;
use Platform\Utility\RestRequest;

/**
 * RemoteFileSvc.php
 * File storage service giving REST access to remote file storage.
 */
abstract class RemoteFileSvc extends BaseFileSvc implements BlobServiceLike
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $storageContainer;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function _handleResource()
	{
		// restrictions on default application storage
		if ( 0 == strcasecmp( 'app', $this->getApiName() ) )
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
		}

		return parent::_handleResource();
	}

	/**
	 * Create a new RemoteFileSvc
	 *
	 * @param  array $config
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		// Validate blob setup
		$_storeName = Option::get( $config, 'storage_name' );
		if ( empty( $_storeName ) )
		{
			if ( 0 == strcasecmp( 'app', Option::get( $config, 'api_name' ) ) )
			{
				$_storeName = 'applications';
			}
			else
			{
				throw new \InvalidArgumentException( 'Blob container name can not be empty.' );
			}
		}
		$this->storageContainer = $_storeName;
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		unset( $this->storageContainer );
	}

	/**
	 * Creates the container for this file management if it does not already exist
	 *
	 * @throws \Exception
	 */
	public function checkContainerForWrite()
	{
		try
		{
			if ( !$this->containerExists( $this->storageContainer ) )
			{
				$this->createContainer( $this->storageContainer );
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $path
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function folderExists( $path )
	{
		$path = FileUtilities::fixFolderPath( $path );
		try
		{
			if ( $this->containerExists( $this->storageContainer ) )
			{
				if ( $this->blobExists( $this->storageContainer, $path ) )
				{
					return true;
				}
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}

		return false;
	}

	/**
	 * @param string $path
	 * @param  bool  $include_files
	 * @param  bool  $include_folders
	 * @param  bool  $full_tree
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return array
	 */
	public function getFolderContent( $path, $include_files = true, $include_folders = true, $full_tree = false )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$delimiter = ( $full_tree ) ? '' : '/';
		try
		{
			$files = array();
			$folders = array();
			if ( $this->containerExists( $this->storageContainer ) )
			{
				if ( !empty( $path ) )
				{
					if ( !$this->blobExists( $this->storageContainer, $path ) )
					{
						throw new NotFoundException( "Folder '$path' does not exist in storage." );
					}
				}
				$results = $this->listBlobs( $this->storageContainer, $path, $delimiter );
				foreach ( $results as $blob )
				{
					$fullPathName = $blob['name'];
					$shortName = substr_replace( $fullPathName, '', 0, strlen( $path ) );
					if ( '/' == substr( $fullPathName, strlen( $fullPathName ) - 1 ) )
					{
						// folders
						if ( $include_folders )
						{
							$shortName = FileUtilities::getNameFromPath( $shortName );
							$folders[] = array(
								'name'         => $shortName,
								'path'         => $fullPathName,
								'lastModified' => isset( $blob['lastModified'] ) ? $blob['lastModified'] : null
							);
						}
					}
					else
					{
						// files
						if ( $include_files )
						{
							$blob['path'] = $fullPathName;
							$blob['name'] = $shortName;
							$files[] = $blob;
						}
					}
				}
			}

			return array( "folder" => $folders, "file" => $files );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return array
	 */
	public function getFolderProperties( $path )
	{
		$path = FileUtilities::fixFolderPath( $path );
		try
		{
			if ( !$this->blobExists( $this->storageContainer, $path ) )
			{
				throw new NotFoundException( "Folder '$path' does not exist in storage." );
			}
			$folder = $this->getBlobData( $this->storageContainer, $path );
			$properties = json_decode( $folder, true ); // array of properties
			return array( 'folder' => array( array( 'path' => $path, 'properties' => $properties ) ) );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 * @param bool   $is_public
	 * @param array  $properties
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function createFolder( $path, $is_public = true, $properties = array(), $check_exist = true )
	{
		if ( empty( $path ) )
		{
			throw new BadRequestException( "Invalid empty path." );
		}
		$parent = FileUtilities::getParentFolder( $path );
		$path = FileUtilities::fixFolderPath( $path );

		// does this folder already exist?
		if ( $this->folderExists( $path ) )
		{
			if ( $check_exist )
			{
				throw new BadRequestException( "Folder '$path' already exists." );
			}

			return;
		}
		// does this folder's parent exist?
		if ( !empty( $parent ) && ( !$this->folderExists( $parent ) ) )
		{
			if ( $check_exist )
			{
				throw new NotFoundException( "Folder '$parent' does not exist." );
			}
			$this->createFolder( $parent, $is_public, $properties, false );
		}

		try
		{
			// create the folder
			$this->checkContainerForWrite(); // need to be able to write to storage
			$properties = ( empty( $properties ) ) ? '' : json_encode( $properties );
			$this->putBlobData( $this->storageContainer, $path, $properties );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function copyFolder( $dest_path, $src_path, $check_exist = false )
	{
		// does this file already exist?
		if ( !$this->folderExists( $src_path ) )
		{
			throw new NotFoundException( "Folder '$src_path' does not exist." );
		}
		if ( $this->folderExists( $dest_path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new BadRequestException( "Folder '$dest_path' already exists." );
			}
		}
		// does this file's parent folder exist?
		$parent = FileUtilities::getParentFolder( $dest_path );
		if ( !empty( $parent ) && ( !$this->folderExists( $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}
		try
		{
			// create the folder
			$this->checkContainerForWrite(); // need to be able to write to storage
			$this->copyBlob( $this->storageContainer, $dest_path, $this->storageContainer, $src_path );
			// now copy content of folder...
			$blobs = $this->listBlobs( $this->storageContainer, $src_path );
			if ( !empty( $blobs ) )
			{
				foreach ( $blobs as $blob )
				{
					$srcName = $blob['name'];
					if ( ( 0 !== strcasecmp( $src_path, $srcName ) ) )
					{
						// not self properties blob
						$name = FileUtilities::getNameFromPath( $srcName );
						$fullPathName = $dest_path . $name;
						$this->copyBlob( $this->storageContainer, $fullPathName, $this->storageContainer, $srcName );
					}
				}
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return void
	 */
	public function updateFolderProperties( $path, $properties = array() )
	{
		$path = FileUtilities::fixFolderPath( $path );
		// does this folder exist?
		if ( !$this->folderExists( $path ) )
		{
			throw new NotFoundException( "Folder '$path' does not exist." );
		}
		try
		{
			// update the file that holds folder properties
			$properties = json_encode( $properties );
			$this->putBlobData( $this->storageContainer, $path, $properties );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 * @param bool   $force If true, delete folder content as well,
	 *                      otherwise return error when content present.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function deleteFolder( $path, $force = false )
	{
		try
		{
			$this->checkContainerForWrite(); // need to be able to write to storage
			error_log( $path );
			$blobs = $this->listBlobs( $this->storageContainer, $path );
			if ( !empty( $blobs ) )
			{
				if ( ( 1 === count( $blobs ) ) && ( 0 === strcasecmp( $path, $blobs[0]['name'] ) ) )
				{
					// only self properties blob
				}
				else
				{
					if ( !$force )
					{
						throw new \Exception( "Folder '$path' contains other files or folders." );
					}
					foreach ( $blobs as $blob )
					{
						$this->deleteBlob( $this->storageContainer, $blob['name'] );
					}
				}
			}
			$this->deleteBlob( $this->storageContainer, $path );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param array  $folders
	 * @param string $root
	 * @param bool   $force If true, delete folder content as well,
	 *                      otherwise return error when content present.
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function deleteFolders( $folders, $root = '', $force = false )
	{
		$root = FileUtilities::fixFolderPath( $root );
		foreach ( $folders as $key => $folder )
		{
			try
			{
				// path is full path, name is relative to root, take either
				if ( isset( $folder['path'] ) )
				{
					$path = $folder['path'];
				}
				elseif ( isset( $folder['name'] ) )
				{
					$path = $root . $folder['name'];
				}
				else
				{
					throw new BadRequestException( 'No path or name found for folder in delete request.' );
				}
				if ( !empty( $path ) )
				{
					$this->deleteFolder( $path, $force );
				}
				else
				{
					throw new BadRequestException( 'No path or name found for folder in delete request.' );
				}
			}
			catch ( \Exception $ex )
			{
				// error whole batch here?
				$folders[$key]['error'] = array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() );
			}
		}

		return $folders;
	}

	/**
	 * @param $path
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function fileExists( $path )
	{
		try
		{
			if ( $this->containerExists( $this->storageContainer ) )
			{
				if ( $this->blobExists( $this->storageContainer, $path ) )
				{
					return true;
				}
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}

		return false;
	}

	/**
	 * @param string $path
	 * @param string $local_file
	 * @param bool   $content_as_base
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return string
	 */
	public function getFileContent( $path, $local_file = '', $content_as_base = true )
	{
		try
		{
			if ( !$this->blobExists( $this->storageContainer, $path ) )
			{
				throw new NotFoundException( "File '$path' does not exist in storage." );
			}
			if ( !empty( $local_file ) )
			{
				// write to local or temp file
				$this->getBlobAsFile( $this->storageContainer, $path, $local_file );

				return '';
			}
			else
			{
				// get content as raw or encoded as base64 for transport
				$data = $this->getBlobData( $this->storageContainer, $path );
				if ( $content_as_base )
				{
					$data = base64_encode( $data );
				}

				return $data;
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 * @param bool   $include_content
	 * @param bool   $content_as_base
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return array
	 */
	public function getFileProperties( $path, $include_content = false, $content_as_base = true )
	{
		try
		{
			if ( !$this->blobExists( $this->storageContainer, $path ) )
			{
				throw new NotFoundException( "File '$path' does not exist in storage." );
			}
			$blob = $this->listBlob( $this->storageContainer, $path );
			$shortName = FileUtilities::getNameFromPath( $path );
			$blob['path'] = $path;
			$blob['name'] = $shortName;
			if ( $include_content )
			{
				$data = $this->getBlobData( $this->storageContainer, $path );
				if ( $content_as_base )
				{
					$data = base64_encode( $data );
				}
				$blob['content'] = $data;
			}

			return $blob;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function streamFile( $path )
	{
		try
		{
			$this->streamBlob( $this->storageContainer, $path, array() );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function downloadFile( $path )
	{
		try
		{
			$params = array( 'disposition' => 'attachment' );
			$this->streamBlob( $this->storageContainer, $path, $params );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string  $path
	 * @param string  $content
	 * @param boolean $content_is_base
	 * @param bool    $check_exist
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return void
	 */
	public function writeFile( $path, $content, $content_is_base = false, $check_exist = false )
	{
		// does this file already exist?
		if ( $this->fileExists( $path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new \Exception( "File '$path' already exists." );
			}
		}
		// does this folder's parent exist?
		$parent = FileUtilities::getParentFolder( $path );
		if ( !empty( $parent ) && ( !$this->folderExists( $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}
		try
		{
			// create the file
			$this->checkContainerForWrite(); // need to be able to write to storage
			if ( $content_is_base )
			{
				$content = base64_decode( $content );
			}
			$ext = FileUtilities::getFileExtension( $path );
			$mime = FileUtilities::determineContentType( $ext, $content );
			$this->putBlobData( $this->storageContainer, $path, $content, $mime );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 * @param string $local_path
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function moveFile( $path, $local_path, $check_exist = true )
	{
		// does local file exist?
		if ( !file_exists( $local_path ) )
		{
			throw new NotFoundException( "File '$local_path' does not exist." );
		}
		// does this file already exist?
		if ( $this->fileExists( $path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new BadRequestException( "File '$path' already exists." );
			}
		}
		// does this file's parent folder exist?
		$parent = FileUtilities::getParentFolder( $path );
		if ( !empty( $parent ) && ( !$this->folderExists( $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}
		try
		{
			// create the file
			$this->checkContainerForWrite(); // need to be able to write to storage
			$ext = FileUtilities::getFileExtension( $path );
			$mime = FileUtilities::determineContentType( $ext, '', $local_path );
			$this->putBlobFromFile( $this->storageContainer, $path, $local_path, $mime );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function copyFile( $dest_path, $src_path, $check_exist = false )
	{
		// does this file already exist?
		if ( !$this->fileExists( $src_path ) )
		{
			throw new NotFoundException( "File '$src_path' does not exist." );
		}
		if ( $this->fileExists( $dest_path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new BadRequestException( "File '$dest_path' already exists." );
			}
		}
		// does this file's parent folder exist?
		$parent = FileUtilities::getParentFolder( $dest_path );
		if ( !empty( $parent ) && ( !$this->folderExists( $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}
		try
		{
			// create the file
			$this->checkContainerForWrite(); // need to be able to write to storage
			$this->copyBlob( $this->storageContainer, $dest_path, $this->storageContainer, $src_path );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $path
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function deleteFile( $path )
	{
		try
		{
			$this->deleteBlob( $this->storageContainer, $path );
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param array  $files
	 * @param string $root
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function deleteFiles( $files, $root = '' )
	{
		$root = FileUtilities::fixFolderPath( $root );
		foreach ( $files as $key => $file )
		{
			try
			{
				// path is full path, name is relative to root, take either
				if ( isset( $file['path'] ) )
				{
					$path = $file['path'];
				}
				elseif ( isset( $file['name'] ) )
				{
					$path = $root . $file['name'];
				}
				else
				{
					throw new BadRequestException( 'No path or name found for file in delete request.' );
				}
				if ( !empty( $path ) )
				{
					$this->deleteFile( $path );
				}
				else
				{
					throw new BadRequestException( 'No path or name found for file in delete request.' );
				}
			}
			catch ( \Exception $ex )
			{
				// error whole batch here?
				$files[$key]['error'] = array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() );
			}
		}

		return $files;
	}

	/**
	 * @param string           $path
	 * @param null|\ZipArchive $zip
	 * @param string           $zipFileName
	 * @param bool             $overwrite
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return string Zip File Name created/updated
	 */
	public function getFolderAsZip( $path, $zip = null, $zipFileName = '', $overwrite = false )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$delimiter = '';
		try
		{
			if ( $this->containerExists( $this->storageContainer ) )
			{
				throw new BadRequestException( "Can not find directory '$this->storageContainer'." );
			}
			$needClose = false;
			if ( !isset( $zip ) )
			{
				$needClose = true;
				$zip = new \ZipArchive();
				if ( empty( $zipFileName ) )
				{
					$temp = FileUtilities::getNameFromPath( $path );
					if ( empty( $temp ) )
					{
						$temp = $this->storageContainer;
					}
					$tempDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
					$zipFileName = $tempDir . $temp . '.zip';
				}
				if ( true !== $zip->open( $zipFileName, ( $overwrite ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE ) ) )
				{
					throw new \Exception( "Can not create zip file for directory '$path'." );
				}
			}
			$results = $this->listBlobs( $this->storageContainer, $path, $delimiter );
			foreach ( $results as $blob )
			{
				$fullPathName = $blob['name'];
				$shortName = substr_replace( $fullPathName, '', 0, strlen( $path ) );
				if ( empty( $shortName ) )
				{
					continue;
				}
				error_log( $shortName );
				if ( '/' == substr( $fullPathName, strlen( $fullPathName ) - 1 ) )
				{
					// folders
					if ( !$zip->addEmptyDir( $shortName ) )
					{
						throw new \Exception( "Can not include folder '$shortName' in zip file." );
					}
				}
				else
				{
					// files
					$content = $this->getBlobData( $this->storageContainer, $fullPathName );
					if ( !$zip->addFromString( $shortName, $content ) )
					{
						throw new \Exception( "Can not include file '$shortName' in zip file." );
					}
				}
			}
			if ( $needClose )
			{
				$zip->close();
			}

			return $zipFileName;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param            $path
	 * @param \ZipArchive $zip
	 * @param bool       $clean
	 * @param string     $drop_path
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function extractZipFile( $path, $zip, $clean = false, $drop_path = '' )
	{
		if ( ( $clean ) )
		{
			try
			{
				// clear out anything in this directory
				$blobs = $this->listBlobs( $this->storageContainer, $path );
				if ( !empty( $blobs ) )
				{
					foreach ( $blobs as $blob )
					{
						if ( ( 0 !== strcasecmp( $path, $blob['name'] ) ) )
						{ // not folder itself
							$this->deleteBlob( $this->storageContainer, $blob['name'] );
						}
					}
				}
			}
			catch ( \Exception $ex )
			{
				throw new \Exception( "Could not clean out existing directory $path.\n{$ex->getMessage()}" );
			}
		}
		for ( $i = 0; $i < $zip->numFiles; $i++ )
		{
			try
			{
				$name = $zip->getNameIndex( $i );
				if ( empty( $name ) )
				{
					continue;
				}
				if ( !empty( $drop_path ) )
				{
					$name = str_ireplace( $drop_path, '', $name );
				}
				$fullPathName = $path . $name;
				$parent = FileUtilities::getParentFolder( $fullPathName );
				if ( !empty( $parent ) )
				{
					$this->createFolder( $parent, true, array(), false );
				}
				if ( '/' === substr( $fullPathName, -1 ) )
				{
					$this->createFolder( $fullPathName, true, array(), false );
				}
				else
				{
					$content = $zip->getFromIndex( $i );
					$this->writeFile( $fullPathName, $content );
				}
			}
			catch ( \Exception $ex )
			{
				throw $ex;
			}
		}

		return array( 'folder' => array( 'name' => rtrim( $path, DIRECTORY_SEPARATOR ), 'path' => $path ) );
	}

	// implement Blob Service
	/**
	 * @return array
	 * @throws \Exception
	 */
	abstract public function listContainers();

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	abstract public function containerExists( $container = '' );

	/**
	 * @param string $container
	 * @param array  $metadata
	 *
	 * @throws \Exception
	 */
	abstract public function createContainer( $container = '', $metadata = array() );

	/**
	 * @param string $container
	 *
	 * @throws \Exception
	 */
	abstract public function deleteContainer( $container = '' );

	/**
	 * Check if a blob exists
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	abstract public function blobExists( $container = '', $name = '' );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $blob
	 * @param string $type
	 *
	 * @throws \Exception
	 */
	abstract public function putBlobData( $container = '', $name = '', $blob = '', $type = '' );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $localFileName
	 * @param string $type
	 *
	 * @throws \Exception
	 */
	abstract public function putBlobFromFile( $container = '', $name = '', $localFileName = '', $type = '' );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 *
	 * @throws \Exception
	 */
	abstract public function copyBlob( $container = '', $name = '', $src_container = '', $src_name = '' );

	/**
	 * Get blob
	 *
	 * @param  string $container     Container name
	 * @param  string $name          Blob name
	 * @param  string $localFileName Local file name to store downloaded blob
	 *
	 * @throws \Exception
	 */
	abstract public function getBlobAsFile( $container = '', $name = '', $localFileName = '' );

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	abstract public function getBlobData( $container = '', $name = '' );

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws \Exception
	 */
	abstract public function deleteBlob( $container = '', $name = '' );

	/**
	 * List blobs
	 *
	 * @param  string $container Container name
	 * @param  string $prefix    Optional. Filters the results to return only blobs whose name begins with the specified prefix.
	 * @param  string $delimiter Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function listBlobs( $container = '', $prefix = '', $delimiter = '' );

	/**
	 * List blob
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return array instance
	 * @throws \Exception
	 */
	abstract public function listBlob( $container, $name );

	/**
	 * @param       $container
	 * @param       $blobName
	 * @param array $params
	 *
	 * @throws \Exception
	 */
	abstract public function streamBlob( $container, $blobName, $params = array() );
}
