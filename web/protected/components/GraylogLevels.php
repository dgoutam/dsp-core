<?php
/**
 * GraylogLevels
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
