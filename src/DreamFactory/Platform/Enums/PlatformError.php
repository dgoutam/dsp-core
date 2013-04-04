<?php
namespace DreamFactory\Platform\Enums;

use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Inflector;

/**
 * Class PlatformError
 *
 * @package DreamFactory\Platform\Enums
 */
class PlatformError extends HttpResponse
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param int $code
	 *
	 * @return string
	 */
	public static function getHttpStatusCodeTitle( $code )
	{
		return ucfirst( str_replace( '_', ' ', Inflector::neutralize( static::nameOf( $code ) ) ) );
	}

	/**
	 * @param int $code
	 *
	 * @return mixed
	 */
	public static function getHttpStatusCode( $code )
	{
		if ( !static::contains( $code ) )
		{
			return static::InternalServerError;
		}

		return $code;
	}
}
