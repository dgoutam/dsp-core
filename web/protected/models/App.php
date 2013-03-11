<?php

/**
 * App.php
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
 * The system application model for the DSP
 */

/**
 * This is the model class for table "df_sys_app".
 *
 * The followings are the available columns in table 'df_sys_app':
 * @property integer $id
 * @property string $name
 * @property string $api_name
 * @property string $description
 * @property boolean $is_active
 * @property string $url
 * @property integer $is_url_external
 * @property boolean $filter_by_device
 * @property boolean $filter_phone
 * @property boolean $filter_tablet
 * @property boolean $filter_desktop
 * @property boolean $requires_plugin
 * @property string $import_url
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property User $created_by
 * @property User $last_modified_by
 * @property Role[] $roles_default_app
 * @property User[] $users_default_app
 * @property AppGroup[] $app_groups
 * @property Role[] $roles
 */
class App extends BaseSystemModel
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return App the static model class
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
        return static::tableNamePrefix() . 'app';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        $rules = array(
            array('name, api_name', 'required'),
            array('name, api_name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false),
            array('is_active, is_url_external, filter_by_device, filter_phone, filter_tablet, filter_desktop, requires_plugin', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 64),
            array('api_name', 'length', 'max' => 64),
            array('description, url, import_url', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, api_name, is_active, is_url_external, filter_by_device, filter_phone, filter_tablet, filter_desktop, requires_plugin, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
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
            'roles_default_app' => array(self::HAS_MANY, 'Role', 'default_app_id'),
            'users_default_app' => array(self::HAS_MANY, 'User', 'default_app_id'),
            'app_groups' => array(self::MANY_MANY, 'AppGroup', 'df_sys_app_to_app_group(app_id, app_group_id)'),
            'roles' => array(self::MANY_MANY, 'Role', 'df_sys_app_to_role(app_id, role_id)'),
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
            'api_name' => 'API Name',
            'description' => 'Description',
            'is_active' => 'Is Active',
            'url' => 'Url',
            'is_url_external' => 'Is Url External',
            'filter_by_device' => 'Filter By Device',
            'filter_phone' => 'Filter Phone',
            'filter_tablet' => 'Filter Tablet',
            'filter_desktop' => 'Filter Desktop',
            'requires_plugin' => 'Requires Plugin',
            'import_url' => 'Import Url',
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
        $criteria->compare('api_name', $this->api_name, true);
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('is_url_external', $this->is_url_external);
        $criteria->compare('filter_by_device', $this->filter_by_device);
        $criteria->compare('filter_phone', $this->filter_phone);
        $criteria->compare('filter_tablet', $this->filter_tablet);
        $criteria->compare('filter_desktop', $this->filter_desktop);
        $criteria->compare('requires_plugin', $this->requires_plugin);
        $criteria->compare('created_date', $this->created_date, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_by_id', $this->created_by_id);
        $criteria->compare('last_modified_by_id', $this->last_modified_by_id);

        return new CActiveDataProvider($this, array('criteria' => $criteria,));
    }

    /**
     * @param array $values
     */
    public function setRelated($values, $id)
    {
        if (isset($values['app_groups'])) {
            $this->assignManyToOneByMap($id, 'app_group', 'app_to_app_group', 'app_id', 'app_group_id', $values['app_groups']);
        }
        if (isset($values['roles'])) {
            $this->assignManyToOneByMap($id, 'role', 'app_to_role', 'app_id', 'role_id', $values['roles']);
        }
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeValidate()
    {
        if (is_bool($this->is_active))
            $this->is_active = intval($this->is_active);
        if (is_bool($this->is_url_external))
            $this->is_url_external = intval($this->is_url_external);
        if (is_bool($this->filter_by_device))
            $this->filter_by_device = intval($this->filter_by_device);
        if (is_bool($this->filter_phone))
            $this->filter_phone = intval($this->filter_phone);
        if (is_bool($this->filter_tablet))
            $this->filter_tablet = intval($this->filter_tablet);
        if (is_bool($this->filter_desktop))
            $this->filter_desktop = intval($this->filter_desktop);
        if (is_bool($this->requires_plugin))
            $this->requires_plugin = intval($this->requires_plugin);

        return parent::beforeValidate();
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeSave()
    {
        if (!$this->is_url_external && empty($this->url)) {
            $this->url = '/index.html';
        }

        return parent::beforeSave();
    }

    /**
     * {@InheritDoc}
     */
    protected function afterSave()
    {
        // make sure we have an app in the folder
        if (!$this->is_url_external) {
            $appSvc = ServiceHandler::getInstance()->getServiceObject('app');
            if ($appSvc) {
                if (!$appSvc->appExists($this->api_name)) {
                    $appSvc->createApp($this->api_name, $this->name, $this->url);
                }
            }
        }

        parent::afterSave();
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeDelete()
    {
        $currApp = SessionManager::getCurrentAppName();
        // make sure you don't delete yourself
        if ($currApp === $this->api_name) {
            throw new Exception("The current application can not be deleted.");
            //return false;
        }

        $store = ServiceHandler::getInstance()->getServiceObject('app');
        $store->deleteApp($this->api_name);

        return parent::beforeDelete();
    }

    /**
     * {@InheritDoc}
     */
    public function afterFind()
    {
        parent::afterFind();

        // correct data type
        $this->id = intval($this->id);
        $this->is_active = intval($this->is_active);
        $this->is_url_external = intval($this->is_url_external);
        $this->filter_by_device = intval($this->filter_by_device);
        $this->filter_phone = intval($this->filter_phone);
        $this->filter_tablet = intval($this->filter_tablet);
        $this->filter_desktop = intval($this->filter_desktop);
        $this->requires_plugin = intval($this->requires_plugin);
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
            return array('id','name','api_name','description','is_active',
                         'url','is_url_external','import_url',
                         'filter_by_device','filter_phone','filter_tablet','filter_desktop','requires_plugin',
                         'created_date','created_by_id','last_modified_date','last_modified_by_id');
        }
        else {
            // remove any undesired retrievable fields
//            $requested = Utilities::removeOneFromList($requested, 'password', ',');
            return explode(',', $requested);
        }
    }

}