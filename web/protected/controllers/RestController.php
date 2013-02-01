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

    /**
     * Initializes the controller.
     * This method is called by the application before the controller starts to execute.
     */
    public function init()
    {
        // need this running at startup
        try {
            SessionManager::getInstance();
        }
        catch (Exception $ex) {
            $ex = new Exception("Failed to create session service.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR);
            $this->handleErrors($ex);
        }

    }

    // Actions

    /**
     *
     */
    public function actionIndex()
    {
        try {
            // add non-service managers
            $managers = array(array('name' => 'system', 'label' => 'System Configuration'),
                              array('name' => 'user', 'label' => 'User Login'));
            $services = ServiceHandler::getInstance()->getServiceListing();
            $result = array_merge($managers, $services);
            $this->handleResults(array('resources'=>$result));
        }
        catch (Exception $ex) {
            $this->handleErrors($ex);
        }
    }

    /**
     *
     */
    public function actionGet($service='')
    {
        try {
            switch (strtolower($service)) {
            case 'system':
                $result = SystemManager::getInstance()->actionGet();
                break;
            case 'user':
                $result = UserManager::getInstance()->actionGet();
                break;
            default:
                $svcObj = ServiceHandler::getInstance()->getServiceObject($service);
                $result = $svcObj->actionGet();

                $type = $svcObj->getType();
                if (0 === strcasecmp($type, 'Remote Web Service')) {
                    $nativeFormat = $svcObj->getNativeFormat();
                    if (0 !== strcasecmp($nativeFormat, $this->format)) {
                        // reformat the code here
                    }
                }
                break;
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex);
        }
    }

    /**
     *
     */
    public function actionPost($service='')
    {
        try {
            // check for verb tunneling
            $tunnel_method = Utilities::getArrayValue('HTTP_X_HTTP_METHOD', $_SERVER, '');
            if (empty($tunnel_method)) {
                $tunnel_method = Utilities::getArrayValue('method', $_REQUEST, '');
            }
            if (!empty($tunnel_method)) {
                switch (strtolower($tunnel_method)) {
                case 'post': // in case they use it in the header as well
                    break;
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
                        throw new Exception("Unknown tunneling verb '$tunnel_method' in REST request.", ErrorCodes::BAD_REQUEST);
                    }
                    break;
                }
            }
            $code = ErrorCodes::OK;
            switch (strtolower($service)) {
                case 'system':
                    $result = SystemManager::getInstance()->actionPost();
                    $code = ErrorCodes::CREATED;
                    break;
                case 'user':
                    $result = UserManager::getInstance()->actionPost();
                    break;
                default:
                    $svcObj = ServiceHandler::getInstance()->getServiceObject($service);
                    $result = $svcObj->actionPost();

                    $type = $svcObj->getType();
                    if (0 === strcasecmp($type, 'Remote Web Service')) {
                        $nativeFormat = $svcObj->getNativeFormat();
                        if (0 !== strcasecmp($nativeFormat, $this->format)) {
                            // reformat the code here
                        }
                    }
                    break;
            }
            $this->handleResults($result, $code);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex);
        }
    }

    /**
     *
     */
    public function actionMerge($service='')
    {
        try {
            switch (strtolower($service)) {
                case 'system':
                    $result = SystemManager::getInstance()->actionMerge();
                    break;
                case 'user':
                    $result = UserManager::getInstance()->actionMerge();
                    break;
                default:
                    $svcObj = ServiceHandler::getInstance()->getServiceObject($service);
                    $result = $svcObj->actionMerge();

                    $type = $svcObj->getType();
                    if (0 === strcasecmp($type, 'Remote Web Service')) {
                        $nativeFormat = $svcObj->getNativeFormat();
                        if (0 !== strcasecmp($nativeFormat, $this->format)) {
                            // reformat the code here
                        }
                    }
                    break;
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex);
        }
    }

    /**
     *
     */
    public function actionPut($service='')
    {
        try {
            switch (strtolower($service)) {
                case 'system':
                    $result = SystemManager::getInstance()->actionPut();
                    break;
                case 'user':
                    $result = UserManager::getInstance()->actionPut();
                    break;
                default:
                    $svcObj = ServiceHandler::getInstance()->getServiceObject($service);
                    $result = $svcObj->actionPut();

                    $type = $svcObj->getType();
                    if (0 === strcasecmp($type, 'Remote Web Service')) {
                        $nativeFormat = $svcObj->getNativeFormat();
                        if (0 !== strcasecmp($nativeFormat, $this->format)) {
                            // reformat the code here
                        }
                    }
                    break;
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex);
        }
    }

    /**
     *
     */
    public function actionDelete($service='')
    {
        try {
            switch (strtolower($service)) {
                case 'system':
                    $result = SystemManager::getInstance()->actionDelete();
                    break;
                case 'user':
                    $result = UserManager::getInstance()->actionDelete();
                    break;
                default:
                    $svcObj = ServiceHandler::getInstance()->getServiceObject($service);
                    $result = $svcObj->actionDelete();

                    $type = $svcObj->getType();
                    if (0 === strcasecmp($type, 'Remote Web Service')) {
                        $nativeFormat = $svcObj->getNativeFormat();
                        if (0 !== strcasecmp($nativeFormat, $this->format)) {
                            // reformat the code here
                        }
                    }
                    break;
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex);
        }
    }

    /**
     * Override base method to do some processing of incoming requests
     *
     * @param CAction $action
     * @return bool
     * @throws CHttpException
     */
    protected function beforeAction($action)
    {
        $temp = strtolower(Utilities::getArrayValue('format', $_REQUEST, ''));
        if (!empty($temp)) {
            $this->format = $temp;
        }

        // determine application if any
        $appName = Utilities::getArrayValue('HTTP_X_APPLICATION_NAME', $_SERVER, '');
        if (empty($appName)) {
            $appName = Utilities::getArrayValue('app_name', $_REQUEST, '');
        }
        if (empty($appName)) {
            $ex = new Exception("No application name header or parameter value in REST request.", ErrorCodes::BAD_REQUEST);
            $this->handleErrors($ex);
        }
        $GLOBALS['app_name'] = $appName;

        // fix removal of trailing slashes from resource
        $resource = Utilities::getArrayValue('resource', $_GET, '');
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
     * @param Exception $ex
     * @return void
     */
    private function handleErrors(Exception $ex)
    {
        $code = ErrorCodes::getHttpStatusCode($ex->getCode());
        $title = ErrorCodes::getHttpStatusCodeTitle($code);
        $msg = $ex->getMessage();
        $result = array("error" => array(array("message" => htmlentities($msg),
                                               "code" => $code)));
        header("HTTP/1.1 $code $title");
        switch ($this->format) {
        case 'json':
            $result = json_encode($result);
            Utilities::sendJsonResponse($result);
            break;
        case 'xml':
            $result = Utilities::arrayToXml('', $result);
            Utilities::sendXmlResponse($result);
            break;
        }
        Yii::app()->end();
    }

    /**
     * @param $result
     * @param int $code
     */
    private function handleResults($result, $code=200)
    {
        $code = ErrorCodes::getHttpStatusCode($code);
        $title = ErrorCodes::getHttpStatusCodeTitle($code);
        header("HTTP/1.1 $code $title");
        switch ($this->format) {
        case 'json':
            $result = json_encode($result);
            Utilities::sendJsonResponse($result);
            break;
        case 'xml':
            $result = Utilities::arrayToXml('', $result);
            Utilities::sendXmlResponse($result);
            break;
        }
        Yii::app()->end();
    }

}