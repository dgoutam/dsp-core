<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
	const REMOTE_WEB_SERVICE = 'Remote Web Service';
	/**
	 * @var string
	 */
	const LOCAL_FILE_STORAGE = 'Local File Storage';
	/**
	 * @var string
	 */
	const REMOTE_FILE_STORAGE = 'Remote File Storage';
	/**
	 * @var string
	 */
	const LOCAL_SQL_DB = 'Local SQL DB';
	/**
	 * @var string
	 */
	const REMOTE_SQL_DB = 'Remote SQL DB';
	/**
	 * @var string
	 */
	const LOCAL_SQL_DB_SCHEMA = 'Local SQL DB Schema';
	/**
	 * @var string
	 */
	const REMOTE_SQL_DB_SCHEMA = 'Remote SQL DB Schema';
	/**
	 * @var string
	 */
	const EMAIL_SERVICE = 'Email Service';
	/**
	 * @var string
	 */
	const LOCAL_EMAIL_SERVICE = 'Local Email Service';
	/**
	 * @var string
	 */
	const REMOTE_EMAIL_SERVICE = 'Remote Email Service';
	/**
	 * @var string
	 */
	const NOSQL_DB = 'NoSQL DB';
	/**
	 * @var string
	 */
	const SERVICE_REGISTRY = 'Service Registry';
	/**
	 * @var string
	 */
	const REMOTE_OAUTH_SERVICE = 'Remote OAuth Service';
}
