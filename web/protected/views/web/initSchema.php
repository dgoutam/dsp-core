<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
/* @var $this WebController */
/* @var $model InitSchemaForm */
/* @var $form CActiveForm */

$this->pageTitle = Yii::app()->name . ' - Upgrade Schema';
$this->breadcrumbs = array(
	'Upgrade Schema',
);
?>

	<h2 class="headline">Database Upgrade Available!</h2>

<?php if ( Yii::app()->user->hasFlash( 'init-schema' ) ): ?>

	<div class="flash-success">
		<?php echo Yii::app()->user->getFlash( 'init-schema' ); ?>
	</div>

<?php else: ?>

	<p>A database update is available for this DreamFactory Services Platform&trade;. Click the 'Update' button below to start the update.</p>

	<div class="space200"></div>
	<div class="space50"></div>
	<form id="init-schema-form" method="POST">
		<input type="hidden" name="InitSchemaForm[dummy]" id="InitSchemaForm_dummy" value="1">

		<div class="form-actions">
			<button type="submit" class="btn btn-success btn-primary">Update!</button>
		</div>
	</form>

<?php endif; ?>