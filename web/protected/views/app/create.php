<?php
/* @var $this AppController */
/* @var $model App */

$this->breadcrumbs=array(
	'Apps'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List App', 'url'=>array('index')),
	array('label'=>'Manage App', 'url'=>array('admin')),
);
?>

<h1>Create App</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>