<?php
namespace DreamFactory\Yii\Components;

use DreamFactory\Yii\Utility\Pii;

/**
 * SimpleUserIdentity
 * Provides a password-based login. The allowed users are retrieved from the configuration file in the 'params' section.
 * The array data should be in 'UserName' => 'password' format.
 */
class SimpleUserIdentity extends \CUserIdentity
{
	//*************************************************************************
	//* Public Methods
	//*************************************************************************

	/**
	 * Authenticates a user.
	 *
	 * @return boolean
	 */
	public function authenticate()
	{
		return ( self::ERROR_NONE === ( $this->errorCode = self::_authenticate( $this->username, $this->password ) ) );
	}

	//*************************************************************************
	//* Private Methods
	//*************************************************************************

	/**
	 * @param string $userName
	 * @param string $password
	 *
	 * @return int
	 */
	protected static function _authenticate( $userName, $password )
	{
		$_checkUser = trim( strtolower( $userName ) );
		$_checkPassword = trim( $password );

		$_allowedUsers = Pii::getParam( 'auth.allowedUsers', array() );

		if ( !isset( $_allowedUsers[$_checkUser] ) )
		{
			return self::ERROR_USERNAME_INVALID;
		}

		if ( $_allowedUsers[$_checkUser] !== $_checkPassword && $_allowedUsers[$_checkUser] !== md5( $_checkPassword ) )
		{
			return self::ERROR_PASSWORD_INVALID;
		}

		return self::ERROR_NONE;
	}
}