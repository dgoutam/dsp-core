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
    protected $_name;

    /**
     * @var string
     */
    protected $_label;

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
        $this->_name = Utilities::getArrayValue('name', $config, '');
        if (empty($this->_name)) {
            throw new \InvalidArgumentException('Service name can not be empty.');
        }
        $this->_type = Utilities::getArrayValue('type', $config, '');
        if (empty($this->_type)) {
            throw new \InvalidArgumentException('Service type can not be empty.');
        }
        $this->_label = Utilities::getArrayValue('label', $config, '');
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
    public function getLabel()
    {
        return $this->_label;
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
        Utilities::checkPermission($request, $this->_name, $component);
    }
}
