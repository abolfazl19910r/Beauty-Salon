<?php

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * deploy/single/tls-reload.sh (reloadcmd ‏acme.sh روی میزبان، ۲۰۲۶-۱۱-۱۱) با یک docker ساختگی که هر فراخوانی را ثبت می‌کند.
 * اولین گواهی: nginx-setup فقط هنگام شروع کانتینر tls.conf را روشن می‌کند ← docker restart (نه up -d، که بی تغییر کاری نمی‌کند،
 * و نه force-recreate، که تغییرهای docker cp را دور می‌ریزد). تمدید: nginx -t و reload بی قطعی.
 */
class TlsReloadTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/mahru-tls-'.uniqid();
        mkdir($this->dir.'/tls', 0777, true);
        file_put_contents($this->dir.'/tls/fullchain.pem', 'CERT');
        file_put_contents($this->dir.'/tls/privkey.pem', 'KEY');
        chmod($this->dir.'/tls/privkey.pem', 0644);
    }

    protected function tearDown(): void
    {
        shell_exec('rm -rf '.escapeshellarg($this->dir));
        parent::tearDown();
    }

    /** docker ساختگی: running / tls.conf هست / nginx -t قبول از متغیرها */
    private function reload(bool $running, bool $httpsOn, bool $configOk = true): Process
    {
        $fake = $this->dir.'/docker';
        file_put_contents($fake, <<<'SH'
            #!/usr/bin/env bash
            echo "$*" >> "$LOG"
            case "$1 $2" in
              "inspect -f")
                if [[ "$3" == *Running* ]]; then echo "$FAKE_RUNNING"; else echo healthy; fi ;;
              "exec mahru")
                case "$3 $4" in
                  "test -f") [[ "$FAKE_HTTPS" == 1 ]] ;;
                  "nginx -t") [[ "$FAKE_CONFIG_OK" == 1 ]] ;;
                  *) true ;;
                esac ;;
              *) true ;;
            esac
            SH);
        chmod($fake, 0755);

        $process = new Process(['bash', dirname(__DIR__, 3).'/deploy/single/tls-reload.sh'], null, [
            'TLS_DIR' => $this->dir.'/tls', 'DOCKER' => $fake, 'LOG' => $this->dir.'/calls.log',
            'FAKE_RUNNING' => $running ? 'true' : 'false', 'FAKE_HTTPS' => $httpsOn ? '1' : '0', 'FAKE_CONFIG_OK' => $configOk ? '1' : '0',
        ]);
        $process->run();

        return $process;
    }

    private function calls(): string
    {
        return (string) @file_get_contents($this->dir.'/calls.log');
    }

    public function test_the_first_certificate_restarts_the_container_so_nginx_turns_on_443(): void
    {
        $run = $this->reload(running: true, httpsOn: false);

        $this->assertSame(0, $run->getExitCode(), $run->getErrorOutput());
        $this->assertStringContainsString("restart mahru\n", $this->calls());
        $this->assertStringNotContainsString('nginx -s reload', $this->calls());
        $this->assertStringNotContainsString('compose', $this->calls());
        $this->assertSame('600', substr(sprintf('%o', fileperms($this->dir.'/tls/privkey.pem')), -3), 'کلید خصوصی فقط root');
    }

    public function test_a_renewal_reloads_nginx_without_downtime(): void
    {
        $run = $this->reload(running: true, httpsOn: true);

        $this->assertSame(0, $run->getExitCode(), $run->getErrorOutput());
        $this->assertStringContainsString("exec mahru nginx -t -q\n", $this->calls());
        $this->assertStringContainsString("exec mahru nginx -s reload\n", $this->calls());
        $this->assertStringNotContainsString('restart', $this->calls());
    }

    public function test_a_certificate_nginx_rejects_is_not_loaded(): void
    {
        $run = $this->reload(running: true, httpsOn: true, configOk: false);

        $this->assertSame(1, $run->getExitCode());
        $this->assertStringNotContainsString('nginx -s reload', $this->calls());
        $this->assertStringNotContainsString('restart', $this->calls());
    }

    public function test_a_stopped_container_is_left_alone(): void
    {
        $run = $this->reload(running: false, httpsOn: false);

        $this->assertSame(0, $run->getExitCode(), $run->getErrorOutput());
        $this->assertStringNotContainsString('restart', $this->calls());
        $this->assertStringNotContainsString('exec', $this->calls());
    }

    public function test_missing_certificate_files_fail_loudly(): void
    {
        unlink($this->dir.'/tls/privkey.pem');

        $this->assertSame(1, $this->reload(running: true, httpsOn: true)->getExitCode());
        $this->assertSame('', $this->calls());
    }

    public function test_the_guide_installs_the_certificate_with_this_hook_and_auto_renewal(): void
    {
        $guide = (string) file_get_contents(dirname(__DIR__, 3).'/docs/deployment/ARVAN_SERVER.md');
        foreach ([
            '--dns dns_arvan -d arisheh.ir -d \'*.arisheh.ir\'',
            '--reloadcmd "bash /srv/mahru/deploy/single/tls-reload.sh"',
            '--fullchain-file /srv/mahru/tls/fullchain.pem',
            '--key-file /srv/mahru/tls/privkey.pem',
            '--staging',
            'crontab -l | grep acme',
        ] as $needle) {
            $this->assertStringContainsString($needle, $guide, $needle);
        }
        // up -d بی تغییر کانتینر را دوباره نمی‌سازد و nginx-setup دوباره اجرا نمی‌شد (راهنمای پیشین)
        $this->assertStringNotContainsString('# بار اول: nginx با دیدن گواهی ۴۴۳ را روشن می‌کند', $guide);
    }
}
