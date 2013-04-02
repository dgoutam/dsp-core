<?php
namespace DreamFactory\Exceptions;

use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Inflector;

/**
 * RestException
 *
 * This file is part of the DreamFactory Document Services Platform(tm)
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
 *
 * @file
 * @copyright     Copyright 2013 DreamFactory Software, Inc. All rights reserved.
 * @link          http://dreamfactory.com DreamFactory Software, Inc.
 */
class RestException extends \CHttpException
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @InheritDoc
	 * Note status comes first. Will create a message based on the constant name
	 */
	public function __construct( $status, $message = null, $code = 0 )
	{
		//	If no message was given and it's common, we can set it.
		if ( null === $message && HttpResponse::defines( $status ) ))
		{
			//	ka-jigger the constant name into some kinda english
			$message = ucfirst( strtolower( str_replace( '_', ' ', Inflector::neutralize( HttpResponse::nameOf( $status ) ) ) ) );
		}

		parent::__construct( $status, $message, $code );
	}
}