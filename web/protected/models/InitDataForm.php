<?php

/**
 * InitDataForm class.
 * InitDataForm is the data structure for keeping system initialization data.
 * It is used by the 'initData' action of 'SiteController'.
 */
class InitDataForm extends CFormModel
{
    public $dummy;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
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
		);
	}

}