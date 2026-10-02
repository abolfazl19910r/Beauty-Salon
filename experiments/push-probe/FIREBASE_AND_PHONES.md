# راهنمای قدم‌به‌قدم: Firebase، ساخت APK و تنظیم دو گوشی

(برای ntfy: `ntfy/README.md`؛ تنظیمات باتری بخش ۳ را همان‌جا برای اپ ntfy تکرار کنید.)

## ۱. پروژه‌ی Firebase
> ⚠️ کنسول Firebase را همیشه با IP غیرایرانی باز کنید؛ گوگل به ورود مدیر کنسول از ایران حساس است (مستند پوشه هم همین را
> توصیه کرده). خود آزمایش (ارسال از سیستم شما) عمداً از IP ایران است.

1. https://console.firebase.google.com ← **Create a project** ← نام: `mahru-push-probe` ← Google Analytics: **خاموش** ← Create.
2. در صفحه‌ی پروژه، آیکن اندروید (**Add app**):
   - Android package name: `ir.mahru.pushprobe` (دقیقاً همین؛ با `app.json` یکی است)
   - App nickname: `push probe` — SHA-1 لازم نیست.
   - **Download google-services.json** ← بگذارید در `experiments/push-probe/app/google-services.json`
     (در `.gitignore` است؛ commit نکنید). بقیه‌ی قدم‌های ویزارد (Gradle) را رد کنید — Expo خودش انجام می‌دهد.
3. ⚙️ **Project settings ← Cloud Messaging**: باید «Firebase Cloud Messaging API (V1)» **Enabled** باشد (در پروژه‌ی تازه
   پیش‌فرض روشن است). اگر Disabled بود، از منوی سه‌نقطه‌ی همان ردیف «Manage API in Google Cloud Console» ← Enable.
   (کلید قدیمی Server key / Legacy API لازم نیست؛ گوگل آن را از ۲۰۲۴ بسته است.)
4. ⚙️ **Project settings ← Service accounts ← Generate new private key** ← فایل JSON دانلود می‌شود.
   - جایی بیرون از پروژه و `public_html` بگذارید، مثلاً `C:/xampp/secrets/mahru-push-probe-sa.json`.
   - در `.env`: `PUSH_PROBE_FCM_CREDENTIALS=C:/xampp/secrets/mahru-push-probe-sa.json`
   - ⚠️ این فایل کلید ارسال پوش به همه‌ی نصب‌های اپ است: نه در چت، نه در گیت. اشتباهی `google-services.json` را آنجا
     نگذارید — دستور خطای «کلید client_email ندارد» می‌دهد.
5. **بعد از آزمایش:** Google Cloud Console ← IAM & Admin ← Service Accounts ← `firebase-adminsdk-…` ← Keys ← حذف کلید.

## ۲. ساخت APK
### راه پیشنهادی: EAS Build (ساخت روی سرورهای Expo)
پیش‌نیاز: Node.js 20+، حساب رایگان expo.dev (ورود و ساخت ممکن است VPN بخواهد).
```bash
cd experiments/push-probe/app
npm install
npx eas-cli login
npx eas-cli build -p android --profile apk
```
- سؤال «ساخت پروژه‌ی EAS» و «Generate a new Android Keystore» ← **Yes**.
- `google-services.json` با وجود `.gitignore` به ساخت می‌رسد، چون `.easignore` جای آن را گرفته است.
- آخر کار یک لینک/QR برای دانلود APK می‌دهد ← روی هر دو گوشی نصب (اجازه‌ی «نصب از منابع ناشناس» برای مرورگر/فایل‌منیجر).

### راه دوم: ساخت روی ویندوز خودتان
پیش‌نیاز: Android Studio (SDK و JDK 17 داخلش). دانلود وابستگی‌ها از `dl.google.com`/Maven گوگل از ایران معمولاً VPN می‌خواهد.
```bash
cd experiments/push-probe/app
npm install
npx expo prebuild -p android
cd android
gradlew.bat assembleRelease
```
خروجی: `android/app/build/outputs/apk/release/app-release.apk` (با کلید debug امضا می‌شود؛ برای آزمایش کافی است).

