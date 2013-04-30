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
/**
 * common.config.php
 * Parameters common to SAPI and CLI apps
 */
global $_dbName, $_blobConfig, $_instance, $_dspName;

//*************************************************************************
//* Set fabric-hosted storage paths here...
//*************************************************************************

if ( Fabric::fabricHosted() && !empty( $_instance ) )
{
	if ( is_object( $_instance ) )
	{
		$_instance = (array)$_instance;
	}

	$_instanceSettings = array(
		'storage_base_path'      => $_instance['storage_path'],
		'private_path'           => $_instance['private_path'],
		'storage_path'           => $_instance['blob_storage_path'],
		'snapshot_path'          => $_instance['snapshot_path'],
		'dsp_name'               => $_instance['instance']->instance_name_text,
		'dsp.storage_id'         => $_instance['storage_key'],
		'dsp.private_storage_id' => $_instance['private_storage_key'],
	);
}
else
{
	$_instanceSettings = array(
		'storage_base_path'      => __DIR__ . '/../storage',
		'private_path'           => __DIR__ . '/../storage/.private',
		'storage_path'           => __DIR__ . '/../storage',
		'snapshot_path'          => __DIR__ . '/../storage/.private/snapshots',
		'dsp_name'               => gethostname(),
		'dsp.storage_id'         => null,
		'dsp.private_storage_id' => null,
	);
}

return array_merge(
	$_instanceSettings,
	array(
		 /**
		  * DSP Information
		  */
		 'dsp.version'           => '1.0.0-beta',
		 'dsp.name'              => $_instanceSettings['dsp_name'],
		 'cloud.endpoint'        => 'http://api.cloud.dreamfactory.com',
		 /**
		  * User data
		  */
		 'blobStorageConfig'     => file_exists( $_blobConfig ) ? require( $_blobConfig ) : array(),
		 'adminEmail'            => 'developer-support@dreamfactory.com',
	)
);
