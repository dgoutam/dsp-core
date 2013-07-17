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
namespace Platform\Interfaces;
/**
 * Graylog
 */
interface Graylog
{
	//**************************************************************************
	//* Constants
	//**************************************************************************

	/**
	 * @var string Hostname of graylog2 server
	 */
	const DefaultHost = 'graylog.fabric.dreamfactory.com';
	/**
	 * @const integer Port that graylog2 server listens on
	 */
	const DefaultPort = 12201;
	/**
	 * @const integer Maximum message size before splitting into chunks
	 */
	const MaximumChunkSize = 8154;
	/**
	 * @const integer Maximum message size before splitting into chunks
	 */
	const MaximumChunkSizeWan = 1420;
	/**
	 * @const integer Maximum number of chunks allowed by GELF
	 */
	const MaximumChunksAllowed = 128;
	/**
	 * @const string GELF version
	 */
	const GelfVersion = '1.0';
	/**
	 * @const integer Default GELF message level
	 */
	const DefaultLevel = GraylogLevels::Alert;
	/**
	 * @const string Default facility value for messages
	 */
	const DefaultFacility = 'platform';
}