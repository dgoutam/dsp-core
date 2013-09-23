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
use DreamFactory\Oasys\Components\BaseProvider;
use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Oasys;
use DreamFactory\Oasys\Stores\FileSystem;
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Interfaces\PlatformStates;
use DreamFactory\Platform\Resources\User\Session;
use DreamFactory\Platform\Services\AsgardService;
use DreamFactory\Platform\Services\SystemManager;
use DreamFactory\Platform\Utility\Fabric;
use DreamFactory\Platform\Yii\Models\Provider;
use DreamFactory\Platform\Yii\Models\User;
use DreamFactory\Yii\Controllers\BaseWebController;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Interfaces\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * WebController.php
 * The initialization and set-up controller
 */
class WebController extends BaseWebController
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_STARTUP_APP = '/public/launchpad/index.html';

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var bool
	 */
	protected $_activated = false;
	/**
	 * @var bool
	 */
	protected $_autoLogged = false;
	/**
	 * @var string
	 */
	protected $_remoteError = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Initialize
	 */
	public function init()
	{
		parent::init();

		$this->defaultAction = 'index';
		$this->_activated = SystemManager::activated();

		//	Remote login errors?
		$_error = FilterInput::request( 'error', null, FILTER_SANITIZE_STRING );
		$_message = FilterInput::request( 'error_description', null, FILTER_SANITIZE_STRING );

		if ( !empty( $_error ) )
		{
			$this->_remoteError = $_error . ( !empty( $_message ) ? ' (' . $_message . ')' : null );
		}
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
				'actions' => array(
					'index',
					'login',
					'error',
					'activate',
					'initSystem',
					'initSchema',
					'initData',
					'initAdmin',
					'authorize',
					'remoteLogin',
				),
				'users'   => array( '*' ),
			),
			//	Allow authenticated users access to init commands
			array(
				'allow',
				'actions' => array(
					'upgrade',
					'upgradeSchema',
					'initAdmin',
					'environment',
					'metrics',
					'fileTree',
					'logout',
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

	public function actionActivate()
	{
		$_model = new LoginForm();

		//	Came from login form? Don't do drupal auth, do dsp auth
		$_fromLogin = ( 0 != Option::get( $_POST, 'login-only', 0 ) );

		//	Did we come because we need to log in?
		if ( !Pii::postRequest() && $this->_activated )
		{
			if ( null !== ( $_returnUrl = Pii::user()->getReturnUrl() ) && 200 == Option::server( 'REDIRECT_STATUS' ) )
			{
				$this->actionLogin( true );

				return;
			}
		}
		else
		{
			if ( 1 == Option::get( $_POST, 'skipped', 0 ) )
			{
				$this->actionInitAdmin();

				return;
			}
		}

		if ( isset( $_POST, $_POST['LoginForm'] ) )
		{
			$_model->attributes = $_POST['LoginForm'];

			if ( !empty( $_model->username ) && !empty( $_model->password ) )
			{
				$_model->setDrupalAuth( !$_fromLogin );

				//	Validate user input and redirect to the previous page if valid
				if ( $_model->validate() && $_model->login() )
				{
					if ( !$this->_activated )
					{
						SystemManager::initAdmin();
					}

					if ( null === ( $_returnUrl = Pii::user()->getReturnUrl() ) )
					{
						$_returnUrl = Pii::url( $this->id . '/index' );
					}

					$this->redirect( $_returnUrl );

					return;
				}
				else
				{
					$_model->addError( 'username', 'Invalid user name and password combination.' );

					//	Came from login form? Don't do drupal auth, do dsp auth
					if ( $_fromLogin )
					{
						$this->actionLogin( true );

						return;
					}
				}
			}
			else
			{
				if ( !$this->_activated )
				{
					$this->redirect( '/' . $this->id . '/initAdmin' );
				}
				else
				{
					$this->redirect( '/' . $this->id . '/index' );
				}
			}
		}

		$this->render(
			'activate',
			array(
				 'model'     => $_model,
				 'activated' => $this->_activated,
			)
		);
	}

	/**
	 * {@InheritDoc}
	 */
	public function actionIndex()
	{
		try
		{
			$_error = false;
			$_state = SystemManager::getSystemState();

			if ( !$this->_activated && $_state != PlatformStates::INIT_REQUIRED )
			{
				$_state = PlatformStates::ADMIN_REQUIRED;
			}

			if ( !empty( $this->_remoteError ) )
			{
				$_error = 'error=' . urlencode( $this->_remoteError );
			}

			switch ( $_state )
			{
				case PlatformStates::READY:
					$_defaultApp = Pii::getParam( 'dsp.default_app', static::DEFAULT_STARTUP_APP );

					//	Try local launchpad
					if ( is_file( \Kisma::get( 'app.app_path' ) . $_defaultApp ) )
					{
						if ( $_error )
						{
							$_defaultApp = $_defaultApp . ( false !== strpos( $_defaultApp, '?' ) ? '&' : '?' ) . $_error;
						}

						$this->redirect( $_defaultApp );
					}

					//	Fall back to this app default site
					$this->render( 'index' );
					break;

				case PlatformStates::INIT_REQUIRED:
					$this->redirect( '/' . $this->id . '/initSystem' );
					break;

				case PlatformStates::ADMIN_REQUIRED:
					$this->redirect( '/' . $this->id . '/initAdmin' );
					break;

				case PlatformStates::SCHEMA_REQUIRED:
				case PlatformStates::UPGRADE_REQUIRED:
					$this->redirect( '/' . $this->id . '/upgradeSchema' );
					break;

				case PlatformStates::DATA_REQUIRED:
					$this->redirect( '/' . $this->id . '/initData' );
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
	public function actionLogin( $redirected = false )
	{
		$_model = new LoginForm();
		$_model->setDrupalAuth( !$redirected );

		// if it is ajax validation request
		if ( isset( $_POST, $_POST['ajax'] ) && 'login-form' === $_POST['ajax'] )
		{
			echo \CActiveForm::validate( $_model );
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
					$_returnUrl = Pii::url( $this->id . '/index' );
				}

				$this->redirect( $_returnUrl );

				return;
			}
			else
			{
				$_model->addError( 'username', 'Invalid user name and password combination . ' );
			}
		}

		$this->render(
			'login',
			array(
				 'model'      => $_model,
				 'activated'  => $this->_activated,
				 'redirected' => $redirected,
			)
		);
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
			else
			{
//				Log::debug( 'Failed validation' );
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
		if ( $this->_activated )
		{
//			Log::debug( 'initAdmin activated' );
			SystemManager::initAdmin();
			$this->redirect( '/' );
		}

//		Log::debug( 'initAdmin NOT activated' );

		$_model = new InitAdminForm();

		if ( isset( $_POST, $_POST['InitAdminForm'] ) )
		{
			$_model->attributes = $_POST['InitAdminForm'];

			if ( $_model->validate() )
			{
				SystemManager::initAdmin();
				$this->redirect( '/' );
			}

			$this->refresh();
		}

		$this->render(
			'initAdmin',
			array(
				 'model' => $_model
			)
		);
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

	/**
	 * @throws \Exception
	 */
	public function actionUpgrade()
	{
		if ( Fabric::fabricHosted() )
		{
			throw new \Exception( 'Fabric hosted DSPs can not be upgraded . ' );
		}

		/** @var \CWebUser $_user */
		$_user = \Yii::app()->user;
		// Create and login first admin user
		if ( !$_user->getState( 'df_authenticated' ) )
		{
			try
			{
				Session::checkSessionPermission( 'admin', 'system' );
			}
			catch ( \Exception $ex )
			{
				throw new \Exception( 'Upgrade requires admin privileges, logout and login with admin credentials . ' );
			}
		}

		$_current = SystemManager::getCurrentVersion();
		$_temp = SystemManager::getDspVersions();
		$_versions = array();
		foreach ( $_temp as $_version )
		{
			$_name = Option::get( $_version, 'name', '' );
			if ( version_compare( $_current, $_name, ' < ' ) )
			{
				$_versions[] = $_name;
			}
		}
		if ( empty( $_versions ) )
		{
			throw new \Exception( 'No upgrade available . This DSP is running the latest available version . ' );
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

	/**
	 * Action for URL that the client redirects to when coming back from providers.
	 */
	public function actionRemoteLogin()
	{
		if ( null !== $this->_remoteError )
		{
			$this->_redirectError( $this->_remoteError );
		}

		if ( null === ( $_providerId = Option::request( 'pid' ) ) )
		{
			throw new BadRequestException( 'No remote login provider specified.' );
		}

		$this->layout = false;

		$_flow = FilterInput::request( 'flow', Flows::CLIENT_SIDE, FILTER_SANITIZE_NUMBER_INT );

		$_providerModel = Fabric::getProviderCredentials( $_providerId );

		if ( empty( $_providerModel ) )
		{
			/** @var Provider $_providerModel */
			if ( null === ( $_providerModel = Provider::model()->byPortal( $_providerId )->find() ) )
			{
				throw new BadRequestException( 'The provider "' . $_providerId . '" is not configured for remote login.' );
			}
		}

		//	Set our store...
		Oasys::setStore( $_store = new FileSystem( $_sid = session_id() ) );

		$_config = Provider::buildConfig(
			$_providerModel,
			array(
				 'flow_type'    => $_flow,
				 'redirect_uri' => Curl::currentUrl( false ) . '?pid=' . $_providerId,
			),
			Pii::getState( $_providerId . '.user_config', array() )
		);

		$_provider = Oasys::getProvider( $_providerId, $_config );

		if ( $_provider->handleRequest() )
		{
			//	Now let the user model figure out what to do...
			try
			{
				$_user = User::remoteLoginRequest( $_providerId, $_provider, $_providerModel );
				Log::debug( 'Remote login success: ' . $_user->email . ' (id#' . $_user->id . ')' );
			}
			catch ( \Exception $_ex )
			{
				Log::error( $_ex->getMessage() );

				//	No soup for you!
				$this->_redirectError( $_ex->getMessage() );
			}

			//	Go home baby!
			$this->redirect( '/' );
		}

		Log::error( 'Seems that the provider rejected the login...' );
		$this->_redirectError( 'Error during remote login sequence. Please try again.' );
	}

	/**
	 * @param string $message
	 * @param string $url
	 */
	protected function _redirectError( $message, $url = '/' )
	{
		$this->redirect( $url . '?error=' . urlencode( $message ) );
	}
}
