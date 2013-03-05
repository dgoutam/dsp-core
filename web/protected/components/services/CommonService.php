<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage CommonService
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

abstract class CommonService
{
    /**
     * @var string
     */
    protected $_api_name;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var string
     */
    protected $_description;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var string
     */
    protected $_nativeFormat;

    /**
     * @var boolean
     */
    protected $_isActive;

    /**
     * Creates a new Service instance
     *
     * @param array $config configuration array
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function __construct($config = array())
    {
        // Validate basic configuration
        $this->_api_name = Utilities::getArrayValue('api_name', $config, '');
        if (empty($this->_api_name)) {
            throw new \InvalidArgumentException('Service name can not be empty.');
        }
        $this->_type = Utilities::getArrayValue('type', $config, '');
        if (empty($this->_type)) {
            throw new \InvalidArgumentException('Service type can not be empty.');
        }
        $this->_name = Utilities::getArrayValue('name', $config, '');
        $this->_description = Utilities::getArrayValue('description', $config, '');
        $this->_nativeFormat = Utilities::getArrayValue('native_format', $config, '');
        $this->_isActive = Utilities::boolval(Utilities::getArrayValue('is_active', $config, false));
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->_isActive;
    }

    /**
     * @return string
     */
    public function getApiName()
    {
        return $this->_api_name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return string
     */
    public function getNativeFormat()
    {
        return $this->_nativeFormat;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param $request
     * @param string $component
     */
    protected function checkPermission($request, $component = '')
    {
        SessionManager::checkPermission($request, $this->_api_name, $component);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionSwagger()
    {
        $result = SwaggerUtilities::swaggerBaseInfo($this->_name);
        return $result;
    }
}
