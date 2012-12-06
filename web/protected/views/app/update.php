<?php
/* @var $this AppController */
/* @var $model App */

$this->breadcrumbs=array(
	'Apps'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List App', 'url'=>array('index')),
	array('label'=>'Create App', 'url'=>array('create')),
	array('label'=>'View App', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage App', 'url'=>array('admin')),
);
?>

<h1>Update App <?php echo $model->id; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>