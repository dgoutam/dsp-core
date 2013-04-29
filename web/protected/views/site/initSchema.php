<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
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