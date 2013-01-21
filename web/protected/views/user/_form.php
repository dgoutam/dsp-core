<?php
/* @var $this UserController */
/* @var $model User */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'user-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'display_name'); ?>
		<?php echo $form->textField($model,'display_name',array('size'=>60,'maxlength'=>80)); ?>
		<?php echo $form->error($model,'display_name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'email'); ?>
		<?php echo $form->textField($model,'email',array('size'=>60,'maxlength'=>80)); ?>
		<?php echo $form->error($model,'email'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'phone'); ?>
		<?php echo $form->textField($model,'phone',array('size'=>16,'maxlength'=>16)); ?>
		<?php echo $form->error($model,'phone'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'username'); ?>
		<?php echo $form->textField($model,'username',array('size'=>32,'maxlength'=>32)); ?>
		<?php echo $form->error($model,'username'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'password'); ?>
		<?php echo $form->passwordField($model,'password',array('size'=>32,'maxlength'=>32)); ?>
		<?php echo $form->error($model,'password'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'is_active'); ?>
		<?php echo $form->checkBox($model,'is_active'); ?>
		<?php echo $form->error($model,'is_active'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'is_sys_admin'); ?>
		<?php echo $form->checkBox($model,'is_sys_admin'); ?>
		<?php echo $form->error($model,'is_sys_admin'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'confirm_code'); ?>
		<?php echo $form->textField($model,'confirm_code',array('size'=>40,'maxlength'=>40)); ?>
		<?php echo $form->error($model,'confirm_code'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'role_id'); ?>
		<?php echo $form->textField($model,'role_id'); ?>
		<?php echo $form->error($model,'role_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'first_name'); ?>
		<?php echo $form->textField($model,'first_name',array('size'=>40,'maxlength'=>40)); ?>
		<?php echo $form->error($model,'first_name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'last_name'); ?>
		<?php echo $form->textField($model,'last_name',array('size'=>40,'maxlength'=>40)); ?>
		<?php echo $form->error($model,'last_name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'security_question'); ?>
		<?php echo $form->textField($model,'security_question',array('size'=>60,'maxlength'=>160)); ?>
		<?php echo $form->error($model,'security_question'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'security_answer'); ?>
		<?php echo $form->textField($model,'security_answer',array('size'=>60,'maxlength'=>80)); ?>
		<?php echo $form->error($model,'security_answer'); ?>
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