<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage BlobSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

/**
 *
 */
abstract class CommonBlob
{
    /**
     * @return array
     * @throws \Exception
     */
    abstract public function listContainers();

    /**
     * Check if a container exists
     *
     * @param  string  $container Container name
     * @return boolean
     * @throws \Exception
     */
    abstract public function containerExists($container = '');

    /**
     * @param string $container
     * @param array $metadata
     * @throws \Exception
     */
    abstract public function createContainer($container = '', $metadata = array());

    /**
     * @param string $container
     * @throws \Exception
     */
    abstract public function deleteContainer($container = '');

    /**
     * Check if a blob exists
     *
     * @param  string  $container Container name
     * @param  string  $name      Blob name
     * @return boolean
     * @throws \Exception
     */
    abstract public function blobExists($container = '', $name = '');

    /**
     * @param string $container
     * @param string $name
     * @param string $blob
     * @param string $type
     * @throws \Exception
     */
    abstract public function putBlobData($container = '', $name = '', $blob = '', $type = '');

    /**
     * @param string $container
     * @param string $name
     * @param string $localFileName
     * @param string $type
     * @throws \Exception
     */
    abstract public function putBlobFromFile($container = '', $name = '', $localFileName = '', $type = '');

    /**
     * @param string $container
     * @param string $name
     * @param string $src_container
     * @param string $src_name
     * @throws \Exception
     */
    abstract public function copyBlob($container = '', $name = '', $src_container = '', $src_name = '');

    /**
     * Get blob
     *
     * @param  string $container     Container name
     * @param  string $name          Blob name
     * @param  string $localFileName Local file name to store downloaded blob
     * @throws \Exception
     */
    abstract public function getBlobAsFile($container = '', $name = '', $localFileName = '');

    /**
     * @param string $container
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    abstract public function getBlobData($container = '', $name = '');

    /**
     * @param string $container
     * @param string $name
     * @throws \Exception
     */
    abstract public function deleteBlob($container = '', $name = '');

    /**
     * List blobs
     *
     * @param  string $container Container name
     * @param  string $prefix    Optional. Filters the results to return only blobs whose name begins with the specified prefix.
     * @param  string $delimiter Optional. Delimiter, i.e. '/', for specifying folder hierarchy
     * @return array
     * @throws \Exception
     */
    abstract public function listBlobs($container = '', $prefix = '', $delimiter = '');

    /**
     * List blob
     *
     * @param  string $container Container name
     * @param  string $name      Blob name
     * @return array instance
     * @throws \Exception
     */
    abstract public function listBlob($container, $name);

    /**
     * @param $container
     * @param $blobName
     * @param array $params
     * @throws \Exception
     */
    abstract public function streamBlob($container, $blobName, $params=array());
}
