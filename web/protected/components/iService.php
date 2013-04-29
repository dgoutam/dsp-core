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

/**
 * iService
 * An interface for the base DSP service.
 */
interface iService
{
	/**
	 * @param string $apiName
	 *
	 * @return iService
	 */
	public function setApiName( $apiName );

	/**
	 * @return string
	 */
	public function getApiName();

	/**
	 * @param string $type
	 *
	 * @return iService
	 */
	public function setType( $type );

	/**
	 * @return string
	 */
	public function getType();

	/**
	 * @param string $description
	 *
	 * @return iService
	 */
	public function setDescription( $description );

	/**
	 * @return string
	 */
	public function getDescription();

	/**
	 * @param boolean $isActive
	 *
	 * @return iService
	 */
	public function setIsActive( $isActive );

	/**
	 * @return boolean
	 */
	public function getIsActive();

	/**
	 * @param string $name
	 *
	 * @return iService
	 */
	public function setName( $name );

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @param string $nativeFormat
	 *
	 * @return iService
	 */
	public function setNativeFormat( $nativeFormat );

	/**
	 * @return string
	 */
	public function getNativeFormat();
}
