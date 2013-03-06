<?php
/**
 * SiteController.php
 */
class SiteController extends Controller
{
	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	public function accessRules()
	{
		return array(
			// allow authenticated users access to init commands
			array(
				'allow',
				'actions' => array( 'initSystem', 'initAdmin', 'initSchema', 'upgradeSchema', 'initData' ),
				'users'   => array( '@' ),
			),
			// deny all others access to init commands
			array(
				'deny',
				'actions' => array( 'initSystem', 'initAdmin', 'initSchema', 'upgradeSchema', 'initData' ),
			),
		);
	}

	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha' => array(
				'class'     => 'CCaptchaAction',
				'backColor' => 0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'    => array(
				'class' => 'CViewAction',
			),
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		try
		{
			$state = SystemManager::getInstance()->getSystemState();
			switch ( $state )
			{
				case 'ready':
					$svc = ServiceHandler::getInstance();
					$app = $svc->getServiceObject( 'app' );
					// check if loaded in storage as app
					if ( $app && $app->appExists( 'LaunchPad' ) )
					{
						$this->redirect( './app/LaunchPad/index.html' );
					}
					// otherwise try local copy
					elseif ( is_file( './public/launchpad/index.html' ) )
					{
						$this->redirect( './public/launchpad/index.html' );
					}
					// fall back to this app default site
					else
					{
						$this->render( 'index' );
					}
					break;
				case 'init required':
					$this->redirect( array( 'site/initSystem' ) );
					break;
				case 'admin required':
					$this->redirect( array( 'site/initAdmin' ) );
					break;
				case 'schema required':
					$this->redirect( array( 'site/upgradeSchema' ) );
					break;
				case 'upgrade required':
					$this->redirect( array( 'site/upgradeSchema' ) );
					break;
			}
		}
		catch ( Exception $ex )
		{
			die( $ex->getMessage() );
		}
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		$error = Yii::app()->errorHandler->error;
		if ( $error )
		{
			if ( Yii::app()->request->isAjaxRequest )
			{
				echo $error['message'];
			}
			else
			{
				$this->render( 'error', $error );
			}
		}
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model = new ContactForm;
		if ( isset( $_POST['ContactForm'] ) )
		{
			$model->attributes = $_POST['ContactForm'];
			if ( $model->validate() )
			{
				$name = '=?UTF-8?B?' . base64_encode( $model->name ) . '?=';
				$subject = '=?UTF-8?B?' . base64_encode( $model->subject ) . '?=';
				$headers = "From: $name <{$model->email}>\r\n" .
						   "Reply-To: {$model->email}\r\n" .
						   "MIME-Version: 1.0\r\n" .
						   "Content-type: text/plain; charset=UTF-8";

				mail( Yii::app()->params['adminEmail'], $subject, $model->body, $headers );
				Yii::app()->user->setFlash( 'contact', 'Thank you for contacting us. We will respond to you as soon as possible.' );
				$this->refresh();
			}
		}
		$this->render( 'contact', array( 'model' => $model ) );
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		$model = new LoginForm;

		// if it is ajax validation request
		if ( isset( $_POST['ajax'] ) && $_POST['ajax'] === 'login-form' )
		{
			echo CActiveForm::validate( $model );
			Yii::app()->end();
		}

		// collect user input data
		if ( isset( $_POST['LoginForm'] ) )
		{
			$model->attributes = $_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if ( $model->validate() && $model->login() )
			{
				$this->redirect( Yii::app()->user->returnUrl );
			}
		}
		// display the login form
		$this->render( 'login', array( 'model' => $model ) );
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect( Yii::app()->homeUrl );
	}

	/**
	 * Displays the system init schema page
	 */
	public function actionUpgradeSchema()
	{
		$model = new InitSchemaForm;

		// collect user input data
		if ( isset( $_POST['InitSchemaForm'] ) )
		{
			$model->attributes = $_POST['InitSchemaForm'];
			// validate user input, configure the system and redirect to the home page
			if ( $model->validate() )
			{
				SystemManager::getInstance()->initSchema();
				$this->redirect( Yii::app()->homeUrl );
			}
			$this->refresh();
		}
		// display the init form
		$this->render( 'initSchema', array( 'model' => $model ) );
	}

	/**
	 * Displays the system init admin page
	 */
	public function actionInitSystem()
	{
		$model = new InitAdminForm;

		// collect user input data
		if ( isset( $_POST['InitAdminForm'] ) )
		{
			$model->attributes = $_POST['InitAdminForm'];
			// validate user input, configure the system and redirect to the previous page
			if ( $model->validate() )
			{
				SystemManager::getInstance()->initSystem( $model->attributes );
				$this->redirect( Yii::app()->homeUrl );
			}
			$this->refresh();
		}
		$model->email = Yii::app()->user->getState( 'email' );
		$model->username = substr( $model->email, 0, strpos( $model->email, '@' ) );
		$model->firstName = Yii::app()->user->getState( 'first_name' );
		$model->lastName = Yii::app()->user->getState( 'last_name' );
		$model->displayName = Yii::app()->user->getState( 'display_name' );
		// display the init form
		$this->render( 'initAdmin', array( 'model' => $model ) );
	}

	/**
	 * Displays the system init admin page
	 */
	public function actionInitAdmin()
	{
		$model = new InitAdminForm;

		// collect user input data
		if ( isset( $_POST['InitAdminForm'] ) )
		{
			$model->attributes = $_POST['InitAdminForm'];
			// validate user input, configure the system and redirect to the previous page
			if ( $model->validate() )
			{
				SystemManager::getInstance()->initAdmin( $model->attributes );
				$this->redirect( Yii::app()->homeUrl );
			}
			$this->refresh();
		}
		$model->email = Yii::app()->user->getState( 'email' );
		$model->username = substr( $model->email, 0, strpos( $model->email, '@' ) );
		$model->firstName = Yii::app()->user->getState( 'first_name' );
		$model->lastName = Yii::app()->user->getState( 'last_name' );
		$model->displayName = Yii::app()->user->getState( 'display_name' );
		// display the init form
		$this->render( 'initAdmin', array( 'model' => $model ) );
	}

	/**
	 * Displays the system init data page
	 */
	public function actionInitData()
	{
		$model = new InitDataForm;

		// collect user input data
		if ( isset( $_POST['InitDataForm'] ) )
		{
			$model->attributes = $_POST['InitDataForm'];
			// validate user input, configure the system and redirect to the previous page
			if ( $model->validate() )
			{
				SystemManager::getInstance()->initData();
				$this->redirect( Yii::app()->homeUrl );
			}
			$this->refresh();
		}
		// display the init form
		$this->render( 'initData', array( 'model' => $model ) );
	}

	/**
	 * Displays the admin page
	 */
	public function actionAdmin()
	{
		$this->render( 'admin' );
	}

	/**
	 * Displays the environment page
	 */
	public function actionEnvironment()
	{
		$this->render( 'environment' );
	}

	public function actionTestDrupalValidate( $e, $p )
	{
		$this->layout = false;
		print_r( Drupal::authenticateUser( $e, $p ) );
	}
}
