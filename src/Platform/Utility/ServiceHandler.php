<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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

use Kisma\Core\Utility\Option;
use Platform\Exceptions\NotFoundException;
use Platform\Services\AwsDynamoDbSvc;
use Platform\Services\AwsSimpleDbSvc;
use Platform\Services\AwsS3Svc;
use Platform\Services\CouchDbSvc;
use Platform\Services\EmailSvc;
use Platform\Services\LocalFileSvc;
use Platform\Services\MongoDbSvc;
use Platform\Services\OAuthService;
use Platform\Services\OpenStackObjectStoreSvc;
use Platform\Services\RemoteWebSvc;
use Platform\Services\RestService;
use Platform\Services\SchemaSvc;
use Platform\Services\ServiceRegistry;
use Platform\Services\SqlDbSvc;
use Platform\Services\SystemManager;
use Platform\Services\UserManager;
use Platform\Services\WindowsAzureBlobSvc;
use Platform\Services\WindowsAzureTablesSvc;

/**
 * ServiceHandler.php
 * DSP service factory
 */
class ServiceHandler
{
	/**
	 * Services
	 *
	 * array of created services
	 *
	 * @access private
	 * @var array
	 */
	private static $_services = array();

	/**
	 * Creates a new ServiceHandler instance
	 *
	 */
	public function __construct()
	{
		// create services as needed, store local pointer in array for speed
		static::$_services = array();
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		if ( !empty( static::$_services ) )
		{
			foreach ( static::$_services as $key => $service )
			{
				unset( static::$_services[$key] );
			}
			static::$_services = null;
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
		if ( empty( $api_name ) )
		{
			throw new \Exception( "Failed to launch service, no service name given." );
		}

		// if it hasn't been created, do so
		$service = Option::get( static::$_services, $api_name, null );
		if ( isset( $service ) && !empty( $service ) )
		{
			return $service;
		}

		try
		{
			switch ( strtolower( $api_name ) )
			{
				// some special cases first
				case 'system':
					$service = new SystemManager();
					break;
				case 'user':
					$service = new UserManager();
					break;
				default:
					$record = \Service::getRecordByName( $api_name );
					if ( empty( $record ) )
					{
						throw new NotFoundException( "Service '$api_name' not found." );
					}
					$service = static::createService( $record );
					break;
			}
			static::$_services[$api_name] = $service;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to launch service '$api_name'.\n{$ex->getMessage()}" );
		}

		if ( $check_active && !$service->getIsActive() )
		{
			throw new \Exception( "Requested service '$api_name' is not active." );
		}

		return $service;
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
	 * @param int     $id
	 * @param boolean $check_active Throws an exception if true and the service is not active.
	 *
	 * @return RestService The new or previously constructed XXXSvc
	 * @throws \Exception if construction of service is not possible
	 */
	public static function getServiceObjectById( $id, $check_active = false )
	{
		if ( empty( $id ) )
		{
			throw new \Exception( "Failed to launch service, no service id given." );
		}

		$record = \Service::getRecordById( $id );
		if ( empty( $record ) )
		{
			throw new \Exception( "Failed to launch service, no service record found for id '$id''." );
		}

		$_apiName = Option::get( $record, 'api_name' );

		// if it hasn't been created, do so
		$service = Option::get( static::$_services, $_apiName, null );
		if ( isset( $service ) && !empty( $service ) )
		{
			return $service;
		}

		try
		{
			$service = static::createService( $record );
			static::$_services[$_apiName] = $service;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Failed to launch service '$_apiName'.\n{$ex->getMessage()}" );
		}

		if ( $check_active && !$service->getIsActive() )
		{
			throw new \Exception( "Requested service '$_apiName' is not active." );
		}

		return $service;
	}

	protected static function createService( $record )
	{
		$type = Option::get( $record, 'type', '' );
		switch ( $type )
		{
			case 'Remote Web Service':
				$service = new RemoteWebSvc( $record );
				break;
			case 'Local File Storage':
				$service = new LocalFileSvc( $record );
				break;
			case 'Remote File Storage':
				$storageType = Option::get( $record, 'storage_type', '' );
				switch ( strtolower( $storageType ) )
				{
					case 'azure blob':
						$service = new WindowsAzureBlobSvc( $record );
						break;
					case 'aws s3':
						$service = new AwsS3Svc( $record );
						break;
					case 'rackspace cloudfiles':
					case 'openstack object storage':
						$service = new OpenStackObjectStoreSvc( $record );
						break;
					default:
						throw new \Exception( "Invalid Remote Blob Storage Type '$storageType' in configuration environment." );
						break;
				}
				break;
			case 'Local SQL DB':
				$service = new SqlDbSvc( $record, true );
				break;
			case 'Remote SQL DB':
				$service = new SqlDbSvc( $record, false );
				break;
			case 'Local SQL DB Schema':
				$service = new SchemaSvc( $record, true );
				break;
			case 'Remote SQL DB Schema':
				$service = new SchemaSvc( $record, false );
				break;
			case 'Email Service':
			case 'Local Email Service':
			case 'Remote Email Service':
				$service = new EmailSvc( $record );
				break;
			case 'NoSQL DB':
				$storageType = Option::get( $record, 'storage_type', '' );
				switch ( strtolower( $storageType ) )
				{
					case 'azure tables':
						$service = new WindowsAzureTablesSvc( $record );
						break;
					case 'aws dynamodb':
						$service = new AwsDynamoDbSvc( $record );
						break;
					case 'aws simpledb':
						$service = new AwsSimpleDbSvc( $record );
						break;
					case 'mongodb':
						$service = new MongoDbSvc( $record );
						break;
					case 'couchdb':
						$service = new CouchDbSvc( $record );
						break;
					default:
						throw new \Exception( "Invalid NoSQL Storage Type '$storageType' in configuration environment." );
						break;
				}
				break;

			case 'Service Registry':
				$service = new ServiceRegistry( $record );
				break;

			case 'Remote OAuth Service':
				$service = new OAuthService( $record );
				break;

			default:
				throw new \Exception( "Unknown type value '$type' in service record." );
				break;
		}

		return $service;
	}
}
