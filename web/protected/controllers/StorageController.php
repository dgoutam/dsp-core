<?php
/**
 *  Generic controller for streaming content from storage services
 */
class StorageController extends Controller
{

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array();
    }

    /**
     *
     */
    public function actionGet()
    {
		$service = (isset($_GET['service']) ? $_GET['service'] : '');
        $path = (isset($_GET['path']) ? $_GET['path'] : '');
        try {
            $service = ServiceHandler::getServiceObject($service);
			switch ( $service->getType() )
			{
				case 'Local File Storage':
				case 'Remote File Storage':
					$service->streamFile($path);
					break;
			}
            Yii::app()->end();
        }
        catch (\Exception $ex) {
            die($ex->getMessage());
        }
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        Yii::app()->end();
    }

}
