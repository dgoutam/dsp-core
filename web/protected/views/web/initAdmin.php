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
 * @var $model InitAdminForm
 */
use Kisma\Core\Utility\Bootstrap;
use Platform\Yii\Utility\Validate;

Validate::register(
	'form#init-form',
	array(
		 'ignoreTitle'    => true,
		 'errorClass'     => 'error',
		 'errorPlacement' => 'function(error,element){error.appendTo(element.parent("div"));error.css("margin","-10px 0 0");}',
		 'rules'          => array(
			 'InitAdminForm[email]'          => array(
				 'required'  => true,
				 'minlength' => 5,
			 ),
			 'InitAdminForm[displayName]'    => array(
				 'required'  => true,
				 'minlength' => 5,
			 ),
			 'InitAdminForm[password]'       => array(
				 'required'  => true,
				 'minlength' => 5,
			 ),
			 'InitAdminForm[passwordRepeat]' => array(
				 'required'  => true,
				 'minlength' => 5,
				 'equalTo'   => '#InitAdminForm_password',
			 ),
		 ),
	)
);
?><h2 class="headline">Create a System Admin User</h2>

<p>Your DreamFactory Services Platform(tm) requires a local system administrator.</p><p>This user is a separate account that exists only
	inside your DSP. It cannot be used elsewhere, like on the <strong>dreamfactory.com</strong> site for instance.</p><p>More administrative and
	regular users can be easily added using the DSP's built-in 'Admin' application.</p>
<div class="spacer"></div>

<form id="init-form" method="POST">
	<?php
	echo '<legend>Login Credentials</legend>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_email' ), 'Email Address' ) . '<div class="controls">' .
		 Bootstrap::text( array(
							   'id'    => 'InitAdminForm_email',
							   'name'  => 'InitAdminForm[email]',
							   'class' => 'email required',
							   'value' => $model->email
						  )
		 ) . '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_password' ), 'Password' ) . '<div class="controls">' .
		 Bootstrap::password( array(
								   'id'    => 'InitAdminForm_password',
								   'name'  => 'InitAdminForm[password]',
								   'class' => 'password required',
								   'value' => null
							  )
		 ) .
		 '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_passwordRepeat' ),
														   'Password Again'
	) . '<div class="controls">' .
		 Bootstrap::password( array(
								   'id'    => 'InitAdminForm_passwordRepeat',
								   'name'  => 'InitAdminForm[passwordRepeat]',
								   'class' => 'password required',
								   'value' => null
							  )
		 ) .
		 '</div></div>';

	echo '<legend>User Details</legend>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_firstName' ), 'First Name' ) . '<div class="controls">' .
		 Bootstrap::text( array(
							   'id'    => 'InitAdminForm_firstName',
							   'name'  => 'InitAdminForm[firstName]',
							   'class' => 'required',
							   'value' => $model->firstName
						  )
		 ) . '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_lastName' ), 'Last Name' ) . '<div class="controls">' .
		 Bootstrap::text( array(
							   'id'    => 'InitAdminForm_lastName',
							   'name'  => 'InitAdminForm[lastName]',
							   'class' => 'required',
							   'value' => $model->lastName
						  )
		 ) . '</div></div>';

	echo '<div class="control-group">' . Bootstrap::label( array( 'for' => 'InitAdminForm_displayName' ),
														   'Display Name'
	) . '<div class="controls">' .
		 Bootstrap::text( array(
							   'id'    => 'InitAdminForm_displayName',
							   'name'  => 'InitAdminForm[displayName]',
							   'class' => 'required',
							   'value' => $model->displayName,
						  )
		 ) . '</div></div>';

	?>

	<div class="form-actions">
		<button type="submit" class="btn btn-success btn-primary">Gimme My Mojo!</button>
	</div>
</form>
