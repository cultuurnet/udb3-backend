#!/bin/sh

if ! grep -q "host.docker.internal" /etc/hosts; then
  echo "host.docker.internal has to be in your hosts-file, to add you need sudo privileges"
  sudo sh -c 'echo "127.0.0.1 host.docker.internal" >> /etc/hosts'
fi

DIR="../appconfig/files/udb3/docker/udb3-backend/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
  cp "$DIR"/.env .
else
  echo "Error: missing appconfig see docker.md prerequisites to fix this."
  exit 1
fi

DIR="../appconfig/files/udb3/docker/keys/"
if [ -d "$DIR" ]; then
  cp -R "$DIR"/* .
else
  echo "Error: missing appconfig see docker.md prerequisites to fix this."
  exit 1
fi