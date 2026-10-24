@echo off
rem ============================================================
rem Mahru - ساخت image تک‌کانتینری روی ویندوز و ذخیره در فایل برای انتقال به سرور (۲۰۲۶-۱۰-۲۴)
rem
rem   deploy\single\build-image.cmd
rem
rem خروجی: build-images\mahru-single-<commit>.tar  ← با WinSCP به /srv/mahru/images روی سرور
rem سپس روی سرور:  bash deploy/single/server-deploy.sh images/mahru-single-<commit>.tar
rem پیش‌نیاز: .env.single (برای compose لازم است) و ترجیحاً build-vendor (docs/deployment/DOCKER_DESKTOP.md بخش ۱)
rem راهنما: docs/deployment/ARVAN_SERVER.md
rem ============================================================
setlocal
chcp 65001 >nul
cd /d "%~dp0\..\.."

set V=
for /f %%i in ('git rev-parse --short HEAD') do set V=%%i
if "%V%"=="" (
    echo git پیدا نشد یا اینجا مخزن نیست
    exit /b 1
)
rem تغییر commit‌نشده: نسخه با پسوند dirty تا با image یک commit تمیز اشتباه نشود
git diff --quiet HEAD || set V=%V%-dirty

if not exist .env.single copy .env.single.example .env.single >nul

echo === build mahru:single (%V%) ===
docker compose -f docker-compose.single.yml build || exit /b 1
docker tag mahru:single mahru:single-%V% || exit /b 1

if not exist build-images mkdir build-images
echo === save build-images\mahru-single-%V%.tar ===
docker save mahru:single-%V% -o build-images\mahru-single-%V%.tar || exit /b 1

echo.
echo آماده: build-images\mahru-single-%V%.tar
echo با WinSCP به /srv/mahru/images بفرستید و روی سرور:
echo   cd /srv/mahru ^&^& bash deploy/single/server-deploy.sh images/mahru-single-%V%.tar
endlocal
