<?php

/**
 * @category   DreamFactory
 * @package    DreamFactory
 * @subpackage Utilities
 * @copyright  Copyright (c) 2009 - 2012, DreamFactory (http://www.dreamfactory.com)
 * @license    http://www.dreamfactory.com/license
 */

class Utilities
{

	// constants

	private static $_userId = null;

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
			"UTF-8" ); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
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
						$current[$tag] =
							array( $current[$tag], $result ); //This will combine the existing item and the new item together to make an array
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

	public static function simpleArrayToXml( $array, $suppress_empty = false )
	{
		if ( !is_bool( $suppress_empty ) )
		{
			$suppress_empty = filter_var( $suppress_empty, FILTER_VALIDATE_BOOLEAN );
		}
		$xml = '';
		foreach ( $array as $key => $value )
		{
			$value = trim( $value, " " );
			if ( empty( $value ) and $suppress_empty )
			{
				continue;
			}
			$htmlvalue = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
			if ( $htmlvalue != $value )
			{
				$xml .= "\t" . "<$key>$htmlvalue</$key>\n";
			}
			else
			{
				$xml .= "\t" . "<$key>$value</$key>\n";
			}
		}

		return $xml;
	}

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
	 * @access public
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

	public static function arrayToXml( $root, $array, $level = 1, $format = true )
	{
		$xml = '';
		if ( static::isArrayNumeric( $array ) )
		{
			if ( !empty( $root ) )
			{
				if ( $format )
				{
					$xml .= str_repeat( "\t", $level - 1 );
				}
				$xml .= "<" . static::pluralize( $root ) . ">";
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
				$xml .= "</" . static::pluralize( $root ) . ">";
				if ( $format )
				{
					$xml .= "\n";
				}
			}
		}
		else if ( static::isArrayAssociative( $array ) )
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
						$htmlvalue = htmlspecialchars( $array, ENT_QUOTES, 'UTF-8' );
						$xml .= $htmlvalue;
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

	public static function xmlToJson( $xmlstring )
	{
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xmlstring );
		if ( !$xml )
		{
			$xmlstr = explode( "\n", $xmlstring );
			$errstr = "[INVALIDREQUEST]: Invalid XML Data: ";
			foreach ( libxml_get_errors() as $error )
			{
				$errstr .= static::display_xml_error( $error, $xmlstr ) . "\n";
			}
			libxml_clear_errors();
			throw new Exception( $errstr );
		}

		return json_encode( (array)$xml );
	}

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

	public static function isInList( $list, $find, $delim = ',', $strict = false )
	{
		return ( false !== array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict ) );
	}

	public static function findInList( $list, $find, $delim = ',', $strict = false )
	{
		return array_search( $find, array_map( 'trim', explode( $delim, strtolower( $list ) ) ), $strict );
	}

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

	/*    function boolval($var)
		{
			if (is_bool($var)) {
				return $var;
			}

			return filter_var(mb_strtolower(strval($var)), FILTER_VALIDATE_BOOLEAN);
		}*/

	/** Checks a variable to see if it should be considered a boolean true or false.
	 *     Also takes into account some text-based representations of true or false,
	 *     such as 'false','N','yes','on','off', etc.
	 *
	 * @author Samuel Levy <sam+nospam@samuellevy.com>
	 *
	 * @param mixed $in     The variable to check
	 * @param bool  $strict If set to false, consider everything that is not false to
	 *                      be true.
	 *
	 * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
	 */
	public static function boolval( $in, $strict = true )
	{
		if ( is_bool( $in ) )
		{
			return $in;
		}
		$out = null;
		$in = ( is_string( $in ) ? strtolower( $in ) : $in );
		// if not strict, we only have to check if something is false
		if ( in_array( $in, array( 'false', 'no', 'n', '0', 'off', false, 0 ), true ) || !$in || empty( $in ) )
		{
			$out = false;
		}
		elseif ( $strict )
		{
			// if strict, check the equivalent true values
			if ( in_array( $in, array( 'true', 'yes', 'y', '1', 'on', true, 1 ), true ) )
			{
				$out = true;
			}
		}
		else
		{
			// not strict? let the regular php bool check figure it out (will
			//     largely default to true)
			$out = ( $in ? true : false );
		}

		return $out;
	}

	private static function get_rnd_iv( $iv_len )
	{
		$iv = '';
		while ( $iv_len-- > 0 )
		{
			$iv .= chr( mt_rand() & 0xff );
		}

		return $iv;
	}

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

	public static function decryptPassword( $enc_text )
	{
		$enc_text = base64_decode( strtr( $enc_text, '-_,', '+/=' ) );

		return $enc_text;
	}

	/**
	 * @todo this function needs to be revisited
	 */
	public static function sendResponse()
	{
//		$accepted = ( !empty( $_SERVER["HTTP_ACCEPT_ENCODING"] ) ) ? $_SERVER["HTTP_ACCEPT_ENCODING"] : '';
//
//		if ( headers_sent() )
//		{
//			$encoding = false;
//		}
//		elseif ( strpos( $accepted, 'gzip' ) !== false )
//		{
//			$encoding = true;
//		}
//		else
//		{
//			$encoding = false;
//		}

		//	IE 9 requires hoop for session cookies in iframes
		if ( !headers_sent() )
		{
			header( 'P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"' );
		}

		ob_end_flush();
	}

	/**
	 * @param $data
	 */
	public static function sendXmlResponse( $data )
	{
		/* gzip handling output if necessary */
		ob_start();
		ob_implicit_flush( 0 );

		header( "Content-type: application/xml" );
		echo "<?xml version=\"1.0\" ?>\n<dfapi>\n" . $data . "</dfapi>";
		self::sendResponse();
	}

	/**
	 * @param $data
	 */
	public static function sendJsonResponse( $data )
	{
		/* gzip handling output if necessary */
		ob_start();
		ob_implicit_flush( 0 );

		header( "Content-type: application/json" );
		echo $data;
		self::sendResponse();
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
				$data = static::array_key_lower( $data );
				$data = ( isset( $data['dfapi'] ) ) ? $data['dfapi'] : $data;
			}
		}

		return $data;
	}

	/* checks for post data and performs gunzip functions */
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

	public static function markTimeStart( $tracker )
	{
		if ( isset( $GLOBALS['TRACK_TIME'] ) && isset( $GLOBALS[$tracker] ) )
		{
			$GLOBALS[$tracker . '_TIMER'] = microtime( true );
		}
	}

	public static function markTimeStop( $tracker )
	{
		if ( isset( $GLOBALS[$tracker . '_TIMER'] ) && isset( $GLOBALS['TRACK_TIME'] ) && isset( $GLOBALS[$tracker] ) )
		{
			$GLOBALS[$tracker] = $GLOBALS[$tracker] + ( microtime( true ) - $GLOBALS[$tracker . '_TIMER'] );
			$GLOBALS[$tracker . '_TIMER'] = null;
		}
	}

	public static function logTimers( $pre_log = '' )
	{
		$track = static::getTimers();
		if ( $track )
		{
			//error_log(print_r($pre_log, true) . PHP_EOL . print_r($track, true));
		}
	}

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

	public static function getAbsoluteURLFolder()
	{
		$scriptFolder = ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' ) ) ? 'https://' : 'http://';
		$scriptFolder .= $_SERVER['HTTP_HOST'] . rtrim( dirname( $_SERVER['REQUEST_URI'] ), '/\\' ) . '/';

		return $scriptFolder;
	}
}
