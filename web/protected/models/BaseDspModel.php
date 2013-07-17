<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
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
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Sql;
use Platform\Resources\UserSession;

/**
 * BaseDspModel.php
 *
 * Defines two "built-in" behaviors: DataFormat and TimeStamp
 *  - DataFormat automatically formats date/time values for the target database platform (MySQL, Oracle, etc.)
 *  - TimeStamp automatically updates create_date and lmod_date columns in tables upon save.
 *
 * @property int    $id
 * @property string $created_date
 * @property string $last_modified_date
 */
class BaseDspModel extends \CActiveRecord
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const ALL_ATTRIBUTES = '*';

	//*******************************************************************************
	//* Members
	//*******************************************************************************

	/**
	 * @var array Our schema, cached for speed
	 */
	protected $_schema;
	/**
	 * @var string The name of the model class
	 */
	protected $_modelClass = null;
	/**
	 * @var \CDbTransaction The current transaction
	 */
	protected $_transaction = null;
	/**
	 * @var bool If true,save() and delete() will throw an exception on failure
	 */
	protected $_throwOnError = true;

	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * Init
	 */
	public function init()
	{
		$this->_modelClass = get_class( $this );
		parent::init();
	}

	/**
	 * Returns this model's schema
	 *
	 * @return array()
	 */
	public function getSchema()
	{
		return $this->_schema ? $this->_schema : $this->_schema = $this->getMetaData()->columns;
	}

	/**
	 * Returns an array of all attribute labels.
	 *
	 * @param array $additionalLabels
	 *
	 * @return array
	 */
	public function attributeLabels( $additionalLabels = array() )
	{
		static $_cache;

		if ( null !== $_cache )
		{
			return $_cache;
		}

		//	Merge all the labels together
		return $_cache = array_merge(
			parent::attributeLabels(),
			//	Mine
			array(
				 'id'                 => 'ID',
				 'create_date'        => 'Created Date',
				 'created_date'       => 'Created Date',
				 'last_modified_date' => 'Last Modified Date',
				 'lmod_date'          => 'Last Modified Date',
			),
			//	Subclass
			$additionalLabels
		);
	}

	/**
	 * @param string $attribute
	 *
	 * @return array
	 */
	public function attributeLabel( $attribute )
	{
		return Option::get( $this->attributeLabels(), $attribute );
	}

	/**
	 * PHP sleep magic method.
	 * Take opportunity to flush schema cache...
	 *
	 * @return array
	 */
	public function __sleep()
	{
		//	Clean up and phone home...
		$this->_schema = null;

		return parent::__sleep();
	}

	/**
	 * Override of CModel::setAttributes
	 * Populates member variables as well.
	 *
	 * @param array $attributes
	 * @param bool  $safeOnly
	 *
	 * @return void
	 */
	public function setAttributes( $attributes, $safeOnly = true )
	{
		if ( !is_array( $attributes ) )
		{
			return;
		}

		$_attributes = array_flip( $safeOnly ? $this->getSafeAttributeNames() : $this->attributeNames() );

		foreach ( $attributes as $_column => $_value )
		{
			if ( isset( $_attributes[$_column] ) )
			{
				$this->setAttribute( $_column, $_value );
			}
			else
			{
				if ( $this->canSetProperty( $_column ) )
				{
					$this->{$_column} = $_value;
				}
			}
		}
	}

	/**
	 * Sets our default behaviors
	 *
	 * @return array
	 */
	public function behaviors()
	{
		return array_merge(
			parent::behaviors(),
			array(
				 //	Data formatter
				 'base_platform_model.data_format_behavior' => array(
					 'class' => 'Platform\\Yii\\Behaviors\\DataFormatBehavior',
				 ),
				 //	Timestamper
				 'base_platform_model.timestamp_behavior'   => array(
					 'class'                => 'Platform\\Yii\\Behaviors\\TimestampBehavior',
					 'currentUserId'        => function ( $inquirer )
					 {
						 return UserSession::getCurrentUserId( $inquirer );
					 },
					 'createdColumn'        => array( 'create_date', 'created_date' ),
					 'createdByColumn'      => array( 'create_user_id', 'created_by_id' ),
					 'lastModifiedColumn'   => array( 'lmod_date', 'last_modified_date' ),
					 'lastModifiedByColumn' => array( 'lmod_user_id', 'last_modified_by_id' ),
				 ),
			)
		);
	}

	/**
	 * Returns the errors on this model in a single string suitable for logging.
	 *
	 * @param string $attribute Attribute name. Use null to retrieve errors for all attributes.
	 *
	 * @return string
	 */
	public function getErrorsForLogging( $attribute = null )
	{
		$_result = null;
		$_i = 1;

		$_errors = $this->getErrors( $attribute );

		if ( !empty( $_errors ) )
		{
			foreach ( $_errors as $_attribute => $_error )
			{
				$_result .= $_i++ . '. [' . $_attribute . '] : ' . implode( '|', $_error ) . PHP_EOL;
			}
		}

		return $_result;
	}

	/**
	 * A mo-betta CActiveRecord update method. Pass in array( column => value, ... ) to update.
	 *
	 * Simply, this method updates each attribute with the passed value, then calls parent::update();
	 *
	 * NB: validation is not performed in this method. You may call {@link validate} to perform the validation.
	 *
	 * @param array $attributes list of attributes and values that need to be saved. Defaults to null, meaning do a full update.
	 *
	 * @return bool whether the update is successful
	 * @throws \CException if the record is new
	 */
	public function update( $attributes = null )
	{
		if ( empty( $attributes ) )
		{
			return parent::update();
		}

		$_columns = array();

		foreach ( $attributes as $_column => $_value )
		{
			//	column => value specified
			if ( !is_numeric( $_column ) )
			{
				$this->{$_column} = $_value;
			}
			else
			{
				//	n => column specified
				$_column = $_value;
			}

			$_columns[] = $_column;
		}

		return parent::update( $_columns );
	}

	/**
	 * Forces an exception on failed delete
	 *
	 * @throws \CDbException
	 * @return bool
	 */
	public function delete()
	{
		if ( !parent::delete() )
		{
			if ( $this->_throwOnError )
			{
				throw new \CDbException( $this->getErrorsForLogging() );
			}

			return false;
		}

		return true;
	}

	/**
	 * Optionally force an exception on failed save
	 *
	 * @param bool  $runValidation
	 * @param array $attributes
	 *
	 * @throws \CDbException
	 * @return bool
	 */
	public function save( $runValidation = true, $attributes = null )
	{
		if ( !parent::save( $runValidation, $attributes ) )
		{
			if ( $this->_throwOnError )
			{
				throw new \CDbException( $this->getErrorsForLogging() );
			}

			return false;
		}

		return true;
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @param \CDbCriteria $criteria
	 *
	 * @return bool the data provider that can return the models based on the search/filter conditions.
	 */
	public function search( $criteria = null )
	{
		$_criteria = $criteria ? : new \CDbCriteria;

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'created_date', $this->created_date, true );
		$_criteria->compare( 'last_modified_date', $this->last_modified_date, true );

		return new \CActiveDataProvider(
			$this,
			array(
				 'criteria' => $_criteria,
			)
		);
	}

	/**
	 * @param string $modelClass
	 *
	 * @return BaseDspModel
	 */
	public function setModelClass( $modelClass )
	{
		$this->_modelClass = $modelClass;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getModelClass()
	{
		return $this->_modelClass;
	}

	/**
	 * @param boolean $throwOnError
	 *
	 * @return BaseDspModel
	 */
	public function setThrowOnError( $throwOnError )
	{
		$this->_throwOnError = $throwOnError;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getThrowOnError()
	{
		return $this->_throwOnError;
	}

	//*******************************************************************************
	//* Transaction Management
	//*******************************************************************************

	/**
	 * Checks to see if there are any transactions going...
	 *
	 * @return boolean
	 */
	public function hasTransaction()
	{
		return ( null !== $this->_transaction );
	}

	/**
	 * Begins a database transaction
	 *
	 * @throws \CDbException
	 * @return \CDbTransaction
	 */
	public function transaction()
	{
		if ( $this->hasTransaction() )
		{
			throw new \CDbException( 'Cannot start new transaction while one is in progress.' );
		}

		return $this->_transaction = static::model()->getDbConnection()->beginTransaction();
	}

	/**
	 * Commits the transaction at the top of the stack, if any.
	 *
	 * @throws \CDbException
	 */
	public function commit()
	{
		if ( $this->hasTransaction() )
		{
			$this->_transaction->commit();
		}
	}

	/**
	 * Rolls back the current transaction, if any...
	 *
	 * @throws \CDbException
	 */
	public function rollback( Exception $exception = null )
	{
		if ( $this->hasTransaction() )
		{
			$this->_transaction->rollback();
		}

		//	Throw it if given
		if ( null !== $exception )
		{
			throw $exception;
		}
	}

	//*******************************************************************************
	//* REST Methods
	//*******************************************************************************

	/**
	 * A mapping of attributes to REST attributes
	 *
	 * @return array
	 */
	public function restMap()
	{
		return array();
	}

	/**
	 * If a model has a REST mapping, attributes are mapped an returned in an array.
	 *
	 * @param array $filter Only columns in $filter will be returned. All columns if empty( $filter )
	 *
	 * @return array|null The resulting view
	 */
	public function getRestAttributes( $filter = array() )
	{
		$_map = $this->restMap();

		if ( empty( $_map ) )
		{
			return null;
		}

		$_results = array();
		$_columns = $this->getSchema();

		foreach ( $this->restMap() as $_key => $_value )
		{
			//	Apply the filter
			if ( !empty( $filter ) && !in_array( $_key, array_values( $filter ) ) )
			{
				continue;
			}

			$_attributeValue = $this->getAttribute( $_key );

			//	Apply formats
			switch ( $_columns[$_key]->dbType )
			{
				case 'date':
				case 'datetime':
				case 'timestamp':
					//	Handle blanks
					if ( null !== $_attributeValue && $_attributeValue != '0000-00-00' && $_attributeValue != '0000-00-00 00:00:00' )
					{
						$_attributeValue = date( 'c', strtotime( $_attributeValue ) );
					}
					break;
			}

			$_results[$_value] = $_attributeValue;
		}

		return $_results;
	}

	/**
	 * Sets the values in the model based on REST attribute names
	 *
	 * @param array $attributeList
	 *
	 * @return BaseDspModel
	 */
	public function setRestAttributes( array $attributeList = array() )
	{
		$_map = $this->restMap();

		if ( !empty( $_map ) )
		{
			foreach ( $attributeList as $_key => $_value )
			{
				if ( false !== ( $_mapKey = array_search( $_key, $_map ) ) )
				{
					$this->setAttribute( $_mapKey, $_value );
				}
			}
		}

		return $this;
	}

	/**
	 * Maps a set of REST columns to actual columns
	 *
	 * @param array $restColumns
	 *
	 * @return array
	 */
	public function mapRestColumns( $restColumns )
	{
		$_restMap = \Registry::model()->restMap();

		if ( empty( $restColumns ) )
		{
			$restColumns = array_values( $_restMap );
		}

		//	Translate the columns...
		$_new = array();
		$_map = array_flip( $_restMap );

		foreach ( $restColumns as $_column )
		{
			$_new[] = Option::get( $_map, $_column );
		}

		return $_new;
	}

	//*************************************************************************
	//* Static Helper Methods
	//*************************************************************************

	/**
	 * Executes the SQL statement and returns all rows. (static version)
	 *
	 * @param mixed   $_criteria         The criteria for the query
	 * @param boolean $fetchAssociative  Whether each row should be returned as an associated array with column names as the keys or the array keys are column indexes (0-based).
	 * @param array   $parameters        input parameters (name=>value) for the SQL execution. This is an alternative to {@link bindParam} and {@link bindValue}. If you have multiple input parameters, passing them in this way can improve the performance. Note that you pass parameters in this way, you cannot bind parameters or values using {@link bindParam} or {@link bindValue}, and vice versa. binding methods and  the input parameters this way can improve the performance. This parameter has been available since version 1.0.10.
	 *
	 * @return array All rows of the query result. Each array element is an array representing a row. An empty array is returned if the query results in nothing.
	 * @throws \CException execution failed
	 * @static
	 */
	public static function queryAll( $_criteria, $fetchAssociative = true, $parameters = array() )
	{
		if ( null !== ( $_builder = static::getDb()->getCommandBuilder() ) )
		{
			if ( null !== ( $_command = $_builder->createFindCommand( static::model()->getTableSchema(), $_criteria ) ) )
			{
				return $_command->queryAll( $fetchAssociative, $parameters );
			}
		}

		return null;
	}

	/**
	 * Convenience method to execute a query (static version)
	 *
	 * @param string $sql
	 * @param array  $parameters
	 *
	 * @return int The number of rows affected by the operation
	 */
	public static function execute( $sql, $parameters = array() )
	{
		return Sql::execute( $sql, $parameters, static::model()->getDbConnection()->getPdoInstance() );
	}

	/**
	 * Convenience method to execute a scalar query (static version)
	 *
	 * @param string $sql
	 * @param array  $parameters
	 *
	 * @param int    $columnNumber
	 *
	 * @return int|string|null The result or null if nada
	 */
	public static function scalar( $sql, $parameters = array(), $columnNumber = 0 )
	{
		return Sql::scalar( $sql, $columnNumber, $parameters, static::model()->getDbConnection()->getPdoInstance() );
	}

	/**
	 * Convenience method to get a database connection to a model's database
	 *
	 * @return \CDbConnection
	 */
	public static function getDb()
	{
		return static::model()->getDbConnection();
	}

	/**
	 * Convenience method to get a database command model's database
	 *
	 * @param string $sql
	 *
	 * @return \CDbCommand
	 */
	public static function createCommand( $sql )
	{
		return static::getDb()->createCommand( $sql );
	}
}