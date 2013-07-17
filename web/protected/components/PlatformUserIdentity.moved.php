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
use Kisma\Core\Utility\FilterInput;

/**
 * PlatformUserIdentity
 * Provides a password-based login against the database.
 */
class PlatformUserIdentity extends \CUserIdentity
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const Authenticated = 0;
	/**
	 * @var int
	 */
	const InvalidCredentials = 1;

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var int Our user id
	 */
	protected $_userId;
	/**
	 * @var DrupalUserIdentity
	 */
	protected $_drupalIdentity = null;
	/**
	 * @var \User
	 */
	protected $_user = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param \User $user
	 *
	 * @return bool
	 */
	public function logInUser( $user )
	{
		return $this->_initializeSession( $user );
	}

	/**
	 * @param \User $user
	 *
	 * @return bool
	 */
	protected function _initializeSession( $user )
	{
		if ( empty( $user ) )
		{
			return false;
		}

		//	Create entry in stat table...
		\Stat::create(
			Stat::TYPE_LOCAL_AUTH,
			$user->id,
			array_merge(
				isset( $_SESSION ) ? $_SESSION : array(),
				$user->getAttributes()
			)
		);

		$this->_user = $user;
		$this->_userId = $user->id;

		$this->setState( 'display_name', $user->display_name );
		$this->setState( 'email', $user->email );
		$this->setState( 'first_name', $user->first_name );
		$this->setState( 'last_name', $user->last_name );
		$this->setState( 'display_name', $user->display_name );
		$this->setState( 'password', $user->password );
		$this->setState( 'df_authenticated', false );

		$this->errorCode = static::Authenticated;

		return true;
	}

	/**
	 * Authenticates a user.
	 *
	 * @param DrupalUserIdentity $drupalIdentity
	 *
	 * @return boolean
	 */
	public function authenticate( $drupalIdentity = null )
	{
		$_user = null;
		$this->_drupalIdentity = $drupalIdentity;

		if ( false === ( $_user = \User::authenticate( $this->username, $this->password ) ) )
		{
			$this->errorCode = static::InvalidCredentials;

			return false;
		}

		return $this->_initializeSession( $_user );
	}

	/**
	 * @return \User
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * Returns the user's ID instead of the name
	 *
	 * @return int|string
	 */
	public function getId()
	{
		return $this->_userId;
	}

	/**
	 * @param int $userId
	 *
	 * @return PlatformUserIdentity
	 */
	public function setUserId( $userId )
	{
		$this->_userId = $userId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->_userId;
	}

	/**
	 * @param \DrupalUserIdentity $drupalIdentity
	 *
	 * @return PlatformUserIdentity
	 */
	public function setDrupalIdentity( $drupalIdentity )
	{
		$this->_drupalIdentity = $drupalIdentity;

		return $this;
	}

	/**
	 * @return \DrupalUserIdentity
	 */
	public function getDrupalIdentity()
	{
		return $this->_drupalIdentity;
	}
}
