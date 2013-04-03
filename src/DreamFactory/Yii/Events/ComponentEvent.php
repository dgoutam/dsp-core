<?php
namespace DreamFactory\Yii\Events;

/**
 * ComponentEvent
 * Contains the events triggered by a component
 */
class ComponentEvent extends DreamEvent
{
	//*************************************************************************
	//* Class Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const AfterConstruct = 'after_construct';

	/**
	 * @var string
	 */
	const BeforeDestruct = 'before_destruct';

}
