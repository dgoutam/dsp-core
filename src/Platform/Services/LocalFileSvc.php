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
	 * Create a new LocalFileSvc
	 *
	 * @param  array $config
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		// Validate local setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			throw new \InvalidArgumentException( 'Local container name can not be empty.' );
		}
		$this->storageContainer = $store_name;
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
		$container = self::addContainerToName( $this->storageContainer, '' );
		if ( !is_dir( $container ) )
		{
			if ( !mkdir( $container ) )
			{
				throw new \Exception( 'Failed to create container.' );
			}
		}
	}

	/**
	 * @param $path
	 *
	 * @return bool
	 */
	public function folderExists( $path )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$key = self::addContainerToName( $this->storageContainer, $path );

		return is_dir( $key );
	}

	/**
	 * @param             $path
	 * @param  bool       $include_files
	 * @param  bool       $include_folders
	 * @param  bool       $full_tree
	 *
	 * @return array
	 * @throws NotFoundException
	 */
	public function getFolderContent( $path, $include_files = true, $include_folders = true, $full_tree = false )
	{
		$path = FileUtilities::fixFolderPath( $path );
		$delimiter = ( $full_tree ) ? '' : DIRECTORY_SEPARATOR;
		$files = array();
		$folders = array();
		$dirPath = self::addContainerToName( $this->storageContainer, '' );
		if ( is_dir( $dirPath ) )
		{
			$results = static::listTree( $dirPath, $path, $delimiter );
			foreach ( $results as $data )
			{
				$fullPathName = $data['name'];
				$shortName = FileUtilities::getNameFromPath( $fullPathName );
				if ( '/' == substr( $fullPathName, strlen( $fullPathName ) - 1 ) )
				{
					// folders
					if ( $include_folders )
					{
						$folders[] = array(
							'name'         => $shortName,
							'path'         => $fullPathName,
							'lastModified' => isset( $data['lastModified'] ) ? $data['lastModified'] : null
						);
					}
				}
				else
				{
					// files
					if ( $include_files )
					{
						$data['path'] = $fullPathName;
						$data['name'] = $shortName;
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
	 * @param string $path
	 *
	 * @return array
	 * @throws NotFoundException
	 */
	public function getFolderProperties( $path )
	{
		$path = FileUtilities::fixFolderPath( $path );
		if ( !$this->folderExists( $path ) )
		{
			throw new NotFoundException( "Folder '$path' does not exist in storage." );
		}

		return array( 'folder' => array( array( 'path' => $path, 'properties' => array() ) ) );
	}

	/**
	 * @param string $path
	 * @param bool   $is_public
	 * @param array  $properties
	 * @param bool   $check_exist
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
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

		// create the folder
		$this->checkContainerForWrite(); // need to be able to write to storage
		$key = self::addContainerToName( $this->storageContainer, $path );
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
	 * @param string $dest_path
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
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
		// create the folder
		$this->checkContainerForWrite(); // need to be able to write to storage
		FileUtilities::copyTree(
			static::addContainerToName( $this->storageContainer, $src_path ),
			static::addContainerToName( $this->storageContainer, $dest_path )
		);
	}

	/**
	 * @param string $path
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
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
			// update the file that holds folder properties
//            $properties = json_encode($properties);
//            $key = self::addContainerToName($this->storageContainer, $path);
//            $result = file_put_contents($key, $properties);
//            if (false === $result) {
//                throw new \Exception('Failed to create folder properties.');
//            }
	}

	/**
	 * @param string $path
	 * @param bool   $force If true, delete folder content as well,
	 *                      otherwise return error when content present.
	 *
	 * @return void
	 */
	public function deleteFolder( $path, $force = false )
	{
		$this->checkContainerForWrite(); // need to be able to write to storage
		$dirPath = static::addContainerToName( $this->storageContainer, $path );
		FileUtilities::deleteTree( $dirPath, $force );
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
	 * @return bool
	 */
	public function fileExists( $path )
	{
		$key = static::asFullPath( self::addContainerToName( $this->storageContainer, $path ) );

		return is_file( $key ); // is_file() faster than file_exists()
	}

	/**
	 * @param string $path
	 * @param string $local_file
	 * @param bool   $content_as_base
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\NotFoundException
	 * @return string
	 */
	public function getFileContent( $path, $local_file = '', $content_as_base = true )
	{
		$key = static::addContainerToName( $this->storageContainer, $path );
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
		if ( !$this->fileExists( $path ) )
		{
			throw new NotFoundException( "File '$path' does not exist in storage." );
		}
		$key = self::addContainerToName( $this->storageContainer, $path );
		$shortName = FileUtilities::getNameFromPath( $path );
		$ext = FileUtilities::getFileExtension( $key );
		$data = array(
			'path'         => $path,
			'name'         => $shortName,
			'contentType'  => FileUtilities::determineContentType( $ext, '', $key ),
			'lastModified' => gmdate( 'D, d M Y H:i:s \G\M\T', filemtime( $key ) ),
			'size'         => filesize( $key )
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
	 * @param string $path
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function streamFile( $path )
	{
		$key = static::addContainerToName( $this->storageContainer, $path );
		if ( is_file( $key ) )
		{
			$ext = FileUtilities::getFileExtension( $key );
			$result = file_get_contents( $key );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', filemtime( $key ) ) );
			header( 'Content-type: ' . FileUtilities::determineContentType( $ext, '', $key ) );
			header( 'Content-Length:' . filesize( $key ) );
			$disposition = 'inline';
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
	 * @param string $path
	 *
	 * @return void
	 */
	public function downloadFile( $path )
	{
		$key = static::addContainerToName( $this->storageContainer, $path );
		if ( is_file( $key ) )
		{
			$result = file_get_contents( $key );
			$ext = FileUtilities::getFileExtension( $key );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', filemtime( $key ) ) );
			header( 'Content-type: ' . FileUtilities::determineContentType( $ext, '', $key ) );
			header( 'Content-Length:' . filesize( $key ) );
			$disposition = 'attachment';
			header( "Content-Disposition: $disposition; filename=\"$path\";" );
			echo $result;
		}
		else
		{
			$status_header = "HTTP/1.1 404 The specified file '$path' does not exist.";
			header( $status_header );
			header( 'Content-type: text/html' );
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

		// create the file
		$this->checkContainerForWrite(); // need to be able to write to storage
		if ( $content_is_base )
		{
			$content = base64_decode( $content );
		}
		$key = self::addContainerToName( $this->storageContainer, $path );
		$result = file_put_contents( $key, $content );
		if ( false === $result )
		{
			throw new \Exception( 'Failed to create file.' );
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

		// create the file
		$this->checkContainerForWrite(); // need to be able to write to storage
		$key = static::addContainerToName( $this->storageContainer, $path );
		if ( !rename( $local_path, $key ) )
		{
			throw new \Exception( "Failed to move file '$path'" );
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
			$key = self::addContainerToName( $this->storageContainer, $dest_path );
			$src_key = self::addContainerToName( $this->storageContainer, $src_path );
			$result = copy( $src_key, $key );
			if ( !$result )
			{
				throw new \Exception( 'Failed to copy file.' );
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $path
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return void
	 */
	public function deleteFile( $path )
	{
		try
		{
			$key = self::addContainerToName( $this->storageContainer, $path );
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
	 * @param string      $path
	 * @param \ZipArchive $zip
	 * @param string      $zipFileName
	 * @param bool        $overwrite
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return string Zip File Name created/updated
	 */
	public function getFolderAsZip( $path, $zip = null, $zipFileName = '', $overwrite = false )
	{
		try
		{
			$container = self::addContainerToName( $this->storageContainer, '' );
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
			FileUtilities::addTreeToZip( $zip, $container, rtrim( $path, '/' ) );
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
	 * @param string     $path
	 * @param \ZipArchive $zip
	 * @param bool       $clean
	 * @param string     $drop_path
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function extractZipFile( $path, $zip, $clean = false, $drop_path = '' )
	{
		if ( ( $clean ) )
		{
			try
			{
				// clear out anything in this directory
				$dirPath = static::addContainerToName( $this->storageContainer, $path );
				FileUtilities::deleteTree( $dirPath, true, false );
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
//		return substr( $name, strlen( $root ) + 1 );
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
	 * @return array
	 * @throws \Exception
	 */
	public function listContainers()
	{
		try
		{
			$out = array();
			$handle = opendir( static::asFullPath( '' ) );
			if ( $handle )
			{
				while ( false !== ( $name = readdir( $handle ) ) )
				{
					if ( $name != "." && $name != ".." )
					{
						if ( is_dir( $name ) )
						{
							$out[] = array( 'name' => $name );
						}
					}
				}
				closedir( $handle );
			}

			return $out;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $container
	 *
	 * @throws \Exception
	 */
	public function deleteContainer( $container = '' )
	{
		$dir = static::addContainerToName( $container, '' );
		$result = rmdir( $dir );
		if ( !$result )
		{
			throw new \Exception( 'Failed to delete container.' );
		}
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
					$out[] = array(
						'name'         => str_replace( DIRECTORY_SEPARATOR, '/', $local ) . '/',
						'lastModified' => gmdate( 'D, d M Y H:i:s \G\M\T', filemtime( $key ) )
					);
					if ( empty( $delimiter ) )
					{
						$out = array_merge( $out, self::listTree( $root, $local . DIRECTORY_SEPARATOR ) );
					}
				}
				elseif ( is_file( $key ) )
				{
					$ext = FileUtilities::getFileExtension( $key );
					$out[] = array(
						'name'         => str_replace( DIRECTORY_SEPARATOR, '/', $local ),
						'contentType'  => FileUtilities::determineContentType( $ext, '', $key ),
						'lastModified' => gmdate( 'D, d M Y H:i:s \G\M\T', filemtime( $key ) ),
						'size'         => filesize( $key )
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
