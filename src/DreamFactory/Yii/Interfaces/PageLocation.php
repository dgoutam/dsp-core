<?php
namespace DreamFactory\Yii\Interfaces;

/**
 * PageLocation.php
 *
 * @copyright Copyright (c) 2012 DreamFactory Software, Inc.
 * @link      http://www.dreamfactory.com DreamFactory Software, Inc.
 * @author    Jerry Ablan <jerryablan@dreamfactory.com>
 *
 * @filesource
 */
interface PageLocation
{
	//**************************************************************************
	//* Constants
	//**************************************************************************

	/**
	 * @var int Within the <HEAD> section
	 */
	const HEAD = 0;
	/**
	 * @var int After the <BODY> tag
	 */
	const AFTER_BODY_START = 1;
	/**
	 * @var int Before the </BODY> tag
	 */
	const BEFORE_END_BODY = 2;
	/**
	 * @var int The window's "onload" function
	 */
	const WINDOW_ON_LOAD = 3;
	/**
	 * @var int Inside the jQuery doc-ready function
	 */
	const DOCUMENT_READY = 4;
}
