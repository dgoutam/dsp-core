<?php
namespace DreamFactory\Yii\Controllers;

use DreamFactory\Enums\GraylogLevels;
use DreamFactory\Services\Graylog\GelfLogger;
use DreamFactory\Interfaces\Graylog;
use DreamFactory\Yii\Actions\RestAction;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Enums\OutputFormat;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;

/**
 * DreamRestController
 */
class DreamRestController extends DreamController implements Graylog
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const RESPONSE_FORMAT_V1 = 1;

	/**
	 * @var int
	 */
	const RESPONSE_FORMAT_V2 = 2;

	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @var string The inbound content type
	 */
	protected $_contentType = 'application/json';

	/**
	 * @var int The requested output format. Defaults to null which requires the handler to return the proper format.
	 */
	protected $_outputFormat = OutputFormat::JSON;

	/**
	 * @var bool If true, all inbound request parameters will be passed to the action as a hash instead of individual arguments.
	 */
	protected $_singleParameterActions = false;

	/**
	 * @var int
	 */
	protected $_responseFormat = self::RESPONSE_FORMAT_V1;

	/**
	 * @var float The microtime of the request
	 */
	protected $_timestamp = null;

	/**
	 * @var float The time taken to complete this request
	 */
	protected $_elapsed = null;

	/**
	 * @var int The status code to send back with the response
	 */
	protected $_statusCode = 200;

	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * @InheritDoc
	 */
	public function init()
	{
		parent::init();

		Pii::app()->attachEventHandler( 'onException', array( $this, '_errorHandler' ) );
	}

	/**
	 * @param \CExceptionEvent|\CErrorEvent $event
	 */
	protected function _errorHandler( $event )
	{
		if ( $event->exception instanceof \CHTTPException )
		{
			$event->handled = true;
			$this->_sendResponse( $event->exception, $this->_createErrorResponse( $event ) );
		}
	}

	/**
	 * Runs the action after passing through all filters.
	 * This method is invoked by {@link runActionWithFilters} after all
	 * possible filters have been executed and the action starts to run.
	 *
	 * @param \CAction $action Action to run
	 *
	 * @throws \Exception
	 */
	public function runAction( $action )
	{
		try
		{
			$_oldAction = $this->getAction();

			$this->setAction( $action );

			if ( $this->beforeAction( $action ) )
			{
				$this->_dispatchRequest( $action );
				$this->afterAction( $action );
			}

			$this->setAction( $_oldAction );
		}
		catch ( \Exception $_ex )
		{
			$this->_createErrorResponse( $_ex );
		}
	}

	/**
	 * Creates the action instance based on the action name.
	 * The action can be either an inline action or an object.
	 * The latter is created by looking up the action map specified in {@link actions}.
	 *
	 * @param string                               $actionId
	 *
	 * @return \CAction|\DreamFactory\Yii\Actions\RestAction
	 */
	public function createAction( $actionId )
	{
		return new RestAction(
			$this,
			$actionId ? : $this->defaultAction,
			strtoupper(
				Pii::request()->getRequestType()
			)
		);
	}

	/**
	 * @param int    $statusCode
	 * @param mixed  $response
	 * @param string $contentType
	 * @param bool   $endApp
	 */
	protected function _sendResponse( $statusCode = 200, $response = null, $contentType = null, $endApp = true )
	{
		$_contentType = $contentType ? : ( $this->_outputFormat == OutputFormat::JSON ? 'application/json' : 'text/html' );
		$_statusCode = $statusCode;
		$_message = null;

		if ( $statusCode instanceof \CHttpException )
		{
			$_statusCode = $statusCode->statusCode;
			$_message = $statusCode->getMessage();
		}
		elseif ( $statusCode instanceOf \Exception )
		{
			$_statusCode = $statusCode->getCode();
			$_message = $statusCode->getMessage();
		}

		$_header[] = 'HTTP/1.1 ' . $_statusCode . ' ' . $_message;
		$_header[] = 'Content-type: ' . $_contentType;

		array_walk(
			$_header,
			function ( $value, $key )
			{
				foreach ( explode( PHP_EOL, $value ) as $_header )
				{
					header( $_header );
				}
			}
		);

		// pages with body are easy
		if ( !empty( $response ) )
		{
			if ( OutputFormat::JSON == $this->_outputFormat && !is_string( $response ) )
			{
				$response = json_encode( $response );
			}
		}

		echo $response;

		if ( true !== $endApp )
		{
			return;
		}

		die();
	}

	/**
	 * Runs the named REST action.
	 * Filters specified via {@link filters()} will be applied.
	 *
	 * @param \CAction|\DreamFactory\Yii\Actions\RestAction $action
	 *
	 * @return mixed
	 */
	protected function _dispatchRequest( \CAction $action )
	{
		$this->_statusCode = 200;

		$_actionId = $action->getId();
		$_urlParameters = $this->_parseRequest();
		$_callResults = null;

		//	If the inbound request is JSON data, convert to an array
		if ( false !== stripos( $this->_contentType, 'application/json' ) && !empty( $GLOBALS['HTTP_RAW_POST_DATA'] ) )
		{
			//	Merging madness!
			$_urlParameters = array_merge(
				$_urlParameters,
				json_decode( $GLOBALS['HTTP_RAW_POST_DATA'], true )
			);
		}

		//	Is it a valid request?
		$_httpMethod = strtoupper( Pii::request()->getRequestType() );

		switch ( $_httpMethod )
		{
			case HttpMethod::Post:
				foreach ( $_POST as $_key => $_value )
				{
					if ( !is_array( $_value ) )
					{
						$_urlParameters[$_key] = $_value;
					}
					else
					{
						foreach ( $_value as $_subKey => $_subValue )
						{
							$_urlParameters[$_subKey] = $_subValue;
						}
					}
				}
				break;

			case HttpMethod::Get:
				break;
		}

		/**
		 * If the determined target method is the same as the request method, shift the action into
		 * the first parameter position.
		 *
		 * GET /api/resource/<resourceId> becomes $object->get(<resourceId>[,<payload>])
		 */
		if ( $_httpMethod === ( $_targetMethod = $this->_determineMethod( $_httpMethod, $_actionId ) ) )
		{
			array_unshift( $_urlParameters, $_actionId );
		}

		try
		{
			//	Get the additional data ready
			$_logInfo = array(
				'short_message' => $_httpMethod . ' ' . '/' . $this->id . ( $_actionId && '/' != $_actionId ? '/' . $this->action->id :
					null ),
				'full_message'  => $_httpMethod . ' ' . '/' . $this->id . ( $_actionId && '/' != $_actionId ? '/' . $this->action->id : null ),
				'level'         => GraylogLevels::Info,
				'facility'      => Graylog::DefaultFacility . '/' . $this->id,
				'source'        => $_SERVER['REMOTE_ADDR'],
				'payload'       => $_urlParameters,
				'php_server'    => $_SERVER,
			);

			$this->_elapsed = null;
			$this->_timestamp = microtime( true );

			$_callResults = call_user_func_array(
				array(
					 $this,
					 $_targetMethod
				),
				//	Pass in parameters collected as a single array or individual values
				$this->_singleParameterActions ? array( $_urlParameters ) : array_values( $_urlParameters )
			);

			$this->_elapsed = ( microtime( true ) - $this->_timestamp );

			$_response = $this->_createResponse( $_callResults );

			//	Format and echo the results
			$_logInfo['success'] = true;
			$_logInfo['elapsed'] = $this->_elapsed;
			$_logInfo['response'] = $_response;

			GelfLogger::logMessage( $_logInfo );

			$_output = $this->_formatOutput( $_response );

			Log::debug( 'Complete (-)< ' . $_targetMethod . ' < result : ' . PHP_EOL . ( is_scalar( $_output ) ? $_output :
				print_r( $_output, true ) ) );

			$this->_sendResponse( $this->_statusCode ? : 200, $_output );
		}
		catch ( \Exception $_ex )
		{
			$this->_elapsed = microtime( true ) - $this->_timestamp;
			$_response = $this->_createErrorResponse( $_ex, $_ex->getCode(), $_callResults );

			//	Format and echo the results
			$_logInfo['success'] = false;
			$_logInfo['elapsed'] = $this->_elapsed;
			$_logInfo['response'] = $_response;
			$_logInfo['level'] = GraylogLevels::Error;

			GelfLogger::logMessage( $_logInfo );

			$_output = $this->_formatOutput( $_response );
			Log::debug( 'Complete (!)< ' . $_targetMethod . ' < ERROR result : ' . PHP_EOL . ( is_scalar( $_output ) ? $_output :
				print_r( $_output, true ) ) );
			$this->_sendResponse( $_ex, $_output );
		}
	}

	/**
	 * Parses the request and returns a KVP array of parameters
	 *
	 * @return array
	 */
	protected function _parseRequest()
	{
		$this->_contentType = FilterInput::server( 'CONTENT_TYPE' );
		$_urlParameters = array();
		$_options = array();

		$_uri = Pii::request()->getRequestUri();

		//	If additional parameters are specified in the URL, convert to parameters...
		$_frag = '/' . $this->getId() . '/' . $this->action->id;

		//	Strip off everything after the route...
		if ( null != ( $_uri = trim( substr( $_uri, stripos( $_uri, $_frag ) + strlen( $_frag ) ), ' /?' ) ) )
		{
			$_options = ( !empty( $_uri ) ? explode( '/', $_uri ) : array() );

			foreach ( $_options as $_key => $_value )
			{
				if ( false !== strpos( $_value, '=' ) )
				{
					if ( null != ( $_list = explode( '=', $_value ) ) )
					{
						$_options[$_list[0]] = $_list[1];
					}

					unset( $_options[$_key] );
				}
				else
				{
					$_options[$_key] = $_value;
				}
			}
		}

		//	Any query string? (?x=y&...)
		if ( null != ( $_queryString = \parse_url( $_uri, PHP_URL_QUERY ) ) )
		{
			$_queryOptions = array();
			\parse_str( $_queryString, $_queryOptions );
			$_options = \array_merge( $_queryOptions, $_options );

			//	Remove Yii route variable
			if ( isset( $_options['r'] ) )
			{
				unset( $_options['r'] );
			}
		}

		//	load into url params
		foreach ( $_options as $_key => $_value )
		{
			if ( !isset( $_urlParameters[$_key] ) )
			{
				if ( $this->_singleParameterActions )
				{
					$_urlParameters[$_key] = $_value;
				}
				else
				{
					$_urlParameters[] = $_value;
				}
			}
		}

		return $_urlParameters;
	}

	/**
	 * @param string $httpMethod
	 * @param string $actionId
	 *
	 * @return string
	 */
	protected function _determineMethod( $httpMethod, $actionId )
	{
		$_targetMethod = strtolower( $httpMethod ) . ucfirst( $actionId );
		$_altTargetMethod = strtolower( $httpMethod ) . '_' . lcfirst( $actionId );

		//.........................................................................
		//. Step 1: Check for "<method>[ActionId]" (i.e. getCar() or postOffice() )
		//.			Also check for underscore prefixed methods (i.e. _getCar())
		//.........................................................................

		if ( method_exists( $this, $_targetMethod ) )
		{
			return $_targetMethod;
		}

		if ( method_exists( $this, $_altTargetMethod ) )
		{
			return $_altTargetMethod;
		}

		//.........................................................................
		//. Step 2: Check for catch-all "request[ActionId]" (i.e. requestCar() or requestOffice() )
		//.			Also check for underscore prefixed methods (i.e. _requestCar())
		//.........................................................................

		$_targetMethod = 'request' . ucfirst( $actionId );
		$_altTargetMethod = 'request_' . lcfirst( $actionId );

		if ( method_exists( $this, $_targetMethod ) )
		{
			return $_targetMethod;
		}

		if ( method_exists( $this, $_altTargetMethod ) )
		{
			return $_altTargetMethod;
		}

		//.........................................................................
		//. Step 3: Check for single purpose controllers (i.e. get(), post(), put(), delete())
		//.			Also check for underscore prefixed methods (i.e. _requestCar())
		//.........................................................................

		//	Single-purpose controllers (i.e. get(), put(), post(), delete(), etc.)
		if ( method_exists( $this, $httpMethod ) )
		{
			return $httpMethod;
		}

		//.........................................................................
		//. Step 4: Let the missingAction() method take care of the request...
		//.........................................................................

		//	No clue what it is, so must be bogus. Hand off to missing action...
		$this->missingAction( $actionId );
	}

	/**
	 * Converts the given argument to the proper format for
	 * return the consumer application.
	 *
	 * @param mixed $output
	 *
	 * @return mixed
	 */
	protected function _formatOutput( $output )
	{
		if ( static::RESPONSE_FORMAT_V2 == $this->_responseFormat )
		{
			$this->setOutputFormat( OutputFormat::JSON );
		}

		//	Transform output
		switch ( $this->_outputFormat )
		{
			case OutputFormat::JSON:
				@header( 'Content-type: application/json' );

				//	Are we already in JSON?
				if ( null !== @json_decode( $output ) )
				{
					break;
				}

				/**
				 * Chose NOT to overwrite in the case of an error while
				 * formatting into json via builtin.
				 */
				//	@todo Not sure if this is all that wise, will cause confusion when your methods return nada.
				if ( false !== ( $_response = json_encode( $output ) ) )
				{
					$output = $_response;
				}
				break;

			case OutputFormat::XML:
				//	Set appropriate content type
				if ( stristr( $_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml' ) )
				{
					header( 'Content-type: application/xhtml+xml;charset=utf-8' );
				}
				else
				{
					header( 'Content-type: text/xml;charset=utf-8' );
				}
				break;

			case OutputFormat::Raw:
				//	Nothing to do...
				break;

			default:
				if ( !is_array( $output ) )
				{
					$output = array( $output );
				}
				break;
		}

		//	And return the formatted (or not as the case may be) output
		return $output;
	}

	/**
	 * Creates a JSON encoded array (as a string) with a standard REST response. Override to provide
	 * a different response format.
	 *
	 * @param array   $resultList
	 * @param boolean $isError
	 * @param string  $errorMessage
	 * @param integer $errorCode
	 * @param array   $additionalInfo
	 *
	 * @return string JSON encoded array
	 */
	protected function _createResponse( $resultList = array(), $isError = false, $errorMessage = 'failure', $errorCode = 0, $additionalInfo = array() )
	{
		if ( static::RESPONSE_FORMAT_V2 == $this->_responseFormat )
		{
			$this->setOutputFormat( OutputFormat::JSON );

			if ( false !== $isError )
			{
				$_response = $resultList;

				if ( empty( $_response ) )
				{
					$_response = array();
				}

				if ( !empty( $additionalInfo ) )
				{
					$_response = array_merge( $additionalInfo, $_response );
				}

				return $this->_buildErrorContainer( $errorMessage, $errorCode, $_response );
			}

			return $this->_buildContainer( true, $resultList );
		}

		//.........................................................................
		//. Version 1
		//.........................................................................

		if ( $isError )
		{
			$_response = array(
				'result'       => 'failure',
				'errorMessage' => $errorMessage,
				'errorCode'    => $errorCode,
			);

			if ( $resultList )
			{
				$_response['resultData'] = $resultList;
			}
		}
		else
		{
			$_response = array(
				'result' => 'success',
			);

			if ( $resultList )
			{
				$_response['resultData'] = $resultList;
			}
		}

		//	Add in any additional info...
		if ( is_array( $additionalInfo ) && !empty( $additionalInfo ) )
		{
			$_response = array_merge(
				$additionalInfo,
				$_response
			);
		}

		return $_response;
	}

	/**
	 * Creates a JSON encoded array (as a string) with a standard REST response. Override to provide
	 * a different response format.
	 *
	 * @param string|Exception $errorMessage
	 * @param integer          $errorCode
	 * @param mixed            $details
	 *
	 * @return string JSON encoded array
	 */
	protected function _createErrorResponse( $errorMessage = 'failure', $errorCode = 0, $details = null )
	{
		if ( static::RESPONSE_FORMAT_V2 == $this->_responseFormat )
		{
			return $this->_buildErrorContainer( $errorMessage, $errorCode, $details );
		}

		//	Version 1
		$_additionalInfo = null;

		if ( $errorMessage instanceof \Exception )
		{
			$_ex = $errorMessage;

			$errorMessage = $_ex->getMessage();
			$details = ( 0 !== $errorCode ? $errorCode : null );
			$errorCode = ( $_ex instanceof \CHttpException ? $_ex->statusCode : $_ex->getCode() );
			$_previous = $_ex->getPrevious();

			//	In debug mode, we output more information
			if ( $this->_debugMode )
			{
				$_additionalInfo = array(
					'errorType'  => 'Exception',
					'errorClass' => get_class( $_ex ),
					'errorFile'  => $_ex->getFile(),
					'errorLine'  => $_ex->getLine(),
					'stackTrace' => $_ex->getTrace(),
					'previous'   => ( $_previous ? $this->_createErrorResponse( $_previous ) : null ),
				);
			}
		}

		if ( $details && !is_array( $details ) )
		{
			$details = array( $details );
		}

		if ( empty( $_additionalInfo ) )
		{
			$_additionalInfo = array();
		}

		$_fullDetails = array_merge( $_additionalInfo, empty( $details ) ? array() : $details );
		if ( empty( $_fullDetails ) )
		{
			$_fullDetails = null;
		}

		//	Set some error headers
		header( 'Pragma: no-cache' );
		header( 'Cache-Control: no-store, no-cache, max-age=0, must-revalidate' );

		return $this->_createResponse(
			array(),
			true,
			$errorMessage,
			$errorCode,
			$_fullDetails
		);
	}

	/**
	 * Builds a v2 error container
	 *
	 * @param string $message
	 * @param int    $code
	 * @param mixed  $details Additional error details
	 *
	 * @return array
	 */
	protected function _buildErrorContainer( $message = 'failure', $code = 0, $details = null )
	{
		if ( empty( $details ) )
		{
			$details = array();
		}

		if ( $message instanceof \Exception )
		{
			$_ex = $message;

			$message = $_ex->getMessage();
			$code = ( $_ex instanceof \CHttpException ? $_ex->statusCode : $_ex->getCode() );
			$_previous = $_ex->getPrevious();

			//	In debug mode, we output more information
			if ( $this->_debugMode )
			{
				$details = array_merge(
					$details,
					array(
						 'errorType'  => 'Exception',
						 'errorClass' => get_class( $_ex ),
						 'errorFile'  => $_ex->getFile(),
						 'errorLine'  => $_ex->getLine(),
						 'stackTrace' => $_ex->getTrace(),
						 'previous'   => ( $_previous ? $this->_createErrorResponse( $_previous ) : null ),
					)
				);
			}
		}

		$details['message'] = $message;
		$details['code'] = $code;

		return $this->_buildContainer( false, $details );
	}

	/**
	 * Builds a v2 response container
	 *
	 * @param bool   $success
	 * @param mixed  $details Additional details/data/payload
	 *
	 * @return array
	 */
	protected function _buildContainer( $success = true, $details = null )
	{
		$_actionId = $this->action->id;
		$_id = md5( $_SERVER['REQUEST_TIME'] . $_SERVER['HTTP_HOST'] ) . '_' . $_SERVER['REMOTE_ADDR'];
		$_resource = '/' . $this->id . ( $_actionId && '/' != $_actionId ? '/' . $this->action->id : null );
		$_uri = str_replace( $_resource, null, Pii::baseUrl( true ) . Pii::url( $this->route ) );

		$_container = array(
			'success' => $success,
			'details' => $details,
			'request' => array(
				'id'        => $_id,
				'timestamp' => $_SERVER['REQUEST_TIME'],
				'elapsed'   => number_format( $this->_elapsed, 4 ),
				'uri'       => $_uri,
				'resource'  => $_resource,
				'signature' => base64_encode( hash_hmac( 'sha256', $_id, $_id, true ) ),
			),
			//	Faux-HAL
			'_links'  => array(
				'self' => array(
					'href' => $_resource,
				)
			),
		);

		return $_container;
	}

	/***
	 * Translates errors from normal model attribute names to REST map names
	 *
	 * @param \CActiveRecord|DreamRestController $model
	 *
	 * @return array
	 */
	protected function _translateErrors( \CActiveRecord $model )
	{
		if ( !Pii::isEmpty( $_errorList = $model->getErrors() ) )
		{
			if ( method_exists( $model, 'attributeRestMap' ) )
			{
				/** @noinspection PhpUndefinedMethodInspection */
				$_restMap = $model->attributeRestMap();
				$_resultList = array();

				foreach ( $_errorList as $_key => $_value )
				{
					if ( in_array( $_key, array_keys( $_restMap ) ) )
					{
						$_resultList[$_restMap[$_key]] = $_value;
					}
				}

				$_errorList = $_resultList;
			}
		}

		return $_errorList;
	}

	/**
	 * @param int $outputFormat
	 *
	 * @return \DreamRestController
	 */
	public function setOutputFormat( $outputFormat )
	{
		$this->_outputFormat = $outputFormat;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getOutputFormat()
	{
		return $this->_outputFormat;
	}

	/**
	 * @param boolean $singleParameterActions
	 *
	 * @return \DreamRestController
	 */
	public function setSingleParameterActions( $singleParameterActions )
	{
		$this->_singleParameterActions = $singleParameterActions;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getSingleParameterActions()
	{
		return $this->_singleParameterActions;
	}

	/**
	 * @return string
	 */
	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * @param int $responseFormat
	 *
	 * @return DreamRestController
	 */
	public function setResponseFormat( $responseFormat = self::RESPONSE_FORMAT_V2 )
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
	 * @return float
	 */
	public function getElapsed()
	{
		return $this->_elapsed;
	}

	/**
	 * @return float
	 */
	public function getTimestamp()
	{
		return $this->_timestamp;
	}

	/**
	 * @param int $statusCode
	 *
	 * @return DreamRestController
	 */
	public function setStatusCode( $statusCode )
	{
		$this->_statusCode = $statusCode;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->_statusCode;
	}
}
