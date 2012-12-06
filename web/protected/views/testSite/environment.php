<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>Environment Information</title>
</head>

<body>
<h1>DreamFactory Cloud Environment Information</h1>

<?php
require_once dirname(__FILE__) . '/../DreamFactory/CloudServicesPlatform/AutoLoader.php';
use CloudServicesPlatform\Utilities\Config;

echo "Session Info: <br />";
echo 'Module: ' . session_module_name() . ' Name: ' . session_name() . ' Id: ' . session_id();
echo "<br /><br />";
echo 'Session Env: ' . print_r($_SESSION, true);
echo "<br /><br />";
echo 'DreamFactory SQL Cloud API Version: ' . Config::API_VERSION . "<br />";
echo 'SQL DB Data Source Name: ' . Config::getEnvValue('DbDSN') . "<br />";
echo 'Storage Account: ' . Config::getEnvValue('BlobStorageKey1') . "<br />";
echo "<br />";
echo 'ENV: SQL DB Host: ' . getenv('DbDSN') . "<br />";
echo 'ENV: Storage Account: ' . getenv('BlobStorageKey1') . "<br />";
echo "<br /><br /><a href='../index.php'>Back</a> to Main Menu.<br />";
echo "<br />";
phpinfo();

?>

</body>
</html>
