<?php

/**
 * AwsS3Blob.php
 * Class for handling AWS S3 blob storage resources.
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
class AwsS3Blob implements iDspBlob
{
	/**
	 * @var null
	 */
	protected $_blobConn = null;

	/**
	 * @throws Exception
	 */
	protected function checkConnection()
	{
		if ( !isset( $this->_blobConn ) )
		{
			throw new Exception( "No valid connection to blob file storage." );
		}
	}

	/**
	 * @param $accessKey
	 * @param $secretKey
	 * @param $bucket
	 *
	 * @throws Exception
	 */
	public function __construct( $accessKey, $secretKey )
	{
		if ( empty( $accessKey ) )
		{
			throw new Exception( 'Amazon S3 access key can not be empty.' );
		}
		if ( empty( $secretKey ) )
		{
			throw new Exception( 'Amazon S3 secret key can not be empty.' );
		}
		try
		{
			$s3 = Aws\S3\S3Client::factory(
				array(
					'key'    => $accessKey,
					'secret' => $secretKey
				)
			);

		}
		catch ( Exception $ex )
		{
			throw new Exception( "Unexpected Amazon S3 Service Exception:\n{$ex->getMessage()}" );
		}

		$this->_blobConn = $s3;
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		if ( isset( $this->_blobConn ) )
		{
			self::disconnect( $this->_blobConn );
			unset( $this->_blobConn );
		}
	}

	/**
	 * @param $conn
	 */
	protected static function disconnect( $conn )
	{
		if ( isset( $conn ) )
		{
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function listContainers()
	{
		try
		{
			$this->checkConnection();
			$buckets = $this->_blobConn->listBuckets()->get('Buckets');

			$out = array();
			foreach ( $buckets as $bucket )
			{
				$out[] = array( 'name' => rtrim( $bucket['Name'] ) );
			}

			return $out;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function containerExists( $container = '' )
	{
		try
		{
			$this->checkConnection();

			return $this->_blobConn->doesBucketExist( $container );
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $container
	 * @param array  $metadata
	 *
	 * @throws Exception
	 */
	public function createContainer( $container = '', $metadata = array() )
	{
		try
		{
			$this->checkConnection();
			$options = array(
				'Bucket' => $container
			);
			$result = $this->_blobConn->createBucket( $options );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to create container '$container'.\n{$ex->getMessage()}" );
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
			$options = array(
				'Bucket' => $container
			);
			$result = $this->_blobConn->deleteBucket( $options );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to delete container '$container'.\n{$ex->getMessage()}" );
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
				'Key' => $name,
				'Body' => $blob
			);
			if ( !empty( $type ) )
			{
				$options['ContentType'] = $type;
			}
			$result = $this->_blobConn->putObject( $options );
		}
		catch ( Exception $ex )
		{
			throw new Exception( 'Failed to create blob.' );
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
				'Bucket' => $container,
				'Key' => $name,
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
			throw new Exception( 'Failed to create blob.' );
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
				'Bucket' => $container,
				'Key' => $name,
				'CopySource' => urlencode( $src_container . '/' . $src_name )
			);
			$result = $this->_blobConn->copyObject( $options );
		}
		catch ( Exception $ex )
		{
			throw new Exception( 'Failed to copy blob.' );
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
			throw new Exception( 'Failed to retrieve blob.' );
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
			throw new Exception( 'Failed to retrieve blob.' );
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
			$options = array(
				'Bucket' => $container,
				'Key'    => $name
			);
			$result = $this->_blobConn->deleteObject( $options );
		}
		catch ( Exception $ex )
		{
			throw new Exception( 'Failed to delete blob.' );
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
		try
		{
			$options = array(
				'Bucket' => $container,
				'Prefix' => $prefix
			);
			if ( !empty( $delimiter ) )
			{
				$options['Delimiter'] = $delimiter;
			}
			// No max-keys specified. Get everything.
			$keys = array();
			do
			{
				$list = $this->_blobConn->listObjects( $options );
				$objects = $list->get('Contents');
				if ( !empty( $objects ) )
				{
					foreach ( $objects as $object )
					{
						if ( 0 != strcasecmp($prefix, $object['Key'] ) )
						{
							$keys[] = $object['Key'];
						}
					}
				}
				$objects = $list->get('CommonPrefixes');
				if ( !empty( $objects ) )
				{
					foreach ( $objects as $object )
					{
						if ( 0 != strcasecmp($prefix, $object['Prefix'] ) )
						{
							$keys[] = $object['Prefix'];
						}
					}
				}
				$options['Marker'] = $list->get('Marker');
			}
			while ($list->get('IsTruncated'));

			$out = array();
			$options = array(
				'Bucket' => $container,
				'Key' => ''
			);
			$keys = array_unique($keys);
			foreach ($keys as $key) {
				$options['Key'] = $key;
				$meta = $this->_blobConn->headObject( $options );
				$out[] = array(
					'name' => $key,
					'contentType' => $meta->get('ContentType'),
					'size' => $meta->get('Size'),
					'lastModified' => $meta->get('LastModified')
				);
			}

			return $out;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
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
			$options = array(
				'Bucket' => $container,
				'Key'    => $name
			);
			$result = $this->_blobConn->headObject( $options );
			$file = array(
				'name'         => $name,
				'contentType'  => $result->get('ContentType'),
				'size'         => $result->get('Size'),
				'lastModified' => $result->get('LastModified')
			);

			return $file;
		}
		catch ( Exception $ex )
		{
			throw new Exception( 'Failed to list blob metadata.' );
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
			$options = array(
				'Bucket' => $container,
				'Key'    => $name
			);
			$result = $this->_blobConn->getObject( $options );
			header( 'Last-Modified: ' . $result->get('LastModified') );
			header( 'Content-type: ' . $result->get('ContentType') );
			header( 'Content-Length:' . $result->get('ContentLength') );
			$disposition = ( isset( $params['disposition'] ) && !empty( $params['disposition'] ) ) ? $params['disposition'] : 'inline';
			header( "Content-Disposition: $disposition; filename=\"$name\";" );
			echo $result->get('Body');
		}
		catch ( Exception $ex )
		{
			if ( 'Resource could not be accessed.' == $ex->getMessage() )
			{
				$status_header = "HTTP/1.1 404 The specified file '$name' does not exist.";
				header( $status_header );
				header( 'Content-type: text/html' );
			}
			else
			{
				throw new Exception( 'Failed to stream blob.' );
			}
		}
		Yii::app()->end();
	}
}
