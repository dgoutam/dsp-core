<?php

/**
 * UserManager.php
 * DSP user manager
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2009-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
class UserManager implements iRestHandler
{
	/**
	 * @var UserManager
	 */
	private static $_instance = null;

	/**
	 * @var string
	 */
	protected $command;

	/**
	 * @var string
	 */
	protected static $_randKey;

	/**
	 * @var null
	 */
	private static $_userId = null;

	/**
	 * @var null
	 */
	private static $_cache = null;

	/**
	 * Create a new UserManager
	 *
	 */
	public function __construct()
	{
		//For better security. Get a random string from this link: http://tinyurl.com/randstr and put it here
		static::$_randKey = 'M1kVi0kE9ouXxF9';
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
	}

	/**
	 * Gets the static instance of this class.
	 *
	 * @return UserManager
	 */
	public static function getInstance()
	{
		if ( !isset( self::$_instance ) )
		{
			self::$_instance = new UserManager();
		}

		return self::$_instance;
	}

	/**
	 * For compatibility with BaseService
	 *
	 * @return string
	 */
	public static function getType()
	{
		return 'User';
	}

	// Controller based methods

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionSwagger()
	{
		$this->detectCommonParams();
		$result = SwaggerUtilities::swaggerBaseInfo( 'user' );
		$apis = array(
			array(
				'path'        => '/user/session',
				'description' => "Operations on a user's session",
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve the current user session information",
						"notes"          => "Calling this refreshes the current session, or returns an error for timed-out or invalid sessions.",
						"responseClass"  => "Session",
						"nickname"       => "getSession",
						"parameters"     => array(),
						"errorResponses" => array(
							array(
								"code"   => 401,
								"reason" => "Unauthorized Access - No currently valid session available."
							)
						)
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Login and create a new user session",
						"notes"          => "Calling this creates a new session and logs in the user.",
						"responseClass"  => "Session",
						"nickname"       => "login",
						"parameters"     => array(
							array(
								"paramType"     => "body",
								"name"          => "credentials",
								"description"   => "Data containing name-value pairs used for logging into the system.",
								"dataType"      => "Login",
								"required"      => true,
								"allowMultiple" => false
							)
						),
						"errorResponses" => array(
							array(
								"code"   => 400,
								"reason" => "Request is not complete or valid."
							),
							array(
								"code"   => 401,
								"reason" => "Unauthorized Access - No currently valid session available."
							)
						)
					),
					array(
						"httpMethod"     => "DELETE",
						"summary"        => "Logout and destroy the current user session",
						"notes"          => "Calling this deletes the current session and logs out the user.",
						"responseClass"  => "Success",
						"nickname"       => "logout",
						"parameters"     => array(),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/user/profile',
				'description' => "Operations on a user's profile",
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve the current user's profile information",
						"notes"          => "This profile, along with password, is the only things that the user can directly change.",
						"responseClass"  => "Profile",
						"nickname"       => "getProfile",
						"parameters"     => array(),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Update the current user's profile information",
						"notes"          => "Update the security question and answer through this api, as well as, display name, email, etc.",
						"responseClass"  => "Success",
						"nickname"       => "changeProfile",
						"parameters"     => array(
							array(
								"paramType"     => "body",
								"name"          => "profile",
								"description"   => "Data containing name-value pairs of the user profile.",
								"dataType"      => "Profile",
								"required"      => true,
								"allowMultiple" => false
							)
						),
						"errorResponses" => array(
							array(
								"code"   => 400,
								"reason" => "Request is not complete or valid."
							),
							array(
								"code"   => 401,
								"reason" => "Unauthorized Access - No currently valid session available."
							)
						)
					),
				)
			),
			array(
				'path'        => '/user/password',
				'description' => "Operations on a user's password",
				'operations'  => array(
					array(
						"httpMethod"     => "POST",
						"summary"        => "Update the current user's password",
						"notes"          => "A valid session is required to change the password through this API.",
						"responseClass"  => "Success",
						"nickname"       => "changePassword",
						"parameters"     => array(
							array(
								"paramType"     => "body",
								"name"          => "credentials",
								"description"   => "Data containing name-value pairs for password change.",
								"dataType"      => "Password",
								"required"      => true,
								"allowMultiple" => false
							)
						),
						"errorResponses" => array(
							array(
								"code"   => 400,
								"reason" => "Request is not complete or valid."
							),
							array(
								"code"   => 401,
								"reason" => "Unauthorized Access - No currently valid session available."
							)
						)
					),
				)
			),
			array(
				'path'        => '/user/challenge',
				'description' => '',
				'operations'  => array(
					array(
						"httpMethod"     => "GET",
						"summary"        => "Retrieve the security challenge question for the given user",
						"notes"          => "Use this to retrieve the challenge question to present to the user.",
						"responseClass"  => "array",
						"nickname"       => "getChallenge",
						"parameters"     => array(),
						"errorResponses" => array()
					),
					array(
						"httpMethod"     => "POST",
						"summary"        => "Answer the security challenge question for the given user",
						"notes"          => "Use this to gain temporary access to change password.",
						"responseClass"  => "array",
						"nickname"       => "answerChallenge",
						"parameters"     => array(),
						"errorResponses" => array()
					),
				)
			),
			array(
				'path'        => '/user/register',
				'description' => '',
				'operations'  => array(
					array(
						"httpMethod"     => "POST",
						"summary"        => "Register a new user in the system.",
						"notes"          => "The new user is created and sent an email for confirmation.",
						"responseClass"  => "Success",
						"nickname"       => "registerUser",
						"parameters"     => array(
							array(
								"paramType"     => "body",
								"name"          => "registration",
								"description"   => "Data containing name-value pairs for new user registration.",
								"dataType"      => "Register",
								"required"      => true,
								"allowMultiple" => false
							)
						),
						"errorResponses" => array(
							array(
								"code"   => 400,
								"reason" => "Request is not complete or valid."
							)
						)
					),
				)
			),
			array(
				'path'        => '/user/confirm',
				'description' => '',
				'operations'  => array(
					array(
						"httpMethod"     => "POST",
						"summary"        => "Confirm a new user registration or password change request.",
						"notes"          => "The new user is confirmed and assumes the role given by system admin.",
						"responseClass"  => "Success",
						"nickname"       => "confirmUser",
						"parameters"     => array(
							array(
								"paramType"     => "body",
								"name"          => "confirmation",
								"description"   => "Data containing name-value pairs for new user confirmation.",
								"dataType"      => "Confirm",
								"required"      => true,
								"allowMultiple" => false
							)
						),
						"errorResponses" => array(
							array(
								"code"   => 400,
								"reason" => "Request is not complete or valid."
							)
						)
					),
				)
			),
			array(
				'path'        => '/user/ticket',
				'description' => '',
				'operations'  => array()
			)
		);
		$models = array(
			"Session" => array(
				"id" => "Session",
				"properties" => array(
					"id" => array(
						"type" => "string",
						"description" => "Identifier for the current user."
					),
					"email" => array(
						"type" => "string",
						"description" => "Email address of the current user."
					),
					"first_name" => array(
						"type" => "string",
						"description" => "First name of the current user."
					),
					"last_name" => array(
						"type" => "string",
						"description" => "Last name of the current user."
					),
					"display_name" => array(
						"type" => "string",
						"description" => "Full display name of the current user."
					),
					"is_sys_admin" => array(
						"type" => "boolean",
						"description" => "Is the current user a system administrator."
					),
					"last_login_date" => array(
						"type" => "string",
						"description" => "Date and time of the last login for the current user."
					),
					"app_groups" => array(
						"type" => "array",
						"description" => "App groups and the containing apps."
					),
					"no_group_apps" => array(
						"type" => "array",
						"description" => "Apps that are not in any app groups."
					),
					"ticket" => array(
						"type" => "string",
						"description" => "Timed ticket that can be used to start a separate session."
					),
					"ticket_expiry" => array(
						"type" => "string",
						"description" => "Expiration time for the given ticket."
					),
				)
			),
			"Profile" => array(
				"id" => "Profile",
				"properties" => array(
					"email" => array(
						"type" => "string"
					),
					"first_name" => array(
						"type" => "string",
						"description" => "First name of the current user."
					),
					"last_name" => array(
						"type" => "string",
						"description" => "Last name of the current user."
					),
					"display_name" => array(
						"type" => "string",
						"description" => "Full display name of the current user."
					),
					"phone" => array(
						"type" => "string",
						"description" => "First name of the current user."
					),
					"security_question" => array(
						"type" => "string",
						"description" => "Question to be answered to initiate password reset."
					),
					"default_app_id" => array(
						"type" => "integer",
						"description" => "Id of the application to be launched at login."
					),
				)
			),
			"Register" => array(
				"id" => "Register",
				"properties" => array(
					"email" => array(
						"type" => "string"
					),
					"first_name" => array(
						"type" => "string",
						"description" => "First name of the current user."
					),
					"last_name" => array(
						"type" => "string",
						"description" => "Last name of the current user."
					),
					"display_name" => array(
						"type" => "string",
						"description" => "Full display name of the current user."
					),
				)
			),
			"Confirm" => array(
				"id" => "Confirm",
				"properties" => array(
					"email" => array(
						"type" => "string"
					),
					"new_password" => array(
						"type" => "string"
					)
				)
			),
			"Login" => array(
				"id" => "Login",
				"properties" => array(
					"email" => array(
						"type" => "string"
					),
					"password" => array(
						"type" => "string"
					)
				)
			),
			"Password" => array(
				"id" => "Password",
				"properties" => array(
					"old_password" => array(
						"type" => "string"
					),
					"new_password" => array(
						"type" => "string"
					)
				)
			),
		);
		$result['apis'] = array_merge( $result['apis'], $apis );
		$result['models'] = array_merge( $result['models'], $models );

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionGet()
	{
		$this->detectCommonParams();
		switch ( $this->command )
		{
			case '':
				$resources = array(
					array( 'name' => 'session' ),
					array( 'name' => 'profile' ),
					array( 'name' => 'password' ),
					array( 'name' => 'challenge' ),
					array( 'name' => 'register' ),
					array( 'name' => 'confirm' ),
					array( 'name' => 'ticket' )
				);
				$result = array( 'resource' => $resources );
				break;
			case 'challenge':
				$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
				$send_email = Utilities::getArrayValue( 'send_email', $_REQUEST, false );
				$result = $this->forgotPassword( $email, $send_email );
				break;
			case 'profile':
				$result = $this->getProfile();
				break;
			case 'session':
				$ticket = Utilities::getArrayValue( 'ticket', $_REQUEST, '' );
				$result = $this->userSession( $ticket );
				break;
			case 'ticket':
				$result = $this->userTicket();
				break;
			default:
				// unsupported GET request
				throw new Exception( "GET Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( $this->command )
		{
			case 'session':
				$email = Utilities::getArrayValue( 'email', $data, '' );
				$password = Utilities::getArrayValue( 'password', $data, '' );
				//$password = Utilities::decryptPassword($password);
				$result = $this->userLogin( $email, $password );
				break;
			case 'register':
				$firstName = Utilities::getArrayValue( 'first_name', $data, '' );
				$lastName = Utilities::getArrayValue( 'last_name', $data, '' );
				$displayName = Utilities::getArrayValue( 'display_name', $data, '' );
				$email = Utilities::getArrayValue( 'email', $data, '' );
				$result = $this->userRegister( $email, $firstName, $lastName, $displayName );
				break;
			case 'confirm':
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
					throw new Exception( "Missing required email or code for invitation.", ErrorCodes::BAD_REQUEST );
				}
				$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
				if ( empty( $newPassword ) )
				{
					throw new Exception( "Missing required fields 'new_password'.", ErrorCodes::BAD_REQUEST );
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
			case 'challenge':
				$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
				if ( empty( $newPassword ) )
				{
					throw new Exception( "Missing required fields 'new_password'.", ErrorCodes::BAD_REQUEST );
				}
				$email = Utilities::getArrayValue( 'email', $_REQUEST, '' );
				if ( empty( $email ) )
				{
					$email = Utilities::getArrayValue( 'email', $data, '' );
				}
				$answer = Utilities::getArrayValue( 'security_answer', $data, '' );
				if ( !empty( $email ) && !empty( $answer ) )
				{
					$result = $this->passwordResetBySecurityAnswer( $email, $answer, $newPassword );
				}
				else
				{
					$code = Utilities::getArrayValue( 'code', $_REQUEST, '' );
					if ( empty( $code ) )
					{
						$code = Utilities::getArrayValue( 'code', $data, '' );
					}
					if ( !empty( $code ) )
					{
						$result = $this->passwordResetByCode( $code, $newPassword );
					}
					else
					{
						throw new Exception( "Missing required fields 'email' and 'security_answer'.", ErrorCodes::BAD_REQUEST );
					}
				}
				break;
			case 'password':
				$oldPassword = Utilities::getArrayValue( 'old_password', $data, '' );
				//$oldPassword = Utilities::decryptPassword($oldPassword);
				$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
				//$newPassword = Utilities::decryptPassword($newPassword);
				$result = $this->changePassword( $oldPassword, $newPassword );
				break;
			case 'profile':
				$result = $this->changeProfile( $data );
				break;
			default:
				// unsupported GET request
				throw new Exception( "POST Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( $this->command )
		{
			case 'password':
				$oldPassword = Utilities::getArrayValue( 'old_password', $data, '' );
				//$oldPassword = Utilities::decryptPassword($oldPassword);
				$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
				//$newPassword = Utilities::decryptPassword($newPassword);
				$result = $this->changePassword( $oldPassword, $newPassword );
				break;
			case 'profile':
				$result = $this->changeProfile( $data );
				break;
			default:
				// unsupported GET request
				throw new Exception( "PUT Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->detectCommonParams();
		$data = Utilities::getPostDataAsArray();
		switch ( $this->command )
		{
			case 'password':
				$oldPassword = Utilities::getArrayValue( 'old_password', $data, '' );
				//$oldPassword = Utilities::decryptPassword($oldPassword);
				$newPassword = Utilities::getArrayValue( 'new_password', $data, '' );
				//$newPassword = Utilities::decryptPassword($newPassword);
				$result = $this->changePassword( $oldPassword, $newPassword );
				break;
			case 'profile':
				$result = $this->changeProfile( $data );
				break;
			default:
				// unsupported GET request
				throw new Exception( "MERGE Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->detectCommonParams();
		switch ( $this->command )
		{
			case 'session':
				$this->userLogout();
				$result = array( 'success' => true );
				break;
			default:
				// unsupported GET request
				throw new Exception( "DELETE Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $result;
	}

	/**
	 *
	 */
	protected function detectCommonParams()
	{
		$resource = Utilities::getArrayValue( 'resource', $_GET, '' );
		$resource = ( !empty( $resource ) ) ? explode( '/', $resource ) : array();
		$this->command = ( isset( $resource[0] ) ) ? strtolower( $resource[0] ) : '';
	}

	//------- Email Helpers ----------------------

	public static function sendUserEmail( $template, $data )
	{
		$to = Utilities::getArrayValue( 'to', $data );
		$cc = Utilities::getArrayValue( 'cc', $data );
		$bcc = Utilities::getArrayValue( 'bcc', $data );
		$subject = Utilities::getArrayValue( 'subject', $template );
		$content = Utilities::getArrayValue( 'content', $template );
		$bodyText = Utilities::getArrayValue( 'text', $content );
		$bodyHtml = Utilities::getArrayValue( 'html', $content );
		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmail( $to, $cc, $bcc, $subject, $bodyText, $bodyHtml ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed to send user email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $email
	 * @param $fullname
	 *
	 * @throws Exception
	 */
	protected function sendUserWelcomeEmail( $email, $fullname )
	{
//        $to = "$fullname <$email>";
		$to = $email;
		$subject = 'Welcome to ' . $this->siteName;
		$body = 'Hello ' . $fullname . ",\r\n\r\n" .
				"Welcome! Your registration  with " . $this->siteName . " is complete.\r\n" .
				"\r\n" .
				"Regards,\r\n" .
				"Webmaster\r\n" .
				$this->siteName;

		$html = str_replace( "\r\n", "<br />", $body );
		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmail( $to, '', '', $subject, $body, $html ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed to send user welcome email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $email
	 * @param $fullname
	 *
	 * @throws Exception
	 */
	protected function sendAdminIntimationOnRegComplete( $email, $fullname )
	{
		$subject = "Registration Completed: " . $fullname;
		$body = "A new user registered at " . $this->siteName . ".\r\n" .
				"Name: " . $fullname . "\r\n" .
				"Email address: " . $email . "\r\n";

		$html = str_replace( "\r\n", "<br />", $body );

		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmailToAdmin( '', '', $subject, $body, $html ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed to send registration complete email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $email
	 * @param $full_name
	 *
	 * @throws Exception
	 */
	protected function sendResetPasswordLink( $email, $full_name )
	{
//        $to = "$full_name <$email>";
		$to = $email;
		$subject = "Your reset password request at " . $this->siteName;
		$link = Yii::app()->homeUrl . '?code=' . urlencode( $this->getResetPasswordCode( $email ) );

		$body = "Hello " . $full_name . ",\r\n\r\n" .
				"There was a request to reset your password at " . $this->siteName . "\r\n" .
				"Please click the link below to complete the request: \r\n" . $link . "\r\n" .
				"Regards,\r\n" .
				"Webmaster\r\n" .
				$this->siteName;

		$html = str_replace( "\r\n", "<br />", $body );

		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmailPhp( $to, '', '', $subject, $body, $html ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed to send user password change email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $user_rec
	 * @param $new_password
	 *
	 * @throws Exception
	 */
	protected function sendNewPassword( $user_rec, $new_password )
	{
		$email = $user_rec['email'];
//        $to = $user_rec['name'] . ' <' . $email . '>';
		$to = $email;
		$subject = "Your new password for " . $this->siteName;
		$body = "Hello " . $user_rec['name'] . ",\r\n\r\n" .
				"Your password was reset successfully. " .
				"Here is your updated login:\r\n" .
				"email:" . $user_rec['email'] . "\r\n" .
				"password:$new_password\r\n" .
				"\r\n" .
				"Login here: " . Utilities::getAbsoluteURLFolder() . "login.php\r\n" .
				"\r\n" .
				"Regards,\r\n" .
				"Webmaster\r\n" .
				$this->siteName;

		$html = str_replace( "\r\n", "<br />", $body );

		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmailPhp( $to, '', '', $subject, $body, $html ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed to send new password email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param $email
	 * @param $confirmcode
	 * @param $fullname
	 *
	 * @throws Exception
	 */
	protected function sendUserConfirmationEmail( $email, $confirmcode, $fullname )
	{
		$confirm_url = Utilities::getAbsoluteURLFolder() . 'confirmreg.php?code=' . $confirmcode;

//        $to = "$fullname <$email>";
		$to = $email;
		$subject = "Your registration with " . $this->siteName;
		$body = "Hello " . $fullname . ",\r\n\r\n" .
				"Thanks for your registration with " . $this->siteName . "\r\n" .
				"Please click the link below to confirm your registration.\r\n" .
				"$confirm_url\r\n" .
				"\r\n" .
				"Regards,\r\n" .
				"Webmaster\r\n" .
				$this->siteName;

		$html = str_replace( "\r\n", "<br />", $body );
		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmailPhp( $to, '', '', $subject, $body, $html ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed sending registration confirmation email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( $ex->getMessage() );
		}
	}

	/**
	 * @param $email
	 * @param $fullname
	 *
	 * @throws Exception
	 */
	protected function sendAdminIntimationEmail( $email, $fullname )
	{
		$subject = "New registration: " . $fullname;
		$body = "A new user registered at " . $this->siteName . ".\r\n" .
				"Name: " . $fullname . "\r\n" .
				"Email address: " . $email;

		$html = str_replace( "\r\n", "<br />", $body );

		try
		{
			$svc = ServiceHandler::getServiceObject( 'email' );
			$result = ( $svc ) ? $svc->sendEmailToAdmin( '', '', $subject, $body, $html ) : false;
			if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
			{
				$msg = "Error: Failed to send admin notification email.";
				if ( is_string( $result ) )
				{
					$msg .= "\n$result";
				}
				throw new Exception( $msg );
			}
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
	protected function getResetPasswordCode( $email )
	{
		return substr( md5( $email . static::$_randKey ), 0, 10 );
	}

	/**
	 * @param $conf_key
	 *
	 * @return string
	 */
	protected function makeConfirmationMd5( $conf_key )
	{
		$randNo1 = rand();
		$randNo2 = rand();

		return md5( $conf_key . static::$_randKey . $randNo1 . '' . $randNo2 );
	}

	/**
	 * @param array $session
	 * @param bool  $is_sys_admin
	 * @param bool  $add_apps
	 *
	 * @return array
	 */
	protected static function addSessionExtras( $session, $is_sys_admin = false, $add_apps = false )
	{
		$data = Utilities::getArrayValue( 'data', $session, array() );
		$userId = Utilities::getArrayValue( 'id', $data, '' );
		$timestamp = time();
		$ticket = Utilities::encryptCreds( "$userId,$timestamp", "gorilla" );
		$data['ticket'] = $ticket;
		$data['ticket_expiry'] = time() + ( 5 * 60 );
		$data['session_id'] = session_id();

		if ( $add_apps )
		{
			$appFields = 'id,api_name,name,description,url,is_url_external,requires_fullscreen,allow_fullscreen_toggle,toggle_location';
			$theApps = Utilities::getArrayValue( 'allowed_apps', $session, array() );
			if ( $is_sys_admin )
			{
				$theApps = App::model()->findAll( 'is_active = :ia', array( ':ia' => 1 ) );
			}
			$theGroups = AppGroup::model()->with( 'apps' )->findAll();
			$appGroups = array();
			$noGroupApps = array();
			$defaultAppId = Utilities::getArrayValue( 'default_app_id', $session, null );
			foreach ( $theApps as $app )
			{
				$appId = $app->getAttribute( 'id' );
				$tempGroups = $app->getRelated( 'app_groups' );
				$appData = $app->getAttributes( explode( ',', $appFields ) );
				$appData['is_default'] = ( $defaultAppId === $appId );
				$found = false;
				foreach ( $theGroups as $g_key => $group )
				{
					$groupId = $group->getAttribute( 'id' );
					$groupData = ( isset( $appGroups[$g_key] ) ) ? $appGroups[$g_key] : $group->getAttributes( array( 'id', 'name', 'description' ) );
					foreach ( $tempGroups as $tempGroup )
					{
						if ( $tempGroup->getAttribute( 'id' ) === $groupId )
						{
							$found = true;
							$temp = Utilities::getArrayValue( 'apps', $groupData, array() );
							$temp[] = $appData;
							$groupData['apps'] = $temp;
						}
					}
					$appGroups[$g_key] = $groupData;
				}
				if ( !$found )
				{
					$noGroupApps[] = $appData;
				}
			}
			// clean out any empty groups
			foreach ( $appGroups as $g_key => $group )
			{
				if ( !isset( $group['apps'] ) )
				{
					unset( $appGroups[$g_key] );
				}
			}
			$data['app_groups'] = array_values( $appGroups ); // reset indexing
			$data['no_group_apps'] = $noGroupApps;
		}

		return $data;
	}

	//-------- User Operations ------------------------------------------------

	/**
	 * @param $email
	 * @param $password
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function userLogin( $email, $password )
	{
		if ( empty( $email ) )
		{
			throw new Exception( "Login request is missing required email.", ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $password ) )
		{
			throw new Exception( "Login request is missing required password.", ErrorCodes::BAD_REQUEST );
		}

		try
		{
			$identity = new DspUserIdentity( $email, $password );
			if ( $identity->authenticate() )
			{
				Yii::app()->user->login( $identity );
			}
			else
			{
				throw new Exception( "The credentials supplied do not match system records.", ErrorCodes::UNAUTHORIZED );
			}

			$theUser = $identity->getUser();
			if ( null === $theUser )
			{
				// bad user object
				throw new Exception( "The user session contains no data.", ErrorCodes::INTERNAL_SERVER_ERROR );
			}
			if ( 'y' !== $theUser->getAttribute( 'confirm_code' ) )
			{
				throw new Exception( "Login registration has not been confirmed." );
			}
			$isSysAdmin = $theUser->getAttribute( 'is_sys_admin' );
			$result = static::generateSessionDataFromUser( Yii::app()->user->getId() );

			// write back login datetime
			$theUser->last_login_date = new CDbExpression( 'NOW()' );
			$theUser->save();

			// additional stuff for session - launchpad mainly
			return static::addSessionExtras( $result, $isSysAdmin, true );
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * userSession refreshes an existing session or
	 *     allows the SSO creation of a new session for external apps via timed ticket
	 *
	 * @param string $ticket
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function userSession( $ticket = '' )
	{
		if ( empty( $ticket ) )
		{
			try
			{
				$userId = static::validateSession();
			}
			catch ( Exception $ex )
			{
				static::userLogout();

				// special case for possible guest user
				$theConfig = Config::model()->with(
					'guest_role.role_service_accesses',
					'guest_role.apps',
					'guest_role.services'
				)->find();

				if ( !empty( $theConfig ) )
				{
					if ( Utilities::boolval( $theConfig->getAttribute( 'allow_guest_user' ) ) )
					{
						$result = static::generateSessionDataFromRole( null, $theConfig->getRelated( 'guest_role' ) );

						// additional stuff for session - launchpad mainly
						return static::addSessionExtras( $result, false, true );
					}
				}

				// otherwise throw original exception
				throw $ex;
			}
		}
		else
		{ // process ticket
			$creds = Utilities::decryptCreds( $ticket, "gorilla" );
			$pieces = explode( ',', $creds );
			$userId = $pieces[0];
			$timestamp = $pieces[1];
			$curTime = time();
			$lapse = $curTime - $timestamp;
			if ( empty( $userId ) || ( $lapse > 300 ) )
			{ // only lasts 5 minutes
				static::userLogout();
				throw new Exception( "Ticket used for session generation is too old.", ErrorCodes::UNAUTHORIZED );
			}
		}

		try
		{
			$theUser = User::model()->with( 'role.role_service_accesses', 'role.apps', 'role.services' )->findByPk( $userId );
			if ( null === $theUser )
			{
				throw new Exception( "The user identified in the session or ticket does not exist in the system.", ErrorCodes::UNAUTHORIZED );
			}
			$isSysAdmin = $theUser->getAttribute( 'is_sys_admin' );
			$result = static::generateSessionDataFromUser( null, $theUser );

			// additional stuff for session - launchpad mainly
			return static::addSessionExtras( $result, $isSysAdmin, true );
		}
		catch ( Exception $ex )
		{
			throw $ex;
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
			$userId = static::validateSession();
		}
		catch ( Exception $ex )
		{
			static::userLogout();
			throw $ex;
		}
		// regenerate new timed ticket
		$timestamp = time();
		$ticket = Utilities::encryptCreds( "$userId,$timestamp", "gorilla" );

		return array( 'ticket' => $ticket, 'ticket_expiry' => time() + ( 5 * 60 ) );
	}

	/**
	 *
	 */
	public static function userLogout()
	{
		Yii::app()->user->logout();
	}

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
		$confirmCode = $theUser->getAttribute( 'confirm_code' );
		if ( 'y' == $confirmCode )
		{
			throw new Exception( "User with email '$email' has already confirmed registration in the system.", ErrorCodes::BAD_REQUEST );
		}
		try
		{
			if ( empty( $confirmCode ) )
			{
				$confirmCode = static::makeConfirmationMd5( $email );
				$record = array( 'confirm_code' => $confirmCode );
				$theUser->setAttributes( $record );
				$theUser->save();
			}

			// generate link
			$link = Yii::app()->createAbsoluteUrl( 'public/launchpad/confirm.html' );
			$link .= '?email=' . urlencode( $email ) . '&code=' . urlencode( $confirmCode );

			return $link;
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to generate user invite!\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param        $email
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
		$confirmCode = static::makeConfirmationMd5( $email );
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
			if ( 'y' !== $theUser->getAttribute( 'confirm_code' ) )
			{
				throw new Exception( "Login registration has not been confirmed." );
			}
			if ( $send_email )
			{
				$email = $theUser->getAttribute( 'email' );
				$fullName = $theUser->getAttribute( 'display_name' );
				if ( !empty( $email ) && !empty( $fullName ) )
				{
					static::sendResetPasswordLink( $email, $fullName );

					return array( 'success' => true );
				}
				else
				{
					throw new Exception( 'No valid email provisioned for this user.' );
				}
			}
			else
			{
				$question = $theUser->getAttribute( 'security_question' );
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
	 * @param $email
	 * @param $answer
	 * @param $new_password
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public static function passwordResetBySecurityAnswer( $email, $answer, $new_password )
	{
		try
		{
			$theUser = User::model()->find( 'email=:email', array( ':email' => $email ) );
			if ( null === $theUser )
			{
				// bad email
				throw new Exception( "The supplied email was not found in the system." );
			}
			if ( 'y' !== $theUser->getAttribute( 'confirm_code' ) )
			{
				throw new Exception( "Login registration has not been confirmed." );
			}
			// validate answer
			if ( !CPasswordHelper::verifyPassword( $answer, $theUser->getAttribute( 'security_answer' ) ) )
			{
				throw new Exception( "The challenge response supplied does not match system records.", ErrorCodes::UNAUTHORIZED );
			}
			$theUser->setAttribute( 'password', CPasswordHelper::hashPassword( $new_password ) );
			$theUser->save();

			return static::userLogin( $email, $new_password );
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
			return static::userLogin( $theUser->getAttribute( 'email' ), $new_password );
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
			$confirmCode = $theUser->getAttribute( 'confirm_code' );
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
			return static::userLogin( $email, $new_password );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Password set, but failed to create a session.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
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
		$userId = static::validateSession();

		try
		{
			$theUser = User::model()->findByPk( $userId );
			if ( null === $theUser )
			{
				// bad session
				throw new Exception( "The user for the current session was not found in the system." );
			}
			// validate answer
			if ( !CPasswordHelper::verifyPassword( $old_password, $theUser->getAttribute( 'password' ) ) )
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
	 * @param array $record
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function changeProfile( $record )
	{
		// check valid session,
		// using userId from session, update with new profile elements
		$userId = static::validateSession();

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
	 * @return bool
	 * @throws Exception
	 */
	public static function getProfile()
	{
		// check valid session,
		// using userId from session, update with new profile elements
		$userId = static::validateSession();

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
	 * @param      $user_id
	 * @param null $user
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function generateSessionDataFromUser( $user_id, $user = null )
	{
		if ( !isset( $user ) )
		{
			$user = User::model()->with( 'role.role_service_accesses', 'role.apps', 'role.services' )->findByPk( $user_id );
		}
		if ( null === $user )
		{
			throw new Exception( "The user with id $user_id does not exist in the system.", ErrorCodes::UNAUTHORIZED );
		}
		$email = $user->getAttribute( 'email' );
		if ( !$user->getAttribute( 'is_active' ) )
		{
			throw new Exception( "The user with email '$email' is not currently active.", ErrorCodes::FORBIDDEN );
		}

		$isSysAdmin = $user->getAttribute( 'is_sys_admin' );
		$defaultAppId = $user->getAttribute( 'default_app_id' );
		$fields = array( 'id', 'display_name', 'first_name', 'last_name', 'email', 'is_sys_admin', 'last_login_date' );
		$userInfo = $user->getAttributes( $fields );
		$data = $userInfo; // reply data
		$allowedApps = array();

		if ( !$isSysAdmin )
		{
			$theRole = $user->getRelated( 'role' );
			if ( !isset( $theRole ) )
			{
				throw new Exception( "The user '$email' has not been assigned a role.", ErrorCodes::FORBIDDEN );
			}
			if ( !$theRole->getAttribute( 'is_active' ) )
			{
				throw new Exception( "The role this user is assigned to is not currently active.", ErrorCodes::FORBIDDEN );
			}

			if ( !isset( $defaultAppId ) )
			{
				$defaultAppId = $theRole->getAttribute( 'default_app_id' );
			}
			$role = $theRole->attributes;
			$theApps = $theRole->getRelated( 'apps' );
			$roleApps = array();
			if ( !empty( $theApps ) )
			{
				$appFields = array( 'id', 'api_name', 'is_active' );
				foreach ( $theApps as $app )
				{
					$roleApps[] = $app->getAttributes( $appFields );
					if ( $app->getAttribute( 'is_active' ) )
					{
						$allowedApps[] = $app;
					}
				}
			}
			$role['apps'] = $roleApps;
			$permsFields = array( 'service_id', 'component', 'access' );
			$thePerms = $theRole->getRelated( 'role_service_accesses' );
			$theServices = $theRole->getRelated( 'services' );
			$perms = array();
			foreach ( $thePerms as $perm )
			{
				$permServiceId = $perm->getAttribute( 'service_id' );
				$temp = $perm->getAttributes( $permsFields );
				foreach ( $theServices as $service )
				{
					if ( $permServiceId == $service->getAttribute( 'id' ) )
					{
						$temp['service'] = $service->getAttribute( 'api_name' );
					}
				}
				$perms[] = $temp;
			}
			$role['services'] = $perms;
			$userInfo['role'] = $role;
		}

		return array(
			'public'         => $userInfo,
			'data'           => $data,
			'allowed_apps'   => $allowedApps,
			'default_app_id' => $defaultAppId
		);
	}

	/**
	 * @param      $role_id
	 * @param null $role
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	public static function generateSessionDataFromRole( $role_id, $role = null )
	{
		if ( !isset( $role ) )
		{
			if ( empty( $role_id ) )
			{
				throw new Exception( "No valid role is assigned for guest users.", ErrorCodes::UNAUTHORIZED );
			}
			$role = Role::model()->with( 'role_service_accesses', 'apps', 'services' )->findByPk( $role_id );
		}
		if ( null === $role )
		{
			throw new Exception( "The role with id $role_id does not exist in the system.", ErrorCodes::UNAUTHORIZED );
		}
		$name = $role->getAttribute( 'name' );
		if ( !$role->getAttribute( 'is_active' ) )
		{
			throw new Exception( "The role '$name' is not currently active.", ErrorCodes::FORBIDDEN );
		}

		$userInfo = array();
		$data = array(); // reply data
		$allowedApps = array();

		$defaultAppId = $role->getAttribute( 'default_app_id' );
		$roleData = $role->attributes;
		$theApps = $role->getRelated( 'apps' );
		$roleApps = array();
		if ( !empty( $theApps ) )
		{
			$appFields = array( 'id', 'api_name', 'is_active' );
			foreach ( $theApps as $app )
			{
				$roleApps[] = $app->getAttributes( $appFields );
				if ( $app->getAttribute( 'is_active' ) )
				{
					$allowedApps[] = $app;
				}
			}
		}
		$roleData['apps'] = $roleApps;
		$permsFields = array( 'service_id', 'component', 'access' );
		$thePerms = $role->getRelated( 'role_service_accesses' );
		$theServices = $role->getRelated( 'services' );
		$perms = array();
		foreach ( $thePerms as $perm )
		{
			$permServiceId = $perm->getAttribute( 'service_id' );
			$temp = $perm->getAttributes( $permsFields );
			foreach ( $theServices as $service )
			{
				if ( $permServiceId == $service->getAttribute( 'id' ) )
				{
					$temp['service'] = $service->getAttribute( 'api_name' );
				}
			}
			$perms[] = $temp;
		}
		$roleData['services'] = $perms;
		$userInfo['role'] = $roleData;

		return array(
			'public'         => $userInfo,
			'data'           => $data,
			'allowed_apps'   => $allowedApps,
			'default_app_id' => $defaultAppId
		);
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function validateSession()
	{
		if ( !Yii::app()->user->getIsGuest() &&
			 !Yii::app()->user->getState( 'df_authenticated', false )
		)
		{
			return Yii::app()->user->getId();
		}

		throw new Exception( "There is no valid session for the current request.", ErrorCodes::UNAUTHORIZED );
	}

	public static function isSystemAdmin()
	{
		if ( empty( static::$_cache ) )
		{
			if ( Yii::app()->user->getIsGuest() )
			{
				return false;
			}
			static::$_cache = static::generateSessionDataFromUser( Yii::app()->user->getId() );
		}
		$_public = Utilities::getArrayValue( 'public', static::$_cache, array() );

		return Utilities::boolval( Utilities::getArrayValue( 'is_sys_admin', $_public, false ) );
	}

	/**
	 * @param        $request
	 * @param        $service
	 * @param string $component
	 *
	 * @throws Exception
	 */
	public static function checkPermission( $request, $service, $component = '' )
	{
		if ( empty( static::$_cache ) )
		{
			if ( !Yii::app()->user->getIsGuest() &&
				 !Yii::app()->user->getState( 'df_authenticated', false )
			)
			{
				static::$_cache = static::generateSessionDataFromUser( Yii::app()->user->getId() );
			}
			else
			{
				// special case for possible guest user
				$theConfig = Config::model()->with(
					'guest_role.role_service_accesses',
					'guest_role.apps',
					'guest_role.services'
				)->find();

				if ( empty( $theConfig ) )
				{
					throw new Exception( "There is no valid session for the current request.", ErrorCodes::UNAUTHORIZED );
				}
				if ( !Utilities::boolval( $theConfig->getAttribute( 'allow_guest_user' ) ) )
				{
					throw new Exception( "There is no valid session for the current request.", ErrorCodes::UNAUTHORIZED );
				}
				static::$_cache = static::generateSessionDataFromRole( null, $theConfig->getRelated( 'guest_role' ) );
			}
		}
		$_public = Utilities::getArrayValue( 'public', static::$_cache, array() );
		$admin = Utilities::getArrayValue( 'is_sys_admin', $_public, false );
		if ( $admin )
		{
			return; // no need to check role
		}
		$roleInfo = Utilities::getArrayValue( 'role', $_public, array() );
		if ( empty( $roleInfo ) )
		{
			// no role assigned, if not sys admin, denied service
			throw new Exception( "A valid user role or system administrator is required to access services.", ErrorCodes::FORBIDDEN );
		}

		// check if app allowed in role
		$appName = Utilities::getArrayValue( 'app_name', $GLOBALS, '' );
		if ( empty( $appName ) )
		{
			throw new Exception( "A valid application name is required to access services.", ErrorCodes::BAD_REQUEST );
		}

		$apps = Utilities::getArrayValue( 'apps', $roleInfo, null );
		if ( !is_array( $apps ) || empty( $apps ) )
		{
			throw new Exception( "Access to application '$appName' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN );
		}
		$found = false;
		foreach ( $apps as $app )
		{
			$temp = Utilities::getArrayValue( 'api_name', $app );
			if ( 0 == strcasecmp( $appName, $temp ) )
			{
				$found = true;
			}
		}
		if ( !$found )
		{
			throw new Exception( "Access to application '$appName' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN );
		}
		/*
			 // see if we need to deny access to this app
			 $result = $db->retrieveSqlRecordsByIds('app', $appName, 'name', 'id,is_active');
			 if ((0 >= count($result)) || empty($result[0])) {
				 throw new Exception("The application '$appName' could not be found.");
			 }
			 if (!$result[0]['is_active']) {
				 throw new Exception("The application '$appName' is not currently active.");
			 }
			 $appId = $result[0]['id'];
			 // is this app part of the role's allowed apps
			 if (!empty($allowedAppIds)) {
				 if (!Utilities::isInList($allowedAppIds, $appId, ',')) {
					 throw new Exception("The application '$appName' is not currently allowed by this role.");
				 }
			 }
		 */

		$services = Utilities::getArrayValue( 'services', $roleInfo, null );
		if ( !is_array( $services ) || empty( $services ) )
		{
			throw new Exception( "Access to service '$service' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN );
		}

		$allAllowed = false;
		$allFound = false;
		$serviceAllowed = false;
		$serviceFound = false;
		foreach ( $services as $svcInfo )
		{
			$theService = Utilities::getArrayValue( 'service', $svcInfo );
			$theAccess = Utilities::getArrayValue( 'access', $svcInfo, '' );
			if ( 0 == strcasecmp( $service, $theService ) )
			{
				$theComponent = Utilities::getArrayValue( 'component', $svcInfo );
				if ( !empty( $component ) )
				{
					if ( 0 == strcasecmp( $component, $theComponent ) )
					{
						if ( !static::isAllowed( $request, $theAccess ) )
						{
							$msg = ucfirst( $request ) . " access to component '$component' of service '$service' ";
							$msg .= "is not allowed by this user's role.";
							throw new Exception( $msg, ErrorCodes::FORBIDDEN );
						}

						return; // component specific found and allowed, so bail
					}
					elseif ( empty( $theComponent ) || ( '*' == $theComponent ) )
					{
						$serviceAllowed = static::isAllowed( $request, $theAccess );
						$serviceFound = true;
					}
				}
				else
				{
					if ( empty( $theComponent ) || ( '*' == $theComponent ) )
					{
						if ( !static::isAllowed( $request, $theAccess ) )
						{
							$msg = ucfirst( $request ) . " access to service '$service' ";
							$msg .= "is not allowed by this user's role.";
							throw new Exception( $msg, ErrorCodes::FORBIDDEN );
						}

						return; // service specific found and allowed, so bail
					}
				}
			}
			elseif ( empty( $theService ) || ( '*' == $theService ) )
			{
				$allAllowed = static::isAllowed( $request, $theAccess );
				$allFound = true;
			}
		}

		if ( $serviceFound )
		{
			if ( $serviceAllowed )
			{
				return; // service found and allowed, so bail
			}
		}
		elseif ( $allFound )
		{
			if ( $allAllowed )
			{
				return; // all services found and allowed, so bail
			}
		}
		$msg = ucfirst( $request ) . " access to ";
		if ( !empty( $component ) )
		{
			$msg .= "component '$component' of ";
		}
		$msg .= "service '$service' is not allowed by this user's role.";
		throw new Exception( $msg, ErrorCodes::FORBIDDEN );
	}

	/**
	 * @param $request
	 * @param $access
	 *
	 * @return bool
	 */
	protected static function isAllowed( $request, $access )
	{
		switch ( $request )
		{
			case 'read':
				switch ( $access )
				{
					case 'Read Only':
					case 'Read and Write':
					case 'Full Access':
						return true;
				}
				break;
			case 'create':
				switch ( $access )
				{
					case 'Write Only':
					case 'Read and Write':
					case 'Full Access':
						return true;
				}
				break;
			case 'update':
				switch ( $access )
				{
					case 'Write Only':
					case 'Read and Write':
					case 'Full Access':
						return true;
				}
				break;
			case 'delete':
				switch ( $access )
				{
					case 'Full Access':
						return true;
				}
				break;
			default:
				break;
		}
		return false;
	}

	/**
	 * @param $userId
	 */
	public static function setCurrentUserId( $userId )
	{
		static::$_userId = $userId;

		return $userId;
	}

	/**
	 * @return int|null
	 */
	public static function getCurrentUserId()
	{
		if ( isset( static::$_userId ) )
		{
			return static::$_userId;
		}

		if ( !Yii::app()->user->getIsGuest() )
		{
			if ( !Yii::app()->user->getState( 'df_authenticated', false ) )
			{
				return static::setCurrentUserId( Yii::app()->user->getId() );
			}
		}

		return null;
	}
}
