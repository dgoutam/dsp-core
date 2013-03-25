<?php
namespace DreamFactory\Yii\Behaviors;

use Kisma\Core\Utility\Option;

/**
 * BaseDspModelBehavior.php
 * A base class for AR behaviors
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
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
class BaseDspModelBehavior extends \CActiveRecordBehavior
{
	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * Constructor
	 */
	function __construct( $settings = array() )
	{
		if ( !empty( $settings ) )
		{
			foreach ( Option::clean( $settings ) as $_key => $_value )
			{
				if ( $this->hasProperty( $_key ) )
				{
					$this->__set( $_key, $_value );
				}
			}
		}
	}
}