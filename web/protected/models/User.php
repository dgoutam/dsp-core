<?php

/**
 * User.php
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
 * The system user model for the DSP
 */

/**
 * This is the model for table "user".
 *
 * @property integer    $id
 * @property string     $username
 * @property string     $password
 * @property string     $first_name
 * @property string     $last_name
 * @property string     $display_name
 * @property string     $email
 * @property string     $phone
 * @property integer    $is_active
 * @property integer    $is_sys_admin
 * @property string     $confirm_code
 * @property integer    $default_app_id
 * @property integer    $role_id
 * @property string     $security_question
 * @property string     $security_answer
 * @property string     $last_login_date
 * @property string     $created_date
 * @property string     $last_modified_date
 * @property integer    $created_by_id
 * @property integer    $last_modified_by_id
 *
 * The followings are the available model relations:
 * @property App[]      $apps_created
 * @property App[]      $apps_modified
 * @property AppGroup[] $app_groups_created
 * @property AppGroup[] $app_groups_modified
 * @property Role[]     $roles_created
 * @property Role[]     $roles_modified
 * @property Service[]  $services_created
 * @property Service[]  $services_modified
 * @property User[]     $users_created
 * @property User[]     $users_modified
 * @property User       $created_by
 * @property User       $last_modified_by
 * @property App        $default_app
 * @property Role       $role
 */
