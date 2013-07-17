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
namespace Platform\Utility;

use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Log;

/**
 * DataCache
 */
class DataCache
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const CACHE_PATH = '/tmp';
	/**
	 * @var string
	 */
	const SALTY_GOODNESS = '/%S9DE,h4|e0O70v)K-[;,_bA4sC<shV4wd3qX!T-bW~WasVRjCLt(chb9mVp$7f';
	/**
	 * @var int
	 */
	const CACHE_TTL = 30;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string $key
	 * @param mixed  $data
	 *
	 * @return bool|string
	 */
	public static function load( $key, $data = null )
	{
		if ( file_exists( $_fileName = static::_getCacheFileName( $key ) ) )
		{
			if ( ( time() - fileatime( $_fileName ) ) > static::CACHE_TTL )
			{
				@unlink( $_fileName );
			}
			else
			{
				$_data = json_decode( Hasher::decryptString( file_get_contents( $_fileName ), static::SALTY_GOODNESS ), true );
				@touch( $_fileName );

				return $_data;
			}
		}

		if ( !empty( $data ) )
		{
			return static::store( $key, $data );
		}

		return false;
	}

	/**
	 * @param string $key
	 * @param mixed  $data
	 *
	 * @return bool
	 */
	public static function store( $key, $data )
	{
		if ( file_exists( $_fileName = static::_getCacheFileName( $key ) ) )
		{
			unlink( $_fileName );
			//Log::debug( 'Removing old cache file: ' . $_fileName );
		}

		if ( !is_string( $data ) )
		{
			$data = json_encode( $data );
		}

		//Log::debug( 'Cached data: ' . $_fileName );

		return file_put_contents( $_fileName, Hasher::encryptString( $data, static::SALTY_GOODNESS ) );
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected static function _getCacheFileName( $key )
	{
		return static::CACHE_PATH . '/.dsp-' . sha1( $key );
	}
}