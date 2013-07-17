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
use Platform\Enums\ServiceTypes;
use Platform\Enums\StorageTypes;

/**
 * services.config.php
 * This file contains the master service mapping for the DSP
 */
return
	array(
		ServiceTypes::LOCAL_FILE_STORAGE   => array(
			'class' => 'Platform\\Services\\LocalFileSvc',
		),
		ServiceTypes::REMOTE_FILE_STORAGE  => array(
			'class' => array(
				StorageTypes::AZURE_BLOB               => array(
					'class' => 'Platform\\Services\\WindowsAzureBlobSvc',
				),
				StorageTypes::AWS_S3                   => array(
					'class' => 'Platform\\Services\\AwsS3Svc',
				),
				StorageTypes::OPENSTACK_OBJECT_STORAGE => array(
					'class' => 'Platform\\Services\\OpenStackObjectStoreSvc',
				),
				StorageTypes::RACKSPACE_CLOUDFILES     => array(
					'class' => 'Platform\\Services\\OpenStackObjectStoreSvc',
				),
			),
		),
		ServiceTypes::LOCAL_SQL_DB         => array(
			'class' => 'Platform\\Services\\SqlDbSvc',
			'local' => true,
		),
		ServiceTypes::REMOTE_SQL_DB        => array(
			'class' => 'Platform\\Services\\SqlDbSvc',
			'local' => false,
		),
		ServiceTypes::LOCAL_SQL_DB_SCHEMA  => array(
			'class' => 'Platform\\Services\\SchemaSvc',
			'local' => true,
		),
		ServiceTypes::REMOTE_SQL_DB_SCHEMA => array(
			'class' => 'Platform\\Services\\SchemaSvc',
			'local' => false,
		),
		ServiceTypes::LOCAL_EMAIL_SERVICE  => array(
			'class' => 'Platform\\Services\\EmailSvc',
			'local' => true,
		),
		ServiceTypes::REMOTE_EMAIL_SERVICE => array(
			'class' => 'Platform\\Services\\EmailSvc',
			'local' => false,
		),
		ServiceTypes::NOSQL_DB             => array(
			'class' => array(
				StorageTypes::AZURE_TABLES => array(
					'class' => 'Platform\\Services\\WindowsAzureTablesSvc',
				),
				StorageTypes::AWS_DYNAMODB => array(
					'class' => 'Platform\\Services\\AwsDynamoDbSvc',
				),
				StorageTypes::AWS_SIMPLEDB => array(
					'class' => 'Platform\\Services\\AwsSimpleDbSvc',
				),
				StorageTypes::MONGODB      => array(
					'class' => 'Platform\\Services\\MongoDbSvc',
				),
				StorageTypes::COUCHDB      => array(
					'class' => 'Platform\\Services\\CouchDbSvc',
				),
			),
		),
		ServiceTypes::SERVICE_REGISTRY     => array(
			'class' => 'Platform\\Services\\ServiceRegistry',
		),
		ServiceTypes::REMOTE_OAUTH_SERVICE => array(
			'class' => 'Platform\\Services\\OAuthService',
		),
		ServiceTypes::REMOTE_WEB_SERVICE   => array(
			'class' => 'Platform\\Services\\RemoteWebSvc',
		),
	);
