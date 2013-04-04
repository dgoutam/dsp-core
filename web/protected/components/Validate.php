<?php
use Kisma\Core\Utility\Option;

/**
 * Validate.php
 */
class Validate implements PageLocation
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string The CDN root
	 */
	const Cdn = '//ajax.aspnetcdn.com/ajax/jquery.validate/1.10.0/';

	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * Registers the needed CSS and JavaScript.
	 *
	 * @param string       $selector
	 * @param string|array $options
	 *
	 * @return \CClientScript The current app's ClientScript object
	 */
	public static function register( $selector, $options = array() )
	{
		//	Don't screw with div formatting...
		if ( null === Option::get( $options, 'error_placement' ) )
		{
			$options['error_placement'] = 'function(error,element){error.appendTo(element.parent("div"));}';
		}

		if ( null === Option::get( $options, 'highlight' ) )
		{
			$options['highlight']
				= <<<SCRIPT
function( element, errorClass ) {
	$(element).closest('div.control-group').addClass('error');
	$(element).addClass(errorClass);
}
SCRIPT;
		}

		if ( null === Option::get( $options, 'unhighlight' ) )
		{
			$options['unhighlight']
				= <<<SCRIPT
function( element, errorClass ) {
	$(element).closest('div.control-group').removeClass('error');
	$(element).removeClass(errorClass);
}
SCRIPT;
		}

		//	Get the options...
		$_scriptOptions = is_string( $options ) ? $options : PiiScript::encodeOptions( $options );

		$_validate
			= <<<JS
jQuery.validator.addMethod(
	"phoneUS",
	function(phone_number, element) {
		phone_number = phone_number.replace(/\s+/g, "");
		return this.optional(element) || phone_number.length > 9 && phone_number.match(/^(1[\s\.-]?)?(\([2-9]\d{2}\)|[2-9]\d{2})[\s\.-]?[2-9]\d{2}[\s\.-]?\d{4}$/);
	},
	"Please specify a valid phone number"
);

jQuery.validator.addMethod(
	"postalCode",
	function(postalcode, element) {
		return this.optional(element) || postalcode.match(/(^\d{5}(-\d{4})?$)|(^[ABCEGHJKLMNPRSTVXYabceghjklmnpstvxy]{1}\d{1}[A-Za-z]{1} ?\d{1}[A-Za-z]{1}\d{1})$/);
	},
	"Please specify a valid postal/zip code"
);

var	_validator = $("{$selector}").validate({$_scriptOptions});
JS;

		//	Register the jquery plugin
		Pii::scriptFile(
			array(
				 self::Cdn . 'jquery.validate.min.js',
				 self::Cdn . 'additional-methods.min.js',
			),
			self::End
		);

		//	Add to the page load
		return Pii::script( '#df-jquery-validate.validator.addMethod#', $_validate );
	}
}