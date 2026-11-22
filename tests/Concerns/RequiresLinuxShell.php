<?php

namespace Tests\Concerns;

use Symfony\Component\Process\Process;

/**
 * تست اسکریپت‌های bash سرور (mahru.sh، tls-reload.sh، …) با docker/ssh ساختگی که خودشان اسکریپت bash با shebang هستند
 * (۲۰۲۶-۱۱-۲۲). روی ویندوز «bash» معمولاً رابط WSL است (بی توزیع: execvpe(/bin/bash) failed) و فایل‌های ساختگی هم اجرا نمی‌شوند؛
 * پس آنجا رد می‌شوند، همان‌طور که تست‌های PowerShell بی pwsh رد می‌شوند. روی لینوکس/macOS و CI اجرا می‌شوند.
 */
trait RequiresLinuxShell
{
    private static ?bool $linuxShellUsable = null;

    /**
     * فقط در helperی صدا بزنید که bash اجرا می‌کند (نه setUp): تست‌های همان کلاس که bash لازم ندارند روی ویندوز هم اجرا شوند، و
     * پراپرتی‌هایی که setUp مقدار می‌دهد پیش از tearDown مقدار گرفته باشند.
     */
    protected function requireLinuxShell(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('اسکریپت‌های bash سرور فقط روی لینوکس/macOS آزموده می‌شوند (ویندوز: WSL یا Git Bash با docker ساختگی کار نمی‌کند).');
        }

        if (self::$linuxShellUsable === null) {
            $probe = new Process(['bash', '-c', 'echo ok']);
            $probe->run();
            self::$linuxShellUsable = trim($probe->getOutput()) === 'ok';
        }
        if (! self::$linuxShellUsable) {
            $this->markTestSkipped('bash در دسترس نیست.');
        }
    }
}
