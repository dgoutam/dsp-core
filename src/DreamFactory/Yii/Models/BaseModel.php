<?php
namespace DreamFactory\Yii\Models;

use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Log;
use DreamFactory\Yii\Utility\Pii;

/**
 * BaseModel
 *
 * The base class for all models. Defines two "built-in" behaviors: DataFormat and TimeStamp
 *  - DataFormat automatically formats date/time values for the target database platform (MySQL, Oracle, etc.)
 *  - TimeStamp automatically updates create_date and lmod_date columns in tables upon save.
 *
 * @property int    $id
 * @property string $create_date
 * @property string $lmod_date
 */
class BaseModel extends \CActiveRecord
{
	//*******************************************************************************
	//* Members
	//*******************************************************************************

	/**
	 * @var array Our schema, cached for speed
	 */
	protected $_schema;
	/**
	 * @var array Attribute labels cache
	 */
	protected $_attributeLabels = array();
	/**
	 * @var string The name of the model class
	 */
	protected $_modelClass = null;
	/**
	 * @var \CDbTransaction The current transaction
	 */
	protected $_transaction = null;
	/**
	 * @var bool If true, CDbExceptions will be thrown on save() or update() fail
	 */
	protected $_throwExceptionsForSaveUpdate = true;

	//********************************************************************************
	//* Methods
	//********************************************************************************

	/**
	 * Init
	 */
	public function init()
	{
		parent::init();
		$this->_modelClass = get_class( $this );
	}

	/**
	 * Returns this model's schema
	 *
	 * @return array()
	 */
	public function getSchema()
	{
		return $this->_schema ? : $this->_schema = $this->getMetaData()->columns;
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
				 'base_model.data_format_behavior' => array(
					 'class' => 'DreamFactory\\Yii\\Behaviors\\DataFormatBehavior',
				 ),
				 //	Timestamper
				 'base_model.timestamp_behavior'   => array(
					 'class'              => 'DreamFactory\\Yii\\Behaviors\\TimestampBehavior',
					 'createdColumn'      => 'create_date',
					 'lastModifiedColumn' => 'lmod_date',
				 ),
			)
		);
	}

	/**
	 * Returns the errors on this model in a single string suitable for logging.
	 *
	 * @param string $attribute Attribute name. Use null to retrieve errors for all attributes.
	 * @param string $pattern   Pass NULL to get errors as an array
	 *
	 * @return string|array
	 */
	public function getErrorsForLogging( $attribute = null, $pattern = '%%#%% "%%column%%": %%error_message%%' )
	{
		$_result = null;
		$_i = 1;

		$_errors = $this->getErrors( $attribute );

		if ( !empty( $_errors ) )
		{
			if ( empty( $pattern ) )
			{
				return $_errors;
			}

			foreach ( $_errors as $_attribute => $_error )
			{
				$_format = str_ireplace(
					array(
						 '%%#%%',
						 '%%column%%',
						 '%%error_message%%',
					),
					array(
						 $_i++,
						 $_attribute,
						 $_error
					),
					$pattern
				);

				$_result .= $_format;
				//$_i++ . '. [' . $_attribute . '] : ' . implode( '|', $_error );
			}
		}

		return $_result;
	}

	/**
	 * Forces an exception on failed save
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
			if ( false === $this->_throwExceptionsForSaveUpdate )
			{
				return false;
			}

			throw new \CDbException( $this->getErrorsForLogging() );
		}

		return true;
	}

	/**
	 * A mo-betta CActiveRecord update method. Pass in column => value to update.
	 * NB: validation is not performed in this method. You may call {@link validate} to perform the validation.
	 *
	 * @param array $attributes list of attributes and values that need to be saved. Defaults to null, meaning all attributes that are loaded from DB will be saved.
	 *
	 * @throws \CDbException
	 * @return bool whether the update is successful
	 */
	public function update( $attributes = null )
	{
		$_columns = array();

		if ( null === $attributes )
		{
			return parent::update( $attributes );
		}

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

		$_result = parent::update( $_columns );

		if ( empty( $_result ) )
		{
			if ( false === $this->_throwExceptionsForSaveUpdate )
			{
				return $_result;
			}

			throw new \CDbException( $this->getErrorsForLogging() );
		}

		return $_result;
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * @param bool $returnCriteria
	 *
	 * @return bool the data provider that can return the models based on the search/filter conditions.
	 */
	public function search( $returnCriteria = false )
	{
		$_criteria = new \CDbCriteria;

		$_criteria->compare( 'id', $this->id );
		$_criteria->compare( 'create_date', $this->create_date, true );
		$_criteria->compare( 'lmod_date', $this->lmod_date, true );

		if ( false !== $returnCriteria )
		{
			return $_criteria;
		}

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
	 * @return BaseModel
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
		return !empty( $this->_transaction );
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
	 * @param \Exception $exception
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function rollback( \Exception $exception = null )
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

		return false;
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		static $_cache;

		if ( null !== $_cache )
		{
			return $_cache;
		}

		return $_cache = array_merge(
			parent::attributeLabels(),
			array(
				 'id'          => 'ID',
				 'create_date' => 'Create Date',
				 'lmod_date'   => 'Last Modified Date',
			)
		);
	}

	/**
	 * @param string $attribute
	 *
	 * @return mixed
	 */
	public function attributeLabel( $attribute )
	{
		return Option::get( $this->attributeLabels(), $attribute );
	}

	/**
	 * @return array
	 */
	public function getAttributeLabels()
	{
		return $this->attributeLabels();
	}

	/**
	 * @param array $attributeLabels
	 *
	 * @return BaseModel
	 */
	public function setAttributeLabels( $attributeLabels )
	{
		$this->_attributeLabels = $attributeLabels;

		return $this;
	}

	/**
	 * @param boolean $throwExceptionsForSaveUpdate
	 *
	 * @return BaseModel
	 */
	public function setThrowExceptionsForSaveUpdate( $throwExceptionsForSaveUpdate )
	{
		$this->_throwExceptionsForSaveUpdate = $throwExceptionsForSaveUpdate;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getThrowExceptionsForSaveUpdate()
	{
		return $this->_throwExceptionsForSaveUpdate;
	}

	/**
	 * @return \CDbTransaction
	 */
	public function getTransaction()
	{
		return $this->transaction();
	}

	//*************************************************************************
	//* Static Helper Methods
	//*************************************************************************

	/**
	 * Executes the SQL statement and returns all rows. (static version)
	 *
	 * @param mixed   $criteria         The criteria for the query
	 * @param boolean $fetchAssociative Whether each row should be returned as an associated array with column names as the keys or the array keys are column indexes (0-based).
	 * @param array   $parameters       input parameters (name=>value) for the SQL execution. This is an alternative to {@link bindParam} and {@link bindValue}. If you have multiple input parameters, passing them in this way can improve the performance. Note that you pass parameters in this way, you cannot bind parameters or values using {@link bindParam} or {@link bindValue}, and vice versa. binding methods and  the input parameters this way can improve the performance. This parameter has been available since version 1.0.10.
	 *
	 * @return array All rows of the query result. Each array element is an array representing a row. An empty array is returned if the query results in nothing.
	 * @throws \CException execution failed
	 * @static
	 */
	public static function queryAll( $criteria, $fetchAssociative = true, $parameters = array() )
	{
		if ( null !== ( $_builder = static::getDb()->getCommandBuilder() ) )
		{
			if ( null !== ( $_command = $_builder->createFindCommand( static::model()->getTableSchema(), $criteria ) ) )
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
		return static::createCommand( $sql )->execute( $parameters );
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