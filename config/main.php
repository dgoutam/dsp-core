<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'protected',
    'name' => 'DreamFactory Document Services Platform',
    'runtimePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'log',

    // preloading 'log' component
    'preload' => array('log'),

    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
    ),

    'modules' => array(
        // uncomment the following to enable the Gii tool
        'gii' => array(
            'class' => 'system.gii.GiiModule',
            'password' => 'Dream123',
            // If removed, Gii defaults to localhost only. Edit carefully to taste.
            //'ipFilters'=>array('127.0.0.1','::1'),
        ),
    ),

    // application components
    'components' => array(
        'user' => array(
            // enable cookie-based authentication
            'allowAutoLogin' => true,
        ),

        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'caseSensitive' => false,
            'urlFormat' => 'path',
            'rules' => array(
                // REST patterns
                array('lib/view', 'pattern' => 'lib/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
                array('apps/view', 'pattern' => 'apps/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
                array('rest/view', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-]+>/<id:\d+>', 'verb' => 'GET'),
                array('rest/list', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>/', 'verb' => 'GET'),
                array('rest/view', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
                array('rest/create', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:\w+>', 'verb' => 'POST'),
                array('rest/update', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:\w+>/<id:\d+>', 'verb' => 'PUT'),
                array('rest/delete', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:\w+>/<id:\d+>', 'verb' => 'DELETE'),
                array('rest/index', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'GET'),
                // Other controllers
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),

        // uncomment the following to use the Sqlite database
        /*
		'db'=>array(
			'connectionString' => 'sqlite:'.dirname(__FILE__).'/../data/testdrive.db',
		),
        */
        // uncomment the following to use a MySQL database

        'db' => array(
            'connectionString' => 'mysql:host=localhost;port=3306;dbname=dfTestDb',
            'emulatePrepare' => true,
            'username' => 'root',
            'password' => 'Dream123',
            'charset' => 'utf8',
        ),

        // uncomment the following to use a SQL Azure database
        /*
		'db'=>array(
			'connectionString' => 'sqlsrv:server=tcp:hof7lqw5qv.database.windows.net,1433;Database=dfTestDB',
			'username' => 'dfadmin',
			'password' => 'Dream123',
			'charset' => 'utf8',
		),
        */
        'errorHandler' => array(
            // use 'site/error' action to display errors
            'errorAction' => 'site/error',
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
                // uncomment the following to show log messages on web pages
                /*
                array(
                    'class'=>'CWebLogRoute',
                ),
                */
            ),
        ),
    ),

    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array(
//        'blobStorageType'   => 'WindowsAzureBlob',
        // Windows Azure
        'blobAccountName'   => 'dreamfactorysoftware',
        'blobAccountKey'    => 'lpUCNR/7lmxBVsQuB3jD4yBQ4SWTvbmoJmJ4f+2q7vvm7/qQBHF0Lkfq4QQSk7KefNc5O3VJbQuW+wLLp79F3A==',
        // Amazon S3
//        'BlobAccessKey'     => '',
//        'BlobSecretKey'     => '',
//        'BlobBucketName'    => '',
        // this is used in contact page
        'adminEmail' => 'leehicks@dreamfactory.com',
        'companyLabel' => 'My Dream Cloud',
        'allowOpenRegistration' => 'true',
    ),
);