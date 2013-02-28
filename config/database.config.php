<?php
global $_dbName;

//	This file must be present to hit this block...
if ( file_exists( '/var/www/.fabric_hosted' ) )
{
	$_host = isset( $_SERVER, $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : gethostname();

	if ( false === strpos( $_host, '.cloud.dreamfactory.com' ) )
	{
		throw new CHttpException( 401, 'You are not authorized to access this system.' );
	}

	$_parts = explode( '.', $_host );
	$_dbName = $_parts[0];
	$_dbUser = $_dbName . '_user';
}
else
{
	$_dbName = 'dreamfactory';
	$_dbUser = 'dsp_user';
}

return array(
	'connectionString' => 'mysql:host=localhost;port=3306;dbname=' . $_dbName,
	'username'         => $_dbUser,
	'password'         => $_dbUser,
	'emulatePrepare'   => true,
	'charset'          => 'utf8',
);