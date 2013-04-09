<?php
namespace DreamFactory\Platform\Services;

use DreamFactory\Platform\Exceptions\FileSystemException;
use DreamFactory\Platform\Interfaces\EscapeStyle;
use DreamFactory\Platform\Interfaces\ReaderLike;
use DreamFactory\Platform\Interfaces\WriterLike;
use Kisma\Core\Seed;

/**
 * Csv.php
 */
class Csv extends Seed implements ReaderLike, WriterLike
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
	protected $_fieldSeparator = ',';
	/**
	 * @var string
	 */
	protected $_fieldEnclosure = '"';
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
	 * @var bool
	 */
	protected $_header = true;
	/**
	 * @var int
	 */
	protected $_escapeStyle = EscapeStyle::DOUBLED;
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
	protected $_ignoreWhitespace = true;
	/**
	 * @var bool
	 */
	protected $_eof = false;
	/**
	 * @var bool
	 */
	protected $_overrideKeys = true;
	/**
	 * @var bool
	 */
	protected $_bof = false;
	/**
	 * @var array
	 */
	protected $_lineHandlers = array( 'before' => null, 'after' => null );
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
		if ( is_resource( $this->_handle ) )
		{
			if ( !fclose( $this->_handle ) )
			{
				throw new FileSystemException( 'Error closing file: ' . $this->_fileName );
			}
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

		if ( false === ( $_row = $this->readRow() ) )
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
		if ( $this->valid() )
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
		if ( $this->valid() )
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
			$_header = $this->readRow( true );

			if ( $_header === false )
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
		$this->_bof = true;
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

		while ( $this->_rowId < $index && $this->valid() )
		{
			$this->next();
		}

		if ( !$this->valid() )
		{
			throw new \OutOfBoundsException( 'Invalid position' );
		}
	}

	/**
	 * @param bool $rewinding
	 *
	 * @return array|bool
	 * @throws Exception
	 */
	protected function readRow( $rewinding = false )
	{
		if ( !$this->_bof && !$rewinding )
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

			if ( !is_callable( $this->_lineHandlers['before'] ) )
			{
				$_line = call_user_func( $this->_lineHandlers['before'], $_line );

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

			if ( empty( $this->_fieldEnclosure ) )
			{
				return explode( $this->_fieldSeparator, preg_replace( '#\\r?\\n$#', null, $_buffer ) );
			}

			switch ( $this->_escapeStyle )
			{
				case EscapeStyle::DOUBLED:
					$_result = $this->explodeExcel( $_buffer );
					break;

				case EscapeStyle::SLASHED:
					$_result = $this->explodeUnix( $_buffer );
					break;

				case EscapeStyle::NONE:
				default:
					$_result = $this->explodeNoEscape( $_buffer );
					break;
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

	protected function explodeRegex( $_line, $regex )
	{
		$regex = str_replace( '#', '\\#', $regex );
		$wrap = preg_quote( $this->_fieldEnclosure, '#' );
		$sep = preg_quote( $this->separator, '#' );
		$fullre = '#^(?:' . $regex . ',)*' . $regex . '(?:\\r\\n|\\n)$#s';
		$fullre = str_replace( array( '"', ',' ), array( $wrap, $sep ), $fullre );

		$res = preg_match( $fullre, $_line );
		if ( $res === false )
		{
			throw new Exception( 'Regular expression error while matching line' );
		}

		if ( !$res )
		{
			return false;
		}

		$regex = '#' . $regex . '(?:,|\\r\\n|\\n)#s';
		$regex = str_replace( array( '"', ',' ), array( $wrap, $sep ), $regex );

		$count = preg_match_all( $regex, $_line, $matches, PREG_SET_ORDER );
		if ( $count === false )
		{
			throw new Exception( 'Regular expression error while exploding line' );
		}
		$ret = array();

		for ( $i = 0; $i < $count; $i++ )
		{
			unset( $matches[$i][0] );
			$ret[] = implode( '', $matches[$i] );
		}

		return $ret;
	}

	protected function explodeExcel( $_line )
	{
		$ret = $this->explodeRegex( $_line, '(?:"((?:""|[^"])*)"|((?U)[^,"]*))' );

		if ( $ret )
		{
			foreach ( $ret as $i => $val )
			{
				$ret[$i] = str_replace( $this->_fieldEnclosure . $this->_fieldEnclosure, $this->_fieldEnclosure, $val );
			}
		}

		return $ret;
	}

	protected function explodeUnix( $_line )
	{
		$ret = $this->explodeRegex( $_line, '(?:"((?:\\\\"|[^"])*)"|((?U)[^,"]*))' );

		if ( $ret )
		{
			foreach ( $ret as $i => $val )
			{
				$ret[$i] = str_replace( '\\' . $this->_fieldEnclosure, $this->_fieldEnclosure, $val );
			}
		}

		return $ret;
	}

	protected function explodeTME( $_line )
	{
		return $this->explodeRegex( $_line, '(?:""|"((?:""|[^"])*,(?:""|[^"])*)"|((?U)[^,]*))' );
	}

	protected function explodeNoEscape( $_line )
	{
		return $this->explodeRegex( $_line, '(?:"((?U).*)")' );
	}

	protected function explodeNone( $_line )
	{
		$_line = preg_replace( '#\\r?\\n$#', '', $_line );

		return explode( $this->separator, $_line );
	}

	/**
	 * @return array
	 */
	public function read()
	{
		// TODO: Implement read() method.
	}

	/**
	 * @param array $row
	 *
	 * @return bool
	 */
	public function write( array $row )
	{
		// TODO: Implement write() method.
	}

	/***
	 * @return array
	 */
	public function keys()
	{
		// TODO: Implement keys() method.
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
		if ( !$this->_bof )
		{
			$this->rewind();
		}

		return $this->_keys;
	}

	/**
	 * @return null|string
	 */
	public function getLastLine()
	{
		return $this->_lastLine;
	}

	/**
	 * @return null|string
	 */
	public function getLastBuffer()
	{
		return $this->_lastBuffer;
	}

	/**
	 * @param boolean $bof
	 *
	 * @return Csv
	 */
	public function setBof( $bof )
	{
		$this->_bof = $bof;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getBof()
	{
		return $this->_bof;
	}

	/**
	 * @param array $currentRow
	 *
	 * @return Csv
	 */
	public function setCurrentRow( $currentRow )
	{
		$this->_currentRow = $currentRow;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getCurrentRow()
	{
		return $this->_currentRow;
	}

	/**
	 * @param boolean $eof
	 *
	 * @return Csv
	 */
	public function setEof( $eof )
	{
		$this->_eof = $eof;

		return $this;
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
	 * @return Csv
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
	 * @param string $fieldEnclosure
	 *
	 * @return Csv
	 */
	public function setFieldEnclosure( $fieldEnclosure )
	{
		$this->_fieldEnclosure = $fieldEnclosure;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFieldEnclosure()
	{
		return $this->_fieldEnclosure;
	}

	/**
	 * @param string $fieldSeparator
	 *
	 * @return Csv
	 */
	public function setFieldSeparator( $fieldSeparator )
	{
		$this->_fieldSeparator = $fieldSeparator;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFieldSeparator()
	{
		return $this->_fieldSeparator;
	}

	/**
	 * @param string $fileName
	 *
	 * @return Csv
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
	 * @param resource $handle
	 *
	 * @return Csv
	 */
	public function setHandle( $handle )
	{
		$this->_handle = $handle;

		return $this;
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
	 * @return Csv
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
	 * @return Csv
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
	 * @param callback $before
	 * @param callback $after
	 *
	 * @return Csv
	 */
	public function setLineHandlers( $before = null, $after = null )
	{
		$this->_lineHandlers = array( 'before' => $before, 'after' => $after );

		return $this;
	}

	/**
	 * @return array
	 */
	public function getLineHandlers()
	{
		return $this->_lineHandlers;
	}

	/**
	 * @param boolean $overrideKeys
	 *
	 * @return Csv
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
	 * @param int $skipLines
	 *
	 * @return Csv
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

}
