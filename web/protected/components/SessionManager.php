<?php

use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * SessionManager.php
 * DSP session manager
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
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
class SessionManager
{
	/**
	 * @var SessionManager
	 */
	private static $_instance = null;

	/**
	 * @var null
	 */
	private static $_userId = null;

	/**
	 * @var null
	 */
	private static $_roleId = null;

	/**
	 * @var \CDbConnection
	 */
	protected $_sqlConn;

	/**
	 * @var int
	 */
	protected $_driverType = DbUtilities::DRV_OTHER;

	/**
	 *
	 */
	public function __construct()
	{
		$this->_sqlConn = Yii::app()->db;
		$this->_driverType = DbUtilities::getDbDriverType( $this->_sqlConn );

		if ( !session_set_save_handler( array( $this, 'open' ),
			array( $this, 'close' ),
			array( $this, 'read' ),
			array( $this, 'write' ),
			array( $this, 'destroy' ),
			array( $this, 'gc' ) )
		)
		{
			error_log( "Failed to set session handler." );
		}

		// make sure we close out the session
		register_shutdown_function( 'session_write_close' );
	}

	/**
	 *
	 */
	public function __destruct()
	{
		session_write_close(); // IMPORTANT!
	}

	/**
	 * Gets the static instance of this class.
	 *
	 * @return SessionManager
	 */
	public static function getInstance()
	{
		if ( !isset( self::$_instance ) )
		{
			self::$_instance = new SessionManager();
		}

		return self::$_instance;
	}

	/**
	 * @return bool
	 */
	public function open()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function close()
	{
		return true;
	}

	/**
	 * @param $id
	 *
	 * @return mixed|string
	 */
	public function read( $id )
	{
		try
		{
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			$command->select( 'data' )->from( 'df_sys_session' )->where( array( 'in', 'id', array( $id ) ) );
			$result = $command->queryRow();
			if ( !empty( $result ) )
			{
				return ( isset( $result['data'] ) ) ? $result['data'] : '';
			}
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}

		return '';
	}

