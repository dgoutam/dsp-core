<?php

/**
 * REST API router and controller
 */
class RestController extends Controller
{
    // Members

    /**
     * Default response format
     * either 'json' or 'xml'
     */
    private $format = 'json';

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array();
    }

    // Actions

    /**
     *
     */
    public function actionIndex()
    {
        try {
            $svc = ServiceHandler::getInstance();
            $result = $svc->getServiceListing();
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    /**
     *
     */
    public function actionGet($service='')
    {
        try {
            $svc = ServiceHandler::getInstance();
            $svcObj = $svc->getServiceObject($service);
            $result = $svcObj->actionGet();

            $type = $svcObj->getType();
            if (0 === strcasecmp($type, 'Remote Web Service')) {
                $nativeFormat = $svcObj->getNativeFormat();
                if (0 !== strcasecmp($nativeFormat, $this->format)) {
                    // reformat the code here
                }
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    /**
     *
     */
    public function actionPost($service='')
    {
        try {
            // check for verb tunneling
            $tunnel_method = (isset($_SERVER['HTTP_X_HTTP_METHOD'])) ? $_SERVER['HTTP_X_HTTP_METHOD'] : '';
            if (empty($tunnel_method)) {
                $tunnel_method = (isset($_REQUEST['method'])) ? $_REQUEST['method'] : '';
            }
            if (!empty($tunnel_method)) {
                switch (strtolower($tunnel_method)) {
                case 'delete':
                    $this->actionDelete($service);
                    break;
                case 'get': // complex retrieves, non-standard
                    $this->actionGet($service);
                    break;
                case 'merge':
                case 'patch':
                    $this->actionMerge($service);
                    break;
                case 'put':
                    $this->actionPut($service);
                    break;
                default:
                    if (!empty($tunnel_method)) {
                        throw new Exception("Unknown verb tunneling method '$tunnel_method' in REST request.");
                    }
                    break;
                }
            }
            $svc = ServiceHandler::getInstance();
            $svcObj = $svc->getServiceObject($service);
            $result = $svcObj->actionPost();

            $type = $svcObj->getType();
            if (0 === strcasecmp($type, 'Remote Web Service')) {
                $nativeFormat = $svcObj->getNativeFormat();
                if (0 !== strcasecmp($nativeFormat, $this->format)) {
                    // reformat the code here
                }
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    /**
     *
     */
    public function actionMerge($service='')
    {
        try {
            $svc = ServiceHandler::getInstance();
            $svcObj = $svc->getServiceObject($service);
            $result = $svcObj->actionMerge();

            $type = $svcObj->getType();
            if (0 === strcasecmp($type, 'Remote Web Service')) {
                $nativeFormat = $svcObj->getNativeFormat();
                if (0 !== strcasecmp($nativeFormat, $this->format)) {
                    // reformat the code here
                }
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    /**
     *
     */
    public function actionPut($service='')
    {
        try {
            $svc = ServiceHandler::getInstance();
            $svcObj = $svc->getServiceObject($service);
            $result = $svcObj->actionMerge();

            $type = $svcObj->getType();
            if (0 === strcasecmp($type, 'Remote Web Service')) {
                $nativeFormat = $svcObj->getNativeFormat();
                if (0 !== strcasecmp($nativeFormat, $this->format)) {
                    // reformat the code here
                }
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    /**
     *
     */
    public function actionDelete($service='')
    {
        try {
            $svc = ServiceHandler::getInstance();
            $svcObj = $svc->getServiceObject($service);
            $result = $svcObj->actionDelete();

            $type = $svcObj->getType();
            if (0 === strcasecmp($type, 'Remote Web Service')) {
                $nativeFormat = $svcObj->getNativeFormat();
                if (0 !== strcasecmp($nativeFormat, $this->format)) {
                    // reformat the code here
                }
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    /**
     * Override base method to do some processing of incoming requests
     *
     * @param CAction $action
     * @return bool
     * @throws Exception
     */
    protected function beforeAction($action)
    {
        $temp = (isset($_REQUEST['format'])) ? strtolower($_REQUEST['format']) : '';
        if (!empty($temp)) {
            $this->format = $temp;
        }

        // determine application if any
        $appName = (isset($_SERVER['HTTP_X_APPLICATION_NAME'])) ? $_SERVER['HTTP_X_APPLICATION_NAME'] : '';
        if (empty($appName)) {
            $appName = (isset($_REQUEST['app_name'])) ? $_REQUEST['app_name'] : '';
        }
        if (empty($appName)) {
            throw new Exception("No application name header or parameter value in REST request.");
        }
        $GLOBALS['app_name'] = $appName;

        // fix removal of trailing slashes from resource
        $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
        if (!empty($resource)) {
            $requestUri = yii::app()->request->requestUri;
            if ((false === strpos($requestUri, '?') &&
                 '/' === substr($requestUri, strlen($requestUri) - 1, 1)) ||
                ('/' === substr($requestUri, strpos($requestUri, '?') - 1, 1))) {
                $resource .= '/';
            }
            $_GET['resource'] = $resource;
        }

        return parent::beforeAction($action);
    }

    /**
     * @param $error
     */
    private function handleErrors($error)
    {
        $result = array("fault" => array("faultString" => htmlentities($error),
                                         "faultCode" => htmlentities('Sender')));
        switch ($this->format) {
        case 'json':
            $result = json_encode($result);
            Utilities::sendJsonResponse($result);
            break;
        case 'xml':
            $result = '<fault>';
            $result .= '<faultString>' . htmlentities($error) . '</faultString>';
            $result .= '<faultCode>' . htmlentities('Sender') . '</faultCode>';
            $result .= '</fault>';
            Utilities::sendXmlResponse($result);
            break;
        }
    }

    /**
     * @param $result
     */
    private function handleResults($result)
    {
        if (0 === strcasecmp('xml', $this->format)) {
            $result = Utilities::arrayToXml('', $result);
        }
        else {
            $result = json_encode($result);
        }
        switch ($this->format) {
        case 'json':
            Utilities::sendJsonResponse($result);
            break;
        case 'xml':
            Utilities::sendXmlResponse($result);
            break;
        }
    }

}