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
use DreamFactory\Platform\Enums\ResponseFormats;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Utility\ResourceStore;
use DreamFactory\Platform\Utility\RestResponse;
use DreamFactory\Platform\Utility\ServiceHandler;
use DreamFactory\Platform\Yii\Models\Service;
use DreamFactory\Yii\Controllers\BaseFactoryController;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * RestController
 * REST API router and controller
 */
class RestController extends BaseFactoryController
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var string Default output format, either 'json' or 'xml'. NOTE: Output format is different from RESPONSE format (inner payload format vs. envelope)
	 */
	protected $_outputFormat = 'json';
	/**
	 * @var int The inner payload response format
	 */
	protected $_responseFormat = null;
	/**
	 * @var string service to direct call to
	 */
	protected $_service;
	/**
	 * @var string resource to be handled by service
	 */
	protected $_resource;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * All authorization handled by services
	 *
	 * @return array
	 */
	public function accessRules()
	{
		return array();
	}

	/**
	 * /rest/index
	 */
	public function actionIndex()
	{
		try
		{
			$_result = array( 'service' => Service::available( false, array( 'id', 'api_name' ) ) );

			RestResponse::sendResults( $_result, RestResponse::Ok, null, $this->_outputFormat );
		}
		catch ( \Exception $_ex )
		{
			RestResponse::sendErrors( $_ex );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionGet()
	{
		try
		{
			$svcObj = ServiceHandler::getService( $this->_service );
			$result = $svcObj->processRequest( $this->_resource, HttpMethod::Get );
			$resultFormat = $svcObj->getNativeFormat();

			RestResponse::sendResults( $result, RestResponse::Ok, $resultFormat, $this->_outputFormat );
		}
		catch ( \Exception $ex )
		{
			RestResponse::sendErrors( $ex );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionPost()
	{
		try
		{
			//	Check for verb tunneling
			$_tunnelMethod = FilterInput::server( 'HTTP_X_HTTP_METHOD', null, FILTER_SANITIZE_STRING );

			if ( empty( $_tunnelMethod ) )
			{
				$_tunnelMethod = FilterInput::request( 'method', null, FILTER_SANITIZE_STRING );
			}

			if ( !empty( $_tunnelMethod ) )
			{
				switch ( strtoupper( $_tunnelMethod ) )
				{
					case HttpMethod::Get:
						// complex retrieves, non-standard
						$this->actionGet();
						break;

					case HttpMethod::Post:
						// in case they use it in the header as well
						break;

					case HttpMethod::Put:
						$this->actionPut();
						break;

					case HttpMethod::Merge:
					case HttpMethod::Patch:
						$this->actionMerge();
						break;

					case HttpMethod::Delete:
						$this->actionDelete();
						break;

					default:
						throw new BadRequestException( 'Unknown tunneling verb "' . $_tunnelMethod . '" in request.' );
				}
			}

			$svcObj = ServiceHandler::getService( $this->_service );
			$result = $svcObj->processRequest( $this->_resource, HttpMethod::Post );
			$resultFormat = $svcObj->getNativeFormat();
			$code = RestResponse::Created;

			RestResponse::sendResults( $result, $code, $resultFormat, $this->_outputFormat );
		}
		catch ( \Exception $ex )
		{
			RestResponse::sendErrors( $ex );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionMerge()
	{
		try
		{
			$svcObj = ServiceHandler::getService( $this->_service );
			$result = $svcObj->processRequest( $this->_resource, HttpMethod::Merge );
			$resultFormat = $svcObj->getNativeFormat();

			RestResponse::sendResults( $result, RestResponse::Ok, $resultFormat, $this->_outputFormat );
		}
		catch ( \Exception $ex )
		{
			RestResponse::sendErrors( $ex );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionPut()
	{
		try
		{
			$svcObj = ServiceHandler::getService( $this->_service );
			$result = $svcObj->processRequest( $this->_resource, HttpMethod::Put );
			$resultFormat = $svcObj->getNativeFormat();

			RestResponse::sendResults( $result, RestResponse::Ok, $resultFormat, $this->_outputFormat );
		}
		catch ( \Exception $ex )
		{
			RestResponse::sendErrors( $ex );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionDelete()
	{
		try
		{
			$svcObj = ServiceHandler::getService( $this->_service );
			$result = $svcObj->processRequest( $this->_resource, HttpMethod::Delete );
			$resultFormat = $svcObj->getNativeFormat();

			RestResponse::sendResults( $result, RestResponse::Ok, $resultFormat, $this->_outputFormat );
		}
		catch ( \Exception $ex )
		{
			RestResponse::sendErrors( $ex );
		}
	}

	/**
	 * Override base method to do some processing of incoming requests
	 *
	 * @param \CAction $action
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function beforeAction( $action )
	{
		$GLOBALS['app_name'] = $this->_determineAppName();
		$this->_outputFormat = $this->_determineFormat();

//        'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>'
		$path = Option::get( $_GET, 'path', '' );
		$slashIndex = strpos( $path, '/' );
		if ( false === $slashIndex )
		{
			$this->_service = $path;
		}
		else
		{
			$this->_service = substr( $path, 0, $slashIndex );
			$this->_resource = substr( $path, $slashIndex + 1 );
			// fix removal of trailing slashes from resource
			if ( !empty( $this->_resource ) )
			{
				$requestUri = Yii::app()->request->requestUri;
				if ( ( false === strpos( $requestUri, '?' ) &&
					   '/' === substr( $requestUri, strlen( $requestUri ) - 1, 1 ) ) ||
					 ( '/' === substr( $requestUri, strpos( $requestUri, '?' ) - 1, 1 ) )
				)
				{
					$this->_resource .= '/';
				}
			}
		}

		return parent::beforeAction( $action );
	}

	/**
	 * Determine the app_name/API key of this request
	 *
	 * @return mixed
	 */
	protected function _determineAppName()
	{
		// 	Determine application if any
		$_appName = FilterInput::request( 'app_name', null, FILTER_SANITIZE_STRING );
		if ( empty( $_appName ) )
		{
			if ( null === ( $_appName = Option::get( $_SERVER, 'HTTP_X_DREAMFACTORY_APPLICATION_NAME' ) ) )
			{
				//	Old non-name-spaced header
				$_appName = Option::get( $_SERVER, 'HTTP_X_APPLICATION_NAME' );
			}
		}

		//	Still empty?
		if ( empty( $_appName ) )
		{
			RestResponse::sendErrors( new BadRequestException( 'No application name header or parameter value in request.' ) );
		}

		return $_appName;
	}

	/**
	 * @return string
	 */
	protected function _determineFormat()
	{
		$this->_responseFormat = ResponseFormats::RAW;

		$_outputFormat = trim( strtolower( FilterInput::request( 'format', FilterInput::server( 'HTTP_ACCEPT', null, FILTER_SANITIZE_STRING ), FILTER_SANITIZE_STRING ) ) );

		Log::debug( 'Format = ' . $_outputFormat );

		switch ( $_outputFormat )
		{
			case 'json':
			case 'xml':
				//	These are good...
				break;

			default:
				if ( ResponseFormats::contains( $_outputFormat ) )
				{
					//	Set the response format here and in the store
					ResourceStore::setResponseFormat( $this->_responseFormat = $_outputFormat );
				}

				//	Set envelope to JSON
				$_outputFormat = 'json';
				break;
		}

		return $_outputFormat;
	}

	/**
	 * @param string $outputFormat
	 *
	 * @return RestController
	 */
	public function setOutputFormat( $outputFormat )
	{
		$this->_outputFormat = $outputFormat;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getOutputFormat()
	{
		return $this->_outputFormat;
	}

	/**
	 * @param string $resource
	 *
	 * @return RestController
	 */
	public function setResource( $resource )
	{
		$this->_resource = $resource;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getResource()
	{
		return $this->_resource;
	}

	/**
	 * @param int $responseFormat
	 *
	 * @return RestController
	 */
	public function setResponseFormat( $responseFormat )
	{
		$this->_responseFormat = $responseFormat;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getResponseFormat()
	{
		return $this->_responseFormat;
	}

	/**
	 * @param string $service
	 *
	 * @return RestController
	 */
	public function setService( $service )
	{
		$this->_service = $service;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getService()
	{
		return $this->_service;
	}
}
