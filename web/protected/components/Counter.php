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
/**
 * Counter
 * A simple counter
 *
 * @property array $apps     Info about apps
 * @property array $database Info about the database
 * @property array $users    Info about users
 */
class Counter
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_counters
		= array(
			'total' => 0,
		);

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param int   $total      The starting total
	 * @param array $additional An array of additional counters without ( array('foo','bar',etc.) ) or with ( array('foo' => 0, 'bar' => 666) ) values.
	 */
	public function __construct( $additional = array(), $total = 0 )
	{
		if ( !empty( $additional ) )
		{
			if ( is_numeric( $additional ) )
			{
				$this->_counters['total'] += $additional;
			}
			else if ( is_array( $additional ) || is_string( $additional ) )
			{
				$additional = (array)$additional;

				foreach ( $additional as $_datapoint => $_value )
				{
					if ( is_numeric( $_datapoint ) && is_string( $_value ) )
					{
						$this->_counters[$_value] = 0;
					}
					else
					{
						$this->_counters[$_datapoint] = $_value;
					}
				}
			}
		}

		$this->_counters['total'] = $total;
	}

	/**
	 * Add a counter to the object
	 *
	 * @param string $name
	 * @param int    $startValue
	 *
	 * @return $this
	 */
	public function add( $name, $startValue = 0 )
	{
		$this->_counters[$name] = $startValue;

		return $this;
	}

	/**
	 * @param string|array $name         The counter or counters to increment. Pass '*' to increment ALL counters.
	 * @param int          $howMuch      How much to increment. Defaults to 1
	 * @param int          $startValue   If a counter doesn't exist, create it with this starting value
	 *
	 * @return $this
	 */
	public function increment( $name = null, $howMuch = 1, $startValue = 0 )
	{
		if ( null === $name )
		{
			$_list = array( 'total' );
		}
		else if ( '*' === $name )
		{
			$_list = $this->_counters;
		}
		elseif ( is_array( $name ) )
		{
			$_list = $name;
		}
		else
		{
			$_list = array( $name );
		}

		foreach ( $_list as $_name => $_value )
		{
			$_key = ( is_numeric( $_name ) && is_string( $_value ) ? $_value : $_name );
			$this->_counters[$_key] += $howMuch;
		}

		return $this;
	}

	/**
	 * @param null $name
	 * @param int  $startValue
	 *
	 * @return $this
	 */
	public function reset( $name = null, $startValue = 0 )
	{
		if ( null !== $name )
		{
			$this->_counters[$name] = $startValue;

			return $this;
		}

		foreach ( $this->_counters as $_name => $_value )
		{
			$this->_counters[$_name] = $startValue;
		}

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getCounters()
	{
		return $this->_counters;
	}

	/**
	 * @return string
	 */
	public function toJson()
	{
		return json_encode( $this->_counters );
	}
}