<?php
/**
 * Copyright (c) 2009 - 2012, DreamFactory Software, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of DreamFactory nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY DreamFactory ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL DreamFactory BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage UserManager
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory Software, Inc. (http://www.dreamfactory.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: Services.php 66505 2012-04-02 08:45:51Z unknown $
 */

//use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(
 *     apiVersion="0.2",
 *     swaggerVersion="1.1",
 *     resourcePath="/user",
 *     basePath="{{swagger_url}}"
 * )
 */

/**
 *
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
    protected $siteName;

    /**
     * @var string
     */
    protected $randKey;

    /**
     * Creates a new UserManager instance
     *
     */
    public function __construct()
    {
        $this->siteName = Yii::app()->params['companyLabel'];
        if (empty($this->siteName)) {
            $this->siteName = $_SERVER['HTTP_HOST'];
        }

        //For better security. Get a random string from this link: http://tinyurl.com/randstr and put it here
        $this->randKey = 'M1kVi0kE9ouXxF9';
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
        if (!isset(self::$_instance)) {
            self::$_instance = new UserManager();
        }

        return self::$_instance;
    }

    /**
     * For compatibility with CommonServices
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
        $result = SwaggerUtilities::swaggerBaseInfo('user');
        $resources = array(
            array('path' => '/user',
                 'description' => "Operations on the user service",
                 'operations' => array(
                     array("httpMethod"=> "GET",
                           "summary"=> "List resources available in the user service",
                           "notes"=> "Use these resources to maintain session, update profile and change password.",
                           "responseClass"=> "array",
                           "nickname"=> "getResources",
                           "parameters"=> array(),
                           "errorResponses"=> array()
                     )
                 )
            ),
            array('path' => '/user/session',
                 'description' => "Operations on a user's session",
                 'operations' => array(
                     array("httpMethod"=> "GET",
                           "summary"=> "Retrieve the current user session information",
                           "notes"=> "Calling this refreshes the current session.",
                           "responseClass"=> "array",
                           "nickname"=> "getSession",
                           "parameters"=> array(),
                           "errorResponses"=> array()
                     ),
                     array("httpMethod"=> "POST",
                           "summary"=> "Login and create a new user session",
                           "notes"=> "Calling this creates a new session and logs in the user.",
                           "responseClass"=> "array",
                           "nickname"=> "login",
                           "parameters"=> array(),
                           "errorResponses"=> array()
                     ),
                     array("httpMethod"=> "DELETE",
                           "summary"=> "Logout and destroy the current user session",
                           "notes"=> "Calling this deletes the current session and logs out the user.",
                           "responseClass"=> "array",
                           "nickname"=> "logout",
                           "parameters"=> array(),
                           "errorResponses"=> array()
                     ),
                 )
            ),
            array('path' => '/user/profile',
                  'description' => "Operations on a user's profile",
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve the current user's profile information",
                            "notes"=> "This profile, along with password, is the only things that the user can directly change.",
                            "responseClass"=> "array",
                            "nickname"=> "getProfile",
                            "parameters"=> array(),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Update the current user's profile information",
                            "notes"=> "Update the security question and answer through this api, as well as, display name, email, etc.",
                            "responseClass"=> "array",
                            "nickname"=> "changeProfile",
                            "parameters"=> array(),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/user/password',
                  'description' => "Operations on a user's password",
                  'operations' => array(
                      array("httpMethod"=> "POST",
                            "summary"=> "Update the current user's password",
                            "notes"=> "A valid session is required to change the password through this API.",
                            "responseClass"=> "array",
                            "nickname"=> "changePassword",
                            "parameters"=> array(),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/user/challenge',
                  'description' => '',
                  'operations' => array(
                      array("httpMethod"=> "GET",
                            "summary"=> "Retrieve the security challenge question for the given username",
                            "notes"=> "Use this to retrieve the challenge question to present to the user.",
                            "responseClass"=> "array",
                            "nickname"=> "getChallenge",
                            "parameters"=> array(),
                            "errorResponses"=> array()
                      ),
                      array("httpMethod"=> "POST",
                            "summary"=> "Answer the security challenge question for the given username",
                            "notes"=> "Use this to gain temporary access to change password.",
                            "responseClass"=> "array",
                            "nickname"=> "answerChallenge",
                            "parameters"=> array(),
                            "errorResponses"=> array()
                      ),
                  )
            ),
            array('path' => '/user/register',
                  'description' => '',
                  'operations' => array()
            ),
            array('path' => '/user/confirm',
                  'description' => '',
                  'operations' => array()
            ),
            array('path' => '/user/ticket',
                  'description' => '',
                  'operations' => array()
            )
        );
        $result['apis'] = $resources;

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionGet()
    {
        $this->detectCommonParams();
        switch ($this->command) {
        case '':
            $resources = array(
                array('name' => 'session'),
                array('name' => 'profile'),
                array('name' => 'password'),
                array('name' => 'challenge'),
                array('name' => 'register'),
                array('name' => 'confirm'),
                array('name' => 'ticket')
            );
            $result = array('resource' => $resources);
            break;
        case 'challenge':
            $username = Utilities::getArrayValue('username', $_REQUEST, '');
            $send_email = Utilities::getArrayValue('send_email', $_REQUEST, '');
            $result = $this->forgotPassword($username, $send_email);
            break;
        case 'profile':
            $result = $this->getProfile();
            break;
        case 'session':
            $ticket = Utilities::getArrayValue('ticket', $_REQUEST, '');
            $result = $this->userSession($ticket);
            break;
        case 'ticket':
            $result = $this->userTicket();
            break;
        default:
           // unsupported GET request
           throw new Exception("GET Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST);
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
        switch ($this->command) {
        case 'session':
            $username = Utilities::getArrayValue('username', $data, '');
            $password = Utilities::getArrayValue('password', $data, '');
            //$password = Utilities::decryptPassword($password);
            $result = $this->userLogin($username, $password);
            break;
        case 'register':
            $firstName = Utilities::getArrayValue('first_name', $data, '');
            $lastName = Utilities::getArrayValue('last_name', $data, '');
            $username = Utilities::getArrayValue('username', $data, '');
            $email = Utilities::getArrayValue('email', $data, '');
            $password = Utilities::getArrayValue('password', $data, '');
            //$password = Utilities::decryptPassword($password);
            $result = $this->userRegister($username, $email, $password, $firstName, $lastName);
            break;
        case 'confirm':
            $code = Utilities::getArrayValue('code', $_REQUEST, '');
            if (empty($code))
                $code = Utilities::getArrayValue('code', $data, '');
            $result = $this->userConfirm($code);
            break;
        case 'challenge':
            $newPassword = Utilities::getArrayValue('new_password', $data, '');
            if (empty($newPassword)) {
                throw new Exception("Missing required fields 'new_password'.", ErrorCodes::BAD_REQUEST);
            }
            $username = Utilities::getArrayValue('username', $_REQUEST, '');
            if (empty($username))
                $username = Utilities::getArrayValue('username', $data, '');
            $answer = Utilities::getArrayValue('security_answer', $data, '');
            if (!empty($username) && !empty($answer)) {
                $result = $this->passwordResetBySecurityAnswer($username, $answer, $newPassword);
            }
            else {
                $code = Utilities::getArrayValue('code', $_REQUEST, '');
                if (empty($code))
                    $code = Utilities::getArrayValue('code', $data, '');
                if (!empty($code)) {
                    $result = $this->passwordResetByCode($code, $newPassword);
                }
                else {
                    throw new Exception("Missing required fields 'username' and 'security_answer'.", ErrorCodes::BAD_REQUEST);
                }
            }
            break;
        case 'password':
            $oldPassword = Utilities::getArrayValue('old_password', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('new_password', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'profile':
            $result = $this->changeProfile($data);
            break;
        default:
           // unsupported GET request
           throw new Exception("POST Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST);
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
        switch ($this->command) {
        case 'password':
            $oldPassword = Utilities::getArrayValue('old_password', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('new_password', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'profile':
            $result = $this->changeProfile($data);
            break;
        default:
           // unsupported GET request
           throw new Exception("PUT Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST);
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
        switch ($this->command) {
        case 'password':
            $oldPassword = Utilities::getArrayValue('old_password', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('new_password', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'profile':
            $result = $this->changeProfile($data);
            break;
        default:
           // unsupported GET request
           throw new Exception("MERGE Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST);
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
        switch ($this->command) {
        case 'session':
            $this->userLogout();
            $result = array('success' => 'true');
            break;
        default:
            // unsupported GET request
            throw new Exception("DELETE Request command '$this->command' is not currently supported by this User API.", ErrorCodes::BAD_REQUEST);
            break;
        }

        return $result;
    }

    /**
     *
     */
    protected function detectCommonParams()
    {
        $resource = Utilities::getArrayValue('resource', $_GET, '');
        $resource = (!empty($resource)) ? explode('/', $resource) : array();
        $this->command = (isset($resource[0])) ? strtolower($resource[0]) : '';
    }

    //------- Email Helpers ----------------------

    /**
     * @param $email
     * @param $fullname
     * @throws Exception
     */
    protected function sendUserWelcomeEmail($email, $fullname)
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

        $html = str_replace("\r\n", "<br />", $body);
        try {
            $svc = ServiceHandler::getServiceObject('email');
            $result = ($svc) ? $svc->sendEmail($to, '', '', $subject, $body, $html) : false;
            if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
                $msg = "Error: Failed to send user welcome email.";
                if (is_string($result)) {
                    $msg .= "\n$result";
                }
                throw new Exception($msg);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $email
     * @param $fullname
     * @throws Exception
     */
    protected function sendAdminIntimationOnRegComplete($email, $fullname)
    {
        $subject = "Registration Completed: " . $fullname;
        $body = "A new user registered at " . $this->siteName . ".\r\n" .
            "Name: " . $fullname . "\r\n" .
            "Email address: " . $email . "\r\n";

        $html = str_replace("\r\n", "<br />", $body);

        try {
            $svc = ServiceHandler::getServiceObject('email');
            $result = ($svc) ? $svc->sendEmailToAdmin('', '', $subject, $body, $html) : false;
            if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
                $msg = "Error: Failed to send registration complete email.";
                if (is_string($result)) {
                    $msg .= "\n$result";
                }
                throw new Exception($msg);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $email
     * @param $full_name
     * @throws Exception
     */
    protected function sendResetPasswordLink($email, $full_name)
    {
//        $to = "$full_name <$email>";
        $to = $email;
        $subject = "Your reset password request at " . $this->siteName;
        $link = Yii::app()->homeUrl . '?code=' . urlencode($this->getResetPasswordCode($email));

        $body = "Hello " . $full_name . ",\r\n\r\n" .
            "There was a request to reset your password at " . $this->siteName . "\r\n" .
            "Please click the link below to complete the request: \r\n" . $link . "\r\n" .
            "Regards,\r\n" .
            "Webmaster\r\n" .
            $this->siteName;

        $html = str_replace("\r\n", "<br />", $body);

        try {
            $svc = ServiceHandler::getServiceObject('email');
            $result = ($svc) ? $svc->sendEmailPhp($to, '', '', $subject, $body, $html) : false;
            if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
                $msg = "Error: Failed to send user password change email.";
                if (is_string($result)) {
                    $msg .= "\n$result";
                }
                throw new Exception($msg);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $user_rec
     * @param $new_password
     * @throws Exception
     */
    protected function sendNewPassword($user_rec, $new_password)
    {
        $email = $user_rec['email'];
//        $to = $user_rec['name'] . ' <' . $email . '>';
        $to = $email;
        $subject = "Your new password for " . $this->siteName;
        $body = "Hello " . $user_rec['name'] . ",\r\n\r\n" .
            "Your password was reset successfully. " .
            "Here is your updated login:\r\n" .
            "username:" . $user_rec['username'] . "\r\n" .
            "password:$new_password\r\n" .
            "\r\n" .
            "Login here: " . Utilities::getAbsoluteURLFolder() . "login.php\r\n" .
            "\r\n" .
            "Regards,\r\n" .
            "Webmaster\r\n" .
            $this->siteName;

        $html = str_replace("\r\n", "<br />", $body);

        try {
            $svc = ServiceHandler::getServiceObject('email');
            $result = ($svc) ? $svc->sendEmailPhp($to, '', '', $subject, $body, $html) : false;
            if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
                $msg = "Error: Failed to send new password email.";
                if (is_string($result)) {
                    $msg .= "\n$result";
                }
                throw new Exception($msg);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $email
     * @param $confirmcode
     * @param $fullname
     * @throws Exception
     */
    protected function sendUserConfirmationEmail($email, $confirmcode, $fullname)
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

        $html = str_replace("\r\n", "<br />", $body);
        try {
            $svc = ServiceHandler::getServiceObject('email');
            $result = ($svc) ? $svc->sendEmailPhp($to, '', '', $subject, $body, $html) : false;
            if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
                $msg = "Error: Failed sending registration confirmation email.";
                if (is_string($result)) {
                    $msg .= "\n$result";
                }
                throw new Exception($msg);
            }
        }
        catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * @param $email
     * @param $username
     * @param $fullname
     * @throws Exception
     */
    protected function sendAdminIntimationEmail($email, $username, $fullname)
    {
        $subject = "New registration: " . $fullname;
        $body = "A new user registered at " . $this->siteName . ".\r\n" .
            "Name: " . $fullname . "\r\n" .
            "Email address: " . $email . "\r\n" .
            "UserName: " . $username;

        $html = str_replace("\r\n", "<br />", $body);

        try {
            $svc = ServiceHandler::getServiceObject('email');
            $result = ($svc) ? $svc->sendEmailToAdmin('', '', $subject, $body, $html) : false;
            if (!filter_var($result, FILTER_VALIDATE_BOOLEAN)) {
                $msg = "Error: Failed to send admin notification email.";
                if (is_string($result)) {
                    $msg .= "\n$result";
                }
                throw new Exception($msg);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $email
     * @return string
     */
    protected function getResetPasswordCode($email)
    {
        return substr(md5($email . $this->siteName . $this->randKey), 0, 10);
    }

    /**
     * @param $conf_key
     * @return string
     */
    protected function makeConfirmationMd5($conf_key)
    {
        $randNo1 = rand();
        $randNo2 = rand();

        return md5($conf_key . $this->randKey . $randNo1 . '' . $randNo2);
    }

    /**
     * @param array $session
     * @param bool $is_sys_admin
     * @param bool $add_apps
     * @return array
     */
    protected static function addSessionExtras($session, $is_sys_admin=false, $add_apps=false)
    {
        $data = Utilities::getArrayValue('data', $session, array());
        $userId = Utilities::getArrayValue('id', $data, '');
        $timestamp = time();
        $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");
        $data['ticket'] = $ticket;
        $data['ticket_expiry'] = time() + (5 * 60);
        $data['session_id'] = session_id();

        if ($add_apps) {
            $appFields = 'id,api_name,name,description,url,is_url_external';
            $theApps = Utilities::getArrayValue('allowed_apps', $session, array());
            if ($is_sys_admin) {
                $theApps = App::model()->findAll('is_active = :ia', array(':ia'=>1));
            }
            $theGroups = AppGroup::model()->with('apps')->findAll();
            $appGroups = array();
            $noGroupApps = array();
            $defaultAppId = Utilities::getArrayValue('default_app_id', $session, null);
            foreach ($theApps as $app) {
                $appId = $app->getAttribute('id');
                $tempGroups = $app->getRelated('app_groups');
                $appData = $app->getAttributes(explode(',', $appFields));
                $appData['is_default'] = ($defaultAppId === $appId);
                $found = false;
                foreach ($theGroups as $g_key=>$group) {
                    $groupId = $group->getAttribute('id');
                    $groupData = (isset($appGroups[$g_key])) ? $appGroups[$g_key] : $group->getAttributes(array('id','name','description'));
                    foreach ($tempGroups as $tempGroup) {
                        if ($tempGroup->getAttribute('id') === $groupId) {
                            $found = true;
                            $temp = Utilities::getArrayValue('apps', $groupData, array());
                            $temp[] = $appData;
                            $groupData['apps'] = $temp;
                        }
                    }
                    $appGroups[$g_key] = $groupData;
                }
                if (!$found) {
                    $noGroupApps[] = $appData;
                }
            }
            // clean out any empty groups
            foreach ($appGroups as $g_key=>$group) {
                if (!isset($group['apps'])) {
                    unset($appGroups[$g_key]);
                }
            }
            $data['app_groups'] = array_values($appGroups); // reset indexing
            $data['no_group_apps'] = $noGroupApps;
        }

        return $data;
    }

    //-------- User Operations ------------------------------------------------

    /**
     * @param $username
     * @param $password
     * @return array
     * @throws Exception
     */
    public function userLogin($username, $password)
    {
        if (empty($username)) {
            throw new Exception("Login request is missing required username.", ErrorCodes::BAD_REQUEST);
        }
        if (empty($password)) {
            throw new Exception("Login request is missing required password.", ErrorCodes::BAD_REQUEST);
        }

        try {
            $theUser = User::model()->with('role.role_service_accesses','role.apps','role.services')->find('username=:un', array(':un'=>$username));
            if (null === $theUser) {
                // bad username
                throw new Exception("The credentials supplied do not match system records.", ErrorCodes::UNAUTHORIZED);
            }
            if ('y' !== $theUser->getAttribute('confirm_code')) {
                throw new Exception("Login registration has not been confirmed.");
            }
            if (!CPasswordHelper::verifyPassword($password, $theUser->getAttribute('password'))) {
                throw new Exception("The credentials supplied do not match system records.", ErrorCodes::UNAUTHORIZED);
            }
            $isSysAdmin = $theUser->getAttribute('is_sys_admin');
            $result = SessionManager::generateSessionData(null, $theUser);

            // write back login datetime
            $theUser->last_login_date = new CDbExpression('NOW()');
            $theUser->save();

            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['public'] = Utilities::getArrayValue('public', $result, array());
            $GLOBALS['write_session'] = true;

            // additional stuff for session - launchpad mainly
            return static::addSessionExtras($result, $isSysAdmin, true);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * userSession refreshes an existing session or
     *     allows the SSO creation of a new session for external apps via timed ticket
     *
     * @param string $ticket
     * @return mixed
     * @throws Exception
     */
    public function userSession($ticket = '')
    {
        if (empty($ticket)) {
            try {
                $userId = SessionManager::validateSession();
            }
            catch (Exception $ex) {
                $this->userLogout();
                throw $ex;
            }
        }
        else { // process ticket
            $creds = Utilities::decryptCreds($ticket, "gorilla");
            $pieces = explode(',', $creds);
            $userId = $pieces[0];
            $timestamp = $pieces[1];
            $curTime = time();
            $lapse = $curTime - $timestamp;
            if (empty($userId) || ($lapse > 300)) { // only lasts 5 minutes
                $this->userLogout();
                throw new Exception("Ticket used for session generation is too old.", ErrorCodes::UNAUTHORIZED);
            }
        }

        try {
            $theUser = User::model()->with('role.role_service_accesses','role.apps','role.services')->findByPk($userId);
            if (null === $theUser) {
                throw new Exception("The user identified in the session or ticket does not exist in the system.", ErrorCodes::UNAUTHORIZED);
            }
            $isSysAdmin = $theUser->getAttribute('is_sys_admin');
            $result = SessionManager::generateSessionData(null, $theUser);

            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['public'] = Utilities::getArrayValue('public', $result, array());
            $GLOBALS['write_session'] = true;

            // additional stuff for session - launchpad mainly
            return static::addSessionExtras($result, $isSysAdmin, true);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * userTicket generates a SSO timed ticket for current valid session
     *
     * @return array
     * @throws Exception
     */
    public function userTicket()
    {
        try {
            $userId = SessionManager::validateSession();
        }
        catch (Exception $ex) {
            $this->userLogout();
            throw $ex;
        }
        // regenerate new timed ticket
        $timestamp = time();
        $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");
        return array('ticket' => $ticket, 'ticket_expiry' => time() + (5 * 60));
    }

    /**
     *
     */
    public function userLogout()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        session_unset();
        $_SESSION=array();
        session_destroy();
        session_write_close();
        setcookie(session_name(), '', 0, '/');
        session_regenerate_id(true);
    }

    /**
     * @param $username
     * @param $email
     * @param string $password
     * @param string $first_name
     * @param string $last_name
     * @return array
     * @throws Exception
     */
    public function userRegister($username, $email, $password = '', $first_name = '', $last_name = '')
    {
        $confirmCode = $this->makeConfirmationMd5($username);
        // fill out the user fields for creation
        $fields = array('username' => $username, 'email' => $email, 'password' => $password);
        $fields['first_name'] = (!empty($first_name)) ? $first_name : $username;
        $fields['last_name'] = (!empty($last_name)) ? $last_name : $username;
        $fullName = (!empty($first_name) && !empty($last_name)) ? $first_name . ' ' . $last_name : $username;
        $fields['display_name'] = $fullName;
        $fields['confirm_code'] = $confirmCode;
        $record = Utilities::array_key_lower($fields);
        try {
            if (empty($username)) {
                throw new Exception("The username field for User can not be empty.", ErrorCodes::BAD_REQUEST);
            }
            $theUser = User::model()->find('username=:un', array(':un'=>$username));
            if (null !== $theUser) {
                throw new Exception("A User already exists with the username '$username'.", ErrorCodes::BAD_REQUEST);
            }
            $user = new User();
            $user->setAttributes($record);
            if (!$user->save()) {
                throw new Exception("Failed to create a new user.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }
            try {
                $this->sendUserConfirmationEmail($email, $confirmCode, $fullName);
            }
            catch (Exception $ex) {
                // need to remove from database here, otherwise they can't register again
                $user->delete();
                throw new Exception("Failed to register new user!\nError sending registration email.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }
        }
        catch (Exception $ex) {
            throw new Exception("Failed to register new user!\n{$ex->getMessage()}", $ex->getCode());
        }

        $this->sendAdminIntimationEmail($email, $username, $fullName);

        unset($fields['password']);

        return $fields;
    }

    /**
     * @param $code
     * @return mixed
     * @throws Exception
     */
    public function userConfirm($code)
    {
        try {
            $theUser = User::model()->find('confirm_code=:cc', array(':cc'=>$code));
            if (null === $theUser) {
                throw new Exception("Invalid confirm code.", ErrorCodes::BAD_REQUEST);
            }
            $theUser->setAttribute('confirm_code', 'y');
            if (!$theUser->save()) {
                throw new Exception("Failed to update user.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }
            try {
                $fullName = $theUser->display_name;
                $email = $theUser->email;
                $this->sendUserWelcomeEmail($email, $fullName);
                $this->sendAdminIntimationOnRegComplete($email, $fullName);
            }
            catch (Exception $ex) {
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error validating confirmation.\n{$ex->getMessage()}", $ex->getCode());
        }

        return array();
    }

    /**
     * @param $username
     * @param bool $send_email
     * @throws Exception
     * @return string
     */
    public function forgotPassword($username, $send_email = false)
    {
        try {
            $theUser = User::model()->find('username=:un', array(':un'=>$username));
            if (null === $theUser) {
                // bad username
                throw new Exception("The supplied username was not found in the system.");
            }
            if ('y' !== $theUser->getAttribute('confirm_code')) {
                throw new Exception("Login registration has not been confirmed.");
            }
            if ($send_email) {
                $email = $theUser->getAttribute('email');
                $fullName = $theUser->getAttribute('display_name');
                if (!empty($email) && !empty($fullName)) {
                    $this->sendResetPasswordLink($email, $fullName);
                    return array('success' => true);
                }
                else {
                    throw new Exception('No valid email provisioned for this user.');
                }
            }
            else {
                $question = $theUser->getAttribute('security_question');
                if (!empty($question)) {
                    return array('security_question'=>$question);
                }
                else {
                    throw new Exception('No valid security question provisioned for this user.');
                }
            }

        }
        catch (Exception $ex) {
            throw new Exception("Error with password challenge.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $username
     * @param $answer
     * @param $new_password
     * @throws Exception
     * @return mixed
     */
    public function passwordResetBySecurityAnswer($username, $answer, $new_password)
    {
        try {
            $theUser = User::model()->find('username=:un', array(':un'=>$username));
            if (null === $theUser) {
                // bad username
                throw new Exception("The supplied username was not found in the system.");
            }
            if ('y' !== $theUser->getAttribute('confirm_code')) {
                throw new Exception("Login registration has not been confirmed.");
            }
            // validate answer
            if (!CPasswordHelper::verifyPassword($answer, $theUser->getAttribute('security_answer'))) {
                throw new Exception("The challenge response supplied does not match system records.", ErrorCodes::UNAUTHORIZED);
            }
            $theUser->setAttribute('password', CPasswordHelper::hashPassword($new_password));
            if (!$theUser->save()) {
                throw new Exception("Failed to change the password.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            $userId = $theUser->getPrimaryKey();
            $timestamp = time();
            $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");

            return $this->userSession($ticket);
        }
        catch (Exception $ex) {
            throw new Exception("Error processing security answer.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param string $code
     * @param string $new_password
     * @throws Exception
     * @return mixed
     */
    public function passwordResetByCode($code, $new_password)
    {
        try {
            $theUser = User::model()->find('confirm_code=:cc', array(':cc'=>$code));
            if (null === $theUser) {
                // bad username
                throw new Exception("The supplied username was not found in the system.");
            }
            $theUser->setAttribute('confirm_code', 'y');
            $theUser->setAttribute('password', CPasswordHelper::hashPassword($new_password));
            if (!$theUser->save()) {
                throw new Exception("Failed to change the password.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            $userId = $theUser->getPrimaryKey();
            $timestamp = time();
            $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");

            return $this->userSession($ticket);
        }
        catch (Exception $ex) {
            throw new Exception("Error processing security answer.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $old_password
     * @param $new_password
     * @return bool
     * @throws Exception
     */
    public function changePassword($old_password, $new_password)
    {
        // check valid session,
        // using userId from session, query with check for old password
        // then update with new password
        $userId = SessionManager::validateSession();

        try {
            $theUser = User::model()->findByPk($userId);
            if (null === $theUser) {
                // bad session
                throw new Exception("The user for the current session was not found in the system.");
            }
            // validate answer
            if (!CPasswordHelper::verifyPassword($old_password, $theUser->getAttribute('password'))) {
                throw new Exception("The password supplied does not match.", ErrorCodes::BAD_REQUEST);
            }
            $theUser->setAttribute('password', CPasswordHelper::hashPassword($new_password));
            if (!$theUser->save()) {
                throw new Exception("Failed to change the password.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            return array('success' => true);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param array $record
     * @return bool
     * @throws Exception
     */
    public function changeProfile($record)
    {
        // check valid session,
        // using userId from session, update with new profile elements
        $userId = SessionManager::validateSession();

        try {
            $theUser = User::model()->findByPk($userId);
            if (null === $theUser) {
                // bad session
                throw new Exception("The user for the current session was not found in the system.");
            }
            $allow = array('first_name','last_name','display_name','email',
                           'phone','security_question','security_answer','default_app_id');
            foreach ($record as $key=>$value) {
                if (false === array_search($key, $allow)) {
                    throw new Exception("Attribute '$key' can not be updated through profile change.", ErrorCodes::INTERNAL_SERVER_ERROR);
                }
            }
            $theUser->setAttributes($record);
            if (!$theUser->save()) {
                throw new Exception("Failed to update the user profile.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }

            return array('success' => true);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function getProfile()
    {
        // check valid session,
        // using userId from session, update with new profile elements
        $userId = SessionManager::validateSession();

        try {
            $theUser = User::model()->findByPk($userId);
            if (null === $theUser) {
                // bad session
                throw new Exception("The user for the current session was not found in the system.");
            }
            // todo protect certain attributes here
            $fields = $theUser->getAttributes(array('first_name','last_name','display_name','email',
                                                    'phone','security_question','default_app_id'));

            return $fields;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }
}
