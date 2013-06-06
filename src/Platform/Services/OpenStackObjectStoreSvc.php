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
namespace Platform\Services;

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use OpenCloud\Base\Exceptions\ContainerNotFoundError;
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;
use OpenCloud\AbstractClass\Collection;
use OpenCloud\ObjectStore\Container;
use OpenCloud\ObjectStore\DataObject;
use OpenCloud\ObjectStore\Service;
use Platform\Exceptions\BlobServiceException;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\NotFoundException;

/**
 * RemoteFileSvc.php
 * Remote File Storage Service giving REST access to file storage.
 */
class OpenStackObjectStoreSvc extends RemoteFileSvc
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var Service
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

		$_auth_url = Option::get( $_credentials, 'url', 'https://identity.api.rackspacecloud.com/v2.0' );
		$_username = Option::get( $_credentials, 'username' );
		$_password = Option::get( $_credentials, 'password' );
		$_apiKey = Option::get( $_credentials, 'api_key' );
		$_tenantName = Option::get( $_credentials, 'tenant_name' );
		if ( empty( $_username ) )
		{
			throw new \InvalidArgumentException( 'Object Store username can not be empty.' );
		}

		$_secret = array( 'username' => $_username );

		if ( empty( $_apiKey ) )
		{
			if ( empty( $_password ) )
			{
				throw new \InvalidArgumentException( 'Object Store credentials must contain an API key or a password.' );
			}

			$_secret['password'] = $_password;
		}
		else
		{
			$_secret['apiKey'] = $_apiKey;
		}
		if ( !empty( $_tenantName ) )
		{
			$_secret['tenantName'] = $_tenantName;
		}

		try
		{
			$_os = new Rackspace( $_auth_url, $_secret );
			$this->_blobConn = $_os->ObjectStore( 'cloudFiles', 'DFW' );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to launch OpenStack service: ' . $ex->getMessage() );
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

		try
		{
			/** @var Collection $_containers */
			$_containers = $this->_blobConn->ContainerList();

			$out = array();
			/** @var Container $_container */
			while( $_container = $_containers->Next() )
			{
				$out[] = array( 'name' => rtrim( $_container->name ) );
			}

			return $out;
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to list containers: ' . $ex->getMessage() );
		}
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

		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );

			return !empty( $_container );
		}
		catch ( ContainerNotFoundError $ex )
		{
			return false;
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to list containers: ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param array  $metadata
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function createContainer( $container = '', $metadata = array() )
	{
		$this->checkConnection();

		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container();
			$_params = array( 'name' => $container );
			if ( !$_container->Create( $_params ) )
			{
				throw new \Exception( '' );
			}
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to create container '$container': " . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function deleteContainer( $container = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			if ( !$_container->Delete() )
			{
				throw new \Exception( '' );
			}
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to delete container '$container': " . $ex->getMessage() );
		}
	}

	/**
	 * Check if a blob exists
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @throws \Exception
	 * @return boolean
	 */
	public function blobExists( $container = '', $name = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject( $name );

			return !empty( $_obj );
		}
		catch ( \Exception $ex )
		{
		}

		return false;
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $blob
	 * @param string $type
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function putBlobData( $container = '', $name = '', $blob = '', $type = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject();
			$_obj->SetData( $blob );
			$_obj->name = $name;
			if ( !empty( $type ) )
			{
				$_obj->content_type = $type;
			}
			if ( !$_obj->Create() )
			{
				throw new \Exception( '' );
			}
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to create blob '$name': " . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $localFileName
	 * @param string $type
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function putBlobFromFile( $container = '', $name = '', $localFileName = '', $type = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject();
			$_params = array( 'name' => $name );
			if ( !empty( $type ) )
			{
				$_params['content_type'] = $type;
			}

			if ( !$_obj->Create( $_params, $localFileName ) )
			{
				throw new \Exception( '' );
			}
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to create blob '$name': " . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function copyBlob( $container = '', $name = '', $src_container = '', $src_name = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_src_container */
			$_src_container = $this->_blobConn->Container( $src_container );
			if ( empty( $_src_container) )
			{
				throw new \Exception( "No container named '$src_container'" );
			}
			/** @var Container $_dest_container */
			$_dest_container = $this->_blobConn->Container( $container );
			if ( empty( $_dest_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_source = $_src_container->DataObject( $src_name );
			$_destination = $_dest_container->DataObject();
			$_destination->name = $name;

			$_source->Copy( $_destination );
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to copy blob '$name': " . $ex->getMessage() );
		}
	}

	/**
	 * Get blob
	 *
	 * @param  string $container     Container name
	 * @param  string $name          Blob name
	 * @param  string $localFileName Local file name to store downloaded blob
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function getBlobAsFile( $container = '', $name = '', $localFileName = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject( $name );

			if ( !$_obj->SaveToFilename( $localFileName ) )
			{
				throw new \Exception( '' );
			}
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to retrieve blob '$name': " . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 * @return string
	 */
	public function getBlobData( $container = '', $name = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject( $name );

			return $_obj->SaveToString();
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to retrieve blob '$name': " . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function deleteBlob( $container = '', $name = '' )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject( $name );

			$_obj->Delete();
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( "Failed to delete blob '$name': " . $ex->getMessage() );
		}
	}

	/**
	 * List blobs
	 *
	 * @param  string $container Container name
	 * @param  string $prefix    Optional. Filters the results to return only blobs whose name begins with the specified prefix.
	 * @param  string $delimiter Optional. Delimiter, i.e. '/', for specifying folder hierarchy
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function listBlobs( $container = '', $prefix = '', $delimiter = '' )
	{
		$this->checkConnection();

		$_options = array();
		if ( !empty( $prefix ) )
		{
			$_options['prefix'] = $prefix;
		}
		if ( !empty( $delimiter ) )
		{
			$_options['delimiter'] = $delimiter;
		}

		/** @var Container $_container */
		$_container = $this->_blobConn->Container( $container );
		if ( empty( $_container) )
		{
			throw new \Exception( "No container named '$container'" );
		}

		/** @var Collection $_list */
		$_list = $_container->ObjectList( $_options );
		$_out = array();
		/** @var DataObject $_obj */
		while ( $_obj = $_list->Next() )
		{
			if ( !empty( $_obj->name ) )
			{
				$_out[] = array(
					'name'         => $_obj->name,
					'contentType'  => $_obj->content_type,
					'size'         => $_obj->bytes,
					'lastModified' => $_obj->last_modified
				);
			}
			elseif ( !empty( $_obj->subdir ) ) // sub directories formatted differently
			{
				$_out[] = array(
					'name'         => $_obj->subdir
				);
			}
		}

		return $_out;
	}

	/**
	 * List blob
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 * @return array
	 */
	public function listBlob( $container, $name )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject( $name );

			$file = array(
				'name'         => $_obj->name,
				'contentType'  => $_obj->content_type,
				'size'         => $_obj->bytes,
				'lastModified' => $_obj->last_modified
			);

			return $file;
		}
		catch ( \Exception $ex )
		{
			throw new BlobServiceException( 'Failed to list metadata: ' . $ex->getMessage() );
		}
	}

	/**
	 * @param string $container
	 * @param string $name
	 * @param array  $params
	 *
	 * @throws \Platform\Exceptions\BlobServiceException
	 * @throws \Exception
	 */
	public function streamBlob( $container, $name, $params = array() )
	{
		$this->checkConnection();
		try
		{
			/** @var Container $_container */
			$_container = $this->_blobConn->Container( $container );
			if ( empty( $_container) )
			{
				throw new \Exception( "No container named '$container'" );
			}

			$_obj = $_container->DataObject( $name );

			header( 'Last-Modified: ' . $_obj->last_modified );
			header( 'Content-type: ' . $_obj->content_type );
			header( 'Content-Length:' . $_obj->content_length );

			$disposition = ( isset( $params['disposition'] ) && !empty( $params['disposition'] ) ) ? $params['disposition'] : 'inline';

			header( 'Content-Disposition: ' . $disposition . '; filename="' . $name . '";' );
			echo $_obj->SaveToString();
		}
		catch ( \Exception $ex )
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
	}
}
