<?php

use Kisma\Core\Utility\Log;

/**
 * SystemManager.php
 * DSP system administration manager
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2009-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class SystemManager implements iRestHandler
{

	// constants
	/**
	 *
	 */
	const SYSTEM_TABLE_PREFIX = 'df_sys_';

	// Members

	/**
	 * @var SystemManager
	 */
	private static $_instance = null;

	/**
	 * @var
	 */
	protected $modelName;

	/**
	 * @var
	 */
	protected $modelId;

	/**
	 * @var
	 */
	protected $relatedModelName;

	/**
	 * Creates a new SystemManager instance
	 *
	 */
	public function __construct()
	{
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
	}

	/**
	 * Gets the static instance of this class.
	 *
	 * @return SystemManager
	 */
	public static function getInstance()
	{
		if ( !isset( self::$_instance ) )
		{
			self::$_instance = new SystemManager();
		}

		return self::$_instance;
	}

	/**
	 * For compatibility with CommonServices
	 *
	 * @return string
	 */
	public static function getType()
	{
		return 'System';
	}

	/**
	 * @param $old
	 * @param $new
	 *
	 * @return bool
	 */
	public static function doesDbVersionRequireUpgrade( $old, $new )
	{
		// todo need to be fancier here
		return ( 0 !== strcasecmp( $old, $new ) );
	}

	/**
	 * Determines the current state of the system
	 */
	public static function getSystemState()
	{
		try
		{
			// refresh the schema that we just added
			Yii::app()->db->schema->refresh();
			$tables = Yii::app()->db->schema->getTableNames();
			if ( empty( $tables ) || ( 'df_sys_cache' == Utilities::getArrayValue( 0, $tables ) ) )
			{
				return 'init required';
			}

			// need to check for db upgrade, based on tables or version
			$contents = file_get_contents( Yii::app()->basePath . '/data/system_schema.json' );
			if ( !empty( $contents ) )
			{
				$contents = Utilities::jsonToArray( $contents );
				// check for any missing necessary tables
				$needed = Utilities::getArrayValue( 'table', $contents, array() );
				foreach ( $needed as $table )
				{
					$name = Utilities::getArrayValue( 'name', $table, '' );
					if ( !empty( $name ) && !in_array( $name, $tables ) )
					{
						return 'schema required';
					}
				}
				$version = Utilities::getArrayValue( 'version', $contents );
				$oldVersion = '';
				$config = Config::model()->find();
				if ( isset( $config ) )
				{
					$oldVersion = $config->getAttribute( 'db_version' );
				}
				if ( static::doesDbVersionRequireUpgrade( $oldVersion, $version ) )
				{
					return 'schema required';
				}
			}

			// check for at least one system admin user
			$theUser = User::model()->find( 'is_sys_admin=:is', array( ':is' => 1 ) );
			if ( null === $theUser )
			{
				return 'admin required';
			}
			$result = Service::model()->findAll();
			if ( empty( $result ) )
			{
				return 'data required';
			}

			return 'ready';
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * Configures the system.
	 *
	 * @param array $data
	 *
	 * @return null
	 */
	public static function initSystem( $data = array() )
	{
		static::initSchema();
        static::initAdmin( $data );
        static::initData();
	}

	/**
	 * Configures the system schema.
	 *
	 * @throws Exception
	 * @return null
	 */
	public static function initSchema()
	{
		try
		{
			$contents = file_get_contents( Yii::app()->basePath . '/data/system_schema.json' );
			if ( empty( $contents ) )
			{
				throw new \Exception( "Empty or no system schema file found." );
			}
			$contents = Utilities::jsonToArray( $contents );
			$version = Utilities::getArrayValue( 'version', $contents );
			$config = null;
			$oldVersion = '';
			if ( DbUtilities::doesTableExist( Yii::app()->db, static::SYSTEM_TABLE_PREFIX . 'config' ) )
			{
				$config = Config::model()->find();
				if ( isset( $config ) )
				{
					$oldVersion = $config->getAttribute( 'db_version' );
				}
			}
			// create system tables
			$tables = Utilities::getArrayValue( 'table', $contents );
			if ( empty( $tables ) )
			{
				throw new \Exception( "No default system schema found." );
			}
			$result = DbUtilities::createTables( Yii::app()->db, $tables, true, false );

			// setup session stored procedure
			$command = Yii::app()->db->createCommand();
//            $query = 'SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
//                      WHERE ROUTINE_TYPE="PROCEDURE"
//                          AND ROUTINE_SCHEMA="dreamfactory"
//                          AND ROUTINE_NAME="UpdateOrInsertSession";';
//            $result = $db->singleSqlQuery($query);
//            if ((empty($result)) || !isset($result[0]['ROUTINE_NAME'])) {
			switch ( DbUtilities::getDbDriverType( Yii::app()->db ) )
			{
				case DbUtilities::DRV_SQLSRV:
					$contents = file_get_contents( Yii::app()->basePath . '/data/procedures.mssql.sql' );
					if ( ( false === $contents ) || empty( $contents ) )
					{
						throw new \Exception( "Empty or no system db procedures file found." );
					}
					$query
						= "IF ( OBJECT_ID('dbo.UpdateOrInsertSession') IS NOT NULL )
                                  DROP PROCEDURE dbo.UpdateOrInsertSession";
					$command->setText( $query );
					$command->execute();
					$command->reset();
					$command->setText( $contents );
					$command->execute();
					break;
				case DbUtilities::DRV_MYSQL:
					$contents = file_get_contents( Yii::app()->basePath . '/data/procedures.mysql.sql' );
					if ( ( false === $contents ) || empty( $contents ) )
					{
						throw new \Exception( "Empty or no system db procedures file found." );
					}
					$query = 'DROP PROCEDURE IF EXISTS `UpdateOrInsertSession`';
					$command->setText( $query );
					$command->execute();
					$command->reset();
					$command->setText( $contents );
					$command->execute();
					break;
				default:
					break;
			}
//            }
			// initialize config table if not already
			try
			{
				if ( !isset( $config ) )
				{
					// first time is troublesome with session user id
					$command->reset();
					$rows = $command->insert( Config::tableNamePrefix() . 'config', array('db_version' => $version) );
					if ( 0 >= $rows )
					{
						throw new Exception( "Record insert failed." );
					}
				}
				else
				{
					$config->db_version = $version;
					$config->save();
				}
			}
			catch ( CDbException $_ex )
			{
				Log::error( 'Exception saving database version: ' . $_ex->getMessage() );
			}
			// refresh the schema that we just added
			Yii::app()->db->schema->refresh();
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * Configures the system.
	 *
	 * @param array $data
	 *
	 * @throws Exception
	 * @return null
	 */
	public static function initAdmin( $data = array() )
	{
		try
		{
			// create and login first admin user
			// fill out the user fields for creation
			$username = Utilities::getArrayValue( 'username', $data );
			$theUser = User::model()->find( 'username=:un', array( ':un' => $username ) );
			if ( null !== $theUser )
			{
				throw new Exception( "A user already exists with the username '$username'.", ErrorCodes::BAD_REQUEST );
			}
			$firstName = Utilities::getArrayValue( 'firstName', $data );
			$lastName = Utilities::getArrayValue( 'lastName', $data );
			$displayName = Utilities::getArrayValue( 'displayName', $data );
			$displayName = ( empty( $displayName ) ? $firstName . ( empty( $lastName ) ? '' : ' ' . $lastName )
				: $displayName );
			$pwd = Utilities::getArrayValue( 'password', $data, '' );
			$fields = array(
				'username'     => $username,
				'email'        => Utilities::getArrayValue( 'email', $data ),
				'password'     => $pwd,
				'first_name'   => $firstName,
				'last_name'    => $lastName,
				'display_name' => $displayName,
				'is_active'    => true,
				'is_sys_admin' => true,
				'confirm_code' => 'y'
			);
			try
			{
				$user = new User();
				$user->setAttributes( $fields );
				// write back login datetime
				$user->last_login_date = new CDbExpression( 'NOW()' );
				$user->save();
			}
			catch ( Exception $ex )
			{
				throw new Exception( "Failed to create a new user.\n{$ex->getMessage()}", ErrorCodes::BAD_REQUEST );
			}
			$userId = $user->getPrimaryKey();
			// set session for first user
			$result = SessionManager::generateSessionData( $userId );
			$_SESSION = array( 'public' => Utilities::getArrayValue( 'public', $result, array() ) );
			$GLOBALS['write_session'] = true;
			$data = session_encode();
			$sess_name = session_name();
			if ( isset( $_COOKIE[$sess_name] ) )
			{
				SessionManager::write( $_COOKIE[$sess_name], $data );
			}
			else
			{
				error_log( 'Failed to create first admin session in db.' );
			}
		}
		catch ( \Exception $ex )
		{
			throw $ex;
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
		// for now use the first admin we find
		$theUser = User::model()->find( 'is_sys_admin=:is', array( ':is' => 1 ) );
		if ( null === $theUser )
		{
			throw new \Exception( "Failed to retrieve admin user." );
		}
		$userId = $theUser->getPrimaryKey();
		if ( empty( $userId ) )
		{
			error_log( "Failed to retrieve user id.\n" . print_r( $theUser, true ) );
			throw new \Exception( "Failed to retrieve user id." );
		}
		SessionManager::setCurrentUserId( $userId );

		// init with system required data
		$contents = file_get_contents( Yii::app()->basePath . '/data/system_data.json' );
		if ( empty( $contents ) )
		{
			throw new \Exception( "Empty or no system data file found." );
		}
		$contents = Utilities::jsonToArray( $contents );
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
			$contents = Utilities::jsonToArray( $contents );
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

	// Controller based methods

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionSwagger()
	{
		try
		{
			$this->detectCommonParams();

			$result = SwaggerUtilities::swaggerBaseInfo( 'system' );
			$resources = array(
				array(
					'path'        => '/system',
					'description' => '',
					'operations'  => array(
						array(
							"httpMethod"     => "GET",
							"summary"        => "List resources available in the system service",
							"notes"          => "Use these resources for system administration.",
							"responseClass"  => "array",
							"nickname"       => "getResources",
							"parameters"     => array(),
							"errorResponses" => array()
						),
					)
				)
			);
			$resources = array_merge(
				$resources,
				SwaggerUtilities::swaggerPerResource( 'system', 'app' ),
				SwaggerUtilities::swaggerPerResource( 'system', 'app_group' ),
				SwaggerUtilities::swaggerPerResource( 'system', 'role' ),
				SwaggerUtilities::swaggerPerResource( 'system', 'service' ),
				SwaggerUtilities::swaggerPerResource( 'system', 'user' )
			);
			$result['apis'] = $resources;

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionGet()
	{
		try
		{
			$this->detectCommonParams();
			switch ( $this->modelName )
			{
				case '':
					$result = array(
						array( 'name' => 'app', 'label' => 'Application' ),
						array( 'name' => 'app_group', 'label' => 'Application Group' ),
						array( 'name' => 'config', 'label' => 'Configuration' ),
						array( 'name' => 'role', 'label' => 'Role' ),
						array( 'name' => 'service', 'label' => 'Service' ),
						array( 'name' => 'user', 'label' => 'User' )
					);
					$result = array( 'resource' => $result );
					break;
				case 'app':
				case 'app_group':
				case 'role':
				case 'service':
				case 'user':
					// Most requests contain 'returned fields' parameter, all by default
					$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '*' );
					$extras = array();
					$related = Utilities::getArrayValue( 'related', $_REQUEST, '' );
					if ( !empty( $related ) )
					{
						$related = array_map( 'trim', explode( ',', $related ) );
						foreach ( $related as $relative )
						{
							$extraFields = Utilities::getArrayValue( $relative . '_fields', $_REQUEST, '*' );
							$extraOrder = Utilities::getArrayValue( $relative . '_order', $_REQUEST, '' );
							$extras[] = array( 'name' => $relative, 'fields' => $extraFields, 'order' => $extraOrder );
						}
					}

					if ( empty( $this->modelId ) )
					{
						$ids = Utilities::getArrayValue( 'ids', $_REQUEST, '' );
						if ( !empty( $ids ) )
						{
							$result = static::retrieveRecordsByIds( $this->modelName, $ids, $fields, $extras );
						}
						else
						{ // get by filter or all
							$data = Utilities::getPostDataAsArray();
							if ( !empty( $data ) )
							{ // complex filters or large numbers of ids require post
								$ids = Utilities::getArrayValue( 'ids', $data, '' );
								if ( !empty( $ids ) )
								{
									$result = static::retrieveRecordsByIds( $this->modelName, $ids, $fields, $extras );
								}
								else
								{
									$records = Utilities::getArrayValue( 'record', $data, null );
									if ( empty( $records ) )
									{
										// xml to array conversion leaves them in plural wrapper
										$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
									}
									if ( !empty( $records ) )
									{
										// passing records to have them updated with new or more values, id field required
										// for single record and no id field given, get records matching given fields
										$result = static::retrieveRecords( $this->modelName, $records, $fields, $extras );
									}
									else
									{ // if not specified use filter
										$filter = Utilities::getArrayValue( 'filter', $data, '' );
										$limit = intval( Utilities::getArrayValue( 'limit', $data, 0 ) );
										$order = Utilities::getArrayValue( 'order', $data, '' );
										$offset = intval( Utilities::getArrayValue( 'offset', $data, 0 ) );
										$include_count = Utilities::boolval( Utilities::getArrayValue( 'include_count', $data, false ) );
										$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $data, false ) );
										$result = static::retrieveRecordsByFilter(
											$this->modelName,
											$fields,
											$filter,
											$limit,
											$order,
											$offset,
											$include_count,
											$include_schema,
											$extras
										);
									}
								}
							}
							else
							{
								$filter = Utilities::getArrayValue( 'filter', $_REQUEST, '' );
								$limit = intval( Utilities::getArrayValue( 'limit', $_REQUEST, 0 ) );
								$order = Utilities::getArrayValue( 'order', $_REQUEST, '' );
								$offset = intval( Utilities::getArrayValue( 'offset', $_REQUEST, 0 ) );
								$include_count = Utilities::boolval( Utilities::getArrayValue( 'include_count', $_REQUEST, false ) );
								$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $_REQUEST, false ) );
								$result = static::retrieveRecordsByFilter(
									$this->modelName,
									$fields,
									$filter,
									$limit,
									$order,
									$offset,
									$include_count,
									$include_schema,
									$extras
								);
							}
						}
					}
					else
					{
						if ( 0 == strcasecmp( 'app', $this->modelName ) )
						{
							$asPkg = Utilities::boolval( Utilities::getArrayValue( 'pkg', $_REQUEST, false ) );
							if ( $asPkg )
							{
								$includeFiles = Utilities::boolval( Utilities::getArrayValue( 'include_files', $_REQUEST, false ) );
								$includeServices = Utilities::boolval( Utilities::getArrayValue( 'include_services', $_REQUEST, false ) );
								$includeSchema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $_REQUEST, false ) );
								$includeData = Utilities::boolval( Utilities::getArrayValue( 'include_data', $_REQUEST, false ) );
								static::exportAppAsPackage( $this->modelId, $includeFiles, $includeServices, $includeSchema, $includeData );
							}
						}
						// single entity by id
						$result = static::retrieveRecordById( $this->modelName, $this->modelId, $fields, $extras );
					}
					break;
				default:
					throw new Exception( "GET request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST );
					break;
			}

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPost()
	{
		try
		{
			$this->detectCommonParams();
			$data = Utilities::getPostDataAsArray();
			// Most requests contain 'returned fields' parameter
			$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
			$extras = array();
			$related = Utilities::getArrayValue( 'related', $_REQUEST, '' );
			if ( !empty( $related ) )
			{
				$related = array_map( 'trim', explode( ',', $related ) );
				foreach ( $related as $relative )
				{
					$extraFields = Utilities::getArrayValue( $relative . '_fields', $_REQUEST, '*' );
					$extraOrder = Utilities::getArrayValue( $relative . '_order', $_REQUEST, '' );
					$extras[] = array( 'name' => $relative, 'fields' => $extraFields, 'order' => $extraOrder );
				}
			}
			switch ( $this->modelName )
			{
				case '':
					throw new Exception( "Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN );
					break;
				case 'app':
					// you can import an application package file, local or remote, or from zip, but nothing else
					$fileUrl = Utilities::getArrayValue( 'url', $_REQUEST, '' );
					if ( 0 === strcasecmp( 'dfpkg', FileUtilities::getFileExtension( $fileUrl ) ) )
					{
						// need to download and extract zip file and move contents to storage
						$filename = FileUtilities::importUrlFileToTemp( $fileUrl );
						try
						{
							return static::importAppFromPackage( $filename, $fileUrl );
						}
						catch ( Exception $ex )
						{
							throw new Exception( "Failed to import application package $fileUrl.\n{$ex->getMessage()}" );
						}
					}
					$name = Utilities::getArrayValue( 'name', $_REQUEST, '' );
					// from repo or remote zip file
					if ( !empty( $name ) && ( 0 === strcasecmp( 'zip', FileUtilities::getFileExtension( $fileUrl ) ) ) )
					{
						// need to download and extract zip file and move contents to storage
						$filename = FileUtilities::importUrlFileToTemp( $fileUrl );
						try
						{
							return static::importAppFromZip( $name, $filename );
							// todo save url for later updates
						}
						catch ( Exception $ex )
						{
							throw new Exception( "Failed to import application package $fileUrl.\n{$ex->getMessage()}" );
						}
					}
					if ( isset( $_FILES['files'] ) && !empty( $_FILES['files'] ) )
					{
						// older html multi-part/form-data post, single or multiple files
						$files = $_FILES['files'];
						if ( is_array( $files['error'] ) )
						{
							throw new Exception( "Only a single application package file is allowed for import." );
						}
						$filename = $files['name'];
						$error = $files['error'];
						if ( $error !== UPLOAD_ERR_OK )
						{
							throw new Exception( "Failed to import application package $filename.\n$error" );
						}
						$tmpName = $files['tmp_name'];
						$contentType = $files['type'];
						if ( 0 === strcasecmp( 'dfpkg', FileUtilities::getFileExtension( $filename ) ) )
						{
							try
							{
								// need to extract zip file and move contents to storage
								return static::importAppFromPackage( $tmpName );
							}
							catch ( Exception $ex )
							{
								throw new Exception( "Failed to import application package $filename.\n{$ex->getMessage()}" );
							}
						}
						if ( !empty( $name ) && !FileUtilities::isZipContent( $contentType ) )
						{
							try
							{
								// need to extract zip file and move contents to storage
								return static::importAppFromZip( $name, $tmpName );
							}
							catch ( Exception $ex )
							{
								throw new Exception( "Failed to import application package $filename.\n{$ex->getMessage()}" );
							}
						}
					}

				case 'app_group':
				case 'role':
				case 'service':
				case 'user':
					$records = Utilities::getArrayValue( 'record', $data, array() );
					if ( empty( $records ) )
					{
						// xml to array conversion leaves them in plural wrapper
						$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
					}
					if ( empty( $records ) )
					{
						if ( empty( $data ) )
						{
							throw new Exception( 'No record in POST create request.', ErrorCodes::BAD_REQUEST );
						}
						$result = static::createRecord( $this->modelName, $data, $fields, $extras );
					}
					else
					{
						$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
						if ( !isset( $rollback ) )
						{
							$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
						}
						$result = static::createRecords( $this->modelName, $records, $rollback, $fields, $extras );
					}
					break;
				default:
					throw new Exception( "POST request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST );
					break;
			}

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionPut()
	{
		try
		{
			$this->detectCommonParams();
			$data = Utilities::getPostDataAsArray();
			// Most requests contain 'returned fields' parameter
			$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
			$extras = array();
			$related = Utilities::getArrayValue( 'related', $_REQUEST, '' );
			if ( !empty( $related ) )
			{
				$related = array_map( 'trim', explode( ',', $related ) );
				foreach ( $related as $relative )
				{
					$extraFields = Utilities::getArrayValue( $relative . '_fields', $_REQUEST, '*' );
					$extraOrder = Utilities::getArrayValue( $relative . '_order', $_REQUEST, '' );
					$extras[] = array( 'name' => $relative, 'fields' => $extraFields, 'order' => $extraOrder );
				}
			}
			switch ( $this->modelName )
			{
				case '':
					throw new Exception( "Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN );
					break;
				case 'app':
				case 'app_group':
				case 'role':
				case 'service':
				case 'user':
					if ( empty( $this->modelId ) )
					{
						$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
						if ( !isset( $rollback ) )
						{
							$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
						}
						$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
						if ( empty( $ids ) )
						{
							$ids = Utilities::getArrayValue( 'ids', $data, '' );
						}
						if ( !empty( $ids ) )
						{
							$result = static::updateRecordsByIds( $this->modelName, $ids, $data, $rollback, $fields, $extras );
						}
						else
						{
							$records = Utilities::getArrayValue( 'record', $data, null );
							if ( empty( $records ) )
							{
								// xml to array conversion leaves them in plural wrapper
								$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
							}
							if ( empty( $records ) )
							{
								if ( empty( $data ) )
								{
									throw new Exception( 'No record in PUT update request.', ErrorCodes::BAD_REQUEST );
								}
								$result = static::updateRecord( $this->modelName, $data, $fields, $extras );
							}
							else
							{
								$result = static::updateRecords( $this->modelName, $records, $rollback, $fields, $extras );
							}
						}
					}
					else
					{
						$result = static::updateRecordById( $this->modelName, $this->modelId, $data, $fields, $extras );
					}
					break;
				default:
					throw new Exception( "PUT request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST );
					break;
			}

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionMerge()
	{
		try
		{
			$this->detectCommonParams();
			$data = Utilities::getPostDataAsArray();
			// Most requests contain 'returned fields' parameter
			$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
			$extras = array();
			$related = Utilities::getArrayValue( 'related', $_REQUEST, '' );
			if ( !empty( $related ) )
			{
				$related = array_map( 'trim', explode( ',', $related ) );
				foreach ( $related as $relative )
				{
					$extraFields = Utilities::getArrayValue( $relative . '_fields', $_REQUEST, '*' );
					$extraOrder = Utilities::getArrayValue( $relative . '_order', $_REQUEST, '' );
					$extras[] = array( 'name' => $relative, 'fields' => $extraFields, 'order' => $extraOrder );
				}
			}
			switch ( $this->modelName )
			{
				case '':
					throw new Exception( "Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN );
					break;
				case 'app':
				case 'app_group':
				case 'role':
				case 'service':
				case 'user':
					if ( empty( $this->modelId ) )
					{
						$rollback = ( isset( $_REQUEST['rollback'] ) ) ? Utilities::boolval( $_REQUEST['rollback'] ) : null;
						if ( !isset( $rollback ) )
						{
							$rollback = Utilities::boolval( Utilities::getArrayValue( 'rollback', $data, false ) );
						}
						$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
						if ( empty( $ids ) )
						{
							$ids = Utilities::getArrayValue( 'ids', $data, '' );
						}
						if ( !empty( $ids ) )
						{
							$result = static::updateRecordsByIds( $this->modelName, $ids, $data, $rollback, $fields, $extras );
						}
						else
						{
							$records = Utilities::getArrayValue( 'record', $data, null );
							if ( empty( $records ) )
							{
								// xml to array conversion leaves them in plural wrapper
								$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
							}
							if ( empty( $records ) )
							{
								if ( empty( $data ) )
								{
									throw new Exception( 'No record in MERGE update request.', ErrorCodes::BAD_REQUEST );
								}
								$result = static::updateRecord( $this->modelName, $data, $fields, $extras );
							}
							else
							{
								$result = static::updateRecords( $this->modelName, $records, $rollback, $fields, $extras );
							}
						}
					}
					else
					{
						$result = static::updateRecordById( $this->modelName, $this->modelId, $data, $fields, $extras );
					}
					break;
				default:
					throw new Exception( "MERGE/PATCH request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST );
					break;
			}

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function actionDelete()
	{
		try
		{
			$this->detectCommonParams();
			// Most requests contain 'returned fields' parameter
			$fields = Utilities::getArrayValue( 'fields', $_REQUEST, '' );
			$extras = array();
			$related = Utilities::getArrayValue( 'related', $_REQUEST, '' );
			if ( !empty( $related ) )
			{
				$related = array_map( 'trim', explode( ',', $related ) );
				foreach ( $related as $relative )
				{
					$extraFields = Utilities::getArrayValue( $relative . '_fields', $_REQUEST, '*' );
					$extraOrder = Utilities::getArrayValue( $relative . '_order', $_REQUEST, '' );
					$extras[] = array( 'name' => $relative, 'fields' => $extraFields, 'order' => $extraOrder );
				}
			}
			switch ( $this->modelName )
			{
				case '':
					throw new Exception( "Multi-table batch requests not currently available through this API.", ErrorCodes::FORBIDDEN );
					break;
				case 'app':
				case 'app_group':
				case 'role':
				case 'service':
				case 'user':
					if ( empty( $this->modelId ) )
					{
						$data = Utilities::getPostDataAsArray();
						$ids = ( isset( $_REQUEST['ids'] ) ) ? $_REQUEST['ids'] : '';
						if ( empty( $ids ) )
						{
							$ids = Utilities::getArrayValue( 'ids', $data, '' );
						}
						if ( !empty( $ids ) )
						{
							$result = static::deleteRecordsByIds( $this->modelName, $ids, $fields, $extras );
						}
						else
						{
							$records = Utilities::getArrayValue( 'record', $data, null );
							if ( empty( $records ) )
							{
								// xml to array conversion leaves them in plural wrapper
								$records = ( isset( $data['records']['record'] ) ) ? $data['records']['record'] : null;
							}
							if ( empty( $records ) )
							{
								if ( empty( $data ) )
								{
									throw new Exception( "Id list or record containing Id field required to delete $this->modelName records.", ErrorCodes::BAD_REQUEST );
								}
								$result = static::deleteRecord( $this->modelName, $data, $fields, $extras );
							}
							else
							{
								$result = static::deleteRecords( $this->modelName, $records, $fields, $extras );
							}
						}
					}
					else
					{
						$result = static::deleteRecordById( $this->modelName, $this->modelId, $fields, $extras );
					}
					break;
				default:
					throw new Exception( "DELETE request received for an unsupported system resource named '$this->modelName'.", ErrorCodes::BAD_REQUEST );
					break;
			}

			return $result;
		}
		catch ( Exception $ex )
		{
			throw $ex;
		}
	}

	/**
	 *
	 */
	protected function detectCommonParams()
	{
		$resource = Utilities::getArrayValue( 'resource', $_GET, '' );
		$resource = ( !empty( $resource ) ) ? explode( '/', $resource ) : array();
		$this->modelName = strtolower( ( isset( $resource[0] ) ) ? $resource[0] : '' );
		$this->modelId = ( isset( $resource[1] ) ) ? $resource[1] : '';
	}

	/**
	 * @param $resource
	 *
	 * @return App|AppGroup|Role|Service|User
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
			default:
				throw new Exception( "Invalid system resource '$resource' requested.", ErrorCodes::BAD_REQUEST );
				break;
		}

		return $model;
	}

	/**
	 * @param $resource
	 *
	 * @return App|AppGroup|Role|Service|User
	 * @throws Exception
	 */
	public static function getNewResource( $resource )
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
			default:
				throw new Exception( "Attempting to create an invalid system resource '$resource'.", ErrorCodes::INTERNAL_SERVER_ERROR );
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
			$obj = static::getNewResource( $table );
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
				error_log( "Failed to get primary key from created user.\n" . print_r( $obj, true ) );
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
		SessionManager::checkPermission( 'create', 'system', $table );
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
		SessionManager::checkPermission( 'create', 'system', $table );

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
		$sessionAction = '';
		switch ( strtolower( $table ) )
		{
			case 'user':
				if ( $obj->is_active && isset( $record['is_active'] ) )
				{
					$isActive = Utilities::boolval( $record['is_active'] );
					if ( Utilities::boolval( $obj->is_active ) !== $isActive )
					{
						$sessionAction = 'delete';
					}
				}
				if ( isset( $record['is_sys_admin'] ) )
				{
					$isSysAdmin = Utilities::boolval( $record['is_sys_admin'] );
					if ( Utilities::boolval( $obj->is_sys_admin ) !== $isSysAdmin )
					{
						$sessionAction = 'update';
					}
				}
				if ( isset( $record['role_id'] ) )
				{
					$roleId = $record['role_id'];
					if ( $obj->role_id !== $roleId )
					{
						$sessionAction = 'update';
					}
				}
				break;
			case 'role':
				if ( $obj->is_active && isset( $record['is_active'] ) )
				{
					$isActive = Utilities::boolval( $record['is_active'] );
					if ( Utilities::boolval( $obj->is_active ) !== $isActive )
					{
						$sessionAction = 'delete';
					}
				}
				break;
		}

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

			switch ( strtolower( $table ) )
			{
				case 'role':
					switch ( $sessionAction )
					{
						case 'delete':
							SessionManager::deleteSessionsByRole( $id );
							break;
						case 'update':
							SessionManager::updateSessionByRole( $id );
							break;
					}
					break;
				case 'user':
					switch ( $sessionAction )
					{
						case 'delete':
							SessionManager::deleteSessionsByUser( $id );
							break;
						case 'update':
							SessionManager::updateSessionByUser( $id );
							break;
					}
					break;
			}

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
		SessionManager::checkPermission( 'update', 'system', $table );
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
		SessionManager::checkPermission( 'update', 'system', $table );
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
		SessionManager::checkPermission( 'update', 'system', $table );
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
		SessionManager::checkPermission( 'update', 'system', $table );

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
		SessionManager::checkPermission( 'delete', 'system', $table );
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
		SessionManager::checkPermission( 'delete', 'system', $table );

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
		SessionManager::checkPermission( 'read', 'system', $table );
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
		SessionManager::checkPermission( 'read', 'system', $table );
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
		SessionManager::checkPermission( 'read', 'system', $table );
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
		SessionManager::checkPermission( 'read', 'system', 'app' );
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
		$record = Utilities::jsonToArray( $data );
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
				$data = Utilities::jsonToArray( $data );
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
				$data = Utilities::jsonToArray( $data );
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
				$data = Utilities::jsonToArray( $data );
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
	public static function getCurrentAppId()
	{
		return static::getAppIdFromName( SessionManager::getCurrentAppName() );
	}

}
