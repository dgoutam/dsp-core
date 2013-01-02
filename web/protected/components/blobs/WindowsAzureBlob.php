<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage BlobSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Blob\BlobRestProxy;

/**
 *
 */
class WindowsAzureBlob extends CommonBlob
{

    /**
     * @var BlobRestProxy|null
     */
    protected $_blobConn = null;

    /**
     * @throws Exception
     */
    protected function checkConnection()
    {
        if (!isset($this->_blobConn)) {
            throw new Exception("No valid connection to blob file storage.");
        }
    }

    /**
     * Connects to a Azure Blob Storage
     *
     * @param boolean $useDev Utilize the local emulator storage
     * @param string $name Storage account name
     * @param string $key Storage account primary key
     * @throws Exception
     */
    public function __construct($useDev = true, $name = '', $key = '')
    {
        if (empty($name)) {
            throw new Exception('WindowsAzure storage name can not be empty.');
        }
        if (empty($key)) {
            throw new Exception('WindowsAzure storage key can not be empty.');
        }
        if (!$useDev) {
            $connectionString = "DefaultEndpointsProtocol=https;AccountName=$name;AccountKey=$key";
        }
        else {
            $connectionString = "UseDevelopmentStorage=true";
        }

        try {
            $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);
        }
        catch (ServiceException $ex) {
            throw new Exception("Unexpected Windows Azure Blob Service Exception:\n{$ex->getMessage()}");
        }

