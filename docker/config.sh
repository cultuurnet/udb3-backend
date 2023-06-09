#!/bin/sh

# setup config & key files
DIR="../appconfig/files/udb3/docker/udb3-backend/"
if [ -d "$DIR" ]; then
  cp "$DIR"/* .
  # needed because it is hidden
  cp "$DIR"/.env .
  mv ./config.features.php features/
else
  echo "Error: missing appconfig see docker.md prerequisites to fix this."
  exit 1
fi
