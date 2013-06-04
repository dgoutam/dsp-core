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

use Platform\Utility\RestRequest;
use Platform\Utility\Utilities;

/**
 * AppRemoteFileSvc.php
 * A service to handle application-specific file storage accessed through the REST API.
 */
class AppRemoteFileSvc extends RemoteFileSvc
{
	/**
	 * @param array $config
	 */
	public function __construct( $config )
	{
		// Validate storage setup
		$store_name = Utilities::getArrayValue( 'storage_name', $config, '' );
		if ( empty( $store_name ) )
		{
			$config['storage_name'] = 'applications';
		}

		parent::__construct( $config );
	}

	/**
	 * Object destructor
	 */
	public function __destruct()
	{
		parent::__destruct();
	}

	// REST interface implementation

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function _handleResource()
	{
		switch ( $this->_action )
		{
			case static::Get:
				$this->checkPermission( 'read' );
				if ( empty( $this->_resource ) )
				{
					// list app folders only for now
					return $this->getFolderContent( '', false, true, false );
				}
				break;
			case static::Post:
				$this->checkPermission( 'create' );
				if ( empty( $this->_resource ) )
				{
					// for application management at root directory,
					throw new \Exception( "Application service root directory is not available for file creation." );
				}
				break;
			case static::Put:
			case static::Patch:
			case static::Merge:
				$this->checkPermission( 'update' );
				if ( empty( $this->_resource ) || ( ( 1 === count( $this->_resourceArray ) ) && empty( $this->_resourceArray[0] ) ) )
				{
					// for application management at root directory,
					throw new \Exception( "Application service root directory is not available for file updates." );
				}
				break;
			case static::Delete:
				$this->checkPermission( 'delete' );
				if ( empty( $this->_resource ) )
				{
					// for application management at root directory,
					throw new \Exception( "Application service root directory is not available for file deletes." );
				}
				$more = ( isset( $this->_resourceArray[1] ) ? $this->_resourceArray[1] : '' );
				if ( empty( $more ) )
				{
					// dealing only with application root here
					$content = RestRequest::getPostDataAsArray();
					if ( empty( $content ) )
					{
						throw new \Exception( "Application root directory is not available for delete. Use the system API to delete the app." );
					}
				}
				break;
		}

		return parent::_handleResource();
	}
}
