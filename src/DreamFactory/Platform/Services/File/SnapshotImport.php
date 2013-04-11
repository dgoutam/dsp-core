<?php
namespace DreamFactory\Platform\Services\File;

use DreamFactory\Platform\Exceptions\FileSystemException;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;

/**
 * SnapshotImport.php
 * Imports a snapshot file
 */
class SnapshotImport extends Curl
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DOWNLOAD_URL_BASE = 'http://cerberus.fabric.dreamfactory.com/api/download';
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
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
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

			return false;
		}

		return array( $_tempPath, $_workPath, $_workFile, $_snapshot );
	}

	/**
	 * Given an instance and a snapshot ID, replace the data with that of the snapshot.
	 *
	 * @return bool
	 */
	public function restore()
	{
		list( $_tempPath, $_workPath, $_workFile, $_snapshot ) = $this->_openSnapshot();

		//	3. Install snapshot storage files
		$_command = 'cd ' . \Pii::getParam( 'storage_path' ) . '; rm -rf ./*; /bin/tar zxf ' . $_tempPath . '/' . $_snapshot->storage->tarball . ' ./';
		$_result = exec( $_command, $_output, $_return );

		if ( 0 != $_return )
		{
			Log::error(
				'Error importing storage directory of dsp "' . $instanceId . '": ' . $_result . ' (' . $_return . ')' . PHP_EOL . $_command . PHP_EOL
			);
			Log::error( implode( PHP_EOL, $_output ) );
			$this->_killTempDirectory( $_tempPath );

			return false;
		}

		//	4. Drop old, Create new database for snapshot mysql data
		$_db = \Pii::db();
		$_dbName = '';

		$_sql
			= <<<SQL
DROP DATABASE {$_dbName};
CREATE DATABASE {$_dbName};
SQL;

		if ( Provisioners::DreamFactory == $this->_instance->guest_location_nbr )
		{
			$_command
				= 'sudo -u dfadmin /opt/dreamfactory/fabric/cerberus/config/scripts/restore_snapshot_mysql.sh ' . $this->_instance->db_name_text . ' ' .
				  $_workPath;
		}
		else
		{
//			$_command
//				= '/usr/bin/ssh ' . static::DEFAULT_SSH_OPTIONS . ' dfadmin@' . $this->_instance->instance_name_text . DSP::DEFAULT_DSP_SUB_DOMAIN .
//				' \'mysqldump --delayed-insert -e -u ' . $this->_instance->db_user_text . ' -p' . $this->_instance->db_password_text . ' ' .
//				$this->_instance->db_name_text . '\' | gzip -c >' . $_workPath;
		}

		$_result = exec( $_command, $_output, $_return );

		if ( 0 != $_return )
		{
			Log::error( 'Error restoring mysql dump of dsp "' . $instanceId . '": ' . $_result . ' (' . $_return . ')' . PHP_EOL . $_command . PHP_EOL );
			Log::error( implode( PHP_EOL, $_output ) );
			$this->_killTempDirectory( $_tempPath );

			//@TODO need to restore snapshot taken at the beginning cuz we sucked at this...

			return false;
		}

		Log::debug( 'MySQL dump restored: ' . $_workFile );
//
//		//	5. Import mysql data
//
//		$_command
//			= 'cd ' . $_tempPath . '; ' .
//			'gunzip ' . $_snapshot->mysql->tarball . '; ' .
//			'mysql -u ' . $_db->username . ' -p' . $_db->password . ' -h ' . DSP::DEFAULT_DSP_SERVER . ' --database=' .
//			$this->_instance->db_name_text . ' < mysql.' . $snapshot . '.sql';
//
//		$_result = exec( $_command, $_output, $_return );
//
//		if ( 0 != $_return )
//		{
//			Log::error( 'Error importing mysql dump of dsp "' . $instanceId . '": ' . $_result . ' (' . $_return . ')' . PHP_EOL . $_command . PHP_EOL );
//			Log::error( implode( PHP_EOL, $_output ) );
//
//			//	Roll everything back...
//			$_service->deprovision(
//				array(
//					 'name'        => $this->_instance->instance_name_text,
//					 'storage_key' => $this->_instance->storage_id_text
//				),
//				true,
//				$this->_instance
//			);
//
//			$this->_killTempDirectory( $_tempPath );
//
//			return false;
//		}
//
//		//	6.	Update snapshot with import info
//		$_snapshot->imports[] = array(
//			'timestamp' => date( 'c' ),
//		);
//
//		$_import->addFromString( 'snapshot.json', json_encode( $_snapshot ) );

		//	7. Cleanup
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
		$_instance = static::post( static::API_ENDPOINT . '/locate', array( 'user_name' => \Pii::db()->username, 'password' => \Pii::db()->password ) );

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
				$subs = array( $subs );
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