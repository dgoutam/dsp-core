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
namespace Platform\Services;

use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Platform\Exceptions\BadRequestException;
use Platform\Exceptions\InternalServerErrorException;
use Platform\Exceptions\NotFoundException;
use Platform\Exceptions\RestException;
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
	 * @throws \Platform\Exceptions\RestException
	 * @return array|bool
	 */
	protected function _handleResource()
	{
		switch ( $this->_action )
		{
			case static::Get:
				if ( empty( $this->_resource ) )
				{
					return $this->_listResources();
				}

				return $this->_get( $this->_resource );

			case static::Post:
				return $this->_post( $this->_resource );

			case static::Delete:
				return $this->_delete( $this->_resource );

			default:
				throw new RestException( HttpResponse::BadRequest );
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
		$_columns = \Registry::model()->mapRestColumns(
			isset( $_GET['sColumns'] )
				? explode( ',', FilterInput::get( INPUT_GET, 'sColumns', null, FILTER_SANITIZE_STRING ) )
				: array()
		);

		/** @var \Registry $_resource */
		$_resource = \Registry::model()->userTag( UserSession::getCurrentUserId(), $request )->find( $this->_buildCriteria( $_columns ) );

		if ( empty( $_resource ) )
		{
			throw new NotFoundException();
		}

		return array(
			'resource' => $_resource->getRestAttributes( $_columns ),
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
		$_dataTablesOutput = isset( $_GET['sEcho'] );

		$_columns = \Registry::model()->mapRestColumns(
			isset( $_GET['sColumns'] )
				? explode( ',', FilterInput::get( INPUT_GET, 'sColumns', null, FILTER_SANITIZE_STRING ) )
				: array()
		);

		/** @var \Registry[] $_rows */
		$_rows = \Registry::model()->userTag( UserSession::getCurrentUserId() )->findAll( $this->_buildCriteria( $_columns ) );

		$_response = array();

		if ( !empty( $_rows ) )
		{
			foreach ( $_rows as $_row )
			{
				//	DataTables just gets the values, not the keys
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
				'iTotalRecords'        => $_count,
				'iTotalDisplayRecords' => $_count,
				'aaData'               => $_response,
			);

			if ( isset( $_GET, $_GET['sEcho'] ) )
			{
				$_output['sEcho'] = intval( $_GET['sEcho'] );
			}

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
