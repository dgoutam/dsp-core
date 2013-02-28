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
	$_iterator = new \DirectoryIterator( $path );

	$_data = array();

	/** @var $_node \DirectoryIterator */
	foreach ( $_iterator as $_node )
	{
		if ( $_node->isDir() && !$_node->isDot() )
		{
			$_data[$_node->getFilename()] = _buildTree( new \DirectoryIterator( $_node->getPathname() ) );
		}
		else if ( $_node->isFile() )
		{
			$_data[] = $_node->getFilename();
		}
	}

	return $_data;
}

//.........................................................................
//. Main
//.........................................................................

echo json_encode( _buildTree( dirname( __DIR__ ) . '/storage' ) );