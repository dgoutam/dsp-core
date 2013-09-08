<?php
/**
 * @var WebController           $this
 * @var array                   $schema
 * @var BasePlatformSystemModel $model
 * @var array                   $_formOptions Provided by includer
 * @var array                   $errors       Errors if any
 * @var string                  $resourceName The name of this resource (i.e. App, AppGroup, etc.) Essentially the model name
 * @var string                  $displayName
 * @var array                   $_data_
 */
use DreamFactory\Yii\Utility\BootstrapForm;

$update = false;

$_form = new BootstrapForm();

$_options = array(
	'breadcrumbs' => array(
		'Admin Dashboard'   => '/admin',
		$resourceName . 's' => '/admin#tab-' . strtolower( $resourceName ),
		$displayName        => false,
	)
);

$_formOptions = $_form->pageHeader( $_options );

//	Render the form
$this->renderPartial( '_provider_form', $_data_ );
