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
namespace Platform\Interfaces;

/**
 * FileServiceLike.php
 * Interface for handling file storage resources.
 */
interface FileServiceLike
{
	/**
	 * List all containers, just names if noted
	 *
	 * @param bool $include_properties If true, additional properties are retrieved
	 *
	 * @return array
	 */
	public function listContainers( $include_properties = false );

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 */
	public function containerExists( $container );

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
	public function getContainer( $container, $include_files = false, $include_folders = false, $full_tree = false );

	/**
	 * Create a container using properties, where at least name is required
	 *
	 * @param array $properties
	 * @param bool  $check_exist If true, throws error if the container already exists
	 *
	 * @return void
	 */
	public function createContainer( $properties = array(), $check_exist = false );

	/**
	 * Create multiple containers using array of properties, where at least name is required
	 *
	 * @param array $properties
	 * @param bool  $check_exist If true, throws error if the container already exists
	 *
	 * @return array
	 */
	public function createContainers( $properties = array(), $check_exist = false );

	/**
	 * Update a container with some properties
	 *
	 * @param string $container
	 * @param array  $properties
	 *
	 * @return void
	 */
	public function updateContainerProperties( $container, $properties = array() );

	/**
	 * Delete a container and all of its content
	 *
	 * @param string $container
	 * @param bool   $force Force a delete if it is not empty
	 *
	 * @return void
	 */
	public function deleteContainer( $container, $force = false );

	/**
	 * Delete multiple containers and all of their content
	 *
	 * @param array $containers
	 * @param bool  $force Force a delete if it is not empty
	 *
	 * @return array
	 */
	public function deleteContainers( $containers, $force = false );

	/**
	 * @param $container
	 * @param $path
	 *
	 * @return bool
	 */
	public function folderExists( $container, $path );

	/**
	 * Gets all properties of a particular folder, if options are false,
	 * otherwise include content from the folder
	 *
	 * @param        $container
	 * @param string $path
	 * @param bool   $include_files
	 * @param bool   $include_folders
	 * @param bool   $full_tree
	 *
	 * @return array
	 */
	public function getFolder( $container, $path, $include_files = false, $include_folders = false, $full_tree = false );

	/**
	 * @param        $container
	 * @param string $path
	 * @param array  $properties
	 *
	 * @return void
	 */
	public function createFolder( $container, $path, $properties = array() );

	/**
	 * @param              $container
	 * @param string       $path
	 * @param array|string $properties
	 *
	 * @return void
	 */
	public function updateFolderProperties( $container, $path, $properties = array() );

	/**
	 * @param        $container
	 * @param string $dest_path
	 * @param        $src_container
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 */
	public function copyFolder( $container, $dest_path,  $src_container, $src_path, $check_exist = false );

	/**
	 * @param        $container
	 * @param string $path Folder path relative to the service root directory
	 * @param  bool  $force
	 *
	 * @return void
	 */
	public function deleteFolder( $container, $path, $force = false );

	/**
	 * @param $container
	 * @param $path
	 *
	 * @return bool
	 */
	public function fileExists( $container, $path );

	/**
	 * @param         $container
	 * @param         $path
	 * @param  string $local_file
	 * @param  bool   $content_as_base
	 *
	 * @return string
	 */
	public function getFileContent( $container, $path, $local_file = '', $content_as_base = true );

	/**
	 * @param       $container
	 * @param       $path
	 * @param  bool $include_content
	 * @param  bool $content_as_base
	 *
	 * @return array
	 */
	public function getFileProperties( $container, $path, $include_content = false, $content_as_base = true );

	/**
	 * @param string $container
	 * @param string $path
	 * @param bool   $download
	 *
	 * @return void
	 */
	public function streamFile( $container, $path, $download = false );

	/**
	 * @param string $container
	 * @param string $path
	 * @param array  $properties
	 *
	 * @return void
	 */
	public function updateFileProperties( $container, $path, $properties = array() );

	/**
	 * @param         $container
	 * @param string  $path
	 * @param string  $content
	 * @param boolean $content_is_base
	 * @param bool    $check_exist
	 *
	 * @return void
	 */
	public function writeFile( $container, $path, $content, $content_is_base = true, $check_exist = false );

	/**
	 * @param        $container
	 * @param string $path
	 * @param string $local_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 */
	public function moveFile( $container, $path, $local_path, $check_exist = false );

	/**
	 * @param        $container
	 * @param string $dest_path
	 * @param        $sc_container
	 * @param string $src_path
	 * @param bool   $check_exist
	 *
	 * @return void
	 */
	public function copyFile( $container, $dest_path, $sc_container, $src_path, $check_exist = false );

	/**
	 * @param        $container
	 * @param string $path File path relative to the service root directory
	 *
	 * @return void
	 */
	public function deleteFile( $container, $path );

	/**
	 * @param        $container
	 * @param array  $files Array of file paths relative to root
	 * @param string $root
	 *
	 * @return array
	 */
	public function deleteFiles( $container, $files, $root = '' );

	/**
	 * @param             $container
	 * @param             $path
	 * @param \ZipArchive $zip
	 * @param bool        $clean
	 * @param string      $drop_path
	 *
	 * @return array
	 */
	public function extractZipFile( $container, $path, $zip, $clean = false, $drop_path = '' );

	/**
	 * @param                  $container
	 * @param string           $path
	 * @param null|\ZipArchive $zip
	 * @param string           $zipFileName
	 * @param bool             $overwrite
	 *
	 * @return string Zip File Name created/updated
	 */
	public function getFolderAsZip( $container, $path, $zip = null, $zipFileName = '', $overwrite = false );

}
