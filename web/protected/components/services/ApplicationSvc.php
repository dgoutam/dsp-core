<?php

/**
 * @category   DreamFactory
 * @package    web-csp
 * @subpackage ApplicationSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class ApplicationSvc extends CommonFileSvc
{
    /**
     * @param array $config
     * @throws Exception
     */
    public function __construct($config)
    {
        // Validate storage setup
        $store_name = Utilities::getArrayValue('storage_name', $config, '');
        if (empty($store_name)) {
            $config['storage_name'] = Defaults::APPS_STORAGE_NAME;
        }
        parent::__construct($config);
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
//        unset($this->localPath);
        parent::__destruct();
    }

    /**
     * @param $app_root
     * @return bool
     * @throws Exception
     */
    public function appExists($app_root)
    {
        // applications are defined as a top level folder in the storage
        try {
            return $this->fileRestHandler->folderExists($app_root);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $app_root
     * @throws Exception
     */
    public function createApp($app_root)
    {
        try {
            // create in permanent storage
            // place holder file for app check (faster than listing any blobs with that prefix)
            // also may include app folder permission properties
            $this->fileRestHandler->createFolder($app_root, true, array(), false);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $app_root
     * @throws Exception
     */
    public function deleteApp($app_root)
    {
        try {
            // check if an application (folder) exists with that name
            if ($this->appExists($app_root)) {
                $this->fileRestHandler->deleteFolder($app_root, true);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $app_root
     * @param bool $include_files
     * @param bool $include_schema
     * @param bool $include_data
     * @throws Exception
     * @return void
     */
    public function exportAppAsPackage($app_root, $include_files=true, $include_schema=true, $include_data=false)
    {
        if (empty($app_root)) {
            throw new Exception("Application root name can not be empty.");
        }
        if (!$this->appExists($app_root)) {
            throw new Exception("Application '$app_root' does not exist in the system.");
        }

        try {
            $zip = new ZipArchive();
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $zipFileName = $tempDir . $app_root . '.dfpkg';
            if (true !== $zip->open($zipFileName, \ZipArchive::CREATE)) {
                throw new Exception("Can not create package file for this application.");
            }

            $fields = array('name','label','description','is_active','url','is_url_external','schemas',
                            'filter_by_device','filter_phone','filter_tablet','filter_desktop','requires_plugin');
            $app = App::model()->find('name = :name', array(':name' => $app_root));
            if (null === $app) {
                throw new Exception("No database entry exists for this application '$app_root'.");
            }
            $record = $app->getAttributes($fields);
            // add database entry file
            if (!$zip->addFromString('description.json', json_encode($record))) {
                throw new Exception("Can not include description file in package file.");
            }
            $tables = Utilities::getArrayValue('schemas', $record, '');
            if (!empty($tables) && $include_schema) {
                // add related/required database table schemas
                // todo assuming which service this came from
                $db = ServiceHandler::getInstance()->getServiceObject('schema');
                $schema = $db->describeTables($tables);
                if (!$zip->addFromString('schema.json', json_encode($schema))) {
                    throw new Exception("Can not include database schema files in package file.");
                }
            }
            $isExternal = Utilities::boolval(Utilities::getArrayValue('is_url_external', $record, false));
            if (!$isExternal && $include_files) {
                // add files
                $this->fileRestHandler->getFolderAsZip($app_root, $zip, $zipFileName, true);
            }
            $zip->close();

            $fd = fopen($zipFileName, "r");
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
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $app_root
     * @throws Exception
     * @return void
     */
    public function exportAppContentAsZip($app_root)
    {
        if (empty($app_root)) {
            throw new Exception("Application root name can not be empty.");
        }
        if (!$this->appExists($app_root)) {
            throw new Exception("Application '$app_root' does not exist in the system.");
        }

        try {
            $zip = new ZipArchive();
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $zipFileName = $tempDir . $app_root . '.zip';
            if (true !== $zip->open($zipFileName, \ZipArchive::CREATE)) {
                throw new Exception("Can not create zip file for this application.");
            }

            $this->fileRestHandler->getFolderAsZip($app_root, $zip, $zipFileName, true);
            $zip->close();

            $fd = fopen($zipFileName, "r");
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
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $pkg_file
     * @return array
     * @throws Exception
     */
    public function importAppFromPackage($pkg_file)
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($pkg_file)) {
            throw new Exception('Error opening zip file.');
        }
        $data = $zip->getFromName('description.json');
        if (false === $data) {
            throw new Exception('No application description file in this package file.');
        }
        $record = Utilities::jsonToArray($data);
        $sys = SystemManager::getInstance();
        try {
            $result = $sys->createRecord('app', $record, 'id,name');
        }
        catch (Exception $ex) {
            throw new Exception("Could not create the application.\n{$ex->getMessage()}");
        }
        $id = (isset($result['id'])) ? $result['id'] : '';
        $name = (isset($result['name'])) ? $result['name'] : '';
        $zip->deleteName('description.json');
        try {
            $data = $zip->getFromName('schema.json');
            if (false !== $data) {
                $data = Utilities::jsonToArray($data);
                // todo how to determine which service to send this?
                $tables = Utilities::getArrayValue('table', $data, array());
                $db = ServiceHandler::getInstance()->getServiceObject('schema');
                $result = $db->createTables($tables);
                if (isset($result['table'][0]['error'])) {
                    $msg = $result['table'][0]['error']['message'];
                    throw new Exception("Could not create the database tables for this application.\n$msg");
                }
                $zip->deleteName('schema.json');
            }
            try {
                $data = $zip->getFromName('data.json');
                if (false !== $data) {
                    $data = Utilities::jsonToArray($data);
                    // todo how to determine which service to send this?
                    $db = ServiceHandler::getInstance()->getServiceObject('db');
                    $tables = Utilities::getArrayValue('table', $data, array());
                    foreach ($tables as $table) {
                        $tableName = Utilities::getArrayValue('name', $table, '');
                        $records = Utilities::getArrayValue('record', $table, '');
                        $result = $db->createRecords($tableName, $records);
                        if (isset($result['record'][0]['error'])) {
                            $msg = $result['record'][0]['error']['message'];
                            throw new Exception("Could not insert the database entries for table '$tableName'' for this application.\n$msg");
                        }
                    }
                    $zip->deleteName('data.json');
                }
            }
            catch (Exception $ex) {
                // delete db record
                // todo anyone else using schema created?
                $sys->deleteRecordById('app', $id);
                throw $ex;
            }
        }
        catch (Exception $ex) {
            // delete db record
            // todo anyone else using schema created?
            $sys->deleteRecordById('app', $id);
            throw $ex;
        }
        // extract the rest of the zip file into storage
        $result = $this->fileRestHandler->extractZipFile('', $zip);
        return array('folder'=>array('name'=>$name, 'path'=>$name.'/'));
    }

    /**
     * @param $name
     * @param $zip_file
     * @return array
     * @throws Exception
     */
    public function importAppFromZip($name, $zip_file)
    {
        $record = array('name'=>$name, 'label'=>$name, 'is_url_external'=>0, 'url'=>'/index.html');
        $sys = SystemManager::getInstance();
        try {
            $result = $sys->createRecord('app', $record);
        }
        catch (Exception $ex) {
            throw new Exception("Could not create the database entry for this application.\n{$ex->getMessage()}");
        }
        $id = (isset($result['id'])) ? $result['id'] : '';

        $zip = new ZipArchive();
        if (true === $zip->open($zip_file)) {
            // extract the rest of the zip file into storage
            $dropPath = $zip->getNameIndex(0);
            $dropPath = substr($dropPath, 0, strpos($dropPath, '/')) . '/';
            return $this->fileRestHandler->extractZipFile($name . DIRECTORY_SEPARATOR, $zip, false, $dropPath);
        }
        else {
            throw new Exception('Error opening zip file.');
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
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        $app_root = (isset($path_array[0]) ? $path_array[0] : '');
        if (empty($app_root)) {
            // list app folders only for now
            return $this->fileRestHandler->getFolderContent('', false, true, false);
        }
        $more = (isset($path_array[1]) ? $path_array[1] : '');
        if (empty($more)) {
            // dealing only with application root here
            $asPkg = Utilities::boolval(Utilities::getArrayValue('pkg', $_REQUEST, false));
            if ($asPkg) {
                $includeFiles = Utilities::boolval(Utilities::getArrayValue('include_files', $_REQUEST, true));
                $includeSchema = Utilities::boolval(Utilities::getArrayValue('include_schema', $_REQUEST, true));
                $includeData = ($includeSchema) ? Utilities::boolval(Utilities::getArrayValue('include_data', $_REQUEST, false)) : false;
                $this->exportAppAsPackage($app_root, $includeFiles, $includeSchema, $includeData);
                Yii::app()->end();
            }
        }
        return parent::actionGet();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionPost()
    {
        $this->checkPermission('create');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        $app_root = (isset($path_array[0]) ? $path_array[0] : '');
        if (empty($app_root)) {
            // for application management at root directory,
            // you can import an application package file, local or remote, or from zip, but nothing else
            $fileUrl = Utilities::getArrayValue('url', $_REQUEST, '');
            if (0 === strcasecmp('dfpkg', FileUtilities::getFileExtension($fileUrl))) {
                // need to download and extract zip file and move contents to storage
                $filename = FileUtilities::importUrlFileToTemp($fileUrl);
                try {
                    return $this->importAppFromPackage($filename);
                    // todo save url for later updates
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to import application package $fileUrl.\n{$ex->getMessage()}");
                }
            }
            $name = Utilities::getArrayValue('name', $_REQUEST, '');
            // from repo or remote zip file
            if (!empty($name) && (0 === strcasecmp('zip', FileUtilities::getFileExtension($fileUrl)))) {
                // need to download and extract zip file and move contents to storage
                $filename = FileUtilities::importUrlFileToTemp($fileUrl);
                try {
                    return $this->importAppFromZip($name, $filename);
                    // todo save url for later updates
                }
                catch (Exception $ex) {
                    throw new Exception("Failed to import application package $fileUrl.\n{$ex->getMessage()}");
                }
            }
            if (isset($_FILES['files']) && !empty($_FILES['files'])) {
                // older html multi-part/form-data post, single or multiple files
                $files = $_FILES['files'];
                if (is_array($files['error'])) {
                    throw new Exception("Only a single application package file is allowed for import.");
                }
                $filename = $files['name'];
                $error = $files['error'];
                if ($error !== UPLOAD_ERR_OK) {
                    throw new Exception("Failed to import application package $filename.\n$error");
                }
                $tmpName = $files['tmp_name'];
                $contentType = $files['type'];
                if (0 === strcasecmp('dfpkg', FileUtilities::getFileExtension($filename))) {
                    try {
                        // need to extract zip file and move contents to storage
                        return $this->importAppFromPackage($tmpName);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to import application package $filename.\n{$ex->getMessage()}");
                    }
                }
                if (!empty($name) && !FileUtilities::isZipContent($contentType)) {
                    try {
                        // need to extract zip file and move contents to storage
                        return $this->importAppFromZip($name, $tmpName);
                    }
                    catch (Exception $ex) {
                        throw new Exception("Failed to import application package $filename.\n{$ex->getMessage()}");
                    }
                }
            }

            throw new Exception("Application service root directory is not available for file creation.");
        }
        $more = (isset($path_array[1]) ? $path_array[1] : '');
        if (empty($more)) {
            // dealing only with application root here
        }
        return parent::actionPost();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionPut()
    {
        $this->checkPermission('update');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        if (empty($path_array) || ((1 === count($path_array)) && empty($path_array[0]))) {
            // for application management at root directory,
            // you can import application package files, but not post other files
            throw new Exception("Application service root directory is not currently available for file updates.");
        }
        $more = (isset($path_array[1]) ? $path_array[1] : '');
        if (empty($more)) {
            // dealing only with application root here
        }
        return parent::actionPut();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionMerge()
    {
        $this->checkPermission('update');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        $app_root = (isset($path_array[0]) ? $path_array[0] : '');
        if (empty($app_root)) {
            // for application management at root directory,
            // you can import application package files, but not post other files
            throw new Exception("Application service root directory is not currently available for file updates.");
        }
        $more = (isset($path_array[1]) ? $path_array[1] : '');
        if (empty($more)) {
            // dealing only with application root here
        }
        return parent::actionMerge();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionDelete()
    {
        $this->checkPermission('delete');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        $app_root = (isset($path_array[0]) ? $path_array[0] : '');
        if (empty($app_root)) {
            // for application management at root directory,
            // you can not delete everything
            throw new Exception("Application service root directory is not available for file deletes.");
        }
        $more = (isset($path_array[1]) ? $path_array[1] : '');
        if (empty($more)) {
            // dealing only with application root here
            $content = Utilities::getPostDataAsArray();
            if (empty($content)) {
                throw new Exception("Application root directory is not available for delete. Use the system API to delete the app.");
            }
        }
        return parent::actionDelete();
    }

}
