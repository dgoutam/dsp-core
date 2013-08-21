<?php
/**
 * _provider.form.php
 *
 * @var WebController                             $this
 * @var array                                          $_formOptions
 * @var DreamFactory\Fabric\Yii\Models\Deploy\Instance $model
 */
use Cerberus\Yii\Controllers\PlatformController;
use DreamFactory\Fabric\Yii\Models\Deploy\Vendor;
use DreamFactory\Yii\Utility\BootstrapForm;
use DreamFactory\Yii\Utility\Pii;
use DreamFactory\Yii\Utility\Validate;
use Kisma\Core\Utility\Bootstrap;

$this->setBreadcrumbs(
	array(
		 'Platforms' => 'platform/index',
		 'Update'    => false,
	)
);


$_errors = null;

if ( isset( $model ) )
{
	$_errors = $model->getErrors();
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
		<strong>{$_headline}</strong> {$_messages}
	</div>
HTML;
	}
}

//	Set up validation...
$_rules = array(
	//	Validation Rules
	'rules' => array(),
);

Validate::register( 'form#update-platform', $_rules );

$_hashedId = $this->hashId( $model->id );

$_form = new BootstrapForm(
	Bootstrap::Horizontal,
	array(
		 'id'             => 'update-platform',
		 'method'         => 'POST',
		 'x_editable_url' => '/dashboard/platform',
		 'x_editable_pk'  => $_hashedId,
	),
	$modelName = str_replace( '\\', '_', get_class( $model ) )
);

$_form->setFormData( $model->getAttributes() );

$_fields = array(
	'Basics'     => array(
		'instance_name_text' => array( 'class' => 'input-xlarge required x-editable', 'label' => 'Instance Name' ),
		'vendor_id'          => array(
			'type'     => 'select',
			'class'    => 'input-large',
			'disabled' => 'disabled',
			'label'    => 'Vendor',
			'value'    => $model->vendor_id,
			'data'     => Vendor::listData( 'vendor_name_text' ),
		),
		'vendor_image_id'    => array( 'disabled' => 'disabled', 'class' => 'input-large uneditable-input', 'label' => 'Vendor Image ID' ),
		'guest_location_nbr' => array(
			'type'     => 'select_enum',
			'disabled' => 'disabled',
			'class'    => 'input-large',
			'label'    => 'Guest Location',
			'value'    => $model->guest_location_nbr,
			'enum'     => '\\DreamFactory\\Fabric\\Enums\\Provisioners',
		),
		'instance_id_text'   => array( 'class' => 'input-large uneditable-input', 'label' => 'Instance ID', 'disabled' => 'disabled', ),
	),
	'Networking' => array(
		'public_host_text'  => array( 'class' => 'input-xxlarge required x-editable', 'label' => 'Public Host Name' ),
		'public_ip_text'    => array( 'class' => 'input-xlarge required x-editable', 'label' => 'Public IP Address' ),
		'private_host_text' => array( 'class' => 'input-xlarge x-editable', 'label' => 'Private Host Name' ),
		'private_ip_text'   => array( 'class' => 'input-xlarge x-editable', 'label' => 'Private IP Address' ),
	),
	'Database'   => array(
		'db_name_text'     => array( 'class' => 'input-xlarge uneditable-input borderless', 'disabled' => 'disabled', 'label' => 'Name' ),
		'db_user_text'     => array( 'class' => 'input-xxlarge uneditable-input borderless', 'disabled' => 'disabled', 'label' => 'User' ),
		'db_password_text' => array( 'class' => 'input-xxlarge uneditable-input borderless', 'disabled' => 'disabled', 'label' => 'Password' ),
	),
	'Storage'    => array(
		'storage_id_text' => array( 'class' => 'input-xxlarge uneditable-input borderless', 'disabled' => 'disabled', 'label' => 'Storage Key' ),
	),
);
?>
<div class="row-fluid" style="border-bottom:1px solid #ddd">
	<div class="span8">
		<h2 style="margin-bottom: 0">Platform
			<small><?php echo $model->instance_name_text; ?></small>
		</h2>
	</div>
	<div class="span4" style="margin-top:10px">
		<span class="pull-right">
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
		</span>
	</div>
</div>

<div class="tabbable tabs-left">
	<ul id="platform-navbar" class="nav nav-tabs">
		<li class="active"><a href="#tab-edit" data-toggle="tab">Settings</a></li>
		<li><a href="#tab-metrics" data-toggle="tab">Metrics</a></li>
		<li><a href="#tab-history" data-toggle="tab">History</a></li>
	</ul>

	<div class="tab-content">
		<div class="tab-pane active" id="tab-edit">
			<form id="update-platform" method="POST" class="form-horizontal tab-form" action>
				<div class="row-fluid">
					<div class="span12">
						<?php $_form->renderFields( $_fields, $modelName ); ?>
					</div>
				</div>
			</form>
		</div>
		<div class="tab-pane" id="tab-metrics">
			<?php require_once __DIR__ . '/_metrics.php'; ?>
		</div>
		<div class="tab-pane" id="tab-history">
			<?php require_once __DIR__ . '/_history.php'; ?>
		</div>
	</div>
</div>

<script src="//code.highcharts.com/highcharts.js"></script>
<script src="//code.highcharts.com/modules/exporting.js"></script>
<script type="text/javascript">
jQuery(function($) {
	$('form#form-button-bar').on('click', 'button', function(e) {
		e.preventDefault();
		var _cmd = $(this).attr('id').replace('-instance', '');
		var _id = $(this).data('row-id');

		if (!confirm('Do you REALLY REALLY wish to perform this action? It is irreversable!')) {
			return false;
		}

		$.ajax({url:         '/dashboard/action',
				   method:   'POST',
				   dataType: 'json',
				   data:     {id: _id, action: _cmd },
				   async:    false,
				   success:  function(data) {
					   if (data && data.success) {
						   alert('Your platform request has been queued.');
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
								   url:         '/dashboard/platform',
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
