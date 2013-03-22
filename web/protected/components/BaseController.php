<?php
/**
 * BaseController.php
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
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
class BaseController extends CController implements PlatformStates
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_menu = array();
	/**
	 * @var array
	 */
	protected $_breadcrumbs = array();

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			'captcha' => array(
				'class'     => 'CCaptchaAction',
				'backColor' => 0xFFFFFF,
			),
			'page'    => array(
				'class' => 'CViewAction',
			),
		);
	}

	/**
	 * Overridden to log API requests to local graylog server
	 *
	 * @param CAction $action
	 *
	 * @return bool
	 */
	protected function beforeAction( $action )
	{
		//	Get the additional data ready
		$_logInfo = array(
			'short_message' => 'dsp request from "' . Pii::getParam( 'dsp_name' ) . '": ' . $action->id,
			'full_message'  => 'dsp request from "' . Pii::getParam( 'dsp_name' ) . '": ' . $action->id,
			'level'         => GraylogLevels::Info,
			'facility'      => 'dsp/api',
			'source'        => 'web',
			'payload'       => null,
		);

		GelfLogger::logMessage( $_logInfo );

		return parent::beforeAction( $action );
	}
}