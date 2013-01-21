<?php
/* @var $this SiteController */
/* @var $model InitSchemaForm */
/* @var $form CActiveForm */

$this->pageTitle=Yii::app()->name . ' - Upgrade Schema';
$this->breadcrumbs=array(
	'Upgrade Schema',
);
?>

<h1>Upgrade Schema</h1>

<?php if(Yii::app()->user->hasFlash('init-schema')): ?>

<div class="flash-success">
	<?php echo Yii::app()->user->getFlash('init-schema'); ?>
</div>

<?php else: ?>

<p>
Your Document Services Platform database needs some schema that is missing to continue.
When you are ready, click the 'Upgrade Schema' button below.
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
		<?php echo CHtml::submitButton('Upgrade Schema'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

<?php endif; ?>