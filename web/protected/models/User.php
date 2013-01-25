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
 * @property string $created_date
 * @property string $last_modified_date
 * @property integer $created_by_id
 * @property integer $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property App[] $apps
 * @property App[] $apps1
 * @property AppGroup[] $appGroups
 * @property AppGroup[] $appGroups1
 * @property Role[] $roles
 * @property Role[] $roles1
 * @property Service[] $services
 * @property Service[] $services1
 * @property User $createdBy
 * @property User[] $users
 * @property App $defaultApp
 * @property User $lastModifiedBy
 * @property User[] $users1
 * @property Role $role
 */
class User extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
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
			array('username, first_name, last_name, display_name, email, created_date, last_modified_date', 'required'),
			array('is_active, is_sys_admin, default_app_id, role_id, created_by_id, last_modified_by_id', 'numerical', 'integerOnly'=>true),
			array('username, password, first_name, last_name', 'length', 'max'=>40),
			array('display_name, email, security_answer', 'length', 'max'=>80),
			array('phone', 'length', 'max'=>16),
			array('confirm_code, security_question', 'length', 'max'=>128),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, username, password, first_name, last_name, display_name, email, phone, is_active, is_sys_admin, confirm_code, default_app_id, role_id, security_question, security_answer, created_date, last_modified_date, created_by_id, last_modified_by_id', 'safe', 'on'=>'search'),
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
			'apps' => array(self::HAS_MANY, 'App', 'created_by_id'),
			'apps1' => array(self::HAS_MANY, 'App', 'last_modified_by_id'),
			'appGroups' => array(self::HAS_MANY, 'AppGroup', 'created_by_id'),
			'appGroups1' => array(self::HAS_MANY, 'AppGroup', 'last_modified_by_id'),
			'roles' => array(self::HAS_MANY, 'Role', 'created_by_id'),
			'roles1' => array(self::HAS_MANY, 'Role', 'last_modified_by_id'),
			'services' => array(self::HAS_MANY, 'Service', 'created_by_id'),
			'services1' => array(self::HAS_MANY, 'Service', 'last_modified_by_id'),
			'createdBy' => array(self::BELONGS_TO, 'User', 'created_by_id'),
			'users' => array(self::HAS_MANY, 'User', 'created_by_id'),
			'defaultApp' => array(self::BELONGS_TO, 'App', 'default_app_id'),
			'lastModifiedBy' => array(self::BELONGS_TO, 'User', 'last_modified_by_id'),
			'users1' => array(self::HAS_MANY, 'User', 'last_modified_by_id'),
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
			'is_sys_admin' => 'Is Sys Admin',
			'confirm_code' => 'Confirm Code',
			'default_app_id' => 'Default App',
			'role_id' => 'Role',
			'security_question' => 'Security Question',
			'security_answer' => 'Security Answer',
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

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('first_name',$this->first_name,true);
		$criteria->compare('last_name',$this->last_name,true);
		$criteria->compare('display_name',$this->display_name,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('is_active',$this->is_active);
		$criteria->compare('is_sys_admin',$this->is_sys_admin);
		$criteria->compare('confirm_code',$this->confirm_code,true);
		$criteria->compare('default_app_id',$this->default_app_id);
		$criteria->compare('role_id',$this->role_id);
		$criteria->compare('security_question',$this->security_question,true);
		$criteria->compare('security_answer',$this->security_answer,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('last_modified_date',$this->last_modified_date,true);
		$criteria->compare('created_by_id',$this->created_by_id);
		$criteria->compare('last_modified_by_id',$this->last_modified_by_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}