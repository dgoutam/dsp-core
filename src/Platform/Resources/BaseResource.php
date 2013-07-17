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
namespace Platform\Resources;

use Kisma\Core\Utility\Option;
use Platform\Utility\DataFormat;

/**
 * BaseResource
 * A base service resource class to handle service resources of various kinds.
 */
abstract class BaseResource
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_serviceName;

	/**
	 * @var string
	 */
	protected $_apiName;

	/**
	 * @var string
	 */
	protected $_name;

	/**
	 * @var string
	 */
	protected $_description;

	/**
	 * @var string
	 */
	protected $_type;

	/**
	 * @var boolean
	 */
	protected $_isActive = false;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Create a new service
	 *
	 * @param array $settings configuration array
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct( $settings = array() )
	{
		//parent::__construct( $settings ); // not ready to let the seed out of the bag

		$this->_serviceName = Option::get( $settings, 'service_name', '' );

		if ( empty( $this->_serviceName ) )
		{
			throw new \InvalidArgumentException( 'Service name can not be empty.' );
		}

		$this->_apiName = Option::get( $settings, 'api_name', '' );
		$this->_type = Option::get( $settings, 'type', '' );
		$this->_name = Option::get( $settings, 'name', '' );
		$this->_description = Option::get( $settings, 'description', '' );
		$this->_isActive = DataFormat::boolval( Option::get( $settings, 'is_active', false ) );
	}

	/**
	 * @param string $request
	 */
	protected function checkPermission( $request )
	{
		UserSession::checkSessionPermission( $request, $this->_serviceName, $this->_apiName );
	}

	/**
	 * @param string $service_name
	 *
	 * @return BaseResource
	 */
	public function setServiceName( $service_name )
	{
		$this->_serviceName = $service_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getServiceName()
	{
		return $this->_serviceName;
	}

	/**
	 * @param string $api_name
	 *
	 * @return BaseResource
	 */
	public function setApiName( $api_name )
	{
		$this->_apiName = $api_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getApiName()
	{
		return $this->_apiName;
	}

	/**
	 * @param string $type
	 *
	 * @return BaseResource
	 */
	public function setType( $type )
	{
		$this->_type = $type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param string $description
	 *
	 * @return BaseResource
	 */
	public function setDescription( $description )
	{
		$this->_description = $description;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * @param boolean $isActive
	 *
	 * @return BaseResource
	 */
	public function setIsActive( $isActive )
	{
		$this->_isActive = $isActive;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getIsActive()
	{
		return $this->_isActive;
	}

	/**
	 * @param string $name
	 *
	 * @return BaseResource
	 */
	public function setName( $name )
	{
		$this->_name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}
}
