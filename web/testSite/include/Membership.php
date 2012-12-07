<?php
/*
    Membership Registration/Login script
    V0.1

    DreamFactory Software, Inc.
*/

require_once("FormValidator.php");
require_once dirname(__FILE__) . '/../../../vendor/autoload.php';
use CloudServicesPlatform\ServiceHandlers\ServiceHandler;

class Membership
{
    protected $dfService;
    
    public $errorMessage;
    
    //-----Initialization -------
    public function __construct()
    {
        $this->errorMessage = '';
        
        // connect to the service
        try {
            $this->dfService = ServiceHandler::getInstance();
        }
        catch (Exception $ex) {
            echo "Failed to start DreamFactory Services.\n{$ex->getMessage()}";
            exit;   // no need to go any further
        }
    }
        
    //-------Main UI Operations ----------------------
    
    public function getState()
    {
        try {
            return $this->dfService->getSystemState();
        }
        catch (Exception $ex) {
            echo "Failed to check initialization of DreamFactory Services.\n{$ex->getMessage()}";
            exit;   // no need to go any further
        }
    }
        
    public function initLogin()
    {
        if (empty($_POST['username'])) {
            $this->handleError("UserName is empty!");
            return false;
        }
        
        if (empty($_POST['password'])) {
            $this->handleError("Password is empty!");
            return false;
        }
        
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        try {
            $this->dfService->initLogin($username, $password);
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }

        return true;
    }
    
    public function initSystem()
    {
        try {
            $this->dfService->initSystem();
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }

        return true;
    }

    public function initAdmin()
    {
        if (!$this->validateUserSubmission()) {
            return false;
        }

        $firstName = static::sanitize($_POST['firstname']);
        $lastName = static::sanitize($_POST['lastname']);
        $email = static::sanitize($_POST['email']);
        $username = static::sanitize($_POST['username']);
        $password = static::sanitize($_POST['password']);

        try {
            $this->dfService->initAdmin($username, $email, $password, $firstName, $lastName);
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }

        return true;
    }

    // User Handlers
    
    public function registerUser()
    {
        if (!$this->validateUserSubmission()) {
            return false;
        }
        
        $firstname = static::sanitize($_POST['firstname']);
        $lastname = static::sanitize($_POST['lastname']);
        $email = static::sanitize($_POST['email']);
        $username = static::sanitize($_POST['username']);
        $password = static::sanitize($_POST['password']);
        
        try {
            $svc = $this->dfService->getServiceObject('User');
            $result = $svc->userRegister($username, $email, $password, $firstname, $lastname);
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }        
        
        return true;
    }

    public function confirmUser()
    {
        $confirmcode = isset($_GET['code'])?$_GET['code']:'';
        if (empty($confirmcode) || strlen($confirmcode) <= 10) {
            $this->handleError("Please provide the confirm code");
            return false;
        }
        
        try {
            $svc = $this->dfService->getServiceObject('User');
            $result = $svc->userConfirm($confirmcode);
            if (count($result) < 1) {
                $this->handleError("Invalid confirm code.");
                return false;
            }
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }
        
        return true;
    }    
    
    public function login()
    {
        if (empty($_POST['username'])) {
            $this->handleError("UserName is empty!");
            return false;
        }
        
        if (empty($_POST['password'])) {
            $this->handleError("Password is empty!");
            return false;
        }
        
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        try {
            $svc = $this->dfService->getServiceObject('User');
            $result = $svc->userLogin($username, $password);
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }
        
        return true;
    }
    
    public function checkLogin()
    {
        return Utilities::validateSession();
    }
    
    public function logOut()
    {
        $svc = $this->dfService->getServiceObject('User');
        return $svc->userLogout();
    }
    
    public function userFullName()
    {
        return isset($_SESSION['public']['FullName'])?$_SESSION['public']['FullName']:'';
    }
    
    public function userEmail()
    {
        return isset($_SESSION['public']['Email'])?$_SESSION['public']['Email']:'';
    }

    public function userIsAdmin()
    {
        return isset($_SESSION['public']['IsSysAdmin'])?filter_var($_SESSION['public']['IsSysAdmin'], FILTER_VALIDATE_BOOLEAN):false;
    }
    
    public function forgotPassword()
    {
        if (empty($_POST['username'])) {
            $this->handleError("UserName is empty!");
            return false;
        }
        $username = $_POST['username'];
        try {
            $svc = $this->dfService->getServiceObject('User');
            $svc->forgotPassword($username);
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }
        return true;
    }
    
