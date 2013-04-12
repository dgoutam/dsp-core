<?php
namespace DreamFactory\Platform\Services\File;

use DreamFactory\Platform\Exceptions\FileSystemException;
use DreamFactory\Platform\Interfaces\ReaderLike;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Log;

/**
 * Reader.php
 * File reader
 */
class Reader extends Seed implements ReaderLike
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var integer The number of lines to skip when reading the file for the first time
	 */
	protected $_skipLines = 0;
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
	protected $_currentLine;
	/**
	 * @var int
	 */
	protected $_lineNumber = -1;
	/**
	 * @var callback
	 */
	protected $_beforeLineCallback = null;
	/**
	 * @var callback
	 */
	protected $_afterLineCallback = null;

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
			if ( false === @fclose( $this->_handle ) )
			{
				Log::error( 'Error closing file during destruct(): ' . $this->_fileName );
			}
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
		$this->_currentLine = null;
		$this->_lineNumber = -1;
		$this->_handle = null;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the key of the current element
	 *
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key()
	{
		if ( null !== $this->current() )
		{
			return $this->_lineNumber;
		}

		return null;
	}

	/**
	 * @return array|bool|mixed|null
	 */
	public function current()
	{
		if ( null !== $this->_currentLine )
		{
			return $this->_currentLine;
		}

		if ( false === ( $_line = $this->_readLine() ) )
		{
			return null;
		}

		return $this->_currentLine = $_line;
	}

	/**
	 * The next line
	 */
	public function next()
	{
		if ( null !== $this->current() )
		{
			$this->_currentLine = null;
			$this->_lineNumber++;
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
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
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

		$this->_currentLine = null;
		$this->_lineNumber = 1;
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

		while ( $this->_lineNumber < $index && null !== $this->current() )
		{
			$this->next();
		}

		if ( null === $this->current() )
		{
			throw new \OutOfBoundsException( 'Invalid position' );
		}
	}

	/**
	 * @param callable $afterLineCallback
	 *
	 * @return Reader
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
	 * @return Reader
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
	public function getCurrentLine()
	{
		return $this->_currentLine;
	}

	/**
	 * @return boolean
	 */
	public function getEof()
	{
		return $this->_eof;
	}

	/**
	 * @param string $fileName
	 *
	 * @return Reader
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
	 * @param boolean $ignoreWhitespace
	 *
	 * @return Reader
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
	 * @return int
	 */
	public function getLineNumber()
	{
		return $this->_lineNumber;
	}

	/**
	 * @param int $skipLines
	 *
	 * @return Reader
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
			if ( is_callable( $this->_beforeLineCallback ) )
			{
				$_line = call_user_func( $this->_beforeLineCallback, $_line );

				if ( substr( $_line, 0, -1 ) != PHP_EOL )
				{
					$_line .= PHP_EOL;
				}
			}

			$_line = trim( $_line );

			if ( $this->_ignoreWhitespace && empty( $_line ) && empty( $_buffer ) )
			{
				continue;
			}

			$_buffer .= $_line;
			$_result = $this->_parseLine( $_buffer );

			if ( is_callable( $this->_afterLineCallback ) )
			{
				if ( false === ( $_result = call_user_func( $this->_afterLineCallback, $_result ) ) )
				{
					//	Skip this line if callback calls foul
					continue;
				}
			}

			if ( false !== $_result )
			{
				return $_result;
			}
		}

		if ( false !== ( $this->_eof = feof( $this->_handle ) ) )
		{
			if ( !empty( $_buffer ) )
			{
				throw new FileSystemException( 'Cannot parse data at record #' . $this->_lineNumber . '.' );
			}

			return false;
		}

		throw new FileSystemException( 'Cannot read file: ' . $this->_fileName );
	}

	/**
	 * Opportunity to parse the line out if you want
	 *
	 * @param string $line
	 *
	 * @return mixed
	 */
	protected function _parseLine( $line )
	{
		//	Does nothing, like the goggles.
		return $line;
	}
}
