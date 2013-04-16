<?php
/**
 * index.php
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
 */
use DreamFactory\Platform\Utility\DataCache;

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
