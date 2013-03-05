<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Utilities
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class SwaggerUtilities
{

    /**
     * Swagger base response used by Swagger-UI
     * @param string $service
     * @return array
     */
    public static function swaggerBaseInfo($service='')
    {
        $swagger = array('apiVersion'=> Versions::API_VERSION,
                         'swaggerVersion'=> '1.1',
                         'basePath'=> Yii::app()->getRequest()->getHostInfo().'/rest');
        if (!empty($service)) {
            $swagger['resourcePath'] = '/'.$service;
        }

        return $swagger;
    }

    /**
     * @param $parameters
     * @param string $method
     * @return array
     */
    public static function swaggerParameters($parameters, $method = '')
    {
        $swagger = array();
        foreach ($parameters as $param) {
            switch ($param) {
            case 'app_name':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Application name that makes the API call.",
                                   "dataType"=>"String",
                                   "required"=>true,
                                   "allowMultiple"=>false
                );
                break;
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
            case 'limit':
                $swagger[] = array("paramType"=>"query",
                                   "name"=>$param,
                                   "description"=>"Set to limit the filter results.",
                                   "dataType"=>"int",
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
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>true
                );
                break;
            }
        }

        return $swagger;
    }

    /**
     * @param string $service
     * @param string $resource
     * @param string $label
     * @param string $plural
     * @return array
     */
    public static function swaggerPerResource($service, $resource, $label='', $plural='')
    {
        if (empty($label)) {
            $label = Utilities::labelize($resource);
        }
        if (empty($plural)) {
            $plural = Utilities::pluralize($label);
        }
        $swagger = array(
            array('path' => '/'.$service.'/'.$resource,
                  'description' => 'Operations for '.ucfirst($plural).' administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve multiple $plural",
                            "notes"=> "Use the 'ids' or 'filter' parameter to limit resources that are returned.",
                            "responseClass"=> "array",
                            "nickname"=> "get".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','ids','filter',
                                                                           'limit','offset','order',
                                                                           'fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Create one or more $plural",
                            "notes"=> "Post data should be an array of fields for a single $resource or a record array of $plural",
                            "responseClass"=> "array",
                            "nickname"=> "create".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one or more $plural",
                            "notes"=> "Post data should be an array of fields for a single $resource or a record array of $plural",
                            "responseClass"=> "array",
                            "nickname"=> "update".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one or more $plural",
                            "notes"=> "Use the 'ids' or 'filter' parameter to limit resources that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "delete".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','fields','related')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/'.$service.'/'.$resource.'/{id}',
                  'description' => 'Operations for single '.ucfirst($label).' administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve one $label by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to limit properties that are returned.",
                            "responseClass"=> "array",
                            "nickname"=> "get".ucfirst($label),
                            "parameters"=> static::swaggerParameters(array('app_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one $label by identifier",
                            "notes"=> "Post data should be an array of fields for a single $resource",
                            "responseClass"=> "array",
                            "nickname"=> "update".ucfirst($label),
                            "parameters"=> static::swaggerParameters(array('app_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one $label by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to return properties that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "delete".ucfirst($label),
                            "parameters"=> static::swaggerParameters(array('app_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
        );

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
                            "parameters"=> SwaggerUtilities::swaggerParameters(array('app_name')),
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
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','ids',
                                                                           'filter','limit','offset','order',
                                                                           'fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Create one or more records",
                            "notes"=> "Post data should be an array of fields for a single record or an array of records",
                            "responseClass"=> "array",
                            "nickname"=> "createRecords",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one or more records",
                            "notes"=> "Post data should be an array of fields for a single record or an array of records",
                            "responseClass"=> "array",
                            "nickname"=> "updateRecords",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one or more records",
                            "notes"=> "Use the 'ids' or 'filter' parameter to limit resources that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "deleteRecords",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','fields','related')),
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
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one record by identifier",
                            "notes"=> "Post data should be an array of fields for a single record",
                            "responseClass"=> "array",
                            "nickname"=> "updateRecord",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one record by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to return properties that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "deleteRecord",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
        );

        return $swagger;
    }

    /**
     * @param string $service
     * @param string $description
     * @return array
     */
    public static function swaggerPerSchema($service, $description='')
    {
        $swagger = array(
            array('path' => '/'.$service,
                  'description' => $description,
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "List tables available to the schema service",
                            "notes"=> "Use the table names in available schema operations.",
                            "responseClass"=> "array",
                            "nickname"=> "getTables",
                            "parameters"=> SwaggerUtilities::swaggerParameters(array('app_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Create one or more tables",
                            "notes"=> "Post data should be a single table definition or an array of table definitions",
                            "responseClass"=> "array",
                            "nickname"=> "createTables",
                            "parameters"=> static::swaggerParameters(array('app_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one or more tables",
                            "notes"=> "Post data should be a single table definition or an array of table definitions",
                            "responseClass"=> "array",
                            "nickname"=> "updateTables",
                            "parameters"=> static::swaggerParameters(array('app_name')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/'.$service.'/{table_name}',
                  'description' => 'Operations for per table administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve table definition for the given table",
                            "notes"=> "This describes the table, its fields and relations to other tables.",
                            "responseClass"=> "array",
                            "nickname"=> "describeTable",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Create one or more fields in the given table",
                            "notes"=> "Post data should be an array of field properties for a single record or an array of fields",
                            "responseClass"=> "array",
                            "nickname"=> "createFields",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one or more fields in the given table",
                            "notes"=> "Post data should be an array of field properties for a single record or an array of fields",
                            "responseClass"=> "array",
                            "nickname"=> "updateFields",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete (aka drop) the given table",
                            "notes"=> "Careful, this drops the database table and all of its contents.",
                            "responseClass"=> "array",
                            "nickname"=> "deleteTable",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/'.$service.'/{table_name}/{field_name}',
                  'description' => 'Operations for single record administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve the definition of the given field for the given table",
                            "notes"=> "This describes the field and its properties.",
                            "responseClass"=> "array",
                            "nickname"=> "describeField",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','field_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one record by identifier",
                            "notes"=> "Post data should be an array of field properties for the given field",
                            "responseClass"=> "array",
                            "nickname"=> "updateField",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','field_name')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete (aka drop) the given field from the given table",
                            "notes"=> "Careful, this drops the database table field/column and all of its contents.",
                            "responseClass"=> "array",
                            "nickname"=> "deleteField",
                            "parameters"=> static::swaggerParameters(array('app_name','table_name','field_name')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
        );

        return $swagger;
    }


}
