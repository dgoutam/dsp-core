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

/**
 * Data Format Utilities
 */
class DataFormat
{

	//*************************************************************************
	//	Methods
	//*************************************************************************

	public static function reformatData( $data, $current_format = null, $desired_format = null )
	{
		if ( $current_format != $desired_format )
		{
			switch ( $current_format )
			{
				case 'json':
					switch ( $desired_format )
					{
						case 'xml':
							$data = static::arrayToXml( '', static::jsonToArray( $data ) );
							break;
						default:
							$data = static::jsonToArray( $data );
							break;
					}
					break;
				case 'xml':
					switch ( $desired_format )
					{
						case 'json':
							$data = static::xmlToJson( '', $data );
							break;
						default:
							$data = static::xmlToArray( $data );
							break;
					}
					break;
				default:
					switch ( $desired_format )
					{
						case 'json':
							$data = json_encode( $data );
							break;
						case 'xml':
							$data = static::arrayToXml( '', $data );
							break;
						default:
							break;
					}
					break;
			}
		}

		return $data;
	}

	/**
	 * @param $vector
	 *
	 * @return array
	 */
	public static function reorgFilePostArray( $vector )
	{
		$result = array();
		foreach ( $vector as $key1 => $value1 )
		{
			foreach ( $value1 as $key2 => $value2 )
			{
				$result[$key2][$key1] = $value2;
			}
		}

		return $result;
	}

	// xml helper functions

