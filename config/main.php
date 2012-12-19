<?php
return array(
    'basePath' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'protected',
    'name' => 'DreamFactory Document Services Platform',
    'runtimePath' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'log',

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
        'assetManager' => array(
            'class'      => 'CAssetManager',
            'basePath'   => 'public/assets',
            'baseUrl'    => '/public/assets',
            'linkAssets' => true,
        ),

        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'caseSensitive' => false,
            'urlFormat' => 'path',
            'rules' => array(
                // REST patterns
                array('lib/stream', 'pattern' => 'lib/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
                array('app/stream', 'pattern' => 'app/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
                array('rest/get', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
                array('rest/post', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'POST'),
                array('rest/put', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'PUT'),
                array('rest/merge', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'PATCH,MERGE'),
                array('rest/delete', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'DELETE'),
                array('rest/list', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'GET'),
                // Other controllers
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),

		'db'           => require_once( __DIR__ . DIRECTORY_SEPARATOR . 'database.config.php'),
		'errorHandler' => array(
			// use 'site/error' action to display errors
			'errorAction' => 'site/error',
		),
		'log'          => array(
			'class'  => 'CLogRouter',
			'routes' => array(
				array(
					'class'  => 'CFileLogRoute',
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
	'params'      => array(
//        'blobStorageType'   => 'WindowsAzureBlob',
//        'blobAccountName'   => 'dreamfactorysoftware',
//        'blobAccountKey'    => 'lpUCNR/7lmxBVsQuB3jD4yBQ4SWTvbmoJmJ4f+2q7vvm7/qQBHF0Lkfq4QQSk7KefNc5O3VJbQuW+wLLp79F3A==',
        // this is used in contact page
        'adminEmail' => 'leehicks@dreamfactory.com',
        'companyLabel' => 'My Dream Cloud',
        'allowOpenRegistration' => 'true',
    ),
);