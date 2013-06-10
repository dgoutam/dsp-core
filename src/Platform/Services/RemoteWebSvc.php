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
namespace Platform\Services;

use Platform\Utility\Curl;
use Platform\Utility\Utilities;

/**
 * RemoteWebSvc
 * A service to handle remote web services accessed through the REST API.
 */
class RemoteWebSvc extends RestService
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_baseUrl;
	/**
	 * @var array
	 */
	protected $_credentials;
	/**
	 * @var array
	 */
	protected $_headers;
	/**
	 * @var array
	 */
	protected $_parameters;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Create a new RemoteWebSvc
	 *
	 * @param array $config configuration array
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		// Validate url setup

		$this->_baseUrl = Utilities::getArrayValue( 'base_url', $config, '' );
		if ( empty( $this->_baseUrl ) )
		{
			throw new \InvalidArgumentException( 'Remote Web Service base url can not be empty.' );
		}
		$this->_credentials = Utilities::getArrayValue( 'credentials', $config, null );
		$this->_headers = Utilities::getArrayValue( 'headers', $config, null );
		$this->_parameters = Utilities::getArrayValue( 'parameters', $config, null );
	}

	/**
	 * @param $action
	 *
	 * @return string
	 */
	protected function buildParameterString( $action )
	{
		$param_str = '';
		foreach ( $_REQUEST as $key => $value )
		{
			switch ( strtolower( $key ) )
			{
				case '_': // timestamp added by jquery
				case 'app_name': // app_name required by our api
				case 'method': // method option for our api
				case 'format':
					break;
				default:
					$param_str .= ( !empty( $param_str ) ) ? '&' : '';
					$param_str .= urlencode( $key );
					$param_str .= ( empty( $value ) ) ? '' : '=' . urlencode( $value );
					break;
			}
		}
		if ( !empty( $this->_parameters ) )
		{
			foreach ( $this->_parameters as $param )
			{
				$paramAction = Utilities::getArrayValue( 'action', $param );
				if ( !empty( $paramAction ) && ( 0 !== strcasecmp( 'all', $paramAction ) ) )
				{
					if ( 0 !== strcasecmp( $action, $paramAction ) )
					{
						continue;
					}
				}
				$key = Utilities::getArrayValue( 'name', $param );
				$value = Utilities::getArrayValue( 'value', $param );
				$param_str .= ( !empty( $param_str ) ) ? '&' : '';
				$param_str .= urlencode( $key );
				$param_str .= ( empty( $value ) ) ? '' : '=' . urlencode( $value );
			}
		}

		return $param_str;
	}

	/**
	 * @param string $action
	 * @param array  $options
	 *
	 * @return array
	 */
	protected function addHeaders( $action, $options = array() )
	{
		if ( !empty( $this->_headers ) )
		{
			foreach ( $this->_headers as $header )
			{
				$headerAction = Utilities::getArrayValue( 'action', $header );
				if ( !empty( $headerAction ) && ( 0 !== strcasecmp( 'all', $headerAction ) ) )
				{
					if ( 0 !== strcasecmp( $action, $headerAction ) )
					{
						continue;
					}
				}
				$key = Utilities::getArrayValue( 'name', $header );
				$value = Utilities::getArrayValue( 'value', $header );
				if ( !isset( $options[CURLOPT_HTTPHEADER] ) )
				{
					$options[CURLOPT_HTTPHEADER] = array( $key . ': ' . $value );
				}
				else
				{
					$options[CURLOPT_HTTPHEADER][] = $key . ': ' . $value;
				}
			}
		}

		return $options;
	}

	protected function _handleResource()
	{
		$param_str = $this->buildParameterString( $this->_action );
		$url = $this->_baseUrl . $this->_resourcePath . '?' . $param_str;

		$co = array();
		$co[CURLOPT_RETURNTRANSFER] = false; // return results directly to browser
		$co[CURLOPT_HEADER] = false; // don't include headers in payload

		// set additional headers
		$co = $this->addHeaders( $this->_action, $co );

		switch ( $this->_action )
		{
			case self::Get:
				$this->checkPermission( 'read' );

				$results = Curl::get( $url, array(), $co );
				break;
			case self::Post:
				$this->checkPermission( 'create' );
				$results = Curl::post( $url, array(), $co );
				break;
			case self::Put:
				$this->checkPermission( 'update' );
				$results = Curl::put( $url, array(), $co );
				break;
			case self::Patch:
				$this->checkPermission( 'update' );
				$results = Curl::patch( $url, array(), $co );
				break;
			case self::Merge:
				$this->checkPermission( 'update' );
				$results = Curl::merge( $url, array(), $co );
				break;
			case self::Delete:
				$this->checkPermission( 'delete' );
				$results = Curl::delete( $url, array(), $co );
				break;
			default:
				return false;
		}

		if ( false === $results )
		{
			$err = Curl::getError();
			throw new \Exception( Utilities::getArrayValue( 'message', $err ),
								 Utilities::getArrayValue( 'code', $err, 500 ) );
		}

		// future support should be in post processing
//		if ( 0 !== strcasecmp( $this->_nativeFormat, $this->format ) )
//		{
//			// reformat the response here
//		}

		exit; // bail to avoid header error, unless we are reformatting the data
	}
}
