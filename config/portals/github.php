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
 * github.php
 * This is the configuration file for github portal
 */
return array(
	/**
	 * Basics
	 */
	'api_name'               => 'github',
	'client_id'              => 'caf2ba694afc90d62c2a',
	'client_secret'          => '8f5b38a65ddfc0761febe0c113a2e128c43bac9e',
	'authorization_endpoint' => 'https://github.com/login',
	'service_endpoint'       => 'https://api.github.com',
	'resource_endpoint'      => 'https://api.github.com',
	'auth_header_name'       => 'token',
	'access_token_type'      => OAuthTokenTypes::URI,
	'scope'                  => array( 'user', 'user:email', 'user:follow', 'public_repo', 'repo', 'repo:status', 'notifications', 'gist' ),
	'user_agent'             => 'dreamfactorysoftware/portal-github',
);