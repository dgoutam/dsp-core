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
 * Session.php
 * The system session model for the DSP
 *
 * Columns
 *
 * @property string  $id
 * @property integer $user_id
 * @property integer $role_id
 * @property integer $start_time
 * @property string  $data
 */
class Session extends BaseDspSystemModel
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return Session the static model class
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return static::tableNamePrefix() . 'session';
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id'         => 'Session Id',
			'user_id'    => 'User Id',
			'role_id'    => 'Role Id',
			'start_time' => 'Start Time',
			'data'       => 'Data',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$_criteria = new CDbCriteria();
		$_criteria->compare( 'id', $this->id, true );
		$_criteria->compare( 'user_id', $this->user_id );
		$_criteria->compare( 'role_id', $this->role_id );
		$_criteria->compare( 'start_time', $this->start_time );

		return new CActiveDataProvider(
			$this,
			array(
				 'criteria' => $_criteria,
			)
		);
	}
}