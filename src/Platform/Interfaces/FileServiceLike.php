<?php

/**
 * FileServiceLike.php
 * Interface for handling file storage.
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
namespace Platform\Interfaces;

interface FileServiceLike
{
    /**
     * @throw \Exception
     */
    public function checkContainerForWrite();

    /**
     * @param $path
     * @return bool
     * @throw \Exception
     */
    public function folderExists($path);

    /**
     * @param string $path
     * @param bool $include_files
     * @param bool $include_folders
     * @param bool $full_tree
     * @return array
     * @throws \Exception
     */
    public function getFolderContent($path, $include_files = true, $include_folders = true, $full_tree = false);

    /**
     * @param $path
     * @return array
     * @throws \Exception
     */
    public function getFolderProperties($path);

    /**
     * @param string $path
     * @param array $properties
     * @return void
     * @throws \Exception
     */
    public function createFolder($path, $properties = array());

    /**
     * @param string $path
     * @param string $properties
     * @return void
     * @throws \Exception
     */
    public function updateFolderProperties($path, $properties = '');

    /**
     * @param string $dest_path
     * @param string $src_path
     * @param bool $check_exist
     * @return void
     * @throws \Exception
     */
    public function copyFolder($dest_path, $src_path, $check_exist = false);

    /**
     * @param string $path Folder path relative to the service root directory
     * @param  bool $force
     * @return void
     * @throws \Exception
     */
    public function deleteFolder($path, $force = false);

    /**
     * @param array $folders Array of folder paths that are relative to the root directory
     * @param string $root directory from which to delete
     * @param  bool $force
     * @return array
     * @throws \Exception
     */
     public function deleteFolders($folders, $root = '', $force = false);

    /**
     * @param $path
     * @return bool
     * @throws \Exception
     */
    public function fileExists($path);

    /**
     * @param $path
     * @param  string     $local_file
     * @param  bool       $content_as_base
     * @return string
     * @throws \Exception
     */
    public function getFileContent($path, $local_file = '', $content_as_base = true);

    /**
     * @param $path
     * @param  bool       $include_content
     * @param  bool       $content_as_base
     * @return array
     * @throws \Exception
     */
    public function getFileProperties($path, $include_content = false, $content_as_base = true);

    /**
     * @param string $path
     * @return null
     */
    public function streamFile($path);

    /**
     * @param string $path
     * @return null
     */
    public function downloadFile($path);

    /**
     * @param string $path
     * @param string $content
     * @param boolean $content_is_base
     * @param bool $check_exist
     * @return void
     * @throws \Exception
     */
    public function writeFile($path, $content, $content_is_base = true, $check_exist = false);

    /**
     * @param string $path
     * @param string $local_path
     * @param bool $check_exist
     * @return void
     * @throws \Exception
     */
    public function moveFile($path, $local_path, $check_exist = false);

    /**
     * @param string $dest_path
     * @param string $src_path
     * @param bool $check_exist
     * @return void
     * @throws \Exception
     */
    public function copyFile($dest_path, $src_path, $check_exist = false);

    /**
     * @param string $path File path relative to the service root directory
     * @return void
     * @throws \Exception
     */
    public function deleteFile($path);

    /**
     * @param array $files Array of file paths relative to root
     * @param string $root
     * @return array
     * @throws \Exception
     */
    public function deleteFiles($files, $root='');

    /**
     * @param $path
     * @param \ZipArchive $zip
     * @param bool $clean
     * @param string $drop_path
     * @return array
     * @throws \Exception
     */
    public function extractZipFile($path, $zip, $clean = false, $drop_path = '');

    /**
     * @param string $path
     * @param null|\ZipArchive $zip
     * @param string $zipFileName
     * @param bool $overwrite
     * @return string Zip File Name created/updated
     */
    public function getFolderAsZip($path, $zip = null, $zipFileName = '', $overwrite = false);

}
