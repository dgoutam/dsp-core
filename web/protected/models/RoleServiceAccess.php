<?php

/**
 * This is the model class for table "role_service_access".
 *
 * The followings are the available columns in table 'role_service_access':
 * @property integer $id
 * @property integer $role_id
 * @property integer $service_id
 * @property string $service
 * @property string $component
 * @property integer $read
 * @property integer $create
 * @property integer $update
 * @property integer $delete
 *
 * The followings are the available model relations:
 * @property Role $role
 * @property Service $service0
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
        return 'role_service_access';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('role_id, service, component', 'required'),
            array('role_id, service_id, read, create, update, delete', 'numerical', 'integerOnly' => true),
            array('service', 'length', 'max' => 40),
            array('component', 'length', 'max' => 80),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, role_id, service_id, service, component, read, create, update, delete', 'safe', 'on' => 'search'),
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
            'service0' => array(self::BELONGS_TO, 'Service', 'service_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'role_id' => 'Role',
            'service_id' => 'Service',
            'service' => 'Service',
            'component' => 'Component',
            'read' => 'Read',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
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
        $criteria->compare('service', $this->service, true);
        $criteria->compare('component', $this->component, true);
        $criteria->compare('read', $this->read);
        $criteria->compare('create', $this->create);
        $criteria->compare('update', $this->update);
        $criteria->compare('delete', $this->delete);

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
        if (is_bool($this->read))
            $this->read = intval($this->read);
        if (is_bool($this->create))
            $this->create = intval($this->create);
        if (is_bool($this->update))
            $this->update = intval($this->update);
        if (is_bool($this->delete))
            $this->delete = intval($this->delete);

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
            return array('id','role_id','service_id','service','component',
                         'read','create','update','delete');
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

        // correct data type
        $this->read = intval($this->read);
        $this->create = intval($this->create);
        $this->update = intval($this->update);
        $this->delete = intval($this->delete);
    }
}