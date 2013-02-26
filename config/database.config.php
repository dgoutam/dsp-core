<?php
global $_dbName;

if ( 'cli' == PHP_SAPI )
{
	$_dbName = 'dreamfactory';
}
else
{
	$_host = $_SERVER['HTTP_HOST'];

	if ( false === strpos( $_host, '.cloud.dreamfactory.com' ) )
	{
		throw new CHttpException( 401, $_host . ': Not authorized.' );
	}

	$_parts = explode( '.', $_host );
	$_dbName = $_parts[0];
}

return array(
	'connectionString' => 'mysql:host=localhost;port=3306;dbname=' . $_dbName,
	'username'         => $_dbName . '_user',
	'password'         => $_dbName . '_user',
	'emulatePrepare'   => true,
	'charset'          => 'utf8',
);