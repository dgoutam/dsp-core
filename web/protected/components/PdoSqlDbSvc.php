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
     * @var array
     */
    protected $_fieldCache;

    protected $_driverType = Utilities::DRV_OTHER;

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
            $this->_driverType = Utilities::getDbDriverType($this->_sqlConn->driverName);
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
            $this->_driverType = Utilities::getDbDriverType($this->_sqlConn->driverName);
            switch ($this->_driverType) {
            case Utilities::DRV_MYSQL:
                $this->_sqlConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $this->_sqlConn->setAttribute('charset', 'utf8');
                break;
            case Utilities::DRV_SQLSRV:
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
            throw new Exception('[NODBTYPE]: Database driver has not been initialized.');
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
     * @param $pwd
     * @throws Exception
     * @return void
     */
    public function checkAdminLogin($name, $pwd)
    {
        if (0 !== strcmp($this->_sqlConn->username, $name)) {
            throw new Exception('UserName is incorrect.');
        }

        if (0 !== strcmp($this->_sqlConn->password, $pwd)) {
            throw new Exception('Password is incorrect.');
        }
    }

    /**
     * @param $name
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function checkTableExists($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Table name can not be empty.');
        }
        try {
            $tables = $this->_sqlConn->schema->getTableNames();
            if (!in_array($this->_tablePrefix . $name, $tables)) {
                throw new Exception("Table '$name' does not exist in the database.");
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $name
     * @return bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function doesTableExist($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Table name can not be empty.');
        }
        try {
            $tables = $this->_sqlConn->schema->getTableNames();
            if (in_array($this->_tablePrefix . $name, $tables))
                return true;
            // mysql drops every table name to lowercase
            if (in_array(strtolower($this->_tablePrefix . $name), $tables))
                return true;
        }
        catch (Exception $ex) {
            throw $ex;
        }

        return false;
    }

    /**
     * @param string $include
     * @param string $exclude
     * @return array
     * @throws Exception
     */
    public function describeDatabase($include = '', $exclude = '')
    {
        // todo need to assess schemas in ms sql and load them separately.
        try {
            $names = $this->_sqlConn->schema->getTableNames();
            natcasesort($names);
            $names = array_values($names);
            $includeArray = array_map('trim', explode(',', strtolower($include)));
            $excludeArray = array_map('trim', explode(',', strtolower($exclude)));
            $temp = array();
            foreach ($names as $name) {
                if (!empty($include)) {
                    if (false === array_search(strtolower($name), $includeArray)) {
                        continue;
                    }
                }
                elseif (!empty($exclude)) {
                    if (false !== array_search(strtolower($name), $excludeArray)) {
                        continue;
                    }
                }
                $temp[] = $name;
            }
            $names = $temp;
            $labels = array();
            if ($this->doesTableExist('label')) {
                $command = $this->_sqlConn->createCommand();
                $command->select('table,label,plural');
                $command->from('label');
                $command->where(array('and', "field=''", array('in', 'table', $names)));
                $labels = $command->queryAll();
            }
            $tables = array();
            foreach($names as $name) {
                $label = '';
                $plural = '';
                foreach ($labels as $each) {
                    if (0 === strcasecmp($name, $each['table'])) {
                        $label = Utilities::getArrayValue('label', $each);
                        $plural = Utilities::getArrayValue('plural', $each);
                        break;
                    }
                }
                if (empty($label)) $label = Utilities::makeLabel($name);
                if (empty($plural)) $plural = Utilities::makePlural($label);
                $tables[] = array('name' => $name, 'label' => $label, 'plural' => $plural);
            }

            $data = array('table' => $tables);
            return $data;
        }
        catch (Exception $ex) {
            throw new Exception("Failed to query database schema.\n{$ex->getMessage()}");
        }
    }

    public static function determineDfType($column, $found_pick_list=false)
    {
        switch ($column->type) {
        case 'string':
            if ($found_pick_list) {
                return 'picklist';
            }
            break;
        case 'integer':
            if ($column->isPrimaryKey && $column->autoIncrement) {
                return 'id';
            }
            if ($column->isForeignKey) {
                return 'reference';
            }
            break;
        }
        if (0 === strcasecmp($column->dbType, 'datetimeoffset')) {
            return 'datetime';
        }
        return $column->type;
    }

    public static function determineMultiByteSupport($type)
    {
        switch ($type) {
        case 'nchar':
        case 'nvarchar':
            return true;
        default:
            return false;
        }
    }

    public static function determineRequired($column)
    {
        if ((1 == $column->allowNull) || (isset($column->defaultValue)) || (1 == $column->autoIncrement)) {
            return false;
        }
        return true;
    }

    /**
     * @param $name
     * @return array
     * @throws Exception
     */
    public function describeTable($name)
    {
        try {
            $table = $this->_sqlConn->schema->getTable($this->_tablePrefix . $name);
            if (!$table) {
                error_log(print_r($this->_sqlConn->schema->getTableNames(), true));
                throw new Exception("Table '$name' does not exist in the database.");
            }
            $labels = array();
            if ($this->doesTableExist('label')) {
                $command = $this->_sqlConn->createCommand();
                $command->select();
                $command->from('label');
                $command->where(array('in', 'table', $name));
                $labels = $command->queryAll();
            }

            $label = '';
            $plural = '';
            foreach ($labels as $each) {
                if (empty($label['field'])) {
                    $label = Utilities::getArrayValue('label', $each);
                    $plural = Utilities::getArrayValue('plural', $each);
                    break;
                }
            }
            if (empty($label)) $label = Utilities::makeLabel($table->name);
            if (empty($plural)) $plural = Utilities::makePlural($label);
            $basic = array('name' => $table->name, 'label' => $label, 'plural' => $plural);

            $fields = array();
            foreach ($table->columns as $column) {
                $label = Utilities::makeLabel($column->name);
                $picklist = null;
                foreach ($labels as $item) {
                    $temp = Utilities::getArrayValue('field', $item);
                    if (empty($temp)) continue;
                    if (0 === strcasecmp($column->name, $temp)) {
                        $label = Utilities::getArrayValue('label', $item, $label);
                        $picklist = Utilities::getArrayValue('picklist', $item, null);
                        break;
                    }
                }
                $refTable = null;
                $refFields = null;
                if (1 == $column->isForeignKey) {
                    $referenceTo = Utilities::getArrayValue($column->name, $table->foreignKeys, null);
                    $refTable = (isset($referenceTo[0]) ? $referenceTo[0] : null);
                    $refFields = (isset($referenceTo[1]) ? $referenceTo[1] : null);
                }
                $field = array('name' => $column->name,
                               'label'=> $label,
                               'size' => $column->size,
                               'precision' => $column->precision,
                               'scale' => $column->scale,
                               'default' => $column->defaultValue,
                               'required' => static::determineRequired($column),
                               'allow_null' => $column->allowNull,
                               'picklist_values' => $picklist,
                               'supports_multi_byte' => static::determineMultiByteSupport($column->dbType),
                               'type' => $column->type,
                               'db_type' => $column->dbType,
                               'df_type' => static::determineDfType($column, !empty($picklist)),
                               'auto_increment' => $column->autoIncrement,
                               'is_primary_key' => $column->isPrimaryKey,
                               'is_foreign_key' => $column->isForeignKey,
                               'ref_table' => $refTable,
                               'ref_fields' => $refFields
                              );
                $fields[] = $field;
            }
            $children = $this->describeTableChildren($name);

            $basic['field'] = $fields;
            $basic['child'] = $children;

            return array('table' => $basic);
        }
        catch (Exception $ex) {
            throw new Exception("Failed to query database schema.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param null $names
     * @return array|string
     * @throws Exception
     */
    public function describeTables($names = null)
    {
        try {
            $out = array();
            foreach ($names as $table) {
                $temp = $this->describeTable($table);
                $out[] = $temp['table'];
            }

            $result = array('table' => $out);
            return $result;
        }
        catch (Exception $ex) {
            throw new Exception("Failed to query database schema.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $name
     * @return array
     * @throws Exception
     */
    public function describeTableFields($name)
    {
        if (empty($name)) {
            throw new Exception("[NO_TABLE]: The database table name has not been specified.");
        }
        if (isset($this->_fieldCache[$name])) {
            return $this->_fieldCache[$name];
        }

        try {
            $table = $this->_sqlConn->schema->getTable($this->_tablePrefix . $name);
            if (!$table) {
                error_log(print_r($this->_sqlConn->schema->getTableNames(), true));
                throw new Exception("Table '$name' does not exist in the database.");
            }
            $fields = array();
            foreach ($table->columns as $column) {
                $refTable = null;
                $refFields = null;
                if (1 == $column->isForeignKey) {
                    $referenceTo = Utilities::getArrayValue($column->name, $table->foreignKeys, null);
                    $refTable = (isset($referenceTo[0]) ? $referenceTo[0] : null);
                    $refFields = (isset($referenceTo[1]) ? $referenceTo[1] : null);
                }
                $field = array('name' => $column->name,
                               'size' => $column->size,
                               'precision' => $column->precision,
                               'scale' => $column->scale,
                               'default' => $column->defaultValue,
                               'required' => static::determineRequired($column),
                               'allow_null' => $column->allowNull,
                               'supports_multi_byte' => static::determineMultiByteSupport($column->dbType),
                               'type' => $column->type,
                               'db_type' => $column->dbType,
                               'df_type' => static::determineDfType($column, false),
                               'is_primary_key' => $column->isPrimaryKey,
                               'is_foreign_key' => $column->isForeignKey,
                               'ref_table' => $refTable,
                               'ref_fields' => $refFields
                              );
                $fields[] = $field;
            }
            $this->_fieldCache[$name] = $fields;

            return $fields;
        }
        catch (Exception $ex) {
            throw new Exception("Failed to query table schema.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param $parent_table
     * @return array
     * @throws Exception
     */
    public function describeTableChildren($parent_table)
    {
        if (empty($parent_table)) {
            throw new Exception("[NO_TABLE]: The database table name has not been specified.");
        }
        $names = $this->_sqlConn->schema->getTableNames();
        natcasesort($names);
        $names = array_values($names);
        $children = array();
        foreach ($names as $name) {
            $table = $this->_sqlConn->schema->getTable($name);
            foreach ($table->foreignKeys as $key => $value) {
                $refTable = (isset($value[0]) ? $value[0] : '');
                if (0 === strcasecmp($refTable, $this->_tablePrefix . $parent_table)) {
                    $children[] = array('table' => $name, 'field' => $key);
                }
            }
        }

        return $children;
    }

    /**
     * @param $avail_fields
     * @return string
     */
    protected function listAllFieldsFromDescribe($avail_fields)
    {
        $out = '';
        foreach ($avail_fields as $field_info) {
            if (!empty($out)) {
                $out .= ',';
            }
            $out .= $field_info['name'];
        }

        return $out;
    }

    /**
     * @param $field_name
     * @param $avail_fields
     * @return null
     */
    protected function getFieldFromDescribe($field_name, $avail_fields)
    {
        foreach ($avail_fields as $field_info) {
            if (0 == strcasecmp($field_name, $field_info['name'])) {
                return $field_info;
            }
        }

        return null;
    }

    /**
     * @param $field_name
     * @param $avail_fields
     * @return bool|int|string
     */
    protected function findFieldFromDescribe($field_name, $avail_fields)
    {
        foreach ($avail_fields as $key => $field_info) {
            if (0 == strcasecmp($field_name, $field_info['name'])) {
                return $key;
            }
        }

        return false;
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
                            case Utilities::DRV_SQLSRV:
                                switch ($dbType) {
                                case 'bit':
                                    $fieldVal = (Utilities::boolval($fieldVal) ? 1 : 0);
                                    break;
                                }
                                break;
                            case Utilities::DRV_MYSQL:
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
                    case Utilities::DRV_SQLSRV:
                        $parsed[$name] = new CDbExpression('(SYSDATETIMEOFFSET())');
                        break;
                    case Utilities::DRV_MYSQL:
                        $parsed[$name] = new CDbExpression('(NOW())');
                        break;
                    }
                }
                break;
            case 'lastmodifieddate':
            case 'last_modified_date':
                switch ($this->_driverType) {
                case Utilities::DRV_SQLSRV:
                    $parsed[$name] = new CDbExpression('(SYSDATETIMEOFFSET())');
                    break;
                case Utilities::DRV_MYSQL:
                    $parsed[$name] = new CDbExpression('(NOW())');
                    break;
                }
                break;
            case 'createdbyid':
            case 'created_by_id':
                if (!$for_update) {
                    $userId = Utilities::getCurrentUserId();
                    if (isset($userId)) {
                        $parsed[$name] = $userId;
                    }
                }
                break;
            case 'lastmodifiedbyid':
            case 'last_modified_by_id':
                $userId = Utilities::getCurrentUserId();
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
        if (empty($fields)) {
            $fields = $this->listAllFieldsFromDescribe($avail_fields);
        }
        $field_arr = array_map('trim', explode(",", $fields));
        $as_arr = array_map('trim', explode(",", $fields_as));
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
            $field_info = $this->getFieldFromDescribe($field, $avail_fields);
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
                case Utilities::DRV_SQLSRV:
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
        $field_arr = array_map('trim', explode(",", $fields));
        foreach ($field_arr as $field) {
            // find the type
            if (false === $this->findFieldFromDescribe($field, $avail_fields)) {
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

        $this->checkTableExists($table);
        try {
            $field_info = $this->describeTableFields($table);
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
            // todo figure out primary key
            if (empty($out_fields) || (0 === strcasecmp('id', $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    array('id' => $ids[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
                $temp = $this->retrieveSqlRecordsByIds($table, implode(',', $ids), 'id', $out_fields);
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

        $this->checkTableExists($table);
        try {
            $field_info = $this->describeTableFields($table);
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
            // todo figure out primary key
            if (empty($out_fields) || (0 === strcasecmp('id', $out_fields))) {
                return array(array('id' => $id));
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
                return $this->retrieveSqlRecordsByIds($table, $id, 'id', $out_fields);
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
        if (empty($id_field)) {
            throw new Exception("Identifying field can not be empty.");
        }
        if (!isset($records) || !is_array($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }

        $this->checkTableExists($table);
        try {
            $field_info = $this->describeTableFields($table);
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
                    $rows = $command->update($this->_tablePrefix . $table, $record, array('in', 'id', $id));
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
            if (empty($out_fields) || (0 === strcasecmp('id', $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    array('id' => $ids[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
                $temp =  $this->retrieveSqlRecordsByIds($table, implode(',', $ids), 'id', $out_fields);
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
        if (empty($id_field)) {
            throw new Exception("Identifying field can not be empty.");
        }
        if (empty($id_list)) {
            throw new Exception("Identifying values for '$id_field' can not be empty for update request.");
        }
        if (!is_array($record) || empty($record)) {
            throw new Exception("No record fields were passed in the request.");
        }
        $this->checkTableExists($table);
        try {
            $record = Utilities::removeOneFromArray($id_field, $record);
            $field_info = $this->describeTableFields($table);
            // simple update request
            $record = $this->parseRecord($record, $field_info, true);
            if (empty($record)) {
                throw new Exception("No valid field values were passed in the request.");
            }
            $ids = array_map('trim', explode(',', $id_list));
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
                    $rows = $command->update($this->_tablePrefix . $table, $record, array('in', 'id', $id));
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
            if (empty($out_fields) || (0 === strcasecmp('id', $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($outIds[$i]) ?
                                    array('id' => $outIds[$i]) :
                                    (isset($errors[$i]) ? $errors[$i] : null));
                }
            }
            else {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
                $temp = $this->retrieveSqlRecordsByIds($table, implode(',', $ids), 'id', $out_fields);
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
        $this->checkTableExists($table);
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
//                throw new Exception("Record with $id_field '$id' not found in table '$table'.");
            }

            $results = array();
            // todo figure out primary key
            if (!empty($out_fields)) {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
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
        if (empty($id_field)) {
            throw new Exception("Identifying field can not be empty.");
        }
        if (!isset($records) || !is_array($records) || empty($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
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
        if (empty($id_field)) {
            throw new Exception("Identifying field can not be empty.");
        }
        if (empty($id_list)) {
            throw new Exception("Identifying values for '$id_field' can not be empty for update request.");
        }

        $this->checkTableExists($table);
        try {
            $field_info = $this->describeTableFields($table);
            $ids = array_map('trim', explode(",", $id_list));
            $errors = array();
            $count = count($ids);
            $command = $this->_sqlConn->createCommand();

            // get the returnable fields first, then issue delete
            $outResults = array();
            // todo figure out primary key
            if (!(empty($out_fields) || (0 === strcasecmp('id', $out_fields)))) {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
                $outResults = $this->retrieveSqlRecordsByIds($table, implode(',', $ids), 'id', $out_fields);
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
                    $rows = $command->delete($this->_tablePrefix . $table, array('in', 'id', $id));
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
            // todo figure out primary key
            if (empty($out_fields) || (0 === strcasecmp('id', $out_fields))) {
                for ($i=0; $i<$count; $i++) {
                    $results[$i] = (isset($ids[$i]) ?
                                    array('id' => $ids[$i]) :
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
        $this->checkTableExists($table);
        try {
            $command = $this->_sqlConn->createCommand();
            $results = array();
            // get the returnable fields first, then issue delete
            // todo figure out primary key
            if (!empty($out_fields)) {
                $out_fields = Utilities::addOnceToList($out_fields, 'id');
                $results = $this->retrieveSqlRecordsByFilter($table, $out_fields, $filter);
            }

            // parse filter
            $command->reset();
            $rows = $command->delete($this->_tablePrefix . $table, $filter);
            if (0 >= $rows) {
                throw new Exception("No records updated in table '$table'.");
//                throw new Exception("Record with $id_field '$id' not found in table '$table'.");
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
        if (empty($id_field)) {
            throw new Exception("Identifying field can not be empty.");
        }
        if (!isset($records) || !is_array($records)) {
            throw new Exception('[InvalidParam]: There are no record sets in the request.');
        }
        if (empty($records)) {
            return array();
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
        if (empty($id_field)) {
            throw new Exception("Identifying field can not be empty.");
        }
        if (empty($id_list)) {
            return array();
        }
        $ids = array_map('trim', explode(',', $id_list));
        $this->checkTableExists($table);
        try {
            $availFields = $this->describeTableFields($table);
            if (!empty($fields)) {
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
     * @return array
     * @throws Exception
     */
    public function retrieveSqlRecordsByFilter($table, $fields = '', $filter = '', $limit = 0, $order = '', $offset = 0)
    {
        try {
            $this->checkTableExists($table);
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

            // count total records in some scenarios
            if (!(($limit > 0) && ($offset > 0))) {
                $command->reset();
                $command->select('(COUNT(*)) as ' . $this->_sqlConn->quoteColumnName('total'));
                $command->from($this->_tablePrefix . $table);
                if (!empty($filter)) {
                    $command->where($filter);
                }
                $reader = $command->query();
                $total = 0;
                $reader->bindColumn('total', $total, PDO::PARAM_INT);
                $reader->setFetchMode(PDO::FETCH_BOUND);
                if ($row = $reader->read()) {
//                    $data['count'] = $count;
                    $data['total'] = $total;
                }
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

    protected function buildColumnType($field)
    {
        if (empty($field)) {
            throw new Exception("No field given.");
        }

        try {
            $definition = Utilities::getArrayValue('definition', $field, '');
            if (!empty($definition)) {
                // raw definition, just pass it on
                return $definition;
            }
            $type = Utilities::getArrayValue('type', $field, '');
            if (empty($type)) {
                throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no type element.");
            }
            $allowNull = Utilities::getArrayValue('allow_null', $field, true);
            $length = Utilities::getArrayValue('length', $field, null);
            if (!isset($length)) {
                $length = Utilities::getArrayValue('size', $field, null);
            }
            $default = Utilities::getArrayValue('default', $field, null);
            $isPrimaryKey = Utilities::getArrayValue('is_primary_key', $field, false);

            /* abstract types handled by yii directly for each driver type

                pk: a generic primary key type, will be converted into int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY for MySQL;
                string: string type, will be converted into varchar(255) for MySQL;
                text: text type (long string), will be converted into text for MySQL;
                integer: integer type, will be converted into int(11) for MySQL;
                float: floating number type, will be converted into float for MySQL;
                decimal: decimal number type, will be converted into decimal for MySQL;
                datetime: datetime type, will be converted into datetime for MySQL;
                timestamp: timestamp type, will be converted into timestamp for MySQL;
                time: time type, will be converted into time for MySQL;
                date: date type, will be converted into date for MySQL;
                binary: binary data type, will be converted into blob for MySQL;
                boolean: boolean type, will be converted into tinyint(1) for MySQL;
                money: money/currency type, will be converted into decimal(19,4) for MySQL.
            */
            switch (strtolower($type)) {
            // handle non-abstract types here
            case 'pk':
                // if no other specifics use yii abstract type
                $definition = 'pk';
                $allowNull = true; // override addition below
                $isPrimaryKey = false; // override addition below
                break;
            // date and time fields
            case 'timestamp':
                $definition = 'timestamp'; // behaves differently, sometimes just a number (sqlsrv), not a date!
                $allowNull = true; // override addition below
                break;
            case 'datetimeoffset':
                switch ($this->_driverType) {
                case Utilities::DRV_SQLSRV:
                    $definition = 'datetimeoffset';
                    break;
                default:
                    $definition = 'timestamp';
                    $allowNull = true; // override addition below
                    break;
                }
                break;
            case 'datetime':
                $definition = (Utilities::DRV_SQLSRV === $this->_driverType) ? 'datetime2' : 'datetime'; // microsoft recommends
                break;
            case 'year':
                $definition = (Utilities::DRV_MYSQL === $this->_driverType) ? 'year' : 'date';
                break;
            // numbers
            case 'bool':
                $definition = 'boolean';
                break;
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
            case 'integer':
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType) && ('mediumint' == $type)) ? 'int' : $type;
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_MYSQL === $this->_driverType) && ($length <= 255) && ($length > 0)) {
                        $definition .= "($length)"; // sets the viewable length
                    }
                }
                break;
            case 'decimal':
            case 'numeric':
            case 'number':
            case 'percent':
                $definition = 'decimal';
                $precision = Utilities::getArrayValue('precision', $field, $length);
                if (isset($precision)) {
                    $precision = intval($precision);
                    if (((Utilities::DRV_MYSQL === $this->_driverType) && ($precision > 65)) ||
                        ((Utilities::DRV_SQLSRV === $this->_driverType) && ($precision > 38))) {
                        throw new Exception("Decimal precision '$precision' is out of valid range.");
                    }
                    $scale = Utilities::getArrayValue('scale', $field, null);
                    if (empty($scale)) {
                        $scale = Utilities::getArrayValue('decimals', $field, null);
                    }
                    if (!empty($scale)) {
                        if (((Utilities::DRV_MYSQL === $this->_driverType) && ($scale > 30)) ||
                            ((Utilities::DRV_SQLSRV === $this->_driverType) && ($scale > 18)) ||
                            ($scale > $precision)) {
                            throw new Exception("Decimal scale '$scale' is out of valid range.");
                        }
                        $definition .= "($precision,$scale)";
                    }
                    else {
                        $definition .= "($precision)";
                    }
                }
                break;
            case 'float':
            case 'double':
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType)) ? 'float' : $type;
                $precision = Utilities::getArrayValue('precision', $field, $length);
                if (isset($precision)) {
                    $precision = intval($precision);
                    if (((Utilities::DRV_MYSQL === $this->_driverType) && ($precision > 53)) ||
                        ((Utilities::DRV_SQLSRV === $this->_driverType) && ($precision > 38))) {
                        throw new Exception("Decimal precision '$precision' is out of valid range.");
                    }
                    $scale = Utilities::getArrayValue('scale', $field, null);
                    if (empty($scale)) {
                        $scale = Utilities::getArrayValue('decimals', $field, null);
                    }
                    if (!empty($scale) && !(Utilities::DRV_SQLSRV === $this->_driverType)) {
                        if (((Utilities::DRV_MYSQL === $this->_driverType) && ($scale > 30)) ||
                            ($scale > $precision)) {
                            throw new Exception("Decimal scale '$scale' is out of valid range.");
                        }
                        $definition .= "($precision,$scale)";
                    }
                    else {
                        $definition .= "($precision)";
                    }
                }
                break;
            case 'money':
            case 'smallmoney':
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType)) ? $type : 'money'; // let yii handle it
                break;
            // string types
            case 'text':
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType)) ? 'varchar(max)' : 'text'; // microsoft recommended
                break;
            case 'ntext':
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType)) ? 'nvarchar(max)' : 'text'; // microsoft recommended
                break;
            case 'varbinary':
            case 'varchar':
                $definition = 'varchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_SQLSRV === $this->_driverType) && ($length > 8000)) {
                        $length = 'max';
                    }
                    if ((Utilities::DRV_MYSQL === $this->_driverType) && ($length > 65535)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                break;
            case 'char':
                $definition = 'char';
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_SQLSRV === $this->_driverType) && ($length > 8000)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    if ((Utilities::DRV_MYSQL === $this->_driverType) && ($length > 255)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                break;
            case 'nvarchar':
                $definition = 'nvarchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_SQLSRV === $this->_driverType) && ($length > 4000)) {
                        $length = 'max';
                    }
                    if ((Utilities::DRV_MYSQL === $this->_driverType) && ($length > 65535)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                break;
            case 'nchar':
                $definition = 'nchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_SQLSRV === $this->_driverType) && ($length > 4000)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    if ((Utilities::DRV_MYSQL === $this->_driverType) && ($length > 255)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                break;
            // dreamfactory specific
            case 'id':
                // if no other specifics use yii abstract type
                $definition = 'pk';
                $allowNull = true; // override addition below
                $isPrimaryKey = false; // override addition below
                break;
            case 'currency':
                $definition = 'money';
                break;
            case "textarea":
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType)) ? 'varchar(max)' : 'text';
                break;
            case 'picklist':
                // use enum for mysql?
                $definition = 'nvarchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_SQLSRV === $this->_driverType) && ($length > 4000)) {
                        $length = 'max';
                    }
                    $definition .= "($length)";
                }
                break;
            case 'multipicklist':
                // use set for mysql?
                $definition = 'nvarchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((Utilities::DRV_SQLSRV === $this->_driverType) && ($length > 4000)) {
                        $length = 'max';
                    }
                    $definition .= "($length)";
                }
                break;
            case 'phone':
                $definition = 'varchar(20)';
                break;
            case 'email':
                $definition = 'varchar(320)';
                break;
            case 'url':
                $definition = ((Utilities::DRV_SQLSRV === $this->_driverType)) ? 'varchar(max)' : 'text';
                break;
            case "reference":
                $definition = 'int';
                break;
            default:
                // blind copy of column type
                $definition = $type;
            }
            if (!$allowNull) {
                $definition .= ' NOT NULL';
            }
            if (isset($default)) {
                if ('' === $default) $default = "''";
                $definition .= ' DEFAULT ' . $default;
            }
            elseif ($isPrimaryKey) {
                $definition .= ' PRIMARY KEY';
            }

            return $definition;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $tableName
     * @param array $fields
     * @param bool $for_create
     * @return string
     * @throws Exception
     */
    protected function buildTableFields($tableName, $fields, $for_create = true)
    {
        if (empty($fields)) {
            throw new Exception("No fields given.");
        }
        $columns = array();
        $references = array();
        $labels = array();
        $hasPrimaryKey = false;
        $hasTimeStamp = false;
        if (!isset($fields[0])) {
            $fields = array($fields);
        }
        foreach ($fields as $field) {
            try {
                $name = Utilities::getArrayValue('name', $field, '');
                if (empty($name)) {
                    throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no name element.");
                }
                $definition = $this->buildColumnType($field);
                if (!empty($definition)) {
                    $columns[$name] = $definition;
                }
                $type = Utilities::getArrayValue('type', $field, '');
                if (empty($type)) {
                    // raw definition, just pass it on
                    continue;
                }
                $picklist = '';
                switch (strtolower($type)) {
                // handle non-abstract types here
                case 'id':
                case 'pk':
                    // if no other specifics use yii abstract type
                    if ($hasPrimaryKey) {
                        throw new Exception("Designating more than one column as a primary key is not allowed.");
                    }
                    $hasPrimaryKey = true;
                    break;
                // date and time fields
                case 'timestamp':
                    if ($hasTimeStamp) {
                        throw new Exception("Designating more than one column as a timestamp is not allowed.");
                    }
                    $hasTimeStamp = true;
                    break;
                // dreamfactory specific
                case 'picklist':
                case 'multipicklist':
                    $picklist = '';
                    $values = Utilities::getArrayValue('value', $field, '');
                    if (empty($values)) {
                        $values = (isset($field['values']['value'])) ? $field['values']['value'] : array();
                    }
                    if (empty($values)) {
                        throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no value element on picklist type.");
                    }
                    foreach ($values as $value) {
                        if (!empty($picklist)) {
                            $picklist .= "\r";
                        }
                        $picklist .= $value;
                    }
                    break;
                case "reference":
                    // special case for references because the table referenced may not be created yet
                    $refTable = Utilities::getArrayValue('ref_table', $field, '');
                    if (empty($refTable)) {
                        throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no table element for reference type.");
                    }
                    $refColumns = Utilities::getArrayValue('ref_fields', $field, 'id');

                    // will get to it later, $refTable may not be there
                    $keyName = 'fk_' . $tableName . '_' . $name;
                    $references[] = array('name' => $keyName,
                                          'table' => $tableName,
                                          'column' => $name,
                                          'ref_table' => $refTable,
                                          'ref_fields' => $refColumns,
                                          'delete' => null,
                                          'update' => null);
                    break;
                default:
                }

                // labels
                $label = (isset($field['label'])) ? $field['label'] : '';
                if (!empty($label) || !empty($picklist)) {
                    $labels[] = array('table' => $tableName,
                                      'field' => $name,
                                      'label' => $label,
                                      'plural' => '',
                                      'picklist' => $picklist);
                }
            }
            catch (Exception $ex) {
                throw $ex;
            }
        }

        return array('columns' => $columns, 'references' => $references, 'labels' => $labels);
    }

    /**
     * @param $data
     * @param bool $return_labels_refs
     * @throws Exception
     * @return array
     */
    protected function createTable($data, $return_labels_refs=false)
    {
        $tableName = Utilities::getArrayValue('name', $data, '');
        if (empty($tableName)) {
            throw new Exception("Table schema received does not have a valid name.");
        }
        // does it already exist
        if ($this->doesTableExist($tableName)) {
            throw new Exception("A table with name '$tableName' already exist in the database.");
        }
        // add the table to the default schema
        $fields = (isset($data['field'])) ? $data['field'] : array();
        if (empty($fields)) {
            $fields = (isset($data['fields']['field'])) ? $data['fields']['field'] : array();
        }
        if (empty($fields)) {
            throw new Exception("No valid fields exist in the received table schema.");
        }
        if (!isset($fields[0])) {
            $fields = array($fields);
        }
        try {
            $results = $this->buildTableFields($tableName, $fields);
            $columns = Utilities::getArrayValue('columns', $results, null);
            if (empty($columns)) {
                throw new Exception("No valid fields exist in the received table schema.");
            }
            $command = $this->_sqlConn->createCommand();
            $command->createTable($tableName, $columns);

            $labels = Utilities::getArrayValue('labels', $results, null);
            // add table labels
            $label = Utilities::getArrayValue('label', $data, '');
            $plural = Utilities::getArrayValue('plural', $data, '');
            if (!empty($label) || !empty($plural)) {
                $labels[] = array('table' => $tableName,
                                  'field' => '',
                                  'label' => $label,
                                  'plural' => $plural,
                                  'picklist' => '');
            }
            $references = Utilities::getArrayValue('references', $results, null);
            if ($return_labels_refs) {
                return array('references' => $references, 'labels' => $labels);
            }

            if (!empty($labels) && $this->doesTableExist('label')) {
                // todo batch this for speed
                foreach ($labels as $label) {
                    $command->reset();
                    $rows = $command->insert('label',
                                             array('table' => $label['table'],
                                                   'field' => $label['field'],
                                                   'label' => $label['label'],
                                                   'picklist' => $label['picklist']
                                             ));
                }
            }
            if (!empty($references)) {
                foreach ($references as $reference) {
                    $command->reset();
                    $rows = $command->addForeignKey($reference['name'],
                                                    $reference['table'],
                                                    $reference['column'],
                                                    $reference['ref_table'],
                                                    $reference['ref_fields'],
                                                    $reference['delete'],
                                                    $reference['update']
                                                    );

                }
            }

            return array('name' => $tableName);
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param $data
     * @param bool $return_labels_refs
     * @throws Exception
     * @return array
     */
    protected function updateTable($data, $return_labels_refs=false)
    {
        $tableName = Utilities::getArrayValue('name', $data, '');
        if (empty($tableName)) {
            throw new Exception("Table schema received does not have a valid name.");
        }
        // does it already exist
        if (!$this->doesTableExist($tableName)) {
            throw new Exception("Update schema called on a table with name '$tableName' that does not exist in the database.");
        }

        // is there a name update
        $newName = Utilities::getArrayValue('new_name', $data, '');
        if (!empty($newName)) {
            // todo change table name, has issue with references
        }

        // update column types
        $fields = (isset($data['field'])) ? $data['field'] : array();
        if (empty($fields)) {
            $fields = (isset($data['fields']['field'])) ? $data['fields']['field'] : array();
        }
        if (empty($fields)) {
            throw new Exception("No valid fields exist in the received table schema.");
        }
        if (!isset($fields[0])) {
            $fields = array($fields);
        }
        try {
            $references = array();
            $labels = array();
            $hasPrimaryKey = true; // todo
            $hasTimeStamp = true; // todo
            $schema = $this->_sqlConn->schema->getTable($tableName);
            $command = $this->_sqlConn->createCommand();
            foreach ($fields as $field) {
                try {
                    $name = Utilities::getArrayValue('name', $field, '');
                    if (empty($name)) {
                        throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no name element.");
                    }
                    $colSchema = $schema->getColumn($name);
                    if (isset($colSchema)) {
                        // todo manage type changes
                        // drop references
                        // add new reference if needed
                    }
                    else {
                        // add column
                        $definition = $this->buildColumnType($field);
                        $type = Utilities::getArrayValue('type', $field, '');
                        $picklist = '';
                        switch (strtolower($type)) {
                        // handle non-abstract types here
                        case 'id':
                        case 'pk':
                            // if no other specifics use yii abstract type
                            if ($hasPrimaryKey) {
                                throw new Exception("Designating more than one column as a primary key is not allowed.");
                            }
                            $hasPrimaryKey = true;
                            break;
                        // date and time fields
                        case 'timestamp':
                            if ($hasTimeStamp) {
                                throw new Exception("Designating more than one column as a timestamp is not allowed.");
                            }
                            $hasTimeStamp = true;
                            break;
                        // dreamfactory specific
                        case 'picklist':
                        case 'multipicklist':
                            $picklist = '';
                            $values = Utilities::getArrayValue('value', $field, '');
                            if (empty($values)) {
                                $values = (isset($field['values']['value'])) ? $field['values']['value'] : array();
                            }
                            if (empty($values)) {
                                throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no value element on picklist type.");
                            }
                            foreach ($values as $value) {
                                if (!empty($picklist)) {
                                    $picklist .= "\r";
                                }
                                $picklist .= $value;
                            }
                            break;
                        case "reference":
                            // special case for references because the table referenced may not be created yet
                            $refTable = (isset($field['table'])) ? $field['table'] : '';
                            if (empty($refTable)) {
                                throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no table element for reference type.");
                            }
                            $refColumns = (isset($field['column'])) ? $field['column'] : 'id';

                            // will get to it later, $refTable may not be there
                            $keyName = 'fk_' . $tableName . '_' . $name;
                            $references[] = array('name' => $keyName,
                                                  'table' => $tableName,
                                                  'column' => $name,
                                                  'ref_table' => $refTable,
                                                  'ref_fields' => $refColumns,
                                                  'delete' => null,
                                                  'update' => null);
                            break;
                        default:
                        }
                        // need to add labels
                        $label = (isset($field['label'])) ? $field['label'] : '';
                        if (!empty($label) || !empty($picklist)) {
                            $labels[] = array('table' => $tableName,
                                              'field' => $name,
                                              'label' => $label,
                                              'plural' => '',
                                              'picklist' => $picklist);
                        }

                        $command->reset();
                        $command->addColumn($tableName, $name, $definition);
                    }
                }
                catch (Exception $ex) {
                    throw $ex;
                }
            }

            if ($return_labels_refs) {
                return array('references' => $references, 'labels' => $labels);
            }

            if (!empty($labels) && $this->doesTableExist('label')) {
                // todo batch this for speed
                foreach ($labels as $label) {
                    $command->reset();
                    $rows = $command->insert('label',
                                             array('table' => $label['table'],
                                                   'field' => $label['field'],
                                                   'label' => $label['label'],
                                                   'picklist' => $label['picklist']
                                             ));
                }
            }
            if (!empty($references)) {
                foreach ($references as $reference) {
                    $command->reset();
                    $rows = $command->addForeignKey($reference['name'],
                                                    $reference['table'],
                                                    $reference['column'],
                                                    $reference['ref_table'],
                                                    $reference['ref_fields'],
                                                    $reference['delete'],
                                                    $reference['update']
                                                    );

                }
            }

            return array('name' => $tableName);
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param array $tables
     * @param bool $allow_merge
     * @param bool $rollback
     * @throws Exception
     * @return array
     */
    public function createTables($tables, $allow_merge=true, $rollback=true)
    {
        // refresh the schema so we have the latest
        Yii::app()->db->schema->refresh();
        $references = array();
        $labels = array();
        $out = array();
        $count = 0;
        $created = array();
        if (isset($tables[0])) {
            foreach ($tables as $table) {
                try {
                    $name = Utilities::getArrayValue('name', $table, '');
                    if (empty($name)) {
                        throw new Exception("Table schema received does not have a valid name.");
                    }
                    // does it already exist
                    if ($this->doesTableExist($name)) {
                        if ($allow_merge) {
                            $results = $this->updateTable($table, true);
                        }
                        else {
                            throw new Exception("A table with name '$name' already exist in the database.");
                        }
                    }
                    else {
                        $results = $this->createTable($table, true);
                        if ($rollback) {
                            $created[] = $name;
                        }
                    }
                    $labels = array_merge($labels, Utilities::getArrayValue('labels', $results, array()));
                    $references = array_merge($references, Utilities::getArrayValue('references', $results, array()));
                    $out[$count] = array('name' => $name);
                }
                catch (Exception $ex) {
                    if ($rollback) {
                        // delete any created tables
                        throw $ex;
                    }
                    $out[$count] = array('fault' => array('faultString' => $ex->getMessage(),
                                                          'faultCode' => 'RequestFailed'));
                }
                $count++;
            }
        }
        else { // single table, references must already be present
            try {
                $name = Utilities::getArrayValue('name', $tables, '');
                if (empty($name)) {
                    throw new Exception("Table schema received does not have a valid name.");
                }
                // does it already exist
                if ($this->doesTableExist($name)) {
                    if ($allow_merge) {
                        $results = $this->updateTable($tables, false);
                    }
                    else {
                        throw new Exception("A table with name '$name' already exist in the database.");
                    }
                }
                else {
                    $results = $this->createTable($tables, false);
                    if ($rollback) {
                        $created[] = $name;
                    }
                }
                $out[$count] = $results;
            }
            catch (Exception $ex) {
                if ($rollback) {
                    throw $ex;
                }
                $out[$count] = array('fault' => array('faultString' => $ex->getMessage(),
                                                      'faultCode' => 'RequestFailed'));
            }
        }

        // create the additional items
        try {
            $command = $this->_sqlConn->createCommand();
            if (!empty($references)) {
                foreach ($references as $reference) {
                    $command->reset();
                    $rows = $command->addForeignKey($reference['name'],
                                                    $reference['table'],
                                                    $reference['column'],
                                                    $reference['ref_table'],
                                                    $reference['ref_fields'],
                                                    $reference['delete'],
                                                    $reference['update']
                                                    );

                }
            }
        }
        catch (Exception $ex) {
            if ($rollback) {
                // delete any created tables
            }
            throw new Exception("Schema tables were create, but not all foreign keys were added.\n{$ex->getMessage()}");
        }
        try {
            if (!empty($labels) && $this->doesTableExist('label')) {
                // todo batch this for speed
                foreach ($labels as $label) {
                    $command->reset();
                    $rows = $command->insert('label',
                                             array('table' => $label['table'],
                                                   'field' => $label['field'],
                                                   'label' => $label['label'],
                                                   'picklist' => $label['picklist']
                                             ));
                }
            }
        }
        catch (Exception $ex) {
            if ($rollback) {
                // delete any created tables
            }
            throw new Exception("Schema tables were create, but not all labels were added.\n{$ex->getMessage()}");
        }

        // refresh the schema that we just added
        Yii::app()->db->schema->refresh();
        return $out;
    }

}
