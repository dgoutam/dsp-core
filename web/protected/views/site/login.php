<?php
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
		href="//dfnew.piuid.com/user/register"
		target="_blank">click here</a> to do so
	now.</p>
<div class="spacer"></div>

<form id="login-form" method="POST">
	<?php
	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'LoginForm_username' ), 'Email Address' );
	echo'<div class="controls">' . Bootstrap::text( array( 'id' => 'LoginForm_username', 'name' => 'LoginForm[username]', 'class' => 'email required' ) ) .
		'</div></div>';
	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'LoginForm_password' ), 'Password' );
	echo'<div class="controls">' .
		Bootstrap::password( array( 'id' => 'LoginForm_password', 'name' => 'LoginForm[password]', 'class' => 'password required' ) ) . '</div></div>';
	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Activate!</button>
	</div>
</form>
