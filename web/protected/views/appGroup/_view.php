<?php
/* @var $this AppGroupController */
/* @var $data AppGroup */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::encode($data->name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('description')); ?>:</b>
	<?php echo CHtml::encode($data->description); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('created_date')); ?>:</b>
	<?php echo CHtml::encode($data->created_date); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('last_modified_date')); ?>:</b>
	<?php echo CHtml::encode($data->last_modified_date); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('created_by_id')); ?>:</b>
	<?php echo CHtml::encode($data->created_by_id); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('last_modified_by_id')); ?>:</b>
	<?php echo CHtml::encode($data->last_modified_by_id); ?>
	<br />


</div>