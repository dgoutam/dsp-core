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
 * @subpackage UserSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory Software, Inc. (http://www.dreamfactory.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: Services.php 66505 2012-04-02 08:45:51Z unknown $
 */

/**
 *
 */
class UserSvc extends CommonService implements iRestHandler
{
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
     * Creates a new UserSvc instance
     *
     */
    public function __construct($config)
    {
        parent::__construct($config);

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
        parent::__destruct();

        unset($this->nativeDb);
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
            $resources = array(array('name' => 'login'), array('name' => 'logout'),
                               array('name' => 'register'), array('name' => 'confirm'),
                               array('name' => 'ChangePassword'), array('name' => 'ForgotPassword'),
                               array('name' => 'NewPassword'), array('name' => 'SecurityAnswer'),
                               array('name' => 'ticket'), array('name' => 'session'));
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
           throw new Exception("GET Request command '$this->command' is not currently supported by this User API.");
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
        case 'forgotpassword':
            $username = Utilities::getArrayValue('username', $data, '');
            $result = $this->forgotPassword($username);
            break;
        case 'securityanswer':
            $username = Utilities::getArrayValue('username', $data, '');
            $answer = Utilities::getArrayValue('username', $data, '');
            $result = $this->securityAnswer($username, $answer);
            break;
        case 'newpassword':
            $code = Utilities::getArrayValue('code', $data, '');
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->newPassword($code, $newPassword);
            break;
        case 'changepassword':
            $oldPassword = Utilities::getArrayValue('oldpassword', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'changeprofile':
            $result = $this->changeProfile($data);
            break;
        default:
           // unsupported GET request
           throw new Exception("POST Request command '$this->command' is not currently supported by this User API.");
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
        case 'changepassword':
            $oldPassword = Utilities::getArrayValue('oldpassword', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'changeprofile':
            $result = $this->changeProfile($data);
            break;
        default:
           // unsupported GET request
           throw new Exception("PUT Request command '$this->command' is not currently supported by this User API.");
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
        case 'changepassword':
            $oldPassword = Utilities::getArrayValue('oldpassword', $data, '');
            //$oldPassword = Utilities::decryptPassword($oldPassword);
            $newPassword = Utilities::getArrayValue('newpassword', $data, '');
            //$newPassword = Utilities::decryptPassword($newPassword);
            $result = $this->changePassword($oldPassword, $newPassword);
            break;
        case 'changeprofile':
            $result = $this->changeProfile($data);
            break;
        default:
           // unsupported GET request
           throw new Exception("MERGE Request command '$this->command' is not currently supported by this User API.");
           break;
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function actionDelete()
    {
        throw new Exception("DELETE Request is not currently supported by this User API.");
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
            $result = ServiceHandler::getInstance()->getServiceObject('Email')->sendEmail($to, '', '', $subject, $body, $html);
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
            $result = ServiceHandler::getInstance()->getServiceObject('Email')->sendEmailToAdmin('', '', $subject, $body, $html);
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
            $result = ServiceHandler::getInstance()->getServiceObject('Email')->sendEmail($to, '', '', $subject, $body, $html);
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
            $result = ServiceHandler::getInstance()->getServiceObject('Email')->sendEmail($to, '', '', $subject, $body, $html);
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
            $result = ServiceHandler::getInstance()->getServiceObject('Email')->sendEmail($to, '', '', $subject, $body, $html);
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
            $result = ServiceHandler::getInstance()->getServiceObject('Email')->sendEmailToAdmin('', '', $subject, $body, $html);
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
            throw new Exception("[InvalidParam]: Login request is missing required username.");
        }
        if (empty($password)) {
            throw new Exception("[InvalidParam]: Login request is missing required password.");
        }

        try {
            $db = $this->nativeDb;
            $pwd_md5 = md5($password);
            $query = "username='$username' and password='$pwd_md5' and confirm_code='y'";
            $fields = 'id,full_name,first_name,last_name,username,email,phone,';
            $fields .= 'is_active,is_sys_admin,role_id,created_date,created_by_id,last_modified_date,last_modified_by_id';
            $result = $db->retrieveSqlRecordsByFilter('user', $fields, $query, 1);
            unset($result['total']);
            if (count($result) < 1) {
                // Check if the password is wrong
                $query = "username='$username' and confirm_code='y'";
                $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new Exception("Either the username or password supplied does not match system records.");
                }
                $query = "username='$username'";
                $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new Exception("Login registration has not been confirmed.");
                }
                throw new Exception("Either the username or password supplied does not match system records.");
            }
            $userInfo = $result[0];
            if (!$userInfo['is_active']) {
                throw new Exception("The login with username '$username' is not currently active.");
            }
            unset($userInfo['is_active']);

            $isSysAdmin = Utilities::boolval(Utilities::getArrayValue('is_sys_admin', $userInfo, false));
            $roleId = Utilities::getArrayValue('role_id', $userInfo, '');
            $roleId = trim(trim($roleId, ',')); // todo
            if (empty($roleId) && !$isSysAdmin) {
                throw new Exception("The username '$username' has not been assigned a role.");
            }
            unset($userInfo['role_id']);

            $data = $userInfo; // session data
            $allowedApps = array();
            if (!empty($roleId)) {
                $result = $db->retrieveSqlRecordsByIds('role', $roleId, 'id', '');
                if (0 >= count($result) && !$isSysAdmin) {
                    throw new Exception("The username '$username' has not been assigned a valid role.");
                }
                else {
                    $role = $result[0];
                    $allowedAppIds = trim(Utilities::getArrayValue('app_ids', $role, ''), ',');
                    try {
                        $roleApps = array();
                        if (!empty($allowedAppIds)) {
                            $appFields = 'id,name,label,description,url,is_url_external,app_group_ids,is_active';
                            $temp = $db->retrieveSqlRecordsByIds('app', $allowedAppIds, 'id', $appFields);
                            foreach($temp as $app) {
                                $roleApps[] = array('id'=>$app['id'], 'name'=>$app['name']);
                                if ($app['is_active'])
                                    $allowedApps[] = $app;
                            }
                        }
                        $role['apps'] = $roleApps;
                        unset($role['app_ids']);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                    $permsFields = 'service_id,service,component,read,create,update,delete';
                    $permQuery = "role_id='$roleId'";
                    $perms = $db->retrieveSqlRecordsByFilter('role_service_access', $permsFields, $permQuery);
                    unset($perms['total']);
                    $role['services'] = $perms;
                }
                $userInfo['role'] = $role;
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

            $apps = $allowedApps;
            if ($isSysAdmin) {
                $appFields = 'id,name,label,description,url,is_url_external,app_group_ids';
                $apps = $db->retrieveSqlRecordsByFilter('app', $appFields, "is_active = '1'");
                unset($apps['total']);
            }
            $appGroups = $db->retrieveSqlRecordsByFilter('app_group', 'id,name,description');
            unset($appGroups['total']);
            $noGroupApps = array();
            foreach ($apps as $a_key=>$app) {
                $groupIds = Utilities::getArrayValue('app_group_ids', $app, '');
                $groupIds = array_map('trim', explode(',', trim($groupIds, ',')));
                $found = false;
                foreach ($appGroups as $g_key=>$group) {
                    $groupId = Utilities::getArrayValue('id', $group, '');
                    if (false !== array_search($groupId, $groupIds)) {
                        $found = true;
                        $temp = Utilities::getArrayValue('apps', $group, array());
                        $temp[] = $app;
                        $appGroups[$g_key]['apps'] = $temp;
                    }
                }
                if (!$found) {
                    $noGroupApps[] = $app;
                }
            }
            // clean out any empty groups
            foreach ($appGroups as $g_key=>$group) {
                $apps = Utilities::getArrayValue('apps', $group, null);
                if (!isset($apps) || empty($apps)) {
                    unset($appGroups[$g_key]);
                }
            }
            $appGroups = array_values($appGroups); // reset indexing
            $data['app_groups'] = $appGroups;
            $data['no_group_apps'] = $noGroupApps;

            return $data;
        }
        catch (Exception $ex) {
            throw new Exception("Error logging in.\n{$ex->getMessage()}");
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
                throw new Exception("[INVALIDSESSION]: Ticket used for session generation is too old.", 401);
            }
        }

        try {
            $db = $this->nativeDb;
            $fields = 'id,full_name,first_name,last_name,username,email,phone,';
            $fields .= 'is_active,is_sys_admin,role_id,created_date,created_by_id,last_modified_date,last_modified_by_id';
            $result = $db->retrieveSqlRecordsByIds('user', $userId, 'id', $fields);
            if (count($result) < 1) {
                throw new Exception("The user identified in the ticket does not exist in the system.");
            }
            $userInfo = $result[0];
            if (!$userInfo['is_active']) {
                throw new Exception("The user identified in the ticket is not currently active.");
            }
            unset($userInfo['is_active']);

            $isSysAdmin = Utilities::boolval(Utilities::getArrayValue('is_sys_admin', $userInfo, false));
            $roleId = Utilities::getArrayValue('role_id', $userInfo, '');
            $roleId = trim(trim($roleId, ',')); // todo
            if (empty($roleId) && !$isSysAdmin) {
                throw new Exception("The user identified in the ticket has not been assigned a role.");
            }
            unset($userInfo['role_id']);

            $data = $userInfo;
            $allowedApps = array();
            if (!empty($roleId)) {
                $result = $db->retrieveSqlRecordsByIds('role', $roleId, 'id', '');
                if (0 >= count($result) && !$isSysAdmin) {
                    throw new Exception("The user identified in the ticket has not been assigned a valid role.");
                }
                else {
                    $role = $result[0];
                    $allowedAppIds = trim(Utilities::getArrayValue('app_ids', $role, ''), ',');
                    try {
                        $roleApps = array();
                        if (!empty($allowedAppIds)) {
                            $appFields = 'id,name,label,description,url,is_url_external,app_group_ids,is_active';
                            $temp = $db->retrieveSqlRecordsByIds('app', $allowedAppIds, 'id', $appFields);
                            foreach($temp as $app) {
                                $roleApps[] = array('id'=>$app['id'], 'name'=>$app['name']);
                                if ($app['is_active'])
                                    $allowedApps[] = $app;
                            }
                        }
                        $role['apps'] = $roleApps;
                        unset($role['app_ids']);
                    }
                    catch (Exception $ex) {
                        throw $ex;
                    }
                    $permsFields = 'service_id,service,component,read,create,update,delete';
                    $permQuery = "role_id='$roleId'";
                    $perms = $db->retrieveSqlRecordsByFilter('role_service_access', $permsFields, $permQuery);
                    unset($perms['total']);
                    $role['services'] = $perms;
                }
                $userInfo['role'] = $role;
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

            $apps = $allowedApps;
            if ($isSysAdmin) {
                $appFields = 'id,name,label,description,url,is_url_external,app_group_ids';
                $apps = $db->retrieveSqlRecordsByFilter('app', $appFields, "is_active = '1'");
                unset($apps['total']);
            }
            $data['apps'] = $apps;
            $appGroups = $db->retrieveSqlRecordsByFilter('app_group', 'id,name,description');
            unset($appGroups['total']);
            $noGroupApps = array();
            foreach ($apps as $a_key=>$app) {
                $groupIds = Utilities::getArrayValue('app_group_ids', $app, '');
                $groupIds = array_map('trim', explode(',', trim($groupIds, ',')));
                $found = false;
                foreach ($appGroups as $g_key=>$group) {
                    $groupId = Utilities::getArrayValue('id', $group, '');
                    if (false !== array_search($groupId, $groupIds)) {
                        $found = true;
                        $temp = Utilities::getArrayValue('apps', $group, array());
                        $temp[] = $app;
                        $appGroups[$g_key]['apps'] = $temp;
                    }
                }
                if (!$found) {
                    $noGroupApps[] = $app;
                }
            }
            // clean out any empty groups
            foreach ($appGroups as $g_key=>$group) {
                $apps = Utilities::getArrayValue('apps', $group, null);
                if (!isset($apps) || empty($apps)) {
                    unset($appGroups[$g_key]);
                }
            }
            $appGroups = array_values($appGroups); // reset indexing
            $data['app_groups'] = $appGroups;
            $data['no_group_apps'] = $noGroupApps;

            return $data;
        }
        catch (Exception $ex) {
            $this->userLogout();
            throw new Exception("Error generating session.\n{$ex->getMessage()}");
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
        $fields = array('username' => $username, 'email' => $email, 'password' => md5($password));
        $fields['first_name'] = (!empty($first_name)) ? $first_name : $username;
        $fields['last_name'] = (!empty($last_name)) ? $last_name : $username;
        $fullName = (!empty($first_name) && !empty($last_name)) ? $first_name . ' ' . $last_name : $username;
        $fields['full_name'] = $fullName;
        $fields['confirm_code'] = $confirmCode;
        $record = Utilities::array_key_lower($fields);
        $db = $this->nativeDb;
        try {
            if (empty($username)) {
                throw new Exception("The username field for User can not be empty.");
            }
            $result = $db->retrieveSqlRecordsByFilter('user', 'id', "username = '$username'", 1);
            unset($result['total']);
            if (count($result) > 0) {
                throw new Exception("A User already exists with the username '$username'.");
            }
            $result = $db->createSqlRecord('user', $record, 'id');
        }
        catch (Exception $ex) {
            throw new Exception("Failed to register new user!\n{$ex->getMessage()}");
        }

        try {
            $this->sendUserConfirmationEmail($email, $confirmCode, $fullName);
        }
        catch (Exception $ex) {
            // need to remove from database here, otherwise they can't register again
            $db->deleteSqlRecordsByIds('user', $result[0]['id'], 'id');
            throw new Exception("Failed to register new user!\nError sending registration email.");
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
            $result = $db->retrieveSqlRecordsByFilter('user', 'id,full_name,email', "confirm_code='$code'", 1);
            unset($result['total']);
            if (count($result) < 1) {
                throw new Exception("Invalid confirm code.");
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error validating confirmation.\n{$ex->getMessage()}");
        }

        $result[0]['confirm_code'] = 'y';
        try {
            $record = Utilities::array_key_lower(array('fields' => $result[0]));
            $db->updateSqlRecords('user', $record, 'id');
        }
        catch (Exception $ex) {
            throw new Exception("Error updating confirmation.\n{$ex->getMessage()}");
        }

        try {
            $fullName = $result[0]['full_name'];
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
            $result = $db->retrieveSqlRecordsByFilter('user', 'security_question,email,full_name', $query, 1);
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
            $fullName = Utilities::getArrayValue('full_name', $userInfo, '');
            if (!empty($email) && !empty($fullName)) {
                $this->sendResetPasswordLink($email, $fullName);
            }

            return '';
        }
        catch (Exception $ex) {
            throw new Exception("Error sending new password.\n{$ex->getMessage()}");
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
                    throw new Exception("The user has not confirmed registration.");
                }
                throw new Exception("The supplied answer to the security question is invalid.");
            }

            $userId = $result[0]['id'];
            $timestamp = time();
            $ticket = Utilities::encryptCreds("$userId,$timestamp", "gorilla");

            return $this->userSession($ticket);
        }
        catch (Exception $ex) {
            throw new Exception("Error processing security answer.\n{$ex->getMessage()}");
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
                throw new Exception("Reset code could not be found. Please contact your administrator.");
            }
            $userInfo = array('password' => md5($new_password));
            $result = $db->updateSqlRecordsByIds('user', $userInfo, $result[0]['id'], 'id');
            if (count($result) < 1) {
                throw new Exception("The user identified in the ticket does not exist in the system.");
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
            $pwdmd5 = md5($old_password);
            $query = "id='$userId' and password='$pwdmd5' and confirm_code='y'";
            $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
            unset($result['total']);
            if (count($result) < 1) {
                // Check if the password is wrong
                $query = "id='$userId' and confirm_code='y'";
                $result = $db->retrieveSqlRecordsByFilter('user', 'id', $query, 1);
                unset($result['total']);
                if (count($result) > 0) {
                    throw new Exception("The password supplied does not match.");
                }
                throw new Exception("Login registration has not been confirmed.");
            }
            $userInfo = array('password' => md5($new_password));
            $result = $db->updateSqlRecordsByIds('user', $userInfo, $userId, 'id');
            if (count($result) < 1) {
                throw new Exception("The user identified in the ticket does not exist in the system.");
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
                throw new Exception("The user identified in the ticket does not exist in the system.");
            }
        }
        catch (Exception $ex) {
            throw $ex;
        }

        return array('status' => true);
    }

}
