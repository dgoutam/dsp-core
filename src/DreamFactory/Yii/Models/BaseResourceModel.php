<?php
namespace DreamFactory\Yii\Models;

use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Log;
use DreamFactory\Yii\Utility\Pii;

/**
 * BaseResourceModel.php
 *
 * The base class for all resource models. For use with REST APIs
 */
class BaseResourceModel extends BaseModel
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const CAMEL_CASE = 0;
	/**
	 * @var int
	 */
	const SNAKE_CASE = 1;
	/**
	 * @var int
	 */
	const STUDLY_CAPS = 2;

	//*******************************************************************************
	//* Members
	//*******************************************************************************

	/**
	 * @var array Our REST mappings
	 */
	protected $_restMap = null;
	/**
	 * @var bool
	 */
	protected $_autogenerateMap = true;
	/**
	 * @var int
	 */
	protected $_attributeStyle = self::CAMEL_CASE;

	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * Init
	 */
	public function init()
	{
		parent::init();

		//	Generate if wanted
		if ( true === $this->_autogenerateMap && empty( $this->_restMap ) )
		{
			$this->_generateRestMap();
		}
	}

	/**
	 * @param boolean $autogenerateMap
	 *
	 * @return BaseResourceModel
	 */
	public function setAutogenerateMap( $autogenerateMap )
	{
		$this->_autogenerateMap = $autogenerateMap;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getAutogenerateMap()
	{
		return $this->_autogenerateMap;
	}

	/**
	 * @param array $restMap
	 *
	 * @return BaseResourceModel
	 */
	public function setRestMap( $restMap )
	{
		$this->_restMap = $restMap;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRestMap()
	{
		return $this->_restMap;
	}

	//*******************************************************************************
	//* REST Methods
	//*******************************************************************************

	/**
	 * If a model has a REST mapping, attributes are mapped an returned in an array.
	 *
	 * @return array|null The resulting view
	 */
	public function getRestAttributes()
	{
		$_map = $this->_restMap;

		//	Generate a map
		if ( empty( $_map ) )
		{
			return null;
		}

		$_results = array();
		$_columns = $this->getSchema();

		foreach ( $_map as $_key => $_value )
		{
			$_attributeValue = $this->getAttribute( $_key );

			if ( !isset( $_columns[$_key] ) )
			{
				continue;
			}

			//	Apply formats
			switch ( $_columns[$_key]->dbType )
			{
				case 'date':
				case 'datetime':
				case 'timestamp':
					//	Handle blanks
					if ( null !== $_attributeValue && $_attributeValue != '0000-00-00' && $_attributeValue != '0000-00-00 00:00:00' )
					{
						$_attributeValue = date( 'c', strtotime( $_attributeValue ) );
					}
					break;
			}

			$_results[$_value] = $_attributeValue;
		}

		return $_results;
	}

	/**
	 * Sets the values in the model based on REST attribute names
	 *
	 * @param array $attributeList
	 *
	 * @return \DreamFactory\Yii\Models\BaseModel
	 */
	public function setRestAttributes( array $attributeList = array() )
	{
		$_map = $this->_restMap;

		if ( !empty( $_map ) )
		{
			foreach ( $attributeList as $_key => $_value )
			{
				if ( false !== ( $_mapKey = array_search( $_key, $_map ) ) )
				{
					$this->setAttribute( $_mapKey, $_value );
				}
			}
		}

		return $this;
	}

	/**
	 * Generates a REST attribute map
	 *
	 * @return mixed
	 */
	protected function _generateRestMap()
	{
		static $_forbiddenFruit = array(
			'password',
			'secure',
			'security',
		);

		$this->_restMap = array();

		foreach ( $this->getAttributes() as $_column => $_value )
		{
			//	Skip columns with senskitive (popeye) info
			foreach ( $_forbiddenFruit as $_term )
			{
				if ( false !== stripos( $_column, $_term ) )
				{
					continue;
				}
			}

			switch ( $this->_attributeStyle )
			{
				case static::SNAKE_CASE:
					$_restColumn = Inflector::decamelize( $_column );
					break;

				case static::STUDLY_CAPS:
					$_restColumn = Inflector::camelize( $_column );
					break;

				case static::CAMEL_CASE:
				default:
					$_restColumn = ucfirst( Inflector::camelize( $_column ) );
					break;
			}

			$this->_restMap[$_column] = $_restColumn;
		}

		return $this->_restMap;
	}
}