<?php
/**
 * storage.helper.php
 *
 * @author Jerry Ablan <jerryablan@dreamfactory.com>
 *
 * This file provides directory listings to the snapshot service.
 */

/**
 * @param string $path
 *
 * @return array
 */
function _buildTree( $path )
{
	$_data = array();
	$_path = realpath( $path );

	if ( false === stripos( $_path, '/var/www/launchpad/', 0 ) )
	{
		return $_data;
	}

	$_objects = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $_path ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	/** @var $_node \SplFileInfo */
	foreach ( $_objects as $_name => $_node )
	{
		if ( $_node->isDir() || $_node->isLink() || '.' == $_name || '..' == $_name )
		{
			continue;
		}

		$_data[str_ireplace( $_path, null, dirname( $_node->getPathname() ) )][] = basename( $_name );
	}

	return $_data;
}

//.........................................................................
//. Main
//.........................................................................

echo json_encode( _buildTree( dirname( __DIR__ ) . '/storage' ) );