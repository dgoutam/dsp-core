<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout = '//layouts/column1';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu = array();
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs = array();

	protected function beforeAction( $action )
	{
//		//	Get the additional data ready
//		$_logInfo = array(
//			'short_message' => 'dsp request from "' . Pii::getParam( 'dsp_name' ) . '": ' . $action->id,
//			'full_message'  => 'dsp request from "' . Pii::getParam( 'dsp_name' ) . '": ' . $action->id,
//			'level'         => GraylogLevels::Info,
//			'facility'      => 'dsp/api',
//			'source'        => 'web',
//			'payload'       => null,
//		);
//
//		\Kisma\Core\Utility\Log::info( 'API request', $_logInfo );

		return parent::beforeAction( $action );
	}

}