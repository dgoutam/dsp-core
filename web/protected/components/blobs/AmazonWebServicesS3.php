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

class AmazonWebServicesS3 extends CommonBlob
{
    /**
     * @var
     */
    private $_bucket;

    /**
     * @var null
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
     * @param $accessKey
     * @param $secretKey
     * @param $bucket
     * @throws Exception
     */
    public function __construct($accessKey, $secretKey, $bucket)
    {
        if (empty($accessKey)) {
            throw new Exception('Amazon S3 access key can not be empty.');
        }
        if (empty($secretKey)) {
            throw new Exception('Amazon S3 secret key can not be empty.');
        }
        if (empty($bucket)) {
            throw new Exception('Amazon S3 bucket name can not be empty.');
        }
        try {
            $options = array(
                'certificate_authority' => true,
                'key' => $accessKey,
                'secret' => $secretKey
            );
            $blobRestProxy = new \AmazonS3($options);
        } catch (Exception $ex) {
            throw new Exception("Unexpected Amazon S3 Service Exception:\n{$ex->getMessage()}");
        }

        $this->_blobConn = $blobRestProxy;
        $this->_bucket = $bucket;
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

    private static function fixContainerName($container)
    {
        return rtrim($container, '/') . '/';
    }

    private static function addContainerToName($container, $name)
    {
        if (empty($container)) return $name;
        $container = self::fixContainerName($container);
        return $container . $name;
    }

    private static function removeContainerFromName($container, $name)
    {
        if (empty($container)) return $name;
        $container = self::fixContainerName($container);
        return substr($name, strlen($container) + 1);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function listContainers()
    {
        try {
            $this->checkConnection();

            $options = array('prefix' => '', 'delimiter' => '/');
            $objects = $this->_blobConn->list_objects($this->_bucket, $options);
            $out = array();
            foreach ($objects as $object) {
                $out[] = array('name' => rtrim($object->Prefix, '/'));;
            }
            return $out;
        } catch (Exception $ex) {
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
            $container = self::fixContainerName($container);
            return $this->_blobConn->if_object_exists($this->_bucket, $container);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param array $metadata
     * @throws Exception
     */
    public function createContainer($container = '', $metadata = array())
    {
        try {
            $this->checkConnection();
            $container = self::fixContainerName($container);
            $options = array('body' => '');
            $result = $this->_blobConn->create_object($this->_bucket, $container, $options);
            if (!$result->isOK()) {
                throw new Exception('Failed to create container.');
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @throws Exception
     */
    public function deleteContainer($container = '')
    {
        try {
            $this->checkConnection();
            $container = self::fixContainerName($container);
            $result = $this->_blobConn->delete_object($this->_bucket, $container);
            if (!$result->isOK()) {
                throw new Exception('Failed to delete container.');
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Check if a blob exists
     *
     * @param  string  $container Container name
     * @param  string  $name      Blob name
     * @return boolean
     */
    public function blobExists($container = '', $name = '')
    {
        try {
            $this->checkConnection();
            $key = self::addContainerToName($container, $name);
            return $this->_blobConn->if_object_exists($this->_bucket, $key);
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @param string $blob
     * @param string $type
     * @throws Exception
     */
    public function putBlobData($container = '', $name = '', $blob = '', $type = '')
    {
        try {
            $this->checkConnection();
            $key = self::addContainerToName($container, $name);
            $options = array('body' => $blob);
            if (!empty($type)) {
                $options['contentType'] = $type;
            }
            $result = $this->_blobConn->create_object($this->_bucket, $key, $options);
            if (!$result->isOK()) {
                throw new Exception('Failed to create blob.');
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @param string $localFileName
     * @param string $type
     * @throws Exception
     */
    public function putBlobFromFile($container = '', $name = '', $localFileName = '', $type = '')
    {
        try {
            $this->checkConnection();
            $key = self::addContainerToName($container, $name);
            $options = array('fileUpload' => $localFileName);
            if (!empty($type)) {
                $options['contentType'] = $type;
            }
            $result = $this->_blobConn->create_object($this->_bucket, $key, $options);
            if (!$result->isOK()) {
                throw new Exception('Failed to create blob.');
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @param string $src_container
     * @param string $src_name
     * @throws Exception
     */
    public function copyBlob($container = '', $name = '', $src_container = '', $src_name = '')
    {
        try {
            $this->checkConnection();
            $key = self::addContainerToName($container, $name);
            $src_key = self::addContainerToName($src_container, $src_name);
            $source = array('bucket' => $this->_bucket, 'filename' => $src_key);
            $dest = array('bucket' => $this->_bucket, 'filename' => $key);
            $result = $this->_blobConn->copy_object($source, $dest);
            if (!$result->isOK()) {
                throw new Exception('Failed to copy blob.');
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Get blob
     *
     * @param  string                           $container     Container name
     * @param  string                           $name          Blob name
     * @param  string                           $localFileName Local file name to store downloaded blob
     * @throws Exception
     */
    public function getBlobAsFile($container = '', $name = '', $localFileName = '')
    {
        try {
            $this->checkConnection();
            $key = self::addContainerToName($container, $name);
            $options = array('fileDownload' => $localFileName);
            $result = $this->_blobConn->get_object($this->_bucket, $key, $options);
            if (!$result->isOK()) {
                throw new Exception('Failed to retrieve blob.');
            }
        } catch (Exception $ex) {
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
            $key = self::addContainerToName($container, $name);
            $options = array();
            $result = $this->_blobConn->get_object($this->_bucket, $key, $options);
            if (!$result->isOK()) {
                throw new Exception('Failed to retrieve blob.');
            }
            return $result->body;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @throws Exception
     */
    public function deleteBlob($container = '', $name = '')
    {
        try {
            $this->checkConnection();
            $key = self::addContainerToName($container, $name);
            $result = $this->_blobConn->delete_object($this->_bucket, $key);
            if (!$result->isOK()) {
                throw new Exception('Failed to delete blob.');
            }
        } catch (Exception $ex) {
            throw $ex;
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
            //if (!empty($delimiter)) $options['delimiter'] = $delimiter;
            $search = $container . '/';
            if (!empty($prefix)) $search .= $prefix;
            $options = array('prefix' => $search/*, 'delimiter' => $delimiter*/);
            $objects = $this->_blobConn->get_object_list($this->_bucket, $options);
            $out = array();
            foreach ($objects as $object) {
                $meta = $this->_blobConn->get_object_metadata($this->_bucket, $object);
                $name = $meta['Key'];
                $name = self::removeContainerFromName($container, $name);
                $out[] = array(
                    'name' => $name,
                    'contentType' => $meta['ContentType'],
                    'size' => $meta['Size'],
                    'lastModified' => $meta['Headers']['last-modified']
                );
            }
            return $out;
        } catch (Exception $ex) {
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
            $key = self::addContainerToName($container, $name);
            $result = $this->_blobConn->get_object_metadata($this->_bucket, $key);
            if (!$result) {
                throw new Exception('Failed to list blob metadata.');
            }
            $file = array(
                'name' => $name,
                'contentType' => $result['ContentType'],
                'size' => $result['Size'],
                'lastModified' => $result['Headers']['last-modified']
            );

            return $file;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param string $container
     * @param string $name
     * @param array $params
     * @throws Exception
     */
    public function streamBlob($container, $name, $params=array())
    {
        try {
            $this->checkConnection();
            $key = $container . '/' . $name;
            $result = $this->_blobConn->get_object($this->_bucket, $key);
            if (!$result->isOK()) {
                throw new Exception('Failed to stream blob.');
            }
            header('Last-Modified: ' . $result->header['last-modified']);
            header('Content-type: ' . $result->header['content-type']);
            header('Content-Length:' . $result->header['content-length']);
            $disposition = (isset($params['disposition']) && !empty($params['disposition'])) ? $params['disposition'] : 'inline';
            header("Content-Disposition: $disposition; filename=\"$name\";");
            echo $result->body;
            exit;
        } catch (Exception $ex) {
            if ('Resource could not be accessed.' == $ex->getMessage()) {
                header("Status: 404 The specified file '$name' does not exist.");
                exit;
            } else {
                throw $ex;
            }
        }
    }
}
