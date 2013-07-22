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
use DreamFactory\Yii\Utility\Pii;

/**
 * @var $this WebController
 */
$this->pageTitle = Pii::appName();
?>

<h1><i><?php echo $this->pageTitle; ?></i> Environment</h1>

<?php

if ( !isset( $_SESSION ) )
{
	session_start();
}

echo "Session Info: <br />";
echo 'Module: ' . session_module_name() . ' Name: ' . session_name() . ' Id: ' . session_id();
echo "<br /><br />";
echo 'Session Env: ' . print_r( $_SESSION, true );
echo "<br /><br />";
echo 'API Version: ' . API_VERSION . "<br />";
echo 'DSP Version: ' . DSP_VERSION . "<br />";
echo 'SQL DB Data Source Name: ' . Yii::app()->db->connectionString . "<br />";
echo 'Blob Storage Type: ' . Yii::app()->params['BlobStorageStorageType'] . "<br />";
echo "<br /><br />";
phpinfo();

?>
