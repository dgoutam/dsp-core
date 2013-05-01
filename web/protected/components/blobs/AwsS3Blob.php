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
use Aws\S3\S3Client;
use Kisma\Core\Enums\HttpResponse;
use Platform\Exceptions\BlobServiceException;

/**
 * AwsS3Blob.php
 * Class for handling AWS S3 blob storage resources.
 */
class AwsS3Blob implements iDspBlob
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var iDspBlob
	 */
	protected $_blobConn = null;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @throws Platform\Exceptions\BlobServiceException
	 */
	protected function checkConnection()
	{
		if ( empty( $this->_blobConn ) )
		{
			throw new BlobServiceException( 'No valid connection to blob file storage.' );
		}
	}

	/**
	 * @param $accessKey
	 * @param $secretKey
	 *
	 * @throws Kisma\Core\Exceptions\ServiceException
	 * @throws InvalidArgumentException
	 */
	public function __construct( $accessKey, $secretKey )
	{
		if ( empty( $accessKey ) )
		{
			throw new InvalidArgumentException( 'Amazon S3 access key can not be empty.' );
		}

		if ( empty( $secretKey ) )
		{
			throw new InvalidArgumentException( 'Amazon S3 secret key can not be empty.' );
		}

		try
		{
			$s3 = S3Client::factory(
				array(
					 'key'    => $accessKey,
					 'secret' => $secretKey
				)
			);

			$this->_blobConn = $s3;
		}
		catch ( Exception $ex )
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
	 * @return array
	 */
	public function listContainers()
	{
		$this->checkConnection();

		$buckets = $this->_blobConn->listBuckets()->get( 'Buckets' );

		$out = array();
		foreach ( $buckets as $bucket )
		{
			$out[] = array( 'name' => rtrim( $bucket['Name'] ) );
		}

		return $out;
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
	 * @param string $container
	 * @param array  $metadata
	 *
	 * @throws Platform\Exceptions\BlobServiceException
	 */
	public function createContainer( $container = '', $metadata = array() )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->createBucket(
				array(
					 'Bucket' => $container
				)
			);
		}
		catch ( Exception $ex )
		{
			throw new BlobServiceException( 'Failed to create container "' . $container . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 *
	 * @throws Exception
	 */
	public function deleteContainer( $container = '' )
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
		catch ( Exception $ex )
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
		catch ( Exception $ex )
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
	 * @throws Exception
	 */
	public function putBlobData( $container = '', $name = '', $blob = '', $type = '' )
	{
		try
		{
			$this->checkConnection();

			$options = array(
				'Bucket' => $container,
				'Key'    => $name,
				'Body'   => $blob
			);

			if ( !empty( $type ) )
			{
				$options['ContentType'] = $type;
			}

			$result = $this->_blobConn->putObject( $options );
		}
		catch ( Exception $ex )
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
	 * @throws Exception
	 */
	public function putBlobFromFile( $container = '', $name = '', $localFileName = '', $type = '' )
	{
		try
		{
			$this->checkConnection();

			$options = array(
				'Bucket'     => $container,
				'Key'        => $name,
				'SourceFile' => $localFileName
			);

			if ( !empty( $type ) )
			{
				$options['ContentType'] = $type;
			}

			$result = $this->_blobConn->putObject( $options );
		}
		catch ( Exception $ex )
		{
			throw new BlobServiceException( 'Failed to create blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 *
	 * @throws Exception
	 */
	public function copyBlob( $container = '', $name = '', $src_container = '', $src_name = '' )
	{
		try
		{
			$this->checkConnection();

			$options = array(
				'Bucket'     => $container,
				'Key'        => $name,
				'CopySource' => urlencode( $src_container . '/' . $src_name )
			);

			$result = $this->_blobConn->copyObject( $options );
		}
		catch ( Exception $ex )
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
	 * @throws Exception
	 */
	public function getBlobAsFile( $container = '', $name = '', $localFileName = '' )
	{
		try
		{
			$this->checkConnection();

			$options = array(
				'Bucket' => $container,
				'Key'    => $name,
				'SaveAs' => $localFileName
			);

			$result = $this->_blobConn->getObject( $options );
		}
		catch ( Exception $ex )
		{
			throw new BlobServiceException( 'Failed to retrieve blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getBlobData( $container = '', $name = '' )
	{
		try
		{
			$this->checkConnection();

			$options = array(
				'Bucket' => $container,
				'Key'    => $name
			);

			$result = $this->_blobConn->getObject( $options );

			return $result['Body'];
		}
		catch ( Exception $ex )
		{
			throw new BlobServiceException( 'Failed to retrieve blob "' . $name . '": ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws Exception
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
		catch ( Exception $ex )
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
	 * @throws Exception
	 */
	public function listBlobs( $container = '', $prefix = '', $delimiter = '' )
	{
		$options = array(
			'Bucket' => $container,
			'Prefix' => $prefix
		);

		if ( !empty( $delimiter ) )
		{
			$options['Delimiter'] = $delimiter;
		}

		//	No max-keys specified. Get everything.
		$keys = array();

		do
		{
			/** @var \Aws\S3\Iterator\ListObjectsIterator $list */
			$list = $this->_blobConn->listObjects( $options );

			$objects = $list->get( 'Contents' );

			if ( !empty( $objects ) )
			{
				foreach ( $objects as $object )
				{
					if ( 0 != strcasecmp( $prefix, $object['Key'] ) )
					{
						$keys[] = $object['Key'];
					}
				}
			}

			$objects = $list->get( 'CommonPrefixes' );

			if ( !empty( $objects ) )
			{
				foreach ( $objects as $object )
				{
					if ( 0 != strcasecmp( $prefix, $object['Prefix'] ) )
					{
						$keys[] = $object['Prefix'];
					}
				}
			}

			$options['Marker'] = $list->get( 'Marker' );
		}
		while ( $list->get( 'IsTruncated' ) );

		$out = array();
		$options = array(
			'Bucket' => $container,
			'Key'    => ''
		);

		$keys = array_unique( $keys );

		foreach ( $keys as $key )
		{
			$options['Key'] = $key;

			/** @var \Aws\S3\Iterator\ListObjectsIterator $meta */
			$meta = $this->_blobConn->headObject( $options );

			$out[] = array(
				'name'         => $key,
				'contentType'  => $meta->get( 'ContentType' ),
				'size'         => $meta->get( 'Size' ),
				'lastModified' => $meta->get( 'LastModified' )
			);
		}

		return $out;
	}

	/**
	 * List blob
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return array instance
	 * @throws Exception
	 */
	public function listBlob( $container, $name )
	{
		try
		{
			$this->checkConnection();

			/** @var \Aws\S3\Iterator\ListObjectsIterator $result */
			$result = $this->_blobConn->headObject(
				array(
					 'Bucket' => $container,
					 'Key'    => $name
				)
			);

			$file = array(
				'name'         => $name,
				'contentType'  => $result->get( 'ContentType' ),
				'size'         => $result->get( 'Size' ),
				'lastModified' => $result->get( 'LastModified' )
			);

			return $file;
		}
		catch ( Exception $ex )
		{
			throw new BlobServiceException( 'Failed to list blob metadata: ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param array  $params
	 *
	 * @throws Exception
	 */
	public function streamBlob( $container, $name, $params = array() )
	{
		try
		{
			$this->checkConnection();

			/** @var \Aws\S3\Iterator\ListObjectsIterator $result */
			$result = $this->_blobConn->getObject(
				array(
					 'Bucket' => $container,
					 'Key'    => $name
				)
			);

			header( 'Last-Modified: ' . $result->get( 'LastModified' ) );
			header( 'Content-type: ' . $result->get( 'ContentType' ) );
			header( 'Content-Length:' . $result->get( 'ContentLength' ) );

			$disposition = ( isset( $params['disposition'] ) && !empty( $params['disposition'] ) ) ? $params['disposition'] : 'inline';

			header( 'Content-Disposition: ' . $disposition . '; filename="' . $name . '";' );
			echo $result->get( 'Body' );
		}
		catch ( Exception $ex )
		{
			if ( 'Resource could not be accessed.' == $ex->getMessage() )
			{
				header( 'The specified file "' . $name . '" does not exist.' );
				header( 'Content-type: text/html' );
			}
			else
			{
				throw new BlobServiceException( 'Failed to stream blob: ' . $ex->getMessage() );
			}
		}

		Pii::end();
	}

}
