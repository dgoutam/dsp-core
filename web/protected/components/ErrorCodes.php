<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Defaults
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class ErrorCodes
{
    /**
     * Constants
     */

    const OK                    = 200;
    const CREATED               = 201;
    const BAD_REQUEST           = 400;
    const UNAUTHORIZED          = 401;
    const FORBIDDEN             = 403;
    const NOT_FOUND             = 404;
    const METHOD_NOT_ALLOWED    = 405;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED       = 501;


    private static $titles = array(
        200 => 'OK',
        201 => 'Created',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
    );

    public static function getHttpStatusCodeTitle($code)
    {
        return Utilities::getArrayValue($code, static::$titles, '');
    }

    public static function getHttpStatusCode($code)
    {
        // if not valid code, return 500 - server error
        return (isset(static::$titles[$code])) ? $code : static::INTERNAL_SERVER_ERROR;
    }
}
