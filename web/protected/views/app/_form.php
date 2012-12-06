<?php
/* @var $this AppController */
/* @var $model App */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'app-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>40,'maxlength'=>40)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'label'); ?>
		<?php echo $form->textField($model,'label',array('size'=>60,'maxlength'=>80)); ?>
		<?php echo $form->error($model,'label'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'description'); ?>
		<?php echo $form->textField($model,'description',array('size'=>-1,'maxlength'=>-1)); ?>
		<?php echo $form->error($model,'description'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'is_active'); ?>
		<?php echo $form->checkBox($model,'is_active'); ?>
		<?php echo $form->error($model,'is_active'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'url'); ?>
		<?php echo $form->textField($model,'url',array('size'=>-1,'maxlength'=>-1)); ?>
		<?php echo $form->error($model,'url'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'is_url_external'); ?>
		<?php echo $form->checkBox($model,'is_url_external'); ?>
		<?php echo $form->error($model,'is_url_external'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'app_group_ids'); ?>
		<?php echo $form->textField($model,'app_group_ids',array('size'=>-1,'maxlength'=>-1)); ?>
		<?php echo $form->error($model,'app_group_ids'); ?>
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