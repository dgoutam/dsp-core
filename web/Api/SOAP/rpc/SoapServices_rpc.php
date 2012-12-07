<?php
/*
    SOAP interface
    V0.1

    DreamFactory Software, Inc.
*/

require_once dirname(__FILE__) . '/../DreamFactory/AutoLoader.php';

class SoapServices
{
    protected $dfService;
    
    //-----Initialization -------
    public function __construct()
    {
        // connect to the service
        try {
            $this->dfService = new DreamFactory_Services();
        }
        catch (Exception $ex) {
            echo "Failed to start DreamFactory Services.\n{$ex->getMessage()}";
            exit;   // no need to go any further
        }
    }
        
    public function login($username, $password, $appname)
    {
        try {
            $result = $this->dfService->userLogin($username, $password, $appname);
            $result['result'] = 'success';
            return $result;
        }
        catch (Exception $ex) {
            $out = array('ticket' => '', 
                         'result' => 'error', 
                         'errorstring' => $ex->getMessage(), 
                         'errorcode' => 'Login Failed');
            return $out;
        }
    }
        
    public function logOut()
    {
    }
    
}
