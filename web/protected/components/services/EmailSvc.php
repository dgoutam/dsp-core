<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage EmailSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class EmailSvc extends CommonService implements iRestHandler
{
    protected $adminName;
    protected $adminAddress;
    protected $fromName;
    protected $fromAddress;
    protected $replyToName;
    protected $replyToAddress;

    /**
     * Creates a new EmailSvc instance
     *
     * @param array $config
     * @throws InvalidArgumentException
     */
    public function __construct($config)
    {
        parent::__construct($config);

        $company = Yii::app()->params['companyLabel'];

        // Provide the admin email address where you want to get notifications
        $this->adminName = empty($company) ? $_SERVER['SERVER_NAME'] . 'Admin' : $company . ' Admin';
        $this->adminAddress = Utilities::getArrayValue('adminEmail',
                                                       Yii::app()->params,
                                                       'admin@' . $_SERVER['SERVER_NAME']);

        // Provide the from email address to display for all outgoing emails
        $this->fromName = empty($company) ? $_SERVER['SERVER_NAME'] . 'Admin' : $company . ' Admin';
        $this->fromAddress = Utilities::getArrayValue('adminEmail',
                                                      Yii::app()->params,
                                                      'admin@' . $_SERVER['SERVER_NAME']);

        // Provide the reply-to email address where you want users to reply to for support
        $this->replyToName = empty($company) ? $_SERVER['SERVER_NAME'] . 'Support' : $company . ' Support';
        $this->replyToAddress = Utilities::getArrayValue('adminEmail',
                                                         Yii::app()->params,
                                                         'admin@' . $_SERVER['SERVER_NAME']);
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    // Controller based methods

    public function actionPost()
    {
        // get all possibly parameters from request
        $toEmails = Utilities::getArrayValue('to_emails', $_REQUEST, '');
        $ccEmails = Utilities::getArrayValue('cc_emails', $_REQUEST, '');
        $bccEmails = Utilities::getArrayValue('bcc_emails', $_REQUEST, '');
        $subject = Utilities::getArrayValue('subject', $_REQUEST, '');
        $textBody = Utilities::getArrayValue('text_body', $_REQUEST, '');
        $htmlBody = Utilities::getArrayValue('html_body', $_REQUEST, '');
        $fromName = Utilities::getArrayValue('from_name', $_REQUEST, $this->fromName);
        $fromEmail = Utilities::getArrayValue('from_email', $_REQUEST, $this->fromAddress);
        $replyName = Utilities::getArrayValue('reply_name', $_REQUEST, $this->replyToName);
        $replyEmail = Utilities::getArrayValue('reply_email', $_REQUEST, $this->replyToAddress);

        $data = Utilities::getPostDataAsArray();
        if (!empty($data)) {
            // override any parameters with posted data
            $toEmails = Utilities::getArrayValue('to_emails', $data, $toEmails);
            $ccEmails = Utilities::getArrayValue('cc_emails', $data, $ccEmails);
            $bccEmails = Utilities::getArrayValue('bcc_emails', $data, $bccEmails);
            $subject = Utilities::getArrayValue('subject', $data, $subject);
            $textBody = Utilities::getArrayValue('text_body', $data, $textBody);
            $htmlBody = Utilities::getArrayValue('html_body', $data, $htmlBody);
            $fromName = Utilities::getArrayValue('from_name', $data, $fromName);
            $fromEmail = Utilities::getArrayValue('from_email', $data, $fromEmail);
            $replyName = Utilities::getArrayValue('reply_name', $data, $replyName);
            $replyEmail = Utilities::getArrayValue('reply_email', $data, $replyEmail);
        }

        $toEmails = filter_var($toEmails, FILTER_SANITIZE_EMAIL);
        if (false === filter_var($toEmails, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid 'to' email - '$toEmails'.");
        }
        if (!empty($ccEmails)) {
            $ccEmails = filter_var($ccEmails, FILTER_SANITIZE_EMAIL);
            if (false === filter_var($ccEmails, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid 'cc' email - '$ccEmails'.");
            }
        }
        if (!empty($bccEmails)) {
            $bccEmails = filter_var($bccEmails, FILTER_SANITIZE_EMAIL);
            if (false === filter_var($bccEmails, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid 'bcc' email - '$bccEmails'.");
            }
        }
        if (!empty($fromEmail)) {
            $fromEmail = filter_var($fromEmail, FILTER_SANITIZE_EMAIL);
            if (false === filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid 'from' email - '$fromEmail'.");
            }
        }
        if (!empty($replyEmail)) {
            $replyEmail = filter_var($replyEmail, FILTER_SANITIZE_EMAIL);
            if (false === filter_var($replyEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid 'reply' email - '$replyEmail'.");
            }
        }

        // todo do subject and body text substitution

        $result = $this->sendEmailPhp($toEmails, $ccEmails, $bccEmails, $subject, $textBody,
                                      $htmlBody, $fromName, $fromEmail, $replyName, $replyEmail);
        return $result;
    }

    public function actionPut()
    {
        throw new Exception("PUT Request is not supported by this Email API.");
    }

    public function actionMerge()
    {
        throw new Exception("MERGE Request is not supported by this Email API.");
    }

    public function actionDelete()
    {
        throw new Exception("DELETE Request is not supported by this Email API.");
    }

    public function actionGet()
    {
        throw new Exception("GET Request is not supported by this Email API.");
    }

    //------- Email Helpers ----------------------

    /**
     * Email Web Service Send Email API
     *
     * @param string $to_emails   comma-delimited list of receiver addresses
     * @param string $cc_emails   comma-delimited list of CC'd addresses
     * @param string $bcc_emails  comma-delimited list of BCC'd addresses
     * @param string $subject     Text only subject line
     * @param string $text_body   Text only version of the email body
     * @param string $html_body   Escaped HTML version of the email body
     * @param string $from_name   Name displayed for the sender
     * @param string $from_email  Email displayed for the sender
     * @param string $reply_name  Name displayed for the reply to
     * @param string $reply_email Email used for the sender reply to
     * @return bool|string
     */
    public function sendEmailPhp($to_emails, $cc_emails, $bcc_emails, $subject, $text_body,
                                 $html_body='', $from_name='', $from_email='', $reply_name='', $reply_email='')
    {
        // support utf8
        $fromName = '=?UTF-8?B?' . base64_encode($from_name) . '?=';
        $replyName = '=?UTF-8?B?' . base64_encode($reply_name) . '?=';
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers  = '';
        $headers .= "From: $fromName <{$from_email}>\r\n";
        $headers .= "Reply-To: $replyName <{$reply_email}>\r\n";
//        $headers .= "To: $to_emails" . "\r\n";
        if (!empty($cc_emails))
            $headers .= "Cc: $cc_emails" . "\r\n";
        if (!empty($bcc_emails))
            $headers .= "Bcc: $bcc_emails" . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        if (!empty($html_body)) {
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $body = $html_body;
        }
        else {
            $headers .= 'Content-type: text/plain; charset=UTF-8' . "\r\n";
            $body = $text_body;
        }

        $result = mail($to_emails, $subject, $body, $headers);
        if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
            $msg = "Error: Failed to send email.";
            if (is_string($result)) {
                $msg .= "\n$result";
            }

            return $msg;
        }

        return true;
    }

    /**
     * Email Web Service Send Email API
     *
     * @param string $to_emails   comma-delimited list of receiver addresses
     * @param string $cc_emails   comma-delimited list of CC'd addresses
     * @param string $bcc_emails  comma-delimited list of BCC'd addresses
     * @param string $subject     Text only subject line
     * @param string $text_body   Text only version of the email body
     * @param string $html_body   Escaped HTML version of the email body
     * @param string $from_name   Name displayed for the sender
     * @param string $from_email  Email displayed for the sender
     * @param string $reply_name  Name displayed for the reply to
     * @param string $reply_email Email used for the sender reply to
     * @return bool|string
     */
    public function sendEmail($to_emails, $cc_emails, $bcc_emails, $subject, $text_body, $html_body='', $from_name='', $from_email='', $reply_name='', $reply_email='')
    {
        if (empty($html_body)) {
            $html_body = str_replace("\r\n", "<br />", $text_body);
        }
        if (empty($from_name)) {
            $from_name = $this->getFromName();
        }
        if (empty($from_email)) {
            $from_email = $this->getFromAddress();
        }
        if (empty($reply_name)) {
            $reply_name = $this->getReplyToName();
        }
        if (empty($reply_email)) {
            $reply_email = $this->getReplyToAddress();
        }

        // for now run with the Soap version
        $result = $this->sendEmailSoap($from_name, $from_email, $reply_name, $reply_email, $to_emails, $cc_emails, $bcc_emails, $subject, $text_body, $html_body);
        if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
            $msg = "Error: Failed to send email.";
            if (is_string($result)) {
                $msg .= "\n$result";
            }

            return $msg;
        }

        return true;
    }

    public function sendEmailToAdmin($cc_emails, $bcc_emails, $subject, $text_body, $html_body='', $from_name='', $from_email='', $reply_name='', $reply_email='')
    {
        if (empty($this->adminAddress)) {
            return true;    // admin not configured
        }

        if (empty($html_body)) {
            $html_body = str_replace("\r\n", "<br />", $text_body);
        }
        if (empty($from_name)) {
            $from_name = $this->getFromName();
        }
        if (empty($from_email)) {
            $from_email = $this->getFromAddress();
        }
        if (empty($reply_name)) {
            $reply_name = $this->getReplyToName();
        }
        if (empty($reply_email)) {
            $reply_email = $this->getReplyToAddress();
        }

        // for now run with the Soap version
        $result = $this->sendEmailSoap($from_name, $from_email, $reply_name, $reply_email, $this->adminAddress, $cc_emails, $bcc_emails, $subject, $text_body, $html_body);
        if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
            $msg = "Error: Failed to send email.";
            if (is_string($result)) {
                $msg .= "\n$result";
            }

            return $msg;
        }

        return true;
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
     * @param string $text_body   Text only version of the email body
     * @param string $html_body   Escaped HTML version of the email body
     * @return mixed
     */
    private function sendEmailCurl($from_name, $from_email, $reply_name, $reply_email, $to_emails, $cc_emails, $bcc_emails, $subject, $text_body, $html_body)
    {
        $url = 'http://www.dreamfactory.net/mail/dfemail.php';
        $thesoap = '<?xml version="1.0" encoding="utf-8"?>
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
                    <text_body xsi:type="xsd:string">' . $text_body . '</text_body>
                    <html_body xsi:type="xsd:string">' . $html_body . '</html_body>
                    <att_name xsi:type="xsd:string"></att_name>
                    <att_body xsi:type="xsd:string"></att_body>
                </m:send_email>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>';

        // Generate curl request
        $session = curl_init($url);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "SOAPAction: 'dreamfactory-send-email'"));

        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, $thesoap);

        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        // For Debug mode; enable Fiddler proxy
        curl_setopt($session, CURLOPT_PROXY, '127.0.0.1:8888');
        // For Debug mode; shows up any error encountered during the operation
        curl_setopt($session, CURLOPT_VERBOSE, 1);

        // obtain response
        $response = curl_exec($session);
        curl_close($session);

        // print everything out
        return $response;
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
     * @param string $text_body   Text only version of the email body
     * @param string $html_body   Escaped HTML version of the email body
     * @return string
     * @throws Exception
     */
    private function sendEmailSoap($from_name, $from_email, $reply_name, $reply_email, $to_emails, $cc_emails, $bcc_emails, $subject, $text_body, $html_body)
    {
        try {
            $client = @new SoapClient('http://www.dreamfactory.net/mail/dfemail.wsdl', array("exceptions" => 1));
        } catch (SoapFault $s) {
            throw new Exception("Error: {$s->faultstring}.");
        }

        try {
            $result = $client->send_email($from_name, $from_email, $reply_name, $reply_email, $to_emails, $cc_emails, $bcc_emails, $subject, $text_body, $html_body);

            return $result;
        } catch (Exception $e) {
            throw new Exception("Error: {$e->getMessage()}.");
        }
    }
}
