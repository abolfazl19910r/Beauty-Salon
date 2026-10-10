# اپ موبایل ماهرو («ماهرو همکار» و بعداً «ماهرو»)

یک کد Expo که دو اپ می‌سازد (تصمیم ۲۰۲۶-۱۰-۰۲): `APP_VARIANT=staff` ← **ماهرو همکار** (`ir.mahru.staff`، فقط متخصص) و
`APP_VARIANT=customer` ← **ماهرو** (`ir.mahru`، بسته‌ی ۳). سرور: API نسخه‌ی ۱ پروژه‌ی لاراول (`/api/v1`، بسته‌های ۱ و ۲الف).

این کد روی **برنچ یتیم `mobile-app`** ریپوی `Beauty-Salon` است (تاریخچه‌اش از `develop` جداست و هیچ‌وقت merge نمی‌شود).
ریشه‌ی برنچ خود اپ است، پس `eas build` فقط همین را بارگذاری می‌کند (مشکل کیت بسته‌ی ۰ اینجا پیش نمی‌آید).

## گرفتن کد (یک بار، در پوشه‌ای جدا از پروژه‌ی لاراول)
فایل‌های `app-*.patch` را در یک پوشه بگذارید (مثلاً `C:\Users\Parsa\Downloads\app-patches`). در **cmd**:
```bat
git clone https://github.com/abolfazl19910r/Beauty-Salon.git Mahru-App
cd Mahru-App
git checkout --orphan mobile-app
git rm -rf .
for %f in (C:\Users\Parsa\Downloads\app-patches\app-*.patch) do git am --keep-cr "%f"
git log --oneline
git push -u origin mobile-app
npm ci
```
⚠️ `git am --keep-cr app-*.patch` روی ویندوز کار نمی‌کند: نه cmd و نه PowerShell `*` را برای git باز نمی‌کنند. حلقه‌ی
`for` بالا فایل‌ها را به ترتیب نام (`app-0001`، `app-0002`، …) یکی‌یکی اعمال می‌کند. داخل فایل `.bat` به‌جای `%f` بنویسید `%%f`.
در **PowerShell**: `git am --keep-cr (Get-ChildItem C:\Users\Parsa\Downloads\app-patches\app-*.patch | Sort-Object Name).FullName`
اگر یکی از پچ‌ها خطا داد، بقیه را ادامه ندهید: `git am --abort` و خطا را بفرستید.

پوشه‌ی جدا لازم است: در پوشه‌ی پروژه‌ی لاراول، `vendor`، `node_modules` و `.env` روی برنچ یتیم «فایل اضافه» دیده می‌شوند.

## اجرا و تست روی گوشی
- **آدرس سرور در نسخه‌ی آزمایشی:** در صفحه‌ی ورود **پنج بار پشت سر هم روی لوگو بزنید** ← «آدرس سرور» ← مثلاً
  `http://192.168.1.5:8000` (آی‌پی سیستم در Wi‑Fi؛ `ipconfig`). سرور لاراول: `php artisan serve --host=0.0.0.0 --port=8000`.
  گوشی و سیستم روی یک Wi‑Fi، و پورت ۸۰۰۰ در فایروال ویندوز باز. «امتحان» با `GET /api/v1/status` جواب می‌دهد.
  در نسخه‌ی `staff-production` این فیلد خاموش است و `http` هم بسته (فقط `https`).
- **ورود:** همان حساب متخصص وب (موبایل + رمز ← کد پیامکی). مدیر/سوپرادمین بدون رکورد متخصص پیام «این حساب برای این اپلیکیشن
  نیست» می‌گیرد. روی لوکال کد ورود در `storage/logs/laravel.log` هم هست (`Queued login verification code`).
- صف `otp` باید اجرا شود تا پیامک برود (`php artisan queue:work --queue=otp,sms,payments,default,reports`).

## ساخت APK با EAS (حساب expo.dev: abolfazl1991)
```powershell
npm i -g eas-cli
eas login
eas init            # پروژه‌ی mahru-staff را می‌سازد و projectId می‌دهد
```
پروژه‌ی `mahru-staff` ساخته شده و شناسه‌اش در `EAS_PROJECT_IDS` داخل `app.config.ts` هست (`eas init` پیام «Cannot automatically
write to dynamic config» می‌دهد که طبیعی است — config پویاست). برای پروژه‌ی تازه (مثلاً اپ مشتری) شناسه‌ی چاپ‌شده را همان‌جا بگذارید. بعد:
```powershell
eas build --platform android --profile staff-apk          # آزمایشی: فیلد آدرس سرور + http محلی
eas build --platform android --profile staff-production   # نهایی: فقط https
```
آدرس پیش‌فرض سرور: متغیر `EXPO_PUBLIC_API_URL` در expo.dev (Environment variables) — وگرنه `https://mahru.ir`.
پوش (Firebase، `google-services.json`) در بسته‌ی ۴ اضافه می‌شود؛ مثل بقیه‌ی اپ‌ها فایل را در expo.dev بگذارید، نه در ریپو.

## ساختار
- `src/app/` — صفحه‌ها (expo-router): ورود، کد، فراموشی رمز، آدرس سرور؛ زبانه‌ها: امروز، نوبت‌ها، تقویم، کیف پول، بیشتر؛
  جزئیات نوبت، برنامه‌ی هفتگی، مرخصی، برداشت، شبا، تراکنش‌ها، اعلان‌ها، دستگاه‌ها، «سالن غیرفعال».
- `src/lib/api.ts` — کلاینت `/api/v1` (قالب `{success, data, meta}` / `{success:false, error:{code, message, fields}}`).
- `src/lib/session.tsx` — توکن (SecureStore)، ۴۰۱ ← خروج، `salon_inactive` ← صفحه‌ی خودش، `wrong_app` ← خروج.
- `src/lib/jalali.ts`، `src/lib/format.ts` — شمسی (شنبه تا جمعه)، تومان و ارقام فارسی.
- `src/ui/` — رنگ برند (روشن/تیره از گوشی)، فونت وزیرمتن (OFL، `assets/fonts/OFL.txt`).
- راست‌به‌چپ از اولین اجرا با plugin `expo-localization` (`forcesRTL`).

## بررسی‌ها
```powershell
npm run typecheck
npm test
npx expo export --platform android
```
