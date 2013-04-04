<?php
namespace DreamFactory\Yii\Events;

/**
 * ServiceEvent
 * Contains the events triggered by a service
 */
class ServiceEvent extends DreamEvent
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const AfterServiceCall = 'after_service_call';

	/**
	 * @var string
	 */
	const BeforeServiceCall = 'before_service_call';

}
