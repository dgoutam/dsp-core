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
use DreamFactory\Yii\Utility\Validate;
use Kisma\Core\Utility\Bootstrap;

/**
 * @var WebController $this
 * @var LoginForm     $model
 * @var bool          $redirected
 */

Validate::register(
	'form#login-form',
	array(
		 'ignoreTitle'    => true,
		 'errorClass'     => 'error',
		 'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
	)
);

$_headline = 'System Notices Available!';
?>
<h2 class="headline"><?php echo $_headline; ?></h2><p>Please log into a DSP system administrator account to view these notices. </p>
<div class="spacer"></div>
<form id="login-form" method="POST">
	<?php
	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'LoginForm_username' ), 'DSP Admin Email Address' );
	echo '<div class="controls">' . Bootstrap::text( array( 'id' => 'LoginForm_username', 'name' => 'LoginForm[username]', 'class' => 'email required' ) ) .
		 '</div></div>';
	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'LoginForm_password' ), 'Password' );
	echo '<div class="controls">' .
		 Bootstrap::password( array( 'id' => 'LoginForm_password', 'name' => 'LoginForm[password]', 'class' => 'password required' ) ) . '</div></div>';
	?>
	<input type="hidden" name="login-only" value="<?php echo $redirected ? 1 : 0; ?>">

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Login</button>
	</div>
</form>