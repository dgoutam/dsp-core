<?php
/**
 * SiteController.php
 *
 * This file is part of the DreamFactory Services Platform (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This source file and all is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * This is the default controller for the DSP
 */
use Kisma\Core\Utility\FilterInput;

/**
 * SiteController.php
 */
class SiteController extends Controller
{
	/**
	 * Returns the filter configurations.
	 *
	 * By overriding this method, child classes can specify filters to be applied to actions.
	 *
	 * This method returns an array of filter specifications. Each array element specify a single filter.
	 *
	 * For a method-based filter (called inline filter), it is specified as 'FilterName[ +|- Action1, Action2, ...]',
	 * where the '+' ('-') operators describe which actions should be (should not be) applied with the filter.
	 *
	 * For a class-based filter, it is specified as an array like the following:
	 * <pre>
	 * array(
	 *     'FilterClass[ +|- Action1, Action2, ...]',
	 *     'name1'=>'value1',
	 *     'name2'=>'value2',
	 *     ...
	 * )
	 * </pre>
	 * where the name-value pairs will be used to initialize the properties of the filter.
	 *
	 * Note, in order to inherit filters defined in the parent class, a child class needs to
	 * merge the parent filters with child filters using functions like array_merge().
	 *
	 * @return array a list of filter configurations.
	 * @see CFilter
	 */
	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	/**
	 * Returns the access rules for this controller.
	 * Override this method if you use the {@link filterAccessControl accessControl} filter.
	 *
	 * @return array list of access rules. See {@link CAccessControlFilter} for details about rule specification.
	 */
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
			'captcha' => array(
				'class'     => 'CCaptchaAction',
				'backColor' => 0xFFFFFF,
			),
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
					// try local launchpad
					if ( is_file( './public/launchpad/index.html' ) )
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
                case 'data required':
                    $this->redirect( array( 'site/initData' ) );
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

	/**
	 * Makes a file tree. Used exclusively by the snapshot service at this time.
	 *
	 * @param string $instanceName
	 * @param string $path
	 *
	 * @return string
	 */
	public function actionFileTree( $instanceName = null, $path = null )
	{
		$instanceName = $instanceName ? : \Kisma::get( 'app.dsp_name', FilterInput::request( 'instanceName' ) );
		$path = $path ? : FilterInput::request( 'path' );

		$_data = array();

		$_path = realpath(
			$path
				? :
				$instanceName && file_exists( '/var/www/.fabric_hosted' )
					?
					'/data/storage/' . $instanceName . '/blob'
					:
					dirname( __DIR__ ) . '/storage'
		);

		if ( !empty( $_path ) )
		{
			$_objects = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $_path ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			/** @var $_node \SplFileInfo */
			foreach ( $_objects as $_name => $_node )
			{
				if ( $_node->isDir() || $_node->isLink() || '.' == $_name || '..' == $_name )
				{
					continue;
				}

				$_cleanPath = str_ireplace( $_path, null, dirname( $_node->getPathname() ) );

				if ( empty( $_cleanPath ) )
				{
					$_cleanPath = '/';
				}

				$_data[$_cleanPath][] = basename( $_name );
			}
		}

		echo json_encode( $_data );
		die();
	}
}
