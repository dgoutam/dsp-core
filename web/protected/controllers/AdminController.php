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
use DreamFactory\Oasys\Oasys;
use DreamFactory\Platform\Utility\ResourceStore;
use DreamFactory\Yii\Controllers\BaseWebController;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * AdminController.php
 * The administrative site controller. This is the replacement for the javascript admin app. WIP
 */
class AdminController extends BaseWebController
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * {@InheritDoc}
	 */
	public function init()
	{
		parent::init();

		$this->layout = 'admin';
		$this->defaultAction = 'index';

		$this->addUserActions( static::Authenticated, array( 'index', 'services', 'applications', 'providers', 'providerUsers', 'update' ) );
	}

	/**
	 * @param array $options
	 * @param bool  $fromCreate
	 *
	 * @throws DreamFactory\Platform\Exceptions\BadRequestException
	 */
	public function actionUpdate( $options = array(), $fromCreate = false )
	{
		$_response = $_schema = $_errors = null;

		try
		{
			$_resourceId = strtolower( trim( FilterInput::request( 'resource', null, FILTER_SANITIZE_STRING ) ) );
			$_id = FilterInput::request( 'id', null, FILTER_SANITIZE_STRING );

			if ( empty( $_resourceId ) || ( empty( $_resourceId ) && empty( $_id ) ) )
			{
				throw new \CHttpException( 404, 'Not found.' );
			}

			//	Handle a plural request
			if ( false !== ( $_tempId = Inflector::isPlural( $_resourceId, true ) ) )
			{
				$_resourceId = $_tempId;
			}

			if ( !empty( $_id ) )
			{
				if ( null === ( $_model = ResourceStore::model( $_resourceId )->unhashId( $_id )->find() ) )
				{
					$_model = ReourceStore::model( $_resourceId );
				}
			}

			if ( Pii::postRequest() )
			{
				$_data = $_POST[$_resourceId];
			}

//			$_response = $_resource->processRequest( $_resourceId . '/' . $_id, Option::server( 'REQUEST_METHOD' ) );
			$_schema = null;

			if ( !empty( $_response ) && isset( $_response['api_name'] ) )
			{
				$_schema = Oasys::getProvider( $_response['api_name'] )->getConfig()->getSchema( false );

				if ( !empty( $_schema ) && !empty( $_response['config_text'] ) )
				{
					//	Load the resource into the schema for a goof
					foreach ( $_response['config_text'] as $_key => $_value )
					{
						if ( Option::contains( $_schema, $_key ) )
						{
							if ( is_array( $_value ) )
							{
								$_value = implode( ', ', $_value );
							}
						}

						$_schema[$_key]['value'] = $_value;
					}
				}
			}
		}
		catch ( \Exception $_ex )
		{
			$_errors[] = 'Error [' . $_ex->getCode() . '] ' . $_ex->getMessage();
			Log::error( 'Admin::actionUpdate exception: ' . $_ex->getMessage() );
		}

		$this->render(
			'update',
			array(
				 'resource' => $_response,
				 'schema'   => $_schema,
				 'errors'   => $_errors,
			)
		);
	}

	/**
	 *
	 */
	public function actionIndex()
	{
		static $_resourceColumns = array(
			'app'           => array(
				'header'   => 'Installed Applications',
				'resource' => 'app',
				'fields'   => array( 'id', 'api_name', 'url', 'is_active' ),
				'labels'   => array( 'ID', 'Name', 'Starting Path', 'Active' )
			),
			'app_group'     => array(
				'header'   => 'Application Groups',
				'resource' => 'app_group',
				'fields'   => array( 'id', 'name', 'description' ),
				'labels'   => array( 'ID', 'Name', 'Description' )
			),
			'user'          => array(
				'header'   => 'Users',
				'resource' => 'user',
				'fields'   => array( 'id', 'email', 'first_name', 'last_name', 'created_date' ),
				'labels'   => array( 'ID', 'Email', 'First Name', 'Last Name', 'Created' )
			),
			'role'          => array(
				'header'   => 'Roles',
				'resource' => 'role',
				'fields'   => array( 'id', 'name', 'description', 'is_active' ),
				'labels'   => array( 'ID', 'Name', 'Description', 'Active' )
			),
			'data'          => array(
				'header'   => 'Data',
				'resource' => 'db',
				'fields'   => array(),
				'labels'   => array(),
			),
			'service'       => array(
				'header'   => 'Services',
				'resource' => 'service',
				'fields'   => array( 'id', 'api_name', 'type_id', 'storage_type_id', 'is_active' ),
				'labels'   => array( 'ID', 'Endpoint', 'Type', 'Storage Type', 'Active' ),
			),
			'schema'        => array(
				'header'   => 'Schema Manager',
				'resource' => 'schema',
				'fields'   => array(),
				'labels'   => array(),
			),
			'config'        => array(
				'header'   => 'System Configuration',
				'resource' => 'config',
				'fields'   => array(),
				'labels'   => array(),
			),
			'provider'      => array(
				'header'   => 'Auth Providers',
				'resource' => 'provider',
				'fields'   => array( 'id', 'provider_name', 'api_name' ),
				'labels'   => array( 'ID', 'Name', 'Endpoint' ),
			),
			'provider_user' => array(
				'header'   => 'Provider Users',
				'resource' => 'provider_user',
				'fields'   => array( 'id', 'user_id', 'provider_id', 'provider_user_id', 'last_use_date' ),
				'labels'   => array( 'ID', 'User', 'Provider', 'Provider User ID', 'Last Used' ),
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
	public function actionProviders()
	{
		if ( Pii::postRequest() )
		{
		}

		$this->render( 'Providers' );
	}
}
