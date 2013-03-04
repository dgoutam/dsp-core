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

    public static function swaggerPerResource($service, $resource)
    {
        $plural = Utilities::pluralize($resource);
        $swagger = array(
            array('path' => '/'.$service.'/'.$resource,
                  'description' => '',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve all $plural",
                            "notes"=> "Use the 'ids' or 'filter' parameter to limit resources that are returned.",
                            "responseClass"=> "array",
                            "nickname"=> "getAll".ucfirst($plural),
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
                  'description' => 'Operations for '.ucfirst($resource).' administration.',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve one $resource by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to limit properties that are returned.",
                            "responseClass"=> "array",
                            "nickname"=> "getAll".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "PUT",
                            "summary"=> "Update one $resource by identifier",
                            "notes"=> "Post data should be an array of fields for a single $resource",
                            "responseClass"=> "array",
                            "nickname"=> "update".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "DELETE",
                            "summary"=> "Delete one $resource by identifier",
                            "notes"=> "Use the 'fields' and/or 'related' parameter to return properties that are deleted.",
                            "responseClass"=> "array",
                            "nickname"=> "delete".ucfirst($plural),
                            "parameters"=> static::swaggerParameters(array('app_name','id','fields','related')),
                            "errorResponses"=> array()
                      ),
                  )
            ),
        );

        return $swagger;
    }


}
