<?php
namespace DreamFactory\Platform\Services;

use DreamFactory\Yii\Interfaces\RestLike;
use Kisma\Core\Exceptions\NotImplementedException;
use Kisma\Core\Services\SeedService;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;

/**
 * BaseService.php
 * Provides services to the DSP
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
abstract class BaseResourceService extends SeedService implements RestLike
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const RESOURCE_TYPE = null;

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected static $_tablePrefix;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @throws \Kisma\Core\Exceptions\NotImplementedException
	 * @return string
	 */
	public static function getType()
	{
		if ( null === static::RESOURCE_TYPE )
		{
			throw new NotImplementedException( 'You must define the "RESOURCE_TYPE" constant in your base class.' );
		}

		return static::RESOURCE_TYPE;
	}

	/**
	 * @param string $old
	 * @param string $new
	 * @param        $operator An optional operator to use ('>','<','>=','<=', '=', '!=')
	 *
	 * @return bool
	 */
	public static function versionCompare( $old, $new, $operator = null )
	{
		return version_compare( $old, $new, $operator );
	}

	/**
	 * @param string $tablePrefix
	 */
	public static function setTablePrefix( $tablePrefix )
	{
		self::$_tablePrefix = $tablePrefix;
	}

	/**
	 * @return string
	 */
	public static function getTablePrefix()
	{
		return self::$_tablePrefix;
	}

	//*************************************************************************
	//* Interface Stubs
	//*************************************************************************

	/**
	 * @return mixed
	 */
	public function restGet()
	{
		return null;
	}

	/**
	 * @return mixed
	 */
	public function restPost()
	{
		return null;
	}

	/**
	 * @return mixed
	 */
	public function restPatch()
	{
		return null;
	}

	/**
	 * @return mixed
	 */
	public function restPut()
	{
		return null;
	}

	/**
	 * @return mixed
	 */
	public function restMerge()
	{
		return null;
	}

	/**
	 * @return mixed
	 */
	public function restDelete()
	{
		return null;
	}

}
