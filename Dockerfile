# ============================================================
# Beauty Salon - Dockerfile
# PHP 8.3 + Nginx + Node.js (multi-stage build)
#
# دو target:
#   production (پیش‌فرض docker-compose.yml): فقط برنامه؛ MySQL/Redis/workerها سرویس‌های جدای compose
#   single (docker-compose.single.yml): همه‌چیز در یک کانتینر با supervisord — nginx، php-fpm، MariaDB، دو worker صف و
#           scheduler؛ داده‌ها روی volume /data (بسته‌ی Docker، ۲۰۲۶-۱۰-۲۳)
# ============================================================

# ─── Stage 1: Node.js — build frontend assets ───────────────
FROM node:22-alpine AS frontend-builder

WORKDIR /app

# فقط package files رو کپی کن اول (layer cache)
COPY package.json package-lock.json ./

RUN npm ci --frozen-lockfile

# سورس کامل رو کپی کن برای build
COPY resources/ resources/
COPY vite.config.js tailwind.config.cjs postcss.config.cjs ./
COPY public/ public/

RUN npm run build


# ─── Stage 2: PHP dependencies ──────────────────────────────
FROM composer:2.7 AS composer-builder

WORKDIR /app

# composer.json/lock و (اگر هست) build-vendor/ همه در /app/src: الگوی «build-vendo[r]» وقتی پوشه نیست خطا نمی‌دهد چون
# composer.json هم‌زمان کپی می‌شود. محتوای پوشه (نه خودش) کپی می‌شود: vendor آماده = /app/src/autoload.php.
COPY composer.json composer.lock build-vendo[r] /app/src/

# دو راه (۲۰۲۶-۱۰-۲۳، اولین build روی Docker Desktop ابوالفضل: دانلود از codeload.github.com داخل VM داکر چند KB/s بود،
# fallback به «git clone» از سقف ۳۰۰ ثانیه‌ی کامپوزر گذشت و build شکست):
#   ۱. vendor آماده روی همان سیستم (کامپوزر میزبان با cache/VPN خودش) در build-vendor/ — راهنما: docs/deployment/DOCKER_DESKTOP.md
#   ۲. وگرنه composer install؛ cache کامپوزر بین buildها می‌ماند (بسته‌های تمام‌شده دوباره دانلود نمی‌شوند) و سقف زمانی git برداشته شد.
# ⚠️ image composer افزونه‌هایی مثل gd/intl/bcmath رو نداره (mpdf و phpspreadsheet ext-gd می‌خوان) و بدون
# --ignore-platform-req این مرحله شکست می‌خورد. افزونه‌ها در image نهایی نصب و همون‌جا با check-platform-reqs چک می‌شن.
ENV COMPOSER_PROCESS_TIMEOUT=0
RUN --mount=type=cache,target=/tmp/cache \
    mv /app/src/composer.json /app/src/composer.lock /app/ \
    && if [ -f /app/src/autoload.php ]; then \
        echo "Using prebuilt build-vendor/" && mv /app/src /app/vendor \
        && composer dump-autoload --no-dev --optimize --no-scripts --no-interaction; \
    else \
        rm -rf /app/src && composer install \
            --no-dev \
            --no-interaction \
            --no-scripts \
            --prefer-dist \
            --optimize-autoloader \
            --ignore-platform-req='ext-*'; \
    fi


# ─── Stage 3: Final image ───────────────────────────────────
FROM php:8.3-fpm-alpine AS production

LABEL maintainer="Beauty Salon <info@beautysalon.ir>"
LABEL description="Mahru - salon booking SaaS (Laravel 11)"

# ─── System dependencies ────────────────────────────────────
RUN apk add --no-cache \
    # Nginx
    nginx \
    # Process manager
    supervisor \
    # اجرای artisan / queue / scheduler با کاربر www (نه root) — docker/entrypoint.sh
    su-exec \
    # PHP extensions dependencies
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    # Git و curl
    git \
    curl \
    # Timezone
    tzdata \
    # Font support برای PDF فارسی (PDF گزارش‌ها با mPDF ساخته می‌شود؛ wkhtmltopdf از Alpine 3.15 به بعد بسته ندارد و
    # «apk add wkhtmltopdf» build را می‌شکست — پکیج snappy هم هیچ‌جای کد استفاده نمی‌شود)
    fontconfig \
    ttf-dejavu

# ─── PHP Extensions ─────────────────────────────────────────
# ⚠️ ابزار کامپایل ($PHPIZE_DEPS) صریحاً با نام مجازی «.phpize-deps» (۲۰۲۶-۱۰-۲۳): docker-php-ext-install در پایان ابزار
# کامپایلی را که خودش نصب کرده پاک می‌کند، پس «pecl install redis» بعد از آن بی autoconf/phpize می‌شکست. با همین نام،
# اسکریپت‌های image دیگر خودشان نصب/پاک نمی‌کنند و ما در آخر یک بار پاک می‌کنیم.
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps \
    && rm -rf /tmp/pear

# ─── Timezone ───────────────────────────────────────────────
RUN ln -snf /usr/share/zoneinfo/Asia/Tehran /etc/localtime \
    && echo "Asia/Tehran" > /etc/timezone

