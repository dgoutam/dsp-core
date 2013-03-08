<?php

/**
 * RoleServiceAccess.php
 *
 * This file is part of the DreamFactory Document Service Platform (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This source file and all is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * The system access model for the DSP
 */

/**
 * This is the model class for table "role_service_access".
 *
 * The followings are the available columns in table 'role_service_access':
 * @property integer $id
 * @property integer $role_id
 * @property integer $service_id
 * @property string $component
 * @property string $access
 *
 * The followings are the available model relations:
 * @property Role $role
 * @property Service $service
 */
class RoleServiceAccess extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return RoleServiceAccess the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'df_sys_role_service_access';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('role_id', 'required'),
            array('role_id, service_id', 'numerical', 'integerOnly' => true),
            array('access', 'length', 'max' => 64),
            array('component', 'length', 'max' => 128),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, role_id, service_id, component, access', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'role' => array(self::BELONGS_TO, 'Role', 'role_id'),
            'service' => array(self::BELONGS_TO, 'Service', 'service_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'Access Id',
            'role_id' => 'Role',
            'service_id' => 'Service',
            'component' => 'Component',
            'access' => 'Access',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('role_id', $this->role_id);
        $criteria->compare('service_id', $this->service_id);
        $criteria->compare('component', $this->component);
        $criteria->compare('access', $this->access);

        return new CActiveDataProvider($this, array(
                                                   'criteria' => $criteria,
                                              ));
    }

    /**
     * Overrides base class
     * @return bool
     */
    protected function beforeValidate()
    {

        return parent::beforeValidate();
    }

    /**
     * Overrides base class
     * @return bool
     */
    protected function beforeSave()
    {

        return parent::beforeSave();
    }

    /**
     * Overrides base class
     * @return bool
     * @throws Exception
     */
    protected function beforeDelete()
    {

        return parent::beforeDelete();
    }

    /**
     * @param string $requested
     * @return array
     */
    public function getRetrievableAttributes($requested)
    {
        if (empty($requested)) {
            // primary keys only
            return array('id');
        }
        elseif ('*' == $requested) {
            return array('id','role_id','service_id','component','access');
        }
        else {
            // remove any undesired retrievable fields
//            $requested = Utilities::removeOneFromList($requested, 'password', ',');
            return explode(',', $requested);
        }
    }

    public function afterFind()
    {
        parent::afterFind();
    }
}