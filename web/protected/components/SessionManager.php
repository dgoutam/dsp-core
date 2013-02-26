<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage SessionManager
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

// 1/10 api calls will cleanup sessions
ini_set('session.gc_divisor', 10);
// 10 minutes for debug
ini_set('session.gc_maxlifetime', 600);

class SessionManager
{
    /**
     * @var SessionManager
     */
    private static $_instance = null;

    /**
     * @var null
     */
    private static $_userId = null;

    /**
     * @var \CDbConnection
     */
    protected $_sqlConn;

    /**
     * @var int
     */
    protected $_driverType = DbUtilities::DRV_OTHER;

    /**
     *
     */
    public function __construct()
    {
        $this->_sqlConn = Yii::app()->db;
        $this->_driverType = DbUtilities::getDbDriverType($this->_sqlConn);

        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    /**
     *
     */
    public function __destruct()
    {
        session_write_close(); // IMPORTANT!
    }

    /**
     * Gets the static instance of this class.
     *
     * @return SessionManager
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new SessionManager();
        }

        return self::$_instance;
    }

    /**
     * @return bool
     */
    public function open()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param $id
     * @return mixed|string
     */
    public function read($id)
    {
        try {
            Utilities::markTimeStart('SESS_TIME');
            if (!$this->_sqlConn->active) $this->_sqlConn->active = true;
            $command = $this->_sqlConn->createCommand();
            $command->select('data')->from('session')->where(array('in', 'id', array($id)));
            $result = $command->queryRow();
            Utilities::markTimeStop('SESS_TIME');
            if (!empty($result)) {
                $data = (isset($result['data'])) ? $result['data'] : '';
                if (!empty($data)) {
                    $data = unserialize(base64_decode($data));
                    return $data;
                }
            }
        }
        catch (Exception $ex) {
            Utilities::markTimeStop('SESS_TIME');
            error_log($ex->getMessage());
        }

        return '';
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function write($id, $data)
    {
        try {
            $data = base64_encode(serialize($data));
            $start_time = time();
            $params = array($id, $start_time, $data);
            switch ($this->_driverType) {
            case DbUtilities::DRV_SQLSRV:
                $sql = "{call UpdateOrInsertSession(?,?,?)}";
                break;
            case DbUtilities::DRV_MYSQL:
                $sql = "call UpdateOrInsertSession(?,?,?)";
                break;
            default:
                $sql = "call UpdateOrInsertSession(?,?,?)";
                break;
            }
            Utilities::markTimeStart('SESS_TIME');
            if (!$this->_sqlConn->active) $this->_sqlConn->active = true;
            $command = $this->_sqlConn->createCommand($sql);
            $result = $command->execute($params);
            Utilities::markTimeStop('SESS_TIME');
            return true;
        }
        catch (Exception $ex) {
            Utilities::markTimeStop('SESS_TIME');
            error_log($ex->getMessage());
        }

        return false;
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id)
    {
        try {
            Utilities::markTimeStart('SESS_TIME');
            if (!$this->_sqlConn->active) $this->_sqlConn->active = true;
            $command = $this->_sqlConn->createCommand();
            $command->delete('session', array('in', 'Id', array($id)));
            Utilities::markTimeStop('SESS_TIME');
            return true;
        }
        catch (Exception $ex) {
            Utilities::markTimeStop('SESS_TIME');
            error_log($ex->getMessage());
        }

        return false;
    }

    /**
     * @param $lifeTime
     * @return bool
     */
    public function gc($lifeTime)
    {
        try {
            $expired = time() - $lifeTime;
            Utilities::markTimeStart('SESS_TIME');
            if (!$this->_sqlConn->active) $this->_sqlConn->active = true;
            $command = $this->_sqlConn->createCommand();
            $command->delete('session', "start_time < $expired");
            Utilities::markTimeStop('SESS_TIME');
            return true;
        }
        catch (Exception $ex) {
            Utilities::markTimeStop('SESS_TIME');
            error_log($ex->getMessage());
        }

        return false;
    }
    
    // helper functions

    /**
     * @return string
     * @throws Exception
     */
    public static function validateSession()
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['public']) || empty($_SESSION['public'])) {
            throw new Exception("There is no valid session for the current request.", ErrorCodes::UNAUTHORIZED);
        }

