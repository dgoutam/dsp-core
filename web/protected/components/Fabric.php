<?php
use Kisma\Core\Enums\DateTime;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\SeedUtility;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

require_once __DIR__ . '/HttpMethod.php';
require_once __DIR__ . '/Curl.php';
require_once __DIR__ . '/Pii.php';

/**
 * Fabric.php
 * The configuration file for fabric-hosted DSPs
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
class Fabric extends SeedUtility
{
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
	const DSP_DB_CONFIG_FILE_NAME_PATTERN = '%%INSTANCE_NAME%%.database.config.php';
	/**
	 * @var string
	 */
	const DSP_DEFAULT_SUBDOMAIN = '.cloud.dreamfactory.com';
	/**
	 * @var string
	 */
	const DSP_DB_CONFIG = '___DSP_DB_CONFIG___';
	/**
	 * @var string
	 */
	const STORAGE_KEY = '___DSP_STORAGE_KEY___';
	/**
	 * @var string My favorite cookie
	 */
	const FigNewton = 'dsp.blob';
	/**
	 * @var string My favorite cookie
	 */
	const PrivateFigNewton = 'dsp.private';
	/**
	 * @var string
	 */
	const BaseStorage = '/data/storage';
	/**
	 * @var string
	 */
	const FABRIC_MARKER = '/var/www/.fabric_hosted';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @return array|mixed
	 * @throws RuntimeException
	 * @throws CHttpException
	 */
	public static function initialize()
	{
		global $_dbName, $_instance;

		//	If this isn't a cloud request, bail
		$_host = isset( $_SERVER, $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : gethostname();

		if ( false === strpos( $_host, '.cloud.dreamfactory.com' ) )
		{
			Log::error( 'Attempt to access system from non-provisioned host: ' . $_host );
			throw new \CHttpException( HttpResponse::Forbidden, 'You are not authorized to access this system you cheeky devil you. (' . $_host . ').' );
		}

		//	What has it gots in its pocketses?
		if ( null === ( $_privateKey = FilterInput::cookie( static::PrivateFigNewton ) ) )
		{
			//	If there is a configuration file available, we'll use it for this session.
			$_privateKey = isset( $_SESSION ) ? Option::get( $_SESSION, static::PrivateFigNewton ) : null;
		}

		if ( null === ( $_key = FilterInput::cookie( static::FigNewton ) ) )
		{
			//	If there is a configuration file available, we'll use it for this session.
			$_key = isset( $_SESSION ) ? Option::get( $_SESSION, static::FigNewton ) : null;
		}

		$_dspName = str_ireplace( static::DSP_DEFAULT_SUBDOMAIN, null, $_host );

		$_dbConfigFileName = str_ireplace(
			'%%INSTANCE_NAME%%',
			$_dspName,
			static::DSP_DB_CONFIG_FILE_NAME_PATTERN
		);

		\Kisma::set( 'platform.dsp_name', $_dspName );
		\Kisma::set( 'platform.db_config_file_name', $_dbConfigFileName );
		\Kisma::set( 'platform.storage_key', $_key );
		\Kisma::set( 'platform.user_key', $_privateKey );

		//	If an existing database config file is found for this instance
		if ( !empty( $_privateKey ) )
		{
			$_config = static::BaseStorage . '/' . $_privateKey . '/.private/' . $_dbConfigFileName;

			if ( file_exists( $_config ) )
			{
				\Kisma::set( 'platform.private_path', static::BaseStorage . '/' . $_privateKey . '/.private' );
				\Kisma::set( 'platform.db_config_file', $_config );

				/** @noinspection PhpIncludeInspection */

				return require_once $_config;
			}
		}

		//	Otherwise we need to build it.
		$_parts = explode( '.', $_host );
		$_dbName = $_dspName = $_parts[0];

		//	Otherwise, get the credentials from the auth server...
		$_response = \Curl::get( static::AUTH_ENDPOINT . '/' . $_dspName . '/database' );

		if ( is_object( $_response ) && isset( $_response->details, $_response->details->code ) && HttpResponse::NotFound == $_response->details->code )
		{
			Log::error( 'Instance "' . $_dspName . '" not found during web initialize.' );
			throw new \CHttpException( HttpResponse::NotFound, 'Instance not available.' );
		}

		if ( !$_response || !is_object( $_response ) || false == $_response->success )
		{
			Log::error( 'Error connecting to Cerberus Authentication System: ' . print_r( $_response, true ) );
			throw new \CHttpException( HttpResponse::InternalServerError, 'Cannot connect to authentication service' );
		}

		$_instance = $_cache = $_response->details;
		$_privatePath = $_cache->private_path;

		\Kisma::set( 'dsp.credentials', $_cache );

		//	Save it for later (don't run away and let me down <== extra points if you get the reference)
		setcookie( static::FigNewton, $_instance->storage_key, time() + DateTime::TheEnd, '/' );
		setcookie( static::PrivateFigNewton, $_privateKey, time() + DateTime::TheEnd, '/' );

		//	File should be there from provisioning... If not, tenemos un problema!
		if ( file_exists( $_privatePath . '/' . $_dbConfigFileName ) )
		{
			\Kisma::set( 'platform.private_path', $_privatePath );
			\Kisma::set( 'platform.db_config_file', $_privatePath . '/' . $_dbConfigFileName );

			/** @noinspection PhpIncludeInspection */

			return require_once $_privatePath . '/' . $_dbConfigFileName;
		}

		Log::error( 'Unable to find private path or database config: ' . $_privatePath . '/' . $_dbConfigFileName );
		throw new \CHttpException( HttpResponse::BadRequest );
	}
}
