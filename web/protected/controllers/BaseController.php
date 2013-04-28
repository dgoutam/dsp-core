<?php
use DreamFactory\Platform\Interfaces\PlatformStates;

\Yii::import( 'DreamFactory.Platform.Interfaces.PlatformStates' );

/**
 * BasePlatformController.php
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
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
class BasePlatformController extends \CController implements PlatformStates
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_menu = array();
	/**
	 * @var array
	 */
	protected $_breadcrumbs = array();

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * {@InheritDoc}
	 */
	public function init()
	{
		parent::init();

		$this->layout = false;
	}

	/**
	 * Overridden to log API requests to local graylog server
	 *
	 * @param CAction $action
	 *
	 * @return bool
	 */
	protected function beforeAction( $action )
	{
		$_host = $_SERVER['HTTP_HOST'];

		//	Get the additional data ready
		$_logInfo = array(
			'short_message' => 'DSP <--- "' . $action->id . '"',
			'full_message'  => 'Inbound DSP request to DSP from "' . $_host . '": ' . $action->id,
			'level'         => GraylogLevels::Info,
			'facility'      => 'platform/api',
			'source'        => $_SERVER['REMOTE_ADDR'],
			'payload'       => $_REQUEST,
		);

		GelfLogger::logMessage( $_logInfo );

		return parent::beforeAction( $action );
	}

	/**
	 * Makes a file tree. Used exclusively by the snapshot service at this time.
	 *
	 * @param string $instanceName
	 * @param string $path
	 *
	 * @return string
	 */
	public function actionFileTree( $instanceName = null, $path = null )
	{
		$_data = array();
		$_storagePath = realpath( Defaults::getStoragePath() );

		if ( !empty( $_storagePath ) )
		{
			$_objects = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $_storagePath ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			/** @var $_node \SplFileInfo */
			foreach ( $_objects as $_name => $_node )
			{
				if ( $_node->isDir() || $_node->isLink() || ' . ' == $_name || ' ..' == $_name )
				{
					continue;
				}

				$_cleanPath = str_ireplace( $_path, null, dirname( $_node->getPathname() ) );

				if ( empty( $_cleanPath ) )
				{
					$_cleanPath = ' / ';
				}

				$_data[$_cleanPath][] = basename( $_name );
			}
		}

		echo json_encode( $_data );
		die();
	}
}