<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Kisma\Core\Utility\Log;

/**
 * LiveLogRoute.php
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
