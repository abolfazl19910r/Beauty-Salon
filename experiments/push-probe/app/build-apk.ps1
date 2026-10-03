# ساخت APK آزمایشی با EAS — از همین پوشه اجرا کنید:
#   powershell -ExecutionPolicy Bypass -File .\build-apk.ps1
#
# چرا این اسکریپت: این اپ داخل ریپوی لاراول است. EAS به‌طور پیش‌فرض ریشه‌ی گیت (کل پروژه‌ی لاراول) را بارگذاری می‌کند و
# .easignore را فقط همان‌جا می‌خواند؛ آن‌وقت google-services.json (که در .gitignore است) به ساخت نمی‌رسد و فایل‌های untracked
# پروژه هم بارگذاری می‌شوند. با EAS_NO_VCS و EAS_PROJECT_ROOT فقط همین پوشه با قوانین .easignore همین پوشه بارگذاری می‌شود.
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

if (-not (Test-Path '.\google-services.json')) {
    Write-Error 'google-services.json اینجا نیست — از کنسول Firebase دانلود کنید (FIREBASE_AND_PHONES.md بخش ۱).'
}
if (-not (Test-Path '.\node_modules')) {
    npm install
}

$env:EAS_NO_VCS = '1'
$env:EAS_PROJECT_ROOT = $PSScriptRoot
npx eas-cli@latest build --platform android --profile apk
