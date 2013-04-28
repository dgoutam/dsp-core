<?php
/**
 * BE AWARE...
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * web.php
 * This is the main configuration file for the DreamFactory Services Platform server application.
 */

//.........................................................................
//. Default Values
//.........................................................................

use Kisma\Core\Utility\Log;

const ENABLE_DB_CACHE = true;

global $_autoloader;

$_dbCache = $_dbName = null;
$_appName = 'DreamFactory Services Platform';

//	Our base path
$_basePath = dirname( __DIR__ );

//	Get the globals set...
require_once $_basePath . '/web/protected/components/Fabric.php';

//	Our log file path. Log name is set by startup script
$_logFilePath = $_basePath . '/log';
$_vendorPath = $_basePath . '/vendor';

//	Location of the blob storage credentials if provisioned, otherwise local file storage is used.
$_blobConfig = __DIR__ . '/blob.config.php';

//	Read in the database configuration
$_dbConfig = require_once( __DIR__ . '/database.config.php' );
$_commonConfig = file_exists( __DIR__ . '/common.config.php' ) ? require __DIR__ . '/common.config.php' : array();

/**
 * Database Caching
 */
if ( ENABLE_DB_CACHE )
{
	/**
	 * The database cache object
	 */
	$_dbCache = array(
		'class'                => 'CDbCache',
		'connectionID'         => 'db',
		'cacheTableName'       => 'df_sys_cache',
		'autoCreateCacheTable' => true,
	);
}

//.........................................................................
//. The configuration himself (like Raab)
//.........................................................................

return array(
	//.........................................................................
	//. Base Configuration
	//.........................................................................

	'basePath'    => $_basePath . '/web/protected',
	'name'        => $_appName,
	'runtimePath' => $_logFilePath,
	'preload'     => array('log'),
	'import'      => array(
		'system.utils.*',
		'application.behaviors.*',
		'application.models.*',
		'application.models.forms.*',
		'application.components.*',
		'application.components.blobs.*',
		'application.components.file_managers.*',
		'application.components.services.*',
	),
	'modules'     => array(
		'gii' => array(
			'class'    => 'system.gii.GiiModule',
			'password' => 'xyzzy',
		),
	),
	//.........................................................................
	//. Application Components
	//.........................................................................

	'components'  => array(
		//	Asset management
		'assetManager' => array(
			'class'      => 'CAssetManager',
			'basePath'   => 'public/assets',
			'baseUrl'    => '/public/assets',
			'linkAssets' => true,
		),
		//	Database configuration
		'db'           => $_dbConfig,
		//	Error management
		'errorHandler' => array(
			'errorAction' => 'site/error',
		),
		//	Route configuration
		'urlManager'   => array(
			'caseSensitive'  => false,
			'urlFormat'      => 'path',
			'showScriptName' => false,
			'rules'          => array(
				// REST patterns
				array('rest/get', 'pattern' => 'rest/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
				array('rest/post', 'pattern' => 'rest/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'POST'),
				array('rest/put', 'pattern' => 'rest/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'PUT'),
				array('rest/merge', 'pattern' => 'rest/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'PATCH,MERGE'),
				array('rest/delete', 'pattern' => 'rest/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'DELETE'),
				// Other controllers
				'<controller:\w+>/<id:\d+>'              => '<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
				'<controller:\w+>/<action:\w+>'          => '<controller>/<action>',
				// fall through to storage services for direct access
				array('storage/get', 'pattern' => '<service:[_0-9a-zA-Z-]+>/<path:[_0-9a-zA-Z-\/. ]+>', 'verb' => 'GET'),
			),
		),
		//	User configuration
		'user'         => array(
			'allowAutoLogin' => true,
		),
		//	Logging configuration
		'log'          => array(
			'class'  => 'CLogRouter',
			'routes' => array(
				array(
					'class'       => 'LiveLogRoute',
					'maxFileSize' => '102400',
					'logFile'     => basename( \Kisma::get( 'app.log_file' ) ),
					'logPath'     => $_logFilePath,
					'levels'      => 'error, warning, trace, info',
				),
			),
		),
		//	Database Cache
		'cache'        => $_dbCache,
	),
	//.........................................................................
	//. Global application parameters
	//.........................................................................

	'params'      => $_commonConfig,
);