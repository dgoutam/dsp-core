<?php

namespace CloudServicesPlatform\Utilities;

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Config
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */
class Config
{
    /**
     * Constants
     */
    const SVC_VERSION = "0.1";
    const API_VERSION = "0.1";

    /**
     * Service Directory Constants
     */
    const APPS_STORAGE_NAME = 'applications';
    const ATTACHMENTS_STORAGE_NAME = 'attachments';
    const DOCS_STORAGE_NAME = 'documents';

    /**
     * @var array
     */
    private static $_env_defaults = array(
        'BlobStorageType'   => 'WindowsAzureBlob',
// Windows Azure
        'BlobAccountName'   => 'dreamfactorysoftware',
        'BlobAccountKey'    => 'lpUCNR/7lmxBVsQuB3jD4yBQ4SWTvbmoJmJ4f+2q7vvm7/qQBHF0Lkfq4QQSk7KefNc5O3VJbQuW+wLLp79F3A==',
// Amazon S3
//        'BlobAccessKey'     => '',
//        'BlobSecretKey'     => '',
//        'BlobBucketName'    => '',
        'DbType'            => 'sql',
//        'DbDSN'             => 'sqlsrv:server = tcp:jecfdpix16.database.windows.net,1433; Database = dfCspDb;',
        'DbDSN'             => 'sqlsrv:server = tcp:hof7lqw5qv.database.windows.net,1433; Database = dfTestDB;',
        'DbAdminUser'       => 'dfadmin',
        'DbAdminPwd'        => 'Dream123',
//        'DbDSN'             => 'mysql:host=us-cdbr-azure-east-b.cloudapp.net;dbname=dftestDb',
//        'DbAdminUser'       => 'b87314a4dd182f',
//        'DbAdminPwd'        => '01f9b9a2',
        ''
    );

    /**
     * @var array
     */
    private static $_cfg_defaults = array(
        'CompanyLabel'          => 'My Dream Cloud',
        'AdminEmail'            => 'leehicks@dreamfactory.com',
        'AllowOpenRegistration' => 'true',
        ''
    );

    public static function getConfigValue($name)
    {
        Utilities::markTimeStart('CFG_TIME');

        $value = getenv($name);
        if (false === $value || empty($value)) {
            if (!isset(self::$_cfg_defaults[$name])) {
                error_log('Environment not available and no default for "' . $name . '"');
            }
            else {
                $value = self::$_cfg_defaults[$name];
            }
        }

        Utilities::markTimeStop('CFG_TIME');

        return $value;
    }

    public static function getEnvValue($name)
    {
        Utilities::markTimeStart('CFG_TIME');

        $value = getenv($name);
        if (false === $value || empty($value)) {
            if (!isset(self::$_env_defaults[$name])) {
                error_log('Environment not available and no default for "' . $name . '"');
            }
            else {
                $value = self::$_env_defaults[$name];
            }
        }

        Utilities::markTimeStop('CFG_TIME');

        return $value;
    }
}
