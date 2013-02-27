<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage CommonFileSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class CommonFileSvc extends CommonService implements iRestHandler
{
    /**
     * @var FileManager|BlobFileManager
     */
    protected $fileRestHandler;

    /**
     * @param array $config
     * @throws Exception
     */
    public function __construct($config)
    {
        parent::__construct($config);

        // Validate storage setup
        $store_name = Utilities::getArrayValue('storage_name', $config, '');
        if (empty($store_name)) {
            throw new Exception("Error creating common file service. No storage name given.");
        }
        try {
            $type = Utilities::getArrayValue('type', $config, '');
            switch (strtolower($type)) { // remote blob storage or native disk
            case 'remote file storage':
                $this->fileRestHandler = new BlobFileManager($config, $store_name);
                break;
            default:
                $this->fileRestHandler = new FileManager($store_name);
                break;
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error creating document storage.\n{$ex->getMessage()}");
        }
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * @param string $path
     * @return void
     * @throws Exception
     */
    public function streamFile($path)
    {
        try {
            $this->fileRestHandler->streamFile($path);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function handleFile($dest_path, $dest_name, $source_file, $contentType='',
                                  $extract=false, $clean=false, $check_exist=false)
    {
        $ext = FileUtilities::getFileExtension($source_file);
        if (empty($contentType)) {
            $contentType = FileUtilities::determineContentType($ext, '', $source_file);
        }
        if ((FileUtilities::isZipContent($contentType) || ('zip' === $ext)) && $extract) {
            // need to extract zip file and move contents to storage
            $zip = new ZipArchive();
            if (true === $zip->open($source_file)) {
                return $this->fileRestHandler->extractZipFile($dest_path, $zip, $clean);
            }
            else {
                throw new Exception('Error opening temporary zip file.');
            }
        }
        else {
            $name = (empty($dest_name) ? basename($source_file) : $dest_name);
            $fullPathName = $dest_path . $name;
            $this->fileRestHandler->moveFile($fullPathName, $source_file, $check_exist);
            return array('file' => array(array('name' => $name, 'path' => $fullPathName)));
        }
    }

    protected function handleFileContent($dest_path, $dest_name, $content, $contentType='',
                                         $extract=false, $clean=false, $check_exist=false)
    {
        $ext = FileUtilities::getFileExtension($dest_name);
        if (empty($contentType)) {
            $contentType = FileUtilities::determineContentType($ext, $content);
        }
        if ((FileUtilities::isZipContent($contentType) || ('zip' === $ext)) && $extract) {
            // need to extract zip file and move contents to storage
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $tmpName = $tempDir  . $dest_name;
            file_put_contents($tmpName, $content);
            $zip = new ZipArchive();
            if (true === $zip->open($tmpName)) {
                return $this->fileRestHandler->extractZipFile($dest_path, $zip, $clean);
            }
            else {
                throw new Exception('Error opening temporary zip file.');
            }
        }
        else {
            $fullPathName = $dest_path . $dest_name;
            $this->fileRestHandler->writeFile($fullPathName, $content, false, $check_exist);
            return array('file' => array(array('name' => $dest_name, 'path' => $fullPathName)));
        }
    }

    // Controller based methods

    /**
     * @return array
     * @throws Exception
     */
    public function actionGet()
    {
        $this->checkPermission('read');
        $result = array();
        $path = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path)) ? explode('/', $path) : array();
        if (empty($path) || empty($path_array[count($path_array) - 1])) {
            // list from root
            // or if ending in '/' then resource is a folder
            try {
                if (isset($_REQUEST['properties'])) {
                    // just properties of the directory itself
                    $result = $this->fileRestHandler->getFolderProperties($path);
                }
                else {
                    $asZip = Utilities::boolval(Utilities::getArrayValue('zip', $_REQUEST, false));
                    if ($asZip) {
                        $zipFileName = $this->fileRestHandler->getFolderAsZip($path);
                        $fd = fopen ($zipFileName, "r");
                        if ($fd) {
                            $fsize = filesize($zipFileName);
                            $path_parts = pathinfo($zipFileName);
                            header("Content-type: application/zip");
                            header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
                            header("Content-length: $fsize");
                            header("Cache-control: private"); //use this to open files directly
                            while(!feof($fd)) {
                                $buffer = fread($fd, 2048);
                                echo $buffer;
                            }
                        }
                        fclose($fd);
                        unlink($zipFileName);
                        Yii::app()->end();
                    }
                    else {
                        $full_tree = (isset($_REQUEST['full_tree'])) ? true : false;
                        $include_files = true;
                        $include_folders = true;
                        if (isset($_REQUEST['files_only'])) {
                            $include_folders = false;
                        }
                        elseif (isset($_REQUEST['folders_only'])) {
                            $include_files = false;
                        }
                        $result = $this->fileRestHandler->getFolderContent($path, $include_files, $include_folders, $full_tree);
                    }
                }
            }
            catch (Exception $ex) {
                throw new Exception("Failed to retrieve folder content for '$path''.\n{$ex->getMessage()}");
            }
        }
        else {
            // resource is a file
            try {
                if (isset($_REQUEST['properties'])) {
                    // just properties of the file itself
                    $content = Utilities::boolval(Utilities::getArrayValue('content', $_REQUEST, false));
                    $result = $this->fileRestHandler->getFileProperties($path, $content);
                }
                else {
                    $download = Utilities::boolval(Utilities::getArrayValue('download', $_REQUEST, false));
                    if ($download) {
                        // stream the file, exits processing
                        $this->fileRestHandler->downloadFile($path);
                    }
                    else {
                        // stream the file, exits processing
                        $this->fileRestHandler->streamFile($path);
                    }
                }
            }
            catch (Exception $ex) {
                throw new Exception("Failed to retrieve file '$path''.\n{$ex->getMessage()}");
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
        $this->checkPermission('create');
        $path = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path)) ? explode('/', $path) : array();
        $result = array();
        // possible file handling parameters
        $extract = Utilities::boolval(Utilities::getArrayValue('extract', $_REQUEST, false));
        $clean = Utilities::boolval(Utilities::getArrayValue('clean', $_REQUEST, false));
        $checkExist = Utilities::boolval(Utilities::getArrayValue('check_exist', $_REQUEST, true));
        if (empty($path) || empty($path_array[count($path_array) - 1])) {
            // if ending in '/' then create files or folders in the directory
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !empty($_SERVER['HTTP_X_FILE_NAME'])) {
                // html5 single posting for file create
                $name = $_SERVER['HTTP_X_FILE_NAME'];
                $fullPathName = $path . $name;
                try {
                    $content = Utilities::getPostData();
                    if (empty($content)) {
                        // empty post?
                        error_log("Empty content in create file $fullPathName.");
                    }
                    $contentType = Utilities::getArrayValue('CONTENT_TYPE', $_SERVER, '');
                    $result = $this->handleFileContent($path, $name, $content, $contentType, $extract, $clean, $checkExist);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to create file $fullPathName.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_SERVER['HTTP_X_FOLDER_NAME']) && !empty($_SERVER['HTTP_X_FOLDER_NAME'])) {
                // html5 single posting for folder create
                $name = $_SERVER['HTTP_X_FOLDER_NAME'];
                $fullPathName = $path . $name;
                try {
                    $content = Utilities::getPostDataAsArray();
                    $this->fileRestHandler->createFolder($fullPathName, true, $content, true);
                    $result = array('folder' => array(array('name' => $name, 'path' => $fullPathName)));
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to create folder $fullPathName.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multi-part/form-data post, single or multiple files
                $files = $_FILES['files'];
                if (!is_array($files['error'])) {
                    // single file
                    $name = $files['name'];
                    $fullPathName = $path . $name;
                    $error = $files['error'];
                    if ($error == UPLOAD_ERR_OK) {
                        $tmpName = $files['tmp_name'];
                        $contentType = $files['type'];
                        $result = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                    }
                    else {
                        $result = array('code' => 500,
                                        'message' => "Failed to create file $fullPathName.\n$error");
                    }
                }
                else {
                    $out = array();
                    //$files = Utilities::reorgFilePostArray($files);
                    foreach ($files['error'] as $key => $error) {
                        $name = $files['name'][$key];
                        $fullPathName = $path . $name;
                        if ($error == UPLOAD_ERR_OK) {
                            $tmpName = $files['tmp_name'][$key];
                            $contentType = $files['type'][$key];
                            $tmp = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                            $out[$key] = (isset($tmp['file']) ? $tmp['file'] : array());
                        }
                        else {
                            $out[$key]['error'] = array('code' => 500,
                                                        'message' => "Failed to create file $fullPathName.\n$error");
                        }
                    }
                    $result = array('file' => $out);
                }
            }
            else {
                // possibly xml or json post either of files or folders to create, copy or move
                $fileUrl = Utilities::getArrayValue('url', $_REQUEST, '');
                if (!empty($fileUrl)) {
                    // upload a file from a url, could be expandable zip
                    $tmpName = FileUtilities::importUrlFileToTemp($fileUrl);
                    try {
                        $result = $this->handleFile($path, '', $tmpName, '', $extract, $clean, $checkExist);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update folders or files from request.\n{$ex->getMessage()}");
                    }
                }
                else {
                    try {
                        $data = Utilities::getPostDataAsArray();
                        if (empty($data)) {
                            // create folder from resource path
                            $this->fileRestHandler->createFolder($path);
                            $result = array('folder' => array(array('path' => $path)));
                        }
                        else {
                            $out = array('folder' => array(), 'file' => array());
                            $folders = Utilities::getArrayValue('folder', $data, null);
                            if (empty($folders)) {
                                $folders = (isset($data['folders']['folder']) ? $data['folders']['folder'] : null);
                            }
                            if (!empty($folders)) {
                                if (!isset($folders[0])) {
                                    // single folder, make into array
                                    $folders = array($folders);
                                }
                                foreach ($folders as $key=>$folder) {
                                    $name = Utilities::getArrayValue('name', $folder, '');
                                    if (isset($folder['source_path'])) {
                                        // copy or move
                                        $srcPath = $folder['source_path'];
                                        if (empty($name)) {
                                            $name = FileUtilities::getNameFromPath($srcPath);
                                        }
                                        $fullPathName = $path . $name . '/';
                                        $out['folder'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->copyFolder($fullPathName, $srcPath, true);
                                            $deleteSource = Utilities::boolval(Utilities::getArrayValue('delete_source', $folder, false));
                                            if ($deleteSource) {
                                                $this->fileRestHandler->deleteFolder($srcPath, true);
                                            }
                                        }
                                        catch (Exception $ex) {
                                            $out['folder'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                    else {
                                        $fullPathName = $path . $name;
                                        $content = Utilities::getArrayValue('content', $folder, '');
                                        $isBase64 = Utilities::boolval(Utilities::getArrayValue('is_base64', $folder, false));
                                        if ($isBase64) {
                                            $content = base64_decode($content);
                                        }
                                        $out['folder'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->createFolder($fullPathName, true, $content);
                                        }
                                        catch (Exception $ex) {
                                            $out['folder'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                }
                            }
                            $files = Utilities::getArrayValue('file', $data, null);
                            if (empty($files)) {
                                $files = (isset($data['files']['file']) ? $data['files']['file'] : null);
                            }
                            if (!empty($files)) {
                                if (!isset($files[0])) {
                                    // single file, make into array
                                    $files = array($files);
                                }
                                foreach ($files as $key=>$file) {
                                    $name = Utilities::getArrayValue('name', $file, '');
                                    if (isset($file['source_path'])) {
                                        // copy or move
                                        $srcPath = $file['source_path'];
                                        if (empty($name)) {
                                            $name = FileUtilities::getNameFromPath($srcPath);
                                        }
                                        $fullPathName = $path . $name;
                                        $out['file'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->copyFile($fullPathName, $srcPath, true);
                                            $deleteSource = Utilities::boolval(Utilities::getArrayValue('delete_source', $file, false));
                                            if ($deleteSource) {
                                                $this->fileRestHandler->deleteFile($srcPath);
                                            }
                                        }
                                        catch (Exception $ex) {
                                            $out['file'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                    elseif (isset($file['content'])) {
                                        $fullPathName = $path . $name;
                                        $out['file'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        $content = Utilities::getArrayValue('content', $file, '');
                                        $isBase64 = Utilities::boolval(Utilities::getArrayValue('is_base64', $file, false));
                                        if ($isBase64) {
                                            $content = base64_decode($content);
                                        }
                                        try {
                                            $this->fileRestHandler->writeFile($fullPathName, $content);
                                        }
                                        catch (Exception $ex) {
                                            $out['file'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                }
                            }
                            $result = $out;
                        }
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to create folders or files from request.\n{$ex->getMessage()}");
                    }
                }
            }
        }
        else {
            // if ending in file name, create the file or folder
            $name = substr($path, strripos($path, '/') + 1);
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !empty($_SERVER['HTTP_X_FILE_NAME'])) {
                $x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
                if (0 !== strcasecmp($name, $x_file_name)) {
                    throw new Exception("Header file name '$x_file_name' mismatched with REST resource '$name'.");
                }
                try {
                    $content = Utilities::getPostData();
                    if (empty($content)) {
                        // empty post?
                        error_log("Empty content in write file $path to storage.");
                    }
                    $contentType = Utilities::getArrayValue('CONTENT_TYPE', $_SERVER, '');
                    $path = substr($path, 0, strripos($path, '/') + 1);
                    $result = $this->handleFileContent($path, $name, $content, $contentType, $extract, $clean, $checkExist);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to create file $path.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_SERVER['HTTP_X_FOLDER_NAME']) && !empty($_SERVER['HTTP_X_FOLDER_NAME'])) {
                $x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
                if (0 !== strcasecmp($name, $x_folder_name)) {
                    throw new Exception("Header folder name '$x_folder_name' mismatched with REST resource '$name'.");
                }
                try {
                    $content = Utilities::getPostDataAsArray();
                    $this->fileRestHandler->createFolder($path, true, $content);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to create file $path.\n{$ex->getMessage()}");
                }
                $result = array('folder' => array(array('name' => $name, 'path' => $path)));
            }
            elseif (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multipart/form-data post, should be single file
                $files = $_FILES['files'];
                //$files = Utilities::reorgFilePostArray($files);
                if (1 < count($files['error'])) {
                    throw new Exception("Multiple files uploaded to a single REST resource '$name'.");
                }
                $name = $files['name'][0];
                $fullPathName = $path;
                $path = substr($path, 0, strripos($path, '/') + 1);
                $error = $files['error'][0];
                if (UPLOAD_ERR_OK == $error) {
                    $tmpName = $files["tmp_name"][0];
                    $contentType = $files['type'][0];
                    try {
                        $result = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to create file $fullPathName.\n{$ex->getMessage()}");
                    }
                }
                else {
                    throw new Exception("Failed to upload file $name.\n$error");
                }
            }
            else {
                // possibly xml or json post either of file or folder to create, copy or move
                try {
                    $data = Utilities::getPostDataAsArray();
                    error_log(print_r($data, true));
                    //$this->addFiles($path, $data['files']);
                    $result = array();
                }
                catch (Exception $ex) {

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
        $this->checkPermission('update');
        $path = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path)) ? explode('/', $path) : array();
        $result = array();
        // possible file handling parameters
        $extract = Utilities::boolval(Utilities::getArrayValue('extract', $_REQUEST, false));
        $clean = Utilities::boolval(Utilities::getArrayValue('clean', $_REQUEST, false));
        $checkExist = false;
        if (empty($path) || empty($path_array[count($path_array) - 1])) {
            // if ending in '/' then create files or folders in the directory
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !empty($_SERVER['HTTP_X_FILE_NAME'])) {
                // html5 single posting for file create
                $name = $_SERVER['HTTP_X_FILE_NAME'];
                $fullPathName = $path . $name;
                try {
                    $content = Utilities::getPostData();
                    if (empty($content)) {
                        // empty post?
                        error_log("Empty content in update file $fullPathName.");
                    }
                    $contentType = Utilities::getArrayValue('CONTENT_TYPE', $_SERVER, '');
                    $result = $this->handleFileContent($path, $name, $content, $contentType, $extract, $clean, $checkExist);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update file $fullPathName.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_SERVER['HTTP_X_FOLDER_NAME']) && !empty($_SERVER['HTTP_X_FOLDER_NAME'])) {
                // html5 single posting for folder create
                $name = $_SERVER['HTTP_X_FOLDER_NAME'];
                $fullPathName = $path . $name;
                try {
                    $content = Utilities::getPostDataAsArray();
                    $this->fileRestHandler->createFolder($fullPathName, true, $content, true);
                    $result = array('folder' => array(array('name' => $name, 'path' => $fullPathName)));
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update folder $fullPathName.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multi-part/form-data post, single or multiple files
                $files = $_FILES['files'];
                if (!is_array($files['error'])) {
                    // single file
                    $name = $files['name'];
                    $fullPathName = $path . $name;
                    $error = $files['error'];
                    if ($error == UPLOAD_ERR_OK) {
                        $tmpName = $files['tmp_name'];
                        $contentType = $files['type'];
                        $result = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                    }
                    else {
                        $result = array('code' => 500,
                                        'message' => "Failed to update file $fullPathName.\n$error");
                    }
                }
                else {
                    $out = array();
                    //$files = Utilities::reorgFilePostArray($files);
                    foreach ($files['error'] as $key => $error) {
                        $name = $files['name'][$key];
                        $fullPathName = $path . $name;
                        if ($error == UPLOAD_ERR_OK) {
                            $tmpName = $files['tmp_name'][$key];
                            $contentType = $files['type'][$key];
                            $tmp = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                            $out[$key] = (isset($tmp['file']) ? $tmp['file'] : array());
                        }
                        else {
                            $out[$key]['error'] = array('code' => 500,
                                                        'message' => "Failed to update file $fullPathName.\n$error");
                        }
                    }
                    $result = array('file' => $out);
                }
            }
            else {
                $fileUrl = Utilities::getArrayValue('url', $_REQUEST, '');
                if (!empty($fileUrl)) {
                    // upload a file from a url, could be expandable zip
                    $tmpName = FileUtilities::importUrlFileToTemp($fileUrl);
                    try {
                        $result = $this->handleFile($path, '', $tmpName, '', $extract, $clean, $checkExist);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update folders or files from request.\n{$ex->getMessage()}");
                    }
                }
                else {
                    try {
                        $data = Utilities::getPostDataAsArray();
                        if (empty($data)) {
                            // create folder from resource path
                            $this->fileRestHandler->createFolder($path);
                            $result = array('folder' => array(array('path' => $path)));
                        }
                        else {
                            $out = array('folder' => array(), 'file' => array());
                            $folders = Utilities::getArrayValue('folder', $data, null);
                            if (empty($folders)) {
                                $folders = (isset($data['folders']['folder']) ? $data['folders']['folder'] : null);
                            }
                            if (!empty($folders)) {
                                if (!isset($folders[0])) {
                                    // single folder, make into array
                                    $folders = array($folders);
                                }
                                foreach ($folders as $key=>$folder) {
                                    $name = Utilities::getArrayValue('name', $folder, '');
                                    if (isset($folder['source_path'])) {
                                        // copy or move
                                        $srcPath = $folder['source_path'];
                                        if (empty($name)) {
                                            $name = FileUtilities::getNameFromPath($srcPath);
                                        }
                                        $fullPathName = $path . $name . '/';
                                        $out['folder'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->copyFolder($fullPathName, $srcPath, true);
                                            $deleteSource = Utilities::boolval(Utilities::getArrayValue('delete_source', $folder, false));
                                            if ($deleteSource) {
                                                $this->fileRestHandler->deleteFolder($srcPath, true);
                                            }
                                        }
                                        catch (Exception $ex) {
                                            $out['folder'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                    else {
                                        $fullPathName = $path . $name;
                                        $content = Utilities::getArrayValue('content', $folder, '');
                                        $isBase64 = Utilities::boolval(Utilities::getArrayValue('is_base64', $folder, false));
                                        if ($isBase64) {
                                            $content = base64_decode($content);
                                        }
                                        $out['folder'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->createFolder($fullPathName, true, $content);
                                        }
                                        catch (Exception $ex) {
                                            $out['folder'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                }
                            }
                            $files = Utilities::getArrayValue('file', $data, null);
                            if (empty($files)) {
                                $files = (isset($data['files']['file']) ? $data['files']['file'] : null);
                            }
                            if (!empty($files)) {
                                if (!isset($files[0])) {
                                    // single file, make into array
                                    $files = array($files);
                                }
                                foreach ($files as $key=>$file) {
                                    $name = Utilities::getArrayValue('name', $file, '');
                                    if (isset($file['source_path'])) {
                                        // copy or move
                                        $srcPath = $file['source_path'];
                                        if (empty($name)) {
                                            $name = FileUtilities::getNameFromPath($srcPath);
                                        }
                                        $fullPathName = $path . $name;
                                        $out['file'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->copyFile($fullPathName, $srcPath, true);
                                            $deleteSource = Utilities::boolval(Utilities::getArrayValue('delete_source', $file, false));
                                            if ($deleteSource) {
                                                $this->fileRestHandler->deleteFile($srcPath);
                                            }
                                        }
                                        catch (Exception $ex) {
                                            $out['file'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                    elseif (isset($file['content'])) {
                                        $fullPathName = $path . $name;
                                        $out['file'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        $content = Utilities::getArrayValue('content', $file, '');
                                        $isBase64 = Utilities::boolval(Utilities::getArrayValue('is_base64', $file, false));
                                        if ($isBase64) {
                                            $content = base64_decode($content);
                                        }
                                        try {
                                            $this->fileRestHandler->writeFile($fullPathName, $content);
                                        }
                                        catch (Exception $ex) {
                                            $out['file'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                }
                            }
                            $result = $out;
                        }
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update folders or files from request.\n{$ex->getMessage()}");
                    }
                }
            }
        }
        else {
            // if ending in file name, update the file or folder
            $name = substr($path, strripos($path, '/') + 1);
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !empty($_SERVER['HTTP_X_FILE_NAME'])) {
                $x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
                if (0 !== strcasecmp($name, $x_file_name)) {
                    throw new Exception("Header file name '$x_file_name' mismatched with REST resource '$name'.");
                }
                try {
                    $content = Utilities::getPostData();
                    if (empty($content)) {
                        // empty post?
                        error_log("Empty content in write file $path to storage.");
                    }
                    $contentType = Utilities::getArrayValue('CONTENT_TYPE', $_SERVER, '');
                    $path = substr($path, 0, strripos($path, '/') + 1);
                    $result = $this->handleFileContent($path, $name, $content, $contentType, $extract, $clean, $checkExist);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update file $path.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_SERVER['HTTP_X_FOLDER_NAME']) && !empty($_SERVER['HTTP_X_FOLDER_NAME'])) {
                $x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
                if (0 !== strcasecmp($name, $x_folder_name)) {
                    throw new Exception("Header folder name '$x_folder_name' mismatched with REST resource '$name'.");
                }
                try {
                    $content = Utilities::getPostDataAsArray();
                    $this->fileRestHandler->updateFolderProperties($path, $content);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update folder $path.\n{$ex->getMessage()}");
                }
                $result = array('folder' => array(array('name' => $name, 'path' => $path)));
            }
            elseif (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multipart/form-data post, should be single file
                $files = $_FILES['files'];
                //$files = Utilities::reorgFilePostArray($files);
                if (1 < count($files['error'])) {
                    throw new Exception("Multiple files uploaded to a single REST resource '$name'.");
                }
                $name = $files['name'][0];
                $fullPathName = $path;
                $path = substr($path, 0, strripos($path, '/') + 1);
                $error = $files['error'][0];
                if (UPLOAD_ERR_OK == $error) {
                    $tmpName = $files["tmp_name"][0];
                    $contentType = $files['type'][0];
                    try {
                        $result = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update file $fullPathName.\n{$ex->getMessage()}");
                    }
                }
                else {
                    throw new Exception("Failed to upload file $name.\n$error");
                }
            }
            else {
                // possibly xml or json post either of file or folder to create, copy or move
                try {
                    $data = Utilities::getPostDataAsArray();
                    error_log(print_r($data, true));
                    //$this->addFiles($path, $data['files']);
                    $result = array();
                }
                catch (Exception $ex) {

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
        $this->checkPermission('update');
        $path = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path)) ? explode('/', $path) : array();
        $result = array();
        // possible file handling parameters
        $extract = Utilities::boolval(Utilities::getArrayValue('extract', $_REQUEST, false));
        $clean = Utilities::boolval(Utilities::getArrayValue('clean', $_REQUEST, false));
        $checkExist = false;
        if (empty($path) || empty($path_array[count($path_array) - 1])) {
            // if ending in '/' then create files or folders in the directory
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !empty($_SERVER['HTTP_X_FILE_NAME'])) {
                // html5 single posting for file create
                $name = $_SERVER['HTTP_X_FILE_NAME'];
                $fullPathName = $path . $name;
                try {
                    $content = Utilities::getPostData();
                    if (empty($content)) {
                        // empty post?
                        error_log("Empty content in update file $fullPathName.");
                    }
                    $contentType = Utilities::getArrayValue('CONTENT_TYPE', $_SERVER, '');
                    $result = $this->handleFileContent($path, $name, $content, $contentType, $extract, $clean, $checkExist);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update file $fullPathName.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_SERVER['HTTP_X_FOLDER_NAME']) && !empty($_SERVER['HTTP_X_FOLDER_NAME'])) {
                // html5 single posting for folder create
                $name = $_SERVER['HTTP_X_FOLDER_NAME'];
                $fullPathName = $path . $name;
                try {
                    $content = Utilities::getPostDataAsArray();
                    $this->fileRestHandler->createFolder($fullPathName, true, $content, true);
                    $result = array('folder' => array(array('name' => $name, 'path' => $fullPathName)));
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update folder $fullPathName.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multi-part/form-data post, single or multiple files
                $files = $_FILES['files'];
                if (!is_array($files['error'])) {
                    // single file
                    $name = $files['name'];
                    $fullPathName = $path . $name;
                    $error = $files['error'];
                    if ($error == UPLOAD_ERR_OK) {
                        $tmpName = $files['tmp_name'];
                        $contentType = $files['type'];
                        $result = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                    }
                    else {
                        $result = array('code' => 500,
                                        'message' => "Failed to update file $fullPathName.\n$error");
                    }
                }
                else {
                    $out = array();
                    //$files = Utilities::reorgFilePostArray($files);
                    foreach ($files['error'] as $key => $error) {
                        $name = $files['name'][$key];
                        $fullPathName = $path . $name;
                        if ($error == UPLOAD_ERR_OK) {
                            $tmpName = $files['tmp_name'][$key];
                            $contentType = $files['type'][$key];
                            $tmp = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                            $out[$key] = (isset($tmp['file']) ? $tmp['file'] : array());
                        }
                        else {
                            $out[$key]['error'] = array('code' => 500,
                                                        'message' => "Failed to update file $fullPathName.\n$error");
                        }
                    }
                    $result = array('file' => $out);
                }
            }
            else {
                // possibly xml or json post either of files or folders to create, copy or move
                $fileUrl = Utilities::getArrayValue('url', $_REQUEST, '');
                if (!empty($fileUrl)) {
                    // upload a file from a url, could be expandable zip
                    $tmpName = FileUtilities::importUrlFileToTemp($fileUrl);
                    try {
                        $result = $this->handleFile($path, '', $tmpName, '', $extract, $clean, $checkExist);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update folders or files from request.\n{$ex->getMessage()}");
                    }
                }
                else {
                    try {
                        $data = Utilities::getPostDataAsArray();
                        if (empty($data)) {
                            // create folder from resource path
                            $this->fileRestHandler->createFolder($path);
                            $result = array('folder' => array(array('path' => $path)));
                        }
                        else {
                            $out = array('folder' => array(), 'file' => array());
                            $folders = Utilities::getArrayValue('folder', $data, null);
                            if (empty($folders)) {
                                $folders = (isset($data['folders']['folder']) ? $data['folders']['folder'] : null);
                            }
                            if (!empty($folders)) {
                                if (!isset($folders[0])) {
                                    // single folder, make into array
                                    $folders = array($folders);
                                }
                                foreach ($folders as $key=>$folder) {
                                    $name = Utilities::getArrayValue('name', $folder, '');
                                    if (isset($folder['source_path'])) {
                                        // copy or move
                                        $srcPath = $folder['source_path'];
                                        if (empty($name)) {
                                            $name = FileUtilities::getNameFromPath($srcPath);
                                        }
                                        $fullPathName = $path . $name . '/';
                                        $out['folder'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->copyFolder($fullPathName, $srcPath, true);
                                            $deleteSource = Utilities::boolval(Utilities::getArrayValue('delete_source', $folder, false));
                                            if ($deleteSource) {
                                                $this->fileRestHandler->deleteFolder($srcPath, true);
                                            }
                                        }
                                        catch (Exception $ex) {
                                            $out['folder'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                    else {
                                        $fullPathName = $path . $name;
                                        $content = Utilities::getArrayValue('content', $folder, '');
                                        $isBase64 = Utilities::boolval(Utilities::getArrayValue('is_base64', $folder, false));
                                        if ($isBase64) {
                                            $content = base64_decode($content);
                                        }
                                        $out['folder'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->createFolder($fullPathName, true, $content);
                                        }
                                        catch (Exception $ex) {
                                            $out['folder'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                }
                            }
                            $files = Utilities::getArrayValue('file', $data, null);
                            if (empty($files)) {
                                $files = (isset($data['files']['file']) ? $data['files']['file'] : null);
                            }
                            if (!empty($files)) {
                                if (!isset($files[0])) {
                                    // single file, make into array
                                    $files = array($files);
                                }
                                foreach ($files as $key=>$file) {
                                    $name = Utilities::getArrayValue('name', $file, '');
                                    if (isset($file['source_path'])) {
                                        // copy or move
                                        $srcPath = $file['source_path'];
                                        if (empty($name)) {
                                            $name = FileUtilities::getNameFromPath($srcPath);
                                        }
                                        $fullPathName = $path . $name;
                                        $out['file'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        try {
                                            $this->fileRestHandler->copyFile($fullPathName, $srcPath, true);
                                            $deleteSource = Utilities::boolval(Utilities::getArrayValue('delete_source', $file, false));
                                            if ($deleteSource) {
                                                $this->fileRestHandler->deleteFile($srcPath);
                                            }
                                        }
                                        catch (Exception $ex) {
                                            $out['file'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                    elseif (isset($file['content'])) {
                                        $fullPathName = $path . $name;
                                        $out['file'][$key] = array('name' => $name, 'path' => $fullPathName);
                                        $content = Utilities::getArrayValue('content', $file, '');
                                        $isBase64 = Utilities::boolval(Utilities::getArrayValue('is_base64', $file, false));
                                        if ($isBase64) {
                                            $content = base64_decode($content);
                                        }
                                        try {
                                            $this->fileRestHandler->writeFile($fullPathName, $content);
                                        }
                                        catch (Exception $ex) {
                                            $out['file'][$key]['error'] = array('message' => $ex->getMessage());
                                        }
                                    }
                                }
                            }
                            $result = $out;
                        }
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update folders or files from request.\n{$ex->getMessage()}");
                    }
                }
            }
        }
        else {
            // if ending in file name, create the file or folder
            $name = substr($path, strripos($path, '/') + 1);
            if (isset($_SERVER['HTTP_X_FILE_NAME']) && !empty($_SERVER['HTTP_X_FILE_NAME'])) {
                $x_file_name = $_SERVER['HTTP_X_FILE_NAME'];
                if (0 !== strcasecmp($name, $x_file_name)) {
                    throw new Exception("Header file name '$x_file_name' mismatched with REST resource '$name'.");
                }
                try {
                    $content = Utilities::getPostData();
                    if (empty($content)) {
                        // empty post?
                        error_log("Empty content in write file $path to storage.");
                    }
                    $contentType = Utilities::getArrayValue('CONTENT_TYPE', $_SERVER, '');
                    $path = substr($path, 0, strripos($path, '/') + 1);
                    $result = $this->handleFileContent($path, $name, $content, $contentType, $extract, $clean, $checkExist);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update file $path.\n{$ex->getMessage()}");
                }
            }
            elseif (isset($_SERVER['HTTP_X_FOLDER_NAME']) && !empty($_SERVER['HTTP_X_FOLDER_NAME'])) {
                $x_folder_name = $_SERVER['HTTP_X_FOLDER_NAME'];
                if (0 !== strcasecmp($name, $x_folder_name)) {
                    throw new Exception("Header folder name '$x_folder_name' mismatched with REST resource '$name'.");
                }
                try {
                    $content = Utilities::getPostDataAsArray();
                    $this->fileRestHandler->updateFolderProperties($path, $content);
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to update folder $path.\n{$ex->getMessage()}");
                }
                $result = array('folder' => array(array('name' => $name, 'path' => $path)));
            }
            elseif (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multipart/form-data post, should be single file
                $files = $_FILES['files'];
                //$files = Utilities::reorgFilePostArray($files);
                if (1 < count($files['error'])) {
                    throw new Exception("Multiple files uploaded to a single REST resource '$name'.");
                }
                $name = $files['name'][0];
                $fullPathName = $path;
                $path = substr($path, 0, strripos($path, '/') + 1);
                $error = $files['error'][0];
                if (UPLOAD_ERR_OK == $error) {
                    $tmpName = $files["tmp_name"][0];
                    $contentType = $files['type'][0];
                    try {
                        $result = $this->handleFile($path, $name, $tmpName, $contentType, $extract, $clean, $checkExist);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to update file $fullPathName.\n{$ex->getMessage()}");
                    }
                }
                else {
                    throw new Exception("Failed to upload file $name.\n$error");
                }
            }
            else {
                // possibly xml or json post either of file or folder to create, copy or move
                try {
                    $data = Utilities::getPostDataAsArray();
                    error_log(print_r($data, true));
                    //$this->addFiles($path, $data['files']);
                    $result = array();
                }
                catch (Exception $ex) {

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
        $this->checkPermission('delete');
        $path = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path)) ? explode('/', $path) : array();
        if (empty($path) || empty($path_array[count($path_array) - 1])) {
            // delete directory of files and the directory itself
            $force = Utilities::boolval(Utilities::getArrayValue('force', $_REQUEST, false));
            // multi-file or folder delete via post data
            try {
                $content = Utilities::getPostDataAsArray();
            }
            catch (Exception $ex) {
                throw new Exception("Failed to delete storage folders.\n{$ex->getMessage()}");
            }
            if (empty($content)) {
                if (empty($path)) {
                    throw new Exception("Empty file or folder path given for storage delete.");
                }
                try {
                    $this->fileRestHandler->deleteFolder($path, $force);
                    $result = array('folder' => array(array('path' => $path)));
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to delete storage folder '$path'.\n{$ex->getMessage()}");
                }
            }
            else {
                try {
                    $out = array();
                    if (isset($content['file'])) {
                        $files = $content['file'];
                        $out['file'] = $this->fileRestHandler->deleteFiles($files, $path);
                    }
                    if (isset($content['folder'])) {
                        $folders = $content['folder'];
                        $out['folder'] = $this->fileRestHandler->deleteFolders($folders, $path, $force);
                    }
                    $result = $out;
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to delete storage folders.\n{$ex->getMessage()}");
                }
            }
        }
        else {
            // delete file from permanent storage
            try {
                $this->fileRestHandler->deleteFile($path);
                $result = array('file' => array(array('path' => $path)));
            }
            catch (Exception $ex) {
                throw new Exception("Failed to delete storage file '$path'.\n{$ex->getMessage()}");
            }
        }

        return $result;
    }
}
