<?php
namespace DreamFactory\Yii\Components;

use Kisma\Core\Interfaces\RequestLike;
use DreamFactory\Yii\Interfaces\PageLocation;
use Kisma\Core\Interfaces\ResponseLike;

/**
 * Routing.php
 * A down and dirty routing controller
 *
 * @copyright Copyright (c) 2012 DreamFactory Software, Inc.
 * @link      http://www.dreamfactory.com DreamFactory Software, Inc.
 * @author    Jerry Ablan <jerryablan@dreamfactory.com>
 *
 * @filesource
 */
class Routing implements PageLocation
{
	/**
	 * @var RequestLike
	 */
	protected $_request;
	/**
	 * @var ResponseLike
	 */
	protected $_response;
}