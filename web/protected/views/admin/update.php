<?php
/**
 * @var WebController $this
 * @var array         $resource
 */
use DreamFactory\Yii\Utility\BootstrapForm;

$update = false;

$_form = new BootstrapForm();

$_options = array(
	'breadcrumbs' => false,
);

$_formOptions = $_form->pageHeader( $_options );

print_r($resource);

////	Render the form
//echo $this->renderPartial(
//	'_form',
//	array(
//		 'model'        => $model,
//		 '_formOptions' => $_formOptions,
//		 'update'       => $update,
//	)
//);
