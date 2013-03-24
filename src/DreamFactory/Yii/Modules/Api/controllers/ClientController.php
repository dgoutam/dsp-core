<?php

/**
 * ClientController
 */
use Cerberus\Yii\Controllers\ResourceController;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Hasher;

class ClientController extends ResourceController
{
	//*************************************************************************
	//* Public Actions
	//*************************************************************************
	public function init()
	{
		$this->_resourceClass = '\\Cerberus\\Yii\\Models\\Auth\\User';
		parent::init();

		$this->addUserActions(
			static::Any,
			array(
				 'generate',
			)
		);
	}

	/**
	 * @param string $seed
	 *
	 * @return array
	 */
	public function postGenerate( $seed = null )
	{
		$_key = ( $seed ? : Hasher::generateUnique() ) . getmyuid() . getmypid() . microtime( true );

		$_id = md5( $_key );
		$_secret = hash_hmac( 'sha256', $_id, $_key );

		return array( 'client_id' => $_id, 'client_secret' => $_secret );
	}

	/**
	 * @param string $id
	 * @param string $secret
	 */
	public function postAuthorize( $id, $secret )
	{
		$_client = $this->_validateRequest( $id, $secret );
	}

	/**
	 * @param string $id
	 * @param string $secret
	 */
	public function getToken()
	{
//		$_client = $this->_validateRequestToken( $requestToken );
	}

	/**
	 * @param int|string $id
	 * @param string     $secret
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return AuthClient
	 */
	protected function _validateRequest( $id, $secret = null )
	{
		if ( empty( $id ) || empty( $secret ) )
		{
			throw new RestException( HttpResponse::BadRequest );
		}

		$_client = AuthClient::model()->find(
			'client_id_text = :id and client_secret_text = :secret',
			array(
				 ':id'     => $id,
				 ':secret' => $secret,
			)
		);

		if ( null === $_client )
		{
			throw new RestException( HttpResponse::BadRequest );
		}

		return $this->_resource = $_client;
	}
}