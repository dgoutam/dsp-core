<?php
/* @var $this SiteController */
/* @var $model InitDataForm */
/* @var $form CActiveForm */

$this->pageTitle=Yii::app()->name . ' - Initialization';
$this->breadcrumbs=array(
	'Initialization',
);
?>

<h1>Data Initialization</h1>

<?php if(Yii::app()->user->hasFlash('init')): ?>

<div class="flash-success">
	<?php echo Yii::app()->user->getFlash('init'); ?>
</div>

<?php else: ?>

<p>
Your Document Services Platform needs some default setup data configured.
When you are ready, click the 'Configure' button below.
</p>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'init-form',
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
)); ?>

	<?php echo $form->errorSummary($model); ?>

    <div class="row">
   		<?php echo $form->hiddenField($model,'dummy'); ?>
   	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Configure'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

<?php endif; ?>