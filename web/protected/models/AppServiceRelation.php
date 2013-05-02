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
 * AppServiceRelation.php
 * The system application to service relationship model for the DSP
 *
 * Columns:
 *
 * @property integer $id
 * @property integer $app_id
 * @property integer $service_id
 * @property string  $component
 *
 * Relations:
 *
 * @property App     $app
 * @property Service $service
 */
class AppServiceRelation extends BaseDspSystemModel
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return AppServiceRelation the static model class
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
		return 'df_sys_app_to_service';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array( 'app_id', 'required' ),
			array( 'app_id, service_id', 'numerical', 'integerOnly' => true ),
			array( 'component', 'length', 'max' => 128 ),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array( 'id, app_id, service_id, component', 'safe', 'on' => 'search' ),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
			'app'     => array( self::BELONGS_TO, 'App', 'app_id' ),
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
			'app_id'     => 'App',
			'service_id' => 'Service',
			'component'  => 'Component',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria = new CDbCriteria;

		$criteria->compare( 'id', $this->id );
		$criteria->compare( 'app_id', $this->app_id );
		$criteria->compare( 'service_id', $this->service_id );
		$criteria->compare( 'component', $this->component );

		return new CActiveDataProvider(
			$this,
			array( 'criteria' => $criteria, )
		);
	}

	/**
	 * {@InheritDoc}
	 */
	public function afterFind()
	{
		parent::afterFind();

		// unserialize component data
		if ( !empty( $this->component ) )
		{
			$this->component = json_decode( $this->component, true );
		}
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
		return array( 'app_id', 'service_id', 'component' );
	}
}