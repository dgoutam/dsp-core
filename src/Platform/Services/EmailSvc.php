<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
namespace Platform\Services;

use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;
use Platform\Utility\EmailUtilities;
use Platform\Utility\RestRequest;
use Swagger\Annotations as SWG;

/**
 * EmailSvc
 * A service to handle email services accessed through the REST API.
 *
 * @SWG\Resource(
 *   resourcePath="/{email}"
 * )
 *
 * @SWG\Model(id="Email",
 *   @SWG\Property(name="template",type="string",description="Email Template to base email on."),
 *   @SWG\Property(name="to",type="Array",items="$ref:EmailAddress",description="Required single or multiple receiver addresses."),
 *   @SWG\Property(name="cc",type="Array",items="$ref:EmailAddress",description="Optional CC receiver addresses."),
 *   @SWG\Property(name="bcc",type="Array",items="$ref:EmailAddress",description="Optional BCC receiver addresses."),
 *   @SWG\Property(name="subject",type="string",description="Text only subject line."),
 *   @SWG\Property(name="body_text",type="string",description="Text only version of the body."),
 *   @SWG\Property(name="body_html",type="string",description="Escaped HTML version of the body."),
 *   @SWG\Property(name="from_name",type="string",description="Required sender name."),
 *   @SWG\Property(name="from_email",type="string",description="Required sender email."),
 *   @SWG\Property(name="reply_to_name",type="string",description="Optional reply to name."),
 *   @SWG\Property(name="reply_to_email",type="string",description="Optional reply to email.")
 * )
 * @SWG\Model(id="EmailAddress",
 *   @SWG\Property(name="name",type="string",description="Optional name displayed along with the email address."),
 *   @SWG\Property(name="email",type="string",description="Required email address.")
 * )
 * @SWG\Model(id="EmailResponse",
 *   @SWG\Property(name="count",type="int",description="Number of emails successfully sent.")
 * )
 *
 */