	/**
	 * xml2array() will convert the given XML text to an array in the XML structure.
	 * Link: http://www.bin-co.com/php/scripts/xml2array/
	 * Arguments : $contents - The XML text
	 *             $get_attributes - 1 or 0. If this is 1 the function will
	 *                               get the attributes as well as the tag values
	 *                               - this results in a different array structure in the return value.
	 *             $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array structure.
	 *                         For 'tag', the tags are given more importance.
	 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
	 * Examples: $array =  xml2array(file_get_contents('feed.xml'));
	 *           $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));
	 */
	public static function xmlToArray( $contents, $get_attributes = 0, $priority = 'tag' )
	{
		if ( empty( $contents ) )
		{
			return null;
		}

		if ( !function_exists( 'xml_parser_create' ) )
		{
			//print "'xml_parser_create()' function not found!";
			return null;
		}

		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create( '' );
		xml_parser_set_option( $parser,
							   XML_OPTION_TARGET_ENCODING,
							   "UTF-8"
		); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct( $parser, trim( $contents ), $xml_values );
		xml_parser_free( $parser );

		if ( !$xml_values )
		{
			return null;
		} //Hmm...

		//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();

		$current = & $xml_array; //Reference

		//Go through the tags.
		$repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
		foreach ( $xml_values as $data )
		{
			unset( $attributes, $value ); //Remove existing values, or there will be trouble

			//This command will extract these variables into the foreach scope
			// tag(string) , type(string) , level(int) , attributes(array) .
			extract( $data ); //We could use the array by itself, but this cooler.

			$result = array();
			$attributes_data = array();

			if ( isset( $value ) )
			{
				if ( $priority == 'tag' )
				{
					$result = $value;
				}
				else
				{
					$result['value'] = $value;
				} //Put the value in a assoc array if we are in the 'Attribute' mode
			}

			//Set the attributes too.
			if ( isset( $attributes ) and $get_attributes )
			{
				foreach ( $attributes as $attr => $val )
				{
					if ( $priority == 'tag' )
					{
						$attributes_data[$attr] = $val;
					}
					else
					{
						$result['attr'][$attr] = $val;
					} //Set all the attributes in a array called 'attr'
				}
			}

			//See tag status and do the needed.
			/** @var string $type */
			/** @var string $tag */
			/** @var string $level */
			if ( $type == "open" )
			{ //The starting of the tag '<tag>'
				$parent[$level - 1] = & $current;
				if ( !is_array( $current ) or ( !in_array( $tag, array_keys( $current ) ) ) )
				{ //Insert New tag
					$current[$tag] = $result;
					if ( $attributes_data )
					{
						$current[$tag . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level] = 1;

					$current = & $current[$tag];
				}
				else
				{ //There was another element with the same tag name

					if ( isset( $current[$tag][0] ) )
					{ //If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level]++;
					}
					else
					{ //This section will make the value an array if multiple tags with the same name appear together
						$current[$tag]
							= array( $current[$tag], $result ); //This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag . '_' . $level] = 2;

						if ( isset( $current[$tag . '_attr'] ) )
						{ //The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset( $current[$tag . '_attr'] );
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current = & $current[$tag][$last_item_index];
				}
			}
			elseif ( $type == "complete" )
			{ //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if ( !isset( $current[$tag] ) )
				{ //New Key
					$current[$tag] = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ( $priority == 'tag' and $attributes_data )
					{
						$current[$tag . '_attr'] = $attributes_data;
					}
				}
				else
				{ //If taken, put all things inside a list(array)
					if ( isset( $current[$tag][0] ) and is_array( $current[$tag] ) )
					{ //If it is already an array...

						// ...push the new element into that array.
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

						if ( $priority == 'tag' and $get_attributes and $attributes_data )
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level]++;
					}
					else
					{ //If it is not an array...
						$current[$tag] = array( $current[$tag], $result ); //...Make it an array using using the existing value and the new value
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ( $priority == 'tag' and $get_attributes )
						{
							if ( isset( $current[$tag . '_attr'] ) )
							{ //The attribute of the last(0th) tag must be moved as well

								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset( $current[$tag . '_attr'] );
							}

							if ( $attributes_data )
							{
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
					}
				}
			}
			elseif ( $type == 'close' )
			{ //End of tag '</tag>'
				$current = & $parent[$level - 1];
			}
		}

		return $xml_array;
	}

	/**
	 * @param      $array
	 * @param bool $suppress_empty
	 *
	 * @return string
	 */
	public static function simpleArrayToXml( $array, $suppress_empty = false )
	{
		$xml = '';
		foreach ( $array as $key => $value )
		{
			$value = trim( $value, " " );
			if ( empty( $value ) and Utilities::boolval( $suppress_empty ) )
			{
				continue;
			}
			$htmlValue = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			if ( $htmlValue != $value )
			{
				$xml .= "\t" . "<$key>$htmlValue</$key>\n";
			}
			else
			{
				$xml .= "\t" . "<$key>$value</$key>\n";
			}
		}

		return $xml;
	}

	/**
	 * @param      $root
	 * @param      $array
	 * @param int  $level
	 * @param bool $format
	 *
	 * @return string
	 */
	public static function arrayToXml( $root, $array, $level = 1, $format = true )
	{
		$xml = '';
		if ( Utilities::isArrayNumeric( $array ) )
		{
			if ( !empty( $root ) )
			{
				if ( $format )
				{
					$xml .= str_repeat( "\t", $level - 1 );
				}
				$xml .= "<" . Utilities::pluralize( $root ) . ">";
				if ( $format )
				{
					$xml .= "\n";
				}
			}
			foreach ( $array as $key => $value )
			{
				$xml .= self::arrayToXml( $root, $value, $level + 1, $format );
			}
			if ( !empty( $root ) )
			{
				if ( $format )
				{
					$xml .= "\n" . str_repeat( "\t", $level - 1 );
				}
				$xml .= "</" . Utilities::pluralize( $root ) . ">";
				if ( $format )
				{
					$xml .= "\n";
				}
			}
		}
		else if ( Utilities::isArrayAssociative( $array ) )
		{
			if ( !empty( $root ) )
			{
				if ( $format )
				{
					$xml .= str_repeat( "\t", $level - 1 );
				}
				$xml .= "<$root>";
				if ( $format )
				{
					$xml .= "\n";
				}
			}
			foreach ( $array as $key => $value )
			{
				$xml .= self::arrayToXml( $key, $value, $level + 1, $format );
			}
			if ( !empty( $root ) )
			{
				if ( $format )
				{
					$xml .= "\n" . str_repeat( "\t", $level - 1 );
				}
				$xml .= "</$root>";
				if ( $format )
				{
					$xml .= "\n";
				}
			}
		}
		else
		{ // empty array or not an array
			if ( !empty( $root ) )
			{
				if ( $format )
				{
					$xml .= str_repeat( "\t", $level - 1 );
				}
				$xml .= "<$root>";
				if ( !empty( $array ) )
				{
					if ( trim( $array ) != '' )
					{
						$htmlValue = htmlspecialchars( $array, ENT_QUOTES, 'UTF-8' );
						$xml .= $htmlValue;
					}
				}
				$xml .= "</$root>";
				if ( $format )
				{
					$xml .= "\n";
				}
			}
		}

		return $xml;
	}

	/**
	 * @param $xml_string
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function xmlToJson( $xml_string )
	{
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_string );
		if ( !$xml )
		{
			$xmlStr = explode( "\n", $xml_string );
			$errStr = "[INVALIDREQUEST]: Invalid XML Data: ";
			foreach ( libxml_get_errors() as $error )
			{
				$errStr .= static::display_xml_error( $error, $xmlStr ) . "\n";
			}
			libxml_clear_errors();
			throw new Exception( $errStr );
		}

		return json_encode( (array)$xml );
	}

	/**
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public static function getPostDataAsArray()
	{
		$postdata = static::getPostData();
		$data = null;
		if ( !empty( $postdata ) )
		{
			$content_type = ( isset( $_SERVER['CONTENT_TYPE'] ) ) ? $_SERVER['CONTENT_TYPE'] : '';
			if ( !empty( $content_type ) )
			{
				if ( false !== stripos( $content_type, '/json' ) )
				{
					$data = static::jsonToArray( $postdata );
				}
				elseif ( false !== stripos( $content_type, '/xml' ) )
				{ // application/xml or text/xml
					$data = static::xmlToArray( $postdata );
				}
			}
			if ( !isset( $data ) )
			{
				try
				{
					$data = static::jsonToArray( $postdata );
				}
				catch ( Exception $ex )
				{
					try
					{
						$data = static::xmlToArray( $postdata );
					}
					catch ( Exception $ex )
					{
						throw new Exception( 'Invalid Format Requested' );
					}
				}
			}
			if ( !empty( $data ) && is_array( $data ) )
			{
				$data = Utilities::array_key_lower( $data );
				$data = ( isset( $data['dfapi'] ) ) ? $data['dfapi'] : $data;
			}
		}

		return $data;
	}

	/* checks for post data and performs gunzip functions */
	/**
	 * @return string
	 */
	public static function getPostData()
	{
		$content_enc = ( !empty( $_SERVER["HTTP_CONTENT_ENCODING"] ) ) ? $_SERVER["HTTP_CONTENT_ENCODING"] : '';
		if ( $content_enc === 'gzip' )
		{
			// Until PHP 6.0 is installed where gzunencode() is supported
			// we must use the temp file support
			$data = "";
			$gzfp = gzopen( 'php://input', "r" );
			while ( !gzeof( $gzfp ) )
			{
				$data .= gzread( $gzfp, 1024 );
			}
			gzclose( $gzfp );
		}
		else
		{
			$data = file_get_contents( 'php://input' );
		}

		return $data;
	}

	/**
	 * @param $error
	 * @param $xml
	 *
	 * @return string
	 */
	public static function display_xml_error( $error, $xml )
	{
		$return = $xml[$error->line - 1] . "\n";
		$return .= str_repeat( '-', $error->column ) . "^\n";

		switch ( $error->level )
		{
			case LIBXML_ERR_WARNING:
				$return .= "Warning $error->code: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "Error $error->code: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "Fatal Error $error->code: ";
				break;
		}

		$return .= trim( $error->message ) .
				   "\n  Line: $error->line" .
				   "\n  Column: $error->column";

		if ( $error->file )
		{
			$return .= "\n  File: $error->file";
		}

		return "$return\n\n--------------------------------------------\n\n";
	}

	/**
	 * @param $xmlString
	 *
	 * @return null|SimpleXMLElement
	 * @throws Exception
	 */
	public static function xmlToObject( $xmlString )
	{
		if ( empty( $xmlString ) )
		{
			return null;
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xmlString );
		if ( !$xml )
		{
			$xmlstr = explode( "\n", $xmlString );
			$errstr = "[INVALIDREQUEST]: Invalid XML Data: ";
			foreach ( libxml_get_errors() as $error )
			{
				$errstr .= static::display_xml_error( $error, $xmlstr ) . "\n";
			}
			libxml_clear_errors();
			throw new Exception( $errstr );
		}

		return $xml;
	}

//        $postarray = json_decode(json_encode((array) $xml), true);

	/**
	 * @param $json
	 *
	 * @return mixed|null
	 * @throws Exception
	 */
	public static function jsonToArray( $json )
	{
		if ( empty( $json ) )
		{
			return null;
		}
		$array = json_decode( $json, true );
		switch ( json_last_error() )
		{
			case JSON_ERROR_NONE:
				$error = '';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Invalid or malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			case JSON_ERROR_DEPTH:
				$error = 'The maximum stack depth has been exceeded';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Control character error, possibly incorrectly encoded';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON';
				break;
			default:
				$error = 'Unknown error';
		}
		if ( !empty( $error ) )
		{
			throw new Exception( 'JSON Error: ' . $error );
		}

		return $array;
	}

	/**
	 * Build the array from data.
	 *
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public static function export( $data )
	{
		if ( is_object( $data ) )
		{
			// allow embedded export method for specific export
			if ( method_exists( $data, 'export' ) )
			{
				$data = $data->export();
			}
			else
			{
				$data = get_object_vars( $data );
			}
		}
		if ( is_array( $data ) === false )
		{
			return $data;
		}
		$output = array();
		foreach ( $data as $key => $value )
		{
			$output[$key] = self::export( $value );
		}

		return $output;
	}

	/**
	 * @param mixed     $data Could be object, array, or simple type
	 * @param bool $prettyPrint
	 *
	 * @return null|string
	 */
	public static function jsonEncode( $data, $prettyPrint = false )
	{
		$data = static::export( $data );
		if ( version_compare( PHP_VERSION, '5.4', '>=' ) )
		{
			return json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}
		else
		{
			$json = str_replace( '\/', '/', json_encode( $data ) );
		}

		if ( !$prettyPrint )
		{
			return $json;
		}

		// make it look good
		$tokens = preg_split( '|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE );
		$result = null;
		$indentTotal = 0;
		$lineBreak = "\n";
		$indent = '    ';
		$indentLine = false;
		foreach ( $tokens as $token )
		{
			if ( $token == '' )
			{
				continue;
			}
			$preText = str_repeat( $indent, $indentTotal );
			if ( !$indentLine && ( $token == '{' || $token == '[' ) )
			{
				$indentTotal++;
				if ( ( $result != '' ) && ( $result[( strlen( $result ) - 1 )] == $lineBreak ) )
				{
					$result .= $preText;
				}
				$result .= $token . $lineBreak;
			}
			elseif ( !$indentLine && ( $token == '}' || $token == ']' ) )
			{
				$indentTotal--;
				$preText = str_repeat( $indent, $indentTotal );
				$result .= $lineBreak . $preText . $token;
			}
			elseif ( !$indentLine && $token == ',' )
			{
				$result .= $token . $lineBreak;
			}
			else
			{
				$result .= ( $indentLine ? '' : $preText ) . $token;
				if ( ( substr_count( $token, '"' ) - substr_count( $token, '\"' ) ) % 2 != 0 )
				{
					$indentLine = !$indentLine;
				}
			}
		}

		return $result;
	}

}
