<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;
use Platform\Interfaces\Graylog;
use Platform\Interfaces\GraylogLevels;
use Platform\Services\Graylog\GelfLogger;

/**
 * Controller
 */
class Controller extends \CController
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array
	 */
	public $breadcrumbs = array();
	/**
	 * @var array
	 */
	public $menu = array();

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
			'short_message' => strtoupper( $action->id ) . ' /' . $this->route,
			'full_message'  => 'Inbound API request from "' . $_host . '": ' . $action->id,
			'level'         => GraylogLevels::Info,
			'facility'      => Graylog::DefaultFacility . '/api',
			'_dsp_name'     => $_host,
			'_source'       => $_SERVER['REMOTE_ADDR'],
			'_payload'      => $_REQUEST,
			'_path'         => Option::get( $_REQUEST, 'path' ),
			'_app_name'     => Option::get( $_REQUEST, 'app_name' ),
			'_method'       => FilterInput::server( 'REQUEST_METHOD' ),
		);

		GelfLogger::logMessage( $_logInfo );

		return true;
	}
}