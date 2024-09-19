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
	docker-compose exec php composer install

migrate:
	docker-compose exec php ./vendor/bin/doctrine-dbal migrations:migrate --no-interaction

init: install migrate

ci:
	docker-compose exec php composer ci

stan:
	docker-compose exec php composer phpstan

cs:
	docker-compose exec php composer cs

cs-fix:
	docker-compose exec php composer cs-fix

test:
	docker-compose exec php composer test

test-filter:
	docker-compose exec php composer test -- --filter=$(filter)

test-group:
	docker-compose exec php composer test -- --group=$(group)

feature-init:
	docker-compose exec php composer feature -- --tags @init

feature-tag:
	docker-compose exec php composer feature -- --tags $(tag)

feature:
	docker-compose exec php composer feature -- --tags "~@init&&~@external"

feature-filter:
	docker-compose exec php composer feature -- $(path)


feature-random:
	docker-compose exec php composer feature -- --order=random --tags "~@init&&~@external"

