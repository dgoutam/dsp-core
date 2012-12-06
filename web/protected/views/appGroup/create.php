<?php
/* @var $this AppGroupController */
/* @var $model AppGroup */

$this->breadcrumbs=array(
	'App Groups'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List AppGroup', 'url'=>array('index')),
	array('label'=>'Manage AppGroup', 'url'=>array('admin')),
);
?>

<h1>Create AppGroup</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>