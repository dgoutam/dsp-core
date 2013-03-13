<?php

/**
 * Service.php
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
 * The system service model for the DSP
 */

/**
 * This is the model class for table "service".
 *
 * The followings are the available columns in table 'service':
 * @property integer $id
 * @property string $name
 * @property string $api_name
 * @property string $description
 * @property integer $is_active
 * @property string $type
 * @property string $storage_name
 * @property string $storage_type
 * @property string $credentials
 * @property string $native_format
 * @property string $base_url
 * @property string $parameters
 * @property string $headers
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property RoleServiceAccess[] $roleServiceAccesses
 * @property User $created_by
 * @property User $last_modified_by
 */
class Service extends BaseSystemModel
{

    /**
     * Is this service a system service that should not be deleted
     * or modified in certain ways, i.e. api name and type.
     * @var bool
     */
    protected $is_system = false;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Service the static model class
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
        return static::tableNamePrefix() . 'service';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        $rules =  array(
            array('name, api_name, type', 'required'),
            array('name, api_name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false),
            array('is_active', 'numerical', 'integerOnly' => true),
            array('name, api_name, type, storage_type, native_format', 'length', 'max' => 64),
            array('storage_name', 'length', 'max' => 80),
            array('base_url', 'length', 'max' => 255),
            array('description, credentials, parameters, headers', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, api_name, is_active, type, storage_name, storage_type, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
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
            'role_service_accesses' => array(self::HAS_MANY, 'RoleServiceAccess', 'service_id'),
            'apps' => array(self::MANY_MANY, 'App', 'df_sys_app_to_service(app_id, service_id)'),
            'roles' => array(self::MANY_MANY, 'Role', 'df_sys_role_service_access(service_id, role_id)'),
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
            'type' => 'Type',
            'storage_name' => 'Storage Name',
            'storage_type' => 'Storage Type',
            'credentials' => 'Credentials',
            'native_format' => 'Native Format',
            'base_url' => 'Base Url',
            'parameters' => 'Parameters',
            'headers' => 'Headers',
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
        $criteria->compare('type', $this->type, true);
        $criteria->compare('storage_name', $this->storage_name, true);
        $criteria->compare('storage_type', $this->storage_type, true);
        $criteria->compare('created_date', $this->created_date, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_by_id', $this->created_by_id);
        $criteria->compare('last_modified_by_id', $this->last_modified_by_id);

        return new CActiveDataProvider($this, array(
                                                   'criteria' => $criteria,
                                              ));
    }

    /**
     * {@InheritDoc}
     */
    public function setAttributes($values, $safeOnly=true)
    {
        if (!$this->isNewRecord) {
            if (isset($values['type'])) {
                if (0 !== strcasecmp($this->type, $values['type'])) {
                    throw new Exception('Service type currently can not be modified after creation.', ErrorCodes::BAD_REQUEST);
                }
            }
            if ((0 === strcasecmp('app', $this->api_name)) || (0 === strcasecmp('lib', $this->api_name))) {
                if (isset($values['api_name'])) {
                    if (0 !== strcasecmp($this->api_name, $values['api_name'])) {
                        throw new Exception('Service API name currently can not be modified after creation.', ErrorCodes::BAD_REQUEST);
                    }
                }
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @param array $values
     * @param       $id
     */
    public function setRelated($values, $id)
    {
        if (isset($values['apps'])) {
            $this->assignManyToOneByMap($id, 'app', 'app_to_service', 'service_id', 'app_id', $values['apps']);
        }
        if (isset($values['roles'])) {
            $this->assignManyToOneByMap($id, 'role', 'role_service_access', 'service_id', 'role_id', $values['roles']);
        }
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeValidate()
    {
        // correct data type
        if (is_bool($this->is_active))
            $this->is_active = intval($this->is_active);

        return parent::beforeValidate();
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeSave()
    {

        if (is_array($this->credentials)) {
            $this->credentials = json_encode($this->credentials);
        }
        if (is_array($this->parameters)) {
            $this->parameters = json_encode($this->parameters);
        }
        if (is_array($this->headers)) {
            $this->headers = json_encode($this->headers);
        }

        return parent::beforeSave();
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeDelete()
    {
        // add fake field for client
        $this->is_system = false;
        switch ($this->type) {
        case 'Local SQL DB':
        case 'Local SQL DB Schema':
            throw new Exception('System generated database services can not be deleted.', ErrorCodes::BAD_REQUEST);
            break;
        case 'Local Email Service':
            throw new Exception('System generated email service can not be deleted.', ErrorCodes::BAD_REQUEST);
            break;
        case 'Local File Storage':
            switch ($this->api_name) {
            case 'app':
            case 'lib':
                throw new Exception('System generated storage service can not be deleted.', ErrorCodes::BAD_REQUEST);
                break;
            }
            break;
        }

        return parent::beforeDelete();
    }

    /**
     * {@InheritDoc}
     */
    public function afterFind()
    {
        // correct data type
        $this->is_active = intval($this->is_active);
        // add fake field for client
        $this->is_system = false;
        switch ($this->type) {
        case 'Local SQL DB':
        case 'Local SQL DB Schema':
        case 'Local Email Service':
            $this->is_system = true;
            break;
        case 'Local File Storage':
            switch ($this->api_name) {
            case 'app':
            case 'lib':
                $this->is_system = true;
                break;
            }
            break;
        }
        if (isset($this->credentials)) {
            $this->credentials = json_decode($this->credentials, true);
        }
        if (isset($this->parameters)) {
            $this->parameters = json_decode($this->parameters, true);
        }
        else {
            $this->parameters = array();
        }
        if (isset($this->headers)) {
            $this->headers = json_decode($this->headers, true);
        }
        else {
            $this->headers = array();
        }

        parent::afterFind();
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
            return array('id','name','api_name','description','is_active','type','is_system',
                         'storage_name','storage_type','credentials','native_format',
                         'base_url','parameters','headers',
                         'created_date','created_by_id','last_modified_date','last_modified_by_id');
        }
        else {
            // remove any undesired retrievable fields
//            $requested = Utilities::removeOneFromList($requested, 'password', ',');
            return explode(',', $requested);
        }
    }

}