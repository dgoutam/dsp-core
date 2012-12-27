<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage EmailSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class EmailSvc
{
    protected $adminName;
    protected $adminAddress;
    protected $fromName;
    protected $fromAddress;
    protected $replyToName;
    protected $replyToAddress;

    /**
     * Creates a new EmailSvc instance
     */
    public function __construct()
    {
        $company = Yii::app()->params['companyLabel'];

        // Provide the admin email address where you want to get notifications
        $this->adminName = empty($company) ? 'Admin' : $company . ' Admin';
        $this->adminAddress = Yii::app()->params['adminEmail'];

        // Provide the from email address to display for all outgoing emails
        $this->fromName = empty($company) ? 'Admin' : $company . ' Admin';
        $this->fromAddress = Yii::app()->params['adminEmail'];

        // Provide the reply-to email address where you want users to reply to for support
        $this->replyToName = empty($company) ? 'Support' : $company . ' Support';
        $this->replyToAddress = Yii::app()->params['adminEmail'];
    }

    //------- Email Helpers ----------------------

    protected function getFromName()
    {
        if (!empty($this->fromName)) { return $this->fromName; }

        // make up something if not set
        return $_SERVER['SERVER_NAME'] . ' Admin';
    }

    protected function getFromAddress()
    {
        if (!empty($this->fromAddress)) { return $this->fromAddress; }

        // make up something if not set
        return "admin@" . $_SERVER['SERVER_NAME'];
    }

    protected function getReplyToName()
    {
        if (!empty($this->replyToName)) { return $this->replyToName; }

        // make up something if not set
        return $_SERVER['SERVER_NAME'] . ' Support';
    }

    protected function getReplyToAddress()
    {
        if (!empty($this->replyToAddress)) { return $this->replyToAddress; }

        // make up something if not set
        return "support@" . $_SERVER['SERVER_NAME'];
    }

    protected function getAdminName()
    {
        if (!empty($this->adminName)) { return $this->adminName; }

        // make up something if not set
        return $_SERVER['SERVER_NAME'] . ' Admin';
    }

    protected function getAdminAddress()
    {
        if (!empty($this->adminAddress)) { return $this->adminAddress; }

        // make up something if not set
        return "admin@" . $_SERVER['SERVER_NAME'];
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
