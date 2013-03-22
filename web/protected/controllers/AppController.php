<?php
/**
 *
 */
class AppController extends Controller
{
	/**
	 *
	 */
	public function actionStream()
	{
		$path = ( isset( $_GET['path'] ) ? $_GET['path'] : '' );
		try
		{
			$app = ServiceHandler::getInstance()->getServiceObject( 'app' );
			$app->streamFile( $path );
			Yii::app()->end();
		}
		catch ( \Exception $ex )
		{
			die( $ex->getMessage() );
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
