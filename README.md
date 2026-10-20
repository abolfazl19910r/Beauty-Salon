# اپ موبایل ماهرو («ماهرو همکار» و «ماهرو»)

یک کد Expo که دو اپ می‌سازد (تصمیم ۲۰۲۶-۱۰-۰۲): `APP_VARIANT=staff` ← **ماهرو همکار** (`ir.mahru.staff`، فقط متخصص) و
`APP_VARIANT=customer` ← **ماهرو** (`ir.mahru`، مشتری، بسته‌ی ۳ب). سرور: API نسخه‌ی ۱ پروژه‌ی لاراول (`/api/v1`، بسته‌های ۱، ۲الف و ۳الف).
هر نسخه درخت صفحه‌های خودش را دارد (`src/app` همکار، `src/customer` مشتری؛ گزینه‌ی `root` پلاگین expo-router در `app.config.ts`)،
پس هر APK فقط صفحه‌های خودش را دارد. کتابخانه‌ها (`src/lib`) و اجزای ظاهری (`src/ui`) مشترک‌اند.

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

## اپ مشتری «ماهرو» روی گوشی
- **سالن:** اولین صفحه «ورود به سالن» است: اسکن QR کارت «آدرس اختصاصی سالن» پنل مدیر، یا لینک/کد سالن (کد = slug، مثلاً `rasta`).
  اپ فقط یک سالن نگه می‌دارد؛ بعد از «خروج» همان سالن می‌ماند و «ورود به سالن دیگر» در صفحه‌ی ورود عوضش می‌کند.
- **QR سرور لوکال:** QR روی XAMPP آدرسی مثل `http://192.168.1.5:8000/s/rasta` دارد؛ در نسخه‌ی آزمایشی اگر سرور اپ چیز دیگری باشد
  می‌پرسد «سرور اپ عوض شود؟». فیلد مخفی آدرس سرور (پنج ضربه روی لوگوی صفحه‌ی اول) هم مثل همکار هست.
- **پرداخت:** صفحه‌ی بانک در مرورگر گوشی باز می‌شود و صفحه‌ی برگشت سرور با `mahru://payment?tx=…` به اپ برمی‌گرداند؛ اپ نتیجه را
  فقط از سرور می‌پرسد. اگر مرورگر را بستید، با برگشتن به اپ همان پرداخت دوباره پرسیده می‌شود. روی لوکال درگاه سندباکس سالن لازم است.
- **لینک اپ:** `mahru://salon/rasta` اپ را روی همان سالن باز می‌کند (دکمه‌ی «باز کردن در اپ ماهرو» صفحه‌ی وب سالن روی اندروید).
- ورود مشتری با حساب **مشتری همان سالن** است (موبایل + رمز ← کد)؛ ثبت‌نام هم از داخل اپ (حساب فقط بعد از کد ساخته می‌شود).

## ساخت APK با EAS (حساب expo.dev: abolfazl19910r)
از ۲۰۲۶-۱۰-۱۶ هر دو اپ با حساب **`abolfazl19910r`** ساخته می‌شوند (`EAS_OWNER` و `EAS_PROJECT_IDS` در `app.config.ts`):
`@abolfazl19910r/mahru-staff` و `@abolfazl19910r/mahru`. پروژه‌های قبلی `@abolfazl1991/*` دست‌نخورده‌اند ولی دیگر استفاده نمی‌شوند.
کلید امضای APK مال پروژه‌ی expo.dev است؛ اولین APK از حساب تازه روی نسخه‌ی قبلی Update نمی‌شود (یک بار حذف و نصب).
```cmd
npm i -g eas-cli
eas login            :: با abolfazl19910r
eas whoami
```
در **cmd** (در PowerShell به‌جای `set` بنویسید `$env:APP_VARIANT="customer"`):
```cmd
set APP_VARIANT=staff
eas build --platform android --profile staff-apk          :: آزمایشی: فیلد آدرس سرور + http محلی
eas build --platform android --profile staff-production   :: نهایی: فقط https

set APP_VARIANT=customer
eas build --platform android --profile customer-apk
eas build --platform android --profile customer-production
```
به پرسش «Install and run on an emulator?» جواب No بدهید (بدون Android Studio خطای adb می‌دهد؛ build سالم است).
پروژه‌ی تازه (مثلاً حساب دیگر) = `eas init` با `APP_VARIANT` همان اپ، و گذاشتن شناسه‌ی چاپ‌شده در `EAS_PROJECT_IDS` (config پویاست و
`eas init` خودش نمی‌تواند بنویسد؛ اگر شناسه‌ی قبلی در فایل باشد `eas init` پروژه‌ی تازه نمی‌سازد — اول خالی‌اش کنید).
آدرس پیش‌فرض سرور: متغیر `EXPO_PUBLIC_API_URL` در expo.dev (Environment variables) — وگرنه `https://mahru.ir`.

