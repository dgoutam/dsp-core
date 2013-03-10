<?php

/**
 * AppGroup.php
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
 * The system application group model for the DSP
 */

/**
 * This is the model class for table "app_group".
 *
 * The followings are the available columns in table 'app_group':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property User $created_by
 * @property User $last_modified_by
 * @property App[] $apps
 */
class AppGroup extends BaseSystemModel
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return AppGroup the static model class
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
        return static::tableNamePrefix() . 'app_group';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        $rules = array(
            array('name', 'required'),
            array('name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false),
            array('name', 'length', 'max' => 64),
            array('description', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
        );

        return array_merge(parent::rules(), $rules);
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        $relations = array(
            'apps' => array(self::MANY_MANY, 'App', 'df_sys_app_to_app_group(app_id, app_group_id)'),
        );

        return array_merge(parent::relations(), $relations);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = array(
            'name' => 'Name',
            'description' => 'Description',
        );

        return array_merge(parent::attributeLabels(), $labels);
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
        $criteria->compare('name', $this->name, true);
        $criteria->compare('created_date', $this->created_date, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_by_id', $this->created_by_id);
        $criteria->compare('last_modified_by_id', $this->last_modified_by_id);

        return new CActiveDataProvider($this, array(
                                                   'criteria' => $criteria,
                                              ));
    }

    /**
     * @param array $values
     */
    public function setRelated($values)
    {
        if (isset($record['apps'])) {
            $this->assignManyToOneByMap($id, 'app', 'app_to_app_group', 'app_group_id', 'app_id', $record['apps']);
        }
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeValidate()
    {

        return parent::beforeValidate();
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeSave()
    {

        return parent::beforeSave();
    }

    /**
     * {@InheritDoc}
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
            return array('id','name','description',
                         'created_date','created_by_id','last_modified_date','last_modified_by_id');
        }
        else {
            // remove any undesired retrievable fields
//            $requested = Utilities::removeOneFromList($requested, 'password', ',');
            return explode(',', $requested);
        }
    }

}