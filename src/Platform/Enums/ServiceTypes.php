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
 * ServiceTypes
 * Service type constants
 */
class ServiceTypes extends SeedEnum
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const REMOTE_WEB_SERVICE = 'remote web service';
	/**
	 * @var string
	 */
	const LOCAL_FILE_STORAGE = 'local file storage';
	/**
	 * @var string
	 */
	const REMOTE_FILE_STORAGE = 'remote file storage';
	/**
	 * @var string
	 */
	const LOCAL_SQL_DB = 'local sql db';
	/**
	 * @var string
	 */
	const REMOTE_SQL_DB = 'remote sql db';
	/**
	 * @var string
	 */
	const LOCAL_SQL_DB_SCHEMA = 'local sql db schema';
	/**
	 * @var string
	 */
	const REMOTE_SQL_DB_SCHEMA = 'remote sql db schema';
	/**
	 * @var string
	 */
	const LOCAL_EMAIL_SERVICE = 'local email service';
	/**
	 * @var string
	 */
	const REMOTE_EMAIL_SERVICE = 'remote email service';
	/**
	 * @var string
	 */
	const NOSQL_DB = 'nosql db';
	/**
	 * @var string
	 */
	const SERVICE_REGISTRY = 'service registry';
	/**
	 * @var string
	 */
	const REMOTE_OAUTH_SERVICE = 'remote oauth service';
}