class User extends BaseSystemModel
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return User the static model class
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return static::tableNamePrefix() . 'user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$rules =  array(
			array( 'username, first_name, display_name, email', 'required' ),
			array( 'username, display_name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false ),
			array( 'email', 'email' ),
			array( 'is_active, is_sys_admin, default_app_id, role_id', 'numerical', 'integerOnly' => true ),
			array( 'username, password, first_name, last_name, security_answer', 'length', 'max' => 64 ),
			array( 'email', 'length', 'max' => 255 ),
			array( 'phone', 'length', 'max' => 32 ),
			array( 'confirm_code, display_name, security_question', 'length', 'max' => 128 ),
			array(
				'id, username, first_name, last_name, display_name, email, phone, is_active, is_sys_admin, confirm_code, default_app_id, role_id, last_login_data, created_date, last_modified_date, created_by_id, last_modified_by_id',
				'safe',
				'on' => 'search'
			),
		);

        return array_merge(parent::rules(), $rules);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$relations = array(
			'apps_created'        => array( self::HAS_MANY, 'App', 'created_by_id' ),
			'apps_modified'       => array( self::HAS_MANY, 'App', 'last_modified_by_id' ),
			'app_groups_created'  => array( self::HAS_MANY, 'AppGroup', 'created_by_id' ),
			'app_groups_modified' => array( self::HAS_MANY, 'AppGroup', 'last_modified_by_id' ),
			'roles_created'       => array( self::HAS_MANY, 'Role', 'created_by_id' ),
			'roles_modified'      => array( self::HAS_MANY, 'Role', 'last_modified_by_id' ),
			'services_created'    => array( self::HAS_MANY, 'Service', 'created_by_id' ),
			'services_modified'   => array( self::HAS_MANY, 'Service', 'last_modified_by_id' ),
			'users_created'       => array( self::HAS_MANY, 'User', 'created_by_id' ),
			'users_modified'      => array( self::HAS_MANY, 'User', 'last_modified_by_id' ),
			'default_app'         => array( self::BELONGS_TO, 'App', 'default_app_id' ),
			'role'                => array( self::BELONGS_TO, 'Role', 'role_id' ),
		);

        return array_merge(parent::relations(), $relations);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		$labels = array(
			'username'            => 'Username',
			'password'            => 'Password',
			'first_name'          => 'First Name',
			'last_name'           => 'Last Name',
			'display_name'        => 'Display Name',
			'email'               => 'Email',
			'phone'               => 'Phone',
            'is_active'           => 'Is Active',
            'is_sys_admin'        => 'Is System Admin',
			'confirm_code'        => 'Confirmation Code',
			'default_app_id'      => 'Default App',
			'role_id'             => 'Role',
			'security_question'   => 'Security Question',
			'security_answer'     => 'Security Answer',
		);

        return array_merge(parent::attributeLabels(), $labels);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria = new CDbCriteria;

		$criteria->compare( 'id', $this->id );
		$criteria->compare( 'username', $this->username, true );
		$criteria->compare( 'first_name', $this->first_name, true );
		$criteria->compare( 'last_name', $this->last_name, true );
		$criteria->compare( 'display_name', $this->display_name, true );
		$criteria->compare( 'email', $this->email, true );
		$criteria->compare( 'phone', $this->phone, true );
		$criteria->compare( 'is_active', $this->is_active );
		$criteria->compare( 'is_sys_admin', $this->is_sys_admin );
		$criteria->compare( 'confirm_code', $this->confirm_code, true );
		$criteria->compare( 'default_app_id', $this->default_app_id );
		$criteria->compare( 'role_id', $this->role_id );
		$criteria->compare( 'last_login_date', $this->created_date, true );
		$criteria->compare( 'created_date', $this->created_date, true );
		$criteria->compare( 'last_modified_date', $this->last_modified_date, true );
		$criteria->compare( 'created_by_id', $this->created_by_id );
		$criteria->compare( 'last_modified_by_id', $this->last_modified_by_id );

		return new CActiveDataProvider( $this, array( 'criteria' => $criteria, ) );
	}

    /**
     * {@InheritDoc}
     */
    public function setAttributes($values, $safeOnly=true)
    {
        if (isset($values['password'])) {
            if (!empty($values['password'])) {
                $this->setAttribute('password', CPasswordHelper::hashPassword($values['password']));
            }
            unset($values['password']);
        }
        if (isset($values['security_answer'])) {
            if (!empty($values['security_answer'])) {
                $this->setAttribute('security_answer', CPasswordHelper::hashPassword($values['security_answer']));
            }
            unset($values['security_answer']);
        }

        parent::setAttributes($values, $safeOnly);
    }


    /**
     * @param array $values
     */
    public function setRelated($values, $id)
    {
        // default app id
        // role id
    }

    /**
     * {@InheritDoc}
	 *
	 * @return bool
	 */
	protected function beforeValidate()
	{
		if ( $this->isNewRecord )
		{
			if ( empty( $this->confirm_code ) )
			{
				$this->confirm_code = 'y';
			}
			if ( empty( $this->first_name ) )
			{
				$this->first_name = $this->username;
			}
			if ( empty( $this->display_name ) )
			{
				$this->display_name = $this->first_name;
				if ( !empty( $this->last_name ) )
				{
					$this->display_name .= ' ' . $this->last_name;
				}
			}
		}
		if ( is_bool( $this->is_active ) )
		{
			$this->is_active = intval( $this->is_active );
		}
		if ( is_bool( $this->is_sys_admin ) )
		{
			$this->is_sys_admin = intval( $this->is_sys_admin );
		}

		return parent::beforeValidate();
	}

	/**
     * {@InheritDoc}
	 *
	 * @return bool
	 */
	protected function beforeSave()
	{

		return parent::beforeSave();
	}

	/**
     * {@InheritDoc}
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function beforeDelete()
	{
		$currUser = SessionManager::getCurrentUserId();
		// make sure you don't delete yourself
		if ( $currUser === $this->getPrimaryKey() )
		{
			throw new Exception( "The current logged in user can not be deleted." );
		}
		// check and make sure this is not the last admin user
		$count = static::model()->count( 'is_sys_admin=:is and id != :id', array( ':is' => 1, ':id' => $this->getPrimaryKey() ) );
		if ( 0 >= $count )
		{
			throw new Exception( "The last user set to administrator can not be deleted." );
		}

		return parent::beforeDelete();
	}

	/**
	 * {@InheritDoc}
	 */
	public function afterFind()
	{
		parent::afterFind();

		// correct data type
        $this->is_active = intval( $this->is_active );
		$this->is_sys_admin = intval( $this->is_sys_admin );
	}

    /**
     * @param string $requested
     *
     * @return array
     */
    public function getRetrievableAttributes( $requested )
    {
        if ( empty( $requested ) )
        {
            // primary keys only
            return array( 'id' );
        }
        elseif ( '*' == $requested )
        {
            return array(
                'id',
                'display_name',
                'first_name',
                'last_name',
                'username',
                'email',
                'phone',
                'is_active',
                'is_sys_admin',
                'role_id',
                'default_app_id',
                'created_date',
                'created_by_id',
                'last_modified_date',
                'last_modified_by_id'
            );
        }
        else
        {
            // remove any undesired retrievable fields
            $requested = Utilities::removeOneFromList( $requested, 'password', ',' );
            $requested = Utilities::removeOneFromList( $requested, 'security_question', ',' );
            $requested = Utilities::removeOneFromList( $requested, 'security_answer', ',' );
            $requested = Utilities::removeOneFromList( $requested, 'confirm_code', ',' );

            return explode( ',', $requested );
        }
    }

}