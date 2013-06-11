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
namespace Platform\Resources;

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Resources\UserSession;
use Platform\Services\SystemManager;
use Platform\Utility\DataFormat;
use Platform\Utility\RestRequest;
use Platform\Utility\SqlDbUtilities;
use Platform\Utility\Utilities;
use Platform\Yii\Utility\Pii;
use Swagger\Annotations as SWG;

/**
 * SystemConfig
 * DSP system administration manager
 *
 * @SWG\Resource(
 *   resourcePath="/system"
 * )
 *
 * @SWG\Model(id="Config",
 * @SWG\Property(name="dsp_version",type="string",description="Version of the DSP software."),
 * @SWG\Property(name="db_version",type="string",description="Version of the database schema."),
 * @SWG\Property(name="allow_open_registration",type="boolean",description="Allow guests to register for a user account."),
 * @SWG\Property(name="open_reg_role_id",type="int",description="Default Role Id assigned to newly registered users."),
 * @SWG\Property(name="allow_guest_user",type="boolean",description="Allow app access for non-authenticated users."),
 * @SWG\Property(name="guest_role_id",type="int",description="Role Id assigned for all guest sessions."),
 * @SWG\Property(name="editable_profile_fields",type="string",description="Comma-delimited list of fields the user is allowed to edit."),
 * @SWG\Property(name="allowed_hosts",type="Array",items="$ref:HostInfo",description="CORS whitelist of allowed remote hosts.")
 * )
 * @SWG\Model(id="HostInfo",
 * @SWG\Property(name="host",type="string",description="URL, server name, or * to define the CORS host."),
 * @SWG\Property(name="is_enabled",type="boolean",description="Allow this host's configuration to be used by CORS."),
 * @SWG\Property(name="verbs",type="Array",items="$ref:string",description="Allowed HTTP verbs for this host.")
 * )
 *
 */
