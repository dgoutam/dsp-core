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
 * @var $this  WebController
 * @var $model LoginForm
 */
use Kisma\Core\Utility\Bootstrap;
use Platform\Yii\Utility\Validate;

Validate::register(
	'form#login-form',
	array(
		 'ignoreTitle'    => true,
		 'errorClass'     => 'error',
		 'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
	)
);

$_headline = ( isset( $activated ) && $activated ) ? 'Welcome!' : 'Activate Your New DSP!';
?>
<h2 class="headline"><?php echo $_headline; ?></h2>
<p>Thank you for installing the DreamFactory Services Platform&trade;. You may
	<strong>optionally</strong> register your DSP to receive free technical support and automatic software updates. Registration is quick and easy at
	<a href="https://www.dreamfactory.com/user/register">http://www.dreamfactory.com</a>
   .
</p><p>
<div class="space200"></div>
<p>If you've previously registered on
	<a href="https://www.dreamfactory.com/user/register">http://www.dreamfactory.com</a>
   , you can register your DSP with those credentials. Please enter the email address and password you used to register on the <strong>DreamFactory</strong> web site.
</p>
<div class="spacer"></div>
<form id="login-form" method="POST">
	<input type="hidden" name="skipped" id="skipped" value="0">
	<?php
	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'LoginForm_username' ), 'Email Address' );
	echo '<div class="controls">' . Bootstrap::text( array( 'id' => 'LoginForm_username', 'name' => 'LoginForm[username]', 'class' => 'email' ) ) .
		 '</div></div>';
	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'LoginForm_password' ), 'Password' );
	echo '<div class="controls">' .
		 Bootstrap::password( array( 'id' => 'LoginForm_password', 'name' => 'LoginForm[password]', 'class' => 'password' ) ) . '</div></div>';
	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Activate!</button>
		<button id="btn-skip" class="btn btn-secondary pull-right">Skip</button>
	</div>
</form>
<script type="text/javascript">
jQuery(function($) {
	$('#btn-skip').on('click', function(e) {
		e.preventDefault();
		$('input#skipped').val(1);
		$('form#login-form').submit();
	});
});
</script>