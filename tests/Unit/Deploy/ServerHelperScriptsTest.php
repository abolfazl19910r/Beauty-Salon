<?php

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * ۲۰۲۶-۱۱-۱۱: دستور «mahru» روی سرور (deploy/single/mahru.sh) و کشیدن هفتگی پشتیبان به ویندوز (deploy/backup/pull-backup.ps1).
 * mahru با docker ساختگی؛ pull-backup فقط اگر PowerShell (pwsh) هست، با ssh/scp ساختگی — همان آزمونی که پیش از تحویل دو باگ واقعی
 * را گرفت: تابع «Ssh» خود ssh.exe را صدا می‌زد (نام‌ها در PowerShell بی حساسیت به حروف‌اند) و هشدار stderr ‏ssh خط checksum شمرده می‌شد.
 */
class ServerHelperScriptsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/mahru-helpers-'.uniqid();
        mkdir($this->dir.'/bin', 0777, true);
    }

    protected function tearDown(): void
    {
        shell_exec('rm -rf '.escapeshellarg($this->dir));
        parent::tearDown();
    }

    private static function root(string $path): string
    {
        return dirname(__DIR__, 3).'/'.$path;
    }

    private function fake(string $name, string $body): void
    {
        file_put_contents($this->dir.'/bin/'.$name, "#!/usr/bin/env bash\n".$body);
        chmod($this->dir.'/bin/'.$name, 0755);
    }

    private function mahru(string ...$args): Process
    {
        $this->fake('docker', <<<'SH'
            echo "$*" >> "$LOG"
            [[ "$1" == inspect ]] && echo healthy
            exit 0
            SH);
        $p = new Process(['bash', self::root('deploy/single/mahru.sh'), ...$args], null, [
            'PATH' => $this->dir.'/bin:'.getenv('PATH'), 'LOG' => $this->dir.'/calls.log', 'MAHRU_DIR' => $this->dir,
        ]);
        $p->run();

        return $p;
    }

    private function calls(): string
    {
        return (string) @file_get_contents($this->dir.'/calls.log');
    }

    public function test_mahru_help_lists_the_everyday_commands(): void
    {
        $run = $this->mahru();
        $this->assertSame(0, $run->getExitCode());
        foreach (['status', 'logs', 'errors', 'sms', 'backups', 'backup', 'restart', 'recreate', 'artisan', 'cert'] as $cmd) {
            $this->assertStringContainsString('mahru '.$cmd, $run->getOutput());
        }
        $this->assertSame(2, $this->mahru('nonsense')->getExitCode());
    }

    public function test_mahru_maps_to_the_same_docker_commands_as_the_guide(): void
    {
        $this->mahru('restart');
        $this->assertStringContainsString("restart mahru\n", $this->calls());

        $this->mahru('recreate');
        $this->assertStringContainsString('compose -f '.$this->dir.'/docker-compose.server.yml up -d --force-recreate', $this->calls());

        $this->mahru('backup');
        $this->assertStringContainsString('exec -u www mahru mahru-backup now', $this->calls(), 'همان دستور server-deploy.sh');

        $this->mahru('artisan', 'superadmin:create', '--help');
        $this->assertStringContainsString('exec -it -u www mahru php artisan superadmin:create --help', $this->calls());

        $this->mahru('sms', '5');
        $this->assertStringContainsString('logs --since 5m mahru', $this->calls());
        $this->assertStringContainsString("grep -h 'production\\.ERROR'", (string) file_get_contents(self::root('deploy/single/mahru.sh')));
    }

    public function test_pull_backup_copies_verifies_skips_and_prunes(): void
    {
        $pwsh = getenv('PWSH') ?: trim((string) shell_exec('command -v pwsh 2>/dev/null'));
        if ($pwsh === '' || ! is_executable($pwsh)) {
            $this->markTestSkipped('PowerShell (pwsh) نصب نیست — PWSH=/path/to/pwsh');
        }

        mkdir($this->dir.'/remote/20261110_034000', 0777, true);
        mkdir($this->dir.'/remote/20261111_034000', 0777, true);
        file_put_contents($this->dir.'/remote/20261111_034000/database.sql.gz.age', 'db');
        file_put_contents($this->dir.'/remote/20261111_034000/storage-app.tar.gz.age', 'files');
        $this->fake('ssh', <<<'SH'
            [[ -n "${FAKE_SSH_FAIL:-}" ]] && { echo "ssh: connect to host timed out" >&2; exit 255; }
            echo "Warning: Permanently added '[1.2.3.4]:9011' to the list of known hosts." >&2
            bash -c "${@: -1}"
            SH);
        $this->fake('scp', <<<'SH'
            args=("$@"); src="${args[-2]#*:}"; dst="${args[-1]}"
            cp $src "$dst"/
            [[ -n "${FAKE_SCP_CORRUPT:-}" ]] && echo broken > "$dst/database.sql.gz.age"
            exit 0
            SH);
        $dest = $this->dir.'/local';
        $pull = function (array $env = [], array $args = []) use ($pwsh, $dest): Process {
            $p = new Process([$pwsh, '-NoProfile', '-File', self::root('deploy/backup/pull-backup.ps1'),
                '-Dest', $dest, '-RemoteDir', $this->dir.'/remote', '-Retries', '1', ...$args], null,
                ['PATH' => $this->dir.'/bin:'.getenv('PATH')] + $env, null, 120);
            $p->run();

            return $p;
        };

        $run = $pull(['FAKE_SCP_CORRUPT' => '1']);
        $this->assertSame(1, $run->getExitCode());
        $this->assertStringContainsString('checksum mismatch', $run->getOutput());
        $this->assertFileExists($dest.'/LAST-PULL-FAILED.txt');
        $this->assertDirectoryDoesNotExist($dest.'/20261111_034000', 'نیمه‌کاره هرگز کامل دیده نمی‌شود');

        $run = $pull();
        $this->assertSame(0, $run->getExitCode(), $run->getOutput().$run->getErrorOutput());
        $this->assertSame('db', file_get_contents($dest.'/20261111_034000/database.sql.gz.age'));
        $this->assertFileExists($dest.'/20261111_034000/.complete');
        $this->assertFileDoesNotExist($dest.'/LAST-PULL-FAILED.txt');
        $this->assertDirectoryDoesNotExist($dest.'/20261110_034000', 'فقط تازه‌ترین');

        $this->assertStringContainsString('already have', $pull()->getOutput());

        $this->assertSame(1, $pull(['FAKE_SSH_FAIL' => '1'])->getExitCode());

        foreach (['20261101_034000', '20261102_034000', '20261103_034000'] as $old) {
            mkdir($dest.'/'.$old);
        }
        unlink($dest.'/20261111_034000/.complete');
        $this->assertSame(0, $pull([], ['-Keep', '2'])->getExitCode());
        $this->assertSame(['20261103_034000', '20261111_034000'], array_values(array_filter(scandir($dest), fn ($f) => preg_match('/^\d{8}_\d{6}$/', $f))));
    }

    public function test_upload_image_resumes_after_dropped_connections_and_verifies_the_checksum(): void
    {
        $pwsh = getenv('PWSH') ?: trim((string) shell_exec('command -v pwsh 2>/dev/null'));
        if ($pwsh === '' || ! is_executable($pwsh)) {
            $this->markTestSkipped('PowerShell (pwsh) نصب نیست — PWSH=/path/to/pwsh');
        }
        mkdir($this->dir.'/remote');
        mkdir($this->dir.'/local');
        file_put_contents($this->dir.'/local/mahru-single-abc.tar', random_bytes(300000));
        $this->fake('ssh', "echo 'Warning: Permanently added' >&2\nbash -c \"\${@: -1}\"\n");
        // هر اتصال حداکثر ۱۲۰ کیلوبایت و بعد «Connection reset» (مثل خط خانه، ۲۰۲۶-۱۱-۱۱)
        $this->fake('sftp', <<<'SH'
            while [[ $# -gt 0 ]]; do [[ "$1" == -b ]] && { B="$2"; shift; }; shift; done
            dir=$(sed -n 's/^cd //p' "$B"); line=$(tail -1 "$B"); verb=${line%% *}; src=$(echo "$line" | sed 's/^[a-z]* "\(.*\)"$/\1/')
            dst="$dir/$(basename "$src")"; total=$(stat -c %s "$src"); have=0
            [[ "$verb" == reput ]] && have=$(stat -c %s "$dst"); [[ "$verb" == put ]] && : > "$dst"
            echo "$verb $have" >> "$LOG"
            n=$(( total - have < 120000 ? total - have : 120000 ))
            tail -c +$((have+1)) "$src" 2>/dev/null | head -c $n >> "$dst"
            [[ $((have+n)) -lt $total ]] && { echo "Connection reset" >&2; exit 1; }
            exit 0
            SH);
        $upload = function () use ($pwsh): Process {
            $p = new Process([$pwsh, '-NoProfile', '-File', self::root('deploy/single/upload-image.ps1'), 'mahru-single-abc.tar',
                '-RemoteDir', $this->dir.'/remote', '-MaxWaitSeconds', '1'], $this->dir.'/local',
                ['PATH' => $this->dir.'/bin:'.getenv('PATH'), 'LOG' => $this->dir.'/calls.log', 'TEMP' => $this->dir], null, 120);
            $p->run();

            return $p;
        };

        $run = $upload();
        $this->assertSame(0, $run->getExitCode(), $run->getOutput());
        $this->assertSame("put 0\nreput 120000\nreput 240000\n", $this->calls(), 'بار اول put، بعد از هر قطع reput از همان‌جا');
        $this->assertFileEquals($this->dir.'/local/mahru-single-abc.tar', $this->dir.'/remote/mahru-single-abc.tar');
        $this->assertStringContainsString('server-deploy.sh images/mahru-single-abc.tar', $run->getOutput());

        // کامل روی سرور ← بی آپلود دوباره
        $this->assertSame(0, $upload()->getExitCode());
        $this->assertSame(3, substr_count($this->calls(), "\n"));

        // هم‌اندازه ولی خراب ← شناخته و پاک می‌شود
        $fh = fopen($this->dir.'/remote/mahru-single-abc.tar', 'r+');
        fseek($fh, 10);
        fwrite($fh, 'X');
        fclose($fh);
        $run = $upload();
        $this->assertSame(1, $run->getExitCode());
        $this->assertStringContainsString('CHECKSUM MISMATCH', $run->getOutput());
        $this->assertFileDoesNotExist($this->dir.'/remote/mahru-single-abc.tar');
    }

    public function test_windows_scripts_are_ascii_and_crlf_on_checkout(): void
    {
        // PowerShell 5.1 فایل UTF-8 بی BOM را ANSI می‌خواند: متن فارسی خراب و حتی خطای parse
        foreach (['deploy/backup/pull-backup.ps1', 'deploy/backup/install-pull-task.ps1', 'deploy/single/upload-image.ps1'] as $f) {
            $this->assertDoesNotMatchRegularExpression('/[^\x00-\x7F]/', (string) file_get_contents(self::root($f)), $f);
        }
        $this->assertStringContainsString('*.ps1 text eol=crlf', (string) file_get_contents(self::root('.gitattributes')));
        $this->assertStringContainsString('StartWhenAvailable', (string) file_get_contents(self::root('deploy/backup/install-pull-task.ps1')));
    }
}