        $userId = (isset($_SESSION['public']['id'])) ? $_SESSION['public']['id'] : '';
        if (empty($userId)) {
            throw new Exception("There is no valid user data in the current session.", ErrorCodes::UNAUTHORIZED);
        }
        return $userId;
    }

    /**
     * @param $request
     * @param $service
     * @param string $component
     * @throws Exception
     */
    public static function checkPermission($request, $service, $component = '')
    {
        $userId = static::validateSession();
        $admin = (isset($_SESSION['public']['is_sys_admin'])) ? $_SESSION['public']['is_sys_admin'] : false;
        $roleInfo = (isset($_SESSION['public']['role'])) ? $_SESSION['public']['role'] : array();
        if (empty($roleInfo)) {
            if (!$admin) {
                // no role assigned, if not sys admin, denied service
                throw new Exception("A valid user role or system administrator is required to access services.", ErrorCodes::FORBIDDEN);
            }
            return; // no need to check role
        }

        // check if app allowed in role
        $appName = (isset($GLOBALS['app_name'])) ? $GLOBALS['app_name'] : '';
        if (empty($appName)) {
            throw new Exception("A valid application name is required to access services.", ErrorCodes::BAD_REQUEST);
        }

        $apps = Utilities::getArrayValue('apps', $roleInfo, null);
        if (!is_array($apps) || empty($apps)) {
            throw new Exception("Access to application '$appName' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN);
        }
        $found = false;
        foreach ($apps as $app) {
            $temp = Utilities::getArrayValue('api_name', $app);
            if (0 === strcasecmp($appName, $temp)) {
                $found = true;
            }
        }
        if (!$found) {
            throw new Exception("Access to application '$appName' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN);
        }
        /*
             // see if we need to deny access to this app
             $result = $db->retrieveSqlRecordsByIds('app', $appName, 'name', 'id,is_active');
             if ((0 >= count($result)) || empty($result[0])) {
                 throw new Exception("The application '$appName' could not be found.");
             }
             if (!$result[0]['is_active']) {
                 throw new Exception("The application '$appName' is not currently active.");
             }
             $appId = $result[0]['id'];
             // is this app part of the role's allowed apps
             if (!empty($allowedAppIds)) {
                 if (!Utilities::isInList($allowedAppIds, $appId, ',')) {
                     throw new Exception("The application '$appName' is not currently allowed by this role.");
                 }
             }
         */

        $services = Utilities::getArrayValue('services', $roleInfo, null);
        if (!is_array($services) || empty($services)) {
            throw new Exception("Access to service '$service' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN);
        }

        $allAllowed = false;
        $allFound = false;
        $serviceAllowed = false;
        $serviceFound = false;
        foreach ($services as $svcInfo) {
            $theService = Utilities::getArrayValue('service', $svcInfo);
            if (0 === strcasecmp($service, $theService)) {
                $theComponent = Utilities::getArrayValue('component', $svcInfo);
                if (!empty($component)) {
                    if (0 === strcasecmp($component, $theComponent)) {
                        if (static::isAllowed($request, Utilities::getArrayValue('access', $svcInfo, ''))) {
                            $msg = ucfirst($request) . " access to component '$component' of service '$service' ";
                            $msg .= "is not allowed by this user's role.";
                            throw new Exception($msg, ErrorCodes::FORBIDDEN);
                        }
                        return; // component specific found and allowed, so bail
                    }
                    elseif (empty($theComponent) || ('*' == $theComponent)) {
                        $serviceAllowed = static::isAllowed($request, Utilities::getArrayValue('access', $svcInfo, ''));
                        $serviceFound = true;
                    }
                }
                else {
                    if (empty($theComponent) || ('*' == $theComponent)) {
                        if (static::isAllowed($request, Utilities::getArrayValue('access', $svcInfo, ''))) {
                            $msg = ucfirst($request) . " access to service '$service' ";
                            $msg .= "is not allowed by this user's role.";
                            throw new Exception($msg, ErrorCodes::FORBIDDEN);
                        }
                        return; // service specific found and allowed, so bail
                    }
                }
            }
            elseif ('*' == $theService) {
                $allAllowed = static::isAllowed($request, Utilities::getArrayValue('access', $svcInfo, ''));
                $allFound = true;
            }
        }

        if ($serviceFound) {
            if ($serviceAllowed) {
                return; // service found and allowed, so bail
            }
        }
        elseif ($allFound) {
            if ($allAllowed) {
                return; // all services found and allowed, so bail
            }
        }
        $msg = ucfirst($request) . " access to ";
        if (!empty($component))
            $msg .= "component '$component' of ";
        $msg .= "service '$service' is not allowed by this user's role.";
        throw new Exception($msg, ErrorCodes::FORBIDDEN);
    }

    /**
     * @param $request
     * @param $access
     * @return bool
     */
    protected static function isAllowed($request, $access)
    {
        switch ($request) {
        case 'read':
            switch ($access) {
            case 'Read Only':
            case 'Read and Write':
            case 'Full Access':
                return true;
            }
            break;
        case 'create':
            switch ($access) {
            case 'Write Only':
            case 'Read and Write':
            case 'Full Access':
                return true;
            }
            break;
        case 'update':
            switch ($access) {
            case 'Write Only':
            case 'Read and Write':
            case 'Full Access':
                return true;
            }
            break;
        case 'delete':
            switch ($access) {
            case 'Full Access':
                return true;
            }
            break;
        default:
            break;
        }
        return false;
    }

    /**
     * @param $userId
     */
    public static function setCurrentUserId($userId)
    {
        static::$_userId = $userId;
    }

    /**
     * @return null
     */
    public static function getCurrentUserId()
    {
        if (isset(static::$_userId)) return static::$_userId;

        try {
            static::$_userId = static::validateSession();
            return static::$_userId;
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * @return int|null
     */
    public static function getCurrentRoleId()
    {
        try {
            static::validateSession();
            return (isset($_SESSION['public']['role']['id'])) ? intval($_SESSION['public']['role']['id']) : null;
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * @return string
     */
    public static function getCurrentAppName()
    {
        return (isset($GLOBALS['app_name'])) ? $GLOBALS['app_name'] : '';
    }

}
