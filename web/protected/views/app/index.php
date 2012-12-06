<?php
/* @var $this AppController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Apps',
);

$this->menu=array(
	array('label'=>'Create App', 'url'=>array('create')),
	array('label'=>'Manage App', 'url'=>array('admin')),
);
?>

<h1>Apps</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
