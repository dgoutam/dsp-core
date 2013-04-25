<?php

//	Load up SwiftMailer
$_vendorPath = Yii::app()->basePath . '/../../vendor';
require_once $_vendorPath . '/swiftmailer/swiftmailer/lib/classes/Swift.php';
Yii::registerAutoloader( array( 'Swift', 'autoload' ) );
require_once $_vendorPath . '/swiftmailer/swiftmailer/lib/swift_required.php';

/**
 * EmailSvc.php
 * A service to handle email services accessed through the REST API.
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
class EmailSvc extends BaseService
{
	/**
	 * @var boolean
	 */
	protected $_isNative = false;

	/**
	 * @var null | Swift_SmtpTransport | Swift_SendMailTransport | Swift_MailTransport
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

	/**
	 * Create a new EmailSvc
	 *
	 * @param array $config
	 * @param bool  $native
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $config, $native = false )
	{
		parent::__construct( $config );

		$this->_isNative = $native;
		$transportType = Utilities::getArrayValue( 'storage_type', $config, '' );
		// Create the Transport
		if ( $native )
		{
			if ( !empty( $transportType ) )
			{
				if ( 0 == strcasecmp( 'smtp', $transportType ) ) // local support?
				{
					$this->_transport = Swift_SmtpTransport::newInstance();
				}
				else
				{
					// sendmail, exim, postscript, etc
					$this->_transport = Swift_SendmailTransport::newInstance( $transportType );
				}
			}
			else
			{
				// mail()
				$this->_transport = Swift_MailTransport::newInstance();
			}
		}
		else
		{
			switch ( $transportType )
			{
				case 'smtp': // for now only protocol supported
				default:
					// SMTP
					$credentials = Utilities::getArrayValue( 'credentials', $config, array() );
					// Validate other parameters
					$host = Utilities::getArrayValue( 'host', $credentials, 'localhost' );
					if ( empty( $host ) )
					{
						throw new InvalidArgumentException( 'SMTP host name can not be empty.', ErrorCodes::BAD_REQUEST );
					}
					$port = Utilities::getArrayValue( 'port', $credentials, 25 );
					$security = Utilities::getArrayValue( 'security', $credentials, null );
					$security = ( !empty( $security ) ) ? strtolower($security) : null;
					$this->_transport = Swift_SmtpTransport::newInstance( $host, $port, $security );
					$user = Utilities::getArrayValue( 'user', $credentials, '' );
					$pwd = Utilities::getArrayValue( 'pwd', $credentials, '' );
					if ( !empty( $user ) && !empty( $pwd ) )
					{
						$this->_transport->setUsername( $user );
						$this->_transport->setPassword( $pwd );
					}
					break;
			}
		}

		$parameters = Utilities::getArrayValue( 'parameters', $config, array() );
		foreach ($parameters as $param)
		{
			$key = Utilities::getArrayValue( 'name', $param );
			$value = Utilities::getArrayValue( 'value', $param );
			switch ($key)
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

	// Controller based methods

	/**
	 *
	 * @return array
	 * @throws Exception
	 */
	public function actionSwagger()
	{
		$result = parent::actionSwagger();
		$resources = array(
			array(
				'path'        => '/' . $this->_apiName,
				'description' => $this->_description,
				'operations'  => array(
					array(
						"httpMethod"     => "POST",
						"summary"        => "Send an email created from posted data.",
						"notes"          => "Post email data as an array of parameters. If a standalone template is not used, include all required fields.",
						"responseClass"  => "Response",
						"nickname"       => "sendEmail",
						"parameters"     => array(
							array(
								"paramType"     => "query",
								"name"          => "template",
								"description"   => "Email Template to base email on.",
								"dataType"      => "String",
								"required"      => false,
								"allowMultiple" => false
							),
							array(
								"paramType"     => "body",
								"name"          => "post_data",
								"description"   => "Data containing name-value pairs used for provisioning emails.",
								"dataType"      => "Email",
								"required"      => false,
								"allowMultiple" => false
							)
						),
						"errorResponses" => array(
							array(
								"code"   => 400,
								"reason" => "Request is not complete or valid."
							),
							array(
								"code"   => 404,
								"reason" => "Email Template not found."
							)
						)
					)
				)
			)
		);
		$result['apis'] = $resources;
		$result['models'] = array(
			"Email"    => array(
				"id"         => "Email",
				"properties" => array(
					"template"       => array(
						"type"        => "string",
						"description" => "Email Template to base email on."
					),
					"to"             => array(
						"type"        => "string",
						"description" => "Comma-delimited list of receiver addresses."
					),
					"cc"             => array(
						"type"        => "string",
						"description" => "Comma-delimited list of CC receiver addresses."
					),
					"bcc"            => array(
						"type"        => "string",
						"description" => "Comma-delimited list of BCC receiver addresses."
					),
					"subject"        => array(
						"type"        => "string",
						"description" => "Text only subject line."
					),
					"body_text"      => array(
						"type"        => "string",
						"description" => "Text only version of the email body."
					),
					"body_html"      => array(
						"type"        => "string",
						"description" => "Escaped HTML version of the email body."
					),
					"from_name"      => array(
						"type"        => "string",
						"description" => "Name displayed for the sender."
					),
					"from_email"     => array(
						"type"        => "string",
						"description" => "Email displayed for the sender."
					),
					"reply_to_name"  => array(
						"type"        => "string",
						"description" => "Name displayed for the reply to."
					),
					"reply_to_email" => array(
						"type"        => "string",
						"description" => "Email displayed for the reply to."
					),
				)
			),
			"Response" => array(
				"id"         => "Response",
				"properties" => array(
					"count" => array(
						"type" => "integer"
					)
				)
			)
		);

		return $result;
	}

	public function actionPost()
	{
		$data = Utilities::getPostDataAsArray();

		// build email from posted data
		$template = Utilities::getArrayValue( 'template', $_REQUEST );
		$template = Utilities::getArrayValue( 'template', $data, $template );
		if ( !empty( $template ) )
		{
			$count = $this->sendEmailByTemplate( $template, $data );
		}
		else
		{
			if ( empty( $data ) )
			{
				throw new Exception( 'No POST data in request.', ErrorCodes::BAD_REQUEST );
			}

			$to = Utilities::getArrayValue( 'to', $data );
			$cc = Utilities::getArrayValue( 'cc', $data );
			$bcc = Utilities::getArrayValue( 'bcc', $data );
			$subject = Utilities::getArrayValue( 'subject', $data );
			$text = Utilities::getArrayValue( 'body_text', $data );
			$html = Utilities::getArrayValue( 'body_html', $data );
			$fromName = Utilities::getArrayValue( 'from_name', $data );
			$fromEmail = Utilities::getArrayValue( 'from_email', $data );
			$replyName = Utilities::getArrayValue( 'reply_to_name', $data );
			$replyEmail = Utilities::getArrayValue( 'reply_to_email', $data );
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
	}

	public function actionPut()
	{
		throw new Exception( "PUT Request is not supported by this Email API.", ErrorCodes::BAD_REQUEST );
	}

	public function actionMerge()
	{
		throw new Exception( "MERGE Request is not supported by this Email API.", ErrorCodes::BAD_REQUEST );
	}

	public function actionDelete()
	{
		throw new Exception( "DELETE Request is not supported by this Email API.", ErrorCodes::BAD_REQUEST );
	}

	public function actionGet()
	{
		throw new Exception( "GET Request is not supported by this Email API.", ErrorCodes::BAD_REQUEST );
	}

	//------- Email Methods ----------------------

	/**
	 * @param $template
	 * @param $data
	 *
	 * @return int
	 * @throws Exception
	 */
	public function sendEmailByTemplate( $template, $data )
	{
		// find template in system db
		$record = EmailTemplate::model()->findByAttributes( array( 'name' => $template ) );
		if ( empty( $record ) )
		{
			throw new Exception( "Email Template '$template' not found", ErrorCodes::NOT_FOUND );
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
		$to = Utilities::getArrayValue( 'to', $data, $to );
		$cc = Utilities::getArrayValue( 'cc', $data, $cc );
		$bcc = Utilities::getArrayValue( 'bcc', $data, $bcc );
		$fromName = Utilities::getArrayValue( 'from_name', $data, $fromName );
		$fromEmail = Utilities::getArrayValue( 'from_email', $data, $fromEmail );
		$replyName = Utilities::getArrayValue( 'reply_to_name', $data, $replyName );
		$replyEmail = Utilities::getArrayValue( 'reply_to_email', $data, $replyEmail );
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
	 * @throws Exception
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

		$to_emails = static::sanitizeAndValidateEmails( $to_emails, 'swift' );
		if ( !empty( $cc_emails ) )
		{
			$cc_emails = static::sanitizeAndValidateEmails( $cc_emails, 'swift' );
		}
		if ( !empty( $bcc_emails ) )
		{
			$bcc_emails = static::sanitizeAndValidateEmails( $bcc_emails, 'swift' );
		}

		$from_email = static::sanitizeAndValidateEmails( $from_email, 'swift' );
		if ( !empty( $reply_email ) )
		{
			$reply_email = static::sanitizeAndValidateEmails( $reply_email, 'swift' );
		}

		// do template field replacement
		if ( !empty( $data ) )
		{
			foreach ( $data as $name => $value )
			{
				if (is_string($value))
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

		return $this->sendBySwiftMailer(
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
	}

	/**
	 * Email Web Service Send Email API
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
	 *
	 * @throws Exception
	 * @return int
	 */
	private function sendBySwiftMailer( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
										$body_html = '', $from_name = '', $from_email = '',
										$reply_name = '', $reply_email = '' )
	{
		// Create the message
		$message = Swift_Message::newInstance()
			->setSubject( $subject )
			->setTo( $to_emails ) // array('receiver@domain.org', 'other@domain.org' => 'A name')
			->setFrom( $from_email, $from_name ); // can be multiple
		if ( !empty( $reply_email ) )
		{
			$message->setSender( $reply_email, $reply_name ); // single address
		}
//			$message->setReturnPath('bounces@address.tld') // bounce back notification
		if ( !empty( $cc_emails ) )
		{
			$message->setCc( $cc_emails );
		}
		if ( !empty( $bcc_emails ) )
		{
			$message->setBcc( $bcc_emails );
		}
		$message->setBody( $body_text );
		// And optionally an alternative body
		$message->addPart( $body_html, 'text/html' ); // Optionally add any attachments
//			$message->attach(Swift_Attachment::fromPath('my-document.pdf'))
		;

		// Send the message
		// Create the Mailer using your created Transport
		$count = Swift_Mailer::newInstance( $this->_transport )
			->send( $message, $failures );
		if ( !empty( $failures ) )
		{
			throw new Exception( 'Failed to send to the following addresses:' . print_r( $failures, true ), ErrorCodes::BAD_REQUEST );
		}

		return $count;
	}

	// Email helpers - Received Format Options
	/*
	{
		"email_single": "lee@dreamfactory.com",
		"email_single_personal": {
			"name": "Lee Hicks",
			"email": "lee@dreamfactory.com"
		},
		"email_multiple": [
			"lee@dreamfactory.com",
			{
				"email": "lee@dreamfactory.com"
			},
			"lee@dreamfactory.com"
			{
				"name": "Lee Hicks",
				"email": "lee@dreamfactory.com"
			}
		]
	}
	 */
	private static function sanitizeAndValidateEmails( $emails, $return_format = '' )
	{
		if ( is_array( $emails ) )
		{
			if ( isset( $emails[0] ) ) // multiple
			{
				$out = array();
				foreach ($emails as $info)
				{
					if ( is_array( $info ) )
					{
						$email = Utilities::getArrayValue('email', $info );
						$email = filter_var( $email, FILTER_SANITIZE_EMAIL );
						if ( false === filter_var( $email, FILTER_VALIDATE_EMAIL ) )
						{
							throw new Exception( "Invalid email - '$email'.", ErrorCodes::BAD_REQUEST );
						}
						if (empty($email))
						{
							throw new Exception('Email can not be empty.', ErrorCodes::BAD_REQUEST );
						}
						$name = Utilities::getArrayValue('name', $emails );
						if ( empty($name))
						{
							$out[] = $email;
						}
						else
						{
							switch ($return_format)
							{
								case 'swift':
									$out[$email] = $name;
									break;
								case 'wrapped': // rfc2822
									$out[] = $name . '<' . $email .'>';
									break;
								default:
									$out[] = $info;
							}
						}
					}
					else // simple email addresses
					{
						$info = filter_var( $info, FILTER_SANITIZE_EMAIL );
						if ( false === filter_var( $info, FILTER_VALIDATE_EMAIL ) )
						{
							throw new Exception( "Invalid email - '$info'.", ErrorCodes::BAD_REQUEST );
						}
						$out[] = $info;
					}
				}
			}
			else // single pair
			{
				$email = Utilities::getArrayValue('email', $emails );
				$email = filter_var( $email, FILTER_SANITIZE_EMAIL );
				if ( false === filter_var( $email, FILTER_VALIDATE_EMAIL ) )
				{
					throw new Exception( "Invalid email - '$email'.", ErrorCodes::BAD_REQUEST );
				}
				if (empty($email))
				{
					throw new Exception('Email can not be empty.', ErrorCodes::BAD_REQUEST );
				}
				$name = Utilities::getArrayValue('name', $emails );
				if ( empty($name))
				{
					$out = $email;
				}
				else
				{
					switch ($return_format)
					{
						case 'swift':
							$out = array($email => $name);
							break;
						case 'wrapped': // rfc2822
							$out = $name . '<' . $email .'>';
							break;
						default:
							$out = $emails;
					}
				}
			}
		}
		else
		{
			// simple single email
			$emails = filter_var( $emails, FILTER_SANITIZE_EMAIL );
			if ( false === filter_var( $emails, FILTER_VALIDATE_EMAIL ) )
			{
				throw new Exception( "Invalid email - '$emails'.", ErrorCodes::BAD_REQUEST );
			}
			$out = $emails;
		}

		return $out;
	}
}
