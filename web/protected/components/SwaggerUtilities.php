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

	/**
	 * @param RestService $service
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function getSwaggerForService( $service )
	{
		$path = Yii::app()->basePath . '/components';
		$swagger = Swagger::discover($path);
		$swagger->setDefaultApiVersion( Versions::API_VERSION );
		$swagger->setDefaultBasePath( Yii::app()->getRequest()->getHostInfo() . '/rest' );

		$serviceName = $apiName = $service->getApiName();
		$replacePath = false;
		switch ($service->getType())
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
		// from $swagger->getResource();
//		$swagger->applyDefaults($resource);
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
		$result = $swagger->export($resource);
//		$result = $swagger->jsonEncode($resource, true);

		return $result;
	}

}
