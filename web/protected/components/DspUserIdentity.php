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
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * DspUserIdentity.php
 * A model of a DSP user identity. Contains the authentication method that checks if the provided
 * data can identify the user.
 */
class DspUserIdentity extends \CUserIdentity
{
	private $_id;

	private $_user = null;

	public $df_authenticate = false;

	/**
	 * Constructor.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @param bool   $df_authenticate
	 */
	public function __construct( $username, $password, $df_authenticate = false )
	{
		parent::__construct( $username, $password );

		$this->df_authenticate = $df_authenticate;
	}

	/**
	 * @return bool
	 */
	public function authenticate()
	{
		if ( $this->df_authenticate )
		{
			$record = Drupal::authenticateUser( $this->username, $this->password );

			if ( false === $record )
			{
				$this->errorCode = self::ERROR_USERNAME_INVALID;
			}
			else
			{
				if ( !isset( $record->drupal_id ) )
				{
					Log::warning( 'suspicious post to auth: ' . print_r( $record, true ) );
				}

				$this->_id = Option::get( $record, 'drupal_id' );
				$this->setState( 'email', $this->username );
				$this->setState( 'first_name', Option::get( $record, 'first_name', $this->username ) );
				$this->setState( 'last_name', Option::get( $record, 'last_name', $this->username ) );
				$this->setState( 'display_name', Option::get( $record, 'display_name', $this->username ) );
				$this->setState( 'password', $this->password );
				$this->setState( 'df_authenticated', true );
				$this->errorCode = self::ERROR_NONE;
			}

			return !$this->errorCode;
		}

		$this->_user = User::model()
					   ->with( 'role.role_service_accesses', 'role.apps', 'role.services' )
					   ->findByAttributes( array( 'email' => $this->username ) );
		if ( $this->_user === null )
		{
			$this->errorCode = static::ERROR_USERNAME_INVALID;
		}
		else if ( !CPasswordHelper::verifyPassword( $this->password, $this->_user->password ) )
		{
			$this->errorCode = static::ERROR_PASSWORD_INVALID;
		}
		else
		{
			$this->_id = $this->_user->id;
			$this->setState( 'display_name', $this->_user->display_name );
			$this->errorCode = static::ERROR_NONE;

			//	Create entry in stat table...
			Stat::create(
				Stat::TYPE_LOCAL_AUTH,
				$this->_user->id,
				isset( $_SESSION ) ? $_SESSION : $this->_user->getAttributes()
			);
		}

		return !$this->errorCode;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}

	public function getUser()
	{
		return $this->_user;
	}
}