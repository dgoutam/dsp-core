<?php
namespace DreamFactory\Yii\Controllers;

/**
 * SecureRestController
 * Validates request before allowing
 *
 * @copyright Copyright (c) 2013 DreamFactory Software, Inc.
 * @link      DreamFactory Software, Inc. <http://www.dreamfactory.com>
 * @package   cerberus
 * @filesource
 * @author    Jerry Ablan <jerryablan@dreamfactory.com>
 */
use Kisma\Core\Enums\OutputFormat;

abstract class SecureRestController extends DreamRestController
{
	//*************************************************************************
	//* Abstract Methods
	//*************************************************************************

	/**
	 * Validate that the parameters provided are valid
	 *
	 * @param string|int $id
	 * @param array      $payload
	 */
	abstract protected function _validateRequest( $id, $payload = null );

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Initialize the controller
	 */
	public function init()
	{
		parent::init();

		$this->layout = false;
		$this->setResponseFormat( static::RESPONSE_FORMAT_V2 );
		$this->setOutputFormat( OutputFormat::JSON );
	}
}
