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
use DreamFactory\Yii\Utility\Pii;

/* @var $this WebController */
/* @var $model InitDataForm */
/* @var $form CActiveForm */

$this->pageTitle = Yii::app()->name . ' - Install System Data';
$this->breadcrumbs = array( 'Install System Data', );
$_user = Pii::app()->user;
?>
<h2 class="headline">Data Initialization</h2>
<?php if ( $_user->hasFlash( 'init' ) ): ?>

	<div class="flash-success">
		<?php echo $_user->getFlash( 'init' ); ?>
	</div>

<?php else: ?>
	<p>Your DSP requires the installation of system data in order to be properly configured. When you are ready, click the 'Install' button to add this data.</p>
	<div class="space200"></div>
	<div class="space50"></div>
	<form id="init-data-form" method="POST">
		<input type="hidden" name="InitDataForm[dummy]" id="InitDataForm_dummy" value="1">

		<div class="form-actions">
			<button type="submit" class="btn btn-success btn-primary">Install</button>
		</div>
	</form>

<?php endif; ?>
