<?php
/* @var $this AppGroupController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'App Groups',
);

$this->menu=array(
	array('label'=>'Create AppGroup', 'url'=>array('create')),
	array('label'=>'Manage AppGroup', 'url'=>array('admin')),
);
?>

<h1>App Groups</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
