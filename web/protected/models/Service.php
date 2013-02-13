<?php

/**
 * This is the model class for table "service".
 *
 * The followings are the available columns in table 'service':
 * @property integer $id
 * @property string $name
 * @property string $label
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
class Service extends CActiveRecord
{
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
        return 'service';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, type', 'required'),
            array('name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false),
            array('is_active, created_by_id, last_modified_by_id', 'numerical', 'integerOnly' => true),
            array('name, type, storage_type, native_format', 'length', 'max' => 40),
            array('label, storage_name', 'length', 'max' => 80),
            array('base_url', 'length', 'max' => 255),
            array('description, credentials, parameters, headers', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, label, description, is_active, type, storage_name, storage_type, credentials, native_format, base_url, parameters, headers, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
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
            'role_service_accesses' => array(self::HAS_MANY, 'RoleServiceAccess', 'service_id'),
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
            'id' => 'ID',
            'name' => 'Name',
            'label' => 'Label',
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
        $criteria->compare('label', $this->label, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('type', $this->type, true);
        $criteria->compare('storage_name', $this->storage_name, true);
        $criteria->compare('storage_type', $this->storage_type, true);
        $criteria->compare('credentials', $this->credentials, true);
        $criteria->compare('native_format', $this->native_format, true);
        $criteria->compare('base_url', $this->base_url, true);
        $criteria->compare('parameters', $this->parameters, true);
        $criteria->compare('headers', $this->headers, true);
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
        // correct data type
        if (is_bool($this->is_active))
            $this->is_active = intval($this->is_active);

        return parent::beforeValidate();
    }

    /**
     * Overrides base class
     * @return bool
     */
    protected function beforeSave()
    {
        $userId = SessionManager::getCurrentUserId();
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
            return array('id','name','label','description','is_active','type',
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

    public function afterFind()
    {
        parent::afterFind();

        // correct data type
        $this->is_active = intval($this->is_active);
    }
}