class EmailSvc extends RestService
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var null|\Swift_SmtpTransport|\Swift_SendMailTransport|\Swift_MailTransport
	 */
	protected $_transport = null;
	/**
	 * @var string
	 */
	protected $fromName;
	/**
	 * @var string
	 */
	protected $fromAddress;
	/**
	 * @var string
	 */
	protected $replyToName;
	/**
	 * @var string
	 */
	protected $replyToAddress;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Create a new EmailSvc
	 *
	 * @param array $config
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		$transportType = Option::get( $config, 'storage_type', '' );
		$credentials = Option::get( $config, 'credentials', array() );
		// Create the Transport
		$this->_transport = EmailUtilities::createTransport( $transportType, $credentials );

		$parameters = Option::get( $config, 'parameters', array() );
		foreach ( $parameters as $param )
		{
			$key = Option::get( $param, 'name' );
			$value = Option::get( $param, 'value' );
			switch ( $key )
			{
				case 'from_name':
					$this->fromName = $value;
					break;
				case 'from_email':
					$this->fromAddress = $value;
					break;
				case 'reply_to_name':
					$this->replyToName = $value;
					break;
				case 'reply_to_email':
					$this->replyToAddress = $value;
					break;
			}
		}
	}

	/**
	 * @SWG\Api(
	 *   path="/{email}", description="Operations on a email service.",
	 *   @SWG\Operations(
	 *     @SWG\Operation(
	 *       httpMethod="POST", summary="Send an email created from posted data.",
	 *       notes="If a template is not used with all required fields, then they must be included in the request. If the 'from' address is not provisioned in the service, then it must be included in the request..",
	 *       responseClass="EmailResponse", nickname="sendEmail",
	 *       @SWG\Parameters(
	 *         @SWG\Parameter(
	 *           name="template", description="Optional template to base email on.",
	 *           paramType="query", required="false", allowMultiple=false, dataType="string"
	 *         ),
	 *         @SWG\Parameter(
	 *           name="data", description="Data containing name-value pairs used for provisioning emails.",
	 *           paramType="body", required="false", allowMultiple=false, dataType="Email"
	 *         )
	 *       ),
	 *       @SWG\ErrorResponses(
	 *          @SWG\ErrorResponse(code="400", reason="Bad Request - Request is not complete or valid."),
	 *          @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 *          @SWG\ErrorResponse(code="404", reason="Not Found - Email template or system resource not found."),
	 *          @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *       )
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 * @throws BadRequestException
	 */
	protected function _handleResource()
	{
		switch ( $this->_resource )
		{
			case '':
				switch ( $this->_action )
				{
					case self::Post:
						$data = RestRequest::getPostDataAsArray();

						// build email from posted data
						$template = FilterInput::request( 'template' );
						$template = Option::get( $data, 'template', $template );
						if ( !empty( $template ) )
						{
							$count = $this->sendEmailByTemplate( $template, $data );
						}
						else
						{
							if ( empty( $data ) )
							{
								throw new BadRequestException( 'No POST data in request.' );
							}

							$to = Option::get( $data, 'to' );
							$cc = Option::get( $data, 'cc' );
							$bcc = Option::get( $data, 'bcc' );
							$subject = Option::get( $data, 'subject' );
							$text = Option::get( $data, 'body_text' );
							$html = Option::get( $data, 'body_html' );
							$fromName = Option::get( $data, 'from_name' );
							$fromEmail = Option::get( $data, 'from_email' );
							$replyName = Option::get( $data, 'reply_to_name' );
							$replyEmail = Option::get( $data, 'reply_to_email' );
							$count = $this->sendEmail(
								$to,
								$cc,
								$bcc,
								$subject,
								$text,
								$html,
								$fromName,
								$fromEmail,
								$replyName,
								$replyEmail,
								$data
							);
						}

						return array( 'count' => $count );
						break;
				}
				break;
		}

		return false;
	}

	/**
	 * @param $template
	 * @param $data
	 *
	 * @return int
	 * @throws NotFoundException
	 */
	public function sendEmailByTemplate( $template, $data )
	{
		// find template in system db
		$record = \EmailTemplate::model()->findByAttributes( array( 'name' => $template ) );
		if ( empty( $record ) )
		{
			throw new NotFoundException( "Email Template '$template' not found" );
		}
		$to = $record->getAttribute( 'to' );
		$cc = $record->getAttribute( 'cc' );
		$bcc = $record->getAttribute( 'bcc' );
		$subject = $record->getAttribute( 'subject' );
		$text = $record->getAttribute( 'body_text' );
		$html = $record->getAttribute( 'body_html' );
		$fromName = $record->getAttribute( 'from_name' );
		$fromEmail = $record->getAttribute( 'from_email' );
		$replyName = $record->getAttribute( 'reply_to_name' );
		$replyEmail = $record->getAttribute( 'reply_to_email' );
		$defaults = $record->getAttribute( 'defaults' );

		// build email from template defaults overwritten by posted data
		$to = Option::get( $data, 'to', $to );
		$cc = Option::get( $data, 'cc', $cc );
		$bcc = Option::get( $data, 'bcc', $bcc );
		$fromName = Option::get( $data, 'from_name', $fromName );
		$fromEmail = Option::get( $data, 'from_email', $fromEmail );
		$replyName = Option::get( $data, 'reply_to_name', $replyName );
		$replyEmail = Option::get( $data, 'reply_to_email', $replyEmail );
		$data = array_merge( $defaults, $data );

		return $this->sendEmail(
			$to,
			$cc,
			$bcc,
			$subject,
			$text,
			$html,
			$fromName,
			$fromEmail,
			$replyName,
			$replyEmail,
			$data
		);
	}

	/**
	 * Default Send Email function determines specific mailing function
	 * based on service configuration.
	 *
	 * @param string $to_emails   comma-delimited list of receiver addresses
	 * @param string $cc_emails   comma-delimited list of CC'd addresses
	 * @param string $bcc_emails  comma-delimited list of BCC'd addresses
	 * @param string $subject     Text only subject line
	 * @param string $body_text   Text only version of the email body
	 * @param string $body_html   Escaped HTML version of the email body
	 * @param string $from_name   Name displayed for the sender
	 * @param string $from_email  Email displayed for the sender
	 * @param string $reply_name  Name displayed for the reply to
	 * @param string $reply_email Email used for the sender reply to
	 * @param array  $data        Name-Value pairs for replaceable data in subject and body
	 *
	 * @return int
	 */
	public function sendEmail( $to_emails, $cc_emails = '', $bcc_emails = '', $subject = '', $body_text = '',
							   $body_html = '', $from_name = '', $from_email = '',
							   $reply_name = '', $reply_email = '', $data = array() )
	{
		if ( empty( $from_email ) )
		{
			$from_email = $this->fromAddress;
			if ( empty( $from_name ) )
			{
				$from_name = $this->fromName;
			}
		}
		if ( empty( $reply_email ) )
		{
			$reply_email = $this->replyToAddress;
			if ( empty( $reply_name ) )
			{
				$reply_name = $this->replyToName;
			}
		}

		$to_emails = EmailUtilities::sanitizeAndValidateEmails( $to_emails, 'swift' );
		if ( !empty( $cc_emails ) )
		{
			$cc_emails = EmailUtilities::sanitizeAndValidateEmails( $cc_emails, 'swift' );
		}
		if ( !empty( $bcc_emails ) )
		{
			$bcc_emails = EmailUtilities::sanitizeAndValidateEmails( $bcc_emails, 'swift' );
		}

		$from_email = EmailUtilities::sanitizeAndValidateEmails( $from_email, 'swift' );
		if ( !empty( $reply_email ) )
		{
			$reply_email = EmailUtilities::sanitizeAndValidateEmails( $reply_email, 'swift' );
		}

		// do template field replacement
		if ( !empty( $data ) )
		{
			foreach ( $data as $name => $value )
			{
				if ( is_string( $value ) )
				{
					// replace {xxx} in subject
					$subject = str_replace( '{' . $name . '}', $value, $subject );
					// replace {xxx} in body - text and html
					$body_text = str_replace( '{' . $name . '}', $value, $body_text );
					$body_html = str_replace( '{' . $name . '}', $value, $body_html );
				}
			}
		}
		// handle special case, like invite link
		if ( false !== strpos( $body_text, '{_invite_url_}' ) ||
			 false !== strpos( $body_html, '{_invite_url_}' )
		)
		{
			// generate link for user, to email should always be the user
			$inviteLink = UserManager::userInvite( $to_emails );
			$body_text = str_replace( '{_invite_url_}', $inviteLink, $body_text );
			$body_html = str_replace( '{_invite_url_}', $inviteLink, $body_html );
		}
		if ( empty( $body_html ) )
		{
			$body_html = str_replace( "\r\n", "<br />", $body_text );
		}

		$message = EmailUtilities::createMessage(
			$to_emails,
			$cc_emails,
			$bcc_emails,
			$subject,
			$body_text,
			$body_html,
			$from_name,
			$from_email,
			$reply_name,
			$reply_email
		);

		return EmailUtilities::sendMessage( $this->_transport, $message );
	}
