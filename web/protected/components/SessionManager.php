<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage SessionManager
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class SessionManager
{
    /**
     * @var SessionManager
     */
    private static $_instance = null;

    /**
     * @var \CDbConnection
     */
    protected $_sqlConn;

    protected $_driverType = Utilities::DRV_OTHER;

    public function __construct()
    {
        $this->_sqlConn = Yii::app()->db;
        $this->_driverType = Utilities::getDbDriverType($this->_sqlConn->driverName);

        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

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

    public function open()
    {
        return true;
    }

    public function close()
    {
        return true;
    }

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

    public function write($id, $data)
    {
        try {
            $data = base64_encode(serialize($data));
            $start_time = time();
            $params = array($id, $start_time, $data);
            switch ($this->_driverType) {
            case Utilities::DRV_SQLSRV:
                $sql = "{call UpdateOrInsertSession(?,?,?)}";
                break;
            case Utilities::DRV_MYSQL:
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
}