	/**
	 * @param $id
	 * @param $data
	 *
	 * @return bool
	 */
	public function write( $id, $data )
	{
		try
		{
			// would like to not write to db if nothing changed, but need timestamp update to keep session alive
//			if ( isset( $GLOBALS['write_session'] ) && $GLOBALS['write_session'] )
//			{
			// get extra stuff used for disabling users
			$userId = ( isset( $_SESSION['public']['id'] ) ) ? $_SESSION['public']['id'] : null;
			$userId = ( !empty( $userId ) ) ? intval( $userId ) : null;
			$roleId = ( isset( $_SESSION['public']['role']['id'] ) ) ? $_SESSION['public']['role']['id'] : null;
			$roleId = ( !empty( $roleId ) ) ? intval( $roleId ) : null;
			$startTime = time();
			$params = array( $id, $userId, $roleId, $startTime, $data );
			switch ( $this->_driverType )
			{
				case DbUtilities::DRV_SQLSRV:
					$sql = "{call UpdateOrInsertSession(?,?,?,?,?)}";
					break;
				case DbUtilities::DRV_MYSQL:
					$sql = "call UpdateOrInsertSession(?,?,?,?,?)";
					break;
				default:
					$sql = "call UpdateOrInsertSession(?,?,?,?,?)";
					break;
			}
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand( $sql );
			$result = $command->execute( $params );

//			}

			return true;
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}

		return false;
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function destroy( $id )
	{
		try
		{
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			$command->delete( 'df_sys_session', array( 'in', 'id', array( $id ) ) );

			return true;
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}

		return false;
	}

	/**
	 * @param $lifeTime
	 *
	 * @return bool
	 */
	public function gc( $lifeTime )
	{
		try
		{
			$expired = time() - $lifeTime;
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			$command->delete( 'df_sys_session', "start_time < $expired" );

			return true;
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}

		return false;
	}

	// helper functions

	/**
	 * @param $user_ids
	 */
	public function deleteSessionsByUser( $user_ids )
	{
		try
		{
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			if ( is_array( $user_ids ) )
			{
				$command->delete( 'df_sys_session', array( 'in', 'user_id', $user_ids ) );
			}
			elseif ( !empty( $user_ids ) )
			{
				$command->delete( 'df_sys_session', 'user_id=:id', array( ':id' => $user_ids ) );
			}
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}
	}

	/**
	 * @param $user_ids
	 */
	public function deleteSessionsByRole( $role_ids )
	{
		try
		{
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			if ( is_array( $role_ids ) )
			{
				$command->delete( 'df_sys_session', array( 'in', 'role_id', $role_ids ) );
			}
			elseif ( !empty( $role_ids ) )
			{
				$command->delete( 'df_sys_session', 'role_id=:id', array( ':id' => $role_ids ) );
			}
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}
	}

	/**
	 * @param $user_id
	 */
	public function updateSessionByUser( $user_id )
	{
		try
		{
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			$command->select( 'id' )->from( 'df_sys_session' )->where( 'user_id=:id', array( ':id' => $user_id ) );
			$results = $command->queryScalar();
			if ( false !== $results )
			{
				try
				{
					$data = static::generateSessionData( $user_id );
					$data = array( 'public' => $data['public'] );
					$command->reset();
					// wacky, but making sure session encoding is the same as it went in
					$temp = $_SESSION;
					$_SESSION = $data;
					$data = session_encode();
					$_SESSION = $temp;
					$command->update( 'df_sys_session', array( 'data' => $data ), 'user_id=:id', array( ':id' => $user_id ) );
				}
				catch ( Exception $ex )
				{
					// delete sessions because something bad happened
					$command->reset();
					$command->delete( 'df_sys_session', 'user_id=:id', array( ':id' => $user_id ) );
				}
			}
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}
	}

	/**
	 * @param $role_id
	 */
	public function updateSessionByRole( $role_id )
	{
		try
		{
			if ( !$this->_sqlConn->active )
			{
				$this->_sqlConn->active = true;
			}
			$command = $this->_sqlConn->createCommand();
			$command->select( 'user_id' )->from( 'df_sys_session' )->where( 'role_id=:id', array( ':id' => $role_id ) );
			$results = $command->queryAll();
			if ( false !== $results )
			{
				foreach ( $results as $result )
				{
					$user_id = Utilities::getArrayValue( 'user_id', $result, '' );
					if ( !empty( $user_id ) )
					{
						try
						{
							$data = static::generateSessionData( $user_id );
							$data = array( 'public' => $data['public'] );
							$temp = $_SESSION;
							$_SESSION = $data;
							$data = session_encode();
							$_SESSION = $temp;
							$command->reset();
							$command->update( 'df_sys_session', array( 'data' => $data ), 'user_id=:id', array( ':id' => $user_id ) );
						}
						catch ( Exception $ex )
						{
							// delete sessions because something bad happened
							$command->reset();
							$command->delete( 'df_sys_session', 'user_id=:id', array( ':id' => $user_id ) );
						}
					}
				}
			}
		}
		catch ( Exception $ex )
		{
			error_log( $ex->getMessage() );
		}
	}

	/**
	 * @param      $user_id
	 * @param null $user
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function generateSessionData( $user_id, $user = null )
	{
		if ( !isset( $user ) )
		{
			$user = User::model()->with( 'role.role_service_accesses', 'role.apps', 'role.services' )->findByPk( $user_id );
		}
		if ( null === $user )
		{
			throw new Exception( "The user with id $user_id does not exist in the system.", ErrorCodes::UNAUTHORIZED );
		}
		$username = $user->getAttribute( 'username' );
		if ( !$user->getAttribute( 'is_active' ) )
		{
			throw new Exception( "The user with username '$username' is not currently active.", ErrorCodes::FORBIDDEN );
		}

		$isSysAdmin = $user->getAttribute( 'is_sys_admin' );
		$defaultAppId = $user->getAttribute( 'default_app_id' );
		$fields = array( 'id', 'display_name', 'first_name', 'last_name', 'username', 'email', 'is_sys_admin', 'last_login_date' );
		$userInfo = $user->getAttributes( $fields );
		$data = $userInfo; // reply data
		$allowedApps = array();

		if ( !$isSysAdmin )
		{
			$theRole = $user->getRelated( 'role' );
			if ( !isset( $theRole ) )
			{
				throw new Exception( "The user '$username' has not been assigned a role.", ErrorCodes::FORBIDDEN );
			}
			if ( !$theRole->getAttribute( 'is_active' ) )
			{
				throw new Exception( "The role this user is assigned to is not currently active.", ErrorCodes::FORBIDDEN );
			}

			if ( !isset( $defaultAppId ) )
			{
				$defaultAppId = $theRole->getAttribute( 'default_app_id' );
			}
			$role = $theRole->attributes;
			$theApps = $theRole->getRelated( 'apps' );
			$roleApps = array();
			if ( !empty( $theApps ) )
			{
				$appFields = array( 'id', 'api_name', 'is_active' );
				foreach ( $theApps as $app )
				{
					$roleApps[] = $app->getAttributes( $appFields );
					if ( $app->getAttribute( 'is_active' ) )
					{
						$allowedApps[] = $app;
					}
				}
			}
			$role['apps'] = $roleApps;
			$permsFields = array( 'service_id', 'component', 'access' );
			$thePerms = $theRole->getRelated( 'role_service_accesses' );
			$theServices = $theRole->getRelated( 'services' );
			$perms = array();
			foreach ( $thePerms as $perm )
			{
				$permServiceId = $perm->getAttribute( 'service_id' );
				$temp = $perm->getAttributes( $permsFields );
				foreach ( $theServices as $service )
				{
					if ( $permServiceId == $service->getAttribute( 'id' ) )
					{
						$temp['service'] = $service->getAttribute( 'api_name' );
					}
				}
				$perms[] = $temp;
			}
			$role['services'] = $perms;
			$userInfo['role'] = $role;
		}

		return array(
			'public'         => $userInfo,
			'data'           => $data,
			'allowed_apps'   => $allowedApps,
			'default_app_id' => $defaultAppId
		);
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function validateSession()
	{
		if ( !isset( $_SESSION ) )
		{
			session_start();
		}

		if ( isset( $_SESSION['public'] ) && !empty( $_SESSION['public'] ) )
		{
			if ( isset( $_SESSION['public']['id'] ) )
			{
				$_userId = $_SESSION['public']['id'];

				//Log::debug( 'Session validate user id: ' . $_userId . ' ' . print_r( $_SESSION['public'], true ) );

				return $_userId;
			}
		}

		// special case for possible guest user
		$theUser = User::model()->with(
			'role.role_service_accesses',
			'role.apps',
			'role.services'
		)->find( 'username=:un', array( ':un' => 'guest' ) );

		if ( !empty( $theUser ) )
		{
			$result = static::generateSessionData( null, $theUser );
			$_SESSION['public'] = Utilities::getArrayValue( 'public', $result, array() );
			$GLOBALS['write_session'] = true;

//			Log::debug( 'Session validate primary key: ' . $theUser->primaryKey );

			return intval( $theUser->primaryKey );
		}

		throw new Exception( "There is no valid session for the current request.", ErrorCodes::UNAUTHORIZED );
	}

	/**
	 * @param        $request
	 * @param        $service
	 * @param string $component
	 *
	 * @throws Exception
	 */
	public static function checkPermission( $request, $service, $component = '' )
	{
		$userId = static::validateSession();
		$admin = ( isset( $_SESSION['public']['is_sys_admin'] ) ) ? $_SESSION['public']['is_sys_admin'] : false;
		if ( $admin )
		{
			return; // no need to check role
		}
		$roleInfo = ( isset( $_SESSION['public']['role'] ) ) ? $_SESSION['public']['role'] : array();
		if ( empty( $roleInfo ) )
		{
			// no role assigned, if not sys admin, denied service
			throw new Exception( "A valid user role or system administrator is required to access services.", ErrorCodes::FORBIDDEN );
		}

		// check if app allowed in role
		$appName = ( isset( $GLOBALS['app_name'] ) ) ? $GLOBALS['app_name'] : '';
		if ( empty( $appName ) )
		{
			throw new Exception( "A valid application name is required to access services.", ErrorCodes::BAD_REQUEST );
		}

		$apps = Utilities::getArrayValue( 'apps', $roleInfo, null );
		if ( !is_array( $apps ) || empty( $apps ) )
		{
			throw new Exception( "Access to application '$appName' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN );
		}
		$found = false;
		foreach ( $apps as $app )
		{
			$temp = Utilities::getArrayValue( 'api_name', $app );
			if ( 0 == strcasecmp( $appName, $temp ) )
			{
				$found = true;
			}
		}
		if ( !$found )
		{
			throw new Exception( "Access to application '$appName' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN );
		}
		/*
			 // see if we need to deny access to this app
			 $result = $db->retrieveSqlRecordsByIds('app', $appName, 'name', 'id,is_active');
			 if ((0 >= count($result)) || empty($result[0])) {
				 throw new Exception("The application '$appName' could not be found.");
			 }
			 if (!$result[0]['is_active']) {
				 throw new Exception("The application '$appName' is not currently active.");
			 }
			 $appId = $result[0]['id'];
			 // is this app part of the role's allowed apps
			 if (!empty($allowedAppIds)) {
				 if (!Utilities::isInList($allowedAppIds, $appId, ',')) {
					 throw new Exception("The application '$appName' is not currently allowed by this role.");
				 }
			 }
		 */

		$services = Utilities::getArrayValue( 'services', $roleInfo, null );
		if ( !is_array( $services ) || empty( $services ) )
		{
			throw new Exception( "Access to service '$service' is not provisioned for this user's role.", ErrorCodes::FORBIDDEN );
		}

		$allAllowed = false;
		$allFound = false;
		$serviceAllowed = false;
		$serviceFound = false;
		foreach ( $services as $svcInfo )
		{
			$theService = Utilities::getArrayValue( 'service', $svcInfo );
			$theAccess = Utilities::getArrayValue( 'access', $svcInfo, '' );
			if ( 0 == strcasecmp( $service, $theService ) )
			{
				$theComponent = Utilities::getArrayValue( 'component', $svcInfo );
				if ( !empty( $component ) )
				{
					if ( 0 == strcasecmp( $component, $theComponent ) )
					{
						if ( !static::isAllowed( $request, $theAccess ) )
						{
							$msg = ucfirst( $request ) . " access to component '$component' of service '$service' ";
							$msg .= "is not allowed by this user's role.";
							throw new Exception( $msg, ErrorCodes::FORBIDDEN );
						}

						return; // component specific found and allowed, so bail
					}
					elseif ( empty( $theComponent ) || ( '*' == $theComponent ) )
					{
						$serviceAllowed = static::isAllowed( $request, $theAccess );
						$serviceFound = true;
					}
				}
				else
				{
					if ( empty( $theComponent ) || ( '*' == $theComponent ) )
					{
						if ( !static::isAllowed( $request, $theAccess ) )
						{
							$msg = ucfirst( $request ) . " access to service '$service' ";
							$msg .= "is not allowed by this user's role.";
							throw new Exception( $msg, ErrorCodes::FORBIDDEN );
						}

						return; // service specific found and allowed, so bail
					}
				}
			}
			elseif ( empty( $theService ) || ( '*' == $theService ) )
			{
				$allAllowed = static::isAllowed( $request, $theAccess );
				$allFound = true;
			}
		}

		if ( $serviceFound )
		{
			if ( $serviceAllowed )
			{
				return; // service found and allowed, so bail
			}
		}
		elseif ( $allFound )
		{
			if ( $allAllowed )
			{
				return; // all services found and allowed, so bail
			}
		}
		$msg = ucfirst( $request ) . " access to ";
		if ( !empty( $component ) )
		{
			$msg .= "component '$component' of ";
		}
		$msg .= "service '$service' is not allowed by this user's role.";
		throw new Exception( $msg, ErrorCodes::FORBIDDEN );
	}

	/**
	 * @param $request
	 * @param $access
	 *
	 * @return bool
	 */
	protected static function isAllowed( $request, $access )
	{
		switch ( $request )
		{
			case 'read':
				switch ( $access )
				{
					case 'Read Only':
					case 'Read and Write':
					case 'Full Access':
						return true;
				}
				break;
			case 'create':
				switch ( $access )
				{
					case 'Write Only':
					case 'Read and Write':
					case 'Full Access':
						return true;
				}
				break;
			case 'update':
				switch ( $access )
				{
					case 'Write Only':
					case 'Read and Write':
					case 'Full Access':
						return true;
				}
				break;
			case 'delete':
				switch ( $access )
				{
					case 'Full Access':
						return true;
				}
				break;
			default:
				break;
		}
		return false;
	}

	/**
	 * @param $userId
	 */
	public static function setCurrentUserId( $userId )
	{
		static::$_userId = $userId;
		Pii::setState( 'user_id', $userId );
	}

	/**
	 * @return int|null
	 */
	public static function getCurrentUserId()
	{
		if ( isset( static::$_userId ) )
		{
			return static::$_userId;
		}

		if ( isset( $_SESSION, $_SESSION['public'], $_SESSION['public']['id'] ) )
		{
			return static::$_userId = $_SESSION['public']['id'];
		}

		return null;
	}

	/**
	 * @param $roleId
	 */
	public static function setCurrentRoleId( $roleId )
	{
		static::$_roleId = $roleId;
		Pii::setState( 'role_id', $userId );
	}

	/**
	 * @return int|null
	 */
	public static function getCurrentRoleId()
	{
		if ( isset( static::$_roleId ) )
		{
			return static::$_roleId;
		}

		if ( isset( $_SESSION, $_SESSION['public'], $_SESSION['public']['role'], $_SESSION['public']['role']['id'] ) )
		{
			return static::$_roleId = $_SESSION['public']['role']['id'];
		}

		return null;
	}

	/**
	 * @return string
	 */
	public static function getCurrentAppName()
	{
		return ( isset( $GLOBALS['app_name'] ) ) ? $GLOBALS['app_name'] : '';
	}
}
