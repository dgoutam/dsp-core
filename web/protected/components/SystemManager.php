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
use Platform\Yii\Utility\Pii;
use Swagger\Annotations as SWG;

/**
 * SystemManager
 * DSP system administration manager
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   swaggerVersion="1.1",
 *   basePath="http://localhost/rest",
 *   resourcePath="/system"
 * )
 *
 */
class SystemManager extends RestService
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const SYSTEM_TABLE_PREFIX = 'df_sys_';

	//*************************************************************************
	//	Members
	//*************************************************************************

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new SystemManager instance
	 *
	 */
	public function __construct()
	{
		$config = array(
			'name'        => 'System Configuration Management',
			'api_name'    => 'system',
			'type'        => 'System',
			'description' => 'Service for system administration.',
			'is_active'   => true,
		);

		parent::__construct( $config );
	}

	// Service interface implementation

	/**
	 * @param string $apiName
	 *
	 * @throws Exception
	 * @return BaseService
	 */
	public function setApiName( $apiName )
	{
		throw new Exception( 'SystemManager API name can not be changed.' );
	}

	/**
	 * @param string $type
	 *
	 * @throws Exception
	 * @return BaseService
	 */
	public function setType( $type )
	{
		throw new Exception( 'SystemManager type can not be changed.' );
	}

	/**
	 * @param string $description
	 *
	 * @throws Exception
	 * @return BaseService
	 */
	public function setDescription( $description )
	{
		throw new Exception( 'SystemManager description can not be changed.' );
	}

	/**
	 * @param boolean $isActive
	 *
	 * @throws Exception
	 * @return BaseService
	 */
	public function setIsActive( $isActive )
	{
		throw new Exception( 'SystemManager active flag can not be changed.' );
	}

	/**
	 * @param string $name
	 *
	 * @throws Exception
	 * @return BaseService
	 */
	public function setName( $name )
	{
		throw new Exception( 'SystemManager name can not be changed.' );
	}

	/**
	 * @param string $nativeFormat
	 *
	 * @throws Exception
	 * @return BaseService
	 */
	public function setNativeFormat( $nativeFormat )
	{
		throw new Exception( 'SystemManager native format can not be changed.' );
	}

	/**
	 * @param string $old
	 * @param string $new
	 * @param bool   $useVersionCompare If true, built-in "version_compare" will be used
	 * @param null   $operator          Operator to pass to version_compare
	 *
	 * @return bool|mixed
	 */
	public static function doesDbVersionRequireUpgrade( $old, $new, $useVersionCompare = false, $operator = null )
	{
		if ( false !== $useVersionCompare )
		{
			return version_compare( $old, $new, $operator );
		}

		return ( 0 !== strcasecmp( $old, $new ) );
	}

	/**
	 * Determines the current state of the system
	 */
	public static function getSystemState()
	{
		// Refresh the schema that we just added
		$_db = Pii::db();
		$_schema = $_db->getSchema();

		$tables = $_schema->getTableNames();

		if ( empty( $tables ) || ( 'df_sys_cache' == Utilities::getArrayValue( 0, $tables ) ) )
		{
			return PlatformStates::INIT_REQUIRED;
		}

		// need to check for db upgrade, based on tables or version
		$contents = file_get_contents( Pii::basePath() . '/data/system_schema.json' );

		if ( !empty( $contents ) )
		{
			$contents = DataFormat::jsonToArray( $contents );

			// check for any missing necessary tables
			$needed = Utilities::getArrayValue( 'table', $contents, array() );

			foreach ( $needed as $table )
			{
				$name = Utilities::getArrayValue( 'name', $table, '' );
				if ( !empty( $name ) && !in_array( $name, $tables ) )
				{
					return PlatformStates::SCHEMA_REQUIRED;
				}
			}

			$version = Utilities::getArrayValue( 'version', $contents );
			$oldVersion = $_db->createCommand()
						  ->select( 'db_version' )->from( 'df_sys_config' )
						  ->order( 'id DESC' )->limit( 1 )
						  ->queryScalar();
			if ( static::doesDbVersionRequireUpgrade( $oldVersion, $version ) )
			{
				return PlatformStates::SCHEMA_REQUIRED;
			}
		}

		// Check for at least one system admin user
		$command = $_db->createCommand()
				   ->select( '(COUNT(*))' )->from( 'df_sys_user' )->where( 'is_sys_admin=:is' );
		if ( 0 == $command->queryScalar( array( ':is' => 1 ) ) )
		{
			return PlatformStates::ADMIN_REQUIRED;
		}

		// Need to check for the default services
		$command = $_db->createCommand()
				   ->select( '(COUNT(*))' )->from( 'df_sys_service' );
		if ( 0 == $command->queryScalar() )
		{
			return PlatformStates::DATA_REQUIRED;
		}

		return PlatformStates::READY;
	}

	/**
	 * Configures the system.
	 *
	 * @return null
	 */
	public static function initSystem()
	{
		static::initSchema( true );
		static::initAdmin();
		static::initData();
	}

	/**
	 * Configures the system schema.
	 *
	 * @param bool $init
	 *
	 * @throws Exception
	 * @return null
	 */
	public static function initSchema( $init = false )
	{
		$_db = Pii::db();

		try
		{
			$contents = file_get_contents( Pii::basePath() . '/data/system_schema.json' );

			if ( empty( $contents ) )
			{
				throw new \Exception( "Empty or no system schema file found." );
			}

			$contents = DataFormat::jsonToArray( $contents );
			$version = Utilities::getArrayValue( 'version', $contents );

			$command = $_db->createCommand();
			$oldVersion = '';
			if ( DbUtilities::doesTableExist( $_db, static::SYSTEM_TABLE_PREFIX . 'config' ) )
			{
				$command->reset();
				$oldVersion = $command->select( 'db_version' )->from( 'df_sys_config' )->queryScalar();
			}

			// create system tables
			$tables = Utilities::getArrayValue( 'table', $contents );
			if ( empty( $tables ) )
			{
				throw new \Exception( "No default system schema found." );
			}

			$result = DbUtilities::createTables( $_db, $tables, true, false );

			if ( !empty( $oldVersion ) )
			{
				// clean up old unique index, temporary for upgrade
				try
				{
					$command->reset();
					$command->dropIndex( 'undx_df_sys_user_username', 'df_sys_user' );
					$command->dropindex( 'ndx_df_sys_user_email', 'df_sys_user' );
				}
				catch ( Exception $_ex )
				{
					Log::error( 'Exception clearing username index: ' . $_ex->getMessage() );
				}
			}
			// initialize config table if not already
			try
			{
				$command->reset();
				if ( empty( $oldVersion ) )
				{
					// first time is troublesome with session user id
					$rows = $command->insert( 'df_sys_config', array( 'db_version' => $version ) );
				}
				else
				{
					$rows = $command->update( 'df_sys_config', array( 'db_version' => $version ) );
				}
				if ( 0 >= $rows )
				{
					throw new Exception( "old_version: $oldVersion new_version: $version" );
				}
			}
			catch ( Exception $_ex )
			{
				Log::error( 'Exception saving database version: ' . $_ex->getMessage() );
			}

			//	Refresh the schema that we just added
			$_db->getSchema()->refresh();
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}

		if ( !$init )
		{
			// clean up session
			static::initAdmin();
		}
	}

	/**
	 * Configures the system.
	 *
	 * @throws Exception
	 * @return null
	 */
	public static function initAdmin()
	{
		$_user = Yii::app()->user;
		$_piiUser = Pii::app()->getUser();

		try
		{
			// Create and login first admin user
			$email = $_user->getState( 'email' );
			$pwd = $_user->getState( 'password' );

			if ( empty( $email ) || empty( $pwd ) )
			{
				Pii::redirect( '/site/login' );
			}

			$theUser = User::model()->find( 'email = :email', array( ':email' => $email ) );

			if ( empty( $theUser ) )
			{
				$theUser = new User();
				$firstName = $_user->getState( 'first_name' );
				$lastName = $_user->getState( 'last_name' );
				$displayName = $_user->getState( 'display_name' );
				$displayName = ( empty( $displayName )
					? $firstName . ( empty( $lastName ) ? '' : ' ' . $lastName )
					: $displayName );
				$fields = array(
					'email'        => $email,
					'password'     => $pwd,
					'first_name'   => $firstName,
					'last_name'    => $lastName,
					'display_name' => $displayName,
					'is_active'    => true,
					'is_sys_admin' => true,
					'confirm_code' => 'y'
				);
			}
			else
			{
				// in case something is messed up
				$fields = array(
					'is_active'    => true,
					'is_sys_admin' => true,
					'confirm_code' => 'y'
				);
			}

			$theUser->setAttributes( $fields );

			// write back login datetime
			$theUser->last_login_date = date( 'c' );
			$theUser->save();

			// update session with current real user
			$_user->setId( $theUser->primaryKey );
			$_user->setState( 'df_authenticated', false ); // removes catch
			$_user->setState( 'password', $pwd, $pwd ); // removes password
		}
		catch ( \Exception $ex )
		{
			throw new Exception( "Failed to create a new user.\n{$ex->getMessage()}", ErrorCodes::BAD_REQUEST );
		}
	}

	/**
	 * Configures the default system data.
	 *
	 * @throws Exception
	 * @return boolean whether configuration is successful
	 */
	public static function initData()
	{
		// init with system required data
		$contents = file_get_contents( Pii::basePath() . '/data/system_data.json' );
		if ( empty( $contents ) )
		{
			throw new \Exception( "Empty or no system data file found." );
		}
		$contents = DataFormat::jsonToArray( $contents );
		foreach ( $contents as $table => $content )
		{
			switch ( $table )
			{
				case 'df_sys_service':
					$result = Service::model()->findAll();
					if ( empty( $result ) )
					{
						if ( empty( $content ) )
						{
							throw new \Exception( "No default system services found." );
						}
						foreach ( $content as $service )
						{
							try
							{
								$obj = new Service;
								$obj->setAttributes( $service );
								$obj->save();
							}
							catch ( Exception $ex )
							{
								throw new Exception( "Failed to create services.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR );
							}
						}
					}
					break;
			}
		}
		// init system with sample setup
		$contents = file_get_contents( Yii::app()->basePath . '/data/sample_data.json' );
		if ( !empty( $contents ) )
		{
			$contents = DataFormat::jsonToArray( $contents );
			foreach ( $contents as $table => $content )
			{
				switch ( $table )
				{
					case 'df_sys_service':
						if ( !empty( $content ) )
						{
							foreach ( $content as $service )
							{
								try
								{
									$obj = new Service;
									$obj->setAttributes( $service );
									$obj->save();
								}
								catch ( Exception $ex )
								{
									Log::error( "Failed to create sample services.\n{$ex->getMessage()}" );
								}
							}
						}
						break;
					case 'app_package':
						$result = App::model()->findAll();
						if ( empty( $result ) )
						{
							if ( !empty( $content ) )
							{
								foreach ( $content as $package )
								{
									$fileUrl = Utilities::getArrayValue( 'url', $package, '' );
									if ( 0 === strcasecmp( 'dfpkg', FileUtilities::getFileExtension( $fileUrl ) ) )
									{
										try
										{
											// need to download and extract zip file and move contents to storage
											$filename = FileUtilities::importUrlFileToTemp( $fileUrl );
											static::importAppFromPackage( $filename, $fileUrl );
										}
										catch ( Exception $ex )
										{
											Log::error( "Failed to import application package $fileUrl.\n{$ex->getMessage()}" );
										}
									}
								}
							}
						}
						break;
				}
			}
		}
	}

	// REST interface implementation

	/**
	 * @SWG\Api(
	 *       path="/system", description="Operations available for system management.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List resources available for system management.",
	 *       notes="See listed operations for each resource available.",
	 *       responseClass="Resources", nickname="getResources"
	 *     )
	 *   )
	 * )
	 *
	 * @return array
	 */
	protected function _listResources()
	{
		$resources = array(
			array( 'name' => 'app', 'label' => 'Application' ),
			array( 'name' => 'app_group', 'label' => 'Application Group' ),
			array( 'name' => 'config', 'label' => 'Configuration' ),
			array( 'name' => 'role', 'label' => 'Role' ),
			array( 'name' => 'service', 'label' => 'Service' ),
			array( 'name' => 'user', 'label' => 'User' ),
			array( 'name' => 'email_template', 'label' => 'Email Template' )
		);

		return array( 'resource' => $resources );
	}

	/**
	 *
	 * @return array|bool
	 * @throws Exception
	 */
	protected function _handleResource()
	{
		switch ( $this->_resource )
		{
			case '':
				switch ( $this->_action )
				{
					case self::Get:
						return $this->_listResources();
						break;
					default:
						return false;
				}
				break;
			case 'config':
				$obj = new SystemConfig();

				return $obj->processRequest( $this->_action );
				break;
			case 'app':
			case 'app_group':
			case 'role':
			case 'service':
			case 'user':
			case 'email_template':
				$_resource = static::getNewResource( $this->_resource );
				$_resource->setResourceArray( $this->_resourceArray );

				return $_resource->processRequest( $this->_action );
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * @param $resource
	 *
	 * @return App|AppGroup|Role|Service|User|EmailTemplate
	 * @throws Exception
	 */
	public static function getResourceModel( $resource )
	{
		switch ( strtolower( $resource ) )
		{
			case 'app':
				$model = App::model();
				break;
			case 'app_group':
			case 'appgroup':
				$model = AppGroup::model();
				break;
			case 'role':
				$model = Role::model();
				break;
			case 'service':
				$model = Service::model();
				break;
			case 'user':
				$model = User::model();
				break;
			case 'email_template':
				$model = EmailTemplate::model();
				break;
			default:
				throw new Exception( "Invalid system resource '$resource' requested.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $model;
	}

	/**
	 * @param $resource
	 *
	 * @return SystemApp|SystemAppGroup|SystemRole|SystemService|SystemUser|SystemEmailTemplate
	 * @throws Exception
	 */
	public static function getNewResource( $resource )
	{
		switch ( strtolower( $resource ) )
		{
			case 'app':
				$obj = new SystemApp;
				break;
			case 'app_group':
			case 'appgroup':
				$obj = new SystemAppGroup;
				break;
			case 'role':
				$obj = new SystemRole;
				break;
			case 'service':
				$obj = new SystemService;
				break;
			case 'user':
				$obj = new SystemUser;
				break;
			case 'email_template':
				$obj = new SystemEmailTemplate();
				break;
			default:
				throw new Exception( "Attempting to create an invalid system resource '$resource'.", ErrorCodes::INTERNAL_SERVER_ERROR );
				break;
		}

		return $obj;
	}

	/**
	 * @param $resource
	 *
	 * @return App|AppGroup|Role|Service|User|EmailTemplate
	 * @throws Exception
	 */
	public static function getNewModel( $resource )
	{
		switch ( strtolower( $resource ) )
		{
			case 'app':
				$obj = new App;
				break;
			case 'app_group':
			case 'appgroup':
				$obj = new AppGroup;
				break;
			case 'role':
				$obj = new Role;
				break;
			case 'service':
				$obj = new Service;
				break;
			case 'user':
				$obj = new User;
				break;
			case 'email_template':
				$obj = new EmailTemplate();
				break;
			default:
				throw new Exception( "Attempting to create an invalid system model '$resource'.", ErrorCodes::INTERNAL_SERVER_ERROR );
				break;
		}

		return $obj;
	}

	//-------- System Records Operations ---------------------
	// records is an array of field arrays

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	protected static function createRecordLow( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record to create.', ErrorCodes::BAD_REQUEST );
		}
		try
		{
			// create DB record
			$obj = static::getNewModel( $table );
			$obj->setAttributes( $record );
			$obj->save();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to create $table.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR );
		}

		try
		{
			$id = $obj->primaryKey;
			if ( empty( $id ) )
			{
				Log::error( 'Failed to get primary key from created user: ' . print_r( $obj, true ) );
				throw new Exception( "Failed to get primary key from created user.", ErrorCodes::INTERNAL_SERVER_ERROR );
			}

			// after record create
			$obj->setRelated( $record, $id );

			$primaryKey = $obj->tableSchema->primaryKey;
			if ( empty( $return_fields ) && empty( $extras ) )
			{
				$data = array( $primaryKey => $id );
			}
			else
			{
				// get returnables
				$obj->refresh();
				$return_fields = $obj->getRetrievableAttributes( $return_fields );
				$data = $obj->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relations = $obj->relations();
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $obj->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}
			}

			return $data;
		}
		catch ( Exception $ex )
		{
			// need to delete the above table entry and clean up
			if ( isset( $obj ) && !$obj->getIsNewRecord() )
			{
				$obj->delete();
			}
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function createRecords( $table, $records, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( !isset( $records[0] ) )
		{ // isArrayNumeric($records)
			// conversion from xml can pull single record out of array format
			$records = array( $records );
		}
		UserSession::checkSessionPermission( 'create', 'system', $table );
		// todo implement rollback
		$out = array();
		foreach ( $records as $record )
		{
			try
			{
				$out[] = static::createRecordLow( $table, $record, $return_fields, $extras );
			}
			catch ( Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function createRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'create', 'system', $table );

		return static::createRecordLow( $table, $record, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	protected static function updateRecordLow( $table, $id, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record to create.', ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $id ) )
		{
			throw new Exception( "Identifying field 'id' can not be empty for update request.", ErrorCodes::BAD_REQUEST );
		}
		$model = static::getResourceModel( $table );
		$obj = $model->findByPk( $id );
		if ( !$obj )
		{
			throw new Exception( "Failed to find the $table resource identified by '$id'.", ErrorCodes::NOT_FOUND );
		}

		$primaryKey = $obj->tableSchema->primaryKey;
		$record = Utilities::removeOneFromArray( $primaryKey, $record );

		try
		{
			$obj->setAttributes( $record );
			$obj->save();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to update user.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR );
		}

		try
		{
			// after record create
			$obj->setRelated( $record, $id );

			if ( empty( $return_fields ) && empty( $extras ) )
			{
				$data = array( $primaryKey => $id );
			}
			else
			{
				// get returnables
				$obj->refresh();
				$return_fields = $model->getRetrievableAttributes( $return_fields );
				$data = $obj->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relations = $obj->relations();
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $obj->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}
			}

			return $data;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function updateRecords( $table, $records, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( !isset( $records[0] ) )
		{
			// conversion from xml can pull single record out of array format
			$records = array( $records );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );
		$out = array();
		foreach ( $records as $record )
		{
			try
			{
				// todo this needs to use $model->getPrimaryKey()
				$id = Utilities::getArrayValue( 'id', $record, '' );
				$out[] = static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
			}
			catch ( Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function updateRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $record ) )
		{
			throw new Exception( 'There is no record in the request.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );
		// todo this needs to use $model->getPrimaryKey()
		$id = Utilities::getArrayValue( 'id', $record, '' );

		return static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param        $record
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function updateRecordsByIds( $table, $id_list, $record, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $record ) )
		{
			throw new Exception( 'There is no record in the request.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		$out = array();
		foreach ( $ids as $id )
		{
			try
			{
				$out[] = static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
			}
			catch ( Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function updateRecordById( $table, $id, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		if ( empty( $record ) )
		{
			throw new Exception( 'There is no record in the request.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'update', 'system', $table );

		return static::updateRecordLow( $table, $id, $record, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	protected static function deleteRecordLow( $table, $id, $return_fields = '', $extras = array() )
	{
		if ( empty( $id ) )
		{
			throw new Exception( "Identifying field 'id' can not be empty for delete request.", ErrorCodes::BAD_REQUEST );
		}
		$model = static::getResourceModel( $table );
		$obj = $model->findByPk( $id );
		if ( !$obj )
		{
			throw new Exception( "Failed to find the $table resource identified by '$id'.", ErrorCodes::NOT_FOUND );
		}
		try
		{
			$obj->delete();
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Failed to delete $table.\n{$ex->getMessage()}", ErrorCodes::INTERNAL_SERVER_ERROR );
		}

		try
		{
			$return_fields = $model->getRetrievableAttributes( $return_fields );
			$data = $obj->getAttributes( $return_fields );
			if ( !empty( $extras ) )
			{
				$relations = $obj->relations();
				$relatedData = array();
				foreach ( $extras as $extra )
				{
					$extraName = $extra['name'];
					if ( !isset( $relations[$extraName] ) )
					{
						throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
					}
					$extraFields = $extra['fields'];
					$relatedRecords = $obj->getRelated( $extraName, true );
					if ( is_array( $relatedRecords ) )
					{
						// an array of records
						$tempData = array();
						if ( !empty( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
							foreach ( $relatedRecords as $relative )
							{
								$tempData[] = $relative->getAttributes( $relatedFields );
							}
						}
					}
					else
					{
						$tempData = null;
						if ( isset( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
							$tempData = $relatedRecords->getAttributes( $relatedFields );
						}
					}
					$relatedData[$extraName] = $tempData;
				}
				if ( !empty( $relatedData ) )
				{
					$data = array_merge( $data, $relatedData );
				}
			}

			return $data;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param bool   $rollback
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function deleteRecords( $table, $records, $rollback = false, $return_fields = '', $extras = array() )
	{
		if ( empty( $records ) )
		{
			throw new Exception( 'There are no record sets in the request.', ErrorCodes::BAD_REQUEST );
		}
		if ( !isset( $records[0] ) )
		{
			// conversion from xml can pull single record out of array format
			$records = array( $records );
		}
		$out = array();
		foreach ( $records as $record )
		{
			if ( !empty( $record ) )
			{
				throw new Exception( 'There are no fields in the record set.', ErrorCodes::BAD_REQUEST );
			}
			$id = Utilities::getArrayValue( 'id', $record, '' );
			try
			{
				$out[] = static::deleteRecordLow( $table, $id, $return_fields, $extras );
			}
			catch ( Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function deleteRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		if ( empty( $record ) )
		{
			throw new Exception( 'There are no fields in the record.', ErrorCodes::BAD_REQUEST );
		}
		$id = Utilities::getArrayValue( 'id', $record, '' );

		return static::deleteRecordById( $table, $id, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function deleteRecordsByIds( $table, $id_list, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'delete', 'system', $table );
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		$out = array();
		foreach ( $ids as $id )
		{
			try
			{
				$out[] = static::deleteRecordLow( $table, $id, $return_fields, $extras );
			}
			catch ( Exception $ex )
			{
				$out[] = array( 'error' => array( 'message' => $ex->getMessage(), 'code' => $ex->getCode() ) );
			}
		}

		return array( 'record' => $out );
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function deleteRecordById( $table, $id, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'delete', 'system', $table );

		return static::deleteRecordLow( $table, $id, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param        $records
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function retrieveRecords( $table, $records, $return_fields = '', $extras = array() )
	{
		if ( isset( $records[0] ) )
		{
			// an array of records
			$ids = array();
			foreach ( $records as $key => $record )
			{
				$id = Utilities::getArrayValue( 'id', $record, '' );
				if ( empty( $id ) )
				{
					throw new Exception( "Identifying field 'id' can not be empty for retrieve record [$key] request." );
				}
				$ids[] = $id;
			}
			$idList = implode( ',', $ids );

			return static::retrieveRecordsByIds( $table, $idList, $return_fields, $extras );
		}
		else
		{
			// single record
			$id = Utilities::getArrayValue( 'id', $records, '' );

			return static::retrieveRecordById( $table, $id, $return_fields, $extras );
		}
	}

	/**
	 * @param        $table
	 * @param        $record
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function retrieveRecord( $table, $record, $return_fields = '', $extras = array() )
	{
		$id = Utilities::getArrayValue( 'id', $record, '' );

		return static::retrieveRecordById( $table, $id, $return_fields, $extras );
	}

	/**
	 * @param        $table
	 * @param string $return_fields
	 * @param string $filter
	 * @param int    $limit
	 * @param string $order
	 * @param int    $offset
	 * @param bool   $include_count
	 * @param bool   $include_schema
	 * @param array  $extras
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function retrieveRecordsByFilter( $table, $return_fields = '', $filter = '',
													$limit = 0, $order = '', $offset = 0,
													$include_count = false, $include_schema = false,
													$extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'read', 'system', $table );
		$model = static::getResourceModel( $table );
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();

		try
		{
			$command = new CDbCriteria();
			//$command->select = $return_fields;
			if ( !empty( $filter ) )
			{
				$command->condition = $filter;
			}
			if ( !empty( $order ) )
			{
				$command->order = $order;
			}
			if ( $offset > 0 )
			{
				$command->offset = $offset;
			}
			if ( $limit > 0 )
			{
				$command->limit = $limit;
			}
			else
			{
				// todo impose a limit to protect server
			}
			$records = $model->findAll( $command );
			$out = array();
			foreach ( $records as $record )
			{
				$data = $record->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $record->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}

				$out[] = $data;
			}

			$results = array( 'record' => $out );
			if ( $include_count || $include_schema )
			{
				// count total records
				if ( $include_count )
				{
					$count = $model->count( $command );
					$results['meta']['count'] = intval( $count );
				}
				// count total records
				if ( $include_schema )
				{
					$results['meta']['schema'] = DbUtilities::describeTable( Yii::app()->db, $model->tableName(), static::SYSTEM_TABLE_PREFIX );
				}
			}

			return $results;
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error retrieving $table records.\nquery: $filter\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param        $table
	 * @param        $id_list
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function retrieveRecordsByIds( $table, $id_list, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'read', 'system', $table );
		$ids = array_map( 'trim', explode( ',', $id_list ) );
		$model = static::getResourceModel( $table );
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();

		try
		{
			$records = $model->findAllByPk( $ids );
			if ( empty( $records ) )
			{
				throw new Exception( "No $table resources with ids '$id_list' could be found", ErrorCodes::NOT_FOUND );
			}
			foreach ( $records as $record )
			{
				$pk = $record->primaryKey;
				$key = array_search( $pk, $ids );
				if ( false === $key )
				{
					throw new Exception( 'Bad returned data from query' );
				}
				$data = $record->getAttributes( $return_fields );
				if ( !empty( $extras ) )
				{
					$relatedData = array();
					foreach ( $extras as $extra )
					{
						$extraName = $extra['name'];
						if ( !isset( $relations[$extraName] ) )
						{
							throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $record->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							// an array of records
							$tempData = array();
							if ( !empty( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
								foreach ( $relatedRecords as $relative )
								{
									$tempData[] = $relative->getAttributes( $relatedFields );
								}
							}
						}
						else
						{
							$tempData = null;
							if ( isset( $relatedRecords ) )
							{
								$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
								$tempData = $relatedRecords->getAttributes( $relatedFields );
							}
						}
						$relatedData[$extraName] = $tempData;
					}
					if ( !empty( $relatedData ) )
					{
						$data = array_merge( $data, $relatedData );
					}
				}

				$ids[$key] = $data;
			}
			foreach ( $ids as $key => $id )
			{
				if ( !is_array( $id ) )
				{
					$message = "A $table resource with id '$id' could not be found.";
					$ids[$key] = array( 'error' => array( 'message' => $message, 'code' => ErrorCodes::NOT_FOUND ) );
				}
			}

			return array( 'record' => $ids );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error retrieving $table records.\n{$ex->getMessage()}", $ex->getCode() );
		}
	}

	/**
	 * @param        $table
	 * @param        $id
	 * @param string $return_fields
	 * @param array  $extras
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function retrieveRecordById( $table, $id, $return_fields = '', $extras = array() )
	{
		if ( empty( $table ) )
		{
			throw new Exception( 'Table name can not be empty.', ErrorCodes::BAD_REQUEST );
		}
		UserSession::checkSessionPermission( 'read', 'system', $table );
		$model = static::getResourceModel( $table );
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();
		$record = $model->findByPk( $id );
		if ( null === $record )
		{
			throw new Exception( 'Record not found.', ErrorCodes::NOT_FOUND );
		}
		try
		{
			$data = $record->getAttributes( $return_fields );
			if ( !empty( $extras ) )
			{
				$relatedData = array();
				foreach ( $extras as $extra )
				{
					$extraName = $extra['name'];
					if ( !isset( $relations[$extraName] ) )
					{
						throw new Exception( "Invalid relation '$extraName' requested.", ErrorCodes::BAD_REQUEST );
					}
					$extraFields = $extra['fields'];
					$relatedRecords = $record->getRelated( $extraName, true );
					if ( is_array( $relatedRecords ) )
					{
						// an array of records
						$tempData = array();
						if ( !empty( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords[0]->getRetrievableAttributes( $extraFields );
							foreach ( $relatedRecords as $relative )
							{
								$tempData[] = $relative->getAttributes( $relatedFields );
							}
						}
					}
					else
					{
						$tempData = null;
						if ( isset( $relatedRecords ) )
						{
							$relatedFields = $relatedRecords->getRetrievableAttributes( $extraFields );
							$tempData = $relatedRecords->getAttributes( $relatedFields );
						}
					}
					$relatedData[$extraName] = $tempData;
				}
				if ( !empty( $relatedData ) )
				{
					$data = array_merge( $data, $relatedData );
				}
			}

			return $data;
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Error retrieving $table records.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @param string $app_id
	 * @param bool   $include_files
	 * @param bool   $include_services
	 * @param bool   $include_schema
	 * @param bool   $include_data
	 *
	 * @throws Exception
	 * @return void
	 */
	public static function exportAppAsPackage( $app_id,
											   $include_files = false,
											   $include_services = false,
											   $include_schema = false,
											   $include_data = false )
	{
		UserSession::checkSessionPermission( 'read', 'system', 'app' );
		$model = App::model();
		if ( $include_services || $include_schema )
		{
			$model->with( 'app_service_relations.service' );
		}
		$app = $model->findByPk( $app_id );
		if ( null === $app )
		{
			throw new Exception( "No database entry exists for this application with id '$app_id'." );
		}
		$fields = array(
			'api_name',
			'name',
			'description',
			'is_active',
			'url',
			'is_url_external',
			'import_url',
			'requires_fullscreen',
			'requires_plugin'
		);
		$record = $app->getAttributes( $fields );
		$app_root = Utilities::getArrayValue( 'api_name', $record );

		try
		{
			$zip = new ZipArchive();
			$tempDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
			$zipFileName = $tempDir . $app_root . '.dfpkg';
			if ( true !== $zip->open( $zipFileName, \ZipArchive::CREATE ) )
			{
				throw new Exception( 'Can not create package file for this application.' );
			}

			// add database entry file
			if ( !$zip->addFromString( 'description.json', json_encode( $record ) ) )
			{
				throw new Exception( "Can not include description in package file." );
			}
			if ( $include_services || $include_schema )
			{
				$serviceRelations = $app->getRelated( 'app_service_relations' );
				if ( !empty( $serviceRelations ) )
				{
					$services = array();
					$schemas = array();
					$serviceFields = array(
						'name',
						'api_name',
						'description',
						'is_active',
						'type',
						'is_system',
						'storage_name',
						'storage_type',
						'credentials',
						'native_format',
						'base_url',
						'parameters',
						'headers',
					);
					foreach ( $serviceRelations as $relation )
					{
						$service = $relation->getRelated( 'service' );
						if ( !empty( $service ) )
						{
							if ( $include_services )
							{
								if ( !Utilities::boolval( $service->getAttribute( 'is_system' ) ) )
								{
									// get service details to restore with app
									$temp = $service->getAttributes( $serviceFields );
									$services[] = $temp;
								}
							}
							if ( $include_schema )
							{
								$component = $relation->getAttribute( 'component' );
								if ( !empty( $component ) )
								{
									// service is probably a db, export table schema if possible
									$serviceName = $service->getAttribute( 'api_name' );
									$serviceType = $service->getAttribute( 'type' );
									switch ( strtolower( $serviceType ) )
									{
										case 'local sql db schema':
										case 'remote sql db schema':
											$db = ServiceHandler::getServiceObject( $serviceName );
											$describe = $db->describeTables( implode( ',', $component ) );
											$temp = array(
												'api_name' => $serviceName,
												'table'    => $describe
											);
											$schemas[] = $temp;
											break;
									}
								}
							}
						}
					}
					if ( !empty( $services ) && !$zip->addFromString( 'services.json', json_encode( $services ) ) )
					{
						throw new Exception( "Can not include services in package file." );
					}
					if ( !empty( $schemas ) && !$zip->addFromString( 'schema.json', json_encode( array( 'service' => $schemas ) ) ) )
					{
						throw new Exception( "Can not include database schema in package file." );
					}
				}
			}
			$isExternal = Utilities::boolval( Utilities::getArrayValue( 'is_url_external', $record, false ) );
			if ( !$isExternal && $include_files )
			{
				// add files
				$_service = ServiceHandler::getServiceObject( 'app' );
				if ( $_service->folderExists( $app_root ) )
				{
					$_service->getFolderAsZip( $app_root, $zip, $zipFileName, true );
				}
			}
			$zip->close();

			$fd = fopen( $zipFileName, "r" );
			if ( $fd )
			{
				$fsize = filesize( $zipFileName );
				$path_parts = pathinfo( $zipFileName );
				header( "Content-type: application/zip" );
				header( "Content-Disposition: filename=\"" . $path_parts["basename"] . "\"" );
				header( "Content-length: $fsize" );
				header( "Cache-control: private" ); //use this to open files directly
				while ( !feof( $fd ) )
				{
					$buffer = fread( $fd, 2048 );
					echo $buffer;
				}
			}
			fclose( $fd );
			unlink( $zipFileName );
			Yii::app()->end();
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @param string $pkg_file
	 * @param string $import_url
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function importAppFromPackage( $pkg_file, $import_url = '' )
	{
		$zip = new ZipArchive();
		if ( true !== $zip->open( $pkg_file ) )
		{
			throw new Exception( 'Error opening zip file.' );
		}
		$data = $zip->getFromName( 'description.json' );
		if ( false === $data )
		{
			throw new Exception( 'No application description file in this package file.' );
		}
		$record = DataFormat::jsonToArray( $data );
		if ( !empty( $import_url ) )
		{
			$record['import_url'] = $import_url;
		}
		try
		{
			$returnData = static::createRecord( 'app', $record, 'id,api_name' );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Could not create the application.\n{$ex->getMessage()}" );
		}
		$id = Utilities::getArrayValue( 'id', $returnData );
		$zip->deleteName( 'description.json' );
		try
		{
			$data = $zip->getFromName( 'services.json' );
			if ( false !== $data )
			{
				$data = DataFormat::jsonToArray( $data );
				try
				{
					$result = static::createRecords( 'service', $data, true );
				}
				catch ( Exception $ex )
				{
					throw new Exception( "Could not create the services.\n{$ex->getMessage()}" );
				}
				$zip->deleteName( 'services.json' );
			}
			$data = $zip->getFromName( 'schema.json' );
			if ( false !== $data )
			{
				$data = DataFormat::jsonToArray( $data );
				$services = Utilities::getArrayValue( 'service', $data, array() );
				if ( !empty( $services ) )
				{
					foreach ( $services as $schemas )
					{
						$serviceName = Utilities::getArrayValue( 'api_name', $schemas, '' );
						$db = ServiceHandler::getServiceObject( $serviceName );
						$tables = Utilities::getArrayValue( 'table', $schemas, array() );
						if ( !empty( $tables ) )
						{
							$result = $db->createTables( $tables, true );
							if ( isset( $result[0]['error'] ) )
							{
								$msg = $result[0]['error']['message'];
								throw new Exception( "Could not create the database tables for this application.\n$msg" );
							}
						}
					}
				}
				else
				{
					// single or multiple tables for one service
					$tables = Utilities::getArrayValue( 'table', $data, array() );
					if ( !empty( $tables ) )
					{
						$serviceName = Utilities::getArrayValue( 'api_name', $data, '' );
						if ( empty( $serviceName ) )
						{
							$serviceName = 'schema'; // for older packages
						}
						$db = ServiceHandler::getServiceObject( $serviceName );
						$result = $db->createTables( $tables, true );
						if ( isset( $result[0]['error'] ) )
						{
							$msg = $result[0]['error']['message'];
							throw new Exception( "Could not create the database tables for this application.\n$msg" );
						}
					}
					else
					{
						// single table with no wrappers - try default schema service
						$table = Utilities::getArrayValue( 'name', $data, '' );
						if ( !empty( $table ) )
						{
							$serviceName = 'schema';
							$db = ServiceHandler::getServiceObject( $serviceName );
							$result = $db->createTables( $data, true );
							if ( isset( $result['error'] ) )
							{
								$msg = $result['error']['message'];
								throw new Exception( "Could not create the database tables for this application.\n$msg" );
							}
						}
					}
				}
				$zip->deleteName( 'schema.json' );
			}
			$data = $zip->getFromName( 'data.json' );
			if ( false !== $data )
			{
				$data = DataFormat::jsonToArray( $data );
				$services = Utilities::getArrayValue( 'service', $data, array() );
				if ( !empty( $services ) )
				{
					foreach ( $services as $service )
					{
						$serviceName = Utilities::getArrayValue( 'api_name', $service, '' );
						$db = ServiceHandler::getServiceObject( $serviceName );
						$tables = Utilities::getArrayValue( 'table', $data, array() );
						foreach ( $tables as $table )
						{
							$tableName = Utilities::getArrayValue( 'name', $table, '' );
							$records = Utilities::getArrayValue( 'record', $table, array() );
							$result = $db->createRecords( $tableName, $records );
							if ( isset( $result['record'][0]['error'] ) )
							{
								$msg = $result['record'][0]['error']['message'];
								throw new Exception( "Could not insert the database entries for table '$tableName'' for this application.\n$msg" );
							}
						}
					}
				}
				else
				{
					// single or multiple tables for one service
					$tables = Utilities::getArrayValue( 'table', $data, array() );
					if ( !empty( $tables ) )
					{
						$serviceName = Utilities::getArrayValue( 'api_name', $data, '' );
						if ( empty( $serviceName ) )
						{
							$serviceName = 'db'; // for older packages
						}
						$db = ServiceHandler::getServiceObject( $serviceName );
						foreach ( $tables as $table )
						{
							$tableName = Utilities::getArrayValue( 'name', $table, '' );
							$records = Utilities::getArrayValue( 'record', $table, array() );
							$result = $db->createRecords( $tableName, $records );
							if ( isset( $result['record'][0]['error'] ) )
							{
								$msg = $result['record'][0]['error']['message'];
								throw new Exception( "Could not insert the database entries for table '$tableName'' for this application.\n$msg" );
							}
						}
					}
					else
					{
						// single table with no wrappers - try default database service
						$tableName = Utilities::getArrayValue( 'name', $data, '' );
						if ( !empty( $tableName ) )
						{
							$serviceName = 'db';
							$db = ServiceHandler::getServiceObject( $serviceName );
							$records = Utilities::getArrayValue( 'record', $data, array() );
							$result = $db->createRecords( $tableName, $records );
							if ( isset( $result['record'][0]['error'] ) )
							{
								$msg = $result['record'][0]['error']['message'];
								throw new Exception( "Could not insert the database entries for table '$tableName'' for this application.\n$msg" );
							}
						}
					}
				}
				$zip->deleteName( 'data.json' );
			}
		}
		catch ( Exception $ex )
		{
			// delete db record
			// todo anyone else using schema created?
			static::deleteRecordById( 'app', $id );
			throw $ex;
		}

		// extract the rest of the zip file into storage
		$_service = ServiceHandler::getServiceObject( 'app' );
		$name = Utilities::getArrayValue( 'api_name', $returnData );
		$result = $_service->extractZipFile( '', $zip );

		return $returnData;
	}

	/**
	 * @param $name
	 * @param $zip_file
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function importAppFromZip( $name, $zip_file )
	{
		$record = array( 'api_name' => $name, 'name' => $name, 'is_url_external' => 0, 'url' => '/index.html' );
		try
		{
			$result = static::createRecord( 'app', $record );
		}
		catch ( Exception $ex )
		{
			throw new Exception( "Could not create the database entry for this application.\n{$ex->getMessage()}" );
		}
		$id = ( isset( $result['id'] ) ) ? $result['id'] : '';

		$zip = new ZipArchive();
		if ( true === $zip->open( $zip_file ) )
		{
			// extract the rest of the zip file into storage
			$dropPath = $zip->getNameIndex( 0 );
			$dropPath = substr( $dropPath, 0, strpos( $dropPath, '/' ) ) . '/';

			$_service = ServiceHandler::getServiceObject( 'app' );
			$_service->extractZipFile( $name . DIRECTORY_SEPARATOR, $zip, false, $dropPath );

			return $result;
		}
		else
		{
			throw new Exception( 'Error opening zip file.' );
		}
	}

	//-------- System Helper Operations -------------------------------------------------

	/**
	 * @param $id
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getAppNameFromId( $id )
	{
		if ( !empty( $id ) )
		{
			try
			{
				$app = App::model()->findByPk( $id );
				if ( isset( $app ) )
				{
					return $app->getAttribute( 'name' );
				}

				return '';
			}
			catch ( Exception $ex )
			{
				throw $ex;
			}
		}
	}

	/**
	 * @param $name
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getAppIdFromName( $name )
	{
		if ( !empty( $name ) )
		{
			try
			{
				$app = App::model()->find( 'name=:name', array( ':name' => $name ) );
				if ( isset( $app ) )
				{
					return $app->getPrimaryKey();
				}

				return '';
			}
			catch ( Exception $ex )
			{
				throw $ex;
			}
		}
	}

	/**
	 * @return string
	 */
	public static function getCurrentAppName()
	{
		return ( isset( $GLOBALS['app_name'] ) ) ? $GLOBALS['app_name'] : '';
	}

	/**
	 * @return string
	 */
	public static function getCurrentAppId()
	{
		return static::getAppIdFromName( static::getCurrentAppName() );
	}
}
