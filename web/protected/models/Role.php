<?php
/**
 * Role.php
 * The system role model for the DSP
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
 * Columns:
 *
 * @property integer             $id
 * @property string              $name
 * @property string              $description
 * @property integer             $is_active
 * @property integer             $default_app_id
 *
 * Relations:
 *
 * @property App                 $default_app
 * @property RoleServiceAccess[] $role_service_accesses
 * @property User[]              $users
 * @property App[]               $apps
 * @property Service[]           $services
 */
class Role extends BaseDspSystemModel
{
	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return Role the static model class
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
		return static::tableNamePrefix() . 'role';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$_rules = array(
			array( 'name', 'required' ),
			array( 'name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false ),
			array( 'is_active, default_app_id', 'numerical', 'integerOnly' => true ),
			array( 'name', 'length', 'max' => 64 ),
			array( 'description', 'safe' ),
			array( 'id, name, is_active, default_app_id', 'safe', 'on' => 'search' ),
		);

		return array_merge( parent::rules(), $_rules );
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$_relations = array(
			'default_app'           => array( self::BELONGS_TO, 'App', 'default_app_id' ),
			'role_service_accesses' => array( self::HAS_MANY, 'RoleServiceAccess', 'role_id' ),
			'users'                 => array( self::HAS_MANY, 'User', 'role_id' ),
			'apps'                  => array( self::MANY_MANY, 'App', 'df_sys_app_to_role(app_id, role_id)' ),
			'services'              => array( self::MANY_MANY, 'Service', 'df_sys_role_service_access(role_id, service_id)' ),
		);

		return array_merge( parent::relations(), $_relations );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		$_labels = array(
			'name'           => 'Name',
			'description'    => 'Description',
			'is_active'      => 'Is Active',
			'default_app_id' => 'Default App',
		);

		return array_merge( parent::attributeLabels(), $_labels );
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$_criteria = new CDbCriteria();

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'name', $this->name, true );
		$_criteria->compare( 'is_active', $this->is_active );
		$_criteria->compare( 'default_app_id', $this->default_app_id );
		$_criteria->compare( 'created_date', $this->created_date, true );
		$_criteria->compare( 'last_modified_date', $this->last_modified_date, true );
		$_criteria->compare( 'created_by_id', $this->created_by_id );
		$_criteria->compare( 'last_modified_by_id', $this->last_modified_by_id );

		return new CActiveDataProvider(
			$this,
			array(
				 'criteria' => $_criteria,
			)
		);
	}

	/**
	 * @param array $values
	 * @param int   $id
	 */
	public function setRelated( $values, $id )
	{
		if ( isset( $values['role_service_accesses'] ) )
		{
			$this->assignRoleServiceAccesses( $id, $values['role_service_accesses'] );
		}

		if ( isset( $values['apps'] ) )
		{
			$this->assignManyToOneByMap( $id, 'app', 'app_to_role', 'role_id', 'app_id', $values['apps'] );
		}

		if ( isset( $values['users'] ) )
		{
			$this->assignManyToOne( $id, 'user', 'role_id', $values['users'] );
		}

		if ( isset( $values['services'] ) )
		{
			$this->assignManyToOneByMap( $id, 'service', 'role_service_access', 'role_id', 'service_id', $values['services'] );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeValidate()
	{
		$this->is_active = intval( $this->is_active );

		return parent::beforeValidate();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeDelete()
	{
		if ( SessionManager::getCurrentRoleId() == $this->getPrimaryKey() )
		{
			throw new Exception( 'The current role may not be deleted.' );
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
	}

	/**
	 * @param string $requested
	 *
	 * @return array
	 */
	public function getRetrievableAttributes( $requested )
	{
		return parent::getRetrievableAttributes(
			$requested,
			array(
				 'name',
				 'description',
				 'is_active',
				 'default_app_id',
			)
		);
	}

	/**
	 * @param       $role_id
	 * @param array $accesses
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function assignRoleServiceAccesses( $role_id, $accesses = array() )
	{
		if ( empty( $role_id ) )
		{
			throw new Exception( 'Role id can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		try
		{
			$accesses = array_values( $accesses ); // reset indices if needed
			$count = count( $accesses );
			// check for dupes before processing
			for ( $key1 = 0; $key1 < $count; $key1++ )
			{
				$access = $accesses[$key1];
				$serviceId = Utilities::getArrayValue( 'service_id', $access, null );
				$component = Utilities::getArrayValue( 'component', $access, '' );
				for ( $key2 = $key1 + 1; $key2 < $count; $key2++ )
				{
					$access2 = $accesses[$key2];
					$serviceId2 = Utilities::getArrayValue( 'service_id', $access2, null );
					$component2 = Utilities::getArrayValue( 'component', $access2, '' );
					if ( ( $serviceId === $serviceId2 ) && ( $component === $component2 ) )
					{
						throw new Exception( "Duplicated service and component combination '$serviceId $component' in role service access.", ErrorCodes::BAD_REQUEST );
					}
				}
			}
			$oldAccesses = RoleServiceAccess::model()->findAll( 'role_id = :rid', array( ':rid' => $role_id ) );
			foreach ( $oldAccesses as $oldAccess )
			{
				$found = false;
				foreach ( $accesses as $key => $access )
				{
					$newServiceId = Utilities::getArrayValue( 'service_id', $access, null );
					$newComponent = Utilities::getArrayValue( 'component', $access, '' );
					if ( ( $newServiceId === $oldAccess->service_id ) &&
						 ( $newComponent === $oldAccess->component )
					)
					{
						// found it, make sure nothing needs to be updated
						$newAccess = Utilities::getArrayValue( 'access', $access, '' );
						if ( ( $oldAccess->access != $newAccess ) )
						{
							$oldAccess->access = $newAccess;
							$oldAccess->save();
						}
						// keeping it, so remove it from the list, as this becomes adds
						unset( $accesses[$key] );
						$found = true;
						continue;
					}
				}
				if ( !$found )
				{
					$oldAccess->delete();
					continue;
				}
			}
			if ( !empty( $accesses ) )
			{
				// add what is leftover
				foreach ( $accesses as $access )
				{
					$newAccess = new RoleServiceAccess;
					if ( $newAccess )
					{
						$newAccess->setAttribute( 'role_id', $role_id );
						$newAccess->setAttributes( $access );
						$newAccess->save();
					}
				}
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating accesses to role assignment.\n{$ex->getMessage()}" );
		}
	}

}