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

/**
 * ServiceAuth.php
 * The user service registry model for the DSP
 *
 * Columns:
 *
 * @property int                 $id
 * @property int                 $user_id
 * @property int                 $service_id
 * @property string              $auth_text
 * @property string              $last_use_date
 */
class ServiceAuth extends \BaseDspSystemModel
{
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
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels( $additionalLabels = array() )
	{
		return parent::attributeLabels(
			array_merge(
				$additionalLabels,
				array(
					 'user_id'       => 'User ID',
					 'service_id'    => 'Service ID',
					 'auth_text'     => 'Authorization',
					 'last_use_date' => 'Last Used',
				)
			)
		);
	}

	/**
	 * @param \CModelEvent $event
	 *
	 * @throws \CDbException
	 */
	public function onBeforeSave( $event )
	{
		if ( empty( $this->auth_text ) )
		{
			$this->auth_text = array();
		}

		//	Make sure we can serialize...
		if ( false === ( $_config = json_encode( $this->auth_text ) ) )
		{
			throw new \CDbException( 'The authorization configuration for this service is invalid.' );
		}

		//	Encrypt it...
		$this->auth_text = Hasher::encryptString( $_config, $this->getDb()->password );

		parent::onBeforeSave( $event );
	}

	/**
	 * @param \CModelEvent $event
	 */
	public function onAfterFind( $event )
	{
		if ( empty( $this->auth_text ) )
		{
			$this->auth_text = array();
		}
		else
		{
			//	Decrypt it...
			$this->auth_text = json_decode( Hasher::decryptString( $this->auth_text, $this->getDb()->password ), true ) ? : array();
		}

		parent::onAfterFind( $event );
	}
}