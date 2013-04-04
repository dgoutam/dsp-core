<?php
/**
 * SimpleLoginForm.php
 */
namespace DreamFactory\Yii\Models\Forms;

use \DreamFactory\Yii\Components\SimpleUserIdentity;
use DreamFactory\Yii\Utility\Pii;

/**
 * SimpleLoginForm
 * Provides a standard simple login form
 */
class SimpleLoginForm extends \CFormModel
{
	//********************************************************************************
	//* Public Members
	//********************************************************************************

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

	//********************************************************************************
	//* Private Members
	//********************************************************************************

	/**
	 * @var SimpleUserIdentity Our user identity
	 */
	protected $_identity;

	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 *
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'username, password', 'required' ),
			array( 'rememberMe', 'boolean' ),
			array( 'password', 'authenticate', 'skipOnError' => true ),
		);
	}

	/**
	 * Declares attribute labels.
	 *
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'username'   => 'Email Address',
			'password'   => 'Password',
			'rememberMe' => 'Remember Me',
		);
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 *
	 * @param string $attribute
	 * @param array  $params
	 *
	 * @return void
	 */
	public function authenticate( $attribute, $params )
	{
		$this->_identity = new SimpleUserIdentity( $this->username, $this->password );

		if ( !$this->_identity->authenticate() )
		{
			$this->addError( 'password', 'Incorrect username or password.' );
		}
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 *
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		if ( null === $this->_identity )
		{
			$this->_identity = new SimpleUserIdentity( $this->username, $this->password );
			$this->_identity->authenticate();
		}

		if ( SimpleUserIdentity::ERROR_NONE === $this->_identity->errorCode )
		{
			Pii::user()->login( $this->_identity );

			return true;
		}

		return false;
	}

	/**
	 * @param string $password
	 *
	 * @return \SimpleLoginForm
	 */
	public function setPassword( $password )
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param boolean $rememberMe
	 *
	 * @return \SimpleLoginForm
	 */
	public function setRememberMe( $rememberMe )
	{
		$this->rememberMe = $rememberMe;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getRememberMe()
	{
		return $this->rememberMe;
	}

	/**
	 * @param string $username
	 *
	 * @return \SimpleLoginForm
	 */
	public function setUsername( $username )
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * @return SimpleUserIdentity
	 */
	public function getIdentity()
	{
		return $this->_identity;
	}

	/**
	 * @param SimpleUserIdentity $identity
	 *
	 * @return SimpleLoginForm
	 */
	protected function _setIdentity( $identity )
	{
		$this->_identity = $identity;

		return $this;
	}
}
