<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage DatabaseSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class SchemaSvc extends CommonService implements iRestHandler
{

    // Members

    /**
     * @var
     */
    protected $tableName;
    /**
     * @var
     */
    protected $fieldName;

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
            $result = array('resource' => $result['table']);
            break;
        default:
            if (empty($this->fieldName)) {
                $result = $this->describeTable($this->tableName);
            }
            else {
                $result = $this->describeField($this->tableName, $this->fieldName);
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
            $result = array();
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
            if (empty($this->fieldName)) {
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
            $result = array();
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
            if (empty($this->fieldName)) {
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
            $result = array();
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
            if (empty($this->fieldName)) {
                throw new Exception('Invalid format for DELETE Table request.');
            }
            $result = $this->deleteTable($this->fieldName);
            $result = array('table' => $result);
            break;
        default:
            $result = array();
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
        $this->fieldName = (isset($resource[1])) ? $resource[1] : '';
    }

    /**
     * @return array
     * @throws Exception
     */
    public function describeDatabase()
    {
        // check for system tables and deny
//        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        $sysTables = SystemManager::INTERNAL_TABLES;
        try {
            return DbUtilities::describeDatabase($this->sqlDb->getSqlConn(), '', $sysTables);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database tables.\n{$ex->getMessage()}");
        }
    }

    public function describeTables($table_list)
    {
        // check for system tables and deny
//        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        $sysTables = SystemManager::INTERNAL_TABLES;
        $tables = array_map('trim', explode(',', trim($table_list, ',')));
        foreach ($tables as $table) {
            if (Utilities::isInList($sysTables, $table, ',')) {
                throw new Exception("System table '$table' not available through this interface.");
            }
        }
        try {
            return DbUtilities::describeTables($this->sqlDb->getSqlConn(), $tables);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database tables '$table_list'.\n{$ex->getMessage()}");
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
//        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        $sysTables = SystemManager::INTERNAL_TABLES;
        if (Utilities::isInList($sysTables, $table, ',')) {
            throw new Exception("System table '$table' not available through this interface.");
        }
        try {
            return DbUtilities::describeTable($this->sqlDb->getSqlConn(), $table);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database table '$table'.\n{$ex->getMessage()}");
        }
    }

    public function describeField($table, $field)
    {
        // check for system tables and deny
        $sysTables = SystemManager::SYSTEM_TABLES . ',' . SystemManager::INTERNAL_TABLES;
        if (Utilities::isInList($sysTables, $table, ',')) {
            throw new Exception("System table '$table' not available through this interface.");
        }
        try {
            return DbUtilities::describeTable($this->sqlDb->getSqlConn(), $table);
        }
        catch (Exception $ex) {
            throw new Exception("Error describing database table '$table'.\n{$ex->getMessage()}");
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

        return DbUtilities::createTables($this->sqlDb->getSqlConn(), $tables);
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

        return DbUtilities::createTables($this->sqlDb->getSqlConn(), $tables, true);
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

        return DbUtilities::dropTable($this->sqlDb->getSqlConn(), $table);
    }

}
