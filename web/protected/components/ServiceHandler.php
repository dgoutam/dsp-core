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
 * @subpackage Services
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory Software, Inc. (http://www.dreamfactory.com)
 * @license    http://phpazure.codeplex.com/license
 * @version    $Id: ServiceHandler.php 66505 2012-04-02 08:45:51Z unknown $
 */

/**
 *
 */
class ServiceHandler
{
    /**
     * @var ServiceHandler
     */
    private static $_instance = null;

    /**
     * Services
     *
     * array of created services
     *
     * @access private
     * @var array
     */
    private $_services = array();

    /**
     * SQL Session Service
     *
     * storing session information in the sql db
     *
     * @access protected
     * @var object
     */
    protected $sessionSvc;

    /**
     * Creates a new DreamFactory_Services instance
     *
     */
    public function __construct()
    {
        // need this running at startup
        try {
            $this->sessionSvc = new SessionSvc();
        }
        catch (Exception $ex) {
            throw new Exception("Failed to create session service.\n{$ex->getMessage()}");
        }

        // create services as needed, store local pointer in array for speed
        $this->_services = array();
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        if (!empty($this->_services)) {
            foreach ($this->_services as $key => $service) {
                unset($this->_services[$key]);
            }
            $this->_services = null;
        }
        unset($this->sessionSvc);
    }

    /**
     * Gets the static instance of this class.
     *
     * @return ServiceHandler
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new ServiceHandler();
        }

        return self::$_instance;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getServiceListing()
    {
        try {
            $criteria = new CDbCriteria(array('select' => 'name,label'));
            $result = Service::model()->findAll($criteria);
            $out = array();
            foreach ($result as $service) {
                $out[] = array('name' => $service->name, 'label' => $service->label);
            }

            return array('resource' => $out);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Retrieves the record of the particular service
     *
     * @access public
     * @param string $name
     * @return array The service record array
     * @throws Exception if retrieving of service is not possible
     */
    public function getService($name)
    {
        try {
            $criteria = new CDbCriteria(array('condition' => 'name=:name',
                                              'params' => array(':name' => $name)));
            $result = Service::model()->find($criteria);
            if (isset($result)) {
                return $result->attributes;
            }
            return array();
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Retrieves the pointer to the particular service handler
     *
     * If the service is already created, it just returns the private class
     * member that holds the pointer, otherwise it calls the constructor for
     * the new service, passing in parameters based on the stored configuration settings.
     *
     * @access public
     * @param string $name
     * @param boolean $check_active Throws an exception if true and the service is not active.
     * @return object The new or previously constructed XXXSvc
     * @throws Exception if construction of service is not possible
     */
    public function getServiceObject($name, $check_active=false)
    {
        if (empty($name)) {
            throw new Exception("Failed to launch service, no service name given.");
        }

        // if it hasn't been created, do so
        $service = Utilities::getArrayValue($name, $this->_services, null);
        if (isset($service) && !empty($service)) {
            return $service;
        }

        try {
            $record = $this->getService($name);
            switch (strtolower($name)) {
            case 'app':
                $service = new ApplicationSvc($record);
                break;
            case 'attachment':
                $service = new AttachmentSvc($record);
                break;
            case 'db':
                $service = new DatabaseSvc($record);
                break;
            case 'doc':
                $service = new DocumentSvc($record);
                break;
            case 'email':
                $service = new EmailSvc($record);
                break;
            case 'lib':
                $service = new LibrarySvc($record);
                break;
            case 'session':
                $service = $this->sessionSvc;
                break;
            case 'system':
                $service = new SystemSvc($record);
                break;
            case 'user':
                $service = new UserSvc($record);
                break;
            default:
                $type = Utilities::getArrayValue('type', $record, '');
                switch ($type) {
                case 'Remote Web Service':
                    $service = new WebService($record);
                    break;
                case 'Local File Storage':
                case 'Remote File Storage':
                    $service = new CommonFileSvc($record);
                    break;
                case 'Remote SQL DB':
                    $service = new DatabaseSvc($record);
                    break;
                case 'Local Email Service':
                case 'Remote Email Service':
                    $service = new EmailSvc($record);
                    break;
                default:
                    throw new Exception("Failed to launch service, unknown type value '$type' in service record.");
                    break;
                }
                break;
            }
            $this->_services[$name] = $service;
        }
        catch (Exception $ex) {
            throw new Exception("Failed to launch service '$name'.\n{$ex->getMessage()}");
        }

        if ($check_active && !$service->getIsActive()) {
            throw new Exception("Requested service '$name' is not active.");
        }

        return $service;
    }

}
