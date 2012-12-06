<?php
/* @var $this SessionController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Sessions',
);

$this->menu=array(
	array('label'=>'Create Session', 'url'=>array('create')),
	array('label'=>'Manage Session', 'url'=>array('admin')),
);
?>

<h1>Sessions</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
