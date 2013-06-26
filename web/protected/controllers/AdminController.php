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
use Kisma\Core\Interfaces\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;
use Platform\Interfaces\PlatformStates;
use Platform\Services\SystemManager;
use Platform\Resources\UserSession;
use Platform\Yii\Utility\Pii;

/**
 * AdminController.php
 * The administrative site controller
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
	 *
	 */
	public function actionServices()
	{
		if ( Pii::postRequest() )
		{
		}

		$this->render( 'services' );
	}

	/**
	 *
	 */
	public function actionIndex()
	{
		if ( Pii::postRequest() )
		{
		}

		$this->render( 'index' );
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

	/**
	 *
	 */
	public function actionRegistryData()
	{
		$_columns = array(
			'service_name_text',
			'service_tag_text',
			'enabled_ind',
			'last_use_date',
		);

		$_limit = FilterInput::get( INPUT_GET, 'iDisplayLength', null, FILTER_SANITIZE_NUMBER_INT );
		$_limitStart = FilterInput::get( INPUT_GET, 'iDisplayStart', null, FILTER_SANITIZE_NUMBER_INT );
		$_limit = 'LIMIT ' . ( -1 == $_limit ? null : $_limitStart . ', ' . $_limit );

		$_order = array();

		if ( isset( $_GET['iSortCol_0'] ) )
		{
			for ( $_i = 0, $_count = FilterInput::get( INPUT_GET, 'iSortingCols', 0, FILTER_SANITIZE_NUMBER_INT ); $_i < $_count; $_i++ )
			{
				$_column = FilterInput::get( INPUT_GET, 'iSortCol_' . $_i, 0, FILTER_SANITIZE_NUMBER_INT );

				if ( 'true' == FilterInput::get( INPUT_GET, 'bSortable_' . $_column, 0, FILTER_SANITIZE_NUMBER_INT ) )
				{
					$_order[] = $_columns[$_column] . ' ' . FilterInput::get( INPUT_GET, 'sSortDir_' . $_i, null, FILTER_SANITIZE_STRING );
				}
			}
		}

		$_columnList = implode( ',', $_columns );
		$_sort = !empty( $_order ) ? 'ORDER BY ' . implode( ', ', $_order ) : null;

		$_sql = <<<MYSQL
SELECT
	{$_columnList}
FROM
	df_sys_registry
	{$_sort}
	{$_limit}
MYSQL;

		$_response = array();

		if ( false !== ( $_rows = Sql::query( $_sql, array(), Pii::pdo() ) ) )
		{
			foreach ( $_rows as $_row )
			{
				$_data = array();

				for ( $_i = 0, $_count = count( $_columns ); $_i < $_count; $_i++ )
				{
					if ( !empty( $_columns[$_i] ) )
					{
						$_data[] = $_row[$_columns[$_i]];
					}
				}

				$_response['aaData'][] = $_data;

				unset( $_row, $_data );
			}

			unset( $_rows );
		}

		$this->layout = false;
		echo json_encode( $_response );
		Pii::end();
	}
}
