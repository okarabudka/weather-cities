# Makefile

SHELL = /bin/bash

ifndef VERBOSE
MAKEFLAGS += --no-print-directory
endif

help:
	@printf '		'
	@make print-color-blue TEXT='** Available commands **\n\n'
	@make print-color-green TEXT='up-containers'
	@printf '		start containers (bootstraps Symfony on first run)\n'
	@make print-color-green TEXT='down'
	@printf '		stop containers\n'
	@make print-color-green TEXT='reset'
	@printf '		destroy volume and re-bootstrap Symfony from scratch\n'
	@make print-color-green TEXT='logs'
	@printf '		show container logs\n'
	@make print-color-green TEXT='bash'
	@printf '		open shell in app container\n'
	@make print-color-green TEXT='composer'
	@printf '		run composer command (ARGS="require foo")\n'
	@make print-color-green TEXT='migrate'
	@printf '		run doctrine migrations\n'
	@make print-color-green TEXT='test'
	@printf '		run PHPUnit\n'
	@make print-color-green TEXT='lint'
	@printf '		PHP syntax check\n'
	@make print-color-green TEXT='phpstan'
	@printf '		static analysis\n'
	@make print-color-green TEXT='export-app'
	@printf '		copy generated app to ./app\n'
	@make print-color-green TEXT='finalize'
	@printf '		replace repo with generated app and remove bootstrap files\n'

up-containers:
	@make docker-compose-exec COMPOSE_CMD="up -d --build"

down:
	@make docker-compose-exec COMPOSE_CMD="down"

reset:
	@make docker-compose-exec COMPOSE_CMD="down"
	@docker volume rm $$(docker compose config 2>/dev/null | awk '/^name:/ {print $$2 "_app_data"}') 2>/dev/null || true
	@make docker-compose-exec COMPOSE_CMD="up -d --build"

logs:
	@make docker-compose-exec COMPOSE_CMD="logs -f --tail=200"

composer:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "composer $(ARGS)"'

bash:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash'

migrate:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "php bin/console doctrine:migrations:migrate -n"'

test:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "php bin/phpunit"'

lint:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "find src tests -name \"*.php\" -print0 | xargs -0 -n1 php -l"'

phpstan:
	@make docker-exec CONTAINER="app" CONTAINER_CMD='bash -c "vendor/bin/phpstan analyse"'

export-app:
	@mkdir -p app
	@docker compose run --rm --entrypoint sh \
		-v "$$(pwd)/app:/export" app \
		-c "cp -a /var/www/. /export && rm -f /export/.initialized && chown -R $$(id -u):$$(id -g) /export"

finalize:
	@bash scripts/finalize.sh

docker-exec:
	@docker compose exec $(CONTAINER) $(CONTAINER_CMD)

docker-compose-exec:
	@docker compose $(COMPOSE_CMD)

print-color-green:
	@printf '\033[0;32m${TEXT}\033[0m'

print-color-blue:
	@printf '\033[0;36m${TEXT}\033[0m'