## پوش (بسته‌ی ۴، نسخه‌ی 0.2.0 / versionCode 2)
- **پیش‌نیاز هر build:** متغیر محیطی `GOOGLE_SERVICES_JSON` از نوع **File** (Secret، محیط‌های preview و production) در **هر دو**
  پروژه‌ی expo.dev — `mahru` با `google-services.json` اپ `ir.mahru` و `mahru-staff` با فایل `ir.mahru.staff` (پروژه‌ی Firebase
  تولیدی «mahru»). پروفایل‌های `eas.json` محیط را صریح می‌گیرند. بدون آن APK ساخته می‌شود ولی گوشی توکن پوش نمی‌گیرد.
  ```powershell
  eas env:create --name GOOGLE_SERVICES_JSON --type file --visibility secret --environment preview --environment production --value .\google-services.staff.json
  ```
  (برای اپ مشتری اول `$env:APP_VARIANT="customer"` تا پروژه‌ی `mahru` انتخاب شود.) روی سیستم خودتان برای `expo prebuild`:
  `$env:GOOGLE_SERVICES_JSON=".\google-services.staff.json"` — فایل‌های `google-services*.json` در `.gitignore` و `.easignore` هستند.
- `index.ts` ورودی اپ است: task پس‌زمینه‌ی رسید (`src/lib/pushTask.ts`) پیش از expo-router تعریف می‌شود (اپ بسته فقط همین را اجرا می‌کند).
- پیام سرور data-only با اولویت بالاست؛ اعلان را خود expo-notifications از `title`/`message`/`channelId` می‌سازد (بومی، حتی اگر JS اجرا
  نشود) و task فقط رسید می‌فرستد (`/api/v1/push/receipts`؛ بی‌اینترنت در صف می‌ماند). لمس اعلان صفحه‌ی نوبت/دوره را باز می‌کند.
- کانال‌ها: «نوبت‌ها و یادآوری‌ها» (MAX، صدا) و «باشگاه، تخفیف و اطلاع‌رسانی». آیکون نوار اعلان `assets/notification-icon.png`.
- صفحه‌ی **«دریافت اعلان»** (همکار: بیشتر؛ مشتری: حساب): اجازه، ثبت، «اعلان آزمایشی» و راهنمای Autostart شیائومی / Deep sleeping سامسونگ.
- اگر روی گوشی‌ای رسید از اپ بسته نیامد، روی سرور `PUSH_FCM_STYLE=notification` (اعلان را اندروید می‌سازد؛ رسید فقط با باز کردن اپ).

## انتشار بدون مارکت و لینک‌های سالن (بسته‌ی ۵، نسخه‌ی 0.3.0 / versionCode 8)

**انتشار نسخه‌ی تازه:** در `app.config.ts` مقدار `version` و `versionCode` را بالا ببرید ← build با پروفایل `customer-production`
(یا `staff-production`) ← APK را در پنل ابرمدیر «نسخه‌های اپ» آپلود کنید. نسخه از خود APK خوانده می‌شود؛ versionCode تکراری پذیرفته
نمی‌شود. صفحه‌ی عمومی دانلود `https://mahru.ir/app` است. اپ با `GET /api/v1/app/latest` نسخه‌ی تازه را می‌فهمد: روی صفحه‌ی خانه
(مشتری) و «امروز» (همکار) کارت «نسخه‌ی تازه آمده»، و اگر ابرمدیر نسخه را «اجباری» زده باشد، صفحه‌ی تمام‌صفحه‌ی به‌روزرسانی.

**کلید امضا:** همه‌ی نسخه‌ها باید با همان keystore پروژه‌ی expo.dev امضا شوند (EAS خودش نگه می‌دارد)؛ با کلید دیگر، نسخه‌ی تازه روی
نسخه‌ی قبلی نصب نمی‌شود. نسخه‌ی پشتیبان: `eas credentials -p android` ← Download credentials.

