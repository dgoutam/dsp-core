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
use DreamFactory\Platform\Services\SystemManager;
use DreamFactory\Platform\Utility\ResourceStore;
use DreamFactory\Yii\Controllers\BaseWebController;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;

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
		$_resourceColumns
			= array(
			'apps'     => array(
				'header'       => 'Installed Applications',
				'resource'     => 'app',
				'resourceName' => 'Application',
				'fields'       => array(
					'id'        => array( 'title' => 'ID', 'key' => true, 'list' => false, 'create' => false, 'edit' => false ),
					'name'      => array( 'title' => 'Name' ),
					'api_name'  => array( 'title' => 'Endpoint', 'edit' => false ),
					'url'       => array( 'title' => 'Default Page', 'inputClass' => 'input-xxlarge' ),
					'is_active' => array(
						'title'   => 'Active',
						'options' => array( 'true' => 'Yes', 'false' => 'No' ),
						'display' => '##function (data){return data ? "Yes" : "No";}##',
					),
				),
				'listFields'   => 'id,name,api_name,url,is_active',
				'labels'       => array( 'ID', 'Name', 'Starting Path', 'Active' ),
			),
//			'app_groups' => array(
//				'header'       => 'Application Groups',
//				'resource'     => 'app_group',
//				'resourceName' => 'Group',
//				'fields'       => array(
//					'id'          => array( 'title' => 'ID', 'key' => true, 'list' => false, 'create' => false, 'edit' => false ),
//					'name'        => array( 'title' => 'Name' ),
//					'description' => array( 'title' => 'Description', 'type' => 'textarea', 'inputClass' => 'input-xlarge' ),
//				),
//				'listFields'   => 'id,name,description',
//				'labels'       => array( 'ID', 'Name', 'Description' )
//			),
//			'users'      => array(
//				'header'     => 'Users',
//				'resource'   => 'user',
//				'fields'     => array(
//					'id'           => array( 'title' => 'ID', 'key' => true, 'list' => false, 'create' => false, 'edit' => false ),
//					'email'        => array( 'title' => 'Email' ),
//					'first_name'   => array( 'title' => 'First Name', ),
//					'last_name'    => array( 'title' => 'Last Name', ),
//					'display_name' => array( 'title' => 'Display Name', ),
//				),
//				'listFields' => 'id,email,first_name,last_name,created_date',
//				'labels'     => array( 'ID', 'Email', 'First Name', 'Last Name', 'Created' )
//			),
//			'roles'      => array(
//				'header'   => 'Roles',
//				'resource' => 'role',
//				'fields'   => array( 'id', 'name', 'description', 'is_active' ),
//				'labels'   => array( 'ID', 'Name', 'Description', 'Active' )
//			),
//			'data'       => array(
//				'header'   => 'Data',
//				'resource' => 'db',
//				'fields'   => array(),
//				'labels'   => array(),
//			),
//			'services'   => array(
//				'header'   => 'Services',
//				'resource' => 'service',
//				'fields'   => array( 'id', 'api_name', 'type_id', 'storage_type_id', 'is_active' ),
//				'labels'   => array( 'ID', 'Type', 'Storage Type', 'Active' )
//			),
//			'schema'     => array(
//				'header'   => 'Schema Manager',
//				'resource' => 'schema',
//				'fields'   => array(),
//				'labels'   => array(),
//			),
//			'packager'   => array(
//				'header' => 'Packager',
//				'fields' => array(),
//				'labels' => array()
//			),
//			'config'     => array(
//				'header'   => 'System Configuration',
//				'resource' => 'config',
//				'fields'   => array(),
//				'labels'   => array(),
//			),
			'accounts' => array(
				'header'       => 'Portal Provider Accounts',
				'resource'     => 'portal_account',
				'resourceName' => 'Account',
				'fields'       => array( 'id', 'user_id', 'provider_name', 'provider_user_id', 'last_use_date' ),
				'labels'       => array( 'ID', 'User', 'Provider', 'Provider User ID', 'Last Used' ),
				'listFields'   => 'id,user_id,provider_name,last_use_date',
			),
		);

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
