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

/**
 *
 */
class UserManager implements iRestHandler
{
    /**
     * @var ServiceHandler
     */
    private static $_instance = null;

    /**
     * @var PdoSqlDbSvc
     */
    protected $nativeDb;

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
        $this->nativeDb = new PdoSqlDbSvc();

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
        unset($this->nativeDb);
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

    // Controller based methods

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
                array('name' => 'login'),
                array('name' => 'logout'),
                array('name' => 'register'),
                array('name' => 'confirm'),
                array('name' => 'change_profile'),
                array('name' => 'change_password'),
                array('name' => 'forgot_password'),
                array('name' => 'new_password'),
                array('name' => 'security_answer'),
                array('name' => 'ticket'),
                array('name' => 'session'));
            $result = array('resource' => $resources);
            break;
        case 'ticket':
            $result = $this->userTicket();
            break;
        case 'session':
            $ticket = Utilities::getArrayValue('ticket', $_REQUEST, '');
            $result = $this->userSession($ticket);
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
        case 'login':
            $username = Utilities::getArrayValue('username', $data, '');
            $password = Utilities::getArrayValue('password', $data, '');
            //$password = Utilities::decryptPassword($password);
            $result = $this->userLogin($username, $password);
            break;
        case 'logout':
            $this->userLogout();
            $result = array('result' => 'OK');
            break;
        case 'register':
            $firstname = Utilities::getArrayValue('firstname', $data, '');
            $lastname = Utilities::getArrayValue('lastname', $data, '');
            $username = Utilities::getArrayValue('username', $data, '');
            $email = Utilities::getArrayValue('email', $data, '');
            $password = Utilities::getArrayValue('password', $data, '');
            //$password = Utilities::decryptPassword($password);
            $result = $this->userRegister($username, $email, $password, $firstname, $lastname);
            break;
        case 'confirm':
            $code = Utilities::getArrayValue('code', $data, '');
            $result = $this->userConfirm($code);
            break;
        case 'forgot_password':
            $username = Utilities::getArrayValue('username', $data, '');
            $result = $this->forgotPassword($username);
            break;
        case 'security_answer':
            $username = Utilities::getArrayValue('username', $data, '');
            $answer = Utilities::getArrayValue('username', $data, '');
            $result = $this->securityAnswer($username, $answer);
            break;
        case 'new_password':
            $code = Utilities::getArrayValue('code', $data, '');
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->newPassword($code, $newPassword);
            break;
        case 'change_password':
            $oldPassword = Utilities::getArrayValue('oldpassword', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'change_profile':
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
        case 'change_password':
            $oldPassword = Utilities::getArrayValue('oldpassword', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'change_profile':
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
        case 'change_password':
            $oldPassword = Utilities::getArrayValue('oldpassword', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'change_profile':
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
        throw new Exception("DELETE Request is not currently supported by this User API.", ErrorCodes::METHOD_NOT_ALLOWED);
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
            $svc = ServiceHandler::getInstance()->getServiceObject('email');
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
            $svc = ServiceHandler::getInstance()->getServiceObject('email');
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
     * @param $fullname
     * @throws Exception
     */
    protected function sendResetPasswordLink($email, $fullname)
    {
//        $to = "$fullname <$email>";
        $to = $email;
        $subject = "Your reset password request at " . $this->siteName;
        $link = Utilities::getAbsoluteURLFolder() .
            'resetpwd.php?email=' .
            urlencode($email) . '&code=' .
            urlencode($this->getResetPasswordCode($email));

        $body = "Hello " . $fullname . ",\r\n\r\n" .
            "There was a request to reset your password at " . $this->siteName . "\r\n" .
            "Please click the link below to complete the request: \r\n" . $link . "\r\n" .
            "Regards,\r\n" .
            "Webmaster\r\n" .
            $this->siteName;

        $html = str_replace("\r\n", "<br />", $body);

        try {
            $svc = ServiceHandler::getInstance()->getServiceObject('email');
            $result = ($svc) ? $svc->sendEmail($to, '', '', $subject, $body, $html) : false;
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
            $svc = ServiceHandler::getInstance()->getServiceObject('email');
            $result = ($svc) ? $svc->sendEmail($to, '', '', $subject, $body, $html) : false;
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
            $svc = ServiceHandler::getInstance()->getServiceObject('email');
            $result = ($svc) ? $svc->sendEmail($to, '', '', $subject, $body, $html) : false;
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
            $svc = ServiceHandler::getInstance()->getServiceObject('email');
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
     * @param $confkey
     * @return string
     */
    protected function makeConfirmationMd5($confkey)
    {
        $randno1 = rand();
        $randno2 = rand();

        return md5($confkey . $this->randKey . $randno1 . '' . $randno2);
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
            $theUser = User::model()->find('username=:un and confirm_code=:cc',
                                           array(':un'=>$username, ':cc'=>'y'));
            if (null === $theUser) {
                if (User::model()->exists('username=:un', array(':un'=>$username))) {
                    throw new Exception("Login registration has not been confirmed.");
                }
                // bad username
                throw new Exception("Either the username or password supplied does not match system records.", ErrorCodes::UNAUTHORIZED);
            }
            if (!CPasswordHelper::verifyPassword($password, $theUser->getAttribute('password'))) {
                throw new Exception("Either the username or password supplied does not match system records.", ErrorCodes::UNAUTHORIZED);
            }
            if (!$theUser->getAttribute('is_active')) {
                throw new Exception("The login with username '$username' is not currently active.", ErrorCodes::FORBIDDEN);
            }

            $isSysAdmin = $theUser->getAttribute('is_sys_admin');
            $roleId = $theUser->getAttribute('role_id');
            $roleId = trim(trim($roleId, ',')); // todo
            if (empty($roleId) && !$isSysAdmin) {
                throw new Exception("The username '$username' has not been assigned a role.", ErrorCodes::FORBIDDEN);
            }

            $defaultAppId = $theUser->getAttribute('default_app_id');
            $fields = 'id,display_name,first_name,last_name,username,email,phone,is_sys_admin';
            $fields .= ',created_date,created_by_id,last_modified_date,last_modified_by_id';
            $userInfo = $theUser->getAttributes(explode(',', $fields));
            $data = $userInfo; // reply data
            $allowedApps = array();
            if (!empty($roleId)) {
                $theRole = Role::model()->findByPk($roleId);
                if ((null === $theRole) && !$isSysAdmin) {
                    throw new Exception("The username '$username' has not been assigned a valid role.", ErrorCodes::FORBIDDEN);
                }
                else {
                    if (!isset($defaultAppId)) {
                        $defaultAppId = $theRole->getAttribute('default_app_id');
                    }
                    $role = $theRole->attributes;
                    $allowedAppIds = explode(',', trim($theRole->getAttribute('app_ids'), ','));
                    try {
                        $roleApps = array();
                        if (!empty($allowedAppIds)) {
                            $temp = App::model()->findAllByPk($allowedAppIds);
                            foreach($temp as $app) {
                                $roleApps[] = array('id' => $app->getAttribute('id'),
                                                    'name' => $app->getAttribute('name'));
                                if ($app->getAttribute('is_active'))
                                    $allowedApps[] = $app;
                            }
                        }
                        $role['apps'] = $roleApps;
                        unset($role['app_ids']);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                    $permsFields = array('service','component','read','create','update','delete');
                    $thePerms = RoleServiceAccess::model()->findAll('role_id=:rid',
                                                                    array(':rid'=>$roleId));
                    $perms = array();
                    foreach ($thePerms as $perm) {
                        $perms[] = $perm->getAttributes($permsFields);
                    }
                    $role['services'] = $perms;
                    $userInfo['role'] = $role;
                }
            }

            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['public'] = $userInfo;

            // additional stuff for session - launchpad mainly
            $userId = $userInfo['id'];
            $timestamp = time();
            $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");
            $data['ticket'] = $ticket;
            $data['ticket_expiry'] = time() + (5 * 60);
            $data['session_id'] = session_id();

            $appFields = 'id,name,label,description,url,is_url_external,app_group_ids';
            $theApps = $allowedApps;
            if ($isSysAdmin) {
                $theApps = App::model()->findAll('is_active = :ia', array(':ia'=>1));
            }
            $theGroups = AppGroup::model()->findAll();
            $appGroups = array();
            $noGroupApps = array();
            foreach ($theApps as $app) {
                $appId = $app->getAttribute('id');
                $groupIds = $app->getAttribute('app_group_ids');
                $groupIds = array_map('trim', explode(',', trim($groupIds, ',')));
                $appData = $app->getAttributes(explode(',', $appFields));
                $appData['is_default'] = ($defaultAppId === $appId);
                $found = false;
                foreach ($theGroups as $g_key=>$group) {
                    $groupId = $group->getAttribute('id');
                    $groupData = (isset($appGroups[$g_key])) ? $appGroups[$g_key] : $group->getAttributes(array('id','name','description'));
                    if (false !== array_search($groupId, $groupIds)) {
                        $found = true;
                        $temp = Utilities::getArrayValue('apps', $groupData, array());
                        $temp[] = $appData;
                        $groupData['apps'] = $temp;
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

            return $data;
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
                $userId = Utilities::validateSession();
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
                throw new Exception("[INVALIDSESSION]: Ticket used for session generation is too old.", ErrorCodes::UNAUTHORIZED);
            }
        }

        try {
            $theUser = User::model()->findByPk($userId);
            if (null === $theUser) {
                throw new Exception("The user identified in the session or ticket does not exist in the system.", ErrorCodes::UNAUTHORIZED);
            }
            $username = $theUser->getAttribute('username');
            if (!$theUser->getAttribute('is_active')) {
                throw new Exception("The login with username '$username' is not currently active.", ErrorCodes::FORBIDDEN);
            }

            $isSysAdmin = $theUser->getAttribute('is_sys_admin');
            $roleId = $theUser->getAttribute('role_id');
            $roleId = trim(trim($roleId, ',')); // todo
            if (empty($roleId) && !$isSysAdmin) {
                throw new Exception("The username '$username' has not been assigned a role.", ErrorCodes::FORBIDDEN);
            }

            $defaultAppId = $theUser->getAttribute('default_app_id');
            $fields = 'id,display_name,first_name,last_name,username,email,phone,is_sys_admin';
            $fields .= ',created_date,created_by_id,last_modified_date,last_modified_by_id';
            $userInfo = $theUser->getAttributes(explode(',', $fields));
            $data = $userInfo; // reply data
            $allowedApps = array();
            if (!empty($roleId)) {
                $theRole = Role::model()->findByPk($roleId);
                if ((null === $theRole) && !$isSysAdmin) {
                    throw new Exception("The username '$username' has not been assigned a valid role.", ErrorCodes::FORBIDDEN);
                }
                else {
                    if (!isset($defaultAppId)) {
                        $defaultAppId = $theRole->getAttribute('default_app_id');
                    }
                    $role = $theRole->attributes;
                    $allowedAppIds = explode(',', trim($theRole->getAttribute('app_ids'), ','));
                    try {
                        $roleApps = array();
                        if (!empty($allowedAppIds)) {
                            $temp = App::model()->findAllByPk($allowedAppIds);
                            foreach($temp as $app) {
                                $roleApps[] = array('id' => $app->getAttribute('id'),
                                                    'name' => $app->getAttribute('name'));
                                if ($app->getAttribute('is_active'))
                                    $allowedApps[] = $app;
                            }
                        }
                        $role['apps'] = $roleApps;
                        unset($role['app_ids']);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                    $permsFields = array('service','component','read','create','update','delete');
                    $thePerms = RoleServiceAccess::model()->findAll('role_id=:rid',
                                                                    array(':rid'=>$roleId));
                    $perms = array();
                    foreach ($thePerms as $perm) {
                        $perms[] = $perm->getAttributes($permsFields);
                    }
                    $role['services'] = $perms;
                    $userInfo['role'] = $role;
                }
            }

            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['public'] = $userInfo;

            // additional stuff for session - launchpad mainly
            $userId = $userInfo['id'];
            $timestamp = time();
            $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");
            $data['ticket'] = $ticket;
            $data['ticket_expiry'] = time() + (5 * 60);
            $data['session_id'] = session_id();

            $appFields = 'id,name,label,description,url,is_url_external,app_group_ids';
            $theApps = $allowedApps;
            if ($isSysAdmin) {
                $theApps = App::model()->findAll('is_active = :ia', array(':ia'=>1));
            }
            $theGroups = AppGroup::model()->findAll();
            $appGroups = array();
            $noGroupApps = array();
            foreach ($theApps as $app) {
                $appId = $app->getAttribute('id');
                $groupIds = $app->getAttribute('app_group_ids');
                $groupIds = array_map('trim', explode(',', trim($groupIds, ',')));
                $appData = $app->getAttributes(explode(',', $appFields));
                $appData['is_default'] = ($defaultAppId === $appId);
                $found = false;
                foreach ($theGroups as $g_key=>$group) {
                    $groupId = $group->getAttribute('id');
                    $groupData = (isset($appGroups[$g_key])) ? $appGroups[$g_key] : $group->getAttributes(array('id','name','description'));
                    if (false !== array_search($groupId, $groupIds)) {
                        $found = true;
                        $temp = Utilities::getArrayValue('apps', $groupData, array());
                        $temp[] = $appData;
                        $groupData['apps'] = $temp;
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

            return $data;
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
            $userId = Utilities::validateSession();
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
        $fields = array('username' => $username, 'email' => $email, 'password' => CPasswordHelper::hashPassword($password));
        $fields['first_name'] = (!empty($first_name)) ? $first_name : $username;
        $fields['last_name'] = (!empty($last_name)) ? $last_name : $username;
        $fullName = (!empty($first_name) && !empty($last_name)) ? $first_name . ' ' . $last_name : $username;
        $fields['display_name'] = $fullName;
        $fields['confirm_code'] = $confirmCode;
        $record = Utilities::array_key_lower($fields);
        $db = $this->nativeDb;
        try {
            if (empty($username)) {
                throw new Exception("The username field for User can not be empty.", ErrorCodes::BAD_REQUEST);
            }
            $result = $db->retrieveSqlRecordsByFilter('user', 'id', "username = '$username'", 1);
            unset($result['total']);
            if (count($result) > 0) {
                throw new Exception("A User already exists with the username '$username'.", ErrorCodes::BAD_REQUEST);
            }
            $result = $db->createSqlRecord('user', $record, 'id');
        }
        catch (Exception $ex) {
            throw new Exception("Failed to register new user!\n{$ex->getMessage()}", $ex->getCode());
        }

        try {
            $this->sendUserConfirmationEmail($email, $confirmCode, $fullName);
        }
        catch (Exception $ex) {
            // need to remove from database here, otherwise they can't register again
            $db->deleteSqlRecordsByIds('user', $result[0]['id'], 'id');
            throw new Exception("Failed to register new user!\nError sending registration email.", ErrorCodes::INTERNAL_SERVER_ERROR);
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
            $db = $this->nativeDb;
            $result = $db->retrieveSqlRecordsByFilter('user', 'id,display_name,email', "confirm_code='$code'", 1);
            unset($result['total']);
            if (count($result) < 1) {
                throw new Exception("Invalid confirm code.", ErrorCodes::BAD_REQUEST);
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error validating confirmation.\n{$ex->getMessage()}", $ex->getCode());
        }

        $result[0]['confirm_code'] = 'y';
        try {
            $record = Utilities::array_key_lower(array('fields' => $result[0]));
            $db->updateSqlRecords('user', $record, 'id');
        }
        catch (Exception $ex) {
            throw new Exception("Error updating confirmation.\n{$ex->getMessage()}", $ex->getCode());
        }

        try {
            $fullName = $result[0]['display_name'];
            $email = $result[0]['email'];
            $this->sendUserWelcomeEmail($email, $fullName);
            $this->sendAdminIntimationOnRegComplete($email, $fullName);
        }
        catch (Exception $ex) {
        }

        return $result;
    }

    /**
     * @param $username
     * @return string
     * @throws Exception
     */
    public function forgotPassword($username)
    {
        try {
            $db = $this->nativeDb;
            // if security question available ask, otherwise if email svc available send tmp password
            $query = "username='$username' and confirm_code='y'";
            $result = $db->retrieveSqlRecordsByFilter('user', 'security_question,email,display_name', $query, 1);
            unset($result['total']);
            if (count($result) < 1) {
                // Check if the confirmation was never completed
                $query = "username='$username'";
                $result = $db->retrieveSqlRecordsByFilter('user', '', $query, 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new Exception("The user has not confirmed registration.");
                }
                throw new Exception("The supplied username was not found in the system.");
            }
            $userInfo = $result[0];
            $question = Utilities::getArrayValue('security_question', $userInfo, '');
            if (!empty($question)) {
                unset($userInfo['email']);

                return $userInfo;
            }
            $email = Utilities::getArrayValue('email', $userInfo, '');
            $fullName = Utilities::getArrayValue('display_name', $userInfo, '');
            if (!empty($email) && !empty($fullName)) {
                $this->sendResetPasswordLink($email, $fullName);
            }

            return '';
        }
        catch (Exception $ex) {
            throw new Exception("Error sending new password.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $username
     * @param $answer
     * @return mixed
     * @throws Exception
     */
    public function securityAnswer($username, $answer)
    {
        try {
            $db = $this->nativeDb;
            $query = "username='$username' and security_answer='$answer' and confirm_code='y'";
            $result = $db->retrieveSqlRecordsByFilter('user', 'id,email', $query, 1);
            unset($result['total']);
            if (count($result) < 1) {
                // Check if the confirmation was never completed
                $query = "username='$username'";
                $result = $db->retrieveSqlRecordsByFilter('user', '', $query, 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new Exception("The user has not confirmed registration.", ErrorCodes::BAD_REQUEST);
                }
                throw new Exception("The supplied answer to the security question is invalid.", ErrorCodes::BAD_REQUEST);
            }

            $userId = $result[0]['id'];
            $timestamp = time();
            $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");

            return $this->userSession($ticket);
        }
        catch (Exception $ex) {
            throw new Exception("Error processing security answer.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $code
     * @param $new_password
     * @throws Exception
     * @return bool
     */
    public function newPassword($code, $new_password)
    {
        // query user with reset code
        // then update with new password
        try {
            $db = $this->nativeDb;
            $query = "confirm_code='$code'";
            $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
            unset($result['total']);
            if (count($result) < 1) {
                throw new Exception("Reset code could not be found. Please contact your administrator.", ErrorCodes::BAD_REQUEST);
            }
            $userInfo = array('password' => CPasswordHelper::hashPassword($new_password));
            $result = $db->updateSqlRecordsByIds('user', $userInfo, $result[0]['id'], 'id');
            if (count($result) < 1) {
                throw new Exception("The user identified in the ticket does not exist in the system.", ErrorCodes::BAD_REQUEST);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }

        return array('status' => true);
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
        $userId = Utilities::validateSession();

        try {
            $db = $this->nativeDb;
            $pwd = CPasswordHelper::hashPassword($old_password);
            $query = "id='$userId' and password='$pwd' and confirm_code='y'";
            $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
            unset($result['total']);
            if (count($result) < 1) {
                // Check if the password is wrong
                $query = "id='$userId' and confirm_code='y'";
                $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new Exception("The password supplied does not match.", ErrorCodes::BAD_REQUEST);
                }
                throw new Exception("Login registration has not been confirmed.", ErrorCodes::BAD_REQUEST);
            }
            $userInfo = array('password' => CPasswordHelper::hashPassword($new_password));
            $result = $db->updateSqlRecordsByIds('user', $userInfo, $userId, 'id');
            if (count($result) < 1) {
                throw new Exception("The user identified in the ticket does not exist in the system.", ErrorCodes::BAD_REQUEST);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }

        return array('status' => true);
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
        $userId = Utilities::validateSession();

        try {
            $db = $this->nativeDb;
            $record = Utilities::removeOneFromArray('password', $record);
            $result = $db->updateSqlRecordsByIds('user', $record, $userId, 'id');
            if (count($result) < 1) {
                throw new Exception("The user profile for this session does not exist in the system.", ErrorCodes::INTERNAL_SERVER_ERROR);
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }

        return array('status' => true);
    }

}
