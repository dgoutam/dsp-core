<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Kisma\Core\Enums\SeedEnum;

/**
 * ErrorCodes
 * Standard HTTP response codes
 */
class ErrorCodes extends SeedEnum
{
	/**
	 * @var int
	 */
	const OK = 200;
	/**
	 * @var int
	 */
	const CREATED = 201;
	/**
	 * @var int
	 */
	const BAD_REQUEST = 400;
	/**
	 * @var int
	 */
	const UNAUTHORIZED = 401;
	/**
	 * @var int
	 */
	const FORBIDDEN = 403;
	/**
	 * @var int
	 */
	const NOT_FOUND = 404;
	/**
	 * @var int
	 */
	const METHOD_NOT_ALLOWED = 405;
	/**
	 * @var int
	 */
	const INTERNAL_SERVER_ERROR = 500;
	/**
	 * @var int
	 */
	const NOT_IMPLEMENTED = 501;

	/**
	 * @param int $code
	 *
	 * @return string
	 */
	public static function getHttpStatusCodeTitle( $code )
	{
		return ucwords( str_replace( '_', ' ', static::nameOf( $code ) ) );
	}

	/**
	 * @param int $code
	 *
	 * @return int
	 */
	public static function getHttpStatusCode( $code )
	{
		//	If not valid code, return 500 - server error
		return !static::contains( $code ) ? static::INTERNAL_SERVER_ERROR : $code;
	}
}
