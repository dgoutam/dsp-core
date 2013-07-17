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
namespace Platform\Enums;

use Kisma\Core\Enums\SeedEnum;

/**
 * StorageTypes
 * Storage type constants
 */
class StorageTypes extends SeedEnum
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const AZURE_BLOB = 'azure blob';
	/**
	 * @var string
	 */
	const AWS_S3 = 'aws s3';
	/**
	 * @var string
	 */
	const RACKSPACE_CLOUDFILES = 'rackspace cloudfiles';
	/**
	 * @var string
	 */
	const OPENSTACK_OBJECT_STORAGE = 'openstack object storage';
	/**
	 * @var string
	 */
	const AZURE_TABLES = 'azure tables';
	/**
	 * @var string
	 */
	const AWS_DYNAMODB = 'aws dynamodb';
	/**
	 * @var string
	 */
	const AWS_SIMPLEDB = 'aws simpledb';
	/**
	 * @var string
	 */
	const MONGODB = 'mongodb';
	/**
	 * @var string
	 */
	const COUCHDB = 'couchdb';
}
