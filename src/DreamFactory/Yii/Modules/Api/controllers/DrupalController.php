<?PHP
/**
 * no namespace, Yii don't like 'em in controllers...
 * namespace Cerberus\Yii\Modules\Api\Controllers;
 */
use Cerberus\Yii\Controllers\AuthResourceController;
use Cerberus\Yii\Models\Auth\User;
use Cerberus\Yii\Models\BaseFabricAuthModel;
use Cerberus\Yii\Models\Deploy\Instance;
use DreamFactory\Yii\Controllers\DreamRestController;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Enums\OutputFormat;
use Kisma\Core\Exceptions\InvalidRequestException;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;

/**
 * DrupalController.php
 * Special API for Drupal to sync users
 *
 * @link       http:// www.dreamfactory.com DreamFactory Software, Inc.
 * @author     Jerry Ablan <jerryablan@dreamfactory.com>
 * @filesource
 */
class DrupalController extends AuthResourceController
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_instanceName;
	/**
	 * @var stdClass
	 */
	protected $_drupalUser = null;

	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Initialize the controller
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return void
	 */
	public function init()
	{
		$this->_resourceClass = '\\Cerberus\\Yii\\Models\\Auth\\User';

		parent::init();

		$this->addUserActions(
			static::Any,
			array(
				 'user',
				 'login',
				 'available',
				 'launch',
				 'provision',
				 'destroy',
				 'inTrial',
				 'trialInstance',
				 'validate',
				 'instances',
				 'drupalValidate',
				 'drupalUser',
				 'status',
			)
		);

		if ( Pii::postRequest() && false === stripos( file_get_contents( 'php://input' ), 'dsp-auth-key' ) )
		{
			list( $this->_resourceUser, $this->_drupalUser ) = $this->_validateUser( $_POST );

			if ( empty( $this->_resourceUser ) )
			{
				if ( !$this->postLogin() )
				{
					//	If that didn't work, we're boned.
					throw new RestException( HttpResponse::Forbidden );
				}
			}

			if ( empty( $this->_drupalUser ) )
			{
				//	No anonymous access
				throw new RestException( HttpResponse::Forbidden );
			}

			//$_alternateName = Instance::sanitizeInstanceName( Inflector::neutralize( $this->_resourceUser->display_name_text ) );
			$this->_instanceName = FilterInput::post( 'name' );
		}

		$this->setSingleParameterActions( true );
	}

	/**
	 * Validates an inbound user from a DSP against the Drupal user base
	 *
	 * @param array|\stdClass $payload
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return array
	 */
	public function postDrupalValidate( $payload )
	{
		Log::debug( print_r( $payload, true ) );
		$_email = FilterInput::get( $payload, 'email' );
		$_password = FilterInput::get( $payload, 'password' );

		/** @var $_user User */
		if ( null === ( $_user = User::model()->find( 'email_addr_text = :email_addr_text', array( ':email_addr_text' => $_email ) ) ) )
		{
			Log::error( 'Drupal Validation Failure for user: ' . $_email );
			throw new RestException( HttpResponse::NotFound, 'User "' . $_email . '" not found.' );
		}

		if ( $_user->validateDrupalPassword( $_password ) )
		{
			Log::info( 'POST drupalValidate "success": ' . print_r( $payload, true ) );

			return array(
				'success'      => 'true',
				'drupal_id'    => $_user->drupal_id,
				'first_name'   => $_user->first_name_text,
				'last_name'    => $_user->last_name_text,
				'display_name' => $_user->display_name_text,
			);
		}

		return array(
			'success'   => 'false',
			'drupal_id' => null,
		);
	}

	/**
	 * @param array|\stdClass $payload
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return array
	 */
	public function postDrupalUser( $payload )
	{
		Log::debug( 'user: ' . print_r( $payload, true ) );
		$_id = FilterInput::get( $payload, 'id' );

		/** @var $_user User */
		if ( null === ( $_user = User::model()->find( 'drupal_id = :drupal_id', array( ':drupal_id' => $_id ) ) ) )
		{
			Log::error( 'Drupal lookup failure for user ID: ' . $_id );
			throw new RestException( HttpResponse::NotFound );
		}

		$_response = $_user->getAttributes();

		foreach ( $_response as $_key => $_value )
		{
			if ( false !== stripos( $_key, 'password' ) || false !== stripos( $_key, '_key' ) )
			{
				unset( $_response[$_key] );
			}
		}

		$_response['success'] = true;

		return $_response;
	}

	/**
	 * @return array
	 * @throws InvalidRequestException
	 */
	public function postUser()
	{
		$_token = array(
			'id'        => $this->_resourceUser->email_addr_text,
			'token'     => $this->_resourceUser->api_token_text,
			'instances' => $this->postInstances(),
		);

		Log::info(
			'Token "' . $this->_resourceUser->api_token_text . '" issued to user "' . $this->_resourceUser->email_addr_text . '" (' . $this->_resourceUser->id .
				')'
		);

		$this->_drupalSync( $this->_drupalUser, $this->_resourceUser );

		//	Return the goods
		return $_token;
	}

	/**
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 * @return bool
	 * @todo This method is far too long. refactor
	 */
	public function postLogin()
	{
		$_password = null;

		if ( empty( $this->_drupalUser ) && empty( $this->_resourceUser ) )
		{
			throw new RestException( HttpResponse::NotFound );
		}

		//	New dude, make a row
		if ( null === $this->_resourceUser )
		{
			Log::debug( 'New inbound user from drupal: ' . print_r( $this->_drupalUser, true ) );

			$this->_resourceUser = new User();
			$this->_resourceUser->create_date = date( 'Y-m-d H:i:s', $this->_drupalUser ? $this->_drupalUser->created : time() );
			$this->_resourceUser->password_text
				= Hasher::generateUnique(
				$this->_drupalUser ? $this->_drupalUser->pass : FilterInput::get( $_POST, 'access_token' )
			);

			//	Check uniqueness of dsp name
			if ( !empty( $this->_drupalUser ) )
			{
				if ( 0 != User::model()->count( 'display_name_text = :name', array( ':name' => $this->_drupalUser->name ) ) )
				{
					$this->_resourceUser->display_name_text = $this->_drupalUser->mail;
				}
				else
				{
					$this->_resourceUser->display_name_text = $this->_drupalUser->name;
				}
			}
		}

		if ( empty( $this->_resourceUser->email_addr_text ) && !empty( $this->_drupalUser ) )
		{
			$this->_resourceUser->email_addr_text = $this->_drupalUser->mail;
		}

		$this->_resourceUser->drupal_id = $this->_drupalUser->uid;
		$this->_resourceUser->first_name_text = Option::get( $this->_drupalUser, 'field_first_name', $this->_drupalUser->mail );
		$this->_resourceUser->last_name_text = Option::get( $this->_drupalUser, 'field_last_name', $this->_drupalUser->mail );
		$this->_resourceUser->company_name_text = Option::get( $this->_drupalUser, 'field_company_name' );
		$this->_resourceUser->city_text = Option::get( $this->_drupalUser, 'field_city' );
		$this->_resourceUser->state_province_text = Option::get( $this->_drupalUser, 'field_state_province' );
		$this->_resourceUser->postal_code_text = Option::get( $this->_drupalUser, 'field_zip_postal_code' );
		$this->_resourceUser->country_text = Option::get( $this->_drupalUser, 'field_country' );
		$this->_resourceUser->phone_text = Option::get( $this->_drupalUser, 'field_phone_number' );
		$this->_resourceUser->title_text = Option::get( $this->_drupalUser, 'field_title' );
		$this->_resourceUser->api_token_text = Hasher::generateUnique( $this->_resourceUser->password_text );

		try
		{
			Log::info( 'Creating new Drupal sync user: ' . $this->_drupalUser->mail );

			return $this->_resourceUser->save();
		}
		catch ( \CDbException $_ex )
		{
			Log::error( 'Exception while saving new api user: ' . $_ex->getMessage() );
		}

		//	Log user in...
		if ( empty( $this->_resourceUser ) )
		{
			throw new RestException( HttpResponse::InternalServerError, 'Unable to synchronize.' );
		}

		return true;
	}

	/**
	 *
	 */
	public function postLogout()
	{
		$this->_drupalUser = $this->_resourceUser = null;

		return true;
	}

	/**
	 * Returns 0 (not-available) or 1 (available) if an email address or display name is in use.
	 *
	 * @return string
	 */
	public function requestAvailable()
	{
		$this->layout = false;

		$_type = FilterInput::request( 'type' );
		$_value = FilterInput::request( 'value' );

		$_column = null;

		switch ( $_type )
		{
			case 0:
				$_column = 'email_addr_text';
				break;

			case 1:
				$_column = 'display_name_text';
				break;

			default:
				return 0;
		}

		//	Validate user input and redirect to the previous page if invalid
		$_count = User::model()->count( $_column . ' = :' . $_column, array( ':' . $_column => $_value ) );

		if ( null !== $_column && 0 == $_count )
		{
			//	Available!
			return 1;
		}

		//	Not available!
		return 0;
	}

	/**
	 * Launch a trial instance
	 *
	 * @throws \Kisma\Core\Exceptions\InvalidRequestException
	 * @return bool
	 */
	public function postLaunch()
	{
		//	Launch!
		return $this->postProvision();
	}

	/**
	 * Launch a trial instance
	 *
	 *
	 * @throws \CDbException|\Exception|DreamFactory\Yii\Exceptions\RestException
	 * @return bool
	 */
	public function postProvision()
	{
		//	Launch!
		try
		{
			return Instance::launch( $this->_resourceUser, $this->_instanceName );
		}
		catch ( \CDbException $_ex )
		{
			if ( false !== stripos( $_ex->getMessage(), '1062 duplicate entry' ) )
			{
				//	Unavailable instance name
				throw new RestException( HttpResponse::Conflict, 'The instance name "' . $this->_instanceName . '" is not available.' );
			}
			throw $_ex;
		}
	}

	/**
	 * Launch a trial instance
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function postDestroy( $name = null )
	{
		//	kill it
		return Instance::deprovision( $this->_resourceUser, $this->_instanceName );
	}

	/**
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 * @return bool
	 */
	public function postInTrial()
	{
		return $this->_resourceUser->inTrial();
	}

	/**
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 * @return bool
	 */
	public function requestTrialInstance()
	{
		return $this->_resourceUser->getTrialInstance();
	}

	/**
	 *
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 * @return array
	 */
	public function postInstance()
	{
		$_name = FilterInput::post( 'name' );
		$_id = FilterInput::post( 'id' );

		if ( empty( $_name ) && empty( $_id ) )
		{
			throw new RestException( HttpResponse::NotFound );
		}

		/** @var $_instance Instance */
		$_instance = Instance::model()
			->userOwned( $this->_resourceUser->id )
			->byNameOrId( $_name, $_id )
			->find();

		if ( empty( $_instance ) )
		{
			return false;
		}

		return $_instance->getRestAttributes();
	}

	/**
	 * @return array
	 */
	public function postInstances()
	{
		Log::debug( '/instances requested' );

		$_response = array();

		if ( $this->_resourceUser->instances )
		{
			foreach ( $this->_resourceUser->instances as $_instance )
			{
				if ( !empty( $_instance->instance_name_text ) )
				{
					$_response[$_instance->instance_name_text] = $_instance->getRestAttributes();
				}

				unset( $_instance );
			}
		}

		return $_response;
	}

	/**
	 * Retrieve the status of an instance
	 */
	public function requestStatus( $payload )
	{
		$_id = array_shift( $payload );
		Log::debug( 'status -- ID: ' . $_id . ' Payload: ' . print_r( $payload, true ) );
		$_instance = $this->_validateInstanceRequest( $_id );

		return array(
			'instanceName'     => $_instance->instance_name_text,
			'instanceId'       => $_instance->id,
			'vendorInstanceId' => $_instance->instance_id_text,
			'instanceState'    => $_instance->state_nbr,
			'vendorState'      => $_instance->vendor_state_nbr,
			'vendorStateName'  => $_instance->vendor_state_text,
			'provisioned'      => ( 1 == $_instance->provision_ind ),
			'trial'            => ( 1 == $_instance->trial_instance_ind ),
			'deprovisioned'    => ( 1 == $_instance->deprovision_ind ),
		);
	}

	/**
	 * @param int|string $id
	 * @param array      $payload
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return Instance
	 */
	protected function _validateInstanceRequest( $id, $payload = null )
	{
		if ( empty( $id ) )
		{
			throw new RestException( HttpResponse::BadRequest );
		}

		$_instance = Instance::model()->find(
			'instance_name_text = :id or id = :id',
			array(
				 ':id' => $id,
			)
		);

		if ( null === $_instance )
		{
			throw new RestException( HttpResponse::NotFound, 'Instance ID "' . $id . '" Not found.' );
		}

		return $_instance;
	}

	/**
	 * @param array|\stdClass     $source
	 * @param BaseFabricAuthModel $target
	 * @param bool                $save
	 *
	 * @throws CDbException
	 * @return void
	 */
	protected function _drupalSync( $source, $target, $save = true )
	{
		$target->drupal_id = $source->uid;
		$target->drupal_password_text = $source->pass;

		if ( null === $target->create_date )
		{
			$target->create_date = date( 'Y-m-d H:i:s' );
		}

		if ( null === $target->api_token_text )
		{
			$target->api_token_text = Hasher::generateUnique( $target->password_text );
		}

		if ( true === $save && !$target->save() )
		{
			throw new \CDbException( $target->getErrorsForLogging() );
		}

		Log::debug( 'Drupal sync complete' );
	}

	/**
	 * Looking at a POST from the drupal site, validates token and user
	 *
	 * @param array $data
	 * @param bool  $requireToken
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return array|bool
	 */
	protected function _validateUser( $data, $requireToken = true )
	{
		$_token = null;

		Log::debug( 'validateUser payload: ' . print_r( $data, true ) );

		$_requestUser = json_decode( Option::get( $data, 'user' ) );

		$_userId = FilterInput::get( $data, 'user_id' );
		$_token = FilterInput::get( $data, 'access_token', FilterInput::get( $data, 'token' ) );
		$this->_adminView = FilterInput::get( $data, 'admin_view', false );

		if ( !empty( $_userId ) )
		{
			/** @var $_user User */
			$_user = User::model()->byTokenOrEmailOrDrupalOhMy( $_token, null, $_userId )->find();

			//	New user?
			if ( empty( $_user ) )
			{
				return array(
					null,
					$_requestUser,
				);
			}

			if ( empty( $_requestUser ) )
			{
				$_requestUser = new \stdClass();
				$_requestUser->uid = $_user->drupal_id;
				$_requestUser->mail = $_user->email_addr_text;
			}
		}
		else
		{
			if ( empty( $_requestUser ) ) //|| ( $requireToken && null === $_token ) )
			{
				Log::error( 'Drupal request but no user in payload: ' . print_r( $data, true ) );
				throw new RestException( HttpResponse::BadRequest );
			}

			foreach ( $_requestUser as $_key => $_value )
			{
				if ( is_scalar( $_value ) )
				{
					continue;
				}

				//	Yank out the drupal goodies
				if ( is_object( $_value ) && isset( $_value->und ) )
				{
					if ( !empty( $_value->und ) )
					{
						$_requestUser->{$_key} = $_value->und[0]->value;
					}
				}

				if ( is_array( $_value ) && empty( $_value ) )
				{
					$_requestUser->{$_key} = null;
				}
			}

			if ( !isset( $_requestUser->mail ) )
			{
				$_requestUser->mail = null;
			}

			/** @var $_user User */
			$_user = User::model()
				->byTokenOrEmailOrDrupalOhMy( $_token, $_requestUser->mail, $_requestUser->uid )
				->find();
		}

		if ( empty( $_user ) )
		{
			Log::info(
				'DC Drupal validation fail: token=[' . $_token . '] userId=[' . $_userId . '] user=[' . var_export(
					$_requestUser,
					true
				) . ']' . PHP_EOL .
					' data=[' . print_r( $data, true ) . ']' . PHP_EOL .
					' post=[' . print_r( $_POST, true ) . ']' . PHP_EOL .
					' server=[' . print_r( $_SERVER, true ) . ']' . PHP_EOL .
					' request=[' . print_r( $_REQUEST, true ) . ']' . PHP_EOL
			);
		}
		else
		{
			Log::debug( 'Drupal user validated: ' . $_user->email_addr_text );

			if ( 1 != $_user->admin_ind )
			{
				$this->_adminView = false;
			}
		}

		return array(
			$_user,
			$_requestUser,
		);
	}
}
