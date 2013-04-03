<?php
namespace DreamFactory\Yii\Events;

/**
 * ControllerEvent
 * Contains the events triggered by a controller
 */
class ControllerEvent extends DreamEvent
{
	//*************************************************************************
	//* Class Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const AfterAction = 'after_action';

	/**
	 * @var string
	 */
	const BeforeAction = 'before_action';

	//*************************************************************************
	//* Private Members
	//*************************************************************************

	/**
	 * @var mixed The result of the event
	 */
	protected $_result = null;

	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * @param mixed $result
	 *
	 * @return \Kisma\Event\ControllerEvent
	 */
	public function setResult( $result )
	{
		$this->_result = $result;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->_result;
	}
}
