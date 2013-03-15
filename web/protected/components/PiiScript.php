<?php
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
	 * @param array $options The options to use as a source
	 * @param int   $format
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
