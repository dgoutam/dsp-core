<?php
/**
 * BE AWARE...
 *
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
/**
 * HttpMethod
 * Defines the available Http methods for CURL
 */
interface HttpMethod
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const Get = 'GET';
	/**
	 * @var string
	 */
	const Put = 'PUT';
	/**
	 * @var string
	 */
	const Head = 'HEAD';
	/**
	 * @var string
	 */
	const Post = 'POST';
	/**
	 * @var string
	 */
	const Delete = 'DELETE';
	/**
	 * @var string
	 */
	const Options = 'OPTIONS';
	/**
	 * @var string
	 */
	const Copy = 'COPY';
	/**
	 * @var string
	 */
	const Patch = 'PATCH';
    /**
     * @var string
     */
    const Merge = 'MERGE';
	/**
	 * @var string
	 */
	const Trace = 'TRACE';
	/**
	 * @var string
	 */
	const Connect = 'CONNECT';
}
