<?php
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

		$this->errorCode = static::Authenticated;

		//	Create entry in stat table...
		\Stat::create(
			Stat::TYPE_LOCAL_AUTH,
			$_user->id,
			array_merge(
				isset( $_SESSION ) ? $_SESSION : array(),
				$_user->getAttributes()
			)
		);

		$this->_user = $_user;
		$this->_userId = $_user->id;
		$this->setState( 'display_name', $_user->display_name );
		$this->setState( 'email', $_user->email );
		$this->setState( 'first_name', $_user->first_name );
		$this->setState( 'last_name', $_user->last_name );
		$this->setState( 'display_name', $_user->display_name );
		$this->setState( 'password', $_user->password );
		$this->setState( 'df_authenticated', false );

		return true;
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
