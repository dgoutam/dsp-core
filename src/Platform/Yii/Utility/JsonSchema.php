<?php
/**
 * This file is part of the DreamFactory PHP Common Components Library
 *
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
namespace Platform\Yii\Utility;

use Kisma\Core\Seed;
use Kisma\Core\SeedUtility;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Scalar;
use DreamFactory\Common\Utility\DataFormat;

/**
 * JsonSchema
 * Dumps a schema from a db connection to JSON
 */
class JsonSchema extends Seed
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param \CDbConnection $db
	 * @param string         $schemaName
	 * @param bool           $pretty
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public static function generate( $db, $schemaName = null, $pretty = false )
	{
		if ( empty( $db ) || !( $db instanceof \CDbConnection ) )
		{
			throw new \InvalidArgumentException( 'The "$db" property must be an instance of \\CDbConnection.' );
		}

		$_tree = array(
			'connection_string' => $db->connectionString,
			'server_version'    => $db->getPdoInstance()->getAttribute( \PDO::ATTR_SERVER_VERSION ),
			'server_info'       => $db->getPdoInstance()->getAttribute( \PDO::ATTR_SERVER_INFO ),
			'tables'            => static::_buildTree( $db, $schemaName ),
		);

		return DataFormat::jsonEncode( $_tree, $pretty );
	}

	/**
	 * @param \CDbConnection $db
	 * @param string         $schema
	 *
	 * @return array
	 */
	protected static function _buildTree( $db, $schema = null )
	{
		$_tables = array();

		/** @var \CDbTableSchema $_schema */
		foreach ( $db->getSchema()->getTables( $schema ? : '' ) as $_schema )
		{
			$_columns = null;

			/** @var $_column \CDbColumnSchema */
			foreach ( $_schema->columns as $_column )
			{
				$_row = array(
					'name'           => $_column->name,
					'raw_name'       => $_column->rawName,
					'db_type'        => $_column->dbType,
					'size'           => $_column->size,
					'scale'          => $_column->scale,
					'precision'      => $_column->precision,
					'default_value'  => $_column->defaultValue,
					'allow_null'     => $_column->allowNull,
					'auto_increment' => $_column->autoIncrement,
					'primary_key'    => $_column->isPrimaryKey,
					'foreign_key'    => $_column->isForeignKey,
					//	PHP type
					'type'           => $_column->type,
					'comment'        => $_column->comment,
				);

				$_columns[$_column->name] = $_row;

				unset( $_row, $_column );
			}

			$_table = array(
				'name'          => $_schema->name,
				'raw_name'      => $_schema->rawName,
				'schema'        => $_schema->schemaName,
				'primary_key'   => $_schema->primaryKey,
				'foreign_keys'  => $_schema->foreignKeys,
				'columns'       => $_columns,
				'sequence_name' => $_schema->sequenceName,
			);

			$_tables[$_schema->name] = $_table;

			unset( $_columns, $_table, $_schema );
		}

		return $_tables;
	}
}
