<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Kisma\Core\Exceptions\StorageException;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;
use Platform\Resources\UserSession;
use Platform\Utility\DataFormat;

/**
 * User.php
 * The system user model for the DSP
 *
 * Columns
 *
 * @property integer    $id
 * @property string     $email
 * @property string     $password
 * @property string     $first_name
 * @property string     $last_name
 * @property string     $display_name
 * @property string     $phone
 * @property integer    $is_active
 * @property integer    $is_sys_admin
 * @property string     $confirm_code
 * @property integer    $default_app_id
 * @property integer    $role_id
 * @property string     $security_question
 * @property string     $security_answer
 * @property string     $last_login_date
 *
 * Relations
 *
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
 * @property App        $default_app
 * @property Role       $role
 */
class User extends BaseDspSystemModel
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

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
		$_rules = array(
			array( 'email, display_name', 'required' ),
			array( 'email, display_name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false ),
			array( 'email', 'email' ),
			array( 'email', 'length', 'max' => 255 ),
			array( 'is_active, is_sys_admin, default_app_id, role_id', 'numerical', 'integerOnly' => true ),
			array( 'password, first_name, last_name, security_answer', 'length', 'max' => 64 ),
			array( 'phone', 'length', 'max' => 32 ),
			array( 'confirm_code, display_name, security_question', 'length', 'max' => 128 ),
		);

		return array_merge( parent::rules(), $_rules );
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$_relations = array(
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

		return array_merge( parent::relations(), $_relations );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		$_labels = array(
			'email'             => 'Email',
			'password'          => 'Password',
			'first_name'        => 'First Name',
			'last_name'         => 'Last Name',
			'display_name'      => 'Display Name',
			'phone'             => 'Phone',
			'is_active'         => 'Is Active',
			'is_sys_admin'      => 'Is System Admin',
			'confirm_code'      => 'Confirmation Code',
			'default_app_id'    => 'Default App',
			'role_id'           => 'Role',
			'security_question' => 'Security Question',
			'security_answer'   => 'Security Answer',
		);

		return array_merge( parent::attributeLabels(), $_labels );
	}

	/** {@InheritDoc} */
	public function setAttributes( $values, $safeOnly = true )
	{
		if ( isset( $values['password'] ) )
		{
			if ( !empty( $values['password'] ) )
			{
				$this->setAttribute( 'password', CPasswordHelper::hashPassword( $values['password'] ) );
			}

			unset( $values['password'] );
		}

		if ( isset( $values['security_answer'] ) )
		{
			if ( !empty( $values['security_answer'] ) )
			{
				$this->setAttribute( 'security_answer', CPasswordHelper::hashPassword( $values['security_answer'] ) );
			}
			unset( $values['security_answer'] );
		}

		parent::setAttributes( $values, $safeOnly );
	}

	/** {@InheritDoc} */
	protected function beforeValidate()
	{
		if ( empty( $this->confirm_code ) && ( !empty( $this->password ) ) )
		{
			$this->confirm_code = 'y';
		}

		if ( $this->isNewRecord )
		{
			if ( empty( $this->first_name ) )
			{
				$temp = strstr( $this->email, '@', true );
				$this->first_name = $temp;
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
		$this->is_active = intval( DataFormat::boolval( $this->is_active ) );
		$this->is_sys_admin = intval( DataFormat::boolval( $this->is_sys_admin ) );
		if ( is_string( $this->role_id ) )
		{
			if ( empty( $this->role_id ) )
			{
				$this->role_id = null;
			}
			else
			{
				$this->role_id = intval( $this->role_id );
			}
		}
		if ( is_string( $this->default_app_id ) )
		{
			if ( empty( $this->default_app_id ) )
			{
				$this->default_app_id = null;
			}
			else
			{
				$this->default_app_id = intval( $this->default_app_id );
			}
		}

		return parent::beforeValidate();
	}

	/** {@InheritDoc} */
	protected function beforeDelete()
	{
		$_id = $this->getPrimaryKey();

		// make sure you don't delete yourself
		if ( $_id == UserSession::getCurrentUserId() )
		{
			throw new StorageException( 'The currently logged in user may not be deleted.' );
		}

		//	Check and make sure this is not the last admin user
		if ( $this->is_sys_admin &&
			 !static::model()->count( 'is_sys_admin = :is AND id != :id', array( ':is' => 1, ':id' => $_id ) )
		)
		{
			throw new StorageException( 'There must be at least one administrative account. This one may not be deleted.' );
		}

		return parent::beforeDelete();
	}

	/**
	 * {@InheritDoc}
	 */
	public function afterFind()
	{
		parent::afterFind();

		//	Correct data type
		$this->is_active = intval( $this->is_active );
		$this->is_sys_admin = intval( $this->is_sys_admin );
	}

	/**
	 * @param string $requested
	 * @param array  $columns
	 * @param array  $hidden
	 *
	 * @return array
	 */
	public function getRetrievableAttributes( $requested, $columns = array(), $hidden = array() )
	{
		$addConfirmCode = UserSession::isSystemAdmin();

		return parent::getRetrievableAttributes(
			$requested,
			array_merge(
				array(
					 'display_name',
					 'first_name',
					 'last_name',
					 'email',
					 'phone',
					 'is_active',
					 'is_sys_admin',
					 'role_id',
					 'default_app_id',
					 ( $addConfirmCode ? 'confirm_code' : '' )
				),
				$columns
			),
			// hide these from the general public
			array_merge(
				array(
					 'password',
					 'security_question',
					 'security_answer'
				),
				$hidden
			)
		);
	}

	/**
	 * @param string $userName
	 * @param string $password
	 *
	 * @return bool|\User
	 */
	public static function authenticate( $userName, $password )
	{
		$_user = static::model()
				 ->with( 'role.role_service_accesses', 'role.apps', 'role.services' )
				 ->findByAttributes(
				array(
					 'email' => $userName
				)
			);

		if ( empty( $_user ) || !CPasswordHelper::verifyPassword( $password, $_user->password ) )
		{
			return false;
		}

		Log::debug( 'Platform user auth: ' . $userName );

		return $_user;
	}
}