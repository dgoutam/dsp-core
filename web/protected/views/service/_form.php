<?php
/* @var $this ServiceController */
/* @var $model Service */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'service-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'label'); ?>
		<?php echo $form->textField($model,'label',array('size'=>60,'maxlength'=>80)); ?>
		<?php echo $form->error($model,'label'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'is_active'); ?>
		<?php echo $form->checkBox($model,'is_active'); ?>
		<?php echo $form->error($model,'is_active'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'type'); ?>
		<?php echo $form->textField($model,'type',array('size'=>40,'maxlength'=>40)); ?>
		<?php echo $form->error($model,'type'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'native_format'); ?>
		<?php echo $form->textField($model,'native_format',array('size'=>32,'maxlength'=>32)); ?>
		<?php echo $form->error($model,'native_format'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'base_url'); ?>
		<?php echo $form->textField($model,'base_url',array('size'=>60,'maxlength'=>120)); ?>
		<?php echo $form->error($model,'base_url'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'parameters'); ?>
		<?php echo $form->textField($model,'parameters',array('size'=>-1,'maxlength'=>-1)); ?>
		<?php echo $form->error($model,'parameters'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'headers'); ?>
		<?php echo $form->textField($model,'headers',array('size'=>-1,'maxlength'=>-1)); ?>
		<?php echo $form->error($model,'headers'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'created_date'); ?>
		<?php echo $form->textField($model,'created_date'); ?>
		<?php echo $form->error($model,'created_date'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'last_modified_date'); ?>
		<?php echo $form->textField($model,'last_modified_date'); ?>
		<?php echo $form->error($model,'last_modified_date'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'created_by_id'); ?>
		<?php echo $form->textField($model,'created_by_id'); ?>
		<?php echo $form->error($model,'created_by_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'last_modified_by_id'); ?>
		<?php echo $form->textField($model,'last_modified_by_id'); ?>
		<?php echo $form->error($model,'last_modified_by_id'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->