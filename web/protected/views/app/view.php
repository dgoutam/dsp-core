<?php
/* @var $this AppController */
/* @var $model App */

$this->breadcrumbs=array(
	'Apps'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List App', 'url'=>array('index')),
	array('label'=>'Create App', 'url'=>array('create')),
	array('label'=>'Update App', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete App', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage App', 'url'=>array('admin')),
);
?>

<h1>View App #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		'label',
		'description',
		'is_active',
		'url',
		'is_url_external',
		'app_group_ids',
		'created_date',
		'last_modified_date',
		'created_by_id',
		'last_modified_by_id',
	),
)); ?>
