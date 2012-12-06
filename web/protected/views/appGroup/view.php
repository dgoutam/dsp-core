<?php
/* @var $this AppGroupController */
/* @var $model AppGroup */

$this->breadcrumbs=array(
	'App Groups'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List AppGroup', 'url'=>array('index')),
	array('label'=>'Create AppGroup', 'url'=>array('create')),
	array('label'=>'Update AppGroup', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete AppGroup', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage AppGroup', 'url'=>array('admin')),
);
?>

<h1>View AppGroup #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		'description',
		'created_date',
		'last_modified_date',
		'created_by_id',
		'last_modified_by_id',
	),
)); ?>
