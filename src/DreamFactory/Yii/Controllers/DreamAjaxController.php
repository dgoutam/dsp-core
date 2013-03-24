<?php
/**
 * DreamAjaxController.php
 */
namespace DreamFactory\Yii\Controllers;

/**
 * DreamAjaxController
 * A convenient base for AJAX controllers
 *
 * @property array $responseTemplate The response structure for an AJAX call
 */
use Kisma\Core\Enums\OutputFormat;

class DreamAjaxController extends DreamRestController
{
	//********************************************************************************
	//* Constants
	//********************************************************************************

	/**
	 * @var int
	 */
	const SUCCESS_RESPONSE = 1;
	/**
	 * @var int
	 */
	const ERROR_RESPONSE = 0;

	//********************************************************************************
	//* Private Members
	//********************************************************************************

	/**
	 * @var array The response structure for an AJAX call
	 */
	protected $_responseTemplate
		= array(
			'result'   => null,
			'message'  => null,
			'response' => null,
		);
	/**
	 * @var bool Enable/disable AJAX method profiling
	 */
	protected $_enableAjaxProfiling = false;
	/**
	 * @var array The profiles being tracked
	 */
	protected static $_ajaxProfiles = array();
	/**
	 * @var array|string
	 */
	protected $_response = null;

	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Initialize the controller
	 */
	public function init()
	{
		parent::init();

		//	There's no layouts in REST!
		$this->layout = false;

		//	Set our access rules..
		$this->addUserActions(
			self::Authenticated,
			array( //	Add your exposed action names here, one per element
			)
		);

		//	Default to JSON
		$this->setOutputFormat( OutputFormat::JSON );
	}

	//********************************************************************************
	//* Private Methods
	//********************************************************************************

	/**
	 * Overridden dispatcher to trap exceptions and return them as errors.
	 *
	 * @param CAction $action
	 *
	 * @return mixed
	 */
	protected function _dispatchRequest( CAction $action )
	{
		try
		{
			//	Dispatch the request
			$this->_response = parent::_dispatchRequest( $action );

			return $this->_response;
		}
		catch ( \Exception $_ex )
		{
			CPSLog::trace( 'Exception: ' . $_ex->getMessage() . ' (' . $_ex->getCode() . ')' );
			$this->_response = $this->_createErrorResponse( $_ex );
			echo $this->_formatOutput( $this->_response );
		}

		return $this->_response;
	}

	/**
	 * Builds a response based on the template. Automatically can return a JSON array of data suitable for jqGrid by
	 * setting $gridResponse to "true".
	 *
	 * @param mixed  $response
	 * @param bool   $gridResponse
	 * @param string $message
	 *
	 * @return string The JSON-encoded response
	 */
	protected function _buildSuccessResponse( $response = null, $gridResponse = false, $message = null )
	{
		if ( $gridResponse )
		{
			return json_encode(
				array(
					 'total'   => count( $response ),
					 'page'    => 1,
					 'records' => count( $response ),
					 'rows'    => $response,
				)
			);
		}

		return $this->_createResponse( $response );
	}

	/**
	 * Builds an error response
	 *
	 * @param mixed  $response
	 * @param string $message
	 *
	 * @return string JSON encoded array
	 */
	protected function _buildErrorResponse( $response = null, $message = null )
	{
		return $this->_createErrorResponse( $response );
	}

	/**
	 * Begins profiling with the specified tag as a marker
	 *
	 * @param string $profileTag
	 */
	protected function _startAjaxProfiling( $profileTag )
	{
		if ( $this->_enableAjaxProfiling )
		{
			if ( isset( self::$_ajaxProfiles[$profileTag] ) )
			{
				unset( self::$_ajaxProfiles[$profileTag] );
			}

			self::$_ajaxProfiles[$profileTag] = microtime( true );
		}
	}

	/**
	 * Ends profiling of the specified tag
	 *
	 * @param string $profileTag
	 */
	protected function _endAjaxProfiling( $profileTag )
	{
		if ( $this->_enableAjaxProfiling )
		{
			if ( !isset( self::$_ajaxProfiles[$profileTag] ) )
			{
				return;
			}

			$_time = microtime( true ) - self::$_ajaxProfiles[$profileTag];
			unset( self::$_ajaxProfiles[$profileTag] );

			CPSLog::trace( 'ajaxProfiler', 'Action "' . $profileTag . '" completed in ' . number_format( $_time, 2 ) . 's' );
		}
	}

	//********************************************************************************
	//* Property Accessors
	//********************************************************************************

	/**
	 * @param string $responseTemplate
	 *
	 * @return \CBaseAjaxController
	 */
	public function setResponseTemplate( $responseTemplate )
	{
		$this->_responseTemplate = $responseTemplate;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getResponseTemplate()
	{
		return $this->_responseTemplate;
	}

	/**
	 * @param  $enableAjaxProfiling
	 *
	 * @return \CBaseAjaxController
	 */
	public function setEnableAjaxProfiling( $enableAjaxProfiling )
	{
		$this->_enableAjaxProfiling = $enableAjaxProfiling;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getEnableAjaxProfiling()
	{
		return $this->_enableAjaxProfiling;
	}

	/**
	 * @static
	 * @return array
	 */
	public static function getAjaxProfiles()
	{
		return self::$_ajaxProfiles;
	}

	/**
	 * @param array|string $response
	 *
	 * @return \CBaseAjaxController
	 */
	public function setResponse( $response )
	{
		$this->_response = $response;

		return $this;
	}

	/**
	 * @return array|string
	 */
	public function getResponse()
	{
		return $this->_response;
	}

}