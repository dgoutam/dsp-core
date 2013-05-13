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
 * RestController
 * REST API router and controller
 */
class RestController extends Controller
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
	 * /rest/index
	 */
	public function actionIndex()
	{
		try
		{
			$command = Yii::app()->db->createCommand();

			if ( $this->swagger )
			{
				$services = array(
					array( 'path' => '/user', 'description' => 'User Login' ),
					array( 'path' => '/system', 'description' => 'System Configuration' )
				);
				$command->select( 'api_name,description' )
				->from( 'df_sys_service' )
				->order( 'api_name' )
				->where( 'type != :t', array( ':t' => 'Remote Web Service' ) );
				$result = $command->queryAll();
				foreach ( $result as $service )
				{
					$services[] = array( 'path' => '/' . $service['api_name'], 'description' => $service['description'] );
				}
				$result = SwaggerUtilities::getBaseInfo();
				$result['apis'] = $services;
			}
			else
			{
				// add non-service managers
				$services = array(
					array( 'api_name' => 'user', 'name' => 'User Login' ),
					array( 'api_name' => 'system', 'name' => 'System Configuration' )
				);
				$result = $command->select( 'api_name,name' )->from( 'df_sys_service' )->order( 'api_name' )->queryAll();
				$result = array( 'resources' => array_merge( $services, $result ) );
			}
			$this->handleResults( $result );
		}
		catch ( Exception $ex )
		{
			$this->handleErrors( $ex );
		}
	}

	/**
	 *
	 */
	public function actionGet()
	{
		try
		{
			$svcObj = ServiceHandler::getServiceObject( $this->service );
			if ( $this->swagger )
			{
				$result = SwaggerUtilities::getSwaggerForService( $svcObj );
			}
			else
			{
				$result = $svcObj->processRequest( $this->resource, 'GET' );
			}
			$this->handleResults( $result );
		}
		catch ( Exception $ex )
		{
			$this->handleErrors( $ex );
		}
	}

	/**
	 *
	 */
	public function actionPost()
	{
		try
		{
			// check for verb tunneling
			$tunnel_method = Utilities::getArrayValue( 'HTTP_X_HTTP_METHOD', $_SERVER, '' );
			if ( empty( $tunnel_method ) )
			{
				$tunnel_method = Utilities::getArrayValue( 'method', $_REQUEST, '' );
			}
			if ( !empty( $tunnel_method ) )
			{
				switch ( strtolower( $tunnel_method ) )
				{
					case 'get': // complex retrieves, non-standard
						$this->actionGet();
						break;
					case 'post': // in case they use it in the header as well
						break;
					case 'put':
						$this->actionPut();
						break;
					case 'merge':
					case 'patch':
						$this->actionMerge();
						break;
					case 'delete':
						$this->actionDelete();
						break;
					default:
						if ( !empty( $tunnel_method ) )
						{
							throw new Exception( "Unknown tunneling verb '$tunnel_method' in REST request.", ErrorCodes::BAD_REQUEST );
						}
						break;
				}
			}
			$svcObj = ServiceHandler::getServiceObject( $this->service );
			$result = $svcObj->processRequest( $this->resource, 'POST' );
			$code = ErrorCodes::CREATED;
			$this->handleResults( $result, $code );
		}
		catch ( Exception $ex )
		{
			$this->handleErrors( $ex );
		}
	}

	/**
	 *
	 */
	public function actionMerge()
	{
		try
		{
			$svcObj = ServiceHandler::getServiceObject( $this->service );
			$result = $svcObj->processRequest( $this->resource, 'MERGE' );
			$this->handleResults( $result );
		}
		catch ( Exception $ex )
		{
			$this->handleErrors( $ex );
		}
	}

	/**
	 *
	 */
	public function actionPut()
	{
		try
		{
			$svcObj = ServiceHandler::getServiceObject( $this->service );
			$result = $svcObj->processRequest( $this->resource, 'PUT' );
			$this->handleResults( $result );
		}
		catch ( Exception $ex )
		{
			$this->handleErrors( $ex );
		}
	}

	/**
	 *
	 */
	public function actionDelete()
	{
		try
		{
			$svcObj = ServiceHandler::getServiceObject( $this->service );
			$result = $svcObj->processRequest( $this->resource, 'DELETE' );
			$this->handleResults( $result );
		}
		catch ( Exception $ex )
		{
			$this->handleErrors( $ex );
		}
	}

	/**
	 * Override base method to do some processing of incoming requests
	 *
	 * @param CAction $action
	 *
	 * @return bool
	 * @throws CHttpException
	 */
	protected function beforeAction( $action )
	{
		$temp = strtolower( Utilities::getArrayValue( 'format', $_REQUEST, '' ) );
		if ( !empty( $temp ) )
		{
			$this->format = $temp;
		}

		// determine application if any
		$appName = Utilities::getArrayValue( 'HTTP_X_APPLICATION_NAME', $_SERVER, '' );
		if ( empty( $appName ) )
		{
			$appName = Utilities::getArrayValue( 'app_name', $_REQUEST, '' );
		}
		if ( empty( $appName ) )
		{
			// check for swagger documentation request
			$appName = Utilities::getArrayValue( 'swagger_app_name', $_REQUEST, '' );
			if ( !empty( $appName ) )
			{
				$this->swagger = true;
			}
			else
			{
				$ex = new Exception( "No application name header or parameter value in REST request.", ErrorCodes::BAD_REQUEST );
				$this->handleErrors( $ex );
			}
		}
		$GLOBALS['app_name'] = $appName;

//        'rest/<service:[_0-9a-zA-Z-]+>/<resource:[_0-9a-zA-Z-\/. ]+>'
		$path = Utilities::getArrayValue( 'path', $_GET, '' );
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
				$requestUri = yii::app()->request->requestUri;
				if ( ( false === strpos( $requestUri, '?' ) &&
					   '/' === substr( $requestUri, strlen( $requestUri ) - 1, 1 ) ) ||
					 ( '/' === substr( $requestUri, strpos( $requestUri, '?' ) - 1, 1 ) )
				)
				{
					$this->resource .= '/';
				}
			}
		}

		$_GET['resource'] = $this->resource;

		return parent::beforeAction( $action );
	}

	/**
	 * @param Exception $ex
	 */
	protected function handleErrors( Exception $ex )
	{
		$result = array(
			"error" => array(
				array(
					"message" => htmlentities( $ex->getMessage() ),
					"code"    => $ex->getCode()
				)
			)
		);
		$this->handleResults( $result, $ex->getCode() );
	}

	/**
	 * @param     $result
	 * @param int $code
	 */
	protected function handleResults( $result, $code = 200 )
	{
		$code = ErrorCodes::getHttpStatusCode( $code );
		$title = ErrorCodes::getHttpStatusCodeTitle( $code );
		header( "HTTP/1.1 $code $title" );
		switch ( $this->format )
		{
			case 'json':
				$result = json_encode( $result );
				Utilities::sendJsonResponse( $result );
				break;
			case 'xml':
				$result = Utilities::arrayToXml( '', $result );
				Utilities::sendXmlResponse( $result );
				break;
		}
		/**
		 * @var \Platform\Yii\Components\PlatformWebApplication $app
		 */
		$app = Yii::app();
		$app->addCorsHeaders(  );
		$app->end();
	}
}