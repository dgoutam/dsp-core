<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage LibrarySvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class LibrarySvc extends CommonFileSvc
{
    /**
     * @param array $config
     * @param string $store_name
     * @throws \Exception
     */
    public function __construct($config, $store_name = '')
    {
        // Validate blob setup
        if (empty($store_name)) {
            $store_name = Defaults::LIBS_STORAGE_NAME;
        }
        parent::__construct($config, $store_name);
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        parent::__destruct();
    }

}
