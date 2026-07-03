# Onboarding-targets för den lokala Docker-stacken. `make` finns överallt.
# Sajten: http://localhost:8380/100 · importer-API: http://localhost:8381

COMPOSE := docker compose

.PHONY: help up down build rebuild logs ps seed scheduler import artisan tinker mysql shell destroy

help: ## Visa denna hjälp
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | \
		awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-10s\033[0m %s\n",$$1,$$2}'

up: ## Starta stacken (bygger images automatiskt första gången). Snabb vid dagligt bruk.
	$(COMPOSE) up -d
	@echo "Klar. Sajten: http://localhost:8380/100  (vänta nån sekund på init-importen)"
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
