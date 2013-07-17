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
namespace Platform\Utility;

use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;

//	Load up SwiftMailer
\Yii::registerAutoloader( array( 'Swift', 'autoload' ) );
//require_once '../../../vendor/swiftmailer/swiftmailer/lib/swift_required.php';
// or require_once '../../../vendor/swiftmailer/swiftmailer/lib/swift_init.php';

/**
 * EmailUtilities
 * A utility class with email capabilities.
 */
class EmailUtilities
{
	/**
	 * Create a SwiftMailer Transport
	 *
	 * @param string $type
	 * @param array  $credentials
	 *
	 * @throws \InvalidArgumentException
	 * @return \Swift_MailTransport|\Swift_SendmailTransport|\Swift_SmtpTransport
	 */
	public static function createTransport( $type = '', $credentials = array() )
	{
		switch ( $type )
		{
			case null:
			case '':
				// mail()
				$transport = \Swift_MailTransport::newInstance();
				break;
			case 'smtp':
			case 'SMTP':
				// SMTP
				$host = Option::get( $credentials, 'host', 'localhost' );
				if ( empty( $host ) )
				{
					throw new \InvalidArgumentException( 'SMTP host name can not be empty.' );
				}
				$port = Option::get( $credentials, 'port', 25 );
				$security = Option::get( $credentials, 'security', null );
				$security = ( !empty( $security ) ) ? strtolower($security) : null;
				$transport = \Swift_SmtpTransport::newInstance( $host, $port, $security );
				$user = Option::get( $credentials, 'user', '' );
				$pwd = Option::get( $credentials, 'pwd', '' );
				if ( !empty( $user ) && !empty( $pwd ) )
				{
					$transport->setUsername( $user );
					$transport->setPassword( $pwd );
				}
				break;
			default:
				// use local process, i.e. sendmail, exim, postscript, etc
				$transport = \Swift_SendmailTransport::newInstance( $type );
				break;
		}

		return $transport;
	}

	/**
	 * SwiftMailer create message
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
	 * @return int
	 */
	public static function createMessage( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
										  $body_html = '', $from_name = '', $from_email = '',
										  $reply_name = '', $reply_email = '' )
	{
		// Create the message
		$message = \Swift_Message::newInstance()
			->setSubject( $subject )
			->setTo( $to_emails ) // array('receiver@domain.org', 'other@domain.org' => 'A name')
			->setFrom( $from_email, $from_name ); // can be multiple
		if ( !empty( $reply_email ) )
		{
			$message->setReplyTo( $reply_email, $reply_name ); // single address
		}
//		$message->setSender( $reply_email, $reply_name ); // single address
//		$message->setReturnPath('bounces@address.tld') // bounce back notification
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
//		$message->attach(Swift_Attachment::fromPath('my-document.pdf'));

		return $message;
	}

	public static function sendMessage( $transport, $message )
	{
		// Send the message
		// Create the Mailer using your created Transport
		$count = \Swift_Mailer::newInstance( $transport )->send( $message, $failures );
		if ( !empty( $failures ) )
		{
			throw new InternalServerErrorException( 'Failed to send to the following addresses:' . print_r( $failures, true ) );
		}

		return $count;
	}

	// Email helpers - Received Format Options
	/*
	{
		"email_single": "support@dreamfactory.com",
		"email_single_personal": {
			"name": "Developer Support",
			"email": "support@dreamfactory.com"
		},
		"email_multiple": [
			"sales@dreamfactory.com",
			{
				"email": "support@dreamfactory.com"
			},
			{
				"name": "Developer Support",
				"email": "support@dreamfactory.com"
			}
		]
	}
	 */
	public static function sanitizeAndValidateEmails( $emails, $return_format = '' )
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
						$email = Option::get( $info, 'email' );
						$email = filter_var( $email, FILTER_SANITIZE_EMAIL );
						if ( false === filter_var( $email, FILTER_VALIDATE_EMAIL ) )
						{
							throw new BadRequestException( "Invalid email - '$email'." );
						}
						if (empty($email))
						{
							throw new BadRequestException('Email can not be empty.' );
						}
						$name = Option::get( $info, 'name' );
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
							throw new BadRequestException( "Invalid email - '$info'." );
						}
						$out[] = $info;
					}
				}
			}
			else // single pair
			{
				$email = Option::get( $emails, 'email' );
				$email = filter_var( $email, FILTER_SANITIZE_EMAIL );
				if ( false === filter_var( $email, FILTER_VALIDATE_EMAIL ) )
				{
					throw new BadRequestException( "Invalid email - '$email'." );
				}
				if (empty($email))
				{
					throw new BadRequestException('Email can not be empty.' );
				}
				$name = Option::get( $emails, 'name' );
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
				throw new BadRequestException( "Invalid email - '$emails'." );
			}
			$out = $emails;
		}

		return $out;
	}
}
