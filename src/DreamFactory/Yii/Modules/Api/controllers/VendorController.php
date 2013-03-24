<?php
/**
 * no namespace, Yii don't like 'em in controllers...
 * namespace Cerberus\Yii\Modules\Api\Controllers;
 */
use Cerberus\Yii\Controllers\ResourceController;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;

/**
 * VendorController
 */
class VendorController extends ResourceController
{
	//*************************************************************************
	//* Public Actions
	//*************************************************************************

	/**
	 * Initialize and set our resource type
	 */
	public function init()
	{
		$this->_resourceClass = '\\Vendor';

		parent::init();
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array();
	}

	/**
	 * Delete a resource
	 *
	 * @param string|int $id
	 *
	 * @return bool
	 * @throws \CDbException
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 */
	public function delete( $id )
	{
		throw new RestException( HttpResponse::NotImplemented );
	}

	/**
	 * @param int $userId
	 * @param int $vendorId
	 *
	 * @return array
	 */
	public function getVendorCredentials( $userId, $vendorId = null )
	{
		$_criteria = array(
			'condition' => 'user_id = :user_id',
			'params'    => array(
				':user_id' => $userId,
			)
		);

		if ( null !== $vendorId )
		{
			$_criteria['condition'] .= ' and vendor_id = :vendor_id';
			$_criteria['params'][':vendor_id'] = $vendorId;
		}

		$_credentials = \Cerberus\Yii\Models\Auth\VendorCredentials::model()->findAll( $_criteria );

		if ( empty( $_credentials ) )
		{
			return array();
		}

		$_result = array();

		/** @var $_cred \Cerberus\Yii\Models\Auth\VendorCredentials */
		foreach ( $_credentials as $_cred )
		{
			$_result[] = $_cred->getRestAttributes();
		}

		return $_credentials;
	}

	/**
	 * @param array $payload
	 *
	 * @return bool
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 */
	public function postVendorCredentials( $payload )
	{
		$_vendorId = FilterInput::post( 'vendorId', $payload );
		$_userId = FilterInput::post( 'userId', $payload );
		$_keys = FilterInput::post( 'keys', $payload );

		if ( !$_vendorId || !$_userId || !$_keys )
		{
			throw new RestException( HttpResponse::BadRequest, 'Missing vendor, user or keys.' );
		}

		$_vendor = $this->_validateRequest( $_vendorId );

		$_credentials = new \Cerberus\Yii\Models\Auth\VendorCredentials();
		$_credentials->user_id = $_userId;
		$_credentials->vendor_id = $_vendor->id;
		$_credentials->keys_text = $_keys;

		return $_credentials->save();
	}

	/**
	 * @param int|string $id
	 * @param array      $payload
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return \Instance
	 */
	protected function _validateRequest( $id, $payload = null )
	{
		if ( empty( $id ) )
		{
			throw new RestException( HttpResponse::BadRequest );
		}

		/** @var $_vendor \Vendor */
		$_vendor = \Vendor::model()->find(
			'vendor_name_text = :id or id = :id',
			array(
				 ':id' => $id,
			)
		);

		if ( null === $_vendor )
		{
			throw new RestException( HttpResponse::NotFound, 'Vendor ID "' . $id . '" Not found.' );
		}

		return $_vendor;
	}

}