<?php
use \CloudServicesPlatform\ServiceHandlers\ServiceHandler;

class SiteController extends Controller
{
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
			if ( 'ready' === $this->getSystemState() )
			{
                $svc = ServiceHandler::getInstance();
				$app = $svc->getServiceObject( 'App' );
				// check if loaded in blob storage as app
				if ( $app->appExists( 'LaunchPad' ) )
				{
					header( "Location: ./app/LaunchPad/index.html" );
					exit;
				}
				// otherwise use local copy
				header( "Location: ./public/launchpad/index.html" );
                // renders the view file 'protected/views/site/index.php'
                // using the default layout 'protected/views/layouts/main.php'
                //$this->render( 'index' );
                Yii::app()->end();
			}
			else
			{
				$this->actionInit();
                Yii::app()->end();
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
   	 * Displays the system init page
   	 */
   	public function actionInit()
   	{
   		$model = new InitForm;

   		// collect user input data
   		if ( isset( $_POST['InitForm'] ) )
   		{
   			$model->attributes = $_POST['InitForm'];
   			// validate user input, configure the system and redirect to the previous page
   			if ( $model->validate() && $model->configure() )
   			{
   				$this->redirect( Yii::app()->user->returnUrl );
   			}
            $this->refresh();
   		}
   		// display the init form
   		$this->render( 'init', array( 'model' => $model ) );
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

    /**
   	 * Determines the current state of the system
   	 */
    public function getSystemState()
    {
        try {
            $tables = Yii::app()->db->schema->getTableNames();
            if (!in_array('df_app', $tables) ||
                !in_array('df_app_group', $tables) ||
                !in_array('df_label', $tables) ||
                !in_array('df_role', $tables) ||
                !in_array('df_role_service_access', $tables) ||
                !in_array('df_service', $tables) ||
                !in_array('df_session', $tables) ||
                !in_array('df_user', $tables)) {
                return 'init required';
            }
            $db = new \CloudServicesPlatform\Storage\Database\PdoSqlDbSvc('df_');
            $result = $db->retrieveSqlRecordsByFilter('df_service', 'name');
            unset($result['total']);
            if (count($result) < 1) {
                return 'init required';
            }
            $result = $db->retrieveSqlRecordsByFilter('df_app', 'name');
            unset($result['total']);
            if (count($result) < 1) {
                return 'init required';
            }

            $result = $db->retrieveSqlRecordsByFilter('df_user', 'username', "is_sys_admin = 1", 1);
            unset($result['total']);
            if (count($result) < 1) {
                return 'admin required';
            }
            return 'ready';
        }
        catch (\Exception $ex) {
            throw $ex;
        }
    }
}