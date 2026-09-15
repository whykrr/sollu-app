.PHONY: dev db-up db-down db-logs ps test pint tinker migrate artisan

# Single Command Runner: Vite, Queue Worker, Scheduler, and Reverb
dev:
	composer run dev

# Docker Infrastructure: PostgreSQL + Redis
db-up:
	docker compose up -d postgres redis

db-down:
	docker compose stop postgres redis

db-logs:
	docker compose logs -f postgres redis

ps:
	docker compose ps

# Native Testing, Linting & Tools
test:
	php artisan test --compact

pint:
	vendor/bin/pint

tinker:
	php artisan tinker

migrate:
	php artisan migrate

# Shortcut for artisan commands: make artisan migrate, make artisan route:list, etc.
artisan:
	php artisan $(filter-out $@,$(MAKECMDGOALS))

%:
	@:
