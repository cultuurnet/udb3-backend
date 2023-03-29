#!/bin/sh

echo Please provide a classic Github access token from https://github.com/settings/tokens
read token

array=(
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/keys/public.pem
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/keys/public-auth0.pem
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.allow_all.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.excluded_labels.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.external_id_mapping_organizer.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.external_id_mapping_place.php
  https://raw.githubusercontent.com/cultuurnet/udb3-vagrant/main/config/udb3-backend/config.php
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

sed -i'' -e '7s/https:\/\/io.uitdatabank.dev/http:\/\/host.docker.internal:8000/' config.php
sed -i'' -e '18s/http:\/\/search.uitdatabank.dev/http:\/\/host.docker.internal:9000/' config.php
sed -i'' -e '27s/127.0.0.1/mysql/' config.php
sed -i'' -e '51s/127.0.0.1/redis/' config.php
sed -i'' -e '59s/127.0.0.1/rabbitmq/' config.php
sed -i'' -e "19s/,/,\n            'scheme' => 'http',/" config.php
sed -i'' -e "20s/,/,\n            'port' => 9000,/" config.php
