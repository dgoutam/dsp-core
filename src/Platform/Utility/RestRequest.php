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
 * REST Request Utilities
 */
class RestRequest
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	// xml helper functions

	/**
	 * @return array|mixed|null
	 * @throws \Exception
	 */
	public static function getPostDataAsArray()
	{
		$_postData = static::getPostData();
		$_data = null;
		if ( !empty( $_postData ) )
		{
			$_contentType = ( isset( $_SERVER['CONTENT_TYPE'] ) ) ? $_SERVER['CONTENT_TYPE'] : '';
			if ( !empty( $_contentType ) )
			{
				if ( false !== stripos( $_contentType, '/json' ) )
				{
					$_data = DataFormat::jsonToArray( $_postData );
				}
				elseif ( false !== stripos( $_contentType, 'application/x-www-form-urlencoded' ) )
				{
					parse_str( $_postData, $_data );
				}
				elseif ( false !== stripos( $_contentType, '/xml' ) )
				{ // application/xml or text/xml
					$_data = DataFormat::xmlToArray( $_postData );
				}
			}
			if ( !isset( $_data ) )
			{
				try
				{
					$_data = DataFormat::jsonToArray( $_postData );
				}
				catch ( \Exception $ex )
				{
					try
					{
						$_data = DataFormat::xmlToArray( $_postData );
					}
					catch ( \Exception $ex )
					{
						throw new \Exception( 'Invalid Format Requested. ' . $ex->getMessage() );
					}
				}
			}
			if ( !empty( $_data ) && is_array( $_data ) )
			{
				$_data = DataFormat::arrayKeyLower( $_data );
				$_data = ( isset( $_data['dfapi'] ) ) ? $_data['dfapi'] : $_data;
			}
		}

		return $_data;
	}

	/**
	 * Checks for post data and performs gunzip functions
	 *
	 * @return string
	 */
	public static function getPostData()
	{
		if ( 'gzip' === FilterInput::server( 'HTTP_CONTENT_ENCODING' ) )
		{
			// Until PHP 6.0 is installed where gzunencode() is supported we must use the temp file support
			$data = "";
			$gzfp = gzopen( 'php://input', 'r' );

			while ( !gzeof( $gzfp ) )
			{
				$data .= gzread( $gzfp, 1024 );
			}
			gzclose( $gzfp );
		}
		else if ( isset( $_POST ) && !empty( $_POST ) )
		{
			$data = $_POST;
		}
		else
		{
			$data = file_get_contents( 'php://input' );
		}

		return $data;
	}
}
