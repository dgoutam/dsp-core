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
     * @throws \Exception
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
     * @throws \Exception
     */
    public function appExists($app_root)
    {
        // applications are defined as a top level folder in the storage
        try {
            return $this->fileRestHandler->folderExists($app_root);
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $app_root
     * @throws \Exception
     */
    public function createApp($app_root)
    {
        try {
            // create in permanent storage
            // place holder file for app check (faster than listing any blobs with that prefix)
            // also may include app folder permission properties
            $this->fileRestHandler->createFolder($app_root, true, array(), false);
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $app_root
     * @throws \Exception
     */
    public function deleteApp($app_root)
    {
        try {
            // check if an application (folder) exists with that name
            if ($this->appExists($app_root)) {
                $this->fileRestHandler->deleteFolder($app_root, true);
            }
        }
        catch (\Exception $ex) {
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
        try {
            $zip = new \ZipArchive();
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $zipFileName = $tempDir . $app_root . '.zip';
            if (true !== $zip->open($zipFileName, \ZipArchive::CREATE)) {
                throw new \Exception("Can not create zip file for this application.");
            }

            $sys = ServiceHandler::getInstance()->getServiceObject('system');
            $fields = 'name,label,description,is_active,url,is_url_external,schemas';
            $fields .= ',filter_by_device,filter_phone,filter_tablet,filter_desktop,requires_plugin';
            $records = $sys->retrieveRecordsByFilter('app', $fields, "name = '$app_root'", 1);
            if ((0 === count($records)) || !isset($records['record'][0]['fields'])) {
                throw new \Exception("No database entry exists for this application '$app_root''");
            }
            $record = $records['record'][0]['fields'];
            // add database entry file
            if (!$zip->addFromString('description.json', json_encode($record))) {
                throw new \Exception("Can not include description file in package file.");
            }
            $tables = Utilities::getArrayValue('schemas', $record, '');
            if (!empty($tables) && $include_schema) {
                // add related/required database table schemas
                // todo assuming which service this came from
                $db = ServiceHandler::getInstance()->getServiceObject('db');
                $schema = $db->describeTables($tables);
                if (!$zip->addFromString('schema.json', json_encode($schema))) {
                    throw new \Exception("Can not include database schema files in package file.");
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
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $pkg_file
     * @throws \Exception
     */
    public function importAppFromPackage($pkg_file)
    {
        $zip = new \ZipArchive();
        if (true === $zip->open($pkg_file)) {
            $data = $zip->getFromName('description.json');
            if (false === $data) {
                throw new \Exception('No application description file in this package file.');
            }
            $data = Utilities::jsonToArray($data);
            $records = array(array('fields' => $data)); // todo bad assumption of right format
            $sys = ServiceHandler::getInstance()->getServiceObject('system');
            $result = $sys->createRecords('app', $records, false, 'Id');
            if (isset($result['record'][0]['fault'])) {
                $msg = $result['record'][0]['fault']['faultString'];
                throw new \Exception("Could not create the database entry for this application.\n$msg");
            }
            $id = $result['record'][0]['fields']['id'];
            $zip->deleteName('description.json');
            try {
                $data = $zip->getFromName('schema.json');
                if (false !== $data) {
                    $data = Utilities::jsonToArray($data);
                    // todo how to determine which service to send this?
                    $tables = Utilities::getArrayValue('table', $data, array());
                    $db = ServiceHandler::getInstance()->getServiceObject('db');
                    $result = $db->createTables($tables);
                    if (isset($result['table'][0]['fault'])) {
                        $msg = $result['table'][0]['fault']['faultString'];
                        throw new \Exception("Could not create the database tables for this application.\n$msg");
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
                            if (isset($result['record'][0]['fault'])) {
                                $msg = $result['record'][0]['fault']['faultString'];
                                throw new \Exception("Could not insert the database entries for table '$tableName'' for this application.\n$msg");
                            }
                        }
                        $zip->deleteName('data.json');
                    }
                }
                catch (\Exception $ex) {
                    // delete db record
                    // todo anyone else using schema created?
                    $sys->deleteRecordsByIds('app', $id);
                    throw $ex;
                }
            }
            catch (\Exception $ex) {
                // delete db record
                // todo anyone else using schema created?
                $sys->deleteRecordsByIds('app', $id);
                throw $ex;
            }
            // expand the rest of the zip file into storage
            $this->fileRestHandler->expandZipFile('', $zip);
        }
        else {
            throw new \Exception('Error opening zip file.');
        }
    }

    // Controller based methods

    /**
     * @return array
     */
    public function actionGet()
    {
        $this->checkPermission('read');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        if (empty($path_array) || ((1 === count($path_array)) && empty($path_array[0]))) {
            $resources = array();
            return array('resource' => $resources);
            // currently don't allow 'all' access to root directory for get
            //throw new \Exception("Application root directory is not available for GET requests.");
        }
        $asPkg = Utilities::boolval(Utilities::getArrayValue('export', $_REQUEST, false));
        if (((1 === count($path_array)) && !empty($path_array[0])) || (2 === count($path_array))) {
            if ($asPkg) {
                $this->exportAppAsPackage($path_array[0]);
                Yii::app()->end();
            }
        }
        return parent::actionGet();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function actionPost()
    {
        $this->checkPermission('create');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        if (empty($path_array) || ((1 === count($path_array)) && empty($path_array[0]))) {
            // for application management at root directory,
            // you can import application package files, but not post other files
            $asPkg = Utilities::boolval(Utilities::getArrayValue('import', $_REQUEST, false));
            if ($asPkg) {
                if (isset($_FILES['files']) && !empty($_FILES['files'])) {
                    // older html multi-part/form-data post, single or multiple files
                    $files = $_FILES['files'];
                    if (!is_array($files['error'])) {
                        // single file
                        $name = $files['name'];
                        $error = $files['error'];
                        if ($error == UPLOAD_ERR_OK) {
                            $tmpName = $files['tmp_name'];
                            $contentType = $files['type'];
                            if (Utilities::isZipContent($contentType)) {
                                try {
                                    // need to expand zip file and move contents to storage
                                    $this->importAppFromPackage($tmpName);
                                    return array('file' => array(array('name' => $name, 'path' => $name)));
                                }
                                catch (\Exception $ex) {
                                    throw new \Exception("Failed to import application package $name.\n{$ex->getMessage()}");
                                }
                            }
                            else {
                                throw new \Exception("Only application package files are allowed for import.");
                            }
                        }
                        else {
                            throw new \Exception("Failed to import application package $name.\n$error");
                        }
                    }
                    else {
                        throw new \Exception("Only a single application package file is allowed for import.");
                    }
                }
            }
            else {
                throw new \Exception("Application root directory is not available for file creation.");
            }
        }
        return parent::actionPost();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function actionPut()
    {
        $this->checkPermission('update');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        if (empty($path_array) || ((1 === count($path_array)) && empty($path_array[0]))) {
            // for application management at root directory,
            // you can import application package files, but not post other files
            throw new \Exception("Application root directory is not available for file updates.");
        }
        return parent::actionPut();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function actionMerge()
    {
        $this->checkPermission('update');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        if (empty($path_array) || ((1 === count($path_array)) && empty($path_array[0]))) {
            // for application management at root directory,
            // you can import application package files, but not post other files
            throw new \Exception("Application root directory is not available for file updates.");
        }
        return parent::actionMerge();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function actionDelete()
    {
        $this->checkPermission('delete');
        $path_array = Utilities::getArrayValue('resource', $_GET, '');
        $path_array = (!empty($path_array)) ? explode('/', $path_array) : array();
        if (empty($path_array) || ((1 === count($path_array)) && empty($path_array[0]))) {
            // for application management at root directory,
            // you can delete everything
            throw new \Exception("Application root directory is not available for file deletes.");
        }
        return parent::actionDelete();
    }

}
