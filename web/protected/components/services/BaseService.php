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
 * BaseService
 * A base service class to handle services of various kinds.
 */
abstract class BaseService implements iService
{
	//*************************************************************************
	//* Members
	//*************************************************************************

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

	/**
	 * @var string
	 */
	protected $_nativeFormat = '';

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

		// Validate basic settings
		$this->_apiName = Utilities::getArrayValue( 'api_name', $settings, '' );

		if ( empty( $this->_apiName ) )
		{
			throw new \InvalidArgumentException( 'Service name can not be empty.' );
		}

		$this->_type = Utilities::getArrayValue( 'type', $settings, '' );

		if ( empty( $this->_type ) )
		{
			throw new \InvalidArgumentException( 'Service type can not be empty.' );
		}

		$this->_name = Utilities::getArrayValue( 'name', $settings, '' );
		$this->_description = Utilities::getArrayValue( 'description', $settings, '' );
		$this->_nativeFormat = Utilities::getArrayValue( 'native_format', $settings, '' );
		$this->_isActive = Utilities::boolval( Utilities::getArrayValue( 'is_active', $settings, false ) );
	}

	/**
	 * @param string $request
	 * @param string $component
	 */
	protected function checkPermission( $request, $component = '' )
	{
		UserManager::checkSessionPermission( $request, $this->_apiName, $component );
	}

	/**
	 * @param string $apiName
	 *
	 * @return BaseService
	 */
	public function setApiName( $apiName )
	{
		$this->_apiName = $apiName;

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
	 * @return BaseService
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
	 * @return BaseService
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
	 * @return BaseService
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
	 * @return BaseService
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

	/**
	 * @param string $nativeFormat
	 *
	 * @return BaseService
	 */
	public function setNativeFormat( $nativeFormat )
	{
		$this->_nativeFormat = $nativeFormat;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNativeFormat()
	{
		return $this->_nativeFormat;
	}
}
