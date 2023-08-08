.PHONY: up down install ci stan cs cs-fix test migrate config init

up:
	docker-compose up -d

down:
	docker-compose down

install:
	docker exec -it php.uitdatabank composer install

ci:
	docker exec -it php.uitdatabank composer ci

stan:
	docker exec -it php.uitdatabank composer phpstan

cs:
	docker exec -it php.uitdatabank composer cs

cs-fix:
	docker exec -it php.uitdatabank composer cs-fix

test:
	docker exec -it php.uitdatabank composer test

test-filter:
	docker exec -it php.uitdatabank composer test -- --filter=$(filter)

test-group:
	docker exec -it php.uitdatabank composer test -- --group=$(group)

features-init:
	docker exec -it php.uitdatabank composer features -- --tags @init

features:
	docker exec -it php.uitdatabank composer features  -- --tags "~@init"

features-filter:
	docker exec -it php.uitdatabank composer features -- $(path)

features-all: features-init features

migrate:
	docker exec -it php.uitdatabank ./vendor/bin/doctrine-dbal migrations:migrate --no-interaction

bash:
	docker exec -it php.uitdatabank bash

config:
	sh ./docker/config.sh

init: install migrate
