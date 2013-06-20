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

use Kisma\Core\Utility\Option;
use Platform\Services\AwsDynamoDbSvc;
use Platform\Services\AwsSimpleDbSvc;
use Platform\Services\AwsS3Svc;
use Platform\Services\BaseService;
use Platform\Services\EmailSvc;
use Platform\Services\LocalFileSvc;
use Platform\Services\OpenStackObjectStoreSvc;
use Platform\Services\RemoteWebSvc;
use Platform\Services\RestService;
use Platform\Services\SchemaSvc;
use Platform\Services\SqlDbSvc;
use Platform\Services\SystemManager;
use Platform\Services\UserManager;
use Platform\Services\WindowsAzureBlobSvc;
use Platform\Services\WindowsAzureTablesSvc;
use Platform\Yii\Utility\Pii;

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
	 * @return array
	 * @throws \Exception
	 */
	public static function getServiceListing()
	{
		$command = Pii::db()->createCommand();

		return $command->select( 'api_name,name' )->from( 'df_sys_service' )->queryAll();
	}

	/**
	 * Retrieves the record of the particular service
	 *
	 * @access private
	 *
	 * @param string $api_name
	 *
	 * @return array The service record array
	 * @throws \Exception if retrieving of service is not possible
	 */
	private static function getService( $api_name )
	{
		$command = Pii::db()->createCommand();
		$result = $command->from( 'df_sys_service' )
				  ->where( 'api_name=:name' )
				  ->queryRow( true, array( ':name' => $api_name ) );
		if ( !$result )
		{
			return array();
		}

		if ( isset( $result['credentials'] ) )
		{
			$result['credentials'] = json_decode( $result['credentials'], true );
		}

		if ( isset( $result['parameters'] ) )
		{
			$result['parameters'] = json_decode( $result['parameters'], true );
		}

		if ( isset( $result['headers'] ) )
		{
			$result['headers'] = json_decode( $result['headers'], true );
		}

		return $result;
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
					$record = static::getService( $api_name );
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
						case 'Local Email Service':
							$service = new EmailSvc( $record, true );
							break;
						case 'Remote Email Service':
							$service = new EmailSvc( $record, false );
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
								case 'couchdb':
									throw new \Exception( "NoSQL Storage Type not currently supported." );
									break;
								default:
									throw new \Exception( "Invalid NoSQL Storage Type '$storageType' in configuration environment." );
									break;
							}
							break;
						default:
							throw new \Exception( "Unknown type value '$type' in service record." );
							break;
					}
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

}
