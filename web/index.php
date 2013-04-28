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
 * index.php
 */
use Platform\Utility\DataCache;

$_basePath = dirname( __DIR__ );
$_autoloader = require_once( $_basePath . '/vendor/autoload.php' );
require_once __DIR__ . '/protected/components/Pii.php';

//	Initialize app settings
\Pii::run( __DIR__, $_autoloader );

//	Main DSP web configuration
if ( !( $_config = DataCache::load( $_key = $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_HOST'] . '.web' ) ) )
{
	DataCache::store( $_key, $_config = require $_basePath . '/config/web.php' );
}

require_once $_basePath . '/config/aliases.php';

//	Comment out the following lines in production
//defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', true );
//defined( 'YII_TRACE_LEVEL' ) or define( 'YII_TRACE_LEVEL', 3 );

\Yii::createWebApplication( $_config );

//	This initializes caching inside Pii
\Pii::app()->run();
