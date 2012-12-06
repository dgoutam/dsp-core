#!/bin/bash

if [ ${UID} != 0 ] ; then
	echo "This script must be run as root"
	exit 1
fi

BASE_PATH="`dirname "${0}" | xargs dirname`"
if [ "." == "${BASE_PATH}" ] ; then
	BASE_PATH=".."
fi

LOG_DIR="${BASE_PATH}/log/"
WEB_DIR="${BASE_PATH}/web"
VENDOR_DIR="${BASE_PATH}/vendor"
PUBLIC_DIR="${WEB_DIR}/public"
ASSETS_DIR="${PUBLIC_DIR}/assets"

echo "
Paths
--------
Base	:	${BASE_PATH}
Log     :	${LOG_DIR}
Web	    :	${WEB_DIR}
Vendor	:	${VENDOR_DIR}
Public	:	${PUBLIC_DIR}
Assets	:	${ASSETS_DIR}

";

if [ ! -d "${LOG_DIR}" ] ; then
	mkdir "${LOG_DIR}" >/dev/null 2>&1
fi

if [ ! -d "${ASSETS_DIR}" ] ; then
	mkdir "${ASSETS_DIR}" >/dev/null 2>&1
fi

if [ ! -d "${VENDOR_DIR}" ] ; then
	mkdir "${VENDOR_DIR}" >/dev/null 2>&1
fi

if [ ! -d "${PUBLIC_DIR}/launchpad" ] ; then
	ln -s "${VENDOR_DIR}/dreamfactory/app-launchpad/" "${PUBLIC_DIR}/launchpad" >/dev/null 2>&1
fi

chgrp -R www-data "${LOG_DIR}" "${PUBLIC_DIR}"
chmod -R 2755 "${PUBLIC_DIR}"
chmod -R 2775 "${ASSETS_DIR}" 
chmod -R 2775 "${LOG_DIR}" 

echo "Complete."
echo
