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

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BlobServiceException;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;
use Platform\Utility\DataFormat;
use WindowsAzure\Blob\BlobRestProxy;
use WindowsAzure\Blob\Models\Container;
use WindowsAzure\Blob\Models\CreateBlobOptions;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\ListBlobsOptions;
use WindowsAzure\Blob\Models\ListBlobsResult;
use WindowsAzure\Blob\Models\GetBlobResult;
use WindowsAzure\Blob\Models\GetBlobPropertiesResult;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;

/**
 * RemoteFileSvc.php
 * Remote File Storage Service giving REST access to file storage.
 */
class WindowsAzureBlobSvc extends RemoteFileSvc
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
	 * @throws BlobServiceException
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
	 * @param array $config Authentication configuration
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		$_credentials = Option::get( $config, 'credentials' );
		$_connectionString = Option::get( $_credentials, 'connection_string' );
		if ( empty( $_connectionString ) )
		{
			$_localDev = DataFormat::boolval( Option::get( $_credentials, 'local_dev', false ) );
			if ( !$_localDev )
			{
				$_accountName = Option::get( $_credentials, 'account_name' );
				$_accountKey = Option::get( $_credentials, 'account_key' );
				$_protocol = Option::get( $_credentials, 'protocol', 'https' );
				if ( empty( $_accountName ) )
				{
					throw new \InvalidArgumentException( 'WindowsAzure storage account name can not be empty.' );
				}

				if ( empty( $_accountKey ) )
				{
					throw new \InvalidArgumentException( 'WindowsAzure storage account key can not be empty.' );
				}
				$_connectionString = "DefaultEndpointsProtocol=$_protocol;AccountName=$_accountName;AccountKey=$_accountKey";
			}
			else
			{
				$_connectionString = "UseDevelopmentStorage=true";
			}
		}

		try
		{
			$this->_blobConn = ServicesBuilder::getInstance()->createBlobService( $_connectionString );
		}
		catch ( ServiceException $ex )
		{
			throw new \Exception( 'Unexpected Windows Azure Blob Service Exception: ' . $ex->getMessage() );
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

		/** @var \WindowsAzure\Blob\Models\ListContainersResult $result */
		$result = $this->_blobConn->listContainers();

		/** @var \WindowsAzure\Blob\Models\Container[] $_items */
		$_items = $result->getContainers();
		$result = array();
		foreach ( $_items as $_item )
		{
			$out = array( 'name' => $_item->getName() );
			if ( $include_properties )
			{
				$props = $_item->getProperties();
				$out['last_modified'] = gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() );
			}
			$result[] = $out;
		}

		return $result;
	}

	/**
	 * Gets all properties of a particular container, if options are false,
	 * otherwise include content from the container
	 *
	 * @param string $container Container name
	 * @param bool   $include_files
	 * @param bool   $include_folders
	 * @param bool   $full_tree
	 * @param bool   $include_properties
	 *
	 * @return array
	 */
	public function getContainer( $container, $include_files = true, $include_folders = true, $full_tree = false, $include_properties = false )
	{
		$this->checkConnection();

		$result = $this->getFolder( $container, '', $include_files, $include_folders, $full_tree, false );
		$result['name'] = $container;
		if ( $include_properties )
		{
			/** @var \WindowsAzure\Blob\Models\GetContainerPropertiesResult $props  */
			$props = $this->_blobConn->getContainerProperties( $container );
			$result['last_modified'] = gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() );
		}

		return $result;
	}

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public function containerExists( $container )
	{
		$this->checkConnection();
		try
		{
			$this->_blobConn->getContainerProperties( $container );

			return true;
		}
		catch ( \Exception $ex )
		{
			if ( false === stripos( $ex->getMessage(), 'does not exist' ) )
			{
				throw $ex;
			}
		}

		return false;
	}

	/**
	 * @param array $properties
	 * @param array $metadata
	 *
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public function createContainer( $properties, $metadata = array() )
	{
		$this->checkConnection();

		$_name = Option::get( $properties, 'name', Option::get( $properties, 'path' ) );
		if ( empty( $_name ) )
		{
			throw new BadRequestException( 'No name found for container in create request.' );
		}
		$options = new CreateContainerOptions();
		$options->setMetadata( $metadata );
//		$options->setPublicAccess('blob');

		$this->_blobConn->createContainer( $_name, $options );

		return array( 'name' => $_name, 'path' => $_name );
	}

	/**
	 * Update a container with some properties
	 *
	 * @param string $container
	 * @param array  $properties
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @return void
	 */
	public function updateContainerProperties( $container, $properties = array() )
	{
		$this->checkConnection();

		$options = new CreateContainerOptions();
		$options->setMetadata( $properties );
//		$options->setPublicAccess('blob');

		$this->_blobConn->setContainerMetadata( $container, $options );
	}

	/**
	 * @param string $container
	 * @param bool   $force
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function deleteContainer( $container, $force = false )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->deleteContainer( $container );
		}
		catch ( \Exception $ex )
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
	 * @throws \Exception
	 */
	public function blobExists( $container, $name )
	{
		try
		{
			$this->checkConnection();
			$name = $this->fixBlobName( $name );
			$this->_blobConn->getBlobProperties( $container, $name );

			return true;
		}
		catch ( \Exception $ex )
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
	 * @return void
	 */
	public function putBlobData( $container, $name, $blob = '', $type = '' )
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
	 * @return void
	 */
	public function putBlobFromFile( $container, $name, $localFileName = '', $type = '' )
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
	 * @return void
	 */
	public function copyBlob( $container, $name, $src_container, $src_name, $properties = array() )
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
	 * @return void
	 */
	public function getBlobAsFile( $container, $name, $localFileName = '' )
	{
		$this->checkConnection();
		/** @var GetBlobResult $results  */
		$results = $this->_blobConn->getBlob( $container, $this->fixBlobName( $name ) );
		file_put_contents( $localFileName, stream_get_contents( $results->getContentStream() ) );
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return mixed|string
	 * @return string
	 */
	public function getBlobData( $container, $name )
	{
		$this->checkConnection();
		/** @var GetBlobResult $results  */
		$results = $this->_blobConn->getBlob( $container, $this->fixBlobName( $name ) );

		return stream_get_contents( $results->getContentStream() );
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function deleteBlob( $container, $name )
	{
		try
		{
			$this->checkConnection();
			$this->_blobConn->deleteBlob( $container, $this->fixBlobName( $name ) );
		}
		catch ( \Exception $ex )
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
	 * @throws \Exception
	 */
	public function listBlobs( $container, $prefix = '', $delimiter = '' )
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

		/** @var ListBlobsResult $results  */
		$results = $this->_blobConn->listBlobs( $container, $options );
		$blobs = $results->getBlobs();
		$prefixes = $results->getBlobPrefixes();
		$out = array();

		/** @var \WindowsAzure\Blob\Models\Blob $blob */
		foreach ( $blobs as $blob )
		{
			$name = $blob->getName();
			if ( 0 == strcmp( $prefix, $name ) )
			{
				continue;
			}
			$props = $blob->getProperties();
			$out[] = array(
				'name'             => $name,
				'last_modified'    => gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() ),
				'content_length'   => $props->getContentLength(),
				'content_type'     => $props->getContentType(),
				'content_encoding' => $props->getContentEncoding(),
				'content_language' => $props->getContentLanguage()
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
	 * @throws \Exception
	 */
	public function getBlobProperties( $container, $name )
	{
		$this->checkConnection();
		$name = $this->fixBlobName( $name );
		/** @var GetBlobPropertiesResult $result */
		$result = $this->_blobConn->getBlobProperties( $container, $name );
		$props = $result->getProperties();
		$file = array(
			'name'           => $name,
			'last_modified'  => gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() ),
			'content_length' => $props->getContentLength(),
			'content_type'   => $props->getContentType()
		);

		return $file;
	}

	/**
	 * @param string $container
	 * @param string $blobName
	 * @param array  $params
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function streamBlob( $container, $blobName, $params = array() )
	{
		try
		{
			$this->checkConnection();
			/** @var GetBlobResult $blob */
			$blob = $this->_blobConn->getBlob( $container, $blobName );
			$props = $blob->getProperties();

			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp() ) );
			header( 'Content-Type: ' . $props->getContentType() );
			header( 'Content-Transfer-Encoding: ' . $props->getContentEncoding() );
			header( 'Content-Length:' . $props->getContentLength() );

			$disposition = ( isset( $params['disposition'] ) && !empty( $params['disposition'] ) ) ? $params['disposition'] : 'inline';

			header( "Content-Disposition: $disposition; filename=\"$blobName\";" );
			fpassthru( $blob->getContentStream() );

//            $this->_blobConn->registerStreamWrapper();
//            $blobUrl = 'azure://' . $container . '/' . $blobName;
//            readfile($blobUrl);
		}
		catch ( \Exception $ex )
		{
			if ( 'Resource could not be accessed.' == $ex->getMessage() )
			{
				$status_header = "HTTP/1.1 404 The specified file '$blobName' does not exist.";
				header( $status_header );
				header( 'Content-Type: text/html' );
			}
			else
			{
				throw $ex;
			}
		}
	}
}
