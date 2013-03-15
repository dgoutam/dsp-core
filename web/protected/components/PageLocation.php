<?php
/**
 * PageLocation.php
 * The various places within a web page
 */
interface PageLocation
{
	//**************************************************************************
	//* Constants
	//**************************************************************************

	/**
	 * @var int Within the <HEAD> section
	 */
	const Head = 0;
	/**
	 * @var int After the <BODY> tag
	 */
	const Begin = 1;
	/**
	 * @var int Before the </BODY> tag
	 */
	const End = 2;
	/**
	 * @var int The window's "onload" function
	 */
	const Load = 3;
	/**
	 * @var int Inside the jQuery doc-ready function
	 */
	const DocReady = 4;

}
