<?php
/**
 * This file is part of the DreamFactory Document Service Platform (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * This source file and all is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * This script is the main entry point for the DSP.
 */
require_once dirname( __DIR__ ) . '/vendor/yiisoft/yii/framework/yii.php';
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

//	Main DSP web configuration
$_config = dirname( __DIR__ ) . '/config/web.php';

//	Comment out the following lines in production
defined('YII_DEBUG') or define('YII_DEBUG',true);
//	Specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

Yii::createWebApplication( $_config )->run();
