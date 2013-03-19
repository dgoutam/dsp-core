#!/bin/bash
# DSP Post-deployment tasks
# Copyright (C) 2012 DreamFactory Software, Inc. All Rights Reserved
#

if [ ${UID} != 0 ] ; then
	echo "This script must be run as root"
	exit 1
fi

##
##	Initial settings
##

INSTALL_DIR=/usr/local/bin
COMPOSER=composer.phar
PHP=/usr/bin/php
VERBOSE=1
WEB_USER=www-data
WRAPPER=/var/www/launchpad/git-ssh-wrapper
LOCAL_USER=dfadmin

echo "DreamFactory Services Platform(tm) System Updater v1.0.0"

## No wrapper, unset
if [ ! -f ${WRAPPER} ] ; then
	WRAPPER=
else
	WRAPPER="GIT_SSH=${WRAPPER}"
fi

## User supplied local user name?
if [ "x" != "x$1" ] ; then
	LOCAL_USER=$1
	echo "Running for local user: ${LOCAL_USER}"
fi

echo ""

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

# Make sure these are there...
[ ! -d "${APPS_DIR}" ] && mkdir "${APPS_DIR}" >/dev/null 2>&1
[ ! -d "${LOG_DIR}" ] && mkdir "${LOG_DIR}" >/dev/null 2>&1

##
## Check directory permissions...
##

echo "Spot-checking file system"
chown -R ${LOCAL_USER}:${WEB_USER} *
find ./ -type d -exec chmod 2775 {} \;
find ./ -type f -exec chmod 0664 {} \;
find ./ -name '*.sh' -exec chmod 0770 {} \;
rm -rf ~${LOCAL_USER}/.composer/
[ -f ${BASE_PATH}/git-ssh-wrapper ] && chmod +x ${BASE_PATH}/git-ssh-wrapper

##
## Do a pull for good measure
##
echo "Checking for core updates {$WRAPPER}"
git reset --hard --quiet HEAD
git pull --quiet origin master
git submodule --quiet update --init

##
## Check if composer is installed
## If not, install. If it is, make sure it's current
##

if [ ! -f "${INSTALL_DIR}/${COMPOSER}" ] ; then
	echo "Installing package manager"
	curl -s https://getcomposer.org/installer | ${PHP} -- --install-dir=${INSTALL_DIR} --quiet --no-interaction
else
	echo "Checking for package manager updates"
	${PHP} ${INSTALL_DIR}/${COMPOSER} --quiet --no-interaction self-update
fi

##
##	Install composer dependencies
##

pushd "${BASE_PATH}" >/dev/null

if [ ! -d "${VENDOR_DIR}" ] ; then
	echo "Installing packages"
	${PHP} ${INSTALL_DIR}/${COMPOSER} --quiet --no-interaction install
else
	echo "Updating packages"
	${PHP} ${INSTALL_DIR}/${COMPOSER} --quiet --no-interaction update
fi

##
##	Make sure our directories are in place...
##

if [ ! -d "${LOG_DIR}" ] ; then
	mkdir "${LOG_DIR}" >/dev/null 2>&1
fi

if [ ! -d "${STORAGE_DIR}" ] ; then
	mkdir "${STORAGE_DIR}" >/dev/null 2>&1
fi

if [ ! -d "${ASSETS_DIR}" ] ; then
	mkdir "${ASSETS_DIR}" >/dev/null 2>&1
fi

if [ ! -d "${PUBLIC_DIR}/launchpad" ] ; then
    ln -s "${APPS_DIR}/dreamfactory/app-launchpad/" "${PUBLIC_DIR}/launchpad" >/dev/null 2>&1
fi

if [ ! -d "${PUBLIC_DIR}/admin" ] ; then
    ln -s "${APPS_DIR}/dreamfactory/app-admin/" "${PUBLIC_DIR}/admin" >/dev/null 2>&1
fi

if [ ! -d "${PUBLIC_DIR}/web-core" ] ; then
    ln -s "${APPS_DIR}/dreamfactory/web-core/" "${PUBLIC_DIR}/web-core" >/dev/null 2>&1
fi

##
## make owned by user
##
chown -R ${LOCAL_USER}:${WEB_USER} *

##
## Restart non-essential services
##

service mysql start >/dev/null 2>&1
service apache2 start >/dev/null 2>&1

echo
echo "Complete. Enjoy the rest of your day!"

exit 0