# ─── PHP-FPM config ─────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/beauty-salon.ini
# ⚠️ نام zzz- عمداً: image رسمی php-fpm.d/www.conf (user = www-data, listen = 127.0.0.1:9000) و zz-docker.conf
# (listen = 9000) رو داره و همه‌ی فایل‌ها به ترتیب حروف خونده می‌شن؛ همون pool [www] رو هر فایل بعدی override
# می‌کنه. با نام قبلی (beauty-salon.conf) فایل ما اول خونده می‌شد → php-fpm روی TCP 9000 با www-data گوش می‌داد،
# سوکتی که nginx بهش وصل می‌شه هیچ‌وقت ساخته نمی‌شد (502) و worker ها در storage مال www نمی‌تونستن بنویسن.
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-beauty-salon.conf

# ─── Nginx config ───────────────────────────────────────────
# ⚠️ default.conf باید در conf.d باشه: nginx.conf خودمون «include /etc/nginx/conf.d/*.conf» داره (نه http.d
# پیش‌فرض alpine). قبلاً در http.d کپی می‌شد → nginx هیچ server blockی نداشت و روی هیچ پورتی گوش نمی‌داد.
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/security-headers.conf /etc/nginx/security-headers.conf
RUN rm -f /etc/nginx/http.d/default.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# ─── Supervisor config ──────────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ─── App user ───────────────────────────────────────────────
RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www \
    && addgroup nginx www   # worker های nginx (کاربر nginx) به سوکت php-fpm با mode 0660 و گروه www دسترسی دارن

# ─── App directory ──────────────────────────────────────────
WORKDIR /var/www/html

# کپی vendor از composer-builder
# --chown همین‌جا: «chown -R» جدا بعداً کل vendor را در یک لایه‌ی تازه دوباره کپی می‌کرد (image دو برابر)
COPY --from=composer-builder --chown=www:www /app/vendor ./vendor

# افزونه‌هایی که composer-builder نادیده گرفت، اینجا (با PHP واقعی image) واقعاً باید باشن
COPY --from=composer-builder /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer check-platform-reqs --no-dev --no-interaction

# کپی asset های build شده از frontend-builder
COPY --from=frontend-builder --chown=www:www /app/public/build ./public/build

# کپی سورس پروژه
COPY --chown=www:www . .

# کپی فونت‌های فارسی برای PDF
RUN mkdir -p /var/www/html/storage/fonts \
    && cp storage/fonts/* /var/www/html/storage/fonts/ 2>/dev/null || true

# ─── Permissions ─────────────────────────────────────────────
# فقط پوشه‌های قابل‌نوشتن (بقیه با COPY --chown مال www شده‌اند؛ chown -R کل پروژه vendor را در لایه‌ای تازه تکرار می‌کرد)
RUN chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ─── Entrypoint ──────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
# ⚠️ اگر git ویندوز فایل‌ها را با CRLF بیرون آورده باشد، «#!/bin/sh\r» در لینوکس پیدا نمی‌شود و کانتینر با «not found» می‌افتد
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh /etc/supervisor/conf.d/supervisord.conf /etc/nginx/nginx.conf \
        /etc/nginx/conf.d/default.conf /etc/nginx/security-headers.conf /usr/local/etc/php-fpm.d/zzz-beauty-salon.conf /usr/local/etc/php/conf.d/beauty-salon.ini \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]


# ─── Target «single»: همه‌چیز در یک کانتینر ──────────────────
# docker compose -f docker-compose.single.yml up -d --build
# MariaDB (بسته‌ی رسمی Alpine) فقط روی 127.0.0.1؛ صف، cache و session روی همان دیتابیس (بدون Redis). همه‌ی داده‌ها روی /data:
# دیتابیس، storage/app (آپلودها، کلید فایربیس)، لاگ‌ها و کلید APP_KEY ساخته‌شده.
FROM production AS single

# bash: deploy/backup/mahru-backup.sh (پشتیبان شبانه‌ی داخل کانتینر، ۲۰۲۶-۱۰-۲۴)
RUN apk add --no-cache mariadb mariadb-client bash

COPY docker/single/supervisord.conf /etc/supervisor/conf.d/supervisord-single.conf
COPY docker/single/my.cnf /etc/my.cnf.d/zz-mahru.cnf
COPY docker/single/entrypoint.sh /usr/local/bin/entrypoint-single.sh
COPY docker/single/wait-db.sh /usr/local/bin/wait-db
COPY docker/single/backup.sh /usr/local/bin/mahru-backup
RUN sed -i 's/\r$//' /etc/supervisor/conf.d/supervisord-single.conf /etc/my.cnf.d/zz-mahru.cnf \
        /usr/local/bin/entrypoint-single.sh /usr/local/bin/wait-db /usr/local/bin/mahru-backup \
        /var/www/html/deploy/backup/mahru-backup.sh \
    && chmod +x /usr/local/bin/entrypoint-single.sh /usr/local/bin/wait-db /usr/local/bin/mahru-backup \
    && ln -sf /etc/supervisor/conf.d/supervisord-single.conf /etc/supervisord.conf   # «supervisorctl status» بدون -c

# پیش‌فرض‌های حالت تک‌کانتینری؛ .env.single (env_file) می‌تواند هر کدام را عوض کند
ENV MAHRU_SINGLE=1 \
    DB_CONNECTION=mysql \
    DB_HOST=127.0.0.1 \
    DB_PORT=3306 \
    DB_DATABASE=mahru \
    DB_USERNAME=mahru \
    QUEUE_CONNECTION=database \
    CACHE_STORE=database \
    SESSION_DRIVER=database \
    QUEUE_WORK_VIA_SCHEDULER=false \
    FILESYSTEM_DISK=public \
    BROADCAST_CONNECTION=log

VOLUME ["/data"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -fsS http://localhost/up >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint-single.sh"]
