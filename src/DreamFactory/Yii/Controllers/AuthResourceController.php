<?php
namespace Cerberus\Yii\Controllers;

use Cerberus\Yii\Models\Auth\User;
use DreamFactory\Yii\Controllers\AuthClient;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Enums\OutputFormat;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;

/**
 * AuthResourceController
 * A generic authenticated resource controller
 */
class AuthResourceController extends ResourceController
{
	//*************************************************************************
	//* Public Actions
	//*************************************************************************

	/**
	 * @param array $data
	 * @param bool  $requireToken
	 *
	 * @throws \DreamFactory\Yii\Exceptions\RestException
	 * @return array|bool
	 */
	protected function _validateUser( $data, $requireToken = true )
	{
		$_requestUser = $_user = $_userId = $_token = null;

		if ( isset( $data['user_id'], $data['access_token'] ) )
		{
			$_userId = FilterInput::get( $data, 'user_id' );
			$_token = FilterInput::get( $data, 'access_token' );

			$this->setOutputFormat( static::RESPONSE_FORMAT_V2 );

			/** @var $_user User */
			$_user = User::model()->byTokenOrEmailOrDrupalOhMy( $_token, null, $_userId )->find();
			$_requestUser = $_user->asDrupalObject();
		}
		else if ( isset( $data['user'], $data['token'] ) )
		{
			$_requestUser = FilterInput::get( $data, 'user' );
			$_token = FilterInput::get( $data, 'token' );

			$this->setOutputFormat( static::RESPONSE_FORMAT_V1 );

			if ( is_string( $_requestUser ) )
			{
				$_requestUser = json_decode( $_requestUser );

				foreach ( $_requestUser as $_key => $_value )
				{
					if ( is_scalar( $_value ) )
					{
						continue;
					}

					//	Yank out the drupal goodies
					if ( is_object( $_value ) && isset( $_value->und ) )
					{
						$_requestUser->{$_key} = $_value->und[0]->value;
					}

					if ( is_array( $_value ) && empty( $_value ) )
					{
						$_requestUser->{$_key} = null;
					}
				}
			}

			/** @var $_user User */
			$_user = User::model()
				->byTokenOrEmailOrDrupalOhMy( $_token, $_requestUser->mail, $_requestUser->uid )
				->find();
		}

		if ( empty( $_user ) )
		{
			Log::info( 'ARC Drupal validation fail: token=[' . $_token . '] userId=[' . $_userId . '] user=[' . var_export( $_requestUser,
					true ) . ']' . PHP_EOL .
					' data=[' . print_r( $data, true ) . ']' . PHP_EOL .
					' post=[' . print_r( $_POST, true ) . ']' . PHP_EOL .
					' server=[' . print_r( $_SERVER, true ) . ']' . PHP_EOL .
					' request=[' . print_r( $_REQUEST, true ) . ']' . PHP_EOL
			);
			throw new RestException( HttpResponse::BadRequest );
		}

		$this->_adminView = FilterInput::get( $data, 'admin_view', false );

		if ( 1 != $_user->admin_ind )
		{
			$this->_adminView = false;
		}

		Log::debug( 'Drupal user validated: ' . $_user->email_addr_text );

		return array(
			$_user,
			$_requestUser,
		);
	}
}