    public function resetPassword()
    {
        if (empty($_GET['email'])) {
            $this->handleError("Email is empty!");
            return false;
        }
        if (empty($_GET['code'])) {
            $this->handleError("reset code is empty!");
            return false;
        }
        $email = trim($_GET['email']);
        $code = trim($_GET['code']);
        
        $svc = $this->dfService->getServiceObject('User');
        if ($this->getResetPasswordCode($email) != $code) {
            $this->handleError("Bad reset code!");
            return false;
        }
        
        $user_rec = array();
        if (!$this->getUserFromEmail($email, $user_rec)) {
            return false;
        }
        
        $new_password = $this->resetUserPasswordInDB($user_rec);
        if (!$new_password || empty($new_password)) {
            $this->handleError("Error updating new password");
            return false;
        }
        
        if (!$this->sendNewPassword($user_rec, $new_password)) {
            $this->handleError("Error sending new password");
            return false;
        }
        return true;
    }
    
    public function changePassword()
    {
        if (empty($_POST['oldpwd'])) {
            $this->handleError("Old password is empty!");
            return false;
        }
        if (empty($_POST['newpwd'])) {
            $this->handleError("New password is empty!");
            return false;
        }
        
        $oldpwd = trim($_POST['oldpwd']);
        $newpwd = trim($_POST['newpwd']);
        try {
            $svc = $this->dfService->getServiceObject('User');
            $svc->changePassword($oldpwd, $newpwd);
        }
        catch (Exception $ex) {
            $this->handleError($ex->getMessage());
            return false;
        }
        return true;
    }

    public function getUsers()
    {
        try {
            $result = $this->dfService->retrieveSystemRecordsByFilter('User');
        }
        catch (Exception $ex) {
            $this->handleError("There was an error looking up users.\n{$ex->getMessage()}");
            return '';
        }
        
        return $result;
    }
    
    public function deleteUser($userid)
    {
        try {
            $result = $this->dfService->deleteSystemRecordsByIds('User', $userid);
        }
        catch (Exception $ex) {
            $this->handleError("There was an error deleting users.\n{$ex->getMessage()}");
            return false;
        }
        
        return true;
    }
    
    // Application handlers
    
    public function registerApp()
    {
        if (empty($_POST['name'])) {
           $this->handleError('Applications require a valid directory name.');
           return false;
        }
        if (empty($_POST['label'])) {
           $this->handleError('Applications require a valid displayable name.');
           return false;
        }
        if (empty($_FILES["files"])) {
           $this->handleError('Applications require at least one file.');
           return false;
        }
        
        $formvars = array();
        $formvars['Name'] = static::sanitize($_POST['name']);
        $formvars['Label'] = static::sanitize($_POST['label']);
        $formvars['Description'] = static::sanitize($_POST['desc']);
        $files = $_FILES["files"];
        try {
            $this->dfService->createApp($formvars);
            $this->dfService->addAppFiles($formvars['Name'], $files);
        }
        catch (Exception $ex) {
            $this->handleError("Application failed to load properly.\n{$ex->getMessage()}.");
            return false;
        }
        
        return true;
    }

    public function addAppFiles($appname)
    {
        if (empty($appname)) {
           $this->handleError('Application name can not be empty.');
           return false;
        }
        if (!isset($_FILES['files']) || empty($_FILES['files'])) {
           $this->handleError('Adding files requires at least one file.');
           return false;
        }
        
        try {
            $this->dfService->handleServiceRest('App', 'POST', array($appname, ''), NULL, 'array');
        }
        catch (Exception $ex) {
            $this->handleError("Application files failed to load properly.\n{$ex->getMessage()}.");
            return false;
        }
        
        return true;
    }

    public function deleteAppFile($appname, $filename)
    {
        if (empty($appname)) {
           $this->handleError('Application name can not be empty.');
           return false;
        }
        if (empty($filename)) {
           $this->handleError('File name can not be empty.');
           return false;
        }
        
        $files = array();
        $files[] = $filename;
        try {
            $this->dfService->deleteAppFiles($appname, $files);
        }
        catch (Exception $ex) {
            $this->handleError("Application files failed to delete properly.\n{$ex->getMessage()}.");
            return false;
        }
        
        return true;
    }

    public function getApps()
    {
        try {
            $result = $this->dfService->handleServiceRest('System', 'GET', array('App'), NULL, 'array');
        }
        catch (Exception $ex) {
            $this->handleError("There was an error looking up apps.\n{$ex->getMessage()}");
            return '';
        }
        return $result;
    }
    
    public function deleteApp($name)
    {
        try {
            $result = $this->dfService->deleteAppByName($name);
        }
        catch (Exception $ex) {
            $this->handleError("There was an error deleting the selected app '$name'.\n{$ex->getMessage()}");
            return false;
        }
        
        return true;
    }
    
