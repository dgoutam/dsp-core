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
/**
 * @var $this  SiteController
 * @var $model LoginForm
 */
use Kisma\Core\Utility\Bootstrap;

Validate::register(
	'form#login-form',
	array(
		'ignoreTitle'    => true,
		'errorClass'     => 'error',
		'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
	)
);
?>
<h2 class="headline">Activate Your New DSP!</h2>
<p>In order to activate this DSP, you must enter your <strong>DreamFactory.com</strong> site credentials.</p><p>Please enter the email address and
	password you used to register on the <strong>DreamFactory.com</strong> web site. If you have not yet registered, you may <a
		href="https://www.dreamfactory.com/user/register"
		target="_blank">click here</a> to do so
	now.</p>
<div class="spacer"></div>
<form id="login-form" method="POST">
	<?php
	echo '<div class="control-group">' . Bootstrap::label( array('for' => 'LoginForm_username'), 'Email Address' );
	echo '<div class="controls">' . Bootstrap::text( array('id' => 'LoginForm_username', 'name' => 'LoginForm[username]', 'class' => 'email required') ) .
		'</div></div>';
	echo '<div class="control-group">' . Bootstrap::label( array('for' => 'LoginForm_password'), 'Password' );
	echo '<div class="controls">' .
		Bootstrap::password( array('id' => 'LoginForm_password', 'name' => 'LoginForm[password]', 'class' => 'password required') ) . '</div></div>';
	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Activate!</button>
	</div>
</form>