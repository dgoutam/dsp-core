<?php
namespace DreamFactory\Platform\Enums;

use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Inflector;

/**
 * Class LineBreak
 *
 * @package DreamFactory\Platform\Enums
 */
class LineBreak
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const Linux = "\n";
	/**
	 * @var string
	 */
	const Windows = "\r\n";
	/**
	 * @var string
	 */
	const OSX = "\r";

}
