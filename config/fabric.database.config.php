<?php
/**
 * fabric.database.config.php
 * The database configuration file for shared DSPs
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

global $_dbName, $_dspName;

require_once dirname( __DIR__ ) . '/web/protected/components/HttpMethod.php';
require_once dirname( __DIR__ ) . '/web/protected/components/Curl.php';
require_once dirname( __DIR__ ) . '/web/protected/components/Pii.php';

//*************************************************************************
//* Constants
//*************************************************************************

/**
 * @var string
 */
const AUTH_ENDPOINT = 'http://cerberus.fabric.dreamfactory.com/api/instance/credentials';
/**
 * @var string
 */
const DSP_HOST = '___DSP_HOST___';
/**
 * @var string
 */
const DSP_CREDENTIALS = '___DSP_CREDENTIALS___';
/**
 * @var string
 */
const DSP_DB_CONFIG_FILE_NAME = '/database.config.php';
/**
 * @var string
 */
const DSP_DB_CONFIG = '___DSP_DB_CONFIG___';
/**
 * @var string
 */
const STORAGE_KEY = '___DSP_STORAGE_KEY___';

//*************************************************************************
//* Initialize
//*************************************************************************

//	Make sure the session has started...
if ( !isset( $_SESSION ) )
{
	Log::debug( 'Starting session...' );
	session_start();
}

//	If this isn't a cloud request, bail
$_host = Option::get( $_SESSION, '__DSP_HOST__', isset( $_SERVER, $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : gethostname() );

if ( false === strpos( $_host, '.cloud.dreamfactory.com' ) )
{
	throw new \CHttpException( 401, 'You are not authorized to access this system (' . $_host . ').' );
}

//*************************************************************************
//* Load Configuration
//*************************************************************************

//	If there is a prior config, we'll use it for this session.
if ( null !== ( $_config = Option::get( $_SESSION, DSP_DB_CONFIG ) ) && file_exists( $_config ) )
{
	/** @noinspection PhpIncludeInspection */
	return require_once $_config;
}

//	Otherwise we need to build it.
$_parts = explode( '.', $_host );
$_dbName = $_dspName = $_parts[0];

//	Otherwise, get the credentials from the auth server...
$_response = \Curl::get( AUTH_ENDPOINT . '/' . $_dspName . '/database' );

if ( isset( $_response->details, $_response->details->code ) && 404 == $_response->details->code )
{
	Log::error( 'Instance "' . $_dspName . '" not found.' );
	throw new CHttpException( 404, 'Instance not valid.' );
}

if ( !$_response || !is_object( $_response ) || false == $_response->success )
{
	Log::error( 'Error connecting to Cerberus Authentication System: ' . print_r( $_response, true ) );
	throw new RuntimeException( 'Cannot connect to authentication service' );
}

$_cache = $_response->details;

$_date = date( 'c' );
$_privatePath = $_cache->private_path;

//	Save for later
Option::set( $_SESSION, STORAGE_KEY, $_cache->storage_key );
Option::set( $_SESSION, DSP_HOST, $_host );
Option::set( $_SESSION, DSP_DB_CONFIG, $_privatePath . DSP_DB_CONFIG_FILE_NAME );

if ( file_exists( $_privatePath . DSP_DB_CONFIG_FILE_NAME ) )
{
	/** @noinspection PhpIncludeInspection */
	return require_once $_privatePath . DSP_DB_CONFIG_FILE_NAME;
}

$_config = array(
	'connectionString' => 'mysql:host=localhost;port=3306;dbname=' . $_cache->db_name,
	'username'         => $_cache->db_user,
	'password'         => $_cache->db_password,
	'emulatePrepare'   => true,
	'charset'          => 'utf8',
);

//	Also create a config file for next time...
file_put_contents(
	$_privatePath . DSP_DB_CONFIG_FILE_NAME,
	<<<PHP
<?php
/**
* DO NOT MODIFY THIS FILE. ANY CHANGES WILL BE OVERWRITTEN
* @(#)\$Id: database.config.php,v 0.0.1 {$_date} \$
*/
return array(
	'connectionString' => 'mysql:host=localhost;port=3306;dbname={$_cache->db_name}',
	'username'         => '{$_cache->db_user}',
	'password'         => '{$_cache->db_password}',
	'emulatePrepare'   => true,
	'charset'          => 'utf8',
);
PHP
);

//	Return the config
return $_config;
