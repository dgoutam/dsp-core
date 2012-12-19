<?php
use CloudServicesPlatform\ServiceHandlers\ServiceHandler;
use CloudServicesPlatform\Storage\Database\PdoSqlDbSvc;
use CloudServicesPlatform\Utilities\Utilities;

class DbController extends Controller
{
    // Members

    /**
     * @var PdoSqlDbSvc
     */
    protected $sqlDb;

    public function init()
   	{
        parent::init();
        try {
            $this->sqlDb = new PdoSqlDbSvc();
        }
        catch (\Exception $ex) {
            throw new \Exception("Failed to create database service.\n{$ex->getMessage()}");
        }
   	}

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array();
    }

    // Actions
    public function actionIndex()
    {
        try {
            $this->detectCommonParams();
            $service = (isset($_GET['service']) ? $_GET['service'] : '');
            if (empty($service)) {
                $result = ServiceHandler::getInstance()->getServiceListing();
                if (0 === strcasecmp('xml', $this->format)) {
                    $result = Utilities::arrayToXml('', $result);
                }
                else {
                    $result = json_encode($result);
                }
            }
            else {
                $resource = (isset($_GET['resource']) ? $_GET['resource'] : null);
                $resource = (!empty($resource)) ? explode('/', $resource) : array();
                $result = ServiceHandler::getInstance()->handleRestRequest($service, 'GET', $resource, $_REQUEST, $this->format);
            }
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();
    }

    public function actionList()
    {
        try {
            $this->detectCommonParams();
            $service = (isset($_GET['service']) ? $_GET['service'] : '');
            $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
            $resource = (!empty($resource)) ? explode('/', $resource) : array();
            $result = ServiceHandler::getInstance()->handleRestRequest($service, 'GET', $resource, $_REQUEST, $this->format);
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();

        // Get the respective model instance
        switch ($_GET['service']) {
        case 'label':
            $models = Label::model()->findAll();
            break;
        default:
            // Model not implemented error
            $this->_sendResponse(501, sprintf(
                'Error: Mode <b>list</b> is not implemented for model <b>%s</b>',
                $_GET['model']));
            Yii::app()->end();
        }
        // Did we get some results?
        if (empty($models)) {
            // No
            $this->_sendResponse(200,
                                 sprintf('No items where found for model <b>%s</b>', $_GET['model']));
        }
        else {
            // Prepare response
            $rows = array();
            foreach ($models as $model)
                $rows[] = $model->attributes;
            // Send the response
            $this->_sendResponse(200, CJSON::encode($rows));
        }
    }

    public function actionView()
    {
        try {
            $this->detectCommonParams();
            $service = (isset($_GET['service']) ? $_GET['service'] : '');
            $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
            $resource = (!empty($resource)) ? explode('/', $resource) : array();
            $result = ServiceHandler::getInstance()->handleRestRequest($service, 'GET', $resource, $_REQUEST, $this->format);
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();

        // Check if id was submitted via GET
        if (!isset($_GET['id']))
            $this->_sendResponse(500, 'Error: Parameter <b>id</b> is missing');

        switch ($_GET['model']) {
            // Find respective model
        case 'label':
            $model = Label::model()->findByPk($_GET['id']);
            break;
        default:
            $this->_sendResponse(501, sprintf(
                'Mode <b>view</b> is not implemented for model <b>%s</b>',
                $_GET['model']));
            Yii::app()->end();
        }
        // Did we find the requested model? If not, raise an error
        if (is_null($model))
            $this->_sendResponse(404, 'No Item found with id ' . $_GET['id']);
        else
            $this->_sendResponse(200, CJSON::encode($model));
    }

    public function actionCreate()
    {
        try {
            $this->detectCommonParams();
            // check for verb tunneling
            $tunnel_method = (isset($_SERVER['HTTP_X_HTTP_METHOD'])) ? $_SERVER['HTTP_X_HTTP_METHOD'] : '';
            if (empty($tunnel_method)) {
                $tunnel_method = (isset($_REQUEST['method'])) ? $_REQUEST['method'] : '';
            }
            if (!empty($tunnel_method)) {
                switch (strtolower($tunnel_method)) {
                case 'delete':
                    $this->actionDelete();
                    break;
                case 'get': // complex retrieves, non-standard
                    $this->actionView();
                    break;
                case 'merge':
                case 'patch':
                case 'put':
                    $this->actionUpdate();
                    break;
                default:
                    if (!empty($tunnel_method)) {
                        throw new Exception("Unknown verb tunneling method '$tunnel_method' in REST request.");
                    }
                    break;
                }
            }
            $service = (isset($_GET['service']) ? $_GET['service'] : '');
            $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
            $resource = (!empty($resource)) ? explode('/', $resource) : array();
            $result = ServiceHandler::getInstance()->handleRestRequest($service, 'POST', $resource, $_REQUEST, $this->format);
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();

        switch ($_GET['model']) {
            // Get an instance of the respective model
        case 'label':
            $model = new Label;
            break;
        default:
            $this->_sendResponse(501,
                                 sprintf('Mode <b>create</b> is not implemented for model <b>%s</b>',
                                         $_GET['model']));
            Yii::app()->end();
        }
        // Try to assign POST values to attributes
        foreach ($_POST as $var => $value) {
            // Does the model have this attribute? If not raise an error
            if ($model->hasAttribute($var))
                $model->$var = $value;
            else
                $this->_sendResponse(500,
                                     sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var,
                                             $_GET['model']));
        }
        // Try to save the model
        if ($model->save())
            $this->_sendResponse(200, CJSON::encode($model));
        else {
            // Errors occurred
            $msg = "<h1>Error</h1>";
            $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
            $msg .= "<ul>";
            foreach ($model->errors as $attribute => $attr_errors) {
                $msg .= "<li>Attribute: $attribute</li>";
                $msg .= "<ul>";
                foreach ($attr_errors as $attr_error)
                    $msg .= "<li>$attr_error</li>";
                $msg .= "</ul>";
            }
            $msg .= "</ul>";
            $this->_sendResponse(500, $msg);
        }
    }

    public function actionUpdate()
    {
        try {
            $this->detectCommonParams();
            $service = (isset($_GET['service']) ? $_GET['service'] : '');
            $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
            $resource = (!empty($resource)) ? explode('/', $resource) : array();
            $result = ServiceHandler::getInstance()->handleRestRequest($service, 'MERGE', $resource, $_REQUEST, $this->format);
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();

        // Parse the PUT parameters. This didn't work: parse_str(file_get_contents('php://input'), $put_vars);
        $json = file_get_contents('php://input'); //$GLOBALS['HTTP_RAW_POST_DATA'] is not preferred: http://www.php.net/manual/en/ini.core.php#ini.always-populate-raw-post-data
        $put_vars = CJSON::decode($json, true); //true means use associative array

        switch ($_GET['model']) {
            // Find respective model
        case 'label':
            $model = Label::model()->findByPk($_GET['id']);
            break;
        default:
            $this->_sendResponse(501,
                                 sprintf('Error: Mode <b>update</b> is not implemented for model <b>%s</b>',
                                         $_GET['model']));
            Yii::app()->end();
        }
        // Did we find the requested model? If not, raise an error
        if ($model === null)
            $this->_sendResponse(400,
                                 sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                                         $_GET['model'], $_GET['id']));

        // Try to assign PUT parameters to attributes
        foreach ($put_vars as $var => $value) {
            // Does model have this attribute? If not, raise an error
            if ($model->hasAttribute($var))
                $model->$var = $value;
            else {
                $this->_sendResponse(500,
                                     sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>',
                                             $var, $_GET['model']));
            }
        }
        // Try to save the model
        if ($model->save())
            $this->_sendResponse(200, CJSON::encode($model));
        else
            // Errors occurred
            $msg = "<h1>Error</h1>";
            $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
            $msg .= "<ul>";
            foreach ($model->errors as $attribute => $attr_errors) {
                $msg .= "<li>Attribute: $attribute</li>";
                $msg .= "<ul>";
                foreach ($attr_errors as $attr_error)
                    $msg .= "<li>$attr_error</li>";
                $msg .= "</ul>";
            }
            $msg .= "</ul>";
            $this->_sendResponse(500, $msg);
    }

    public function actionDelete()
    {
        try {
            $this->detectCommonParams();
            $service = (isset($_GET['service']) ? $_GET['service'] : '');
            $resource = (isset($_GET['resource']) ? $_GET['resource'] : '');
            $resource = (!empty($resource)) ? explode('/', $resource) : array();
            $result = ServiceHandler::getInstance()->handleRestRequest($service, 'DELETE', $resource, $_REQUEST, $this->format);
            $this->handleResults($result);
        }
        catch (Exception $ex) {
            $this->handleErrors($ex->getMessage());
        }
        Yii::app()->end();

        switch ($_GET['model']) {
            // Load the respective model
        case 'label':
            $model = Label::model()->findByPk($_GET['id']);
            break;
        default:
            $this->_sendResponse(501,
                                 sprintf('Error: Mode <b>delete</b> is not implemented for model <b>%s</b>',
                                         $_GET['model']));
            Yii::app()->end();
        }
        // Was a model found? If not, raise an error
        if ($model === null)
            $this->_sendResponse(400,
                                 sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                                         $_GET['model'], $_GET['id']));

        // Delete the model
        $num = $model->delete();
        if ($num > 0)
            $this->_sendResponse(200, $num); //this is the only way to work with backbone
        else
            $this->_sendResponse(500,
                                 sprintf("Error: Couldn't delete model <b>%s</b> with ID <b>%s</b>.",
                                         $_GET['model'], $_GET['id']));
    }

    protected function detectCommonParams()
    {
    }

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

    private function handleResults($result)
    {
        switch ($this->format) {
        case 'json':
            Utilities::sendJsonResponse($result);
            break;
        case 'xml':
            Utilities::sendXmlResponse($result);
            break;
        }
    }

    private function _sendResponse($status = 200, $body = '', $content_type = 'text/html')
    {
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);

        // pages with body are easy
        if($body != '')
        {
            // send the body
            echo $body;
        }
        // we need to create the body if none is passed
        else
        {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch($status)
            {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on
            // (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templated in a real-world solution
            $body = '
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
    </head>
    <body>
        <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
        <p>' . $message . '</p>
        <hr />
        <address>' . $signature . '</address>
    </body>
    </html>';

            echo $body;
        }
        Yii::app()->end();
    }

    private function _getStatusCodeMessage($status)
    {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
}