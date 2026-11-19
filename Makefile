# Makefile — local Docker workflow. Run `make help` for the list of targets.

SHELL := /bin/bash
.DEFAULT_GOAL := help

export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose
# -T keeps exec usable without a TTY (CI, scripts).
EXEC := $(COMPOSE) exec -T app

.PHONY: help env up down install sh test stan lint check

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

env: ## Create .env from .env.example (skipped if present)
	@test -f .env || cp .env.example .env

up: env ## Build and start containers, install dependencies
	$(COMPOSE) up -d --build
	$(EXEC) composer install --no-interaction

down: ## Stop and remove containers
	$(COMPOSE) down

install: ## Install Composer dependencies
	$(EXEC) composer install --no-interaction

sh: ## Open a shell in the PHP container
	$(COMPOSE) exec app sh

test: ## Run PHPUnit
	$(EXEC) composer test

stan: ## Run PHPStan (level max)
	$(EXEC) composer stan

lint: ## Check PHP syntax
	$(EXEC) composer lint

check: lint stan test ## Run lint, PHPStan and PHPUnit
