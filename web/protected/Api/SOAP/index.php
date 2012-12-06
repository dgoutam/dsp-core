<?php

// until soap is working well
ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '0');

if (isset($_GET['wsdl']) || isset($_GET['WSDL']))
{
    header("Content-type: text/xml");
    $wsdl = file_get_contents('./soap.wsdl', FILE_USE_INCLUDE_PATH);
    $wsdl = str_replace('127.0.0.1:81', $_SERVER['HTTP_HOST'], $wsdl);
    echo $wsdl;
    exit;
}

require_once("./DreamFactory/SoapServices.php");

try {
    $server = new SoapServer("soap.wsdl", 
                             array('cache_wsdl' => WSDL_CACHE_NONE, 
                                   'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
    $server->setClass("SoapServices");
    $server->handle();
}
catch (SoapFault $s) {
    echo $s->getMessage();
}
catch (Exception $e) {
    echo $e->getMessage();
}
?>