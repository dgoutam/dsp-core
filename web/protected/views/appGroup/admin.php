<?php
/* @var $this AppGroupController */
/* @var $model AppGroup */

$this->breadcrumbs=array(
	'App Groups'=>array('index'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List AppGroup', 'url'=>array('index')),
	array('label'=>'Create AppGroup', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$.fn.yiiGridView.update('app-group-grid', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1>Manage App Groups</h1>

<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'app-group-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'name',
		'description',
		'created_date',
		'last_modified_date',
		'created_by_id',
		/*
		'last_modified_by_id',
		*/
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
