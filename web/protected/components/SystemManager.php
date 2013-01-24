<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage SystemManager
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class SystemManager implements iRestHandler
{

    // constants
    /**
     *
     */
    const SYSTEM_TABLES = 'app,app_group,role,user,service';
    /**
     *
     */
    const INTERNAL_TABLES = 'config,label,role_service_access,session';

    // Members

    /**
     * @var ServiceHandler
     */
    private static $_instance = null;

    /**
     * @var
     */
    protected $modelName;

    /**
     * @var
     */
    protected $modelId;

    /**
     * @var PdoSqlDbSvc
     */
    protected $nativeDb;

    /**
     * Creates a new SystemManager instance
     *
     */
    public function __construct()
    {
        $this->nativeDb = new PdoSqlDbSvc();
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
    }

    /**
     * Gets the static instance of this class.
     *
     * @return SystemManager
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new SystemManager();
        }

        return self::$_instance;
    }

    /**
     * Determines the current state of the system
     */
    public function getSystemState()
    {
        try {
            // refresh the schema that we just added
            Yii::app()->db->schema->refresh();
            $tables = Yii::app()->db->schema->getTableNames();
            if (empty($tables)) {
                return 'init required';
            }

            // check for any missing necessary tables
            $needed = explode(',', self::SYSTEM_TABLES . ',' . self::INTERNAL_TABLES);
            foreach ($needed as $name) {
                if (!in_array($name, $tables)) {
                    return 'schema required';
                }
            }

            // check for at least one system admin user
            $result = $this->nativeDb->retrieveSqlRecordsByFilter('user', 'username', "is_sys_admin = 1", 1);
            unset($result['total']);
            if (count($result) < 1) {
                return 'admin required';
            }

            // need to check for db upgrade, based on tables or version

            return 'ready';
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Configures the system.
     *
     * @param array $data
     * @return null
     */
    public function initSystem($data = array())
    {
        $this->initSchema();
        $this->initAdmin($data);
        $this->initData();
    }

    /**
     * Configures the system schema.
     *
     * @throws Exception
     * @return null
     */
    public function initSchema()
    {
        try {
            $contents = file_get_contents(Yii::app()->basePath . '/data/system_schema.json');
            if (empty($contents)) {
                throw new \Exception("Empty or no system schema file found.");
            }
            $contents = Utilities::jsonToArray($contents);
            $version = Utilities::getArrayValue('version', $contents);
            $config = array();
            if ($this->nativeDb->doesTableExist('config')) {
                $config = $this->nativeDb->retrieveSqlRecordsByFilter('config', 'id', '', 1);
                unset($config['total']);
                $oldVersion = Utilities::getArrayValue('db_version', $config, '');
            }
            // create system tables
            $tables = Utilities::getArrayValue('table', $contents);
            if (empty($tables)) {
                throw new \Exception("No default system schema found.");
            }
            $result = $this->nativeDb->createTables($tables, true, true);

            // setup session stored procedure
            $command = Yii::app()->db->createCommand();
//            $query = 'SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
//                      WHERE ROUTINE_TYPE="PROCEDURE"
//                          AND ROUTINE_SCHEMA="dreamfactory"
//                          AND ROUTINE_NAME="UpdateOrInsertSession";';
//            $result = $db->singleSqlQuery($query);
//            if ((empty($result)) || !isset($result[0]['ROUTINE_NAME'])) {
            switch ($this->nativeDb->getDriverType()) {
                case Utilities::DRV_SQLSRV:
                    $contents = file_get_contents(Yii::app()->basePath . '/data/procedures.mssql.sql');
                    if ((false === $contents) || empty($contents)) {
                        throw new \Exception("Empty or no system db procedures file found.");
                    }
                    $query = "IF ( OBJECT_ID('dbo.UpdateOrInsertSession') IS NOT NULL )
                                  DROP PROCEDURE dbo.UpdateOrInsertSession";
                    $command->setText($query);
                    $command->execute();
                    $command->reset();
                    $command->setText($contents);
                    $command->execute();
                    break;
                case Utilities::DRV_MYSQL:
                    $contents = file_get_contents(Yii::app()->basePath . '/data/procedures.mysql.sql');
                    if ((false === $contents) || empty($contents)) {
                        throw new \Exception("Empty or no system db procedures file found.");
                    }
                    $query = 'DROP PROCEDURE IF EXISTS `UpdateOrInsertSession`';
                    $command->setText($query);
                    $command->execute();
                    $command->reset();
                    $command->setText($contents);
                    $command->execute();
                    break;
                default:
                    break;
            }
//            }
            // initialize config table if not already
            if (empty($config)) {
                $this->nativeDb->createSqlRecord('config', array('db_version'=>$version));
            }
            // refresh the schema that we just added
            Yii::app()->db->schema->refresh();
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Configures the system.
     *
     * @param array $data
     * @throws Exception
     * @return null
     */
    public function initAdmin($data = array())
    {
        try {
            // create and login first admin user
            // fill out the user fields for creation
            $username = Utilities::getArrayValue('username', $data);
            $firstName = Utilities::getArrayValue('firstName', $data);
            $lastName = Utilities::getArrayValue('lastName', $data);
            $pwd = Utilities::getArrayValue('password', $data, '');
            $fields = array('username' => $username,
                'email' => Utilities::getArrayValue('email', $data),
                'password' => CPasswordHelper::hashPassword($pwd),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'display_name' => $firstName . ' ' . $lastName,
                'is_active' => true,
                'is_sys_admin' => true,
                'confirm_code' => 'y'
            );
            $result = $this->nativeDb->retrieveSqlRecordsByFilter('user', 'id', "username = '$username'", 1);
            unset($result['total']);
            if (count($result) > 0) {
                throw new \Exception("A user already exists with the username '$username'.");
            }
            $result = $this->nativeDb->createSqlRecord('user', $fields);
            if (!isset($result[0])) {
                error_log(print_r($result, true));
                throw new \Exception("Failed to create user.");
            }
            $userId = Utilities::getArrayValue('id', $result[0]);
            if (empty($userId)) {
                error_log(print_r($result[0], true));
                throw new \Exception("Failed to create user.");
            }
            Utilities::setCurrentUserId($userId);
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Configures the default system data.
     *
     * @throws Exception
     * @return boolean whether configuration is successful
     */
    public function initData()
    {
        try {
            // for now use the first admin we find
            $result = $this->nativeDb->retrieveSqlRecordsByFilter('user', 'id', "is_sys_admin = 1", 1);
            unset($result['total']);
            if (!isset($result[0])) {
                error_log(print_r($result, true));
                throw new \Exception("Failed to retrieve admin user.");
            }
            $userId = Utilities::getArrayValue('id', $result[0]);
            if (empty($userId)) {
                error_log(print_r($result[0], true));
                throw new \Exception("Failed to retrieve user id.");
            }
            Utilities::setCurrentUserId($userId);

            // init system tables with records
            $contents = file_get_contents(Yii::app()->basePath . '/data/system_data.json');
            if (empty($contents)) {
                throw new \Exception("Empty or no system data file found.");
            }
            $contents = Utilities::jsonToArray($contents);
            $result = $this->nativeDb->retrieveSqlRecordsByFilter('service', 'id', '', 1);
            unset($result['total']);
            if (empty($result)) {
                $services = Utilities::getArrayValue('service', $contents);
                if (empty($services)) {
                    error_log(print_r($contents, true));
                    throw new \Exception("No default system services found.");
                }
                $this->nativeDb->createSqlRecords('service', $services, true);
            }
            $result = $this->nativeDb->retrieveSqlRecordsByFilter('app', 'id', '', 1);
            unset($result['total']);
            if (empty($result)) {
                $apps = Utilities::getArrayValue('app', $contents);
                if (!empty($apps)) {
                    $this->nativeDb->createSqlRecords('app', $apps, true);
                }
            }
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    // Controller based methods

    /**
     * @return array
     * @throws Exception
     */
    public function actionGet()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            $extras = array();
            switch ($this->modelName) {
            case '':
                $result = array(array('name' => 'app', 'label' => 'Application'),
                    array('name' => 'app_group', 'label' => 'Application Group'),
                    array('name' => 'config', 'label' => 'Configuration'),
                    array('name' => 'role', 'label' => 'Role'),
                    array('name' => 'schema', 'label' => 'Schema'),
                    array('name' => 'service', 'label' => 'Service'),
                    array('name' => 'user', 'label' => 'User'));
                $result = array('resource' => $result);
                break;
            case 'schema':
                if (empty($this->modelId)) {
                    $result = $this->describeSystem();
                    $result = array('table' => $result);
                }
                else {
                    $result = $this->describeTable(strtolower($this->modelId));
                }
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                if (isset($_REQUEST['apps'])) {
                    $extras['apps'] = $_REQUEST['apps'];
                }
                if (isset($_REQUEST['users'])) {
                    $extras['users'] = $_REQUEST['users'];
                }
                if (isset($_REQUEST['roles'])) {
                    $extras['roles'] = $_REQUEST['roles'];
                }
                if (empty($this->modelId)) {
                    $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                    if (!empty($ids)) {
                        $result = $this->retrieveRecordsByIds($this->modelName, $ids, $fields, $extras);
                    }
                    else { // get by filter or all
                        if (!empty($data)) { // complex filters or large numbers of ids require post
                            $ids = Utilities::getArrayValue('ids', $data, '');
                            $records = Utilities::getArrayValue('record', $data, null);
                            if (empty($records)) {
                                // xml to array conversion leaves them in plural wrapper
                                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                            }
                            if (!empty($ids)) {
                                $result = $this->retrieveRecordsByIds($this->modelName, $ids, $fields, $extras);
                            }
                            elseif (!empty($records)) {
                                $result = $this->retrieveRecords($this->modelName, $records, $fields, $extras);
                            }
                            else { // if not specified use empty filter
                                $filter = Utilities::getArrayValue('filter', $data, '');
                                $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                                $order = Utilities::getArrayValue('order', $data, '');
                                $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                                $result = $this->retrieveRecordsByFilter($this->modelName, $fields, $filter, $limit, $order, $offset, $extras);
                            }
                        }
                        else {
                            $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : '';
                            $limit = (isset($_REQUEST['limit'])) ? intval($_REQUEST['limit']) : 0;
                            $order = (isset($_REQUEST['order'])) ? $_REQUEST['order'] : '';
                            $offset = (isset($_REQUEST['offset'])) ? intval($_REQUEST['offset']) : 0;
                            $result = $this->retrieveRecordsByFilter($this->modelName, $fields, $filter, $limit, $order, $offset, $extras);
                        }
                    }
                }
                else { // single entity by id
                    $result = $this->retrieveRecordsByIds($this->modelName, $this->modelId, $fields, $extras);
                }
                break;
            default:
                throw new Exception("GET received to an unsupported system resource named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionPost()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch ($this->modelName) {
            case '':
            case 'schema':
                throw new Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                $records = Utilities::getArrayValue('record', $data, null);
                if (empty($records)) {
                    // xml to array conversion leaves them in plural wrapper
                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                }
                if (empty($records)) {
                    throw new Exception('No records in POST create request.');
                }
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->createRecords($this->modelName, $records, $rollback, $fields);
                break;
            default:
                throw new Exception("POST received to an unsupported system resource named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionPut()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch ($this->modelName) {
            case '':
            case 'schema':
                throw new Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                $records = Utilities::getArrayValue('record', $data, null);
                if (empty($records)) {
                    // xml to array conversion leaves them in plural wrapper
                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                }
                if (empty($records)) {
                    throw new Exception('No records in POST update request.');
                }
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->updateRecords($this->modelName, $records, $rollback, $fields);
                break;
            default:
                throw new Exception("PUT received to an unsupported system resource named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionMerge()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch ($this->modelName) {
            case '':
            case 'schema':
                throw new Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                $records = Utilities::getArrayValue('record', $data, null);
                if (empty($records)) {
                    // xml to array conversion leaves them in plural wrapper
                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                }
                if (empty($records)) {
                    throw new Exception('No records in POST update request.');
                }
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->updateRecords($this->modelName, $records, $rollback, $fields);
                break;
            default:
                throw new Exception("MERGE/PATCH received to an unsupported system resource named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionDelete()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch ($this->modelName) {
            case 'schema':
                throw new Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                if (empty($this->modelId)) {
                    $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                    if (!empty($ids)) {
                        $result = $this->deleteRecordsByIds($this->modelName, $ids, $fields);
                    }
                    else {
                        if (!empty($data)) {
                            $ids = Utilities::getArrayValue('ids', $data, '');
                            $records = Utilities::getArrayValue('record', $data, null);
                            if (empty($records)) {
                                // xml to array conversion leaves them in plural wrapper
                                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                            }
                            if (!empty($ids)) {
                                $result = $this->deleteRecordsByIds($this->modelName, $ids, $fields);
                            }
                            elseif (!empty($records)) {
                                $result = $this->deleteRecords($this->modelName, $records, $fields);
                            }
                            else {
                                throw new Exception("Id list or record sets containing Id fields required to delete $this->modelName records.");
                            }
                        }
                        else {
                            throw new Exception("Id list or record sets containing Id fields required to delete $this->modelName records.");
                        }
                    }
                }
                else {
                    $result = $this->deleteRecordsByIds($this->modelName, $this->modelId, $fields);
                }
                break;
            default:
                throw new Exception("DELETE received to an unsupported system resource named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     *
     */
    protected function detectCommonParams()
    {
        $resource = Utilities::getArrayValue('resource', $_GET, '');
        $resource = (!empty($resource)) ? explode('/', $resource) : array();
        $this->modelName = strtolower((isset($resource[0])) ? $resource[0] : '');
        $this->modelId = (isset($resource[1])) ? $resource[1] : '';
    }

    //-------- System Records Operations ---------------------
    // records is an array of field arrays

    /**
     * @param $table
     * @param $record
     * @param string $fields
     * @return array
     * @throws Exception
     */
    protected function createRecordLow($table, $record, $fields = '')
    {
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('[InvalidParam]: There are no fields in the record set.');
        }
        try {
            // before record create - all
            $this->validateUniqueSystemName($table, $record['fields']);
            // specific
            switch (strtolower($table)) {
            case 'user':
                $this->validateUser($record['fields']);
                break;
            case 'app':
                // need name and isUrlExternal to create app directory in storage
                $fields = Utilities::addOnceToList($fields, 'name', ',');
                $fields = Utilities::addOnceToList($fields, 'is_url_external', ',');
                break;
            }

            // create DB record
            $fields = Utilities::addOnceToList($fields, 'id', ',');
            $results = $this->nativeDb->createSqlRecord($table, $record['fields'], $fields);
            if (!isset($results[0])) {
                error_log(print_r($results, true));
                throw new Exception("Failed to create user.");
            }
            $id = Utilities::getArrayValue('id', $results[0]);
            if (empty($id)) {
                error_log(print_r($results[0], true));
                throw new Exception("Failed to create user.");
            }

            // after record create
            switch (strtolower($table)) {
            case 'app':
                // need name and isUrlExternal to create app directory in storage
                if (isset($results[0]['is_url_external'])) {
                    $isExternal = Utilities::boolval($results[0]['is_url_external']);
                    if (!$isExternal) {
                        $appSvc = ServiceHandler::getInstance()->getServiceObject('app');
                        if ($appSvc) {
                            $appSvc->createApp($results[0]['name']);
                        }
                    }
                }
                break;
            case 'app_group':
                if (isset($record['fields']['app_ids'])) {
                    try {
                        $appIds = $record['fields']['app_ids'];
                        $this->assignAppGroups($id, $appIds);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            case 'role':
                if (isset($record['users'])) {
                    try {
                        $users = (isset($record['users']['assign'])) ? $record['users']['assign'] : '';
                        if (!empty($users)) {
                            $override = (isset($record['users']['override'])) ?
                                Utilities::boolval($record['users']['override']) : false;
                            $this->assignRole($id, $users, $override);
                        }
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                if (isset($record['fields']['services'])) {
                    try {
                        $services = $record['fields']['services'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }

            return array('fields' => (isset($results[0]) ? $results[0] : $results));
        }
        catch (Exception $ex) {
            // need to delete the above table entry and clean up
            if (isset($id) && !empty($id)) {
                $this->nativeDb->deleteSqlRecordsByIds($table, $id, 'id');
            }
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function createRecords($table, $records, $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('create', 'system', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (!isset($records[0])) { // isArrayNumeric($records)
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $out = array();
        foreach ($records as $record) {
            try {
                $out[] = $this->createRecordLow($table, $record, $fields);
            }
            catch (Exception $ex) {
                $out[] = array('error' => array('message' => $ex->getMessage(), 'code' => $ex->getCode()));
            }
        }

        return array('record' => $out);
    }

    /**
     * @param $table
     * @param $record
     * @param $fields
     * @return array
     * @throws Exception
     */
    protected function updateRecordLow($table, $record, $fields)
    {
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('[InvalidParam]: There are no fields in the record set.');
        }
        try {
            // before record update
            $this->validateUniqueSystemName($table, $record['fields'], true);
            // specific
            switch (strtolower($table)) {
            case 'user':
                $this->validateUser($record['fields'], true);
                break;
            case 'app':
                // need name and isUrlExternal to create app directory in storage
                $fields = Utilities::addOnceToList($fields, 'name', ',');
                break;
            }
            $id = Utilities::getArrayValue('id', $record['fields'], '');
            if (empty($id)) {
                throw new Exception("Identifying field 'id' can not be empty for update request.");
            }
            $record['fields'] = Utilities::removeOneFromArray('id', $record['fields']);

            $results = $this->nativeDb->updateSqlRecordsByIds($table, $record['fields'], $id, 'id', false, $fields);

            // after record update
            switch (strtolower($table)) {
            case 'app':
                // need name and isExternal to create app directory in storage
                $isUrlExternal = Utilities::getArrayValue('is_url_external', $record, null);
                if (isset($isUrlExternal)) {
                    $name = (isset($results[0]['name'])) ? $results[0]['name'] : '';
                    if (!empty($name)) {
                        if (!Utilities::boolval($isUrlExternal)) {
                            $appSvc = ServiceHandler::getInstance()->getServiceObject('app');
                            if ($appSvc) {
                                if (!$appSvc->appExists($name)) {
                                    $appSvc->createApp($name);
                                }
                            }
                        }
                    }
                }
                break;
            case 'app_group':
                if (isset($record['fields']['app_ids'])) {
                    try {
                        $appIds = $record['fields']['app_ids'];
                        $this->assignAppGroups($id, $appIds, true);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            case 'role':
                if (isset($record['users'])) {
                    try {
                        $users = (isset($record['users']['assign'])) ? $record['users']['assign'] : '';
                        if (!empty($users)) {
                            $override = (isset($record['users']['override'])) ?
                                Utilities::boolval($record['users']['override']) : false;
                            $this->assignRole($id, $users, $override);
                        }
                        $users = (isset($record['users']['unassign'])) ? $record['users']['unassign'] : '';
                        if (!empty($users)) {
                            $this->unassignRole($id, $users);
                        }
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                if (isset($record['fields']['services'])) {
                    try {
                        $services = $record['fields']['services'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }

            return array('fields' => (isset($results[0]) ? $results[0] : $results));
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecords($table, $records, $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('update', 'system', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        if (!isset($records[0])) {
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $out = array();
        foreach ($records as $record) {
            try {
                $out[] = $this->updateRecordLow($table, $record, $fields);
            }
            catch (Exception $ex) {
                $out[] = array('error' => array('message' => $ex->getMessage(), 'code' => $ex->getCode()));
            }
        }

        return array('record' => $out);
    }

    /**
     * @param $table
     * @param $record
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecordById($table, $record, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('update', 'system', $table);

        try {
            $result = $this->updateRecordLow($table, $record, $fields);

            return (isset($result[0]) ? $result[0] : $result);
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $id_list
     * @throws Exception
     */
    protected function preDeleteUsers($id_list)
    {
        try {
            $currUser = Utilities::getCurrentUserId();
            $ids = array_map('trim', explode(',', $id_list));
            foreach ($ids as $id) {
                if ($currUser === $id) {
                    throw new Exception("The current logged in user with Id '$id' can not be deleted.");
                }
            }
            // todo check and make sure this is not the last admin user
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $id_list
     * @throws Exception
     */
    protected function preDeleteRoles($id_list)
    {
        try {
            $currentRole = Utilities::getCurrentRoleId();
            $ids = array_map('trim', explode(',', $id_list));
            if (false !== array_search($currentRole, $ids)) {
                throw new Exception("Your current role with Id '$currentRole' can not be deleted.");
            }
            foreach ($ids as $id) {
                // clean up User.RoleIds pointing here
                $result = $this->nativeDb->retrieveSqlRecordsByFilter('user', 'id,role_id', "role_id = '$id'");
                $total = (isset($result['total'])) ? $result['total'] : '';
                unset($result['total']);
                if (!empty($result)) {
                    foreach ($result as $key => $userInfo) {
                        $result[$key]['role_id'] = '';
                    }
                    $this->nativeDb->updateSqlRecords('user', $result, 'id');
                }
                // Clean out the RoleServiceAccess for this role
                $this->nativeDb->deleteSqlRecordsByFilter('role_service_access', "role_id = '$id'");
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $id_list
     * @throws Exception
     */
    protected function preDeleteApps($id_list)
    {
        try {
            $currApp = Utilities::getCurrentAppName();
            $result = $this->nativeDb->retrieveSqlRecordsByIds('app', $id_list, 'id', 'id,name');
            foreach ($result as $appInfo) {
                if (!is_array($appInfo) || empty($appInfo)) {
                    throw new Exception("One of the application ids is invalid.");
                }
                $name = $appInfo['name'];
                if ($currApp === $name) {
                    throw new Exception("The currently running application '$name' can not be deleted.");
                }
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
        try {
            $store = ServiceHandler::getInstance()->getServiceObject('app');
            foreach ($result as $appInfo) {
                $id = $appInfo['id'];
                // delete roles - which need cleaning of users first
                $roles = $this->nativeDb->retrieveSqlRecordsByFilter('role', 'id', "app_ids='$id'");
                unset($roles['total']);
                $roleIdList = '';
                foreach ($roles as $role) {
                    $roleIdList .= (!empty($roleIdList)) ? ',' . $role['id'] : $role['id'];
                }
                if (!empty($roleIdList)) {
                    $this->deleteRecordsByIds('role', $roleIdList);
                }
                // remove file storage
                $name = $appInfo['name'];
                $store->deleteApp($name);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $id_list
     * @throws Exception
     */
    protected function preDeleteAppGroups($id_list)
    {
        try {
            $ids = array_map('trim', explode(',', $id_list));
            foreach ($ids as $id) {
                $this->assignAppGroups($id, '', true);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecords($table, $records, $rollback = false, $fields = '')
    {
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        if (!isset($records[0])) {
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $idList = '';
        foreach ($records as $record) {
            if (!isset($record['fields']) || empty($record['fields'])) {
                throw new Exception('[InvalidParam]: There are no fields in the record set.');
            }
            if (!empty($idList)) $idList .= ',';
            $idList .= $record['fields']['id'];
        }

        return $this->deleteRecordsByIds($table, $idList, $fields);
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordsByIds($table, $id_list, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('delete', 'system', $table);

        try {
            switch (strtolower($table)) {
            case "app_group":
                $this->preDeleteAppGroups($id_list);
                break;
            case "app":
                $this->preDeleteApps($id_list);
                break;
            case "role":
                $this->preDeleteRoles($id_list);
                break;
            case "user":
                $this->preDeleteUsers($id_list);
                break;
            }

            $results = $this->nativeDb->deleteSqlRecordsByIds($table, $id_list, 'id', false, $fields);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('error' => array('message' => $result, 'code' => 500));
                }
            }

            return array('record' => $out);
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $records
     * @param string $fields
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecords($table, $records, $fields = '', $extras = null)
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('read', 'system', $table);
        $fields = $this->checkRetrievableSystemFields($table, $fields);

        try {
            $results = $this->nativeDb->retrieveSqlRecords($table, $records, 'id', $fields);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    switch (strtolower($table)) {
                    case 'app_group':
                        break;
                    case 'role':
                        if ((isset($result['id']) && !empty($result['id'])) &&
                            (empty($fields) || (false !== stripos($fields, 'services')))) {
                            $permFields = 'service_id,service,component,read,create,update,delete';
                            $permQuery = "role_id='" . $result['id'] . "'";
                            $perms = $this->nativeDb->retrieveSqlRecordsByFilter('role_service_access', $permFields, $permQuery, 0, 'service');
                            unset($perms['total']);
                            $result['services'] = $perms;
                        }
                        break;
                    case 'service':
                        if (isset($result['type']) && !empty($result['type'])) {
                            switch (strtolower($result['type'])) {
                            case 'native':
                            case 'managed':
                                unset($result['base_url']);
                                unset($result['parameters']);
                                unset($result['headers']);
                                break;
                            case 'web':
                                break;
                            }
                        }
                        break;
                    case 'user':
                        if (isset($extras['role'])) {
                            if (isset($result['role_id']) && !empty($result['role_id'])) {
                                $roleInfo = $this->retrieveRecordsByIds('role', $result['role_id'], 'id', '');
                                $result['role'] = $roleInfo[0];
                            }
                        }
                        break;
                    }
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('error' => array('message' => $result, 'code' => 500));
                }
            }

            return array('record' => $out);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param string $fields
     * @param string $filter
     * @param int $limit
     * @param string $order
     * @param int $offset
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByFilter($table, $fields = '', $filter = '', $limit = 0, $order = '', $offset = 0, $extras = null)
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('read', 'system', $table);
        $fields = $this->checkRetrievableSystemFields($table, $fields);

        try {
            $results = $this->nativeDb->retrieveSqlRecordsByFilter($table, $fields, $filter, $limit, $order, $offset);
            $total = (isset($results['total'])) ? $results['total'] : '';
            unset($results['total']);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    switch (strtolower($table)) {
                    case 'app_group':
                        break;
                    case 'role':
                        if ((isset($result['id']) && !empty($result['id'])) &&
                            (empty($fields) || (false !== stripos($fields, 'services')))) {
                            $permFields = 'service_id,service,component,read,create,update,delete';
                            $permQuery = "role_id='" . $result['id'] . "'";
                            $perms = $this->nativeDb->retrieveSqlRecordsByFilter('role_service_access', $permFields, $permQuery, 0, 'service');
                            unset($perms['total']);
                            $result['services'] = $perms;
                        }
                        break;
                    case 'service':
                        if (isset($result['type']) && !empty($result['type'])) {
                            switch (strtolower($result['type'])) {
                            case 'native':
                            case 'managed':
                                unset($result['base_url']);
                                unset($result['parameters']);
                                unset($result['headers']);
                                break;
                            case 'web':
                                break;
                            }
                        }
                        break;
                    case 'user':
                        if (isset($extras['role'])) {
                            if (isset($result['role_id']) && !empty($result['role_id'])) {
                                $roleInfo = $this->retrieveRecordsByIds('role', $result['role_id'], 'id', '');
                                $result['role'] = $roleInfo[0];
                            }
                        }
                        break;
                    }
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('error' => array('message' => $result, 'code' => 500));
                }
            }

            return array('record' => $out, 'meta' => array('total' => $total));
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $fields
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByIds($table, $id_list, $fields = '', $extras = null)
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        Utilities::checkPermission('read', 'system', $table);
        $fields = $this->checkRetrievableSystemFields($table, $fields);

        try {
            $results = $this->nativeDb->retrieveSqlRecordsByIds($table, $id_list, 'id', $fields);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    switch (strtolower($table)) {
                    case 'app_group':
                        break;
                    case 'role':
                        if ((isset($result['id']) && !empty($result['id'])) &&
                            (empty($fields) || (false !== stripos($fields, 'services')))) {
                            $permFields = 'service_id,service,component,read,create,update,delete';
                            $permQuery = "role_id='" . $result['id'] . "'";
                            $perms = $this->nativeDb->retrieveSqlRecordsByFilter('role_service_access', $permFields, $permQuery, 0, 'service');
                            unset($perms['total']);
                            $result['services'] = $perms;
                        }
                        break;
                    case 'service':
                        if (isset($result['type']) && !empty($result['type'])) {
                            switch (strtolower($result['type'])) {
                            case 'native':
                            case 'managed':
                                unset($result['base_url']);
                                unset($result['parameters']);
                                unset($result['headers']);
                                break;
                            case 'web':
                                break;
                            }
                        }
                        break;
                    case 'user':
                        if (isset($extras['role'])) {
                            if (isset($result['role_id']) && !empty($result['role_id'])) {
                                $roleInfo = $this->retrieveRecordsByIds('role', $result['role_id'], 'id', '');
                                $result['role'] = $roleInfo[0];
                            }
                        }
                        break;
                    }
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('error' => array('message' => $result, 'code' => 500));
                }
            }

            return array('record' => $out);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

    //-------- Schema Operations ---------------------

    /**
     * @return array
     */
    public function describeSystem()
    {
        $result = array(array('name' => 'app', 'label' => 'Application', 'plural' => 'Applications'),
                        array('name' => 'app_group', 'label' => 'Application Group', 'plural' => 'Application Groups'),
                        array('name' => 'role', 'label' => 'Role', 'plural' => 'Roles'),
                        array('name' => 'service', 'label' => 'Service', 'plural' => 'Services'),
                        array('name' => 'user', 'label' => 'User', 'plural' => 'Users'));
        return $result;
        /* don't expose
        $tables = self::SYSTEM_TABLES;
        try {
            return $this->nativeDb->describeDatabase($tables, '');
        }
        catch (Exception $ex) {
            throw new Exception("Error describing system tables.\n{$ex->getMessage()}");
        }
        */
    }

    /**
     * @param $table
     * @return array
     * @throws Exception
     */
    public function describeTable($table)
    {
        try {
            return $this->nativeDb->describeTable($table);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database table '$table'.\n{$ex->getMessage()}");
        }
    }


    //-------- System Helper Operations -------------------------------------------------

    /**
     * @param $id
     * @return string
     * @throws Exception
     */
    public function getAppNameFromId($id)
    {
        if (!empty($id)) {
            try {
                $result = $this->nativeDb->retrieveSqlRecordsByIds('app', $id, 'id', 'name');
                if (count($result) > 0) {
                    return $result[0]['name'];
                }
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }

        return '';
    }

    /**
     * @param $name
     * @return string
     * @throws Exception
     */
    public function getAppIdFromName($name)
    {
        if (!empty($name)) {
            try {
                $result = $this->nativeDb->retrieveSqlRecordsByIds('app', $name, 'name', 'id');
                if (count($result) > 0) {
                    return $result[0]['id'];
                }
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCurrentAppId()
    {
        return $this->getAppIdFromName(Utilities::getCurrentAppName());
    }

    /**
     * @param $user_names
     * @return string
     * @throws Exception
     */
    protected function getUserIdsFromNames($user_names)
    {
        try {
            $result = $this->nativeDb->retrieveSqlRecordsByIds('user', "'$user_names'", 'username', 'id');
            $userIds = '';
            foreach ($result as $item) {
                if (!empty($userIds)) {
                    $userIds .= ',';
                }
                $userIds .= $item['id'];
            }

            return $userIds;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $role_id
     * @param string $user_ids
     * @param string $user_names
     * @param bool $override
     * @throws Exception
     */
    public function assignRole($role_id, $user_ids = '', $user_names = '', $override = false)
    {
        Utilities::checkPermission('create', 'system', 'RoleAssign');
        // ids have preference, if blank, use names for users
        // override is true/false, true allows overriding an existing role assignment
        // i.e. move user to a different role,
        // false will return an error for that user that is already assigned to a role
        if (empty($role_id)) {
            throw new Exception('[InvalidParam]: Role id can not be empty.');
        }
        if (empty($user_ids)) {
            if (empty($user_names)) {
                throw new Exception('[InvalidParam]: User ids and names can not both be empty.');
            }
            // find user ids
            try {
                $user_ids = $this->getUserIdsFromNames($user_names);
            }
            catch (Exception $ex) {
                throw new Exception("Error looking up users.\n{$ex->getMessage()}");
            }
        }

        $user_ids = implode(',', array_map('trim', explode(',', $user_ids)));
        try {
            $users = $this->nativeDb->retrieveSqlRecordsByIds('user', $user_ids, 'id', 'role_id,id');
            foreach ($users as $key => $user) {
                $users[$key]['role_id'] = $role_id;
            }
            $this->nativeDb->updateSqlRecords('user', $users, 'id', false, 'id');
        }
        catch (Exception $ex) {
            throw new Exception("Error updating users.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $role_id
     * @param string $user_ids
     * @param string $user_names
     * @throws Exception
     */
    public function unassignRole($role_id, $user_ids = '', $user_names = '')
    {
        Utilities::checkPermission('delete', 'system', 'RoleAssign');
        // ids have preference, if blank, use names for both role and users
        // use this to officially remove a user from the current app
        if (empty($role_id)) {
            throw new Exception('[InvalidParam]: Role id can not be empty.');
        }
        if (empty($user_ids)) {
            if (empty($user_names)) {
                throw new Exception('[InvalidParam]: User ids and names can not both be empty.');
            }
            // find user ids
            try {
                $user_ids = $this->getUserIdsFromNames($user_names);
            }
            catch (Exception $ex) {
                throw new Exception("Error looking up users.\n{$ex->getMessage()}");
            }
        }

        $user_ids = implode(',', array_map('trim', explode(',', $user_ids)));
        try {
            $users = $this->nativeDb->retrieveSqlRecordsByIds('user', $user_ids, 'id', 'role_id,id');
            foreach ($users as $key => $user) {
                $userRoleId = trim($user['role_id']);
                if ($userRoleId === $role_id) {
                    $users[$key]['role_id'] = '';
                }
            }
            $this->nativeDb->updateSqlRecords('user', $users, 'id', false, 'id');
        }
        catch (Exception $ex) {
            throw new Exception("Error updating users.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $role_id
     * @param array $services
     * @throws Exception
     */
    protected function assignServiceAccess($role_id, $services = array())
    {
        // get any pre-existing access records
        $old = $this->nativeDb->retrieveSqlRecordsByFilter('role_service_access', 'id', "role_id = '$role_id'");
        unset($old['total']);
        // create a new access record for each service
        $noDupes = array();
        if (!empty($services)) {
            foreach ($services as $key=>$sa) {
                $services[$key]['role_id'] = $role_id;
                // validate service
                $component = Utilities::getArrayValue('component', $sa);
                $serviceName = Utilities::getArrayValue('service', $sa);
                $serviceId = Utilities::getArrayValue('service_id', $sa);
                if (empty($serviceName) && empty($serviceId)) {
                    throw new Exception("No service name or id in role service access.");
                }
                if ('*' !== $serviceName) { // special 'All Services' designation
                    if (!empty($serviceId)) {
                        $temp = $this->nativeDb->retrieveSqlRecordsByIds('service', $serviceId, 'id', 'id,name');
                        if ((count($temp) > 0) && isset($temp[0]['name']) && !empty($temp[0]['name'])) {
                            $serviceName = $temp[0]['name'];
                            $services[$key]['service'] = $serviceName;
                        }
                        else {
                            throw new Exception("Invalid service id '$serviceId' in role service access.");
                        }
                    }
                    elseif (!empty($serviceName)) {
                        $temp = $this->nativeDb->retrieveSqlRecordsByIds('service', $serviceName, 'name', 'id,name');
                        if ((count($temp) > 0) && isset($temp[0]['id']) && !empty($temp[0]['id'])) {
                            $services[$key]['service_id'] = $temp[0]['id'];
                        }
                        else {
                            throw new Exception("Invalid service name '$serviceName' in role service access.");
                        }
                    }
                }
                $test = $serviceName.'.'.$component;
                if (false !== array_search($test, $noDupes)) {
                    throw new Exception("Duplicated service and component combination '$serviceName $component' in role service access.");
                }
                $noDupes[] = $test;
            }
            $this->nativeDb->createSqlRecords('role_service_access', $services);
        }
        if ((count($old) > 0) && isset($old[0]['id'])) {
            // delete any pre-existing access records
            $this->nativeDb->deleteSqlRecords('role_service_access', $old, 'id');
        }
    }

    /**
     * @param $app_group_id
     * @param string $app_ids
     * @param bool $for_update
     * @throws Exception
     * @return void
     */
    protected function assignAppGroups($app_group_id, $app_ids, $for_update = false)
    {
        if (empty($app_group_id)) {
            throw new Exception('[InvalidParam]: App group id can not be empty.');
        }
        // drop outer commas and spaces
        $app_ids = implode(',', array_map('trim', explode(',', trim($app_ids, ','))));
        try {
            if ($for_update) {
                // possibly remove existing groups
                // %,$id,% is more accurate but in case of sloppy updating by client, filter for %$id%
                $query = "(app_group_ids like '%$app_group_id%')";
                if (!empty($app_ids)) {
                    $query .= " and (id not in ($app_ids))";
                }
                $apps = $this->nativeDb->retrieveSqlRecordsByFilter('app', 'id,app_group_ids', $query);
                unset($apps['total']);
                foreach ($apps as $key => $app) {
                    $groupIds = Utilities::getArrayValue('app_group_ids', $app, '');
                    $groupIds = trim($groupIds, ','); // in case of sloppy updating
                    if (false === stripos(",$groupIds,", ",$app_group_id,")) {
                        // may not be there due to sloppy updating query
                        unset($apps[$key]);
                        continue;
                    }
                    $groupIds = trim(str_ireplace(",$app_group_id,", '', ",$groupIds,"), ',');
                    if (!empty($groupIds))
                        $groupIds = ",$groupIds,";
                    $apps[$key]['app_group_ids'] = $groupIds;
                }
                $apps = array_values($apps);
                if (!empty($apps)) {
                    $this->nativeDb->updateSqlRecords('app', $apps, 'id');
                }
            }
            if (!empty($app_ids)) {
                // add new groups
                $apps = $this->nativeDb->retrieveSqlRecordsByIds('app', $app_ids, 'id', 'id,app_group_ids');
                foreach ($apps as $key => $app) {
                    $groupIds = Utilities::getArrayValue('app_group_ids', $app, '');
                    $groupIds = trim($groupIds, ','); // in case of sloppy updating
                    $groupIds = Utilities::addOnceToList($groupIds, $app_group_id, ',');
                    $apps[$key]['app_group_ids'] = ",$groupIds,";
                }
                $apps = array_values($apps);
                if (!empty($apps)) {
                    $this->nativeDb->updateSqlRecords('app', $apps, 'id');
                }
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error updating users.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $fields
     * @param bool $for_update
     * @throws Exception
     */
    protected function validateUniqueSystemName($table, $fields, $for_update = false)
    {
        $id = Utilities::getArrayValue('id', $fields, '');
        if ($for_update && empty($id)) {
            throw new Exception("The Id field for $table can not be empty for updates.");
        }
        // make sure it is named
        $nameField = 'name';
        if (0 == strcasecmp('user', $table)) {
            $nameField = 'username';
        }
        $name = Utilities::getArrayValue($nameField, $fields, '');
        if (empty($name)) {
            if ($for_update) {
                return; // no need to check
            }
            throw new Exception("The $nameField field for $table can not be empty.");
        }
        if ($for_update && (0 == strcasecmp('app', $table))) {
            throw new Exception("Application names can not change. Change the label instead.");
        }
        $appId = '';
        if (0 == strcasecmp('role', $table)) {
            $appId = Utilities::getArrayValue('app_ids', $fields, '');
            if (empty($appId) && $for_update) {
                // get the appId from the db for this role id
                try {
                    $result = $this->nativeDb->retrieveSqlRecordsByIds('role', $id, 'id', 'app_ids');

                    if (count($result) > 0) {
                        $appId = (isset($result[0]['app_ids'])) ? $result[0]['app_ids'] : '';
                    }
                }
                catch (Exception $ex) {
                    throw new Exception("A Role with this id does not exist.");
                }
            }
            // make sure it is unique
            try {
                $result = $this->nativeDb->retrieveSqlRecordsByFilter('role', 'id', "name='$name' AND app_ids='$appId'", 1);
                unset($result['total']);
                if (count($result) > 0) {
                    if ($for_update) {
                        if ($id != $result[0]['id']) { // not self
                            throw new Exception("A $table already exists with the $nameField '$name'.");
                        }
                    }
                    else {
                        throw new Exception("A $table already exists with the $nameField '$name'.");
                    }
                }
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }
        else {
            // make sure it is unique
            try {
                $result = $this->nativeDb->retrieveSqlRecordsByFilter($table, 'id', "$nameField = '$name'", 1);
                unset($result['total']);
                if (count($result) > 0) {
                    if ($for_update) {
                        if ($id != $result[0]['id']) { // not self
                            throw new Exception("A $table already exists with the $nameField '$name'.");
                        }
                    }
                    else {
                        throw new Exception("A $table already exists with the $nameField '$name'.");
                    }
                }
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }
    }

    /**
     * @param $table
     * @param $fields
     * @return string
     */
    protected function checkRetrievableSystemFields($table, $fields)
    {
        switch (strtolower($table)) {
        case 'user':
            if (empty($fields)) {
                $fields = 'id,display_name,first_name,last_name,username,email,phone,';
                $fields .= 'is_active,is_sys_admin,role_id,created_date,created_by_id,last_modified_date,last_modified_by_id';
            }
            else {
                $fields = Utilities::removeOneFromList($fields, 'password', ',');
                $fields = Utilities::removeOneFromList($fields, 'security_question', ',');
                $fields = Utilities::removeOneFromList($fields, 'security_answer', ',');
                $fields = Utilities::removeOneFromList($fields, 'confirm_code', ',');
            }
            break;
        default:
        }

        return $fields;
    }

    /**
     * @param $fields
     * @param bool $for_update
     */
    protected function validateUser(&$fields, $for_update = false)
    {
        $pwd = Utilities::getArrayValue('password', $fields, '');
        if (!empty($pwd)) {
            $fields['password'] = CPasswordHelper::hashPassword($pwd);
            $fields['confirm_code'] = 'y';
        }
        else {
            // todo autogenerate ?
        }
    }

}
