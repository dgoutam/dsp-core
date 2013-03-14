<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Defaults
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class Defaults
{
	/**
	 * Constants
	 */

	/**
	 * Storage Service Directory Defaults
	 */
	const APPS_STORAGE_NAME = 'applications';

	const ATTACHMENTS_STORAGE_NAME = 'attachments';

	const DOCS_STORAGE_NAME = 'documents';

	const LIBS_STORAGE_NAME = 'libraries';

	/**
	 * Constructs the virtual storage path
	 *
	 * @param string $append
	 *
	 * @return string
	 */
	public static function getStoragePath( $append = null )
	{
		$_base = Yii::app()->params[ 'storage_path' ];

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
