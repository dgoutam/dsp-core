<?PHP
/**
 * no namespace, Yii don't like 'em in controllers...
 * namespace Cerberus\Yii\Modules\Api\Controllers;
 */
use Cerberus\Yii\Controllers\AuthResourceController;
use Cerberus\Yii\Models\Auth\User;
use Cerberus\Yii\Models\BaseFabricAuthModel;
use Cerberus\Yii\Models\Deploy\Instance;
use Cerberus\Yii\Models\Deploy\RouteHash;
use DreamFactory\Yii\Controllers\DreamRestController;
use DreamFactory\Yii\Exceptions\RestException;
use DreamFactory\Yii\Utility\Pii;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Enums\OutputFormat;
use Kisma\Core\Exceptions\InvalidRequestException;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;

/**
 * DrupalController.php
 * Special API for Drupal to sync users
 *
 * @link       http:// www.dreamfactory.com DreamFactory Software, Inc.
 * @author     Jerry Ablan <jerryablan@dreamfactory.com>
 * @filesource
 */
class DownloadController extends AuthResourceController
{
	//********************************************************************************
	//* Public Methods
	//********************************************************************************

	/**
	 * Initialize the controller
	 *
	 * @throws DreamFactory\Yii\Exceptions\RestException
	 * @return void
	 */
	public function init()
	{
		parent::init();

		$this->setSingleParameterActions( false );
	}

	/**
	 * @return array
	 */
	public function accessRules()
	{
		return array();
	}

	/**
	 * @param string $hash
	 *
	 * @return array|null|void
	 */
	public function get( $hash )
	{
		if ( false === ( $_path = RouteHash::getFileFromHash( $hash ) ) )
		{
			$this->redirect( 'http://www.dreamfactory.com/' );
		}

		Log::info( 'Received download request for: ' . $_path );

		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: public' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/x-gzip' );
		header( 'Content-Length: ' . filesize( $_path ) );
		header( 'Content-Disposition: attachment; filename="' . basename( $_path ) . '"' );
		header( 'Content-Transfer-Encoding: binary' );

		readfile( $_path );

		exit();
	}
}
