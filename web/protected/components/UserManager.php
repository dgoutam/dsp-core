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
use Kisma\Core\Utility\Sql;
use Swagger\Swagger;
use Swagger\Annotations as SWG;

/**
 * UserManager
 * DSP user manager
 *
 * @package
 * @category
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.1",
 *   basePath="http://localhost/rest",
 *   resourcePath="/user"
 * )
 *
 */
class UserManager extends RestService
{
	/**
	 * @var string
	 */
	protected static $_randKey;

	/**
	 * Create a new UserManager
	 *
	 */
	public function __construct()
	{
		$config = array(
			'name'        => 'User Session Management',
			'api_name'    => 'user',
			'type'        => 'User',
			'description' => 'Service for a user to manage their session, profile and password.',
			'is_active'   => true,
		);
		parent::__construct( $config );

		//For better security. Get a random string from this link: http://tinyurl.com/randstr and put it here
		static::$_randKey = 'M1kVi0kE9ouXxF9';
	}

	// Service interface implementation

	public function setApiName( $apiName )
	{
		throw new Exception( 'UserManager API name can not be changed.' );
	}

	public function setType( $type )
	{
		throw new Exception( 'UserManager type can not be changed.' );
	}

	public function setDescription( $description )
	{
		throw new Exception( 'UserManager description can not be changed.' );
	}

	public function setIsActive( $isActive )
	{
		throw new Exception( 'UserManager active flag can not be changed.' );
	}

	public function setName( $name )
	{
		throw new Exception( 'UserManager name can not be changed.' );
	}

	public function setNativeFormat( $nativeFormat )
	{
		throw new Exception( 'UserManager native format can not be changed.' );
	}

	// Swagger interface implementation

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		$path = Yii::app()->basePath . '/components';
		$swagger = Swagger::discover($path);
		$apis = $swagger->registry['/user']->apis;
//		$apis = array_merge( parent::getSwaggerApis(), $apis );

		return $apis;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerModels()
	{
		$models = array(
			"Session"  => array(
				"id"         => "Session",
				"properties" => array(
					"id"              => array(
						"type"        => "string",
						"description" => "Identifier for the current user."
					),
					"email"           => array(
						"type"        => "string",
						"description" => "Email address of the current user."
					),
					"first_name"      => array(
						"type"        => "string",
						"description" => "First name of the current user."
					),
					"last_name"       => array(
						"type"        => "string",
						"description" => "Last name of the current user."
					),
					"display_name"    => array(
						"type"        => "string",
						"description" => "Full display name of the current user."
					),
					"is_sys_admin"    => array(
						"type"        => "boolean",
						"description" => "Is the current user a system administrator."
					),
					"last_login_date" => array(
						"type"        => "string",
						"description" => "Date and time of the last login for the current user."
					),
					"app_groups"      => array(
						"type"        => "array",
						"description" => "App groups and the containing apps."
					),
					"no_group_apps"   => array(
						"type"        => "array",
						"description" => "Apps that are not in any app groups."
					),
					"ticket"          => array(
						"type"        => "string",
						"description" => "Timed ticket that can be used to start a separate session."
					),
					"ticket_expiry"   => array(
						"type"        => "string",
						"description" => "Expiration time for the given ticket."
					),
				)
			),
			"Profile"  => array(
				"id"         => "Profile",
				"properties" => array(
					"email"             => array(
						"type" => "string"
					),
					"first_name"        => array(
						"type"        => "string",
						"description" => "First name of the current user."
					),
					"last_name"         => array(
						"type"        => "string",
						"description" => "Last name of the current user."
					),
					"display_name"      => array(
						"type"        => "string",
						"description" => "Full display name of the current user."
					),
					"phone"             => array(
						"type"        => "string",
						"description" => "First name of the current user."
					),
					"security_question" => array(
						"type"        => "string",
						"description" => "Question to be answered to initiate password reset."
					),
					"default_app_id"    => array(
						"type"        => "integer",
						"description" => "Id of the application to be launched at login."
					),
				)
			),
			"Register" => array(
				"id"         => "Register",
				"properties" => array(
					"email"        => array(
						"type" => "string"
					),
					"first_name"   => array(
						"type"        => "string",
						"description" => "First name of the current user."
					),
					"last_name"    => array(
						"type"        => "string",
						"description" => "Last name of the current user."
					),
					"display_name" => array(
						"type"        => "string",
						"description" => "Full display name of the current user."
					),
				)
			),
			"Confirm"  => array(
				"id"         => "Confirm",
				"properties" => array(
					"email"        => array(
						"type" => "string"
					),
					"new_password" => array(
						"type" => "string"
					)
				)
			),
			"Login"    => array(
				"id"         => "Login",
				"properties" => array(
					"email"    => array(
						"type" => "string"
					),
					"password" => array(
						"type" => "string"
					)
				)
			),
			"Password" => array(
				"id"         => "Password",
				"properties" => array(
					"old_password" => array(
						"type" => "string"
					),
					"new_password" => array(
						"type" => "string"
					)
				)
			),
			"Question" => array(
				"id"         => "Question",
				"properties" => array(
					"security_question" => array(
						"type" => "string"
					)
				)
			),
			"Answer"   => array(
				"id"         => "Answer",
				"properties" => array(
					"email"           => array(
						"type" => "string"
					),
					"security_answer" => array(
						"type" => "string"
					)
				)
			),
		);
		$models = array_merge( parent::getSwaggerModels(), $models );

		return $models;
	}

