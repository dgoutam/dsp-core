<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
use Platform\Utility\Curl;
/**
 * Drupal
 * Drupal authentication
 */
class Drupal
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const Endpoint = 'http://cerberus.fabric.dreamfactory.com/api/drupal';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string $url
	 * @param array  $payload
	 * @param array  $options
	 * @param string $method
	 *
	 * @return \stdClass|string
	 */
	protected static function _drupal( $url, array $payload = array(), array $options = array(), $method = Curl::Post )
	{
		$_url = '/' . ltrim( $url, '/' );

		if ( empty( $options ) )
		{
			$options = array();
		}

		if ( !isset( $options[CURLOPT_HTTPHEADER] ) )
		{
			$options[CURLOPT_HTTPHEADER] = array( 'Content-Type: application/json' );
		}
		else
		{
			$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
		}

		//	Add in a source block
		$payload['source'] = array(
			'host'    => gethostname(),
			'address' => gethostbynamel( gethostname() ),
		);

		$payload['dsp-auth-key'] = md5( microtime( true ) );

//		$payload = json_encode( $payload );

		return Curl::request( $method, static::Endpoint . $_url, json_encode( $payload ), $options );
	}

	/**
	 * @param string $userName
	 * @param string $password
	 *
	 * @return bool
	 */
	public static function authenticateUser( $userName, $password )
	{
		$_payload = array(
			'email'    => $userName,
			'password' => $password,
		);

		if ( false !== ( $_response = static::_drupal( 'drupalValidate', $_payload ) ) )
		{
			if ( $_response->success )
			{
				return $_response->details;
			}
		}

		return false;
	}

	/**
	 * @param int $userId
	 *
	 * @return stdClass|string
	 */
	public static function getUser( $userId )
	{
		$_payload = array(
			'id' => $userId,
		);

		$_response = static::_drupal( 'drupalUser', $_payload );

		return $_response->details;
	}
}
