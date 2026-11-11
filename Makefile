# ─────────────────────────────────────────────────────────────────────────────
# Makefile — atajos para el proyecto Nuclear (todo corre dentro de Docker)
# Uso: make <target>
# ─────────────────────────────────────────────────────────────────────────────

DC        = docker compose
DC_API    = $(DC) run --rm api
DC_PWA    = $(DC) run --rm pwa

.PHONY: help install up down restart logs \
        key migrate fresh seed \
        test test-unit test-arch lint \
        pwa-install pwa-build

# ─── Ayuda ───────────────────────────────────────────────────────────────────
help:
	@echo ""
	@echo "  make install      Primera vez: construye imágenes, instala deps y configura"
	@echo "  make up           Levanta todos los servicios en segundo plano"
	@echo "  make down         Detiene y elimina los contenedores"
	@echo "  make restart      Reinicia todos los servicios"
	@echo "  make logs         Sigue los logs de todos los servicios"
	@echo ""
	@echo "  make key          Genera APP_KEY de Laravel"
	@echo "  make migrate      Ejecuta las migraciones pendientes"
	@echo "  make fresh        Borra la BD y re-ejecuta todas las migraciones + seeders"
	@echo "  make seed         Ejecuta los seeders"
	@echo ""
	@echo "  make test         Ejecuta toda la suite (Pest)"
	@echo "  make test-unit    Solo las pruebas unitarias del dominio (sin BD)"
	@echo "  make test-arch    Pruebas de arquitectura (Deptrac)"
	@echo "  make lint         Análisis estático (PHPStan nivel 8)"
	@echo ""
	@echo "  make pwa-install  Instala dependencias del frontend"
	@echo "  make pwa-build    Compila la PWA para producción"
	@echo ""

# ─── Setup inicial ────────────────────────────────────────────────────────────
install: build
	@echo ">> Instalando dependencias PHP..."
	$(DC_API) composer install --no-interaction
	@echo ">> Copiando .env..."
	cp -n .env.example backend/.env || true
	@echo ">> Generando APP_KEY..."
	$(DC_API) php artisan key:generate
	@echo ">> Ejecutando migraciones iniciales..."
	$(DC) up -d db
	sleep 3
	$(DC_API) php artisan migrate --no-interaction
	@echo ">> Instalando dependencias frontend..."
	$(DC_PWA) npm install
	@echo ""
	@echo "  Listo. Ejecuta: make up"
	@echo "  API:  http://localhost:8080"
	@echo "  PWA:  http://localhost:5173"
	@echo ""

build:
	$(DC) build --no-cache

# ─── Ciclo de vida ────────────────────────────────────────────────────────────
up:
	$(DC) up -d

down:
	$(DC) down

restart:
	$(DC) restart

logs:
	$(DC) logs -f

# ─── Base de datos ────────────────────────────────────────────────────────────
key:
	$(DC_API) php artisan key:generate

migrate:
	$(DC_API) php artisan migrate --no-interaction

fresh:
	$(DC_API) php artisan migrate:fresh --seed --no-interaction

seed:
	$(DC_API) php artisan db:seed --no-interaction

# ─── Pruebas y calidad ────────────────────────────────────────────────────────
test:
	$(DC) -f docker-compose.yml -f docker-compose.test.yml run --rm api \
		./vendor/bin/pest --colors=always

test-unit:
	$(DC) -f docker-compose.yml -f docker-compose.test.yml run --rm api \
		./vendor/bin/pest --group=domain --colors=always

test-arch:
	$(DC_API) ./vendor/bin/deptrac analyse --no-interaction

lint:
	$(DC_API) ./vendor/bin/phpstan analyse --memory-limit=512M

# ─── Frontend ─────────────────────────────────────────────────────────────────
pwa-install:
	$(DC_PWA) npm install

pwa-build:
	$(DC_PWA) npm run build
