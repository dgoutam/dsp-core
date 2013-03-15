<?php

/**
 * REST API router and controller
 */
class RestController extends Controller
{
    // Members

    /**
     * Default response format
     * @var string
     * either 'json' or 'xml'
     */
    private $format = 'json';

    /**
     * Swagger controlled get
     * @var bool
     */
    private $swagger = false;

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
            $criteria = new CDbCriteria(array('select' => 'api_name,name,description',
                                              'order' => 'api_name'));
            $result = Service::model()->findAll($criteria);
            if ($this->swagger) {
                $services = array(array('path' => '/user', 'description' => 'User Login'),
                                  array('path' => '/system', 'description' => 'System Configuration'));
                foreach ($result as $service) {
                    $services[] = array('path' => '/'.$service->api_name, 'description' => $service->name);
                }
                $result = SwaggerUtilities::swaggerBaseInfo();
                $result['apis'] = $services;
            }
            else {
                // add non-service managers
                $services = array(array('name' => 'user', 'label' => 'User Login'),
                                  array('name' => 'system', 'label' => 'System Configuration'));
                foreach ($result as $service) {
                    $services[] = array('api_name' => $service->api_name, 'name' => $service->name);
                }
                $result = array('resources'=>$services);
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
    public function actionGet($service='')
    {
        try {
            switch (strtolower($service)) {
            case 'system':
                $svcObj = SystemManager::getInstance();
                break;
            case 'user':
                $svcObj = UserManager::getInstance();
                break;
            default:
                $svcObj = ServiceHandler::getInstance()->getServiceObject($service);
                break;
            }
            if ($this->swagger) {
                $result = $svcObj->actionSwagger();
            }
            else {
                $result = $svcObj->actionGet();
                $type = $svcObj->getType();
                if (0 === strcasecmp($type, 'Remote Web Service')) {
                    $nativeFormat = $svcObj->getNativeFormat();
                    if (0 !== strcasecmp($nativeFormat, $this->format)) {
                        // reformat the code here
                    }
                }
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
                case 'get': // complex retrieves, non-standard
                    $this->actionGet($service);
                    break;
                case 'post': // in case they use it in the header as well
                    break;
                case 'put':
                    $this->actionPut($service);
                    break;
                case 'merge':
                case 'patch':
                    $this->actionMerge($service);
                    break;
                case 'delete':
                    $this->actionDelete($service);
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
            // check for swagger documentation request
            $appName = Utilities::getArrayValue('swagger_app_name', $_REQUEST, '');
            if (!empty($appName)) {
                $this->swagger = true;
            }
            else {
                $ex = new Exception("No application name header or parameter value in REST request.", ErrorCodes::BAD_REQUEST);
                $this->handleErrors($ex);
            }
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
     */
    private function handleErrors(Exception $ex)
    {
        $result = array("error" => array(array("message" => htmlentities($ex->getMessage()),
                                               "code" => $ex->getCode())));
        $this->handleResults($result, $ex->getCode());
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