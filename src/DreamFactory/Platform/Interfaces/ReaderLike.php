<?php
namespace DreamFactory\Platform\Interfaces;

/**
 * ReaderLike.php
 */
interface ReaderLike extends \SeekableIterator
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @return array
	 */
	public function read();
}
