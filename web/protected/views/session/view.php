<?php
/* @var $this SessionController */
/* @var $model Session */

$this->breadcrumbs=array(
	'Sessions'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List Session', 'url'=>array('index')),
	array('label'=>'Create Session', 'url'=>array('create')),
	array('label'=>'Update Session', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Session', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Session', 'url'=>array('admin')),
);
?>

<h1>View Session #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'start_time',
		'data',
	),
)); ?>