        $this->_blobConn = $blobRestProxy;
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        if (isset($this->_blobConn)) {
            self::disconnect($this->_blobConn);
            unset($this->_blobConn);
        }
    }

    /**
     * @param $conn
     */
    protected static function disconnect($conn)
    {
        if (isset($conn)) {
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    private function fixBlobName($name)
    {
        // doesn't like spaces in the name, anything else?
        return str_replace(' ', '%20', $name);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function listContainers()
    {
        try {
            $this->checkConnection();
            $result = $this->_blobConn->listContainers();
            return $result->getContainers();
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Check if a container exists
     *
     * @param  string  $container Container name
     * @return boolean
     * @throws Exception
     */
    public function containerExists($container = '')
    {
        try {
            $this->checkConnection();
            $this->_blobConn->getContainerProperties($container);
            return true;
        }
        catch (Exception $ex) {
            if (0 === stripos($ex->getMessage(), 'does not exist')) {
                throw $ex;
            }
        }
        return false;
    }

    /**
     * @param string $container
     * @param array $metadata
     * @return Exception|void
     * @throws Exception
     */
    public function createContainer($container = '', $metadata = array())
    {
        try {
            $this->checkConnection();
            $options = new \WindowsAzure\Blob\Models\CreateContainerOptions();
            $options->setMetadata($metadata);
//            $options->setPublicAccess('blob');
            $this->_blobConn->createContainer($container, $options);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @return Exception|void
     * @throws Exception
     */
    public function deleteContainer($container = '')
    {
        try {
            $this->checkConnection();
            $this->_blobConn->deleteContainer($container);
        }
        catch (Exception $ex) {
            if (0 === stripos($ex->getMessage(), 'does not exist')) {
                throw $ex;
            }
        }
    }

    /**
     * Check if a blob exists
     *
     * @param  string  $container Container name
     * @param  string  $name      Blob name
     * @return boolean
     * @throws Exception
     */
    public function blobExists($container = '', $name = '')
    {
        try {
            $this->checkConnection();
            $name = $this->fixBlobName($name);
            $this->_blobConn->getBlobProperties($container, $name);
            return true;
        }
        catch (Exception $ex) {
            if (0 === stripos($ex->getMessage(), 'does not exist')) {
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
     * @return Exception|void
     * @throws Exception
     */
    public function putBlobData($container = '', $name = '', $blob = '', $type = '')
    {
        try {
            $this->checkConnection();
            $options = new \WindowsAzure\Blob\Models\CreateBlobOptions();
            if (!empty($type)) {
                $options->setContentType($type);
            }
            $name = $this->fixBlobName($name);
            $this->_blobConn->createBlockBlob($container, $name, $blob, $options);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @param string $localFileName
     * @param string $type
     * @return Exception|void
     * @throws Exception
     */
    public function putBlobFromFile($container = '', $name = '', $localFileName = '', $type = '')
    {
        try {
            $this->checkConnection();
            $options = new \WindowsAzure\Blob\Models\CreateBlobOptions();
            if (!empty($type)) {
                $options->setContentType($type);
            }
            $name = $this->fixBlobName($name);
            $this->_blobConn->createBlockBlob($container, $name, $localFileName, $options);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @param string $src_container
     * @param string $src_name
     * @return Exception|void
     * @throws Exception
     */
    public function copyBlob($container = '', $name = '', $src_container = '', $src_name = '')
    {
        try {
            $this->checkConnection();
            $name = $this->fixBlobName($name);
            $src_name = $this->fixBlobName($src_name);
            $this->_blobConn->copyBlob($container, $name, $src_container, $src_name);
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Get blob
     *
     * @param  string                           $container     Container name
     * @param  string                           $name          Blob name
     * @param  string                           $localFileName Local file name to store downloaded blob
     * @return Exception|void
     * @throws Exception
     */
    public function getBlobAsFile($container = '', $name = '', $localFileName = '')
    {
        try {
            $this->checkConnection();
            $name = $this->fixBlobName($name);
            $results = $this->_blobConn->getBlob($container, $name);
            file_put_contents($localFileName, stream_get_contents($results->getContentStream()));
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function getBlobData($container = '', $name = '')
    {
        try {
            $this->checkConnection();
            $name = $this->fixBlobName($name);
            $results = $this->_blobConn->getBlob($container, $name);
            return stream_get_contents($results->getContentStream());
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @return Exception|void
     * @throws Exception
     */
    public function deleteBlob($container = '', $name = '')
    {
        try {
            $this->checkConnection();
            $name = $this->fixBlobName($name);
            $this->_blobConn->deleteBlob($container, $name);
        }
        catch (Exception $ex) {
            if (0 === stripos($ex->getMessage(), 'does not exist')) {
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
     * @return array
     * @throws Exception
     */
    public function listBlobs($container = '', $prefix = '', $delimiter = '')
    {
        try {
            $this->checkConnection();
            $options = new \WindowsAzure\Blob\Models\ListBlobsOptions();
            if (!empty($delimiter)) $options->setDelimiter($delimiter);
            if (!empty($prefix)) $options->setPrefix($prefix);
            $results = $this->_blobConn->listBlobs($container, $options);
            $blobs = $results->getBlobs();
            $prefixes = $results->getBlobPrefixes();
            $out = array();
            foreach ($blobs as $blob) {
                /* @param string  $name            Name
                 * @param string  $lastModified    Last modified date
                 * @param int     $contentLength   Content Length
                 * @param string  $contentType     Content Type
                 * @param string  $contentEncoding Content Encoding
                 * @param string  $contentLanguage Content Language
                 * @param array   $metadata        Key/value pairs of meta data */
                $props = $blob->getProperties();
                $out[] = array(
                    'name' => $blob->getName(),
                    'lastModified' => gmdate('D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp()),
                    'size' => $props->getContentLength(),
                    'contentType' => $props->getContentType(),
                    'contentEncoding' => $props->getContentEncoding(),
                    'contentLanguage' => $props->getContentLanguage()
                );
            }
            foreach ($prefixes as $blob) {
                $out[] = array(
                    'name' => $blob->getName()
                );
            }
            return $out;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * List blob
     *
     * @param  string $container Container name
     * @param  string $name      Blob name
     * @return array instance
     * @throws Exception
     */
    public function listBlob($container, $name)
    {
        try {
            $this->checkConnection();
            $name = $this->fixBlobName($name);
            $result = $this->_blobConn->getBlobProperties($container, $name);
            $props = $result->getProperties();
            $file = array(
                'name' => $name,
                'lastModified' => gmdate('D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp()),
                'size' => $props->getContentLength(),
                'contentType' => $props->getContentType()
            );
            return $file;
        }
        catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $blobName
     * @param array $params
     * @throws Exception
     * @return Exception|void
     */
    public function streamBlob($container, $blobName, $params=array())
    {
        try {
            $this->checkConnection();
            $blob = $this->_blobConn->getBlob($container, $blobName);
            $props = $blob->getProperties();
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $props->getLastModified()->getTimestamp()));
            header('Content-type: ' . $props->getContentType());
            header('Content-Transfer-Encoding: ' . $props->getContentEncoding());
            header('Content-Length:' . $props->getContentLength());
            $disposition = (isset($params['disposition']) && !empty($params['disposition'])) ? $params['disposition'] : 'inline';
            header("Content-Disposition: $disposition; filename=\"$blobName\";");
            fpassthru($blob->getContentStream());
//            $this->_blobConn->registerStreamWrapper();
//            $blobUrl = 'azure://' . $container . '/' . $blobName;
//            readfile($blobUrl);
            exit;
        }
        catch (Exception $ex) {
            if ('Resource could not be accessed.' == $ex->getMessage()) {
                header("Status: 404 The specified file '$blobName' does not exist.");
                exit;
            } else {
                throw $ex;
            }
        }
    }
}
