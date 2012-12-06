<?php

require_once dirname(__FILE__) . '/../../../../vendor/autoload.php';

use CloudServicesPlatform\ServiceHandlers\ServiceHandler;
use CloudServicesPlatform\Utilities\Utilities;

//  REST API commands follow the following pattern...
//      
//      http[s]://servername/REST/service[/service_resource_path][?[service_parameters][&format={json | xml}]]
//
//  where path elements are defined as...
//      servername - DreamFactory Direct Connect Cloud server domain
//      service - the hosted/native (Login, System, DB, Doc, etc) or provisioned external service
//      format - the desired format of the reply, also used for incoming data when Content-Type not explicit (xml or json)
//  
//  where supported HTTP REST verbs are...
//      POST - for create/insert resources or various system commands
//      GET - retrieve via whole resources, resource ids, id lists, or simple SQL-like filters
//      Other resource requests are done through verb tunneling including the X-HTTP-Method Header as...
//          MERGE/PATCH - partial resource update
//          PUT - full resource replace or insert if not found
//          DELETE - delete the resource
//          GET - for complicated filters or request for large id lists
//
//  where native services are currently...
//      User - login and session management services, resources/commands including...       
//          Login - login to the system with username and password, creating a new session, or failure
//          Logout - logout of the system, destroying existing session
//          Register - allows a new user to self-register in the system, sends confirmation required email
//          Confirm - confirms a new user registration, allows login session
//          ForgotPassword - interface for presenting challenge question or notifying via email with change code
//          NewPassword - updating a forgotten password with a change code
//          ChangePassword - updating a password with a valid login session
//          Session - 'renew' and redeliver the session login information
//          Ticket - retrieve a ticket for use by non-browser (plugins) apps to be launched
//
//      System - administrative system resources including User, Role, App, AppGroup, and Service.
//      DB - access to database tables, and schema if defined.
//      App - access to application file storage
//      Doc - access to document storage
//
//  Requesting application should set X-Application-Name Header or URL parameter 'appName' equal to the application name.
//

// use during debug for replying with time spent in processing
$GLOBALS['TRACK_TIME'] = true;
$GLOBALS['API_TIME'] = 0;
$GLOBALS['DB_TIME'] = 0;
$GLOBALS['SESS_TIME'] = 0;
$GLOBALS['CFG_TIME'] = 0;
$GLOBALS['WS_TIME'] = 0;
$GLOBALS['DB_DEBUG'] = true;

Utilities::markTimeStart('API_TIME');

$format_out = (isset($_REQUEST['format'])) ? strtolower($_REQUEST['format']) : '';
if (empty($format_out)) {
    $format_out = 'json';
}

try {
    // determine the REST verb/method to process
    $method = $_SERVER['REQUEST_METHOD'];
    if ('POST' === $method) {
        // check for verb tunneling
        $tunnel_method = (isset($_SERVER['HTTP_X_HTTP_METHOD'])) ? $_SERVER['HTTP_X_HTTP_METHOD'] : '';
        if (!empty($tunnel_method)) {
            switch ($tunnel_method) {
            case 'DELETE':
            case 'GET': // complex retrieves, non-standard
            case 'MERGE':
            case 'PATCH':
            case 'PUT':
                $method = $tunnel_method;
                break;
            default:
                if (!empty($tunnel_method)) {
                    throw new Exception("Unknown verb tunneling method '$tunnel_method' in REST request.");
                }
                break;
            }
        }
    }

    // determine application if any
    $appName = (isset($_SERVER['HTTP_X_APPLICATION_NAME'])) ? $_SERVER['HTTP_X_APPLICATION_NAME'] : '';
    if (empty($appName)) {
        $appName = (isset($_REQUEST['appName'])) ? $_REQUEST['appName'] : '';
    }
    if (empty($appName)) {
        throw new Exception("No application name header or parameter value in REST request.");
    }
    $GLOBALS['appName'] = $appName;

    $path = @$_SERVER['PATH_INFO'];
    $path_array = explode("/", substr($path, 1));   // skip 'REST/'

    if (1 > count($path_array)) {
        throw new \Exception('Invalid Request - Invalid path given.');
    }

    // determine service context
    $service = (isset($path_array[0])) ? $path_array[0] : '';
    unset($path_array[0]);
    $path_array = array_values($path_array);
    $result = ServiceHandler::getInstance()->handleRestRequest($service, $method, $path_array, $_REQUEST, $format_out);
}
catch (\Exception $ex) {
    $result = array("fault" => array("faultString" => htmlentities($ex->getMessage()),
                                     "faultCode" => htmlentities('Sender')));
    switch ($format_out) {
    case 'json':
        $result = json_encode($result);
        break;
    case 'xml':
        $result = '<fault>';
        $result .= '<faultString>' . htmlentities($ex->getMessage()) . '</faultString>';
        $result .= '<faultCode>' . htmlentities('Sender') . '</faultCode>';
        $result .= '</fault>';
        break;
    }
}

Utilities::markTimeStop('API_TIME');
Utilities::logTimers($method . ' ' . @$_SERVER['PATH_INFO']);

switch ($format_out) {
case 'json':
    Utilities::sendJsonResponse($result);
    break;
case 'xml':
    Utilities::sendXmlResponse($result);
    break;
}