    public function getAppFiles($name)
    {
        try {
            $result = $this->dfService->handleServiceRest('App', 'GET', array($name, ''), NULL, 'array');
        }
        catch (Exception $ex) {
            $this->handleError("There was an error looking up app files.\n{$ex->getMessage()}");
            return '';
        }
        
        return $result;
    }
    

    //-------Public Helper functions -------------
    
    public function getSelfScript()
    {
        return htmlentities($_SERVER['PHP_SELF']);
    }    
    
    public function safeDisplay($value_name)
    {
        if (empty($_POST[$value_name])) {
            return'';
        }
        return htmlentities($_POST[$value_name]);
    }
    
    public function redirectToURL($url)
    {
        header("Location: $url");
        exit;
    }
    
    public function getSpamTrapInputName()
    {
        return 'sp'.md5('KHGdnbvsgst'.'M1kVi0kE9ouXxF9');
    }
    
    public function getErrorMessage()
    {
        if (empty($this->errorMessage)) {
            return '';
        }
        $errormsg = nl2br(htmlentities($this->errorMessage));
        return $errormsg;
    }
    
    /*
    sanitize() function removes any potential threat from the
    data submitted. Prevents email injections or any other hacker attempts.
    if $remove_nl is true, newline chracters are removed from the input.
    */
    public static function sanitize($str, $remove_nl=true)
    {
        if (get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }

        if ($remove_nl) {
            $injections = array('/(\n+)/i',
                '/(\r+)/i',
                '/(\t+)/i',
                '/(%0A+)/i',
                '/(%0D+)/i',
                '/(%08+)/i',
                '/(%09+)/i'
                );
            $str = preg_replace($injections,'',$str);
        }

        return $str;
    }

    //-------Private Helper functions-----------
    
    protected function handleError($err)
    {
        $this->errorMessage .= $err."\r\n";
    }
        
    protected function resetUserPasswordInDB($user_rec)
    {
        $new_password = substr(md5(uniqid()),0,10);
        
        if (false == $this->changePasswordInDB($user_rec, $new_password)) {
            return false;
        }
        return $new_password;
    }
    
    protected function changePasswordInDB($user_rec, $newpwd)
    {
        $newpwd = DreamFactory_Services::sanitizeForSQL($newpwd);
        
        $query = "Id = '" . $user_rec['id'] . "'";
        $data = array('password' => md5($newpwd));
        try {
            $result = $this->dfService->updateUser($query, $data);
        }
        catch (Exception $ex) {
            $this->handleError("Error updating the password./n{$ex->getMessage()}");
            return false;
        }     
        return true;
    }
    
    protected function getUserFromEmail($email, &$user_rec)
    {
        $email = DreamFactory_Services::sanitizeForSQL($email);
        $query = "email='$email'";
        try {
            $result = $this->dfService->filterUsers('*', $query, 1);
        }
        catch (Exception $ex) {
            $this->handleError("There was an error looking up this user with email: $email.\n{$ex->getMessage()}");
            return false;
        }
        if (count($result) < 1) {
            $this->handleError("There is no user with this email: $email");
            return false;
        }
        $user_rec = $result;
        
        return true;
    }
    
    protected function validateUserSubmission()
    {
        //This is a hidden input field. Humans won't fill this field.
        if (!empty($_POST[$this->getSpamTrapInputName()])) {
            //The proper error is not given intentionally
            $this->handleError("Automated submission prevention: case 2 failed");
            return false;
        }
        
        $validator = new FormValidator();
        $validator->addValidation("email","email","The input for Email should be a valid email value");
        $validator->addValidation("email","req","Please fill in Email");
        $validator->addValidation("username","req","Please fill in UserName");
        $validator->addValidation("password","req","Please fill in Password");
        
        if (!$validator->validateForm()) {
            $error='';
            $error_hash = $validator->getErrors();
            foreach($error_hash as $inpname => $inp_err) {
                $error .= $inpname.':'.$inp_err."\n";
            }
            $this->handleError($error);
            return false;
        }        
        return true;
    }
        
    protected function validateAppSubmission()
    {
        //This is a hidden input field. Humans won't fill this field.
        if (!empty($_POST[$this->getSpamTrapInputName()])) {
            //The proper error is not given intentionally
            $this->handleError("Automated submission prevention: case 2 failed");
            return false;
        }
        
        $validator = new FormValidator();
        $validator->addValidation("name","req","Please fill in Name");
        $validator->addValidation("name","alnum_u","The input for Name should be alpha-numeric or underscores");
        $validator->addValidation("label","req","Please fill in Label");
        
        if (!$validator->validateForm()) {
            $error='';
            $error_hash = $validator->getErrors();
            foreach($error_hash as $inpname => $inp_err) {
                $error .= $inpname.':'.$inp_err."\n";
            }
            $this->handleError($error);
            return false;
        }        
        return true;
    }
        
    
    
}
