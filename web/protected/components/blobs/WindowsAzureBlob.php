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
use Platform\Exceptions\BlobServiceException;
use WindowsAzure\Blob\Models\CreateBlobOptions;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\ListBlobsOptions;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Blob\BlobRestProxy;

/**
 * WindowsAzureBlob.php
 * Class for handling Windows Azure blob storage resources.
 */
class WindowsAzureBlob implements iDspBlob
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var BlobRestProxy|null
	 */
	protected $_blobConn = null;

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @throws Exception
	 */
	protected function checkConnection()
	{
		if ( !isset( $this->_blobConn ) )
		{
			throw new BlobServiceException( 'No valid connection to blob file storage.' );
		}
	}

	/**
	 * Connects to a Azure Blob Storage
	 *
	 * @param boolean $useDev Utilize the local emulator storage
	 * @param string  $name   Storage account name
	 * @param string  $key    Storage account primary key
	 *
	 * @throws Exception
	 */
	public function __construct( $useDev = true, $name = '', $key = '' )
	{
		if ( empty( $name ) )
		{
			throw new InvalidArgumentException( 'WindowsAzure storage name can not be empty.' );
		}

		if ( empty( $key ) )
		{
			throw new InvalidArgumentException( 'WindowsAzure storage key can not be empty.' );
		}

		if ( !$useDev )
		{
			$connectionString = "DefaultEndpointsProtocol=https;AccountName=$name;AccountKey=$key";
		}
		else
		{
			$connectionString = "UseDevelopmentStorage=true";
		}

		try
		{
			$this->_blobConn = ServicesBuilder::getInstance()->createBlobService( $connectionString );
		}
		catch ( ServiceException $ex )
		{
			throw new BlobServiceException( 'Unexpected Windows Azure Blob Service Exception: ' . $ex->getMessage() );
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
	 * @param $name
	 *
	 * @return mixed
	 */
	private function fixBlobName( $name )
	{
		// doesn't like spaces in the name, anything else?
		return str_replace( ' ', '%20', $name );
	}

	/**
	 * @return mixed
	 */
	public function listContainers()
	{
		$this->checkConnection();

		/** @var \WindowsAzure\Blob\Models\ListContainersResult $result */
		$result = $this->_blobConn->listContainers();

		return $result->getContainers();
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
			$this->_blobConn->getContainerProperties( $container );

			return true;
		}
		catch ( Exception $ex )
		{
			if ( false === stripos( $ex->getMessage(), 'does not exist' ) )
			{
				throw $ex;
			}
		}

		return false;
	}

	/**
	 * @param string $container
	 * @param array  $metadata
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function createContainer( $container = '', $metadata = array() )
	{
		$this->checkConnection();

		$options = new CreateContainerOptions();
		$options->setMetadata( $metadata );
//		$options->setPublicAccess('blob');

		$this->_blobConn->createContainer( $container, $options );
	}

	/**
	 * @param string $container
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function deleteContainer( $container = '' )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->deleteContainer( $container );
		}
		catch ( Exception $ex )
		{
			if ( false === stripos( $ex->getMessage(), 'does not exist' ) )
			{
				throw $ex;
			}
		}
	}

	/**
	 * Check if a blob exists
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function blobExists( $container = '', $name = '' )
	{
		try
		{
			$this->checkConnection();
			$name = $this->fixBlobName( $name );
			$this->_blobConn->getBlobProperties( $container, $name );

			return true;
		}
		catch ( Exception $ex )
		{
			if ( false === stripos( $ex->getMessage(), 'does not exist' ) )
			{
				throw $ex;
			}
		}

		return false;
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $blob
	 * @param string $type
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function putBlobData( $container = '', $name = '', $blob = '', $type = '' )
	{
		$this->checkConnection();

		$options = new CreateBlobOptions();

		if ( !empty( $type ) )
		{
			$options->setContentType( $type );
		}

		$this->_blobConn->createBlockBlob( $container, $this->fixBlobName( $name ), $blob, $options );
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $localFileName
	 * @param string $type
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function putBlobFromFile( $container = '', $name = '', $localFileName = '', $type = '' )
	{
		$this->checkConnection();
		$options = new CreateBlobOptions();

		if ( !empty( $type ) )
		{
			$options->setContentType( $type );
		}

		$this->_blobConn->createBlockBlob( $container, $this->fixBlobName( $name ), $localFileName, $options );
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function copyBlob( $container = '', $name = '', $src_container = '', $src_name = '' )
	{
		$this->checkConnection();
		$this->_blobConn->copyBlob( $container, $this->fixBlobName( $name ), $src_container, $this->fixBlobName( $src_name ) );
	}

	/**
	 * Get blob
	 *
	 * @param  string $container     Container name
	 * @param  string $name          Blob name
	 * @param  string $localFileName Local file name to store downloaded blob
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function getBlobAsFile( $container = '', $name = '', $localFileName = '' )
	{
		$this->checkConnection();
		$results = $this->_blobConn->getBlob( $container, $this->fixBlobName( $name ) );
		file_put_contents( $localFileName, stream_get_contents( $results->getContentStream() ) );
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
		$this->checkConnection();
		$results = $this->_blobConn->getBlob( $container, $this->fixBlobName( $name ) );

		return stream_get_contents( $results->getContentStream() );
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return Exception|void
	 * @throws Exception
	 */
	public function deleteBlob( $container = '', $name = '' )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->deleteBlob( $container, $this->fixBlobName( $name ) );
		}
		catch ( Exception $ex )
		{
			if ( false === stripos( $ex->getMessage(), 'does not exist' ) )
			{
				throw $ex;
			}
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
		$this->checkConnection();
		$options = new ListBlobsOptions();

		if ( !empty( $delimiter ) )
		{
			$options->setDelimiter( $delimiter );
		}

		if ( !empty( $prefix ) )
		{
			$options->setPrefix( $prefix );
		}

		$results = $this->_blobConn->listBlobs( $container, $options );
		$blobs = $results->getBlobs();
		$prefixes = $results->getBlobPrefixes();
		$out = array();

		/** @var \WindowsAzure\Blob\Models\Blob $blob */
		foreach ( $blobs as $blob )
		{
			$props = $blob->getProperties();
			$out[] = array(
				'name'            => $blob->getName(),
				'lastModified'    => gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() ),
				'size'            => $props->getContentLength(),
				'contentType'     => $props->getContentType(),
				'contentEncoding' => $props->getContentEncoding(),
				'contentLanguage' => $props->getContentLanguage()
			);
		}

		foreach ( $prefixes as $blob )
		{
			$out[] = array(
				'name' => $blob->getName()
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
		$this->checkConnection();
		$name = $this->fixBlobName( $name );
		$result = $this->_blobConn->getBlobProperties( $container, $name );
		$props = $result->getProperties();
		$file = array(
			'name'         => $name,
			'lastModified' => gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() ),
			'size'         => $props->getContentLength(),
			'contentType'  => $props->getContentType()
		);

		return $file;
	}

	/**
	 * @param string $container
	 * @param string $blobName
	 * @param array  $params
	 *
	 * @throws Exception
	 * @return Exception|void
	 */
	public function streamBlob( $container, $blobName, $params = array() )
	{
		try
		{
			$this->checkConnection();
			$blob = $this->_blobConn->getBlob( $container, $blobName );
			$props = $blob->getProperties();

			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() ) );
			header( 'Content-type: ' . $props->getContentType() );
			header( 'Content-Transfer-Encoding: ' . $props->getContentEncoding() );
			header( 'Content-Length:' . $props->getContentLength() );

			$disposition = ( isset( $params['disposition'] ) && !empty( $params['disposition'] ) ) ? $params['disposition'] : 'inline';

			header( "Content-Disposition: $disposition; filename=\"$blobName\";" );
			fpassthru( $blob->getContentStream() );

//            $this->_blobConn->registerStreamWrapper();
//            $blobUrl = 'azure://' . $container . '/' . $blobName;
//            readfile($blobUrl);
		}
		catch ( Exception $ex )
		{
			if ( 'Resource could not be accessed.' == $ex->getMessage() )
			{
				$status_header = "HTTP/1.1 404 The specified file '$blobName' does not exist.";
				header( $status_header );
				header( 'Content-type: text/html' );
			}
			else
			{
				throw $ex;
			}
		}

		Pii::end();
	}
}
