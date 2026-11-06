# THUGSred Dorkify - shortcuts. `make help` lists them.
PORT ?= 8080

.PHONY: help up down logs build dev static lint

help:
	@echo "make up      - build and start the container (http://localhost:$(PORT))"
	@echo "make down    - stop and remove the container"
	@echo "make logs    - follow container logs"
	@echo "make build   - build the image only"
	@echo "make dev     - run locally with PHP's built-in server, no Docker"
	@echo "make static  - export a static site to dist/"
	@echo "make lint    - check the dork library"

up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f

build:
	docker compose build

dev:
	php -S 127.0.0.1:$(PORT) -t src/public

static:
	sh scripts/build-static.sh

lint:
	php scripts/lint-dorks.php
