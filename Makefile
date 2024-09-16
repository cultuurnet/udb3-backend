.PHONY: up down bash config install migrate init ci stan cs cs-fix test feature

up:
	docker-compose up -d

down:
	docker-compose down

bash:
	docker-compose exec php bash

config:
	sh ./docker/config.sh

install:
	docker-compose exec -T php composer install

migrate:
	docker-compose exec -T php ./vendor/bin/doctrine-dbal migrations:migrate --no-interaction

init: install migrate

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
	docker-compose exec -T php composer feature -- --tags @init

feature-tag:
	docker exec -it php.uitdatabank composer feature -- --tags $(tag)

feature:
	docker-compose exec -T php composer feature -- --tags "~@init&&~@external"

feature-filter:
	docker exec -it php.uitdatabank composer feature -- $(path)
