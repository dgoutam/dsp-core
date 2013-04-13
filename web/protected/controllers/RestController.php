<?php
/**
 * REST API router and controller
 */
class RestController extends Controller
{
	// Members

	/**
	 * Default response format
	 *
	 * @var string
	 * either 'json' or 'xml'
	 */
	private $format = 'json';

	/**
	 * service to direct call to
	 *
	 * @var string
	 */
	private $service = '';

	/**
	 * resource to be handled by service
	 *
	 * @var string
	 */
	private $resource = '';

	/**
	 * Swagger controlled get
	 *
	 * @var bool
	 */
	private $swagger = false;

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array();
	}

	/**
	 * Initializes the REST controller.
	 * This method registers the session handler to manager the sessions.
	 */
	public function init()
	{
		// need this running at startup
		try
		{
			$sessHandler = new SessionManager();
		}
		catch ( Exception $ex )
		{
			$ex = new Exception( "Failed to create session service.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR );
			$this->handleErrors( $ex );
		}
	}

	// Actions

	/**
	 *
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
				$result = SwaggerUtilities::swaggerBaseInfo();
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
			switch ( strtolower( $this->service ) )
			{
				case 'system':
					$svcObj = SystemManager::getInstance();
					break;
				case 'user':
					$svcObj = UserManager::getInstance();
					break;
				default:
					$svcObj = ServiceHandler::getServiceObject( $this->service );
					break;
			}
			if ( $this->swagger )
			{
				$result = $svcObj->actionSwagger();
			}
			else
			{
				$result = $svcObj->actionGet();
				$type = $svcObj->getType();
				if ( 0 === strcasecmp( $type, 'Remote Web Service' ) )
				{
					$nativeFormat = $svcObj->getNativeFormat();
					if ( 0 !== strcasecmp( $nativeFormat, $this->format ) )
					{
						// reformat the code here
					}
				}
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
			$code = ErrorCodes::OK;
			switch ( strtolower( $this->service ) )
			{
				case 'system':
					$result = SystemManager::getInstance()->actionPost();
					$code = ErrorCodes::CREATED;
					break;
				case 'user':
					$result = UserManager::getInstance()->actionPost();
					break;
				default:
					$svcObj = ServiceHandler::getServiceObject( $this->service );
					$result = $svcObj->actionPost();

					$type = $svcObj->getType();
					if ( 0 === strcasecmp( $type, 'Remote Web Service' ) )
					{
						$nativeFormat = $svcObj->getNativeFormat();
						if ( 0 !== strcasecmp( $nativeFormat, $this->format ) )
						{
							// reformat the code here
						}
					}
					break;
			}
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
			switch ( strtolower( $this->service ) )
			{
				case 'system':
					$svcObj = SystemManager::getInstance();
					break;
				case 'user':
					$svcObj = UserManager::getInstance();
					break;
				default:
					$svcObj = ServiceHandler::getServiceObject( $this->service );
					break;
			}
			$result = $svcObj->actionMerge();
			$type = $svcObj->getType();
			if ( 0 === strcasecmp( $type, 'Remote Web Service' ) )
			{
				$nativeFormat = $svcObj->getNativeFormat();
				if ( 0 !== strcasecmp( $nativeFormat, $this->format ) )
				{
					// reformat the code here
				}
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
	public function actionPut()
	{
		try
		{
			switch ( strtolower( $this->service ) )
			{
				case 'system':
					$svcObj = SystemManager::getInstance();
					break;
				case 'user':
					$svcObj = UserManager::getInstance();
					break;
				default:
					$svcObj = ServiceHandler::getServiceObject( $this->service );
					break;
			}
			$result = $svcObj->actionPut();
			$type = $svcObj->getType();
			if ( 0 === strcasecmp( $type, 'Remote Web Service' ) )
			{
				$nativeFormat = $svcObj->getNativeFormat();
				if ( 0 !== strcasecmp( $nativeFormat, $this->format ) )
				{
					// reformat the code here
				}
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
	public function actionDelete()
	{
		try
		{
			switch ( strtolower( $this->service ) )
			{
				case 'system':
					$svcObj = SystemManager::getInstance();
					break;
				case 'user':
					$svcObj = UserManager::getInstance();
					break;
				default:
					$svcObj = ServiceHandler::getServiceObject( $this->service );
					break;
			}
			$result = $svcObj->actionDelete();
			$type = $svcObj->getType();
			if ( 0 === strcasecmp( $type, 'Remote Web Service' ) )
			{
				$nativeFormat = $svcObj->getNativeFormat();
				if ( 0 !== strcasecmp( $nativeFormat, $this->format ) )
				{
					// reformat the code here
				}
			}
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
	private function handleErrors( Exception $ex )
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
	private function handleResults( $result, $code = 200 )
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
		Yii::app()->end();
	}
}