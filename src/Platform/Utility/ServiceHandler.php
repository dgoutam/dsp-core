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
namespace Platform\Utility;

use DreamFactory\Platform\Enums\PlatformServiceTypes;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Enums\ServiceTypes;
use Platform\Services\EmailSvc;
use Platform\Services\LocalFileSvc;
use Platform\Services\RemoteWebSvc;
use Platform\Services\RestService;
use Platform\Services\SchemaSvc;
use Platform\Services\SystemManager;
use Platform\Services\UserManager;

/**
 * ServiceHandler.php
 * DSP service factory
 */
class ServiceHandler
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array Created services
	 */
	protected static $_serviceCache = array();
	/**
	 * @var array The services available
	 */
	protected static $_serviceConfig = array();
	/**
	 * @var array
	 */
	protected static $_baseServices
		= array(
			'system' => 'Platform\\Services\\SystemManager',
			'user'   => 'Platform\\Services\\UserManager',
		);

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new ServiceHandler instance
	 */
	public function __construct()
	{
		//	Create services as needed, store local pointer in array for speed
		static::$_serviceConfig = static::$_serviceCache = array();
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		if ( !empty( static::$_serviceCache ) )
		{
			foreach ( static::$_serviceCache as $_key => $_service )
			{
				unset( static::$_serviceCache[$_key] );
			}

			static::$_serviceCache = null;
		}
	}

	/**
	 * Retrieves the pointer to the particular service handler
	 *
	 * If the service is already created, it just returns the private class
	 * member that holds the pointer, otherwise it calls the constructor for
	 * the new service, passing in parameters based on the stored configuration settings.
	 *
	 * @access public
	 *
	 * @param string  $api_name
	 * @param boolean $check_active Throws an exception if true and the service is not active.
	 *
	 * @return RestService The new or previously constructed XXXSvc
	 * @throws \Exception if construction of service is not possible
	 */
	public static function getServiceObject( $api_name, $check_active = false )
	{
		if ( empty( static::$_serviceConfig ) )
		{
			static::$_serviceConfig = Pii::getParam( 'dsp.service_config', array() );
		}

		$_tag = strtolower( trim( $api_name ) );

		//	Cached?
		if ( null !== ( $_service = Option::get( static::$_serviceCache, $_tag ) ) )
		{
			return $_service;
		}

		//	A base service?
		if ( isset( static::$_baseServices[$_tag] ) )
		{
			return new static::$_baseServices[$_tag];
		}

		try
		{
			if ( null === ( $_config = \Service::getRecordByName( $api_name ) ) )
			{
				throw new \Exception( 'Service not found' );
			}

			$_service = static::_createService( $_config );

			if ( $check_active && !$_service->getIsActive() )
			{
				throw new \Exception( 'Requested service "' . $api_name . '" is not active.' );
			}

			return static::$_serviceCache[$api_name] = $_service;
		}
		catch ( \Exception $_ex )
		{
			throw new \Exception( 'Failed to launch service "' . $api_name . '": ' . $_ex->getMessage() );
		}
	}

	/**
	 * Retrieves the pointer to the particular service handler
	 *
	 * If the service is already created, it just returns the private class
	 * member that holds the pointer, otherwise it calls the constructor for
	 * the new service, passing in parameters based on the stored configuration settings.
	 *
	 * @param int     $id
	 * @param boolean $check_active Throws an exception if true and the service is not active.
	 *
	 * @return RestService The new or previously constructed XXXSvc
	 * @throws \Exception if construction of service is not possible
	 */
	public static function getServiceObjectById( $id, $check_active = false )
	{
		if ( null === ( $_record = \Service::getRecordById( $id ) ) )
		{
			throw new \Exception( "Failed to launch service, no service record found." );
		}

		return static::getServiceObject( $_record['api_name'], $check_active );
	}

	/**
	 * Creates a new instance of a configured service
	 *
	 * @param array $record
	 *
	 * @return RestService
	 * @throws \InvalidArgumentException
	 */
	protected static function _createService( $record )
	{
		if ( null === ( $_typeId = Option::get( $record, 'type_id' ) ) )
		{
			throw new \InvalidArgumentException( 'Service type_id not set in database.' );
		}

		//	Get config mapping...
		if ( null === ( $_config = Option::get( static::$_serviceConfig, $_typeId ) ) )
		{
			throw new \InvalidArgumentException( 'Service type id# "' . $_typeId . '" is not configured.' );
		}

		if ( null !== ( $_serviceClass = Option::get( $_config, 'class' ) ) )
		{
			if ( is_array( $_serviceClass ) )
			{
				$_storageType = strtolower( trim( Option::get( $record, 'storage_type' ) ) );
				$_config = Option::get( $_serviceClass, $_storageType );
				$_serviceClass = Option::get( $_config, 'class' );
			}

			$_arguments = array( $record, Option::get( $_config, 'local', true ) );

			$_mirror = new \ReflectionClass( $_serviceClass );

			return $_mirror->newInstanceArgs( $_arguments );
		}

		throw new \InvalidArgumentException( 'The service requested is invalid.' );
	}
}
