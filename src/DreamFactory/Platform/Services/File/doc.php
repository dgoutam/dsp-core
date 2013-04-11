<?php
/**
 * Standard CSV file (comma-separated, fields enclosed by double quotes, slashed escapes, first row contains column names):
 */
$_reader = new \DreamFactory\Platform\Services\File\TabularReader( 'my-csv-file.csv' );

/**
 * Non-standard CSV file (comma-separated, no field enclosure):
 */
$_reader = new \DreamFactory\Platform\Services\File\TabularReader(
	array(
		 'fileName'  => 'my-funky-csv-file.csv',
		 'separator' => DataSeparator::COMMA,
		 'enclosure' => null,
	)
);

/**
 * Non-standard CSV file (tab-separated, single-quote field enclosure):
 */
$_reader = new \DreamFactory\Platform\Services\File\TabularReader(
	array(
		 'fileName'  => 'my-funky-csv-file.csv',
		 'separator' => DataSeparator::TAB,
		 'enclosure' => DataEnclosure::SINGLE_QUOTE,
		 'header'    => true | false //	(true if first row are field names...),
	)
);

/**
 * Get the column names...
 */
$_columnNames = $_reader->getKeys();

/**
 * Read the file...
 */
foreach ( $_reader as $_row )
{
	//	do stuff
}

/**
 * Go to 2nd line of file
 */
$_reader->seek( 2 );
$_secondRow = $_reader->current();

/**
 * How about a nifty little converter? (TSV => CSV)
 */
$_reader = new \DreamFactory\Platform\Services\File\TabularReader(
	array(
		 'fileName'  => 'my-funky-csv-file.csv',
		 'separator' => DataSeparator::COMMA,
		 'enclosure' => null,
	)
);

$_writer = new \DreamFactory\Platform\Services\File\TabularWriter(
	array(
		 'fileName'  => $_reader->getFileName() . '.tsv',
		 'separator' => \DreamFactory\Platform\Enums\DataSeparator::TAB,
		 'enclosure' => \DreamFactory\Platform\Enums\DataEnclosure::DOUBLE_QUOTE,
		 'keys'      => $_reader->getKeys(),
	)
);

//	Easy peasy...
foreach ( $_reader as $_row )
{
	$_writer->writeRow( $_row );
}
