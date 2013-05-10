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
 * A base to handle services accessed through the REST API.
 */
abstract class RestService extends BaseService implements iRestHandler, iSwagger, HttpMethod
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string Full path coming from the URL of the REST call
	 */
	protected $_resourcePath = null;

	/**
	 * @var array Resource path broken into array by path divider ('/')
	 */
	protected $_resourceArray = null;

	/**
	 * @var string First piece of the resource path array
	 */
	protected $_resource = null;

	/**
	 * @var string REST verb to take action on
	 */
	protected $_action = self::Get;


	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param null $resource_path
	 */
	protected function _setResource( $resource_path = null )
	{
		$this->_resourcePath = $resource_path;
		$this->_resourceArray = ( !empty( $this->_resourcePath ) ) ? explode( '/', $this->_resourcePath ) : array();
	}

	/**
	 * @param string $action
	 */
	protected function _setAction( $action = self::Get )
	{
		$this->_action = strtoupper( $action );
	}

	/**
	 * Apply the commonly used REST path members to the class
	 */
	protected function _detectResourceMembers()
	{
		$this->_resource = ( isset( $this->_resourceArray, $this->_resourceArray[0] ) )
			? strtolower( $this->_resourceArray[0] )
			: null;
	}

	/**
	 *
	 */
	protected function _preProcess()
	{
		// throw exception here to stop processing
	}

	/**
	 * @param null $results
	 */
	protected function _postProcess( $results = null )
	{

	}

	/**
	 * @return bool
	 */
	protected function _handleResource()
	{
		return false;
	}

	/**
	 * List all possible resources accessible via this service, return false if this is not applicable
	 *
	 * @Api(
	 *   path="/{api_name}", description="Operations available for this service.",
	 *   @operations(
	 *     @operation(
	 *       httpMethod="GET", summary="List resources available for this service.",
	 *       notes="See listed operations for each resource available.",
	 *       responseClass="Resources", nickname="getResources",
	 *       @parameters(
	 *       ),
	 *       @errorResponses(
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @return array|boolean
	 */
	protected function _listResources()
	{
		return false;
	}

	/**
	 * @param null   $resource
	 * @param string $action
	 *
	 * @return array|bool
	 * @throws BadRequestException
	 */
	public function processRequest( $resource = null, $action = self::Get)
	{
		$this->_setResource( $resource );
		$this->_setAction( $action );
		$this->_detectResourceMembers();

		$this->_preProcess();

		if ( empty( $this->_resource ) && (0 == strcasecmp( self::Get, $action ) ) )
		{
			$results = $this->_listResources();
			if ( false === $results )
			{
				$results = $this->_handleResource();
			}
		}
		else
		{
			$results = $this->_handleResource();
		}

		$this->_postProcess( $results );

		if ( false === $results )
		{
			$msg = strtoupper($action) . ' Request';
			$msg .= ( empty( $this->_resource ) ) ? " for resource '$this->_resourcePath'" : ' with no resource';
			$msg .=	" is not currently supported by the '$this->_apiName' service API.";
			throw new BadRequestException( $msg );
		}

		return $results;
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
