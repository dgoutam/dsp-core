<?php
use Kisma\Core\Utility\FilterInput;

Yii::import( 'application.components.PlatformStates.php' );

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
class SiteController extends Controller implements PlatformStates
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Set our new layout here
	 */
	public function init()
	{
		parent::init();

		$this->layout = 'initial';
	}

	/**
	 * {@InheritDoc}
	 */
	public function filters()
	{
		return array(
			'accessControl',
		);
	}

	/**
	 * {@InheritDoc}
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
	 * {@InheritDoc}
	 */
	public function actionIndex()
	{
		try
		{
			$_systemState = SystemManager::getInstance()->getSystemState();

			switch ( $_systemState )
			{
				case static::READY:
					// try local launchpad
					if ( is_file( './public/launchpad/index.html' ) )
					{
						$this->redirect( './public/launchpad/index.html' );
					}

					// fall back to this app default site
					$this->render( 'index' );
					break;

				case static::INIT_REQUIRED:
					$this->redirect( array( 'site/initSystem' ) );
					break;

				case static::ADMIN_REQUIRED:
					$this->redirect( array( 'site/initAdmin' ) );
					break;

				case static::SCHEMA_REQUIRED:
					$this->redirect( array( 'site/upgradeSchema' ) );
					break;

				case static::UPGRADE_REQUIRED:
					$this->redirect( array( 'site/upgradeSchema' ) );
					break;

				case static::DATA_REQUIRED:
					$this->redirect( array( 'site/initData' ) );
					break;

				default:
					throw new CHttpException( 400, 'Bad request' );
			}
		}
		catch ( \Exception $_ex )
		{
			die( $_ex->getMessage() );
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
