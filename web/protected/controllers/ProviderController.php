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
use DreamFactory\Yii\Controllers\BaseWebController;

/**
 * ProviderController
 */
class ProviderController extends BaseWebController
{
	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * Initialize the controller
	 *
	 * @return void
	 */
	public function init()
	{
		parent::init();

		//	We want merged update/create...
		$this->setSingleViewMode( true );
		$this->defaultAction = 'index';
		$this->setModelClass( 'DreamFactory\\Platform\\Yii\\Models\\Provider' );
		$this->layout = 'admin';

		//	Everything is auth-required
		$this->addUserActions(
			self::Authenticated,
			array(
				 'index',
				 'update',
				 'error',
			)
		);

		$this->setBreadcrumbs(
			array(
				 'Providers' => '/admin/#tab-providers',
			)
		);
	}

	/**
	 * Index page
	 */
	public function actionIndex()
	{
		$_sql
			= <<<MYSQL
SELECT
	i.id,
	i.user_id,
	i.instance_name_text,
	i.public_host_text,
	i.create_date,
	u.email_addr_text,
	u.last_login_date,
	u.drupal_id
FROM
	fabric_deploy.instance_t i,
	fabric_auth.user_t u
WHERE
	i.user_id = u.id;
MYSQL;

		$this->render(
			'index',
			array(
				 'models' => Sql::findAll( $_sql, null, Pii::pdo( 'db.fabric_deploy' ) ),
			)
		);
	}

	/**
	 * This method reads the data from the database and returns the row.
	 * Must override in subclasses.
	 *
	 * @var int $id The primary key to look up
	 * @return \CActiveRecord
	 */
	protected function _load( $id = null )
	{
		//	we get a string key, assume hash...
		if ( !is_numeric( $id ) )
		{
			if ( null === ( $_staticModel = $this->_staticModel() ) )
			{
				return null;
			}

			$_staticModel->unhashId( $id, true );

			return $_staticModel->find();
		}

		return $this->_staticModel()->findByPk( $id );
	}
}
