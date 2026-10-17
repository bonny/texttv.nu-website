# Onboarding-targets för den lokala Docker-stacken. `make` finns överallt.
# Sajten: http://texttv.test:8380/100 · importer-API: http://localhost:8381

COMPOSE := docker compose

.PHONY: help up down build rebuild logs ps seed test-api phpstan phpstan-website phpstan-importer phpstan-baseline phpstan-install scheduler import artisan tinker mysql shell destroy

help: ## Visa denna hjälp
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | \
		awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-10s\033[0m %s\n",$$1,$$2}'

up: ## Starta stacken (bygger images automatiskt första gången). Snabb vid dagligt bruk.
	$(COMPOSE) up -d
	@echo "Klar. Sajten: http://texttv.test:8380/100  (vänta nån sekund på init-importen)"
	@echo "Schedulern (auto-import från SVT) är AV — starta vid behov med 'make scheduler'."

down: ## Stoppa stacken (behåller data)
	$(COMPOSE) down

build: ## Bygg om images
	$(COMPOSE) build

rebuild: ## Bygg om från grunden (ingen cache) — kör efter ändrad Dockerfile/DB-cred
	$(COMPOSE) build --no-cache

logs: ## Följ loggar (alla services)
	$(COMPOSE) logs -f

ps: ## Lista services
	$(COMPOSE) ps

seed: ## Seeda om DB:n (migrera + importera startsidorna). Säkert att köra om — idempotent.
	$(COMPOSE) run --rm init

test-api: ## Jämför /api/*-svaren mot origin/main (eller BASE=<git-ref>). Kräver make up.
	BASE=$(or $(BASE),origin/main) tests/api/run.sh

# PHPStan körs i projektets egna images (PHP 8.2 som prod), med verktyget i
# tools/phpstan/ som varken deployas eller startar en deploy. Importern körs
# med importer/ monterad och imagens vendor/ kvar (Laravel behövs för analysen).
UIDGID := $(shell id -u):$(shell id -g)
PHPSTAN_WEB := docker run --rm --entrypoint "" -u $(UIDGID) -v "$(CURDIR)":/app -w /app/tools/phpstan texttv-website:local php -d memory_limit=1G vendor/bin/phpstan
PHPSTAN_IMP := docker run --rm --entrypoint "" -u $(UIDGID) -v "$(CURDIR)/importer":/var/www/html -v /var/www/html/vendor -v "$(CURDIR)/tools/phpstan":/tools -w /tools texttv-importer:local php -d memory_limit=1G /tools/vendor/bin/phpstan

phpstan: phpstan-website phpstan-importer ## PHPStan på webbsajten (nivå 1) och importern (nivå 5). Bara ny kod granskas (baseline).

phpstan-install: ## Installera PHPStan i tools/phpstan/ (görs automatiskt av make phpstan)
	docker run --rm --entrypoint "" -u $(UIDGID) -e COMPOSER_HOME=/tmp/composer -v "$(CURDIR)":/app -w /app/tools/phpstan texttv-website:local composer install --no-interaction --quiet

tools/phpstan/vendor/bin/phpstan:
	$(MAKE) phpstan-install

phpstan-website: tools/phpstan/vendor/bin/phpstan ## PHPStan på texttv.nu/
	$(PHPSTAN_WEB) analyse -c website.neon --no-progress

phpstan-importer: tools/phpstan/vendor/bin/phpstan ## PHPStan på importer/
	$(PHPSTAN_IMP) analyse -c importer.neon --no-progress

phpstan-baseline: tools/phpstan/vendor/bin/phpstan ## Skriv om båda baselines (bara när gamla fel är rättade eller medvetet godkända)
	$(PHPSTAN_WEB) analyse -c website.neon --no-progress --generate-baseline website-baseline.neon
	$(PHPSTAN_IMP) analyse -c importer.neon --no-progress --generate-baseline importer-baseline.neon

scheduler: ## Starta schedulern (opt-in: auto-importerar från SVT var 2:e min)
	$(COMPOSE) --profile scheduler up -d scheduler

import: ## Importera sidor: make import RANGE=100-110
	$(COMPOSE) exec importer php artisan texttv:import $(RANGE)

artisan: ## Kör artisan: make artisan CMD="schedule:list"
	$(COMPOSE) exec importer php artisan $(CMD)

tinker: ## Laravel tinker i importer-containern
	$(COMPOSE) exec importer php artisan tinker

mysql: ## MariaDB-shell (texttv_nu)
	$(COMPOSE) exec mariadb mariadb -u texttv -ptexttv texttv_nu

shell: ## Bash i importer-containern
	$(COMPOSE) exec importer bash

destroy: ## Stoppa + RADERA all data (volymer)
	$(COMPOSE) down -v
