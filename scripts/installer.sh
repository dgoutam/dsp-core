#!/bin/bash
# DSP install/update utility
# Copyright (C) 2012-2013 DreamFactory Software, Inc. All Rights Reserved
#
# CHANGELOG:
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

VERSION=1.1.3
SYSTEM_TYPE=`uname -s`
INSTALL_DIR=${HOME}/bin
COMPOSER=composer.phar
COMPOSER_INSTALLED=0
PHP=/usr/bin/php
WEB_USER=www-data
BASE=`pwd`
B1=`tput bold`
B2=`tput sgr0`
FABRIC=0
FABRIC_MARKER=/var/www/.fabric_hosted
TAG="Mode: ${B1}Local${B2}"
VERBOSE=
QUIET="--quiet"

# Hosted or standalone?
if [ -f "${FABRIC_MARKER}" ] ; then
	FABRIC=1
	TAG="Mode: ${B1}Fabric${B2}"
fi

echo "${B1}DreamFactory Services Platform(tm)${B2} ${SYSTEM_TYPE} Installer [${TAG} v${VERSION}]"

#	Execute getopt on the arguments passed to this program, identified by the special character $@
PARSED_OPTIONS=$(getopt -n "$0"  -o hvc --long "help,verbose,clean"  -- "$@")

#	Bad arguments, something has gone wrong with the getopt command.
if [ $? -ne 0 ] ; then
	exit 1
fi

# Composer already installed?
if [ -f "${INSTALL_DIR}/${COMPOSER}" ] ; then
	COMPOSER_INSTALLED=1
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
			VERBOSE="--verbose"
			QUIET=
			echo "  * Verbose mode enabled"
			shift
			;;

		-c|--clean)
			if [ ${COMPOSER_INSTALLED} -eq 0 ] ; then
				if [ -f "/usr/local/bin/composer.phar" ] ; then
					rm /usr/local/bin/composer.phar
					if [ $? -ne 0 ] ; then
						echo "  ! Cannot remove \"${B1}/usr/local/bin/composer.phar${B2}\". Please remove manually and re-run script."
					fi
				fi
			else
				echo "  * ${B1}Did not remove composer.phar as we did not install it."
			fi

			rm -rf ./shared/ ./vendor/ ./composer.lock >/dev/null 2>&1
			echo "  * Clean install. Dependencies removed."
			shift
			;;

		--)
			shift
			break;;
	esac
done

echo "  * Install user is ${B1}\"${USER}\"${B2}"

if [ "Darwin" = "${SYSTEM_TYPE}" ] ; then
	WEB_USER=_www
	echo "  * OS X installation: Apache user set to \"${B1}_www${B2}\""
elif [ "Linux" != "${SYSTEM_TYPE}" ] ; then
	echo "  * Windows/other installation. ${B1}Not fully tested so your mileage may vary${B2}."
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
chown -R ${USER} * .git* >/dev/null 2>&1
find ./ -type d -exec chmod 2775 {}  >/dev/null 2>&1 \;
find ./ -type f -exec chmod 0664 {}  >/dev/null 2>&1 \;
find ./ -name '*.sh' -exec chmod 0750 {}  >/dev/null 2>&1 \;
rm -rf ~${HOME}/.composer/
chmod +x ${BASE_PATH}/scripts/*.sh  >/dev/null 2>&1
[ -f ${BASE_PATH}/git-ssh-wrapper ] && chmod +x ${BASE_PATH}/git-ssh-wrapper

##
## Check if composer is installed
## If not, install. If it is, make sure it's current
##

if [ ! -d "${INSTALL_DIR}" ] ; then
	mkdir -p "${INSTALL_DIR}" >/dev/null 2>&1
fi

if [ ! -f "${INSTALL_DIR}/${COMPOSER}" ] ; then
	echo "  * Installing package manager"
	curl -s https://getcomposer.org/installer | ${PHP} -- --install-dir=${INSTALL_DIR} ${QUIET} ${VERBOSE} --no-interaction
else
	[ "${VERBOSE}" = "--verbose" ] && echo "  * Composer pre-installed"
	echo "  * Checking for package manager updates"
	${PHP} ${INSTALL_DIR}/${COMPOSER} ${QUIET} ${VERBOSE} --no-interaction self-update
fi

##
##	Install composer dependencies
##

pushd "${BASE_PATH}" >/dev/null

if [ ! -d "${VENDOR_DIR}" ] ; then
	echo "  * Installing dependencies"
	${PHP} ${INSTALL_DIR}/${COMPOSER} ${QUIET} ${VERBOSE} --no-interaction install
else
	echo "  * Updating dependencies"
	${PHP} ${INSTALL_DIR}/${COMPOSER} ${QUIET} ${VERBOSE} --no-interaction update
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
chown -R ${USER}:${WEB_USER} * .git*  >/dev/null 2>&1

##
## make writable by web server
##
chmod -R 0777 log/ web/public/assets/ >/dev/null 2>&1

##
## Restart non-essential services
##

service mysql start >/dev/null 2>&1
service apache2 start >/dev/null 2>&1

echo
echo "Complete. Enjoy the rest of your day!"

exit 0
