<?php

/**
 *
 */
class LibController extends Controller
{

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
    public function actionStream()
    {
        $path = (isset($_GET['path']) ? $_GET['path'] : '');
        try {
            if (0 === substr_compare($path, 'web-core/', 0, 9, true)) {
                $key = Yii::app()->basePath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
                $key .= 'public' . DIRECTORY_SEPARATOR . $path;
                if (is_file($key)) {
                    $result = file_get_contents($key);
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime($key)));
                    header('Content-type: ' . FileUtilities::determineContentType($key));
                    header('Content-Length:' . filesize($key));
                    $disposition = 'inline';
                    header("Content-Disposition: $disposition; filename=\"$path\";");
                    echo $result;
                }
                else {
                    $status_header = "HTTP/1.1 404 The specified file '$path' does not exist.";
                    header($status_header);
                    header('Content-type: text/html');
                }
            }
            else {
                $app = ServiceHandler::getInstance()->getServiceObject('lib');
                $app->streamFile($path);
            }
            Yii::app()->end();
        }
        catch (\Exception $ex) {
            die($ex->getMessage());
        }
    }

    /**
     *
     */
    public function actionIndex()
    {
        Yii::app()->end();
    }

}