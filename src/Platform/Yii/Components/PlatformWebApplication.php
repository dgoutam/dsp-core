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
namespace Platform\Yii\Components;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Utility\FilterInput;
use Platform\Yii\Utility\Pii;

/**
 * PlatformWebApplication
 */
class PlatformWebApplication extends \CWebApplication
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Initialize
	 */
	protected function init()
	{
		parent::init();

		/** @noinspection PhpUndefinedFieldInspection */
		Pii::app()->onBeginRequest = array( $this, 'checkRequestMethod' );
	}

	/**
	 * Handles an OPTIONS request to the server to allow CORS
	 */
	public function checkRequestMethod()
	{
		$_origin = FilterInput::server( 'HTTP_ORIGIN' ) ? : '*';

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && HttpMethod::Options == $_SERVER['REQUEST_METHOD'] )
		{
			header( 'HTTP/1.1 204' );
			header( 'content-length: 0' );
			header( 'content-type: text/plain' );
			header( 'access-control-allow-origin: ' . $_origin );
			header( 'access-control-allow-methods: GET, POST, PUT, DELETE, PATCH, MERGE, COPY, OPTIONS' );
			header( 'access-control-allow-headers: content-type, accept' );
			header( 'access-control-max-age: 3600' );

			Pii::end();
		}
	}
}