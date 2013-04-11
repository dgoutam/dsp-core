<?php
namespace DreamFactory\Platform\Services\File;

use DreamFactory\Platform\Enums\DataSeparator;

require_once __DIR__ . '/../../../../../vendor/autoload.php';

/**
 * Class TabularTest
 *
 * @package DreamFactory\Platform\Services
 */
class TabularTest extends \PHPUnit_Framework_TestCase
{
	protected $_fileName;

	public function testReadTsv()
	{
		$_reader = new TabularReader(
			array(
				 'fileName'  => __DIR__ . '/test-data.tsv',
				 'enclosure' => null,
				 'separator' => "\t",
			)
		);

		$_lines = 0;

		foreach ( $_reader as $_row )
		{
			$_lines++;
			echo implode( ', ', $_row ) . PHP_EOL;
		}

		echo PHP_EOL;
		echo 'Read ' . $_lines . ' rows (not including header).' . PHP_EOL;
	}

	public function testReadCsv()
	{
//		$_reader = new TabularReader(
//			array(
//				 'fileName'  => $this->_fileName,
//				 'enclosure' => null,
//			)
//		);
//
//		$_lines = 0;
//
//		foreach ( $_reader as $_row )
//		{
//			$_lines++;
//			echo implode( ', ', $_row ) . PHP_EOL;
//		}
//
//		echo PHP_EOL;
//		echo 'Read ' . $_lines . ' rows (not including header).' . PHP_EOL;
	}

	public function testWriteCsv()
	{
		$_reader = new TabularReader(
			array(
				 'fileName'  => __DIR__ . '/test-data.tsv',
				 'enclosure' => null,
				 'separator' => "\t",
			)
		);

		$_tsvWriter = new TabularWriter(
			array(
				 'fileName'  => __DIR__ . '/write-test-out-test-data.tsv',
				 'keys'      => $_reader->getKeys(),
				 'separator' => DataSeparator::TAB,
			)
		);

		$_csvWriter = new TabularWriter(
			array(
				 'fileName'  => __DIR__ . '/write-test-out-test-data.csv',
				 'keys'      => $_reader->getKeys(),
				 'separator' => DataSeparator::COMMA,
			)
		);

		$_psvWriter = new TabularWriter(
			array(
				 'fileName'  => __DIR__ . '/write-test-out-test-data.psv',
				 'keys'      => $_reader->getKeys(),
				 'separator' => DataSeparator::PIPE,
			)
		);

		$_lines = 0;

		foreach ( $_reader as $_row )
		{
			$_lines++;
			$_csvWriter->writeRow( $_row );
			$_tsvWriter->writeRow( $_row );
			$_psvWriter->writeRow( $_row );
		}

		echo PHP_EOL;
		echo 'Read ' . $_lines . ' rows (not including header).' . PHP_EOL;
		echo PHP_EOL;
		echo 'Wrote ' . $_csvWriter->getRowsOut() . ' CSV rows (including header).' . PHP_EOL;
		echo 'Wrote ' . $_tsvWriter->getRowsOut() . ' TSV rows (including header).' . PHP_EOL;
		echo 'Wrote ' . $_psvWriter->getRowsOut() . ' PSV rows (including header).' . PHP_EOL;
	}

	/**
	 *
	 */
	protected function setUp()
	{
		$this->_fileName = __DIR__ . '/test-data-no_enclosure.csv';
	}

	/**
	 *
	 */
	protected function tearDown()
	{
	}

}
