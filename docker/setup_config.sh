#!/bin/sh

# copy env files
cp .env.dist .env

# setup config & key files
DIR="../appconfig/files/udb3/docker/udb3-backend/"
if [ -d "$DIR" ]; then
  cp "$DIR"/* .
else
  echo "Error: see docker.md to setup config"
  exit 1
fi