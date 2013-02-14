<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Utilities
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class DbUtilities
{

    // constants

    // DB driver types
    const DRV_OTHER  = 0;
    const DRV_SQLSRV = 1;
    const DRV_MYSQL  = 2;
    const DRV_SQLITE = 3;
    const DRV_PGSQL  = 4;
    const DRV_OCSQL  = 5;

    /**
     * @param CDbConnection $db
     * @return int
     */
    public static function getDbDriverType($db)
    {
        switch ($db->driverName) {
        case 'mssql':
        case 'dblib':
        case 'sqlsrv':
            return self::DRV_SQLSRV;
        case 'mysqli':
        case 'mysql':
            return self::DRV_MYSQL;
        case 'sqlite':
        case 'sqlite2':
            return self::DRV_SQLITE;
        case 'oci':
            return self::DRV_OCSQL;
        case 'pgsql':
            return self::DRV_PGSQL;
        default:
            return self::DRV_OTHER;
        }
    }

    /**
     * @param CDbConnection $db
     * @param $name
     * @throws InvalidArgumentException
     * @throws Exception
     * @return string
     */
    public static function correctTableName($db, $name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Table name can not be empty.');
        }
        $tables = $db->schema->getTableNames();
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
     * @param CDbConnection $db
     * @param $name
     * @return bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function doesTableExist($db, $name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Table name can not be empty.');
        }
        $tables = $db->schema->getTableNames();
        // make search case insensitive
        foreach ($tables as $table) {
            if (0 == strcasecmp($table, $name)) {
                return true;
            }
        }

        error_log(print_r($tables, true));
        return false;
    }

    /**
     * @param CDbConnection $db
     * @param string $include
     * @param string $exclude
     * @return array
     * @throws Exception
     */
    public static function describeDatabase($db, $include = '', $exclude = '')
    {
        // todo need to assess schemas in ms sql and load them separately.
        try {
            $names = $db->schema->getTableNames();
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
            $labels = static::getLabels(array('and', "field=''", array('in', 'table', $names)),
                                        'table,label,plural');
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

    /**
     * @param $column
     * @param bool $found_pick_list
     * @return string
     */
    protected static function determineDfType($column, $found_pick_list=false)
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

    /**
     * @param $type
     * @return bool
     */
    protected static function determineMultiByteSupport($type)
    {
        switch ($type) {
        case 'nchar':
        case 'nvarchar':
            return true;
        default:
            return false;
        }
    }

    /**
     * @param $column
     * @return bool
     */
    protected static function determineRequired($column)
    {
        if ((1 == $column->allowNull) || (isset($column->defaultValue)) || (1 == $column->autoIncrement)) {
            return false;
        }
        return true;
    }

    /**
     * @param CDbConnection $db
     * @param $name
     * @return array
     * @throws Exception
     */
    public static function describeTable($db, $name)
    {
        $name = static::correctTableName($db, $name);
        try {
            $table = $db->schema->getTable($name);
            if (!$table) {
                throw new Exception("Table '$name' does not exist in the database.");
            }
            $labels = static::getLabels(array('in', 'table', $name));
            $label = '';
            $plural = '';
            foreach ($labels as $each) {
                if (empty($each['field'])) {
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
            $related = static::describeTableRelated($db, $name);

            $basic['field'] = $fields;
            $basic['related'] = $related;

            return array('table' => $basic);
        }
        catch (Exception $ex) {
            throw new Exception("Failed to query database schema.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param CDbConnection $db
     * @param null $names
     * @return array|string
     * @throws Exception
     */
    public static function describeTables($db, $names = null)
    {
        try {
            $out = array();
            foreach ($names as $table) {
                $temp = static::describeTable($db, $table);
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
     * @param CDbConnection $db
     * @param $name
     * @return array
     * @throws Exception
     */
    public static function describeTableFields($db, $name)
    {
        try {
            $table = $db->schema->getTable($name);
            if (!$table) {
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

            return $fields;
        }
        catch (Exception $ex) {
            throw new Exception("Failed to query table schema.\n{$ex->getMessage()}");
        }
    }

    /**
     * @param CDbConnection $db
     * @param $parent_table
     * @return array
     * @throws Exception
     */
    public static function describeTableRelated($db, $parent_table)
    {
        $names = $db->schema->getTableNames();
        natcasesort($names);
        $names = array_values($names);
        $related = array();
        foreach ($names as $name) {
            $table = $db->schema->getTable($name);
            $fks = $fks2 = $table->foreignKeys;
            foreach ($fks as $key => $value) {
                $refTable = Utilities::getArrayValue(0, $value, '');
                $refField = Utilities::getArrayValue(1, $value, '');
                if (0 === strcasecmp($refTable, $parent_table)) {
                    // other, must be has_many or many_many
                    $related[] = array('name' => Utilities::makePlural($name) .'_by_'. $key, 'type' => 'has_many',
                                       'table' => $name, 'field' => $key);
                    // if other has many relationships exist, we can say these are related as well
                    foreach ($fks2 as $key2 => $value2) {
                        $tmpTable = Utilities::getArrayValue(0, $value2, '');
                        $tmpField = Utilities::getArrayValue(1, $value2, '');
                        if ((0 !== strcasecmp($key, $key2)) && // not same key
                            (0 !== strcasecmp($tmpTable, $name)) && // not self-referencing table
                            (0 !== strcasecmp($parent_table, $name))) { // not same as parent, i.e. via reference back to self
                            // not the same key
                            $related[] = array('name' => Utilities::makePlural($tmpTable) .'_by_'. $name, 'type' => 'many_many',
                                               'table' => $tmpTable, 'field' => $tmpField,
                                               'join' => "$name($key,$key2)");
                        }
                    }
                }
                if (0 === strcasecmp($name, $parent_table)) {
                    // self, get belongs to relations
                    $related[] = array('name' => $refTable .'_by_'. $key, 'type' => 'belongs_to',
                                       'table' => $refTable, 'field' => $refField);
                }
            }
        }

        return $related;
    }

    /**
     * @param $avail_fields
     * @return string
     */
    public static function listAllFieldsFromDescribe($avail_fields)
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
    public static function getFieldFromDescribe($field_name, $avail_fields)
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
    public static function findFieldFromDescribe($field_name, $avail_fields)
    {
        foreach ($avail_fields as $key => $field_info) {
            if (0 == strcasecmp($field_name, $field_info['name'])) {
                return $key;
            }
        }

        return false;
    }

    /**
     * @param $avail_fields
     * @return string
     */
    public static function getPrimaryKeyFieldFromDescribe($avail_fields)
    {
        foreach ($avail_fields as $field_info) {
            if ($field_info['is_primary_key']) {
                return $field_info['name'];
            }
        }

        return '';
    }

    /**
     * @param $field
     * @param int $driver_type
     * @throws Exception
     * @return array|string
     */
    protected static function buildColumnType($field, $driver_type = DbUtilities::DRV_MYSQL)
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
            $quoteDefault = false;
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
                switch ($driver_type) {
                case DbUtilities::DRV_SQLSRV:
                    $definition = 'datetimeoffset';
                    break;
                default:
                    $definition = 'timestamp';
                    $allowNull = true; // override addition below
                    break;
                }
                break;
            case 'datetime':
                $definition = (DbUtilities::DRV_SQLSRV === $driver_type) ? 'datetime2' : 'datetime'; // microsoft recommends
                break;
            case 'year':
                $definition = (DbUtilities::DRV_MYSQL === $driver_type) ? 'year' : 'date';
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
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type) && ('mediumint' == $type)) ? 'int' : $type;
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_MYSQL === $driver_type) && ($length <= 255) && ($length > 0)) {
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
                    if (((DbUtilities::DRV_MYSQL === $driver_type) && ($precision > 65)) ||
                        ((DbUtilities::DRV_SQLSRV === $driver_type) && ($precision > 38))) {
                        throw new Exception("Decimal precision '$precision' is out of valid range.");
                    }
                    $scale = Utilities::getArrayValue('scale', $field, null);
                    if (empty($scale)) {
                        $scale = Utilities::getArrayValue('decimals', $field, null);
                    }
                    if (!empty($scale)) {
                        if (((DbUtilities::DRV_MYSQL === $driver_type) && ($scale > 30)) ||
                            ((DbUtilities::DRV_SQLSRV === $driver_type) && ($scale > 18)) ||
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
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type)) ? 'float' : $type;
                $precision = Utilities::getArrayValue('precision', $field, $length);
                if (isset($precision)) {
                    $precision = intval($precision);
                    if (((DbUtilities::DRV_MYSQL === $driver_type) && ($precision > 53)) ||
                        ((DbUtilities::DRV_SQLSRV === $driver_type) && ($precision > 38))) {
                        throw new Exception("Decimal precision '$precision' is out of valid range.");
                    }
                    $scale = Utilities::getArrayValue('scale', $field, null);
                    if (empty($scale)) {
                        $scale = Utilities::getArrayValue('decimals', $field, null);
                    }
                    if (!empty($scale) && !(DbUtilities::DRV_SQLSRV === $driver_type)) {
                        if (((DbUtilities::DRV_MYSQL === $driver_type) && ($scale > 30)) ||
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
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type)) ? $type : 'money'; // let yii handle it
                break;
                // string types
            case 'text':
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type)) ? 'varchar(max)' : 'text'; // microsoft recommended
                $quoteDefault = true;
                break;
            case 'ntext':
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type)) ? 'nvarchar(max)' : 'text'; // microsoft recommended
                $quoteDefault = true;
                break;
            case 'varbinary':
            case 'varchar':
                $definition = 'varchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_SQLSRV === $driver_type) && ($length > 8000)) {
                        $length = 'max';
                    }
                    if ((DbUtilities::DRV_MYSQL === $driver_type) && ($length > 65535)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                $quoteDefault = true;
                break;
            case 'char':
                $definition = 'char';
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_SQLSRV === $driver_type) && ($length > 8000)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    if ((DbUtilities::DRV_MYSQL === $driver_type) && ($length > 255)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                $quoteDefault = true;
                break;
            case 'nvarchar':
                $definition = 'nvarchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_SQLSRV === $driver_type) && ($length > 4000)) {
                        $length = 'max';
                    }
                    if ((DbUtilities::DRV_MYSQL === $driver_type) && ($length > 65535)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                $quoteDefault = true;
                break;
            case 'nchar':
                $definition = 'nchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_SQLSRV === $driver_type) && ($length > 4000)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    if ((DbUtilities::DRV_MYSQL === $driver_type) && ($length > 255)) {
                        throw new Exception("String length '$length' is out of valid range.");
                    }
                    $definition .= "($length)";
                }
                $quoteDefault = true;
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
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type)) ? 'varchar(max)' : 'text';
                $quoteDefault = true;
                break;
            case 'picklist':
                // use enum for mysql?
                $definition = 'nvarchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_SQLSRV === $driver_type) && ($length > 4000)) {
                        $length = 'max';
                    }
                    $definition .= "($length)";
                }
                $quoteDefault = true;
                break;
            case 'multipicklist':
                // use set for mysql?
                $definition = 'nvarchar';
                if (isset($length)) {
                    $length = intval($length);
                    if ((DbUtilities::DRV_SQLSRV === $driver_type) && ($length > 4000)) {
                        $length = 'max';
                    }
                    $definition .= "($length)";
                }
                $quoteDefault = true;
                break;
            case 'phone':
                $definition = 'varchar(20)';
                $quoteDefault = true;
                break;
            case 'email':
                $definition = 'varchar(320)';
                $quoteDefault = true;
                break;
            case 'url':
                $definition = ((DbUtilities::DRV_SQLSRV === $driver_type)) ? 'varchar(max)' : 'text';
                $quoteDefault = true;
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
                if ($quoteDefault)
                    $default = "'" . $default . "'";
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
    protected static function buildTableFields($tableName, $fields, $for_create = true)
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
                $definition = static::buildColumnType($field);
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
                        throw new Exception("[BAD_SCHEMA]: Invalid schema detected - no table element for reference type of $name.");
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
                $label = Utilities::getArrayValue('label', $field, '');
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
     * @param CDbConnection $db
     * @param $data
     * @param bool $return_labels_refs
     * @param bool $check_sys
     * @throws Exception
     * @return array
     */
    public static function createTable($db, $data, $return_labels_refs=false, $check_sys=true)
    {
        $tableName = Utilities::getArrayValue('name', $data, '');
        if (empty($tableName)) {
            throw new Exception("Table schema received does not have a valid name.");
        }
        // does it already exist
        if (static::doesTableExist($db, $tableName)) {
            throw new Exception("A table with name '$tableName' already exist in the database.");
        }
        // check for system tables and deny
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        if ($check_sys && Utilities::isInList($sysTables, $tableName, ',')) {
            throw new Exception("System table '$tableName' not available through this interface.");
        }
        // add the table to the default schema
        $fields = Utilities::getArrayValue('field', $data, array());
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
            $results = static::buildTableFields($tableName, $fields);
            $columns = Utilities::getArrayValue('columns', $results, null);
            if (empty($columns)) {
                throw new Exception("No valid fields exist in the received table schema.");
            }
            $command = $db->createCommand();
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
            static::setLabels($labels);

            return array('name' => $tableName);
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param CDbConnection $db
     * @param $data
     * @param bool $return_labels_refs
     * @param bool $check_sys
     * @throws Exception
     * @return array
     */
    public static function updateTable($db, $data, $return_labels_refs=false, $check_sys=true)
    {
        $tableName = Utilities::getArrayValue('name', $data, '');
        if (empty($tableName)) {
            throw new Exception("Table schema received does not have a valid name.");
        }
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        if ($check_sys && Utilities::isInList($sysTables, $tableName, ',')) {
            throw new Exception("System table '$tableName' not available through this interface.");
        }
        // does it already exist
        if (!static::doesTableExist($db, $tableName)) {
            throw new Exception("Update schema called on a table with name '$tableName' that does not exist in the database.");
        }

        // is there a name update
        $newName = Utilities::getArrayValue('new_name', $data, '');
        if (!empty($newName)) {
            // todo change table name, has issue with references
        }

        // update column types
        $fields = Utilities::getArrayValue('field', $data, array());
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
            $schema = $db->schema->getTable($tableName);
            $command = $db->createCommand();
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
                        $definition = static::buildColumnType($field);
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
                            $refTable = Utilities::getArrayValue('ref_table', $field, '');
                            if (empty($refTable)) {
                                throw new Exception("Invalid schema detected - no table element for reference type of $name.");
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
                        // need to add labels
                        $label = Utilities::getArrayValue('label', $field, '');
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
            static::setLabels($labels);

            return array('name' => $tableName);
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param CDbConnection $db
     * @param array $tables
     * @param bool $allow_merge
     * @param bool $rollback
     * @param bool $check_sys
     * @throws Exception
     * @return array
     */
    public static function createTables($db, $tables, $allow_merge=true, $rollback=false, $check_sys=true)
    {
        // refresh the schema so we have the latest
        $db->schema->refresh();
        $references = array();
        $labels = array();
        $out = array();
        $count = 0;
        $created = array();
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;

        if (isset($tables[0])) {
            foreach ($tables as $table) {
                try {
                    $name = Utilities::getArrayValue('name', $table, '');
                    if (empty($name)) {
                        throw new Exception("Table schema received does not have a valid name.", 400);
                    }
                    // check for system tables and deny
                    if ($check_sys && Utilities::isInList($sysTables, $name, ',')) {
                        throw new Exception("System table '$name' not available through this interface.");
                    }
                    // does it already exist
                    if (static::doesTableExist($db, $name)) {
                        if ($allow_merge) {
                            $results = static::updateTable($db, $table, true, $check_sys);
                        }
                        else {
                            throw new Exception("A table with name '$name' already exist in the database.", 400);
                        }
                    }
                    else {
                        $results = static::createTable($db, $table, true, $check_sys);
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
                    $out[$count] = array('error' => array('message' => $ex->getMessage(),
                                                          'code' => $ex->getCode()));
                }
                $count++;
            }
        }
        else { // single table, references must already be present
            try {
                $name = Utilities::getArrayValue('name', $tables, '');
                if (empty($name)) {
                    throw new Exception("Table schema received does not have a valid name.", 400);
                }
                // does it already exist
                if (static::doesTableExist($db, $name)) {
                    if ($allow_merge) {
                        $results = static::updateTable($db, $tables, false, $check_sys);
                    }
                    else {
                        throw new Exception("A table with name '$name' already exist in the database.", 400);
                    }
                }
                else {
                    $results = static::createTable($db, $tables, false, $check_sys);
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
                $out[$count] = array('error' => array('message' => $ex->getMessage(),
                                                      'code' => $ex->getCode()));
            }
        }

        // create the additional items
        try {
            $command = $db->createCommand();
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
            static::setLabels($labels);
        }
        catch (Exception $ex) {
            throw new Exception("Schema tables were create, but not all labels were added.\n{$ex->getMessage()}");
        }

        // refresh the schema that we just added
        $db->schema->refresh();
        return $out;
    }

    /**
     * @param CDbConnection $db
     * @param string $tableName
     * @throws Exception
     * @return array
     */
    public static function dropTable($db, $tableName)
    {
        if (empty($tableName)) {
            throw new Exception("Table name received is empty.", ErrorCodes::BAD_REQUEST);
        }
        // check for system tables and deny
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        if (Utilities::isInList($sysTables, $tableName, ',')) {
            throw new Exception("System table '$tableName' not available through this interface.");
        }
        // does it already exist
        if (!static::doesTableExist($db, $tableName)) {
            throw new Exception("A table with name '$tableName' does not exist in the database.", ErrorCodes::NOT_FOUND);
        }
        try {
            $command = $db->createCommand();
            $command->dropTable($tableName);
            static::removeLabels('table = :tn', array(':tn' => $tableName));

            return array('name' => $tableName);
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param CDbConnection $db
     * @param $data
     * @param bool $return_labels_refs
     * @param bool $check_sys
     * @throws Exception
     * @return array
     */
    public static function addField($db, $data, $return_labels_refs=false, $check_sys=true)
    {
        $tableName = Utilities::getArrayValue('name', $data, '');
        if (empty($tableName)) {
            throw new Exception("Table schema received does not have a valid name.");
        }
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        if ($check_sys && Utilities::isInList($sysTables, $tableName, ',')) {
            throw new Exception("System table '$tableName' not available through this interface.");
        }
        // does it already exist
        if (!static::doesTableExist($db, $tableName)) {
            throw new Exception("Update schema called on a table with name '$tableName' that does not exist in the database.");
        }

        // is there a name update
        $newName = Utilities::getArrayValue('new_name', $data, '');
        if (!empty($newName)) {
            // todo change table name, has issue with references
        }

        // update column types
        $fields = Utilities::getArrayValue('field', $data, array());
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
            $schema = $db->schema->getTable($tableName);
            $command = $db->createCommand();
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
                        $definition = static::buildColumnType($field);
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
                            $refTable = Utilities::getArrayValue('ref_table', $field, '');
                            if (empty($refTable)) {
                                throw new Exception("Invalid schema detected - no table element for reference type of $name.");
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
                        // need to add labels
                        $label = Utilities::getArrayValue('label', $field, '');
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
            static::setLabels($labels);

            return array('name' => $tableName);
        }
        catch (Exception $ex) {
            error_log($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param string | array $where
     * @param string $select
     * @return array
     */
    public static function getLabels($where, $select='*')
    {
        $labels = array();
        if (static::doesTableExist(Yii::app()->db, 'label')) {
            $command = Yii::app()->db->createCommand();
            $command->select($select);
            $command->from('label');
            $command->where($where);
            $labels = $command->queryAll();
        }
        return $labels;
    }

    /**
     * @param $labels
     * @return void
     */
    public static function setLabels($labels)
    {
        if (!empty($labels) && static::doesTableExist(Yii::app()->db, 'label')) {
            // todo batch this for speed
            $command = Yii::app()->db->createCommand();
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

    /**
     * @param $where
     * @param null $params
     */
    public static function removeLabels($where, $params = null)
    {
        if (static::doesTableExist(Yii::app()->db, 'label')) {
//                Label::model()->deleteAll('table = :tn', array(':tn' => $tableName));
            $command = Yii::app()->db->createCommand();
            $command->delete('label', $where, $params);
        }
    }
}
