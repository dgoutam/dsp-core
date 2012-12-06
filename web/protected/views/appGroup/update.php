<?php
/* @var $this AppGroupController */
/* @var $model AppGroup */

$this->breadcrumbs=array(
	'App Groups'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List AppGroup', 'url'=>array('index')),
	array('label'=>'Create AppGroup', 'url'=>array('create')),
	array('label'=>'View AppGroup', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage AppGroup', 'url'=>array('admin')),
);
?>

<h1>Update AppGroup <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>