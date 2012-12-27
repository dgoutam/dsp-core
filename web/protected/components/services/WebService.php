<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage WebService
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class WebService extends CommonService implements iRestHandler
{
    /**
     * @var string
     */
    protected $_baseUrl;

    /**
     * @var string
     */
    protected $_parameters;

    /**
     * @var string
     */
    protected $_headers;

    /**
     * Creates a new WebService instance
     *
     * @param array $config configuration array
     * @throws \InvalidArgumentException
     */
    public function __construct($config)
    {
        parent::__construct($config);

        // Validate url setup

        $this->_baseUrl = Utilities::getArrayValue('base_url', $config, '');
        if (empty($this->_baseUrl)) {
            throw new \InvalidArgumentException('Web Service base url can not be empty.');
        }
        $this->_parameters = Utilities::getArrayValue('parameters', $config, '');
        $this->_headers = Utilities::getArrayValue('headers', $config, '');
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    // Controller based methods

    public function actionGet()
    {
        $this->checkPermission('read');

        $path = Utilities::getArrayValue('resource', $_GET, '');
        $param_str = '';
        foreach ($_REQUEST as $key => $value) {
            if (0 == strcmp('_', $key)) continue; // timestamp added by jquery
            $param_str .= (!empty($param_str)) ? '&' : '';
            $param_str .= $key;
            $param_str .= (empty($value)) ? '' : '=' . urlencode($value);
        }
        if (!empty($this->_parameters)) {
            if (!empty($param_str)) $param_str .= '&';
            $param_str .= $this->_parameters;
        }

        $url = $this->_baseUrl . $path . '?' . $param_str;
        $ch = curl_init($url);
        $co = array();
//        $co[CURLOPT_RETURNTRANSFER] = true; // return results as string, otherwise it will go directly to browser

        $co[CURLOPT_HTTPGET] = true; // default but set it just in case

        curl_setopt_array($ch, $co);
        Utilities::markTimeStart('WS_TIME');
        $result = curl_exec($ch);
        Utilities::markTimeStop('WS_TIME');
        if (!$result) {
            error_log(curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        error_log($httpCode);
        curl_close($ch);

        Utilities::markTimeStop('API_TIME');

//      Utilities::logTimers();
        exit; // bail to avoid header error, unless we are reformatting the data
//        return $result;
    }

    public function actionPost()
    {
        $this->checkPermission('create');

        $path = Utilities::getArrayValue('resource', $_GET, '');
        $param_str = '';
        foreach ($_REQUEST as $key => $value) {
            if (0 == strcmp('_', $key)) continue; // timestamp added by jquery
            $param_str .= (!empty($param_str)) ? '&' : '';
            $param_str .= $key;
            $param_str .= (empty($value)) ? '' : '=' . urlencode($value);
        }
        if (!empty($this->_parameters)) {
            if (!empty($param_str)) $param_str .= '&';
            $param_str .= $this->_parameters;
        }

        $url = $this->_baseUrl . $path . '?' . $param_str;
        $ch = curl_init($url);
        $co = array();
//        $co[CURLOPT_RETURNTRANSFER] = true; // return results as string, otherwise it will go directly to browser
        $co[CURLOPT_POST] = true;
        curl_setopt_array($ch, $co);

        Utilities::markTimeStart('WS_TIME');
        $result = curl_exec($ch);
        Utilities::markTimeStop('WS_TIME');
        if (!$result) {
            error_log(curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        error_log($httpCode);
        curl_close($ch);

        Utilities::markTimeStop('API_TIME');

//      Utilities::logTimers();
        exit; // bail to avoid header error, unless we are reformatting the data
//        return $result;
    }

    public function actionPut()
    {
        $request = 'update';
        $this->checkPermission($request);

        $path = Utilities::getArrayValue('resource', $_GET, '');
        $param_str = '';
        foreach ($_REQUEST as $key => $value) {
            if (0 == strcmp('_', $key)) continue; // timestamp added by jquery
            $param_str .= (!empty($param_str)) ? '&' : '';
            $param_str .= $key;
            $param_str .= (empty($value)) ? '' : '=' . urlencode($value);
        }
        if (!empty($this->_parameters)) {
            if (!empty($param_str)) $param_str .= '&';
            $param_str .= $this->_parameters;
        }

        $url = $this->_baseUrl . $path . '?' . $param_str;
        $ch = curl_init($url);
        $co = array();
//        $co[CURLOPT_RETURNTRANSFER] = true; // return results as string, otherwise it will go directly to browser
        $co[CURLOPT_PUT] = true;

        curl_setopt_array($ch, $co);
        Utilities::markTimeStart('WS_TIME');
        $result = curl_exec($ch);
        Utilities::markTimeStop('WS_TIME');
        if (!$result) {
            error_log(curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        error_log($httpCode);
        curl_close($ch);

        Utilities::markTimeStop('API_TIME');

//      Utilities::logTimers();
        exit; // bail to avoid header error, unless we are reformatting the data
//        return $result;
    }

    public function actionMerge()
    {
        $request = 'update';
        $this->checkPermission($request);

        $path = Utilities::getArrayValue('resource', $_GET, '');
        $param_str = '';
        foreach ($_REQUEST as $key => $value) {
            if (0 == strcmp('_', $key)) continue; // timestamp added by jquery
            $param_str .= (!empty($param_str)) ? '&' : '';
            $param_str .= $key;
            $param_str .= (empty($value)) ? '' : '=' . urlencode($value);
        }
        if (!empty($this->_parameters)) {
            if (!empty($param_str)) $param_str .= '&';
            $param_str .= $this->_parameters;
        }

        $url = $this->_baseUrl . $path . '?' . $param_str;
        $ch = curl_init($url);
        $co = array();
//        $co[CURLOPT_RETURNTRANSFER] = true; // return results as string, otherwise it will go directly to browser
        $co[CURLOPT_PUT] = true;

        curl_setopt_array($ch, $co);
        Utilities::markTimeStart('WS_TIME');
        $result = curl_exec($ch);
        Utilities::markTimeStop('WS_TIME');
        if (!$result) {
            error_log(curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        error_log($httpCode);
        curl_close($ch);

        Utilities::markTimeStop('API_TIME');

//      Utilities::logTimers();
        exit; // bail to avoid header error, unless we are reformatting the data
//        return $result;
    }

    public function actionDelete()
    {
        $this->checkPermission('delete');

        // unsupported HTTP verb
        throw new \Exception("HTTP Request type 'DELETE' is not currently supported by this WebService API.");
    }

}