	// REST interface implementation

	/**
	 * @SWG\Api(
	 *   path="/user", description="Operations available for user session management.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="List resources available for user session management.",
	 *       notes="See listed operations for each resource available.",
	 *       responseClass="Resources", nickname="getResources",
	 *       @SWG\Parameters(
	 *       ),
	 *       @SWG\ErrorResponses(
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 */
	protected function _listResources()
	{
		$resources = array(
			array( 'name' => 'session' ),
			array( 'name' => 'profile' ),
			array( 'name' => 'password' ),
			array( 'name' => 'challenge' ),
			array( 'name' => 'register' ),
			array( 'name' => 'confirm' ),
			array( 'name' => 'ticket' )
		);

		return array( 'resource' => $resources );
	}

	/**
	 *
	 *   @SWG\Api(
	 *     path="/user/session", description="Operations on a user's session.",
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Login and create a new user session.",
	 *       notes="Calling this creates a new session and logs in the user.",
	 *       responseClass="Session", nickname="login",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="credentials", description="Data containing name-value pairs used for logging into the system.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Login"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="Retrieve the current user session information.",
	 *       notes="Calling this refreshes the current session, or returns an error for timed-out or invalid sessions.",
	 *       responseClass="Session", nickname="getSession",
	 *       @SWG\Parameters(
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     ),
	 *     @SWG\Operation(
	 *       httpMethod="DELETE", summary="Logout and destroy the current user session.",
	 *       notes="Calling this deletes the current session and logs out the user.",
	 *       responseClass="Success", nickname="logout",
	 *       @SWG\Parameters(
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 *
	 * @return array|bool
	 * @throws BadRequestException
	 */
	protected function _handleResource()
	{
		switch ( $this->_resource )
		{
			case '':
				switch ( $this->_action )
				{
					case self::Get:
						return $this->_listResources();
						break;
					default:
						return false;
				}
				break;
			case 'session':
				$obj = new UserSession();
				$result = $obj->processRequest( $this->_action );
				break;
			case 'profile':
				switch ( $this->_action )
				{
					case self::Get:
						$result = $this->getProfile();
						break;
					case self::Post:
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = Utilities::getPostDataAsArray();
						$result = $this->changeProfile( $data );
						break;
					default:
						return false;
				}
				break;
			case 'password':
				switch ( $this->_action )
				{
					case self::Post:
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = Utilities::getPostDataAsArray();
						$oldPassword = Utilities::getArrayValue( 'old_password', $data, '' );
						//$oldPassword = Utilities::decryptPassword($oldPassword);
						$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
						//$newPassword = Utilities::decryptPassword($newPassword);
						$result = $this->changePassword( $oldPassword, $newPassword );
						break;
					default:
						return false;
				}
				break;
			case 'register':
				switch ( $this->_action )
				{
					case self::Post:
						$data = Utilities::getPostDataAsArray();
						$firstName = Utilities::getArrayValue( 'first_name', $data, '' );
						$lastName = Utilities::getArrayValue( 'last_name', $data, '' );
						$displayName = Utilities::getArrayValue( 'display_name', $data, '' );
						$email = Utilities::getArrayValue( 'email', $data, '' );
						$result = $this->userRegister( $email, $firstName, $lastName, $displayName );
						break;
					default:
						return false;
				}
				break;
			case 'confirm':
				switch ( $this->_action )
				{
					case self::Post:
						$data = Utilities::getPostDataAsArray();
						$code = Utilities::getArrayValue( 'code', $_REQUEST, '' );
						if ( empty( $code ) )
						{
							$code = Utilities::getArrayValue( 'code', $data, '' );
						}
						$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
						if ( empty( $email ) )
						{
							$email = Utilities::getArrayValue( 'email', $data, '' );
						}
						if ( empty( $email ) && !empty( $code ) )
						{
							throw new BadRequestException( "Missing required email or code for invitation." );
						}
						$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
						if ( empty( $newPassword ) )
						{
							throw new BadRequestException( "Missing required fields 'new_password'." );
						}
						if ( !empty( $code ) )
						{
							$result = $this->passwordResetByCode( $code, $newPassword );
						}
						else
						{
							$result = $this->passwordResetByEmail( $email, $newPassword );
						}
						break;
					default:
						return false;
				}
				break;
			case 'challenge':
				switch ( $this->_action )
				{
					case self::Get:
						$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
						$result = $this->getChallenge( $email );
						break;
					case self::Post:
					case self::Put:
					case self::Patch:
					case self::Merge:
						$data = Utilities::getPostDataAsArray();
						$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
						if ( empty( $email ) )
						{
							$email = Utilities::getArrayValue( 'email', $data, '' );
						}
						$answer = Utilities::getArrayValue( 'security_answer', $data, '' );
						if ( !empty( $email ) && !empty( $answer ) )
						{
							$result = $this->userSecurityAnswer( $email, $answer );
						}
						else
						{
							throw new BadRequestException( "Missing required fields 'email' and 'security_answer'." );
						}
						break;
					default:
						return false;
				}
				break;
			case 'ticket':
				switch ( $this->_action )
				{
					case self::Get:
						$result = $this->userTicket();
						break;
					default:
						return false;
				}
				break;
			default:
				return false;
				break;
		}

		return $result;
	}

