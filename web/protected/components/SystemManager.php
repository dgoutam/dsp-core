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
            $theUser = User::model()->find('is_sys_admin=:is', array(':is'=>1));
            if (null === $theUser) {
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
            $oldVersion = '';
            if ($this->nativeDb->doesTableExist('config')) {
                $config = Config::model()->findAll();
                if (!empty($config)) {
                    $oldVersion = $config->getAttribute('db_version');
                }
            }
            // create system tables
            $tables = Utilities::getArrayValue('table', $contents);
            if (empty($tables)) {
                throw new \Exception("No default system schema found.");
            }
            $result = $this->nativeDb->createTables($tables, true, true, false);

            // setup session stored procedure
            $command = Yii::app()->db->createCommand();
//            $query = 'SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
//                      WHERE ROUTINE_TYPE="PROCEDURE"
//                          AND ROUTINE_SCHEMA="dreamfactory"
//                          AND ROUTINE_NAME="UpdateOrInsertSession";';
//            $result = $db->singleSqlQuery($query);
//            if ((empty($result)) || !isset($result[0]['ROUTINE_NAME'])) {
            switch ($this->nativeDb->getDriverType()) {
                case DbUtilities::DRV_SQLSRV:
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
                case DbUtilities::DRV_MYSQL:
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
            $theUser = User::model()->find('username=:un', array(':un'=>$username));
            if (null !== $theUser) {
                throw new Exception("A User already exists with the username '$username'.", ErrorCodes::BAD_REQUEST);
            }
            $firstName = Utilities::getArrayValue('firstName', $data);
            $lastName = Utilities::getArrayValue('lastName', $data);
            $displayName = Utilities::getArrayValue('displayName', $data);
            $pwd = Utilities::getArrayValue('password', $data, '');
            $fields = array('username' => $username,
                            'email' => Utilities::getArrayValue('email', $data),
                            'password' => CPasswordHelper::hashPassword($pwd),
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'display_name' => (empty($displayName) ? $firstName . ' ' . $lastName : $displayName),
                            'is_active' => true,
                            'is_sys_admin' => true,
                            'confirm_code' => 'y'
            );
            $user = new User();
            $user->setAttributes($fields);
            if (!$user->save()) {
                $msg = '';
                if ($user->hasErrors()) {
                    foreach ($user->errors as $error) {
                        $msg .= implode(PHP_EOL, $error);
                    }
                }
                throw new Exception("Failed to create a new user.\n$msg", ErrorCodes::BAD_REQUEST);
            }
            $userId = $user->getPrimaryKey();
            SessionManager::setCurrentUserId($userId);
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
            $theUser = User::model()->find('is_sys_admin=:is', array(':is'=>1));
            if (null === $theUser) {
                throw new \Exception("Failed to retrieve admin user.");
            }
            $userId = $theUser->getPrimaryKey();
            if (empty($userId)) {
                error_log(print_r($theUser, true));
                throw new \Exception("Failed to retrieve user id.");
            }
            SessionManager::setCurrentUserId($userId);

            // init system tables with records
            $contents = file_get_contents(Yii::app()->basePath . '/data/system_data.json');
            if (empty($contents)) {
                throw new \Exception("Empty or no system data file found.");
            }
            $contents = Utilities::jsonToArray($contents);
            $result = Service::model()->findAll();
            if (empty($result)) {
                $services = Utilities::getArrayValue('service', $contents);
                if (empty($services)) {
                    error_log(print_r($contents, true));
                    throw new \Exception("No default system services found.");
                }
                $this->nativeDb->createSqlRecords('service', $services, true);
            }
            $result = App::model()->findAll();
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
            switch ($this->modelName) {
            case '':
                $result = array(array('name' => 'app', 'label' => 'Application'),
                    array('name' => 'app_group', 'label' => 'Application Group'),
                    array('name' => 'config', 'label' => 'Configuration'),
                    array('name' => 'role', 'label' => 'Role'),
                    array('name' => 'service', 'label' => 'Service'),
                    array('name' => 'user', 'label' => 'User'));
                $result = array('resource' => $result);
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                // Most requests contain 'returned fields' parameter, all by default
                $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '*';
                $extras = array();
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
                        $data = Utilities::getPostDataAsArray();
                        if (!empty($data)) { // complex filters or large numbers of ids require post
                            $ids = Utilities::getArrayValue('ids', $data, '');
                            if (!empty($ids)) {
                                $result = $this->retrieveRecordsByIds($this->modelName, $ids, $fields, $extras);
                            }
                            else {
                                $records = Utilities::getArrayValue('record', $data, null);
                                if (empty($records)) {
                                    // xml to array conversion leaves them in plural wrapper
                                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                                }
                                if (!empty($records)) {
                                    // passing records to have them updated with new or more values, id field required
                                    $result = $this->retrieveRecords($this->modelName, $records, $fields, $extras);
                                }
                                elseif (isset($data['fields']) && !empty($data['fields'])) {
                                    // passing record to have it updated with new or more values, id field required
                                    $result = $this->retrieveRecord($this->modelName, $data, $fields, $extras);
                                }
                                else { // if not specified use filter
                                    $filter = Utilities::getArrayValue('filter', $data, '');
                                    $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                                    $order = Utilities::getArrayValue('order', $data, '');
                                    $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                                    $result = $this->retrieveRecordsByFilter($this->modelName, $fields, $filter, $limit, $order, $offset, $extras);
                                }
                            }
                        }
                        else {
                            $filter = Utilities::getArrayValue('filter', $_REQUEST, '');
                            $limit = intval(Utilities::getArrayValue('limit', $_REQUEST, 0));
                            $order = Utilities::getArrayValue('order', $_REQUEST, '');
                            $offset = intval(Utilities::getArrayValue('offset', $_REQUEST, 0));
                            $result = $this->retrieveRecordsByFilter($this->modelName, $fields, $filter, $limit, $order, $offset, $extras);
                        }
                    }
                }
                else { // single entity by id
                    $result = $this->retrieveRecordById($this->modelName, $this->modelId, $fields, $extras);
                }
                break;
            default:
                throw new Exception("GET request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST);
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
                throw new Exception("Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN);
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                $records = Utilities::getArrayValue('record', $data, array());
                if (empty($records)) {
                    // xml to array conversion leaves them in plural wrapper
                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                }
                if (empty($records)) {
                    $single = Utilities::getArrayValue('fields', $data, array());
                    if (empty($single)) {
                        throw new Exception('No record in POST create request.', ErrorCodes::BAD_REQUEST);
                    }
                    $result = $this->createRecord($this->modelName, $data, $fields);
                }
                else {
                    $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                    if (!isset($rollback)) {
                        $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                    }
                    $result = $this->createRecords($this->modelName, $records, $rollback, $fields);
                }
                break;
            default:
                throw new Exception("POST request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST);
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
                throw new Exception("Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN);
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                if (empty($this->modelId)) {
                    $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                    if (!isset($rollback)) {
                        $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                    }
                    $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                    if (empty($ids)) {
                        $ids = Utilities::getArrayValue('ids', $data, '');
                    }
                    if (!empty($ids)) {
                        $result = $this->updateRecordsByIds($this->modelName, $ids, $data, $rollback, $fields);
                    }
                    else {
                        $records = Utilities::getArrayValue('record', $data, null);
                        if (empty($records)) {
                            // xml to array conversion leaves them in plural wrapper
                            $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                        }
                        if (empty($records)) {
                            $single = Utilities::getArrayValue('fields', $data, array());
                            if (empty($single)) {
                                throw new Exception('No record in PUT update request.', ErrorCodes::BAD_REQUEST);
                            }
                            $result = $this->updateRecord($this->modelName, $data, $fields);
                        }
                        else {
                            $result = $this->updateRecords($this->modelName, $records, $rollback, $fields);
                        }
                    }
                }
                else {
                    $result = $this->updateRecordById($this->modelName, $this->modelId, $data, $fields);
                }
                break;
            default:
                throw new Exception("PUT request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST);
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
                throw new Exception("Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN);
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                if (empty($this->modelId)) {
                    $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                    if (!isset($rollback)) {
                        $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                    }
                    $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                    if (empty($ids)) {
                        $ids = Utilities::getArrayValue('ids', $data, '');
                    }
                    if (!empty($ids)) {
                        $result = $this->updateRecordsByIds($this->modelName, $ids, $data, $rollback, $fields);
                    }
                    else {
                        $records = Utilities::getArrayValue('record', $data, null);
                        if (empty($records)) {
                            // xml to array conversion leaves them in plural wrapper
                            $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                        }
                        if (empty($records)) {
                            $single = Utilities::getArrayValue('fields', $data, array());
                            if (empty($single)) {
                                throw new Exception('No record in MERGE update request.', ErrorCodes::BAD_REQUEST);
                            }
                            $result = $this->updateRecord($this->modelName, $data, $fields);
                        }
                        else {
                            $result = $this->updateRecords($this->modelName, $records, $rollback, $fields);
                        }
                    }
                }
                else {
                    $result = $this->updateRecordById($this->modelName, $this->modelId, $data, $fields);
                }
                break;
            default:
                throw new Exception("MERGE/PATCH request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST);
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
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch ($this->modelName) {
            case '':
                throw new Exception("Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN);
                break;
            case 'app':
            case 'app_group':
            case 'role':
            case 'service':
            case 'user':
                if (empty($this->modelId)) {
                    $data = Utilities::getPostDataAsArray();
                    $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                    if (empty($ids)) {
                        $ids = Utilities::getArrayValue('ids', $data, '');
                    }
                    if (!empty($ids)) {
                        $result = $this->deleteRecordsByIds($this->modelName, $ids, $fields);
                    }
                    else {
                        $records = Utilities::getArrayValue('record', $data, null);
                        if (empty($records)) {
                            // xml to array conversion leaves them in plural wrapper
                            $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                        }
                        if (empty($records)) {
                            $single = Utilities::getArrayValue('fields', $data, array());
                            if (empty($single)) {
                                throw new Exception("Id list or record containing Id field required to delete $this->modelName records.", ErrorCodes::BAD_REQUEST);
                            }
                            $result = $this->deleteRecord($this->modelName, $data, $fields);
                        }
                        else {
                            $result = $this->deleteRecords($this->modelName, $records, $fields);
                        }
                    }
                }
                else {
                    $result = $this->deleteRecordById($this->modelName, $this->modelId, $fields);
                }
                break;
            default:
                throw new Exception("DELETE request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST);
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

    public static function getResourceModel($resource)
    {
        switch (strtolower($resource)) {
        case 'app':
            $model = App::model();
            break;
        case 'app_group':
            $model = AppGroup::model();
            break;
        case 'label':
            $model = Label::model();
            break;
        case 'role':
            $model = Role::model();
            break;
        case 'service':
            $model = Service::model();
            break;
        case 'user':
            $model = User::model();
            break;
        default:
            throw new Exception("Invalid system resource '$resource' requested.", ErrorCodes::BAD_REQUEST);
            break;
        }

        return $model;
    }

    public static function getNewResource($resource)
    {
        switch (strtolower($resource)) {
        case 'app':
            $obj = new App;
            break;
        case 'app_group':
            $obj = new AppGroup;
            break;
        case 'label':
            $obj = new Label;
            break;
        case 'role':
            $obj = new Role;
            break;
        case 'service':
            $obj = new Service;
            break;
        case 'user':
            $obj = new User;
            break;
        default:
            throw new Exception("Attempting to create an invalid system resource '$resource'.", ErrorCodes::INTERNAL_SERVER_ERROR);
            break;
        }

        return $obj;
    }

    //-------- System Records Operations ---------------------
    // records is an array of field arrays

    /**
     * @param $table
     * @param $record
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    protected function createRecordLow($table, $record, $return_fields = '')
    {
        $fields = Utilities::getArrayValue('fields', $record);
        if (empty($fields)) {
            throw new Exception('There are no fields in the record to create.', ErrorCodes::BAD_REQUEST);
        }
        $model = static::getResourceModel($table);
        try {
            // create DB record
            $obj = static::getNewResource($table);
            $obj->setAttributes($fields);
            if (!$obj->save()) {
                $msg = '';
                if ($obj->hasErrors()) {
                    foreach ($obj->errors as $error) {
                        $msg .= implode(PHP_EOL, $error);
                    }
                }
                error_log(print_r($obj->errors, true));
                throw new Exception("Failed to create $table.\n$msg", ErrorCodes::INTERNAL_SERVER_ERROR);
            }
            $id = $obj->primaryKey;
            if (empty($id)) {
                error_log(print_r($obj, true));
                throw new Exception("Failed to get primary key from created user.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            $return_fields = $model->getRetrievableAttributes($return_fields);
            $data = $obj->getAttributes($return_fields);
            // after record create
            switch (strtolower($table)) {
            case 'app':
                if (0 === $obj->is_url_external) {
                    $appSvc = ServiceHandler::getInstance()->getServiceObject('app');
                    if ($appSvc) {
                        $appSvc->createApp($obj->name);
                    }
                }
                break;
            case 'app_group':
                if (isset($fields['app_ids'])) {
                    try {
                        $appIds = $fields['app_ids'];
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
                if (isset($fields['services'])) {
                    try {
                        $services = $fields['services'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }

            return array('fields' => $data);
        }
        catch (Exception $ex) {
            // need to delete the above table entry and clean up
            if (isset($obj) && !$obj->getIsNewRecord()) {
                $obj->delete();
            }
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function createRecords($table, $records, $rollback = false, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records[0])) { // isArrayNumeric($records)
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        SessionManager::checkPermission('create', 'system', $table);
        // todo implement rollback
        $out = array();
        foreach ($records as $record) {
            try {
                $out[] = $this->createRecordLow($table, $record, $return_fields);
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
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function createRecord($table, $record, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('create', 'system', $table);
        return $this->createRecordLow($table, $record, $return_fields);
    }

    /**
     * @param $table
     * @param $id
     * @param $record
     * @param $return_fields
     * @return array
     * @throws Exception
     */
    protected function updateRecordLow($table, $id, $record, $return_fields = '')
    {
        $fields = Utilities::getArrayValue('fields', $record, array());
        if (empty($fields)) {
            throw new Exception('There are no fields in the record to create.', ErrorCodes::BAD_REQUEST);
        }
        if (empty($id)) {
            throw new Exception("Identifying field 'id' can not be empty for update request.", ErrorCodes::BAD_REQUEST);
        }
        $model = static::getResourceModel($table);
        $obj = $model->findByPk($id);
        if (!$obj) {
            throw new Exception("Failed to find the $table resource identified by '$id'.", ErrorCodes::NOT_FOUND);
        }
        try {
            $fields = Utilities::removeOneFromArray('id', $fields);
            // todo move this to model rules
            if (isset($fields['password'])) {
                $obj->setAttribute('password', CPasswordHelper::hashPassword($fields['password']));
                unset($fields['password']);
            }
            if (isset($fields['security_answer'])) {
                $obj->setAttribute('security_answer', CPasswordHelper::hashPassword($fields['security_answer']));
                unset($fields['security_answer']);
            }
            $obj->setAttributes($fields);
            if (!$obj->save()) {
                error_log(print_r($obj->errors, true));
                $msg = '';
                if ($obj->hasErrors()) {
                    foreach ($obj->errors as $error) {
                        $msg .= implode(PHP_EOL, $error);
                    }
                }
                throw new Exception("Failed to update user.\n$msg", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            $return_fields = $model->getRetrievableAttributes($return_fields);
            $data = $obj->getAttributes($return_fields);
            // after record update
            switch (strtolower($table)) {
            case 'app':
                $isUrlExternal = Utilities::getArrayValue('is_url_external', $record, null);
                if (isset($isUrlExternal)) {
                    $name = $obj->name;
                    if (!Utilities::boolval($isUrlExternal)) {
                        $appSvc = ServiceHandler::getInstance()->getServiceObject('app');
                        if ($appSvc) {
                            if (!$appSvc->appExists($name)) {
                                $appSvc->createApp($name);
                            }
                        }
                    }
                }
                break;
            case 'app_group':
                if (isset($fields['app_ids'])) {
                    try {
                        $appIds = $fields['app_ids'];
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
                if (isset($fields['services'])) {
                    try {
                        $services = $fields['services'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }

            return array('fields' => $data);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function updateRecords($table, $records, $rollback = false, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records[0])) {
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        SessionManager::checkPermission('update', 'system', $table);
        $out = array();
        foreach ($records as $record) {
            try {
                // todo this needs to use $model->getPrimaryKey()
                $id = (isset($record['fields'])) ? Utilities::getArrayValue('id', $record['fields']) : '';
                $out[] = $this->updateRecordLow($table, $id, $record, $return_fields);
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
    * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function updateRecord($table, $record, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There is no record in the request.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('update', 'system', $table);
        // todo this needs to use $model->getPrimaryKey()
        $id = Utilities::getArrayValue('id', $record['fields']);
        return $this->updateRecordLow($table, $id, $record, $return_fields);
    }

    /**
     * @param $table
     * @param $id_list
     * @param $record
     * @param bool $rollback
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function updateRecordsByIds($table, $id_list, $record, $rollback = false, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($record) || empty($record)) {
            throw new Exception('There is no record in the request.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('update', 'system', $table);
        $ids = array_map('trim', explode(',', $id_list));
        $out = array();
        foreach ($ids as $id) {
            try {
                $out[] = $this->updateRecordLow($table, $id, $record, $return_fields);
            }
            catch (Exception $ex) {
                $out[] = array('error' => array('message' => $ex->getMessage(), 'code' => $ex->getCode()));
            }
        }

        return array('record' => $out);
    }

    /**
     * @param $table
     * @param $id
     * @param $record
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function updateRecordById($table, $id, $record, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($record) || empty($record)) {
            throw new Exception('There is no record in the request.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('update', 'system', $table);
        return $this->updateRecordLow($table, $id, $record, $return_fields);
    }

    /**
     * @param $table
     * @param $id
     * @param $return_fields
     * @return array
     * @throws Exception
     */
    protected function deleteRecordLow($table, $id, $return_fields = '')
    {
        if (empty($id)) {
            throw new Exception("Identifying field 'id' can not be empty for delete request.", ErrorCodes::BAD_REQUEST);
        }
        $model = static::getResourceModel($table);
        $obj = $model->findByPk($id);
        if (!$obj) {
            throw new Exception("Failed to find the $table resource identified by '$id'.", ErrorCodes::NOT_FOUND);
        }
        try {
            if (!$obj->delete()) {
                error_log(print_r($obj->errors, true));
                $msg = '';
                if ($obj->hasErrors()) {
                    foreach ($obj->errors as $error) {
                        $msg .= implode(PHP_EOL, $error);
                    }
                }
                throw new Exception("Failed to delete user.\n$msg", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            $return_fields = $model->getRetrievableAttributes($return_fields);
            $data = $obj->getAttributes($return_fields);

            return array('fields' => $data);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function deleteRecords($table, $records, $rollback = false, $return_fields = '')
    {
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records[0])) {
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $out = array();
        foreach ($records as $record) {
            if (!isset($record['fields']) || empty($record['fields'])) {
                throw new Exception('There are no fields in the record set.', ErrorCodes::BAD_REQUEST);
            }
            $id = $record['fields']['id'];
            try {
                $out[] = $this->deleteRecordLow($table, $id, $return_fields);
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
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function deleteRecord($table, $record, $return_fields = '')
    {
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $id = $record['fields']['id'];
        return $this->deleteRecordById($table, $id, $return_fields);
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordsByIds($table, $id_list, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('delete', 'system', $table);
        $ids = array_map('trim', explode(',', $id_list));
        $out = array();
        foreach ($ids as $id) {
            try {
                $out[] = $this->deleteRecordLow($table, $id, $return_fields);
            }
            catch (Exception $ex) {
                $out[] = array('error' => array('message' => $ex->getMessage(), 'code' => $ex->getCode()));
            }
        }

        return array('record' => $out);
    }

    /**
     * @param $table
     * @param $id
     * @param string $return_fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordById($table, $id, $return_fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('delete', 'system', $table);
        return $this->deleteRecordLow($table, $id, $return_fields);
    }

    /**
     * @param $table
     * @param $records
     * @param string $return_fields
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecords($table, $records, $return_fields = '', $extras = null)
    {
        $ids = array();
        foreach ($records as $key => $record) {
            $id = (isset($record['fields'])) ? Utilities::getArrayValue('id', $record['fields'], '') : '';
            if (empty($id)) {
                throw new Exception("Identifying field 'id' can not be empty for retrieve record [$key] request.");
            }
            $ids[] = $id;
        }
        $idList = implode(',', $ids);
        return $this->retrieveRecordsByIds($table, $idList, $return_fields, $extras);
    }

    /**
     * @param $table
     * @param $record
     * @param string $return_fields
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecord($table, $record, $return_fields = '', $extras = null)
    {
        $id = (isset($record['fields'])) ? Utilities::getArrayValue('id', $record['fields'], '') : '';
        return $this->retrieveRecordById($table, $id, $return_fields, $extras);
    }

    /**
     * @param $table
     * @param string $return_fields
     * @param string $filter
     * @param int $limit
     * @param string $order
     * @param int $offset
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByFilter($table, $return_fields = '', $filter = '', $limit = 0, $order = '', $offset = 0, $extras = null)
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('read', 'system', $table);
        $model = static::getResourceModel($table);
        $return_fields = $model->getRetrievableAttributes($return_fields);

        try {
            $command = new CDbCriteria();
            //$command->select = $return_fields;
            if (!empty($filter)) {
                $command->condition = $filter;
            }
            if (!empty($order)) {
                $command->order = $order;
            }
            if ($offset > 0) {
                $command->offset = $offset;
            }
            if ($limit > 0) {
                $command->limit = $limit;
            }
            else {
                // todo impose a limit to protect server
            }
            $out = array();
            $records = $model->findAll($command);
            foreach ($records as $record) {
                $pk = $record->primaryKey;
                $data = $record->getAttributes($return_fields);
                switch (strtolower($table)) {
                case 'app_group':
                    break;
                case 'role':
                    if (('*' == $return_fields) || (false !== array_search('services', $return_fields))) {
                        $permFields = array('service_id', 'service', 'component', 'read', 'create', 'update', 'delete');
                        $rsa = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid' => $pk));
                        $perms = array();
                        foreach ($rsa as $access) {
                            $perms[] = $access->getAttributes($permFields);
                        }
                        $data['services'] = $perms;
                    }
                    break;
                case 'service':
                    if (isset($data['type']) && !empty($data['type'])) {
                        switch (strtolower($data['type'])) {
                        case 'native':
                        case 'managed':
                            unset($data['base_url']);
                            unset($data['parameters']);
                            unset($data['headers']);
                            break;
                        case 'web':
                            break;
                        }
                    }
                    break;
                case 'user':
                    if (isset($extras['role'])) {
                        if (isset($data['role_id']) && !empty($data['role_id'])) {
                            $roleInfo = $this->retrieveRecordsByIds('role', $data['role_id'], 'id', '');
                            $data['role'] = $roleInfo[0];
                        }
                    }
                    break;
                }
                $out[] = array('fields' => $data);
            }

            $total = $model->count($command);
            return array('record' => $out, 'meta' => array('total' => $total));
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $return_fields
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByIds($table, $id_list, $return_fields = '', $extras = null)
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('read', 'system', $table);
        $ids = array_map('trim', explode(',', $id_list));
        $model = static::getResourceModel($table);
        $return_fields = $model->getRetrievableAttributes($return_fields);

        try {
            $records = $model->findAllByPk($ids);
            foreach ($records as $record) {
                $pk = $record->primaryKey;
                $key = array_search($pk, $ids);
                if (false === $key) {
                    throw new Exception('Bad returned data from query');
                }
                $data = $record->getAttributes($return_fields);
                switch (strtolower($table)) {
                case 'app_group':
                    break;
                case 'role':
                    if (('*' == $return_fields) || (false !== array_search('services', $return_fields))) {
                        $permFields = array('service_id', 'service', 'component', 'read', 'create', 'update', 'delete');
                        $rsa = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid' => $pk));
                        $perms = array();
                        foreach ($rsa as $access) {
                            $perms[] = $access->getAttributes($permFields);
                        }
                        $data['services'] = $perms;
                    }
                    break;
                case 'service':
                    if (isset($data['type']) && !empty($data['type'])) {
                        switch (strtolower($data['type'])) {
                        case 'native':
                        case 'managed':
                            unset($data['base_url']);
                            unset($data['parameters']);
                            unset($data['headers']);
                            break;
                        case 'web':
                            break;
                        }
                    }
                    break;
                case 'user':
                    if (isset($extras['role'])) {
                        if (isset($data['role_id']) && !empty($data['role_id'])) {
                            $roleInfo = $this->retrieveRecordsByIds('role', $data['role_id'], 'id', '');
                            $data['role'] = $roleInfo[0];
                        }
                    }
                    break;
                }
                $ids[$key] = array('fields' => $data);
            }
            foreach ($ids as $key=>$id) {
                if (!is_array($id)) {
                    $message = "A $table resource with id '$id' could not be found'";
                    $ids[$key] = array('error' => array('message' => $message, 'code' => ErrorCodes::NOT_FOUND));
                }
            }

            return array('record' => $ids);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id
     * @param string $return_fields
     * @param null $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordById($table, $id, $return_fields = '', $extras = null)
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('read', 'system', $table);
        $model = static::getResourceModel($table);
        $record = $model->findByPk($id);
        if (null === $record) {
            throw new Exception('Record not found.', ErrorCodes::NOT_FOUND);
        }
        try {
            $return_fields = $model->getRetrievableAttributes($return_fields);
            $data = $record->getAttributes($return_fields);
            switch (strtolower($table)) {
            case 'app_group':
                break;
            case 'role':
                if (('*' == $return_fields) || (false !== array_search('services', $return_fields))) {
                    $permFields = array('service_id', 'service', 'component', 'read', 'create', 'update', 'delete');
                    $rsa = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid' => $id));
                    $perms = array();
                    foreach ($rsa as $access) {
                        $perms[] = $access->getAttributes($permFields);
                    }
                    $data['services'] = $perms;
                }
                break;
            case 'service':
                if (isset($data['type']) && !empty($data['type'])) {
                    switch (strtolower($data['type'])) {
                    case 'native':
                    case 'managed':
                        unset($data['base_url']);
                        unset($data['parameters']);
                        unset($data['headers']);
                        break;
                    case 'web':
                        break;
                    }
                }
                break;
            case 'user':
                if (isset($extras['role'])) {
                    if (isset($data['role_id']) && !empty($data['role_id'])) {
                        $roleInfo = $this->retrieveRecordsByIds('role', $data['role_id'], 'id', '');
                        $data['role'] = $roleInfo[0];
                    }
                }
                break;
            }

            return array('fields' => $data);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}");
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
                $app = App::model()->findByPk($id);
                if (isset($app)) {
                    return $app->getAttribute('name');
                }

                return '';
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }
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
                $app = App::model()->find('name=:name', array(':name'=>$name));
                if (isset($app)) {
                    return $app->getPrimaryKey();
                }

                return '';
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }
    }

    /**
     * @return string
     */
    public function getCurrentAppId()
    {
        return $this->getAppIdFromName(SessionManager::getCurrentAppName());
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
        SessionManager::checkPermission('create', 'system', 'RoleAssign');
        // ids have preference, if blank, use names for users
        // override is true/false, true allows overriding an existing role assignment
        // i.e. move user to a different role,
        // false will return an error for that user that is already assigned to a role
        if (empty($role_id)) {
            throw new Exception('Role id can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (empty($user_ids)) {
            if (empty($user_names)) {
                throw new Exception('User ids and names can not both be empty.', ErrorCodes::BAD_REQUEST);
            }
            // find user ids
            try {
                $user_ids = $this->getUserIdsFromNames($user_names);
            }
            catch (Exception $ex) {
                throw new Exception("Error looking up users.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR);
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
        SessionManager::checkPermission('delete', 'system', 'RoleAssign');
        // ids have preference, if blank, use names for both role and users
        // use this to officially remove a user from the current app
        if (empty($role_id)) {
            throw new Exception('Role id can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if (empty($user_ids)) {
            if (empty($user_names)) {
                throw new Exception('User ids and names can not both be empty.', ErrorCodes::BAD_REQUEST);
            }
            // find user ids
            try {
                $user_ids = $this->getUserIdsFromNames($user_names);
            }
            catch (Exception $ex) {
                throw new Exception("Error looking up users.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR);
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
                    throw new Exception("No service name or id in role service access.", ErrorCodes::BAD_REQUEST);
                }
                if ('*' !== $serviceName) { // special 'All Services' designation
                    if (!empty($serviceId)) {
                        $temp = Service::model()->findByPk($serviceId);
                        if (!isset($temp)) {
                            throw new Exception("Invalid service id '$serviceId' in role service access.", ErrorCodes::BAD_REQUEST);
                        }
                        $serviceName = $temp->getAttribute('name');
                        $services[$key]['service'] = $serviceName;
                    }
                    elseif (!empty($serviceName)) {
                        $temp = Service::model()->find('name = :name', array(':name'=>$serviceName));
                        if (!isset($temp)) {
                            throw new Exception("Invalid service name '$serviceName' in role service access.", ErrorCodes::BAD_REQUEST);
                        }
                        $services[$key]['service_id'] = $temp->getPrimaryKey();
                    }
                }
                $test = $serviceName.'.'.$component;
                if (false !== array_search($test, $noDupes)) {
                    throw new Exception("Duplicated service and component combination '$serviceName $component' in role service access.", ErrorCodes::BAD_REQUEST);
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
            throw new Exception('App group id can not be empty.', ErrorCodes::BAD_REQUEST);
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

}
