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
/**
 * EmailSvc
 * A service to handle email services accessed through the REST API.
 */
class EmailSvc extends RestService
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var boolean
	 */
	protected $_isNative = false;
	/**
	 * @var null|Swift_SmtpTransport|Swift_SendMailTransport|Swift_MailTransport
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
	 * @param bool  $native
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $config, $native = false )
	{
		parent::__construct( $config );

		$this->_isNative = $native;
		$transportType = Utilities::getArrayValue( 'storage_type', $config, '' );
		$credentials = Utilities::getArrayValue( 'credentials', $config, array() );
		// Create the Transport
		$this->_transport = EmailUtilities::createTransport( $transportType, $credentials );

		$parameters = Utilities::getArrayValue( 'parameters', $config, array() );
		foreach ( $parameters as $param )
		{
			$key = Utilities::getArrayValue( 'name', $param );
			$value = Utilities::getArrayValue( 'value', $param );
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
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		$apis = array(
			array(
				'path'        => '/' . $this->_apiName,
				'description' => $this->_description,
				'operations'  => array(
					array(
						"httpMethod"     => "POST",
						"summary"        => "Send an email created from posted data.",
						"notes"          => "If a template is not used with all required fields, then they must be included in the request." .
											" If the 'from' address is not provisioned in the service, then it must be included in the request.",
						"responseClass"  => "Response",
						"nickname"       => "sendEmail",
						"parameters"     => array(
							array(
								"paramType"     => "query",
								"name"          => "template",
								"description"   => "Optional template to base email on.",
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
							),
							array(
								"code"   => 500,
								"reason" => "Failure to send emails."
							)
						)
					)
				)
			)
		);

		return $apis;
	}

	/**
	 * @return array
	 */
	public function getSwaggerModels()
	{
		$models = array(
			"Email"    => array(
				"id"         => "Email",
				"properties" => array(
					"template"       => array(
						"type"        => "string",
						"description" => "Email Template to base email on."
					),
					"to"             => array(
						"type"        => "array",
						"items"       => array( '$ref' => "Address" ),
						"description" => "Required single or multiple receiver addresses."
					),
					"cc"             => array(
						"type"        => "array",
						"items"       => array( '$ref' => "Address" ),
						"description" => "Optional CC receiver addresses."
					),
					"bcc"            => array(
						"type"        => "array",
						"items"       => array( '$ref' => "Address" ),
						"description" => "Optional BCC receiver addresses."
					),
					"subject"        => array(
						"type"        => "string",
						"description" => "Text only subject line."
					),
					"body_text"      => array(
						"type"        => "string",
						"description" => "Text only version of the body."
					),
					"body_html"      => array(
						"type"        => "string",
						"description" => "Escaped HTML version of the body."
					),
					"from_name"      => array(
						"type"        => "string",
						"description" => "Optional sender displayed name."
					),
					"from_email"     => array(
						"type"        => "string",
						"description" => "Required email of the sender."
					),
					"reply_to_name"  => array(
						"type"        => "string",
						"description" => "Optional reply to displayed name."
					),
					"reply_to_email" => array(
						"type"        => "string",
						"description" => "Optional reply to email."
					),
				)
			),
			"Address"  => array(
				"id"         => "Address",
				"properties" => array(
					"name"  => array(
						"type"        => "string",
						"description" => "Optional name displayed along with the email address."
					),
					"email" => array(
						"type"        => "string",
						"description" => "Required email address."
					)
				)
			),
			"Response" => array(
				"id"         => "Response",
				"properties" => array(
					"count" => array(
						"type"        => "integer",
						"description" => "Number of emails successfully sent."
					)
				)
			)
		);
		$models = array_merge( parent::getSwaggerModels(), $models );

		return $models;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
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
}
