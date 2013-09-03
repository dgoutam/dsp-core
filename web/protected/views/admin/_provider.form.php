<?php
use DreamFactory\Yii\Utility\BootstrapForm;
use Kisma\Core\Utility\Bootstrap;

/**
 * _provider.form.php
 *
 * @var WebController $this
 * @var array         $schema
 * @var array         $resource
 * @var array         $_formOptions Provided by includer
 */
$this->setBreadcrumbs(
	array(
		 'Providers' => 'providers/index',
		 'Update'    => false,
	)
);

$_errors = null;

if ( !empty( $resource ) )
{
	//@TODO error handling from resource request
	$_errors = null;
	$_headline = ( isset( $alertMessage ) ? $alertMessage : 'Sorry pal...' );

	if ( !empty( $_errors ) )
	{
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

$_form->setFormData( $resource );

$_fields = array(
	'Configuration' => $schema,
);
?>
<div class="row-fluid" style="border-bottom:1px solid #ddd">
	<div class="span8">
		<h2 style="margin-bottom: 0">Provider
			<small><?php echo 'ProviderName'; ?></small>
		</h2>
	</div>
	<div class="span4" style="margin-top:10px">
		<div class="pull-right" style="display: inline-block;">
			<form method="POST" id="form-button-bar" style="display:inline;">
				<input type="hidden" name="backup_instance" value="0">
				<button data-row-id="<?php echo $_hashedId; ?>" class="btn btn-success" id="backup-instance">Backup</button>
				<input type="hidden" name="restore_instance" value="0">
				<button data-row-id="<?php echo $_hashedId; ?>" class="btn btn-info" id="restore-instance">Restore</button>
				<input type="hidden" name="wipe_instance" value="0">
				<button data-row-id="<?php echo $_hashedId; ?>" class="btn btn-warning" id="wipe-instance">Wipe</button>
				<input type="hidden" name="delete_instance" value="0">
				<button data-row-id="<?php echo $_hashedId; ?>" class="btn btn-danger" id="delete-instance">Delete</button>
			</form>
		</div>
	</div>
</div>

<div class="row-fluid">
	<div class="span12">
		<form id="update-platform" method="POST" class="form-horizontal tab-form" action>
			<?php $_form->renderFields( $_fields ); ?>
		</form>
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
