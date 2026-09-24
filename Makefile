# ============================================================
# Beauty Salon - Makefile
# دستورات سریع برای کار با Docker
# ============================================================

.PHONY: help build up down restart logs logs-scheduler queue-restart shell db-shell redis-shell migrate seed fresh status

# ─── رنگ‌بندی ─────────────────────────────────────────────
GREEN  := \033[0;32m
YELLOW := \033[1;33m
CYAN   := \033[0;36m
RESET  := \033[0m

help: ## نمایش راهنما
	@echo ""
	@echo "$(CYAN)🌸 Beauty Salon Docker Commands$(RESET)"
	@echo "────────────────────────────────────────"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ─── اولین راه‌اندازی ────────────────────────────────────
setup: ## راه‌اندازی اولیه (copy env + key + build + up)
	@cp -n .env.docker .env || true
	@# APP_KEY قبل از بالا آمدن containerها ساخته می‌شه: .env داخل image نیست (dockerignore) و app/queue/scheduler
	@# همه از همین .env (env_file) می‌خونن؛ key:generate داخل container فایلی برای نوشتن نداشت.
	@grep -q '^APP_KEY=base64:' .env || sed -i "s|^APP_KEY=.*|APP_KEY=base64:$$(openssl rand -base64 32)|" .env
	@echo "$(YELLOW)⚙️  Building images...$(RESET)"
	docker compose build --no-cache
	@echo "$(YELLOW)🚀 Starting containers...$(RESET)"
	docker compose up -d
	@echo "$(GREEN)✅ Setup complete! Visit: http://localhost$(RESET)"

# ─── Build & Start ────────────────────────────────────────
build: ## Build ایمیج‌ها
	docker compose build

up: ## اجرای همه سرویس‌ها
	docker compose up -d

up-dev: ## اجرا با phpMyAdmin (dev mode)
	docker compose --profile dev up -d

down: ## خاموش کردن همه سرویس‌ها
	docker compose down

stop: ## متوقف کردن (بدون حذف container)
	docker compose stop

restart: ## ری‌استارت همه سرویس‌ها
	docker compose restart

restart-app: ## ری‌استارت فقط app
	docker compose restart app queue scheduler

# ─── Logs ─────────────────────────────────────────────────
logs: ## لاگ همه سرویس‌ها
	docker compose logs -f

logs-app: ## لاگ فقط app
	docker compose logs -f app

logs-queue: ## لاگ queue worker
	docker compose logs -f queue

logs-scheduler: ## لاگ scheduler (کارهای زمان‌بندی‌شده)
	docker compose logs -f scheduler

queue-restart: ## بعد از هر deploy: worker کد جدید رو بخونه
	docker compose exec queue php artisan queue:restart

logs-nginx: ## لاگ nginx
	docker compose logs -f nginx

# ─── Shell ────────────────────────────────────────────────
shell: ## ورود به shell اپ
	docker compose exec app sh

db-shell: ## ورود به MySQL
	docker compose exec mysql mysql -u${DB_USERNAME:-beauty_user} -p${DB_PASSWORD:-secret} ${DB_DATABASE:-beauty_salon}

redis-shell: ## ورود به Redis CLI
	docker compose exec redis redis-cli

# ─── Laravel Artisan ─────────────────────────────────────
migrate: ## اجرای migrations
	docker compose exec app php artisan migrate --force

migrate-fresh: ## ریست کامل دیتابیس + migration
	docker compose exec app php artisan migrate:fresh --force

seed: ## اجرای seeders
	docker compose exec app php artisan db:seed --force

fresh-seed: ## migrate:fresh + seed (محیط dev)
	docker compose exec app php artisan migrate:fresh --seed --force

tinker: ## باز کردن Tinker
	docker compose exec app php artisan tinker

cache-clear: ## پاک کردن همه کش‌ها
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

cache-optimize: ## cache برای production
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache
	docker compose exec app php artisan event:cache

# ─── Status ───────────────────────────────────────────────
status: ## وضعیت همه سرویس‌ها
	docker compose ps

ps: status

# ─── Cleanup ──────────────────────────────────────────────
clean: ## حذف container ها (volume ها باقی می‌مانند)
	docker compose down --remove-orphans

clean-all: ## حذف همه چیز شامل volumes (خطرناک!)
	@echo "$(YELLOW)⚠️  این کار همه داده‌ها را حذف می‌کند!$(RESET)"
	@read -p "مطمئنی؟ [y/N] " confirm && [ "$$confirm" = "y" ]
	docker compose down -v --remove-orphans

# ─── Backup ───────────────────────────────────────────────
backup-db: ## پشتیبان‌گیری از دیتابیس
	@mkdir -p backups
	docker compose exec mysql mysqldump \
		-u${DB_USERNAME:-beauty_user} \
		-p${DB_PASSWORD:-secret} \
		${DB_DATABASE:-beauty_salon} \
		> backups/beauty_salon_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✅ Backup saved to backups/$(RESET)"
