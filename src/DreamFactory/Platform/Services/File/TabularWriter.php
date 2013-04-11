<?php
namespace DreamFactory\Platform\Services\File;

use DreamFactory\Platform\Enums\DataEnclosure;
use DreamFactory\Platform\Enums\DataSeparator;
use DreamFactory\Platform\Enums\LineBreak;
use DreamFactory\Platform\Exceptions\FileSystemException;
use DreamFactory\Platform\Interfaces\EscapeStyle;
use DreamFactory\Platform\Interfaces\WriterLike;
use Kisma\Core\Seed;

/**
 * TabularWriter.php
 * Tabular data writer
 */
class TabularWriter extends Seed implements WriterLike
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	protected $_rowsOut = 0;
	/**
	 * @var int
	 */
	protected $_linesOut = 0;
	/**
	 * @var string
	 */
	protected $_separator = DataSeparator::COMMA;
	/**
	 * @var string
	 */
	protected $_enclosure = DataEnclosure::DOUBLE_QUOTE;
	/**
	 * @var int
	 */
	protected $_escapeStyle = EscapeStyle::SLASHED;
	/**
	 * @var string|null
	 */
	protected $_nullValue = null;
	/**
	 * @var string
	 */
	protected $_lineBreak = LineBreak::Linux;
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
	protected $_autoWriteHeader = true;
	/**
	 * @var bool
	 */
	protected $_appendEOL = true;
	/**
	 * @var bool
	 */
	protected $_wrapWhitespace = false;
	/**
	 * @var bool
	 */
	protected $_lazyWrap = false;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
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

		if ( false === ( $this->_handle = fopen( $this->_fileName, 'w' ) ) )
		{
			throw new FileSystemException( 'Cannot open file "' . $this->_fileName . '" for writing.' );
		}
	}

	/**
	 * @param bool  $autoOnly
	 * @param array $header
	 */
	protected function _writeHeader( $autoOnly = false, array $header = null )
	{
		if ( $autoOnly && !$this->_autoWriteHeader )
		{
			return;
		}

		if ( null === $header )
		{
			$header = $this->_keys;
		}

		if ( !is_array( $header ) )
		{
			$header = array( (string)$header );
		}

		$this->_write( $header );
		$this->_autoWriteHeader = false;
	}

	/**
	 * @param array $data
	 */
	public function writeRow( array $data )
	{
		$this->_writeHeader( true );

		if ( empty( $this->_keys ) )
		{
			$_data = $data;
		}
		else
		{
			$_data = array();

			foreach ( $this->_keys as $_key )
			{
				$_data[] = isset( $data[$_key] ) ? $data[$_key] : null;
			}
		}

		$this->_write( $_data );
		$this->_rowsOut++;
	}

	/**
	 * @param array $data
	 *
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
	 */
	protected function _write( $data )
	{
		if ( null === $this->_handle )
		{
			throw new FileSystemException( 'The file must be open to write data.' );
		}

		$_values = array();

		foreach ( $data as $_value )
		{
			if ( null === $_value )
			{
				if ( null !== $this->_nullValue )
				{
					$_values[] = $this->_nullValue;
					continue;
				}

				$_value = '';
			}

			if ( '' === $_value )
			{
				$_values[] = !$this->_wrapWhitespace ? '' : ( $this->_enclosure . $this->_enclosure );
				continue;
			}

			if ( $this->_lazyWrap && false === strpos( $_value, $this->_separator ) &&
				 ( $this->_enclosure === '' || false === strpos( $_value, $this->_enclosure ) )
			)
			{
				$_values[] = $_value;
				continue;
			}

			switch ( $this->_escapeStyle )
			{
				case EscapeStyle::DOUBLED:
					$_value = str_replace( $this->_enclosure, $this->_enclosure . $this->_enclosure, $_value );
					break;
				case EscapeStyle::SLASHED:
					$_value = str_replace( $this->_enclosure, '\\' . $this->_enclosure, str_replace( '\\', '\\\\', $_value ) );
					break;
			}

			$_values[] = $this->_enclosure . $_value . $this->_enclosure;
		}

		$_line = implode( $this->_separator, $_values );

		if ( !$this->_appendEOL )
		{
			$_line .= $this->_lineBreak;
		}
		else if ( $this->_linesOut > 0 )
		{
			$_line = $this->_lineBreak . $_line;
		}

		if ( false === ( $_byteCount = fwrite( $this->_handle, $_line ) ) )
		{
			throw new FileSystemException( 'Error writing to file: ' . $this->_fileName );
		}

		if ( $_byteCount != mb_strlen( $_line ) )
		{
			throw new FileSystemException( 'Failed to write entire buffer to file: ' . $this->_fileName );
		}

		$this->_linesOut++;
	}

	/**
	 * Choose your destructor!
	 */
	public function __destruct()
	{
		if ( is_resource( $this->_handle ) )
		{
			$this->_writeHeader( true );
			@fclose( $this->_handle );
			$this->_handle = null;
		}
	}

	/**
	 * @throws \DreamFactory\Platform\Exceptions\FileSystemException
	 */
	public function close()
	{
		if ( is_resource( $this->_handle ) )
		{
			$this->_writeHeader( true );

			if ( !fclose( $this->_handle ) )
			{
				throw new FileSystemException( 'Error closing file: ' . $this->_fileName );
			}

			$this->_handle = null;
		}

		return $this->_rowsOut;
	}

	/**
	 * @return boolean
	 */
	public function getAppendEOL()
	{
		return $this->_appendEOL;
	}

	/**
	 * @return boolean
	 */
	public function getAutoWriteHeader()
	{
		return $this->_autoWriteHeader;
	}

	/**
	 * @return string
	 */
	public function getEnclosure()
	{
		return $this->_enclosure;
	}

	/**
	 * @return int
	 */
	public function getEscapeStyle()
	{
		return $this->_escapeStyle;
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
	 * @return array
	 */
	public function getKeys()
	{
		return $this->_keys;
	}

	/**
	 * @return boolean
	 */
	public function getLazyWrap()
	{
		return $this->_lazyWrap;
	}

	/**
	 * @return string
	 */
	public function getLineBreak()
	{
		return $this->_lineBreak;
	}

	/**
	 * @return int
	 */
	public function getLinesOut()
	{
		return $this->_linesOut;
	}

	/**
	 * @return null|string
	 */
	public function getNullValue()
	{
		return $this->_nullValue;
	}

	/**
	 * @return int
	 */
	public function getRowsOut()
	{
		return $this->_rowsOut;
	}

	/**
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->_separator;
	}

	/**
	 * @return boolean
	 */
	public function getWrapWhitespace()
	{
		return $this->_wrapWhitespace;
	}

	/**
	 * @param boolean $appendEOL
	 *
	 * @return TabularWriter
	 */
	public function setAppendEOL( $appendEOL )
	{
		$this->_appendEOL = $appendEOL;

		return $this;
	}

	/**
	 * @param boolean $autoWriteHeader
	 *
	 * @return TabularWriter
	 */
	public function setAutoWriteHeader( $autoWriteHeader )
	{
		$this->_autoWriteHeader = $autoWriteHeader;

		return $this;
	}

	/**
	 * @param string $enclosure
	 *
	 * @return TabularWriter
	 */
	public function setEnclosure( $enclosure )
	{
		$this->_enclosure = $enclosure;

		return $this;
	}

	/**
	 * @param int $escapeStyle
	 *
	 * @return TabularWriter
	 */
	public function setEscapeStyle( $escapeStyle )
	{
		$this->_escapeStyle = $escapeStyle;

		return $this;
	}

	/**
	 * @param string $fileName
	 *
	 * @return TabularWriter
	 */
	public function setFileName( $fileName )
	{
		$this->_fileName = $fileName;

		return $this;
	}

	/**
	 * @param array $keys
	 *
	 * @return TabularWriter
	 */
	public function setKeys( $keys )
	{
		$this->_keys = $keys;

		return $this;
	}

	/**
	 * @param boolean $lazyWrap
	 *
	 * @return TabularWriter
	 */
	public function setLazyWrap( $lazyWrap )
	{
		$this->_lazyWrap = $lazyWrap;

		return $this;
	}

	/**
	 * @param string $lineBreak
	 *
	 * @return TabularWriter
	 */
	public function setLineBreak( $lineBreak )
	{
		$this->_lineBreak = $lineBreak;

		return $this;
	}

	/**
	 * @param null|string $nullValue
	 *
	 * @return TabularWriter
	 */
	public function setNullValue( $nullValue )
	{
		$this->_nullValue = $nullValue;

		return $this;
	}

	/**
	 * @param string $separator
	 *
	 * @return TabularWriter
	 */
	public function setSeparator( $separator )
	{
		$this->_separator = $separator;

		return $this;
	}

	/**
	 * @param boolean $wrapWhitespace
	 *
	 * @return TabularWriter
	 */
	public function setWrapWhitespace( $wrapWhitespace )
	{
		$this->_wrapWhitespace = $wrapWhitespace;

		return $this;
	}

}
