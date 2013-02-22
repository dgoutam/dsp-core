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
    const INTERNAL_TABLES = 'config,label,role_service_access,session,app_to_app_group,app_to_role,app_to_service';

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
            $config = null;
            $oldVersion = '';
            if (DbUtilities::doesTableExist(Yii::app()->db, 'config')) {
                $config = Config::model()->find();
                if (isset($config)) {
                    $oldVersion = $config->getAttribute('db_version');
                }
            }
            // create system tables
            $tables = Utilities::getArrayValue('table', $contents);
            if (empty($tables)) {
                throw new \Exception("No default system schema found.");
            }
            $result = DbUtilities::createTables(Yii::app()->db, $tables, true, true, false);

            // setup session stored procedure
            $command = Yii::app()->db->createCommand();
//            $query = 'SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
//                      WHERE ROUTINE_TYPE="PROCEDURE"
//                          AND ROUTINE_SCHEMA="dreamfactory"
//                          AND ROUTINE_NAME="UpdateOrInsertSession";';
//            $result = $db->singleSqlQuery($query);
//            if ((empty($result)) || !isset($result[0]['ROUTINE_NAME'])) {
            switch (DbUtilities::getDbDriverType(Yii::app()->db)) {
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
            if (!isset($config)) {
                $config = new Config;
            }
            $config->db_version = $version;
            $config->save();
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
                foreach ($services as $service) {
                    $obj = new Service;
                    $obj->setAttributes($service);
                    if (!$obj->save()) {
                        $msg = '';
                        if ($obj->hasErrors()) {
                            foreach ($obj->errors as $error) {
                                $msg .= implode(PHP_EOL, $error);
                            }
                        }
                        error_log(print_r($obj->errors, true));
                        throw new Exception("Failed to create services.\n$msg", ErrorCodes::INTERNAL_SERVER_ERROR);
                    }
                }
            }
            $result = App::model()->findAll();
            if (empty($result)) {
                $apps = Utilities::getArrayValue('app', $contents);
                if (!empty($apps)) {
                    foreach ($apps as $app) {
                        $obj = new App;
                        $obj->setAttributes($app);
                        if (!$obj->save()) {
                            $msg = '';
                            if ($obj->hasErrors()) {
                                foreach ($obj->errors as $error) {
                                    $msg .= implode(PHP_EOL, $error);
                                }
                            }
                            error_log(print_r($obj->errors, true));
                            throw new Exception("Failed to create apps.\n$msg", ErrorCodes::INTERNAL_SERVER_ERROR);
                        }
                    }
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
                $fields = Utilities::getArrayValue('fields', $_REQUEST, '*');
                $extras = array();
                $related = Utilities::getArrayValue('related', $_REQUEST, '');
                if (!empty($related)) {
                    $related = array_map('trim', explode(',', $related));
                    foreach ($related as $relative) {
                        $extraFields = Utilities::getArrayValue($relative . '_fields', $_REQUEST, '*');
                        $extraOrder = Utilities::getArrayValue($relative . '_order', $_REQUEST, '');
                        $extras[] = array('name'=>$relative, 'fields'=>$extraFields, 'order'=> $extraOrder);
                    }
                }


                if (empty($this->modelId)) {
                    $ids = Utilities::getArrayValue('ids', $_REQUEST, '');
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
                                    // for single record and no id field given, get records matching given fields
                                    $result = $this->retrieveRecords($this->modelName, $records, $fields, $extras);
                                }
                                else { // if not specified use filter
                                    $filter = Utilities::getArrayValue('filter', $data, '');
                                    $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                                    $order = Utilities::getArrayValue('order', $data, '');
                                    $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                                    $include_count = Utilities::boolval(Utilities::getArrayValue('include_count', $data, false));
                                    $result = $this->retrieveRecordsByFilter($this->modelName, $fields, $filter,
                                                                             $limit, $order, $offset, $include_count,
                                                                             $extras);
                                }
                            }
                        }
                        else {
                            $filter = Utilities::getArrayValue('filter', $_REQUEST, '');
                            $limit = intval(Utilities::getArrayValue('limit', $_REQUEST, 0));
                            $order = Utilities::getArrayValue('order', $_REQUEST, '');
                            $offset = intval(Utilities::getArrayValue('offset', $_REQUEST, 0));
                            $include_count = Utilities::boolval(Utilities::getArrayValue('include_count', $_REQUEST, false));
                            $result = $this->retrieveRecordsByFilter($this->modelName, $fields, $filter,
                                                                     $limit, $order, $offset, $include_count,
                                                                     $extras);
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
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                    if (empty($data)) {
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
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                            if (empty($data)) {
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
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                            if (empty($data)) {
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
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                            if (empty($data)) {
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
        case 'appgroup':
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
        case 'appgroup':
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
        if (empty($record)) {
            throw new Exception('There are no fields in the record to create.', ErrorCodes::BAD_REQUEST);
        }
        $model = static::getResourceModel($table);
        try {
            // create DB record
            $obj = static::getNewResource($table);
            $obj->setAttributes($record);
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

            $return_fields = $obj->getRetrievableAttributes($return_fields);
            $data = $obj->getAttributes($return_fields);
            // after record create
            // old relations
            switch (strtolower($table)) {
            case 'app_group':
                if (isset($record['app_ids'])) {
                    $appIds = $record['app_ids'];
                    $this->assignAppGroups($id, $appIds);
                }
                break;
            case 'role':
                if (isset($record['users']['assign'])) {
                    $users = $record['users']['assign'];
                    $override = Utilities::boolval(Utilities::getArrayValue('override', $record['users'], false));
                    $this->assignRole($id, $users, $override);
                }
                if (isset($record['accesses'])) {
                    try {
                        $services = $record['accesses'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }
            // new relations
            switch (strtolower($table)) {
            case 'app':
                if (isset($record['app_groups'])) {
                    $this->assignManyToOneByMap($table, $id, 'app_group', 'app_to_app_group', 'app_id', 'app_group_id', $record['app_groups']);
                }
                if (isset($record['roles'])) {
                    $this->assignManyToOneByMap($table, $id, 'role', 'app_to_role', 'app_id', 'role_id', $record['roles']);
                }
                break;
            case 'app_group':
                if (isset($record['apps'])) {
                    $this->assignManyToOneByMap($table, $id, 'app', 'app_to_app_group', 'app_group_id', 'app_id', $record['apps']);
                }
                break;
            case 'role':
                if (isset($record['role_service_accesses'])) {
                    $this->assignRoleServiceAccesses($id, $record['role_service_accesses']);
                }
                if (isset($record['apps'])) {
                    $this->assignManyToOneByMap($table, $id, 'app', 'app_to_role', 'role_id', 'app_id', $record['apps']);
                }
                if (isset($record['users'])) {
                    $this->assignManyToOne($table, $id, 'user', 'role_id', $record['users']);
                }
                if (isset($record['services'])) {
                    $this->assignManyToOneByMap($table, $id, 'service', 'role_service_access', 'role_id', 'service_id', $record['services']);
                }
                break;
            case 'service':
                if (isset($record['apps'])) {
                    $this->assignManyToOneByMap($table, $id, 'app', 'app_to_service', 'service_id', 'app_id', $record['apps']);
                }
                if (isset($record['roles'])) {
                    $this->assignManyToOneByMap($table, $id, 'role', 'role_service_access', 'service_id', 'role_id', $record['roles']);
                }
                break;
            }
            /*
            $relations = $obj->relations();
            foreach ($relations as $key=>$related) {
                if (isset($record[$key])) {
                    switch ($related[0]) {
                    case CActiveRecord::HAS_MANY:
                        $this->assignManyToOne($table, $id, $related[1], $related[2], $record[$key]);
                        break;
                    case CActiveRecord::MANY_MANY:
                        $this->assignManyToOneByMap($table, $id, $related[1], 'app_to_role', 'role_id', 'app_id', $record[$key]);
                        break;
                    }
                }
            }
            */

            return $data;
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
        if (empty($record)) {
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
            $record = Utilities::removeOneFromArray('id', $record);
            // todo move this to model rules
            if (isset($record['password'])) {
                $obj->setAttribute('password', CPasswordHelper::hashPassword($record['password']));
                unset($record['password']);
            }
            if (isset($record['security_answer'])) {
                $obj->setAttribute('security_answer', CPasswordHelper::hashPassword($record['security_answer']));
                unset($record['security_answer']);
            }
            $obj->setAttributes($record);
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
            // old relations
            switch (strtolower($table)) {
            case 'app_group':
                if (isset($record['app_ids'])) {
                    $appIds = $record['app_ids'];
                    $this->assignAppGroups($id, $appIds, true);
                }
                break;
            case 'role':
                if (isset($record['users']['assign'])) {
                    $users = $record['users']['assign'];
                    $override = Utilities::boolval(Utilities::getArrayValue('override', $record['users'], false));
                    $this->assignRole($id, $users, $override);
                }
                if (isset($record['users']['unassign'])) {
                    $users = $record['users']['unassign'];
                    $this->unassignRole($id, $users);
                }
                if (isset($record['accesses'])) {
                    try {
                        $services = $record['accesses'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }
            // new relations
            switch (strtolower($table)) {
            case 'app':
                if (isset($record['app_groups'])) {
                    $this->assignManyToOneByMap($table, $id, 'app_group', 'app_to_app_group', 'app_id', 'app_group_id', $record['app_groups']);
                }
                if (isset($record['roles'])) {
                    $this->assignManyToOneByMap($table, $id, 'role', 'app_to_role', 'app_id', 'role_id', $record['roles']);
                }
                break;
            case 'app_group':
                if (isset($record['apps'])) {
                    $this->assignManyToOneByMap($table, $id, 'app', 'app_to_app_group', 'app_group_id', 'app_id', $record['apps']);
                }
                break;
            case 'role':
                if (isset($record['role_service_accesses'])) {
                    $this->assignRoleServiceAccesses($id, $record['role_service_accesses']);
                }
                if (isset($record['apps'])) {
                    $this->assignManyToOneByMap($table, $id, 'app', 'app_to_role', 'role_id', 'app_id', $record['apps']);
                }
                if (isset($record['users'])) {
                    $this->assignManyToOne($table, $id, 'user', 'role_id', $record['users']);
                }
                if (isset($record['services'])) {
                    $this->assignManyToOneByMap($table, $id, 'service', 'role_service_access', 'role_id', 'service_id', $record['services']);
                }
                break;
            }
            /*
            $relations = $obj->relations();
            foreach ($relations as $key=>$related) {
                if (isset($record[$key])) {
                    switch ($related[0]) {
                    case CActiveRecord::HAS_MANY:
                        $this->assignManyToOne($table, $id, $related[1], $related[2], $record[$key]);
                        break;
                    case CActiveRecord::MANY_MANY:
                        $this->assignManyToOneByMap($table, $id, $related[1], 'app_to_role', 'role_id', 'app_id', $record[$key]);
                        break;
                    }
                }
            }
            */

            return $data;
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
                $id = Utilities::getArrayValue('id', $record, '');
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
        if (!isset($record) || empty($record)) {
            throw new Exception('There is no record in the request.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('update', 'system', $table);
        // todo this needs to use $model->getPrimaryKey()
        $id = Utilities::getArrayValue('id', $record, '');
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

            return $data;
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
            if (!isset($record) || empty($record)) {
                throw new Exception('There are no fields in the record set.', ErrorCodes::BAD_REQUEST);
            }
            $id = Utilities::getArrayValue('id', $record, '');
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
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $id = Utilities::getArrayValue('id', $record, '');
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
     * @param array $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecords($table, $records, $return_fields = '', $extras = array())
    {
        if (isset($records[0])) {
            // an array of records
            $ids = array();
            foreach ($records as $key => $record) {
                $id = Utilities::getArrayValue('id', $record, '');
                if (empty($id)) {
                    throw new Exception("Identifying field 'id' can not be empty for retrieve record [$key] request.");
                }
                $ids[] = $id;
            }
            $idList = implode(',', $ids);
            return $this->retrieveRecordsByIds($table, $idList, $return_fields, $extras);
        }
        else {
            // single record
            $id = Utilities::getArrayValue('id', $records, '');
            return $this->retrieveRecordById($table, $id, $return_fields, $extras);
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $return_fields
     * @param array $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecord($table, $record, $return_fields = '', $extras = array())
    {
        $id = Utilities::getArrayValue('id', $record, '');
        return $this->retrieveRecordById($table, $id, $return_fields, $extras);
    }

    /**
     * @param $table
     * @param string $return_fields
     * @param string $filter
     * @param int $limit
     * @param string $order
     * @param int $offset
     * @param bool $include_count
     * @param array $extras
     * @throws Exception
     * @return array
     */
    public function retrieveRecordsByFilter($table, $return_fields = '', $filter = '',
                                            $limit = 0, $order = '', $offset = 0, $include_count = false,
                                            $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('read', 'system', $table);
        $model = static::getResourceModel($table);
        $return_fields = $model->getRetrievableAttributes($return_fields);
        $relations = $model->relations();

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
            $records = $model->findAll($command);
            $out = array();
            foreach ($records as $record) {
                $data = $record->getAttributes($return_fields);
                if (!empty($extras)) {
                    $relatedData = array();
                    foreach ($extras as $extra) {
                        $extraName = $extra['name'];
                        if (!isset($relations[$extraName])) {
                            throw new Exception("Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST);
                        }
                        $extraFields = $extra['fields'];
                        $relatedRecords = $record->getRelated($extraName, true);
                        if (is_array($relatedRecords)) {
                            // an array of records
                            $tempData = array();
                            if (!empty($relatedRecords)) {
                                $relatedFields = $relatedRecords[0]->getRetrievableAttributes($extraFields);
                                foreach ($relatedRecords as $relative) {
                                    $tempData[] = $relative->getAttributes($relatedFields);
                                }
                            }
                        }
                        else {
                            $tempData = null;
                            if (isset($relatedRecords)) {
                                $relatedFields = $relatedRecords->getRetrievableAttributes($extraFields);
                                $tempData = $relatedRecords->getAttributes($relatedFields);
                            }
                        }
                        $relatedData[$extraName] = $tempData;
                    }
                    if (!empty($relatedData)) {
                        $data = array_merge($data, $relatedData);
                    }
                }
                // todo temp backward compatibility
                switch (strtolower($table)) {
                case 'role':
                    if (('*' == $return_fields) || (false !== array_search('accesses', $return_fields))) {
                        $permFields = array('service_id', 'service', 'component', 'read', 'create', 'update', 'delete');
                        $pk = $record->primaryKey;
                        $rsa = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid' => $pk));
                        $perms = array();
                        foreach ($rsa as $access) {
                            $perms[] = $access->getAttributes($permFields);
                        }
                        $data['accesses'] = $perms;
                    }
                    break;
                }

                $out[] = $data;
            }

            $results = array('record' => $out);
            if ($include_count) {
                $count = $model->count($command);
                $results['meta'] = array('count' => $count);
            }
            return $results;
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $return_fields
     * @param array $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByIds($table, $id_list, $return_fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('read', 'system', $table);
        $ids = array_map('trim', explode(',', $id_list));
        $model = static::getResourceModel($table);
        $return_fields = $model->getRetrievableAttributes($return_fields);
        $relations = $model->relations();

        try {
            $records = $model->findAllByPk($ids);
            if (empty($records)) {
                throw new Exception("No $table resources with ids '$id_list' could be found", ErrorCodes::NOT_FOUND);
            }
            foreach ($records as $record) {
                $pk = $record->primaryKey;
                $key = array_search($pk, $ids);
                if (false === $key) {
                    throw new Exception('Bad returned data from query');
                }
                $data = $record->getAttributes($return_fields);
                if (!empty($extras)) {
                    $relatedData = array();
                    foreach ($extras as $extra) {
                        $extraName = $extra['name'];
                        if (!isset($relations[$extraName])) {
                            throw new Exception("Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST);
                        }
                        $extraFields = $extra['fields'];
                        $relatedRecords = $record->getRelated($extraName, true);
                        if (is_array($relatedRecords)) {
                            // an array of records
                            $tempData = array();
                            if (!empty($relatedRecords)) {
                                $relatedFields = $relatedRecords[0]->getRetrievableAttributes($extraFields);
                                foreach ($relatedRecords as $relative) {
                                    $tempData[] = $relative->getAttributes($relatedFields);
                                }
                            }
                        }
                        else {
                            $tempData = null;
                            if (isset($relatedRecords)) {
                                $relatedFields = $relatedRecords->getRetrievableAttributes($extraFields);
                                $tempData = $relatedRecords->getAttributes($relatedFields);
                            }
                        }
                        $relatedData[$extraName] = $tempData;
                    }
                    if (!empty($relatedData)) {
                        $data = array_merge($data, $relatedData);
                    }
                }
                // todo temp backward compatibility
                switch (strtolower($table)) {
                case 'role':
                    if (('*' == $return_fields) || (false !== array_search('accesses', $return_fields))) {
                        $permFields = array('service_id', 'service', 'component', 'read', 'create', 'update', 'delete');
                        $pk = $record->primaryKey;
                        $rsa = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid' => $pk));
                        $perms = array();
                        foreach ($rsa as $access) {
                            $perms[] = $access->getAttributes($permFields);
                        }
                        $data['accesses'] = $perms;
                    }
                    break;
                }

                $ids[$key] = $data;
            }
            foreach ($ids as $key=>$id) {
                if (!is_array($id)) {
                    $message = "A $table resource with id '$id' could not be found.";
                    $ids[$key] = array('error' => array('message' => $message, 'code' => ErrorCodes::NOT_FOUND));
                }
            }

            return array('record' => $ids);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param $id
     * @param string $return_fields
     * @param array $extras
     * @return array
     * @throws Exception
     */
    public function retrieveRecordById($table, $id, $return_fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        SessionManager::checkPermission('read', 'system', $table);
        $model = static::getResourceModel($table);
        $return_fields = $model->getRetrievableAttributes($return_fields);
        $relations = $model->relations();
        $record = $model->findByPk($id);
        if (null === $record) {
            throw new Exception('Record not found.', ErrorCodes::NOT_FOUND);
        }
        try {
            $data = $record->getAttributes($return_fields);
            if (!empty($extras)) {
                $relatedData = array();
                foreach ($extras as $extra) {
                    $extraName = $extra['name'];
                    if (!isset($relations[$extraName])) {
                        throw new Exception("Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST);
                    }
                    $extraFields = $extra['fields'];
                    $relatedRecords = $record->getRelated($extraName, true);
                    if (is_array($relatedRecords)) {
                        // an array of records
                        $tempData = array();
                        if (!empty($relatedRecords)) {
                            $relatedFields = $relatedRecords[0]->getRetrievableAttributes($extraFields);
                            foreach ($relatedRecords as $relative) {
                                $tempData[] = $relative->getAttributes($relatedFields);
                            }
                        }
                    }
                    else {
                        $tempData = null;
                        if (isset($relatedRecords)) {
                            $relatedFields = $relatedRecords->getRetrievableAttributes($extraFields);
                            $tempData = $relatedRecords->getAttributes($relatedFields);
                        }
                    }
                    $relatedData[$extraName] = $tempData;
                }
                if (!empty($relatedData)) {
                    $data = array_merge($data, $relatedData);
                }
            }
            // todo temp backward compatibility
            switch (strtolower($table)) {
            case 'role':
                if (('*' == $return_fields) || (false !== array_search('accesses', $return_fields))) {
                    $permFields = array('service_id', 'service', 'component', 'read', 'create', 'update', 'delete');
                    $pk = $record->primaryKey;
                    $rsa = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid' => $pk));
                    $perms = array();
                    foreach ($rsa as $access) {
                        $perms[] = $access->getAttributes($permFields);
                    }
                    $data['accesses'] = $perms;
                }
                break;
            }

            return $data;
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
            $command = Yii::app()->db->createCommand();
            $result = $command->select('id')->from('user')->where(array('in', 'username', $user_names));
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
     * @param $role_id
     * @param array $accesses
     * @throws Exception
     * @return void
     */
    protected function assignRoleServiceAccesses($role_id, $accesses=array())
    {
        if (empty($role_id)) {
            throw new Exception('Role id can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $oldAccesses = RoleServiceAccess::model()->findAll('role_id = :rid', array(':rid'=>$role_id));
            foreach ($oldAccesses as $oldAccess) {
                $oldId = $oldAccess->primaryKey;
                $found = false;
                foreach ($accesses as $key=>$access) {
                    $id = Utilities::getArrayValue('id', $access, '');
                    if ($id == $oldId) {
                        // found it, make sure nothing needs to be updated
                        $newServiceId = Utilities::getArrayValue('service_id', $access, '');
                        $newComponent = Utilities::getArrayValue('component', $access, '');
                        $newRead = Utilities::getArrayValue('read', $access, '');
                        $newCreate = Utilities::getArrayValue('create', $access, '');
                        $newUpdate = Utilities::getArrayValue('update', $access, '');
                        $newDelete = Utilities::getArrayValue('delete', $access, '');
                        if (($oldAccess->service_id != $newServiceId))
                            $oldAccess->service_id = $newServiceId;
                        if (($oldAccess->component != $newComponent))
                            $oldAccess->component = $newComponent;
                        if (($oldAccess->read != $newRead))
                            $oldAccess->read = $newRead;
                        if (($oldAccess->create != $newCreate))
                            $oldAccess->create = $newCreate;
                        if (($oldAccess->update != $newUpdate))
                            $oldAccess->update = $newUpdate;
                        if (($oldAccess->delete != $newDelete))
                            $oldAccess->delete = $newDelete;
                        $oldAccess->save();
                        // keeping it, so remove it from the list, as this becomes adds
                        unset($accesses[$key]);
                        $found = true;
                        continue;
                    }
                }
                if (!$found) {
                    $oldAccess->delete();
                    continue;
                }
            }
            if (!empty($accesses)) {
                // add what is leftover
                foreach ($accesses as $access) {
                    $newAccess = new RoleServiceAccess;
                    if ($newAccess) {
                        $newAccess->setAttributes($access);
                        $newAccess->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error updating apps to app_group assignment.\n{$ex->getMessage()}");
        }
    }

    // generic assignments
    /**
     * @param string $one_table
     * @param string $one_id
     * @param string $many_table
     * @param string $many_field
     * @param array $many_records
     * @throws Exception
     * @return void
     */
    protected function assignManyToOne($one_table, $one_id, $many_table, $many_field, $many_records=array())
    {
        $oneModel = static::getResourceModel($one_table);
        $manyModel = static::getResourceModel($many_table);
        if (empty($one_id)) {
            throw new Exception("The $one_table id can not be empty.", ErrorCodes::BAD_REQUEST);
        }
        try {
            $manyObj = static::getNewResource($many_table);
            $pkField = $manyObj->tableSchema->primaryKey;
            $oldMany = $manyModel->findAll($many_field .' = :oid', array(':oid'=>$one_id));
            foreach ($oldMany as $old) {
                $oldId = $old->primaryKey;
                $found = false;
                foreach ($many_records as $key=>$item) {
                    $id = Utilities::getArrayValue($pkField, $item, '');
                    if ($id == $oldId) {
                        // found it, keeping it, so remove it from the list, as this becomes adds
                        unset($many_records[$key]);
                        $found = true;
                        continue;
                    }
                }
                if (!$found) {
                    $old->setAttribute($many_field, null);
                    $old->save();
                    continue;
                }
            }
            if (!empty($many_records)) {
                // add what is leftover
                foreach ($many_records as $item) {
                    $id = Utilities::getArrayValue($pkField, $item, '');
                    $assigned = $manyModel->findByPk($id);
                    if ($assigned) {
                        $assigned->setAttribute($many_field, $one_id);
                        $assigned->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error updating many to one assignment.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $one_table
     * @param $one_id
     * @param $many_table
     * @param $map_table
     * @param $one_field
     * @param $many_field
     * @param array $many_records
     * @throws Exception
     * @return void
     */
    protected function assignManyToOneByMap($one_table, $one_id, $many_table, $map_table, $one_field, $many_field, $many_records=array())
    {
        if (empty($one_id)) {
            throw new Exception("The $one_table id can not be empty.", ErrorCodes::BAD_REQUEST);
        }
        try {
            $manyObj = static::getNewResource($many_table);
            $pkManyField = $manyObj->tableSchema->primaryKey;
            $pkMapField = 'id';
            $maps = $this->nativeDb->retrieveSqlRecordsByFilter($map_table, $pkMapField.','.$many_field, "$one_field = '$one_id'");
            $toDelete = array();
            foreach ($maps as $map) {
                $manyId = Utilities::getArrayValue($many_field, $map, '');
                $id = Utilities::getArrayValue($pkMapField, $map, '');
                $found = false;
                foreach ($many_records as $key=>$item) {
                    $assignId = Utilities::getArrayValue($pkManyField, $item, '');
                    if ($assignId == $manyId) {
                        // found it, keeping it, so remove it from the list, as this becomes adds
                        unset($many_records[$key]);
                        $found = true;
                        continue;
                    }
                }
                if (!$found) {
                    $toDelete[] = $id;
                    continue;
                }
            }
            if (!empty($toDelete)) {
                $this->nativeDb->deleteSqlRecordsByIds($map_table, implode(',', $toDelete), $pkMapField);
            }
            if (!empty($many_records)) {
                $maps = array();
                foreach ($many_records as $item) {
                    $itemId = Utilities::getArrayValue($pkManyField, $item, '');
                    $maps[] = array($many_field=>$itemId, $one_field=>$one_id);
                }
                $this->nativeDb->createSqlRecords($map_table, $maps);
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error updating many to one map assignment.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

}
