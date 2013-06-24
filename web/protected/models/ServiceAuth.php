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
use Kisma\Core\Utility\Hasher;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Sql;
use Platform\Exceptions\BadRequestException;
use Platform\Resources\UserSession;
use Platform\Yii\Utility\Pii;

/**
 * ServiceAuth.php
 * The user service registry model for the DSP
 *
 * Columns:
 *
 * @property int                 $id
 * @property int                 $user_id
 * @property int                 $registry_id
 * @property int                 $service_type_nbr
 * @property string              $service_name_text
 * @property string              $service_tag_text
 * @property string              $service_config_text
 * @property int                 $enabled_ind
 * @property string              $last_use_date
 */
class ServiceAuth extends BaseDspSystemModel
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const CACHE_ID = 'dsp.service_auth_cache';
	/**
	 * @var string
	 */
	const CONFIG_ID = 'dsp.default_user_services';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return ServiceAuth the static model class
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
		return static::tableNamePrefix() . 'service_auth';
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
		if ( false !== $bust || null === ( $_serviceCache = Pii::getState( static::CACHE_ID ) ) )
		{
			Log::debug( 'Reloading available service registry cache' );
			$_serviceCache = Pii::getParam( static::CONFIG_ID, array() );

			//	List all available registered services
			$_sql = <<<MYSQL
SELECT
	service_tag_text,
	service_name_text
FROM
	df_sys_registry
ORDER BY
	2
MYSQL;

			$_services = Sql::findAll( $_sql, array( ':user_id' => UserSession::getCurrentUserId() ), Pii::pdo() );

			if ( !empty( $_services ) )
			{

				$_serviceCache = array_merge(
					$_serviceCache,
					$_services
				);

				Pii::setState( static::CACHE_ID, $_serviceCache );
			}
		}

		return $_serviceCache;
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		$_rules = array(
			array( 'service_name_text, service_tag_text, service_type_nbr', 'required' ),
			array( 'enabled_ind', 'numerical', 'integerOnly' => true ),
		);

		return array_merge( parent::rules(), $_rules );
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$_relations = array( //			'role_service_accesses' => array( self::HAS_MANY, 'RoleServiceAccess', 'service_id' ),
//			'apps'                  => array( self::MANY_MANY, 'App', 'df_sys_app_to_service(app_id, service_id)' ),
//			'roles'                 => array( self::MANY_MANY, 'Role', 'df_sys_role_service_access(service_id, role_id)' ),
		);

		return array_merge( parent::relations(), $_relations );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		$_labels = array(
			'service_name_text' => 'Name',
			'service_tag_text'  => 'Short Name',
			'service_type_nbr'  => 'Type',
			'description'       => 'Description',
			'enabled_ind'       => 'Enabled',
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
		$_criteria = new \CDbCriteria();

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'service_type_nbr', $this->service_type_nbr, true );
		$_criteria->compare( 'service_name_text', $this->service_name_text, true );
		$_criteria->compare( 'service_tag_text', $this->service_tag_text );
		$_criteria->compare( 'created_date', $this->created_date, true );
		$_criteria->compare( 'last_modified_date', $this->last_modified_date, true );
		$_criteria->compare( 'created_by_id', $this->created_by_id );
		$_criteria->compare( 'last_modified_by_id', $this->last_modified_by_id );

		return new \CActiveDataProvider(
			$this,
			array(
				 'criteria' => $_criteria,
			)
		);
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeValidate()
	{
		// correct data type
		$this->enabled_ind = ( false === $this->enabled_ind ? 0 : 1 );

		return parent::beforeValidate();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeSave()
	{
		if ( empty( $this->service_config_text ) )
		{
			$this->service_config_text = array();
		}

		//	Make sure we can serialize...
		if ( false === ( $_config = json_encode( $this->service_config_text ) ) )
		{
			throw new \CDbException( 'The configuration for this service is invalid.' );
		}

		//	Encrypt it...
		$this->service_config_text = Hasher::encryptString( $_config, $this->getDb()->password );

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
				throw new BadRequestException( 'System generated database services can not be deleted.' );
			case 'Local File Storage':
				switch ( $this->api_name )
				{
					case 'app':
						throw new BadRequestException( 'System generated application storage service can not be deleted.' );
				}
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
		$this->enabled_ind = ( 0 != $this->enabled_ind );

		if ( empty( $this->service_config_text ) )
		{
			$this->service_config_text = array();
		}
		else
		{
			//	Decrypt it...
			if ( false === ( $_config = json_decode( Hasher::decryptString( $this->service_config_text, $this->getDb()->password ) ) ) )
			{
				$this->service_config_text = array();
			}
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
					 'service_name_text',
					 'service_tag_text',
					 'service_type_nbr',
				),
				$columns
			),
			$hidden
		);
	}

	/**
	 * Named scope for easy lookups
	 *
	 * @param int    $userId
	 * @param string $serviceTag
	 *
	 * @return $this
	 */
	public function userTag( $userId, $serviceTag )
	{
		$this->getDbCriteria()->mergeWith(
			array(
				 'condition' => 'service_tag_text = :service_tag_text and user_id = :user_id',
				 'params'    => array(
					 ':service_tag_text' => $serviceTag,
					 ':user_id'          => $userId,
				 )
			)
		);

		return $this;
	}
}