**App Links (فقط اپ مشتری):** لینک https صفحه‌ی اصلی سالن (`rasta.mahru.ir/`) و صفحه‌ی متخصص (`…/specialists/12`، QR متخصص) اگر اپ نصب
است، خود اپ را باز می‌کند (`src/customer/+native-intent.tsx` ← `salon/[slug]`، با `?specialist=`). شرط سرور: اثرانگشت SHA-256 همین keystore
در `.env` سرور (`CUSTOMER_APP_SHA256_FINGERPRINTS`)، از expo.dev ← Credentials ← Android ← `ir.mahru` ← SHA-256 Fingerprint. بررسی روی گوشی:
```
adb shell pm get-app-links ir.mahru
```
آزمایش محلی (http، بدون تأیید خودکار): build با `APP_LINKS_DOMAIN=192.168.1.2.nip.io` و روی گوشی
```
adb shell pm set-app-links-user-selection --user cur --package ir.mahru true '*.192.168.1.2.nip.io'
```
مرورگر داخلی اینستاگرام/تلگرام App Links را نادیده می‌گیرد (همان صفحه‌ی وب و بنر «باز کردن در اپ» می‌ماند)؛ اسکن با دوربین گوشی درست کار می‌کند.

## ساختار
- `src/app/` — صفحه‌های **همکار** (expo-router): ورود، کد، فراموشی رمز، آدرس سرور؛ زبانه‌ها: امروز، نوبت‌ها، تقویم، کیف پول، بیشتر؛
  جزئیات نوبت، برنامه‌ی هفتگی، مرخصی، برداشت، شبا، تراکنش‌ها، اعلان‌ها، دستگاه‌ها، «سالن غیرفعال».
- `src/customer/` — صفحه‌های **مشتری**: ورود به سالن (QR/لینک/کد)، کارت سالن و ورود، ثبت‌نام، فراموشی رمز؛ زبانه‌ها: خانه، رزرو،
  نوبت‌ها، باشگاه، حساب؛ انتخاب متخصص/روز/ساعت، پیش‌فاکتور، پرداخت و نتیجه‌ی پرداخت، جزئیات و جابه‌جایی نوبت، کیف پول و شارژ،
  اعلان‌ها، درباره‌ی سالن، متخصص‌ها، نام، رمز، دستگاه‌ها، حذف حساب.
- `src/lib/customer/` — تایپ‌ها و کوئری‌های `/api/v1/customer/*`، تشخیص لینک/QR/کد سالن، سالن ذخیره‌شده و پرداخت در جریان، کارمزد درگاه.
- آیکون‌ها (نشان تازه‌ی برند، ۲۰۲۶-۱۰-۱۴): `assets/` همکار (اسپرسو + نشان «همکار»)، `assets/customer/` مشتری (اسپرسو). سازنده: `docs/brand/generate_brand_assets.py` پروژه‌ی لاراول (برنچ develop) با آرگومان سوم = پوشه‌ی همین اپ.
- رنگ‌ها (`src/ui/theme.ts`): همان توکن‌های روشن/تیره‌ی لندینگ و پنل‌ها.
- `src/lib/push.ts` (منطق خالص)، `pushRuntime.ts` (اجازه، ثبت، لمس)، `pushReceipts.ts` (رسید بدون React)، `pushTask.ts` (task پس‌زمینه)؛
  `__mocks__/expo-notifications.ts` نسخه‌ی Jest.
- `src/lib/api.ts` — کلاینت `/api/v1` (قالب `{success, data, meta}` / `{success:false, error:{code, message, fields}}`).
- `src/lib/session.tsx` — توکن (SecureStore)، ۴۰۱ ← خروج، `salon_inactive` ← صفحه‌ی خودش، `wrong_app` ← خروج.
- `src/lib/jalali.ts`، `src/lib/format.ts` — شمسی (شنبه تا جمعه)، تومان و ارقام فارسی.
- `src/ui/` — رنگ برند (روشن/تیره از گوشی)، فونت وزیرمتن (OFL، `assets/fonts/OFL.txt`).
- راست‌به‌چپ از اولین اجرا با plugin `expo-localization` (`forcesRTL`).
- دوربین: اپ مشتری `expo-camera` برای اسکن QR سالن؛ اپ همکار `expo-image-picker` برای عکس قبل/بعد جلسه‌ی دوره‌ی درمانی (دوربین یا گالری، فقط بعد از ثبت رضایت مشتری). میکروفون در هر دو با `blockedPermissions` حذف است. پس از این تغییر اپ همکار باید دوباره build شود (پلاگین بومی تازه).

## بررسی‌ها
```powershell
npm run typecheck
npm test
npx expo export --platform android                                   # همکار
$env:APP_VARIANT="customer"; npx expo export --platform android      # مشتری
```
