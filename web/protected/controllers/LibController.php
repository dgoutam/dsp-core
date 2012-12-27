<?php

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
    public function actionStream()
    {
        $path = (isset($_GET['path']) ? $_GET['path'] : '');
        try {
            $app = ServiceHandler::getInstance()->getServiceObject('Lib');
            $app->streamFile($path);
            Yii::app()->end();
        }
        catch (\Exception $ex) {
            die($ex->getMessage());
        }
    }

    public function actionIndex()
    {
        Yii::app()->end();
    }

}