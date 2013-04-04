<?php

/**
 * RemoteWebSvc.php
 * A service to handle remote web services accessed through the REST API.
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2009-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class RemoteWebSvc extends BaseService
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

	// Controller based methods

	/**
	 * @throws Exception
	 */
	public function actionGet()
	{
		$this->checkPermission( 'read' );

		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$param_str = $this->buildParameterString( Curl::Get );
		$url = $this->_baseUrl . $path . '?' . $param_str;

		$co = array();
		$co[CURLOPT_RETURNTRANSFER] = false; // return results directly to browser
		$co[CURLOPT_HEADER] = false; // don't include headers in payload

		// set additional headers
		$co = $this->addHeaders( Curl::Get, $co );

		Utilities::markTimeStart( 'WS_TIME' );
		if ( false === Curl::get( $url, array(), $co ) )
		{
			$err = Curl::getError();
			throw new Exception( Utilities::getArrayValue( 'message', $err ),
								 Utilities::getArrayValue( 'code', $err, 500 ) );
		}
		Utilities::markTimeStop( 'WS_TIME' );
		Utilities::markTimeStop( 'API_TIME' );
//      Utilities::logTimers();

		exit; // bail to avoid header error, unless we are reformatting the data
}

	/**
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->checkPermission( 'create' );

		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$param_str = $this->buildParameterString( 'POST' );
		$url = $this->_baseUrl . $path . '?' . $param_str;

		$co = array();
		$co[CURLOPT_RETURNTRANSFER] = false; // return results directly to browser
		$co[CURLOPT_HEADER] = false; // don't include headers in payload

		// set additional headers
		$co = $this->addHeaders( Curl::Post, $co );

		Utilities::markTimeStart( 'WS_TIME' );
		if ( false === Curl::post( $url, array(), $co ) )
		{
			$err = Curl::getError();
			throw new Exception( Utilities::getArrayValue( 'message', $err ),
								 Utilities::getArrayValue( 'code', $err, 500 ) );
		}
		Utilities::markTimeStop( 'WS_TIME' );

		Utilities::markTimeStop( 'API_TIME' );
//      Utilities::logTimers();

		exit; // bail to avoid header error, unless we are reformatting the data
}

	/**
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->checkPermission( 'update' );

		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$param_str = $this->buildParameterString( 'PUT' );
		$url = $this->_baseUrl . $path . '?' . $param_str;

		$co = array();
		$co[CURLOPT_RETURNTRANSFER] = false; // return results directly to browser
		$co[CURLOPT_HEADER] = false; // don't include headers in payload

		// set additional headers
		$co = $this->addHeaders( Curl::Put, $co );

		Utilities::markTimeStart( 'WS_TIME' );
		if ( false === Curl::put( $url, array(), $co ) )
		{
			$err = Curl::getError();
			throw new Exception( Utilities::getArrayValue( 'message', $err ),
								 Utilities::getArrayValue( 'code', $err, 500 ) );
		}
		Utilities::markTimeStop( 'WS_TIME' );
		Utilities::markTimeStop( 'API_TIME' );
//      Utilities::logTimers();

		exit; // bail to avoid header error, unless we are reformatting the data
}

	/**
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->checkPermission( 'update' );

		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$param_str = $this->buildParameterString( 'PATCH' );
		$url = $this->_baseUrl . $path . '?' . $param_str;

		$co = array();
		$co[CURLOPT_RETURNTRANSFER] = false; // return results directly to browser
		$co[CURLOPT_HEADER] = false; // don't include headers in payload

		// set additional headers
		$co = $this->addHeaders( Curl::Merge, $co );

		Utilities::markTimeStart( 'WS_TIME' );
		if ( false === Curl::merge( $url, array(), $co ) )
		{
			$err = Curl::getError();
			throw new Exception( Utilities::getArrayValue( 'message', $err ),
								 Utilities::getArrayValue( 'code', $err, 500 ) );
		}
		Utilities::markTimeStop( 'WS_TIME' );
		Utilities::markTimeStop( 'API_TIME' );
//      Utilities::logTimers();

		exit; // bail to avoid header error, unless we are reformatting the data
}

	/**
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->checkPermission( 'delete' );

		$path = Utilities::getArrayValue( 'resource', $_GET, '' );
		$param_str = $this->buildParameterString( 'DELETE' );
		$url = $this->_baseUrl . $path . '?' . $param_str;

		$co = array();
		$co[CURLOPT_RETURNTRANSFER] = false; // return results directly to browser
		$co[CURLOPT_HEADER] = false; // don't include headers in payload

		// set additional headers
		$co = $this->addHeaders( Curl::Delete, $co );

		Utilities::markTimeStart( 'WS_TIME' );
		if ( false === Curl::delete( $url, array(), $co ) )
		{
			$err = Curl::getError();
			throw new Exception( Utilities::getArrayValue( 'message', $err ),
								 Utilities::getArrayValue( 'code', $err, 500 ) );
		}
		Utilities::markTimeStop( 'WS_TIME' );
		Utilities::markTimeStop( 'API_TIME' );
//      Utilities::logTimers();

		exit; // bail to avoid header error, unless we are reformatting the data
	}

}
