<?php
use Kisma\Core\SeedBag;

/**
 * BaseService.php
 * A base service class to handle services accessed through the REST API.
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2009-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
abstract class BaseService implements iRestHandler
// not quite ready to let the seed out of the bag
//abstract class BaseService extends SeedBag implements iRestHandler
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
	protected $_nativeFormat;

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
		SessionManager::checkPermission( $request, $this->_apiName, $component );
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionSwagger()
	{
		return SwaggerUtilities::swaggerBaseInfo( $this->_apiName );
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
