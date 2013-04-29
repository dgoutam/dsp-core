<?php
/**
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
namespace Platform\Yii\Utility;

use Kisma\Core\Enums\OutputFormat;

/**
 * PiiScript
 * Yii javascript helpers
 */
class PiiScript
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string Used to mark script keys
	 */
	const Signature = '__script_callback__.';

	//********************************************************************************
	//* Methods
	//********************************************************************************

	/***
	 * Makes an array of key=>value pairs in an array.
	 *
	 * @param array   $options The options to use as a source
	 * @param int     $format
	 *
	 * @return mixed
	 */
	public static function encodeOptions( array $options = array(), $format = OutputFormat::JSON )
	{
		$_encodedOptions = null;

		switch ( $format )
		{
			case OutputFormat::JSON:
				$_encodedOptions = static::json_encode( $options );
				break;

			case OutputFormat::HTTP:
				foreach ( $options as $_key => $_value )
				{
					if ( !empty( $_value ) )
					{
						$_encodedOptions .= '&' . $_key . '=' . urlencode( $_value );
					}
				}
				break;

			default:
				$_encodedOptions = $options;
				break;
		}

		return $_encodedOptions;
	}

	/**
	 * JSON encodes a value that may contain Javascript anonymous functions
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function json_encode( $value )
	{
		return \preg_replace_callback(
			'/(?<=:)"function\((?:(?!}").)*}"/',
			function ( $string )
			{
				return str_replace( array( '\"', '\\n', '\\t', '\\r' ), array( '"', null, null, null ), substr( $string[0], 1, -1 ) );
			},
			\json_encode( $value )
		);
	}
}
