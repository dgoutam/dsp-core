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
use DreamFactory\Platform\Utility\ResourceStore;
use DreamFactory\Yii\Controllers\BaseFactoryController;
use DreamFactory\Yii\Controllers\BaseWebController;
use Kisma\Core\Interfaces\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;
use DreamFactory\Platform\Services\SystemManager;
use DreamFactory\Yii\Utility\Pii;

/**
 * AdminController.php
 * The administrative site controller. This is the replacement for the javascript admin app. WIP
 */
class AdminController extends BaseWebController
{
	/**
	 * {@InheritDoc}
	 */
	public function init()
	{
		parent::init();

		$this->layout = 'admin';
		$this->defaultAction = 'index';

		$this->setUserActions( array() );
		$this->addUserActions( static::Authenticated, array( 'index', 'services', 'applications', 'authorizations' ) );
	}

	/**
	 * @param string $actionId
	 */
	public function missingAction( $actionId = null )
	{
		$_model = ResourceStore::model( $actionId );

		$_models = $_model->findAll();

		if ( Pii::postRequest() )
		{
		}

		$this->render( 'resources', array( 'resourceName' => $actionId, 'model' => $_models ) );
	}

	/**
	 *
	 */
	public function actionIndex()
	{
		static $_resourceColumns
		= array(
			'apps'       => array(
				'header'   => 'Installed Applications',
				'resource' => 'app',
				'fields'   => array( 'id', 'api_name', 'url', 'is_active' ),
				'labels'   => array( 'ID', 'Name', 'Starting Path', 'Active' )
			),
			'app_groups' => array(
				'header'   => 'Application Groups',
				'resource' => 'app_group',
				'fields'   => array( 'id', 'name', 'description' ),
				'labels'   => array( 'ID', 'Name', 'Description' )
			),
			'users'      => array(
				'header'   => 'Users',
				'resource' => 'user',
				'fields'   => array( 'id', 'email', 'first_name', 'last_name', 'created_date' ),
				'labels'   => array( 'ID', 'Email', 'First Name', 'Last Name', 'Created' )
			),
			'roles'      => array(
				'header'   => 'Roles',
				'resource' => 'role',
				'fields'   => array( 'id', 'name', 'description', 'is_active' ),
				'labels'   => array( 'ID', 'Name', 'Description', 'Active' )
			),
			'data'       => array(
				'header'   => 'Data',
				'resource' => 'db',
				'fields'   => array(),
				'labels'   => array(),
			),
			'services'   => array(
				'header'   => 'Services',
				'resource' => 'service',
				'fields'   => array( 'id', 'api_name', 'type_id', 'storage_type_id', 'is_active' ),
				'labels'   => array( 'ID', 'Type', 'Storage Type', 'Active' )
			),
			'schema'     => array(
				'header'   => 'Schema Manager',
				'resource' => 'schema',
				'fields'   => array(),
				'labels'   => array(),
			),
			'packager'   => array(
				'header' => 'Packager',
				'fields' => array(),
				'labels' => array()
			),
			'config'     => array(
				'header'   => 'System Configuration',
				'resource' => 'config',
				'fields'   => array(),
				'labels'   => array(),
			),
			'providers'  => array(
				'header'   => 'Portal Providers',
				'resource' => 'account_provider',
				'fields'   => array( 'id', 'provider_name', 'service_endpoint', 'last_use_date' ),
				'labels'   => array( 'ID', 'Name', 'Endpoint', 'Last Used' ),
			),
			'accounts'   => array(
				'header'   => 'Portal Provider Accounts',
				'resource' => 'service_account',
				'fields'   => array( 'id', 'user_id', 'provider_id', 'last_use_date' ),
				'labels'   => array( 'ID', 'User', 'Provider', 'Last Used' ),
			),
		);

		foreach ( $_resourceColumns as $_resource => &$_config )
		{
			if ( !isset( $_config['columns'] ) )
			{
				if ( isset( $_config['fields'] ) )
				{
					$_config['columns'] = array();

					foreach ( $_config['fields'] as $_field )
					{
						$_config['columns'][] = array( 'sName' => $_field );
					}

					$_config['fields'] = implode( ',', $_config['fields'] );
				}
			}
		}

		$this->render( 'index', array( 'resourceColumns' => $_resourceColumns ) );
	}

	/**
	 *
	 */
	public function actionApplications()
	{
		if ( Pii::postRequest() )
		{
		}

		$this->render( 'applications' );
	}

	/**
	 *
	 */
	public function actionAuthorizations()
	{
		if ( Pii::postRequest() )
		{
		}

		$this->render( 'authorizations' );
	}
}
