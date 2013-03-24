<?php
use Kisma\Core\Utility\FilterInput;

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
class SiteController extends BaseController
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
		$_error = Pii::error();

		if ( $_error )
		{
			if ( Pii::ajaxRequest() )
			{
				echo $_error['message'];
			}
			else
			{
				$this->render( 'error', $_error );
			}
		}
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		$_model = new LoginForm();

		// if it is ajax validation request
		if ( isset( $_POST['ajax'] ) && 'login-form' == $_POST['ajax'] )
		{
			echo CActiveForm::validate( $_model );

			Pii::end();
		}

		// collect user input data
		if ( isset( $_POST, $_POST['LoginForm'] ) )
		{
			$_model->attributes = $_POST['LoginForm'];

			// validate user input and redirect to the previous page if valid
			if ( $_model->validate() && $_model->login() )
			{
				$this->redirect( Pii::user()->getReturnUrl() );
			}
		}
		//	display the login form
		$this->render( 'login', array( 'model' => $_model ) );
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Pii::user()->logout();

		$this->redirect( Pii::app()->getHomeUrl() );
	}

	/**
	 * Displays the system init schema page
	 */
	public function actionUpgradeSchema()
	{
		$_model = new InitSchemaForm();

		// collect user input data
		if ( isset( $_POST, $_POST['InitSchemaForm'] ) )
		{
			$_model->attributes = $_POST['InitSchemaForm'];
			// validate user input, configure the system and redirect to the home page
			if ( $_model->validate() )
			{
				SystemManager::getInstance()->initSchema();
				$this->redirect( Pii::app()->getHomeUrl() );
			}

			$this->refresh();
		}

		// display the init form
		$this->render( 'initSchema', array( 'model' => $_model ) );
	}

	/**
	 * Displays the system init admin page
	 */
	public function actionInitSystem()
	{
		$_model = new InitAdminForm();

		// collect user input data
		if ( isset( $_POST, $_POST['InitAdminForm'] ) )
		{
			$_model->attributes = $_POST['InitAdminForm'];
			// validate user input, configure the system and redirect to the previous page
			if ( $_model->validate() )
			{
				SystemManager::getInstance()->initSystem( $_model->attributes );
				$this->redirect( Pii::app()->getHomeUrl() );
			}
		}

		$_model->email = Pii::user()->getState( 'email' );
		$_model->username = substr( $_model->email, 0, strpos( $_model->email, '@' ) );
		$_model->firstName = Pii::user()->getState( 'first_name' );
		$_model->lastName = Pii::user()->getState( 'last_name' );
		$_model->displayName = Pii::user()->getState( 'display_name' );

		//	display the init form
		$this->render( 'initAdmin', array( 'model' => $_model ) );
	}

	/**
	 * Displays the system init admin page
	 */
	public function actionInitAdmin()
	{
		$_model = new InitAdminForm();

		// collect user input data
		if ( isset( $_POST, $_POST['InitAdminForm'] ) )
		{
			$_model->attributes = $_POST['InitAdminForm'];

			// validate user input, configure the system and redirect to the previous page
			if ( $_model->validate() )
			{
				SystemManager::getInstance()->initAdmin( $_model->attributes );
				$this->redirect( Pii::app()->getHomeUrl() );
			}
		}

		$_model->email = Pii::user()->getState( 'email' );
		$_model->username = substr( $_model->email, 0, strpos( $_model->email, '@' ) );
		$_model->firstName = Pii::user()->getState( 'first_name' );
		$_model->lastName = Pii::user()->getState( 'last_name' );
		$_model->displayName = Pii::user()->getState( 'display_name' );

		// display the init form
		$this->render( 'initAdmin', array( 'model' => $_model ) );
	}

	/**
	 * Displays the system init data page
	 */
	public function actionInitData()
	{
		$_model = new InitDataForm();

		// collect user input data
		if ( isset( $_POST, $_POST['InitDataForm'] ) )
		{
			$_model->attributes = $_POST['InitDataForm'];

			// validate user input, configure the system and redirect to the previous page
			if ( $_model->validate() )
			{
				SystemManager::getInstance()->initData();
				$this->redirect( Pii::app()->getHomeUrl() );
			}
		}

		// display the init form
		$this->render( 'initData', array( 'model' => $_model ) );
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

}
