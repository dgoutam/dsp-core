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
use Platform\Utility\FileUtilities;
use Platform\Utility\RestRequest;

/**
 * RemoteFileSvc.php
 * File storage service giving REST access to remote file storage.
 */
abstract class RemoteFileSvc extends BaseFileSvc
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates the container for this file management if it does not already exist
	 *
	 * @param string $container
	 *
	 * @throws \Exception
	 */
	public function checkContainerForWrite( $container )
	{
		if ( !$this->containerExists( $container ) )
		{
			$this->createContainer( $container );
		}
	}

	public function createContainers( $containers = array(), $check_exist = false )
	{
		$result = array();
		foreach ( $containers as $key => $folder )
		{
			try
			{
				// path is full path, name is relative to root, take either
				$_name = $folder['name'];
				if ( !empty( $_name ) )
				{
					$this->createContainer( $_name, $check_exist );
					$result[$key]['name'] = $_name;
				}
				else
				{
					throw new BadRequestException( 'No name found for container in create request.' );
				}
			}
			catch ( \Exception $ex )
			{
				// error whole batch here?
				$result[$key]['error'] = array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() );
			}
		}

		return array( 'containers' => $result );
	}

	/**
	 * Delete multiple containers and all of their content
	 *
	 * @param array $containers
	 * @param bool  $force Force a delete if it is not empty
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function deleteContainers( $containers, $force = false )
	{
		foreach ( $containers as $key => $folder )
		{
			try
			{
				// path is full path, name is relative to root, take either
				$path = $folder['name'];
				if ( !empty( $path ) )
				{
					$this->deleteContainer( $path, $force );
				}
				else
				{
					throw new BadRequestException( 'No name found for container in delete request.' );
				}
			}
			catch ( \Exception $ex )
			{
				// error whole batch here?
				$folders[$key]['error'] = array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() );
			}
		}
	}

	/**
	 * @param string $container
	 * @param $path
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function folderExists( $container, $path )
	{
		$path = FileUtilities::fixFolderPath( $path );
		if ( $this->containerExists( $container ) )
		{
			if ( $this->blobExists( $container, $path ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $include_files
	 * @param bool   $include_folders
	 * @param bool   $full_tree
	 * @param bool   $include_properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @return array
	 */
	public function getFolder( $container, $path, $include_files = true, $include_folders = true, $full_tree = false, $include_properties = false )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$shortName = FileUtilities::getNameFromPath( $path );
		$out = array( 'container' => $container, 'name' => $shortName, 'path' => $path );
		if ( $include_properties )
		{
			// properties
			if ( $this->blobExists( $container, $path ) )
			{
				$properties = $this->getBlobProperties( $container, $path );

				$out = array_merge( $properties, $out );
			}
		}
		$delimiter = ( $full_tree ) ? '' : '/';
		$files = array();
		$folders = array();
		if ( $this->containerExists( $container ) )
		{
			if ( !empty( $path ) )
			{
				if ( !$this->blobExists( $container, $path ) )
				{
					throw new NotFoundException( "Folder '$path' does not exist in storage." );
				}
			}
			$results = $this->listBlobs( $container, $path, $delimiter );
			foreach ( $results as $blob )
			{
				$fullPathName = $blob['name'];
				if ( '/' == substr( $fullPathName, strlen( $fullPathName ) - 1 ) )
				{
					// folders
					if ( $include_folders )
					{
						$blob['name'] = substr( $fullPathName, strlen( $path ), -1 );
						$blob['path'] = $fullPathName;
						$folders[] = $blob;
					}
				}
				else
				{
					// files
					if ( $include_files )
					{
						$blob['name'] = substr( $fullPathName, strlen( $path ) );
						$blob['path'] = $fullPathName;
						$files[] = $blob;
					}
				}
			}
		}
		else
		{
			if ( !empty( $path ) )
			{
				throw new NotFoundException( "Folder '$path' does not exist in storage." );
			}
			// container root doesn't really exist until first write creates it
		}

		$out['folder'] = $folders;
		$out['file'] = $files;

		return $out;
	}

	/**
	 * @param string $container
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
	public function createFolder( $container, $path, $is_public = true, $properties = array(), $check_exist = true )
	{
		if ( empty( $path ) )
		{
			throw new BadRequestException( "Invalid empty path." );
		}
		$parent = FileUtilities::getParentFolder( $path );
		$path = FileUtilities::fixFolderPath( $path );

		// does this folder already exist?
		if ( $this->folderExists( $container, $path ) )
		{
			if ( $check_exist )
			{
				throw new BadRequestException( "Folder '$path' already exists." );
			}

			return;
		}
		// does this folder's parent exist?
		if ( !empty( $parent ) && ( !$this->folderExists( $container, $parent ) ) )
		{
			if ( $check_exist )
			{
				throw new NotFoundException( "Folder '$parent' does not exist." );
			}
			$this->createFolder( $parent, $is_public, $properties, false );
		}

		// create the folder
		$this->checkContainerForWrite( $container ); // need to be able to write to storage
		$properties = ( empty( $properties ) ) ? '' : json_encode( $properties );
		$this->putBlobData( $container, $path, $properties );
	}

	/**
	 * @param string $container
	 * @param string $dest_path
	 * @param string $src_container
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function copyFolder( $container, $dest_path, $src_container, $src_path, $check_exist = false )
	{
		// does this file already exist?
		if ( !$this->folderExists( $container, $src_path ) )
		{
			throw new NotFoundException( "Folder '$src_path' does not exist." );
		}
		if ( $this->folderExists( $container, $dest_path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new BadRequestException( "Folder '$dest_path' already exists." );
			}
		}
		// does this file's parent folder exist?
		$parent = FileUtilities::getParentFolder( $dest_path );
		if ( !empty( $parent ) && ( !$this->folderExists( $container, $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}
		// create the folder
		$this->checkContainerForWrite( $container ); // need to be able to write to storage
		$this->copyBlob( $container, $dest_path, $src_container, $src_path );
		// now copy content of folder...
		$blobs = $this->listBlobs( $src_container, $src_path );
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
					$this->copyBlob( $container, $fullPathName, $src_container, $srcName );
				}
			}
		}
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return void
	 */
	public function updateFolderProperties( $container, $path, $properties = array() )
	{
		$path = FileUtilities::fixFolderPath( $path );
		// does this folder exist?
		if ( !$this->folderExists( $container, $path ) )
		{
			throw new NotFoundException( "Folder '$path' does not exist." );
		}
		// update the file that holds folder properties
		$properties = json_encode( $properties );
		$this->putBlobData( $container, $path, $properties );
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $force If true, delete folder content as well,
	 *                      otherwise return error when content present.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function deleteFolder( $container, $path, $force = false )
	{
		$blobs = $this->listBlobs( $container, $path );
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
					$this->deleteBlob( $container, $blob['name'] );
				}
			}
		}
		$this->deleteBlob( $container, $path );
	}

	/**
	 * @param string $container
	 * @param array  $folders
	 * @param string $root
	 * @param bool   $force If true, delete folder content as well,
	 *                      otherwise return error when content present.
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function deleteFolders( $container, $folders, $root = '', $force = false )
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
	 * @param string $container
	 * @param $path
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function fileExists( $container, $path )
	{
		if ( $this->containerExists( $container ) )
		{
			if ( $this->blobExists( $container, $path ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param string $local_file
	 * @param bool   $content_as_base
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return string
	 */
	public function getFileContent( $container, $path, $local_file = '', $content_as_base = true )
	{
		if ( !$this->blobExists( $container, $path ) )
		{
			throw new NotFoundException( "File '$path' does not exist in storage." );
		}
		if ( !empty( $local_file ) )
		{
			// write to local or temp file
			$this->getBlobAsFile( $container, $path, $local_file );

			return '';
		}
		else
		{
			// get content as raw or encoded as base64 for transport
			$data = $this->getBlobData( $container, $path );
			if ( $content_as_base )
			{
				$data = base64_encode( $data );
			}

			return $data;
		}
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $include_content
	 * @param bool   $content_as_base
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return array
	 */
	public function getFileProperties( $container, $path, $include_content = false, $content_as_base = true )
	{
		if ( !$this->blobExists( $container, $path ) )
		{
			throw new NotFoundException( "File '$path' does not exist in storage." );
		}
		$blob = $this->getBlobProperties( $container, $path );
		$shortName = FileUtilities::getNameFromPath( $path );
		$blob['path'] = $path;
		$blob['name'] = $shortName;
		if ( $include_content )
		{
			$data = $this->getBlobData( $container, $path );
			if ( $content_as_base )
			{
				$data = base64_encode( $data );
			}
			$blob['content'] = $data;
		}

		return $blob;
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $download
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function streamFile( $container, $path, $download = false )
	{
		$params = ( $download ) ? array( 'disposition' => 'attachment' ) : array();
		$this->streamBlob( $container, $path, $params );
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return void
	 */
	public function updateFileProperties( $container, $path, $properties = array() )
	{
		$path = FileUtilities::fixFolderPath( $path );
		// does this file exist?
		if ( !$this->fileExists( $container, $path ) )
		{
			throw new NotFoundException( "Folder '$path' does not exist." );
		}
		// update the file properties
		$properties = json_encode( $properties );
		$this->putBlobData( $container, $path, $properties );
	}

	/**
	 * @param string $container
	 * @param string  $path
	 * @param string  $content
	 * @param boolean $content_is_base
	 * @param bool    $check_exist
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 * @return void
	 */
	public function writeFile( $container, $path, $content, $content_is_base = false, $check_exist = false )
	{
		// does this file already exist?
		if ( $this->fileExists( $container, $path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new \Exception( "File '$path' already exists." );
			}
		}
		// does this folder's parent exist?
		$parent = FileUtilities::getParentFolder( $path );
		if ( !empty( $parent ) && ( !$this->folderExists( $container, $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}

		// create the file
		$this->checkContainerForWrite( $container ); // need to be able to write to storage
		if ( $content_is_base )
		{
			$content = base64_decode( $content );
		}
		$ext = FileUtilities::getFileExtension( $path );
		$mime = FileUtilities::determineContentType( $ext, $content );
		$this->putBlobData( $container, $path, $content, $mime );
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param string $local_path
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function moveFile( $container, $path, $local_path, $check_exist = true )
	{
		// does local file exist?
		if ( !file_exists( $local_path ) )
		{
			throw new NotFoundException( "File '$local_path' does not exist." );
		}
		// does this file already exist?
		if ( $this->fileExists( $container, $path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new BadRequestException( "File '$path' already exists." );
			}
		}
		// does this file's parent folder exist?
		$parent = FileUtilities::getParentFolder( $path );
		if ( !empty( $parent ) && ( !$this->folderExists( $container, $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}

		// create the file
		$this->checkContainerForWrite( $container ); // need to be able to write to storage
		$ext = FileUtilities::getFileExtension( $path );
		$mime = FileUtilities::determineContentType( $ext, '', $local_path );
		$this->putBlobFromFile( $container, $path, $local_path, $mime );
	}

	/**
	 * @param string $container
	 * @param string $dest_path
	 * @param string $src_container
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function copyFile( $container, $dest_path, $src_container, $src_path, $check_exist = false )
	{
		// does this file already exist?
		if ( !$this->fileExists( $src_container, $src_path ) )
		{
			throw new NotFoundException( "File '$src_path' does not exist." );
		}
		if ( $this->fileExists( $container, $dest_path ) )
		{
			if ( ( $check_exist ) )
			{
				throw new BadRequestException( "File '$dest_path' already exists." );
			}
		}
		// does this file's parent folder exist?
		$parent = FileUtilities::getParentFolder( $dest_path );
		if ( !empty( $parent ) && ( !$this->folderExists( $container, $parent ) ) )
		{
			throw new NotFoundException( "Folder '$parent' does not exist." );
		}

		// create the file
		$this->checkContainerForWrite( $container ); // need to be able to write to storage
		$this->copyBlob( $container, $dest_path, $src_container, $src_path );
	}

	/**
	 * @param string $container
	 * @param $path
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function deleteFile( $container, $path )
	{
		$this->deleteBlob( $container, $path );
	}

	/**
	 * @param string $container
	 * @param array  $files
	 * @param string $root
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function deleteFiles( $container, $files, $root = '' )
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
					$this->deleteFile( $container, $path );
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
	 * @param string           $container
	 * @param string           $path
	 * @param null|\ZipArchive $zip
	 * @param string           $zipFileName
	 * @param bool             $overwrite
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return string Zip File Name created/updated
	 */
	public function getFolderAsZip( $container, $path, $zip = null, $zipFileName = '', $overwrite = false )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$delimiter = '';
		if ( $this->containerExists( $container ) )
		{
			throw new BadRequestException( "Can not find directory '$container'." );
		}
		$needClose = false;
		if ( !isset( $zip ) )
		{
			$needClose = true;
			$zip = new \ZipArchive();
			if ( empty( $zipFileName ) )
			{
				$temp = basename( $path );
				if ( empty( $temp ) )
				{
					$temp = $container;
				}
				$tempDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
				$zipFileName = $tempDir . $temp . '.zip';
			}
			if ( true !== $zip->open( $zipFileName, ( $overwrite ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE ) ) )
			{
				throw new \Exception( "Can not create zip file for directory '$path'." );
			}
		}
		$results = $this->listBlobs( $container, $path, $delimiter );
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
				$content = $this->getBlobData( $container, $fullPathName );
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

	/**
	 * @param string      $container
	 * @param string      $path
	 * @param \ZipArchive $zip
	 * @param bool        $clean
	 * @param string      $drop_path
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function extractZipFile( $container, $path, $zip, $clean = false, $drop_path = '' )
	{
		if ( ( $clean ) )
		{
			try
			{
				// clear out anything in this directory
				$blobs = $this->listBlobs( $container, $path );
				if ( !empty( $blobs ) )
				{
					foreach ( $blobs as $blob )
					{
						if ( ( 0 !== strcasecmp( $path, $blob['name'] ) ) )
						{ // not folder itself
							$this->deleteBlob( $container, $blob['name'] );
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
					$this->writeFile( $container, $fullPathName, $content );
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
	 * Check if a blob exists
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	abstract public function blobExists( $container, $name );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $data
	 * @param array  $properties
	 *
	 * @throws \Exception
	 */
	abstract public function putBlobData( $container, $name = '', $data = null, $properties = array() );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $localFileName
	 * @param array  $properties
	 *
	 * @throws \Exception
	 */
	abstract public function putBlobFromFile( $container, $name = '', $localFileName = null, $properties = array() );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 * @param array  $properties
	 *
	 * @throws \Exception
	 */
	abstract public function copyBlob( $container, $name = '', $src_container = '', $src_name = '', $properties = array() );

	/**
	 * List blobs, all or limited by prefix or delimiter
	 *
	 * @param  string $container Container name
	 * @param  string $prefix    Optional. Filters the results to return only blobs whose name begins with the specified prefix.
	 * @param  string $delimiter Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function listBlobs( $container, $prefix = '', $delimiter = '' );

	/**
	 * Get blob
	 *
	 * @param  string $container     Container name
	 * @param  string $name          Blob name
	 * @param  string $localFileName Local file name to store downloaded blob
	 *
	 * @throws \Exception
	 */
	abstract public function getBlobAsFile( $container, $name, $localFileName = null );

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	abstract public function getBlobData( $container, $name );

	/**
	 * List blob
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return array
	 * @throws \Exception
	 */
	abstract public function getBlobProperties( $container, $name );

	/**
	 * @param string $container
	 * @param string $name
	 * @param array  $params
	 *
	 * @throws \Exception
	 */
	abstract public function streamBlob( $container, $name, $params = array() );

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws \Exception
	 */
	abstract public function deleteBlob( $container, $name );
}
