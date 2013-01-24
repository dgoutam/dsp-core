<?php
/* @var $this SiteController */

$this->pageTitle=Yii::app()->name;
?>

<h1><i><?php echo CHtml::encode(Yii::app()->name); ?></i> Environment</h1>

<?php

if (!isset($_SESSION)) {
    session_start();
}
echo "Session Info: <br />";
echo 'Module: ' . session_module_name() . ' Name: ' . session_name() . ' Id: ' . session_id();
echo "<br /><br />";
echo 'Session Env: ' . print_r($_SESSION, true);
echo "<br /><br />";
echo 'REST API Version: ' . Versions::REST_VERSION . "<br />";
echo 'SOAP API Version: ' . Versions::SOAP_VERSION . "<br />";
echo 'DSP SQL Schema Version: ' . Versions::SYSTEM_DB_VERSION . "<br />";
echo 'SQL DB Data Source Name: ' . Yii::app()->db->connectionString . "<br />";
echo 'Blob Storage Type: ' . Yii::app()->params['BlobStorageStorageType'] . "<br />";
echo "<br /><br />";
phpinfo();

?>
