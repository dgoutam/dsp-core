<?php
/* @var $this SiteController */

$this->pageTitle=Yii::app()->name;
?>

<h1>Welcome to <i><?php echo CHtml::encode(Yii::app()->name); ?></i></h1>

<?php
use CloudServicesPlatform\Utilities\Config;
use CloudServicesPlatform\Utilities\Defaults;

session_start();
echo "Session Info: <br />";
echo 'Module: ' . session_module_name() . ' Name: ' . session_name() . ' Id: ' . session_id();
echo "<br /><br />";
echo 'Session Env: ' . print_r($_SESSION, true);
echo "<br /><br />";
echo 'DreamFactory SQL Cloud API Version: ' . Defaults::API_VERSION . "<br />";
echo 'SQL DB Data Source Name: ' . Config::getEnvValue('DbDSN') . "<br />";
echo 'Storage Account: ' . Config::getEnvValue('BlobStorageKey1') . "<br />";
echo "<br />";
echo 'ENV: SQL DB Host: ' . getenv('DbDSN') . "<br />";
echo 'ENV: Storage Account: ' . getenv('BlobStorageKey1') . "<br />";
echo "<br /><br />";
echo "<br />";
phpinfo();

?>

<p>You may change the content of this page by modifying the following two files:</p>
<ul>
	<li>View file: <code><?php echo __FILE__; ?></code></li>
	<li>Layout file: <code><?php echo $this->getLayoutFile('main'); ?></code></li>
</ul>

<p>For more details on how to further develop this application, please read
the <a href="http://www.yiiframework.com/doc/">documentation</a>.
Feel free to ask in the <a href="http://www.yiiframework.com/forum/">forum</a>,
should you have any questions.</p>
