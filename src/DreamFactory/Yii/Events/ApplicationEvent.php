<?php
namespace DreamFactory\Yii\Events;
/**
 * ApplicationEvent
 * Contains the events triggered by an application
 */
class ApplicationEvent extends DreamEvent
{
	//*************************************************************************
	//* Class Constants
	//*************************************************************************

	/**
	 * Triggered as application initialization begins
	 * dispatching
	 *
	 * @var string
	 */
	const Initialize = 'initialize';

	/**
	 * The TERMINATE is triggered when the application is ending
	 *
	 * @var string
	 */
	const Terminate = 'terminate';

}
