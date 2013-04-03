<?php
namespace DreamFactory\Yii\Events;

/**
 * ModelEvent
 * Contains the events triggered by a model
 */
class ModelEvent extends DreamEvent
{
	//*************************************************************************
	//* Class Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const AfterValidate = 'after_validate';
	/**
	 * @var string
	 */
	const BeforeValidate = 'before_validate';
	/**
	 * @var string
	 */
	const AfterFind = 'after_find';
	/**
	 * @var string
	 */
	const BeforeFind = 'before_find';
	/**
	 * @var string
	 */
	const AfterSave = 'after_save';
	/**
	 * @var string
	 */
	const BeforeSave = 'before_save';
	/**
	 * @var string
	 */
	const BeforeDelete = 'before_delete';
	/**
	 * @var string
	 */
	const AfterDelete = 'after_delete';

	//*************************************************************************
	//* Private Members
	//*************************************************************************

	/**
	 * @var mixed
	 */
	protected $_response;

	/**
	 * @param      $target
	 * @param null $response
	 *
	 * @internal param mixed|null $result
	 */
	public function __construct( $target, $response = null )
	{
		parent::__construct( $target );

		$this->_response = $response;
	}

	/**
	 * @return mixed
	 */
	public function getResponse()
	{
		return $this->_response;
	}

}