class SystemConfig extends RestResource
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	//*************************************************************************
	//	Members
	//*************************************************************************

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Creates a new SystemConfig
	 *
	 */
	public function __construct()
	{
		$config = array(
			'service_name' => 'system',
			'name'         => 'Configuration',
			'api_name'     => 'config',
			'type'         => 'System',
			'description'  => 'System general configuration.',
			'is_active'    => true,
		);

		parent::__construct( $config );
	}

	// Service interface implementation

	// REST interface implementation

	/**
	 *
	 * @throws BadRequestException
	 * @return array|bool
	 */
	protected function _handleAction()
	{
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

		switch ( $this->_action )
		{
			case self::Get:
				$include_schema = Utilities::boolval( Utilities::getArrayValue( 'include_schema', $_REQUEST, false ) );
				$result = static::retrieveConfig( $fields, $include_schema, $extras );
				break;
			case self::Post:
			case self::Put:
			case self::Patch:
			case self::Merge:
				$data = RestRequest::getPostDataAsArray();
				if ( empty( $data ) )
				{
					throw new BadRequestException( 'No record in POST create request.' );
				}
				$result = static::updateConfig( $data, $fields, $extras );
				break;
			default:
				return false;
		}

		return $result;
	}

	//-------- System Records Operations ---------------------
	// records is an array of field arrays

	/**
	 * @SWG\Api(
	 *         path="/system/config", description="Operations for system configuration options.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *         httpMethod="GET", summary="Retrieve system configuration options.",
	 *         notes="The retrieved properties control how the system behaves.",
	 *         responseClass="Config", nickname="getConfig"
	 *       )
	 *     )
	 *   )
	 *
	 * @param string $return_fields
	 * @param bool   $include_schema
	 * @param array  $extras
	 *
	 * @throws \Exception
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array
	 */
	public static function retrieveConfig( $return_fields = '', $include_schema = false, $extras = array() )
	{
		// currently allow everyone to query config, long term this needs to hide certain fields
//		UserManager::checkSessionPermission( 'read', 'system', 'config' );
		$model = \Config::model();
		$return_fields = $model->getRetrievableAttributes( $return_fields );
		$relations = $model->relations();

		try
		{
			$record = $model->find();
			$results = array();
			if ( !empty( $record ) )
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
							throw new BadRequestException( "Invalid relation '$extraName' requested." );
						}
						$extraFields = $extra['fields'];
						$relatedRecords = $record->getRelated( $extraName, true );
						if ( is_array( $relatedRecords ) )
						{
							/**
							 * @var \BaseDspSystemModel[] $relatedRecords
							 */
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

				$results = $data;
			}

			if ( $include_schema )
			{
				$results['meta']['schema'] = SqlDbUtilities::describeTable(
					Pii::db(),
					$model->tableName(),
					SystemManager::SYSTEM_TABLE_PREFIX
				);
			}

			// get current and latest version info
			$_dspVersion = SystemManager::getCurrentVersion();
			$results['dsp_version'] = $_dspVersion;
			if ( !\Fabric::fabricHosted() )
			{
				$_latestVersion = SystemManager::getLatestVersion();
				$results['latest_version'] = $_latestVersion;
				$results['upgrade_available'] = version_compare( $_dspVersion, $_latestVersion, '<' );
			}

			// get cors data from config file
			$results['allowed_hosts'] = SystemManager::getAllowedHosts();

			return $results;
		}
		catch ( \Exception $ex )
		{
			throw new \Exception( "Error retrieving configuration record.\n{$ex->getMessage()}" );
		}
	}

	/**
	 * @SWG\Api(
	 *             path="/system/config", description="Operations for system configuration options.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *             httpMethod="POST", summary="Update one or more system configuration properties.",
	 *             notes="Post data should be an array of properties.",
	 *             responseClass="Success", nickname="setConfig",
	 * @SWG\Parameters(
	 * @SWG\Parameter(
	 *             name="config", description="Data containing name-value pairs of properties to set.",
	 *             paramType="body", required="true", allowMultiple=false, dataType="Config"
	 *           )
	 *         ),
	 * @SWG\ErrorResponses(
	 * @SWG\ErrorResponse(code="400", reason="Bad Request - Request does not have a valid format, all required parameters, etc."),
	 * @SWG\ErrorResponse(code="401", reason="Unauthorized Access - No currently valid session available."),
	 * @SWG\ErrorResponse(code="500", reason="System Error - Specific reason is included in the error message.")
	 *         )
	 *       )
	 *     )
	 *   )
	 *
	 * @param $record
	 * @param $return_fields
	 * @param $extras
	 *
	 * @throws InternalServerErrorException
	 * @throws \Exception
	 * @return array
	 */
	protected static function updateConfig( $record, $return_fields, $extras )
	{
		UserSession::checkSessionPermission( 'update', 'system', 'config' );

		if ( isset( $record['allowed_hosts'] ) )
		{
			$allowedHosts = $record['allowed_hosts'];
			unset( $record['allowed_hosts'] );
			SystemManager::setAllowedHosts( $allowedHosts );
		}
		try
		{
			// try to local config record first
			$obj = \Config::model()->find();
			if ( empty( $obj ) )
			{
				// create DB record
				$obj = new \Config();
			}

			$obj->setAttributes( $record );
			$obj->save();
		}
		catch ( \Exception $ex )
		{
			throw new InternalServerErrorException( "Failed to update system configuration.\n{$ex->getMessage()}" );
		}

		try
		{
			$id = $obj->primaryKey;
			if ( empty( $id ) )
			{
				Log::error( 'Failed to get primary key from created user: ' . print_r( $obj, true ) );
				throw new InternalServerErrorException( "Failed to get primary key from created user." );
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
				$data = static::retrieveConfig( $return_fields, false, $extras );
			}

			return $data;
		}
		catch ( \Exception $ex )
		{
			throw $ex;
		}
	}
}
