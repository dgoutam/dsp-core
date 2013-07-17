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
namespace Platform\Interfaces;
/**
 * BlobServiceLike.php
 * Interface for handling blob storage resources.
 */
interface BlobServiceLike
{
	/**
	 * @return array
	 * @throws \Exception
	 */
	public function listContainers();

	/**
	 * Check if a container exists
	 *
	 * @param  string $container Container name
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public function containerExists( $container = '' );

	/**
	 * @param string $container
	 * @param array  $metadata
	 *
	 * @throws \Exception
	 */
	public function createContainer( $container = '', $metadata = array() );

	/**
	 * @param string $container
	 *
	 * @throws \Exception
	 */
	public function deleteContainer( $container = '' );

	/**
	 * Check if a blob exists
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public function blobExists( $container = '', $name = '' );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $blob
	 * @param string $type
	 *
	 * @throws \Exception
	 */
	public function putBlobData( $container = '', $name = '', $blob = '', $type = '' );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $localFileName
	 * @param string $type
	 *
	 * @throws \Exception
	 */
	public function putBlobFromFile( $container = '', $name = '', $localFileName = '', $type = '' );

	/**
	 * @param string $container
	 * @param string $name
	 * @param string $src_container
	 * @param string $src_name
	 *
	 * @throws \Exception
	 */
	public function copyBlob( $container = '', $name = '', $src_container = '', $src_name = '' );

	/**
	 * Get blob
	 *
	 * @param  string $container     Container name
	 * @param  string $name          Blob name
	 * @param  string $localFileName Local file name to store downloaded blob
	 *
	 * @throws \Exception
	 */
	public function getBlobAsFile( $container = '', $name = '', $localFileName = '' );

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function getBlobData( $container = '', $name = '' );

	/**
	 * @param string $container
	 * @param string $name
	 *
	 * @throws \Exception
	 */
	public function deleteBlob( $container = '', $name = '' );

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
	public function listBlobs( $container = '', $prefix = '', $delimiter = '' );

	/**
	 * List blob
	 *
	 * @param  string $container Container name
	 * @param  string $name      Blob name
	 *
	 * @return array instance
	 * @throws \Exception
	 */
	public function listBlob( $container, $name );

	/**
	 * @param       $container
	 * @param       $blobName
	 * @param array $params
	 *
	 * @throws \Exception
	 */
	public function streamBlob( $container, $blobName, $params = array() );
}
