<?php
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Interfaces\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;

/**
 * SiteController.php
 * The initialization and set-up controller
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/platform-core>
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
			//	Allow authenticated users access to init commands
			array(
				'allow',
				'actions' => array('initSystem', 'initAdmin', 'initSchema', 'upgradeSchema', 'initData', 'metrics'),
				'users'   => array('@'),
			),
			//	Deny all others access to init commands
			array(
				'deny',
				'actions' => array('initSystem', 'initAdmin', 'initSchema', 'upgradeSchema', 'initData', 'metrics'),
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
			switch ( SystemManager::getSystemState() )
			{
				case 'ready':
					// try local launchpad
					if ( is_file( './public/launchpad/index.html' ) )
					{
						$this->redirect( './public/launchpad/index.html' );
					}

					//	Fall back to this app default site
					$this->render( 'index' );
					break;

				case 'init required':
					$this->redirect( array('site/initSystem') );
					break;

				case 'admin required':
					$this->redirect( array('site/initAdmin') );
					break;

				case 'schema required':
					$this->redirect( array('site/upgradeSchema') );
					break;

				case 'upgrade required':
					$this->redirect( array('site/upgradeSchema') );
					break;

				case 'data required':
					$this->redirect( array('site/initData') );
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
		if ( null !== ( $_error = Pii::error() ) )
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
				$this->redirect( '/' );
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
		Yii::app()->user->logout();
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
		$_endpoint = Pii::getParam( 'cloud.endpoint' ) . '/metrics/dsp?dsp=' . urlencode( Pii::getParam( 'dsp.name' ) );

		if ( Fabric::fabricHosted() )
		{
			Curl::setDecodeToArray( true );
			$_stats = Curl::get( $_endpoint );

			if ( empty( $_stats ) )
			{
				throw new CHttpException( HttpResponse::NotFound );
			}

			$this->layout = false;
			header( 'Content-type: application/json' );

			echo json_encode( $_stats );
		}
	}
}
