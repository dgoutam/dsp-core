<?php
namespace DreamFactory\Platform\Services;

/**
 * Class TabularTest
 *
 * @package DreamFactory\Platform\Services
 */
class TabularTest extends \PHPUnit_Framework_TestCase
{
	protected $_fileName;

	/**
	 *
	 */
	protected function setUp()
	{
		$this->_fileName = __DIR__ . '/test.csv';
	}

	/**
	 *
	 */
	protected function tearDown()
	{
	}

}