/*
	public static function sendUserEmail( $template, $data )
	{
		$to = Option::get( $data, 'to' );
		$cc = Option::get( $data, 'cc' );
		$bcc = Option::get( $data, 'bcc' );
		$subject = Option::get( $template, 'subject' );
		$content = Option::get( $template, 'content' );
		$bodyText = Option::get( $content, 'text' );
		$bodyHtml = Option::get( $content, 'html' );
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
	}

	protected function sendAdminIntimationOnRegComplete( $email, $fullname )
	{
		$subject = "Registration Completed: " . $fullname;
		$body = "A new user registered at " . $this->siteName . ".\r\n" .
				"Name: " . $fullname . "\r\n" .
				"Email address: " . $email . "\r\n";

		$html = str_replace( "\r\n", "<br />", $body );
	}

	protected function sendResetPasswordLink( $email, $full_name )
	{
//        $to = "$full_name <$email>";
		$to = $email;
		$subject = "Your reset password request at " . $this->siteName;
		$link = Pii::app()->getHomeUrl() . '?code=' . urlencode( $this->getResetPasswordCode( $email ) );

		$body = "Hello " . $full_name . ",\r\n\r\n" .
				"There was a request to reset your password at " . $this->siteName . "\r\n" .
				"Please click the link below to complete the request: \r\n" . $link . "\r\n" .
				"Regards,\r\n" .
				"Webmaster\r\n" .
				$this->siteName;

		$html = str_replace( "\r\n", "<br />", $body );
	}

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
	}

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
	}

	protected function sendAdminIntimationEmail( $email, $fullname )
	{
		$subject = "New registration: " . $fullname;
		$body = "A new user registered at " . $this->siteName . ".\r\n" .
				"Name: " . $fullname . "\r\n" .
				"Email address: " . $email;

		$html = str_replace( "\r\n", "<br />", $body );
	}

*/
}
