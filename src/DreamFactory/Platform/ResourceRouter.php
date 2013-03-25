<?php
namespace DreamFactory\Platform;

use DreamFactory\Platform\Exceptions\DuplicateRouteException;
use DreamFactory\Platform\Exceptions\InvalidRouteException;
use DreamFactory\Platform\Exceptions\RoutingException;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Seed;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Scalar;

/**
 * ResourceRouter.php
 * Stores/retrieves routes to resources
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
class ResourceRouter extends Seed implements ServiceLike
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_routeMap;
	/**
	 * @var array
	 */
	protected $_routes;
	/**
	 * @var array
	 */
	protected $_basePath;

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @param string $method
	 * @param string $route
	 * @param mixed  $resource
	 * @param string $name
	 *
	 * @return string The route ID
	 * @throws Exceptions\DuplicateRouteException
	 */
	public function map( $method, $route, $resource, $name = null )
	{
		list( $_hash, $_uri, $_name ) = $this->_hashRoute( $method, $route, $name );

		if ( !empty( $_name ) )
		{
			if ( isset( $this->_routeMap[$_name] ) )
			{
				throw new DuplicateRouteException( 'The route name "' . $_name . '" has already been defined.' );
			}

			//	Store the index
			$this->_routeMap[$_name] = $_hash;
		}

		$this->_routes[$_hash] = array(
			'hash'     => $_hash,
			'method'   => $method,
			'uri'      => $_uri,
			'resource' => $resource,
			'name'     => $_name
		);

		return $_hash;
	}

	/**
	 * @param string $method
	 * @param string $route
	 * @param string $name
	 *
	 * @return array
	 */
	public function _hashRoute( $method, $route, $name = null )
	{
		$_name = Inflector::neutralize( $name );
		$_uri = $this->_basePath . '/' . ltrim( $route, '/' );
		$_hash = sha1( $method . '://' . $_uri . ( $_name ? '#' . $_name : null ) );

		return array(
			$_hash,
			$_uri,
			$_name,
		);
	}

	/**
	 * Named routing re-writer
	 *
	 * @param string $routeName The name of the route.
	 * @param array  $params    array of parameters to replace placeholders with.
	 *
	 * @throws Exceptions\InvalidRouteException
	 * @return string The URL of the route with named parameters in place.
	 */
	public function rewrite( $routeName, array $params = array() )
	{
		$_name = Inflector::neutralize( $routeName );

		if ( !isset( $this->_routeMap[$_name] ) )
		{
			throw new InvalidRouteException( 'The route name "' . $_name . '" is invalid.' );
		}

		$_uri = $this->_routes[$this->_routeMap[$_name]]['uri'];

		if ( preg_match_all( '`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $_uri, $_matches, PREG_SET_ORDER ) )
		{
			foreach ( $_matches as $_match )
			{
				list( $_block, $_pre, $_type, $_param, $_optional ) = $_match;

				if ( $_pre )
				{
					$_block = substr( $_block, 1 );
				}

				if ( isset( $params[$_param] ) )
				{
					$_uri = str_replace( $_block, $params[$_param], $_uri );
				}
				elseif ( $_optional )
				{
					$_uri = str_replace( $_block, null, $_uri );
				}
			}
		}

		return $_uri;
	}

	/**
	 * Match a given Request Url against stored routes
	 *
	 * @param string $method
	 * @param string $uri
	 *
	 * @return array|boolean Array with route information on success, false on failure (no match).
	 */
	public function match( $method = null, $uri = null )
	{
		$_params = array();
		$_match = false;

		//	Set uri if not passed
		if ( empty( $uri ) )
		{
			$uri = FilterInput::server( 'REQUEST_URI', '/' );
		}

		//	String query string
		if ( false !== ( $_pos = strpos( $uri, '?' ) ) )
		{
			$uri = substr( $uri, $_pos + 1 );
		}

		//	Set method if not passed
		if ( empty( $method ) )
		{
			$method = FilterInput::server( 'REQUEST_METHOD', HttpMethod::Get );
		}

		$_matched = false;
		list( $_hash, $_uri, $_name ) = $this->_hashRoute( $method, $uri );

		if ( !isset( $this->_routes[$_hash] ) )
		{
			foreach ( $this->_routes as $_location )
			{
				$_methods = is_array( $_location['method'] ) ? explode( '|', $_location['method'] ) : $_location['method'];

				//	Check if request method matches. If not, abandon early. (CHEAP)
				if ( !Option::contains( $_methods, $method ) )
				{
					continue;
				}

				// Check for a wildcard (matches all)
				if ( '*' == $_uri )
				{
					$_matched = true;
				}
				elseif ( isset( $_route[0] ) && $_route[0] === '@' )
				{
					$_matched = preg_match( '`' . substr( $_route, 1 ) . '`', $_uri, $_params );
				}
				else
				{
				}
			}
		}

		return false;
	}
}