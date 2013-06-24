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

use DreamFactory\Platform\Services\Authentication\OAuth\BaseClient;
use DreamFactory\Platform\Services\Authentication\OAuth\GithubClient;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Exceptions\NotFoundException;
use Platform\Resources\UserSession;
use Platform\Utility\Curl;
use Platform\Utility\RestRequest;
use Platform\Utility\Utilities;

/**
 * OAuthService
 * A service to handle remote web services accessed with OAuth through the REST API.
 */
class OAuthService extends RestService
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
	/**
	 * @var BaseClient
	 */
	protected $_client;

	//*************************************************************************
	//* Methods
	//*************************************************************************

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
	 * @param string $serviceTag
	 *
	 * @return \Registry
	 * @throws \Platform\Exceptions\NotFoundException
	 */
	protected function _validateService( $serviceTag )
	{
		$_service = \Registry::model()->userTag( UserSession::getCurrentUserId(), $serviceTag )->find();

		if ( empty( $_service ) )
		{
			throw new NotFoundException( 'The service "' . $serviceTag . '" was not found.' );
		}

		return $_service;
	}

	/**
	 * @param \Registry $service
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return BaseClient
	 */
	protected function _createServiceClient( $service )
	{

		switch ( $service->service_tag_text )
		{
			case 'github':
				$_config = $service->service_config_text;

				$_client = new GithubClient(
					array(
						 'client_id'     => Option::get( $_config, 'client_id' ),
						 'client_secret' => Option::get( $_config, 'client_secret' ),
						 //						 'redirect_proxy_url' => 'http://api.cloud.dreamfactory.com/oauth/redirect',
					)
				);
				break;

			default:
				throw new BadRequestException( 'The service "' . $service->service_tag_text . '" was not found.' );
		}

		return $_client;
	}

	/**
	 * @param \Registry $service
	 *
	 * @return \ServiceAuth
	 */
	protected function _getAuthorization( $service )
	{
		$_token = \ServiceAuth::model()->find(
			'registry_id = :registry_id and user_id = :user_id',
			array(
				 ':registry_id' => $service->id,
				 ':user_id'     => UserSession::getCurrentUserId()
			)
		);

		return $_token;
	}

	/**
	 * Handle an OAuth service request
	 *
	 * Comes in like this:
	 *
	 *                Resource        Action
	 * /rest/service/{service_name}/{service request string}
	 *
	 *
	 * @return bool
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Exception
	 */
	protected function _handleResource()
	{
		$_serviceTag = $this->_resource;

		//	Find service auth record
		$_service = $this->_validateService( $_serviceTag );

		$this->_client = $this->_createServiceClient( $_service );

		$_state = UserSession::getCurrentUserId() . '_' . $_serviceTag . '_' . $this->_client->getClientId();

		$_host = \Kisma::get( 'app.host_name' );

		$_host = 'jablan.is-a-geek.com';

		$this->_client->setRedirectUri( 'http://' . $_host . '/' . $this->_resourcePath );

		if ( $this->_client->authorized( true, array( 'state' => $_state ) ) )
		{
			//	Recreate the request...
			$_queryString = $this->buildParameterString( $this->_action );

			$_response = $this->_client->fetch(
				$this->_resourcePath . '?' . $_queryString,
				RestRequest::getPostDataAsArray(),
				$this->_action,
				$this->_headers
			);

			if ( false === $_response )
			{
				throw new InternalServerErrorException( 'Network error', $_response['code'] );
			}

			return $_response;
		}
	}
}
