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
    protected function requireLinuxShell(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('اسکریپت‌های bash سرور فقط روی لینوکس/macOS آزموده می‌شوند (ویندوز: WSL یا Git Bash با docker ساختگی کار نمی‌کند).');
        }

        $probe = new Process(['bash', '-c', 'echo ok']);
        $probe->run();
        if (trim($probe->getOutput()) !== 'ok') {
            $this->markTestSkipped('bash در دسترس نیست.');
        }
    }
}
