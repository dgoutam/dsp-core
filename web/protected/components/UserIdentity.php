<?php
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * UserIdentity.php
 * A model of a user identity. Contains the authentication method that checks if the provided
 * data can identify the user.
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
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
class UserIdentity extends CUserIdentity
{
	private $_id;

	/**
	 * @return bool
	 */
	public function authenticate()
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
			$this->errorCode = self::ERROR_NONE;
		}

		/* not currently used
		$record = User::model()->findByAttributes(array('username'=>$this->username));
		if ($record === null)
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		else if (!CPasswordHelper::verifyPassword($this->password, $record->password))
			$this->errorCode = self::ERROR_PASSWORD_INVALID;
		else
		{
			$this->_id = $record->id;
			$this->setState('email', $record->email);
			$this->errorCode = self::ERROR_NONE;
		}
		*/

		return !$this->errorCode;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}
}