	//-------- User Operations ------------------------------------------------

	/*
	 * @SWG\Api(
	 *   path="/user/register", description="Operations on a user's security challenge.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Register a new user in the system.",
	 *       notes="The new user is created and sent an email for confirmation.",
	 *       responseClass="Success", nickname="registerUser",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="registration", description="Data containing name-value pairs for new user registration.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Register"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 */
	public static function userInvite( $email )
	{
		if ( empty( $email ) )
		{
			throw new Exception( "The email field for invitation can not be empty.", ErrorCodes::BAD_REQUEST );
		}
		$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
		if ( empty( $theUser ) )
		{
			throw new Exception( "No user currently exists with the email '$email'.", ErrorCodes::BAD_REQUEST );
		}
		$confirmCode = $theUser->confirm_code;
		if ( 'y' == $confirmCode )
		{
			throw new Exception( "User with email '$email' has already confirmed registration in the system.", ErrorCodes::BAD_REQUEST );
		}
		try
		{
			if ( empty( $confirmCode ) )
			{
				$confirmCode = static::_makeConfirmationMd5( $email );
				$record = array( 'confirm_code' => $confirmCode );
				$theUser->setAttributes( $record );
				$theUser->save();
			}

			// generate link
			$link = Pii::app()->createAbsoluteUrl( 'public/launchpad/confirm.html' );
			$link .= '?email=' . urlencode( $email ) . '&code=' . urlencode( $confirmCode );

			return $link;
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to generate user invite!\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/user/register", description="Operations on a user's security challenge.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Register a new user in the system.",
	 *       notes="The new user is created and sent an email for confirmation.",
	 *       responseClass="Success", nickname="registerUser",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="registration", description="Data containing name-value pairs for new user registration.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Register"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param string $email
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $display_name
	 *
	 * @throws Exception
	 * @internal param string $password
	 * @return array
	 */
	public static function userRegister( $email, $first_name = '', $last_name = '', $display_name = '' )
	{
		if ( empty( $email ) )
		{
			throw new Exception( "The email field for User can not be empty.", ErrorCodes::BAD_REQUEST );
		}
		$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
		if ( null !== $theUser )
		{
			throw new Exception( "A User already exists with the email '$email'.", ErrorCodes::BAD_REQUEST );
		}
		$config = SystemManager::retrieveConfig( 'allow_open_registration,open_reg_role_id' );
		if ( !Utilities::boolval( Utilities::getArrayValue( 'allow_open_registration', $config, false ) ) )
		{
			throw new Exception( "Open registration for user accounts is not currently active for this system.", ErrorCodes::BAD_REQUEST );
		}
		$roleId = Utilities::getArrayValue( 'open_reg_role_id', $config, null );
		$confirmCode = static::_makeConfirmationMd5( $email );
		// fill out the user fields for creation
		$temp = substr( $email, 0, strrpos( $email, '@' ) );
		$fields = array(
			'email'        => $email,
			'first_name'   => ( !empty( $first_name ) ) ? $first_name : $temp,
			'last_name'    => ( !empty( $last_name ) ) ? $last_name : $temp,
			'display_name' => ( !empty( $display_name ) )
				? $display_name
				: ( !empty( $first_name ) && !empty( $last_name ) ) ? $first_name . ' ' . $last_name : $temp,
			'role_id'      => $roleId,
			'confirm_code' => $confirmCode
		);
		try
		{
			$user = new User();
			$user->setAttributes( $fields );
			$user->save();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to register new user!\n{$ex->getMessage()}", $ex->getCode() );
		}

		return array( 'success' => true );
	}

	/**
	 * @SWG\Api(
	 *   path="/user/confirm", description="Operations on a user's confirmation.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Confirm a new user registration or password change request.",
	 *       notes="The new user is confirmed and assumes the role given by system admin.",
	 *       responseClass="Success", nickname="confirmUser",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="confirmation", description="Data containing name-value pairs for new user confirmation.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Confirm"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $code
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function userConfirm( $code )
	{
		try
		{
			$theUser = User::model()->find( 'confirm_code=:cc', array( ':cc' => $code ) );
			if ( null === $theUser )
			{
				throw new Exception( "Invalid confirm code.", ErrorCodes::BAD_REQUEST );
			}
			$theUser->setAttribute( 'confirm_code', 'y' );
			$theUser->save();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error validating confirmation.\n{$ex->getMessage()}", $ex->getCode() );
		}

		return array( 'success' => true );
	}

	/**
	 * @SWG\Api(
	 *   path="/user/challenge", description="Operations on a user's security challenge.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="Retrieve the security challenge question for the given user.",
	 *       notes="Use this question to challenge the user..",
	 *       responseClass="Question", nickname="getChallenge",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="email",
	 *           description="User email used to request security question.",
	 *           paramType="query",
	 *           dataType="string",
	 *           defaultValue="user@mycompany.com",
	 *           required="true",
	 *           allowMultiple=false
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param      $email
	 *
	 * @throws Exception
	 * @return string
	 */
	public static function getChallenge( $email )
	{
		$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
		if ( null === $theUser )
		{
			// bad email
			throw new Exception( "The supplied email was not found in the system.", ErrorCodes::NOT_FOUND );
		}
		if ( 'y' !== $theUser->getAttribute( 'confirm_code' ) )
		{
			throw new Exception( "Login registration has not been confirmed.", ErrorCodes::FORBIDDEN );
		}
		$question = $theUser->getAttribute( 'security_question' );
		if ( !empty( $question ) )
		{
			return array( 'security_question' => $question );
		}
		else
		{
			throw new Exception( 'No valid security question provisioned for this user.', ErrorCodes::NOT_FOUND );
		}
	}

	/**
	 * userTicket generates a SSO timed ticket for current valid session
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function userTicket()
	{
		try
		{
			$userId = UserSession::validateSession();
		}
		catch ( Exception $ex )
		{
			UserSession::userLogout();
			throw $ex;
		}
		// regenerate new timed ticket
		$timestamp = time();
		$ticket = Utilities::encryptCreds( "$userId,$timestamp", "gorilla" );

		return array( 'ticket' => $ticket, 'ticket_expiry' => time() + ( 5 * 60 ) );
	}

	/**
	 * @param      $email
	 * @param bool $send_email
	 *
	 * @throws Exception
	 * @return string
	 */
	public static function forgotPassword( $email, $send_email = false )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad email
				throw new Exception( "The supplied email was not found in the system." );
			}
			if ( 'y' !== $theUser->confirm_code )
			{
				throw new Exception( "Login registration has not been confirmed." );
			}
			if ( $send_email )
			{
				$email = $theUser->email;
				$fullName = $theUser->display_name;
				if ( !empty( $email ) && !empty( $fullName ) )
				{
//					static::sendResetPasswordLink( $email, $fullName );

					return array( 'success' => true );
				}
				else
				{
					throw new Exception( 'No valid email provisioned for this user.' );
				}
			}
			else
			{
				$question = $theUser->security_question;
				if ( !empty( $question ) )
				{
					return array( 'security_question' => $question );
				}
				else
				{
					throw new Exception( 'No valid security question provisioned for this user.' );
				}
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error with password challenge.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/user/challenge", description="Operations on a user's security challenge.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Answer the security challenge question for the given user.",
	 *       notes="Use this to gain temporary access to change password.",
	 *       responseClass="Session", nickname="answerChallenge",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="answer", description="Answer to the security question.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Answer"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $email
	 * @param $answer
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public static function userSecurityAnswer( $email, $answer )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad email
				throw new Exception( "The supplied email was not found in the system." );
			}
			if ( 'y' !== $theUser->confirm_code )
			{
				throw new Exception( "Login registration has not been confirmed." );
			}
			// validate answer
			if ( !CPasswordHelper::verifyPassword( $answer, $theUser->security_answer ) )
			{
				throw new Exception( "The challenge response supplied does not match system records.", ErrorCodes::UNAUTHORIZED );
			}

			Pii::user()->setId( $theUser->id );
			$isSysAdmin = $theUser->is_sys_admin;
			$result = UserSession::generateSessionDataFromUser( null, $theUser );

			// write back login datetime
			$theUser->last_login_date = date( 'c' );
			$theUser->save();

			UserSession::setCurrentUserId( $theUser->id );

			// additional stuff for session - launchpad mainly
			return UserSession::addSessionExtras( $result, $isSysAdmin, true );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error processing security answer.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param string $code
	 * @param string $new_password
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public static function passwordResetByCode( $code, $new_password )
	{
		try
		{
			$theUser = User::model()->find( 'confirm_code=:cc', array( ':cc' => $code ) );
			if ( null === $theUser )
			{
				// bad code
				throw new Exception( "The supplied confirmation was not found in the system." );
			}
			$theUser->setAttribute( 'confirm_code', 'y' );
			$theUser->setAttribute( 'password', CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error processing password reset.\n{$ex->getMessage()}", $ex->getCode() );
		}

		try
		{
			return UserSession::userLogin( $theUser->email, $new_password );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Password set, but failed to create a session.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param string $email
	 * @param string $new_password
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public static function passwordResetByEmail( $email, $new_password )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad code
				throw new Exception( "The supplied email was not found in the system." );
			}
			$confirmCode = $theUser->confirm_code;
			if ( empty( $confirmCode ) || ( 'y' == $confirmCode ) )
			{
				throw new Exception( "No invitation was found for the supplied email." );
			}
			$theUser->setAttribute( 'confirm_code', 'y' );
			$theUser->setAttribute( 'password', CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error processing password reset.\n{$ex->getMessage()}", $ex->getCode() );
		}

		try
		{
			return UserSession::userLogin( $email, $new_password );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Password set, but failed to create a session.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/user/password", description="Operations on a user's password.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Update the current user's password.",
	 *       notes="A valid session is required to change the password through this API.",
	 *       responseClass="Success", nickname="changePassword",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="credentials", description="Data containing name-value pairs for password change.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Password"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param $old_password
	 * @param $new_password
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function changePassword( $old_password, $new_password )
	{
		// check valid session,
		// using userId from session, query with check for old password
		// then update with new password
		$userId = UserSession::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new Exception( "The user for the current session was not found in the system." );
			}
			// validate answer
			if ( !CPasswordHelper::verifyPassword( $old_password, $theUser->password ) )
			{
				throw new Exception( "The password supplied does not match.", ErrorCodes::BAD_REQUEST );
			}
			$theUser->setAttribute( 'password', CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();

			return array( 'success' => true );
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/user/profile", description="Operations on a user's profile.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Update the current user's profile information.",
	 *       notes="Update the security question and answer through this api, as well as, display name, email, etc.",
	 *       responseClass="Success", nickname="changeProfile",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="profile", description="Data containing name-value pairs for the user profile.",
	 *           paramType="body", required="true", allowMultiple=false, dataType="Profile"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @param array $record
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function changeProfile( $record )
	{
		// check valid session,
		// using userId from session, update with new profile elements
		$userId = UserSession::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new Exception( "The user for the current session was not found in the system." );
			}
			$allow = array(
				'first_name',
				'last_name',
				'display_name',
				'email',
				'phone',
				'security_question',
				'security_answer',
				'default_app_id'
			);
			foreach ( $record as $key => $value )
			{
				if ( false === array_search( $key, $allow ) )
				{
					throw new Exception( "Attribute '$key' can not be updated through profile change.", ErrorCodes::INTERNAL_SERVER_ERROR );
				}
			}
			$theUser->setAttributes( $record );
			$theUser->save();

			return array( 'success' => true );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to update the user profile.", ErrorCodes::INTERNAL_SERVER_ERROR );
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/user/profile", description="Operations on a user's profile.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="GET", summary="Retrieve the current user's profile information.",
	 *       notes="This profile, along with password, is the only things that the user can directly change.",
	 *       responseClass="Profile", nickname="getProfile",
	 *       @SWG\Parameters(
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getProfile()
	{
		// check valid session,
		// using userId from session, update with new profile elements
		$userId = UserSession::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new Exception( "The user for the current session was not found in the system." );
			}
			// todo protect certain attributes here
			$fields = $theUser->getAttributes(
				array(
					 'first_name',
					 'last_name',
					 'display_name',
					 'email',
					 'phone',
					 'security_question',
					 'default_app_id'
				)
			);

			return $fields;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $email
	 *
	 * @return string
	 */
	protected function _getResetPasswordCode( $email )
	{
		return substr( md5( $email . static::$_randKey ), 0, 10 );
	}

	/**
	 * @param $conf_key
	 *
	 * @return string
	 */
	protected function _makeConfirmationMd5( $conf_key )
	{
		$randNo1 = rand();
		$randNo2 = rand();

		return md5( $conf_key . static::$_randKey . $randNo1 . '' . $randNo2 );
	}
}
