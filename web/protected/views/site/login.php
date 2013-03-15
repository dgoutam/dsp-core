<?php
/* @var $this SiteController */
use Kisma\Core\Utility\Bootstrap;

/* @var $model LoginForm */
/* @var $form CActiveForm */
?>

<h2 class="headline">Activate Your New DSP!</h2>

<p>In order to activate this DSP, you must enter your <strong>DreamFactory.com</strong> site credentials.<p><p>Please enter the email address and
	password you used to register on the DreamFactory.com web site. If you have not yet registered, you may <a href="//dfnew.piuid.com/user/register"
																											   target="_blank">click here</a> to do so
	now.</p>
<div class="spacer"></div>

<form id="login-form" method="POST">
	<?php
	echo Bootstrap::label( array( 'for' => 'LoginForm_username' ), 'Email Address' );
	echo Bootstrap::text( array( 'id' => 'LoginForm_username', 'name' => 'LoginForm[username]' ) );
	echo Bootstrap::label( array( 'for' => 'LoginForm_username' ), 'Password' );
	echo Bootstrap::text( array( 'id' => 'LoginForm_password', 'name' => 'LoginForm[password]' ) );
	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Activate!</button>
	</div>
</form>
