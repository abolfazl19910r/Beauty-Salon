<?php

namespace Tests\Unit\Deploy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * پشتیبان رمزنگاری‌شده با age (بسته‌ی مجوز 5د) — خود deploy/backup/mahru-backup.sh با کلیدهای واقعی age اجرا می‌شود.
 * کلید خصوصی جدا از سرور و جدا از APP_KEY؛ نسخه‌ی باز هرگز روی دیسک نوشته نمی‌شود.
 */
class BackupEncryptionTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['bash', 'age', 'age-keygen', 'tar', 'sqlite3'] as $bin) {
            if (trim((string) shell_exec('command -v '.escapeshellarg($bin))) === '') {
                $this->markTestSkipped("{$bin} نصب نیست");
            }
        }
        $this->dir = sys_get_temp_dir().'/mahru-backup-'.uniqid();
        mkdir($this->dir.'/app/storage/app/course-photos', 0777, true);
        mkdir($this->dir.'/app/storage/app/public', 0777, true);
        file_put_contents($this->dir.'/app/storage/app/course-photos/face.jpg', 'PRIVATE-FACE-PHOTO');
        file_put_contents($this->dir.'/app/storage/app/public/logo.png', 'logo');
        shell_exec('sqlite3 '.escapeshellarg($this->dir.'/db.sqlite')." \"create table users(phone text); insert into users values('09120000777');\"");
        file_put_contents($this->dir.'/app/.env', "DB_CONNECTION=sqlite\nDB_DATABASE={$this->dir}/db.sqlite\n");
        file_put_contents($this->dir.'/app.key', 'base64:SECRETKEY');
        shell_exec('age-keygen -o '.escapeshellarg($this->dir.'/key.txt').' 2>/dev/null');
    }

    protected function tearDown(): void
    {
        if (isset($this->dir)) {
            shell_exec('rm -rf '.escapeshellarg($this->dir));
        }
        parent::tearDown();
    }

    private function publicKey(): string
    {
        return trim((string) shell_exec('age-keygen -y '.escapeshellarg($this->dir.'/key.txt')));
    }

    private function backup(array $env): Process
    {
        $process = new Process(
            ['bash', dirname(__DIR__, 3).'/deploy/backup/mahru-backup.sh', $this->dir.'/app', $this->dir.'/backups'],
            null,
            // محیط پردازه بر .env مقدم است؛ phpunit.xml خودش DB_DATABASE=:memory: دارد
            $env + ['BACKUP_APP_KEY_FILE' => $this->dir.'/app.key', 'KEEP_LAST' => '3', 'HOME' => $this->dir,
                'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $this->dir.'/db.sqlite'],
        );
        $process->run();

        return $process;
    }

    private function decrypt(string $file): string
    {
        return (string) shell_exec('age -d -i '.escapeshellarg($this->dir.'/key.txt').' '.escapeshellarg($file));
    }

    public function test_every_file_is_encrypted_and_restores_with_the_private_key(): void
    {
        $run = $this->backup(['BACKUP_AGE_RECIPIENT' => $this->publicKey()]);
        $this->assertSame(0, $run->getExitCode(), $run->getErrorOutput());

        $dest = glob($this->dir.'/backups/20*')[0];
        $files = array_map('basename', glob($dest.'/*'));
        sort($files);
        $this->assertSame(['app.key.age', 'database.sqlite.age', 'storage-app.tar.gz.age'], $files, 'هیچ فایل بازی کنار پشتیبان نیست');

        foreach (glob($dest.'/*') as $file) {
            $raw = (string) file_get_contents($file);
            $this->assertStringStartsWith('age-encryption.org/v1', $raw);
            $this->assertStringNotContainsString('09120000777', $raw);
            $this->assertStringNotContainsString('SECRETKEY', $raw);
        }

        $this->assertSame('base64:SECRETKEY', $this->decrypt($dest.'/app.key.age'));
        file_put_contents($this->dir.'/restored.sqlite', $this->decrypt($dest.'/database.sqlite.age'));
        $this->assertSame('09120000777', trim((string) shell_exec('sqlite3 '.escapeshellarg($this->dir.'/restored.sqlite').' "select phone from users"')));
        file_put_contents($this->dir.'/files.tgz', $this->decrypt($dest.'/storage-app.tar.gz.age'));
        $this->assertStringContainsString('storage/app/course-photos/face.jpg', (string) shell_exec('tar -tzf '.escapeshellarg($this->dir.'/files.tgz')));

        $status = json_decode((string) file_get_contents($this->dir.'/app/storage/app/backup-status.json'), true);
        $this->assertSame(['ok', true], [$status['status'], $status['encrypted']]);
    }

    public function test_the_server_refuses_to_write_an_unencrypted_backup(): void
    {
        $run = $this->backup(['BACKUP_REQUIRE_ENCRYPTION' => 'true']);

        $this->assertNotSame(0, $run->getExitCode());
        $this->assertStringContainsString('refusing to write an unencrypted backup', $run->getErrorOutput());
        $this->assertSame([], glob($this->dir.'/backups/20*/*') ?: []);
        $status = json_decode((string) file_get_contents($this->dir.'/app/storage/app/backup-status.json'), true);
        $this->assertSame('failed', $status['status'], 'داشبورد ابرمدیر هشدار می‌دهد');
    }

    public function test_a_wrong_key_is_rejected_instead_of_silently_writing_plain_files(): void
    {
        $run = $this->backup(['BACKUP_AGE_RECIPIENT' => 'not-a-key']);

        $this->assertSame(3, $run->getExitCode());
        $this->assertSame([], glob($this->dir.'/backups/20*/*') ?: []);
    }

    public function test_without_a_key_and_not_required_it_stays_as_before_for_local_hosts(): void
    {
        $run = $this->backup([]);

        $this->assertSame(0, $run->getExitCode(), $run->getErrorOutput());
        $this->assertFileExists(glob($this->dir.'/backups/20*')[0].'/database.sqlite');
        $this->assertStringContainsString('NOT encrypted', $run->getOutput());
    }
}
