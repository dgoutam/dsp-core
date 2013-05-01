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
/**
 * Controller
 */
class Controller extends \CController
{
//	/**
//	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
//	 */
//	public $menu = array();
//	/**
//	 * @var array the breadcrumbs of the current page. The value of this property will
//	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
//	 * for more details on how to specify this property.
//	 */
//	public $breadcrumbs = array();
//
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Overridden to log API requests to local graylog server
	 *
	 * @param CAction $action
	 *
	 * @return bool
	 */
	protected function beforeAction( $action )
	{
		$_host = $_SERVER['HTTP_HOST'];

		//	Get the additional data ready
		$_logInfo = array(
			'short_message' => 'DSP <--- "' . $action->id . '"',
			'full_message'  => 'Inbound DSP request from "' . $_host . '": ' . $action->id,
			'level'         => GraylogLevels::Info,
			'facility'      => Graylog::DefaultFacility . '/api',
			'_source'       => $_SERVER['REMOTE_ADDR'],
			'_payload'      => $_REQUEST,
		);

		\GelfLogger::logMessage( $_logInfo );

		return parent::beforeAction( $action );
	}
}