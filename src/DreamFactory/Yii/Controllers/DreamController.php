<?php
/**
 * DreamController.php
 */
namespace DreamFactory\Yii\Controllers;

use DreamFactory\Yii\Models\Forms\SimpleLoginForm;
use Kisma\Core\Utility\FileSystem;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\GlobFlags;
use InvalidArgumentException;
use RuntimeException;
use DreamFactory\Yii\Models\BaseModel;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Interfaces\ConsumerLike;
use Kisma;
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Inflector;

/**
 * DreamController
 * A base controller for the platform. Supplies login/logout, index, and view actions
 */
class DreamController extends BaseDreamController implements ConsumerLike
{
	//*************************************************************************
	//* Private Members
	//*************************************************************************

	/***
	 * @var array The menu
	 */
	protected $_menu = array();
	/**
	 * @var array The breadcrumbs
	 */
	protected $_breadcrumbs = array();
	/**
	 * @var string If set, this view will be used for redering the login page
	 */
	protected $_customLoginView = null;
	/**
	 * @var string
	 */
	protected $_bootstrapHeader = null;
	/**
	 * @var string
	 */
	protected $_loginFormClass;
	/**
	 * @var boolean $singleViewMode If true, only the 'update' view is called for create and update.
	 */
	protected $_singleViewMode = false;
	/**
	 * @var array
	 */
	protected $_formOptions = array();
	/**
	 * @var array
	 */
	protected $_ajaxColumns = array();

	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Initialize the controller
	 */
	public function init()
	{
		parent::init();

		$this->defaultAction = 'index';
		$this->setLoginFormClass( 'DreamFactory\\Yii\Models\\Forms\\SimpleLoginForm' );
		$this->addUserActions( static::Authenticated, array( 'logout', 'index', 'view' ) );
		$this->addUserActions( static::Guest, array( 'login' ) );
	}

	//********************************************************************************
	//* Public Actions
	//********************************************************************************

	/**
	 * Home page
	 */
	public function actionIndex()
	{
		$this->render( 'index' );
	}

	/**
	 * Displays the login page
	 *
	 * @Route   ("/login",name="_app_login")
	 * @Template()
	 *
	 * @throws \CHttpException
	 * @return void
	 */
	public function actionLogin()
	{
		/** @var $_model SimpleLoginForm */
		$_model = new $this->_loginFormClass;
		$_loginPost = $_loginSuccess = false;
		$_postClass = basename( str_replace( '\\', '/', $this->_loginFormClass ) );

		//	If it is ajax validation request
		if ( isset( $_POST['ajax'] ) && 'login-form' === $_POST['ajax'] )
		{
			echo \CActiveForm::validate( $_model );
			Pii::end();
		}

		//	Collect user input data
		if ( isset( $_POST[$_postClass] ) )
		{
			$_loginPost = true;
			$_model->setAttributes( $_POST[$_postClass], false );

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() )
			{
				if ( $_model->login( !empty( $_model->remember_ind ) ) )
				{
					$this->_afterLogin( $_model );

					if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
					{
						$_returnUrl = Pii::url( '/' );
					}

					$this->redirect( $_returnUrl );

					return;
				}
			}
		}

		//	Display the login form
		$this->render(
			$this->_customLoginView ? : 'login',
			array(
				 'modelName' => $this->_loginFormClass,
				 'model'     => $_model,
				 'loginPost' => $_loginPost,
				 'success'   => $_loginSuccess,
				 'header'    => $this->_bootstrapHeader,
			)
		);
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout( $token = null )
	{
		if ( null !== $token && $token != Pii::request()->getCsrfToken() )
		{
			throw new \CHttpException( 'Bad request.', Kisma\Core\Enums\HttpResponse::BadRequest );
		}

		Pii::user()->logout();

		$this->redirect( Pii::app()->getHomeUrl() );
	}

	/**
	 * View the model
	 *
	 */
	public function actionView( $options = array() )
	{
		$_model = $this->loadModel();
		$this->genericAction(
			'view',
			$_model,
			array_merge(
				$this->_formOptions,
				$options
			)
		);
	}

	/**
	 * Post-login event which can be overridden
	 *
	 * @param \CFormModel $model
	 *
	 * @return bool
	 */
	protected function _afterLogin( $model )
	{
		return true;
	}

	/**
	 * @param array $ajaxColumns
	 *
	 * @return DreamController
	 */
	public function setAjaxColumns( $ajaxColumns )
	{
		$this->_ajaxColumns = $ajaxColumns;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAjaxColumns()
	{
		return $this->_ajaxColumns;
	}

	/**
	 * @param string $bootstrapHeader
	 *
	 * @return DreamController
	 */
	public function setBootstrapHeader( $bootstrapHeader )
	{
		$this->_bootstrapHeader = $bootstrapHeader;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBootstrapHeader()
	{
		return $this->_bootstrapHeader;
	}

	/**
	 * @param array $breadcrumbs
	 *
	 * @return DreamController
	 */
	public function setBreadcrumbs( $breadcrumbs )
	{
		$this->_breadcrumbs = $breadcrumbs;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getBreadcrumbs()
	{
		return $this->_breadcrumbs;
	}

	/**
	 * @param string $customLoginView
	 *
	 * @return DreamController
	 */
	public function setCustomLoginView( $customLoginView )
	{
		$this->_customLoginView = $customLoginView;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCustomLoginView()
	{
		return $this->_customLoginView;
	}

	/**
	 * @param array $formOptions
	 *
	 * @return DreamController
	 */
	public function setFormOptions( $formOptions )
	{
		$this->_formOptions = $formOptions;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getFormOptions()
	{
		return $this->_formOptions;
	}

	/**
	 * @param string $loginFormClass
	 *
	 * @return DreamController
	 */
	public function setLoginFormClass( $loginFormClass )
	{
		$this->_loginFormClass = $loginFormClass;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLoginFormClass()
	{
		return $this->_loginFormClass;
	}

	/**
	 * @param array $menu
	 *
	 * @return DreamController
	 */
	public function setMenu( $menu )
	{
		$this->_menu = $menu;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMenu()
	{
		return $this->_menu;
	}

	/**
	 * @param boolean $singleViewMode
	 *
	 * @return DreamController
	 */
	public function setSingleViewMode( $singleViewMode )
	{
		$this->_singleViewMode = $singleViewMode;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getSingleViewMode()
	{
		return $this->_singleViewMode;
	}
}