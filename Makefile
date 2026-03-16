.PHONY: up down build shell logs \
        install test phpstan ecs ecs-fix rector rector-fix migrate simulate \
        check fix

# Docker
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose up -d --build

shell:
	docker compose exec app bash

logs:
	docker compose logs -f

# Backend
install:
	docker compose exec -T app composer install

test:
	docker compose exec -T app php bin/phpunit

phpstan:
	docker compose exec -T app php vendor/bin/phpstan analyse --memory-limit=512M

ecs:
	docker compose exec -T app php vendor/bin/ecs check

ecs-fix:
	docker compose exec -T app php vendor/bin/ecs check --fix

rector:
	docker compose exec -T app php vendor/bin/rector process --dry-run

rector-fix:
	docker compose exec -T app php vendor/bin/rector process

migrate:
	docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

simulate:
	docker compose exec -T app php bin/console app:simulate fixtures/input.xml

# Combined
check: ecs phpstan test

fix: ecs-fix rector-fix
