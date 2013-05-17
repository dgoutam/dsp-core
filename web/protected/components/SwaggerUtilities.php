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
use Swagger\Swagger;
use Kisma\Core\Utility\Option;

/**
 * SwaggerUtilities
 * A utilities class to handle swagger documentation of the REST API.
 */
class SwaggerUtilities
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Swagger base response used by Swagger-UI
	 *
	 * @param string $service
	 *
	 * @return array
	 */
	public static function getBaseInfo( $service = '' )
	{
		$swagger = array(
			'apiVersion'     => Versions::API_VERSION,
			'swaggerVersion' => '1.1',
			'basePath'       => Yii::app()->getRequest()->getHostInfo() . '/rest'
		);

		if ( !empty( $service ) )
		{
			$swagger['resourcePath'] = '/' . $service;
		}

		return $swagger;
	}

	protected static function buildSwagger()
	{
		$basePath = Yii::app()->getRequest()->getHostInfo() . '/rest';

		// build services from database
		$command = Yii::app()->db->createCommand();
		$result = $command->select( 'api_name,type,description' )
			->from( 'df_sys_service' )
			->order( 'api_name' )
			->where( 'type != :t', array( ':t' => 'Remote Web Service' ) )
			->queryAll();

		$services = array(
			array( 'path' => '/user', 'description' => 'User Login' ),
			array( 'path' => '/system', 'description' => 'System Configuration' )
		);
		foreach ( $result as $service )
		{
			$services[] = array( 'path' => '/' . $service['api_name'], 'description' => $service['description'] );
		}

		$_out = SwaggerUtilities::getBaseInfo();
		$_out['apis'] = $services;

		$_swaggerPath = Pii::getParam( 'private_path' ) . '/swagger/';
		// create root directory if it doesn't exists
		if ( !file_exists( $_swaggerPath ) )
		{
			@\mkdir($_swaggerPath, 0777, true);
		}

		file_put_contents( $_swaggerPath . '_.json', json_encode( $_out ) );

		// generate swagger output from file annotations
		$path = Yii::app()->basePath . '/components';
		$swagger = Swagger::discover($path);

		// add static services
		$other = array(
			array( 'api_name' => 'user', 'type' => 'user' ),
			array( 'api_name' => 'system', 'type' => 'system' )
		);
		$result = array_merge( $other, $result );

		// gather the services
		foreach ( $result as $service )
		{
			$serviceName = $apiName = Option::get($service, 'api_name', '');
			$replacePath = false;
			$_type = Option::get($service, 'type', '');
			switch ( $_type )
			{
				case 'Remote Web Service':
					$serviceName = '{web}';
					$replacePath = true;
					// look up definition file and return it
					break;
				case 'Local File Storage':
				case 'Remote File Storage':
					$serviceName = '{file}';
					$replacePath = true;
					break;
				case 'Local SQL DB':
				case 'Remote SQL DB':
					$serviceName = '{sql_db}';
					$replacePath = true;
					break;
				case 'Local SQL DB Schema':
				case 'Remote SQL DB Schema':
					$serviceName = '{sql_schema}';
					$replacePath = true;
					break;
				case 'Local Email Service':
				case 'Remote Email Service':
					$serviceName = '{email}';
					$replacePath = true;
					break;
				default:
					break;
			}
			if ( !array_key_exists('/'.$serviceName, $swagger->registry) )
			{
				throw new Exception("No swagger info");
			}
			$resource = $swagger->registry['/'.$serviceName];

			$resource->resourcePath = str_replace( $serviceName, $apiName, $resource->resourcePath );

//			$swagger->applyDefaults($resource);
			$resource->apiVersion = Versions::API_VERSION;
			$resource->basePath = $basePath;

			// from $swagger->getResource();
			// Sort operation paths alphabetically with shortest first
			$apis = $resource->apis;

			$paths = array();
			foreach ($apis as $key => $api) {
				$paths[$key] = str_replace('.{format}', '', $api->path);
				if ( $replacePath )
				{
					$api->path = str_replace( $serviceName, $apiName, $api->path );
					$apis[$key] = $api;
				}
			}
			array_multisort($paths, SORT_ASC, $apis);
			$resource->apis = $apis;

			// cache to a file for later retrieves
			$_content = $swagger->jsonEncode($resource, true);

			$_filePath = $_swaggerPath . $apiName . '.json';
			file_put_contents( $_filePath, $_content );
		}

		return $_out;
	}

	public static function getSwagger()
	{
		$_swaggerPath = Pii::getParam( 'private_path' ) . '/swagger/';
		$_filePath = $_swaggerPath . '_.json';
		if ( !file_exists( $_filePath ) )
		{
			static::buildSwagger();
			if ( !file_exists( $_filePath ) )
			{
				throw new Exception( "Failed to create swagger cache.", ErrorCodes::INTERNAL_SERVER_ERROR );
			}
		}

		$_content = file_get_contents( $_filePath );
		$_out = array();
		if ( !empty( $_content ) )
		{
			$_out = json_decode( $_content, true );
		}

		return $_out;
	}

	/**
	 * @param string $service
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function getSwaggerForService( $service )
	{
		$_swaggerPath = Pii::getParam( 'private_path' ) . '/swagger/';
		$_filePath = $_swaggerPath . $service . '.json';
		if ( !file_exists( $_filePath ) )
		{
			static::buildSwagger();
			if ( !file_exists( $_filePath ) )
			{
				throw new Exception( "Failed to create swagger cache.", ErrorCodes::INTERNAL_SERVER_ERROR );
			}
		}

		$_content = file_get_contents( $_filePath );
		$_out = array();
		if ( !empty( $_content ) )
		{
			$_out = json_decode( $_content, true );
		}

		return $_out;
	}

}
