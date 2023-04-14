#!/bin/sh

if [ ! -d udb3-docker-config ]; then
  git clone git@github.com:cultuurnet/udb3-docker-config.git;
  cd udb3-docker-config;
else
  cd udb3-docker-config;
  git stash push --include-untracked;
  git checkout main;
  git pull;
fi

cp *.pem ../
cp *.php ../
