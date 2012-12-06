<?php
/* @var $this ServiceController */
/* @var $model Service */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>50,'maxlength'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'label'); ?>
		<?php echo $form->textField($model,'label',array('size'=>60,'maxlength'=>80)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'is_active'); ?>
		<?php echo $form->checkBox($model,'is_active'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'type'); ?>
		<?php echo $form->textField($model,'type',array('size'=>40,'maxlength'=>40)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'native_format'); ?>
		<?php echo $form->textField($model,'native_format',array('size'=>32,'maxlength'=>32)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'base_url'); ?>
		<?php echo $form->textField($model,'base_url',array('size'=>60,'maxlength'=>120)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'parameters'); ?>
		<?php echo $form->textField($model,'parameters',array('size'=>-1,'maxlength'=>-1)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'headers'); ?>
		<?php echo $form->textField($model,'headers',array('size'=>-1,'maxlength'=>-1)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'created_date'); ?>
		<?php echo $form->textField($model,'created_date'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'last_modified_date'); ?>
		<?php echo $form->textField($model,'last_modified_date'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'created_by_id'); ?>
		<?php echo $form->textField($model,'created_by_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'last_modified_by_id'); ?>
		<?php echo $form->textField($model,'last_modified_by_id'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->