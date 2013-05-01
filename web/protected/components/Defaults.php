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
/**
 * Defaults
 */
class Defaults
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Constructs the virtual storage path
	 *
	 * @param string $append
	 *
	 * @return string
	 */
	public static function getStoragePath( $append = null )
	{
		$_base = Pii::getParam( 'storage_path' );

		if ( !file_exists( $_base ) )
		{
			@mkdir( $_base, 0777, true );
		}

		return $_base . ( $append ? '/' . $append : null );
	}

	/**
	 * @param string $namespace
	 *
	 * @return string
	 */
	public static function uuid( $namespace = null )
	{
		static $_uuid = null;

		$_hash = strtoupper(
			hash(
				'ripemd128',
				uniqid( '', true ) . ( $_uuid ? : microtime( true ) ) . md5(
					$namespace . $_SERVER['REQUEST_TIME']
					. $_SERVER['HTTP_USER_AGENT']
					. $_SERVER['LOCAL_ADDR']
					. $_SERVER['LOCAL_PORT']
					. $_SERVER['REMOTE_ADDR']
					. $_SERVER['REMOTE_PORT']
				)
			)
		);

		$_uuid = '{' .
				 substr( $_hash, 0, 8 ) .
				 '-' .
				 substr( $_hash, 8, 4 ) .
				 '-' .
				 substr( $_hash, 12, 4 ) .
				 '-' .
				 substr( $_hash, 16, 4 ) .
				 '-' .
				 substr( $_hash, 20, 12 ) .
				 '}';

		return $_uuid;
	}
}
