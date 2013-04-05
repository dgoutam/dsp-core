<?php

/**
 * iDspBlob.php
 * Interface for handling blob storage resources.
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
interface iDspBlob
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
