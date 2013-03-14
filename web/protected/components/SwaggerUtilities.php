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
     *
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
            case 'folder':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Name of the folder to operate on.",
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'file':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Name of the file to operate on.",
                                   "dataType"=>"String",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'properties':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Return properties of the folder or file.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'content':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Return the content as base64 of the file, only applies when 'properties' is true.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'download':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Prompt the user to download the file from the browser.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'folders_only':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Only include folders in the folder listing.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'files_only':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"Only include files in the folder listing.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            case 'full_tree':
                $swagger[] = array("paramType"=>"path",
                                   "name"=>$param,
                                   "description"=>"List the contents of sub-folders as well.",
                                   "dataType"=>"boolean",
                                   "required"=>false,
                                   "allowMultiple"=>false
                );
                break;
            }
        }

        return $swagger;
    }

    /**
     * Define dynamic swagger output for each service resource.
     * Currently used only for the System service, but maybe others later.
     *
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
                                                                           'include_count','include_schema',
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


}
