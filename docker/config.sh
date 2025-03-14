#!/bin/sh

UPDATE_HOSTS=${HAS_SUDO:-true}

HOSTS="io.uitdatabank.local mailpit.uitdatabank.local"

if [ "$UPDATE_HOSTS" = "true" ]; then
  MISSING_HOSTS=""

  set -- $HOSTS
  for HOST; do
    if ! grep -q "$HOST" /etc/hosts; then
      echo "$HOST is missing from /etc/hosts"
      MISSING_HOSTS="$MISSING_HOSTS\n127.0.0.1 $HOST"
    fi
  done

  if [ -n "$MISSING_HOSTS" ]; then
    echo "Adding missing entries to /etc/hosts (requires sudo)"
    printf "%s\n" "$MISSING_HOSTS" | sudo tee -a /etc/hosts > /dev/null
  fi
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
