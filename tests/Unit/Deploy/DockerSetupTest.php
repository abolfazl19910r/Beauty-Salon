<?php

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * ⭐ Docker (۲۰۲۶-۰۹-۲۶): هر مورد این فایل یک دلیل واقعی بود که stack داکر هرگز یک صفحه سرو نکرد. شبیه‌سازی
 * native همون container (nginx + php-fpm + supervisord با همین فایل‌ها و چیدمان pool رسمی image) قبل/بعد اجرا شد؛
 * این تست‌ها جلوی برگشتن بی‌صدای هر کدوم رو می‌گیرن. خود image در محیط Claude ساخته نشد (registry در دسترس نبود).
 */
class DockerSetupTest extends TestCase
{
    private static function file(string $path): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/'.$path);
    }

    public function test_the_app_boots_without_dev_dependencies(): void
    {
        // laravel/telescope در require-dev است؛ provider اپ فقط وقتی پکیج نصبه ثبت می‌شه (AppServiceProvider)
        $this->assertArrayHasKey('laravel/telescope', json_decode(self::file('composer.json'), true)['require-dev']);
        $this->assertStringNotContainsString('TelescopeServiceProvider', self::file('bootstrap/providers.php'));
        $this->assertStringContainsString('class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)', self::file('app/Providers/AppServiceProvider.php'));
    }

    public function test_the_composer_stage_does_not_need_runtime_extensions_but_the_image_is_checked(): void
    {
        $dockerfile = self::file('Dockerfile');
        $this->assertStringContainsString("--ignore-platform-req='ext-*'", $dockerfile);
        $this->assertStringContainsString('composer check-platform-reqs --no-dev', $dockerfile);
    }

    public function test_our_fpm_pool_is_loaded_after_the_official_images_pool_files(): void
    {
        preg_match('#COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/(\S+)#', self::file('Dockerfile'), $m);
        $files = ['docker.conf', 'www.conf', 'zz-docker.conf', $m[1]];
        sort($files, SORT_STRING);
        $this->assertSame($m[1], end($files), 'فایل آخر برنده است — وگرنه listen = 9000 و user = www-data');
        $this->assertStringContainsString('listen = /var/run/php-fpm/php-fpm.sock', self::file('docker/php/php-fpm.conf'));
    }

    public function test_nginx_loads_the_vhost_and_can_reach_the_fpm_socket(): void
    {
        $dockerfile = self::file('Dockerfile');
        $this->assertStringContainsString('include /etc/nginx/conf.d/*.conf;', self::file('docker/nginx/nginx.conf'));
        $this->assertStringContainsString('COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf', $dockerfile);
        $this->assertStringContainsString('addgroup nginx www', $dockerfile);
        $this->assertStringContainsString('listen.group = www', self::file('docker/php/php-fpm.conf'));
    }

    public function test_the_health_check_reaches_laravel_not_a_static_nginx_reply(): void
    {
        preg_match('/location = \/up \{(.*?)\n    \}/s', self::file('docker/nginx/default.conf'), $m);
        $this->assertStringContainsString('fastcgi_pass', $m[1]);
        $this->assertStringNotContainsString('return 200', $m[1]);
    }

    public function test_compose_serves_the_site_from_the_app_container_and_keeps_databases_private(): void
    {
        $compose = Yaml::parse(self::file('docker-compose.yml'));

        $this->assertArrayNotHasKey('nginx', $compose['services']);
        $this->assertArrayNotHasKey('beauty_public', $compose['volumes']);
        $this->assertSame(['${APP_PORT:-80}:80'], $compose['services']['app']['ports']);
        foreach (['mysql', 'redis', 'phpmyadmin'] as $service) {
            foreach ($compose['services'][$service]['ports'] as $port) {
                $this->assertStringStartsWith('127.0.0.1:', $port, "{$service} نباید به اینترنت باز باشه");
            }
        }
    }

    public function test_local_build_outputs_never_overwrite_the_image_build(): void
    {
        $ignored = array_map('trim', explode("\n", self::file('.dockerignore')));
        foreach (['vendor/', 'public/build/', 'public/storage', 'bootstrap/cache/*.php'] as $entry) {
            $this->assertContains($entry, $ignored);
        }
    }

    public function test_make_targets_run_artisan_as_www(): void
    {
        $makefile = self::file('Makefile');
        $this->assertStringNotContainsString('docker compose exec app php artisan', $makefile);
        $this->assertStringContainsString('ARTISAN := docker compose exec -u www app php artisan', $makefile);
        $this->assertStringContainsString('docker compose up -d --wait', $makefile);
    }

    public function test_the_image_does_not_install_packages_alpine_no_longer_ships(): void
    {
        // wkhtmltopdf از Alpine 3.15 حذف شد و «apk add» build را می‌شکست؛ PDF با mPDF است (۲۰۲۶-۱۰-۲۳)
        $this->assertDoesNotMatchRegularExpression('/^\s+wkhtmltopdf\s*\\\\$/m', self::file('Dockerfile'));
        $this->assertStringContainsString('FROM php:8.3-fpm-alpine AS production', self::file('Dockerfile'));
    }

    public function test_redis_never_evicts_queue_jobs_or_sessions(): void
    {
        $command = Yaml::parse(self::file('docker-compose.yml'))['services']['redis']['command'];
        $this->assertStringContainsString('--maxmemory-policy noeviction', $command);
        $this->assertStringNotContainsString('allkeys-lru', $command);
    }

    public function test_backup_db_reads_credentials_inside_the_mysql_container(): void
    {
        $makefile = self::file('Makefile');
        preg_match('/^backup-db:.*?(?=^\S)/ms', $makefile, $m);
        $this->assertStringNotContainsString('${DB_', $m[0], 'سینتکس shell در make متغیر خالی می‌شود');
        $this->assertStringContainsString('exec -T mysql', $m[0]);
        $this->assertStringContainsString('$$MYSQL_USER', $m[0]);
        $this->assertStringContainsString('test -s', $m[0]);
    }

    public function test_single_container_runs_every_service_under_supervisor(): void
    {
        $conf = self::file('docker/single/supervisord.conf');
        foreach (['mariadb', 'php-fpm', 'nginx', 'queue', 'queue-otp', 'scheduler'] as $program) {
            $this->assertStringContainsString("[program:{$program}]", $conf);
        }
        // همان ترتیب صف‌های App\Support\Queues::workerOrder
        $this->assertStringContainsString('--queue=otp,push,sms,payments,default,reports', $conf);
        $this->assertStringContainsString('skip-networking       = 0', self::file('docker/single/my.cnf'));
        $this->assertStringContainsString('bind-address          = 127.0.0.1', self::file('docker/single/my.cnf'));

        $compose = Yaml::parse(self::file('docker-compose.single.yml'));
        $this->assertSame('single', $compose['services']['app']['build']['target']);
        $this->assertSame(['mahru_data:/data'], $compose['services']['app']['volumes']);
        $this->assertSame(['${MAHRU_PORT:-8080}:80'], $compose['services']['app']['ports']);
        $this->assertSame(['.env.single'], $compose['services']['app']['env_file']);
        $this->assertStringContainsString('.env.single', self::file('.gitignore'));

        $entrypoint = self::file('docker/single/entrypoint.sh');
        $this->assertStringContainsString('migrate --force', $entrypoint);
        $this->assertStringContainsString('/data/app.key', str_replace('$DATA', '/data', $entrypoint));
    }

    public function test_demo_seeding_works_without_dev_dependencies(): void
    {
        // MAHRU_SEED=true در image (composer --no-dev): UserSeeder و factoryها Faker می‌خواهند (۲۰۲۶-۱۰-۲۳)
        $composer = json_decode(self::file('composer.json'), true);
        $this->assertArrayHasKey('fakerphp/faker', $composer['require']);
        $this->assertArrayNotHasKey('fakerphp/faker', $composer['require-dev']);
    }

    public function test_the_image_does_not_duplicate_vendor_in_a_chown_layer(): void
    {
        $dockerfile = self::file('Dockerfile');
        $this->assertStringContainsString('COPY --from=composer-builder --chown=www:www /app/vendor ./vendor', $dockerfile);
        $this->assertStringNotContainsString('chown -R www:www /var/www/html', $dockerfile);
        $this->assertStringContainsString('apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS', $dockerfile);
    }

    public function test_slow_networks_can_build_with_a_prebuilt_vendor(): void
    {
        // اولین build روی Docker Desktop ابوالفضل (۲۰۲۶-۱۰-۲۳): دانلود GitHub داخل داکر چند KB/s، git clone از سقف ۳۰۰ ثانیه گذشت
        $dockerfile = self::file('Dockerfile');
        $this->assertStringContainsString('COPY composer.json composer.lock build-vendo[r] /app/src/', $dockerfile);
        $this->assertStringContainsString('if [ -f /app/src/autoload.php ]', $dockerfile);
        $this->assertStringContainsString('--mount=type=cache,target=/tmp/cache', $dockerfile);
        $this->assertStringContainsString('COMPOSER_PROCESS_TIMEOUT=0', $dockerfile);
        $this->assertStringContainsString('/build-vendor', self::file('.gitignore'));
        $this->assertNotContains('build-vendor', array_map('trim', explode("\n", self::file('.dockerignore'))));
    }

    public function test_secrets_and_uploads_stay_out_of_the_image(): void
    {
        // storage/app/private کلید فایربیس و APKهای آپلودی را داشت و با «COPY . .» داخل image می‌رفت
        $ignored = array_map('trim', explode("\n", self::file('.dockerignore')));
        $this->assertContains('storage/app/*', $ignored);
        $this->assertContains('*.apk', $ignored);
        $this->assertStringContainsString('storage/app/public storage/app/private', self::file('docker/entrypoint.sh'));
    }

    public function test_scripts_survive_windows_line_endings(): void
    {
        $this->assertStringContainsString("sed -i 's/\\r$//' /usr/local/bin/entrypoint.sh", self::file('Dockerfile'));
        $this->assertStringContainsString('eol=lf', self::file('.gitattributes'));
    }

    public function test_the_server_runs_the_transferred_image_and_never_builds_or_pulls(): void
    {
        // سرور آروان (۲۰۲۶-۱۰-۲۴): image روی ویندوز ساخته و با docker save/load منتقل می‌شود؛ روی سرور build و رجیستری نیست
        $app = Yaml::parse(self::file('docker-compose.server.yml'))['services']['app'];
        $this->assertArrayNotHasKey('build', $app);
        $this->assertSame('never', $app['pull_policy']);
        $this->assertStringStartsWith('mahru:single-${MAHRU_VERSION:?', $app['image']);
        $this->assertSame(['.env.server'], $app['env_file']);
        $this->assertSame(['80:80'], $app['ports']);
        $this->assertSame(['mahru_data:/data', './backups:/data/backups'], $app['volumes']);
        $this->assertSame('20m', $app['logging']['options']['max-size']);
        $this->assertStringContainsString('.env.server', self::file('.gitignore'));

        $ignored = array_map('trim', explode("\n", self::file('.dockerignore')));
        $this->assertContains('build-images/', $ignored);
        $this->assertContains('*.tar', $ignored);

        $build = self::file('deploy/single/build-image.cmd');
        $this->assertStringContainsString('docker save mahru:single-%V%', $build);
        $this->assertStringContainsString('*.cmd text eol=crlf', self::file('.gitattributes'));

        $deploy = self::file('deploy/single/server-deploy.sh');
        $this->assertStringContainsString('docker load -i', $deploy);
        $this->assertStringContainsString('docker exec -u www mahru mahru-backup now', $deploy);
        $this->assertMatchesRegularExpression('/mahru-backup now \|\| die/', $deploy);
    }

    public function test_server_env_is_production_without_real_sms_or_demo_data(): void
    {
        $env = self::file('.env.server.example');
        foreach ([
            'APP_ENV=production', 'APP_DEBUG=false', 'APP_URL=https://arisheh.ir', 'CENTRAL_DOMAIN=arisheh.ir',
            'SESSION_DOMAIN=.arisheh.ir', 'SESSION_SECURE_COOKIE=true', 'MAHRU_SEED=false', 'SMS_LOG_ONLY=true',
            'ZARINPAL_SANDBOX=true', 'BACKUP_AT=03:40', 'BACKUP_KEEP=3', 'TELESCOPE_ENABLED=false',
        ] as $line) {
            $this->assertMatchesRegularExpression('/^'.preg_quote($line, '/').'$/m', $env, $line);
        }
        // کلید و رمز را کانتینر می‌سازد؛ خط خالی بر مقدار ساخته‌شده مقدم می‌شد
        $this->assertDoesNotMatchRegularExpression('/^(APP_KEY|DB_PASSWORD)=/m', $env);
        // پیش‌فرض کد (cloudflare) برای آروان غلط است و «*» هرگز
        $this->assertMatchesRegularExpression('/^TRUSTED_PROXIES=$/m', $env);
        $this->assertStringContainsString('/en/ips.txt', self::file('deploy/single/arvan-ips.sh'));
    }

    public function test_the_single_container_backs_up_database_files_and_app_key_every_night(): void
    {
        $conf = self::file('docker/single/supervisord.conf');
        $this->assertMatchesRegularExpression('/\[program:backup\]\n(;[^\n]*\n)*command=\/usr\/local\/bin\/mahru-backup loop\nuser=www\n/', $conf);

        $dockerfile = self::file('Dockerfile');
        $this->assertStringContainsString('apk add --no-cache mariadb mariadb-client bash', $dockerfile);
        $this->assertStringContainsString('COPY docker/single/backup.sh /usr/local/bin/mahru-backup', $dockerfile);

        $backup = self::file('docker/single/backup.sh');
        $this->assertStringContainsString('BACKUP_APP_KEY_FILE=/data/app.key', $backup);
        $this->assertStringContainsString('KEEP_LAST="${BACKUP_KEEP:-3}"', $backup);
        $this->assertStringContainsString('/var/www/html /data/backups', $backup);

        // اسکریپت مشترک DirectAdmin: محیط کانتینر بر .env مقدم، و app.key و نگه‌داری N پشتیبان آخر
        $script = self::file('deploy/backup/mahru-backup.sh');
        $this->assertStringContainsString('local value="${!1:-}"', $script);
        $this->assertStringContainsString('DB_PASSWORD="$(setting DB_PASSWORD)"', $script);
        $this->assertStringContainsString('cp "$BACKUP_APP_KEY_FILE" "$DEST/app.key"', $script);
        $this->assertStringContainsString('tail -n +"$((KEEP_LAST + 1))"', $script);

        $entrypoint = self::file('docker/single/entrypoint.sh');
        $this->assertStringContainsString('chmod 700 "$DATA/backups"', $entrypoint);
        $this->assertStringContainsString('chown www:www "$DATA/app.key"', $entrypoint);
    }
}
