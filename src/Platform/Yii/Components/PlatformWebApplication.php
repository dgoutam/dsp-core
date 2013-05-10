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
namespace Platform\Yii\Components;

use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;
use Platform\Yii\Utility\Pii;

/**
 * PlatformWebApplication
 */
class PlatformWebApplication extends \CWebApplication
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string The allowed HTTP methods
	 */
	const CORS_ALLOWED_METHODS = 'GET, POST, PUT, DELETE, PATCH, MERGE, COPY, OPTIONS';
	/**
	 * @var string The allowed HTTP headers
	 */
	const CORS_ALLOWED_HEADERS = 'x-requested-with';
	/**
	 * @var int The default number of seconds to allow this to be cached. Default is 15 minutes.
	 */
	const CORS_DEFAULT_MAX_AGE = 900;

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array An indexed array of white-listed host URIs
	 */
	protected $_corsWhitelist = array();
	/**
	 * @var bool    If true, the CORS headers will be sent automatically before dispatching the action.
	 *              NOTE: "OPTIONS" calls will always get headers, regardless of the setting. All other requests respect the setting.
	 */
	protected $_autoAddHeaders = true;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Initialize
	 */
	protected function init()
	{
		parent::init();

		Pii::app()->onBeginRequest = array($this, 'checkRequestMethod');
	}

	/**
	 * Handles an OPTIONS request to the server to allow CORS and optionally sends the CORS headers
	 */
	public function checkRequestMethod()
	{
		//	Answer an options call...
		if ( HttpMethod::Options == FilterInput::server( 'REQUEST_METHOD' ) )
		{
			header( 'HTTP/1.1 204' );
			header( 'content-length: 0' );
			header( 'content-type: text/plain' );

			$this->addCorsHeaders( array('*') );

			Pii::end();
		}

		//	Auto-add the CORS headers...
		if ( $this->_autoAddHeaders )
		{
			$this->addCorsHeaders();
		}
	}

	/**
	 * @param array|bool $whitelist Set to "false" to reset the internal method cache.
	 */
	public function addCorsHeaders( $whitelist = array() )
	{
		static $_cache = array();

		//	Reset the cache before processing...
		if ( false === $whitelist )
		{
			$_cache = null;

			return;
		}

		$_header = 'X-DreamFactory-Unclean: 0';

		if ( '*' !== ( $_origin = FilterInput::server( 'HTTP_ORIGIN' ) ? : '*' ) )
		{
			$_origin = parse_url( $_origin, PHP_URL_HOST );
		}

		//	Not checked yet, look in the whitelist...
		if ( !in_array( $_origin, $_cache ) )
		{
			$whitelist = array_merge( $this->_corsWhitelist, $whitelist );

			$_safe = false;

			foreach ( $whitelist as $_source )
			{
				if ( $_source == $_origin || parse_url( $_source, PHP_URL_HOST ) == $_origin )
				{
					$_safe = true;
					break;
				}
			}

			if ( false === $_safe )
			{
				$_header = 'X-DreamFactory-Unclean: 1';
				//throw new \Exception( 'You are not authorized to retrieve this information', HttpResponse::Forbidden );
			}

			$_cache[] = $_origin;
		}

		header( 'Access-Control-Allow-Origin: ' . $_origin );
		header( 'Access-Control-Allow-Methods: ' . static::CORS_ALLOWED_METHODS );
		header( 'Access-Control-Allow-Headers: ' . static::CORS_ALLOWED_HEADERS );
		header( 'Access-Control-Max-Age: ' . static::CORS_DEFAULT_MAX_AGE );
		header( $_header );
	}

	/**
	 * @param array $corsWhitelist
	 *
	 * @return PlatformWebApplication
	 */
	public function setCorsWhitelist( $corsWhitelist )
	{
		$this->_corsWhitelist = $corsWhitelist;

		//	Reset the header cache
		$this->addCorsHeaders( false );

		return $this;
	}

	/**
	 * @return array
	 */
	public function getCorsWhitelist()
	{
		return $this->_corsWhitelist;
	}

	/**
	 * @param boolean $autoAddHeaders
	 *
	 * @return PlatformWebApplication
	 */
	public function setAutoAddHeaders( $autoAddHeaders )
	{
		$this->_autoAddHeaders = $autoAddHeaders;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getAutoAddHeaders()
	{
		return $this->_autoAddHeaders;
	}
}