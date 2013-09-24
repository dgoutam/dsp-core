#!/bin/bash
# DSP install/update utility
# Copyright (C) 2012-2013 DreamFactory Software, Inc. All Rights Reserved
#
# This file is part of the DreamFactory Services Platform(tm) (DSP)
# DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
# Copyright 2012-2013 DreamFactory Software, Inc. <developer-support@dreamfactory.com>
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
#
# CHANGELOG:
#
# v1.2.7
#	Remove composer cache on clean install
#
# v1.2.6
#	Changed default perms on scripts/*.sh to 0775 from 0755 for web update
#
# v1.2.5
#	No longer stopping apache if INSTALL_USER == WEB_USER
#   Found issues surrounding current working directory when run from Apache
#
# v1.2.4
#   Moved composer.phar install directory to project root
#	Reordered the composer checks to happen after the option parsing
#
# v1.2.3.1
#   Removed exit on failed removal of shared directory
#
# v1.2.3
#   chmod 777 on shared and vendor directories so web installer can clean
#
# v1.2.2
#   Changed location of composer.phar
#	Added new argument --debug for extra verbosity
#
# v1.2.1
#   Fixed broken path link
#
# v1.2.0
#   Removed references to $HOME
#   Symlinks to shared apps made relative
#
# v1.1.6
#   Removed separate lib directory for Azure
#
# v1.1.5
#   Restored pull of submodules
#
# v1.1.4
#   chmod 777 scripts for Macs
#
# v1.1.3
#	Silence irrelevant errors on chown/chmod
#
# v1.1.2
#	Make note if composer is already installed, and if so, not remove it after run or on clean
#
# v1.1.1
# 	Added --clean flag for a clean install
#
# v1.1.0
#	Added -v verbose mode option
#	Removed lingering git junk
#
# v1.0.8
#   Installation location aware now
#  	Shared directory aware as well
#
# v1.0.7
#   Added auto-checking of OS type and set web user accordingly
#   Streamlined status output
#   Hopefully! fixed submodule shit so it pulls head properly upon update
#	Added better support for checking if a user name was passed in as an argument
#

##
##	Initial settings
##

VERSION=1.2.7
SYSTEM_TYPE=`uname -s`
COMPOSER=composer.phar
PHP=/usr/bin/php
WEB_USER=www-data
BASE=`pwd`
FABRIC=0
FABRIC_MARKER=/var/www/.fabric_hosted
VERBOSE=
QUIET="--quiet"
WRITE_ACCESS=0777
SCRIPT_PERMS=0775
FILE_PERMS=0664
DIR_PERMS=2775

## Who am I?
INSTALL_USER=${USER}
if [ "x" = "${INSTALL_USER}x" ] ; then
	INSTALL_USER=`whoami`
fi

##	No term, no bold
if [ "x" = "${TERM}x" ] ; then
	B1=
	B2=
else
	B1=`tput bold`
	B2=`tput sgr0`
fi

TAG="Mode: ${B1}Local${B2}"

##
## Construct the various paths
##
BASE_PATH="`dirname "${0}" | xargs dirname`"

##	Get the REAL path of install
pushd "${BASE_PATH}" >/dev/null 2>&1
BASE_PATH=`pwd`
if [ "web" = `basename ${BASE_PATH}` ] ; then
	cd ..
	BASE_PATH=`pwd`
fi
popd >/dev/null 2>&1

LOG_DIR=${BASE_PATH}/log/
STORAGE_DIR=${BASE_PATH}/storage/
VENDOR_DIR=${BASE_PATH}/vendor
WEB_DIR=${BASE_PATH}/web
PUBLIC_DIR=${WEB_DIR}/public
ASSETS_DIR=${PUBLIC_DIR}/assets
APPS_DIR=${BASE_PATH}/apps
SHARE_DIR=${BASE_PATH}/shared
COMPOSER_DIR=${BASE_PATH}

# Hosted or standalone?
if [ -f "${FABRIC_MARKER}" ] ; then
	FABRIC=1
	TAG="Mode: ${B1}Fabric${B2}"
fi

echo "${B1}DreamFactory Services Platform(tm)${B2} ${SYSTEM_TYPE} Installer [${TAG} v${VERSION}]"

#	Execute getopt on the arguments passed to this program, identified by the special character $@
PARSED_OPTIONS=$(getopt -n "$0"  -o hvcD --long "help,verbose,clean,debug"  -- "$@")

#	Bad arguments, something has gone wrong with the getopt command.
if [ $? -ne 0 ] ; then
	exit 1
fi

#	A little magic, necessary when using getopt.
eval set -- "${PARSED_OPTIONS}"

while true ;  do
	case "$1" in
		-h|--help)
			echo "usage: $0 [-v|--verbose] [-c|--clean]"
			shift
	    	;;

		-v|--verbose)
			VERBOSE="-v"
			QUIET=
			echo "  * Verbose mode enabled"
			shift
			;;

		-D|--debug)
			VERBOSE="-vvv"
			QUIET=
			echo "  * Debug verbosity enabled"
			shift
			;;

		-c|--clean)
			rm -rf shared/ vendor/ .composer/ composer.lock >/dev/null
			if [ $? -ne 0 ] ; then
				echo "  * ${B1}WARNING{B2}: Cannot remove \"shared/\", \"vendor/\", and/or \"composer.lock\"."
				echo "  * ${B1}WARNING{B2}: Clean installation NOT guaranteed."
			else
				echo "  * Clean install. Dependencies removed."
			fi
			shift
			;;

		--)
			shift
			break;;
	esac
