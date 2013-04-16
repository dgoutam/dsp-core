<?php

/**
 * InitAdminForm class.
 * InitAdminForm is the data structure for keeping system admin initialization data.
 * It is used by the 'initAdmin' action of 'SiteController'.
 */
class InitAdminForm extends CFormModel
{
	public $email;
	public $password;
	public $passwordRepeat;
	public $firstName;
	public $lastName;
	public $displayName;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
			// names, password, and email are required
			array( 'email, password, passwordRepeat, lastName, firstName', 'required' ),
			// password repeat must match password
			array( 'passwordRepeat', 'required' ),
			array( 'passwordRepeat', 'compare', 'compareAttribute' => 'password' ),
			// email has to be a valid email address
			array( 'email', 'email' ),
		);
	}

	/**
	 * Declares customized attribute labels.
	 * If not declared here, an attribute would have a label that is
	 * the same as its name with the first letter in upper case.
	 */
	public function attributeLabels()
	{
		return array(
			'email'          => 'Email Address',
			'password'       => 'Desired Password',
			'passwordRepeat' => 'Verify Password',
			'firstName'      => 'First Name',
			'lastName'       => 'Last Name',
			'displayName'    => 'Display Name',
		);
	}

}