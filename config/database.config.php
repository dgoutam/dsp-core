<?php
global $_dbName;

if ( 'cli' == PHP_SAPI || file_exists( '/var/www/launchpad/current' ) )
{
	$_dbName = 'dreamfactory';
	$_dbUser = 'dsp_user';
}
else
{
	$_host = $_SERVER['HTTP_HOST'];

	if ( false === strpos( $_host, '.cloud.dreamfactory.com' ) && 'localhost' != $_host )
	{
		throw new CHttpException( 401, $_host . ': Not authorized.' );
	}

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