### بعد از نصب، روی هر گوشی
1. اپ «آزمایش پوش ماهرو» ← **درخواست اجازه** ← Allow.
2. **گرفتن توکن** ← زمان و نتیجه را در فرم بنویسید. خطای `SERVICE_NOT_AVAILABLE` یعنی گوشی به سرویس FCM گوگل وصل نمی‌شود
   (Google Play services نیست/قدیمی است، یا شبکه مسدود است) — یک بار با Wi‑Fi و یک بار با اینترنت همراه امتحان کنید.

## ۳. تنظیمات گوشی‌ها
**اصل آزمایش:** هر سناریو را **اول با تنظیمات پیش‌فرض** (همان چیزی که مشتری واقعی دارد) و بعد با تنظیمات «آزاد» اجرا کنید.
مشتری‌های ماهرو تنظیمات باتری را عوض نمی‌کنند؛ نتیجه‌ی «پیش‌فرض» است که تصمیم بند ۲ (مهلت پیامک) را تعیین می‌کند.

### سامسونگ Galaxy A17 (One UI)
پیش‌فرض: کاری نکنید، فقط اجازه‌ی اعلان.
- اعلان: Settings ← Notifications ← App notifications ← آزمایش پوش ماهرو ← روشن؛ داخل آن دسته‌ی «آزمایش پوش» ← Alert (نه Silent).
- Google Play services: Settings ← Apps ← Google Play services ← Battery ← نباید «Restricted» باشد (به‌طور پیش‌فرض نیست).

حالت «آزاد» (برای مقایسه):
- Settings ← Apps ← آزمایش پوش ماهرو ← Battery ← **Unrestricted**.
- Settings ← Battery ← Background usage limits ← **Never sleeping apps** ← افزودن اپ.

حالت «بسته‌شدن توسط مدیریت باتری» (سناریوی S8):
- Settings ← Battery ← Background usage limits ← **Deep sleeping apps** ← افزودن اپ (این همان کاری است که One UI خودکار با اپ
  کم‌استفاده می‌کند). بعد اپ را از Recents ببندید و پیام بفرستید. بعد از آزمایش اپ را از آن فهرست بیرون بیاورید.

### شیائومی Redmi Note 8 (MIUI)
اول مطمئن شوید رام **Global** است: Settings ← About phone ← نسخه‌ی MIUI باید `MIXM`/`EUXM`/`…` داشته باشد، نه `CNXM`
(رام چینی Google Play ندارد و FCM اصلاً کار نمی‌کند). Google Play services باید نصب و به‌روز باشد.

پیش‌فرض: کاری نکنید، فقط اجازه‌ی اعلان.
- اعلان: Settings ← Notifications ← App notifications ← آزمایش پوش ماهرو ← روشن؛ «Floating notifications» و «Lock screen» روشن.

حالت «آزاد» (برای مقایسه):
- Settings ← Apps ← Manage apps ← آزمایش پوش ماهرو ← **Autostart: روشن**.
- همان صفحه ← Battery saver ← **No restrictions**.
- در Recents روی کارت اپ نگه دارید ← **قفل** (🔒) — MIUI اپ قفل‌شده را با Clean نمی‌بندد.

حالت «بسته‌شدن توسط مدیریت باتری» (سناریوی S8):
- تنظیمات پیش‌فرض (Autostart خاموش، Battery saver: MIUI recommended) ← اپ را با دکمه‌ی **Clean (×)** در Recents ببندید یا
  Security ← Boost speed ← Clean. گزارش‌های زیادی هست که MIUI در این حالت اپ را مثل Force stop می‌بندد؛ مهم‌ترین سؤال این آزمایش همین است.

### هر دو گوشی
- تاریخ و ساعت: **خودکار** (و ساعت ویندوز: Settings ← Time ← Set time automatically ← Sync now).
- حالت صرفه‌جویی شدید باتری (Power saving / Ultra) فقط در سناریوی جدا؛ در بقیه خاموش.
- Force stop (Settings ← Apps ← … ← Force stop) سناریوی **کنترل** است: طبق قاعده‌ی اندروید پیام تا باز شدن دوباره‌ی اپ
  نمی‌رسد. اگر رسید یعنی چیزی را اشتباه اندازه گرفته‌ایم.
