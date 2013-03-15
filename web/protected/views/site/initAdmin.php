<?php
/**
 * @var $this  SiteController
 * @var $model InitAdminForm
 */
use Kisma\Core\Utility\Bootstrap;

Validate::register(
	'form#init-form',
	array(
		 'ignoreTitle'    => true,
		 'errorClass'     => 'error',
		 'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
		 'rules'          => array(
			 'InitAdminForm[username]'       => array(
				 'required'  => true,
				 'minlength' => 6,
			 ),
			 'InitAdminForm[displayName]' => array(
				 'required'  => true,
				 'minlength' => 6,
			 ),
			 'InitAdminForm[password]'       => array(
				 'required'  => true,
				 'minlength' => 6,
			 ),
			 'InitAdminForm[passwordRepeat]' => array(
				 'required'  => true,
				 'minlength' => 6,
				 'equalTo'   => '#InitAdminForm_password',
			 ),
		 ),
	)
);
?>
<h2 class="headline">Now Let's Get Your Mojo Working!</h2>

<p>One last step... Your DreamFactory Services Platform(tm) needs a system administrator.</p>
<p>This user is a separate account that exists only inside your DSP. It cannot be used elsewhere, like on the
	<strong>DreamFactory.com</strong> site for instance.</p>
<p>More administrative and regular users can be easily added using the DSP's built-in 'Admin' application.</p>
<div class="spacer"></div>

<form id="init-form" method="POST">
	<?php
	echo'<legend>Login Credentials</legend>';

	echo'<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_username' ), 'User Name' ) . '<div class="controls">' .
		Bootstrap::text( array( 'id' => 'InitAdminForm_username', 'name' => 'InitAdminForm[username]', 'class' => 'required' ) ) . '</div></div>';

	echo'<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_password' ), 'Password' ) . '<div class="controls">' .
		Bootstrap::password( array( 'id' => 'InitAdminForm_password', 'name' => 'InitAdminForm[password]', 'class' => 'password required' ) ) .
		'</div></div>';

	echo'<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_passwordRepeat' ), 'Password Again' ) . '<div class="controls">' .
		Bootstrap::password( array( 'id' => 'InitAdminForm_passwordRepeat', 'name' => 'InitAdminForm[passwordRepeat]', 'class' => 'password required' ) ) .
		'</div></div>';

	echo'<legend>User Details</legend>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_email' ), 'Email Address' ) . '<div class="controls">' .
		 Bootstrap::text( array( 'id' => 'InitAdminForm_email', 'name' => 'InitAdminForm[email]', 'class' => 'email required' ) ) . '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_firstName' ), 'First Name' ) . '<div class="controls">' .
		 Bootstrap::text( array( 'id' => 'InitAdminForm_firstName', 'name' => 'InitAdminForm[firstName]', 'class' => 'required' ) ) . '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_lastName' ), 'Last Name' ) . '<div class="controls">' .
		 Bootstrap::text( array( 'id' => 'InitAdminForm_lastName', 'name' => 'InitAdminForm[lastName]', 'class' => 'required' ) ) . '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_displayName' ), 'Display Name' ) . '<div class="controls">' .
		 Bootstrap::text( array( 'id' => 'InitAdminForm_displayName', 'name' => 'InitAdminForm[displayName]', 'class' => 'required' ) ) . '</div></div>';

	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Gimme My Mojo!</button>
	</div>
</form>
