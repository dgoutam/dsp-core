<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Kisma\Core\Interfaces\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Interfaces\PlatformStates;
use Platform\Services\SystemManager;
use Platform\Resources\UserSession;
use Platform\Yii\Utility\Pii;

/**
 * SiteController.php
 * The initialization and set-up controller
 */
class SiteController extends Controller
{
	/**
	 * {@InheritDoc}
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
			array(
				'allow',
				'actions' => array( 'index', 'login', 'error', ),
				'users'   => array( '*' ),
			),
			//	Allow authenticated users access to init commands
			array(
				'allow',
				'actions' => array(
					'initSystem',
					'initAdmin',
					'initSchema',
					'upgradeSchema',
					'initData',
					'upgrade',
					'environment',
					'metrics',
					'fileTree',
					'logout'
				),
				'users'   => array( '@' ),
			),
			//	Deny all others access to init commands
			array(
				'deny',
			),
		);
	}

	protected function _initSystemSplash()
	{
		$this->render(
			'_splash',
			array(
				 'for' => PlatformStates::INIT_REQUIRED,
			)
		);

		$this->actionInitSystem();
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionIndex()
	{
		try
		{
			$_state = SystemManager::getSystemState();

			switch ( $_state )
			{
				case PlatformStates::READY:
					// try local launchpad
					if ( is_file( \Kisma::get( 'app.app_path' ) . '/public/launchpad/index.html' ) )
					{
						$this->redirect( '/public/launchpad/index.html' );
					}

					//	Fall back to this app default site
					$this->render( 'index' );
					break;

				case PlatformStates::INIT_REQUIRED:
					$this->redirect( 'site/initSystem' );
					break;

				case PlatformStates::ADMIN_REQUIRED:
					$this->redirect( 'site/initAdmin' );
					break;

				case PlatformStates::SCHEMA_REQUIRED:
				case PlatformStates::UPGRADE_REQUIRED:
					$this->redirect( 'site/upgradeSchema' );
					break;

				case PlatformStates::DATA_REQUIRED:
					$this->redirect( 'site/initData' );
					break;
			}
		}
		catch ( \Exception $_ex )
		{
			die( $_ex->getMessage() );
		}
	}

	/**
	 * Error action
	 */
	public function actionError()
	{
		if ( null !== ( $_error = Pii::currentError() ) )
		{
			if ( Pii::ajaxRequest() )
			{
				echo $_error['message'];

				return;
			}

			$this->render( 'error', $_error );
		}
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		$_model = new LoginForm();

		// if it is ajax validation request
		if ( isset( $_POST, $_POST['ajax'] ) && 'login-form' === $_POST['ajax'] )
		{
			echo CActiveForm::validate( $_model );
			Pii::end();
		}

		// collect user input data
		if ( isset( $_POST['LoginForm'] ) )
		{
			$_model->attributes = $_POST['LoginForm'];

			//	Validate user input and redirect to the previous page if valid
			if ( $_model->validate() && $_model->login() )
			{
				if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
				{
					$_returnUrl = Pii::url( 'site/index' );
				}

				$this->redirect( $_returnUrl );

				return;
			}
			else
			{
				$_model->addError( 'username', 'Invalid user name and password combination.' );
			}
		}

		$this->render(
			'login',
			array(
				 'model' => $_model
			)
		);
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Pii::user()->logout();
		$this->redirect( '/' );
	}

	/**
	 * Activates the system
	 */
	public function actionInitSystem()
	{
		SystemManager::initSystem();
		$this->redirect( '/' );
	}

	/**
	 * Displays the system init schema page
	 */
	public function actionUpgradeSchema()
	{
		$_model = new InitSchemaForm();

		if ( isset( $_POST, $_POST['InitSchemaForm'] ) )
		{
			$_model->attributes = $_POST['InitSchemaForm'];

			if ( $_model->validate() )
			{
				SystemManager::initSchema();
				$this->redirect( '/' );
			}

			$this->refresh();
		}

		$this->render(
			'initSchema',
			array(
				 'model' => $_model
			)
		);
	}

	/**
	 * Adds the first admin, based on DF authenticated login
	 */
	public function actionInitAdmin()
	{
		SystemManager::initAdmin();
		$this->redirect( '/' );
	}

	/**
	 * Displays the system init data page
	 */
	public function actionInitData()
	{
		$_model = new InitDataForm();

		if ( isset( $_POST, $_POST['InitDataForm'] ) )
		{
			$_model->attributes = $_POST['InitDataForm'];

			if ( $_model->validate() )
			{
				SystemManager::initData();
				$this->redirect( '/' );
			}

			$this->refresh();
		}

		$this->render(
			'initData',
			array(
				 'model' => $_model
			)
		);
	}

	public function actionUpgrade()
	{
		if ( \Fabric::fabricHosted() )
		{
			throw new \Exception( 'Fabric hosted DSPs can not be upgraded.' );
		}

		/** @var \CWebUser $_user */
		$_user = \Yii::app()->user;
		// Create and login first admin user
		if ( !$_user->getState( 'df_authenticated' ) )
		{
			try
			{
				UserSession::checkSessionPermission( 'admin', 'system' );
			}
			catch ( \Exception $ex )
			{
				throw new \Exception( 'Upgrade requires admin privileges, logout and login with admin credentials.' );
			}
		}

		$_current = SystemManager::getCurrentVersion();
		$_temp = SystemManager::getDspVersions();
		$_versions = array();
		foreach ( $_temp as $_version )
		{
			$_name = Option::get( $_version, 'name', '' );
			if ( version_compare( $_current, $_name, '<' ) )
			{
				$_versions[] = $_name;
			}
		}
		if ( empty( $_versions ) )
		{
			throw new Exception( 'No upgrade available. This DSP is running the latest available version.' );
		}

		$_model = new UpgradeDspForm();
		$_model->versions = $_versions;

		if ( isset( $_POST, $_POST['UpgradeDspForm'] ) )
		{
			$_model->attributes = $_POST['UpgradeDspForm'];

			if ( $_model->validate() )
			{
				$_version = Option::get( $_versions, $_model->selected, '' );
				SystemManager::upgradeDsp( $_version );
				$this->redirect( '/' );
			}

			$this->refresh();
		}

		$this->render(
			'upgradeDsp',
			array(
				 'model' => $_model
			)
		);
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
		$_data = array();

		$_path = Pii::getParam( 'storage_path' );

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
		Pii::end();
	}

	/**
	 * Get DSP metrics
	 */
	public function actionMetrics()
	{
		if ( !Fabric::fabricHosted() )
		{
			$_stats = AsgardService::getStats();
		}
		else
		{
			$_endpoint = Pii::getParam( 'cloud.endpoint' ) . '/metrics/dsp?dsp=' . urlencode( Pii::getParam( 'dsp.name' ) );

			Curl::setDecodeToArray( true );
			$_stats = Curl::get( $_endpoint );
		}

		if ( empty( $_stats ) )
		{
			throw new \CHttpException( HttpResponse::NotFound );
		}

		$this->layout = false;
		header( 'Content-type: application/json' );

		echo json_encode( $_stats );
		Pii::end();
	}
}
