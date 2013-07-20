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
use DreamFactory\Platform\Yii\Components\DrupalUserIdentity;
use DreamFactory\Platform\Yii\Components\PlatformUserIdentity;
use DreamFactory\Yii\Utility\Pii;

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'WebController'.
 */
class LoginForm extends CFormModel
{
	/**
	 * @var string
	 */
	public $username;
	/**
	 * @var string
	 */
	public $password;
	/**
	 * @var boolean
	 */
	public $rememberMe;
	/**
	 * @var PlatformUserIdentity
	 */
	protected $_identity;
	/**
	 * @var DrupalUserIdentity
	 */
	protected $_drupalIdentity;
	/**
	 * @var bool
	 */
	protected $_drupalAuth = true;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return array(
			array( 'username, password', 'required' ),
			array( 'rememberMe', 'boolean' ),
			array( 'password', 'authenticate' ),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'username'   => 'Email Address',
			'rememberMe' => 'Keep me logged in',
		);
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate( $attribute, $params )
	{
		if ( false !== $this->_drupalAuth )
		{
			return $this->authenticateDrupal( $attribute, $params );
		}

		if ( !$this->hasErrors() )
		{
			$this->_identity = new PlatformUserIdentity( $this->username, $this->password );

			if ( $this->_identity->authenticate() )
			{
				return true;
			}

			$this->addError( 'password', 'The email address and password pair do not match.' );
		}

		return false;
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticateDrupal( $attribute, $params )
	{
		if ( !$this->hasErrors() )
		{
			$this->_drupalIdentity = new DrupalUserIdentity( $this->username, $this->password );

			if ( $this->_drupalIdentity->authenticate() )
			{
				return true;
			}

			$this->addError( 'password', 'The email address and password pair do not match.' );
		}

		return false;
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 *
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		$_identity = ( $this->_drupalAuth ? $this->_drupalIdentity : $this->_identity );

		if ( empty( $_identity ) )
		{
			if ( $this->_drupalAuth )
			{
				$_identity = new DrupalUserIdentity( $this->username, $this->password );
			}
			else
			{
				$_identity = new PlatformUserIdentity( $this->username, $this->password );
				$_identity->setDrupalIdentity( $this->_drupalIdentity );
			}

			if ( !$_identity->authenticate() )
			{
				$_identity = null;

				return false;
			}
		}

		if ( \CBaseUserIdentity::ERROR_NONE == $_identity->errorCode )
		{
			if ( $this->_drupalAuth )
			{
				$this->_drupalIdentity = $_identity;
			}
			else
			{
				$this->_identity = $_identity;
				$_identity->setDrupalIdentity( $this->_drupalIdentity ?: $_identity );
			}

			$_duration = $this->rememberMe ? 3600 * 24 * 30 : 0;

			return Pii::user()->login( $_identity, $_duration );
		}

		return false;
	}

	/**
	 * @param DrupalUserIdentity $drupalIdentity
	 *
	 * @return LoginForm
	 */
	public function setDrupalIdentity( $drupalIdentity )
	{
		$this->_drupalIdentity = $drupalIdentity;

		return $this;
	}

	/**
	 * @return DrupalUserIdentity
	 */
	public function getDrupalIdentity()
	{
		return $this->_drupalIdentity;
	}

	/**
	 * @param PlatformUserIdentity $identity
	 *
	 * @return LoginForm
	 */
	public function setIdentity( $identity )
	{
		$this->_identity = $identity;

		return $this;
	}

	/**
	 * @return PlatformUserIdentity
	 */
	public function getIdentity()
	{
		return $this->_identity;
	}

	/**
	 * @param boolean $drupalAuth
	 *
	 * @return LoginForm
	 */
	public function setDrupalAuth( $drupalAuth )
	{
		$this->_drupalAuth = $drupalAuth;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getDrupalAuth()
	{
		return $this->_drupalAuth;
	}
}
