<?php
/*
 * This is the main configuration file for the Document Services Platform server application.
 */

/*
 * Location of the database credentials.
 */
$_dbName = null;
$_dbConfig = __DIR__ . DIRECTORY_SEPARATOR . 'database.config.php';

/*
 * Location of the blob storage credentials if provisioned,
 * otherwise local file storage is used.
 */
$_blobConfig = __DIR__ . DIRECTORY_SEPARATOR . 'blob.config.php';

$_theRoot = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

return array(
	'basePath'    => $_theRoot . 'web' . DIRECTORY_SEPARATOR . 'protected',
	'name'        => 'DreamFactory Document Services Platform',
	'runtimePath' => $_theRoot . 'log',
	// preloading 'log' component
	'preload'     => array( 'log' ),
	// autoloading model and component classes
	'import'      => array(
		'application.models.*',
		'application.models.forms.*',
		'application.components.*',
		'application.components.blobs.*',
		'application.components.file_managers.*',
		'application.components.services.*',
	),
	'modules'     => array(
		// uncomment the following to enable the Gii tool
		'gii' => array(
			'class'    => 'system.gii.GiiModule',
			'password' => 'Dream123',
			// If removed, Gii defaults to localhost only. Edit carefully to taste.
			//'ipFilters'=>array('127.0.0.1','::1'),
		),
	),
	// application components
	'components'  => array(
		'user'         => array(
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
		'urlManager'   => array(
			'caseSensitive' => false,
			'urlFormat'     => 'path',
			'rules'         => array(
				// REST patterns
				array( 'app/stream', 'pattern' => 'app/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET' ),
				array( 'lib/stream', 'pattern' => 'lib/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET' ),
				array( 'rest/get', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET' ),
				array( 'rest/post', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'POST' ),
				array( 'rest/put', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'PUT' ),
				array( 'rest/merge', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'PATCH,MERGE' ),
				array( 'rest/delete', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'DELETE' ),
				// duplicate for the root service calls due to trailing / problem
				array( 'rest/get', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'GET' ),
				array( 'rest/post', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'POST' ),
				array( 'rest/put', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'PUT' ),
				array( 'rest/merge', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'PATCH,MERGE' ),
				array( 'rest/delete', 'pattern' => 'rest/<service:[_0-9a-zA-Z-]+>/', 'verb' => 'DELETE' ),
				// Other controllers
				'<controller:\w+>/<id:\d+>'              => '<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
				'<controller:\w+>/<action:\w+>'          => '<controller>/<action>',
				'gii'                                    => 'gii',
				'gii/<controller:\w+>'                   => 'gii/<controller>',
				'gii/<controller:\w+>/<action:\w+>'      => 'gii/<controller>/<action>',
			),
		),
		'db'           => require_once( $_dbConfig ),
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
	'params'      => array_merge(
		array(
			 'storage_base_path'     => '/data/storage',
			 'dsp_name'              => $_dbName,
			 'blobStorageConfig'     => file_exists( $_blobConfig ) ? require_once( $_blobConfig ) : array(),
			 // this is used in contact page
			 'adminEmail'            => 'leehicks@dreamfactory.com',
			 'companyLabel'          => 'My Dream Cloud',
			 'allowOpenRegistration' => 'true',
		),
		@require( __DIR__ . '/storage.paths.php' )
	),
);