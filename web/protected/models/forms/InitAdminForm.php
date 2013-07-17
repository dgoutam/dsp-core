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
 * InitAdminForm class.
 * InitAdminForm is the data structure for keeping system admin initialization data.
 * It is used by the 'initAdmin' action of 'WebController'.
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