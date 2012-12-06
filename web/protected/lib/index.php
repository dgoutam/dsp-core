<?php
require_once dirname(__FILE__) . '/../../../vendor/autoload.php';

use CloudServicesPlatform\ServiceHandlers\ServiceHandler;

try {
    $path = ltrim(@$_SERVER['PATH_INFO'], '/');
    $app = ServiceHandler::getInstance()->getServiceObject('App');
    $app->streamFile($path);
}
catch (\Exception $ex) {
    die($ex->getMessage());
}
