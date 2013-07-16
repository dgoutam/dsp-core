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
 * aliases.config.php
 * A single location for all your aliasing needs!
 */
use Platform\Yii\Utility\Pii;

$_basePath = dirname( __DIR__ );
$_vendorPath = $_basePath . '/vendor';

Pii::setPathOfAlias( 'vendor', $_vendorPath );

//	Local
Pii::alias( 'Platform', $_basePath . '/src/Platform' );
Pii::alias( 'Platform.Yii.Behaviors', $_basePath . '/src/Platform/Yii/Behaviors' );
Pii::alias( 'Platform.Yii.Components', $_basePath . '/src/Platform/Yii/Components' );
Pii::alias( 'Platform.Yii.Utility', $_basePath . '/src/Platform/Yii/Utility' );

//	lib-php-common-yii
Pii::alias( 'DreamFactory.Yii.Components', $_vendorPath . '/dreamfactory/lib-php-common-yii/DreamFactory/Yii/Components' );
Pii::alias( 'DreamFactory.Yii.Behaviors', $_vendorPath . '/dreamfactory/lib-php-common-yii/DreamFactory/Yii/Behaviors' );
Pii::alias( 'DreamFactory.Yii.Utility', $_vendorPath . '/dreamfactory/lib-php-common-yii/DreamFactory/Yii/Utility' );

//	lib-php-common-platform
Pii::alias( 'DreamFactory.Platform.Services', $_vendorPath . '/dreamfactory/lib-php-common-platform/DreamFactory/Platform/Services' );
Pii::alias( 'DreamFactory.Platform.Yii.Behaviors', $_vendorPath . '/dreamfactory/lib-php-common-platform/DreamFactory/Platform/Yii/Behaviors' );
Pii::alias( 'DreamFactory.Platform.Yii.Models', $_vendorPath . '/dreamfactory/lib-php-common-platform/DreamFactory/Platform/Yii/Models' );

//	Vendors
Pii::alias( 'Swift', $_vendorPath . '/swiftmailer/swiftmailer/lib/classes' );
