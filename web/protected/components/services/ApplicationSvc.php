<?php

/**
 * ApplicationSvc.php
 * A service to handle application-specific file storage accessed through the REST API.
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2009-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class ApplicationSvc extends BaseFileSvc
{
	/**
	 * @param array $config
	 * @param bool  $native
	 */
	public function __construct( $config, $native = false )
	{
		// Validate storage setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			$config['storage_name'] = 'applications';
		}

		parent::__construct( $config, $native );
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
