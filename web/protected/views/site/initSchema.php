<?php
/* @var $this SiteController */
/* @var $model InitSchemaForm */
/* @var $form CActiveForm */

$this->pageTitle=Yii::app()->name . ' - Schema Initialization';
$this->breadcrumbs=array(
	'Schema Initialization',
);
?>

<h1>Schema Initialization</h1>

<?php if(Yii::app()->user->hasFlash('init-schema')): ?>

<div class="flash-success">
	<?php echo Yii::app()->user->getFlash('init-schema'); ?>
</div>

<?php else: ?>

<p>
Your Document Services Platform needs initializing.
When you are ready, click the 'Initialize' button below.
</p>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'init-schema-form',
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
		<?php echo CHtml::submitButton('Initialize'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

<?php endif; ?>