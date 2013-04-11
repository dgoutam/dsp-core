<?php
namespace DreamFactory\Platform\Services\File;

use DreamFactory\Platform\Exceptions\FileSystemException;
use DreamFactory\Platform\Interfaces\EscapeStyle;
use DreamFactory\Platform\Interfaces\ReaderLike;
use Kisma\Core\Seed;

/**
 * TabularReader.php
 * Tabular data reader
 */
class TabularReader extends Seed implements ReaderLike
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var integer The number of lines to skip when reading the file for the first time
	 */
	protected $_skipLines = 0;
	/**
	 * @var string
	 */
	protected $_separator = ',';
	/**
	 * @var string
	 */
	protected $_enclosure = '"';
	/**
	 * @var int
	 */
	protected $_escapeStyle = EscapeStyle::SLASHED;
	/**
	 * @var bool
	 */
	protected $_ignoreWhitespace = true;
	/**
	 * @var bool End of File
	 */
	protected $_eof = false;
	/**
	 * @var bool Beginning of File
	 */
	protected $_rewound = false;
	/**
	 * @var string
	 */
	protected $_fileName;
	/**
	 * @var resource
	 */
	protected $_handle;
	/**
	 * @var array
	 */
	protected $_keys;
	/**
	 * @var array
	 */
	protected $_currentRow;
	/**
	 * @var int
	 */
	protected $_rowId = -1;
	/**
	 * @var bool
	 */
	protected $_overrideKeys = false;
	/**
	 * @var bool
	 */
	protected $_header = true;
	/**
	 * @var callback
	 */
	protected $_beforeLineCallback = null;
	/**
	 * @var callback
	 */
	protected $_afterLineCallback = null;
	/**
	 * @var string
	 */
	protected $_lastLine = null;
	/**
	 * @var string
	 */
	protected $_lastBuffer = null;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $settings = array() )
	{
		if ( is_string( $settings ) )
		{
			$settings = array( 'fileName' => $settings );
		}

		parent::__construct( $settings );

		if ( null === $this->_fileName )
		{
			throw new \InvalidArgumentException( 'No "fileName" specified.' );
		}
	}

	/**
	 * Choose your destructor!
	 */
	public function __destruct()
	{
		if ( is_resource( $this->_handle ) )
		{
			fclose( $this->_handle );
		}
	}

	/**
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
	 */
	public function close()
	{
		if ( is_resource( $this->_handle ) && !fclose( $this->_handle ) )
		{
			throw new FileSystemException( 'Error closing file: ' . $this->_fileName );
		}

		$this->_eof = true;
		$this->_currentRow = null;
		$this->_rowId = -1;
		$this->_handle = null;
	}

	/**
	 * @return array|bool|mixed|null
	 */
	public function current()
	{
		if ( null !== $this->_currentRow )
		{
			return $this->_currentRow;
		}

		if ( false === ( $_row = $this->_readLine() ) )
		{
			return null;
		}

		if ( null === $this->_keys )
		{
			return $this->_currentRow = $_row;
		}

		$this->_currentRow = array();

		reset( $this->_keys );

		foreach ( $_row as $_column )
		{
			if ( false === ( $_key = each( $this->_keys ) ) )
			{
				break;
			}

			$this->_currentRow[$_key['value']] = $_column;
		}

		return $this->_currentRow;
	}

	/**
	 * @return mixed|null
	 */
	public function key()
	{
		if ( null !== $this->current() )
		{
			return $this->_rowId;
		}

		return null;
	}

	/**
	 *
	 */
	public function next()
	{
		if ( null !== $this->current() )
		{
			$this->_currentRow = null;
			$this->_rowId++;
		}
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		return ( null !== $this->current() );
	}

	/**
	 * @throws Exception
	 */
	public function rewind()
	{
		$this->close();

		if ( false === ( $this->_handle = fopen( $this->_fileName, 'r' ) ) )
		{
			throw new FileSystemException( 'Unable to open file: ' . $this->_fileName );
		}

		$this->_eof = false;

		//	Skip the first "x" lines based on $skipLines property
		$_count = $this->_skipLines;

		while ( $_count && false !== ( $_line = fgets( $this->_handle ) ) )
		{
			--$_count;
		}

		if ( $this->_header )
		{
			if ( false === ( $_header = $this->_readLine( true ) ) )
			{
				throw new FileSystemException( 'Error reading header row from file: ' . $this->_fileName );
			}

			if ( !$this->_overrideKeys )
			{
				$this->_keys = $_header;
			}
		}

		$this->_currentRow = null;
		$this->_rowId = 1;
		$this->_rewound = true;
	}

	/**
	 * @param int $index
	 *
	 * @throws \OutOfBoundsException
	 */
	public function seek( $index )
	{
		$this->rewind();

		if ( $index < 1 )
		{
			throw new \OutOfBoundsException( 'Invalid position' );
		}

		while ( $this->_rowId < $index && null !== $this->current() )
		{
			$this->next();
		}

		if ( null === $this->current() )
		{
			throw new \OutOfBoundsException( 'Invalid position' );
		}
	}

	/**
	 * @param array $keys
	 */
	public function setKeys( $keys )
	{
		$this->_keys = $keys;
		$this->_overrideKeys = true;
	}

	/**
	 * @return array
	 */
	public function getKeys()
	{
		if ( !$this->_rewound )
		{
			$this->rewind();
		}

		return $this->_keys;
	}

	/**
	 * @param callable $afterLineCallback
	 *
	 * @return TabularReader
	 */
	public function setAfterLineCallback( $afterLineCallback )
	{
		$this->_afterLineCallback = $afterLineCallback;

		return $this;
	}

	/**
	 * @return callable
	 */
	public function getAfterLineCallback()
	{
		return $this->_afterLineCallback;
	}

	/**
	 * @param callable $beforeLineCallback
	 *
	 * @return TabularReader
	 */
	public function setBeforeLineCallback( $beforeLineCallback )
	{
		$this->_beforeLineCallback = $beforeLineCallback;

		return $this;
	}

	/**
	 * @return callable
	 */
	public function getBeforeLineCallback()
	{
		return $this->_beforeLineCallback;
	}

	/**
	 * @return boolean
	 */
	public function getRewound()
	{
		return $this->_rewound;
	}

	/**
	 * @return array
	 */
	public function getCurrentRow()
	{
		return $this->_currentRow;
	}

	/**
	 * @param string $enclosure
	 *
	 * @return TabularReader
	 */
	public function setEnclosure( $enclosure )
	{
		$this->_enclosure = $enclosure;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEnclosure()
	{
		return $this->_enclosure;
	}

	/**
	 * @return boolean
	 */
	public function getEof()
	{
		return $this->_eof;
	}

	/**
	 * @param int $escapeStyle
	 *
	 * @return TabularReader
	 */
	public function setEscapeStyle( $escapeStyle )
	{
		$this->_escapeStyle = $escapeStyle;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getEscapeStyle()
	{
		return $this->_escapeStyle;
	}

	/**
	 * @param string $fileName
	 *
	 * @return TabularReader
	 */
	public function setFileName( $fileName )
	{
		$this->_fileName = $fileName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFileName()
	{
		return $this->_fileName;
	}

	/**
	 * @return resource
	 */
	public function getHandle()
	{
		return $this->_handle;
	}

	/**
	 * @param boolean $header
	 *
	 * @return TabularReader
	 */
	public function setHeader( $header )
	{
		$this->_header = $header;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @param boolean $ignoreWhitespace
	 *
	 * @return TabularReader
	 */
	public function setIgnoreWhitespace( $ignoreWhitespace )
	{
		$this->_ignoreWhitespace = $ignoreWhitespace;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getIgnoreWhitespace()
	{
		return $this->_ignoreWhitespace;
	}

	/**
	 * @return string
	 */
	public function getLastBuffer()
	{
		return $this->_lastBuffer;
	}

	/**
	 * @return string
	 */
	public function getLastLine()
	{
		return $this->_lastLine;
	}

	/**
	 * @param boolean $overrideKeys
	 *
	 * @return TabularReader
	 */
	public function setOverrideKeys( $overrideKeys )
	{
		$this->_overrideKeys = $overrideKeys;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getOverrideKeys()
	{
		return $this->_overrideKeys;
	}

	/**
	 * @return int
	 */
	public function getRowId()
	{
		return $this->_rowId;
	}

	/**
	 * @param string $separator
	 *
	 * @return TabularReader
	 */
	public function setSeparator( $separator )
	{
		$this->_separator = $separator;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->_separator;
	}

	/**
	 * @param int $skipLines
	 *
	 * @return TabularReader
	 */
	public function setSkipLines( $skipLines )
	{
		$this->_skipLines = $skipLines;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSkipLines()
	{
		return $this->_skipLines;
	}

	/**
	 * @param bool $rewinding
	 *
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
	 * @return array|bool
	 */
	protected function _readLine( $rewinding = false )
	{
		if ( !$this->_rewound && !$rewinding )
		{
			$this->rewind();
		}

		if ( $this->_eof )
		{
			return false;
		}

		$_buffer = null;

		while ( false !== ( $_line = fgets( $this->_handle ) ) )
		{
			$this->_lastLine = $_line;

			if ( is_callable( $this->_beforeLineCallback ) )
			{
				$_line = call_user_func( $this->_beforeLineCallback, $_line );

				if ( substr( $_line, 0, -1 ) != PHP_EOL )
				{
					$_line .= PHP_EOL;
				}
			}

			if ( !trim( $_line ) && !$_buffer && $this->_ignoreWhitespace )
			{
				continue;
			}

			$_buffer .= $_line;

			if ( empty( $this->_enclosure ) )
			{
				$_result = $this->_parseNone( $_buffer );
			}
			else
			{
				switch ( $this->_escapeStyle )
				{
					case EscapeStyle::DOUBLED:
						$_result = $this->_parseDoubled( $_buffer );
						break;

					case EscapeStyle::SLASHED:
						$_result = $this->_parseSlashed( $_buffer );
						break;

					case EscapeStyle::NONE:
					default:
						$_result = $this->_parseNonEscaped( $_buffer );
						break;
				}
			}

			if ( is_callable( $this->_afterLineCallback ) )
			{
				$_result = call_user_func( $this->_afterLineCallback, $_result );
			}

			if ( false !== $_result )
			{
				$this->_lastBuffer = $_buffer;

				return $_result;
			}
		}

		if ( false !== ( $this->_eof = feof( $this->_handle ) ) )
		{
			if ( !empty( $_buffer ) )
			{
				throw new FileSystemException( 'Cannot parse data at record #' . $this->_rowId . '.' );
			}

			return false;
		}

		throw new FileSystemException( 'Cannot read file: ' . $this->_fileName );
	}

	/**
	 * @param string $line
	 * @param string $regex
	 *
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
	 * @return array|bool
	 */
	protected function _parsePattern( $line, $regex )
	{
		$regex = str_replace( '#', '\\#', $regex );
		$_wrap = preg_quote( $this->_enclosure, '#' );
		$_sep = preg_quote( $this->_separator, '#' );
		$_regexp = '#^(?:' . $regex . ',)*' . $regex . '(?:\\r\\n|\\n)$#s';
		$_regexp = str_replace( array( '"', ',' ), array( $_wrap, $_sep ), $_regexp );

		if ( false === ( $_result = preg_match( $_regexp, $line ) ) )
		{
			throw new FileSystemException( 'Pattern matching error while processing line' );
		}

		if ( !$_result )
		{
			return false;
		}

		$regex = '#' . $regex . '(?:,|\\r\\n|\\n)#s';
		$regex = str_replace( array( '"', ',' ), array( $_wrap, $_sep ), $regex );

		if ( false === ( $_count = preg_match_all( $regex, $line, $matches, PREG_SET_ORDER ) ) )
		{
			throw new FileSystemException( 'Pattern matching error while processing line' );
		}

		$_response = array();

		for ( $_i = 0; $_i < $_count; $_i++ )
		{
			unset( $matches[$_i][0] );
			$_response[] = implode( '', $matches[$_i] );
		}

		return $_response;
	}

	/**
	 * @param string $line
	 *
	 * @return array|bool
	 */
	protected function _parseDoubled( $line )
	{
		$_result = $this->_parsePattern( $line, '(?:"((?:""|[^"])*)"|((?U)[^,"]*))' );

		if ( $_result )
		{
			array_walk(
				$_result,
				function ( &$value, $enclosure )
				{
					$value = str_replace( $enclosure . $enclosure, $enclosure, $value );
				},
				$this->_enclosure
			);
		}

		return $_result;
	}

	/**
	 * @param string $line
	 *
	 * @return mixed
	 */
	protected function _parseSlashed( $line )
	{
		$_result = $this->_parsePattern( $line, '(?:"((?:\\\\"|[^"])*)"|((?U)[^,"]*))' );

		if ( $_result )
		{
			array_walk(
				$_result,
				function ( &$value, $enclosure )
				{
					$value = str_replace( '\\' . $enclosure, $enclosure, $value );
				},
				$this->_enclosure
			);
		}

		return $_result;
	}

	/**
	 * @param string $line
	 *
	 * @return array|bool
	 */
	protected function _parseNonEscaped( $line )
	{
		return $this->_parsePattern( $line, '(?:"((?U).*)")' );
	}

	/**
	 * @param string $line
	 *
	 * @return array
	 */
	protected function _parseNone( $line )
	{
		$_line = preg_replace( '#\\r?\\n$#', '', $line );
		return explode( $this->_separator, $_line );
	}

}
