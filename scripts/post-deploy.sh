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
WEB_USER="www-data"
WRAPPER=/var/www/launchpad/git-ssh-wrapper

## No wrapper, unset
if [ ! -f ${WRAPPER} ] ; then
	WRAPPER=
else
	WRAPPER="GIT_SSH=${WRAPPER}"
fi

##
## Check if composer is installed
## If not, install. If it is, make sure it's current
##

if [ ! -f "${INSTALL_DIR}/${COMPOSER}" ] ; then
	echo "Installing Composer"
	curl -s https://getcomposer.org/installer | ${PHP} -- --install-dir=${INSTALL_DIR}
else
#	echo "Checking for Composer updates"
	${PHP} ${INSTALL_DIR}/${COMPOSER} -q self-update
fi

##
## Construct the various paths
##

BASE_PATH="`dirname "${0}" | xargs dirname`"

##	Get the REAL path of install
pushd "${BASE_PATH}" >/dev/null
BASE_PATH=`pwd`
popd >/dev/null

LOG_DIR="${BASE_PATH}/log/"
STORAGE_DIR="${BASE_PATH}/storage/"
VENDOR_DIR="${BASE_PATH}/vendor"
WEB_DIR="${BASE_PATH}/web"
PUBLIC_DIR="${WEB_DIR}/public"
ASSETS_DIR="${PUBLIC_DIR}/assets"

#if [ 1 -eq ${VERBOSE} ] ; then
#	echo "
#Base    :	${BASE_PATH}
#Log     :	${LOG_DIR}
#Storage :	${STORAGE_DIR}
#Vendor  :	${VENDOR_DIR}
#Web     :	${WEB_DIR}
#Public  :	${PUBLIC_DIR}
#Assets  :	${ASSETS_DIR}
#
#";
#fi

##
##	Install composer dependencies
##

pushd "${BASE_PATH}" >/dev/null

if [ ! -d "${VENDOR_DIR}" ] ; then
#	echo "Installing dependencies"
	${WRAPPER} ${PHP} ${INSTALL_DIR}/${COMPOSER} -q install
else
#	echo "Updating dependencies"
	${WRAPPER} ${PHP} ${INSTALL_DIR}/${COMPOSER} -q update
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

# make writable by web-server, change www-data to _www on Mac, see 'cat /etc/apache2/httpd.conf'
chgrp -R ${WEB_USER} "${LOG_DIR}" "${STORAGE_DIR}" "${PUBLIC_DIR}"
chmod -R 2755 "${PUBLIC_DIR}"
chmod -R 2775 "${ASSETS_DIR}"
chmod -R 2775 "${LOG_DIR}"
chmod -R 2775 "${STORAGE_DIR}"

exit 0