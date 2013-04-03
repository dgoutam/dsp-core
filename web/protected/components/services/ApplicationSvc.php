<?php

/**
 * @category   DreamFactory
 * @package    web-csp
 * @subpackage ApplicationSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class ApplicationSvc extends CommonFileSvc
{
	/**
	 * @param array $config
	 *
	 * @throws Exception
	 */
	public function __construct( $config )
	{
		// Validate storage setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			$config['storage_name'] = 'applications';
		}
		parent::__construct( $config );
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		parent::__destruct();
	}

	// Controller based methods

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionGet()
	{
		$this->checkPermission( 'read' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// list app folders only for now
			return $this->fileRestHandler->getFolderContent( '', false, true, false );
		}

		return parent::actionGet();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		$this->checkPermission( 'create' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file creation." );
		}

		return parent::actionPost();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		$this->checkPermission( 'update' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		if ( empty( $path_array ) || ( ( 1 === count( $path_array ) ) && empty( $path_array[0] ) ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file updates." );
		}

		return parent::actionPut();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		$this->checkPermission( 'update' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file updates." );
		}

		return parent::actionMerge();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		$this->checkPermission( 'delete' );
		$path_array = Utilities::getArrayValue( 'resource', $_GET, '' );
		$path_array = ( !empty( $path_array ) ) ? explode( '/', $path_array ) : array();
		$app_root = ( isset( $path_array[0] ) ? $path_array[0] : '' );
		if ( empty( $app_root ) )
		{
			// for application management at root directory,
			throw new Exception( "Application service root directory is not available for file deletes." );
		}
		$more = ( isset( $path_array[1] ) ? $path_array[1] : '' );
		if ( empty( $more ) )
		{
			// dealing only with application root here
			$content = Utilities::getPostDataAsArray();
			if ( empty( $content ) )
			{
				throw new Exception( "Application root directory is not available for delete. Use the system API to delete the app." );
			}
		}

		return parent::actionDelete();
	}

}