done

# Composer already installed?
if [ -f "${COMPOSER_DIR}/${COMPOSER}" ] ; then
	echo "  * Composer pre-installed: ${B1}${COMPOSER_DIR}/${COMPOSER}${B2}"
	echo "  * Checking for package manager updates"
	${PHP} ${COMPOSER_DIR}/${COMPOSER} ${QUIET} ${VERBOSE} --no-interaction self-update
else
	echo "  * No composer found, installing: ${B1}${COMPOSER_DIR}/${COMPOSER}${B2}"
	curl -s https://getcomposer.org/installer | ${PHP} -- --install-dir ${COMPOSER_DIR} ${QUIET} ${VERBOSE} --no-interaction
fi

echo "  * Install user is ${B1}\"${INSTALL_USER}\"${B2}"

#	Determine OS type
if [ "Darwin" = "${SYSTEM_TYPE}" ] ; then
	WEB_USER=_www
	echo "  * OS X installation: Apache user set to \"${B1}_www${B2}\""
elif [ "Linux" != "${SYSTEM_TYPE}" ] ; then
	echo "  * Windows/other installation. ${B1}Not fully tested so your mileage may vary${B2}."
fi

##
## Shutdown non-essential services
##
if [ "${WEB_USER}" != "${INSTALL_USER}" ] ; then
	service apache2 stop >/dev/null 2>&1
fi

service mysql stop >/dev/null 2>&1

# Make sure these are there...
[ ! -d "${SHARE_DIR}" ] && mkdir -p "${SHARE_DIR}" >/dev/null && chmod ${WRITE_ACCESS} "${SHARE_DIR}" && echo "  * Created ${SHARE_DIR}"

# Git submodules (not currently used, but could be in the future)
/usr/bin/git submodule update --init -q >/dev/null 2>&1 && echo "  * External modules updated"

##
## Check directory permissions...
##
echo "  * Checking file system"
chown -R ${INSTALL_USER}:${WEB_USER} * .git* >/dev/null 2>&1
find ./ -type d -exec chmod ${DIR_PERMS} {}  >/dev/null 2>&1 \;
find ./ -type f -exec chmod ${FILE_PERMS} {}  >/dev/null 2>&1 \;
find ./scripts/ -name '*.sh' -exec chmod ${SCRIPT_PERMS} {}  >/dev/null 2>&1 \;

##
## Check if composer is installed
## If not, install. If it is, make sure it's current
##
echo "  * Working directory: ${B1}${BASE_PATH}${B2}"

##
##	Install composer dependencies
##

pushd "${BASE_PATH}" >/dev/null

if [ ! -d "${VENDOR_DIR}" ] ; then
	echo "  * Installing dependencies"
	${PHP} ${COMPOSER_DIR}/${COMPOSER} ${QUIET} ${VERBOSE} --no-interaction install
#	${PHP} ${COMPOSER_DIR}/${COMPOSER} -v install
else
	echo "  * Updating dependencies"
	${PHP} ${COMPOSER_DIR}/${COMPOSER} ${QUIET} ${VERBOSE} --no-interaction update
#	${PHP} ${COMPOSER_DIR}/${COMPOSER} -v update
fi

[ $? -ne 0 ] && echo "  * ${B1}ERROR:${B2} Composer did not complete successfully ($?). Some features may not operate properly."

##
##	Make sure our directories are in place...
##
chgrp -R ${WEB_USER} ${SHARE_DIR} ${VENDOR_DIR} ./composer.lock >/dev/null 2>&1

if [ ! -d "${LOG_DIR}" ] ; then
	mkdir "${LOG_DIR}" >/dev/null 2>&1 && echo "  * Created ${LOG_DIR}"
fi

if [ ! -d "${STORAGE_DIR}" ] ; then
	mkdir "${STORAGE_DIR}" >/dev/null 2>&1 && echo "  * Created ${STORAGE_DIR}"
fi

if [ ! -d "${ASSETS_DIR}" ] ; then
	mkdir "${ASSETS_DIR}" >/dev/null 2>&1 && echo "  * Created ${ASSETS_DIR}"
fi

# Into public dir, link shared apps and junk
cd ${PUBLIC_DIR}

if [ ! -L "${PUBLIC_DIR}/web-core" ] ; then
    ln -sf ../../shared/dreamfactory/web/web-core/ web-core >/dev/null 2>&1
    echo "  * Core linked"
fi

if [ ! -L "${PUBLIC_DIR}/launchpad" ] ; then
    ln -sf ../../shared/dreamfactory/app/app-launchpad/ launchpad >/dev/null 2>&1
    echo "  * LaunchPad linked"
fi

if [ ! -L "${PUBLIC_DIR}/admin" ] ; then
    ln -sf ../../shared/dreamfactory/app/app-admin/ admin >/dev/null 2>&1
    echo "  * Admin linked"
fi

# Back
cd - >/dev/null 2>&1

##
## make owned by user
##
chown -R ${INSTALL_USER}:${WEB_USER} * .git*  >/dev/null 2>&1

##
## make writable by web server
##
chmod -R ${WRITE_ACCESS} shared/ vendor/ log/ web/public/assets/ >/dev/null 2>&1

##
## Restart non-essential services
##

service mysql start >/dev/null 2>&1

if [ "${WEB_USER}" != "${INSTALL_USER}" ] ; then
	service apache2 start >/dev/null 2>&1
else
	service apache2 reload >/dev/null 2>&1
fi

echo
echo "Complete. Enjoy the rest of your day!"

exit 0
