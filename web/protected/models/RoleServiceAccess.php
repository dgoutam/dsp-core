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
 * RoleServiceAccess.php
 * The system access model for the DSP
 *
 * Columns:
 *
 * @property integer $id
 * @property integer $role_id
 * @property integer $service_id
 * @property string  $component
 * @property string  $access
 *
 * Relations:
 *
 * @property Role    $role
 * @property Service $service
 */
class RoleServiceAccess extends BaseDspSystemModel
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return RoleServiceAccess the static model class
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
		return static::tableNamePrefix() . 'role_service_access';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array( 'role_id', 'required' ),
			array( 'role_id, service_id', 'numerical', 'integerOnly' => true ),
			array( 'access', 'length', 'max' => 64 ),
			array( 'component', 'length', 'max' => 128 ),
			array( 'id, role_id, service_id, component, access', 'safe', 'on' => 'search' ),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'role'    => array( self::BELONGS_TO, 'Role', 'role_id' ),
			'service' => array( self::BELONGS_TO, 'Service', 'service_id' ),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id'         => 'Id',
			'role_id'    => 'Role',
			'service_id' => 'Service',
			'component'  => 'Component',
			'access'     => 'Access',
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

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'role_id', $this->role_id );
		$_criteria->compare( 'service_id', $this->service_id );
		$_criteria->compare( 'component', $this->component );
		$_criteria->compare( 'access', $this->access );

		return new CActiveDataProvider(
			$this,
			array(
				 'criteria' => $_criteria,
			)
		);
	}

	/**
	 * @param string $requested
	 *
	 * @param array  $columns
	 * @param array  $hidden
	 *
	 * @return array
	 */
	public function getRetrievableAttributes( $requested, $columns = array(), $hidden = array() )
	{
		// don't use base class here as those fields are not supported
		return array( 'role_id', 'service_id', 'component', 'access' );
	}
}