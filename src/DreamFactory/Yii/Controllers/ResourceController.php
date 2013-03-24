<?php
namespace Cerberus\Yii\Controllers;

use Cerberus\Yii\Models\Auth\User;
use DreamFactory\Yii\Controllers\SecureRestController;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Models\BaseModel;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;

/**
 * ResourceController
 * A generic resource controller
 */
class ResourceController extends SecureRestController
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var BaseModel
	 */
	protected $_resource = null;
	/**
	 * @var string
	 */
	protected $_resourceClass = null;
	/**
	 * @var User
	 */
	protected $_resourceUser = null;
	/**
	 * @var bool
	 */
	protected $_adminView = false;

	//*************************************************************************
	//* Public Actions
	//*************************************************************************

	/**
	 * Retrieve an instance
	 *
	 * @param string|int $id
	 *
	 * @return array
	 */
	public function get( $id )
	{
		return $this->_validateRequest( $id )->getRestAttributes();
	}

	/**
	 * @param string|int $id
	 * @param array      $payload
	 *
	 * @throws RestException
	 * @return array|null
	 */
	public function put( $id, $payload )
	{
		throw new RestException( HttpResponse::NotImplemented, 'Please use "POST".' );
	}

	/**
	 * Delete a resource
	 *
	 * @param string|int $id
	 *
	 * @return bool
	 * @throws \CDbException
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 */
	public function delete( $id )
	{
		return $this->_validateRequest( $id )->delete();
	}

	/**
	 * Create/update a resource
	 *
	 * @param string|int $id
	 * @param array      $payload
	 *
	 * @return array|null
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 */
	public function post( $id, $payload = null )
	{
		if ( empty( $this->_resourceClass ) )
		{
			throw new RestException( HttpResponse::NotImplemented );
		}

		if ( is_array( $id ) )
		{
			//	new
			$_resource = new $this->_resourceClass;
			$payload = $id;
			unset( $payload['id'] );
		}
		else
		{
			$_resource = $this->_validateRequest( $id, $payload );
		}

		unset( $payload['createDate'], $payload['lastModifiedDate'], $payload['userId'] );

		try
		{
			$_resource->setRestAttributes( $payload );
			$payload['user_id'] = Pii::user()->id;

			$_resource->save();

			return $_resource->getRestAttributes();
		}
		catch ( \CDbException $_ex )
		{
			Log::error( 'Exception saving resource "' . $this->_resourceClass . '::' . $_resource->id . '": ' . $_ex->getMessage() );
			throw new RestException( HttpResponse::InternalServerError );
		}
	}

	/**
	 * @param int|string $id
	 * @param array      $payload
	 *
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 * @return \Instance
	 */
	protected function _validateRequest( $id, $payload = null )
	{
		if ( empty( $id ) )
		{
			throw new RestException( HttpResponse::BadRequest );
		}

		throw new RestException( HttpResponse::NotImplemented );
	}

	/**
	 * @param \Cerberus\Yii\Models\Auth\User $resourceUser
	 *
	 * @return $this
	 */
	public function setResourceUser( $resourceUser )
	{
		$this->_resourceUser = $resourceUser;

		return $this;
	}

	/**
	 * @return \Cerberus\Yii\Models\Auth\User
	 */
	public function getResourceUser()
	{
		return $this->_resourceUser;
	}

	/**
	 * @param \DreamFactory\Yii\Models\BaseModel $resource
	 *
	 * @return ResourceController
	 */
	public function setResource( $resource )
	{
		$this->_resource = $resource;

		return $this;
	}

	/**
	 * @return \DreamFactory\Yii\Models\BaseModel
	 */
	public function getResource()
	{
		return $this->_resource;
	}

	/**
	 * @param string $resourceClass
	 *
	 * @return $this
	 */
	public function setResourceClass( $resourceClass )
	{
		$this->_resourceClass = $resourceClass;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getResourceClass()
	{
		return $this->_resourceClass;
	}

	/**
	 * @param boolean $adminView
	 *
	 * @return AuthResourceController
	 */
	public function setAdminView( $adminView )
	{
		$this->_adminView = $adminView;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getAdminView()
	{
		return $this->_adminView;
	}
}