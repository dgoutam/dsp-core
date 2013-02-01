<?php

/**
 * This is the model class for table "role".
 *
 * The followings are the available columns in table 'role':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $app_ids
 * @property integer $default_app_id
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property User $createdBy
 * @property App $defaultApp
 * @property User $lastModifiedBy
 * @property RoleServiceAccess[] $roleServiceAccesses
 * @property User[] $users
 */
class Role extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Role the static model class
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
        return 'role';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name', 'required'),
            array('default_app_id, created_by_id, last_modified_by_id', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 40),
            array('description, app_ids', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, description, app_ids, default_app_id, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
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
            'createdBy' => array(self::BELONGS_TO, 'User', 'created_by_id'),
            'defaultApp' => array(self::BELONGS_TO, 'App', 'default_app_id'),
            'lastModifiedBy' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
            'roleServiceAccesses' => array(self::HAS_MANY, 'RoleServiceAccess', 'role_id'),
            'users' => array(self::HAS_MANY, 'User', 'role_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'app_ids' => 'App Ids',
            'default_app_id' => 'Default App',
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
        $criteria->compare('name', $this->name, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('app_ids', $this->app_ids, true);
        $criteria->compare('default_app_id', $this->default_app_id);
        $criteria->compare('created_date', $this->created_date, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_by_id', $this->created_by_id);
        $criteria->compare('last_modified_by_id', $this->last_modified_by_id);

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
        $userId = SessionManager::getCurrentUserId();
        switch (DbUtilities::getDbDriverType($this->dbConnection->driverName)) {
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
            $this->created_by_id = $userId;
        }
        $this->last_modified_date = $dateTime;
        $this->last_modified_by_id = $userId;

        return parent::beforeSave();
    }

    /**
     * Overrides base class
     * @return bool
     * @throws Exception
     */
    protected function beforeDelete()
    {
        $currRole = SessionManager::getCurrentRoleId();
        // make sure you don't delete yourself
        if ($currRole === $this->getPrimaryKey()) {
            throw new Exception("The current role can not be deleted.");
            //return false;
        }

        return parent::beforeDelete();
    }

    /**
     * @param $fields
     * @return string
     */
    public function checkRetrievableFields($fields)
    {
        if (empty($fields)) {
            $fields = '';
        }
        else {
//            $fields = Utilities::removeOneFromList($fields, 'fieldname', ',');
        }

        return $fields;
    }
}