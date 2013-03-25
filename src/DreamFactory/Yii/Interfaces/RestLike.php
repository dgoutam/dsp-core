<?php
namespace DreamFactory\Yii\Interfaces;

/**
 * RestLike
 * Controllers that can talk REST
 */
interface RestLike extends ControllerLike
{
	/**
	 * @return mixed
	 */
	public function restGet();

	/**
	 * @return mixed
	 */
	public function restPost();

	/**
	 * @return mixed
	 */
	public function restPatch();

	/**
	 * @return mixed
	 */
	public function restPut();

	/**
	 * @return mixed
	 */
	public function restMerge();

	/**
	 * @return mixed
	 */
	public function restDelete();
}
