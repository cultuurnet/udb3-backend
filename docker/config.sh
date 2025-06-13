#!/bin/sh

UPDATE_HOSTS=${HAS_SUDO:-true}

HOSTS="io.uitdatabank.local mailpit.uitdatabank.local"

if [ "$UPDATE_HOSTS" = "true" ]; then
  MISSING_HOSTS=""

  set -- $HOSTS
  for HOST; do
    if ! grep -q "$HOST" /etc/hosts; then
      MISSING_HOSTS="$MISSING_HOSTS $HOST"
    fi
  done

  set -- $MISSING_HOSTS
  for MISSING_HOST; do
    echo "$MISSING_HOST has to be in your hosts-file, to add you need sudo privileges"
    sudo sh -c "echo '127.0.0.1 $MISSING_HOST' >> /etc/hosts"
  done
fi

APPCONFIG_ROOTDIR=${APPCONFIG:-'../appconfig'}

DIR="${APPCONFIG_ROOTDIR}/templates/docker/uitdatabank/udb3-backend/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
  cp "$DIR"/.env .
else
  echo "Error: missing appconfig. The appconfig repository must be cloned at ${APPCONFIG_ROOTDIR}."
  exit 1
fi

DIR="${APPCONFIG_ROOTDIR}/templates/docker/uitdatabank/keys/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig. The appconfig repository must be cloned at ${APPCONFIG_ROOTDIR}."
  exit 1
fi
