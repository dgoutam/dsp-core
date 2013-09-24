<?php
/**
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
use DreamFactory\Platform\Utility\Fabric;
/**
 * common.config.php
 * This file contains any application-level parameters that are to be shared between the background and web services
 */
//*************************************************************************
//* Constants
//*************************************************************************

/**
 * @var string
 */
const DSP_VERSION = '1.1.0';
/**
 * @var string
 */
const API_VERSION = '1.0';
/**
 * @var string
 */
const BLOB_CONFIG_PATH = '/blob.config.php';
/**
 * @var string
 */
const ALIASES_CONFIG_PATH = '/aliases.config.php';
/**
 * @var string
 */
const SERVICES_CONFIG_PATH = '/services.config.php';
/**
 * @var string
 */
const DEFAULT_CLOUD_API_ENDPOINT = 'http://api.cloud.dreamfactory.com';
/**
 * @var string
 */
const DEFAULT_INSTANCE_AUTH_ENDPOINT = 'http://cerberus.fabric.dreamfactory.com/api/instance/credentials';
/**
 * @var string
 */
const DEFAULT_SUPPORT_EMAIL = 'support@dreamfactory.com';
/**
 * @var string
 */
const DEFAULT_ADMIN_RESOURCE_SCHEMA = '/admin.resource_schema.config.php';


//*************************************************************************
//* Global Configuration Settings
//*************************************************************************

//	The base path of the project, where it's checked out basically
$_basePath = dirname( __DIR__ );
//	The document root
$_docRoot = $_basePath . '/web';
//	The vendor path
$_vendorPath = $_basePath . '/vendor';
//	Set to false to disable database caching
$_dbCacheEnabled = true;
//	The name of the default controller. "site" just sucks
$_defaultController = 'web';
//	Load the BLOB storage configuration settings
$_blobConfig = ( file_exists( __DIR__ . BLOB_CONFIG_PATH ) ? require_once( __DIR__ . BLOB_CONFIG_PATH ) : array() );
//	Where the log files go and the name...
$_logFilePath = $_basePath . '/log';
$_logFileName = basename( \Kisma::get( 'app.log_file' ) );

/**
 * Aliases
 */
file_exists( __DIR__ . ALIASES_CONFIG_PATH ) && require __DIR__ . ALIASES_CONFIG_PATH;

/**
 * Application Paths
 */
\Kisma::set( 'app.app_name', $_appName = 'DreamFactory Services Platform' );
\Kisma::set( 'app.doc_root', $_docRoot );
\Kisma::set( 'app.log_path', $_logFilePath );
\Kisma::set( 'app.vendor_path', $_vendorPath );
\Kisma::set( 'app.log_file_name', $_logFileName );
\Kisma::set( 'app.project_root', $_basePath );

/**
 * Database Caching
 */
$_dbCache = $_dbCacheEnabled ?
	array(
		'class'                => 'CDbCache',
		'connectionID'         => 'db',
		'cacheTableName'       => 'df_sys_cache',
		'autoCreateCacheTable' => true,
	)
	: null;

/**
 * Set up and return the common settings...
 */
if ( Fabric::fabricHosted() )
{
	$_instanceSettings = array(
		'storage_base_path'      => '/data/storage/' . \Kisma::get( 'platform.storage_key' ),
		'private_path'           => \Kisma::get( 'platform.private_path' ),
		'storage_path'           => '/data/storage/' . \Kisma::get( 'platform.storage_key' ) . '/blob',
		'snapshot_path'          => \Kisma::get( 'platform.private_path' ) . '/snapshots',
		'dsp_name'               => \Kisma::get( 'platform.dsp_name' ),
		'dsp.storage_id'         => \Kisma::get( 'platform.storage_key' ),
		'dsp.private_storage_id' => \Kisma::get( 'platform.private_storage_key' ),
	);
}
else
{
	$_instanceSettings = array(
		'storage_base_path'      => $_basePath . '/storage',
		'private_path'           => $_basePath . '/storage/.private',
		'storage_path'           => $_basePath . '/storage',
		'snapshot_path'          => $_basePath . '/storage/.private/snapshots',
		'dsp_name'               => gethostname(),
		'dsp.storage_id'         => null,
		'dsp.private_storage_id' => null,
	);
}

return array_merge(
	$_instanceSettings,
	array(
		 /**
		  * App Information
		  */
		 'base_path'                     => $_basePath,
		 /**
		  * DSP Information
		  */
		 'dsp.version'                   => DSP_VERSION,
		 'dsp.name'                      => $_instanceSettings[ 'dsp_name' ],
		 'dsp.auth_endpoint'             => DEFAULT_INSTANCE_AUTH_ENDPOINT,
		 'cloud.endpoint'                => DEFAULT_CLOUD_API_ENDPOINT,
		 'oauth.salt'                    => 'rW64wRUk6Ocs+5c7JwQ{69U{]MBdIHqmx9Wj,=C%S#cA%+?!cJMbaQ+juMjHeEx[dlSe%h%kcI',
 		 /**
		  * Remote Logins
		  */
		 'dsp.allow_remote_logins'       => true,
		 'dsp.allow_admin_remote_logins' => true,
		 /**
		  * User data
		  */
		 'blobStorageConfig'             => $_blobConfig,
		 'adminEmail'                    => DEFAULT_SUPPORT_EMAIL,
		 /**
		  * The default service configuration
		  */
		 'dsp.service_config'            => require( __DIR__ . SERVICES_CONFIG_PATH ),
		 /** Array of namespaces to locations for service discovery */
		 'dsp.service_location_map'      => array(),
		 /**
		  * Default services provided by all DSPs
		  */
		 'dsp.default_services'          => array(
			 array( 'api_name' => 'user', 'name' => 'User Login' ),
			 array( 'api_name' => 'system', 'name' => 'System Configuration' ),
			 array( 'api_name' => 'api_docs', 'name' => 'API Documentation' ),
		 ),
		 /**
		  * The default application to start
		  */
		 'dsp.default_app'               => '/public/launchpad/index.html',
		 /**
		  * The default application to start
		  */
		 'dsp.db_max_records_returned'   => 1000,
		 /**
		  * The default admin resource schema
		  */
		 'admin.resource_schema'         => require( __DIR__ . DEFAULT_ADMIN_RESOURCE_SCHEMA ),
		 'admin.default_theme'           => 'united',
	)
);
