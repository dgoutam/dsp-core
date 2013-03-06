<?php
/**
 * web.php
 *
 * This file is part of the DreamFactory Document Service Platform (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This source file and all is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * This is the main configuration file for the Document Services Platform server application.
 */
$_dbName = null;
$_appName = 'DreamFactory Document Services Platform';

//	Read in the database configuration
$_dbConfig = require( __DIR__ . '/database.config.php' );
$_commonConfig = __DIR__ . '/common.config.php';

//	Location of the blob storage credentials if provisioned, otherwise local file storage is used.
$_blobConfig = __DIR__ . '/blob.config.php';

//	Our base path
$_basePath = dirname( __DIR__ );

//	Our log file path. Log name is set by startup script
$_logFilePath = $_basePath . '/log';

return array(
	//.........................................................................
	//. Base Configuration
	//.........................................................................

	'basePath'    => $_basePath . '/web/protected',
	'name'        => $_appName,
	'runtimePath' => $_logFilePath,
	'preload'     => array( 'log' ),
	'import'      => array(
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
		'db'           => $_dbConfig,
		'errorHandler' => array(
			'errorAction' => 'site/error',
		),
		'log'          => array(
			'class'  => 'CLogRouter',
			'routes' => array(
				array(
					'class'       => 'CFileLogRoute',
					'maxFileSize' => '102400',
					'logFile'     => basename( \Kisma::get( 'app.log_file' ) ),
					'logPath'     => $_logFilePath,
					'levels'      => 'error, warning, trace, info, profile, debug',
				),
			),
		),
	),
	//.........................................................................
	//. Application Parameters
	//.........................................................................

	'params'      => file_exists( $_commonConfig ) ? require( $_commonConfig ) : array(),
);