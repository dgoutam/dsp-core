<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Platform\Utility;

use Kisma\Core\Utility\FilterInput;

/**
 * Utilities
 */
class Utilities
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	// xml helper functions

	/**
	 * A recursive array_change_key_case lowercase function.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public static function array_key_lower( $input )
	{
		if ( !is_array( $input ) )
		{
			trigger_error( "Invalid input array '{$input}'", E_USER_NOTICE );
			exit;
		}
		$input = array_change_key_case( $input, CASE_LOWER );
		foreach ( $input as $key => $array )
		{
			if ( is_array( $array ) )
			{
				$input[$key] = static::array_key_lower( $array );
			}
		}

		return $input;
	}

	/**
	 * @param $array
	 *
	 * @return bool
	 */
	public static function isArrayNumeric( $array )
	{
		if ( is_array( $array ) )
		{
			if ( !empty( $array ) )
			{
				return ( 0 === count( array_filter( array_keys( $array ), 'is_string' ) ) );
			}
		}

		return false;
	}

	/**
	 * @param      $array
	 * @param bool $strict
	 *
	 * @return bool
	 */
	public static function isArrayAssociative( $array, $strict = true )
	{
		if ( is_array( $array ) )
		{
			if ( !empty( $array ) )
			{
				if ( $strict )
				{
					return ( count( array_filter( array_keys( $array ), 'is_string' ) ) == count( $array ) );
				}
				else
				{
					return ( 0 !== count( array_filter( array_keys( $array ), 'is_string' ) ) );
				}
			}
		}

		return false;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public static function labelize( $name )
	{
		return ucwords( str_replace( '_', ' ', $name ) );
	}

	/**
	 * Pluralizes English nouns.
	 *
	 * @author    Bermi Ferrer Martinez
	 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
	 * @license   GNU Lesser General Public License
	 * @since     0.1
	 * @version   $Revision 0.1 $
	 *
	 * @access    public
	 * @static
	 *
	 * @param    string $word    English noun to pluralize
	 *
	 * @return string Plural noun
	 */
	public static function pluralize( $word )
	{
		$plural = array(
			'/(quiz)$/i'               => '1zes',
			'/^(ox)$/i'                => '1en',
			'/([m|l])ouse$/i'          => '1ice',
			'/(matr|vert|ind)ix|ex$/i' => '1ices',
			'/(x|ch|ss|sh)$/i'         => '1es',
			'/([^aeiouy]|qu)ies$/i'    => '1y',
			'/([^aeiouy]|qu)y$/i'      => '1ies',
			'/(hive)$/i'               => '1s',
			'/(?:([^f])fe|([lr])f)$/i' => '12ves',
			'/sis$/i'                  => 'ses',
			'/([ti])um$/i'             => '1a',
			'/(buffal|tomat)o$/i'      => '1oes',
			'/(bu)s$/i'                => '1ses',
			'/(alias|status)/i'        => '1es',
			'/(octop|vir)us$/i'        => '1i',
			'/(ax|test)is$/i'          => '1es',
			'/s$/i'                    => 's',
			'/$/'                      => 's'
		);

		$uncountable = array( 'equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep', 'deer' );

		$irregular = array(
			'person' => 'people',
			'man'    => 'men',
			'woman'  => 'women',
			'child'  => 'children',
			'sex'    => 'sexes'
		);

		$lowercased_word = strtolower( $word );

		foreach ( $uncountable as $_uncountable )
		{
			if ( substr( $lowercased_word, ( -1 * strlen( $_uncountable ) ) ) == $_uncountable )
			{
				return $word;
			}
		}

		foreach ( $irregular as $_plural => $_singular )
		{
			if ( preg_match( '/(' . $_plural . ')$/i', $word, $arr ) )
			{
				return preg_replace( '/(' . $_plural . ')$/i', substr( $arr[0], 0, 1 ) . substr( $_singular, 1 ), $word );
			}
		}

		foreach ( $plural as $rule => $replacement )
		{
			if ( preg_match( $rule, $word ) )
			{
				return preg_replace( $rule, $replacement, $word );
			}
		}

		return false;
	}

	/**
	 * @param        $key
	 * @param        $array
	 * @param string $if_not_found
	 *
	 * @return array|string
	 */
	public static function getArrayValue( $key, $array, $if_not_found = '' )
	{
		$val = $if_not_found;
		if ( isset( $array ) && !empty( $array ) )
		{
			if ( isset( $array[$key] ) )
			{
				$val = $array[$key];
				// due to conversion from XML to array, null or empty xml elements have the array value of an empty array
				if ( is_array( $val ) && empty( $val ) )
				{
					$val = $if_not_found;
				}
			}
			else
			{
				$array = static::array_key_lower( $array );
				$key = strtolower( $key );
				if ( isset( $array[$key] ) )
				{
					$val = $array[$key];
					// due to conversion from XML to array, null or empty xml elements have the array value of an empty array
					if ( is_array( $val ) && empty( $val ) )
					{
						$val = $if_not_found;
					}
				}
			}
		}

		return $val;
	}

	/**
	 * @param      $key
	 * @param      $array
	 * @param bool $strict
	 *
	 * @return mixed
	 */
	public static function removeOneFromArray( $key, $array, $strict = false )
	{
		$keys = array_keys( $array );
		$pos = array_search( strtolower( $key ), array_change_key_case( $keys, CASE_LOWER ), $strict );
		if ( false !== $pos )
		{
			$realKey = $keys[$pos];
			unset( $array[$realKey] );

			return $array;
		}

		return $array;
	}

	/**
	 * @param        $list
	 * @param        $find
	 * @param string $delim
	 * @param bool   $strict
	 *
	 * @return bool
	 */
	public static function isInList( $list, $find, $delim = ',', $strict = false )
	{
		return ( false !== array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict ) );
	}

	/**
	 * @param        $list
	 * @param        $find
	 * @param string $delim
	 * @param bool   $strict
	 *
	 * @return mixed
	 */
	public static function findInList( $list, $find, $delim = ',', $strict = false )
	{
		return array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
	}

	/**
	 * @param        $list
	 * @param        $find
	 * @param string $delim
	 * @param bool   $strict
	 *
	 * @return string
	 */
	public static function addOnceToList( $list, $find, $delim = ',', $strict = false )
	{
		if ( empty( $list ) )
		{
			$list = $find;

			return $list;
		}
		$pos = array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
		if ( false !== $pos )
		{
			return $list;
		}
		$fieldarr = array_map( 'trim', explode( $delim, $list ) );
		$fieldarr[] = $find;

		return implode( $delim, array_values( $fieldarr ) );
	}

	/**
	 * @param        $list
	 * @param        $find
	 * @param string $delim
	 * @param bool   $strict
	 *
	 * @return string
	 */
	public static function removeOneFromList( $list, $find, $delim = ',', $strict = false )
	{
		$pos = array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
		if ( false === $pos )
		{
			return $list;
		}
		$fieldarr = array_map( 'trim', explode( $delim, $list ) );
		unset( $fieldarr[$pos] );

		return implode( $delim, array_values( $fieldarr ) );
	}

	public static function boolval( $var )
	{
		if (is_bool($var)) {
			return $var;
		}

		return filter_var( mb_strtolower( strval( $var ) ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @param $iv_len
	 *
	 * @return string
	 */
	private static function get_rnd_iv( $iv_len )
	{
		$iv = '';
		while ( $iv_len-- > 0 )
		{
			$iv .= chr( mt_rand() & 0xff );
		}

		return $iv;
	}

	/**
	 * @param     $plain_text
	 * @param     $password
	 * @param int $iv_len
	 *
	 * @return string
	 */
	public static function encryptCreds( $plain_text, $password, $iv_len = 16 )
	{
		$plain_text .= "\x13";
		$n = strlen( $plain_text );
		if ( $n % 16 )
		{
			$plain_text .= str_repeat( "\0", 16 - ( $n % 16 ) );
		}
		$i = 0;
		$enc_text = static::get_rnd_iv( $iv_len );
		$iv = substr( $password ^ $enc_text, 0, 512 );
		while ( $i < $n )
		{
			$block = substr( $plain_text, $i, 16 ) ^ pack( 'H*', md5( $iv ) );
			$enc_text .= $block;
			$iv = substr( $block . $iv, 0, 512 ) ^ $password;
			$i += 16;
		}

		return strtr( base64_encode( $enc_text ), '+/=', '-_,' );
	}

	/**
	 * @param     $enc_text
	 * @param     $password
	 * @param int $iv_len
	 *
	 * @return mixed
	 */
	public static function decryptCreds( $enc_text, $password, $iv_len = 16 )
	{
		$enc_text = base64_decode( strtr( $enc_text, '-_,', '+/=' ) );
		$n = strlen( $enc_text );
		$i = $iv_len;
		$plain_text = '';
		$iv = substr( $password ^ substr( $enc_text, 0, $iv_len ), 0, 512 );
		while ( $i < $n )
		{
			$block = substr( $enc_text, $i, 16 );
			$plain_text .= $block ^ pack( 'H*', md5( $iv ) );
			$iv = substr( $block . $iv, 0, 512 ) ^ $password;
			$i += 16;
		}

		return preg_replace( '/\\x13\\x00*$/', '', $plain_text );
	}

	/**
	 * @param $enc_text
	 *
	 * @return string
	 */
	public static function decryptPassword( $enc_text )
	{
		$enc_text = base64_decode( strtr( $enc_text, '-_,', '+/=' ) );

		return $enc_text;
	}

	/**
	 * @param $tracker
	 */
	public static function markTimeStart( $tracker )
	{
		if ( isset( $GLOBALS['TRACK_TIME'] ) && isset( $GLOBALS[$tracker] ) )
		{
			$GLOBALS[$tracker . '_TIMER'] = microtime( true );
		}
	}

	/**
	 * @param $tracker
	 */
	public static function markTimeStop( $tracker )
	{
		if ( isset( $GLOBALS[$tracker . '_TIMER'] ) && isset( $GLOBALS['TRACK_TIME'] ) && isset( $GLOBALS[$tracker] ) )
		{
			$GLOBALS[$tracker] = $GLOBALS[$tracker] + ( microtime( true ) - $GLOBALS[$tracker . '_TIMER'] );
			$GLOBALS[$tracker . '_TIMER'] = null;
		}
	}

	/**
	 * @param string $pre_log
	 */
	public static function logTimers( $pre_log = '' )
	{
		$track = static::getTimers();
		if ( $track )
		{
			//error_log(print_r($pre_log, true) . PHP_EOL . print_r($track, true));
		}
	}

	/**
	 * @return array|null
	 */
	public static function getTimers()
	{
		$track = null;
		if ( isset( $GLOBALS['TRACK_TIME'] ) )
		{
			$track = array(
				'process_time' => isset( $GLOBALS['API_TIME'] ) ? $GLOBALS['API_TIME'] : '',
				'db_time'      => isset( $GLOBALS['DB_TIME'] ) ? $GLOBALS['DB_TIME'] : '',
				'session_time' => isset( $GLOBALS['SESS_TIME'] ) ? $GLOBALS['SESS_TIME'] : '',
				'cfg_time'     => isset( $GLOBALS['CFG_TIME'] ) ? $GLOBALS['CFG_TIME'] : '',
				'ws_time'      => isset( $GLOBALS['WS_TIME'] ) ? $GLOBALS['WS_TIME'] : ''
			);
		}

		return $track;
	}

	/**
	 * @return string
	 */
	public static function getAbsoluteURLFolder()
	{
		$_url = 'http' . ( 'on' == FilterInput::server( 'HTTPS' ) ? 's' : null ) . '://' . FilterInput::server( 'HTTP_HOST' )
				. rtrim( dirname( $_SERVER['REQUEST_URI'] ), '/\\' ) . '/';

		return $_url;
	}
}
