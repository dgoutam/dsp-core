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

}