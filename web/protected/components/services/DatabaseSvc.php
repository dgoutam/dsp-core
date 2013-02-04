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
     * @return array
     */
    public function actionGet()
    {
        $this->detectCommonParams();
        switch (strtolower($this->tableName)) {
        case '':
            $result = $this->describeDatabase();
            $commands = array(array('name' => 'schema', 'label' => 'Schema', 'plural' => 'Schemas'));
            $result = array('resource' => array_merge($commands, $result['table']));
            break;
        case 'schema':
            if (empty($this->recordId)) {
                $result = $this->describeDatabase();
            }
            else {
                $result = $this->describeTable($this->recordId);
            }
            break;
        default:
            $data = Utilities::getPostDataAsArray();
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($this->recordId)) {
                $ids = Utilities::getArrayValue('ids', $_REQUEST, '');
                if (!empty($ids)) {
                    $result = $this->retrieveRecordsByIds($this->tableName, $ids, $idField, $fields);
                }
                else {
                    $filter = Utilities::getArrayValue('filter', $_REQUEST, '');
                    $order = Utilities::getArrayValue('order', $_REQUEST, '');
                    $limit = intval(Utilities::getArrayValue('limit', $_REQUEST, 0));
                    $offset = intval(Utilities::getArrayValue('offset', $_REQUEST, 0));
                    if (!empty($data)) { // complex filters or large numbers of ids require post
                        $ids = Utilities::getArrayValue('ids', $data, '');
                        $records = Utilities::getArrayValue('record', $data, null);
                        if (empty($records)) {
                            // xml to array conversion leaves them in plural wrapper
                            $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                        }
                        if (empty($idField)) {
                            $idField = Utilities::getArrayValue('id_field', $data, '');
                        }
                        if (!empty($ids)) {
                            $result = $this->retrieveRecordsByIds($this->tableName, $ids, $idField, $fields);
                        }
                        elseif (!empty($records)) {
                            $result = $this->retrieveRecords($this->tableName, $records, $idField, $fields);
                        }
                        else {
                            if (empty($filter)) {
                                $filter = Utilities::getArrayValue('filter', $data, '');
                            }
                            if (empty($order)) {
                                $order = Utilities::getArrayValue('order', $data, '');
                            }
                            if (0 >= $limit) {
                                $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                            }
                            if (0 >= $offset) {
                                $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                            }
                            $result = $this->retrieveRecordsByFilter($this->tableName, $fields, $filter, $limit, $order, $offset);
                        }
                    }
                    else {
                        $result = $this->retrieveRecordsByFilter($this->tableName, $fields, $filter, $limit, $order, $offset);
                    }
                }
            }
            else { // single entity by id
                $result = $this->retrieveRecordsByIds($this->tableName, $this->recordId, $idField, $fields);
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
        case 'schema':
            $tables = Utilities::getArrayValue('table', $data, '');
            if (empty($tables)) {
                // temporary, layer created from xml to array conversion
                $tables = (isset($data['tables']['table'])) ? $data['tables']['table'] : '';
            }
            if (empty($tables)) {
                throw new Exception('No tables in schema create request.');
            }
            $result = $this->createTables($tables);
            $result = array('table' => $result);
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
                throw new Exception('No records in POST create request.');
            }
            $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
            if (!isset($rollback)) {
                $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
            }
            $result = $this->createRecords($this->tableName, $records, $rollback, $fields);
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
        case 'schema':
            if (empty($this->recordId)) {
                $tables = Utilities::getArrayValue('table', $data, '');
                if (empty($tables)) {
                    // temporary, layer created from xml to array conversion
                    $tables = (isset($data['tables']['table'])) ? $data['tables']['table'] : '';
                }
                if (empty($tables)) {
                    throw new Exception('No tables in schema update request.');
                }
                $result = $this->updateTables($tables);
            }
            else {
                // possibly a table name for single schema updates or deletes
                $tables = Utilities::getArrayValue('table', $data, '');
                if (empty($tables)) {
                    // temporary, layer created from xml to array conversion
                    $tables = (isset($data['tables']['table'])) ? $data['tables']['table'] : '';
                }
                if (empty($tables)) {
                    throw new Exception('No tables in schema update request.');
                }
                $result = $this->updateTables($tables);
            }
            $result = array('table' => $result);
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($idField)) {
                $idField = Utilities::getArrayValue('id_field', $data, '');
            }
            $records = Utilities::getArrayValue('record', $data, null);
            if (empty($records)) {
                // xml to array conversion leaves them in plural wrapper
                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
            }
            if (empty($records)) {
                throw new Exception('No records in MERGE/PATCH update request.');
            }
            if (empty($this->recordId)) {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                if (!isset($filter)) {
                    $filter = Utilities::getArrayValue('filter', $data, null);
                }
                if (isset($filter)) {
                    $result = $this->updateRecordsByFilter($this->tableName, $filter, $records, $fields);
                }
                else {
                    $result = $this->updateRecords($this->tableName, $records, $idField, $rollback, $fields);
                }
            }
            else {
                $result = $this->updateRecordById($this->tableName, $records, $this->recordId, $idField, $fields);
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
        case 'schema':
            if (empty($this->recordId)) {
                $tables = Utilities::getArrayValue('table', $data, '');
                if (empty($tables)) {
                    // temporary, layer created from xml to array conversion
                    $tables = (isset($data['tables']['table'])) ? $data['tables']['table'] : '';
                }
                if (empty($tables)) {
                    throw new Exception('No tables in schema update request.');
                }
                $result = $this->updateTables($tables);
            }
            else {
                // possibly a table name for single schema updates or deletes
                $tables = Utilities::getArrayValue('table', $data, '');
                if (empty($tables)) {
                    // temporary, layer created from xml to array conversion
                    $tables = (isset($data['tables']['table'])) ? $data['tables']['table'] : '';
                }
                if (empty($tables)) {
                    throw new Exception('No tables in schema update request.');
                }
                $result = $this->updateTables($tables);
            }
            $result = array('table' => $result);
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($idField)) {
                $idField = Utilities::getArrayValue('id_field', $data, '');
            }
            $records = Utilities::getArrayValue('record', $data, null);
            if (empty($records)) {
                // xml to array conversion leaves them in plural wrapper
                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
            }
            if (empty($records)) {
                throw new Exception('No records in MERGE/PATCH update request.');
            }
            if (empty($this->recordId)) {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                if (!isset($filter)) {
                    $filter = Utilities::getArrayValue('filter', $data, null);
                }
                if (isset($filter)) {
                    $result = $this->updateRecordsByFilter($this->tableName, $filter, $records, $fields);
                }
                else {
                    $result = $this->updateRecords($this->tableName, $records, $idField, $rollback, $fields);
                }
            }
            else {
                $result = $this->updateRecordById($this->tableName, $records, $this->recordId, $idField, $fields);
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
        case 'schema':
            if (empty($this->recordId)) {
                throw new Exception('Invalid format for DELETE Table request.');
            }
            $result = $this->deleteTable($this->recordId);
            $result = array('table' => $result);
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($this->recordId)) {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : false;
                $ids = (isset($_REQUEST['ids'])) ? $_REQUEST['ids'] : '';
                $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : '';
                if (!empty($ids)) {
                    $result = $this->deleteRecordsByIds($this->tableName, $ids, $idField, $rollback, $fields);
                }
                elseif (!empty($filter)) {
                    $result = $this->deleteRecordsByFilter($this->tableName, $filter, $fields);
                }
                elseif (!empty($data)) {
                    if (empty($idField)) {
                        $idField = Utilities::getArrayValue('id_field', $data, '');
                    }
                    $ids = Utilities::getArrayValue('ids', $data, '');
                    $records = Utilities::getArrayValue('record', $data, null);
                    if (empty($records)) {
                        // xml to array conversion leaves them in plural wrapper
                        $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                    }
                    $filter = Utilities::getArrayValue('filter', $data, '');
                    if (isset($data['rollback'])) {
                        $rollback = Utilities::boolval($data['rollback']);
                    }
                    if (!empty($ids)) {
                        $result = $this->deleteRecordsByIds($this->tableName, $ids, $idField, $rollback, $fields);
                    }
                    elseif (!empty($records)) {
                        $result = $this->deleteRecords($this->tableName, $records, $idField, $rollback, $fields);
                    }
                    elseif (!empty($filter)) {
                        $result = $this->deleteRecordsByFilter($this->tableName, $filter, $fields);
                    }
                    else {
                        throw new Exception("Ids or filter required to delete $this->tableName records.");
                    }
                }
                else {
                    throw new Exception("Ids or filter required to delete $this->tableName records.");
                }
            }
            else {
                $result = $this->deleteRecordsByIds($this->tableName, $this->recordId, $idField, false, $fields);
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
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('create', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('[InvalidParam]: There are no fields in the record.');
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
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('update', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('[InvalidParam]: There are no fields in the record.');
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
     * @param $records
     * @param string $filter
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecordsByFilter($table, $records, $filter = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('update', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (isset($records[0])) {
            $records = $records[0];
        }
        if (!isset($records['fields']) || empty($records['fields'])) {
            throw new Exception('[InvalidParam]: There are no fields in the record.');
        }
        $fields_array = $records['fields'];
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
     * @param $records
     * @param $id
     * @param string $idField
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function updateRecordById($table, $records, $id, $idField = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('update', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (isset($records[0])) {
            $records = $records[0];
        }
        if (!isset($records['fields']) || empty($records['fields'])) {
            throw new Exception('[InvalidParam]: There are no fields in the record.');
        }
        $fields_array = $records['fields'];
        try {
            $results = $this->sqlDb->updateSqlRecordsByIds($table, $fields_array, $id, $idField, false, $fields);

            return $results;
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
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('delete', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('[InvalidParam]: There are no fields in the record.');
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
     * @param $filter
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function deleteRecordsByFilter($table, $filter, $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
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
            throw new Exception('[InvalidParam]: Table name can not be empty.');
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
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByFilter($table, $fields, $filter, $limit, $order, $offset);
            $total = (isset($results['total'])) ? $results['total'] : '';
            unset($results['total']);
            $results = array('record' => array_map(array($this, 'addFieldsLevel'), $results));

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
            throw new Exception('[InvalidParam]: Table name can not be empty.');
        }
        $this->checkPermission('read', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        if (isset($records[0])) {
            $fields_array = array_map(array($this, 'removeFieldsLevel'), $records);
        }
        else { // single record
            if (!isset($records['fields']) || empty($records['fields'])) {
                throw new Exception('[InvalidParam]: There are no fields in the record.');
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
     * @param $id_list
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveRecordsByIds($table, $id_list, $id_field = '', $fields = '')
    {
        if (empty($table)) {
            throw new Exception('[InvalidParam]: Table name can not be empty.');
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

    //-------- Schema Operations ---------------------

    /**
     * @return array
     * @throws Exception
     */
    public function describeDatabase()
    {
        // check for system tables and deny
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        try {
            return $this->sqlDb->describeDatabase('', $sysTables);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database tables.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $table
     * @return array
     * @throws Exception
     */
    public function describeTable($table)
    {
        // check for system tables and deny
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        if (Utilities::isInList($sysTables, $table, ',')) {
            throw new Exception("System table '$table' not available through this interface.");
        }
        try {
            return $this->sqlDb->describeTable($table);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database table '$table'.\n{$ex->getMessage()}");
        }
    }

    public function describeTables($table_list)
    {
        // check for system tables and deny
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        $tables = array_map('trim', explode(',', trim($table_list, ',')));
        foreach ($tables as $table) {
            if (Utilities::isInList($sysTables, $table, ',')) {
                throw new Exception("System table '$table' not available through this interface.");
            }
        }
        try {
            return $this->sqlDb->describeTables($tables);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database tables '$table_list'.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $tables
     * @return array
     * @throws Exception
     */
    public function createTables($tables)
    {
        $this->checkPermission('create', 'Schema');
        if (!isset($tables) || empty($tables)) {
            throw new Exception('There are no table sets in the request.');
        }

        return $this->sqlDb->createTables($tables);
    }

    /**
     * @param $tables
     * @return mixed
     * @throws Exception
     */
    public function updateTables($tables)
    {
        $this->checkPermission('update', 'Schema');
        if (!isset($tables) || empty($tables)) {
            throw new Exception('There are no table sets in the request.');
        }

        return $this->sqlDb->createTables($tables, true);
    }

    /**
     * @param $table
     * @return array
     * @throws Exception
     */
    public function deleteTable($table)
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.');
        }
        $this->checkPermission('delete', 'Schema');

        return $this->sqlDb->dropTable($table);
    }

}
