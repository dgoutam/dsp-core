<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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

use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Services\SystemManager;
use Platform\Services\BaseFileSvc;
use Platform\Utility\DataFormat;
use Platform\Utility\ServiceHandler;
use Platform\Yii\Utility\Pii;

/**
 * App.php
 * This is the model for "df_sys_app".
 *
 * Columns:
 *
 * @property integer              $id
 * @property string               $name
 * @property string               $api_name
 * @property string               $description
 * @property boolean              $is_active
 * @property string               $url
 * @property boolean              $is_url_external
 * @property string               $import_url
 * @property string               $storage_service_id
 * @property string               $storage_container
 * @property string               $launch_url
 * @property boolean              $requires_fullscreen
 * @property boolean              $allow_fullscreen_toggle
 * @property boolean              $toggle_location
 * @property boolean              $requires_plugin
 *
 * Relations:
 *
 * @property Role[]               $roles_default_app
 * @property User[]               $users_default_app
 * @property AppGroup[]           $app_groups
 * @property Role[]               $roles
 * @property AppServiceRelation[] $app_service_relation
 * @property Service[]            $services
 * @property Service              $storage_service
 */
class App extends BaseDspSystemModel
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string The url to launch the app from, comprised of its storage and starting file.
	 */
	protected $launch_url = '';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className active record class name.
	 *
	 * @return App the static model class
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
		return static::tableNamePrefix() . 'app';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array_merge(
			parent::rules(),
			array(
				 array( 'name, api_name', 'required' ),
				 array( 'name, api_name', 'unique', 'allowEmpty' => false, 'caseSensitive' => false ),
				 array(
					 'is_active, is_url_external, requires_fullscreen, requires_plugin, allow_fullscreen_toggle, storage_service_id',
					 'numerical',
					 'integerOnly' => true
				 ),
				 array( 'name, api_name', 'length', 'max' => 64 ),
				 array( 'storage_container', 'length', 'max' => 255 ),
				 array( 'description, url, import_url, toggle_location', 'safe' ),
				 array(
					 'id, name, api_name, is_active, is_url_external, requires_fullscreen, requires_plugin, allow_fullscreen_toggle, toggle_location," .
					 " storage_service_id, storage_container, created_date, last_modified_date, created_by_id, last_modified_by_id',
					 'safe',
					 'on' => 'search'
				 ),
			)
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		$_relations = array(
			'roles_default_app'     => array( self::HAS_MANY, 'Role', 'default_app_id' ),
			'users_default_app'     => array( self::HAS_MANY, 'User', 'default_app_id' ),
			'app_groups'            => array( self::MANY_MANY, 'AppGroup', 'df_sys_app_to_app_group(app_id, app_group_id)' ),
			'roles'                 => array( self::MANY_MANY, 'Role', 'df_sys_app_to_role(app_id, role_id)' ),
			'app_service_relations' => array( self::HAS_MANY, 'AppServiceRelation', 'app_id' ),
			'services'              => array( self::MANY_MANY, 'Service', 'df_sys_app_to_service(app_id, service_id)' ),
			'storage_service'       => array( self::BELONGS_TO, 'Service', 'storage_service_id' ),
		);

		return array_merge( parent::relations(), $_relations );
	}

	/**
	 * @param array $additionalLabels
	 *
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels( $additionalLabels = array() )
	{
		return parent::attributeLabels(
			array_merge(
				$additionalLabels,
				array(
					 'name'                    => 'Name',
					 'api_name'                => 'API Name',
					 'description'             => 'Description',
					 'is_active'               => 'Is Active',
					 'url'                     => 'Url',
					 'is_url_external'         => 'Is Url External',
					 'import_url'              => 'Import Url',
					 'storage_service_id'      => 'Storage Service',
					 'storage_container'       => 'Storage Container',
					 'requires_fullscreen'     => 'Requires Fullscreen',
					 'allow_fullscreen_toggle' => 'Allow Fullscreen Toggle',
					 'toggle_location'         => 'Toggle Location',
					 'requires_plugin'         => 'Requires Plugin',
				)
			)
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @param \CDbCriteria $criteria
	 *
	 * @return \CActiveDataProvider
	 */
	public function search( $criteria = null )
	{
		$_criteria = $criteria ? : new \CDbCriteria;

		$_criteria->compare( 'name', $this->name, true );
		$_criteria->compare( 'api_name', $this->api_name, true );
		$_criteria->compare( 'is_active', $this->is_active );
		$_criteria->compare( 'is_url_external', $this->is_url_external );
		$_criteria->compare( 'import_url', $this->import_url );
		$_criteria->compare( 'storage_service_id', $this->storage_service_id );
		$_criteria->compare( 'storage_container', $this->storage_container );
		$_criteria->compare( 'requires_fullscreen', $this->requires_fullscreen );
		$_criteria->compare( 'allow_fullscreen_toggle', $this->allow_fullscreen_toggle );
		$_criteria->compare( 'toggle_location', $this->toggle_location );
		$_criteria->compare( 'requires_plugin', $this->requires_plugin );

		return parent::search( $criteria );
	}

	/**
	 * @param array $values
	 * @param int   $id
	 */
	public function setRelated( $values, $id )
	{
		if ( null !== ( $_groups = Option::get( $values, 'app_groups' ) ) )
		{
			$this->assignManyToOneByMap( $id, 'app_group', 'app_to_app_group', 'app_id', 'app_group_id', $_groups );
		}

		if ( null !== ( $_roles = Option::get( $values, 'roles' ) ) )
		{
			$this->assignManyToOneByMap( $id, 'role', 'app_to_role', 'app_id', 'role_id', $_roles );
		}

		if ( null !== ( $_relations = Option::get( $values, 'app_service_relations' ) ) )
		{
			$this->assignAppServiceRelations( $id, $_relations );
		}
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeValidate()
	{
		$this->is_active = intval( DataFormat::boolval( $this->is_active ) );
		$this->is_url_external = intval( DataFormat::boolval( $this->is_url_external ) );
		$this->requires_fullscreen = intval( DataFormat::boolval( $this->requires_fullscreen ) );
		$this->allow_fullscreen_toggle = intval( DataFormat::boolval( $this->allow_fullscreen_toggle ) );
		$this->requires_plugin = intval( DataFormat::boolval( $this->requires_plugin ) );

		return parent::beforeValidate();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeSave()
	{
		if ( empty( $this->url ) && !$this->is_url_external && !empty($this->storage_service_id) )
		{
			$this->url = '/index.html';
		}

		return parent::beforeSave();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function afterSave()
	{
		if ( !$this->is_url_external )
		{
			if ( !empty($this->storage_service_id) )
			{
				// make sure we have an app in the folder
				$_protocol = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) ? 'https' : 'http';
				$this->launch_url = $_protocol . '://' . FilterInput::server( 'HTTP_HOST' ) . '/';
				/** @var $_service BaseFileSvc */
				$_service = ServiceHandler::getServiceObjectById( $this->storage_service_id );
				$_container = $this->storage_container;
				if ( empty( $_container ) )
				{
					if ( !$_service->containerExists( $this->api_name ) )
					{
						// create in permanent storage
						$_service->createContainer( array( 'name' => $this->api_name ) );
						$name = ( !empty( $this->name ) ) ? $this->name : $this->api_name;
						$content = "<!DOCTYPE html>\n<html>\n<head>\n<title>" . $name . "</title>\n</head>\n";
						$content .= "<body>\nYour app " . $name . " now lives here.</body>\n</html>";
						$path = ( !empty( $this->url ) ) ? ltrim( $this->url, '/' ) : 'index.html';
						if ( !$_service->fileExists( $this->api_name, $path ) )
						{
							$_service->writeFile( $this->api_name, $path, $content );
						}
					}
				}
				else
				{
					if ( !$_service->containerExists( $_container ) )
					{
						$_service->createContainer( array( 'name' => $_container ) );
					}
					if ( !$_service->folderExists( $_container, $this->api_name ) )
					{
						// create in permanent storage
						$_service->createFolder( $_container, $this->api_name );
						$name = ( !empty( $this->name ) ) ? $this->name : $this->api_name;
						$content = "<!DOCTYPE html>\n<html>\n<head>\n<title>" . $name . "</title>\n</head>\n";
						$content .= "<body>\nYour app " . $name . " now lives here.</body>\n</html>";
						$path = $this->api_name . '/';
						$path .= ( !empty( $this->url ) ) ? ltrim( $this->url, '/' ) : 'index.html';
						if ( !$_service->fileExists( $_container, $path ) )
						{
							$_service->writeFile( $_container, $path, $content );
						}
					}
				}

				/** @var $service Service */
				$service = $this->getRelated( 'storage_service' );
				if ( !empty( $service ) )
				{
					$this->launch_url .= $service->api_name . '/';
				}
				if ( !empty( $this->storage_container ) )
				{
					$this->launch_url .= $this->storage_container . '/';
				}
				$this->launch_url .= $this->api_name . $this->url;
			}
			else
			{
				$this->launch_url = '';
			}
		}
		else
		{
			$this->launch_url = $this->url;
		}

		parent::afterSave();
	}

	/**
	 * {@InheritDoc}
	 */
	protected function beforeDelete()
	{
		$currApp = SystemManager::getCurrentAppName();
		// make sure you don't delete yourself
		if ( $currApp == $this->api_name )
		{
			throw new Exception( "The current application can not be deleted." );
			//return false;
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
		$this->is_url_external = intval( $this->is_url_external );
		$this->requires_fullscreen = intval( $this->requires_fullscreen );
		$this->allow_fullscreen_toggle = intval( $this->allow_fullscreen_toggle );
		$this->requires_plugin = intval( $this->requires_plugin );
		if ( !$this->is_url_external )
		{
			if ( !empty($this->storage_service_id) )
			{
				$_protocol = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) ? 'https' : 'http';
				$this->launch_url = $_protocol . '://' . FilterInput::server( 'HTTP_HOST' ) . '/';
				/** @var $service Service */
				$service = $this->getRelated( 'storage_service' );
				if ( !empty( $service ) )
				{
					$this->launch_url .= $service->api_name . '/';
				}
				if ( !empty( $this->storage_container ) )
				{
					$this->launch_url .= $this->storage_container . '/';
				}
				$this->launch_url .= $this->api_name . $this->url;
			}
		}
		else
		{
			$this->launch_url = $this->url;
		}
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
					 'url',
					 'is_url_external',
					 'import_url',
					 'storage_service_id',
					 'storage_container',
					 'launch_url',
					 'requires_fullscreen',
					 'allow_fullscreen_toggle',
					 'toggle_location',
					 'requires_plugin',
				),
				$columns
			),
			$hidden
		);
	}

	/**
	 * @param       $app_id
	 * @param array $relations
	 *
	 * @throws Exception
	 * @throws BadRequestException
	 * @return void
	 */
	protected function assignAppServiceRelations( $app_id, $relations = array() )
	{
		if ( empty( $app_id ) )
		{
			throw new BadRequestException( 'App id can not be empty.' );
		}
		try
		{
			$relations = array_values( $relations ); // reset indices if needed
			$count = count( $relations );
			// check for dupes before processing
			for ( $key1 = 0; $key1 < $count; $key1++ )
			{
				$access = $relations[$key1];
				$serviceId = Option::get( $access, 'service_id' );
				for ( $key2 = $key1 + 1; $key2 < $count; $key2++ )
				{
					$access2 = $relations[$key2];
					$serviceId2 = Option::get( $access2, 'service_id' );
					if ( $serviceId == $serviceId2 )
					{
						throw new BadRequestException( "Duplicated service in app service relation." );
					}
				}
			}
			$map_table = static::tableNamePrefix() . 'app_to_service';
			$pkMapField = 'id';
			// use query builder
			/** @var CDbCommand $command */
			$command = Pii::db()->createCommand();
			$command->select( 'id,service_id,component' );
			$command->from( $map_table );
			$command->where( 'app_id = :aid' );
			$maps = $command->queryAll( true, array( ':aid' => $app_id ) );
			$toDelete = array();
			$toUpdate = array();
			foreach ( $maps as $map )
			{
				$manyId = Option::get( $map, 'service_id' );
				$id = Option::get( $pkMapField, $map, '' );
				$found = false;
				foreach ( $relations as $key => $item )
				{
					$assignId = Option::get( $item, 'service_id' );
					if ( $assignId == $manyId )
					{
						// found it, keeping it, so remove it from the list, as this becomes adds
						// update if need be
						$oldComponent = Option::get( $map, 'component' );
						$newComponent = Option::get( $item, 'component' );
						if ( !empty( $newComponent ) )
						{
							$newComponent = json_encode( $newComponent );
						}
						else
						{
							$newComponent = null; // no empty arrays here
						}
						// old should be encoded in the db
						if ( $oldComponent != $newComponent )
						{
							$map['component'] = $newComponent;
							$toUpdate[] = $map;
						}
						// otherwise throw it out
						unset( $relations[$key] );
						$found = true;
						continue;
					}
				}
				if ( !$found )
				{
					$toDelete[] = $id;
					continue;
				}
			}
			if ( !empty( $toDelete ) )
			{
				// simple delete request
				$command->reset();
				$rows = $command->delete( $map_table, array( 'in', $pkMapField, $toDelete ) );
			}
			if ( !empty( $toUpdate ) )
			{
				foreach ( $toUpdate as $item )
				{
					$itemId = Option::get( $item, 'id', '', true );
					// simple update request
					$command->reset();
					$rows = $command->update( $map_table, $item, 'id = :id', array( ':id' => $itemId ) );
					if ( 0 >= $rows )
					{
						throw new Exception( "Record update failed." );
					}
				}
			}
			if ( !empty( $relations ) )
			{
				foreach ( $relations as $item )
				{
					// simple insert request
					$newComponent = Option::get( $item, 'component' );
					if ( !empty( $newComponent ) )
					{
						$newComponent = json_encode( $newComponent );
					}
					else
					{
						$newComponent = null; // no empty arrays here
					}
					$record = array(
						'app_id'     => $app_id,
						'service_id' => Option::get( $item, 'service_id' ),
						'component'  => $newComponent
					);
					$command->reset();
					$rows = $command->insert( $map_table, $record );
					if ( 0 >= $rows )
					{
						throw new Exception( "Record insert failed." );
					}
				}
			}
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error updating app to service assignment.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}
}