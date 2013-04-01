<?php
/**
 * Copyright (c) 2009 - 2012, DreamFactory Software, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of DreamFactory nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY DreamFactory ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL DreamFactory BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Services
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory Software, Inc. (http://www.dreamfactory.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: ServiceHandler.php 66505 2012-04-02 08:45:51Z unknown $
 */

/**
 *
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
	 * @throws Exception
	 */
	public static function getServiceListing()
	{
		$command = Yii::app()->db->createCommand();
		return $command->select('api_name,name')->from('df_sys_service')->queryAll();
	}

	/**
	 * Retrieves the record of the particular service
	 *
	 * @access private
	 *
	 * @param string $api_name
	 *
	 * @return array The service record array
	 * @throws Exception if retrieving of service is not possible
	 */
	private static function getService( $api_name )
	{
		$command = Yii::app()->db->createCommand();
		$result = $command->from( 'df_sys_service' )->where( 'api_name=:name' )->queryRow( true, array( ':name' => $api_name ) );
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
	 * @return object The new or previously constructed XXXSvc
	 * @throws Exception if construction of service is not possible
	 */
	public static function getServiceObject( $api_name, $check_active = false )
	{
		if ( empty( $api_name ) )
		{
			throw new Exception( "Failed to launch service, no service name given." );
		}

		// if it hasn't been created, do so
		$service = Utilities::getArrayValue( $api_name, static::$_services, null );
		if ( isset( $service ) && !empty( $service ) )
		{
			return $service;
		}

		try
		{
			$record = static::getService( $api_name );
			switch ( strtolower( $api_name ) )
			{
				// some special cases first
				case 'app':
					$service = new ApplicationSvc( $record );
					break;
				default:
					$type = Utilities::getArrayValue( 'type', $record, '' );
					switch ( $type )
					{
						case 'Remote Web Service':
							$service = new WebService( $record );
							break;
						case 'Local File Storage':
						case 'Remote File Storage':
							$service = new CommonFileSvc( $record );
							break;
						case 'Local SQL DB':
						case 'Remote SQL DB':
							$service = new DatabaseSvc( $record );
							break;
						case 'Local SQL DB Schema':
						case 'Remote SQL DB Schema':
							$service = new SchemaSvc( $record );
							break;
						case 'Local Email Service':
						case 'Remote Email Service':
							$service = new EmailSvc( $record );
							break;
						default:
							throw new Exception( "Failed to launch service, unknown type value '$type' in service record." );
							break;
					}
					break;
			}
			static::$_services[$api_name] = $service;
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to launch service '$api_name'.\n{$ex->getMessage()}" );
		}

		if ( $check_active && !$service->getIsActive() )
		{
			throw new Exception( "Requested service '$api_name' is not active." );
		}

		return $service;
	}

}
