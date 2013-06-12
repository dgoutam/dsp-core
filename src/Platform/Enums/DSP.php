<?php
namespace Platform\Enums;

use Kisma\Core\Enums\SeedEnum;

/**
 * DSP
 * DSP constants
 *
 * @copyright Copyright (c) 2013 DreamFactory Software, Inc.
 * @link      DreamFactory Software, Inc. <http://www.dreamfactory.com>
 * @author    Jerry Ablan <jerryablan@dreamfactory.com>
 */
class DSP extends SeedEnum
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const FABRIC_BASE_STORAGE_PATH = '/data/storage/%%STORAGE_KEY%%';
	/**
	 * @var string
	 */
	const FABRIC_INSTANCE_STORAGE_PATH = '/data/storage/%%STORAGE_KEY%%';
	/**
	 * @var string
	 */
	const FABRIC_INSTANCE_SNAPSHOT_PATH = '/data/storage/%%STORAGE_KEY%%/.private/snapshots';
	/**
	 * @var string
	 */
	const FABRIC_INSTANCE_BLOB_PATH = '/data/storage/%%STORAGE_KEY%%/blob';
	/**
	 * @var string
	 */
	const FABRIC_INSTANCE_PRIVATE_PATH = '/data/storage/%%STORAGE_KEY%%/.private';
	/**
	 * @var string
	 */
	const FABRIC_SNAPSHOT_GLOB = '/snapshot.*.tar.gz';
	/**
	 * @var string Where LaunchPad lives when not being hosted
	 */
	const LOCAL_BASE_PATH = '/var/www/launchpad/current';
	/**
	 * @var string Where LaunchPad lives when it is being hosted
	 */
	const HOSTED_BASE_PATH = '/var/www/launchpad';
	/**
	 * @var string
	 */
	const LOCAL_BASE_STORAGE_PATH = '/var/www/launchpad/current/storage';
	/**
	 * @var string
	 */
	const DEFAULT_DSP_DOMAIN = '.dreamfactory.com';
	/**
	 * @var string
	 */
	const DEFAULT_DSP_ZONE = 'cloud';
	/**
	 * @var string
	 */
	const DEFAULT_DSP_SUB_DOMAIN = '.cloud.dreamfactory.com';
	/**
	 * @var string
	 */
	const DEFAULT_DSP_SERVER = 'cumulus.fabric.dreamfactory.com';
	/**
	 * @var string
	 */
	const HOSTED_BLOB_PATH = '/blob';
	/**
	 * @var string
	 */
	const HOSTED_PRIVATE_PATH = '/.private';
	/**
	 * @var string
	 */
	const HOSTED_SNAPSHOT_PATH = '/snapshots';
	/**
	 * @var string
	 */
	const HOSTED_PRIVATE_PATH_PERMISSIONS = 0775;
}
