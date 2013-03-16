<?php

/**
 * BaseSystemModel.php
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
 * The base system model for the DSP
 */

/**
 * This is the abstract base system model class for all UI facing system tables.
 *
 * The followings are the available columns in UI facing system tables:
 * @property integer $id
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property User $created_by
 * @property User $last_modified_by
 */
abstract class BaseSystemModel extends CActiveRecord
{
    /**
     * @return string the system database table name prefix
     */
    public static function tableNamePrefix()
    {
        return 'df_sys_';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('created_by_id, last_modified_by_id', 'numerical', 'integerOnly' => true),
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
            'created_by' => array(self::BELONGS_TO, 'User', 'created_by_id'),
            'last_modified_by' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'Id',
            'created_date' => 'Created Date',
            'last_modified_date' => 'Last Modified Date',
            'created_by_id' => 'Created By',
            'last_modified_by_id' => 'Last Modified By',
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
        $criteria->compare('created_date', $this->created_date, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_by_id', $this->created_by_id);
        $criteria->compare('last_modified_by_id', $this->last_modified_by_id);

        return new CActiveDataProvider($this, array('criteria' => $criteria,));
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
        // until db's get their timestamp act together
        switch (DbUtilities::getDbDriverType($this->dbConnection)) {
        case DbUtilities::DRV_SQLSRV:
            $dateTime = new CDbExpression('SYSDATETIMEOFFSET()');
            break;
        case DbUtilities::DRV_MYSQL:
        default:
            $dateTime = new CDbExpression('NOW()');
            break;
        }
        if ($this->isNewRecord) {
            $this->created_date = $dateTime;
        }
        $this->last_modified_date = $dateTime;

        // set user tracking
        $userId = SessionManager::getCurrentUserId();
        if ($this->isNewRecord) {
            $this->created_by_id = $userId;
        }
        $this->last_modified_by_id = $userId;


        return parent::beforeSave();
    }

    /**
     * {@InheritDoc}
     */
    protected function afterSave()
    {

        parent::afterSave();
    }

    /**
     * {@InheritDoc}
     */
    protected function beforeDelete()
    {

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
        if (isset($this->created_by_id)) {
            $this->created_by_id = intval($this->created_by_id);
        }
        if (isset($this->last_modified_by_id)) {
            $this->last_modified_by_id = intval($this->last_modified_by_id);
        }
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

        return explode(',', $requested);
    }

    /**
     * @param array $values
     */
    public function setRelated($values, $id)
    {
        /*
        $relations = $obj->relations();
        foreach ($relations as $key=>$related) {
            if (isset($record[$key])) {
                switch ($related[0]) {
                case CActiveRecord::HAS_MANY:
                    $this->assignManyToOne($table, $id, $related[1], $related[2], $record[$key]);
                    break;
                case CActiveRecord::MANY_MANY:
                    $this->assignManyToOneByMap($table, $id, $related[1], 'app_to_role', 'role_id', 'app_id', $record[$key]);
                    break;
                }
            }
        }
        */

    }

    // generic assignments

    /**
     * @param string $one_id
     * @param string $many_table
     * @param string $many_field
     * @param array $many_records
     * @throws Exception
     * @return void
     */
    protected function assignManyToOne($one_id, $many_table, $many_field, $many_records=array())
    {
        $manyModel = SystemManager::getResourceModel($many_table);
        if (empty($one_id)) {
            throw new Exception("The id can not be empty.", ErrorCodes::BAD_REQUEST);
        }
        try {
            $manyObj = SystemManager::getNewResource($many_table);
            $pkField = $manyObj->tableSchema->primaryKey;
            $oldMany = $manyModel->findAll($many_field .' = :oid', array(':oid'=>$one_id));
            foreach ($oldMany as $old) {
                $oldId = $old->primaryKey;
                $found = false;
                foreach ($many_records as $key=>$item) {
                    $id = Utilities::getArrayValue($pkField, $item, '');
                    if ($id == $oldId) {
                        // found it, keeping it, so remove it from the list, as this becomes adds
                        unset($many_records[$key]);
                        $found = true;
                        continue;
                    }
                }
                if (!$found) {
                    $old->setAttribute($many_field, null);
                    $old->save();
                    continue;
                }
            }
            if (!empty($many_records)) {
                // add what is leftover
                foreach ($many_records as $item) {
                    $id = Utilities::getArrayValue($pkField, $item, '');
                    $assigned = $manyModel->findByPk($id);
                    if ($assigned) {
                        $assigned->setAttribute($many_field, $one_id);
                        $assigned->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error updating many to one assignment.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

    /**
     * @param $one_id
     * @param $many_table
     * @param $map_table
     * @param $one_field
     * @param $many_field
     * @param array $many_records
     * @throws Exception
     * @return void
     */
    protected function assignManyToOneByMap($one_id, $many_table, $map_table, $one_field, $many_field, $many_records=array())
    {
        if (empty($one_id)) {
            throw new Exception("The id can not be empty.", ErrorCodes::BAD_REQUEST);
        }
        $map_table = SystemManager::SYSTEM_TABLE_PREFIX . $map_table;
        try {
            $manyObj = SystemManager::getNewResource($many_table);
            $pkManyField = $manyObj->tableSchema->primaryKey;
            $pkMapField = 'id';
            // use query builder
            $command = Yii::app()->db->createCommand();
            $command->select($pkMapField.','.$many_field);
            $command->from($map_table);
            $command->where("$one_field = '$one_id'");
            $maps = $command->queryAll();
            $toDelete = array();
            foreach ($maps as $map) {
                $manyId = Utilities::getArrayValue($many_field, $map, '');
                $id = Utilities::getArrayValue($pkMapField, $map, '');
                $found = false;
                foreach ($many_records as $key=>$item) {
                    $assignId = Utilities::getArrayValue($pkManyField, $item, '');
                    if ($assignId == $manyId) {
                        // found it, keeping it, so remove it from the list, as this becomes adds
                        unset($many_records[$key]);
                        $found = true;
                        continue;
                    }
                }
                if (!$found) {
                    $toDelete[] = $id;
                    continue;
                }
            }
            if (!empty($toDelete)) {
                $command->reset();

                foreach ($toDelete as $key => $id) {
                    // simple delete request
                    $command->reset();
                    $rows = $command->delete($map_table, array('in', $pkMapField, $id));
                }
            }
            if (!empty($many_records)) {
                foreach ($many_records as $item) {
                    $itemId = Utilities::getArrayValue($pkManyField, $item, '');
                    $record = array($many_field=>$itemId, $one_field=>$one_id);
                    // simple update request
                    $command->reset();
                    $rows = $command->insert($map_table, $record);
                    if (0 >= $rows) {
                        throw new Exception("Record insert failed for table '$map_table'.");
                    }
                }
            }
        }
        catch (Exception $ex) {
            throw new Exception("Error updating many to one map assignment.\n{$ex->getMessage()}", $ex->getCode());
        }
    }

}