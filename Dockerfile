# ============================================================
# Beauty Salon - Dockerfile
# PHP 8.2 + Nginx + Node.js (multi-stage build)
# ============================================================

# ─── Stage 1: Node.js — build frontend assets ───────────────
FROM node:20-alpine AS frontend-builder

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

COPY composer.json composer.lock ./

# فقط dependencies بدون autoload dev.
# ⚠️ image composer افزونه‌هایی مثل gd/intl/bcmath رو نداره (mpdf و phpspreadsheet ext-gd می‌خوان) و بدون این
# گزینه همین مرحله شکست می‌خورد. افزونه‌ها در image نهایی نصب می‌شن و همون‌جا با check-platform-reqs چک می‌شن.
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-req='ext-*'


# ─── Stage 3: Final image ───────────────────────────────────
FROM php:8.2-fpm-alpine AS production

LABEL maintainer="Beauty Salon <info@beautysalon.ir>"
LABEL description="Beauty Salon Management System - Laravel 11"

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
    # PDF (wkhtmltopdf برای snappy)
    wkhtmltopdf \
    # Git و curl
    git \
    curl \
    # Timezone
    tzdata \
    # Font support برای PDF فارسی
    fontconfig \
    ttf-dejavu

# ─── PHP Extensions ─────────────────────────────────────────
RUN docker-php-ext-configure gd \
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
    && docker-php-ext-enable redis

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
COPY --from=composer-builder /app/vendor ./vendor

# افزونه‌هایی که composer-builder نادیده گرفت، اینجا (با PHP واقعی image) واقعاً باید باشن
COPY --from=composer-builder /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer check-platform-reqs --no-dev --no-interaction

# کپی asset های build شده از frontend-builder
COPY --from=frontend-builder /app/public/build ./public/build

# کپی سورس پروژه
COPY --chown=www:www . .

# کپی فونت‌های فارسی برای PDF
RUN mkdir -p /var/www/html/storage/fonts \
    && cp storage/fonts/* /var/www/html/storage/fonts/ 2>/dev/null || true

# ─── Permissions ─────────────────────────────────────────────
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# ─── Entrypoint ──────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
