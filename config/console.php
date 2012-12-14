<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'DreamFactory Cloud Services Platform Console',

	// preloading 'log' component
	'preload'=>array('log'),

	// application components
	'components'=>array(
		// uncomment the following to use a SQLite database
		'db'=>array(
			'connectionString' => 'sqlite:'.dirname(__FILE__).'/../data/testdrive.db',
		),
		// uncomment the following to use a MySQL database
		/*
		'db'=>array(
			'connectionString' => 'mysql:host=us-cdbr-azure-east-b.cloudapp.net;dbname=dreamfactory',
			'emulatePrepare' => true,
			'username' => 'b87314a4dd182f',
			'password' => '01f9b9a2',
			'charset' => 'utf8',
		),
		*/
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
			),
		),
	),
);