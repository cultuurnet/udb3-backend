#!/bin/sh

# copy env files
cp .env.dist .env

# setup config & key files
DIR="../appconfig/files/udb3/docker/udb3-backend/"
if [ -d "$DIR" ]; then
  cp "$DIR"/* .
else
  echo "Error: missing appconfig see docker.md prerequisites to fix this."
  exit 1
fi
