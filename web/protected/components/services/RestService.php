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

/**
 * RestService
 * A base service class to handle services accessed through the REST API.
 */
abstract class RestService extends BaseService implements iRestHandler, iSwagger
{
	//*************************************************************************
	//* Members
	//*************************************************************************


	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Create a new REST service
	 *
	 * @param array $settings configuration array
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct( $settings = array() )
	{
		parent::__construct( $settings );
	}

	public function actionPost()
	{
		throw new Exception( "POST requests are not supported by this service type.", ErrorCodes::BAD_REQUEST );
	}

	public function actionPut()
	{
		throw new Exception( "PUT requests are not supported by this service type.", ErrorCodes::BAD_REQUEST );
	}

	public function actionMerge()
	{
		throw new Exception( "MERGE requests are not supported by this service type.", ErrorCodes::BAD_REQUEST );
	}

	public function actionDelete()
	{
		throw new Exception( "DELETE requests are not supported by this service type.", ErrorCodes::BAD_REQUEST );
	}

	public function actionGet()
	{
		throw new Exception( "GET requests are not supported by this service type.", ErrorCodes::BAD_REQUEST );
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwagger()
	{
		$swagger = SwaggerUtilities::getBaseInfo( $this->_apiName );
		$swagger['apis'] = $this->getSwaggerApis();
		$swagger['models'] = $this->getSwaggerModels();
		return $swagger;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		$swagger = SwaggerUtilities::getBaseApis( $this->_apiName );
		return $swagger;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerModels()
	{
		$swagger = SwaggerUtilities::getBaseModels();
		return $swagger;
	}
}
