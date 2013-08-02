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
const DSP_VERSION = '1.0.6-dev-gha';
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
		 'base_path'                => $_basePath,
		 /**
		  * DSP Information
		  */
		 'dsp.version'              => DSP_VERSION,
		 'dsp.name'                 => $_instanceSettings['dsp_name'],
		 'dsp.auth_endpoint'        => DEFAULT_INSTANCE_AUTH_ENDPOINT,
		 'cloud.endpoint'           => DEFAULT_CLOUD_API_ENDPOINT,
		 /**
		  * Remote Logins
		  */
		 'dsp.allow_remote_logins'  => true,
		 'dsp.remote_login_options' => array(
			 'base_url'   => '/web/authorize',
			 'providers'  => array(
				 'Facebook' => array( // 'id' is your facebook application id
					 'enabled' => true,
					 'keys'    => array( 'id' => '1392217090991437', 'secret' => 'd5dd3a24b1ec6c5f204a300ed24c60d0' ),
					 'scope'   => 'email, user_about_me, user_birthday, user_hometown' // optional
				 ),
				 'Github'   => array( // 'id' is your facebook application id
					 'enabled' => true,
					 'keys'    => array( 'id' => 'caf2ba694afc90d62c2a', 'secret' => '8f5b38a65ddfc0761febe0c113a2e128c43bac9e' ),
					 'scope'   => 'user, user:email, user:follow, public_repo, repo, repo:status, notifications, gist',
				 ),
//				 'Twitter'  => array( // 'key' is your twitter application consumer key
//					 'enabled' => true,
//					 'keys'    => array( 'key' => '', 'secret' => '' )
//				 ),
			 ),
			 'debug_mode' => true,
			 'debug_file' => $_logFilePath . '/' . $_logFileName,
		 ),
		 /**
		  * User data
		  */
		 'blobStorageConfig'        => $_blobConfig,
		 'adminEmail'               => DEFAULT_SUPPORT_EMAIL,
		 /**
		  * The default service configuration
		  */
		 'dsp.service_config'       => require( __DIR__ . SERVICES_CONFIG_PATH ),
		 /**
		  * Default services provided by all DSPs
		  */
		 'dsp.default_services'     => array(
			 array( 'api_name' => 'user', 'name' => 'User Login' ),
			 array( 'api_name' => 'system', 'name' => 'System Configuration' ),
		 ),
		 /**
		  * The default application to start
		  */
		 'dsp.default_app'          => '/public/launchpad/index.html',
	)
);
