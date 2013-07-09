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
 * index.php
 * Main entry point/bootstrap for all processes
 */
//	Load up composer...
$_autoloader = require_once( __DIR__ . '/../vendor/autoload.php' );

//	Load up Yii
require_once __DIR__ . '/../vendor/dreamfactory/yii/framework/yii.php';

//	Yii debug settings
defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', true );
defined( 'YII_TRACE_LEVEL' ) or define( 'YII_TRACE_LEVEL', 3 );

//	Create the application and run
DreamFactory\Yii\Utility\Pii::run(
	__DIR__,
	$_autoloader,
	'Platform\\Yii\\Components\\PlatformWebApplication'
);
