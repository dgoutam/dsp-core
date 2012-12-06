<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'DreamFactory Cloud Services Platform',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
	),

	'modules'=>array(
		// uncomment the following to enable the Gii tool
		'gii'=>array(
			'class'=>'system.gii.GiiModule',
			'password'=>'Dream123',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			//'ipFilters'=>array('127.0.0.1','::1'),
		),
	),

	// application components
	'components'=>array(
		'user'=>array(
			// enable cookie-based authentication
			'allowAutoLogin'=>true,
		),

		// uncomment the following to enable URLs in path-format
 		'urlManager'=>array(
			'urlFormat'=>'path',
			'rules'=>array(
                // REST patterns
                array('rest/list', 'pattern'=>'rest/<service:\w+>/<resource:\w+>', 'verb'=>'GET'),
                array('rest/view', 'pattern'=>'rest/<service:\w+>/<resource:\w+>/<id:\d+>', 'verb'=>'GET'),
                array('rest/create', 'pattern'=>'rest/<service:\w+>/<resource:\w+>', 'verb'=>'POST'),
                array('rest/update', 'pattern'=>'rest/<service:\w+>/<resource:\w+>/<id:\d+>', 'verb'=>'PUT'),
                array('rest/delete', 'pattern'=>'rest/<service:\w+>/<resource:\w+>/<id:\d+>', 'verb'=>'DELETE'),
                // Other controllers
                '<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),

        // uncomment the following to use the Sqlite database
        /*
		'db'=>array(
			'connectionString' => 'sqlite:'.dirname(__FILE__).'/../data/testdrive.db',
		),
        */
		// uncomment the following to use a MySQL database
		/*
		'db'=>array(
			'connectionString' => 'mysql:host=us-cdbr-azure-east-b.cloudapp.net;dbname=dftestDb',
			'emulatePrepare' => true,
			'username' => 'b87314a4dd182f',
			'password' => '01f9b9a2',
			'charset' => 'utf8',
		),
        */
		// uncomment the following to use a SQL Azure database
		'db'=>array(
			'connectionString' => 'sqlsrv:server=tcp:hof7lqw5qv.database.windows.net,1433;Database=dfTestDB',
			'username' => 'dfadmin',
			'password' => 'Dream123',
			'charset' => 'utf8',
		),

		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>'site/error',
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
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
	'params'=>array(
		// this is used in contact page
		'adminEmail' => 'webmaster@example.com',
        'CompanyLabel' => 'My Dream Cloud',
        'AdminEmail' => 'leehicks@dreamfactory.com',
        'AllowOpenRegistration' => 'true',
	),
);