<?php
namespace DreamFactory\Yii\Events;

/**
 * ContainerEvent
 * A generic container event class
 *
 * @property-read mixed $target
 */
class ContainerEvent extends DreamEvent
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string Triggered with the contents of the container are modified
	 */
	const ContentsModified = 'contents_modified';

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var mixed
	 */
	protected $_property;
	/**
	 * @var mixed
	 */
	protected $_value;

	/**
	 * @param mixed  $target
	 * @param string $property
	 * @param mixed  $value
	 *
	 * @internal param mixed|null $result
	 */
	public function __construct( $target, $property = null, $value = null )
	{
		parent::__construct( $target );

		$this->_property = $property;
		$this->_value = $value;
	}

	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @return mixed
	 */
	public function getProperty()
	{
		return $this->_property;
	}

}
