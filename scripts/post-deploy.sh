#!/bin/bash
# DSP Post-deployment/update tasks
# Copyright (C) 2012-2013 DreamFactory Software, Inc. All Rights Reserved
#
# CHANGELOG:
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

if [ ${UID} != 0 ] ; then
	echo "This script must be run as root"
	exit 1
fi

##
##	Initial settings
##

VERSION=1.0.8
SYSTEM_TYPE=`uname -s`
INSTALL_DIR=/usr/local/bin
COMPOSER=composer.phar
PHP=/usr/bin/php
VERBOSE=1
WEB_USER=www-data
BASE=`pwd`
WRAPPER="${BASE}/git-ssh-wrapper"
SSH_KEY=${1:-id_deploy}
B1=`tput bold`
B2=`tput sgr0`
FABRIC=0
FABRIC_MARKER=/var/www/.fabric_hosted
TAG=" ${B1}Local Install Mode${B2}"

if [ -f "${FABRIC_MARKER}" ] ; then
	FABRIC=1
	TAG=" ${B1}Fabric Install Mode${B2}"
fi

echo "${B1}DreamFactory Services Platform(tm)${B2} System Updater [${TAG} v${VERSION}]"

## No wrapper, unset
if [ ! -f ${WRAPPER} ] ; then
	WRAPPER=
else
	WRAPPER="GIT_SSH=${WRAPPER}"
fi

## User supplied deploy key?
if [ ! -z "${1}" ] ; then
	ssh-add ${SSH_KEY}
	echo "  * Using ${B1}\"${SSH_KEY}\"${B2} for deployment"
fi

echo "  * Install user is ${B1}\"${SUDO_USER}\"${B2}"

if [ "Darwin" = "${SYSTEM_TYPE}" ] ; then
	WEB_USER=_www
	echo "  * OS X installation"
elif [ "Linux" != "${SYSTEM_TYPE}" ] ; then
	echo "  * Windows/other installation. ${B1}Not fully tested so your mileage may vary${B2}."
else
	echo "  * Linux installation"
fi

##
## Shutdown non-essential services
##

service apache2 stop >/dev/null 2>&1
service mysql stop >/dev/null 2>&1

##
## Construct the various paths
##
BASE_PATH="`dirname "${0}" | xargs dirname`"

##	Get the REAL path of install
pushd "${BASE_PATH}" >/dev/null
BASE_PATH=`pwd`
popd >/dev/null

LOG_DIR=${BASE_PATH}/log/
STORAGE_DIR=${BASE_PATH}/storage/
VENDOR_DIR=${BASE_PATH}/vendor
WEB_DIR=${BASE_PATH}/web
PUBLIC_DIR=${WEB_DIR}/public
ASSETS_DIR=${PUBLIC_DIR}/assets
APPS_DIR=${BASE_PATH}/apps
LIB_DIR=${BASE_PATH}/lib

# Determine share location
if [ -f "${FABRIC_MARKER}" ] ; then
	SHARE_DIR=/var/www/dsp-share
else
	SHARE_DIR=${BASE_PATH}/shared
fi

# Make sure these are there...
[ -d "${APPS_DIR}" ] && rm -rf "${APPS_DIR}" >/dev/null 2>&1  && echo "  * Removed bogus apps directory \"${APPS_DIR}\""
[ ! -d "${LIB_DIR}" ] && mkdir "${LIB_DIR}" >/dev/null 2>&1  && echo "  * Created ${LIB_DIR}"
[ ! -d "${SHARE_DIR}" ] && mkdir "${SHARE_DIR}" >/dev/null 2>&1  && echo "  * Created ${SHARE_DIR}"

##
## Check directory permissions...
##
echo "  * Checking file system"
chown -R ${SUDO_USER}:${WEB_USER} * .git*
find ./ -type d -exec chmod 2775 {} \;
find ./ -type f -exec chmod 0664 {} \;
find ./ -name '*.sh' -exec chmod 0770 {} \;
rm -rf ~${SUDO_USER}/.composer/
[ -f ${BASE_PATH}/git-ssh-wrapper ] && chmod +x ${BASE_PATH}/git-ssh-wrapper

##
## Do a pull for good measure
##
echo "  * Checking for DSP updates"
git reset --hard --quiet HEAD && git stash --quiet
git pull --quiet --force origin master

##
## Check if composer is installed
## If not, install. If it is, make sure it's current
##

if [ ! -f "${INSTALL_DIR}/${COMPOSER}" ] ; then
	echo "  * Installing package manager"
	curl -s https://getcomposer.org/installer | ${PHP} -- --install-dir=${INSTALL_DIR} --quiet --no-interaction
else
	echo "  * Checking for package manager updates"
	${PHP} ${INSTALL_DIR}/${COMPOSER} --quiet --no-interaction self-update
fi

##
##	Install composer dependencies
##

pushd "${BASE_PATH}" >/dev/null

if [ ! -d "${VENDOR_DIR}" ] ; then
	echo "  * Installing dependencies"
	${PHP} ${INSTALL_DIR}/${COMPOSER} --quiet --no-interaction install
else
	echo "  * Updating dependencies"
	${PHP} ${INSTALL_DIR}/${COMPOSER} --quiet --no-interaction update
fi

##
##	Make sure our directories are in place...
##

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

if [ ! -d "${PUBLIC_DIR}/web-core" ] ; then
    ln -sf ${SHARE_DIR}/dreamfactory/web/web-core/ web-core >/dev/null 2>&1
    echo "  * Web Core linked"
fi

if [ ! -d "${PUBLIC_DIR}/launchpad" ] ; then
    ln -sf ${SHARE_DIR}/dreamfactory/app/app-launchpad/ launchpad >/dev/null 2>&1
    echo "  * Launchpad linked"
fi

if [ ! -d "${PUBLIC_DIR}/admin" ] ; then
    ln -sf ${SHARE_DIR}/dreamfactory/app/app-admin/ admin >/dev/null 2>&1
    echo "  * Admin linked"
fi

# Back
cd - >/dev/null 2>&1

##
## make owned by user
##
chown -R ${SUDO_USER}:${WEB_USER} * .git*

##
## Restart non-essential services
##

service mysql start >/dev/null 2>&1
service apache2 start >/dev/null 2>&1

echo
echo "Complete. Enjoy the rest of your day!"

exit 0
