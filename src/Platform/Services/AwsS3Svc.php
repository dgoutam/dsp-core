<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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

use Aws\S3\S3Client;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BlobServiceException;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;

/**
 * AwsS3Svc.php
 * Remote File Storage Service giving REST access to file storage.
 */
class AwsS3Svc extends RemoteFileSvc
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var S3Client
	 */
	protected $_blobConn = null;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @throws BlobServiceException
	 */
	protected function checkConnection()
	{
		if ( empty( $this->_blobConn ) )
		{
			throw new BlobServiceException( 'No valid connection to blob file storage.' );
		}
	}

	/**
	 * @param array $config
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Platform\Exceptions\BlobServiceException
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		$_credentials = Option::get( $config, 'credentials' );
		$_accessKey = Option::get( $_credentials, 'access_key' );
		$_secretKey = Option::get( $_credentials, 'secret_key' );
		if ( !empty( $_accessKey ) && !empty( $_secretKey ) )
		{
			// old way
			$_credentials = array( 'key' => $_accessKey, 'secret' => $_secretKey );
		}

		try
		{
			$this->_blobConn = S3Client::factory( $_credentials );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Unexpected Amazon S3 Service Exception: ' . $ex->getMessage() );
		}
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		unset( $this->_blobConn );
	}

	/**
	 * List all containers, just names if noted
	 *
	 * @param bool $include_properties If true, additional properties are retrieved
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function listContainers( $include_properties = false )
	{
		$this->checkConnection();

		$_buckets = $this->_blobConn->listBuckets()->get( 'Buckets' );

		$_out = array();
		foreach ( $_buckets as $_bucket )
		{
			$_out[] = array( 'name' => rtrim( $_bucket['Name'] ) );
		}

		return $_out;
	}

	/**
	 * Gets all properties of a particular container, if options are false,
	 * otherwise include content from the container
	 *
	 * @param  string $container Container name
	 * @param  bool   $include_files
	 * @param  bool   $include_folders
	 * @param  bool   $full_tree
	 * @param  bool   $include_properties
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function getContainer( $container, $include_files = true, $include_folders = true, $full_tree = false, $include_properties = false )
	{
		$result = $this->getFolder( $container, '', $include_files, $include_folders, $full_tree, false );
		$result['name'] = $container;

		if ( $include_properties )
		{
			if ( $this->containerExists( $container ) )
			{
			}
		}

		return $result;
	}

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 */
	public function containerExists( $container = '' )
	{
		$this->checkConnection();

		return $this->_blobConn->doesBucketExist( $container );
	}

	/**
	 * @param array $properties
	 * @param array $metadata
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function createContainer( $properties, $metadata = array() )
	{
		$_name = Option::get( $properties, 'name', Option::get( $properties, 'path' ) );
		if ( empty( $_name ) )
		{
			throw new BadRequestException( 'No name found for container in create request.' );
		}
		try
		{
			$this->checkConnection();
			$this->_blobConn->createBucket(
				array(
					 'Bucket' => $_name
				)
			);

			return array( 'name' => $_name, 'path' => $_name );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to create container '$_name': " . $ex->getMessage() );
		}
	}

	/**
	 * Update a container with some properties
	 *
	 * @param string $container
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @return void
	 */
	public function updateContainerProperties( $container, $properties = array() )
	{
		$this->checkConnection();
		try
		{
			if ( $this->_blobConn->doesBucketExist( $container ) )
			{
				throw new \Exception( "No container named '$container'" );
			}

		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to update container '$container': " . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param bool   $force
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 */
	public function deleteContainer( $container = '', $force = false )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->deleteBucket(
				array(
					 'Bucket' => $container
				)
			);
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to delete container "' . $container . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * Check if a blob exists
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return boolean
	 */
	public function blobExists( $container = '', $name = '' )
	{
		try
		{
			$this->checkConnection();

			return $this->_blobConn->doesObjectExist( $container, $name );
		}
		catch ( \Exception $ex )
		{
			return false;
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $blob
	 * @param string $type
	 *
	 * @throws BlobServiceException
	 */
	public function putBlobData( $container = '', $name = '', $blob = '', $type = '' )
	{
		try
		{
			$this->checkConnection();

			$_options = array(
				'Bucket' => $container,
				'Key'    => $name,
				'Body'   => $blob
			);

			if ( !empty( $type ) )
			{
				$_options['ContentType'] = $type;
			}

			$_result = $this->_blobConn->putObject( $_options );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to create blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $localFileName
	 * @param string $type
	 *
	 * @throws BlobServiceException
	 */
	public function putBlobFromFile( $container = '', $name = '', $localFileName = '', $type = '' )
	{
		try
		{
			$this->checkConnection();

			$_options = array(
				'Bucket'     => $container,
				'Key'        => $name,
				'SourceFile' => $localFileName
			);

			if ( !empty( $type ) )
			{
				$_options['ContentType'] = $type;
			}

			$_result = $this->_blobConn->putObject( $_options );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to create blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 */
	public function copyBlob( $container = '', $name = '', $src_container = '', $src_name = '', $properties = array() )
	{
		try
		{
			$this->checkConnection();

			$_options = array(
				'Bucket'     => $container,
				'Key'        => $name,
				'CopySource' => urlencode( $src_container . '/' . $src_name )
			);

			$result = $this->_blobConn->copyObject( $_options );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to copy blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * Get blob
	 *
	 * @param  string $container     Container name
	 * @param  string $name          Blob name
	 * @param  string $localFileName Local file name to store downloaded blob
	 *
	 * @throws BlobServiceException
	 */
	public function getBlobAsFile( $container = '', $name = '', $localFileName = '' )
	{
		try
		{
			$this->checkConnection();

			$_options = array(
				'Bucket' => $container,
				'Key'    => $name,
				'SaveAs' => $localFileName
			);

			$_result = $this->_blobConn->getObject( $_options );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to retrieve blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return string
	 * @throws BlobServiceException
	 */
	public function getBlobData( $container = '', $name = '' )
	{
		try
		{
			$this->checkConnection();

			$_options = array(
				'Bucket' => $container,
				'Key'    => $name
			);

			$_result = $this->_blobConn->getObject( $_options );

			return $_result['Body'];
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to retrieve blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws BlobServiceException
	 */
	public function deleteBlob( $container = '', $name = '' )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->deleteObject(
				array(
					 'Bucket' => $container,
					 'Key'    => $name
				)
			);
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to delete blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * List blobs
	 *
	 * @param  string $container Container name
	 * @param  string $prefix    Optional. Filters the results to return only blobs whose name begins with the specified prefix.
	 * @param  string $delimiter Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 *
	 * @return array
	 * @throws BlobServiceException
	 */
	public function listBlobs( $container = '', $prefix = '', $delimiter = '' )
	{
		$_options = array(
			'Bucket' => $container,
			'Prefix' => $prefix
		);

		if ( !empty( $delimiter ) )
		{
			$_options['Delimiter'] = $delimiter;
		}

		//	No max-keys specified. Get everything.
		$_keys = array();

		do
		{
			/** @var \Aws\S3\Iterator\ListObjectsIterator $_list */
			$_list = $this->_blobConn->listObjects( $_options );

			$_objects = $_list->get( 'Contents' );

			if ( !empty( $_objects ) )
			{
				foreach ( $_objects as $_object )
				{
					if ( 0 != strcasecmp( $prefix, $_object['Key'] ) )
					{
						$_keys[] = $_object['Key'];
					}
				}
			}

			$_objects = $_list->get( 'CommonPrefixes' );

			if ( !empty( $_objects ) )
			{
				foreach ( $_objects as $_object )
				{
					if ( 0 != strcasecmp( $prefix, $_object['Prefix'] ) )
					{
						$_keys[] = $_object['Prefix'];
					}
				}
			}

			$_options['Marker'] = $_list->get( 'Marker' );
		}
		while ( $_list->get( 'IsTruncated' ) );

		$_options = array(
			'Bucket' => $container,
			'Key'    => ''
		);

		$_keys = array_unique( $_keys );
		$_out = array();
		foreach ( $_keys as $_key )
		{
			$_options['Key'] = $_key;

			/** @var \Aws\S3\Iterator\ListObjectsIterator $_meta */
			$_meta = $this->_blobConn->headObject( $_options );

			$_out[] = array(
				'name'           => $_key,
				'content_type'   => $_meta->get( 'ContentType' ),
				'content_length' => intval( $_meta->get( 'ContentLength' ) ),
				'last_modified'  => $_meta->get( 'LastModified' )
			);
		}

		return $_out;
	}

	/**
	 * List blob
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return array instance
	 * @throws BlobServiceException
	 */
	public function getBlobProperties( $container, $name )
	{
		try
		{
			$this->checkConnection();

			/** @var \Aws\S3\Iterator\ListObjectsIterator $_result */
			$_result = $this->_blobConn->headObject(
				array(
					 'Bucket' => $container,
					 'Key'    => $name
				)
			);

			$_out = array(
				'name'           => $name,
				'content_type'   => $_result->get( 'ContentType' ),
				'content_length' => intval( $_result->get( 'ContentLength' ) ),
				'last_modified'  => $_result->get( 'LastModified' )
			);

			return $_out;
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to list blob metadata: ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param array  $params
	 *
	 * @throws BlobServiceException
	 */
	public function streamBlob( $container, $name, $params = array() )
	{
		try
		{
			$this->checkConnection();

			/** @var \Aws\S3\Iterator\ListObjectsIterator $_result */
			$_result = $this->_blobConn->getObject(
				array(
					 'Bucket' => $container,
					 'Key'    => $name
				)
			);

			header( 'Last-Modified: ' . $_result->get( 'LastModified' ) );
			header( 'Content-Type: ' . $_result->get( 'ContentType' ) );
			header( 'Content-Length:' . intval( $_result->get( 'ContentLength' ) ) );

			$_disposition = ( isset( $params['disposition'] ) && !empty( $params['disposition'] ) ) ? $params['disposition'] : 'inline';

			header( 'Content-Disposition: ' . $_disposition . '; filename="' . $name . '";' );
			echo $_result->get( 'Body' );
		}
		catch ( \Exception $ex )
		{
			if ( 'Resource could not be accessed.' == $ex->getMessage() )
			{
				header( 'The specified file "' . $name . '" does not exist.' );
				header( 'Content-Type: text/html' );
			}
			else
			{
				throw new BlobServiceException( 'Failed to stream blob: ' . $ex->getMessage() );
			}
		}
	}
}
