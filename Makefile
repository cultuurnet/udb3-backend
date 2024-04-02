.PHONY: up down install ci stan cs cs-fix test migrate config init feature

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

feature-init:
	docker exec -it php.uitdatabank composer feature -- --tags @init

feature-tag:
	docker exec -it php.uitdatabank composer feature -- --tags $(tag)

feature:
	docker exec -it php.uitdatabank composer feature -- --tags "~@init"

feature-filter:
	docker exec -it php.uitdatabank composer feature -- $(path)

migrate:
	docker exec -it php.uitdatabank ./vendor/bin/doctrine-dbal migrations:migrate --no-interaction

bash:
	docker exec -it php.uitdatabank bash

config:
	sh ./docker/config.sh

init: install migrate
