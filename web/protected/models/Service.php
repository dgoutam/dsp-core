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
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;
use Platform\Exceptions\BadRequestException;
use Platform\Utility\DataFormat;
use Platform\Yii\Utility\Pii;

/**
 * Service.php
 * The system service model for the DSP
 *
 * Columns:
 *
 * @property integer             $id
 * @property string              $name
 * @property string              $api_name
 * @property string              $description
 * @property integer             $is_active
 * @property integer             $is_system
 * @property string              $type
 * @property string              $storage_name
 * @property string              $storage_type
 * @property string              $credentials
 * @property string              $native_format
 * @property string              $base_url
 * @property string              $parameters
 * @property string              $headers
 *
 * Related:
 *
 * @property RoleServiceAccess[] $role_service_accesses
 * @property App[]               $apps
 * @property Role[]              $roles
 * @property AccountProviders[]  $providers
 */
class Service extends BaseDspSystemModel
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var bool Is this service a system service that should not be deleted or modified in certain ways, i.e. api name and type.
	 */
	protected $is_system = false;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return Service the static model class
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
		return static::tableNamePrefix() . 'service';
	}

	/**
	 * Down and dirty service cache which includes the DSP default services.
	 * Clears when saves to services are made
	 *
	 * @param bool $bust If true, bust the cache
	 *
	 * @return array
	 */
	public static function available( $bust = false )
	{
		if ( false !== $bust || null === ( $_serviceCache = Pii::getState( 'dsp.service_cache' ) ) )
		{
			Log::debug( 'Reloading available service cache' );
			$_serviceCache = Pii::getParam( 'dsp.default_services', array() );

			// list all available services from db
			$_command = Pii::db()->createCommand();
			$_tableName = static::model()->tableName();
			$_services = $_command->select( 'api_name,name' )->from( $_tableName )->queryAll();
			$_serviceCache = array_merge(
				$_serviceCache,
				$_services
			);

			Pii::setState( 'dsp.service_cache', $_serviceCache );
		}

		return $_serviceCache;
	}

	/**
	 * Retrieves the record of the particular service
	 *
	 * @access private
	 *
	 * @param string $api_name
	 *
	 * @return array The service record array
	 * @throws \Exception if retrieving of service is not possible
	 */
	public static function getRecordByName( $api_name )
	{
		$_model = static::model()->find( 'api_name = :api_name', array( ':api_name' => trim( strtolower( $api_name ) ) ) );

		return $_model ? $_model->getAttributes() : null;
	}

	/**
	 * Retrieves the record of the particular service
	 *
	 * @param int $id
	 *
	 * @return array The service record array
	 * @throws \Exception if retrieving of service is not possible
	 */
	public static function getRecordById( $id )
	{
		$_model = static::model()->findByPk( $id );

		return $_model ? $_model->getAttributes() : null;
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$_rules = array(
			array( 'name, api_name, type', 'required' ),
			array( 'name, api_name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false ),
			array( 'is_active, type_id, storage_type_id, native_format_id', 'numerical', 'integerOnly' => true ),
			array( 'name, api_name, type, storage_type, native_format', 'length', 'max' => 64 ),
			array( 'storage_name', 'length', 'max' => 80 ),
			array( 'base_url', 'length', 'max' => 255 ),
			array( 'description, credentials, parameters, headers', 'safe' ),
			array(
				'id, name, api_name, is_active, type, type_id, storage_name, storage_type, storage_type_id',
				'safe',
				'on' => 'search'
			),
		);

		return array_merge( parent::rules(), $_rules );
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$_relations = array(
			'role_service_accesses' => array( self::HAS_MANY, 'RoleServiceAccess', 'service_id' ),
			'apps'                  => array( self::MANY_MANY, 'App', 'df_sys_app_to_service(app_id, service_id)' ),
			'roles'                 => array( self::MANY_MANY, 'Role', 'df_sys_role_service_access(service_id, role_id)' ),
			'providers'             => array( static::HAS_MANY, '\\AccountProviders', 'service_id' ),
		);

		return array_merge( parent::relations(), $_relations );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels( $additionalLabels = array() )
	{
		return parent::attributeLabels(
			array_merge(
				$additionalLabels,
				array(
					 'name'             => 'Name',
					 'api_name'         => 'API Name',
					 'description'      => 'Description',
					 'is_active'        => 'Is Active',
					 'is_system'        => 'Is System',
					 'type'             => 'Type',
					 'type_id'          => 'Type ID',
					 'storage_name'     => 'Storage Name',
					 'storage_type'     => 'Storage Type',
					 'storage_type_id'  => 'Storage Type ID',
					 'credentials'      => 'Credentials',
					 'native_format'    => 'Native Format',
					 'native_format_id' => 'Native Format ID',
					 'base_url'         => 'Base Url',
					 'parameters'       => 'Parameters',
					 'headers'          => 'Headers',
				)
			)
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search( $criteria = null )
	{
		$_criteria = $criteria ? : new \CDbCriteria;

		$_criteria->compare( 'name', $this->name, true );
		$_criteria->compare( 'api_name', $this->api_name, true );
		$_criteria->compare( 'is_active', $this->is_active );
		$_criteria->compare( 'type', $this->type, true );
		$_criteria->compare( 'type_id', $this->type_id, true );
		$_criteria->compare( 'storage_name', $this->storage_name, true );
		$_criteria->compare( 'storage_type', $this->storage_type, true );
		$_criteria->compare( 'storage_type_id', $this->type_id, true );

		return parent::search( $_criteria );
	}

	/**
	 * {@InheritDoc}
	 */
	public function setAttributes( $values, $safeOnly = true )
	{
		if ( !$this->isNewRecord )
		{
			if ( isset( $values['type'] ) && 0 !== strcasecmp( $this->type, $values['type'] ) )
			{
				throw new BadRequestException( 'Service type currently can not be modified after creation.' );
			}

			if ( isset( $values['api_name'] ) )
			{
				if ( ( 0 == strcasecmp( 'app', $this->api_name ) ) || ( 0 == strcasecmp( 'email', $this->api_name ) ) )
				{
					if ( 0 != strcasecmp( $this->api_name, $values['api_name'] ) )
					{
						throw new BadRequestException( 'Service API name currently can not be modified after creation.' );
					}
				}
			}
		}

		parent::setAttributes( $values, $safeOnly );
	}

	/**
	 * @param array $values
	 * @param       $id
	 */
	public function setRelated( $values, $id )
	{
		if ( isset( $values['apps'] ) )
		{
			$this->assignManyToOneByMap( $id, 'app', 'app_to_service', 'service_id', 'app_id', $values['apps'] );
		}

		if ( isset( $values['roles'] ) )
		{
			$this->assignManyToOneByMap( $id, 'role', 'role_service_access', 'service_id', 'role_id', $values['roles'] );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeValidate()
	{
		// correct data type
		$this->is_active = intval( DataFormat::boolval( $this->is_active ) );

		return parent::beforeValidate();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeSave()
	{
		if ( is_array( $this->credentials ) )
		{
			$this->credentials = json_encode( $this->credentials );
		}

		if ( is_array( $this->parameters ) )
		{
			$this->parameters = json_encode( $this->parameters );
		}

		if ( is_array( $this->headers ) )
		{
			$this->headers = json_encode( $this->headers );
		}

		return parent::beforeSave();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function afterDelete()
	{
		//	Bust cache
		static::available( true );

		parent::afterDelete();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function afterSave()
	{
		//	Bust cache
		static::available( true );

		parent::afterSave();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeDelete()
	{
		switch ( $this->type )
		{
			case 'Local SQL DB':
			case 'Local SQL DB Schema':
			case 'Local File Storage':
				throw new BadRequestException( 'System generated services can not be deleted.' );
				break;
		}

		return parent::beforeDelete();
	}

	/**
	 * {@InheritDoc}
	 */
	public function afterFind()
	{
		//	Correct data type
		$this->is_active = ( 0 != $this->is_active );

		//	Add fake field for client
		$this->is_system = false;

		switch ( $this->type )
		{
			case 'Local SQL DB':
			case 'Local SQL DB Schema':
			case 'Local File Storage':
				$this->is_system = true;
				break;

			case 'Email Service':
			case 'Local Email Service':
			case 'Remote Email Service':
				switch ( $this->api_name )
				{
					case 'email':
						$this->is_system = true;
						break;
				}
				break;
		}

		if ( isset( $this->credentials ) )
		{
			$this->credentials = json_decode( $this->credentials, true );
		}

		if ( isset( $this->parameters ) )
		{
			$this->parameters = json_decode( $this->parameters, true );
		}
		else
		{
			$this->parameters = array();
		}

		if ( isset( $this->headers ) )
		{
			$this->headers = json_decode( $this->headers, true );
		}
		else
		{
			$this->headers = array();
		}

		parent::afterFind();
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
		return parent::getRetrievableAttributes(
			$requested,
			array_merge(
				array(
					 'name',
					 'api_name',
					 'description',
					 'is_active',
					 'type',
					 'type_id',
					 'is_system',
					 'storage_name',
					 'storage_type',
					 'storage_type_id',
					 'credentials',
					 'native_format',
					 'native_format_id',
					 'base_url',
					 'parameters',
					 'headers',
				),
				$columns
			),
			$hidden
		);
	}
}