<?php
/**
 * Curl.php
 */
/**
 * Curl
 * A kick-ass cURL wrapper
 */
class Curl implements HttpMethod
{
	//*************************************************************************
	//* Private Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected static $_userName = null;
	/**
	 * @var string
	 */
	protected static $_password = null;
	/**
	 * @var int
	 */
	protected static $_hostPort = null;
	/**
	 * @var array The error of the last call
	 */
	protected static $_error = null;
	/**
	 * @var array The results of the last call
	 */
	protected static $_info = null;
	/**
	 * @var array Default cURL options
	 */
	protected static $_curlOptions = array();
	/**
	 * @var int The last http code
	 */
	protected static $_lastHttpCode = null;
	/**
	 * @var bool Enable/disable logging
	 */
	protected static $_debug = true;
	/**
	 * @var bool If true, and response is "application/json" content-type, it will be returned decoded
	 */
	protected static $_autoDecodeJson = true;
	/**
	 * @var bool If true, auto-decoded response is returned as an array
	 */
	protected static $_decodeToArray = false;

	//*************************************************************************
	//* Public Methods
	//*************************************************************************

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return string|\stdClass
	 */
	public static function get( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Get, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function put( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Put, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function post( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Post, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function delete( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Delete, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function head( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Head, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function options( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Options, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function copy( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Copy, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return bool|mixed|\stdClass
	 */
	public static function patch( $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( static::Patch, $url, $payload, $curlOptions );
	}

	/**
	 * @param string          $method
	 * @param string          $url
	 * @param array|\stdClass $payload
	 * @param array           $curlOptions
	 *
	 * @return string|\stdClass
	 */
	public static function request( $method, $url, $payload = array(), $curlOptions = array() )
	{
		return static::_httpRequest( $method, $url, $payload, $curlOptions );
	}

	//**************************************************************************
	//* Private Methods
	//**************************************************************************

	/**
	 * @param string $method
	 * @param string $url
	 * @param array  $payload
	 * @param array  $curlOptions
	 *
	 * @throws \InvalidArgumentException
	 * @return bool|mixed|\stdClass
	 */
	protected static function _httpRequest( $method = self::Get, $url, $payload = array(), $curlOptions = array() )
	{
		//	Reset!
		static::$_lastHttpCode = static::$_error = static::$_info = $_tmpFile = null;

		//	Build a curl request...
		$_curl = curl_init( $url );

		//	Defaults
		$_curlOptions = array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_SSL_VERIFYPEER => false,
		);

		//	Merge in the global options if any
		if ( !empty( static::$_curlOptions ) )
		{
			$curlOptions = array_merge(
				$curlOptions,
				static::$_curlOptions
			);
		}

		//	Add/override user options
		if ( !empty( $curlOptions ) )
		{
			foreach ( $curlOptions as $_key => $_value )
			{
				$_curlOptions[$_key] = $_value;
			}
		}

		if ( null !== static::$_userName || null !== static::$_password )
		{
			$_curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
			$_curlOptions[CURLOPT_USERPWD] = static::$_userName . ':' . static::$_password;
		}

		switch ( $method )
		{
			case static::Get:
				//	Do nothing, like the goggles...
				break;

			case static::Put:
				$_payload = json_encode( !empty( $payload ) ? $payload : array() );

				$_tmpFile = tmpfile();
				fwrite( $_tmpFile, $_payload );
				rewind( $_tmpFile );

				$_curlOptions[CURLOPT_PUT] = true;
				$_curlOptions[CURLOPT_INFILE] = $_tmpFile;
				$_curlOptions[CURLOPT_INFILESIZE] = mb_strlen( $_payload );
				break;

			case static::Post:
				$_curlOptions[CURLOPT_POST] = true;
				$_curlOptions[CURLOPT_POSTFIELDS] = $payload;
				break;

			case static::Head:
				$_curlOptions[CURLOPT_NOBODY] = true;
				break;

			case static::Patch:
				$_curlOptions[CURLOPT_CUSTOMREQUEST] = static::Patch;
				$_curlOptions[CURLOPT_POSTFIELDS] = $payload;
				break;

			case static::Delete:
			case static::Options:
			case static::Copy:
				$_curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
				break;
		}

		if ( null !== static::$_hostPort && !isset( $_curlOptions[CURLOPT_PORT] ) )
		{
			$_curlOptions[CURLOPT_PORT] = static::$_hostPort;
		}

		//	Set our collected options
		curl_setopt_array( $_curl, $_curlOptions );

		//	Make the call!
		$_result = curl_exec( $_curl );

		static::$_info = curl_getinfo( $_curl );
		static::$_lastHttpCode = isset( static::$_info, static::$_info['http_code'] ) ? static::$_info['http_code'] : null;

		if ( static::$_debug )
		{
			//	@todo Add debug output
		}

		if ( false === $_result )
		{
			static::$_error = array(
				'code'    => curl_errno( $_curl ),
				'message' => curl_error( $_curl ),
			);
		}
		elseif ( true === $_result )
		{
			//	Worked, but no data...
			$_result = null;
		}

		//	Attempt to auto-decode inbound JSON
		if ( !empty( $_result ) && 'application/json' == ( isset( static::$_info, static::$_info['content_type'] ) ? static::$_info['content_type'] : null ) )
		{
			try
			{
				if ( false !== ( $_json = @json_decode( $_result, static::$_decodeToArray ) ) )
				{
					$_result = $_json;
				}
			}
			catch ( \Exception $_ex )
			{
				//	Ignored
			}
		}

		@curl_close( $_curl );

		//	Close temp file if any
		if ( null !== $_tmpFile )
		{
			@fclose( $_tmpFile );
		}

		return $_result;
	}

	/**
	 * @return array
	 */
	public static function getErrorAsString()
	{
		if ( !empty( static::$_error ) )
		{
			return static::$_error['message'] . ' (' . static::$_error['code'] . ')';
		}

		return null;
	}

	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * @param array $error
	 *
	 * @return void
	 */
	protected static function _setError( $error )
	{
		static::$_error = $error;
	}

	/**
	 * @return array
	 */
	public static function getError()
	{
		return static::$_error;
	}

	/**
	 * @param int $hostPort
	 *
	 * @return void
	 */
	public static function setHostPort( $hostPort )
	{
		static::$_hostPort = $hostPort;
	}

	/**
	 * @return int
	 */
	public static function getHostPort()
	{
		return static::$_hostPort;
	}

	/**
	 * @param array $info
	 *
	 * @return void
	 */
	protected static function _setInfo( $info )
	{
		static::$_info = $info;
	}

	/**
	 * @return array
	 */
	public static function getInfo()
	{
		return static::$_info;
	}

	/**
	 * @param string $password
	 *
	 * @return void
	 */
	public static function setPassword( $password )
	{
		static::$_password = $password;
	}

	/**
	 * @return string
	 */
	public static function getPassword()
	{
		return static::$_password;
	}

	/**
	 * @param string $userName
	 *
	 * @return void
	 */
	public static function setUserName( $userName )
	{
		static::$_userName = $userName;
	}

	/**
	 * @return string
	 */
	public static function getUserName()
	{
		return static::$_userName;
	}

	/**
	 * @param array $curlOptions
	 *
	 * @return void
	 */
	public static function setCurlOptions( $curlOptions )
	{
		static::$_curlOptions = $curlOptions;
	}

	/**
	 * @return array
	 */
	public static function getCurlOptions()
	{
		return static::$_curlOptions;
	}

	/**
	 * @param int $lastHttpCode
	 */
	protected static function _setLastHttpCode( $lastHttpCode )
	{
		static::$_lastHttpCode = $lastHttpCode;
	}

	/**
	 * @return int
	 */
	public static function getLastHttpCode()
	{
		return static::$_lastHttpCode;
	}

	/**
	 * @param boolean $debug
	 */
	public static function setDebug( $debug )
	{
		static::$_debug = $debug;
	}

	/**
	 * @return boolean
	 */
	public static function getDebug()
	{
		return static::$_debug;
	}

	/**
	 * @param boolean $autoDecodeJson
	 */
	public static function setAutoDecodeJson( $autoDecodeJson )
	{
		static::$_autoDecodeJson = $autoDecodeJson;
	}

	/**
	 * @return boolean
	 */
	public static function getAutoDecodeJson()
	{
		return static::$_autoDecodeJson;
	}

	/**
	 * @param boolean $decodeToArray
	 */
	public static function setDecodeToArray( $decodeToArray )
	{
		static::$_decodeToArray = $decodeToArray;
	}

	/**
	 * @return boolean
	 */
	public static function getDecodeToArray()
	{
		return static::$_decodeToArray;
	}

}
