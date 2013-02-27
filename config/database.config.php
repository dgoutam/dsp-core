<?php
global $_dbName;

$_host = isset( $_SERVER, $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : gethostname();

$_dbName = 'dreamfactory';
$_dbUser = 'dsp_user';

if ( false !== strpos( $_host, '.cloud.dreamfactory.com' ) )
{
	$_parts = explode( '.', $_host );
	$_dbName = $_parts[0];
	$_dbUser = $_dbName . '_user';
}

return array(
	'connectionString' => 'mysql:host=localhost;port=3306;dbname=' . $_dbName,
	'username'         => $_dbUser,
	'password'         => $_dbUser,
	'emulatePrepare'   => true,
	'charset'          => 'utf8',
);