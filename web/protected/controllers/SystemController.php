<?php
use CloudServicesPlatform\ServiceHandlers\ServiceHandler;
use CloudServicesPlatform\Storage\Database\PdoSqlDbSvc;
use CloudServicesPlatform\Utilities\Utilities;

class SystemController extends Controller
{
    // Members

    protected $modelName;
    protected $modelId;

    /**
     * @var PdoSqlDbSvc
     */
    protected $nativeDb;

    public function init()
   	{
        parent::init();
        try {
            $this->nativeDb = new PdoSqlDbSvc('df_');
        }
        catch (\Exception $ex) {
            throw new \Exception("Failed to create native database service.\n{$ex->getMessage()}");
        }
   	}

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array();
    }

    // Actions
    public function actionIndex()
    {
        try {
            $result = array(array('name' => 'App', 'label' => 'Applications'),
                            array('name' => 'AppGroup', 'label' => 'Application Groups'),
                            array('name' => 'Role', 'label' => 'Roles'),
                            array('name' => 'RoleAssign', 'label' => 'Role Assignment'),
                            array('name' => 'Service', 'label' => 'Services'),
                            array('name' => 'User', 'label' => 'Users'));
            $result = array('resource' => $result);
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    public function actionList()
    {
        try {
            $this->detectCommonParams();
            $result = array(array('name' => 'App', 'label' => 'Applications'),
                            array('name' => 'AppGroup', 'label' => 'Application Groups'),
                            array('name' => 'Role', 'label' => 'Roles'),
                            array('name' => 'RoleAssign', 'label' => 'Role Assignment'),
                            array('name' => 'Service', 'label' => 'Services'),
                            array('name' => 'User', 'label' => 'Users'));
            $result = array('resource' => $result);
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    public function actionGet()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            $extras = array();
            switch (strtolower($this->modelName)) {
            case '':
                $result = $this->actionList();
                break;
            case 'schema':
                if (empty($this->modelId)) {
                    $result = $this->describeSystem();
                }
                else {
                    $result = $this->describeTable($this->modelId);
                }
                break;
            case 'app':
            case 'appgroup':
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
                        $result = $this->retrieveSystemRecordsByIds($this->modelName, $ids, $fields, $extras);
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
                                $result = $this->retrieveSystemRecordsByIds($this->modelName, $ids, $fields, $extras);
                            }
                            elseif (!empty($records)) {
                                $result = $this->retrieveSystemRecords($this->modelName, $records, $fields, $extras);
                            }
                            else { // if not specified use empty filter
                                $filter = Utilities::getArrayValue('filter', $data, '');
                                $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                                $order = Utilities::getArrayValue('order', $data, '');
                                $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                                $result = $this->retrieveSystemRecordsByFilter($this->modelName, $fields, $filter, $limit, $order, $offset, $extras);
                            }
                        }
                        else {
                            $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : '';
                            $limit = (isset($_REQUEST['limit'])) ? intval($_REQUEST['limit']) : 0;
                            $order = (isset($_REQUEST['order'])) ? $_REQUEST['order'] : '';
                            $offset = (isset($_REQUEST['offset'])) ? intval($_REQUEST['offset']) : 0;
                            $result = $this->retrieveSystemRecordsByFilter($this->modelName, $fields, $filter, $limit, $order, $offset, $extras);
                        }
                    }
                }
                else { // single entity by id
                    $result = $this->retrieveSystemRecordsByIds($this->modelName, $this->modelId, $fields, $extras);
                }
                break;
            default:
                throw new \Exception("GET received to an unsupported system table named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    public function actionPost()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch (strtolower($this->modelName)) {
            case '':
            case 'schema':
                throw new \Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'appgroup':
            case 'role':
            case 'service':
            case 'user':
                $records = Utilities::getArrayValue('record', $data, null);
                if (empty($records)) {
                    // xml to array conversion leaves them in plural wrapper
                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                }
                if (empty($records)) {
                    throw new \Exception('No records in POST create request.');
                }
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->createSystemRecords($this->modelName, $records, $rollback, $fields);
                break;
            default:
                throw new \Exception("POST received to an unsupported system table named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    public function actionMerge()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch (strtolower($this->modelName)) {
            case '':
            case 'schema':
                throw new \Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'appgroup':
            case 'role':
            case 'service':
            case 'user':
                $records = Utilities::getArrayValue('record', $data, null);
                if (empty($records)) {
                    // xml to array conversion leaves them in plural wrapper
                    $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                }
                if (empty($records)) {
                    throw new \Exception('No records in POST update request.');
                }
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->updateSystemRecords($this->modelName, $records, $rollback, $fields);
                break;
            default:
                throw new \Exception("MERGE/PATCH received to an unsupported system table named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    public function actionDelete()
    {
        try {
            $this->detectCommonParams();
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = (isset($_REQUEST['fields'])) ? $_REQUEST['fields'] : '';
            switch (strtolower($this->modelName)) {
            case 'schema':
                throw new \Exception("System schema can not currently be modified through this API.");
                break;
            case 'app':
            case 'appgroup':
            case 'role':
            case 'service':
            case 'user':
                if (empty($this->modelId)) {
                    $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                    if (!empty($ids)) {
                        $result = $this->deleteSystemRecordsByIds($this->modelName, $ids, $fields);
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
                                $result = $this->deleteSystemRecordsByIds($this->modelName, $ids, $fields);
                            }
                            elseif (!empty($records)) {
                                $result = $this->deleteSystemRecords($this->modelName, $records, $fields);
                            }
                            else {
                                throw new \Exception("Id list or record sets containing Id fields required to delete $this->modelName records.");
                            }
                        }
                        else {
                            throw new \Exception("Id list or record sets containing Id fields required to delete $this->modelName records.");
                        }
                    }
                }
                else {
                    $result = $this->deleteSystemRecordsByIds($this->modelName, $this->modelId, $fields);
                }
                break;
            default:
                throw new \Exception("DELETE received to an unsupported system table named '$this->modelName'.");
                break;
            }
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function detectCommonParams()
    {
        $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
        $resource = (!empty($resource)) ? explode('/', $resource) : array();
        $this->modelName = (isset($resource[0])) ? $resource[0] : '';
        $this->modelId = (isset($resource[1])) ? $resource[1] : '';
    }

    public function getAppNameFromId($id)
    {
        if (!empty($id)) {
            try {
                $db = $this->nativeDb;
                $result = $db->retrieveSqlRecordsByIds('App', $id, 'Id', 'Name');
                if (count($result) > 0) {
                    return $result[0]['Name'];
                }
            }
            catch (\Exception $ex) {
                throw $ex;
            }
        }

        return '';
    }

    public function getAppIdFromName($name)
    {
        if (!empty($name)) {
            try {
                $db = $this->nativeDb;
                $result = $db->retrieveSqlRecordsByIds('App', $name, 'Name', 'Id');
                if (count($result) > 0) {
                    return $result[0]['Id'];
                }
            }
            catch (\Exception $ex) {
                throw $ex;
            }
        }

        return '';
    }

    public function getCurrentAppId()
    {
        return $this->getAppIdFromName(Utilities::getCurrentAppName());
    }

    protected function getUserIdsFromNames($user_names)
    {
        try {
            $db = $this->nativeDb;
            $result = $db->retrieveSqlRecordsByIds('User', "'$user_names'", 'UserName', 'Id');
            $userIds = '';
            foreach ($result as $item) {
                if (!empty($userIds)) {
                    $userIds .= ',';
                }
                $userIds .= $item['Id'];
            }

            return $userIds;
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function assignRole($role_id, $user_ids = '', $user_names = '', $override = false)
    {
        $this->checkPermission('create', 'RoleAssign');
        // ids have preference, if blank, use names for users
        // override is true/false, true allows overriding an existing role assignment
        // i.e. move user to a different role,
        // false will return an error for that user that is already assigned to a role
        if (empty($role_id)) {
            throw new \Exception('[InvalidParam]: Role id can not be empty.');
        }
        if (empty($user_ids)) {
            if (empty($user_names)) {
                throw new \Exception('[InvalidParam]: User ids and names can not both be empty.');
            }
            // find user ids
            try {
                $user_ids = $this->getUserIdsFromNames($user_names);
            }
            catch (\Exception $ex) {
                throw new \Exception("Error looking up users.\n{$ex->getMessage()}");
            }
        }

        $user_ids = implode(',', array_map('trim', explode(',', $user_ids)));
        try {
            $db = $this->nativeDb;
            $users = $db->retrieveSqlRecordsByIds('User', $user_ids, 'Id', 'RoleIds,Id');
            foreach ($users as $key => $user) {
                $users[$key]['RoleIds'] = $role_id;
            }
            $db->updateSqlRecords('User', $users, 'Id', false, 'Id');
        }
        catch (\Exception $ex) {
            throw new \Exception("Error updating users.\n{$ex->getMessage()}");
        }
    }

    public function unassignRole($role_id, $user_ids = '', $user_names = '')
    {
        $this->checkPermission('delete', 'RoleAssign');
        // ids have preference, if blank, use names for both role and users
        // use this to officially remove a user from the current app
        if (empty($role_id)) {
            throw new \Exception('[InvalidParam]: Role id can not be empty.');
        }
        if (empty($user_ids)) {
            if (empty($user_names)) {
                throw new \Exception('[InvalidParam]: User ids and names can not both be empty.');
            }
            // find user ids
            try {
                $user_ids = $this->getUserIdsFromNames($user_names);
            }
            catch (\Exception $ex) {
                throw new \Exception("Error looking up users.\n{$ex->getMessage()}");
            }
        }

        $user_ids = implode(',', array_map('trim', explode(',', $user_ids)));
        try {
            $db = $this->nativeDb;
            $users = $db->retrieveSqlRecordsByIds('User', $user_ids, 'Id', 'RoleIds,Id');
            foreach ($users as $key => $user) {
                $userRoleId = trim($user['RoleIds']);
                if ($userRoleId === $role_id) {
                    $users[$key]['RoleIds'] = '';
                }
            }
            $db->updateSqlRecords('User', $users, 'Id', false, 'Id');
        }
        catch (\Exception $ex) {
            throw new \Exception("Error updating users.\n{$ex->getMessage()}");
        }
    }

    protected function assignServiceAccess($role_id, $services = array())
    {
        $db = $this->nativeDb;
        // get any pre-existing access records
        $old = $db->retrieveSqlRecordsByFilter('RoleServiceAccess', 'Id', "RoleId = '$role_id'");
        unset($old['total']);
        // create a new access record for each service
        $noDupes = array();
        if (!empty($services)) {
            foreach ($services as $key=>$sa) {
                $services[$key]['roleId'] = $role_id;
                // validate service
                $component = Utilities::getArrayValue('component', $sa);
                $serviceName = Utilities::getArrayValue('service', $sa);
                $serviceId = Utilities::getArrayValue('serviceId', $sa);
                if (empty($serviceName) && empty($serviceId)) {
                    throw new \Exception("No service name or id in role service access.");
                }
                if ('*' !== $serviceName) { // special 'All Services' designation
                    if (!empty($serviceId)) {
                        $temp = $db->retrieveSqlRecordsByIds('Service', $serviceId, 'Id', 'Id,Name');
                        if ((count($temp) > 0) && isset($temp[0]['Name']) && !empty($temp[0]['Name'])) {
                            $serviceName = $temp[0]['Name'];
                            $services[$key]['service'] = $serviceName;
                        }
                        else {
                            throw new \Exception("Invalid service id '$serviceId' in role service access.");
                        }
                    }
                    elseif (!empty($serviceName)) {
                        $temp = $db->retrieveSqlRecordsByIds('Service', $serviceName, 'Name', 'Id,Name');
                        if ((count($temp) > 0) && isset($temp[0]['Id']) && !empty($temp[0]['Id'])) {
                            $services[$key]['serviceid'] = $temp[0]['Id'];
                        }
                        else {
                            throw new \Exception("Invalid service name '$serviceName' in role service access.");
                        }
                    }
                }
                $test = $serviceName.'.'.$component;
                if (false !== array_search($test, $noDupes)) {
                    throw new \Exception("Duplicated service and component combination '$serviceName $component' in role service access.");
                }
                $noDupes[] = $test;
            }
            $db->createSqlRecords('RoleServiceAccess', $services);
        }
        if ((count($old) > 0) && isset($old[0]['Id'])) {
            // delete any pre-existing access records
            $db->deleteSqlRecords('RoleServiceAccess', $old, 'Id');
        }
    }

    protected function validateUniqueSystemName($table, $fields, $for_update = false)
    {
        $id = Utilities::getArrayValue('id', $fields, '');
        if ($for_update && empty($id)) {
            throw new \Exception("The Id field for $table can not be empty for updates.");
        }
        // make sure it is named
        $nameField = 'Name';
        if (0 == strcasecmp('User', $table)) {
            $nameField = 'UserName';
        }
        $name = Utilities::getArrayValue($nameField, $fields, '');
        if (empty($name)) {
            if ($for_update) {
                return; // no need to check
            }
            throw new \Exception("The $nameField field for $table can not be empty.");
        }
        if ($for_update && (0 == strcasecmp('App', $table))) {
            throw new \Exception("Application names can not change. Change the label instead.");
        }
        $appId = '';
        if (0 == strcasecmp('Role', $table)) {
            $appId = Utilities::getArrayValue('AppIds', $fields, '');
            if (empty($appId) && $for_update) {
                // get the appId from the db for this role id
                try {
                    $db = $this->nativeDb;
                    $result = $db->retrieveSqlRecordsByIds('Role', $id, 'Id', 'AppIds');

                    if (count($result) > 0) {
                        $appId = (isset($result[0]['AppIds'])) ? $result[0]['AppIds'] : '';
                    }
                }
                catch (\Exception $ex) {
                    throw new \Exception("A Role with this id does not exist.");
                }
            }
            // make sure it is unique
            try {
                $db = $this->nativeDb;
                $result = $db->retrieveSqlRecordsByFilter('Role', 'Id', "Name='$name' AND AppIds='$appId'", 1);
                unset($result['total']);
                if (count($result) > 0) {
                    if ($for_update) {
                        if ($id != $result[0]['Id']) { // not self
                            throw new \Exception("A $table already exists with the $nameField '$name'.");
                        }
                    }
                    else {
                        throw new \Exception("A $table already exists with the $nameField '$name'.");
                    }
                }
            }
            catch (\Exception $ex) {
                throw $ex;
            }
        }
        else {
            // make sure it is unique
            try {
                $db = $this->nativeDb;
                $result = $db->retrieveSqlRecordsByFilter($table, 'Id', "$nameField = '$name'", 1);
                unset($result['total']);
                if (count($result) > 0) {
                    if ($for_update) {
                        if ($id != $result[0]['Id']) { // not self
                            throw new \Exception("A $table already exists with the $nameField '$name'.");
                        }
                    }
                    else {
                        throw new \Exception("A $table already exists with the $nameField '$name'.");
                    }
                }
            }
            catch (\Exception $ex) {
                throw $ex;
            }
        }
    }

    protected function checkRetrievableSystemFields($table, $fields)
    {
        switch (strtolower($table)) {
        case 'user':
            if (empty($fields)) {
                $fields = 'Id,FullName,FirstName,LastName,UserName,Email,Phone,';
                $fields .= 'IsActive,IsSysAdmin,RoleIds,CreatedDate,CreatedById,LastModifiedDate,LastModifiedById';
            }
            else {
                $fields = Utilities::removeOneFromList($fields, 'Password', ',');
                $fields = Utilities::removeOneFromList($fields, 'SecurityAnswer', ',');
                $fields = Utilities::removeOneFromList($fields, 'ConfirmCode', ',');
            }
            break;
        default:
        }

        return $fields;
    }

    protected function validateUser(&$fields, $for_update = false)
    {
        $pwd = Utilities::getArrayValue('password', $fields, '');
        if (!empty($pwd)) {
            $fields['password'] = md5($pwd);
            $fields['confirmcode'] = 'y';
        }
        else {
            // autogenerate ?
        }
    }

    //-------- System Records Operations ---------------------
    // records is an array of field arrays

    protected function createSystemRecordLow($table, $record, $fields = '')
    {
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new \Exception('[InvalidParam]: There are no fields in the record set.');
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
                $fields = Utilities::addOnceToList($fields, 'Name', ',');
                $fields = Utilities::addOnceToList($fields, 'IsUrlExternal', ',');
                break;
            }

            // create DB record
            $fields = Utilities::addOnceToList($fields, 'Id', ',');
            $db = $this->nativeDb;
            $results = $db->createSqlRecord($table, $record['fields'], $fields);
            $id = $results[0]['Id'];

            // after record create
            switch (strtolower($table)) {
            case 'app':
                // need name and isUrlExternal to create app directory in storage
                if (isset($results[0]['IsUrlExternal'])) {
                    $isExternal = Utilities::boolval($results[0]['IsUrlExternal']);
                    if (!$isExternal) {
                        $appSvc = ServiceHandler::getInstance()->getServiceObject('App');
                        if ($appSvc) {
                            $appSvc->createApp($results[0]['Name']);
                        }
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
                    catch (\Exception $ex) {
                        throw $ex;
                    }
                }
                if (isset($record['fields']['services'])) {
                    try {
                        $services = $record['fields']['services'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (\Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }

            return array('fields' => (isset($results[0]) ? $results[0] : $results));
        }
        catch (\Exception $ex) {
            // need to delete the above table entry and clean up
            if (isset($id) && !empty($id)) {
                $this->nativeDb->deleteSqlRecordsByIds($table, $id, 'Id', false, 'Id,Name');
            }
            throw $ex;
        }
    }

    public function createSystemRecords($table, $records, $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('create', $table);
        if (!isset($records) || empty($records)) {
            throw new \Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (!isset($records[0])) { // isArrayNumeric($records)
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $out = array();
        foreach ($records as $record) {
            try {
                $out[] = $this->createSystemRecordLow($table, $record, $fields);
            }
            catch (\Exception $ex) {
                $out[] = array('fault' => array('faultString' => $ex->getMessage(),
                                                'faultCode' => 'RequestFailed'));
            }
        }

        return array('record' => $out);
    }

    protected function updateSystemRecordLow($table, $record, $fields)
    {
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new \Exception('[InvalidParam]: There are no fields in the record set.');
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
                $fields = Utilities::addOnceToList($fields, 'Name', ',');
                break;
            }
            $id = Utilities::getArrayValue('Id', $record['fields'], '');
            if (empty($id)) {
                throw new \Exception("Identifying field 'Id' can not be empty for update request.");
            }
            $record['fields'] = Utilities::removeOneFromArray('Id', $record['fields']);

            $db = $this->nativeDb;
            $results = $db->updateSqlRecordsByIds($table, $record['fields'], $id, 'Id', false, $fields);

            // after record update
            switch (strtolower($table)) {
            case 'app':
                // need name and isExternal to create app directory in storage
                $isUrlExternal = Utilities::getArrayValue('IsUrlExternal', $record, null);
                if (isset($isUrlExternal)) {
                    $name = (isset($results[0]['Name'])) ? $results[0]['Name'] : '';
                    if (!empty($name)) {
                        if (!Utilities::boolval($isUrlExternal)) {
                            $appSvc = ServiceHandler::getInstance()->getServiceObject('App');
                            if ($appSvc) {
                                if (!$appSvc->appExists($name)) {
                                    $appSvc->createApp($name);
                                }
                            }
                        }
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
                    catch (\Exception $ex) {
                        throw $ex;
                    }
                }
                if (isset($record['fields']['services'])) {
                    try {
                        $services = $record['fields']['services'];
                        $this->assignServiceAccess($id, $services);
                    }
                    catch (\Exception $ex) {
                        throw $ex;
                    }
                }
                break;
            }

            return array('fields' => (isset($results[0]) ? $results[0] : $results));
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function updateSystemRecords($table, $records, $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('update', $table);
        if (!isset($records) || empty($records)) {
            throw new \Exception('[InvalidParam]: There are no record sets in the request.');
        }

        if (!isset($records[0])) {
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $out = array();
        foreach ($records as $record) {
            try {
                $out[] = $this->updateSystemRecordLow($table, $record, $fields);
            }
            catch (\Exception $ex) {
                $out[] = array('fault' => array('faultString' => $ex->getMessage(),
                                                'faultCode' => 'RequestFailed'));
            }
        }

        return array('record' => $out);
    }

    public function updateSystemRecordById($table, $record, $fields = '')
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('update', $table);

        try {
            $result = $this->updateSystemRecordLow($table, $record, $fields);

            return (isset($result[0]) ? $result[0] : $result);
        }
        catch (\Exception $ex) {
            throw new \Exception("Error updating $table records.\n{$ex->getMessage()}");
        }
    }

    protected function preDeleteUsers($id_list)
    {
        try {
            $currUser = Utilities::getCurrentUserId();
            $db = $this->nativeDb;
            $ids = array_map('trim', explode(',', $id_list));
            foreach ($ids as $id) {
                if ($currUser === $id) {
                    throw new \Exception("The current logged in user with Id '$id' can not be deleted.");
                }
            }
            // todo check and make sure this is not the last admin user
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    protected function preDeleteRoles($id_list)
    {
        try {
            $currentRole = Utilities::getCurrentRoleId();
            $ids = array_map('trim', explode(',', $id_list));
            if (false !== array_search($currentRole, $ids)) {
                throw new \Exception("Your current role with Id '$currentRole' can not be deleted.");
            }
            $db = $this->nativeDb;
            foreach ($ids as $id) {
                // clean up User.RoleIds pointing here
                $result = $db->retrieveSqlRecordsByFilter('User', 'Id,RoleIds', "RoleIds = '$id'");
                $total = (isset($result['total'])) ? $result['total'] : '';
                unset($result['total']);
                if (!empty($result)) {
                    foreach ($result as $key => $userInfo) {
                        $result[$key]['RoleIds'] = '';
                    }
                    $db->updateSqlRecords('User', $result, 'Id');
                }
                // Clean out the RoleServiceAccess for this role
                $db->deleteSqlRecordsByFilter('RoleServiceAccess', "RoleId = '$id'");
            }
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    protected function preDeleteApps($id_list)
    {
        try {
            $currApp = Utilities::getCurrentAppName();
            $db = $this->nativeDb;
            $result = $db->retrieveSqlRecordsByIds('App', $id_list, 'Id', 'Id,Name');
            foreach ($result as $appInfo) {
                if (!is_array($appInfo) || empty($appInfo)) {
                    throw new \Exception("One of the application ids is invalid.");
                }
                $name = $appInfo['Name'];
                if ($currApp === $name) {
                    throw new \Exception("The currently running application '$name' can not be deleted.");
                }
            }
        }
        catch (\Exception $ex) {
            throw $ex;
        }
        try {
            $store = ServiceHandler::getInstance()->getServiceObject('App');
            foreach ($result as $appInfo) {
                $id = $appInfo['Id'];
                // delete roles - which need cleaning of users first
                $roles = $db->retrieveSqlRecordsByFilter('Role', "Id", "AppIds='$id'");
                unset($roles['total']);
                $roleIdList = '';
                foreach ($roles as $role) {
                    $roleIdList .= (!empty($roleIdList)) ? ',' . $role['Id'] : $role['Id'];
                }
                if (!empty($roleIdList)) {
                    $this->deleteSystemRecordsByIds('Role', $roleIdList);
                }
                // remove file storage
                $name = $appInfo['Name'];
                $store->deleteApp($name);
            }
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    protected function preDeleteAppGroups($id_list)
    {
        try {
            $db = $this->nativeDb;
            $ids = array_map('trim', explode(',', $id_list));
            foreach ($ids as $id) {
                // %,$id,% is more accurate but in case of slopply updating by client, filter for %$id%
                $result = $db->retrieveSqlRecordsByFilter('App', 'Id,AppGroupIds', "AppGroupIds like '%$id%'");
                $total = (isset($result['total'])) ? $result['total'] : '';
                unset($result['total']);
                foreach ($result as $key => $appInfo) {
                    $groupIds = (isset($appInfo['AppGroupIds'])) ? $appInfo['AppGroupIds'] : '';
                    $groupIds = trim($groupIds, ','); // in case of sloppy updating
                    if (false === stripos(",$groupIds,", ",$id,")) {
                        unset($result[$key]);
                        continue;
                    }
                    $groupIds = str_ireplace(",$id,", '', ",$groupIds,");
                    $result[$key]['RoleIds'] = $groupIds;
                }
                $result = array_values($result);
                if (!empty($result)) {
                    $db->updateSqlRecords('App', $result, 'Id');
                }
            }
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function deleteSystemRecords($table, $records, $rollback = false, $fields = '')
    {
        if (!isset($records) || empty($records)) {
            throw new \Exception('[InvalidParam]: There are no record sets in the request.');
        }

        if (!isset($records[0])) {
            // conversion from xml can pull single record out of array format
            $records = array($records);
        }
        $idList = '';
        foreach ($records as $record) {
            if (!isset($record['fields']) || empty($record['fields'])) {
                throw new \Exception('[InvalidParam]: There are no fields in the record set.');
            }
            if (!empty($idList)) $idList .= ',';
            $idList .= $record['fields']['Id'];
        }

        return $this->deleteSystemRecordsByIds($table, $idList, $fields);
    }

    public function deleteSystemRecordsByIds($table, $id_list, $fields = '')
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('delete', $table);

        try {
            switch (strtolower($table)) {
            case "appgroup":
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

            $db = $this->nativeDb;
            $results = $db->deleteSqlRecordsByIds($table, $id_list, 'Id', false, $fields);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('fault' => array('faultString' => $result,
                                                    'faultCode' => 'RequestFailed'));
                }
            }

            return array('record' => $out);
        }
        catch (\Exception $ex) {
            throw new \Exception("Error deleting $table records.\n{$ex->getMessage()}");
        }
    }

    public function retrieveSystemRecords($table, $records, $fields = '', $extras = null)
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('read', $table);
        $fields = $this->checkRetrievableSystemFields($table, $fields);

        try {
            $db = $this->nativeDb;
            $results = $db->retrieveSqlRecords($table, $records, 'Id', $fields);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    switch (strtolower($table)) {
                    case 'appgroup':
                        break;
                    case 'role':
                        if ((isset($result['Id']) && !empty($result['Id'])) &&
                            (empty($fields) || (false !== stripos($fields, 'Services')))) {
                            $permFields = 'ServiceId,Service,Component,Read,Create,Update,Delete';
                            $permQuery = "RoleId='" . $result['Id'] . "'";
                            $perms = $db->retrieveSqlRecordsByFilter('RoleServiceAccess', $permFields, $permQuery, 0, 'Service');
                            unset($perms['total']);
                            $result['Services'] = $perms;
                        }
                        break;
                    case 'service':
                        if (isset($result['Type']) && !empty($result['Type'])) {
                            switch (strtolower($result['Type'])) {
                            case 'native':
                            case 'managed':
                                unset($result['BaseUrl']);
                                unset($result['ParamList']);
                                unset($result['HeaderList']);
                                break;
                            case 'web':
                                break;
                            }
                        }
                        break;
                    case 'user':
                        if (isset($extras['role'])) {
                            if (isset($result['RoleId']) && !empty($result['RoleId'])) {
                                $roleInfo = $this->retrieveSystemRecordsByIds('Role', $result['RoleId'], 'Id', '');
                                $result['Role'] = $roleInfo[0];
                            }
                        }
                        break;
                    }
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('fault' => array('faultString' => $result,
                                                    'faultCode' => 'RequestFailed'));
                }
            }

            return array('record' => $out);
        }
        catch (\Exception $ex) {
            throw new \Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

    public function retrieveSystemRecordsByFilter($table, $fields = '', $filter = '', $limit = 0, $order = '', $offset = 0, $extras = null)
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('read', $table);
        $fields = $this->checkRetrievableSystemFields($table, $fields);

        try {
            $db = $this->nativeDb;
            $results = $db->retrieveSqlRecordsByFilter($table, $fields, $filter, $limit, $order, $offset);
            $total = (isset($results['total'])) ? $results['total'] : '';
            unset($results['total']);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    switch (strtolower($table)) {
                    case 'appgroup':
                        break;
                    case 'role':
                        if ((isset($result['Id']) && !empty($result['Id'])) &&
                            (empty($fields) || (false !== stripos($fields, 'Services')))) {
                            $permFields = 'ServiceId,Service,Component,Read,Create,Update,Delete';
                            $permQuery = "RoleId='" . $result['Id'] . "'";
                            $perms = $db->retrieveSqlRecordsByFilter('RoleServiceAccess', $permFields, $permQuery, 0, 'Service');
                            unset($perms['total']);
                            $result['Services'] = $perms;
                        }
                        break;
                    case 'service':
                        if (isset($result['Type']) && !empty($result['Type'])) {
                            switch (strtolower($result['Type'])) {
                            case 'native':
                            case 'managed':
                                unset($result['BaseUrl']);
                                unset($result['ParamList']);
                                unset($result['HeaderList']);
                                break;
                            case 'web':
                                break;
                            }
                        }
                        break;
                    case 'user':
                        if (isset($extras['role'])) {
                            if (isset($result['RoleId']) && !empty($result['RoleId'])) {
                                $roleInfo = $this->retrieveSystemRecordsByIds('Role', $result['RoleId'], 'Id', '');
                                $result['Role'] = $roleInfo[0];
                            }
                        }
                        break;
                    }
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('fault' => array('faultString' => $result,
                                                    'faultCode' => 'RequestFailed'));
                }
            }

            return array('record' => $out, 'meta' => array('total' => $total));
        }
        catch (\Exception $ex) {
            throw new \Exception("Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}");
        }
    }

    public function retrieveSystemRecordsByIds($table, $id_list, $fields = '', $extras = null)
    {
        if (empty($table)) {
            throw new \Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('read', $table);
        $fields = $this->checkRetrievableSystemFields($table, $fields);

        try {
            $db = $this->nativeDb;
            $results = $db->retrieveSqlRecordsByIds($table, $id_list, 'Id', $fields);
            $out = array();
            foreach ($results as $result) {
                if (empty($result) || is_array($result)) {
                    switch (strtolower($table)) {
                    case 'appgroup':
                        break;
                    case 'role':
                        if ((isset($result['Id']) && !empty($result['Id'])) &&
                            (empty($fields) || (false !== stripos($fields, 'Services')))) {
                            $permFields = 'ServiceId,Service,Component,Read,Create,Update,Delete';
                            $permQuery = "RoleId='" . $result['Id'] . "'";
                            $perms = $db->retrieveSqlRecordsByFilter('RoleServiceAccess', $permFields, $permQuery, 0, 'Service');
                            unset($perms['total']);
                            $result['Services'] = $perms;
                        }
                        break;
                    case 'service':
                        if (isset($result['Type']) && !empty($result['Type'])) {
                            switch (strtolower($result['Type'])) {
                            case 'native':
                            case 'managed':
                                unset($result['BaseUrl']);
                                unset($result['ParamList']);
                                unset($result['HeaderList']);
                                break;
                            case 'web':
                                break;
                            }
                        }
                        break;
                    case 'user':
                        if (isset($extras['role'])) {
                            if (isset($result['RoleId']) && !empty($result['RoleId'])) {
                                $roleInfo = $this->retrieveSystemRecordsByIds('Role', $result['RoleId'], 'Id', '');
                                $result['Role'] = $roleInfo[0];
                            }
                        }
                        break;
                    }
                    $out[] = array('fields' => $result);
                }
                else { // error
                    $out[] = array('fault' => array('faultString' => $result,
                                                    'faultCode' => 'RequestFailed'));
                }
            }

            return array('record' => $out);
        }
        catch (\Exception $ex) {
            throw new \Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

    public function describeSystem()
    {
        $tables = self::SYSTEM_TABLES;
        try {
            $db = $this->nativeDb;
            return $db->describeDatabase($tables, '');
        }
        catch (\Exception $ex) {
            throw new \Exception("Error describing system tables.\n{$ex->getMessage()}");
        }
    }

    public function describeTable($table)
    {
        try {
            $db = $this->nativeDb;
            return $db->describeTable($table);
        }
        catch (\Exception $ex) {
            throw new \Exception("Error describing database table '$table'.\n{$ex->getMessage()}");
        }
    }

}