<?php

/**
 * InitForm class.
 * InitForm is the data structure for keeping system initialization data.
 * It is used by the 'init' action of 'SiteController'.
 */
class InitForm extends CFormModel
{
	public $username;
    public $password;
	public $email;
    public $firstName;
    public $lastName;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
			// names, password, and email are required
			array('username, password, email, lastName, firstName', 'required'),
			// email has to be a valid email address
			array('email', 'email'),
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
            'username'=>'Desired UserName',
            'password'=>'Desired Password',
            'firstName'=>'First Name',
            'lastName'=>'Last Name',
            'email'=>'Currently Valid Email',
		);
	}

}