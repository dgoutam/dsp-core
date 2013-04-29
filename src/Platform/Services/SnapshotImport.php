<?php
/**
 * BE AWARE...
 *
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
namespace Platform\Services;

use Kisma\Core\Components\LineReader;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Exceptions\FileSystemException;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;

/**
 * SnapshotImport.php
 * Imports a snapshot file
 */
class SnapshotImport
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const API_ENDPOINT = 'http://cerberus.fabric.dreamfactory.com/api';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_fileName = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Constructor
	 */
	public function __construct( $snapshotFileName )
	{
		if ( !file_exists( $snapshotFileName ) || !is_readable( $snapshotFileName ) )
		{
			throw new \InvalidArgumentException( 'The file "' . $snapshotFileName . '" cannot be found or is not readable.' );
		}

		$this->_fileName = $snapshotFileName;
	}

	/**
	 * @throws RestException
	 * @throws \Kisma\Core\Exceptions\FileSystemException
	 * @return bool
	 */
	protected function _openSnapshot()
	{
		//	1. Grab the tarball...
		$_tempPath = $this->_makeTempDirectory( $_workFile );
		$_workPath = $_tempPath . '/' . $_workFile;

		if ( false === ( @copy( $this->_fileName, $_workPath ) ) )
		{
			throw new FileSystemException( 'Unable to copy snapshot file to temporary work area.' );
		}

		//	2. Crack it open and get the goodies
		$_import = new \PharData( $_workPath );

		try
		{
			$_import->extractTo( dirname( $_workPath ) );

			if ( false === ( $_snapshot = json_decode( file_get_contents( $_tempPath . '/snapshot.json' ) ) ) )
			{
				throw new RestException( HttpResponse::BadRequest, 'Invalid snapshot "' . $this->_fileName . '"' );
			}
		}
		catch ( \Exception $_ex )
		{
			Log::error( 'Error extracting snapshot tarball: ' . $_ex->getMessage() );

			$this->_killTempDirectory( $_tempPath );

			throw new RestException( HttpResponse::BadRequest, 'Invalid snapshot "' . $this->_fileName . '"' );
		}

		return array($_tempPath, $_workPath, $_workFile, $_snapshot);
	}

	/**
	 * Given an instance and a snapshot ID, replace the data with that of the snapshot.
	 *
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public function restore()
	{
		//	1. Get the goodies
		list( $_tempPath, $_workPath, $_workFile, $_snapshot ) = $this->_openSnapshot();

		$_storagePath = \Pii::getParam( 'storage_path' );
		$_dbName = \Pii::getParam( 'dsp_name' );

		if ( empty( $_storagePath ) || ( false === strpos( $_storagePath, '/data/storage/', 0 ) && $_storagePath != '/../storage' ) )
		{
			throw new \InvalidArgumentException( 'Invalid storage path "' . $_storagePath . '" specified.' );
		}

		if ( empty( $_dbName ) )
		{
			throw new \InvalidArgumentException( 'Invalid database name "' . $_dbName . '" specified.' );
		}

		//	2. Restore storage...
		$_command =
			'cd ' . $_storagePath . '; rm -rf ' . $_storagePath . '/*; /bin/tar zxf ' . $_tempPath . '/' . $_snapshot->storage->tarball . ' ./';
		$_result = exec( $_command, $_output, $_return );

		if ( 0 != $_return )
		{
			Log::error( 'Error restoring storage directory for instance: ' . $_result . ' (' . $_return . ')' . PHP_EOL . $_command . PHP_EOL );
			Log::error( implode( PHP_EOL, $_output ) );
			$this->_killTempDirectory( $_tempPath );

			return false;
		}

		//	3. Clean out the database...
		$_pdo = \Pii::pdo();
		$_schema = \Pii::db()->getSchema();
		$_schema->refresh();

		foreach ( $_schema->getTables() as $_tableName => $_info )
		{
			if ( !$_pdo->exec( $_schema->dropTable( $_tableName ) ) )
			{
				Log::error( 'Error dropping table "' . $_tableName . '".' );
			}
		}

		//	4.	Import the snapshot...
		$_reader = new LineReader(
			array(
				'fileName'  => $_workPath,
				'separator' => null,
				'enclosure' => null,
			)
		);

		$_sql = null;

		//	Rip through the file and exec...
		foreach ( $_reader as $_line )
		{
			if ( empty( $_line ) || '--' == substr( $_line, 0, 2 ) )
			{
				continue;
			}

			$_sql .= $_line;

			if ( ';' == substr( $_line, -1, 1 ) )
			{
				$_pdo->exec( $_sql ) or Log::error( 'Error executing query: "' . $_sql . '": ' . print_r( $_pdo->errorInfo(), true ) );
				$_sql = null;
			}
		}

		Log::debug( 'MySQL dump restored: ' . $_workFile );

		//	5. Cleanup
		$this->_killTempDirectory( $_tempPath );

		//	Import complete!!!
		return true;
	}

	/**
	 * @return bool|mixed|\stdClass
	 * @throws \InvalidArgumentException
	 */
	protected function _validateInstance()
	{
		$_instance =
			Curl::post( static::API_ENDPOINT . '/locate', array('user_name' => \Pii::db()->username, 'password' => \Pii::db()->password) );

		if ( empty( $_instance ) )
		{
			throw new \InvalidArgumentException( 'This instance cannot be validated.' );
		}

		return $_instance;
	}

	/**
	 * @param string $tempFileToo
	 * @param array  $subs Options subdirectories to create as well.
	 *
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function _makeTempDirectory( &$tempFileToo = null, $subs = array() )
	{
		$_tempPath = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR );
		$tempFileToo = md5( microtime( true ) ) . microtime( true );

		if ( !is_dir( $_tempPath ) )
		{
			throw new \RuntimeException( 'Unable to create temporary working directory.' );
		}

		if ( !empty( $subs ) )
		{
			if ( !is_array( $subs ) )
			{
				$subs = array($subs);
			}

			foreach ( $subs as $_sub )
			{
				exec( 'mkdir -p ' . $_tempPath . '/' . trim( $_sub, ' ' . DIRECTORY_SEPARATOR ) );
			}
		}

		return $_tempPath;
	}

	/**
	 * @param string $path
	 */
	protected function _killTempDirectory( $path )
	{
	}
}