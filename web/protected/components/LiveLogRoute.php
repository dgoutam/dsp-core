<?php
use Kisma\Core\Utility\Log;

/**
 * LiveLogRoute.php
 *
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 * Copyright (c) 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright (c) 2012-2013 by DreamFactory Software, Inc. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class LiveLogRoute extends \CFileLogRoute
{
	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @property array An array of categories to exclude from logging. Regex pattern matching is supported via {@link preg_match}
	 */
	protected $_excludedCategories = array();

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Retrieves filtered log messages from logger for further processing.
	 *
	 * @param \CLogger $logger      logger instance
	 * @param boolean  $processLogs whether to process the logs after they are collected from the logger. ALWAYS TRUE NOW!
	 */
	public function collectLogs( $logger, $processLogs = false )
	{
		$processLogs = true;
		parent::collectLogs( $logger, $processLogs );
	}

	//********************************************************************************
	//* Private Methods
	//********************************************************************************

	/**
	 * Writes log messages in files.
	 *
	 * @param array $logs list of log messages
	 */
	protected function processLogs( $logs )
	{
		try
		{
			Log::setDefaultLog( $_logFile = $this->getLogPath() . DIRECTORY_SEPARATOR . $this->getLogFile() );

			if ( @filesize( $_logFile ) > $this->getMaxFileSize() * 1024 )
			{
				$this->rotateFiles();
			}

			if ( !is_array( $logs ) )
			{
				return;
			}

			//	Write out the log entries
			foreach ( $logs as $_log )
			{
				$_exclude = false;

				//	Check out the exclusions
				if ( !empty( $this->_excludedCategories ) )
				{
					foreach ( $this->_excludedCategories as $_category )
					{
						//	If found, we skip
						if ( trim( strtolower( $_category ) ) == trim( strtolower( $_log[2] ) ) )
						{
							$_exclude = true;
							break;
						}

						//	Check for regex
						if ( '/' == $_category[0] && 0 != @preg_match( $_category, $_log[2] ) )
						{
							$_exclude = true;
							break;
						}
					}
				}

				/**
				 *     Use {@link error_log} facility to write out log entry
				 */
				if ( !$_exclude )
				{
					/**
					 * 0 = $message
					 * 1 = $level
					 * 2 = $category
					 * 3 = $timestamp
					 */
					list( $_message, $_level, $_category, $_timestamp ) = $_log;

					Log::log( $_message, $_level, null, null, $_category );
//					error_log( $this->formatLogMessage( $_log[0], $_log[1], $_log[2], $_log[3] ), 3, $_logFile );
				}
			}

			//	Processed, clear!
			$this->logs = null;
		}
		catch ( \Exception $_ex )
		{
			error_log( __METHOD__ . ': Exception processing application logs: ' . $_ex->getMessage() );
		}
	}

	/**
	 * Formats a log message given different fields.
	 *
	 * @param string     $message  message content
	 * @param int|string $level    message level
	 * @param string     $category message category
	 * @param float      $timestamp
	 *
	 * @return string formatted message
	 */
	protected function formatLogMessage( $message, $level = 'info', $category = null, $timestamp = null )
	{
		return @date( 'M j H:i:s', $timestamp ? : time() ) . ' [' . strtoupper( substr( $level, 0, 4 ) ) . '] ' . $message . ' {"category":"' .
				$category . '"}' . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
	}

	//*************************************************************************
	//* [GS]etters
	//*************************************************************************

	/**
	 * @param $excludedCategories
	 *
	 * @return CPSLiveLogRoute
	 */
	public function setExcludedCategories( $excludedCategories )
	{
		$this->_excludedCategories = $excludedCategories;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExcludedCategories()
	{
		return $this->_excludedCategories;
	}
}
