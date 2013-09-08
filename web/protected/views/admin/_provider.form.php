<?php
/**
 * _provider.form.php
 *
 * @var WebController $this
 * @var array         $schema
 * @var array         $resource
 * @var array         $_formOptions Provided by includer
 * @var array         $errors       Errors if any
 */
use DreamFactory\Yii\Utility\BootstrapForm;
use Kisma\Core\Utility\Bootstrap;
use Kisma\Core\Utility\Option;

$this->setBreadcrumbs(
	array(
		 'Providers' => 'providers/index',
		 'Update'    => false,
	)
);

//@TODO error handling from resource request
$_errors = isset( $errors ) ? Option::clean( $errors ) : array();

if ( !empty( $_errors ) )
{
	$_headline = ( isset( $alertMessage ) ? $alertMessage : 'Sorry pal...' );
	$_messages = null;

	foreach ( $_errors as $_error )
	{
		foreach ( $_error as $_message )
		{
			$_messages .= '<p>' . $_message . '</p>';
		}
	}

	echo <<<HTML
<div class="alert alert-error alert-block alert-fixed fade in" data-alert="alert">
	<strong>{$_headline}</strong>
	{$_messages}</div>
HTML;
}

$_hashedId = $this->hashId( $resource['id'] );

$_form = new BootstrapForm(
	Bootstrap::Horizontal,
	array(
		 'id'             => 'update-provider',
		 'method'         => 'POST',
		 'x_editable_url' => '/admin/provider/update',
		 'x_editable_pk'  => $_hashedId,
	)
);

$_newRecord = ( null === ( $_resourceId = Option::get( $resource, 'id' ) ) );

$_form->setFormData( $resource );

$_fields = array(
	'Basic Settings' => array(
		'api_name'      => array(
			'type'        => 'text',
			'class'       => $_newRecord ? 'required' : 'uneditable-input',
			'placeholder' => 'How to address this provider via REST',
			'hint'        => 'The URI portion to be used when calling this provider. For example: "github", or "facebook".',
			'maxlength'   => 64,
		),
		'provider_name' => array(
			'type'      => 'text',
			'class'     => 'required',
			'hint'      => 'The real name, or "display" name for this provider.',
			'maxlength' => 64,
		),
	),
	'Configuration'  => $schema,
	'Metrics'        => array(
		'created_date'       => array(
			'type'  => 'text',
			'class' => 'uneditable-input',
		),
		'last_modified_date' => array(
			'type'  => 'text',
			'class' => 'uneditable-input',
		),
	),
);
?>
<h2 style="margin-bottom: 0"><?php echo $_newRecord ? 'New Provider' : $resource['provider_name']; ?>
	<small>Edit Provider
	</small>
</h2>

<form id="update-platform" method="POST" class="form-horizontal tab-form" action>
	<?php $_form->renderFields( $_fields ); ?>
	<div class="form-actions">
		<button class="btn btn-secondary">Cancel</button>
		<button class="btn btn-primary">Save</button>
	</div>
</form>

<script type="text/javascript">
jQuery(function($) {
	$('form#form-button-bar').on('click', 'button', function(e) {
		e.preventDefault();
		var _cmd = $(this).attr('id').replace('-instance', '');
		var _id = $(this).data('row-id');

		if (!confirm('Do you REALLY REALLY wish to perform this action? It is irreversible!')) {
			return false;
		}

		$.ajax({url:  '/admin/provider/update',
			method:   'POST',
			dataType: 'json',
			data:     {id: _id, action: _cmd },
			async:    false,
			success:  function(data) {
				if (data && data.success) {
					alert('Your provider request has been queued.');
				}
				else {
					alert('There was an error: ' + data.details.message);
				}
			}
		});

		return false;
	});

	$('.legend-button-bar a').on('click', function(e) {
		alert('Not available');
	});

	$.fn.editable.defaults.mode = 'inline';
	$('a.x-editable').editable({
		url:         '/admin/provider/update',
		emptytext:   'None',
		ajaxOptions: {
			dataType: 'json'
		},
		error:       function(errors) {
			var _data = JSON.parse(errors.responseText);
			return _data.details.message;
		}
	});
});
</script>
