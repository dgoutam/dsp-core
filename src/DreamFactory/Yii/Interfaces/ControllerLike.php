<?php
namespace DreamFactory\Yii\Interfaces;

use Kisma\Core\Interfaces\SeedLike;

/**
 * ControllerLike
 * Things that act like controllers
 */
interface ControllerLike extends SeedLike
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const PreProcess = 'dreamfactory.yii.controller_like.pre_process';
	/**
	 * @var string
	 */
	const PostProcess = 'dreamfactory.yii.controller_like.post_process';
}
