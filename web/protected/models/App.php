<?php

/**
 * This is the model class for table "app".
 *
 * The followings are the available columns in table 'app':
 * @property integer $id
 * @property string $name
 * @property string $label
 * @property string $description
 * @property integer $is_active
 * @property string $url
 * @property integer $is_url_external
 * @property string $app_group_ids
 * @property string $schemas
 * @property integer $filter_by_device
 * @property integer $filter_phone
 * @property integer $filter_tablet
 * @property integer $filter_desktop
 * @property integer $requires_plugin
 * @property string $import_url
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property User $createdBy
 * @property User $lastModifiedBy
 * @property Role[] $roles
 * @property User[] $users
 */
class App extends CActiveRecord
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
        return 'app';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, label', 'required'),
            array('is_active, is_url_external, filter_by_device, filter_phone, filter_tablet, filter_desktop, requires_plugin, created_by_id, last_modified_by_id', 'numerical', 'integerOnly' => true),
            array('name', 'length', 'max' => 40),
            array('label', 'length', 'max' => 80),
            array('description, url, app_group_ids, schemas, import_url', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, name, label, description, is_active, url, is_url_external, app_group_ids, schemas, filter_by_device, filter_phone, filter_tablet, filter_desktop, requires_plugin, import_url, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
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
            'lastModifiedBy' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
            'roles' => array(self::HAS_MANY, 'Role', 'default_app_id'),
            'users' => array(self::HAS_MANY, 'User', 'default_app_id'),
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
            'url' => 'Url',
            'is_url_external' => 'Is Url External',
            'app_group_ids' => 'App Group Ids',
            'schemas' => 'Schemas',
            'filter_by_device' => 'Filter By Device',
            'filter_phone' => 'Filter Phone',
            'filter_tablet' => 'Filter Tablet',
            'filter_desktop' => 'Filter Desktop',
            'requires_plugin' => 'Requires Plugin',
            'import_url' => 'Import Url',
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
        $criteria->compare('url', $this->url, true);
        $criteria->compare('is_url_external', $this->is_url_external);
        $criteria->compare('app_group_ids', $this->app_group_ids, true);
        $criteria->compare('schemas', $this->schemas, true);
        $criteria->compare('filter_by_device', $this->filter_by_device);
        $criteria->compare('filter_phone', $this->filter_phone);
        $criteria->compare('filter_tablet', $this->filter_tablet);
        $criteria->compare('filter_desktop', $this->filter_desktop);
        $criteria->compare('requires_plugin', $this->requires_plugin);
        $criteria->compare('import_url', $this->import_url, true);
        $criteria->compare('created_date', $this->created_date, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_by_id', $this->created_by_id);
        $criteria->compare('last_modified_by_id', $this->last_modified_by_id);

        return new CActiveDataProvider($this, array('criteria' => $criteria,));
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
        $currApp = SessionManager::getCurrentAppName();
        // make sure you don't delete yourself
        if ($currApp === $this->name) {
            throw new Exception("The current application can not be deleted.");
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

    public function afterFind()
    {
        parent::afterFind();

        // correct data type
        $this->is_active = intval($this->is_active);
        $this->is_url_external = intval($this->is_url_external);
        $this->filter_by_device = intval($this->filter_by_device);
        $this->filter_phone = intval($this->filter_phone);
        $this->filter_tablet = intval($this->filter_tablet);
        $this->filter_desktop = intval($this->filter_desktop);
        $this->requires_plugin = intval($this->requires_plugin);
    }
}