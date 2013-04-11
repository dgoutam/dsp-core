<?php
namespace DreamFactory\Platform\Interfaces;

/**
 * WriterLike.php
 */
interface WriterLike
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param array $row
	 *
	 * @return bool
	 */
	public function write( array $row );
}
