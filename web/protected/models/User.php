<?php

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $first_name
 * @property string $last_name
 * @property string $display_name
 * @property string $email
 * @property string $phone
 * @property integer $is_active
 * @property integer $is_sys_admin
 * @property string $confirm_code
 * @property integer $default_app_id
 * @property integer $role_id
 * @property string $security_question
 * @property string $security_answer
 * @property string $last_login_date
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property App[] $apps_created
 * @property App[] $apps_modified
 * @property AppGroup[] $app_groups_created
 * @property AppGroup[] $app_groups_modified
 * @property Role[] $roles_created
 * @property Role[] $roles_modified
 * @property Service[] $services_created
 * @property Service[] $services_modified
 * @property User[] $users_created
 * @property User[] $users_modified
 * @property User $created_by
 * @property User $last_modified_by
 * @property App $default_app
 * @property Role $role
 */
class User extends CActiveRecord
{
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return User the static model class
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
        return 'user';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('username, first_name, display_name, email', 'required'),
            array('username', 'unique', 'allowEmpty' => false, 'caseSensitive' => false),
            array('email', 'email'),
            array('is_active, is_sys_admin, default_app_id, role_id', 'numerical', 'integerOnly' => true),
            array('username, password, first_name, last_name', 'length', 'max' => 40),
            array('display_name, email, security_answer', 'length', 'max' => 80),
            array('phone', 'length', 'max' => 16),
            array('confirm_code, security_question', 'length', 'max' => 128),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, username, first_name, last_name, display_name, email, phone, is_active, is_sys_admin, confirm_code, default_app_id, role_id, last_login_data, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on' => 'search'),
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
            'apps_created' => array(self::HAS_MANY, 'App', 'created_by_id'),
            'apps_modified' => array(self::HAS_MANY, 'App', 'last_modified_by_id'),
            'app_groups_created' => array(self::HAS_MANY, 'AppGroup', 'created_by_id'),
            'app_groups_modified' => array(self::HAS_MANY, 'AppGroup', 'last_modified_by_id'),
            'roles_created' => array(self::HAS_MANY, 'Role', 'created_by_id'),
            'roles_modified' => array(self::HAS_MANY, 'Role', 'last_modified_by_id'),
            'services_created' => array(self::HAS_MANY, 'Service', 'created_by_id'),
            'services_modified' => array(self::HAS_MANY, 'Service', 'last_modified_by_id'),
            'users_created' => array(self::HAS_MANY, 'User', 'created_by_id'),
            'users_modified' => array(self::HAS_MANY, 'User', 'last_modified_by_id'),
            'created_by' => array(self::BELONGS_TO, 'User', 'created_by_id'),
            'last_modified_by' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
            'default_app' => array(self::BELONGS_TO, 'App', 'default_app_id'),
            'role' => array(self::BELONGS_TO, 'Role', 'role_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'display_name' => 'Display Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'is_active' => 'Is Active',
            'is_sys_admin' => 'Is System Admin',
            'confirm_code' => 'Confirmation Code',
            'default_app_id' => 'Default App',
            'role_id' => 'Role',
            'security_question' => 'Security Question',
            'security_answer' => 'Security Answer',
            'last_login_date' => 'Last Login Date',
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
        $criteria->compare('username', $this->username, true);
        $criteria->compare('first_name', $this->first_name, true);
        $criteria->compare('last_name', $this->last_name, true);
        $criteria->compare('display_name', $this->display_name, true);
        $criteria->compare('email', $this->email, true);
        $criteria->compare('phone', $this->phone, true);
        $criteria->compare('is_active', $this->is_active);
        $criteria->compare('is_sys_admin', $this->is_sys_admin);
        $criteria->compare('confirm_code', $this->confirm_code, true);
        $criteria->compare('default_app_id', $this->default_app_id);
        $criteria->compare('role_id', $this->role_id);
        $criteria->compare('last_login_date', $this->created_date, true);
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
        if ($this->isNewRecord) {
            if (empty($this->confirm_code)) {
                $this->confirm_code = 'y';
            }
            if (empty($this->first_name)) {
                $this->first_name = $this->username;
            }
            if (empty($this->display_name)) {
                $this->display_name = $this->first_name;
                if (!empty($this->last_name))
                    $this->display_name .= ' ' . $this->last_name;
            }
        }
        if (is_bool($this->is_active))
            $this->is_active = intval($this->is_active);
        if (is_bool($this->is_sys_admin))
            $this->is_sys_admin = intval($this->is_sys_admin);

        return parent::beforeValidate();
    }

    /**
     * Overrides base class
     * @return bool
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
     * Overrides base class
     * @return bool
     * @throws Exception
     */
    protected function beforeDelete()
    {
        $currUser = SessionManager::getCurrentUserId();
        // make sure you don't delete yourself
        if ($currUser === $this->getPrimaryKey()) {
            throw new Exception("The current logged in user can not be deleted.");
            //return false;
        }
        // check and make sure this is not the last admin user
        $count = static::model()->count('is_sys_admin=:is and id != :id', array(':is' => 1, ':id' => $this->getPrimaryKey()));
        if (0 >= $count) {
            throw new Exception("The last user set to administrator can not be deleted.");
        }

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
            return array('id','display_name','first_name','last_name',
                         'username','email','phone','is_active','is_sys_admin','role_id','default_app_id',
                         'created_date','created_by_id','last_modified_date','last_modified_by_id');
        }
        else {
            // remove any undesired retrievable fields
            $requested = Utilities::removeOneFromList($requested, 'password', ',');
            $requested = Utilities::removeOneFromList($requested, 'security_question', ',');
            $requested = Utilities::removeOneFromList($requested, 'security_answer', ',');
            $requested = Utilities::removeOneFromList($requested, 'confirm_code', ',');
            return explode(',', $requested);
        }
    }

    public function afterFind()
    {
        parent::afterFind();

        // correct data type
        $this->is_active = intval($this->is_active);
        $this->is_sys_admin = intval($this->is_sys_admin);
    }

}