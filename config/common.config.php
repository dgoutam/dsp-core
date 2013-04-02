<?php
/**
 * common.config.php
 * Parameters common to SAPI and CLI apps
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
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
global $_dbName, $_blobConfig, $_instance;

//*************************************************************************
//* Set fabric-hosted storage paths here...
//*************************************************************************

if ( \Kisma::get( 'app.fabric_hosted' ) && !empty( $_instance ) )
{
	$_instanceSettings = array(
		'storage_base_path' => $_instance->storage_path,
		'private_path'      => $_instance->private_path,
		'storage_path'      => $_instance->blob_storage_path,
		'snapshot_path'     => $_instance->snapshot_path,
		'dsp_name'          => $_instance->db_name,
	);
}
else
{
	$_instanceSettings = array(
		'storage_base_path' => __DIR__ . '/../storage',
		'private_path'      => __DIR__ . '/../storage/.private',
		'storage_path'      => __DIR__ . '/../storage',
		'snapshot_path'     => __DIR__ . '/../storage/.private/snapshots',
		'dsp_name'          => $_dbName,
	);
}

return array_merge(
	$_instanceSettings,
	array(
		 'blobStorageConfig'     => file_exists( $_blobConfig ) ? require( $_blobConfig ) : array(),
		 'adminEmail'            => 'developer-support@dreamfactory.com',
		 'companyLabel'          => 'DreamFactory Service Platform(tm)',
		 'allowOpenRegistration' => 'true',
		 'dsp.version'           => '0.6.1',
	)
);
