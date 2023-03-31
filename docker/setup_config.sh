#!/bin/sh

echo Please provide a classic Github access token from https://github.com/settings/tokens
read token

# copy config files
cp .env.dist .env
cp docker-config.php config.php

# retrieve config.php as config.vagrant.php from udb3-vagrant
curl -H 'Authorization: token '"$token" \
  -H 'Accept: application/vnd.github.v3.raw' \
  -o config.vagrant.php \
  -L https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.php

# retrieve necessary files from udb3-vagrant
array=(
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/keys/public.pem
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/keys/public-auth0.pem
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.allow_all.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.excluded_labels.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.external_id_mapping_organizer.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.external_id_mapping_place.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.term_mapping_facilities.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.term_mapping_themes.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.term_mapping_types.php
)

for file in "${array[@]}"
do
  curl -H 'Authorization: token '"$token" \
    -H 'Accept: application/vnd.github.v3.raw' \
    -O \
    -L "$file"
done
