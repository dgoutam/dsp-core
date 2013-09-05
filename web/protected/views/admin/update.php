<?php
/**
 * @var WebController $this
 * @var array         $resource
 * @var array         $schema
 */
use DreamFactory\Yii\Utility\BootstrapForm;

$update = false;

$_form = new BootstrapForm();

$_options = array(
	'breadcrumbs' => array(
		'Admin Dashboard' => '/admin',
		'Resource'        => false,
	)
);

$_formOptions = $_form->pageHeader( $_options );

//	Render the form
require __DIR__ . '/_provider.form.php';
