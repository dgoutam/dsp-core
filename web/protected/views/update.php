<?php
/**
 * @var \Cerberus\Yii\Controllers\PlatformController    $this
 * @var \DreamFactory\Fabric\Yii\Models\Deploy\Instance $model
 */
use DreamFactory\Yii\Utility\BootstrapForm;

$update = false;

$_form = new BootstrapForm();

$_options = array(
	'breadcrumbs' => false,
);

$_formOptions = $_form->pageHeader( $_options );

//	Render the form
echo $this->renderPartial(
	'_form',
	array(
		 'model'        => $model,
		 '_formOptions' => $_formOptions,
		 'update'       => $update,
	)
);
