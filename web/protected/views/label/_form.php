<?php
/* @var $this LabelController */
/* @var $model Label */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'label-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'table'); ?>
		<?php echo $form->textField($model,'table',array('size'=>60,'maxlength'=>128)); ?>
		<?php echo $form->error($model,'table'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'field'); ?>
		<?php echo $form->textField($model,'field',array('size'=>60,'maxlength'=>128)); ?>
		<?php echo $form->error($model,'field'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'picklist'); ?>
		<?php echo $form->textField($model,'picklist',array('size'=>-1,'maxlength'=>-1)); ?>
		<?php echo $form->error($model,'picklist'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'label'); ?>
		<?php echo $form->textField($model,'label',array('size'=>60,'maxlength'=>128)); ?>
		<?php echo $form->error($model,'label'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'plural'); ?>
		<?php echo $form->textField($model,'plural',array('size'=>60,'maxlength'=>128)); ?>
		<?php echo $form->error($model,'plural'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->