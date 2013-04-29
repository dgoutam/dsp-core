<?php
/**
 * BE AWARE...
 *
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
 * GraylogLevels
 */
class GraylogLevels extends \Kisma\Core\Enums\SeedEnum implements Graylog
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const Emergency = 0;
	/**
	 * @var int
	 */
	const Alert = 1;
	/**
	 * @var int
	 */
	const Critical = 2;
	/**
	 * @var int
	 */
	const Error = 3;
	/**
	 * @var int
	 */
	const Warning = 4;
	/**
	 * @var int
	 */
	const Notice = 5;
	/**
	 * @var int
	 */
	const Info = 6;
	/**
	 * @var int
	 */
	const Debug = 7;

}
