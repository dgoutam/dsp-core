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
namespace Platform\Exceptions;

use Kisma\Core\Interfaces\HttpResponse;

/**
 * RestException represents an exception caused by REST API operations of end-users.
 *
 * The HTTP error code can be obtained via {@link statusCode}.
 */
class RestException extends PlatformException implements HttpResponse
{
	/**
	 * @var integer HTTP status code, such as 403, 404, 500, etc.
	 */
	public $statusCode;

	/**
	 * Constructor.
	 *
	 * @param integer $status  HTTP status code, such as 404, 500, etc.
	 * @param string  $message error message
	 * @param integer $code    error code
	 */
	public function __construct( $status, $message = null, $code = 0 )
	{
		$this->statusCode = $status;

		parent::__construct( $message, $code );
	}
}
