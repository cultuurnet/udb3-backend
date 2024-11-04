#!/bin/sh

UPDATE_HOSTS=${HAS_SUDO:-true}

if [ "$UPDATE_HOSTS" = "true" ] && ! grep -q "io.uitdatabank.local" /etc/hosts; then
  echo "io.uitdatabank.local has to be in your hosts-file, to add you need sudo privileges"
  sudo sh -c 'echo "127.0.0.1 io.uitdatabank.local" >> /etc/hosts'
fi

APPCONFIG_ROOTDIR=${APPCONFIG:-'../appconfig'}

DIR="${APPCONFIG_ROOTDIR}/files/uitdatabank/docker/udb3-backend/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
  cp "$DIR"/.env .
else
  echo "Error: missing appconfig. The appconfig repository must be cloned at ${APPCONFIG_ROOTDIR}."
  exit 1
fi

DIR="${APPCONFIG_ROOTDIR}/files/uitdatabank/docker/keys/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig. The appconfig repository must be cloned at ${APPCONFIG_ROOTDIR}."
  exit 1
fi
