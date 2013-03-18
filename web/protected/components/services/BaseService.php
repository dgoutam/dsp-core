<?php
use Kisma\Core\SeedBag;

/**
 * Class BaseService
 */
abstract class BaseService extends SeedBag implements iRestHandler
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
	protected $_apiType;
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
		parent::__construct( $settings );

		if ( null === $this->_apiName )
		{
			throw new InvalidArgumentException( 'You must supply a value for "api_name".' );
		}

		if ( null === $this->_apiType )
		{
			throw new InvalidArgumentException( 'You must supply a value for "api_type".' );
		}
	}

	/**
	 * @param mixed  $request
	 * @param string $component
	 */
	protected function _permissionCheck( $request, $component = null )
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
	 * @param string $apiType
	 *
	 * @return BaseService
	 */
	public function setApiType( $apiType )
	{
		$this->_apiType = $apiType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getApiType()
	{
		return $this->_apiType;
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
