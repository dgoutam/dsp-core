<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage DocumentSvc
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class DocumentSvc extends CommonFileSvc
{
    /**
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        // Validate storage setup
        $store_name = Utilities::getArrayValue('storage_name', $config, '');
        if (empty($store_name)) {
            $config['storage_name'] = Defaults::DOCS_STORAGE_NAME;
        }
        parent::__construct($config);
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        parent::__destruct();
    }

}
