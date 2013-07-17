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
namespace Platform\Services;

use DreamFactory\Common\Exceptions\RestException;
use DreamFactory\Platform\Services\Authentication\OAuth\BaseClient;
use DreamFactory\Platform\Services\Authentication\OAuth\FacebookClient;
use DreamFactory\Platform\Services\Authentication\OAuth\GithubClient;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Exceptions\NotFoundException;
use Platform\Resources\UserSession;
use Platform\Utility\RestRequest;

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
				$paramAction = Option::get( $param, 'action' );
				if ( !empty( $paramAction ) && ( 0 !== strcasecmp( 'all', $paramAction ) ) )
				{
					if ( 0 !== strcasecmp( $action, $paramAction ) )
					{
						continue;
					}
				}
				$key = Option::get( $param, 'name' );
				$value = Option::get( $param, 'value' );
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
		$_config = $service->service_config_text;

		switch ( $service->service_tag_text )
		{
			case 'github':
				$_client = new GithubClient(
					array(
						 'client_id'     => Option::get( $_config, 'client_id' ),
						 'client_secret' => Option::get( $_config, 'client_secret' ),
					)
				);
				break;

			case 'twitter':
				$_client = new TwitterClient(
					array(
						 'client_id'     => Option::get( $_config, 'client_id' ),
						 'client_secret' => Option::get( $_config, 'client_secret' ),
					)
				);
				break;

			case 'facebook':
				$_client = new FacebookClient(
					array(
						 'client_id'     => Option::get( $_config, 'client_id' ),
						 'client_secret' => Option::get( $_config, 'client_secret' ),
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
	 * @param string $state
	 * @param array  $config
	 *
	 * @return string
	 * @throws \DreamFactory\Common\Exceptions\RestException
	 */
	protected function _registerAuthorization( $state, $config )
	{
		$_payload = array(
			'state'  => $state,
			'config' => json_encode( $config ),
		);

		$_endpoint = Pii::getParam( 'cloud.endpoint' ) . '/oauth/register';
		$_redirectUri = Pii::getParam( 'cloud.endpoint' ) . '/oauth/authorize';

		$_result = Curl::post( $_endpoint, $_payload );

		if ( false === $_result || !is_object( $_result ) )
		{
			throw new RestException( 'Error registering authorization request.', HttpResponse::InternalServerError );
		}

		if ( !$_result->success || !$_result->details )
		{
			throw new RestException( 'Error registering authorization request.', HttpResponse::InternalServerError );
		}

		return $_redirectUri;
	}

	/**
	 * @param string $state
	 * @param int    $registryId
	 *
	 * @return string
	 */
	protected function _checkPriorAuthorization( $state, $registryId )
	{
		//	See if there's an entry in the service auth table...
		$_model = \ServiceAuth::model()->find(
			'user_id = :user_id and registry_id = :registry_id',
			array(
				 ':user_id'     => UserSession::getCurrentUserId(),
				 ':registry_id' => $registryId,
			)
		);

		if ( empty( $_model ) )
		{
			Log::info( 'Registering auth request: ' . $state );

			$_endpoint = Pii::getParam( 'cloud.endpoint' ) . '/oauth/register?state=' . $state;
			$_result = Curl::get( $_endpoint );

			if ( false === $_result || !is_object( $_result ) )
			{
				Log::error( 'Error checking authorization request.', HttpResponse::InternalServerError );

				return false;
			}

			if ( !$_result->success || !$_result->details )
			{
				return false;
			}

			//	Looks like we have a token, save it!
			$_model = new \ServiceAuth();
			$_model->user_id = UserSession::getCurrentUserId();
			$_model->registry_id = $registryId;
			$_model->auth_text = $_result->details->token;
			$_model->save();

			$_token = $_result->details->token;
		}
		else
		{
			$_token = $_model->auth_text;
		}

		//	Looks like we have a token
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
		$_host = \Kisma::get( 'app.host_name' );

		//	Find service auth record
		$_service = $this->_validateService( $_serviceTag );

		$this->_client = $this->_createServiceClient( $_service );

		$_state = UserSession::getCurrentUserId() . '_' . $_serviceTag . '_' . $this->_client->getClientId();

		$_token = $this->_checkPriorAuthorization( $_state, $_service->id );

		if ( false !== $_token )
		{
			$this->_client->setAccessToken( $_token );
		}
		else
		{
			if ( !$this->_client->authorized( false ) )
			{
				$_config = array_merge(
					$_service->getAttributes(),
					array(
						 'host_name'              => $_host,
						 'client'                 => serialize( $this->_client ),
						 'resource'               => $this->_resourcePath,
						 'authorize_redirect_uri' => 'http://' . Option::server( 'HTTP_HOST', $_host ) . Option::server( 'REQUEST_URI', '/' ),
					)
				);

				$_redirectUri = $this->_registerAuthorization( $_state, $_config );
				$this->_client->setRedirectUri( $_redirectUri );
			}
		}

		if ( $this->_client->authorized( true, array( 'state' => $_state ) ) )
		{
			//	Recreate the request...
			$_params = $this->_resourceArray;

			//	Shift off the service name
			array_shift( $_params );
			$_path = '/' . implode( '/', $_params );

			$_queryString = $this->buildParameterString( $this->_action );

			$_response = $this->_client->fetch(
				$_path,
				RestRequest::getPostDataAsArray(),
				$this->_action,
				$this->_headers ? : array()
			);

			if ( false === $_response )
			{
				throw new InternalServerErrorException( 'Network error', $_response['code'] );
			}

			if ( false !== stripos( $_response['content_type'], 'application/json', 0 ) )
			{
				return json_decode( $_response['result'] );
			}

			return $_response['result'];
		}
	}
}
