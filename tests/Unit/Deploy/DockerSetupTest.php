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
}
