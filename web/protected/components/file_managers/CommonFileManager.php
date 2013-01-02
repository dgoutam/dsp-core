<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage CommonFileManager
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

abstract class CommonFileManager
{
    /**
     * @var string
     */
    protected $storageContainer;

    /**
     * @throw Exception
     */
    abstract public function checkContainerForWrite();

    /**
     * @param $path
     * @return bool
     * @throw Exception
     */
    abstract public function folderExists($path);

    /**
     * @param string $path
     * @param bool $include_files
     * @param bool $include_folders
     * @param bool $full_tree
     * @return array
     * @throws Exception
     */
    abstract public function getFolderContent($path, $include_files = true, $include_folders = true, $full_tree = false);

    /**
     * @param $path
     * @return array
     * @throws Exception
     */
    abstract public function getFolderProperties($path);

    /**
     * @param string $path
     * @param array $properties
     * @return void
     * @throws Exception
     */
    abstract public function createFolder($path, $properties = array());

    /**
     * @param string $path
     * @param string $properties
     * @return void
     * @throws Exception
     */
    abstract public function updateFolderProperties($path, $properties = '');

    /**
     * @param string $dest_path
     * @param string $src_path
     * @param bool $check_exist
     * @return void
     * @throws Exception
     */
    abstract public function copyFolder($dest_path, $src_path, $check_exist = false);

    /**
     * @param string $path
     * @param  bool $force
     * @return void
     * @throws Exception
     */
    abstract public function deleteFolder($path, $force = false);

    /**
     * @param array $folders
     * @param  bool $force
     * @return array
     * @throws Exception
     */
    abstract  public function deleteFolders($folders, $force = false);

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
    abstract public function fileExists($path);

    /**
     * @param $path
     * @param  string     $local_file
     * @param  bool       $content_as_base
     * @return string
     * @throws Exception
     */
    abstract public function getFileContent($path, $local_file = '', $content_as_base = true);

    /**
     * @param $path
     * @param  bool       $include_content
     * @param  bool       $content_as_base
     * @return array
     * @throws Exception
     */
    abstract public function getFileProperties($path, $include_content = false, $content_as_base = true);

    /**
     * @param string $path
     * @return null
     */
    abstract public function streamFile($path);

    /**
     * @param string $path
     * @return null
     */
    abstract public function downloadFile($path);

    /**
     * @param string $path
     * @param string $content
     * @param boolean $content_is_base
     * @param bool $check_exist
     * @return void
     * @throws Exception
     */
    abstract public function writeFile($path, $content, $content_is_base = true, $check_exist = false);

    /**
     * @param string $path
     * @param string $local_path
     * @param bool $check_exist
     * @return void
     * @throws Exception
     */
    abstract public function moveFile($path, $local_path, $check_exist = false);

    /**
     * @param string $dest_path
     * @param string $src_path
     * @param bool $check_exist
     * @return void
     * @throws Exception
     */
    abstract public function copyFile($dest_path, $src_path, $check_exist = false);

    /**
     * @param $path
     * @return void
     * @throws Exception
     */
    abstract public function deleteFile($path);

    /**
     * @param array $files
     * @return array
     * @throws Exception
     */
    abstract public function deleteFiles($files);

    /**
     * @param $path
     * @param $zip_file
     * @param bool $clean
     * @throws Exception
     */
    abstract public function expandZipFile($path, $zip_file, $clean = false);

    /**
     * @param string $path
     * @param string $zipFileName
     * @param bool $overwrite
     * @throws Exception
     * @return string Zip File Name created/updated
     */
    abstract public function getFolderAsZip($path, $zipFileName = '', $overwrite = false);

}
