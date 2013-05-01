<?php
use Kisma\Core\Utility\Option;

/**
 * DrupalUserIdentity
 * Provides Drupal authentication services
 */
class DrupalUserIdentity extends \CUserIdentity
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
	protected $_drupalId;
	/**
	 * @var \User
	 */
	protected $_user = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Authenticates a user.
	 *
	 * @return boolean
	 */
	public function authenticate()
	{
		if ( false === ( $_user = Drupal::authenticateUser( $this->username, $this->password ) ) )
		{
			$this->errorCode = self::ERROR_USERNAME_INVALID;

			return false;
		}

		if ( !isset( $_user->drupal_id ) )
		{
			Log::warning( 'Uncommon response from Drupal::authenticateUser(): ' . print_r( $_user, true ) );
		}

		$this->_user = $_user;
		$this->_drupalId = Option::get( $_user, 'drupal_id' );

		$this->setState( 'email', $this->username );
		$this->setState( 'first_name', Option::get( $_user, 'first_name', $this->username ) );
		$this->setState( 'last_name', Option::get( $_user, 'last_name', $this->username ) );
		$this->setState( 'display_name', Option::get( $_user, 'display_name', $this->username ) );
		$this->setState( 'password', $this->password );
		$this->setState( 'df_authenticated', true );

		$this->errorCode = self::ERROR_NONE;

		Log::debug( 'Drupal user auth: ' . $this->username );

		return true;
	}

	/**
	 * Returns the user's ID instead of the name
	 *
	 * @return int|string
	 */
	public function getId()
	{
		return $this->_drupalId;
	}

	/**
	 * @param int $drupalId
	 *
	 * @return DrupalUserIdentity
	 */
	public function setUserId( $drupalId )
	{
		$this->_drupalId = $drupalId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->_drupalId;
	}

	/**
	 * @return int
	 */
	public function getDrupalId()
	{
		return $this->_drupalId;
	}

	/**
	 * @return \User
	 */
	public function getUser()
	{
		return $this->_user;
	}
}
