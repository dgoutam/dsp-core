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

use Kisma\Core\Utility\FilterInput;
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

		$_columns = explode( ',', FilterInput::get( INPUT_GET, 'c', null, FILTER_SANITIZE_STRING ) );
		$_criteria = ( !empty( $_columns ) ? array( 'select' => implode( ', ', $_columns ) ) : array_keys( \Registry::model()->restMap() ) );

		/** @var  $_resource */
		$_resource = \Registry::model()->userTag( UserSession::getCurrentUserId(), $request )->find( $_criteria );

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
	 * @SWG\Api(
	 *       path="/{service}", description="The currently available user-owned services.",
	 * @SWG\Operations(
	 * @SWG\Operation(
	 *       httpMethod="GET", summary="List user-owned services.",
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
		$_count = 0;
		$_dataTablesOutput = isset( $_GET['dt'] );

		$_restMap = \Registry::model()->restMap();
		$_columns = explode( ',', FilterInput::get( INPUT_GET, 'c', null, FILTER_SANITIZE_STRING ) );

		if ( empty( $_columns ) )
		{
			$_columns = array_keys( $_restMap );
		}

		//	Translate the columns...
		$_new = array();
		$_map = array_flip( $_restMap );

		foreach ( $_columns as $_column )
		{
			$_new[] = Option::get( $_map, $_column );
		}

		$_columns = $_new;

		$_criteria = ( !empty( $_columns ) ? array( 'select' => implode( ', ', $_columns ) ) : array_keys( $_restMap ) );

		/** @var \Registry[] $_rows */
		$_rows = \Registry::model()->userTag( UserSession::getCurrentUserId() )->findAll( $_criteria );

		$_response = array();

		if ( !empty( $_rows ) )
		{
			foreach ( $_rows as $_row )
			{
				$_response[] = ( $_dataTablesOutput ? array_values( $_row->getRestAttributes( $_columns ) ) : $_row->getRestAttributes( $_columns ) );
				$_count++;

				unset( $_row );
			}

			unset( $_rows );
		}

		//	If this is for datatables, we need to format differently
		if ( $_dataTablesOutput )
		{
			$_output = array(
				'sEcho'                => intval( $_GET['sEcho'] ),
				'iTotalRecords'        => $_count,
				'iTotalDisplayRecords' => $_count,
				'aaData'               => $_response,
			);

			Log::debug( print_r( $_output, true ) );

			return $_output;
		}

		return array( 'resource' => $_response );
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
		$_userId = UserSession::getCurrentUserId();

		$_resource = \Registry::model()->userTag( $_userId, $request )->find();

		if ( empty( $_resource ) )
		{
			$_resource = new \Registry();
			$_resource->user_id = $_userId;
			$_resource->service_tag_text = $request;
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

		return array( 'resource' => $_resource->getRestAttributes() );
	}
}
