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
 * RestService
 * A base class to handle service resources accessed through the REST API.
 */
abstract class RestResource extends BaseResource implements HttpMethod
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_action = self::Get;


	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string $action
	 */
	protected function _setAction( $action = self::Get )
	{
		$this->_action = strtoupper( $action );
	}

	/**
	 * Apply the commonly used REST path members to the class
	 */
	protected function _detectResourceMembers()
	{
	}

	/**
	 *
	 */
	protected function _preProcess()
	{
		// throw exception here to stop processing
	}

	/**
	 * @param null $results
	 */
	protected function _postProcess( $results = null )
	{

	}

	/**
	 * @return bool
	 */
	protected function _handleAction()
	{
		return false;
	}

	/**
	 * @param string $action
	 *
	 * @return bool
	 */public function processRequest( $action = self::Get)
	{
		$this->_setAction( $action );
		$this->_detectResourceMembers();

		$this->_preProcess();

		$results = $this->_handleAction();

		$this->_postProcess( $results );

		return $results;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerApis()
	{
		return array();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getSwaggerModels()
	{
		return array();
	}
}
