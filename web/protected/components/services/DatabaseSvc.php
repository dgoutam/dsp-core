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
     * @var boolean
     */
    protected $_isNative = false;

    /**
     * Creates a new DatabaseSvc instance
     *
     * @param array $config
     * @throws InvalidArgumentException
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $type = Utilities::getArrayValue('storage_type', $config, '');
        $credentials = Utilities::getArrayValue('credentials', $config, array());
        $dsn = Utilities::getArrayValue('dsn', $credentials, '');
        $user = Utilities::getArrayValue('user', $credentials, '');
        $pwd = Utilities::getArrayValue('pwd', $credentials, '');
        $attributes = Utilities::getArrayValue('parameters', $config, array());
        if (!empty($dsn)) {
            $this->_isNative = false;
            $this->sqlDb = new PdoSqlDbSvc('', $dsn, $user, $pwd, $attributes);
        }
        else {
            $this->_isNative = true;
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

    /**
     * Swagger output for common api parameters
     *
     * @param $parameters
     * @param string $method
     * @return array
     */
    public static function swaggerParameters($parameters, $method = '')
    {
        $swagger = array();
        foreach ($parameters as $param) {
            switch ($param) {
            case 'table_name':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Name of the table to perform operations on.",
                                   "dataType"=>"String",
                                   "required"=>true,
                                   "allowMultiple"=>false
                );
                break;
            case 'field_name':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Name of the table field/column to perform operations on.",
                                   "dataType"=>"String",
                                   "required"=>true,
                                   "allowMultiple"=>false
                );
                break;
            case 'id':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Identifier of the resource to retrieve.",
                                   "dataType"=>"String",
                                   "required"=>true,
                                   "allowMultiple"=>false
                );
                break;
            case 'ids':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Comma-delimited list of the identifiers of the resources to retrieve.",
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>true
                );
                break;
            case 'filter':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"SQL-like filter to limit the resources to retrieve.",
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'order':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"SQL-like order containing field and direction for filter results.",
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>true
                );
                break;
            case 'limit':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Set to limit the filter results.",
                                   "dataType"=>"int",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'include_count':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Include the total number of filter results.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'include_schema':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Include the schema of the table queried.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'fields':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Comma-delimited list of field names to retrieve for each record.",
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>true
                );
                break;
            case 'related':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Comma-delimited list of related names to retrieve for each record.",
                                   "dataType"=>"string",
                                   "required"=>false,
                                   "allowMultiple"=>true
                );
                break;
            case 'record':
                $swagger[] = array("paramType"=>"body",
                                   "name"=>$param,
                                   "description"=>"Array of record properties.",
                                   "dataType"=>"array",
                                   "required"=>true,
                                   "allowMultiple"=>true
                );
                break;
            }
        }

        return $swagger;
    }

    /**
     * @param string $service
     * @param string $description
     * @return array
     */
    public static function swaggerPerDb($service, $description='')
    {
        $swagger = array(
            array('path' => '/'.$service,
                  'description' => $description,
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "List tables available in the database service",
                            "notes"=> "Use the table names in available record operations.",
                            "responseClass"=> "array",
                            "nickname"=> "getTables",
                            "parameters"=> array(),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/'.$service.'/{table_name}',
                  'description' => 'Operations for per table administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve multiple records",
                            "notes"=> "Use the 'ids' or 'filter' parameter to limit records that are returned.",
                            "responseClass"=> "array",
                            "nickname"=> "getRecords",
                            "parameters"=> static::swaggerParameters(array('table_name','ids',
                                                                           'filter','limit','offset','order',
                                                                           'include_count','include_schema',
                                                                           'fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Create one or more records",
                            "notes"=> "Post data should be an array of fields for a single record or an array of records",
                            "responseClass"=> "array",
                            "nickname"=> "createRecords",
                            "parameters"=> static::swaggerParameters(array('table_name','fields','related',
                                                                           'record')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one or more records",
                            "notes"=> "Post data should be an array of fields for a single record or an array of records",
                            "responseClass"=> "array",
                            "nickname"=> "updateRecords",
                            "parameters"=> static::swaggerParameters(array('table_name','fields','related',
                                                                           'record')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one or more records",
                            "notes"=> "Use the 'ids' or 'filter' parameter to limit resources that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "deleteRecords",
                            "parameters"=> static::swaggerParameters(array('table_name','ids','filter',
                                                                           'fields','related')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/'.$service.'/{table_name}/{id}',
                  'description' => 'Operations for single record administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve one record by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to limit properties that are returned.",
                            "responseClass"=> "array",
                            "nickname"=> "getRecord",
                            "parameters"=> static::swaggerParameters(array('table_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one record by identifier",
                            "notes"=> "Post data should be an array of fields for a single record",
                            "responseClass"=> "array",
                            "nickname"=> "updateRecord",
                            "parameters"=> static::swaggerParameters(array('table_name','id','fields','related',
                                                                           'record')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one record by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to return properties that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "deleteRecord",
                            "parameters"=> static::swaggerParameters(array('table_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
        );

        return $swagger;
    }

    // Controller based methods

    /**
     * @return array
     * @throws Exception
     */
    public function actionSwagger()
    {
        try {
            $this->detectCommonParams();

            $result = parent::actionSwagger();
            $resources = static::swaggerPerDb($this->_api_name, $this->_description);
            $result['apis'] = $resources;
            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @throws Exception
     * @return array
     */
    public function actionGet()
    {
        $this->detectCommonParams();
        switch (strtolower($this->tableName)) {
        case '':
            $exclude = '';
            if ($this->_isNative) {
                // check for system tables
                $exclude = SystemManager::SYSTEM_TABLE_PREFIX;
            }
            try {
                $result = DbUtilities::describeDatabase($this->sqlDb->getSqlConn(), '', $exclude);
                $result = array('resource' => $result);
            }
            catch (Exception $ex) {
                throw new Exception("Error describing database tables.\n{$ex->getMessage()}");
            }
            break;
        default:
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
            $idField = Utilities::getArrayValue('id_field', $_REQUEST, '');
            if (empty($this->recordId)) {
                $ids = Utilities::getArrayValue('ids', $_REQUEST, '');
                if (!empty($ids)) {
                    $result = $this->retrieveRecordsByIds($this->tableName, $ids, $idField, $fields, $extras);
                }
                else {
                    $data = Utilities::getPostDataAsArray();
                    if (!empty($data)) { // complex filters or large numbers of ids require post
                        $ids = Utilities::getArrayValue('ids', $data, '');
                        if (empty($idField)) {
                            $idField = Utilities::getArrayValue('id_field', $data, '');
                        }
                        if (!empty($ids)) {
                            $result = $this->retrieveRecordsByIds($this->tableName, $ids, $idField, $fields, $extras);
                        }
                        else {
                            $records = Utilities::getArrayValue('record', $data, null);
                            if (empty($records)) {
                                // xml to array conversion leaves them in plural wrapper
                                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                            }
                            if (!empty($records)) {
                                // passing records to have them updated with new or more values, id field required
                                $result = $this->retrieveRecords($this->tableName, $records, $idField, $fields, $extras);
                            }
                            else {
                                $filter = Utilities::getArrayValue('filter', $data, '');
                                $limit = intval(Utilities::getArrayValue('limit', $data, 0));
                                $order = Utilities::getArrayValue('order', $data, '');
                                $offset = intval(Utilities::getArrayValue('offset', $data, 0));
                                $include_count = Utilities::boolval(Utilities::getArrayValue('include_count', $data, false));
                                $include_schema = Utilities::boolval(Utilities::getArrayValue('include_schema', $data, false));
                                $result = $this->retrieveRecordsByFilter($this->tableName, $fields,
                                                                         $filter, $limit, $order, $offset,
                                                                         $include_count, $include_schema, $extras);
                            }
                        }
                    }
                    else {
                        $filter = Utilities::getArrayValue('filter', $_REQUEST, '');
                        $limit = intval(Utilities::getArrayValue('limit', $_REQUEST, 0));
                        $order = Utilities::getArrayValue('order', $_REQUEST, '');
                        $offset = intval(Utilities::getArrayValue('offset', $_REQUEST, 0));
                        $include_count = Utilities::boolval(Utilities::getArrayValue('include_count', $_REQUEST, false));
                        $include_schema = Utilities::boolval(Utilities::getArrayValue('include_schema', $_REQUEST, false));
                        $result = $this->retrieveRecordsByFilter($this->tableName, $fields,
                                                                 $filter, $limit, $order, $offset,
                                                                 $include_count, $include_schema, $extras);
                    }
                }
            }
            else { // single entity by id
                $result = $this->retrieveRecordById($this->tableName, $this->recordId, $idField, $fields, $extras);
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
            throw new Exception('Multi-table batch request not yet implemented.');
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
            $records = Utilities::getArrayValue('record', $data, null);
            if (empty($records)) {
                // xml to array conversion leaves them in plural wrapper
                $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
            }
            if (empty($records)) {
                if (empty($data)) {
                    throw new Exception('No record in POST create request.', ErrorCodes::BAD_REQUEST);
                }
                $result = $this->createRecord($this->tableName, $data, $fields, $extras);
            }
            else {
                $rollback = (isset($_REQUEST['rollback'])) ? Utilities::boolval($_REQUEST['rollback']) : null;
                if (!isset($rollback)) {
                    $rollback = Utilities::boolval(Utilities::getArrayValue('rollback', $data, false));
                }
                $result = $this->createRecords($this->tableName, $records, $rollback, $fields, $extras);
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
            throw new Exception('Multi-table batch request not yet implemented.');
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                    $result = $this->updateRecordsByIds($this->tableName, $ids, $data, $idField, $rollback, $fields, $extras);
                }
                else {
                    $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                    if (!isset($filter)) {
                        $filter = Utilities::getArrayValue('filter', $data, null);
                    }
                    if (isset($filter)) {
                        $result = $this->updateRecordsByFilter($this->tableName, $filter, $data, $fields, $extras);
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
                            $result = $this->updateRecord($this->tableName, $data, $idField, $fields, $extras);
                        }
                        else {
                            $result = $this->updateRecords($this->tableName, $records, $idField, $rollback, $fields, $extras);
                        }
                    }
                }
            }
            else {
                $result = $this->updateRecordById($this->tableName, $data, $this->recordId, $idField, $fields, $extras);
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
            throw new Exception('Multi-table batch request not yet implemented.');
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                    $result = $this->updateRecordsByIds($this->tableName, $ids, $data, $idField, $rollback, $fields, $extras);
                }
                else {
                    $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                    if (!isset($filter)) {
                        $filter = Utilities::getArrayValue('filter', $data, null);
                    }
                    if (isset($filter)) {
                        $result = $this->updateRecordsByFilter($this->tableName, $filter, $data, $fields, $extras);
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
                            $result = $this->updateRecord($this->tableName, $data, $idField, $fields, $extras);
                        }
                        else {
                            $result = $this->updateRecords($this->tableName, $records, $idField, $rollback, $fields, $extras);
                        }
                    }
                }
            }
            else {
                $result = $this->updateRecordById($this->tableName, $data, $this->recordId, $idField, $fields, $extras);
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
            throw new Exception('Multi-table batch request not yet implemented.', ErrorCodes::BAD_REQUEST);
            break;
        default:
            // Most requests contain 'returned fields' parameter
            $fields = Utilities::getArrayValue('fields', $_REQUEST, '');
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
                    $result = $this->deleteRecordsByIds($this->tableName, $ids, $idField, $rollback, $fields, $extras);
                }
                else {
                    $filter = (isset($_REQUEST['filter'])) ? $_REQUEST['filter'] : null;
                    if (!isset($filter)) {
                        $filter = Utilities::getArrayValue('filter', $data, null);
                    }
                    if (isset($filter)) {
                        $result = $this->deleteRecordsByFilter($this->tableName, $filter, $fields, $extras);
                    }
                    else {
                        $records = Utilities::getArrayValue('record', $data, null);
                        if (empty($records)) {
                            // xml to array conversion leaves them in plural wrapper
                            $records = (isset($data['records']['record'])) ? $data['records']['record'] : null;
                        }
                        if (empty($records)) {
                            if (empty($data)) {
                                throw new Exception('No record in DELETE request.', ErrorCodes::BAD_REQUEST);
                            }
                            $result = $this->deleteRecord($this->tableName, $data, $idField, $fields, $extras);
                        }
                        else {
                            $result = $this->deleteRecords($this->tableName, $records, $idField, $rollback, $fields, $extras);
                        }
                    }
                }
            }
            else {
                $result = $this->deleteRecordById($this->tableName, $this->recordId, $idField, $fields, $extras);
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
     * @param        $table
     * @param        $records
     * @param bool   $rollback
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function createRecords($table, $records, $rollback = false, $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('create', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }

        if (!isset($records[0])) {
            // single record
            $records = array($records);
        }
        try {
            $results = $this->sqlDb->createSqlRecords($table, $records, $rollback, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param        $table
     * @param        $record
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function createRecord($table, $record, $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('create', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $result = $this->sqlDb->createSqlRecord($table, $record, $fields, $extras);

            return $result;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param        $table
     * @param        $records
     * @param string $idField
     * @param bool   $rollback
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function updateRecords($table, $records, $idField = '', $rollback = false, $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('update', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records[0])) {
            // single record
            $records = array($records);
        }
        try {
            $results = $this->sqlDb->updateSqlRecords($table, $records, $idField, $rollback, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param        $table
     * @param        $record
     * @param string $idField
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function updateRecord($table, $record, $idField = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('update', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $records = array($record);
            $results = $this->sqlDb->updateSqlRecords($table, $records, $idField, false, $fields, $extras);

            return $results[0];
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param        $table
     * @param        $record
     * @param string $filter
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function updateRecordsByFilter($table, $record, $filter = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('update', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $results = $this->sqlDb->updateSqlRecordsByFilter($table, $record, $filter, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\nquery: $filter\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param        $table
     * @param        $record
     * @param        $id_list
     * @param string $idField
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function updateRecordsByIds($table, $record, $id_list, $idField = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('update', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $results = $this->sqlDb->updateSqlRecordsByIds($table, $record, $id_list, $idField, false, $fields, $extras);

            return $results[0];
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param        $table
     * @param        $record
     * @param        $id
     * @param string $idField
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function updateRecordById($table, $record, $id, $idField = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('update', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $results = $this->sqlDb->updateSqlRecordsByIds($table, $record, $id, $idField, false, $fields, $extras);

            return $results[0];
        }
        catch (Exception $ex) {
            throw new Exception("Error updating $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param        $table
     * @param        $records
     * @param string $idField
     * @param bool   $rollback
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function deleteRecords($table, $records, $idField = '', $rollback = false, $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('delete', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }
        if (!isset($records[0])) {
            // single record
            $records = array($records);
        }
        try {
            $results = $this->sqlDb->deleteSqlRecords($table, $records, $idField, $rollback, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param        $table
     * @param        $record
     * @param string $idField
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function deleteRecord($table, $record, $idField = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('delete', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $records = array($record);
            $results = $this->sqlDb->deleteSqlRecords($table, $records, $idField, false, $fields, $extras);

            return $results[0];
        }
        catch (Exception $ex) {
            throw $ex; // whole batch failed
        }
    }

    /**
     * @param        $table
     * @param        $filter
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function deleteRecordsByFilter($table, $filter, $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('delete', $table);

        try {
            $results = $this->sqlDb->deleteSqlRecordsByFilter($table, $filter, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param        $table
     * @param        $id_list
     * @param string $idField
     * @param bool   $rollback
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function deleteRecordsByIds($table, $id_list, $idField = '', $rollback = false, $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('delete', $table);

        try {
            $results = $this->sqlDb->deleteSqlRecordsByIds($table, $id_list, $idField, $rollback, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param        $table
     * @param        $id
     * @param string $idField
     * @param string $fields
     * @param array  $extras
     *
     * @throws Exception
     * @return array
     */
    public function deleteRecordById($table, $id, $idField = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('delete', $table);

        try {
            $results = $this->sqlDb->deleteSqlRecordsByIds($table, $id, $idField, false, $fields, $extras);

            return $results[0];
        }
        catch (Exception $ex) {
            throw new Exception("Error deleting $table records.\n{$ex->getMessage()}", $ex->getCode());
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
     * @param bool $include_schema
     * @param array $extras
     * @throws Exception
     * @return array
     */
    public function retrieveRecordsByFilter($table, $fields = '', $filter = '',
                                            $limit = 0, $order = '', $offset = 0,
                                            $include_count = false, $include_schema = false,
                                            $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByFilter($table, $fields, $filter,
                                                                $limit, $order, $offset,
                                                                $include_count, $include_schema,
                                                                $extras);
            if (isset($results['meta'])) {
                $meta = $results['meta'];
                unset($results['meta']);
                $results = array('record' => $results, 'meta' => $meta);
            }
            else {
                $results = array('record' => $results);
            }

            return $results;
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param $records
     * @param string $id_field
     * @param string $fields
     * @param array $extras
     * @throws Exception
     * @return array
     */
    public function retrieveRecords($table, $records, $id_field = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('read', $table);
        if (!isset($records) || empty($records)) {
            throw new Exception('There are no record sets in the request.', ErrorCodes::BAD_REQUEST);
        }

        if (!isset($records[0])) {
            // single record
            $records = array($records);
        }
        try {
            $results = $this->sqlDb->retrieveSqlRecords($table, $records, $id_field, $fields, $extras);

            return array('record' => $results);
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
     * @param array $extras
     * @throws Exception
     * @return array
     */
    public function retrieveRecord($table, $record, $id_field = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('read', $table);
        if (!isset($record) || empty($record)) {
            throw new Exception('There are no fields in the record.', ErrorCodes::BAD_REQUEST);
        }
        try {
            $results = $this->sqlDb->retrieveSqlRecords($table, $record, $id_field, $fields, $extras);

            return $results[0];
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
     * @param array $extras
     * @throws Exception
     * @return array
     */
    public function retrieveRecordsByIds($table, $id_list, $id_field = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByIds($table, $id_list, $id_field, $fields, $extras);

            return array('record' => $results);
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $table
     * @param $id
     * @param string $id_field
     * @param string $fields
     * @param array $extras
     * @throws Exception
     * @return array
     */
    public function retrieveRecordById($table, $id, $id_field = '', $fields = '', $extras = array())
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty.', ErrorCodes::BAD_REQUEST);
        }
        if ($this->_isNative) {
            // check for system tables and deny
            $sysPrefix = SystemManager::SYSTEM_TABLE_PREFIX;
            if (0 === substr_compare($table, $sysPrefix, 0, strlen($sysPrefix))) {
                throw new Exception("Table '$table' not found.", ErrorCodes::NOT_FOUND);
            }
        }
        $this->checkPermission('read', $table);

        try {
            $results = $this->sqlDb->retrieveSqlRecordsByIds($table, $id, $id_field, $fields, $extras);

            return $results[0];
        }
        catch (Exception $ex) {
            throw new Exception("Error retrieving $table records.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

}
