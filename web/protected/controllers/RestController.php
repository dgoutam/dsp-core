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
use DreamFactory\Common\Enums\OutputFormats;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Utility\RestResponse;
use DreamFactory\Platform\Utility\ServiceHandler;
use DreamFactory\Platform\Utility\SwaggerUtilities;
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
	 * @var string Default response format, either 'json' or 'xml'
	 */
	protected $format = 'json';
	/**
	 * @var string service to direct call to
	 */
	protected $service = '';
	/**
	 * @var string resource to be handled by service
	 */
	protected $resource = '';
	/**
	 * @var bool Swagger controlled get
	 */
	protected $swagger = false;

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
			$_resultFormat = null;

			if ( $this->swagger )
			{
				$_result = SwaggerUtilities::getSwagger();
				$_resultFormat = 'json';
			}
			else
			{
				$_result = array( 'service' => Service::available( false, array( 'id', 'api_name' ) ) );

				unset( $_services );
			}

			RestResponse::sendResults( $_result, RestResponse::Ok, $_resultFormat, $this->format );
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
			$_resultFormat = null;

			if ( $this->swagger )
			{
				$result = SwaggerUtilities::getSwaggerForService( $this->service );
				$_resultFormat = 'json';
			}
			else
			{
				$result = ServiceHandler::getService( $this->service )->processRequest( $this->resource, HttpMethod::Get );
			}

			RestResponse::sendResults( $result, RestResponse::Ok, $_resultFormat, $this->format );
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

			$svcObj = ServiceHandler::getService( $this->service );
			$result = $svcObj->processRequest( $this->resource, HttpMethod::Post );
			$code = RestResponse::Created;

			RestResponse::sendResults( $result, $code, null, $this->format );
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
			$svcObj = ServiceHandler::getService( $this->service );
			$result = $svcObj->processRequest( $this->resource, HttpMethod::Merge );

			RestResponse::sendResults( $result, RestResponse::Ok, null, $this->format );
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
			$svcObj = ServiceHandler::getService( $this->service );
			$result = $svcObj->processRequest( $this->resource, HttpMethod::Put );

			RestResponse::sendResults( $result, RestResponse::Ok, null, $this->format );
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
			$svcObj = ServiceHandler::getService( $this->service );
			$result = $svcObj->processRequest( $this->resource, HttpMethod::Delete );
			RestResponse::sendResults( $result, RestResponse::Ok, null, $this->format );
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
		$this->_determineAppName();
		$this->format = strtolower( FilterInput::request( 'format', 'json', FILTER_SANITIZE_STRING ) );

//        'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>'
		$path = Option::get( $_GET, 'path', '' );
		$slashIndex = strpos( $path, '/' );
		if ( false === $slashIndex )
		{
			$this->service = $path;
		}
		else
		{
			$this->service = substr( $path, 0, $slashIndex );
			$this->resource = substr( $path, $slashIndex + 1 );
			// fix removal of trailing slashes from resource
			if ( !empty( $this->resource ) )
			{
				$requestUri = Yii::app()->request->requestUri;
				if ( ( false === strpos( $requestUri, '?' ) &&
					   '/' === substr( $requestUri, strlen( $requestUri ) - 1, 1 ) ) ||
					 ( '/' === substr( $requestUri, strpos( $requestUri, '?' ) - 1, 1 ) )
				)
				{
					$this->resource .= '/';
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
		$_appName = null;

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
			//	Check for swagger documentation request
			$_appName = FilterInput::request( 'swagger_app_name', null, FILTER_SANITIZE_STRING );
			if ( empty( $_appName ) )
			{
				RestResponse::sendErrors( new BadRequestException( 'No application name header or parameter value in REST request.' ) );
			}

			$this->swagger = true;
		}

		return $GLOBALS['app_name'] = $_appName;
	}

	/**
	 * @return string
	 */
	public function getFormat()
	{
		return $this->format;
	}

	/**
	 * @param string $format
	 *
	 * @return RestController
	 */
	public function setFormat( $format )
	{
		$this->format = $format;

		return $this;
	}
}