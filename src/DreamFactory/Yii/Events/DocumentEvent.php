<?php
namespace DreamFactory\Yii\Events;

/**
 * DocumentEvent
 * Contains the events triggered by a document
 */
class DocumentEvent extends ModelEvent
{
	//*************************************************************************
	//* Properties
	//*************************************************************************

	/**
	 * @return mixed
	 */
	public function getDocument()
	{
		return $this->getTarget();
	}

	/**
	 * @return mixed
	 */
	public function getResponse()
	{
		return $this->_response;
	}

}
