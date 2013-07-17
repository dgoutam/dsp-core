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
/**
 * CActiveRecord implementation that allows specifying
 * DB table name instead of creating a class for each table.
 *
 * Usage (assuming table 'user' with columns 'id' and 'name'):
 *
 *     $userModel = DynamicActiveRecord::forTable('user');
 *     //list existing users
 *     foreach ($userModel->findAll() as $user)
 *         echo $user->id . ': ' . $user->name . '<br>';
 *     //add new user
 *     $userModel->name = 'Pavle Predic';
 *     $userModel->save();
 *
 * @author Pavle Predic <https://github.com/pavlepredic>
 */
class DynamicActiveRecord extends BaseDspModel
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string Name of the DB table
	 */
	protected $_tableName;

	/**
	 * @var CActiveRecordMetaData Table meta-data. Must re-declare, as parent::_md is private
	 */
	protected $_md;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Constructor
	 *
	 * @param string $scenario (defaults to 'insert')
	 * @param string $tableName
	 */
	public function __construct( $scenario = 'insert', $tableName = null )
	{
		$this->_tableName = $tableName;
		parent::__construct( $scenario );
	}

	/**
	 * Overrides default instantiation logic.
	 * Instantiates AR class by providing table name
	 *
	 * @see CActiveRecord::instantiate()
	 *
	 * @param array $attributes
	 *
	 * @return DynamicActiveRecord
	 */
	protected function instantiate( $attributes )
	{
		return new self( null, $this->tableName() );
	}

	/**
	 * Returns meta-data for this DB table
	 *
	 * @see CActiveRecord::getMetaData()
	 * @return CActiveRecordMetaData
	 */
	public function getMetaData()
	{
		if ( $this->_md !== null )
		{
			return $this->_md;
		}

		return $this->_md = new CActiveRecordMetaData( $this );
	}

	/**
	 * Returns table name
	 *
	 * @see CActiveRecord::tableName()
	 * @return string
	 */
	public function tableName()
	{
		if ( !$this->_tableName )
		{
			$this->_tableName = parent::tableName();
		}

		return $this->_tableName;
	}

	/**
	 * Returns an instance of DynamicActiveRecord for the provided DB table.
	 * This is a helper method that may be used instead of constructor.
	 *
	 * @param string $tableName
	 * @param string $scenario
	 *
	 * @return DynamicActiveRecord
	 */
	public static function forTable( $tableName, $scenario = 'insert' )
	{
		return new DynamicActiveRecord( $scenario, $tableName );
	}

	/**
	 * @param array $attributes
	 * @param bool  $callAfterFind
	 *
	 * @return CActiveRecord|null
	 */
	public function populateRecord( $attributes, $callAfterFind = true )
	{
		if ( $this->useTypeCasting() and is_array( $attributes ) )
		{
			foreach ( $attributes as $name => &$value )
			{
				if ( $this->hasAttribute( $name ) and $value !== null )
				{
					settype( $value, $this->getMetaData()->columns[$name]->type );
				}
			}
		}

		return parent::populateRecord( $attributes, $callAfterFind );
	}

	/**
	 * @return bool
	 */
	public function useTypeCasting()
	{
		return false;
	}
}