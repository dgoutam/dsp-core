<?php
/**
 * _provider.form.php
 *
 * @var WebController           $this
 * @var array                   $schema
 * @var BasePlatformSystemModel $model
 * @var array                   $_formOptions Provided by includer
 * @var array                   $errors       Errors if any
 * @var string                  $resourceName The name of this resource (i.e. App, AppGroup, etc.) Essentially the model name
 * @var string                  $displayName
 */
use DreamFactory\Platform\Yii\Models\BasePlatformSystemModel;
use DreamFactory\Yii\Utility\BootstrapForm;
use Kisma\Core\Utility\Bootstrap;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

//@TODO error handling from resource request
$_errors = isset( $errors ) ? Option::clean( $errors ) : array();
$_update = !$model->isNewRecord;

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

$_hashedId = $model->isNewRecord ? null : $this->hashId( $model->id );

$_form = new BootstrapForm(
	Bootstrap::Horizontal,
	array(
		 'id'             => 'update-resource',
		 'method'         => 'POST',
		 'x_editable_url' => '/admin/' . $resourceName . '/update',
		 'x_editable_pk'  => $_hashedId,
		 'prefix'         => $resourceName,
	)
);

$_form->setFormData( $model );

$_fields = array(
	'Basic Settings' => array(
		'api_name'      => array(
			'type'        => 'text',
			'class'       => !$_update ? 'required' : 'uneditable-input',
			'placeholder' => 'How to address this provider via REST',
			'hint'        => 'The URI portion to be used when calling this provider. For example: "github", or "facebook".',
			'maxlength'   => 64,
		),
		'provider_name' => array(
			'type'      => 'text',
			'class'     => 'required' . ( $_update ? ' x-editable' : null ),
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
<div class="row-fluid" style="border-bottom:1px solid #ddd">
	<div class="pull-right">
		<h2 style="margin-bottom: 0"><?php echo $displayName ?>
			<small><?php echo( $_update ? 'Edit' : 'New' ); ?></small>
		</h2>
	</div>
</div>

<div class="row-fluid">
	<div class="span12">
		<form id="update-platform" method="POST" class="form-horizontal tab-form" action>
			<?php $_form->renderFields( $_fields ); ?>
		</form>
	</div>
</div>

<div class="row-fluid">
	<div class="span12" style="margin-top:10px">
		<div class="pull-right" style="display: inline-block;">
			<form method="POST" id="form-button-bar" style="display:inline;">
				<button class="btn btn-success btn-primary" id="save-resource"> Save</button>
				<button class="btn btn-danger" id="delete-instance"> Delete</button>
			</form>
		</div>
	</div>
</div>

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
				} else {
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
