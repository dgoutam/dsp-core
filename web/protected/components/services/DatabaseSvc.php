<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage DatabaseSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class DatabaseSvc extends CommonService implements iRestHandler
{

    // Members

    /**
     * @var
     */
    protected $tableName;
    /**
     * @var
     */
    protected $recordId;

    /**
     * @var PdoSqlDbSvc
     */
    protected $sqlDb;

    /**
     * Creates a new DatabaseSvc instance
     *
     * @param array $config
     * @throws InvalidArgumentException
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $dsn = Utilities::getArrayValue('dsn', $config, '');
        $user = Utilities::getArrayValue('user', $config, '');
        $pwd = Utilities::getArrayValue('pwd', $config, '');
        if (!empty($dsn))
        {
            $this->sqlDb = new PdoSqlDbSvc('', $dsn, $user, $pwd);
        }
        else {
            $this->sqlDb = new PdoSqlDbSvc();
        }
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        parent::__destruct();
        unset($this->sqlDb);
    }

    // Controller based methods

    /**
     * @throws Exception
     * @return array
     */
    public function actionGet()
    {
        $this->detectCommonParams();
        switch (strtolower($this->tableName)) {
        case '':
            // check for system tables and deny
            $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
            try {
                $result = $this->sqlDb->describeDatabase('', $sysTables);
                $result = array('resource' => $result['table']);
            }
            catch (Exception $ex) {
                throw new Exception("Error describing database tables.\n{$ex->getMessage()}");
            }
            break;
        default:
            // Most requests contain 'returned fields' parameter, all by default
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '*');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($this->recordId)) {
                $ids = Utilities::getArrayValue('ids', $_REQUEST, '');
                if (!empty($ids)) {
                    $result = $this->retrieveRecordsByIds($this->tableName, $ids, $idField, $fields);
                }
                else {
                    $data = Utilities::getPostDataAsArray();
                    if (!empty($data)) { // complex filters or large numbers of ids require post
                        $ids = Utilities::getArrayValue('ids', $data, '');
                        if (empty($idField)) {
                            $idField = Utilities::getArrayValue('id_field', $data, '');
                        }
                        if (!empty($ids)) {
                            $result = $this->retrieveRecordsByIds($this->tableName, $ids, $idField, $fields);
                        }
                        else {
                            $records = Utilities::getArrayValue('record', $data, null);
                            if (empty($records)) {
                                // xml to array conversion leaves them in plural wrapper
                                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                            }
                            if (!empty($records)) {
                                // passing records to have them updated with new or more values, id field required
                                $result = $this->retrieveRecords($this->tableName, $records, $idField, $fields);
                            }
                            elseif (isset($data['fields']) && !empty($data['fields'])) {
                                // passing record to have it updated with new or more values, id field required
                                $result = $this->retrieveRecord($this->tableName, $data, $idField, $fields);
                            }
                            else {
                                $filter = Utilities::getArrayValue('filter', $data, '');
                                $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                                $order = Utilities::getArrayValue('order', $data, '');
                                $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                                $result = $this->retrieveRecordsByFilter($this->tableName, $fields, $filter, $limit, $order, $offset);
                            }
                        }
                    }
                    else {
                        $filter = Utilities::getArrayValue('filter', $_REQUEST, '');
                        $limit = intval(Utilities::getArrayValue('limit', $_REQUEST, 0));
                        $order = Utilities::getArrayValue('order', $_REQUEST, '');
                        $offset = intval(Utilities::getArrayValue('offset', $_REQUEST, 0));
                        $result = $this->retrieveRecordsByFilter($this->tableName, $fields, $filter, $limit, $order, $offset);
                    }
                }
            }
            else { // single entity by id
                $result = $this->retrieveRecordById($this->tableName, $this->recordId, $idField, $fields);
            }
            break;
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionPost()
    {
        $this->detectCommonParams();
        $data = Utilities::getPostDataAsArray();
        switch (strtolower($this->tableName)) {
        case '':
            // batch support for multiple tables
            throw new Exception('Mutli-table batch request not yet implemented.');
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $records = Utilities::getArrayValue('record', $data, null);
            if (empty($records)) {
                // xml to array conversion leaves them in plural wrapper
                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
            }
            if (empty($records)) {
                $single = Utilities::getArrayValue('fields', $data, array());
                if (empty($single)) {
                    throw new Exception('No record in POST create request.', ErrorCodes::BAD_REQUEST);
                }
                $result = $this->createRecord($this->tableName, $data, $fields);
            }
            else {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->createRecords($this->tableName, $records, $rollback, $fields);
            }
            break;
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionPut()
    {
        $this->detectCommonParams();
        $data = Utilities::getPostDataAsArray();
        switch (strtolower($this->tableName)) {
        case '':
            // batch support for multiple tables
            throw new Exception('Mutli-table batch request not yet implemented.');
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($idField)) {
                $idField = Utilities::getArrayValue('id_field', $data, '');
            }
            if (empty($this->recordId)) {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                if (empty($ids)) {
                    $ids = Utilities::getArrayValue('ids', $data, '');
                }
                if (!empty($ids)) {
                    $result = $this->updateRecordsByIds($this->tableName, $ids, $data, $idField, $rollback, $fields);
                }
                else {
                    $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                    if (!isset($filter)) {
                        $filter = Utilities::getArrayValue('filter', $data, null);
                    }
                    if (isset($filter)) {
                        $result = $this->updateRecordsByFilter($this->tableName, $filter, $data, $fields);
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
                            $result = $this->updateRecord($this->tableName, $data, $idField, $fields);
                        }
                        else {
                            $result = $this->updateRecords($this->tableName, $records, $idField, $rollback, $fields);
                        }
                    }
                }
            }
            else {
                $result = $this->updateRecordById($this->tableName, $data, $this->recordId, $idField, $fields);
            }
            break;
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionMerge()
    {
        $this->detectCommonParams();
        $data = Utilities::getPostDataAsArray();
        switch (strtolower($this->tableName)) {
        case '':
            // batch support for multiple tables
            throw new Exception('Mutli-table batch request not yet implemented.');
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($idField)) {
                $idField = Utilities::getArrayValue('id_field', $data, '');
            }
            if (empty($this->recordId)) {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                if (empty($ids)) {
                    $ids = Utilities::getArrayValue('ids', $data, '');
                }
                if (!empty($ids)) {
                    $result = $this->updateRecordsByIds($this->tableName, $ids, $data, $idField, $rollback, $fields);
                }
                else {
                    $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                    if (!isset($filter)) {
                        $filter = Utilities::getArrayValue('filter', $data, null);
                    }
                    if (isset($filter)) {
                        $result = $this->updateRecordsByFilter($this->tableName, $filter, $data, $fields);
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
                            $result = $this->updateRecord($this->tableName, $data, $idField, $fields);
                        }
                        else {
                            $result = $this->updateRecords($this->tableName, $records, $idField, $rollback, $fields);
                        }
                    }
                }
            }
            else {
                $result = $this->updateRecordById($this->tableName, $data, $this->recordId, $idField, $fields);
            }
            break;
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionDelete()
    {
        $this->detectCommonParams();
        $data = Utilities::getPostDataAsArray();
        switch (strtolower($this->tableName)) {
        case '':
            // batch support for multiple tables
            throw new Exception('Mutli-table batch request not yet implemented.', ErrorCodes::BAD_REQUEST);
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($idField)) {
                $idField = Utilities::getArrayValue('id_field', $data, '');
            }
            if (empty($this->recordId)) {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                if (empty($ids)) {
                    $ids = Utilities::getArrayValue('ids', $data, '');
                }
                if (!empty($ids)) {
                    $result = $this->deleteRecordsByIds($this->tableName, $ids, $idField, $rollback, $fields);
                }
                else {
                    $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                    if (!isset($filter)) {
                        $filter = Utilities::getArrayValue('filter', $data, null);
                    }
                    if (isset($filter)) {
                        $result = $this->deleteRecordsByFilter($this->tableName, $filter, $fields);
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
                                throw new Exception('No record in DELETE request.', ErrorCodes::BAD_REQUEST);
                            }
                            $result = $this->deleteRecord($this->tableName, $data, $idField, $fields);
                        }
                        else {
                            $result = $this->deleteRecords($this->tableName, $records, $idField, $rollback, $fields);
                        }
                    }
                }
            }
            else {
                $result = $this->deleteRecordById($this->tableName, $this->recordId, $idField, $fields);
            }
            break;
        }

        return $result;
    }

    /**
     *
     */
    protected function detectCommonParams()
    {
        $resource = Utilities::getArrayValue('resource', $_GET, '');
        $resource = (!empty($resource)) ? explode('/', $resource) : array();
        $this->tableName = (isset($resource[0])) ? $resource[0] : '';
        $this->recordId = (isset($resource[1])) ? $resource[1] : '';
    }

    //-------- Table Records Operations ---------------------
    // records is an array of field arrays

    /**
     * @param $record
     * @return array
     */
    private static function removeFieldsLevel($record)
    {
        return (isset($record['fields'])) ? $record['fields'] : $record;
    }

    /**
     * @param $result
     * @return array
     */
    private static function addFieldsLevel($result)
    {
        if (empty($result) || is_array($result)) {
            return array('fields' => $result);
        }
        else { // error
            return array('error' => array('message' => $result, 'code' => 500));
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
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('create', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }

        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
            }
            $fields_array = array($records['fields']);
        }
        try {
            $results = $this->sqlDb->createSqlRecords($table, $fields_array, $rollback, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function createRecord($table, $record, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('create', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = array($record['fields']);
        try {
            $result = $this->sqlDb->createSqlRecords($table, $fields_array, false, $fields);

            return array('fields' => $result[0]);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $records
     * @param string $idField
     * @param bool $rollback
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecords($table, $records, $idField = '', $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('update', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
            }
            $fields_array = array($records['fields']);
        }
        try {
            $results = $this->sqlDb->updateSqlRecords($table, $fields_array, $idField, $rollback, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $idField
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecord($table, $record, $idField = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('update', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = array($record['fields']);
        try {
            $results = $this->sqlDb->updateSqlRecords($table, $fields_array, $idField, false, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $filter
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecordsByFilter($table, $record, $filter = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('update', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = $record['fields'];
        try {
            $results = $this->sqlDb->updateSqlRecordsByFilter($table, $fields_array, $filter, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\nquery: $filter\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $record
     * @param $id_list
     * @param string $idField
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecordsByIds($table, $record, $id_list, $idField = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('update', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = $record['fields'];
        try {
            $results = $this->sqlDb->updateSqlRecordsByIds($table, $fields_array, $id_list, $idField, false, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $record
     * @param $id
     * @param string $idField
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecordById($table, $record, $id, $idField = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('update', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = $record['fields'];
        try {
            $results = $this->sqlDb->updateSqlRecordsByIds($table, $fields_array, $id, $idField, false, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $records
     * @param string $idField
     * @param bool $rollback
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecords($table, $records, $idField = '', $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('delete', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
            }
            $fields_array = array($records['fields']);
        }
        try {
            $results = $this->sqlDb->deleteSqlRecords($table, $fields_array, $idField, $rollback, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $idField
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecord($table, $record, $idField = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('delete', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = array($record['fields']);
        try {
            $results = $this->sqlDb->deleteSqlRecords($table, $fields_array, $idField, false, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $filter
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordsByFilter($table, $filter, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('delete', $table);

        try {
            $results = $this->sqlDb->deleteSqlRecordsByFilter($table, $filter, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $idField
     * @param bool $rollback
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordsByIds($table, $id_list, $idField = '', $rollback = false, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('delete', $table);

        try {
            $results = $this->sqlDb->deleteSqlRecordsByIds($table, $id_list, $idField, $rollback, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id
     * @param string $idField
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordById($table, $id, $idField = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('delete', $table);

        try {
            $results = $this->sqlDb->deleteSqlRecordsByIds($table, $id, $idField, false, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param string $fields
     * @param string $filter
     * @param int $limit
     * @param string $order
     * @param int $offset
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByFilter($table, $fields = '', $filter = '', $limit = 0, $order = '', $offset = 0)
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByFilter($table, $fields, $filter, $limit, $order, $offset);
            $total = Utilities::getArrayValue('total', $results, 0);
            unset($results['total']);
            $results = array('record' => array_map(array($this, 'addFieldsLevel'), $results),
                             'meta' => array('total' => $total));

            return $results;
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $records
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveRecords($table, $records, $id_field = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('read', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }

        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
            }
            $fields_array = array($records['fields']);
        }
        try {
            $results = $this->sqlDb->retrieveSqlRecords($table, $fields_array, $id_field, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveRecord($table, $record, $id_field = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('read', $table);
        if (!isset($record['fields']) || empty($record['fields'])) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        $fields_array = array($record['fields']);
        try {
            $results = $this->sqlDb->retrieveSqlRecords($table, $fields_array, $id_field, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param $table
     * @param $id_list
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByIds($table, $id_list, $id_field = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByIds($table, $id_list, $id_field, $fields);

            return array('record' => array_map(array($this, 'addFieldsLevel'), $results));
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @param $id
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveRecordById($table, $id, $id_field = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByIds($table, $id, $id_field, $fields);

            return array('fields' => $results[0]);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}");
        }
    }

}
