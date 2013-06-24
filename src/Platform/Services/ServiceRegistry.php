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
namespace Platform\Services;

use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Exceptions\NotFoundException;
use Platform\Resources\UserSession;
use Platform\Utility\RestRequest;
use Swagger\Annotations as SWG;

/**
 * ServiceRegistry.php
 * A service to handle remote services
 *
 * @SWG\Resource(
 *   resourcePath="/{service}"
 * )
 *
 * @SWG\Model(id="Records",
 * @SWG\Property(name="record",type="Array",items="$ref:Record",description="Array of records of the given resource."),
 * @SWG\Property(name="meta",type="MetaData",description="Available meta data for the response.")
 * )
 * @SWG\Model(id="Record",
 * @SWG\Property(name="field",type="Array",items="$ref:string",description="Example field name-value pairs."),
 * @SWG\Property(name="related",type="Array",items="$ref:string",description="Example related records.")
 * )
 *
 */
class ServiceRegistry extends RestService
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\BadRequestException
	 * @return array|bool
	 */
	protected function _handleResource()
	{
		switch ( $this->_action )
		{
			case static::Get:
				return $this->_get( $this->_resource );

			case static::Post:
				return $this->_post( $this->_resource );

			case static::Delete:
				return $this->_delete( $this->_resource );

			default:
				return false;
		}
	}

	/**
	 * GET a resource
	 *
	 * @param string $request
	 *
	 * @return array
	 * @throws \Platform\Exceptions\NotFoundException
	 */
	protected function _get( $request )
	{
		Log::debug( 'Registry GET/' . $request );

		$_resource = \Registry::model()->userTag( UserSession::getCurrentUserId(), $request )->find();

		if ( empty( $_resource ) )
		{
			throw new NotFoundException();
		}

		return array(
			'resource' => array(
				'id'        => $_resource->service_tag_text,
				'type'      => $_resource->service_type_nbr,
				'name'      => $_resource->service_name_text,
				'config'    => $_resource->service_config_text,
				'enabled'   => $_resource->enabled_ind,
				'last_used' => $_resource->last_use_date,
			)
		);
	}

	/**
	 * DELETE a resource
	 *
	 * @param string $request
	 *
	 * @throws \Platform\Exceptions\NotFoundException
	 * @throws \Platform\Exceptions\InternalServerErrorException
	 * @return bool
	 */
	protected function _delete( $request )
	{
		$_resource = \Registry::model()->userTag( UserSession::getCurrentUserId(), $request )->find();

		if ( empty( $_resource ) )
		{
			throw new NotFoundException();
		}

		try
		{
			return $_resource->delete();
		}
		catch ( \Exception $_ex )
		{
			throw new InternalServerErrorException( 'Error deleting resource: ' . $_ex->getMessage() );
		}
	}

	/**
	 * POST a resource
	 *
	 * @param string $request
	 *
	 * @throws \Platform\Exceptions\InternalServerErrorException
	 * @return array
	 */
	protected function _post( $request )
	{
		$_userId = 1; //UserSession::getCurrentUserId();

		Log::debug( 'Registry POST/' . $request );
		$_resource = \Registry::model()->userTag( $_userId, $request )->find();

		if ( empty( $_resource ) )
		{
			$_resource = new \Registry();
			$_resource->user_id = $_userId;
			$_resource->service_tag_text = $request;
			Log::debug( 'new row' );
		}

		$_data = RestRequest::getPostDataAsArray();

		$_resource->service_name_text = Option::get( $_data, 'name' );
		$_resource->service_type_nbr = Option::get( $_data, 'type', 0 );
		$_resource->service_config_text = Option::get( $_data, 'config' );
		$_resource->enabled_ind = Option::get( $_data, 'enabled' );

		try
		{
			$_resource->save();
		}
		catch ( \Exception $_ex )
		{
			throw new InternalServerErrorException( 'Error saving to registry: ' . $_ex->getMessage() );
		}

		return array(
			'resource' => array(
				'id'        => $_resource->service_tag_text,
				'type'      => $_resource->service_type_nbr,
				'name'      => $_resource->service_name_text,
				'config'    => $_resource->service_config_text,
				'enabled'   => $_resource->enabled_ind,
				'last_used' => $_resource->last_use_date,
			)
		);
	}

	/**
	 * @SWG\Api(
	 *       path="/{service}", description="Operations available for SQL database tables.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List resources available for database schema.",
	 *       notes="See listed operations for each resource available.",
	 *       responseClass="Resources", nickname="getResources"
	 *     )
	 *   )
	 * )
	 *
	 * @throws \Exception
	 * @return array
	 */
	protected function _listResources()
	{
		try
		{
			$_resources = \Registry::model()->findAll(
				'user_id = :user_id',
				array(
					 ':user_id' => UserSession::getCurrentUserId(),
				)
			);

			return array( 'resource' => $_resources );
		}
		catch ( \Exception $_ex )
		{
			throw new \Exception( 'Error retrieving resources: ' . $_ex->getMessage() );
		}
	}
}
