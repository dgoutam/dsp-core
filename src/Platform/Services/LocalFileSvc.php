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
use Platform\Utility\Utilities;

/**
 * LocalFileSvc.php
 * File storage service giving REST access to local file storage.
 */
class LocalFileSvc extends BaseFileSvc
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates the container for this file management if it does not already exist
	 *
	 * @throws \Exception
	 */
	public function checkContainerForWrite()
	{
		$container = self::addContainerToName( $this->_container, '' );
		if ( !is_dir( $container ) )
		{
			if ( !mkdir( $container ) )
			{
				throw new \Exception( 'Failed to create container.' );
			}
		}
	}

	/**
	 * List all containers, just names if noted
	 *
	 * @param bool $include_properties If true, additional properties are retrieved
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function listContainers( $include_properties = false )
	{
		$result = array();
		$dir = FileUtilities::fixFolderPath( static::asFullPath( '' ) );
		$files = array_diff( scandir( $dir ), array( '.', '..', '.private' ) );
		foreach ( $files as $file )
		{
			$key = $dir . $file;
			// get file meta
			if ( is_dir( $key ) )
			{
				$out = array( 'name' => $file );
				if ( $include_properties )
				{
					$temp = stat( $key );
					$out['last_modified'] = gmdate( 'D, d M Y H:i:s \G\M\T', Option::get( $temp, 'mtime', 0 ) );
				}

				$result[] = $out;
			}
		}

		return $result;
	}

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 */
	public function containerExists( $container )
	{
		$key = self::addContainerToName( $container, '' );

		return is_dir( $key );
	}

	/**
	 * Gets all properties of a particular container, if options are false,
	 * otherwise include content from the container
	 *
	 * @param  string $container Container name
	 * @param  bool   $include_files
	 * @param  bool   $include_folders
	 * @param  bool   $full_tree
	 *
	 * @return array
	 */
	public function getContainer( $container, $include_files = false, $include_folders = false, $full_tree = false )
	{
		return $this->getFolder( $container, '', $include_files, $include_folders, $full_tree );
	}

	/**
	 * Create a container using properties, where at least name is required
	 *
	 * @param array $properties
	 * @param bool  $check_exist If true, throws error if the container already exists
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function createContainer( $properties = array(), $check_exist = false )
	{
		$container = Option::get( $properties, 'name' );
		// does this folder already exist?
		if ( $this->folderExists( $container, '' ) )
		{
			if ( $check_exist )
			{
				throw new BadRequestException( "Container '$container' already exists." );
			}

			return;
		}

		// create the container
		$key = self::addContainerToName( $container, '' );
		if ( !mkdir( $key ) )
		{
			throw new \Exception( 'Failed to create container.' );
		}
//            $properties = (empty($properties)) ? '' : json_encode($properties);
//            $result = file_put_contents($key, $properties);
//            if (false === $result) {
//                throw new \Exception('Failed to create container properties.');
//            }
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
	 * Update a container with some properties
	 *
	 * @param string $container
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @return void
	 */
	public function updateContainerProperties( $container, $properties = array() )
	{
		// does this folder exist?
		if ( !$this->folderExists( $container, '' ) )
		{
			throw new NotFoundException( "Container '$container' does not exist." );
		}
		// update the file that holds folder properties
//            $properties = json_encode($properties);
//            $key = self::addContainerToName($container, '');
//            $result = file_put_contents($key, $properties);
//            if (false === $result) {
//                throw new \Exception('Failed to create container properties.');
//            }
	}

	/**
	 * Delete a container and all of its content
	 *
	 * @param string $container
	 * @param bool   $force Force a delete if it is not empty
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function deleteContainer( $container, $force = false )
	{
		$dir = static::addContainerToName( $container, '' );
		$result = rmdir( $dir );
		if ( !$result )
		{
			throw new \Exception( 'Failed to delete container.' );
		}
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
	 * @param $container
	 * @param $path
	 *
	 * @return bool
	 */
	public function folderExists( $container, $path )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$key = self::addContainerToName( $container, $path );

		return is_dir( $key );
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param  bool  $include_files
	 * @param  bool  $include_folders
	 * @param  bool  $full_tree
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @return array
	 */
	public function getFolder( $container, $path, $include_files = true, $include_folders = true, $full_tree = false )
	{
		$path = FileUtilities::fixFolderPath( $path );
		if ( !$include_folders && !$include_files )
		{
			$dirPath = self::addContainerToName( $container, $path );
			if ( empty( $path ) )
			{
				$result = array( 'name' => $container );
			}
			else
			{
				$name = basename( $path );
				$result = array( 'name' => $name, 'path' => $path );
			}
			$temp = stat( $dirPath );
			$result['last_modified'] = gmdate( 'D, d M Y H:i:s \G\M\T', Option::get( $temp, 'mtime', 0 ) );

			return $result;
		}

		$delimiter = ( $full_tree ) ? '' : DIRECTORY_SEPARATOR;
		$files = array();
		$folders = array();
		$dirPath = self::addContainerToName( $container, '' );
		if ( is_dir( $dirPath ) )
		{
			$results = static::listTree( $dirPath, $path, $delimiter );
			foreach ( $results as $data )
			{
				$fullPathName = $data['path'];
				if ( '/' == substr( $fullPathName, strlen( $fullPathName ) - 1 ) )
				{
					// folders
					if ( $include_folders )
					{
						$data['name'] = substr( $fullPathName, strlen( $path ), -1 );
						$folders[] = $data;
					}
				}
				else
				{
					// files
					if ( $include_files )
					{
						$data['name'] = substr( $fullPathName, strlen( $path ) );
						$files[] = $data;
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

		return array( "folder" => $folders, "file" => $files );
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
		$this->checkContainerForWrite(); // need to be able to write to storage
		$key = self::addContainerToName( $container, $path );
		if ( !mkdir( $key ) )
		{
			throw new \Exception( 'Failed to create folder.' );
		}
//            $properties = (empty($properties)) ? '' : json_encode($properties);
//            $result = file_put_contents($key, $properties);
//            if (false === $result) {
//                throw new \Exception('Failed to create folder properties.');
//            }
	}

	/**
	 * @param string $container
	 * @param string $dest_path
	 * @param        $src_container
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function copyFolder( $container, $dest_path, $src_container, $src_path, $check_exist = false )
	{
		// does this file already exist?
		if ( !$this->folderExists( $src_container, $src_path ) )
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
		$this->checkContainerForWrite(); // need to be able to write to storage
		FileUtilities::copyTree(
			static::addContainerToName( $src_container, $src_path ),
			static::addContainerToName( $container, $dest_path )
		);
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
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
//            $properties = json_encode($properties);
//            $key = self::addContainerToName($container, $path);
//            $result = file_put_contents($key, $properties);
//            if (false === $result) {
//                throw new \Exception('Failed to create folder properties.');
//            }
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $force If true, delete folder content as well,
	 *                      otherwise return error when content present.
	 *
	 * @return void
	 */
	public function deleteFolder( $container, $path, $force = false )
	{
		$this->checkContainerForWrite(); // need to be able to write to storage
		$dirPath = static::addContainerToName( $container, $path );
		FileUtilities::deleteTree( $dirPath, $force );
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
	 * @return bool
	 */
	public function fileExists( $container, $path )
	{
		$key = static::addContainerToName( $container, $path );

		return is_file( $key ); // is_file() faster than file_exists()
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param string $local_file
	 * @param bool   $content_as_base
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @return string
	 */
	public function getFileContent( $container, $path, $local_file = '', $content_as_base = true )
	{
		$key = static::addContainerToName( $container, $path );
		if ( !is_file( $key ) )
		{
			throw new NotFoundException( "File '$path' does not exist in storage." );
		}
		$data = file_get_contents( $key );
		if ( false === $data )
		{
			throw new \Exception( 'Failed to retrieve file content.' );
		}
		if ( !empty( $local_file ) )
		{
			// write to local or temp file
			$result = file_put_contents( $local_file, $data );
			if ( false === $result )
			{
				throw new \Exception( 'Failed to put file content as local file.' );
			}

			return '';
		}
		else
		{
			// get content as raw or encoded as base64 for transport
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
		if ( !$this->fileExists( $container, $path ) )
		{
			throw new NotFoundException( "File '$path' does not exist in storage." );
		}
		$key = self::addContainerToName( $container, $path );
		$shortName = FileUtilities::getNameFromPath( $path );
		$ext = FileUtilities::getFileExtension( $key );
		$temp = stat( $key );
		$data = array(
			'path'           => $path,
			'name'           => $shortName,
			'content_type'   => FileUtilities::determineContentType( $ext, '', $key ),
			'last_modified'  => gmdate( 'D, d M Y H:i:s \G\M\T', Option::get( $temp, 'mtime', 0 ) ),
			'content_length' => Option::get( $temp, 'size', 0 )
		);
		if ( $include_content )
		{
			$contents = file_get_contents( $key );
			if ( false === $contents )
			{
				throw new \Exception( 'Failed to retrieve file properties.' );
			}
			if ( $content_as_base )
			{
				$contents = base64_encode( $contents );
			}
			$data['content'] = $contents;
		}

		return $data;
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $download
	 *
	 * @return void
	 */
	public function streamFile( $container, $path, $download = false )
	{
		$key = static::addContainerToName( $container, $path );
		if ( is_file( $key ) )
		{
			$ext = FileUtilities::getFileExtension( $key );
			$result = file_get_contents( $key );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', filemtime( $key ) ) );
			header( 'Content-type: ' . FileUtilities::determineContentType( $ext, '', $key ) );
			header( 'Content-Length:' . filesize( $key ) );
			$disposition = ($download) ? 'attachment' : 'inline';
			header( "Content-Disposition: $disposition; filename=\"$path\";" );
			echo $result;
		}
		else
		{
			Log::debug( 'FileManager::streamFile is_file call fail: ' . $key );

			$status_header = "HTTP/1.1 404 The specified file '$path' does not exist.";
			header( $status_header );
			header( 'Content-type: text/html' );
		}
	}

	/**
	 * @param string $container
	 * @param string $path
	 * @param array  $properties
	 *
	 * @return void
	 */
	public function updateFileProperties( $container, $path, $properties = array() )
	{

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
		$this->checkContainerForWrite(); // need to be able to write to storage
		if ( $content_is_base )
		{
			$content = base64_decode( $content );
		}
		$key = self::addContainerToName( $container, $path );
		$result = file_put_contents( $key, $content );
		if ( false === $result )
		{
			throw new \Exception( 'Failed to create file.' );
		}
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
		$this->checkContainerForWrite(); // need to be able to write to storage
		$key = static::addContainerToName( $container, $path );
		if ( !rename( $local_path, $key ) )
		{
			throw new \Exception( "Failed to move file '$path'" );
		}
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
		$this->checkContainerForWrite(); // need to be able to write to storage
		$key = self::addContainerToName( $src_container, $dest_path );
		$src_key = self::addContainerToName( $container, $src_path );
		$result = copy( $src_key, $key );
		if ( !$result )
		{
			throw new \Exception( 'Failed to copy file.' );
		}
	}

	/**
	 * @param string $container
	 * @param string $path
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function deleteFile( $container, $path )
	{
		$key = self::addContainerToName( $container, $path );
		if ( !is_file( $key ) )
		{
			throw new BadRequestException( "'$key' is not a valid filename." );
		}
		$result = unlink( $key );
		if ( !$result )
		{
			throw new \Exception( 'Failed to delete file.' );
		}
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
	 * @param string $container
	 * @param string      $path
	 * @param \ZipArchive $zip
	 * @param string      $zipFileName
	 * @param bool        $overwrite
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return string Zip File Name created/updated
	 */
	public function getFolderAsZip( $container, $path, $zip = null, $zipFileName = '', $overwrite = false )
	{
		$container = self::addContainerToName( $container, '' );
		if ( !is_dir( $container ) )
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
				$temp = FileUtilities::getNameFromPath( $path );
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
		FileUtilities::addTreeToZip( $zip, $container, rtrim( $path, '/' ) );
		if ( $needClose )
		{
			$zip->close();
		}

		return $zipFileName;
	}

	/**
	 * @param string $container
	 * @param string     $path
	 * @param \ZipArchive $zip
	 * @param bool       $clean
	 * @param string     $drop_path
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function extractZipFile( $container, $path, $zip, $clean = false, $drop_path = '' )
	{
		if ( ( $clean ) )
		{
			try
			{
				// clear out anything in this directory
				$dirPath = static::addContainerToName( $container, $path );
				FileUtilities::deleteTree( $dirPath, true, false );
			}
			catch ( \Exception $ex )
			{
				throw new \Exception( "Could not clean out existing directory $path.\n{$ex->getMessage()}" );
			}
		}
		for ( $i = 0; $i < $zip->numFiles; $i++ )
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

		return array( 'folder' => array( 'name' => rtrim( $path, DIRECTORY_SEPARATOR ), 'path' => $path ) );
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	private static function asFullPath( $name )
	{
		return \Defaults::getStoragePath( $name );
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	private static function asLocalPath( $name )
	{
		return basename( \Defaults::getStoragePath( $name ) );
	}

	/**
	 * @param $container
	 *
	 * @return string
	 */
	private static function fixContainerName( $container )
	{
		return rtrim( $container, '/' ) . '/';
	}

	/**
	 * @param $container
	 * @param $name
	 *
	 * @return string
	 */
	private static function addContainerToName( $container, $name )
	{
		if ( !empty( $container ) )
		{
			$container = self::fixContainerName( $container );
		}

		return static::asFullPath( $container . $name );
	}

	/**
	 * @param $container
	 * @param $name
	 *
	 * @return string
	 */
	private static function removeContainerFromName( $container, $name )
	{
		$name = static::asLocalPath( $name );
		if ( empty( $container ) )
		{
			return $name;
		}
		$container = self::fixContainerName( $container );

		return substr( $name, strlen( $container ) + 1 );
	}

	/**
	 * List folders and files
	 *
	 * @param  string $root      root path name
	 * @param  string $prefix    Optional. search only for folders and files by specified prefix.
	 * @param  string $delimiter Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function listTree( $root, $prefix = '', $delimiter = '' )
	{
		$dir = $root . ( ( !empty( $prefix ) ) ? $prefix : '' );
		$out = array();
		if ( is_dir( $dir ) )
		{
			$files = array_diff( scandir( $dir ), array( '.', '..' ) );
			foreach ( $files as $file )
			{
				$key = $dir . $file;
				$local = ( ( !empty( $prefix ) ) ? $prefix : '' ) . $file;
				// get file meta
				if ( is_dir( $key ) )
				{
					$stat = stat( $key );
					$out[] = array(
						'path'          => str_replace( DIRECTORY_SEPARATOR, '/', $local ) . '/',
						'last_modified' => gmdate( 'D, d M Y H:i:s \G\M\T', Option::get( $stat, 'mtime', 0 ) )
					);
					if ( empty( $delimiter ) )
					{
						$out = array_merge( $out, self::listTree( $root, $local . DIRECTORY_SEPARATOR ) );
					}
				}
				elseif ( is_file( $key ) )
				{
					$stat = stat( $key );
					$ext = FileUtilities::getFileExtension( $key );
					$out[] = array(
						'path'           => str_replace( DIRECTORY_SEPARATOR, '/', $local ),
						'content_type'   => FileUtilities::determineContentType( $ext, '', $key ),
						'last_modified'  => gmdate( 'D, d M Y H:i:s \G\M\T', Option::get( $stat, 'mtime', 0 ) ),
						'content_length' => Option::get( $stat, 'size', 0 )
					);
				}
				else
				{
					error_log( $key );
				}
			}
		}
		else
		{
			throw new \Exception( "Folder '$prefix' does not exist in storage." );
		}

		return $out;
	}
}
