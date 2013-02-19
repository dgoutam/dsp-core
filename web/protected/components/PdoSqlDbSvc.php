<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage PdoSqlDbSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */
class PdoSqlDbSvc
{
    // Database Variables

    /**
     * @var string
     */
    protected $_tablePrefix = '';

    /**
     * @var CDbConnection
     */
    protected $_sqlConn;

    /**
     * @return \CDbConnection
     */
    public function getSqlConn()
    {
        return $this->_sqlConn;
    }

    /**
     * @var array
     */
    protected $_fieldCache;

    protected $_driverType = DbUtilities::DRV_OTHER;

    public function getDriverType()
    {
        return $this->_driverType;
    }

    /**
     * Creates a new PdoSqlDbSvc instance
     *
     * @param string $table_prefix db table prefix prepended for this instance
     * @param string $dsn SQL connection string with host and db
     * @param $user
     * @param $pwd
     * @param array $attributes
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __construct($table_prefix = '', $dsn = '', $user = '', $pwd = '', $attributes = array())
    {
        if (empty($dsn) && empty($user) && empty($pwd)) {
            $this->_sqlConn = Yii::app()->db;
            $this->_driverType = DbUtilities::getDbDriverType($this->_sqlConn);
        }
        else {
            // Validate other parameters
            if (empty($dsn)) {
                throw new InvalidArgumentException('DB connection string (DSN) can not be empty.');
            }
            if (empty($user)) {
                throw new InvalidArgumentException('DB admin name can not be empty.');
            }
            if (empty($pwd)) {
                throw new InvalidArgumentException('DB admin password can not be empty.');
            }

            // create pdo connection, activate later
            Utilities::markTimeStart('DB_TIME');
            $this->_sqlConn = new CDbConnection($dsn, $user, $pwd);
            $this->_driverType = DbUtilities::getDbDriverType($this->_sqlConn);
            switch ($this->_driverType) {
            case DbUtilities::DRV_MYSQL:
                $this->_sqlConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $this->_sqlConn->setAttribute('charset', 'utf8');
                break;
            case DbUtilities::DRV_SQLSRV:
                $this->_sqlConn->setAttribute(constant('PDO::SQLSRV_ATTR_DIRECT_QUERY'), true);
                $this->_sqlConn->setAttribute("MultipleActiveResultSets", false);
                $this->_sqlConn->setAttribute("ReturnDatesAsStrings", true);
                $this->_sqlConn->setAttribute("CharacterSet", "UTF-8");
                break;
            }
            Utilities::markTimeStop('DB_TIME');
        }

        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $key=>$value) {
                $this->_sqlConn->setAttribute($key, $value);
            }
        }
//        $this->_tablePrefix = $table_prefix;
        $this->_fieldCache = array();
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        if (isset($this->_sqlConn)) {
            try {
                $this->_sqlConn->active = false;
            }
            catch (PDOException $ex) {
                error_log("Failed to disconnect from database.\n{$ex->getMessage()}");
            }
            catch (Exception $ex) {
                error_log("Failed to disconnect from database.\n{$ex->getMessage()}");
            }
            $this->_sqlConn = null;
        }
    }

    /**
     * @throws Exception
     */
    protected function checkConnection()
    {
        if (!isset($this->_sqlConn)) {
            throw new Exception('Database driver has not been initialized.');
        }
        try {
            Utilities::markTimeStart('DB_TIME');

            if (!$this->_sqlConn->active) {
                $this->_sqlConn->active = true;
            }

            Utilities::markTimeStop('DB_TIME');
        }
        catch (PDOException $ex) {
            throw new Exception("Failed to connect to database.\n{$ex->getMessage()}");
        }
        catch (Exception $ex) {
            throw new Exception("Failed to connect to database.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $name
     * @return string
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function correctTableName($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Table name can not be empty.');
        }
        $tables = $this->_sqlConn->schema->getTableNames();
         // make search case insensitive
        foreach ($tables as $table) {
            if (0 == strcasecmp($table, $name)) {
                return $table;
            }
        }
        error_log(print_r($tables, true));
        throw new Exception("Table '$name' does not exist in the database.");
    }

    /**
     * @param $name
     * @return array
     * @throws Exception
     */
    protected function describeTableFields($name)
    {
        if (isset($this->_fieldCache[$name])) {
            return $this->_fieldCache[$name];
        }

        $fields = DbUtilities::describeTableFields($this->_sqlConn, $name);
        $this->_fieldCache[$name] = $fields;

        return $fields;
    }

    /**
     * @param $record
     * @param $avail_fields
     * @param bool $for_update
     * @return array
     * @throws Exception
     */
    protected function parseRecord($record, $avail_fields, $for_update = false)
    {
        // todo remove check for specific names
        $parsed = array();
        $record = Utilities::array_key_lower($record);
        $keys = array_keys($record);
        $values = array_values($record);
        foreach ($avail_fields as $field_info) {
            $name = mb_strtolower($field_info['name']);
            $type = $field_info['type'];
            $dbType = $field_info['db_type'];
            $pos = array_search($name, $keys);
            if (false !== $pos) {
                $fieldVal = $values[$pos];
                // due to conversion from XML to array, null or empty xml elements have the array value of an empty array
                if (is_array($fieldVal) && empty($fieldVal)) {
                    $fieldVal = null;
                }
                // overwrite some undercover fields
                if (Utilities::getArrayValue('auto_increment', $field_info, false)) {
                    unset($keys[$pos]);
                    unset($values[$pos]);
                    continue;   // should I error this?
                }
                switch ($name) {
                case 'createddate':
                case 'created_date':
                case 'lastmodifieddate':
                case 'last_modified_date':
                case 'createdbyid':
                case 'created_by_id':
                case 'lastmodifiedbyid':
                case 'last_modified_by_id':
                    break;
                default:
                    if (is_null($fieldVal) && !$field_info['allow_null']) {
                        if ($for_update) continue;  // todo throw away nulls for now
                        throw new Exception("Field '$name' can not be NULL.");
                    }
                    else {
                        if (!is_null($fieldVal)) {
                            switch ($this->_driverType) {
                            case DbUtilities::DRV_SQLSRV:
                                switch ($dbType) {
                                case 'bit':
                                    $fieldVal = (Utilities::boolval($fieldVal) ? 1 : 0);
                                    break;
                                }
                                break;
                            case DbUtilities::DRV_MYSQL:
                                switch ($dbType) {
                                case 'tinyint(1)':
                                    $fieldVal = (Utilities::boolval($fieldVal) ? 1 : 0);
                                    break;
                                }
                                break;
                            }
                            switch ($type) {
                            case 'integer':
                                if (!is_int($fieldVal)) {
                                    if (('' === $fieldVal) && $field_info['allow_null']) {
                                        $fieldVal = null;
                                    }
                                    elseif (!(ctype_digit($fieldVal))) {
                                        throw new Exception("Field '$name' must be a valid integer.");
                                    }
                                    else {
                                        $fieldVal = intval($fieldVal);
                                    }
                                }
                                break;
                            default:
                            }
                        }
                    }
                    $parsed[$name] = $fieldVal;
                }
                unset($keys[$pos]);
                unset($values[$pos]);
            }
            else {
                // check specific fields
                switch ($name) {
                case 'createddate':
                case 'created_date':
                case 'lastmodifieddate':
                case 'last_modified_date':
                case 'createdbyid':
                case 'created_by_id':
                case 'lastmodifiedbyid':
                case 'last_modified_by_id':
                    break;
                default:
                    // if field is required, kick back error
                    if ($field_info['required'] && !$for_update) {
                        throw new Exception("Required field '$name' can not be NULL.");
                    }
                    break;
                }
            }
            // add or override for specific fields
            switch ($name) {
            case 'createddate':
            case 'created_date':
                if (!$for_update) {
                    switch ($this->_driverType) {
                    case DbUtilities::DRV_SQLSRV:
                        $parsed[$name] = new CDbExpression('(SYSDATETIMEOFFSET())');
                        break;
                    case DbUtilities::DRV_MYSQL:
                        $parsed[$name] = new CDbExpression('(NOW())');
                        break;
                    }
                }
                break;
            case 'lastmodifieddate':
            case 'last_modified_date':
                switch ($this->_driverType) {
                case DbUtilities::DRV_SQLSRV:
                    $parsed[$name] = new CDbExpression('(SYSDATETIMEOFFSET())');
                    break;
                case DbUtilities::DRV_MYSQL:
                    $parsed[$name] = new CDbExpression('(NOW())');
                    break;
                }
                break;
            case 'createdbyid':
            case 'created_by_id':
                if (!$for_update) {
                    $userId = SessionManager::getCurrentUserId();
                    if (isset($userId)) {
                        $parsed[$name] = $userId;
                    }
                }
                break;
            case 'lastmodifiedbyid':
            case 'last_modified_by_id':
                $userId = SessionManager::getCurrentUserId();
                if (isset($userId)) {
                    $parsed[$name] = $userId;
                }
                break;
            }
        }

        return $parsed;
    }

    /**
     * @param array $record
     * @return string
     */
    protected function parseRecordForSqlInsert($record)
    {
        $values = '';
        foreach ($record as $key=>$value) {
            $fieldVal = (is_null($value)) ? "NULL" : $this->_sqlConn->quoteValue($value);
            $values .= (!empty($values)) ? ',' : '';
            $values .= $fieldVal;
        }

        return $values;
    }

    /**
     * @param array $record
     * @return string
     */
    protected function parseRecordForSqlUpdate($record)
    {
        $out = '';
        foreach ($record as $key=>$value) {
            $fieldVal = (is_null($value)) ? "NULL" : $this->_sqlConn->quoteValue($value);
            $out .= (!empty($values)) ? ',' : '';
            $out .= "$key = $fieldVal";
        }

        return $out;
    }

    /**
     * @param $fields
     * @param $avail_fields
     * @param bool $as_quoted_string
     * @param string $prefix
     * @param string $fields_as
     * @return string
     */
    protected function parseFieldsForSqlSelect($fields, $avail_fields, $as_quoted_string = false, $prefix = '', $fields_as = '')
    {
        if (empty($fields) || ('*' === $fields)) {
            $fields = DbUtilities::listAllFieldsFromDescribe($avail_fields);
        }
        $field_arr = array_map('trim', explode(',', $fields));
        $as_arr = array_map('trim', explode(',', $fields_as));
        if (!$as_quoted_string) {
            // yii will not quote anything if any of the fields are expressions
        }
        $outString = '';
        $outArray = array();
        $bindArray = array();
        for ($i = 0, $size = sizeof($field_arr); $i < $size; $i++) {
            $field = $field_arr[$i];
            $as = (isset($as_arr[$i]) ? $as_arr[$i] : '');
            $context = (empty($prefix) ? $field : $prefix . '.' . $field);
            $out_as = (empty($as) ? $field : $as);
            if ($as_quoted_string) {
                $context = $this->_sqlConn->quoteColumnName($context);
                $out_as = $this->_sqlConn->quoteColumnName($out_as);
            }
            // find the type
            $field_info = DbUtilities::getFieldFromDescribe($field, $avail_fields);
            $dbType = (isset($field_info)) ? $field_info['db_type'] : '';
            $type = (isset($field_info)) ? $field_info['type'] : '';
            switch ($type) {
            case 'boolean':
                $bindArray[] = array('name' => $field, 'type' => PDO::PARAM_BOOL);
                break;
            case 'integer':
                $bindArray[] = array('name' => $field, 'type' => PDO::PARAM_INT);
                break;
            default:
                $bindArray[] = array('name' => $field, 'type' => PDO::PARAM_STR);
                break;
            }
            // todo fix special cases - maybe after retrieve
            switch ($dbType) {
            case 'datetime':
            case 'datetimeoffset':
                switch ($this->_driverType) {
                case DbUtilities::DRV_SQLSRV:
                    if (!$as_quoted_string) {
                        $context = $this->_sqlConn->quoteColumnName($context);
                        $out_as = $this->_sqlConn->quoteColumnName($out_as);
                    }
                    $out = "(CONVERT(nvarchar(30), $context, 127)) AS $out_as";
                    break;
                default:
                    $out = $context;
                    break;
                }
                break;
            default :
                $out = $context;
                if (!empty($as))
                    $out .= ' AS ' . $out_as;
                break;
            }

            $outArray[] = $out;
        }

        return array('fields' => $outArray, 'bindings' => $bindArray);
    }

    /**
     * @param $fields
     * @param $avail_fields
     * @param string $prefix
     * @return string
     * @throws Exception
     */
    public function parseOutFields($fields, $avail_fields, $prefix = 'INSERTED')
    {
        if (empty($fields)) {
            return '';
        }

        $out_str = '';
        $field_arr = array_map('trim', explode(',', $fields));
        foreach ($field_arr as $field) {
            // find the type
            if (false === DbUtilities::findFieldFromDescribe($field, $avail_fields)) {
                throw new Exception("Invalid field '$field' selected for output.");
            }
            if (!empty($out_str)) {
                $out_str .= ', ';
            }
            $out_str .= $prefix . '.' . $this->_sqlConn->quoteColumnName($field);
        }

        return $out_str;
    }

    /**
     * @param $table
     * @param $records
     * @param bool $rollback
     * @param string $out_fields
     * @return array
     * @throws Exception
     */
    public function createSqlRecords($table, $records, $rollback = false, $out_fields = '')
    {
        if (!isset($records) || !is_array($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        $table = $this->correctTableName($table);
        try {
            $field_info = $this->describeTableFields($table);
            $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
            $command = $this->_sqlConn->createCommand();
            $ids = array();
            $errors = array();
            if ($rollback) {
//                $this->_sqlConn->beginTransaction();
            }
            $count = count($records);
            foreach ($records as $key => $record) {
                try {
                    $record = $this->parseRecord($record, $field_info);
                    if (0 >= count($record)) {
                        throw new Exception("No valid fields were passed in the record [$key] request.");
                    }
                    // simple update request
                    $command->reset();
                    $rows = $command->insert($this->_tablePrefix . $table, $record);
                    if (0 >= $rows) {
                        throw new Exception("Record insert failed for table '$table'.");
                    }
                    $ids[$key] = $this->_sqlConn->lastInsertID;
                }
                catch (Exception $ex) {
                    if ($rollback) {
//                        $this->_sqlConn->rollBack();
                        throw $ex;
                    }
                    $errors[$key] = $ex->getMessage();
                }
            }
            if ($rollback) {
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
            }

            $results = array();
            if (empty($out_fields) || (0 === strcasecmp($id_field, $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    array($id_field => $ids[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, $id_field);
                $temp = $this->retrieveSqlRecordsByIds($table, implode(',', $ids), $id_field, $out_fields);
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    $temp[$i] : // todo bad assumption
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }

            return $results;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $out_fields
     * @return array
     * @throws Exception
     */
    public function createSqlRecord($table, $record, $out_fields = '')
    {
        if (!isset($record) || !is_array($record) || empty($record)) {
            throw new Exception('[InvalidParam]: There are no record fields in the request.');
        }

        $table = $this->correctTableName($table);
        try {
            $field_info = $this->describeTableFields($table);
            $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
            $record = $this->parseRecord($record, $field_info);
            if (0 >= count($record)) {
                throw new Exception("No valid fields were passed in the record request.");
            }

            // simple update request
            $command = $this->_sqlConn->createCommand();
            $rows = $command->insert($this->_tablePrefix . $table, $record);
            if (0 >= $rows) {
                throw new Exception("Record insert failed for table '$table'.");
            }
            $id = $this->_sqlConn->lastInsertID;
            if (empty($out_fields) || (0 === strcasecmp($id_field, $out_fields))) {
                return array(array($id_field => $id));
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, $id_field);
                return $this->retrieveSqlRecordsByIds($table, $id, $id_field, $out_fields);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param $id_field
     * @param bool $rollback
     * @param string $out_fields
     * @return array
     * @throws Exception
     */
    public function updateSqlRecords($table, $records, $id_field, $rollback = false, $out_fields = '')
    {
        if (!isset($records) || !is_array($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        $table = $this->correctTableName($table);
        try {
            $field_info = $this->describeTableFields($table);
            if (empty($id_field)) {
                $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
                if (empty($id_field)) {
                    throw new Exception("Identifying field can not be empty.");
                }
            }
            $command = $this->_sqlConn->createCommand();
            $ids = array();
            $errors = array();
            if ($rollback) {
//                $this->_sqlConn->beginTransaction();
            }
            $count = count($records);
            foreach ($records as $key => $record) {
                try {
                    $id = Utilities::getArrayValue($id_field, $record, '');
                    if (empty($id)) {
                        throw new Exception("Identifying field '$id_field' can not be empty for update record [$key] request.");
                    }
                    $record = Utilities::removeOneFromArray($id_field, $record);
                    $record = $this->parseRecord($record, $field_info, true);
                    if (0 >= count($record)) {
                        throw new Exception("No valid fields were passed in the record [$key] request.");
                    }
                    // simple update request
                    $command->reset();
                    $rows = $command->update($this->_tablePrefix . $table, $record, array('in', $id_field, $id));
                    if (0 >= $rows) {
                        throw new Exception("Record update failed for table '$table'.");
                    }
                    $ids[$key] = $id;
                }
                catch (Exception $ex) {
                    if ($rollback) {
//                        $this->_sqlConn->rollBack();
                        throw $ex;
                    }
                    $errors[$key] = $ex->getMessage();
                }
            }
            if ($rollback) {
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
            }

            $results = array();
            // todo figure out primary key
            if (empty($out_fields) || (0 === strcasecmp($id_field, $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    array($id_field => $ids[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, $id_field);
                $temp =  $this->retrieveSqlRecordsByIds($table, implode(',', $ids), $id_field, $out_fields);
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    $temp[$i] : // todo bad assumption
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }

            return $results;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $table
     * @param array $record
     * @param string $id_list
     * @param string $id_field
     * @param bool $rollback
     * @param string $out_fields
     * @throws Exception
     * @return array
     */
    public function updateSqlRecordsByIds($table, $record, $id_list, $id_field, $rollback = false, $out_fields = '')
    {
        if (!is_array($record) || empty($record)) {
            throw new Exception("No record fields were passed in the request.");
        }
        $table = $this->correctTableName($table);
        try {
            $field_info = $this->describeTableFields($table);
            if (empty($id_field)) {
                $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
                if (empty($id_field)) {
                    throw new Exception("Identifying field can not be empty.");
                }
            }
            if (empty($id_list)) {
                throw new Exception("Identifying values for '$id_field' can not be empty for update request.");
            }
            $record = Utilities::removeOneFromArray($id_field, $record);
            // simple update request
            $record = $this->parseRecord($record, $field_info, true);
            if (empty($record)) {
                throw new Exception("No valid field values were passed in the request.");
            }
            $ids = array_map('trim', explode(',', trim($id_list, ',')));
            $outIds = array();
            $errors = array();
            $count = count($ids);
            $command = $this->_sqlConn->createCommand();

            if ($rollback) {
//                $this->_sqlConn->beginTransaction();
            }
            foreach ($ids as $key => $id) {
                try {
                    if (empty($id)) {
                        throw new Exception("Identifying field '$id_field' can not be empty for update record request.");
                    }
                    // simple update request
                    $command->reset();
                    $rows = $command->update($this->_tablePrefix . $table, $record, array('in', $id_field, $id));
                    if (0 >= $rows) {
                        throw new Exception("Record update failed for table '$table'.");
                    }
                    $outIds[$key] = $id;
                }
                catch (Exception $ex) {
                    error_log($ex->getMessage());
                    if ($rollback) {
//                        $this->_sqlConn->rollBack();
                        throw $ex;
                    }
                    $errors[$key] = $ex->getMessage();
                }
            }
            if ($rollback) {
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
            }
/*
            $rows = $command->update($table, $record, array('in', 'id', array_values($ids)));
            if (0 >= $rows) {
                throw new Exception("No records updated in table '$table'.");
//                throw new Exception("Record with $id_field '$id' not found in table '$table'.");
            }
*/
            $results = array();
            // todo figure out primary key
            if (empty($out_fields) || (0 === strcasecmp($id_field, $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($outIds[$i]) ?
                                    array($id_field => $outIds[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, $id_field);
                $temp = $this->retrieveSqlRecordsByIds($table, implode(',', $ids), $id_field, $out_fields);
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($outIds[$i]) ?
                                    $temp[$i] : // todo bad assumption
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }

            return $results;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $record
     * @param string $filter
     * @param string $out_fields
     * @return array
     * @throws Exception
     */
    public function updateSqlRecordsByFilter($table, $record, $filter = '', $out_fields = '')
    {
        if (!is_array($record) || empty($record)) {
            throw new Exception("No record fields were passed in the request.");
        }
        $table = $this->correctTableName($table);
        try {
            $field_info = $this->describeTableFields($table);
            // simple update request
            $record = $this->parseRecord($record, $field_info, true);
            if (empty($record)) {
                throw new Exception("No valid field values were passed in the request.");
            }
            // parse filter
            $command = $this->_sqlConn->createCommand();
            $rows = $command->update($this->_tablePrefix . $table, $record, $filter);
            if (0 >= $rows) {
                throw new Exception("No records updated in table '$table'.");
            }

            $results = array();
            if (!empty($out_fields)) {
                $results = $this->retrieveSqlRecordsByFilter($table, $out_fields, $filter);
            }

            return $results;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $records
     * @param $id_field
     * @param bool $rollback
     * @param string $out_fields
     * @return array|string
     * @throws Exception
     */
    public function deleteSqlRecords($table, $records, $id_field, $rollback = false, $out_fields = '')
    {
        if (!isset($records) || !is_array($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        $table = $this->correctTableName($table);
        $ids = array();
        $field_info = $this->describeTableFields($table);
        if (empty($id_field)) {
            $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
            if (empty($id_field)) {
                throw new Exception("Identifying field can not be empty.");
            }
        }
        foreach ($records as $key => $record) {
            $id = Utilities::getArrayValue($id_field, $record, '');
            if (empty($id)) {
                throw new Exception("Identifying field '$id_field' can not be empty for retrieve record [$key] request.");
            }
            $ids[] = $id;
        }
        $idList = implode(',', $ids);
        return $this->deleteSqlRecordsByIds($table, $idList, $id_field, $rollback, $out_fields);
    }

    /**
     * @param $table
     * @param $id_list
     * @param $id_field
     * @param bool $rollback
     * @param string $out_fields
     * @return array
     * @throws Exception
     */
    public function deleteSqlRecordsByIds($table, $id_list, $id_field, $rollback = false, $out_fields = '')
    {
        $table = $this->correctTableName($table);
        try {
            $field_info = $this->describeTableFields($table);
            if (empty($id_field)) {
                $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
                if (empty($id_field)) {
                    throw new Exception("Identifying field can not be empty.");
                }
            }
            if (empty($id_list)) {
                throw new Exception("Identifying values for '$id_field' can not be empty for update request.");
            }

            $ids = array_map('trim', explode(',', $id_list));
            $errors = array();
            $count = count($ids);
            $command = $this->_sqlConn->createCommand();

            // get the returnable fields first, then issue delete
            $outResults = array();
            if (!(empty($out_fields) || (0 === strcasecmp($id_field, $out_fields)))) {
                $out_fields = Utilities::addOnceToList($out_fields, $id_field);
                $outResults = $this->retrieveSqlRecordsByIds($table, implode(',', $ids), $id_field, $out_fields);
            }

            if ($rollback) {
//                $this->_sqlConn->beginTransaction();
            }
            foreach ($ids as $key => $id) {
                try {
                    if (empty($id)) {
                        throw new Exception("Identifying field '$id_field' can not be empty for delete record request.");
                    }
                    // simple delete request
                    $command->reset();
                    $rows = $command->delete($this->_tablePrefix . $table, array('in', $id_field, $id));
                    if (0 >= $rows) {
                        throw new Exception("Record delete failed for table '$table'.");
                    }
                    $ids[$key] = $id;
                }
                catch (Exception $ex) {
                    if ($rollback) {
//                        $this->_sqlConn->rollBack();
                        throw $ex;
                    }
                    $errors[$key] = $ex->getMessage();
                }
            }
            if ($rollback) {
//                if (!$this->_sqlConn->commit()) {
//                    throw new Exception("Transaction failed.");
//                }
            }
/*
            $rows = $command->delete($table, array('in', 'id', array_values($ids)));
            if (0 >= $rows) {
                throw new Exception("No records deleted in table '$table'.");
//                throw new Exception("Record with $id_field '$id' not found in table '$table'.");
            }
*/
            $results = array();
            if (empty($out_fields) || (0 === strcasecmp($id_field, $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    array($id_field => $ids[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    $outResults[$i] : // todo bad assumption
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }

            return $results;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param $filter
     * @param string $out_fields
     * @return array
     * @throws Exception
     */
    public function deleteSqlRecordsByFilter($table, $filter, $out_fields = '')
    {
        if (empty($filter)) {
            throw new Exception("Filter for delete request can not be empty.");
        }
        $table = $this->correctTableName($table);
        try {
            $command = $this->_sqlConn->createCommand();
            $results = array();
            // get the returnable fields first, then issue delete
            if (!empty($out_fields)) {
                $results = $this->retrieveSqlRecordsByFilter($table, $out_fields, $filter);
            }

            // parse filter
            $command->reset();
            $rows = $command->delete($this->_tablePrefix . $table, $filter);
            if (0 >= $rows) {
                throw new Exception("No records deleted from table '$table'.");
            }

            return $results;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $table
     * @param array $records
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveSqlRecords($table, $records, $id_field, $fields = '')
    {
        if (!isset($records) || !is_array($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (empty($records)) {
            return array();
        }

        $table = $this->correctTableName($table);
        $field_info = $this->describeTableFields($table);
        if (empty($id_field)) {
            $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($field_info);
            if (empty($id_field)) {
                throw new Exception("Identifying field can not be empty.");
            }
        }
        $ids = array();
        foreach ($records as $key => $record) {
            $id = Utilities::getArrayValue($id_field, $record, '');
            if (empty($id)) {
                throw new Exception("Identifying field '$id_field' can not be empty for retrieve record [$key] request.");
            }
            $ids[] = $id;
        }
        $idList = implode(',', $ids);
        return $this->retrieveSqlRecordsByIds($table, $idList, $id_field, $fields);
    }

    /**
     * @param string $table
     * @param string $id_list - comma delimited list of ids
     * @param string $id_field
     * @param string $fields
     * @return array
     * @throws Exception
     */
    public function retrieveSqlRecordsByIds($table, $id_list, $id_field, $fields = '')
    {
        if (empty($id_list)) {
            return array();
        }
        $ids = array_map('trim', explode(',', $id_list));
        $table = $this->correctTableName($table);
        try {
            $availFields = $this->describeTableFields($table);
            if (empty($id_field)) {
                $id_field = DbUtilities::getPrimaryKeyFieldFromDescribe($availFields);
                if (empty($id_field)) {
                    throw new Exception("Identifying field can not be empty.");
                }
            }
            if (!empty($fields) && ('*' !== $fields)) {
                // add id field to field list
                $fields = Utilities::addOnceToList($fields, $id_field, ',');
            }
            $result = $this->parseFieldsForSqlSelect($fields, $availFields);
            $bindings = $result['bindings'];
            $fields = $result['fields'];
            // use query builder
            $command = $this->_sqlConn->createCommand();
            $command->select($fields);
            $command->from($this->_tablePrefix . $table);
            $command->where(array('in', $id_field, $ids));

            $this->checkConnection();
            Utilities::markTimeStart('DB_TIME');
            $reader = $command->query();
            $data = array();
            $dummy = array();
            foreach ($bindings as $binding) {
                $reader->bindColumn($binding['name'], $dummy[$binding['name']], $binding['type']);
            }
            $reader->setFetchMode(PDO::FETCH_BOUND);
            $count = 0;
            while (false !== $reader->read()) {
                $temp = array();
                foreach ($bindings as $binding) {
                    $temp[$binding['name']] = $dummy[$binding['name']];
                }
                $data[$count++] = $temp;
            }

            // order returned data by received ids, fill in error for those not found
            $results = array();
            foreach ($ids as $id) {
                $foundRecord = null;
                foreach ($data as $record) {
                    if (isset($record[$id_field]) && ($record[$id_field] == $id)) {
                        $foundRecord = $record;
                        break;
                    }
                }
                $results[] = (isset($foundRecord) ? $foundRecord :
                                ("Could not find record for id = '$id'"));
            }

            Utilities::markTimeStop('DB_TIME');

            return $results;
        }
        catch (Exception $ex) {
            Utilities::markTimeStop('DB_TIME');
            /*
            $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
            if (isset($GLOBALS['DB_DEBUG'])) {
                error_log($msg . "\n$query");
            }
            */
            throw $ex;
        }
    }

    /**
     * @param $table
     * @param string $fields
     * @param string $filter
     * @param int $limit
     * @param string $order
     * @param int $offset
     * @param bool $include_count
     * @return array
     * @throws Exception
     */
    public function retrieveSqlRecordsByFilter($table, $fields = '', $filter = '',
                                               $limit = 0, $order = '', $offset = 0,
                                               $include_count = false)
    {
        $table = $this->correctTableName($table);
        try {
            // parse filter
            $availFields = $this->describeTableFields($table);
            $result = $this->parseFieldsForSqlSelect($fields, $availFields);
            $bindings = $result['bindings'];
            $fields = $result['fields'];
            if (empty($fields)) {
                $fields = '*';
            }
            $limit = intval($limit);
            $offset = intval($offset);

            // use query builder
            $command = $this->_sqlConn->createCommand();
            $command->select($fields);
            $command->from($this->_tablePrefix . $table);
            if (!empty($filter)) {
                $command->where($filter);
            }
            if (!empty($order)) {
                $command->order($order);
            }
            if ($offset > 0) {
                $command->offset($offset);
            }
            if ($limit > 0) {
                $command->limit($limit);
            }
            else {
                // todo impose a limit to protect server
            }

            $this->checkConnection();
            Utilities::markTimeStart('DB_TIME');
            $reader = $command->query();
            $data = array();
            $dummy = array();
            foreach ($bindings as $binding) {
                $reader->bindColumn($binding['name'], $dummy[$binding['name']], $binding['type']);
            }
            $reader->setFetchMode(PDO::FETCH_BOUND);
            $count = 0;
            while (false !== $reader->read()) {
                $temp = array();
                foreach ($bindings as $binding) {
                    $temp[$binding['name']] = $dummy[$binding['name']];
                }
                $data[$count++] = $temp;
            }

            // count total records
            if ($include_count) {
                $command->reset();
                $command->select('(COUNT(*)) as ' . $this->_sqlConn->quoteColumnName('count'));
                $command->from($this->_tablePrefix . $table);
                if (!empty($filter)) {
                    $command->where($filter);
                }
                $data['count'] = intval($command->queryScalar());
            }

            Utilities::markTimeStop('DB_TIME');
//            error_log('retrievefilter: ' . PHP_EOL . print_r($data, true));

            return $data;
        }
        catch (Exception $ex) {
            Utilities::markTimeStop('DB_TIME');
            error_log('retrievefilter: ' . $ex->getMessage() . PHP_EOL . $filter);
            /*
            $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
            if (isset($GLOBALS['DB_DEBUG'])) {
                error_log($msg . "\n$query");
            }
            */
            throw $ex;
        }
    }

    /**
     * Handle raw SQL Azure requests
     */
    protected function batchSqlQuery($query, $bindings=array())
    {
        if (empty($query)) {
            throw new Exception('[NOQUERY]: No query string present in request.');
        }
        $this->checkConnection();
        try {
            Utilities::markTimeStart('DB_TIME');

            $command = $this->_sqlConn->createCommand($query);
            $reader = $command->query();
            $dummy = null;
            foreach ($bindings as $binding) {
                $reader->bindColumn($binding['name'], $dummy, $binding['type']);
            }

            $data = array();
            $rowData = array();
            while ($row = $reader->read()) {
                $rowData[] = $row;
            }
            if (1 == count($rowData)) {
                $rowData = $rowData[0];
            }
            $data[] = $rowData;

            // Move to the next result and get results
            while ($reader->nextResult()) {
                $rowData = array();
                while ($row = $reader->read()) {
                    $rowData[] = $row;
                }
                if (1 == count($rowData)) {
                    $rowData = $rowData[0];
                }
                $data[] = $rowData;
            }

            Utilities::markTimeStop('DB_TIME');

            return $data;
        }
        catch (Exception $ex) {
            error_log('batchquery: ' . $ex->getMessage() . PHP_EOL . $query);
            Utilities::markTimeStop('DB_TIME');
/*
                $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
                if (isset($GLOBALS['DB_DEBUG'])) {
                    error_log($msg . "\n$query");
                }
*/
            throw $ex;
        }
    }

    /**
     * Handle SQL Db requests with output as array
     */
    public function singleSqlQuery($query, $params = null)
    {
        if (empty($query)) {
            throw new Exception('[NOQUERY]: No query string present in request.');
        }
        $this->checkConnection();
        try {
            Utilities::markTimeStart('DB_TIME');

            $command = $this->_sqlConn->createCommand($query);
            if (isset($params) && !empty($params)) {
                $data = $command->queryAll(true, $params);
            }
            else {
                $data = $command->queryAll();
            }

            Utilities::markTimeStop('DB_TIME');

            return $data;
        }
        catch (Exception $ex) {
            error_log('singlequery: ' . $ex->getMessage() . PHP_EOL . $query . PHP_EOL . print_r($params, true));
            Utilities::markTimeStop('DB_TIME');
/*
                    $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
                    if (isset($GLOBALS['DB_DEBUG'])) {
                        error_log($msg . "\n$query");
                    }
*/
            throw $ex;
        }
    }

    /**
     * Handle SQL Db requests with output as array
     */
    public function singleSqlExecute($query, $params = null)
    {
        if (empty($query)) {
            throw new Exception('[NOQUERY]: No query string present in request.');
        }
        $this->checkConnection();
        try {
            Utilities::markTimeStart('DB_TIME');

            $command = $this->_sqlConn->createCommand($query);
            if (isset($params) && !empty($params)) {
                $data = $command->execute($params);
            }
            else {
                $data = $command->execute();
            }

            Utilities::markTimeStop('DB_TIME');

            return $data;
        }
        catch (Exception $ex) {
            error_log('singleexecute: ' . $ex->getMessage() . PHP_EOL . $query . PHP_EOL . print_r($params, true));
            Utilities::markTimeStop('DB_TIME');
/*
                    $msg = '[QUERYFAILED]: ' . implode(':', $this->_sqlConn->errorInfo()) . "\n";
                    if (isset($GLOBALS['DB_DEBUG'])) {
                        error_log($msg . "\n$query");
                    }
*/
            throw $ex;
        }
    }

}
