#!/bin/sh

# Add host.docker.internal to /etc/hosts
if ! grep -q "host.docker.internal" /etc/hosts; then
  echo "host.docker.internal has to be in your hosts-file, to add you need sudo privileges"
  sudo sh -c 'echo "127.0.0.1 host.docker.internal" >> /etc/hosts'
fi

# setup config & key files
DIR="../appconfig/files/udb3/docker/udb3-backend/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
  # needed because it is hidden
  cp "$DIR"/.env .
else
  echo "Error: missing appconfig see docker.md prerequisites to fix this."
  exit 1
fi