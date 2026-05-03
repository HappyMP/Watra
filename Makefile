.PHONY: up down bash install migrate fixtures test cs-fix phpstan build logs

DC ?= docker compose -f compose.yaml
DOCKER_USER ?= $(shell if [ "$$(id -u)" = "0" ]; then echo "1000:1000"; else echo "$$(id -u):$$(id -g)"; fi)
PHP = $(DC) run --rm -u $(DOCKER_USER) php
PHP_EXEC = $(DC) exec -u $(DOCKER_USER) php

## ─── Stack ───────────────────────────────────────────────────────────────────

up:
	$(DC) up -d --build

down:
	$(DC) down

restart:
	$(DC) restart

logs:
	$(DC) logs -f --tail=100

## ─── PHP shell ───────────────────────────────────────────────────────────────

bash:
	$(DC) exec -u $(DOCKER_USER) php bash

## ─── Instalacja i setup ──────────────────────────────────────────────────────

install:
	$(PHP) composer install --no-interaction

migrate:
	$(PHP_EXEC) bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	$(PHP_EXEC) bin/console sylius:fixtures:load --no-interaction

sylius-install:
	$(PHP_EXEC) bin/console sylius:install -s default -n

build-assets:
	$(DC) run --rm -u $(DOCKER_USER) nodejs

## ─── Jakość kodu ─────────────────────────────────────────────────────────────

cs-fix:
	$(PHP) vendor/bin/ecs check --fix

cs-check:
	$(PHP) vendor/bin/ecs check

phpstan:
	$(PHP) vendor/bin/phpstan analyse

## ─── Testy ───────────────────────────────────────────────────────────────────

test:
	$(PHP) vendor/bin/phpunit
	$(PHP) vendor/bin/behat --colors

phpunit:
	$(PHP) vendor/bin/phpunit

behat:
	$(PHP) vendor/bin/behat --colors

## ─── Konsola Symfony ─────────────────────────────────────────────────────────

console:
	$(PHP_EXEC) bin/console $(filter-out $@, $(MAKECMDGOALS))

%:
	@:

## ─── Czyszczenie ─────────────────────────────────────────────────────────────

clean:
	$(DC) down -v
	rm -rf var/cache var/log

cache-clear:
	$(PHP_EXEC) bin/console cache:clear

## ─── Sprawdzenie środowiska ──────────────────────────────────────────────────

check-env:
	bash bin/check-env.sh
