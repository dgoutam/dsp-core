<?php

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
	protected $fromName;
	protected $fromAddress;
	protected $replyToName;
	protected $replyToAddress;

	/**
	 * Create a new EmailSvc
	 *
	 * @param array $config
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		$parameters = Utilities::getArrayValue( 'parameters', $config, array() );

		// Provide the from email address to display for all outgoing emails
		$this->fromName = Utilities::getArrayValue( 'from_name', $parameters, $_SERVER['SERVER_NAME'] . 'Admin' );
		$this->fromAddress = Utilities::getArrayValue( 'from_email', $parameters, 'admin@' . $_SERVER['SERVER_NAME'] );

		// Provide the reply-to email address where you want users to reply to for support
		$this->replyToName = Utilities::getArrayValue( 'reply_to_name', $parameters, $_SERVER['SERVER_NAME'] . 'Admin' );
		$this->replyToAddress = Utilities::getArrayValue( 'reply_to_email', $parameters, 'admin@' . $_SERVER['SERVER_NAME'] );
	}

	// Controller based methods

	/**
	 *
	 * @return array
	 * @throws Exception
	 */
	public function actionSwagger()
	{
		try
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
				"Email" => array(
					"id" => "Email",
					"properties" => array(
						"template" => array(
							"type" => "string",
							"description" => "Email Template to base email on."
						),
						"to" => array(
							"type" => "string",
							"description" => "Comma-delimited list of receiver addresses."
						),
						"cc" => array(
							"type" => "string",
							"description" => "Comma-delimited list of CC receiver addresses."
						),
						"bcc" => array(
							"type" => "string",
							"description" => "Comma-delimited list of BCC receiver addresses."
						),
						"subject" => array(
							"type" => "string",
							"description" => "Text only subject line."
						),
						"body_text" => array(
							"type" => "string",
							"description" => "Text only version of the email body."
						),
						"body_html" => array(
							"type" => "string",
							"description" => "Escaped HTML version of the email body."
						),
						"from_name" => array(
							"type" => "string",
							"description" => "Name displayed for the sender."
						),
						"from_email" => array(
							"type" => "string",
							"description" => "Email displayed for the sender."
						),
						"reply_to_name" => array(
							"type" => "string",
							"description" => "Name displayed for the reply to."
						),
						"reply_to_email" => array(
							"type" => "string",
							"description" => "Email displayed for the reply to."
						),
					)
				),
				"Response" => array(
					"id" => "Response",
					"properties" => array(
						"success" => array(
							"type" => "boolean"
						)
					)
				)
			);

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	public function actionPost()
	{
		$data = Utilities::getPostDataAsArray();

		// build email from posted data
		$template = Utilities::getArrayValue( 'template', $_REQUEST );
		$template = Utilities::getArrayValue( 'template', $data, $template );
		if ( !empty( $template ) )
		{
			$this->sendEmailByTemplate( $template, $data );
		}
		else
		{
			if ( empty( $data ) )
			{
				throw new Exception( "No POST data in request." );
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
			$this->sendEmail( $to, $cc,	$bcc, $subject, $text, $html,
							  $fromName, $fromEmail, $replyName, $replyEmail );
		}

		return array( 'success' => true );
	}

	public function actionPut()
	{
		throw new Exception( "PUT Request is not supported by this Email API." );
	}

	public function actionMerge()
	{
		throw new Exception( "MERGE Request is not supported by this Email API." );
	}

	public function actionDelete()
	{
		throw new Exception( "DELETE Request is not supported by this Email API." );
	}

	public function actionGet()
	{
		throw new Exception( "GET Request is not supported by this Email API." );
	}

	//------- Email Methods ----------------------

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
		$this->sendEmail( $to, $cc,	$bcc, $subject, $text, $html,
						  $fromName, $fromEmail, $replyName, $replyEmail, $data );
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
	 * @param array  $data        Name-Value pairs for replaceable data in subject an body
	 *
	 * @throws Exception
	 * @return null
	 */
	public function sendEmail( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
							   $body_html = '', $from_name = '', $from_email = '',
							   $reply_name = '', $reply_email = '', $data = array() )
	{
		if ( empty( $from_name ) )
		{
			$from_name = $this->fromName;
		}
		if ( empty( $from_email ) )
		{
			$from_email = $this->fromAddress;
		}
		if ( empty( $reply_name ) )
		{
			$reply_name = $this->replyToName;
		}
		if ( empty( $reply_email ) )
		{
			$reply_email = $this->replyToAddress;
		}

		$to_emails = filter_var( $to_emails, FILTER_SANITIZE_EMAIL );
		if ( false === filter_var( $to_emails, FILTER_VALIDATE_EMAIL ) )
		{
			throw new Exception( "Invalid 'to' email - '$to_emails'." );
		}
		if ( !empty( $cc_emails ) )
		{
			$cc_emails = filter_var( $cc_emails, FILTER_SANITIZE_EMAIL );
			if ( false === filter_var( $cc_emails, FILTER_VALIDATE_EMAIL ) )
			{
				throw new Exception( "Invalid 'cc' email - '$cc_emails'." );
			}
		}
		if ( !empty( $bcc_emails ) )
		{
			$bcc_emails = filter_var( $bcc_emails, FILTER_SANITIZE_EMAIL );
			if ( false === filter_var( $bcc_emails, FILTER_VALIDATE_EMAIL ) )
			{
				throw new Exception( "Invalid 'bcc' email - '$bcc_emails'." );
			}
		}
		if ( !empty( $from_email ) )
		{
			$from_email = filter_var( $from_email, FILTER_SANITIZE_EMAIL );
			if ( false === filter_var( $from_email, FILTER_VALIDATE_EMAIL ) )
			{
				throw new Exception( "Invalid 'from' email - '$from_email'." );
			}
		}
		if ( !empty( $reply_email ) )
		{
			$reply_email = filter_var( $reply_email, FILTER_SANITIZE_EMAIL );
			if ( false === filter_var( $reply_email, FILTER_VALIDATE_EMAIL ) )
			{
				throw new Exception( "Invalid 'reply' email - '$reply_email'." );
			}
		}

		// do template field replacement
		if ( !empty( $data ) )
		{
			foreach ( $data as $name => $value )
			{
				// replace {xxx} in subject
				$subject = str_replace( '{'.$name.'}', $value, $subject );
				// replace {xxx} in body - text and html
				$body_text = str_replace( '{'.$name.'}', $value, $body_text );
				$body_html = str_replace( '{'.$name.'}', $value, $body_html );
			}
		}
		if ( empty( $body_html ) )
		{
			$body_html = str_replace( "\r\n", "<br />", $body_text );
		}

		static::sendByPhpMail( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
							   $body_html, $from_name, $from_email, $reply_name, $reply_email );
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
	 * @return null
	 */
	private static function sendByPhpMail( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
										   $body_html = '', $from_name = '', $from_email = '',
										   $reply_name = '', $reply_email = '' )
	{
		// support utf8
		$fromName = '=?UTF-8?B?' . base64_encode( $from_name ) . '?=';
		$replyName = '=?UTF-8?B?' . base64_encode( $reply_name ) . '?=';
		$subject = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';

		$headers = '';
		$headers .= "From: $fromName <{$from_email}>\r\n";
		$headers .= "Reply-To: $replyName <{$reply_email}>\r\n";
//        $headers .= "To: $to_emails" . "\r\n";
		if ( !empty( $cc_emails ) )
		{
			$headers .= 'Cc: ' . $cc_emails . "\r\n";
		}
		if ( !empty( $bcc_emails ) )
		{
			$headers .= 'Bcc: ' . $bcc_emails . "\r\n";
		}
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		if ( !empty( $body_html ) )
		{
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$body = $body_html;
		}
		else
		{
			$headers .= 'Content-type: text/plain; charset=UTF-8' . "\r\n";
			$body = $body_text;
		}

		$result = mail( $to_emails, $subject, $body, $headers );
		if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
		{
			$msg = 'Error: Failed to send email.';
			if ( is_string( $result ) )
			{
				$msg .= "\n$result";
			}
			throw new Exception( $msg );
		}
	}

	/**
	 * Email Web Service Send Email API via CURL
	 *
	 * @param string $from_name   Name displayed for the sender
	 * @param string $from_email  Email displayed for the sender
	 * @param string $reply_name  Name displayed for the reply to
	 * @param string $reply_email Email used for the sender reply to
	 * @param string $to_emails   comma-delimited list of receiver addresses
	 * @param string $cc_emails   comma-delimited list of CC'd addresses
	 * @param string $bcc_emails  comma-delimited list of BCC'd addresses
	 * @param string $subject     Text only subject line
	 * @param string $body_text   Text only version of the email body
	 * @param string $body_html   Escaped HTML version of the email body
	 *
	 * @throws Exception
	 * @return null
	 */
	private static function sendByDfCurl( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
										  $body_html = '', $from_name = '', $from_email = '',
										  $reply_name = '', $reply_email = '' )
	{
		$url = 'http://www.dreamfactory.net/mail/dfemail.php';
		$thesoap
			= '<?xml version="1.0" encoding="utf-8"?>
        <SOAP-ENV:Envelope
            xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
            xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <SOAP-ENV:Body>
                <m:send_email
                    xmlns:m="urn:dreamfactory-send-email"
                    SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                    <from_name xsi:type="xsd:string">' . $from_name . '</from_name>
                    <from_address xsi:type="xsd:string">' . $from_email . '</from_address>
                    <replyto_name xsi:type="xsd:string">' . $reply_name . '</replyto_name>
                    <replyto_address xsi:type="xsd:string">' . $reply_email . '</replyto_address>
                    <to_email_list xsi:type="xsd:string">' . $to_emails . '</to_email_list>
                    <cc_email_list xsi:type="xsd:string">' . $cc_emails . '</cc_email_list>
                    <bcc_email_list xsi:type="xsd:string">' . $bcc_emails . '</bcc_email_list>
                    <subject xsi:type="xsd:string">' . $subject . '</subject>
                    <text_body xsi:type="xsd:string">' . $body_text . '</text_body>
                    <html_body xsi:type="xsd:string">' . $body_html . '</html_body>
                    <att_name xsi:type="xsd:string"></att_name>
                    <att_body xsi:type="xsd:string"></att_body>
                </m:send_email>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>';

		// Generate curl request
		$session = curl_init( $url );

		// Tell curl to use HTTP POST
		curl_setopt( $session, CURLOPT_POST, true );
		curl_setopt( $session, CURLOPT_HTTPHEADER, array( "Content-Type: text/xml; charset=utf-8", "SOAPAction: 'dreamfactory-send-email'" ) );

		// Tell curl that this is the body of the POST
		curl_setopt( $session, CURLOPT_POSTFIELDS, $thesoap );

		// Tell curl not to return headers, but do return the response
		curl_setopt( $session, CURLOPT_HEADER, false );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

		// For Debug mode; enable Fiddler proxy
		curl_setopt( $session, CURLOPT_PROXY, '127.0.0.1:8888' );
		// For Debug mode; shows up any error encountered during the operation
		curl_setopt( $session, CURLOPT_VERBOSE, 1 );

		// obtain response
		$result = curl_exec( $session );
		curl_close( $session );

		if ( !filter_var( $result, FILTER_VALIDATE_BOOLEAN ) )
		{
			$msg = "Error: Failed to send email.";
			if ( is_string( $result ) )
			{
				$msg .= "\n$result";
			}
			throw new Exception( $msg );
		}
	}

	/**
	 * Email Web Service Send Email API via SoapClient
	 *
	 * @param string $from_name   Name displayed for the sender
	 * @param string $from_email  Email displayed for the sender
	 * @param string $reply_name  Name displayed for the reply to
	 * @param string $reply_email Email used for the sender reply to
	 * @param string $to_emails   comma-delimited list of receiver addresses
	 * @param string $cc_emails   comma-delimited list of CC'd addresses
	 * @param string $bcc_emails  comma-delimited list of BCC'd addresses
	 * @param string $subject     Text only subject line
	 * @param string $body_text   Text only version of the email body
	 * @param string $body_html   Escaped HTML version of the email body
	 *
	 * @return null
	 * @throws Exception
	 */
	private static function sendByDfSoap( $to_emails, $cc_emails, $bcc_emails, $subject, $body_text,
										  $body_html = '', $from_name = '', $from_email = '',
										  $reply_name = '', $reply_email = '' )
	{
		try
		{
			$client = @new SoapClient( 'http://www.dreamfactory.net/mail/dfemail.wsdl', array( "exceptions" => 1 ) );
		}
		catch ( SoapFault $s )
		{
			throw new Exception( "Error: {$s->faultstring}." );
		}

		try
		{
			$result = $client->send_email(
				$from_name,
				$from_email,
				$reply_name,
				$reply_email,
				$to_emails,
				$cc_emails,
				$bcc_emails,
				$subject,
				$body_text,
				$body_html
			);
		}
		catch ( Exception $e )
		{
			throw new Exception( "Error: {$e->getMessage()}." );
		}
	}
}
