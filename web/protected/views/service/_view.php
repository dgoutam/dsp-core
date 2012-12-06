<?php
/* @var $this ServiceController */
/* @var $data Service */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::encode($data->name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('label')); ?>:</b>
	<?php echo CHtml::encode($data->label); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('is_active')); ?>:</b>
	<?php echo CHtml::encode($data->is_active); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('type')); ?>:</b>
	<?php echo CHtml::encode($data->type); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('native_format')); ?>:</b>
	<?php echo CHtml::encode($data->native_format); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('base_url')); ?>:</b>
	<?php echo CHtml::encode($data->base_url); ?>
	<br />

	<?php /*
	<b><?php echo CHtml::encode($data->getAttributeLabel('parameters')); ?>:</b>
	<?php echo CHtml::encode($data->parameters); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('headers')); ?>:</b>
	<?php echo CHtml::encode($data->headers); ?>
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

	*/ ?>

</div>