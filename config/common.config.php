<?php
/**
 * common.config.php
 *
 * This file is part of the DreamFactory Document Service Platform (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This source file and all is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * Parameters common to SAPI and CLI apps
 */
global $_dbName, $_blobConfig;

return array(
	'storage_base_path'     => '/data/storage',
	'private_path'          => '/data/storage/' . $_dbName . '/.private',
	'storage_path'          => '/data/storage/' . $_dbName . '/blob',
	'dsp_name'              => $_dbName,
	'blobStorageConfig'     => file_exists( $_blobConfig ) ? require( $_blobConfig ) : array(),
	'adminEmail'            => 'developer-support@dreamfactory.com',
	'companyLabel'          => 'DreamFactory DSP',
	'allowOpenRegistration' => 'true',
);
