<?php
namespace DreamFactory\Yii\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * DreamEvent
 * Wrapper for an event triggered within DF's stuff
 *
 * @property-read mixed $target
 */
abstract class DreamEvent extends Event
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * The object where this event was thrown
	 *
	 * @var mixed
	 */
	protected $_target;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param mixed $target The event in which the event occurred
	 */
	public function __construct( $target )
	{
		$this->_target = $target;
	}

	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * Returns the app in which this event was thrown
	 *
	 * @return mixed
	 */
	public function getTarget()
	{
		return $this->_target;
	}

}
