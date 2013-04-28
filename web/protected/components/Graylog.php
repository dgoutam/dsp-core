<?php
/**
 * Graylog.php
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