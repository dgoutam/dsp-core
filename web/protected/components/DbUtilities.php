<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Utilities
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class DbUtilities
{

    // constants

    // DB driver types
    const DRV_OTHER  = 0;
    const DRV_SQLSRV = 1;
    const DRV_MYSQL  = 2;
    const DRV_SQLITE = 3;
    const DRV_PGSQL  = 4;
    const DRV_OCSQL  = 5;

    public static function getDbDriverType($driver_name)
    {
        switch ($driver_name) {
        case 'mssql':
        case 'dblib':
        case 'sqlsrv':
            return self::DRV_SQLSRV;
        case 'mysqli':
        case 'mysql':
            return self::DRV_MYSQL;
        case 'sqlite':
        case 'sqlite2':
            return self::DRV_SQLITE;
        case 'oci':
            return self::DRV_OCSQL;
        case 'pgsql':
            return self::DRV_PGSQL;
        default:
            return self::DRV_OTHER;
        }
    }

}
