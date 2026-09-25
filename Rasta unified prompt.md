# پروژه: سالن زیبایی راستا (Beauty Salon) — پرامپت یکپارچه (رفکتور + وضعیت کلی پروژه)

> این فایل، ادغام کامل دو پرامپت قبلی (`Rasta_main_prompt_final.MD` و `Refactor_prompt_v4.MD`) در یک سند واحد است. از این پس فقط همین یک فایل نیاز است؛ دو فایل قبلی منسوخ شدن.

## ریپوی پروژه
`https://github.com/abolfazl19910r/Beauty-Salon.git` — لوکال: Windows/XAMPP

## GitHub Personal Access Token
⚠️ **(۲۰۲۶-۰۹-۲۶)** توکن دیگه در این سند نوشته نمی‌شه (ریپو عمومیه و توکن قبلی همین‌جا لو رفته بود). ابوالفضل در هر
چت توکن تازه رو می‌ده. استفاده: هدر `Authorization: token <TOKEN>` روی `api.github.com` / `raw.githubusercontent.com`.

## برنچ‌های پروژه
`main`, `develop`, `V3` — برنچ `V2` در ۲۰۲۶-۰۹-۲۰ با یک merge commit (`40c58eb`) داخل `develop` ادغام و از ریموت حذف شد. ⚠️ **به‌روزرسانی ۲۰۲۶-۰۹-۲۱**: ابوالفضل تأیید کرد `V3` الان به‌روزترین برنچ کاره، نه `develop` — از این به بعد همیشه فایل‌ها باید از `V3` خونده بشن و هر پچ/کامیت جدید هم باید بر پایه‌ی `V3` باشه (نه `develop`). قبل از هر کاری چک کن `V3` هنوز جدیدترینه یا نه (ممکنه دوباره عوض بشه).

⭐ **قانون ۲۰۲۶-۰۹-۲۳**: پیام commit ها و توضیحات بدنه‌ی پچ‌ها (subject + body) **فقط انگلیسی** نوشته می‌شن. کامنت‌های داخل کد طبق روال پروژه فارسی می‌مونن مگر ابوالفضل خلافش رو بگه.

⭐ **به‌روزرسانی ۲۰۲۶-۰۹-۲۳**: ابوالفضل اعلام کرد به‌روزترین برنچ‌ها `develop` و `V4-merge-migration` هستن (هر دو روی `afb3764`). مرجع خواندن کد از این به بعد `develop` است (نه `V3`؛ بند ۲ روال دسترسی پایین با این جمله جایگزین می‌شه).

---

## ⚠️ روال دسترسی به GitHub (همیشه رعایت شود)
دسترسی شبکه/GitHub در محیط چت وب بین جلسات مختلف متفاوت بوده — گاهی کاملاً بسته (نه bash، نه web_fetch)، گاهی کاملاً باز (در فاز R-AdminBlog مستقیم از `api.github.com`/`raw.githubusercontent.com` روی برنچ `develop` خونده شد و همین باعث کشف چند باگ جدی شد که فقط از روی فایل‌های آپلودی قابل کشف نبودن).

1. **همیشه اول با یک curl ساده (`api.github.com/repos/...`) تست کن** که دسترسی باز است یا نه.
2. اگه باز بود: فایل واقعی رو مستقیم از ریپو (⚠️ برنچ `V3` — نه `develop`، طبق تصمیم ۲۰۲۶-۰۹-۲۱) با هدر `Authorization: token <TOKEN>` بخون، نه فقط فایل آپلودی کاربر رو — چون آپلودها می‌تونن قدیمی‌تر یا جدیدتر از ریپو باشن (چندبار در این پروژه این مورد اتفاق افتاده).
3. اگه بسته بود: کاربر فایل‌های مرتبط رو مستقیم آپلود می‌کنه، فایل اصلاح‌شده تحویل داده می‌شه، کاربر خودش جایگزین و کامیت می‌کنه.
4. **بعد از ساختن هر فایل، حتماً `present_files` صدا زده بشه** — چند بار در این پروژه فایل ساخته شد ولی تحویل داده نشد و کاربر فکر می‌کرد رفع نشده.
5. **⭐ وقتی چند فایل خروجی با اسم مشابه پشت سر هم تحویل داده می‌شن (مثلاً چند `show.blade.php` برای مسیرهای مختلف)، حتماً هر کدوم یک اسم یکتا و صریح بگیره** (مثل `bookings-show.blade.php` در برابر `payment-show.blade.php`) و مسیر دقیق مقصدش (`resources/views/.../اسم‌واقعی.blade.php`) توی جدول توضیح داده بشه — یک بار در همین پروژه یک فایل جدید ناخواسته فایل قبلی رو overwrite کرد چون هر دو `show.blade.php` نام‌گذاری شده بودن.
6. **⭐ وقتی فرض می‌کنی یک کلاس/فایل از فاز قبلی از قبل وجود داره (مثلاً یک Form Request که در مستندسازی فاز قبل «ساخته شد» ذکر شده)، این فرض رو با احتیاط اعلام کن** — حداقل یک‌بار در این پروژه (`App\Http\Requests\StoreGalleryImageRequest`) این فرض اشتباه از آب دراومد و باعث خطای «Class does not exist» در production شد؛ مستند شدن یک فایل در پرامپت لزوماً تضمین نمی‌کنه که واقعاً در کدبیس فعلی commit/موجود باشه. **⭐ نمونه‌ی دوم و معکوس همین الگو (کشف‌شده در فیچر تسویه‌ی دستی کیف‌پول)**: عکس این باگ هم اتفاق می‌افته — یعنی یک Form Request جدید و درست در فاز بعدی ساخته می‌شه (مثلاً `Requests\Specialist\Wallet\Withdrawal\StoreWithdrawalRequest` در R-SpecialistWallet)، ولی کنترلر همچنان به نسخه‌ی قدیمی/کهنه‌ی هم‌نام در namespace دیگه (`Requests\Specialist\StoreWithdrawalRequest` از R2) وصل می‌مونه. باید بعد از هر migration namespace، با `grep -rn "use App\\\\Http\\\\Requests\\\\...OldNamespace"` تأیید کرد که هیچ کنترلری به نسخه‌ی قدیمی وصل نمونده — دقیقاً همون کلاس قانون namespace که در R-Events («فقط کلاس‌هایی که واقعاً جابه‌جا شدن...») نوشته شده بود، این‌بار برعکسش (کلاس جدید ساخته شده ولی مصرف‌کننده هنوز به قدیمی وصله) هم باید چک بشه.

7. **⭐ نمونه‌ی سوم و پرهزینه‌ی همین الگو (کشف‌شده در باگ نوتیفیکیشن تکراری برداشت وجه)**: کاربر بین چند جلسه، فایل زیپ آپلود می‌کنه، ولی بین دو جلسه دستی/با ابزار دیگه (Kimi.ai) روی فایل local تغییر می‌ده — و زیپ جلسه‌ی بعدی این تغییرات local رو داره ولی جلسه‌ی فعلی از زیپ قدیمی‌تر شروع کرده. مشخصاً: یک idempotency guard که به `SendWithdrawalApprovedNotification.php` اضافه شده بود (بین دو زیپ، مستقیماً روی فایل local، بدون این‌که در زیپ اول باشه) به جدول اشتباه `notifications` (به‌جای جدول واقعی پروژه `user_notifications`) کوئری می‌زد و باعث `SQLSTATE[42S02]: Base table or view not found` و fail شدن مکرر job توی صف می‌شد — فقط با فرستادن `laravel.log` جدید (نه با فایل‌های زیپ) کشف شد. **درس**: هر بار که کاربر لاگ جدید می‌فرسته که به فایل/خطی اشاره می‌کنه که در آخرین zip موجود کاربر نیست، اول فرض «فایل local کاربر از zip جلوتره» رو بررسی کن، نه اینکه صرفاً بگی «تو zip همچین چیزی نیست پس این خطا نباید بیفته». همیشه از کاربر بخواه zip تازه‌ای که این فایل تغییریافته رو داره بفرسته تا کامل sync بشین.

---

## ⚠️ نکات فنی مهم Blade — قبل از هر ویرایش حتماً رعایت شود
(کشف‌شده طی فاز R-AdminLoyalty، برای همه‌ی فازهای بعدی هم صادقن)

1. **هرگز اسم یک Blade directive واقعی (`@vite`, `@include`, `@if`, ...) رو داخل کامنت HTML/JS ننویس.** Blade کل فایل رو — حتی داخل `<script>` و کامنت‌های JS — برای الگوی `@کلمه` اسکن می‌کنه. مثلاً نوشتن `(خروجی @vite)` داخل یک کامنت JS باعث شد Blade اون رو یک `@vite` بدون آرگومان تفسیر کنه و فتال ارور `Too few arguments to function Vite::__invoke()` بده. اگه لازم بود اسم directive رو داخل متن بیاری، از `@@` (escape) استفاده کن یا بازنویسی‌اش کن.
2. **اسکریپت‌های `type="module"` (خروجی `@vite`) قبل از `DOMContentLoaded` اجرا می‌شن، نه بعدش.** اگه یک اسکریپت inline قبل از `@vite` بخواد داده‌ای (مثل `window.initialData`) رو برای اون ماژول آماده کنه، نباید اون assignment رو داخل `document.addEventListener('DOMContentLoaded', ...)` بذاره — چون ماژول deferred از قبل اجرا شده و مقدار رو `undefined` می‌بینه. assignment باید synchronous باشه.
3. **برای صفحات مدیریتی ساده (CRUD لیست/فرم)، اول Blade کاملاً سرور-رندر رو در نظر بگیر، نه SPA/fetch.** React SPA mount (`admin.jsx` + کامپوننت‌های جدا) چندبار (گزارشات، امتیازات، وبلاگ، اطلاعیه‌ها، گالری) منبع باگ‌های سخت‌دیباگ بوده: route name mismatch، race condition، خطای ۵۰۰ که هیچ اثری در Telescope/log نداشت (چون اصلاً به PHP نمی‌رسید)، یا حتی routeها/viewهای پشت SPA اصلاً وجود نداشتن بدون اینکه کسی متوجه بشه (چون UI هیچ‌وقت بهشون لینک نمی‌داد). فرم HTML معمولی + `redirect()->with('success'/'error', ...)` که `layouts.admin` خودکار flash می‌کنه، مطمئن‌تره و برای این پروژه استاندارده.
4. **جی‌کوئری و بوت‌استرپ توی `layouts/admin.blade.php` لود نمی‌شن.** سیستم طراحی ادمین: Tailwind (از Vite) + CSS variables (`--admin-*`) + SweetAlert2 (global، فقط برای دیالوگ تأیید حذف؛ هر دکمه با `data-confirm-delete` خودکار فعال می‌شه؛ برای اکشن‌های حساس ولی غیرحذفی مثل تسویه‌ی دستی کیف‌پول، الگوی مشابه `data-confirm-action` هم اضافه شده — به بخش «فیچر تسویه‌ی دستی کیف‌پول» نگاه کن). قبل از اضافه‌کردن هر کتابخونه‌ی CSS دیگه به یک صفحه‌ی خاص، مطمئن شو تداخل نداره.
5. **وقتی یک صفحه از SPA به Blade مهاجرت می‌کنه، حتماً با grep مستقیم (`fetch(`/`axios`) داخل فایل `.jsx` بررسی کن کامپوننت واقعاً از کدوم روت‌ها استفاده می‌کرده** — `window.initialData` تزریق‌شده در Blade می‌تونه کاملاً بی‌ربط/بلااستفاده باشه (نمونه‌ی واقعی در R-AdminBlog: `window.initialData.routes` به روت‌های عمومی اشاره می‌کرد ولی JSX واقعی مستقیم به روت‌های ادمین hardcode‌شده فچ می‌زد؛ initialData هیچ‌وقت خونده نمی‌شد).
6. **همیشه با migration واقعی جدول چک کن که آیا ستون‌های فرم واقعاً در `$fillable` مدل هستن** — این الگو («فرم مقدار می‌گیره، هیچ خطایی نمی‌ده، ولی mass-assignment بی‌صدا دورش می‌ریزه») تا الان سه بار تکرار شده (`admin_commission_percentage` در R-AdminWallet، `description`/`order` در R-AdminBlog). همچنین چک کن مدل‌های دارای ستون `deleted_at` واقعاً تریت `SoftDeletes` رو دارن یا نه (نمونه: `BlogPost` نداشت — همه‌ی حذف‌ها دائمی بودن).
7. **⭐ `admin.jsx` تمام کامپوننت‌های ادمین رو در یک bundle واحد import می‌کنه — یک import شکسته‌ی تنها (مثلاً به یک `.jsx` حذف‌شده)، Vite رو مجبور می‌کنه کل bundle رو رد کنه، نه فقط همون یک صفحه.** یعنی وقتی یک کامپوننت (مثل `BlogAdmin.jsx`) در یک فاز حذف می‌شه ولی importش تو `admin.jsx` فراموش می‌شه، **هر صفحه‌ی دیگه‌ای که همین `admin.jsx` رو `@vite` می‌کنه هم می‌خوابه** (مثلاً صفحه‌ی اطلاعیه‌ها با خطای وبلاگ کرش کرد). بعد از هر migration به Blade، باید بلافاصله import و mount مربوطه از `admin.jsx` هم پاک بشه، نه فقط فایل `.jsx` خودش.
8. **⭐ تقویم شمسی خودکفای پروژه (jcal) فقط تاریخ (`Y/m/d`) رو مدیریت می‌کنه، نه تاریخ+ساعت.** ...
9. **⭐ ورودی‌های عددی فارسی: `type="number"` + `step`/`min`/`max` HTML5 با اعداد فارسی/فرمت‌شده کار نمی‌کنه.** مرورگر validation رو روی `type="text"` با `inputmode="numeric"` اعمال می‌کنه ولی `step` رو از `data-step` (نه attribute استاندارد) بخون. برای جلوگیری از خطای «Please select a valid value» مرورگر:
   - `novalidate` روی `&lt;form&gt;` بذار
   - `type="text"` + `inputmode="numeric"` به‌جای `type="number"`
   - `data-min`/`data-max`/`data-step` به‌جای `min`/`max`/`step`
   - Validation کامل رو به JS + Backend (Form Request) بسپار
   - قبل از submit، مقدار فرمت‌شده (با کاما/ارقام فارسی) رو به عدد خام تبدیل کن
10. **⭐ آیدی‌های UUID (رشته‌ای) هیچ‌وقت بدون کوتیشن داخل attributeهای inline JS (`onclick="fn({{ $model->id }})"`) قرار نگیرن.** جدول‌های notification پروژه (`user_notifications`) کلید اصلی `uuid` دارن، نه عدد. نوشتن `onclick="markAsRead({{ $notification->id }})"` خروجی HTML رو به `markAsRead(9f9e2a1c-...)` تبدیل می‌کنه — این یک SyntaxError جاوااسکریپتیه (چون UUID به‌عنوان عبارت تفریق پارس می‌شه) و کل onclick بی‌صدا از کار می‌افته، بدون هیچ خطای قابل مشاهده در Network tab. همیشه `onclick="fn('{{ $model->id }})'"` (با کوتیشن تک) بنویس وقتی کلید UUID/رشته‌ایه.
11. **⭐ کلیک روی یک `<a>` که هم باید یک فراخوانی fetch (مثل mark-as-read) رو انجام بده هم ناوبری کنه، باید `e.preventDefault()` بزنه و ناوبری واقعی رو داخل `.then()`/`.finally()` بعد از تکمیل fetch انجام بده — نه این‌که fetch رو بدون منتظر موندن fire-and-forget کنه و اجازه بده مرورگر بلافاصله navigate کنه.** مرورگر معمولاً درخواست‌های در حال اجرا رو هنگام ناوبری صفحه لغو می‌کنه، پس نسخه‌ی fire-and-forget عملاً هیچ‌وقت به سرور نمی‌رسه (نمونه‌ی واقعی: صفحه‌ی `specialist/notifications.blade.php` — کلیک روی ردیف نوتیفیکیشن باعث می‌شد شمارنده‌ی خونده‌نشده‌ها کم نشه، چون درخواست mark-as-read قبل از رسیدن به سرور توسط ناوبری صفحه cancel می‌شد؛ الگوی درست همون چیزیه که در dropdown هدر (`fetchLatestNotifications`/`markNotificationAsRead` در `layouts/specialist.blade.php`) از اول درست پیاده‌سازی شده بود).
---

## ✅ هشدار حیاتی سابق — رفع شد در فاز R-Jobs (کشف‌شده در R-AdminWallet)
> **وضعیت: رفع شد.** در فاز R-Jobs تصمیم بیزنسی گرفته شد API واقعی Payout زرین‌پال وصل بشه (نه حذف دکمه). به بخش «✅ R-Jobs» بالا نگاه کن: `ZarinpalPayoutService` + `ProcessWithdrawalJob` جایگزین mock زیر شدن. متن اصلی هشدار فقط برای مرجع تاریخی نگه داشته شده:

`AdminWithdrawalController::autoPayout()` (تسویه‌ی آنلاین/فوری برداشت از طریق زرین‌پال) **یک پیاده‌سازی mock/شبیه‌سازی‌شده بود، نه فیچر واقعی متصل به درگاه:**
```php
$isSuccessful = true;                          // همیشه true — هیچ API واقعی صدا زده نمی‌شود
$referenceCode = "ZRP-" . rand(100000, 999999); // کد ارجاع ساختگی، نه از زرین‌پال
```
کد واقعی درخواست Payout به زرین‌پال (endpoint، هدرها، پارامترها) به‌صورت کامنت آماده در همون متد وجود داره ولی هیچ‌وقت فعال نشده:
```php
/*
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.zarinpal.api_key'),
])->post('https://api.zarinpal.com/pg/v4/payout.json', [...]);
*/
```
**یعنی چی؟** الان اگه ادمین دکمه‌ی «تایید پرداخت» (auto-payout) رو در `withdrawal-show.blade.php` بزنه، سیستم `WithdrawalRequest` رو `completed` می‌کنه و پیام «تسویه با موفقیت انجام شد» نشون می‌ده — **بدون اینکه واقعاً پولی جابه‌جا شده باشه.** این رفتار در production هم دقیقاً همینه، نه فقط dev/test.

**قبل از هر استفاده‌ی واقعی از این دکمه باید یکی از این دو تصمیم گرفته بشه:**
- یا API واقعی Payout زرین‌پال (کلید API مخصوص Payout، نه فقط API عادی پرداخت) وصل و تست بشه
- یا این گزینه کلاً از UI حذف بشه تا فقط مسیر «تایید دستی + وارد کردن کد پیگیری واقعی» (`approve()`) در دسترس بمونه — که در حال حاضر تنها مسیر واقعاً امن و صادق برای تسویه‌ست

این TODO **اولویت بالا و مستقل از ترتیب فازهای رفکتور**ه؛ در R-AdminWallet فقط کشف/مستند شد، عمداً فیکس نشد چون نیاز به تصمیم بیزنسی داره، نه صرفاً رفکتور کد.

## ✅ نکته‌ی امنیتی باز — رفع شد (کشف‌شده در R-AdminBlog، بازبینی و فیکس نهایی ۲۰۲۶-۰۷-۲۶)
> **وضعیت: رفع شد.** بازبینی این آیتم در جلسه‌ی ۲۰۲۶-۰۷-۲۶ نشون داد وضعیت واقعی کدبیس با چیزی که این بخش قبلاً مستند کرده بود فرق داشت — نه یک ریسک امنیتی زنده، بلکه چند روت کاملاً شکسته. متن اصلی هشدار فقط برای مرجع تاریخی نگه داشته شده، بعد از توضیح یافته‌ی جدید:

**یافته‌ی واقعی این بازبینی:** `App\Http\Controllers\User\BlogController` (کنترلر عمومی/غیر ادمین) در کدبیس فعلی **اصلاً متدهای `store`/`update`/`destroy`/`getCategories` را نداره** — فقط `index()`/`show()` روی این کلاس تعریف شده (به‌احتمال زیاد در یک ویرایش قبلی/محلی حذف شده، بدون این‌که مستندسازی این بخش به‌روز بشه؛ دقیقاً همون الگوی «فیکس/تغییر روی فایل local اعمال شده ولی سند بهش نرسیده» که چند بار دیگه هم در این پروژه دیده شده). **اما ۳+۱ فایل route هنوز به این متدهای ناموجود اشاره می‌کردن:**
- `routes/api/admin/services.php`: `Route::post('/posts', [BlogController::class, 'store'])`، `put('/posts/{post}', 'update')`، `delete('/posts/{post}', 'destroy')`
- `routes/api/public/blog.php`: `Route::get('/categories', [BlogController::class, 'getCategories'])`

یعنی فراخوانی هر کدوم از این روت‌ها فتال ارور «Call to undefined method» می‌داد — نه نشتی امنیتی، بلکه کد کاملاً مرده و شکسته.

**نکته‌ی مهم درباره‌ی ریسک امنیتی که قبلاً مستند شده بود**: بررسی `routes/api.php` نشون داد روت‌های `store`/`update`/`destroy` بالا از اول هم داخل `Route::middleware('admin')->prefix('admin')` (که به `['auth', 'admin']` resolve می‌شه، طبق `bootstrap/app.php`) بودن — یعنی حتی اگه متدها وجود داشتن، این‌ها هیچ‌وقت واقعاً بدون احراز هویت در دسترس نبودن. تنها روت واقعاً بدون میدل‌ور، `getCategories` (در `routes/api/public/blog.php`) بود، ولی چون فقط دسته‌بندی‌های عمومی رو لیست می‌کرد (نه نوشتن/حذف)، ریسک امنیتی واقعی‌ای نداشت؛ فقط بابت متد ناموجود فتال می‌داد.

**تأیید شد که CRUD واقعی وبلاگ کاملاً جای دیگه‌ای (از فاز R-AdminBlog) پیاده‌سازی شده و این روت‌های API هیچ‌جا (نه در `resources/js`، نه در هیچ Blade ای) مصرف نمی‌شدن** — یعنی حذفشون هیچ فانکشنالیتی رو نمی‌شکنه.

**فیکس نهایی:**
1. بلوک روت `blog` (`store`/`update`/`destroy`) از `routes/api/admin/services.php` حذف شد (بلوک `gallery` همون فایل دست‌نخورده موند — بحث گالری خارج از scope همین بازبینیه، به `R-Cleanup-DeadCode` نگاه کن).
2. فایل `routes/api/public/blog.php` (شامل `index`/`getCategories` تکراری و بلااستفاده) کلاً حذف شد؛ `require` مربوطه در `routes/api.php` هم حذف شد.

معادل امن و درست CRUD وبلاگ (با authorization، Form Request، Service) از فاز R-AdminBlog در `Admin\Blog\AdminBlogController`/`AdminBlogPostActionController`/`AdminBlogCategoryController` (routes/admin/blog.php) بدون تغییر باقیه و تنها مسیر معتبر مدیریت وبلاگه.

## ✅ رفع مستقل (۲۰۲۶-۰۷-۲۶): بازگشت باگ حساسیت به حروف در نام فایل Blade (سومین/چهارمین نمونه)
حین بازبینی «نکته‌ی امنیتی باز» بالا، به‌صورت جانبی مشخص شد الگوی مستندشده‌ی «فایل‌نام حساس به حروف که فقط روی Windows/XAMPP کار می‌کنه، روی Linux/production فتال ارور View not found می‌ده» (قبلاً در R-Events با `Walletadminservice.php`/`Checkpasswordstrengthrequest.php`، و در رفع مستقل گزارشات ادمین با `admin/notifications/Show.blade.php` دیده شده بود) دوباره در کدبیس فعلی وجود داره:

- `resources/views/blog/Index.blade.php` و `resources/views/blog/Show.blade.php` — کنترلر (`App\Http\Controllers\User\BlogController`) با حروف کوچک صداشون می‌زنه (`view('blog.index')`/`view('blog.show', ...)`). این دو فایل از فاز «رفع مستقل: صفحات عمومی وبلاگ» از صفر ساخته شده بودن ولی ظاهراً روی دیسک کاربر با حرف اول بزرگ ذخیره شدن.
- `resources/views/admin/notifications/Show.blade.php` — **رگرسیون**: این دقیقاً همون فایلی بود که در بخش «رفع مستقل: باگ‌های صفحه‌ی گزارشات ادمین» قبلاً یک‌بار rename شده بود، ولی طبق همون هشدار مستندشده در انتهای اون بخش («فیکس مستند شده ولی روی فایل local کاربر اعمال/کامیت نشده»)، در این زیپ دوباره با حرف بزرگ دیده شد.

هر سه فایل به حروف کوچک rename شدن (`index.blade.php`, `show.blade.php`, `show.blade.php`). **یادآوری تکراری برای کاربر**: لطفاً قبل از کامیت نهایی این جلسه هم، مثل دفعه‌ی قبل، چک بشه این rename ها واقعاً در ریپو ذخیره می‌شن — این سومین باری‌ه که این الگوی خاص (نام‌گذاری حساس به حروف در Blade) در پروژه دیده می‌شه، پس ارزش داره در چک‌لیست قبل از هر deploy به production یک `find resources/views -regex '.*/[A-Z][^/]*\.blade\.php'` اجرا بشه تا هر فایل Blade با حرف اول بزرگ زودتر از deploy شکار بشه، نه بعدش.

## 🟡 نکته‌ی عملیاتی — کامل بودن فایل زیپ آپلودی (کشف‌شده ۲۰۲۶-۰۷-۲۵)
یک‌بار زیپ آپلودشده (`Beauty-Salon.zip`) باز شد ولی **کل پوشه‌ی `resources/views` توش نبود** (فقط `resources/app`, `resources/js`, `resources/css`, `resources/lang`, `resources/vendor` وجود داشت — هیچ فایل `.blade.php`ای به‌جز override پیجینیشن پیدا نشد). این باعث شد کار روی این جلسه محدود به کنترلر/route/migration بمونه و ویرایش Blade ممکن نبود. **درس**: قبل از اتکا به یک زیپ برای کار روی Blade، حتماً چک کن `resources/views` واقعاً داخلشه (`find . -iname "*.blade.php" | wc -l` باید عدد قابل توجهی برگردونه، نه صفر/یک) — اگه نبود، از کاربر بخواه zip رو با پوشه‌ی views کامل دوباره بفرسته، به‌جای فرض کردن که پروژه واقعاً Blade نداره یا حدس زدن محتوای فایل از روی مستندات قبلی.

## ✅ نکته‌ی محیطی — Storage Symlink روی Windows/XAMPP — رفع شد (کشف‌شده حین R-AdminAnnouncement-Gallery)
> **وضعیت: رفع شد (۲۰۲۶-۰۸-۰۷، توسط کاربر).** کد از قبل (بازبینی ۲۰۲۶-۰۸-۰۲) کاملاً سالم تأیید شده بود (مسیر seed/آپلود یکسانن، `config/filesystems.php` استاندارده) — مشکل صرفاً محیطی بود (Windows/XAMPP)، نه باگ کد. کاربر دستورات دستی مستندشده (`rmdir public\storage` + `php artisan storage:link` با ترمینال Administrator) رو اجرا کرد و تصاویر seed‌شده‌ی گالری الان درست لود می‌شن. متن اصلی هشدار فقط برای مرجع تاریخی نگه داشته شده:

بعد از تبدیل صفحه‌ی گالری به Blade، تصاویر seed‌شده‌ی قدیمی (`gallery/image2.jpg` تا `image8.jpg`) با خطای **403 Forbidden** روی `/storage/gallery/...` مواجه شدن، در حالی که تصویر جدیدتری که مستقیم از طریق فرم آپلود شده بود (`iJu8ICNX...webp`) مشکلی نداشت. این یک باگ کد **نیست** — به‌احتمال زیاد symlink `public/storage` (ساخته‌شده با `php artisan storage:link`) روی Windows بدون اجرای ترمینال با دسترسی Administrator به‌درستی کار نمی‌کنه، یا NTFS/Apache (XAMPP) permission روی زیرپوشه‌ی خاص `storage/app/public/gallery` با بقیه‌ی مسیر متفاوته. **قبل از تحویل production**، این موارد رو چک کن:
1. `php artisan storage:link` رو با ترمینال Administrator دوباره اجرا کن.
2. مطمئن شو `public/storage` واقعاً یک symbolic link معتبره، نه یک junction خراب یا پوشه‌ی خالی.
3. Permission پوشه‌ی `storage/app/public/gallery` رو با بقیه‌ی زیرپوشه‌های `storage/app/public` مقایسه کن.

---

## Design Tokens — صفحات کاربر عادی (Customer)
```css
--rasta-gold: #C9A24B;
--rasta-gold-light: #E6CD8A;
--rasta-cream: #F8F3E9;
--rasta-dark: #1A1410;
--rasta-brown: #2E2117;
```
لایوت: `@extends('layouts.app')` | فونت: Vazirmatn

## Design Tokens — پنل متخصص (Specialist)
```css
--specialist-bg: #1F1424;
--specialist-surface: #2C1B32;
--specialist-border: #3A2640;
--specialist-plum-light: #E0B8E8;
--specialist-plum-mid: #D8AEE0;
--specialist-plum-muted: #B98FC4;
--specialist-text: #F3E1F7;
--specialist-text-dim: #C9A6D1;
--specialist-inactive: #7A5C82;
```
دکمه اصلی: `linear-gradient(135deg, #D8AEE0, #A85FB8)` با `color: #250D2B`
لایوت: `@extends('layouts.specialist')` | کلاس‌های کمکی: `.specialist-card`, `.specialist-cta`, `.persian-number`, `.fade-in`

## Design Tokens — پنل ادمین ✅
```css
--admin-bg: #F8FAFC;          /* slate-50 */
--admin-surface: #FFFFFF;
--admin-border: #E2E8F0;      /* slate-200 */
--admin-text: #1E293B;        /* slate-800 */
--admin-text-dim: #64748B;    /* slate-500 */
--admin-text-light: #94A3B8;  /* slate-400 */
--admin-accent: #334155;      /* slate-700 */
--admin-accent-hover: #1E293B;
--admin-accent-light: #F1F5F9;/* slate-100 */
```
لایوت: `@extends('layouts.admin')`
رنگ‌های وضعیتی سبز/کهربایی/قرمز معنایی باقی می‌مونن — فقط آبی برند حذف شده.
جی‌کوئری/بوت‌استرپ لود نمی‌شن (فقط Tailwind + SweetAlert2 — به بخش «نکات فنی Blade» بالا نگاه کن).

---

## ⭐ تقویم شمسی خودکفا (jcal)
جایگزین `persian-datepicker` / `persian-date` از CDN. بدون هیچ وابستگی خارجی، الگوریتم تبدیل میلادی↔شمسی دستی نوشته شده. ورودی‌ها `readonly` (فقط با کلیک روی تقویم پر می‌شن). فقط تاریخ رو مدیریت می‌کنه — برای فیلدهای `datetime` باید کنارش یک ورودی `type="time"` جدا اضافه بشه (به بخش «نکات فنی Blade» بالا نگاه کن).

- **پنل کاربر عادی**: رنگ طلایی
- **پنل متخصص**: رنگ بنفش
- **پنل ادمین**: از CSS variables (`--admin-accent` و غیره)

فایل‌های تبدیل‌شده تا الان: `user/wallet/transactions`, `specialist/wallet/transactions`, `specialist/bookings`, `specialist/reviews/index`, `specialist/reports/index`, `specialist/leaves-create`, `admin/reports/index`, `admin/announcements/create`, `admin/announcements/edit` (این دو تای آخر با ورودی `time` جدا، چون فیلد `datetime` هستن).

اگه جای دیگه‌ای هنوز از `persian-datepicker` استفاده می‌کنه و مشکل ظاهری داره، همین الگو رو پیاده کن.

---

## معماری کلیدی
- **درآمد متخصص**: Observer روی `payment_status→paid`، فرمول `prepayment × (1 - commission%/100)` که `commission%` از `Specialist::getEffectiveCommissionRate()` میاد (اختصاصی یا global)، اول به `pending_amount` می‌ره، با `php artisan wallet:settle-pending` (روزانه ۰۱:۰۰، شیدول‌شده در `bootstrap/app.php`) به `balance` منتقل می‌شه. **لوکال: باید دستی اجرا بشه یا `schedule:work` باز بمونه.** ⭐ علاوه بر این، ادمین از پنل هم می‌تونه این تسویه رو دستی و فوری انجام بده (همه‌ی متخصصین یا فقط یکی، با یا بدون نادیده‌گرفتن مهلت) — به بخش «فیچر تسویه‌ی دستی کیف‌پول توسط ادمین» نگاه کن.
- **امتیاز وفاداری**: Observer فرمول `5 + floor(prepayment/points_per_amount)` (مقدار `points_per_amount` از جدول `loyalty_settings`)، توضیح `"رزرو نوبت #ID"`، انقضا از `points_expiry_months` همون جدول.
- **تبدیل امتیاز به کد تخفیف**: `LoyaltyService::redeemReward(int $userId, Reward $reward)` تنها منبع این منطق است — هم مسیر مشتری (`LoyaltyController::redeemReward`) هم مسیر ادمین (`LoyaltyAdminService::redeemRewardForUser`) از همین متد استفاده می‌کنن (به بخش‌های R-DiscountLogic/R-AdminLoyalty نگاه کن).
- **محاسبه‌ی تخفیف**: `App\Services\Discount\DiscountCalculator::calculate()` تنها منبع فرمول `percentage/fixed + سقف max_amount` در کل پروژه (به فاز R-DiscountLogic نگاه کن).
- **برداشت وجه متخصص**: دو مسیر معتبر — تایید دستی (`approve()`، کد پیگیری واقعی وارد می‌شه توسط ادمین) و تسویه‌ی آنلاین خودکار (`autoPayout()` → `ProcessWithdrawalJob`، از فاز R-Jobs به Payout واقعی زرین‌پال وصل شده و async اجرا می‌شه؛ دیگه mock نیست — به بخش «✅ R-Jobs» نگاه کن). درخواست برداشت با **مبلغ دستی** توسط خود متخصص از قبل در UI (`specialist/wallet/create-withdrawal.blade.php`) وجود داشت؛ باگ اتصال به Form Request اشتباه که مانع ثبتش می‌شد رفع شد (به بخش «فیچر تسویه‌ی دستی کیف‌پول توسط ادمین» نگاه کن). **⭐ باگ جدید کشف/رفع‌شده (۲۰۲۶-۰۷-۲۰)**: `setValue()` در JS مقدار دستی رو به مضرب `step` گرد می‌کرد (مانع وارد کردن موجودی کامل) + HTML5 browser validation روی اعداد فارسی با `novalidate` + `type="text"` به‌جای `type="number"` + `prepareForValidation` در Form Request برای تبدیل ارقام فارسی→انگلیسی- **Pagination**: `vendor/pagination/tailwind.blade.php` — خودکار پنل فعال رو تشخیص می‌ده (specialist/admin/user) و رنگ مناسب اعمال می‌کنه
- **Cron روی DirectAdmin**:
  ```
  * * * * * cd /home/[username]/public_html && php artisan schedule:run >> /dev/null 2>&1
  (از ۲۰۲۶-۰۹-۲۶: با `QUEUE_WORK_VIA_SCHEDULER=true` همین خط صف رو هم اجرا می‌کنه — `docs/deployment/SCHEDULER_AND_QUEUE.md`)
  ```
- **رفع داده‌های تاریخی تکراری**: `cleanup-duplicates.sql` آماده‌ست (SELECT اول، بعد DELETE)
- **سلسله‌مراتب چندسالنی (SaaS، برنامه‌ریزی‌شده)**: `super_admin` (بدون محدودیت سالن) → هر `admin` از طریق جدول pivot `salon_admins` (نه ستون مستقیم) به یک `Salon` وصل است — در فاز ۱ عملاً هر سالن یک ادمین دارد (قانون در سطح اپلیکیشن)، ساختار داده از روز اول آماده‌ی چند ادمین (فاز ۲) است. ایزولاسیون داده از طریق ستون `salon_id` + `BelongsToSalon` Global Scope روی تمام مدل‌های صاحب‌داده. به بخش «SaaS چندسالنی» در انتهای سند نگاه کن.

---

## ⭐ فیچر تکمیل‌شده: Per-specialist commission
هر متخصص می‌تونه نرخ کمیسیون اختصاصی داشته باشه که `WalletSetting.admin_commission_percentage` (نرخ سراسری) رو override می‌کنه.

- Migration: `add_commission_rate_to_specialists_table` — ستون nullable `commission_rate` (decimal 5,2) روی جدول `specialists`
- `app/Models/Specialist.php` — `commission_rate` در `$fillable`/`$casts`، متد:
  ```php
  public function getEffectiveCommissionRate(): float
  {
      if (!is_null($this->commission_rate)) {
          return (float) $this->commission_rate;
      }
      $settings = \App\Models\WalletSetting::first();
      return (float) ($settings->admin_commission_percentage ?? 10);
  }
  ```
- `app/Observers/BookingObserver.php` — `addIncomeAndCommission()` از `$specialist->getEffectiveCommissionRate()` استفاده می‌کنه به‌جای مستقیم خواندن `WalletSetting`
- `app/Http/Controllers/Admin/AdminSpecialistController.php` — validation + پردازش `commission_rate` در `store()`/`update()`
- `resources/views/admin/specialists/{create, edit}.blade.php` — فیلد «نرخ کمیسیون اختصاصی» با راهنمای نرخ global فعلی
- `app/Http/Controllers/Specialist/SpecialistReportController.php` — از `$specialist->getEffectiveCommissionRate()` استفاده می‌کنه
- `admin/reports/index.blade.php` — ستون‌های «نرخ کمیسیون» و «سهم متخصص» در جدول عملکرد متخصصین

**نکته:** `AdminReportsController` عمداً درآمد خام (بدون کسر کمیسیون) رو در ستون «درآمد کل» نشون می‌ده چون اون عدد گردش مالی کل سالنه؛ سهم واقعی متخصص در ستون جدا `specialist_share` محاسبه می‌شه.

---

## ⭐ فیچرهای برنامه‌ریزی‌شده (نه رفکتور — بعد از اتمام فازهای رفکتور بررسی شود)

### R-AdminDiscountCode — پنل مدیریت مستقل کد تخفیف
✅ **تکمیل شد (۲۰۲۶-۰۸-۰۲/۰۳)** — به بخش «⭐ رفع مستقل ... تکمیل ۹ کاندید/کار باز پراکنده» پایین‌تر نگاه کن، آیتم ۹. متن اصلی پلن فقط برای مرجع تاریخی نگه داشته شده:

هدف: امکان ساخت/ویرایش/حذف دستی کد تخفیف توسط ادمین، مستقل از سیستم امتیاز/loyalty فعلی (که در آن کد تخفیف فقط از طریق `redeemReward` ساخته می‌شود).

پایه‌ی آماده (از فاز R-AdminForms): `App\Http\Requests\Admin\DiscountCode\StoreDiscountCodeRequest` (با `MaxPercentage` rule)، `UpdateDiscountCodeRequest`.

باید ساخته شود:
- `App\Http\Controllers\Admin\DiscountCode\AdminDiscountCodeController` (index/create/store/edit/update/destroy)
- `App\Services\Admin\DiscountCode\AdminDiscountCodeService`
- Views: `admin/discount-codes/{index,create,edit}.blade.php`
- Routes: `routes/admin/discount-codes.php`
- لینک منو در سایدبار ادمین

⚠️ باید از `App\Services\Discount\DiscountCalculator` (ساخته‌شده در R-DiscountLogic) برای هرگونه پیش‌نمایش تخفیف در این پنل استفاده کند — نه بازنویسی مجدد فرمول.

### R-SaaS-MultiTenant — SuperAdmin + سالن‌ها + اشتراک (بازنگری نهایی، 🟡 در انتظار پیاده‌سازی)
طرح کامل در بخش «⭐⭐ فیچر برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی)» در انتهای سند مستند شده — شامل جدول `salons`، `salon_id` روی تمام مدل‌های صاحب‌داده، middleware تفکیک سالن، پنل `/superadmin`، جریان اشتراک ۱/۳/۶/۱۲ ماهه، و آدرس یکتای هر سالن (`/s/{slug}`).

---

## هدف کلی رفکتور
پیش از تست‌نویسی، پروژه باید طبق Clean Code، SOLID و معماری لایه‌بندی‌شده بازسازی کامل شود:
```
Controller  → فقط HTTP (request → validate → call Service → return Response)
Form Request → تمام validation rules (هیچ $request->validate inline نماند)
Service     → تمام منطق تجاری (محاسبات، تصمیم‌گیری، orchestration)
Policy      → تمام منطق مجوزدهی
Observer    → side effects خودکار روی تغییر مدل
Event/Listener → رویدادهای دامنه (جایگزین فراخوانی مستقیم)
Job         → عملیات سنگین/async (ارسال SMS، email، PDF)
Notification → اطلاع‌رسانی به کاربر (جایگزین notify مستقیم در کنترلر)
Helper/Trait → کد مشترک بین چند کلاس
Rule        → اعتبارسنجی سفارشی
Exception   → خطاهای دامنه با context لاگ
```

### ⭐ اصول کلیدی
- **SRP**: هر کلاس یک مسئولیت — کنترلر فقط HTTP، Service فقط منطق
- **DRY**: هیچ کدی دوبار نوشته نشود — متد/Service مشترک
- **Early Return**: جلوگیری از Nested if عمیق
- **Type Hinting**: همه‌ی پارامترها و return types صریح
- **Named Arguments**: در فراخوانی Service/Job برای خوانایی
- **No Silent Failures**: هر خطا log یا Exception داشته باشد

---

## وضعیت کلی پروژه
- ✅ صفحات کاربر عادی (تم طلایی): تکمیل (+ صفحات عمومی وبلاگ که جدا رفع شد)
- ✅ پنل متخصص (تم بنفش): تکمیل
- ✅ پنل ادمین — فاز ۱ تا ۴ کامل (لایوت، بوکینگ، افراد، خدمات/محتوا، مالی/سیستم) + Announcement/Gallery نهایتاً از SPA به Blade مهاجرت کردن (R-AdminAnnouncement-Gallery)
- ✅ فیچر: Per-specialist commission
- ✅ باگ‌فیکس: صفحه سفید پرداخت با کد تخفیف ۱۰۰٪
- ✅ باگ‌فیکس: پروفایل ناقص متخصص جدید (mismatch شماره موبایل)
- ✅ فاز: R-DiscountLogic (تحکیم منطق محاسبه‌ی تخفیف + رفع زنجیره‌ی باگ‌های واقعی در مسیر اعمال تخفیف)
- ✅ فاز: R-AdminAnnouncement-Gallery (مهاجرت نهایی از React SPA به Blade + رفع باگ پاداش لویالتی ذخیره نشدن)
- ✅ فاز: Leave-Migration (مهاجرت کامل SpecialistLeave→Leave + صفحه‌ی سراسری مرخصی‌های ادمین)
- ✅ WorkSchedule: فیچر کامل پیاده‌سازی شده بود، ولی تصمیم نهایی (۲۰۲۶-۰۸-۰۷) تغییر کرد — به‌جای نگه‌داشتن بدون استفاده، **کاملاً حذف شد** (نه merge با SpecialistSchedule، نه ادامه‌ی نگه‌داری موازی). به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کامل فیچر WorkSchedule» پایین‌تر نگاه کن.
- ✅ فاز: R-Events (فعال‌سازی کامل زیرساخت Event/Listener که تا قبلش هیچ‌وقت واقعاً کار نمی‌کرد + رفع کرش/برگشت‌وجه لغو نوبت مشتری + چرخه‌ی نوتیفیکیشن برداشت وجه + ۲ فایل با نام اشتباه که فقط روی Linux می‌شکستن + ششمین نمونه‌ی باگ is_admin)
- ✅ رفع مستقل: سه باگ عملکردی کشف‌شده با Laravel Telescope (کندی ۳۰+ ثانیه‌ای لاگین به‌خاطر تماس synchronous با Kavenegar، خالی‌ماندن کامل صفحه‌ی رزرو نوبت به‌خاطر Vue3/persian-date از CDN خارجی، N+1 در `/services`) + کش امتیاز وفاداری در نوار ناوبری + لاگ‌گذاری متمرکز پیامک برای مشاهده‌ی محلی کد OTP/تایید-رد نوبت/مرخصی/برداشت وجه
- ✅ رفع مستقل: فیچر تسویه‌ی دستی کیف‌پول توسط ادمین (همه‌ی متخصصین یا فقط یکی، با/بدون نادیده‌گرفتن مهلت تسویه) + رفع ثبت تکراری شیدول `wallet:settle-pending` + رفع باگ ۴۰۳ دائمی درخواست برداشت متخصص (اتصال کنترلر به Form Request کهنه/اشتباه که `hasRole('specialist')` غیرموجود می‌خواست)
- ✅ **باگ‌فیکس بحرانی جدید (۲۰۲۶-۰۷-۲۱)**: رفع نوتیفیکیشن/پیامک تکراری تایید برداشت وجه (و در واقع هر event صف‌شده‌ی پروژه) — ریشه: auto-discovery فعال کلاس والد `Illuminate\Foundation\Support\Providers\EventServiceProvider` که با override کردن `shouldDiscoverEvents()` به تنهایی خاموش نمی‌شد؛ فیکس نهایی `static::disableEventDiscovery()` در `boot()`. + پاکسازی ثبت تکراری providerها در `config/app.php`/`bootstrap/providers.php` + حذف بارگذاری تکراری routes در `RouteServiceProvider` + `lockForUpdate` دفاعی روی approve/reject برداشت + رفع دو باگ جدای صفحه‌ی نوتیفیکیشن متخصص (UUID بدون کوتیشن در onclick، race condition ناوبری/fetch) + رفع idempotency guard اشتباه (جدول `notifications` به‌جای `user_notifications`). به بخش «رفع مستقل: باگ حیاتی نوتیفیکیشن/پیامک تکراری...» نگاه کن.
- ✅ فاز: R-Jobs (انتقال عملیات سنگین به Jobs: یادآوری نوبت به‌صورت per-booking queued job، زیرساخت جنریک نوتیفیکیشن دسته‌جمعی، تسویه‌ی آنلاین برداشت وجه واقعاً به Payout زرین‌پال وصل شد و async شد + رفع باگ Blade که وضعیت `processing` رو کد مرده کرده بود، خروجی PDF/Excel گزارشات ادمین async شد با جدول `report_exports` جدید)
- ✅ رفع مستقل: باگ‌های صفحه‌ی گزارشات ادمین و نوتیفیکیشن هدر (دکمه‌های امروز/هفته/ماه بازه‌ی زمانی رو هم واقعاً تعیین می‌کنن + پیش‌فرض «امروز» موقع بارگذاری + رفع ستون ناموجود `payment_method` در ۳ جای مختلف + خروجی اکسل که کاملاً خالی بود + ستون تعداد نوبت همیشه صفر در PDF + لینک نوتیفیکیشن‌ها به هاست اشتباه (APP_URL vs هاست واقعی، در ۶ نوتیفیکیشن) + بج شمارنده‌ی نوتیفیکیشن هدر که بعد از خواندن آپدیت نمی‌شد + فایل‌نام حساس به حروف `Show.blade.php`)
- ✅ رفع مستقل (۲۰۲۶-۰۷-۲۵): باگ بحرانی `specialists.user_id NOT NULL` — ساخت متخصص جدید (پیش از ثبت‌نام خود شخص) با خطای Integrity constraint می‌شکست؛ migration nullable اضافه شد.
- ✅ فیچر جدید (۲۰۲۶-۰۷-۲۵): مرتب‌سازی هوشمند لیست «نوبت‌های من» (`/bookings`) بر اساس اولویت وضعیت (تایید‌شده/تکمیل‌شده → در انتظار → لغوشده)، جدیدترین در هر گروه بالاتر.
- ✅ رفع مستقل (۲۰۲۶-۰۷-۲۵): یادآوری پیامکی نوبت از «یک‌بار در روز ساعت ۱۸ برای کل نوبت‌های فردا» به «~۱ ساعت قبل از هر نوبت، هر ۱۰ دقیقه چک می‌شه» تغییر کرد.
- ✅ رفع مستقل (۲۰۲۶-۰۷-۲۶): بازبینی «نکته‌ی امنیتی باز» BlogController عمومی — مشخص شد متدهای مورد بحث (`store`/`update`/`destroy`/`getCategories`) دیگه اصلاً روی کنترلر وجود ندارن ولی ۲ فایل route هنوز بهشون اشاره می‌کردن (فتال ارور، نه نشتی امنیتی چون روت‌های نوشتنی از اول پشت میدل‌ور `admin` بودن)؛ روت‌های مرده حذف شدن + به‌صورت جانبی سومین/چهارمین نمونه‌ی باگ نام‌فایل حساس به حروف کشف/رفع شد (`blog/Index,Show.blade.php` + رگرسیون `admin/notifications/Show.blade.php`).
- ✅ فاز: R-Observers (تکمیل نهایی Observerها — تحکیم دیسپچ `PaymentSucceeded` در `BookingObserver` + رفع race condition واقعی overflow `used_count` کد تخفیف + تصمیم آگاهانه علیه ساخت `PaymentObserver`/`WithdrawalObserver` عمومی + رفع gap واقعی نوتیف auto-payout ناموفق + رفع رگرسیون `payment_method` در `AdminPaymentController` + رفع دسته‌بندی نادرست `gateway_payments` در گزارشات و وایر شدنش هم در صفحه‌ی وب هم در خروجی‌های PDF/Excel + شیت سوم «جزئیات خام نوبت‌ها» با پیوست landscape در PDF + شکیل‌سازی ظاهری هر سه شیت اکسل)
- ✅ فاز: R-Traits (استخراج `HasJalaliDates` در ۱۴ فایل + `HandlesApiResponse` در ۴ فایل، عمداً محدود به الگوی واقعاً یکسان بدون تحمیل قرارداد یکسان به ۱۵۰+ پاسخ JSON متفاوت دیگه‌ی پروژه)
- ✅ رفع مستقل (بحرانی/مالی، ۲۰۲۶-۰۷-۲۷): برگشت وجه لغو نوبت‌های پرداخت‌شده هیچ‌وقت واقعاً کار نمی‌کرد — `RefundService` به متد ناموجود وصل بود (فتال ارور)؛ به‌جای اتصال به گیت‌وی، مسیر کیف‌پولی (که از قبل برای مشتری/متخصص بود) برای ادمین هم فعال شد + پس‌گرفتن سهم متخصص/کمیسیون ادمین موقع لغو (`reverseIncome`/`deductCommission`) + رفع باگ تسویه‌ی مضاعف + پیشگیری از تسویه‌ی زودتر از برگزاری نوبت + دو باگ Blade/Notification لغو نوبت (۴۰۳ مشتری، لیبل مبهم/پیامک تکراری متخصص)
- ✅ فاز: R-DB-Transaction (بررسی کامل هر ۳۶ مورد `DB::transaction(` در `app/`؛ هیچ نمونه‌ای از الگوی خطرناک پیدا نشد؛ بدون تغییر فایل)
- ✅ فاز: R-TypeHints (۱۹۲ متد public بدون return type در ۵۱ فایل + استانداردسازی ۳۹ constructor به property promotion با `readonly` در کل `app/`)
- ✅ فاز: R-Cleanup-DeadCode، بخش اول (حذف ۲۳ فایل کد مرده + کشف زیردرخت React یتیم ۱۴ فایلی + رفع تصادم نام روت لویالتی؛ `WorkSchedule` در همون لحظه به تصمیم کاربر دست‌نخورده موند — بعداً در ۲۰۲۶-۰۸-۰۷ کاملاً حذف شد)
- ✅ رفع مستقل (۲۰۲۶-۰۸-۰۱): تکمیل کامل مسیر «پرداخت امن / ۲FA» (`payments.secure.*` + احراز هویت دو مرحله‌ای حساب کاربری) که کاملاً غیرقابل‌دسترسی و شکسته بود + حذف کد مرده‌ی React باقی‌مانده (`BookingActions.jsx`, `SecureForm.jsx`) + رفع باگ فعال مسیر import حروف کوچک `AnnouncementBanner`. به بخش «✅ رفع مستقل: تکمیل مسیر پرداخت امن / ۲FA» پایین‌تر نگاه کن.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۰۱، بلافاصله بعد): باگ بحرانی OTP کد ۲FA که هیچ‌وقت واقعاً تایید نمی‌شد (`Cache::put()` با درایور `array` بین دو درخواست خالی می‌شد) + تاخیر ۲۱-۲۹ ثانیه‌ای هر ارسال کد (تماس synchronous به Kavenegar). به بخش «✅ رفع مستقل (۲۰۲۶-۰۸-۰۱، بلافاصله بعد از تکمیل مسیر بالا): باگ بحرانی — کد OTP هیچ‌وقت واقعاً تایید نمی‌شد» نگاه کن.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۰۲ تا ۰۸-۰۳): تکمیل ۹ کاندید/کار باز پراکنده‌ی این سند (جداول اکسل گزارشات + نمودار، حذف React یتیم `TwoFactorAuth.jsx`، فیکس فیلتر دسته‌بندی خدمات، بررسی Storage Symlink، حذف `deductCancellationFee`، بررسی `AdminSpecialistService` تکراری، مهاجرت `persian-date`→jcal در reschedule، بررسی محدودیت‌های Kavenegar، و فیچر جدید `R-AdminDiscountCode`) + کشف/رفع دو باگ بحرانی export گزارش (Excel هیچ‌وقت ساخته نمی‌شد، دانلود PDF/Excel فتال ارور می‌داد) + چهارمین رگرسیون فایل‌نام حساس به حروف (`CheckPasswordStrengthRequest`). به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۲ تا ۰۸-۰۳): تکمیل ۹ کاندید/کار باز پراکنده‌ی این سند» پایین‌تر نگاه کن.
- ✅⭐ فیچر بزرگ (۲۰۲۶-۰۸-۰۳): بازطراحی کامل پیش‌پرداخت نوبت + منطق تخفیف — پیش‌پرداخت از هاردکد به درصدی از قیمت خدمت (با سقف + کاملاً قابل‌تنظیم توسط ادمین) تغییر کرد؛ تخفیف دیگه پیش‌پرداخت رو کم نمی‌کنه، از «باقی‌مانده»ی نقدی موقع نوبت کم می‌شه (رفع یک باگ منطقی مخفی که تخفیف رو برای مشتری عملاً بی‌اثر می‌کرد) + نمایش شفاف قیمت‌کل/پیش‌پرداخت/باقی‌مانده در تمام صفحات مرتبط (مشتری و متخصص) + پیامک/نوتیفیکیشن هر دو نقش. به بخش «⭐⭐ فیچر بزرگ (۲۰۲۶-۰۸-۰۳): بازطراحی کامل پیش‌پرداخت نوبت + منطق تخفیف» پایین‌تر نگاه کن.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۰۴): سه باگ کشف‌شده حین تست واقعی فیچر بالا توسط کاربر — صفحه‌ی «رزرو نوبت جدید» هنوز مبلغ پیش‌پرداخت هاردکد قدیمی (۵۰,۰۰۰) رو نشون می‌داد + پیامک تایید نوبت (هم مشتری هم متخصص) تخفیف رو از «باقی‌مانده» کم نمی‌کرد (در حالی که نمایش داخل اپ درست بود) + افزودن «باقی‌مانده» به صفحه‌ی «پرداخت با موفقیت انجام شد». به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۴): سه باگ کشف‌شده حین تست واقعی» پایین‌تر نگاه کن.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۰۶): تکمیل داشبورد امنیتی حساب کاربری (`/security/dashboard,sessions,activity`) + پنل امنیت ادمین (`Admin\Security\...` جدید) — روت‌شده و در دسترس بود ولی هر بخشش فتال ارور می‌داد (متدهای ناموجود، جدول security_logs بدون migration، writer/reader لاگ از دو منبع جدا) + رفع باگ user_id گم‌شده در لاگ‌های ورود ناموفق + رفع حفره‌ی امنیتی واقعی در `routes/api.php` (`auth:sanctum` فقط در production اعمال می‌شد؛ کشف شد `/api/security/*` و `/api/loyalty/*` هر دو کاملاً یتیم بودن). به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۶): تکمیل داشبورد امنیتی حساب کاربری...» پایین‌تر نگاه کن.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کد مرده‌ی ویجت آمار نوبت‌های ادمین (`booking-stats`/`BookingStats.jsx`) — کشف‌شده در بررسی جانبی جلسه‌ی امنیتی قبل، به این جلسه موکول شده بود؛ تأیید شد ادعای مستندشده‌ی «متد ناموجود/تایم‌اوت» نادرست/کهنه بود (خود متد سالم و سریع بود)، مشکل واقعی یتیم‌بودن کامل زنجیره (کامپوننت React → mount → هر دو روت وب/API → کنترلر/سرویس) بود؛ کل زنجیره حذف شد. به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کد مرده‌ی ویجت آمار نوبت‌های ادمین» پایین‌تر نگاه کن.
- ✅ فاز: R-Pint (اجرای `php vendor/bin/pint` با پریست Laravel روی کل پروژه — ۳۰۳ فایل PHP در `app/`, `database/`, `routes/`, `config/`, `lang/` فرمت شدن؛ صرفاً تغییرات ظاهری/سبکی، بدون تغییر منطق؛ تأیید شده با `pint --test` تمیز + `php -l` روی هر ۳۰۳ فایل). **این فاز آخرین آیتم رفکتور بود — کل چرخه‌ی رفکتور کامل شد.**
- ✅ **فاز رفکتور کامل تکمیل شد** (تمام آیتم‌های «ترتیب اجرای فازها» ✅ هستن)
- ✅ فاز: تست‌نویسی کامل و سخت‌گیرانه — نشست اول تا یازدهم (۲۰۲۶-۰۸-۰۹ تا ۰۸-۲۳) **تکمیل و رسماً بسته شد**؛ **۸۶۲ تست/۱۹۲۳ assertion سبز + ۱ skip مستند**، ~۴۰ باگ واقعی کشف/رفع‌شده در مجموع (شامل باگ بحرانی recursion میدل‌ور admin که کل /api/admin/* را OOM می‌کرد، کندی ۲۰۳ ثانیه‌ای ناشی از تلاش واقعی ارسال SMS در محیط testing، چند مدل بدون HasFactory/فکتوری گمشده، کانال‌های لاگ امنیتی/مالی که با env خالی بی‌صدا به emergency logger منحرف می‌شدن، ۲ فرم Form Request با MaxPercentage بدون قید نوع، ۲ view کاملاً گمشده در پنل نظرات/لویالتی، ستون‌های approved_at/rejected_at گمشده از fillable مدل Leave، یک باگ ظریف route-model-binding سراسری که getSpecialistsByService را همیشه می‌شکست، ⭐ باگ بحرانی نشست ششم که کل پنل خودِ متخصص را به‌خاطر hasRole('specialist') در SpecialistPolicy/ReviewPolicy همیشه ۴۰۳ می‌کرد، باگ نوع بازگشتی parseJalaliOrFail که ویرایش تاریخ انتشار وبلاگ را همیشه می‌شکست، همون باگ route-model-binding این‌بار برای {specialist} در ReviewController، ⭐ ستون notes گمشده از schema جدول bookings، TwoFactorController که خطای اعتبارسنجی را قورت می‌داد و ۵۰۰ عمومی برمی‌گرداند، باگ بحرانی نشست هشتم که به‌خاطر قید سراسری Route::pattern('id', hex-only) پایان‌دادن به یک نشست فعال خاص از پنل امنیت کاربر تقریباً همیشه ۴۰۴ می‌داد، صفحه‌ی «پرداخت با موفقیت» که با booking خالی/متعلق‌به‌دیگری فتال می‌داد، ⭐ نشست دهم: میدل‌ور 'verified' که به‌خاطر عدم پیاده‌سازی MustVerifyEmail روی مدل User همیشه no-op بود (کاربران ادمین‌ساخته هیچ‌وقت واقعاً phone-verified نمی‌شدن)، یک تست فلیکی واقعی در BookingObserverTest به‌خاطر ساعت تصادفی factory، ۸ کنترلر کاملاً مرده‌ی Breeze، روت‌های کاملاً یتیم و بخشاً شکسته‌ی /api/loyalty/*، PhoneVerificationService::sendCode() که هنوز synchronous بود، سه مورد دیگر از الگوی env('KEY','default') با مقدار خالی (CACHE_PREFIX/TELESCOPE_PATH/TELESCOPE_ENABLED) + یک کلید config کاملاً wire‌نشده (verification_code_expire_minutes)، و کد کاملاً مرده در RouteServiceProvider شامل یک متد باگ‌دار دیگر (getHomeForUser با همون hasRole('specialists') پلورال)). به بخش «⭐⭐ فاز تست‌نویسی» پایین‌تر نگاه کن.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۲۶): فیلتر تاریخ صفحه‌ی امنیت ادمین به jcal مهاجرت کرد + دو باگ پیامک تکراری رفع شد (ثبت نوبت پیش از پرداخت + تشکر تکراری زمان تکمیل نوبت، به‌همراه کشف جانبی سومی در لغو نوبت) + پنل کامل «تنظیمات اطلاع‌رسانی» ساخته شد (کنترل مستقل پیامک/نوتیفیکیشن‌داخل‌برنامه‌ای/ربات تلگرام یا بله به‌ازای هر رویداد، با NotificationSettingService مرکزی و کانال جدید TelegramChannel)؛ هر ۱۸ کلاس Notification پروژه + ارسال‌های مستقیم پیامک در BookingObserver/ReviewService به این سیستم وایر شدند.
- ✅ رفع مستقل (۲۰۲۶-۰۸-۲۷): ۸ باگ واقعی دیگر کشف/رفع شد — دو رویداد پیامک تکراری قبلی (ثبت نوبت پیش از پرداخت، تشکر تکراری تکمیل نوبت) کلاً از رجیستری تنظیمات حذف شدن (نه فقط پیش‌فرض‌خاموش) + باگ کد تخفیف در صفحه‌ی confirm (روت مرده‌ی /api/check-discount) + نبود پیامک کسب امتیاز بعد از ثبت نظر (User::addLoyaltyPoints هیچ‌وقت نوتیف نمی‌فرستاد) + لینک نظرسنجی localhost + نبود پیامک نظر جدید به متخصص (NewReviewReceivedNotification بدون toSms) + فیلتر جداگانه‌ی اعلانات متخصص (تب‌بندی بر اساس دسته) + کشف باگ لینک اعلان «نظر جدید» که همیشه به داشبورد می‌رفت (کلید review_id ناشناخته) + باگ مالی واقعی: شکست auto-payout زرین‌پال هیچ‌وقت موجودی متخصص را برنمی‌گرداند با اینکه پیامک می‌گفت برگشته.

- 🟡 **فیچر برنامه‌ریزی‌شده (بازنگری نهایی، درخواست ۲۰۲۶-۰۸-۲۸): SaaS چندسالنی — SuperAdmin + سالن‌ها + اشتراک** — تبدیل معماری از یک برند واحد به پلتفرم چند-مستأجری؛ هر سالن نام نمایشی قابل‌تغییر، آدرس یکتای ثابت (`/s/{slug}`)، اشتراک ۱/۳/۶/۱۲ ماهه، سقف متخصص، و دقیقاً یک ادمین دارد. ایزولاسیون کامل داده از طریق `salon_id` + Global Scope. جزئیات کامل در بخش انتهایی سند.
- 🔴 **باگ فوری (مستقل از SaaS، در حال رفع): تداخل نوبت دستی ادمین با نوبت آنلاین** — `AdminBookingController::store()` بدون هیچ چک تداخلی مستقیماً `Booking::create()` می‌زند؛ نوبت تلفنی/حضوری می‌تواند با نوبت آنلاین همان ساعت تداخل کند. طرح رفع (چک اسلات مشترک + قید یکتای دیتابیسی + ستون `source` + جستجو/ساخت سریع مشتری) در بخش «🚨 باگ فوری» زیر همین بخش مستند شده.
---

## فایل‌های کامل‌شده — صفحات کاربر عادی
- `layouts/app.blade.php`, `layouts/guest.blade.php`, `home.blade.php` — لینک ناوبری «وبلاگ» به منوی دسکتاپ/موبایل اضافه شد
- `dashboard.blade.php` — بازسازی کامل (قبلاً AdminDashboard.jsx به‌اشتباه mount می‌شد)
- `bookings/{create, confirm, index, show, success, failed}.blade.php`
  - `bookings/index.blade.php`: فیلتر `date` در کنترلر اضافه شد
  - `bookings/show.blade.php`: بازطراحی کامل + رفع باگ `payment_ref`→`payment_reference`؛ ⭐ **به‌روزرسانی (R-DiscountLogic)**: mount-point React `BookingActions.jsx` کلاً به Blade خالص تبدیل شد (دکمه‌های لغو/اعمال تخفیف با فرم معمولی؛ تغییر زمان/ثبت نظر به صفحات جدای موجود لینک می‌دن) — کامپوننت React قدیمی هیچ‌وقت booking id درستی نمی‌فرستاد و URL هاش با route های واقعی مطابقت نداشت. ⭐ **به‌روزرسانی (رفع مستقل ۲۰۲۶-۰۸-۰۱)**: لینک ثانویه‌ی «پرداخت امن با تایید دو مرحله‌ای» (`payments.secure.checkout`) کنار دکمه‌ی پرداخت معمولی اضافه شد.
  - `bookings/success.blade.php`: بازطراحی + نمایش شماره پیگیری
  - `bookings/reschedule.blade.php`: بازنویسی کامل Vue→vanilla JS
- `user/wallet/{index, transactions, transaction-show, charge, charge-success}.blade.php`
- `payment/{show, result, callback, failed}.blade.php` — رفع باگ payment_ref + رفع باگ صفحه سفید با تخفیف ۱۰۰٪ + ⭐ **باگ مالی رفع‌شده (R-DiscountLogic)**: دکمه‌ی «اعمال کد تخفیف» endpoint پیش‌نمایش (`check-discount`) رو صدا می‌زد نه `apply-discount` — کاربر مبلغ تخفیف‌خورده رو می‌دید ولی چیزی persist نمی‌شد و پرداخت با مبلغ کامل انجام می‌شد.
- `loyalty/{index, my-codes}.blade.php`
- `profile/{show, edit}.blade.php`, `reviews/{create, thank-you}.blade.php` — ⭐ **به‌روزرسانی (رفع مستقل ۲۰۲۶-۰۸-۰۱)**: `profile/edit.blade.php` یک کارت وضعیت/لینک به تنظیمات احراز هویت دو مرحله‌ای (`security.2fa`) گرفت.
- ⭐ `blog/index.blade.php`, `blog/show.blade.php` — فایل‌های کاملاً جدید (به بخش «رفع مستقل: صفحات عمومی وبلاگ» نگاه کن)
- ⭐ `payments/secure/{checkout, otp, verify, result}.blade.php` — فایل‌های کاملاً جدید (به بخش «✅ رفع مستقل: تکمیل مسیر پرداخت امن / ۲FA» پایین‌تر نگاه کن)
- ⭐ `auth/2fa/{index, setup, confirm}.blade.php` — فایل‌های کاملاً جدید (همون بخش)

## فایل‌های کامل‌شده — پنل متخصص
لایوت کامل + تمام صفحات: dashboard, bookings, booking-show, profile-show/edit, schedule, leaves/leaves-create, notifications, profile-not-found, reviews/index+show, wallet/{index, edit-iban, create-withdrawal, transactions}, reports/index, reports/pdf (تم خنثی/رسمی برای چاپ)
- `specialist/loyalty.blade.php` — صفحه‌ی امتیازهای متخصص برای نوبت‌هایی که خودش به‌عنوان مشتری رزرو می‌کنه
- `specialist/schedule.blade.php`: toggle switch‌ها کاملاً با JS درایو می‌شن
- `specialist/notifications.blade.php` + dropdown هدر: لینک هر اعلان بر اساس نوع اعلان تشخیص داده می‌شه
- `specialist/dashboard.blade.php` — بازطراحی کامل داشبورد با ApexCharts + رفع باگ `Str` not found
- **⭐ `specialist/wallet/create-withdrawal.blade.php` (به‌روزرسانی ۲۰۲۶-۰۷-۲۰)**: 
  - `novalidate` روی `&lt;form&gt;` برای غیرفعال کردن HTML5 browser validation
  - `type="text"` + `inputmode="numeric"` به‌جای `type="number"` (جلوگیری از خطای step validation مرورگر روی اعداد فارسی)
  - `setValue(value, shouldRound)` — دکمه‌ها ۱۰,۰۰۰ تومان جابه‌جا می‌کنن (با گرد کردن)، ورودی دستی هر مبلغی بین MIN و MAX قبول می‌کنه (بدون گرد کردن)
  - Validation client-side قبل از submit با پیام فارسی
  - ارسال عدد خام (بدون فرمت فارسی/کاما) به backend
  - `StoreWithdrawalRequest` با `prepareForValidation()` برای تبدیل ارقام فارسی→انگلیسی + حذف کاما/فاصله
## فایل‌های کامل‌شده — پنل ادمین (فاز ۱ تا ۴)
**فاز ۱:** `layouts/admin.blade.php`, `admin/dashboard.blade.php`, `admin/bookings/{index, show}.blade.php`

**فاز ۲:** `admin/specialists/{index, show, create, edit}.blade.php`, `admin/specialists/leaves/index.blade.php`, `admin/specialists/schedules/edit.blade.php`, `admin/users/{index, show, create, edit}.blade.php`

**فاز ۳:** `admin/services/{index, create, edit}.blade.php`, `admin/categories/{index, show, create, edit}.blade.php`, `admin/reviews/{index, show, stats}.blade.php`, `admin/blog/{index, create, edit, show}.blade.php` + `admin/blog/categories/{index, create, edit}.blade.php` (بازنویسی/تکمیل نهایی در R-AdminBlog)، `admin/gallery/index.blade.php`, `admin/announcements/index.blade.php` — ⚠️ **تصحیح مستندسازی**: این دو تای آخر در زمان ثبت این خط هنوز واقعاً Blade نبودن (فقط wrapper خالی داشتن، محتوا از React SPA میومد)؛ migration واقعی و کامل‌شون در فاز `R-AdminAnnouncement-Gallery` انجام شد.

**فاز ۴:**
- `admin/wallet/{index, show, settings, withdrawals, withdrawal-show}.blade.php` — ⭐ `index.blade.php` و `show.blade.php` بعداً برای فیچر تسویه‌ی دستی هم به‌روزرسانی شدن؛ ⭐ **`withdrawal-show.blade.php` (به‌روزرسانی ۲۰۲۶-۰۷-۲۰)**: فیکس فرم approve (فیلد `payment_reference` + `admin_note` + `@error` + `old()`) + فیکس with/elseif منطقی + دکمه reject `type="submit"` + بنر هشدار mock auto-payout- `admin/reports/index.blade.php` — بازنویسی کامل از React SPA به Blade + Chart.js (jcal، فیلتر بازه زمانی اختیاری/خالی پیش‌فرض، تب‌های درآمد/متخصصین/خدمات/رضایت)
- `admin/reports/pdf-report.blade.php` — letterhead رسمی + mPDF با `autoScriptToLang`/`autoLangToFont` فعال + ساختار جدول‌محور (نه div/float)
- `admin/loyalty/{index, show, edit}.blade.php` — بازنویسی نهایی در R-AdminLoyalty
- `admin/notifications/{index, Show}.blade.php`, `admin/profile/{show, edit}.blade.php`, `admin/search/index.blade.php`
- `admin/roles/{index, show, create, edit, assign}.blade.php`, `admin/permissions/{index, show, create, edit}.blade.php`
- جدول عملکرد متخصصین در `admin/reports/index.blade.php` دو ستون «نرخ کمیسیون» و «سهم متخصص (پس از کمیسیون)» داره

**فاز R-AdminAnnouncement-Gallery (تکمیل نهایی فاز ۳):**
- `admin/announcements/{index, create, edit}.blade.php` — CRUD کامل Blade با jcal برای `published_at`/`expires_at`
- `admin/gallery/index.blade.php` — CRUD کامل Blade با آپلود + دکمه‌ی ترتیب ▲▼ (به‌جای drag&drop پیچیده)
- `resources/js/admin.jsx` — کاملاً پاک‌سازی شد (تمام mount های ادمین حذف؛ فقط `booking-stats` سمت مشتری باقی موند)

---

## کنترلرهایی که نیاز به split ندارند
(زیر ۱۵۰ خط و تک‌مسئولیتی)

| کنترلر | خط | توضیح |
|--------|-----|-------|
| `AdminServiceController` | 95 | ✅ کوچک و تمیز |
| `AdminPaymentController` | 45 | ✅ |
| `AdminProfileController` | 56 | ✅ |
| `AdminSpecialistLeaveController` | 66→~ | ✅ (این ردیف به نسخه‌ی *بازنویسی‌شده*‌ی فاز Leave-Migration اشاره داره، نه یک فایل جدای قدیمی؛ خط ۶۶ مربوط به نسخه‌ی پیش از بازنویسیه) |
| `AdminSpecialistScheduleController` | 67 | ✅ |
| `NotificationController` | 45 | ✅ |
| `DashboardController` | 76 | ✅ |
| `ProfileController` | 77 | ✅ |
| `GalleryController` | 78 | ✅ |
| `AnnouncementController` | 83 | ✅ |
| `BlogController` (عمومی) | ~۹۰ | ✅ فقط `index`/`show` روی این کلاس تعریف شده‌ن (بازبینی ۲۰۲۶-۰۷-۲۶ تأیید کرد ۴ متد دیگه اصلاً وجود نداشتن؛ روت‌های مرده‌ی اشاره‌کننده حذف شدن) |
| `ServiceController` | 45 | ✅ |

> نکته: `Admin\Announcement\AdminAnnouncementController` و `Admin\Gallery\AdminGalleryController` (نسخه‌ی جدید، پس از R-AdminAnnouncement-Gallery) کنترلرهای جدا و متفاوتی از ردیف‌های `GalleryController`/`AnnouncementController` این جدول هستن؛ این جدول به کنترلرهای عمومی/غیر-ادمین قدیمی اشاره داره.

---

# فازهای رفکتور — جزئیات کامل

## ✅ فازهای تکمیل‌شده

### R1 — زیرساخت پایه
- `app/Exceptions/DomainException.php` — abstract base (`httpStatus`, `userMessage`, `context()`)
- `app/Exceptions/PaymentProcessingException.php`, `BookingNotAvailableException.php`, `InsufficientWalletBalanceException.php`, `DiscountCodeInvalidException.php`, `RescheduleNotAllowedException.php`, `OwnershipException.php`
- `app/Policies/{BookingPolicy, ReviewPolicy, SpecialistPolicy, UserWalletPolicy, SpecialistWalletPolicy}.php` — همه با `before()` hook
- `app/Providers/AuthServiceProvider.php` — نگاشت کامل Policy ها + `registerPolicies()`
- `bootstrap/providers.php` — ثبت AuthServiceProvider (قبلاً ثبت نشده بود)
- `bootstrap/app.php` — `withExceptions()` با logging context + JSON/web response
- `app/Http/Controllers/Controller.php` — `AuthorizesRequests` trait اضافه شد
- `app/Rules/ValidIban.php` — اعتبارسنجی ۲۴ رقم شبا؛ `app/Rules/MaxPercentage.php` — اعتبارسنجی درصد ≤ ۱۰۰

### R2 — استخراج Form Requests (کامل)
ساختار دایرکتوری فعلی بهتر از flat root است و نباید تغییر کند:
- `Requests/Auth/` — Login, Register, SendResetCode, ResetPassword, VerifyCode, NewPassword, PasswordResetLink, TwoFactorVerify
- `Requests/Booking/` — Confirm, Store, CheckDiscount, ApplyDiscount, Rate, UpdateReschedule
- `Requests/Specialist/` — UpdateProfile, UpdatePassword, UpdateSchedule, StoreLeave, UpdateIban (با ValidIban rule), StoreWithdrawal, RespondReview
- `Requests/Wallet/` — ChargeWallet (با prepareForValidation برای اعداد فارسی)
- `Requests/Payment/` — ProcessPayment
- `Requests/Profile/` — UpdatePassword
- `Requests/Security/` — CheckPasswordStrength
- `Requests/` (root-level) — StoreDiscountCode (با MaxPercentage), UpdateDiscountCode, StoreBlogPost (⚠️ الان بلااستفاده، جایگزینش در R-AdminBlog ساخته شد، کاندید حذف)، StoreGalleryImage، UpdateGalleryImage، ReorderGalleryImages

> ⚠️ **تصحیح (کشف‌شده در R-DiscountLogic، R-AdminAnnouncement-Gallery و فیچر تسویه‌ی دستی کیف‌پول)**: مستندسازی این فاز چند بار اشتباه از آب دراومده. ... **⭐ نمونه‌ی دوم و معکوس همین الگو (کشف‌شده در فیچر تسویه‌ی دستی کیف‌پول)**: ... **⭐ به‌روزرسانی (۲۰۲۶-۰۷-۲۰)**: `StoreWithdrawalRequest` نسخه‌ی درست (`Requests\Specialist\Wallet\Withdrawal\`) با `prepareForValidation()` برای تبدیل ارقام فارسی→انگلیسی + حذف کاما/فاصله به‌روزرسانی شد.
### R3 — تجزیه‌ی BookingController
- `BookingController.php` (کاهش از ۹۶۸ به ~100 خط) — فقط index, show, success, failed, rate + API methods
- `BookingReservationController.php` — create, confirm, store, cancel
- `BookingDiscountController.php` — check, apply
- `BookingAvailabilityController.php` — getAvailableTimeSlots, getAvailableDates, getSpecialistsByService
- `BookingRescheduleController.php` — show, update
- `BookingService.php` — validateDiscountCode, createBooking, cancelBooking, getNextAvailableSlots, getMonthlyAvailability, getUserBookings
- **باگ فیکس:** `PUT /{booking}/reschedule` به متد درست وصل شد
- **باگ فیکس:** `getUpcomingBookings`/`getPastBookings` نام‌گذاری یکدست شد
- ⚠️ **رگرسیون کشف‌شده در R-DiscountLogic**: در همین فاز، `routes/api/public/bookings.php` و `routes/api/user/bookings.php` به `BookingController::checkDiscount`/`applyDiscount` اشاره می‌کردن — متدهایی که در همین فاز به `BookingDiscountController` منتقل شدن ولی این ۳ روت API به‌روز نشده بودن (فتال ارور «Call to undefined method»). در R-DiscountLogic رفع شد.

### R4 — یکپارچه‌سازی Policy authorization
- `PaymentController.php` — ۳ مورد `abort(403)` → `$this->authorize('pay', $booking)`
- `UserWalletController.php` — ۱ مورد → `authorize('viewTransaction', $transaction)`
- `Specialist/SpecialistReviewController.php` — ۴ مورد → `authorize('view'/'respond', $review)`
- `Specialist/SpecialistWalletController.php` — ۱ مورد → `authorize('requestWithdrawal', $wallet)`

### R5 — تجزیه‌ی SpecialistProfileController و AdminReportsController
**SpecialistProfileController (770→205 خط):**
- `SpecialistProfileController.php` — پروفایل، schedule، loyalty، داشبورد
- `SpecialistLeaveController.php` — index, create, store, destroy
- `SpecialistBookingManagementController.php` — index, show, complete, markAsCompleted, cancel
- `SpecialistNotificationController.php` — index, latest, count, markAsRead, markAllAsRead
- `SpecialistDashboardService.php` — ۱۱ متد محاسباتی داشبورد

**AdminReportsController (663→289 خط):**
- `AdminReportsController.php` — فقط index (نمایش صفحه)
- `AdminReportService.php` — تمام query ها و محاسبات

### ✅ R-Reports — تکمیل AdminReportsController
تجزیه به ۴ کنترلر:
- `AdminReportsController` — فقط `index`
- `AdminReportRevenueController` — daily/weekly/monthly/financial API endpoints
- `AdminReportSpecialistController` — performance/satisfaction/services API endpoints
- `AdminReportExportController` — export PDF و Excel
- `AdminReportService` تکمیل با: `parseDateRange`, `getFinancialSummary`, `serviceRevenue`, `monthlyBreakdown`, `paymentBreakdown`
- Route فایل: `routes/admin/reports.php` و `routes/api/admin/reports.php`

### ✅ R-AdminLoyalty — تجزیه‌ی AdminLoyaltyController + رفع باگ‌های واقعی زمان اجرا
بزرگ‌ترین God Class (۴۸۶ خط، ۲۲ متد) تجزیه شد. ساختار نهایی (namespace تودرتو):

**کنترلرها:**
- `Admin\Loyalty\AdminLoyaltyController` — فقط `index()` (آمار + لیست پاداش‌ها، کاملاً Blade)
- `Admin\Loyalty\Reward\AdminLoyaltyRewardController` — create/store/show/edit/update/destroy/redeemReward (وب) + معادل‌های API (بلااستفاده بعد از حذف SPA)
- `Admin\Loyalty\Point\AdminLoyaltyPointsController` — getPoints, getHistory, getStatistics, getUserPoints, addUserPoints, deductUserPoints, export
- `Admin\Loyalty\AdminLoyaltySettingsController` — getSettings, updateSettings

**Service:** `Services\Admin\Loyalty\LoyaltyAdminService` — تمام منطق تجاری

**Form Requests** (`Requests\Admin\Loyalty\...`): `Reward\StoreLoyaltyRewardRequest`, `Reward\UpdateLoyaltyRewardRequest`, `Reward\RedeemRewardRequest`, `Point\AddUserPointsRequest`, `Point\DeductUserPointsRequest`, `UpdateLoyaltySettingsRequest` — همه با `authorize()`: `auth()->check() && auth()->user()->hasPermission('access_admin_panel')` (نه `is_admin` مستقیم، چون میدل‌ور روت هم از `hasPermission` استفاده می‌کنه)

**Exception:** `InsufficientLoyaltyPointsException extends DomainException` — جایگزین استفاده‌ی نادرست از `InsufficientWalletBalanceException`

**Views:** `admin/loyalty/index.blade.php` بازنویسی کامل به Blade خالص (نه SPA)؛ `edit.blade.php`/`show.blade.php` با route name های nested جدید (`admin.loyalty.rewards.*`)

**Routes:** `routes/admin/loyalty.php` (وب)، `routes/api/admin/loyalty.php` (الان عملاً بلااستفاده بعد از حذف SPA)

**⭐ باگ‌های واقعی کشف/رفع‌شده:**
1. **فرمول امتیازدهی به تنظیمات وصل نبود**: `BookingObserver::addLoyaltyPoints()` فرمول `5 + floor(prepayment/10000)` رو hardcode داشت؛ `User::addLoyaltyPoints()` انقضا رو `now()->addYear()` hardcode داشت. هیچ‌کدوم `loyalty_settings` رو نمی‌خوندن — صفحه‌ی «تنظیمات امتیازات» عملاً بی‌اثر بود. رفع شد: هر دو از `LoyaltySetting::getValue('points_per_amount', ...)` و `('points_expiry_months', ...)` می‌خونن. جزء ثابت «۵+» عمداً حفظ شد.
2. **سه منبع مجزا برای تنظیمات وفاداری** به یکی یکپارچه شد: **`loyalty_settings`/`LoyaltySetting`** تنها منبع (کلیدهای واقعی: `points_per_amount`, `points_expiry_months`, `minimum_points_for_discount`).
3. **مدل اشتباه**: بخشی از سرویس از `App\Models\LoyaltyReward` (وجود نداشت؛ مدل واقعی `Reward` است) استفاده می‌کرد — کرش فتال. رفع شد.
4. **رگرسیون داده**: `top_users`/`recent_redemptions` که در `getStatistics()` قدیمی بودن، در بازنویسی اول جا افتاده بودن؛ برگردونده شدن.
5. **مسیر Blade خطرناک با `@vite`** (به نکات فنی بالا نگاه کن).
6. **race condition بین `window.initialData` و ماژول Vite** — با کنار گذاشتن کامل SPA حل شد.
7. **تصمیم معماری نهایی**: `/admin/loyalty` دیگه از SPA (`admin.jsx`+`LoyaltyAdmin.jsx`) استفاده نمی‌کنه؛ اون کامپوننت‌ها الان بلااستفاده‌ان (کاندید فاز پاک‌سازی).
8. **⭐ کشف بعدی (طی گزارش «افزودن پاداش کار نمی‌کند»)**: `LoyaltyAdminService` متدهایی که `AdminLoyaltyRewardController` صداشون می‌زد (`getDashboardStats`, `createReward`, `updateReward`, `deleteReward`, `getActiveRewards`, `redeemRewardForUser`) رو **اصلاً نداشت** — یعنی از همون اول این متدها جا افتاده بودن. چون فراخوانی متد ناموجود در PHP یک `\Error` (که `\Throwable` هم هست) پرتاب می‌کنه و کنترلر همه‌جا `catch (\Throwable $e)` داشت، این خطا بی‌صدا قورت داده می‌شد و در نتیجه هیچ‌جا (نه لاگ، نه دیتابیس) اثری نمی‌ذاشت — کاربر فقط ریدایرکت با پیام خطای کلی می‌دید. همه‌ی متدهای گم‌شده اضافه شدن. **باگ همراه کشف‌شده**: `LoyaltyService::redeemReward()` (که هم مسیر مشتری هم حالا مسیر ادمین ازش استفاده می‌کنه) روی `auth()->user()` تکیه می‌کرد نه پارامتر ورودی `$userId` — یعنی اگه ادمین این متد رو برای کاربر دیگری صدا می‌زد، چک موجودی امتیاز و نوتیفیکیشن به‌جای کاربر هدف، اشتباهاً برای خود ادمین اجرا می‌شد. رفع شد: متد الان کاملاً بر اساس `$userId` عمل می‌کنه (نه `auth()->user()`)، بدون این‌که امضای فراخوانی از بیرون تغییر کنه.

### ✅ R-AdminDashboard — تجزیه‌ی AdminDashboardController
**کنترلرها:** `Admin\Dashboard\AdminDashboardController` — فقط `dashboard()`؛ `Admin\Dashboard\AdminDashboardAnalyticsController` — `getData`, `getPopularServices`, `getActiveSpecialists`

**Service:** `Services\Admin\Dashboard\AdminDashboardService` — `getOverviewData`, `getSummaryStats`, `getPopularServicesWithTrend`, `getActiveSpecialistsWithPerformance`, `calculatePerformanceScore` (خصوصی)

**Routes:** `routes/admin/dashboard.php` (`admin.dashboard`, `admin.dashboard.data`)، `routes/api/admin/dashboard.php` (`api.admin.dashboard.{data,popular-services,active-specialists}`)

**View:** `admin/dashboard.blade.php` — کارت «خدمات محبوب» و جدول «آمار متخصصین» الان از داده‌ی غنی‌شده‌ی سرویس (trend%، rating، performance_score) پر می‌شن

**⭐ تصمیم: حذف dead code به‌جای نگه‌داشتن** (برخلاف الگوی R-AdminLoyalty):
- **حذف شدن**: `getDailyRevenue()`, `getDashboardByPeriod()` — هیچ‌وقت route نداشتن، دوباره‌کاری کامل با `AdminReportRevenueController`
- **واقعاً به کار بسته شدن**: `getPopularServices()`, `getActiveSpecialists()` — الان هم route API دارن هم مستقیم توی `dashboard()` استفاده می‌شن
- `getData()` دست‌نخورده موند (روت بی‌نام قبلی، الان نام‌دار: `api.admin.dashboard.data`)

**⭐ باگ‌های واقعی کشف/رفع‌شده:**
1. **نرخ تکمیل متخصصین از دو بازه‌ی زمانی مختلف محاسبه می‌شد**: «تکمیل‌شده‌های all-time» تقسیم بر «نوبت‌های امروز» — نسبت بی‌معنی که می‌تونست از ۱۰۰٪ رد بشه. رفع شد: `getActiveSpecialistsWithPerformance()` همه‌چیز رو روی یک بازه‌ی یکسان (۳۰ روز اخیر) با `withCount`/`withAvg` محاسبه می‌کنه (بدون N+1).
2. **`completion_rate` هیچ‌وقت واقعاً روی مدل ست نمی‌شد** — فاکتور «تکمیل» در امتیاز عملکرد همیشه ۰ حساب می‌شد. رفع شد: `completionRate` صریحاً پاس داده می‌شه.
3. **N+1 query در Blade** — با انتقال منطق به سرویس (تک کوئری) حذف شد.
4. **`web.php` بعد از تغییر namespace به‌روز نشده بود** — فتال ارور «Class not found» در `/admin`. رفع شد.

⚠️ **تغییر رفتاری آگاهانه**: بازه‌ی «خدمات محبوب» و «درآمد متخصص» در داشبورد از all-time به ۳۰ روز اخیر تغییر کرد؛ عناوین Blade به «(۳۰ روز اخیر)» به‌روز شدن.

### ✅ R-AdminWallet — تجزیه‌ی AdminWalletController
کنترلر ۳۴۹ خطی/۱۱ متدی تجزیه شد:

**کنترلرها:** `Admin\Wallet\AdminWalletController` (index, show, verifyIban, adjust, ⭐ settlePending, settlePendingForWallet — دو متد آخر بعداً در فیچر مستقل «تسویه‌ی دستی کیف‌پول» اضافه شدن)، `Admin\Wallet\AdminWithdrawalController` (index, show, approve, reject, autoPayout)، `Admin\Wallet\AdminWalletSettingsController` (index, update)

**Service:** `Services\Admin\Wallet\WalletAdminService` — تمام منطق تجاری، همه با `DB::transaction()` صحیح (بدون الگوی خطرناک). ⭐ متد `settlePendingIncomes()` هم بعداً به این سرویس اضافه شد (به بخش «فیچر تسویه‌ی دستی کیف‌پول» نگاه کن).

**Form Requests** (`Requests\Admin\Wallet\...`): `AdjustWalletRequest`, `ApproveWithdrawalRequest`, `RejectWithdrawalRequest`, `UpdateWalletSettingsRequest`

**Routes:** `routes/admin/wallet.php` — namespace به‌روز شد؛ **تمام route name ها عیناً حفظ شدن** → هیچ فایل Blade نیاز به تغییر نداشت (به‌جز افزودن‌های بعدی برای تسویه‌ی دستی)

**⭐ باگ‌های واقعی کشف/رفع‌شده:**
1. **`admin_commission_percentage` هیچ‌وقت ذخیره نمی‌شد**: فیلد در `settings.blade.php` وجود داشت ولی نه در validation قدیمی نه در `$fillable` مدل `WalletSetting` — mass-assignment بی‌صدا دورش می‌ریخت. تایید شد با فاصله‌ی یک‌ونیم‌ماهه‌ی مشکوک بین `created_at`/`updated_at` (یعنی تغییر قبلی احتمالاً فقط از دیتابیس/tinker بوده). فیکس: ستون به `$fillable`/`$casts` و به `UpdateWalletSettingsRequest` (با `MaxPercentage`) اضافه شد؛ ستون از قبل در دیتابیس بود، migration جدید لازم نبود.
2. **فرم‌های approve/reject درخواست برداشت اصلاً کار نمی‌کردن (۴۰۵)**: Blade از `@method('PUT')` استفاده می‌کرد ولی روت قدیمی فقط `Route::post` بود. فیکس: روت‌ها به `Route::put` تغییر کردن.
3. **`rejection_reason` بی‌صدا required بود**: کنترلر `required` می‌خواست، Blade به‌عنوان اختیاری نمایشش می‌داد (بدون `@error`) — validation رد می‌شد بدون هیچ پیامی. فیکس: در `RejectWithdrawalRequest` به `nullable`؛ در سرویس یک متن پیش‌فرض جایگزین خالی‌بودنش می‌شه.
4. **فرم approve درخواست برداشت اصلاً کار نمی‌کرد (validation fail بی‌صدا)**: `ApproveWithdrawalRequest` فیلد `payment_reference` را `required` می‌خواست، ولی `withdrawal-show.blade.php` هیچ input برای این فیلد نداشت — FormRequest validation fail می‌شد، لاراول auto-redirect back می‌کرد، ولی چون Blade هیچ `@error` نداشت، ادمین هیچ پیامی نمی‌دید و فکر می‌کرد دکمه کار نمی‌کند. **فیکس**: فیلد `payment_reference` (required) + `admin_note` (optional) + `@error` + `old()` به فرم approve اضافه شد. همچنین `@error('rejection_reason')` + `old()` به فرم reject اضافه شد.
5. **باگ منطقی `if/elseif` در Blade — بخش auto-payout هرگز نمایش داده نمی‌شد**: `processing` هم در شرط اول `in_array(['pending','processing'])` بود و هم در `elseif($status === 'processing')`، بنابراین `elseif` همیشه short-circuit می‌شد. **فیکس**: سه حالت مجزا: `@if($status === 'pending')` / `@elseif($status === 'processing')` / `@else`. همچنین دکمه‌ی reject از `type="button"` به `type="submit"` تغییر کرد (SweetAlert2 interceptor همچنان کار می‌کند).
6. **هشدار UI برای auto-payout mock**: بنر هشدار در بخش `processing` اضافه شد که صراحتاً اعلام می‌کند تسویه آنلاین به درگاه واقعی متصل نیست.

> 🔴 برای باگ چهارم (mock بودن `autoPayout`) به «هشدار حیاتی» بالای این سند نگاه کن.

### ✅ R-SpecialistWallet — تجزیه‌ی SpecialistWalletController (بود: 322 خط، 8 متد)
**این فاز موازی و مستقل از R-AdminWallet، در یک نشست جداگانه انجام شد.**

**کنترلرها:** `Specialist\SpecialistWalletController` (index, transactions, calculateFee)، `Specialist\SpecialistIbanController` (edit, update)، `Specialist\SpecialistWithdrawalController` (create, store, cancel)

**Service:** `Services\Specialist\SpecialistWalletService` — `resolveSpecialist()`, `getWalletOverview()`, `getTransactions()` (فیلتر تاریخ جلالی)، `updateIban()`, `createWithdrawal()`, `cancelWithdrawal()`, `calculateFee()`

**Form Requests** (`Requests\Specialist\...`): `UpdateIbanRequest` (شبا ۲۴ رقم + نام صاحب حساب + بانک)، **`Wallet\Withdrawal\StoreWithdrawalRequest`** (حداقل پویا از `WalletSetting::first()` + روش برداشت؛ این نسخه‌ی جدید و درسته — ⚠️ به بخش «فیچر تسویه‌ی دستی کیف‌پول توسط ادمین» نگاه کن: کنترلر تا مدت‌ها بعد همچنان به نسخه‌ی قدیمی و کهنه‌ی هم‌نام در `Requests\Specialist\StoreWithdrawalRequest` (namespace ریشه، از فاز R2) وصل مونده بود، نه به این نسخه‌ی جدید)

**Routes:** `routes/web/specialistprofile.php` — گروه `specialist/wallet` به‌روز شد؛ نام‌های روت (`specialist.wallet.*`) کاملاً بدون تغییر

**⭐ باگ واقعی کشف/رفع‌شده:** `WalletSetting::get()` به‌جای `WalletSetting::first()` در ۳ متد (`index`, `createWithdrawal`, `storeWithdrawal`) — چون `get()` یک Collection برمی‌گردوند، `$settings->minimum_withdrawal_amount` همیشه `null` بود — قانون `min:` عملاً بی‌اثر. رفع با `::first()`.
> نکته: این باگ ماهیتاً شبیه باگ `admin_commission_percentage` نیست، ولی هر سه نمونه‌ی «فرم/کوئری بی‌صدا بی‌اثر» هستن (بار سوم: `description`/`order` در R-AdminBlog؛ بار چهارم: همین باگ `WalletSetting::get()` که در Form Request جدید این فاز فیکس شد، ولی چون کنترلر بهش وصل نبود، فیکس عملاً بی‌اثر مونده بود تا فیچر تسویه‌ی دستی کشفش کرد).

**⭐ Dead code فعال‌سازی‌شده (Policy)**: `SpecialistWalletPolicy` سه ability داره (`view`, `requestWithdrawal`, `updateIban`)؛ کنترلر قدیمی فقط `requestWithdrawal` رو صدا می‌زد. حالا `view` روی `index`/`transactions`/`calculateFee`/`edit`، `updateIban` روی `update` wire شدن؛ `requestWithdrawal` هم به `store` اضافه شد.

**✅ نکته‌ی باز فاز قبل — بررسی شد و رفع شد:**
1. **تداخل با `ResolvesSpecialist` trait**: `resolveSpecialist(bool $orFail = false)` از `auth()->user()?->specialist` استفاده می‌کنه (رابطه‌ی `hasOne(Specialist::class, 'phone', 'phone')`) — منطقاً معادل query دستی سرویس بود. متد تکراری از سرویس حذف شد؛ هر سه کنترلر حالا از `use ResolvesSpecialist;` استفاده می‌کنن: `resolveSpecialist()` (nullable) جایی که رفتار قبلی `profile-not-found` بود، `requireSpecialist()` (که خودش `abort(404)` می‌کنه) در `store`/`cancel`. **استثنا آگاهانه**: `calculateFee()` عمداً از `resolveSpecialist()` nullable استفاده کرد نه `requireSpecialist()`، چون endpoint با fetch/AJAX صدا زده می‌شه و باید همیشه JSON برگردونه.
2. **`ValidIban` rule**: محتوا با closure دستی قبلی عیناً یکی بود؛ `UpdateIbanRequest` الان مستقیماً `new ValidIban` رو استفاده می‌کنه.
3. **باگ اضافه‌ی کشف‌شده**: کنترلر قدیمی `updateIban()` (برخلاف `editIban()`) هیچ چک null‌ای روی specialist نداشت — خطر فتال ارور. در تجزیه‌ی این فاز این ناسازگاری از قبل رفع شده بود (هر دو متد یک چک یکسان دارن).

### ✅ R-AdminBlog — تجزیه‌ی AdminBlogController + AdminBlogCategoryController (بود: ۲۶۰ + ۱۸۰ خط)
**این فاز اولین باری بود که دسترسی شبکه به GitHub کاملاً باز بود** — فایل‌های واقعی از برنچ `develop` مستقیم خونده شدن (routes، کنترلرها، مدل‌ها، migration، حتی خود کامپوننت React با grep روی `fetch(`) به‌جای تکیه به فایل‌های آپلودی؛ همین باعث کشف چند باگ شد که فقط با فایل‌های آپلودی قابل کشف نبودن.

**کنترلرها:** `Admin\Blog\AdminBlogController` (index, show)، `Admin\Blog\AdminBlogPostActionController` (create, store, edit, update, destroy, togglePublish)، `Admin\Blog\AdminBlogCategoryController` (CRUD کامل دسته‌بندی)

**Service:** `Services\Admin\Blog\BlogPostService` (`getIndexData`, `store`, `update`, `destroy`, `togglePublish` + نرمالایز خصوصی)، `Services\Admin\Blog\BlogCategoryService`

**Form Requests** (`Requests\Admin\Blog\...`): `Post\StoreAdminBlogPostRequest`, `Post\UpdateAdminBlogPostRequest`, `Category\StoreBlogCategoryRequest`, `Category\UpdateBlogCategoryRequest` — همه با `authorize()`: `hasPermission('access_admin_panel')`

**Routes:** `routes/admin/blog.php` — use statements به‌روز شد؛ تمام route name ها عیناً حفظ شدن (`admin.blog.*`, `admin.blog.categories.*`)

**Model:** `BlogPost` — تریت `SoftDeletes` اضافه شد، `$appends = ['image_url', 'published_at_jalali']`، متد استاتیک تکراری `create()` حذف شد. `BlogCategory` — `description`/`order` به `$fillable` اضافه شدن، متد استاتیک زائد `withCount()` حذف شد.

**Views:** `admin/blog/index.blade.php` از React SPA به Blade خالص مهاجرت کرد؛ `admin/blog/edit.blade.php`, `show.blade.php`, `categories/{index,create,edit}.blade.php` همه از صفر ساخته شدن (قبلاً اصلاً وجود نداشتن).

**⭐ باگ‌های واقعی کشف/رفع‌شده:**
1. **بحرانی‌ترین کشف**: `blog_posts` ستون `deleted_at` داره ولی مدل `BlogPost` تریت `SoftDeletes` رو نداشت — از روزی که جدول ساخته شده، هر «حذف مقاله» عملاً دائمی و غیرقابل‌بازگشت بوده. رفع شد. ⚠️ مقالات حذف‌شده‌ی قبل از فیکس قابل بازیابی نیستن.
2. **`admin.blog.edit` و هر سه view دسته‌بندی اصلاً وجود نداشتن** (تأیید با ۴۰۴ مستقیم روی `raw.githubusercontent.com`). چون SPA قدیمی ویرایش/دسته‌بندی رو inline انجام می‌داد، هیچ‌وقت لو نرفته بود. همه از صفر ساخته شدن.
3. **`admin.blog.show` باعث فتال ارور می‌شد**: این view در واقع قالب صفحه‌ی عمومی مشتری بود (`@extends('layouts.app')`) که به‌اشتباه برای پنل ادمین استفاده می‌شد؛ داخلش `route('blog.category', ...)`/`route('blog.tag', ...)` صدا زده می‌شد که با جست‌وجوی کامل کد (`api.github.com/search/code`) تأیید شد **هیچ‌جای پروژه تعریف نشدن**. بازنویسی کامل با تم/layout ادمین.
4. **ستون‌های `description`/`order` روی `BlogCategory` اصلاً `$fillable` نبودن** — همون الگوی `admin_commission_percentage`؛ تأیید با seeder واقعی. ترتیب سفارشی دسته‌بندی‌ها هم به همین دلیل بی‌اثر بود.
5. **چک‌باکس «منتشر شده» موقع ویرایش بی‌اثر بود**: چک‌باکس خالی در request نمی‌اومد و `update()` (برخلاف `store()`) نرمالایزیشنی برای `is_published` نداشت.
6. **`update()` فیلد `published_at_jalali` رو نادیده می‌گرفت**: validation فقط `published_at` خام رو می‌شناخت که هیچ فرمی ارسالش نمی‌کرد. باگ‌های ۵ و ۶ با منطق مشترک در `BlogPostService::resolvePublishedAt()`/`normalizeIsPublished()` رفع شدن.
7. **پیام‌های خطای خام (`$e->getMessage()`) مستقیم به کاربر نشون داده می‌شد** — جایگزین با `Log::error()` (با context) + پیام عمومی فارسی.
8. **کد تکراری/خطرناک در مدل**: متد استاتیک `BlogPost::create()` معادل رفتار پیش‌فرض `Model::create()` بود ولی shadow می‌کرد (منطق slug/author_id هم‌زمان در ۳ جا تکرار). حذف شد؛ الان فقط `boot()` مسئوله.
9. **کد زائد در مدل**: `BlogCategory::withCount()` فقط proxy بود و امضای محدودتری هم داشت. حذف شد.

**⭐ تصمیم معماری: SPA حذف شد** — با grep مستقیم روی `BlogAdmin.jsx` تأیید شد که کامپوننت اصلاً از `window.initialData` استفاده نمی‌کرد (به روت‌های hardcode شده فچ می‌زد) — اون تزریق از اول کد مرده بود. `BlogAdmin.jsx` دیگه mount نمی‌شه (کاندید حذف).

**🟠 کشف جانبی (خارج از scope رفکتور ادمین، در همون جلسه جدا فیکس شد)**: `App\Http\Controllers\BlogController` (عمومی) هیچ ربطی به AdminBlogController نداره ولی مشخص شد `index()`/`show()`ش همیشه JSON خام برمی‌گردوندن، در حالی که یک Blade کامل و آماده (همون که در باگ #۳ در مسیر اشتباه بود) وجود داشت. جزئیات کامل در بخش «رفع مستقل: صفحات عمومی وبلاگ» پایین‌تر.

### ✅ R-AdminForms — Form Requests کامل Admin controllers + ✅ R-AdminSpecialist/User/Booking Service
**این دو فاز در یک نشست جداگانه، موازی با R-AdminBlog انجام شدن.**

تمام `$request->validate([...])` های inline در Admin controllers (به‌جز AdminBlog که در فاز خودش انجام شد) استخراج شدن:

| کنترلر | Form Requests ساخته‌شده | namespace |
|--------|--------------------------|-----------|
| `AdminSpecialistController` | `StoreSpecialistRequest`, `UpdateSpecialistRequest` | `Requests\Admin\Specialist` |
| `AdminUserController` | `StoreAdminUserRequest`, `UpdateAdminUserRequest`, `ResetAdminUserPasswordRequest` | `Requests\Admin\User` |
| `AdminBookingController` | `StoreAdminBookingRequest`, `UpdateAdminBookingRequest` | `Requests\Admin\Booking` |
| `SecurityController` | `CheckPasswordStrengthRequest` | `Requests\Security` |
| `DiscountCodeController` | `StoreDiscountCodeRequest`, `UpdateDiscountCodeRequest`, `ApplyDiscountToBookingRequest` | `Requests\Admin\DiscountCode` (Store/Update) و `Requests` (Apply) |

**نکته درباره‌ی `UpdateAdminBookingRequest`**: کنترلر قدیمی `update()` بین دو رفتار کاملاً متفاوت شاخه می‌زد (فقط تغییر status، یا ویرایش کامل نوبت) — چون هر دو روی یک route/method بودن، این Form Request هر دو مجموعه rule رو با متد کمکی `isStatusOnly()` پوشش می‌ده تا رفتار قبلی دقیقاً حفظ بشه.

**⭐ باگ‌های واقعی کشف/رفع‌شده:**
1. **`CheckPasswordStrengthRequest` و `StoreDiscountCodeRequest`/`UpdateDiscountCodeRequest` طبق مستندات قرار بود از R2 آماده باشن ولی wire نشده بودن** — کنترلرها هنوز `$request->validate()` inline داشتن. هر سه wire شدن.
2. **الگوی تکراری `is_admin` مستقیم به‌جای `hasPermission('access_admin_panel')`** — همون باگی که در R-AdminLoyalty کشف شده بود، این‌بار در ۵ فایل دیگه هم پیدا شد: `StoreDiscountCodeRequest`, `UpdateDiscountCodeRequest`, `ReorderGalleryImagesRequest`, `StoreGalleryImageRequest`, `UpdateGalleryImageRequest`. همه فیکس شدن (سومین نمونه‌ی این الگو در پروژه).
3. **رگرسیون واقعی در `StoreDiscountCodeRequest`**: `MaxPercentage` بدون قید روی فیلد `amount` اعمال شده بود — کد تخفیف نوع `fixed` با مبلغ بالای ۱۰۰ رد می‌شد. رفع شد: `MaxPercentage` فقط شرطی (وقتی `type === percentage`) اضافه می‌شه.
4. **`ApplyDiscountToBookingRequest` اصلاً وجود نداشت** — ساخته و wire شد.

**استخراج Service زودهنگام (R-AdminSpecialist/User/Booking)**: منطق تجاری هر سه کنترلر هم به Service منتقل شد:
- **`Services\Admin\Specialist\AdminSpecialistService`** — `create`/`update`/`delete` + `normalizePhone`/`parseCommissionRate` خصوصی؛ `DB::transaction` با return صریح داخل closure.
- **`Services\Admin\User\AdminUserService`** — `create`/`update`/`delete` (برمی‌گردونه تعداد نوبت‌های باقی‌مونده به‌جای throw) + `updateStatus`/`resetPassword`/`syncRoles`.
- **`Services\Admin\Booking\AdminBookingService`** — منطق «اگه لغو شد و پول پرداخت شده بود رفاند بزن» عیناً دوبار (شاخه‌ی status-only و full-update) در کنترلر کپی شده بود؛ به یک متد خصوصی مشترک (`handlePostUpdateSideEffects`) یکی شد.

هر سه کنترلر الان واقعاً نازک هستن (فقط تزریق Form Request، صدا زدن Service، redirect)؛ رفتار (پیام‌ها، مسیرهای redirect، شرط‌ها) عیناً حفظ شده.

**✅ فاز تکمیلی (نشست جداگانه‌ی بعدی): `R-AdminSpecialist` — Form Request + Service برای `AdminSpecialistController`/`AdminUserController`**
طبق پلن، این فاز نیاز به تجزیه‌ی کنترلر نداشت (هر دو زیر ۲۲۰ خط بودن)؛ فقط استخراج Form Request + Service انجام شد (فایل‌های جدید: `Requests/Admin/Specialist/{Store,Update}SpecialistRequest`, `Requests/Admin/User/{StoreAdminUser,UpdateAdminUser,ResetAdminUserPassword}Request`, `Services/Admin/Specialist/AdminSpecialistService`, `Services/Admin/User/AdminUserService`).

- **تصمیم معماری اضافه‌ی کاربر (فراتر از پلن اولیه)**: کنترلرها به namespace تودرتوی `App\Http\Controllers\Admin\Specialist\` و `App\Http\Controllers\Admin\User\` منتقل شدن (هم‌راستا با الگوی R-AdminLoyalty/Dashboard/Wallet/Blog). کاربر خودش `routes/admin/specialists.php` و `routes/admin/users.php` رو با namespace جدید هماهنگ کرد — این‌بار بدون فاجعه‌ی «Class not found» که در R-AdminDashboard تجربه شده بود، چون از قبل چک شد.
- **⭐ باگ واقعی کشف/رفع‌شده**: `normalizePhone()` (چه در پیاده‌سازی اول، چه در پیاده‌سازی خود کاربر) بعد از اجرای rule های `max:11`/`unique` صدا زده می‌شد، نه قبلش — یعنی هر ورودی با فرمت `+98`/`0098`/فاصله‌دار (که طولش از ۱۱ کاراکتر بیشتره) همون لحظه‌ی validation رد می‌شد و اصلاً به normalizePhone نمی‌رسید؛ منطق تبدیل فرمت بین‌المللی که ظاهراً برای رفع باگ «پروفایل ناقص متخصص جدید» اضافه شده بود، در عمل هیچ‌وقت اجرا نمی‌شد (کد مرده‌ی پنهان). **فیکس نهایی**: نرمالایز به `prepareForValidation()` در Form Request منتقل شد (هم `Store` هم `Update`).
- **بهبود دیگر**: پیام‌های خطای خام (`$e->getMessage()`) که در `AdminUserController` مستقیم به کاربر نشون داده می‌شدن (همون anti-pattern کشف‌شده در R-AdminBlog)، با `Log::error()` + پیام عمومی جایگزین شدن.

**⭐ یافته‌ی معماری (فیکس نشد، موکول به `R-DiscountLogic`)**: `DiscountCodeController` هم اکشن‌های ادمین‌محض (`store`/`update`/`destroy`) هم اکشن‌های مشتری‌محض (`index`/`validate`/`applyToBooking`) رو داره — تداخل SRP.

**⭐ قرارداد Namespace (برای فازهای بعدی رعایت بشه):**
- کنترلر/Request/Service مخصوص یک دامنه‌ی ادمین: `App\{Http\Controllers,Http\Requests,Services}\Admin\{Domain}\...` (مثال: `Admin\Booking`, `Admin\Specialist`, `Admin\User`, `Admin\DiscountCode`, `Admin\Gallery`, `Admin\Blog`, `Admin\Announcement`)
- Request مخصوص دامنه‌ی غیر-ادمین: `App\Http\Requests\{Domain}\...` (مثال: `Profile\ProfileUpdateRequest`, `Review\StoreReviewRequest` جدا از `Booking\RateBookingRequest`)

---

### ✅ R-DiscountLogic — تحکیم منطق محاسبه‌ی تخفیف + رفع زنجیره‌ی باگ مسیر اعمال تخفیف
**این فاز با دو مسیر موازی انجام شد: یک نشست با Kimi Agent (روی کلون مستقیم `develop`، دسترسی شبکه باز) و یک نشست با Claude (بدون دسترسی شبکه، مبتنی بر فایل‌های آپلودی). نتیجه‌ی نهایی ادغام یافته‌های هر دو بود.**

مسیر «اعمال کد تخفیف» در ۶ لایه‌ی مستقل هم‌زمان شکسته بود — نه یک باگ، یک زنجیره:
1. صفحه‌ی پرداخت (`payment/show.blade.php`) endpoint اشتباه صدا می‌زد (باگ مالی — کاربر مبلغ تخفیف‌خورده رو می‌دید ولی چیزی persist نمی‌شد، پرداخت با مبلغ کامل انجام می‌شد)
2. `BookingDiscountController::apply()` به یک کلاس Form Request ناموجود import داشت (`Requests\Booking\ApplyDiscountRequest` — namespace از R3 به بعد منسوخ) → فتال ارور ۵۰۰ دائمی
3. `BookingPolicy::update` نیاز به `status==='pending'` داشت در حالی که نوبت‌های در مسیر تخفیف `pending_payment` بودن → authorize همیشه ۴۰۳ می‌داد
4. ۳ روت API (`routes/api/public/bookings.php`، `routes/api/user/bookings.php`) به متدهای حذف‌شده از R3 اشاره می‌کردن → فتال ارور
5. `CheckDiscountRequest` فیلد `service_id` را `required` می‌خواست بدون این‌که هیچ‌جا واقعاً استفاده بشه یا صفحه‌ی پرداخت ارسالش کنه → ۴۲۲ دائمی
6. `BookingActions.jsx` (React، mount-point در `bookings/show.blade.php`) هیچ‌وقت booking id نمی‌فرستاد و URL هاش با route های واقعی مطابقت نداشت

**راه‌حل‌ها:**
- `App\Services\Discount\DiscountCalculator` — تنها منبع فرمول `percentage/fixed + سقف max_amount` در کل پروژه؛ جایگزین ۶ پیاده‌سازی مستقل و واگرا (`BookingService` سه متد، `DiscountCode::calculateDiscount()` بلااستفاده، `DiscountCodeService`، `DiscountCodeController`).
- `BookingService::applyDiscountCode()` سه گارد جدید گرفت: مالکیت (`canBeUsedBy`)، جلوگیری از اعمال مجدد، جلوگیری از اعمال روی نوبت پرداخت‌شده — قبلاً هیچ‌کدوم وجود نداشت.
- `BookingPolicy` ability اختصاصی `applyDiscount` (مالک + `payment_status==='unpaid'`) اضافه شد؛ کنترلرها از `update` به این ability سوییچ کردن.
- `App\Http\Requests\User\Booking\ApplyDiscountRequest` (namespace درست) ساخته شد؛ `CheckDiscountRequest` با `service_id`/`booking_id` اختیاری بازنویسی شد تا پیش‌نمایش با مبلغ واقعی (نه ۵۰۰۰۰ هاردکد) کار کنه.
- `resources/views/bookings/show.blade.php`: mount-point React کلاً به Blade خالص تبدیل شد (دکمه‌های لغو/اعمال‌تخفیف با فرم معمولی؛ تغییر‌زمان/ثبت‌نظر به صفحات جدای موجود لینک می‌دن، نه modal تکراری).

**✅ تصمیم‌های معماری:**
- `App\Http\Controllers\User\DiscountCodeController` + `App\Services\DiscountCodeService` **کلاً حذف شدن** — کد مرده‌ی ۱۰۰٪، هیچ route‌ای نداشتن، حتی importهای خودشون هم به namespace اشتباه اشاره می‌کرد (از اول کار نمی‌کردن).
- نیاز واقعی به مدیریت دستی کد تخفیف در پنل ادمین به فیچر جدید `R-AdminDiscountCode` (بخش «فیچرهای برنامه‌ریزی‌شده» بالا) موکول شد، نه رفکتور کد موجود — چون هیچ کنترلر/route/UI ای برای این فیچر قبلاً وجود نداشت.
- `App\Http\Requests\Admin\DiscountCode\{Store,Update}DiscountCodeRequest` نگه داشته شدن (پایه‌ی فیچر بالا).

### ✅ R-AdminAnnouncement-Gallery — مهاجرت نهایی Announcement/Gallery از React SPA به Blade
مکمل تصحیح مستندسازی فاز ۳ (که این دو صفحه رو زودتر از موعد «کامل» اعلام کرده بود، در حالی که فقط wrapper خالی داشتن و محتوا از `AnnouncementAdmin.jsx`/`GalleryAdmin.jsx` میومد).

**Announcement:**
- `Admin\Announcement\AdminAnnouncementController` — CRUD کامل وب (index با pagination واقعی + آمار، create, store, edit, update, destroy) به‌جای JSON API قدیمی
- `Requests\Admin\Announcement\{Store,Update}AnnouncementRequest` — با `prepareForValidation()` برای نرمالایز چک‌باکس `is_active` (همون باگ چک‌باکس کشف‌شده در R-AdminBlog، پیشگیرانه فیکس شد)
- Views: `create.blade.php`/`edit.blade.php` جدید با jcal برای `published_at`/`expires_at` (+ ورودی `time` جدا، چون jcal فقط تاریخ داره)
- `routes/admin/announcements.php` — از `stats`/`list` JSON به CRUD وب استاندارد تغییر کرد

**Gallery:**
- `Admin\Gallery\AdminGalleryController` — CRUD کامل وب؛ به‌جای drag&drop پیچیده، دکمه‌ی ساده‌ی ▲▼ برای جابه‌جایی ترتیب (بدون نیاز به JS اضافه)
- از `App\Http\Requests\StoreGalleryImageRequest` استفاده می‌کنه — ⚠️ **باگ کشف‌شده**: این کلاس با وجود این‌که در فاز R2 «ساخته‌شده» مستندسازی شده بود، در کدبیس واقعی وجود نداشت (فتال ارور «Class does not exist»)؛ از صفر ساخته شد.
- `routes/admin/gallery.php` — CRUD کامل وب به‌جای JSON API

**پاک‌سازی مشترک:**
- `resources/js/admin.jsx` — تمام import/mount های ادمین (`AdminDashboard`, `ReportDashboard`, `LoyaltyAdmin`, `AnnouncementAdmin`, `BlogAdmin`, `GalleryAdmin`) حذف شدن؛ فقط mount مربوط به `booking-stats` (سمت مشتری، بی‌ربط به این پاک‌سازی) باقی موند.

**⭐ باگ بحرانی کشف/رفع‌شده:** import شکسته‌ی `BlogAdmin.jsx` (که در فاز R-AdminBlog حذف شده بود ولی importش تو `admin.jsx` فراموش شده بود) باعث می‌شد Vite کل bundle ادمین رو رد کنه — یعنی صفحه‌ی اطلاعیه‌ها (که ربطی به وبلاگ نداشت) با خطای import-analysis مربوط به وبلاگ کرش می‌کرد. با پاک‌سازی کامل `admin.jsx` رفع شد.

**🟡 یافته‌ی محیطی (کد نیست)**: تصاویر گالری seed‌شده با ۴۰۳ روی `/storage/gallery/...` مواجه شدن — به بخش «نکته‌ی محیطی» بالای این سند نگاه کن.

---

### ✅ بررسی‌شده، رد شد، و در نهایت کاملاً حذف شد: WorkSchedule
> **وضعیت نهایی (۲۰۲۶-۰۸-۰۷): حذف کامل.** تصمیم اولیه‌ی این بخش («نگه داشته می‌شه بدون استفاده») بعداً در یک بحث جدا با کاربر عوض شد — به‌جای نگه‌داری نامحدود یک سیستم موازیِ بلااستفاده، کل فیچر حذف شد. جزئیات کامل حذف در بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کامل فیچر WorkSchedule» پایین‌تر. متن اصلی این بخش (چگونگی کشف/تکمیل/تصمیم اولیه) فقط برای مرجع تاریخی نگه داشته شده:

ضمن بررسی `routes/api/admin/specialists.php` و `routes/admin/schedule.php` مشخص شد یک مدل و کنترلر دوم برای برنامه‌ی کاری متخصص از قبل در پروژه وجود داشت (`WorkSchedule` / `AdminSpecialistsWorkScheduleController`) که **کاملاً نیمه‌کاره و ناسازگار با روت‌هاش بود**:

- `index()` به‌جای نمایش داده‌ی `WorkSchedule`، کد کپی‌شده از سیستم قدیمی (`SpecialistSchedule`) رو نشون می‌داد.
- روت‌های `PUT`/`DELETE` به متدهای `update()`/`destroy()`ی اشاره می‌کردن که **اصلاً روی کنترلر وجود نداشتن** (فتال ارور تضمین‌شده در صورت فراخوانی).
- نام route `admin.schedule.index` هم‌زمان در دو فایل (`admin/specialists.php` برای سیستم قدیمی، `admin/schedule.php` برای WorkSchedule) ثبت شده بود؛ چون دومی بعداً لود می‌شد، بی‌صدا اولی رو override می‌کرد.
- مدل `WorkSchedule` هم یک باگ واقعی داشت: `start_time`/`end_time` با cast `datetime:H:i` ذخیره می‌شن (یعنی مقدارشون هنگام خوندن یک شیء Carbon است، نه رشته)، ولی `isWorkingTime()` این شیء رو مستقیم با یک رشته‌ی `"H:i"` مقایسه می‌کرد — مقایسه‌ای که در PHP همیشه نتیجه‌ی غلط می‌داد.

**کارهای انجام‌شده:** فیچر کامل پیاده‌سازی شد — `WorkScheduleService` مشترک، Form Request (ادمین + خود متخصص)، بازنویسی کامل `AdminSpecialistsWorkScheduleController` (فیکس `index`، اضافه‌شدن `update`/`destroy` واقعی)، متدهای جدید در `SpecialistProfileController` (`workSchedule`/`updateWorkSchedule`/`destroyWorkSchedule`)، Blade های جدید هم برای ادمین هم متخصص، فیکس باگ مدل، و رفع تصادم نام route (گروه `admin/schedule.php` به `specialists.work-schedule.*` تغییر نام داد).

**⭐ تصمیم نهایی کاربر (بعد از تکمیل کامل):** با مقایسه مشخص شد `WorkSchedule` (یک بازه‌ی ساعتی مشترک برای چند روز) عملاً **زیرمجموعه‌ی** قابلیت `SpecialistSchedule` (ساعت جدا برای هر روز) است، نه یک قابلیت مستقل — هرچی که WorkSchedule می‌کنه از دل SpecialistSchedule هم با تکرار همون ساعت برای روزهای دلخواه به‌دست میاد. تنها فایده‌ش صرفه‌جویی در چند کلیکه، در مقابل هزینه‌ی نگه‌داری دو سیستم موازی.

**تصمیم (تاریخی، در آن جلسه)**: کد (سرویس، Form Request، کنترلرها، مدل فیکس‌شده) و لینک‌های UI (در `admin/specialists/show.blade.php` و `layouts/specialist.blade.php`) **دست‌نخورده می‌مونن** (به درخواست صریح کاربر — «ولش کن همینجوری بمونه»)، ولی این فیچر اولویت پایینی داره و کاندید حذف کامل در `R-Cleanup-DeadCode` است اگر در آینده تصمیم به سادگی معماری گرفته بشه.

**⚠️ به‌روزرسانی نهایی (۲۰۲۶-۰۸-۰۷)**: در یک بحث جدا، کاربر مستقیماً پرسید که آیا با مسیر «جایگزینی کامل `SpecialistSchedule` با `WorkSchedule`» می‌شه هنوز ساعت جدا برای هر روز تعریف کرد — بررسی migration واقعی (`unique('specialist_id')` + یک `start_time`/`end_time` مشترک برای کل `work_days`) نشون داد **نه**، ساختار داده‌ی `WorkSchedule` ذاتاً این امکان رو نداره (فقط یک بازه‌ی مشترک برای چند روز انتخابی، نه ساعت جدا هر روز). بعد از این کشف، کاربر به‌جای هر سه گزینه‌ی مطرح‌شده (جایگزینی کامل با تغییر schema، جایگزینی هیبریدی، یا استفاده‌ی `WorkSchedule` به‌عنوان یک فرم سریع بالای `SpecialistSchedule`)، تصمیم گرفت **کل فیچر رو حذف کنه**. جزئیات کامل حذف در بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کامل فیچر WorkSchedule» پایین‌تر.

**✅ نکته‌ی اطمینان‌بخش تأییدشده:** با خوندن `Specialist::getAvailableSlots()` (متد واقعی مصرف‌شده در فلوی رزرو مشتری) تأیید شد که این متد از `$this->schedules()` (یعنی `SpecialistSchedule`) می‌خونه، **نه** `WorkSchedule`. یعنی نیمه‌کاره بودن WorkSchedule هیچ تأثیری روی فلوی واقعی رزرو مشتری نداشته و نداره.

---

### ✅ Leave-Migration — مهاجرت کامل SpecialistLeave → Leave + صفحه‌ی سراسری مرخصی‌های ادمین

طبق تصمیم قبلی (که در نسخه‌های پیشین این پرامپت به‌عنوان فاز آینده‌ی `R-SpecialistLeave-Upgrade` ثبت شده بود)، این مهاجرت انجام شد.

**تغییرات مدل/معماری:**
- `Specialist::leaves()`: از `SpecialistLeave::class` به `Leave::class` تغییر کرد (هر دو مدل روی همون جدول `leaves` می‌شینن، migration جدید لازم نبود).
- `AuthServiceProvider`: نگاشت `Leave::class => SpecialistPolicy::class` اضافه شد (نگاشت قدیمی `SpecialistLeave::class` هم نگه داشته شد، بی‌ضرره تا پاک‌سازی نهایی).
- **🔴 باگ فتال بالقوه کشف/رفع‌شده**: `SpecialistPolicy::deleteLeave()` پارامترش رو `SpecialistLeave $leave` type-hint کرده بود. چون کنترلر مهاجرت‌یافته الان یک نمونه از `Leave` پاس می‌ده (دو کلاس کاملاً مجزا، نه پدر/فرزند)، این باعث `TypeError` فتال می‌شد. فیکس شد: type-hint به `Leave` تغییر کرد.

**سرویس جدید مشترک:** `App\Services\Leave\LeaveService` — هم توسط ادمین هم توسط خود متخصص استفاده می‌شه (DRY):
- چک تداخل با مرخصی‌های تاییدشده‌ی دیگر (قبلاً اصلاً وجود نداشت)
- چک تداخل با نوبت‌های از قبل رزروشده (قبلاً اصلاً وجود نداشت)
- ارسال `LeaveStatusNotification` (دیتابیس + پیامک) بعد از هر تایید/رد (قبلاً هیچ اطلاع‌رسانی‌ای به متخصص نمی‌رفت)

**🔴 باگ واقعی مهم کشف/رفع‌شده:** مودال «ثبت مرخصی جدید» در پنل ادمین (`admin/specialists/leaves/index.blade.php`) از قبل تاریخ رو **میلادی** (`start_date`/`end_date`) می‌فرستاد، ولی کنترلر قدیمی (`AdminSpecialistLeaveController`) انتظار `start_date_jalali`/`end_date_jalali` داشت — یعنی **این دکمه هیچ‌وقت کار نمی‌کرد** (همیشه خطای validation، بدون این‌که کسی متوجه بشه چرا). ریشه: این Blade از اول برای سیستم `Leave`/`AdminLeaveController` (که Gregorian می‌خواست) ساخته شده بود ولی به روت اشتباه (کنترلر قدیمی SpecialistLeave) وصل شده بود. با مهاجرت به `Leave`، Form Request جدید (`Admin\Leave\StoreLeaveRequest`) دقیقاً با شکل داده‌ی Blade هماهنگ شد — این یک فیکس واقعی بود، نه فقط آپگرید.

**کنترلرها:**
- `AdminSpecialistLeaveController` (`Admin\Specialist\`) — بازنویسی کامل با Form Request + `LeaveService`؛ type-hint از `SpecialistLeave` به `Leave`.
- `SpecialistLeaveController` (`Specialist\Leave\`) — `store()`/`destroy()` به `Leave` سوییچ شدن؛ منطق تبدیل جلالی موجود حفظ شد، فقط از سرویس مشترک (با چک تداخل) استفاده می‌کنه. پیام خطای خام حذف شد.
- `AdminLeaveController` (`Admin\Leave\`) — از یک کنترلر JSON عمدتاً بلااستفاده (فقط `pendingLeaves` روت داشت) به یک **صفحه‌ی کامل Blade سراسری** ارتقا یافت.

**⭐ فیچر جدید (درخواست صریح کاربر): صفحه‌ی سراسری مرخصی‌های ادمین**
قبلاً ادمین برای دیدن مرخصی‌های جدید مجبور بود تک‌تک صفحه‌ی هر متخصص رو باز کنه. حالا:
- Route جدید: `admin.leaves.index` (`routes/admin/leaves.php`، فایل جدید)
- View جدید: `resources/views/admin/leaves/index.blade.php` — ستون‌ها: تاریخ شروع، تاریخ پایان، دلیل، وضعیت، نام متخصص (لینک‌شده به پروفایل متخصص)، عملیات (تایید تک‌کلیکی / رد با مودال دلیل اجباری) + تب فیلتر وضعیت (همه/در انتظار/تاییدشده/ردشده)
- لینک نویگیشن «مرخصی‌ها» به سایدبار ادمین (`layouts/admin.blade.php`) اضافه شد، بعد از «متخصصین» و قبل از «نوبت‌ها»
- `routes/admin/schedule.php`: خط تکراری `leaves/pending` که قبلاً اونجا بود حذف و به فایل جدید منتقل شد (جلوگیری از رجیستر دوباره‌ی یک نام route)

**⭐ باگ route کشف/رفع‌شده (خودانتقادی — اشتباه در یک جلسه‌ی قبلی):** موقع بازنویسی `routes/web/specialistprofile.php` در فاز WorkSchedule، گروه مرخصی با `->name('leaves.')` پیچیده شد و مسیر لیست `index` نام گرفت — یعنی نام نهایی `specialist.leaves.index` شد. ولی کنترلر (`store`/`destroy`) و `leaves-create.blade.php` (لینک‌های بازگشت) به نام «بدون‌نقطه»‌ی قدیمی `specialist.leaves` وابسته بودن، در حالی که `layouts/specialist.blade.php` (نویگیشن سایدبار) به نام «نقطه‌دار» `specialist.leaves.index` وابسته بود — دو کانوانسیون هم‌زمان در کدبیس، هرکدوم جای دیگه‌ای شکار می‌شد. **فیکس نهایی (قطعی):** هر دو نام مستقیماً روی یک URI ثبت شدن (`specialist.leaves` و `specialist.leaves.index` هر دو به همون `index()`) تا این دسته باگ برای همیشه تموم بشه، به‌جای شکار تک‌تک فایل‌های مصرف‌کننده.

**نتیجه‌گیری برای فازهای پاک‌سازی آینده:** مدل `SpecialistLeave` کاندید حذف در `R-Cleanup-DeadCode` بود (✅ در همون فاز حذف شد — به بخش مربوطه بالاتر نگاه کن). ⚠️ **تصحیح**: `AdminSpecialistLeaveController` **نه** یک فایل جدا/تکراری که باید حذف بشه — همون فایل و همون namespace **بازنویسی شد** (کد قدیمی جایگزین شد، نه این‌که یک فایل جدید کنارش ساخته بشه)؛ پس چیزی برای حذف فیزیکی این کنترلر باقی نمی‌مونه.

---

### ✅ R-Events — فعال‌سازی کامل زیرساخت Event/Listener + رفع کرش/برگشت‌وجه لغو نوبت + چرخه‌ی برداشت وجه

**🔴 بزرگ‌ترین کشف این فاز: کل سیستم Event/Listener پروژه از روز اول کاملاً بی‌اثر بود.**
- `bootstrap/providers.php` فقط ۳ provider داشت (`App`, `Auth`, `Telescope`)؛ `EventServiceProvider` و `BookingServiceProvider` هیچ‌وقت ثبت نشده بودن — یعنی آرایه‌ی `$listen` (نگاشت `BookingCreated`/`NewUserRegistered` به Listenerهاشون) هیچ‌وقت boot نمی‌شد.
- `BookingCreated` و `NewUserRegistered` علاوه بر این، در کل کدبیس **هیچ‌جا اصلاً dispatch هم نمی‌شدن** (نه در کنترلر، نه در Service، نه در Observer) — یعنی ادمین از روز اول نه نوتیفیکیشن نوبت جدید می‌گرفت، نه نوتیفیکیشن کاربر جدید.
- زنجیره‌ی یادآوری نوبت (`bookings:send-reminders`) هم به همین دلیل هیچ‌وقت واقعاً شیدول نمی‌شد؛ تنها محل شیدولش داخل همون `EventServiceProvider`ی بود که اصلاً boot نمی‌شد.
- `ScheduleBookingTasksEvent`/`RegisterBookingSchedule` کاملاً کد مرده بودن (خود job مربوطه — `CancelUnpaidBookings` — از قبل مستقیم توی `routes/console.php` شیدول شده بود، بدون نیاز به این event/listener).

**راه‌حل‌ها:**
- `EventServiceProvider` بازنویسی و در `bootstrap/providers.php` ثبت شد.
- `BookingCreated` حالا از `BookingObserver::created()` (نه از کنترلر/سرویس) dispatch می‌شه — همین یک hook هم رزرو مشتری هم ایجاد دستی نوبت توسط ادمین رو پوشش می‌ده.
- `NewUserRegistered` از `RegisteredUserController::store()` (کنترلر واقعاً وایرشده در `routes/web/auth.php`؛ `RegisterController.php` قدیمی کد مرده و بلااستفاده‌ست) dispatch می‌شه.
- `ScheduleBookingTasksEvent`/`ReminderScheduleEvent`/Listenerهاشون حذف شدن؛ شیدول `bookings:send-reminders` مستقیم به `bootstrap/app.php` منتقل شد (مثل الگوی `wallet:settle-pending`).
- `BookingServiceProvider` (provider مرده‌ی دیگه، هیچ‌وقت ثبت نشده بود — `PaymentService`/`SMSService` الان سازنده‌ی بدون‌آرگومان دارن، Policyها جای Gate definitionهاش رو گرفتن) کلاً حذف شد.

**🔴 باگ بحرانی مالی/کرش کشف/رفع‌شده: لغو نوبت توسط خود مشتری همیشه ۵۰۰ فتال می‌داد و پولش هم برنمی‌گشت**
`BookingService::cancelBooking()` نه `cancelled_by` ست می‌کرد (پس شاخه‌ی برگشت‌وجه کیف‌پول در `BookingObserver` هیچ‌وقت اجرا نمی‌شد) نه نوتیفیکیشن‌ها رو با آرگومان درست صدا می‌زد — `BookingStatusUpdated` و `SpecialistBookingCancelledNotification` هر دو پارامتر دوم اجباری دارن؛ فراخوانی با یک آرگومان کم یک `\ArgumentCountError` پرتاب می‌کرد که چون `\Exception` نیست، `catch (Exception $e)` نمی‌گرفتش — یعنی transaction رول‌بک می‌خورد و کاربر ۵۰۰ می‌گرفت. فیکس: `cancelled_by='customer'` + `cancelled_at` ست می‌شه و به‌جای notify مستقیم، Event جدید `BookingCancelled` dispatch می‌شه.

⚠️ **نکته‌ی مهم برای مسیر ادمین**: عمداً `cancelled_by='admin'` روی مدل ست **نشد** — چون این ستون توسط `BookingObserver` برای برگشت‌وجه کیف‌پول خونده می‌شه، و مسیر ادمین از قبل برگشت‌وجه واقعی از درگاه (`RefundService`) داره؛ اگه هر دو مسیر هم‌زمان فعال بشن، برگشت‌وجه دوبل رخ می‌ده. برای مسیر ادمین فقط Event (برای نوتیفیکیشن) dispatch می‌شه، نه تغییر در ستون دیتابیس.

**Event/Listener/Notification جدید:**
- `App\Events\Booking\BookingCancelled` — از هر ۳ مسیر لغو (مشتری/متخصص/ادمین) dispatch می‌شه؛ `App\Listeners\Booking\Cancellation\SendBookingCancellationNotifications` نوتیفیکیشن دیتابیسی یکسان‌شده می‌فرسته (پیامک دست‌نخورده می‌مونه، چون `BookingObserver` بدون قید برای هر لغوی پیامک می‌فرسته).
  - ⭐ **باگ واژگان کشف/رفع‌شده**: `SpecialistBookingCancelledNotification` فقط `'user'`/`'system'` رو می‌شناسه (بقیه رو «ادمین یا متخصص» نشون می‌ده)، در حالی که ستون `cancelled_by` از `'customer'/'specialist'/'admin'` استفاده می‌کنه. متد `mapToLegacyCancellerLabel()` این ترجمه رو انجام می‌ده (`'customer'` → `'user'`).
- چرخه‌ی کامل برداشت وجه: `App\Events\Withdrawal\{Requested,Approved,Rejected}` + Listenerها + `AdminNewWithdrawalRequestNotification`/`WithdrawalApprovedNotification`/`WithdrawalRejectedNotification` — قبلاً نه ادمین از درخواست جدید باخبر می‌شد، نه متخصص از تایید/رد (`// TODO: ارسال SMS/Notification` خالی توی `WalletAdminService`).

**⭐ ششمین نمونه‌ی باگ تکراری `is_admin` مستقیم به‌جای `hasPermission('access_admin_panel')`** (بعد از R-AdminLoyalty و R-AdminForms) در `SendAdminBookingNotifications`, `SendNewUserNotifications`, `ReviewService::notifyAdminAboutNegativeReview()` پیدا و فیکس شد.

**🔴 دو فایل با نام اشتباه که فقط روی Windows/XAMPP کار می‌کردن (کشف‌شده با اسکن سراسری تطابق نام فایل/نام کلاس):**
- `Walletadminservice.php` → `WalletAdminService.php`
- `Checkpasswordstrengthrequest.php` → `CheckPasswordStrengthRequest.php`

روی فایل‌سیستم حساس به حروف (Linux/production طبق cron مستندشده) composer این کلاس‌ها رو پیدا نمی‌کرد → فتال ارور «Class not found» فقط بعد از deploy، نه در تست لوکال — دقیقاً همون الگوی «Windows اجازه می‌ده، Linux می‌شکنه» که قبلاً با symlink گالری دیده بودیم.

**🟢 نکته‌ی جانبی کشف/رفع‌شده (بی‌ربط به دامنه‌ی این فاز):** `App\Http\Controllers\User\SpecialistController.php` یک `use App\Http\Controllers\Exception;` نادرست داشت که باعث می‌شد `catch (Exception $e)` همون فایل، به‌جای گرفتن استثنا، خودش کرش کنه (چون این کلاس وجود نداره). حذف شد.

**⭐ قرارداد Namespace جدید (اضافه بر قرارداد قبلی `Admin\{Domain}`، برای Event/Listener/Notification):**
لایه‌بندی تودرتو بر اساس دامنه + مرحله‌ی چرخه‌ی زندگی:
```
App\Events\{Domain}\{Stage?}\...          مثال: Events\Booking\BookingCancelled، Events\Withdrawal\Requested\WithdrawalRequested
App\Listeners\{Actor?}\{Domain}\{Stage?}\...  مثال: Listeners\Admin\Booking\SendAdminBookingNotifications، Listeners\Booking\Cancellation\SendBookingCancellationNotifications
App\Notifications\{Actor?}\{Domain}\{Stage?}\... مثال: Notifications\Admin\Withdrawal\Request\AdminNewWithdrawalRequestNotification
```
⚠️ **قانون حیاتی این قرارداد**: فقط کلاس‌هایی که *واقعاً* namespace‌شون تغییر کرده باید با مسیر جدید import بشن — کلاس‌های همسایه که جابه‌جا نشدن (مثلاً `BookingCreated`, `NewUserRegistered` در این فاز، یا نوتیفیکیشن‌های قدیمی‌ای که جابه‌جا نشدن) نباید فقط به این دلیل که «کنارشون» یه چیز دیگه جابه‌جا شده، به اشتباه با یک زیرپوشه‌ی فرضی import بشن. این دقیقاً دو دور کامل باگ فتال (`R-Events1.zip`, نصفه‌کاره) در همین فاز ایجاد کرد؛ رفعش با یک اسکریپت پایتون ساده انجام شد: تطبیق خودکار namespace هر فایل با مسیر فیزیکی‌اش + تطبیق هر `use App\...` در کل پروژه با کلاس‌های واقعاً موجود.

**⭐ نکته‌ی مکمل (کشف‌شده بعداً در فیچر تسویه‌ی دستی کیف‌پول)**: این قانون یک حالت معکوس هم داره که باید جداگانه چک بشه — نه فقط «کلاس جابه‌جانشده رو با namespace جدید import نکن»، بلکه «وقتی یک کلاس واقعاً جابه‌جا/جایگزین می‌شه (مثل `StoreWithdrawalRequest` که در R-SpecialistWallet به namespace تودرتو منتقل شد)، باید با `grep -rn` تأیید کرد که همه‌ی مصرف‌کننده‌ها (کنترلرها) واقعاً به namespace جدید سوییچ کردن، نه اینکه فایل جدید کنار فایل قدیمی ساخته بشه و کنترلر ناخواسته به قدیمی وصل بمونه». به بخش «فیچر تسویه‌ی دستی کیف‌پول توسط ادمین» نگاه کن.

**تصمیم مستند: `PaymentSucceeded` و `BookingCompleted` عمداً پیاده‌سازی نشدن.** بعد از بررسی کامل، هیچ باگ واقعی‌ای پیدا نشد که این دو Event حلش کنن — پرداخت موفق از قبل با اتکا به `Booking::observe()` (که مستقیم و پایدار در `AppServiceProvider` ثبت می‌شه، نه از طریق زنجیره‌ی شکننده‌ی `EventServiceProvider`) درست کار می‌کنه، و `markAsCompleted()` هم از قبل درست کار می‌کنه. تبدیلشون به Event صرفاً بازآرایی معماری بدون رفع باگ بود و ریسک بی‌دلیلی به کد مالی حساس تحمیل می‌کرد — اگه در آینده یه مصرف‌کننده‌ی جدید واقعی پیدا شد (مثلاً رسید ایمیلی)، اون‌موقع دقیقاً برای همون نیاز اضافه می‌شن.

---

## ⭐ رفع مستقل (خارج از فاز رفکتور): صفحات عمومی وبلاگ برای بازدیدکننده
ضمن بررسی فاز R-AdminBlog مشخص شد سیستم وبلاگ سمت بک‌اند کامله (مدل، migration، seeder با ۵ دسته‌بندی، پنل ادمین) ولی **سمت بازدیدکننده هیچ‌وقت واقعاً وصل نشده بود**:
- `BlogController::index()`/`show()` همیشه فقط JSON خام برمی‌گردوندن، نه یک صفحه.
- template کامل نمایش تک‌مقاله به‌جای `resources/views/blog/show.blade.php`، اشتباهاً توی `resources/views/admin/blog/show.blade.php` ذخیره شده بود (همون فایلی که باعث فتال ارور پنل ادمین می‌شد).
- **هیچ لینک ناوبری** به `/blog` در کل سایت وجود نداشت.
- صفحه‌ی لیست مقالات (`blog.index`) اصلاً هیچ‌وقت Blade نداشت.

**فیکس‌ها:**
- `BlogController::index()`/`show()` حالا view واقعی برمی‌گردونن؛ `show()` شمارنده‌ی بازدید رو افزایش می‌ده و ۳ مقاله‌ی مرتبط اضافه می‌کنه.
- `resources/views/blog/index.blade.php` (جدید) — لیست مقالات با فیلتر دسته‌بندی، تم طلایی مشتری.
- `resources/views/blog/show.blade.php` (بازسازی کامل، مستقل از نسخه‌ی ادمین) — routeهای خراب حذف شدن، بخش تگ‌ها (که در دیتابیس وجود نداره) پاک شد.
- `layouts/app.blade.php`: لینک «وبلاگ» به نوار ناوبری دسکتاپ و موبایل اضافه شد.

**نکته‌ی باز:** `store`/`update`/`destroy`/`getCategories` همون `BlogController` عمومی در هیچ route ای ثبت نشدن ولی چون این کنترلر بدون middleware احراز هویته، ریسک امنیتی بالقوه‌ست — به «نکته‌ی امنیتی باز» بالای این سند نگاه کن. ⚠️ **به‌روزرسانی (۲۰۲۶-۰۷-۲۶):** بازبینی نشون داد این متدها اصلاً روی کنترلر وجود ندارن (کد مرده‌ی شکسته، نه ریسک امنیتی زنده)؛ روت‌های اشاره‌کننده حذف شدن — جزئیات در بخش «✅ نکته‌ی امنیتی باز — رفع شد» بالا.

---

## ⭐ رفع مستقل: سه باگ عملکردی کشف‌شده با Laravel Telescope + لاگ‌گذاری متمرکز پیامک

**این کار مستقل از فازبندی رفکتور اصلیه (مثل رفع مستقل صفحات عمومی وبلاگ بالا) — کاربر لاگ‌های واقعی Laravel Telescope (شامل تایمینگ کوئری‌ها و خطاهای واقعی) رو ضمیمه کرد و مستقیماً باعث کشف علت ریشه‌ای هر سه مشکل شد؛ بدون Telescope این تشخیص‌ها ممکن نبود (مخصوصاً مورد ۱، که فقط یک لاگ خطای دقیق با تایمینگ آشکارش کرد).**

### ۱) کندی ۳۰+ ثانیه‌ای لاگین
**ریشه:** `AuthenticatedSessionController::store()` → `PhoneVerificationService::sendLoginCode()` → `SMSService::sendTemplate()` یک تماس **synchronous** و بدون هیچ `timeout` به Kavenegar می‌زد. خود Telescope دقیقاً نشون داد:
```
Kavenegar HTTP Error (Lookup): Failed to connect to api.kavenegar.com port 443 after 32312 ms
```
یعنی هر بار که شبکه به Kavenegar کند/قطع باشه (چه لوکال چه production)، کل صفحه‌ی لاگین دقیقاً به همون اندازه معطل می‌مونه.

**فیکس:** `App\Jobs\SendLoginVerificationCodeJob` (جدید) — تولید کد و ذخیره‌ش در دیتابیس همچنان synchronous و سریع می‌مونه؛ فقط خود ارسال HTTP به Kavenegar به صف (`ShouldQueue`, `timeout=15`, `tries=2`) منتقل شد. `PhoneVerificationService::sendLoginCode()` بعد از ذخیره‌ی کد، فقط `dispatch()` می‌کنه و بلافاصله `true` برمی‌گردونه.

⚠️ **پیش‌نیاز عملیاتی:** چون `QUEUE_CONNECTION` پیش‌فرض پروژه `database`ه، این فیکس فقط وقتی واقعاً پیامک رو می‌فرسته که یک Worker (`php artisan queue:work`) در حال اجرا باشه — دقیقاً همون الگوی وابستگی‌ای که قبلاً برای `wallet:settle-pending` هم مستند شده بود. اگه Worker بسته باشه، Job فقط توی جدول `jobs` می‌مونه (کاربر دیگه ۳۰ ثانیه معطل نمی‌مونه، ولی پیامک واقعی تا اجرای بعدی Worker نمی‌ره).

### ۲) خالی/رندرنشدن کامل صفحه‌ی رزرو نوبت (`bookings/create.blade.php`)
**ریشه:** این صفحه بر خلاف بقیه‌ی پروژه (که به Blade خالص/vanilla JS مهاجرت کرده، مثل `bookings/reschedule.blade.php`) هنوز یک اپ **Vue 3** بود که `vue.global.js` و `persian-date` رو مستقیم از CDN خارجی (`unpkg.com`, `cdn.jsdelivr.net`) لود می‌کرد. چون همون سیستم لوکال کاربر در همون بازه‌ی زمانی به اینترنت بیرون دسترسی نداشت (دقیقاً همون چیزی که باعث تایم‌اوت Kavenegar در مورد ۱ هم شده بود)، این دو اسکریپت لود نشدن، `Vue` تعریف نشد، و چون Blade خط `@{{ getServiceName }}` رو به‌صورت متن خام (`{{ getServiceName }}`) در HTML خروجی می‌ده تا Vue کلاینت پردازشش کنه، بدون Vue کاربر دقیقاً همون متن خام رو دید (طبق اسکرین‌شات).

**فیکس:** بازنویسی کامل `bookings/create.blade.php` به vanilla JS + `fetch` — بدون هیچ کتابخونه یا CDN خارجی. تبدیل میلادی↔شمسی هم با همون الگوریتم خودکفای jcal (که در `bookings/index.blade.php` و بقیه‌ی صفحات پروژه از قبل استفاده می‌شه) پیاده‌سازی شد، نه `persian-date`. Endpoint های API دست‌نخورده موندن: `/api/services`, `/api/specialists/{serviceId}`, `/api/available-dates/{specialist}`, `/api/time-slots/{specialist}/{date}`.

⚠️ **یادآوری برای صفحات مشابه:** ~~`bookings/reschedule.blade.php` هم هنوز `persian-date` رو از `unpkg.com` لود می‌کنه~~ ✅ **در ۲۰۲۶-۰۸-۰۲ به jcal خودکفا مهاجرت کرد** — به بخش «⭐ رفع مستقل ... تکمیل ۹ کاندید» پایین‌تر، آیتم ۷، نگاه کن.

### ۳) N+1 در `/services` + کوئری امتیاز وفاداری روی هر صفحه
**الف) N+1 واقعی:** `ServiceController::index()` سرویس‌ها رو با `BeautyService::paginate(12)` بدون `with('category')` می‌گرفت؛ چون `services/index.blade.php` برای هر سرویس `$service->category` رو صدا می‌زد، هر ردیف (نه هر category یکتا) جدا کوئری می‌زد — طبق تلسکوپ از ۱۸ کوئری این صفحه، **۱۱ تاش تکراری** بودن. فیکس: `BeautyService::with('category')->paginate(12)`.

> ⭐ یافته‌ی جانبی: ~~فیلتر دسته‌بندی (`request('category')`) توی همین کنترلر اصلاً اعمال نمی‌شه~~ ✅ **در ۲۰۲۶-۰۸-۰۲ فیکس شد** — به بخش «⭐ رفع مستقل ... تکمیل ۹ کاندید» پایین‌تر، آیتم ۳، نگاه کن.

**ب) کوئری loyalty points روی هر صفحه:** `layouts/app.blade.php` یک `SUM` مستقیم روی `loyalty_points` رو برای نمایش امتیاز کاربر در نوار ناوبری، **روی هر تک صفحه‌ی هر کاربر لاگین‌شده** (داشبورد، لیست خدمات، فرم رزرو، همه‌جا) دوباره اجرا می‌کرد. فیکس: `Cache::remember('user:{id}:loyalty_points', 5min, ...)`.

⚠️ **نکته‌ی حیاتی این فیکس:** چون امتیاز وفاداری واقعاً تغییر می‌کنه (کسب/خرج)، کش بدون invalidation باعث نمایش عدد غلط تا ۵ دقیقه می‌شد. برای همین `Cache::forget("user:{id}:loyalty_points")` دقیقاً روی هر ۵ نقطه‌ای که واقعاً `LoyaltyPoint` ساخته می‌شه اضافه شد:
- `LoyaltyService::redeemReward()`
- `LoyaltyService::earnPointsFromBooking()`
- `LoyaltyAdminService::addUserPoints()`
- `LoyaltyAdminService::deductUserPoints()`
- `User::addLoyaltyPoints()`

### ۴) لاگ‌گذاری متمرکز پیامک (درخواست صریح کاربر، برای مشاهده‌ی محلی بدون نیاز به Kavenegar واقعی)
کاربر خواست بتونه در فایل لاگ لوکال، کد OTP و همچنین پیامک‌های تایید/رد نوبت و تایید/رد مرخصی رو ببینه تا از صحت روند مطمئن بشه.

**بررسی معماری قبل از فیکس:** تمام مسیرهای پیامکی پروژه (OTP لاگین/ثبت‌نام از طریق `PhoneVerificationService`، `BookingStatusUpdated::toSms()` برای تایید/رد/تکمیل/لغو نوبت، `LeaveStatusNotification::toSms()` برای تایید/رد مرخصی، `WithdrawalApprovedNotification`/`WithdrawalRejectedNotification::toSms()`) نهایتاً از یک نقطه‌ی واحد رد می‌شن: `App\Channels\SmsChannel::send()` که `toSms()` رو صدا می‌زنه، و خود `toSms()` هر کدوم در نهایت `SMSService::send()` یا `SMSService::sendTemplate()` رو صدا می‌زنن.

**فیکس:** به‌جای پخش `Log::info` در تک‌تک کلاس‌های Notification (که هم تکراری بود هم نگه‌داریش سخت)، یک `Log::info` واحد در **ورودی** هر دو متد `SMSService::send()` و `SMSService::sendTemplate()` اضافه شد — قبل از هر `return`/شرط local-skip، پس چه در حالت واقعی ارسال (production) چه در حالت local-skip (که چون `send_in_local` پیش‌فرضش `false`ست، اکثر تست‌های لوکال از همین مسیر رد می‌شن)، محتوای واقعی هر پیامک (شامل کد OTP به‌عنوان `tokens[0]` در `sendTemplate`) همیشه در `storage/logs/laravel.log` ثبت می‌شه.

علاوه بر این، `PhoneVerificationService::sendLoginCode()` یک `Log::info` جدای synchronous هم قبل از `dispatch()` کردن Job می‌زنه (شامل خود کد) — تا حتی وقتی `queue:work` فعلاً باز نیست، کد بلافاصله (نه بعد از پردازش صف) در لاگ قابل مشاهده باشه.

**فایل‌های این نشست:**
- `app/Jobs/SendLoginVerificationCodeJob.php` (جدید)
- `app/Services/PhoneVerificationService.php`
- `app/Services/SMSService.php`
- `app/Http/Controllers/User/ServiceController.php`
- `resources/views/bookings/create.blade.php` (بازنویسی کامل، Vue حذف شد)
- `resources/views/layouts/app.blade.php`
- `app/Services/LoyaltyService.php`
- `app/Services/Admin/Loyalty/LoyaltyAdminService.php`
- `app/Models/User.php`

---

## ⭐ رفع مستقل: فیچر تسویه‌ی دستی کیف‌پول توسط ادمین + رفع باگ ۴۰۳ دائمی درخواست برداشت متخصص

**زمینه (سناریوی کاربر):** چون تسویه‌ی خودکار `wallet:settle-pending` فقط شبانه (۰۱:۰۰) و فقط برای تراکنش‌های سررسیدشده اجرا می‌شه، ادمین نیاز داشت بتونه به‌صورت دستی هم پول‌های در انتظار تسویه رو منتقل کنه تا متخصص زودتر بتونه درخواست برداشت بزنه؛ همچنین لازم بود مطمئن بشیم متخصص می‌تونه مبلغ درخواست برداشت رو خودش دستی وارد کنه.

### ۱) 🔴 باگ کشف‌شده: ثبت تکراری شیدول `wallet:settle-pending`
هم `routes/console.php` (`Schedule::command(...)->dailyAt('01:00')`) هم `bootstrap/app.php` این دستور رو شبانه ثبت کرده بودن — یعنی هر شب دوبار اجرا می‌شد (همون الگوی «ثبت تکراری» که قبلاً با `DiscountCodeObserver` هم دیده شده بود). چون منطق idempotent هست (فقط رکوردهای `pending` رو می‌گیره) ضرر عملی نداشت ولی درست نبود. **رفع شد:** ثبت در `routes/console.php` حذف شد؛ فقط `bootstrap/app.php` باقی موند.

### ۲) فیچر جدید: تسویه‌ی دستی از پنل ادمین
منطق مشترک به `WalletAdminService::settlePendingIncomes(?SpecialistWallet $wallet, bool $ignoreDelay, string $source)` منتقل شد — هم دستور artisan (`wallet:settle-pending`) هم دکمه‌های جدید ادمین از همین یک متد استفاده می‌کنن (DRY).

- **دو حالت:**
  - تسریع تسویه‌ی سررسیدشده‌ها (پیش‌فرض): فقط تراکنش‌هایی که `settlement_date`شون گذشته، تسویه می‌شن — دقیقاً همون منطق شبانه، فقط زودتر و دستی.
  - نادیده گرفتن مهلت (چک‌باکس اختیاری `ignore_delay`): همه‌ی تراکنش‌های `pending` بلافاصله تسویه می‌شن، حتی سررسیدنشده‌ها.
- **دو دامنه:**
  - همه‌ی متخصصین: دکمه‌ی «تسویه‌ی دستی همه‌ی در‌انتظارها» در `admin/wallet/index.blade.php`.
  - یک متخصص خاص: دکمه‌ی «تسویه‌ی دستی این متخصص» + مودال در `admin/wallet/show.blade.php` (فقط وقتی `pending_amount > 0`).
- هر تراکنش تسویه‌شده در `metadata` مقدار `settled_by` می‌گیره (`'schedule'` یا `'admin_manual'`) تا بشه فهمید از کدوم مسیر تسویه شده.
- **کنترلر:** `AdminWalletController::settlePending()` (همه) و `settlePendingForWallet()` (یک متخصص) — هر دو نتیجه (تعداد/مبلغ/تعداد ناموفق) رو با پیام فارسی به کاربر برمی‌گردونن.
- **Routes:** `admin.wallet.settle-pending` (POST `/admin/wallet/settle-pending`)، `admin.wallet.settle-pending-wallet` (POST `/admin/wallet/{wallet}/settle-pending`).
- **UI/UX:** الگوی تایید جدید `data-confirm-action` به `layouts/admin.blade.php` اضافه شد (SweetAlert2، مجزا از `data-confirm-delete` موجود چون متن/رنگ متفاوت لازم داشت — سبز به‌جای قرمز، آیکون question به‌جای warning).

### ۳) 🔴 باگ بحرانی کشف/رفع‌شده — همین باگ مانع مبلغ دستی درخواست برداشت متخصص می‌شد
`SpecialistWithdrawalController::store()` به کلاس **قدیمی و کنارگذاشته‌شده‌ی** `App\Http\Requests\Specialist\StoreWithdrawalRequest` (namespace ریشه، از فاز R2) وصل بود، نه نسخه‌ی درست و به‌روز (`App\Http\Requests\Specialist\Wallet\Withdrawal\StoreWithdrawalRequest`، ساخته‌شده در فاز R-SpecialistWallet با تمام فیکس‌ها). این باعث دو مشکل هم‌زمان می‌شد:

1. **`authorize()`** نسخه‌ی قدیمی `auth()->user()->hasRole('specialist')` چک می‌کرد. تأیید شد که در کل پروژه **هیچ‌جا** نقشی به اسم `specialist` به هیچ کاربری اساین نمی‌شه — میدل‌ور گروه روت‌های متخصص (`routes/web/specialistprofile.php`) فقط `auth`+`verified`ه، و ارتباط کاربر↔متخصص کاملاً بر پایه‌ی match شماره تلفن (نه سیستم Role/Permission) هست. یعنی این شرط همیشه `false` بود و لاراول **قبل از رسیدن به validation یا منطق کنترلر**، خودکار یک `403 Forbidden` برمی‌گردوند — دقیقاً همون چیزی که مانع ثبت درخواست برداشت با مبلغ دستی می‌شد (فیلد ورودی مبلغ در `create-withdrawal.blade.php` از قبل درست بود، مشکل فقط اتصال اشتباه به Form Request بود).
2. همون باگ قدیمی و مستندشده‌ی پروژه (`WalletSetting::get()` به‌جای `::first()` — که در R-SpecialistWallet برای ۳ متد دیگه فیکس شده بود) هنوز توی این فایل قدیمی وجود داشت؛ یعنی حتی اگه ۴۰۳ هم نبود، قانون `min:` مبلغ برداشت عملاً بی‌اثر می‌موند.

**فیکس نهایی:** فقط یک خط `use` در کنترلر عوض شد تا به کلاس درست اشاره کنه (که هم مجوزدهی رو به Policy `requestWithdrawal` واگذار می‌کنه، هم باگ `WalletSetting` رو نداره). هیچ تغییری در Blade یا Service لازم نبود.

⚠️ فایل قدیمی (`app/Http/Requests/Specialist/StoreWithdrawalRequest.php`) الان کاملاً کد مرده‌ست — به تصمیم کاربر، حذفش موکول شد به فاز `R-Cleanup-DeadCode` (نه حذف فوری؛ به لیست کاندیدهای اون فاز پایین‌تر اضافه شد).

**فایل‌های این نشست:**
- `routes/console.php`
- `app/Services/Admin/Wallet/WalletAdminService.php`
- `app/Console/Commands/SettlePendingWalletIncomes.php`
- `routes/admin/wallet.php`
- `app/Http/Controllers/Admin/Wallet/AdminWalletController.php`
- `resources/views/admin/wallet/index.blade.php`
- `resources/views/admin/wallet/show.blade.php`
- `resources/views/layouts/admin.blade.php`
- `app/Http/Controllers/Specialist/Wallet/Withdrawal/SpecialistWithdrawalController.php`

---

## ⭐ رفع مستقل: باگ حیاتی نوتیفیکیشن/پیامک تکراری تایید برداشت وجه (چندجلسه‌ای، Claude + Kimi.ai)

**سناریوی کاربر:** وقتی ادمین درخواست برداشت وجه یک متخصص رو تایید می‌کنه، متخصص دقیقاً ۲ نوتیفیکیشن دیتابیسی و ۲ پیامک یکسان برای همون تایید دریافت می‌کنه (با تایم‌استمپ تقریباً یکسان).

### مسیرهای بررسی‌شده و رد شد
1. **دابل‌سابمیت فرم تایید (فرضیه‌ی اول)**: چون فرم `withdrawal-show.blade.php` هیچ محافظتی در برابر دابل‌کلیک نداشت و چک وضعیت (`in_array($status, ['pending','processing'])`) هم atomic نبود، این به‌عنوان علت محتمل در نظر گرفته شد و `lockForUpdate()` + بازبینی وضعیت داخل transaction به `WalletAdminService::approveWithdrawal()`/`rejectWithdrawal()` اضافه شد (خوب و لازمه، ولی بعداً مشخص شد این *علت اصلی* نبود — با queue log مشخص شد که حتی برای یک تست کاملاً تازه با `queue:clear` قبلش و فقط یک کلیک، دقیقاً همون listener دو بار پشت‌سرهم `RUNNING`/`DONE` می‌شد).
2. **دابل ثبت provider (`config/app.php` هم `App\Providers\Event\EventServiceProvider` رو لیست می‌کرد هم `bootstrap/providers.php`)**: این دابل ثبت واقعاً در کد وجود داشت و پاکسازی شد (به‌عنوان بهداشت کد، نه رفع این باگ خاص) — چون منطق داخلی Laravel (`Application::register()`) provider هم‌نام رو با `getProvider()`/`instanceof` تشخیص و از رجیستر دوباره صرف‌نظر می‌کنه، این دابل‌نویسی به‌تنهایی باعث دوبار boot شدن provider (و در نتیجه دوبار `Event::listen()`) نمی‌شد. کاربر خودش این رو با `count(Event::getListeners(...))` و رد کردن این فرضیه در Tinker تأیید کرد.

### ریشه‌ی واقعی (کشف‌شده توسط کاربر با کمک Kimi.ai، تأییدشده با Tinker + queue log + laravel.log)
کلاس والد `Illuminate\Foundation\Support\Providers\EventServiceProvider` حتی با override شدن `shouldDiscoverEvents(): bool { return false; }`، در برخی مسیرهای boot لاراول ۱۱ باز هم auto-discovery رو روی `app/Listeners` اجرا می‌کرد؛ نتیجه: `count(Event::getListeners(WithdrawalApproved::class))` عدد `۳` برمی‌گشت (یک بار `ClassName` از آرایه‌ی `$listen`، یک بار `ClassName@handle` از discovery، یک بار wildcard تلسکوپ) — یعنی هر listener هر پروژه (نه فقط برداشت وجه) دقیقاً دوبار اجرا می‌شد؛ همین باعث می‌شد `AdminNewBookingNotification`, `AdminPaymentReceivedNotification`, `SendBookingCompletionNotifications` و بقیه هم به همین ترتیب دوبار fire بشن.

**فیکس نهایی** در `App\Providers\Event\EventServiceProvider`:
```php
public function boot(): void
{
    static::disableEventDiscovery(); // فلگ استاتیک $shouldDiscoverEvents رو مستقیم در کلاس والد false می‌کنه
    parent::boot();
}
```
بعد از فیکس: `count(Event::getListeners(...))` به `۲` رسید (۱ listener واقعی + ۱ wildcard تلسکوپ) و `laravel.log` فقط یک ارسال SMS به‌ازای هر تایید برداشت نشون داد.

### پاکسازی‌های همراه (defense-in-depth، پابرجا نگه داشته شدن چون به‌درد می‌خورن، نه چون علت اصلی بودن)
- `WalletAdminService::approveWithdrawal()`/`rejectWithdrawal()`: `lockForUpdate()` روی ردیف `WithdrawalRequest` (و کیف‌پول، در reject) + بازبینی وضعیت داخل transaction — محافظ مستقل در برابر race condition واقعی (نه این باگ خاص، ولی سناریوی معتبر دیگه‌ای‌ست).
- `resources/views/admin/wallet/withdrawal-show.blade.php` + `layouts/admin.blade.php`: دکمه‌های تایید/رد و همه‌ی دکمه‌های `data-confirm-delete`/`data-confirm-action` بعد از اولین کلیک غیرفعال می‌شن (چون `form.submit()` برنامه‌نویسی‌شده رویداد `onsubmit` رو صدا نمی‌زنه، گارد باید داخل خود تابع JS مشترک باشه، نه فقط `onsubmit` روی فرم).
- `config/app.php`: آرایه‌ی قدیمی‌سبک `providers` (کپی کامل لیست providerهای فریم‌ورک، سبک Laravel 10) حذف شد؛ `bootstrap/providers.php` تنها منبع providerهای پروژه شد (شامل انتقال Kavenegar/Excel/DomPDF/Verta/`RouteServiceProvider` که فقط در `config/app.php` بودن).
- `app/Providers/RouteServiceProvider.php`: بلوک `$this->routes(function () {...})` که دوباره `routes/web.php`/`routes/api.php`/`routes/admin/reports.php` رو require می‌کرد حذف شد — این فایل‌ها از قبل توسط `bootstrap/app.php`→`withRouting()` بارگذاری می‌شدن؛ این تکرار باعث دابل‌شدن هر route (نه فقط برداشت وجه) در RouteCollection می‌شد (بی‌ضرر برای dispatch تک‌ریکوئست، ولی منبع بالقوه‌ی تصادم نام route/گیجی در `route:list`).

### دو باگ کاملاً جدا، کشف‌شده حین همین بررسی، در `specialist/notifications.blade.php`
1. **دکمه‌ی «خوانده شد» هیچ واکنشی نداشت**: `onclick="markNotificationAsReadOnPage({{ $notification->id }})"` بدون کوتیشن — چون کلید نوتیفیکیشن `uuid` (رشته) هست نه عدد، خروجی HTML چیزی مثل `markNotificationAsReadOnPage(9f9e2a1c-3d44-...)` تولید می‌کرد که یک SyntaxError جاوااسکریپتیه (تفسیر به‌عنوان عبارت تفریق). فیکس: `onclick="markNotificationAsReadOnPage('{{ $notification->id }}')"`.
2. **کلیک روی خود ردیف نوتیفیکیشن، شمارنده‌ی خونده‌نشده‌ها رو کم نمی‌کرد**: کلیک روی `.notification-link` بدون `e.preventDefault()` بود، پس مرورگر بلافاصله ناوبری می‌کرد و درخواست `fetch` مارک-as-read رو قبل از رسیدن به سرور لغو می‌کرد. فیکس: `preventDefault()` + ناوبری واقعی داخل `.finally()` بعد از تکمیل fetch — دقیقاً همون الگویی که در dropdown هدر (`layouts/specialist.blade.php`) از اول درست پیاده‌سازی شده بود.

### ⭐ باگ سوم — کشف‌شده بین دو زیپ آپلودی، نه در این جلسه ساخته شده
بین دو جلسه، کاربر مستقل (با کمک Kimi.ai) یک idempotency guard اضافی به `SendWithdrawalApprovedNotification::handle()` اضافه کرد (چک اینکه آیا notification از قبل ارسال شده)، ولی این guard مستقیم به `DB::table('notifications')` کوئری می‌زد — در حالی که جدول واقعی این پروژه `user_notifications` هست (به `App\Models\UserNotification` نگاه کن؛ migration: `2024_01_28_173700_create_user_notifications_table`). نتیجه: `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'beauty_salon.notifications' doesn't exist` — هر بار فرآیند تایید برداشت اجرا می‌شد، `queue:work` این خطا رو می‌گرفت و بعد از رسیدن به سقف تلاش، job رو کامل fail می‌کرد. این فایل توی زیپ اول این جلسه اصلاً وجود نداشت (فقط ۲۵ خط بدون هیچ query بود) — یعنی فایل local کاربر جلوتر از زیپی بود که اول آپلود شده بود؛ فقط با ضمیمه‌کردن `laravel.log` جدید کشف شد. فیکس: همون منطق idempotency، ولی از طریق رابطه‌ی Eloquent `$specialist->notifications()` (که به `user_notifications` اشاره می‌کنه)، نه raw table name.

**فایل‌های این نشست:**
- `app/Providers/Event/EventServiceProvider.php`
- `config/app.php`
- `bootstrap/providers.php`
- `app/Providers/RouteServiceProvider.php`
- `app/Services/Admin/Wallet/WalletAdminService.php`
- `resources/views/admin/wallet/withdrawal-show.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/specialist/notifications.blade.php`
- `app/Listeners/Withdrawal/Approved/SendWithdrawalApprovedNotification.php`

---

## ⭐ باگ‌فیکس‌های کلیدی (تجمیعی، کل پروژه)

### Booking.php (Model)
- `'booking_time' => 'datetime'` cast
- `canBeRescheduled()`: status باید `pending`/`confirmed` باشه + `Carbon::parse($this->booking_time)->gt(now()->addHours(24))`

### BookingController.php
- `showReschedule()` + `updateReschedule()`: JSON response برای AJAX
- `index()`: فیلتر `date` (Jalali→Gregorian روزانه)

### SpecialistProfileController.php
- `$todayPersian`, `$monthRevenue` اضافه شدن
- `markAllNotificationsAsRead()` + `loyalty()` متد جدید
- `latestNotifications()`: لینک‌دهی صحیح بر اساس نوع اعلان

### SpecialistWalletController / UserWalletController
- فیلتر تاریخ Jalali→Gregorian + `withQueryString()` روی paginate
- `UserWalletController::showTransaction()` متد جدید

### PaymentController.php
- حذف فراخوانی‌های تکراری `addIncome()`/`earnPointsFromBooking()` — فقط BookingObserver منبع واحد
- **رفع باگ صفحه سفید پرداخت** (مهم): در حالت تخفیف ۱۰۰٪ (`prepayment_amount <= 0`)، نتیجه‌ی `DB::transaction()` مستقیم return می‌شد بدون اینکه closure مقداری برگردونه — یعنی `null` به‌جای Response، صفحه‌ی سفید (200 خالی)؛ refresh بعدی خطای 405 می‌داد. **فیکس**: بعد از commit، صریحاً `redirect()->route('bookings.success', ...)` فراخوانی می‌شه.
  > ✅ **به‌روزرسانی**: نگرانی این هشدار در فاز `R-DB-Transaction` بررسی و برطرف شد — جستجوی سراسری روی هر ۳۶ مورد `DB::transaction(` در `app/` نشون داد این الگوی خطرناک جای دیگه‌ای تکرار نشده بود.

### Providers
- حذف ثبت تکراری BookingObserver از EventServiceProvider و BookingServiceProvider — فقط در AppServiceProvider باقی موند

### رفع کامل باگ payment_ref → payment_reference (سراسری، خواندن و نوشتن)
- **خواندن**: `bookings/success.blade.php`, `bookings/show.blade.php`, `payment/callback.blade.php`, `payment/result.blade.php`, `admin/bookings/show.blade.php`, `CustomerBookingNotification.php`
- **نوشتن**: `PaymentController.php` (process، processWithWallet، callback)، `BookingController.php`

### ⭐ باگ بحرانی: `@stack('styles')` در layouts/app.blade.php وجود نداشت
ریشه‌ی واقعی تمام گزارش‌های «تقویم به‌هم‌ریخته». یک خط `@stack('styles')` قبل از `</head>` اضافه شد.

### باگ‌های گزارشات ادمین
- React SPA کامل با Blade + Chart.js جایگزین شد
- نمودار متخصصین خالی: پارس اشتباه `{success, data: {specialists}}` در React قدیمی — حالا server-side در Blade درست خونده می‌شه
- نمودار گردش مالی ثابت (هاردکد مرداد/شهریور): query از `now()->startOfYear()` به بازه‌ی واقعی انتخابی کاربر تغییر کرد
- خروجی PDF خطای `View not found`: نام view از `admin.reports.pdf` به `admin.reports.pdf-report` تصحیح شد
- خروجی PDF/Excel خطای type declaration: `Response|BinaryFileResponse|JsonResponse` با `use Illuminate\Http\Response` در namespace ادمین تداخل داشت (به `App\Http\Controllers\Admin\Response` resolve می‌شد) — signature حذف شد
- حروف فارسی در PDF جدا از هم بودن: `autoScriptToLang`+`autoLangToFont` فعال نبود + فونت‌ها بدون `format('truetype')`/`unicode-bidi` لود می‌شدن — هر دو فیکس شد + `<div>`/float به `<table>` تبدیل شد (mPDF نسبت به float/inline-block غیرقابل‌اعتماده)
- برچسب `j M` انگلیسی در نمودار: `jalali_date()` پروژه فرمت `j M` رو پشتیبانی نمی‌کنه — جایگزین با `gregorian_to_jalali()` + آرایه‌ی نام ماه فارسی دستی
- هدر صورتی (`#db2777`) PDF حذف و letterhead خنثی/رسمی جایگزین شد
- فیلتر تاریخ گزارشات: قبلاً پیش‌فرض روی بازه‌ی ثابت غیرقابل‌خالی‌کردن بود؛ الان بدون تاریخ انتخابی، پیام «بازه زمانی انتخاب نشده» نشون داده می‌شه

### باگ پروفایل ناقص متخصص جدید (ریشه‌یابی کامل)
**ریشه:** لینک بین کاربر و متخصص فقط از `exact match` ستون `phone` صورت می‌گیره (`hasOne(Specialist::class, 'phone', 'phone')` در `User.php`). اگه شماره‌ای که ادمین وارد می‌کنه با شماره‌ی واقعی حساب متخصص حتی یک کاراکتر فرق داشته باشه، پیوند برقرار نمی‌شه.
**مشکل ثانویه:** `AdminSpecialistController::store()` ستون `user_id` رو به اشتباه به آیدی خودِ ادمین ست می‌کرد.
**فیکس:**
- `normalizePhone()` در `AdminSpecialistController` اضافه شد (حذف کاراکتر غیرعددی + تبدیل `+98`/`0098` به `0`) قبل از ذخیره در `store()`/`update()`
- `user_id` حالا با `User::where('phone', ...)->first()?->id` resolve می‌شه (یا `null`، نه آیدی ادمین)
- نشانگر بصری در `admin/specialists/show.blade.php` اضافه شد
- `RegisterController` و `SpecialistProfileController` نیازی به تغییر نداشتن (regex سخت‌گیرانه `^09[0-9]{9}$` از قبل)

> ⚠️ **یادآوری مهم**: فیکس فقط رکوردهای جدید/ویرایش‌شده رو درست می‌کنه. برای متخصصین قدیمی احتمالاً مشکل‌دار، این کوئری روی دیتابیس باید زده بشه:
> ```sql
> SELECT s.id, s.name, s.phone FROM specialists s
> LEFT JOIN users u ON u.phone = s.phone WHERE u.id IS NULL;
> ```

### Specialist Dashboard (`dashboard.blade.php`)
- `Str::limit($review->review, 100)` → `\Illuminate\Support\Str::limit(...)` — رفع فتال ارور `Class "Str" not found` هنگام لاگین متخصص. ریشه: `Str` facade بدون `use Illuminate\Support\Str;` یا namespace کامل استفاده شده بود.

### withdrawal-show.blade.php (Admin Wallet)
- **فرم approve فاقد فیلدهای required**: `ApproveWithdrawalRequest` فیلد `payment_reference` را `required` می‌خواست ولی Blade هیچ input نداشت → validation fail بی‌صدا → redirect back بدون پیام خطا. فیکس: فیلد `payment_reference` (required) + `admin_note` (optional) با `@error` و `old()`.
- **فرم reject فاقد `@error` و `old()`**: textarea `rejection_reason` بدون نمایش خطای validation و بدون retention مقدار. فیکس: `@error('rejection_reason')` + `old('rejection_reason')`.
- **باگ منطقی if/elseif**: `processing` در هر دو شاخه → بخش auto-payout unreachable. فیکس: سه حالت مجزا `@if('pending')` / `@elseif('processing')` / `@else`.
- **دکمه reject `type="button"`**: با `data-confirm-delete` کار می‌کرد ولی اگه JS interceptor خراب باشه، فرم submit نمی‌شد. فیکس: `type="submit"` (SweetAlert2 هنوز interceptor دارد).

---

### ✅ R-Jobs — انتقال عملیات سنگین به Jobs

هر ۴ بخش مستندشده در پلن اولیه (`SendBookingReminderJob`، `SendBulkNotificationJob`، `ProcessWithdrawalJob`، `GeneratePdfReportJob`) تکمیل شد.

**۱) `SendBookingReminderJob`** — جایگزین حلقه‌ی synchronous در دستور `bookings:send-reminders`. قبلاً کل ارسال یادآوری (مشتری+متخصص، برای همه‌ی نوبت‌های فردا) در یک حلقه‌ی sync و بدون صف انجام می‌شد؛ اگه Kavenegar کند/تایم‌اوت می‌داد (همون کلاس مشکلی که در فاز رفع‌مستقل Telescope باعث تایم‌اوت ۳۰+ ثانیه‌ای لاگین شده بود)، کل دستور معطل می‌موند و خطای یک نوبت مانع رسیدن یادآوری بقیه می‌شد. حالا `reminder_sent` بلافاصله (sync) توسط خود دستور ست می‌شه و فقط ارسال SMS هر نوبت یک Job جدا در صف می‌شه (همون الگوی `SendLoginVerificationCodeJob`).

⚠️ **به‌روزرسانی (۲۰۲۶-۰۷-۲۵)**: منطق زمان‌بندی این بخش (هم دستور، هم شیدولش) دوباره تغییر کرد — به بخش «رفع مستقل: یادآوری پیامکی نوبت واقعاً ۱ ساعت قبل از هر نوبت» پایین‌تر نگاه کن.

**۲) `SendBulkNotificationJob`** — Job جنریک آماده برای ارسال دسته‌جمعی هر نوتیفیکیشن به هر مدلی (`SendBulkNotificationJob::dispatchForModel(notificationClass, notificationArgs, notifiableModel, notifiableIds, chunkSize)`)، با chunk خودکار (پیش‌فرض ۲۰۰ تایی) تا برای لیست‌های بزرگ یک Job سنگین تک نساخته بشه. ⚠️ فعلاً **هیچ فیچر فعلی پروژه از این Job استفاده نمی‌کنه** — صرفاً زیرساخت آماده برای فیچرهای آینده‌ایه که نیاز به اطلاع‌رسانی دسته‌جمعی دارن (مثلاً یک اطلاعیه‌ی عمومی به همه‌ی کاربران/متخصصین که فعلاً در پروژه پیاده نشده).

**۳) `ProcessWithdrawalJob` (جایگزین mock بودن `autoPayout` — رفع «هشدار حیاتی» بالای این سند):**
- تصمیم بیزنسی گرفته شد: API واقعی Payout زرین‌پال وصل بشه (نه حذف دکمه) + sandbox برای تست در دسترس بمونه.
- `App\Services\Payment\ZarinpalPayoutService` (جدید) — اتصال واقعی HTTP به `/payout.json` با `Authorization: Bearer` (کلید API مجزا از پرداخت عادی، چون Payout در زرین‌پال نیاز به فعال‌سازی/کلید جدا داره)، سوییچ خودکار sandbox/production (هم‌الگو با `PaymentService` موجود).
- `config/services.php` — بلوک `zarinpal.payout` جدا: `ZARINPAL_PAYOUT_API_KEY` (env جدید، باید در `.env` ست بشه)، `ZARINPAL_PAYOUT_SANDBOX` (پیش‌فرضش از `ZARINPAL_SANDBOX` می‌خونه ولی مستقل override می‌شه)، base URL های sandbox/production جدا.
- `App\Jobs\ProcessWithdrawalJob` (جدید) — چون تماس HTTP به Payout API می‌تونه کند/تایم‌اوت‌دار باشه، این تماس async شد: کلیک ادمین روی «تسویه‌ی آنلاین خودکار» فقط وضعیت درخواست رو به `processing` می‌بره و این Job رو صف می‌کنه؛ نتیجه‌ی واقعی (`completed` با کد پیگیری واقعی، یا `failed` با پیام خطای واقعی از زرین‌پال) با اجرای Job در صف میاد.
- **⭐ باگ واقعی تازه کشف‌شده در `withdrawal-show.blade.php`**: با وجود اینکه در بخش «باگ‌های واقعی کشف/رفع‌شده» فاز R-AdminWallet مستند شده بود که شرط `if/elseif` به سه حالت مجزا (`@if('pending')`/`@elseif('processing')`/`@else`) فیکس شده، در کدبیس واقعی این فیکس اعمال نشده بود — شرط همچنان `@if(in_array($status, ['pending','processing']))` بود که باعث می‌شد شاخه‌ی `@elseif($status === 'processing')` هیچ‌وقت اجرا نشه (چون `processing` از قبل توسط شرط اول قاپیده می‌شد). در نتیجه وضعیت `processing` که از قبل در schema/UI پیش‌بینی شده بود (رنگ آبی، شاخه‌ی جدا)، از روز اول در کل کدبیس **هیچ‌جا واقعاً ست نمی‌شد** (تأیید با `grep -rn "'processing'"`) — یعنی این دقیقاً همون الگوی مستندشده‌ی «مستند شدن یک فیکس در پرامپت تضمین نمی‌کنه واقعاً در کدبیس commit شده» بود، این‌بار برای یک فیکس Blade نه یک کلاس. با راه‌اندازی این Job برای اولین بار واقعاً استفاده شد؛ باگ Blade هم فیکس شد (شرط اول به `=== 'pending'` تغییر کرد). صفحه الان: در `pending` هم فرم تایید دستی هم دکمه‌ی «تسویه‌ی آنلاین خودکار» رو نشون می‌ده؛ در `processing` فقط پیام وضعیت (بدون دکمه‌ی تکراری قابل‌کلیک).
- بعد از تسویه‌ی موفق، رویداد `WithdrawalApproved` واقعاً dispatch می‌شه — نسخه‌ی mock قبلی این event رو اصلاً صدا نمی‌زد، یعنی متخصص بعد از auto-payout هیچ‌وقت نوتیف/پیامک نمی‌گرفت؛ این گپ هم رایگان از دل همین کار فیکس شد.
- ⚠️ **پیش‌نیاز عملیاتی**: `ZARINPAL_PAYOUT_API_KEY` باید در `.env` ست بشه؛ `queue:work` باید باز باشه (وگرنه Job فقط توی جدول `jobs` می‌مونه و وضعیت روی `processing` معلق باقی می‌مونه) — دقیقاً همون پیش‌نیاز مستندشده‌ی `wallet:settle-pending`/SMS لاگین.

**۴) `GeneratePdfReportJob` (خروجی async گزارشات ادمین):**
- قبلاً `AdminReportExportController::export()` فایل PDF/Excel رو مستقیم و synchronous داخل همون request می‌ساخت و دانلود می‌شد. تبدیلش به async ابتدا **آگاهانه به تعویق افتاد** (چون برخلاف SMS لاگین، هیچ باگ/کندی واقعی گزارش‌شده‌ای پشتش نبود و نیازمند UX جدید بود، نه صرف جابه‌جایی کد) و طراحی کامل مستند شد؛ در ادامه‌ی همون جلسه طبق تصمیم کاربر پیاده‌سازی هم شد.
- Migration جدید: جدول `report_exports` (`admin_user_id`, `format`, `report_type`, `filters` json, `status` [pending/processing/ready/failed], `file_path`, `error_message`, `ready_at`).
- `App\Models\ReportExport` — `isDownloadable()`, accessor های `status_text`/`status_badge_color`/`report_type_text`.
- `App\Jobs\GeneratePdfReportJob` (جدید) — یک Job واحد برای هر دو فرمت (چون `AdminReportService::buildExportData()` بین PDF/Excel کاملاً مشترکه؛ فقط مرحله‌ی نهایی serialize فرق داره). فایل رو در دیسک `local` (`storage/app/private/report-exports/{id}.{ext}`) ذخیره می‌کنه؛ در خطا، `status='failed'` + `error_message` واقعی ثبت می‌شه.
- `App\Notifications\Admin\Report\Export\ReportExportReadyNotification` — نوتیف دیتابیسی به ادمین درخواست‌دهنده، هم برای موفقیت هم برای شکست.
- `AdminReportExportController::export()` بازنویسی شد: به‌جای ساخت مستقیم فایل، فقط رکورد `pending` می‌سازه و Job رو صف می‌کنه؛ متدهای جدید `index()` (لیست همه‌ی خروجی‌های درخواست‌شده توسط هر ادمینی — چون ابزار داخلی تیم کوچیکه، عمداً به «فقط خودم» محدود نشده) و `download()`.
- Route: `admin.reports.export` از GET به **POST** تغییر کرد (چون حالا یک رکورد می‌سازه، mutation محسوب می‌شه) + `admin.reports.exports.index`/`admin.reports.exports.download` جدید.
- View جدید: `admin/reports/exports/index.blade.php`.
- **⭐ نکته‌ی فنی حین پیاده‌سازی (پیشگیری‌شده، نه یک باگ که رخ داده باشه)**: دکمه‌های PDF/Excel داخل همون `<form method="GET">` فیلتر بازه‌ی زمانی صفحه‌ی گزارشاته. یک `<form method="POST">` جدا و تودرتو داخلش گذاشتن HTML معتبر نیست (nested form مرورگر رو گیج می‌کنه و معمولاً فرم بیرونی رو submit می‌کنه، نه داخلی رو). به‌جاش از `formaction`/`formmethod` روی خود دکمه‌ها استفاده شد (مقصد/متد رو per-button override می‌کنن) + `name="format"` روی دکمه (فقط مقدار دکمه‌ی واقعاً کلیک‌شده در body ارسال می‌شه) + یک `@csrf` به همون فرم GET اضافه شد چون حالا گاهی به POST هم submit می‌شه.
- **⭐ فیکس پیشگیرانه‌ی دوباره**: در Blade جدید (`exports/index.blade.php`) عمداً `\Illuminate\Support\Str::limit(...)` با نام کامل نوشته شد (نه `Str::limit`) — دقیقاً همون کلاس باگی که قبلاً در `specialist/dashboard.blade.php` باعث فتال ارور «Class "Str" not found» شده بود (به بخش «Specialist Dashboard» پایین‌تر نگاه کن).
- `App\Console\Commands\CleanupReportExports` (جدید، `reports:cleanup-exports {--days=7}`) — رکورد/فایل‌های `ready`/`failed` قدیمی‌تر از N روز رو پاک می‌کنه؛ در `bootstrap/app.php` روزانه شیدول شد (هم‌الگو با `wallet:settle-pending`) تا فایل‌های تولیدی روی دیسک تجمیع نشن.
- ⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` (جدول جدید) + `queue:work` باز باشه.

---

## ⭐ رفع مستقل (بعد از R-Jobs): باگ‌های صفحه‌ی گزارشات ادمین و نوتیفیکیشن هدر

**این کار مستقل از فازبندی رفکتوره — بعد از تکمیل R-Jobs، حین تست واقعی صفحه‌ی گزارشات (و بعداً با لاگ/Telescope واقعی) کشف/رفع شدن.**

### ۱) دکمه‌های «امروز/هفته/ماه» فقط دسته‌بندی نمودار رو عوض می‌کردن، نه بازه‌ی زمانی رو
`AdminReportsController::index()` بدون `start_date`/`end_date` واقعی همیشه صفحه‌ی خالی نشون می‌داد؛ اما دکمه‌های «روزانه/هفتگی/ماهانه» در JS فقط hidden field `type` (که فقط نوع دسته‌بندی نمودار درآمده، نه بازه‌ی زمانی) رو عوض می‌کردن و فرم رو submit هم نمی‌کردن — پس کلیک روی این دکمه‌ها (بدون انتخاب دستی تاریخ) عملاً هیچ اثری نداشت.

**فیکس:** `setType()` بازنویسی شد تا واقعاً یک بازه‌ی زمانی هم تعیین و فرم رو خودش `submit()` کنه:
- **امروز** → `start=end=امروز`
- **هفته** → ۷ روز اخیر (شامل امروز)
- **ماه** → ⭐ عمداً از **ابتدای ماه شمسی قبل** تا امروز، نه ابتدای ماه جاری — چون اگه فقط ماه جاری در نظر گرفته می‌شد، در روزهای اول هر ماه شمسی (که دقیقاً همون روزی بود که این فیچر تست شد: ۱ مرداد ۱۴۰۵) بازه به یک روز محدود می‌شد و گزارش تقریباً خالی می‌موند. توابع تبدیل جلالی↔میلادی (که قبلاً فقط داخل closure تقویم jcal تعریف شده بودن) به سطح بالای اسکریپت منتقل شدن تا هم دکمه‌های بازه‌ی سریع هم تقویم بتونن ازشون استفاده کنن؛ مرز سال (فروردین → برگشت به اسفند سال قبل) هم درست تست و تایید شد.

### ۲) پیش‌فرض «امروز» موقع بارگذاری اول صفحه
قبلاً بدون انتخاب دستی بازه، صفحه فقط پیام «بازه زمانی انتخاب نشده» نشون می‌داد. `AdminReportsController::index()` تغییر کرد تا بدون `start_date`/`end_date` در URL، به‌صورت پیش‌فرض گزارش «امروز» رو نشون بده (هماهنگ با دکمه‌ی «روزانه» که از قبل هم پیش‌فرض فعال نشون داده می‌شد)؛ بلوک Blade مربوط به حالت خالی (که دیگه هیچ‌وقت اجرا نمی‌شه) حذف شد.

فیچر خروجی async گزارش (بخش «۴» بالا) بعد از استقرار واقعی، در عمل چند باگ جدی داشت که فقط با فرستادن لاگ/Telescope واقعی کشف شدن (نه از روی خواندن کد به‌تنهایی).

### ۳) 🔴 باگ بحرانی: ستون `payment_method` روی جدول `bookings` اصلاً وجود نداره
`AdminReportService::getFinancialSummary()` (خطوط `wallet_payments`/`gateway_payments`) و `paymentBreakdown()` مستقیم `->where('payment_method', 'wallet')` می‌زدن. طبق migration واقعی جدول `bookings`، همچین ستونی هیچ‌وقت وجود نداشته — روش پرداخت واقعی داخل ستون JSON `payment_details` با کلید `method` ذخیره می‌شه (مقادیر: `wallet`, `gateway`, `wallet_gateway`, `full_discount` — طبق `PaymentController.php`). نتیجه: **هر** export گزارش (daily/weekly/monthly، هر دو فرمت) با `SQLSTATE[42S22]: Column not found: 1054` fail می‌شد و وضعیت `report_exports` همیشه `failed` می‌موند.
**فیکس:** هر دو جا به `->where('payment_details->method', ...)` (JSON path) تغییر کرد.
**باگ همراه، همون ریشه:** مدل `Booking` اصلاً `payment_details`/`refund_details` رو `cast` به `array` نکرده بود (برخلاف `ReportExport::$casts['filters']` که درسته) — اضافه شد. `admin/bookings/show.blade.php` هم به `$booking->payment_method` (که همیشه null بود و همیشه فال‌بک «آنلاین» نشون می‌داد) ارجاع می‌داد — به نمایش واقعی از `payment_details->method` (با map فارسی: کیف پول/درگاه بانکی/ترکیبی/تخفیف کامل) تغییر کرد.
**باگ همراه دیگه (همون الگو، جای سوم):** `AdminPaymentController::store()` (ثبت دستی پرداخت توسط ادمین) هم تلاش می‌کرد همین ستون ناموجود `payment_method` رو مستقیم `update()` کنه — چون در `$fillable` مدل `Booking` نیست، بی‌صدا دور ریخته می‌شد (همون الگوی «فرم مقدار می‌گیره، بی‌صدا دورش می‌ریزه»). فیکس شد تا داخل همون `payment_details->method` استاندارد ذخیره بشه.

### ۴) 🔴 باگ بحرانی: خروجی اکسل کاملاً خالی (برخلاف PDF که کار می‌کرد)
`App\Exports\ReportsExport::map()` انتظار داشت `$row` یک **آبجکت** با پراپرتی‌هایی مثل `$row->date`, `$row->total_bookings`, `$row->week_start`, `$row->year`, `$row->average_booking_value` باشه — این دقیقاً همون کلاس باگ مستندشده در بخش «نکات فنی مهم Blade» (کلید/ساختار داده‌ی مصرف‌کننده با تولیدکننده‌ی واقعی هم‌تراز نیست)، این‌بار بین `AdminReportService` و `Exports/ReportsExport`. متدهای واقعی (`dailyRevenue`/`weeklyRevenue`/`monthlyRevenue`) یک **آرایه‌ی انجمنی** با کلیدهای `label`, `date` (فقط daily), `revenue`, `bookings` برمی‌گردونن — نه اون چیزی که `map()` می‌خوند. نتیجه: خوندن پراپرتی از آرایه (warning خاموش، نه فتال) → همه‌ی سلول‌ها خالی/صفر.
**فیکس:** `ReportsExport` کامل بازنویسی شد؛ `map()` حالا `(array) $row` می‌کنه و کلیدهای واقعی رو می‌خونه + یک ستون «میانگین نوبت» محاسبه‌شده اضافه شد.

### ۵) 🟡 باگ دوقلوی همون مشکل، مخفی‌تر: در PDF ستون «تعداد نوبت» همیشه صفر بود
همین ناهماهنگی کلید (`total_bookings` به‌جای `bookings`، و `week_start`/`month` که در ساختار فعلی اصلاً وجود ندارن) در `admin/reports/pdf-report.blade.php` هم بود — ولی چون ستون‌های تاریخ و درآمد درست نمایش داده می‌شدن (کلید `date`/`revenue` واقعاً موجودن)، کاربر متوجه نشده بود که ستون تعداد نوبت همیشه ۰ بود و برای گزارش‌های هفتگی/ماهانه اصلاً ستون دوره نمایش داده نمی‌شد.
**فیکس:** جدول به `$row['bookings']` تغییر کرد + یک ستون «دوره» عمومی (از `$row['label']`) جایگزین منطق شکسته‌ی `week_start`/`month` شد؛ `GeneratePdfReportJob` هم `type` رو به view پاس می‌ده (قبلاً پاس داده نمی‌شد).

### ۶) 🔴 باگ بحرانی: کلیک روی نوتیفیکیشن‌های دیتابیسی به آدرس اشتباه هدایت می‌کنه
`.env` پروژه `APP_URL=http://localhost` داره ولی سرور واقعی روی `http://127.0.0.1:8000` (طبق Telescope) اجرا می‌شه. `ReportExportReadyNotification::toArray()` (و چند نوتیفیکیشن دیگه) لینک رو با `route(...)` **مطلق** می‌ساختن. مشکل اینه که این کد داخل `GeneratePdfReportJob` روی **queue worker** (یک پروسه‌ی CLI جدا، بدون HTTP request فعال) اجرا می‌شه؛ در نبود request، `route()` از `config('app.url')` به‌عنوان fallback استفاده می‌کنه — یعنی لینک ذخیره‌شده همیشه `http://localhost/...` می‌شد، درحالی‌که کاربر روی `127.0.0.1:8000` کار می‌کرد. کلیک از هم زنگوله هم از دکمه‌ی «مشاهده‌ی مورد مرتبط» توی صفحه‌ی جزئیات نوتیفیکیشن (چون هر دو از همون `data->link` استفاده می‌کنن) به هاست/پورت غلط می‌رفت.
**فیکس:** همه‌ی این نوتیفیکیشن‌ها به `route(..., [], false)` (لینک نسبی/relative) تغییر کردن تا مستقل از APP_URL/هاست/پورت کار کنن — هم در `ReportExportReadyNotification` هم در `AdminPaymentReceivedNotification`, `AdminNewWithdrawalRequestNotification`, `SpecialistRespondedNotification`, `NewReviewReceivedNotification`, `NegativeReviewNotification` (همه‌شون همین الگوی مطلق‌سازی رو داشتن).
⚠️ **محدودیت این فیکس:** نوتیفیکیشن‌های قدیمی که همین الان توی `user_notifications` هستن، لینک اشتباه رو از قبل داخل `data` (JSON استاتیک) ذخیره کردن و این تغییر روشون اثر نداره — فقط نوتیفیکیشن‌های جدید از این به بعد درستن. همچنین توصیه شد `.env` هم اصلاح بشه (`APP_URL` واقعی، نه فقط لینک نسبی) چون این mismatch روی `asset()`/لینک ایمیل و جاهای دیگه هم اثر داره.

### ۷) 🔴 باگ واقعی: بج شمارنده‌ی نوتیفیکیشن هدر بعد از خواندن از صفحه‌ی لیست/نمایش آپدیت نمی‌شد
`admin/notifications/index.blade.php` و `admin/notifications/show.blade.php` بعد از هر عملیات (علامت‌گذاری خوانده‌شده، toggle، حذف) این خط رو صدا می‌زدن:
```js
if (typeof window.refreshNotificationCount === 'function') window.refreshNotificationCount();
```
ولی **هیچ‌جای پروژه چنین تابعی تعریف نشده بود** — تابع واقعی در `layouts/admin.blade.php` با نام دیگه‌ای (`fetchUnreadCount`) تعریف شده بود، بدون اینکه به `window` اکسپوز بشه. یعنی بج شمارنده‌ی بالای هدر بعد از خواندن نوتیفیکیشن از این دو صفحه هیچ‌وقت بلافاصله آپدیت نمی‌شد (فقط با رفرش کامل صفحه یا تایمر ۶۰ ثانیه‌ای درست می‌شد). **فیکس:** `window.refreshNotificationCount = fetchUnreadCount;` در `layouts/admin.blade.php` اضافه شد.

**باگ همراه (نیمه‌کاره‌ی مشابه):** همون دو صفحه یک `localStorage.setItem('notification_updated', ...)` هم می‌زدن (ظاهراً برای هماهنگی بج بین چند تب باز مرورگر) ولی هیچ `addEventListener('storage', ...)` ای برای خوندنش وجود نداشت — این نیمه از فیچر هم کامل شد.

**🟡 نکته‌ی محیطی همراه:** فایل `admin/notifications/Show.blade.php` با حرف اول بزرگ ذخیره شده بود، در حالی که کنترلر `view('admin.notifications.show')` (حروف کوچک) صدا می‌زنه. روی Windows/XAMPP بی‌مشکل کار می‌کنه، ولی روی Linux/production (طبق cron مستندشده‌ی این پروژه) باعث فتال ارور «View not found» می‌شد — دقیقاً همون الگوی `Walletadminservice.php`/`Checkpasswordstrengthrequest.php` که در R-Events کشف شده بود. فایل به `show.blade.php` تغییر نام داد.

⚠️ **یادآوری مهم از الگوی مستندشده‌ی این پروژه**: بند ۷ (بج نوتیفیکیشن + rename فایل) یک‌بار در یک نشست قبلی فیکس شده بود، ولی در زیپ بعدی که کاربر فرستاد این فیکس دیده نشد (نه در `layouts/admin.blade.php` نه نام فایل) — یعنی دقیقاً همون الگوی «فیکس مستند شده ولی روی فایل local کاربر اعمال/کامیت نشده» که چندبار دیگه هم در این پروژه اتفاق افتاده. در همین بررسی دوباره اعمال شد؛ **لطفاً قبل از کامیت نهایی چک شود که این دو فایل واقعاً در ریپو ذخیره شدن.**

**درس کلی (تکرار همون الگوی مستندشده در بخش ۵ «نکات فنی Blade» بالا، این‌بار برای Export/Notification):** هر جا یک لایه (Export class، Blade template، Notification) از ساختار داده/محیط اجرای یک لایه‌ی دیگه (سرویس گزارش، queue worker) فرضیات نانوشته می‌کنه، این فرضیات باید صریحاً با گرفتن نمونه‌ی واقعی داده/محیط تأیید بشن، نه با خوندن امضای متد.

**فایل‌های این مجموعه رفع مستقل:**
- `resources/views/admin/reports/index.blade.php`
- `app/Http/Controllers/Admin/Report/AdminReportsController.php`
- `app/Services/Admin/Report/AdminReportService.php`
- `app/Http/Controllers/Admin/Payment/AdminPaymentController.php`
- `app/Models/Booking.php`
- `resources/views/admin/bookings/show.blade.php`
- `app/Exports/ReportsExport.php`
- `resources/views/admin/reports/pdf-report.blade.php`
- `app/Jobs/GeneratePdfReportJob.php`
- `app/Notifications/Admin/Report/Export/ReportExportReadyNotification.php`, `Admin/Payment/AdminPaymentReceivedNotification.php`, `Admin/Withdrawal/Request/AdminNewWithdrawalRequestNotification.php`, `Review/SpecialistRespondedNotification.php`, `Review/NewReviewReceivedNotification.php`, `Review/NegativeReviewNotification.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/notifications/Show.blade.php` → `show.blade.php` (rename)

---

## ⭐ رفع مستقل (۲۰۲۶-۰۷-۲۵): باگ بحرانی `specialists.user_id NOT NULL`

**سناریوی کشف:** ادمین از پنل مستقیم یک متخصص جدید (نام/شماره/خدمات/کمیسیون) ثبت کرد، بدون اینکه شخص مذکور از قبل حساب کاربری (`User`) با همون شماره تلفن داشته باشه. عملیات با خطای زیر fail شد (هم در تلسکوپ هم در `laravel.log`):
```
SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'user_id' cannot be null
```

**ریشه:** `AdminSpecialistService::create()`/`update()` طبق تصمیم معماری قبلی (باگ‌فیکس «پروفایل ناقص متخصص جدید»)، عمداً `user_id` رو با:
```php
$matchedUser = User::where('phone', $validated['phone'])->first();
$validated['user_id'] = $matchedUser?->id;
```
resolve می‌کنن — یعنی وقتی هنوز کاربری با اون شماره ثبت‌نام نکرده، `user_id` باید `null` بمونه تا بعداً با ثبت‌نام خود شخص (بر اساس match شماره تلفن، `User::specialist()` hasOne) لینک خودکار برقرار بشه. این منطق سرویس **کاملاً درست** بود.

مشکل واقعی در schema بود: migration اصلی جدول `specialists` ستون `user_id` رو با `->constrained()->cascadeOnDelete()` (بدون `nullable()`) تعریف کرده بود — یعنی دیتابیس اجازه‌ی `null` رو نمی‌داد، دقیقاً برخلاف چیزی که منطق بیزنسی سرویس نیاز داشت. این یک ناهماهنگی بین کد (که درست بود) و schema (که هیچ‌وقت برای این رفتار عمداً به‌روزرسانی نشده بود) بود، نه یک باگ منطقی جدید.

**فیکس:** migration جدید:
```php
DB::statement('ALTER TABLE `specialists` MODIFY `user_id` BIGINT UNSIGNED NULL');
```
(نسخه‌ی جایگزین با `Schema::table('specialists', fn($table) => $table->foreignId('user_id')->nullable()->change());` هم قابل استفاده‌ست، مشروط به `composer require doctrine/dbal --dev`؛ پروژه در نهایت از همین نسخه‌ی dbal استفاده کرد.)

⚠️ **این migration فقط برای سناریوی خاص لازمه:** اگه روال کاری تیم همیشه «اول کاربر ثبت‌نام می‌کنه، بعد ادمین نقش متخصص بهش می‌ده» باشه، هیچ‌وقت `user_id` نال نمی‌شه و این باگ عملاً رخ نمی‌ده. اما چون سرویس از قبل عمداً برای پشتیبانی از «ادمین زودتر از ثبت‌نام خود شخص متخصص رو با برنامه‌کاری/خدمات می‌سازه» طراحی شده، این migration برای فعال کردن واقعی همون قابلیت لازمه. **کاربر خودش این دو سناریو رو دستی تست کرد**: (۱) ساخت متخصص با شماره‌ی بدون کاربر → خطای Integrity constraint (تأیید باگ)؛ (۲) ثبت‌نام کاربر → دادن نقش متخصص → افزودن به لیست متخصصین با برنامه‌کاری/خدمات → موفق بدون خطا (چون این‌بار `User::where('phone', ...)->first()` نتیجه پیدا کرد). این دو تست رفتار مستندشده‌ی بالا رو کامل تأیید کرد.

⚠️ **به‌روزرسانی نسخه‌ی migration (همون جلسه)**: ابتدا نسخه‌ای با SQL خام (`DB::statement('ALTER TABLE ... MODIFY ...')`) پیشنهاد و توسط کاربر اجرا (`php artisan migrate`) شد. بعداً کاربر ترجیح داد از الگوی استاندارد لاراولی (`Schema::table(...)->change()`) استفاده کنه؛ چون لاراول migration ها رو بر اساس **نام فایل** (نه محتوا) در جدول `migrations` ردیابی می‌کنه، جایگزین کردن محتوای همون فایل (با همون نام) با نسخه‌ی `doctrine/dbal`-محور، **بدون نیاز به اجرای دوباره‌ی migrate و بدون ریسک** برای دیتابیس لوکال بود — چون ستون از قبل `nullable` شده بود. برای environmentهای تازه (production/تیم/`migrate:fresh`)، نسخه‌ی dbal به‌درستی برای اولین‌بار اجرا می‌شه (به شرط نصب بودن `doctrine/dbal`).

**🟡 یافته‌ی جانبی (کد مرده احتمالی)**: در حین بررسی این باگ مشخص شد یک نسخه‌ی تکراری/باقی‌مونده از `AdminSpecialistService.php` روی مسیر غیرمعمول `resources/app/Services/Admin/Specialist/AdminSpecialistService.php` وجود داره (خارج از autoload استاندارد لاراول، پس بی‌اثره) — احتمالاً باقی‌مانده‌ی یک کپی/پیست اشتباه. ✅ **بررسی شد (۲۰۲۶-۰۸-۰۲)**: با `git log --all` تأیید شد این مسیر هیچ‌وقت در گیت commit نشده و پوشه‌ی `resources/app` اصلاً در ریپو وجود نداره — فقط یک باقی‌مانده‌ی محلی روی دیسک کاربر بوده، نه چیزی که گیت ردیابی کنه. نیازی به پچ نبود.

**فایل‌های این نشست:**
- `database/migrations/2026_07_25_000000_make_user_id_nullable_on_specialists_table.php` (جدید)
- `composer.json` / `composer.lock` (افزودن `doctrine/dbal` به‌عنوان dev dependency)

---

## ⭐ فیچر جدید (۲۰۲۶-۰۷-۲۵): مرتب‌سازی هوشمند لیست «نوبت‌های من» بر اساس اولویت وضعیت

**درخواست کاربر:** در صفحه‌ی `/bookings` (نه داشبورد)، نوبت‌ها به‌جای مرتب‌سازی صرف بر اساس زمان، بر اساس اولویت وضعیت هم مرتب بشن — نوبت‌های تایید‌شده/پرداخت‌شده بالاتر از در‌انتظار، و در‌انتظار بالاتر از لغوشده؛ و داخل هر گروه، جدیدترین بالاتر.

**⭐ توضیح رفتار قبلی/فعلی داشبورد (`/dashboard`) که باعث این سوال شد:** بخش «نوبت‌های اخیر» داشبورد (`DashboardController::index()`) **هیچ منطق اولویت‌بندی بر اساس وضعیت نداره و نداشته** — فقط:
```php
Booking::where('user_id', auth()->id())->with(['service','specialist'])->latest()->take(5)->get();
```
`->latest()` یعنی `orderBy('created_at', 'desc')` — یعنی صرفاً «۵ نوبت آخری که ثبت شدن»، فارغ از وضعیت. این بخش داشبورد دست‌نخورده موند (تغییری در این فاز نگرفت)؛ توضیحش صرفاً برای رفع یک برداشت اشتباه بود.

**فیکس/فیچر واقعی، فقط روی `BookingController::index()` (مسیر `/bookings`):**
```php
$query = Booking::with(['service', 'specialist'])
    ->where('user_id', $user->id)
    ->orderByRaw("
        CASE `status`
            WHEN 'confirmed' THEN 1
            WHEN 'completed' THEN 1
            WHEN 'pending' THEN 2
            WHEN 'pending_payment' THEN 2
            WHEN 'cancelled' THEN 3
            ELSE 4
        END
    ")
    ->orderBy('booking_time', 'desc');
```
- گروه ۱ (بالاترین اولویت): `confirmed`, `completed`
- گروه ۲: `pending`, `pending_payment`
- گروه ۳ (پایین‌ترین): `cancelled`
- داخل هر گروه: `booking_time` نزولی (زمان واقعی نوبت، نه زمان ثبت رکورد) — یعنی نزدیک‌ترین/جدیدترین نوبت هر گروه بالاتره.

فیلترهای موجود (`status`, `date`) دست‌نخورده موندن و همچنان کار می‌کنن؛ وقتی کاربر یک وضعیت خاص رو فیلتر می‌کنه، `CASE` بی‌اثر می‌مونه (چون نتیجه یک‌دسته).

**فایل‌های این نشست:**
- `app/Http/Controllers/User/BookingController.php` (`index()`)

---

## ⭐ رفع مستقل (۲۰۲۶-۰۷-۲۵): یادآوری پیامکی نوبت واقعاً ~۱ ساعت قبل از هر نوبت (نه یک‌بار در روز)

**سناریوی کشف:** کاربر مشاهده کرد نوبت‌های ساعت ۱۰:۳۰ و ۱۱:۳۰ صبح هیچ یادآوری پیامکی‌ای در ترمینال `queue:work` نگرفتن.

**ریشه (دو دلیل هم‌زمان):**
1. `bootstrap/app.php` دستور `bookings:send-reminders` رو `->dailyAt('18:00')->timezone('Asia/Tehran')` شیدول کرده بود — یعنی این دستور فقط **یک‌بار در روز، ساعت ۱۸** اجرا می‌شد و همه‌ی نوبت‌های ۲۴ ساعت آینده رو می‌گرفت. برای نوبت‌های صبح همون روز، تا ساعت ۱۸ که دستور اجرا بشه، `booking_time > now()` دیگه صدق نمی‌کرد و اون نوبت‌ها برای همیشه از قلم می‌افتادن.
2. حتی اگه ساعت هم مناسب بود، در ترمینال‌های واقعی کاربر (`queue:work`, `serve`, `npm run dev`) هیچ `schedule:work`/cron واقعی در حال اجرا نبود — یعنی حتی خود دستور شیدول‌شده هم هیچ‌وقت trigger نمی‌شد.

**فیکس (تغییر رفتار آگاهانه، طبق درخواست صریح کاربر برای «۱ ساعت قبل از هر نوبت»):**
- `app/Console/Commands/SendBookingReminders.php`: بازه‌ی انتخاب نوبت‌ها از «هر چیزی بین الان و ۲۴ ساعت دیگه» به **«بین ۵۵ تا ۶۵ دقیقه‌ی دیگه»** تغییر کرد (پنجره‌ی ۱۰ دقیقه‌ای عمداً انتخاب شده تا با فاصله‌ی اجرای schedule هم‌پوشانی داشته باشه و هیچ نوبتی از قلم نیفته/دوبار نگیره).
- `bootstrap/app.php`: زمان‌بندی از `dailyAt('18:00')` به `everyTenMinutes()` تغییر کرد.
- شرط‌های قبلی (`status='confirmed'`, `reminder_sent=false`) دست‌نخورده موندن.

⚠️ **پیش‌نیاز عملیاتی (مثل `wallet:settle-pending`/SMS لاگین):** `php artisan schedule:work` باید همیشه باز باشه (یا cron واقعی روی production) تا این هر-۱۰-دقیقه واقعاً trigger بشه.

**فایل‌های این نشست:**
- `app/Console/Commands/SendBookingReminders.php`
- `bootstrap/app.php`

---

### ✅ R-Observers — تکمیل نهایی Observerها

**۱) `BookingObserver` — بررسی شد، بدون bypass؛ تحکیم `PaymentSucceeded`**
`created()`/`updated()` درست کار می‌کنن (هیچ‌جای پروژه `payment_status` رو با query builder دستی/بدون عبور از Eloquent آپدیت نمی‌کنه). **کشف واقعی:** `event(new PaymentSucceeded($booking))` در ۳ نقطه‌ی پراکنده در `PaymentController` (process/processWithWallet/callback) صدا زده می‌شد، ولی یک مسیر پرداخت چهارم و واقعاً روت‌شده (`SecurePaymentController::verify()`، پرداخت ۲FA-محور) هیچ‌وقت این event رو دیسپچ نمی‌کرد — یعنی نوتیف «پرداخت جدید» ادمین (`SendAdminPaymentNotification`) برای این مسیر همیشه ساکت بود. **فیکس:** دیسپچ به داخل `BookingObserver::handlePaymentStatusChange()` منتقل شد (همون الگوی تحکیمی که در R-Events برای `BookingCreated` استفاده شد) — چون این متد از قبل idempotent هست (Cache guard ۱۸۰ ثانیه‌ای). ۳ فراخوانی دستی از `PaymentController` حذف شدن؛ حالا هر ۴ مسیر پرداخت (از جمله secure) خودکار پوشش داده می‌شن.

⚠️ **به‌روزرسانی (۲۰۲۶-۰۸-۰۱)**: در همون بررسی، مشخص شد این «مسیر پرداخت چهارم» (`SecurePaymentController`) در واقع کاملاً غیرقابل‌دسترسی و شکسته بود (میدل‌ور ثبت‌نشده + ویوهای گم‌شده + چند باگ دیگه) — یعنی فیکس بالا (تحکیم دیسپچ `PaymentSucceeded`) درست بود ولی روی یک فیچر کاملاً مرده اعمال شده بود، نه یک مسیر واقعاً کارکن. این مسیر در رفع مستقل ۲۰۲۶-۰۸-۰۱ کامل و واقعاً قابل‌استفاده شد — به بخش «✅ رفع مستقل: تکمیل مسیر پرداخت امن / ۲FA» پایین‌تر نگاه کن.

**۲) `DiscountCodeObserver` — رفع race condition واقعی `used_count`**
کشف شد: `BookingService::applyDiscountCode()` و `createBooking()` اعتبار کد تخفیف رو **قبل از تراکنش** چک می‌کردن و بدون لاک افزایش می‌دادن — یعنی دو درخواست هم‌زمان می‌تونستن هر دو از آخرین استفاده‌ی باقی‌مانده‌ی یک کد رد بشن (`used_count` از `max_uses` رد بشه). **فیکس:** در هر دو متد، ردیف کد تخفیف با `lockForUpdate()` داخل تراکنش قفل و دوباره validate می‌شه، درست قبل از افزایش استفاده؛ در `createBooking()` (که مقدار تخفیف از قبل خارج از تراکنش محاسبه شده) اگه race نادر رخ بده، رزرو با مبلغ محاسبه‌شده حفظ می‌شه ولی `used_count` عمداً افزایش پیدا نمی‌کنه (با یک `Log::warning`).
> نکته‌ی جانبی بررسی‌شده: `DiscountCode::isValid()` روی `max_uses` نال گارد نداره، ولی چون ستون `discount_codes.max_uses` در schema همیشه `NOT NULL`ه، این عملاً غیرقابل‌وقوعه — فیکس نشد.

**۳) `WithdrawalObserver` — تصمیم آگاهانه: ساخته نشد**
بررسی کامل شد که آیا یک Observer عمومی روی `WithdrawalRequest::updated()` (برای هر تغییر status) لازمه. **نتیجه: نه.** `WalletAdminService::approveWithdrawal()`/`rejectWithdrawal()` از قبل با `lockForUpdate()` + دیسپچ صریح event کار می‌کنن؛ اضافه کردن یک Observer عمومی که روی همون تغییر status هم فایر بشه، دقیقاً همون باگ نوتیفیکیشن/پیامک تکراری که قبلاً با هزینه‌ی زیاد فیکس شده رو بازتولید می‌کرد (چون `SendWithdrawalRejectedNotification`، برخلاف نسخه‌ی Approved، هیچ گارد idempotency نداره). **به‌جاش، تنها gap واقعی فیکس شد:** در `ProcessWithdrawalJob::handle()`، وقتی auto-payout زرین‌پال fail می‌شه (status→`failed`)، قبلاً هیچ event ای دیسپچ نمی‌شد — یعنی متخصص هیچ‌وقت نوتیف/پیامک نمی‌گرفت که auto-payoutش fail شده، برخلاف مسیر reject دستی ادمین. حالا `event(new WithdrawalRejected($withdrawalRequest, $result['message']))` در همون شاخه‌ی fail اضافه شد (از همون Event/Listener موجود، بدون کلاس جدید).

**۴) باگ جانبی کشف/رفع‌شده: `AdminPaymentController::store()`**
با وجود مستندسازی قبلی «فیکس شد» (در بخش «رفع مستقل: باگ‌های صفحه‌ی گزارشات ادمین»)، کد واقعی هنوز مستقیم `'payment_method' => $request->payment_method` رو در `$booking->update()` پاس می‌داد — ستونی که در `$fillable`/schema بوکینگ اصلاً وجود نداره، پس بی‌صدا mass-assignment دورش می‌ریخت (دقیقاً همون الگوی «فیکس مستند شده ولی commit نشده» که چندبار دیگه هم در این پروژه دیده شده). **فیکس:** به `payment_details->method` منتقل شد (با فلگ `admin_recorded => true` برای تشخیص از پرداخت‌های واقعی آنلاین) + `admin/bookings/show.blade.php` برای نمایش لیبل فارسی این متدهای جدید (`cash`/`card`/`online`/`transfer`) به‌روزرسانی شد.

**۵) رفع دسته‌بندی نادرست `gateway_payments` در گزارشات + وایر شدن در UI**
حین بررسی فیکس بالا مشخص شد `AdminReportService::getFinancialSummary()`/`paymentBreakdown()` هر چیزی با `method != 'wallet'` رو به‌عنوان «درآمد درگاه» جمع می‌زدن — یعنی هم `full_discount` (صفر پول واقعی) هم (بعد از فیکس بالا) پرداخت‌های دستی ادمین اشتباهاً زیر «گیت‌وی» حساب می‌شدن. **فیکس:** `gateway_payments` الان فقط `method` واقعی `gateway`/`wallet_gateway` رو می‌شماره؛ باکت جدید `admin_manual_payments` (بر اساس `payment_details->admin_recorded`) اضافه شد. `paymentBreakdown()` هم به بازه‌ی زمانی وابسته شد (قبلاً all-time بود، الان مثل بقیه‌ی آمار همین صفحه فقط بازه‌ی انتخابی رو حساب می‌کنه).
**وایر شدن در UI (به درخواست صریح کاربر):** این endpoint (`AdminReportRevenueController::financial()`) قبلاً هیچ‌جا در Blade/JS مصرف نمی‌شد (کاملاً محاسبه می‌شد ولی هیچ‌وقت رندر نمی‌شد). حالا `AdminReportsController::index()` این داده رو مستقیم (سرور-رندر، طبق کانوانسیون پروژه، نه fetch/JS) به `admin/reports/index.blade.php` پاس می‌ده؛ در تب «📊 درآمد»، درست بعد از نمودار گردش مالی ماهانه، سه کارت جدید («کیف پول»، «درگاه بانکی»، «ثبت دستی توسط ادمین» — هرکدوم با تعداد نوبت + درصد) اضافه شد.

**۶) همون تفکیک به خروجی‌های PDF/Excel گزارشات هم اضافه شد**
بعد از وایر شدن توی صفحه، کاربر درست پرسید که آیا خروجی‌های async گزارش (فاز R-Jobs) هم باید همین داده رو نشون بدن — جواب بله بود، چون تا این لحظه فقط صفحه‌ی وب این تفکیک رو داشت، در حالی که هر دو فرمت خروجی از همون `AdminReportService::buildExportData()` تغذیه می‌شن.
- `buildExportData()` حالا کلید `paymentBreakdown` رو هم (در کنار `summary` که خودش شامل `wallet_payments`/`gateway_payments`/`admin_manual_payments` تصحیح‌شده‌ست) برمی‌گردونه.
- **PDF** (`admin/reports/pdf-report.blade.php`): یک بخش «نحوه‌ی پرداخت نوبت‌های پرداخت‌شده» درست بعد از کارت‌های خلاصه‌ی موجود اضافه شد (همون سه دسته، با همون استایل `stats-table` موجود).
- **Excel**: قبلاً `ReportsExport` یک export تک‌شیتی بود (فقط روند درآمد). چون افزودن ستون‌های نامرتبط به همون شیت، جدول رو به‌هم می‌ریخت، به یک workbook دوشیتی تبدیل شد:
  - `App\Exports\ReportsExport` (بدون تغییر منطق، فقط `WithTitle` اضافه شد → «روند درآمد»)
  - `App\Exports\PaymentBreakdownSheet` (جدید) — شیت دوم، همون سه دسته + مبلغ تومانی هرکدوم (از `summary`)
  - `App\Exports\AdminReportExport` (جدید، `WithMultipleSheets`) — این دو شیت رو ترکیب می‌کنه؛ `GeneratePdfReportJob::generateExcel()` حالا از این کلاس استفاده می‌کنه به‌جای `ReportsExport` مستقیم.
  > نکته: در ردیف «جمع» شیت دوم، عمداً از `total_revenue` استفاده نشد (چون نوبت‌های تخفیف کامل در هیچ‌کدوم از سه دسته نیستن و جمع واقعی رو گمراه‌کننده می‌کرد) — به‌جاش «جمع سه دسته‌ی بالا» (حاصل‌جمع خود همون سه عدد) نمایش داده می‌شه.

**فایل‌های این نشست:**
- `app/Observers/Booking/BookingObserver.php`
- `app/Http/Controllers/User/PaymentController.php`
- `app/Services/Booking/BookingService.php`
- `app/Jobs/ProcessWithdrawalJob.php`
- `app/Http/Controllers/Admin/Payment/AdminPaymentController.php`
- `resources/views/admin/bookings/show.blade.php`
- `app/Services/Admin/Report/AdminReportService.php`
- `app/Http/Controllers/Admin/Report/AdminReportsController.php`
- `app/Http/Controllers/Admin/Report/AdminReportRevenueController.php`
- `resources/views/admin/reports/index.blade.php`
- `resources/views/admin/reports/pdf-report.blade.php`
- `app/Jobs/GeneratePdfReportJob.php`
- `app/Exports/ReportsExport.php`
- `app/Exports/PaymentBreakdownSheet.php` (جدید)
- `app/Exports/AdminReportExport.php` (جدید)

**۷) شیت سوم اکسل + پیوست PDF: «جزئیات خام نوبت‌ها»**
کاربر با مقایسه‌ی دستی PDF متوجه شد «کل نوبت‌ها: ۸» با «۷ ردیف در جزئیات درآمد» جور درنمیاد و هیچ‌جا مشخص نبود نوبت هشتم کدومه. ریشه: `total_bookings` همه‌ی وضعیت‌ها رو می‌شمره، `dailyRevenue`/`paymentBreakdown` فقط `payment_status='paid'` رو. **راه‌حل:** `AdminReportService::getRawBookingsForExport()` (متد جدید) هر نوبت واقعی توی بازه رو، با هر وضعیتی، برمی‌گردونه؛ شیت سوم اکسل (`App\Exports\RawBookingsSheet`، جدید) و یک پیوست landscape در PDF (`<pagebreak orientation="L" />`، فونت فشرده‌تر برای ۲۰+ ستون) این داده رو نشون می‌دن — تا هر عدد خلاصه‌شده قابل راستی‌آزمایی دستی باشه.

**۸) ۵ فیلد اضافه به «جزئیات خام»** (به درخواست صریح کاربر): ساعت واقعی نوبت (جدا از ساعت ثبت)، شماره تماس مشتری، سهم متخصص از نوبت (پس از کسر کمیسیون، با `getEffectiveCommissionRate()`)، دلیل لغو + وضعیت/مبلغ بازگشت وجه، امتیاز + نظر مشتری + کد پیگیری پرداخت + نوع تخفیف (درصدی/مبلغ ثابت — با یک کوئری batch روی `DiscountCode` برای جلوگیری از N+1).

**۹) 🔴 باگ واقعی رفع‌شده: مقادیر صفر در اکسل به‌جای «۰»، کاملاً خالی نمایش داده می‌شدن**
رفتار مستندشده‌ی خود PhpSpreadsheet/Maatwebsite Excel: `FromArray`/`FromCollection` به‌صورت پیش‌فرض `0` رو با مقایسه‌ی loose برابر `null` می‌دونه (`0 == null` در PHP درسته)، پس هر سلول با مقدار واقعی صفر کاملاً خالی رندر می‌شد. **فیکس:** اینترفیس رسمی `WithStrictNullComparison` (راه‌حل مستند خود پکیج) به `PaymentBreakdownSheet`/`ReportsExport`/`RawBookingsSheet` اضافه شد.

**۱۰) شکیل‌سازی ظاهری هر سه شیت اکسل** (به درخواست صریح کاربر، با پیش‌نمایش تأییدشده قبل از پیاده‌سازی): یک Trait مشترک (`App\Exports\Concerns\AppliesReportSheetStyle`) ساخته شد — راست‌چین کامل (RTL)، هدر آبی‌تیره+سفید+بولد با freeze pane، حاشیه‌ی نازک دور همه‌ی خونه‌ها، رنگ‌بندی متناوب ردیف‌ها (زبرا)، پهنای ستون اختصاصی هر شیت. شیت «جزئیات خام» علاوه بر این، رنگ‌بندی وضعیتی هم داره (پرداخت‌نشده/لغوشده قرمز کم‌رنگ، پرداخت‌شده/انجام‌شده سبز کم‌رنگ، در انتظار کهربایی).

> ⏳ **کار باز، نیمه‌کاره مونده (برای ادامه در فاز/جلسه‌ی بعد)**: کاربر همچنین خواسته بود «عملکرد متخصصین»/«خدمات پرطرفدار» (که فقط توی PDF هستن) به شیت‌های موجود اکسل *اضافه* بشن (نه به‌عنوان شیت جدا — این بلوک‌ها باید داخل همون شیت‌های موجود جا بشن) + نمودار (این تصمیم که فقط توی اکسل باشه یا هم اکسل هم PDF هنوز مشخص نشده). این درخواست **در بین بحث پیش نرفت و پیاده‌سازی نشد** چون بلافاصله بعدش کاربر یک باگ جدی‌تر (بحث بعدی: برگشت وجه لغو نوبت) رو مطرح کرد و توجه به اونجا منتقل شد. این آیتم باز مونده.

**فایل‌های اضافه‌ی این نشست‌ها:**
- `app/Exports/RawBookingsSheet.php` (جدید)
- `app/Exports/Concerns/AppliesReportSheetStyle.php` (جدید)

---

### ✅ R-Traits — استخراج Traits مشترک (HasJalaliDates + HandlesApiResponse)

**موجود از قبل (بررسی شد، نیازی به تغییر نداشت):** `HasRoles` (در User model)، `ResolvesSpecialist` (از قبل در ۴+ کنترلر Specialist استخراج شده).

**۱) `App\Traits\HasJalaliDates` (جدید)** — تحکیم منطق تبدیل تاریخ شمسی که مستقل و تکراری (همراه با آرایه‌ی دستی ارقام فارسی→انگلیسی) در کنترلرها/سرویس‌ها/Form Request/Export/Model کپی شده بود. متدها:
- `normalizeToEnglishDigits(?string $value): ?string` — فقط نرمالایز رقم، بدون parse
- `parseJalali(?string $value, string $format = 'Y/m/d', ?string $context = null): ?Carbon` — نسخه‌ی امن (در فرمت نامعتبر `null` برمی‌گردونه)؛ اگه `$context` پاس داده بشه، شکست با `Log::warning` ثبت می‌شه — دقیقاً برای حفظ رفتار قبلی هر call site (بعضی‌ها قبلاً `Log::warning` داشتن، بعضی‌ها کاملاً بی‌صدا بودن؛ این تفاوت با پارامتر اختیاری `$context` حفظ شد، نه یکسان‌سازی اجباری رفتار).
- `parseJalaliOrFail(string $value, string $format = 'Y/m/d'): Carbon` — نسخه‌ی fail-fast (استثنا پرتاب می‌کنه)، برای جاهایی که خودشون یک try/catch بیرونی با مسیر خطای مجزا دارن (مثل `SpecialistLeaveController::store()`) و نباید این شکست بی‌صدا قورت داده بشه.
- `toJalali(\DateTimeInterface $date, string $format = 'Y/m/d'): string` — جهت معکوس (Carbon→رشته‌ی شمسی)، با همون موتور Jalalian.
- `toJalaliParts(\DateTimeInterface $date): array` + `jalaliMonthName(int $month): string` — مخصوص `AdminReportService` که از موتور دستی `gregorian_to_jalali()` (نه Jalalian) استفاده می‌کنه؛ آرایه‌ی نام ماه‌های فارسی (قبلاً پراپرتی خصوصی `$jMonths` همون فایل) به Trait منتقل شد.

⚠️ **تصمیم آگاهانه**: این Trait عمداً موتور تبدیل زیرین رو یکپارچه نکرد — پروژه از قبل دو سیستم مستقل جلالی داره (پکیج `morilog/jalali` در اکثر جاها، تابع دستی `gregorian_to_jalali()` در `app/Helpers/JalaliDate.php` فقط برای `AdminReportService`). یکی‌کردن این دو موتور ریسک رفتاری بی‌دلیل داشت (احتمال نتیجه‌ی متفاوت در تاریخ‌های مرزی سال/ماه) بدون این‌که هیچ باگ واقعی پشتش باشه — پس Trait فقط لایه‌ی «مصرف» رو یکپارچه کرد، نه موتور محاسبه رو.

**فایل‌های wire‌شده با `HasJalaliDates` (۱۴ فایل، تأیید شده با grep سراسری)**: `BookingController` (User)، `UserWalletController`، `SpecialistReviewController`، `SpecialistBookingManagementController`، `SpecialistLeaveController` (با `parseJalaliOrFail`)، `SpecialistWalletService` (متد خصوصی تکراری `parseJalaliDate()` حذف شد)، `UpdateAdminBookingRequest`، `SpecialistDashboardService`، `SpecialistNotificationController`، `SpecialistBookingsExport`، مدل `BlogPost`، `BlogPostService`، `AdminReportService` (۴ بلوک تکراری `gregorian_to_jalali` + حذف `$jMonths`)، و **`SpecialistReportController`** — این آخری در گزارش اولیه‌ی این فاز اصلاً نام برده نشده بود؛ حین بررسی سراسری کشف شد متد خصوصی `convertJalaliToCarbon()` دقیقاً همون الگوی تکراری persian-digit+parse رو داشت.

**۲) `App\Traits\HandlesApiResponse` (جدید)** — `successResponse(?string $message, array $extra, int $status)`/`errorResponse(string $message, int $status, array $extra)`.

⚠️ **تصمیم آگاهانه درباره‌ی محدوده**: در کل پروژه ۱۵۰+ فراخوانی `response()->json()` وجود داره، ولی این Trait عمداً فقط روی الگوی *واقعاً یکسان* `{success: bool, message?: string}` اعمال شد (۴ فایل)؛ بقیه (مثل `BookingAvailabilityController` با کلید `slots`/`dates`، `LoyaltyController` با `points`/`history`، بخش `check()` خود `BookingDiscountController` با کلید `valid` نه `success`) آگاهانه دست‌نخورده موندن — شکل داده‌شون واقعاً متفاوته، یکپارچه‌سازی اجباری اون‌ها یک تغییر قرارداد API واقعی بود (ریسک شکستن جاوااسکریپت مصرف‌کننده‌ی فعلی)، نه صرفاً حذف کد تکراری.

**فایل‌های wire‌شده با `HandlesApiResponse` (۴ فایل، تأیید شده)**: `SpecialistNotificationController::markAsRead()`، `AdminNotificationController` (`markAsRead`/`delete`/`toggleRead`)، `User\NotificationController::markAsRead()`، `BookingDiscountController` (فقط دو catch-branch واقعاً هم‌شکل در `apply()`/`applyApi()`؛ برنچ `check()` که کلید `valid` داره دست‌نخورده موند).

**⭐ منشأ این فاز**: بخش عمده‌ی این کار در یک نشست موازی (مستقل از این گفتگو) انجام شد؛ در همین گفتگو، خروجی اون نشست با فایل‌های در حال کار این گفتگو (که هم‌زمان `AdminReportService` رو با فیلدهای بیشتر تکمیل می‌کرد) دیف و ادغام شد — نسخه‌ی نهایی `AdminReportService` هم Trait رو داره هم تمام فیلدهای اضافه‌شده‌ی این گفتگو رو.

**فایل‌های wire‌شده:**
`app/Traits/HasJalaliDates.php` (جدید)، `app/Traits/HandlesApiResponse.php` (جدید)، `app/Exports/SpecialistBookingsExport.php`، `app/Http/Controllers/Admin/Notification/AdminNotificationController.php`، `app/Http/Controllers/Specialist/Booking/SpecialistBookingManagementController.php`، `app/Http/Controllers/Specialist/Leave/SpecialistLeaveController.php`، `app/Http/Controllers/Specialist/Notification/SpecialistNotificationController.php`، `app/Http/Controllers/Specialist/Report/SpecialistReportController.php`، `app/Http/Controllers/Specialist/Review/SpecialistReviewController.php`، `app/Http/Controllers/User/BookingController.php`، `app/Http/Controllers/User/BookingDiscountController.php`، `app/Http/Controllers/User/NotificationController.php`، `app/Http/Controllers/User/UserWalletController.php`، `app/Http/Requests/Admin/Booking/UpdateAdminBookingRequest.php`، `app/Models/BlogPost.php`، `app/Services/Admin/Blog/BlogPostService.php`، `app/Services/Specialist/SpecialistDashboardService.php`، `app/Services/Specialist/SpecialistWalletService.php`، `app/Services/Admin/Report/AdminReportService.php`

---

## ⭐ رفع مستقل (بحرانی/مالی، ۲۰۲۶-۰۷-۲۷): برگشت وجه لغو نوبت‌های پرداخت‌شده هیچ‌وقت واقعاً کار نمی‌کرد

**کشف اولیه:** کاربر با بررسی دستی خروجی PDF/Excel گزارش روزانه متوجه شد ستون‌های «وضعیت بازگشت وجه» و «مبلغ بازگشتی» برای همه‌ی نوبت‌های لغوشده‌ی پرداخت‌شده همیشه «—» هستن — بدون استثنا.

**ریشه‌یابی:**
1. **🔴 بحرانی/کرش کامل**: `RefundService::processRefund()` متد `PaymentService::refund()` رو صدا می‌زد که **اصلاً وجود نداشت** (`PaymentService` فقط `createPayment`/`verifyPayment`/`createWalletChargePayment`/`verifyWalletChargePayment` داره). فراخوانی متد ناموجود یک `\Error` پرتاب می‌کنه (نه `\Exception`) — همون الگوی «`\Error` vs `\Exception`» که قبلاً هم در این پروژه کشف شده بود. این `\Error` هم از `catch (\Exception $e)` خودِ `RefundService` عبور می‌کرد، هم از `catch (\Exception $e)` در `AdminBookingController::update()` — یعنی هر بار ادمین یک نوبت **پرداخت‌شده** رو از پنل لغو می‌کرد، این کرش رخ می‌داد: نه پول برمی‌گشت، نه SMS، نه Support Ticket. چون `$booking->update(['status' => 'cancelled'])` **قبل از** این تلاش commit شده بود، نوبت در گزارش «لغو شده» دیده می‌شد ولی پولی جابه‌جا نشده بود.
2. **🟡 حتی در مسیر موفقیت فرضی**: `refunded_amount`/`refund_reference` (هر دو `$fillable`) هیچ‌وقت نوشته نمی‌شدن — فقط `refund_status`/`refunded_at`.

**⭐ تصمیم معماری کلیدی (تصمیم صریح کاربر، نه پیش‌فرض من)**: برای رفع باگ ۱، دو مسیر ممکن بررسی شد:
- **گزینه‌ی الف (رد شد)**: ساخت اتصال واقعی به قابلیت Reversal زرین‌پال برای لغو ادمین (نیاز به کلید API جدا، endpoint ناپایدار/غیررسمی، پیچیدگی و ریسک بیشتر).
- **گزینه‌ی ب (انتخاب شد)** ✅: لغو ادمین هم دقیقاً از همون مسیر کیف‌پولی که از قبل برای لغو مشتری/متخصص وجود داشت استفاده کنه (`BookingObserver::handleCancellation()` از قبل شاخه‌ی `cancelled_by === 'admin'` رو داشت، ولی هیچ‌وقت `cancelled_by='admin'` واقعاً روی مدل ست نمی‌شد — دقیقاً برای پرهیز از تداخل با `RefundService` که قرار بود گیت‌وی رو صدا بزنه). کاربر این گزینه رو صریحاً به‌خاطر سادگی و عدم نیاز به درگاه انتخاب کرد.

> ⚠️ **نکته‌ی مهم درباره‌ی یک نشست موازی**: در یک نشست کاملاً جدا (خارج از این گفتگو)، همون باگ ریشه‌ای کشف شد ولی مسیر «گزینه‌ی الف» (اتصال واقعی به زرین‌پال، `App\Services\Payment\ZarinpalRefundService` + بازنویسی کامل `RefundService` بر اساس `payment_details['method']`) پیاده‌سازی شد. بعد از مقایسه‌ی این دو مسیر با کاربر، **گزینه‌ی ب به‌صراحت انتخاب و نهایی شد**؛ فایل‌های اون مسیر دیگه (`ZarinpalRefundService.php`, بازنویسی `RefundService.php`, بلوک `config('services.zarinpal.refund')`) **عمداً adopt نشدن** و به لیست کاندیدهای `R-Cleanup-DeadCode` اضافه شدن (`RefundService.php` قدیمی الان کاملاً کد مرده‌ست، هیچ‌جا صدا زده نمی‌شه).

**پیاده‌سازی نهایی (مسیر انتخاب‌شده):**
- `App\Services\Admin\Booking\AdminBookingService` بازنویسی شد: دیگه به `RefundService` وصل نیست؛ `cancelled_by='admin'` + `cancelled_at` در همون تراکنش/همون فراخوانی `update()` که `status` رو عوض می‌کنه ست می‌شه (نه یک `update()` جدا — تا `BookingObserver::updated()` فقط یک‌بار fire بشه، نه دوبار با ریسک دوبار اجرای منطق کیف‌پولی).
- **🔴 باگ مالی جدی‌تر و جدا کشف/رفع شد**: `BookingObserver::handleCancellation()` (هر سه مسیر: مشتری/متخصص/ادمین) پول رو به کیف‌پول مشتری برمی‌گردوند، ولی **هیچ‌وقت سهمی که موقع پرداخت به متخصص داده شده بود، یا کمیسیونی که ادمین گرفته بود رو پس نمی‌گرفت** — یعنی هر نوبت پرداخت‌شده‌ی بعداً لغوشده، سالن رو عملاً دوبار پول می‌داد. **فیکس:** `SpecialistWallet::reverseIncome()` و `AdminWallet::deductCommission()` (متدهای جدید) + `BookingObserver::reverseOriginalPayout()` — تراکنش اصلی درآمد/کمیسیون همون نوبت رو پیدا و دقیقاً همون مبلغ رو برمی‌گردونن (نه بازمحاسبه بر اساس نرخ فعلی کمیسیون، چون ممکنه از موقع پرداخت تغییر کرده باشه).
- **⚠️ نکته‌ی ریسک باقی‌مونده، آگاهانه پذیرفته‌شده**: اگه سهم متخصص قبلاً واقعاً تسویه و برداشت شده باشه (نه فقط «در انتظار»)، این برگشت از `balance` واقعی کسر می‌شه که می‌تونه **منفی** بشه (یعنی متخصص به سالن بدهکار می‌شه). این عمداً به همین شکل نگه داشته شد (تنها راه حسابداری صحیحه) + یک `Log::warning` واضح ثبت می‌شه. مکانیزم بازیابی: `SpecialistWallet::canWithdraw()` از قبل چک `$amount > $this->balance` داره — یعنی متخصص با موجودی منفی نمی‌تونه درخواست برداشت جدید بزنه تا از درآمد آینده جبران بشه (پس‌گیری فعال/خودکار ساخته نشد، فقط جلوگیری غیرفعال از تشدید بدهی).
- **🔴 باگ دوم، کشف‌شده حین طراحی راه‌حل بالا (نه در نشست موازی)**: وقتی سهم یک نوبت هنوز تسویه نشده بود (`pending_amount`, نه `balance`) و نوبت لغو می‌شد، `reverseIncome()` مبلغ رو از `pending_amount` کم می‌کرد ولی تراکنش اصلی درآمد رو دست‌نخورده (`status: pending`) می‌ذاشت — یعنی چند روز بعد، `wallet:settle-pending` همون تراکنش رو **دوباره** «تسویه» می‌کرد (چون هنوز `pending` بود)، `pending_amount` رو یک‌بار دیگه کم می‌کرد (منفی) و `balance` رو به اشتباه زیاد می‌کرد — یعنی متخصص برای نوبت لغوشده و بازپرداخت‌شده، دوباره پول واقعی می‌گرفت. **فیکس:** `reverseIncome()` حالا امضاش عوض شد (کل آبجکت تراکنش رو می‌گیره، نه فقط مبلغ) و اگه هنوز settle نشده بود، `metadata.status` رو به `'reversed'` تغییر می‌ده — تا کوئری `wallet:settle-pending` (`whereJsonContains('metadata->status', 'pending')`) دیگه هیچ‌وقت پیداش نکنه.
- **🟡 پیشگیری از ریشه‌ی مشکل (به درخواست صریح کاربر، بعد از بحث دقیق درباره‌ی این‌که آیا کیف‌پول = «پول فیک» است یا نه)**: `WalletAdminService::settlePendingIncomes()` قبلاً فقط بر اساس «چند روز از لحظه‌ی پرداخت گذشته» تسویه می‌کرد — کاملاً مستقل از این‌که آیا خودِ نوبت واقعاً برگزار شده یا نه؛ یعنی برای نوبت‌های رزروشده‌ی چند هفته‌ی آینده، سهم متخصص می‌تونست خیلی زودتر از برگزاری واقعی نوبت، قابل‌برداشت بشه. **فیکس:** الان علاوه بر مهلت روزانه، منتظر می‌مونه تا `booking_time` واقعاً بگذره (`$booking->booking_time->isFuture()` → skip، حتی با `--ignore-delay`).
- **`refund_reference`/`refund_details`**: در نشست موازی طراحی و پیاده‌سازی شده بود (`refund_reference = 'WALLET-REFUND-{id تراکنش}'` + `refund_details` JSON با `method`/`cancelled_by`)؛ این بخش (بدون تغییر در منطق گیت‌وی، چون فقط به تراکنش کیف‌پول مربوطه) عیناً ادغام شد.
- **✅ یافته‌ی جانبی که هر دو نشست (این گفتگو و نشست موازی) مستقل از هم پیدا کردن**: `SecurePaymentController::verify()` هیچ‌وقت `payment_reference`/`payment_details` رو روی خود `Booking` ست نمی‌کرد (فقط مدل جدای `Payment`) — باعث خالی‌موندن مرجع پرداخت در Blade ها و نامرئی‌موندن این پرداخت‌ها از گزارش‌های `payment_details->method`-محور. **فیکس:** مثل مسیر اصلی `PaymentController`، `payment_reference` + `payment_details` (با `method: 'gateway'`, `secure_payment: true`) هم روی `Booking` ست می‌شه.

**فایل‌های نهایی این مجموعه رفع (نسخه‌ی ادغام‌شده و تأییدشده):**
- `app/Services/Admin/Booking/AdminBookingService.php`
- `app/Http/Controllers/Admin/Booking/AdminBookingController.php`
- `app/Observers/Booking/BookingObserver.php`
- `app/Models/SpecialistWallet.php`
- `app/Models/AdminWallet.php`
- `app/Services/Admin/Wallet/WalletAdminService.php`
- `app/Http/Controllers/User/SecurePaymentController.php`
- `app/Events/Booking/BookingCancelled.php` (کامنت قدیمی که هنوز از معماری RefundService/گیت‌وی حرف می‌زد، با معماری فعلی هماهنگ شد)

## ⭐ رفع مستقل (۲۰۲۶-۰۷-۲۸): تصحیح نهایی توزیع جریمه‌های لغو (مسیر کیف‌پولی)

بعد از استقرار مسیر کیف‌پولی بالا، کاربر منطق دقیق جریمه‌ها رو با یک بحث مجزا نهایی کرد — نسخه‌ی اول `BookingObserver::handleCancellation()` (چه در این گفتگو، چه در نشست موازی) دو مشکل داشت که کاربر صراحتاً اصلاح‌شون رو خواست:

1. **لغو توسط متخصص**: قبلاً مشتری همیشه *کامل* `prepayment_amount` رو پس می‌گرفت، بدون هیچ کسری — یعنی جریمه‌ی `specialist_cancellation_penalty_percentage` (که در `WalletSetting` و صفحه‌ی تنظیمات ادمین از قبل وجود داشت) هیچ‌وقت واقعاً اعمال نمی‌شد (کد مرده).
2. **لغو توسط مشتری**: جریمه (`customer_cancellation_fee_percentage`) که از مبلغ برگشتی مشتری کسر می‌شد، ۸۰٪ اون به‌عنوان «جبران» مستقیم به کیف‌پول متخصص می‌رفت (`addIncome`) و ۲۰٪ باقی‌مونده هیچ‌جا صریحاً ثبت نمی‌شد.

**قانون نهایی که کاربر تعیین کرد (و پیاده‌سازی شد):**

| نقش لغوکننده | فرمول برگشت به مشتری | جریمه کجا می‌ره؟ |
|---|---|---|
| **مشتری** | `prepayment - customerFee` (`customerFee` شرطی، بر اساس `cancellation_before_hours`) | ۱۰۰٪ کیف‌پول **ادمین** (نه متخصص) |
| **متخصص** | `prepayment - specialistPenalty` (بدون قید زمانی) | ۱۰۰٪ کیف‌پول **ادمین** |
| **ادمین** | `prepayment` کامل | جریمه‌ای نیست |

نکات کلیدی:
- در هر سه حالت، سهم اصلی متخصص + کمیسیون اصلی ادمین از همون نوبت (که موقع پرداخت ثبت شده بودن) توسط `reverseOriginalPayout()` بدون قید معکوس می‌شن — این بخش تغییری نکرد.
- برای لغو متخصص: جریمه دیگه یک کسر *جدا* از کیف‌پول متخصص (مثلاً از طریق `SpecialistWallet::deductCancellationFee()`) نیست؛ بلکه از همون مبلغی که قرار بود کامل به مشتری برگرده کم می‌شه. متخصص در این حالت همیشه خالص صفر می‌مونه (نه بیشتر، نه کمتر از سهم اصلی‌ای که از دست داده)، صرف‌نظر از میزان جریمه.
- هر دو نرخ (`customer_cancellation_fee_percentage`/`cancellation_before_hours` برای مشتری، `specialist_cancellation_penalty_percentage` برای متخصص) از قبل **مستقل از هم** در `WalletSetting`/صفحه‌ی `admin/wallet/settings.blade.php` قابل تنظیم توسط ادمین بودن — درخواست کاربر برای «قوانین جدا برای مشتری و متخصص» از قبل ساختاری برآورده بود، فقط سمت متخصص هیچ‌وقت واقعاً به منطق wire نشده بود.
- پیش‌فرض دیتابیس `specialist_cancellation_penalty_percentage` صفره، پس تا وقتی ادمین صریحاً درصدی وارد نکنه، این فیکس هیچ رفتار موجودی رو تغییر نمی‌ده.

⚠️ **هشدار sync بین این گفتگو و نشست موازی**: این تصحیح خاص (جدول بالا) **در زمان نوشتن این خط، در نسخه‌ی آپلودی کاربر اعمال نشده بود** — یعنی کد واقعی هنوز نسخه‌ی قبلی (برگشت کامل بدون جریمه برای متخصص + تقسیم ۸۰/۲۰ برای مشتری) رو داشت. تصحیح دوباره روی همون فایل (با همون الگوی `$refundTransaction`/`$refundDetails` که در نشست موازی اضافه شده بود، حفظ‌شده) اعمال و تحویل داده شد. **لطفاً قبل از رفتن به فاز بعدی تأیید کنید این نسخه‌ی جدید واقعاً جایگزین فایل local شده** — این دقیقاً همون الگوی تکراری «فیکس تأییدشده در گفتگو ≠ ذخیره‌شده روی دیسک» هست که چندین بار دیگه هم در این پروژه (بین همین دو مسیر موازی) اتفاق افتاده.

**کاندید حذف اضافه برای `R-Cleanup-DeadCode`:** ~~`SpecialistWallet::deductCancellationFee()`~~ ✅ **حذف شد (۲۰۲۶-۰۸-۰۲)** — یک نسخه‌ی میانی از این فیکس ازش استفاده می‌کرد (کسر جدا از کیف‌پول متخصص)، ولی طبق تصمیم نهایی بالا دیگه صدا زده نمی‌شد؛ با `grep -rn` تأیید و حذف شد.

**فایل این بخش:** `app/Observers/Booking/BookingObserver.php`

---

## ⭐ رفع مستقل (۲۰۲۶-۰۷-۲۹): تکمیل قوانین لغو نوبت — ۴ پیشنهاد کاربر تأیید و پیاده‌سازی شد

بعد از تثبیت منطق بالا، ۵ پیشنهاد برای تقویت سیستم جریمه مطرح شد؛ کاربر ۱ تا ۴ رو تأیید کرد (۵ - عدم اتصال به درگاه واقعی برای برگشت وجه - آگاهانه تأیید شد که همینطور بمونه، چون پول در گردش می‌مونه و برای بیزینس بهتره).

### ✅ ۱) آستانه‌ی زمانی جدا برای جریمه‌ی متخصص (تقارن با مشتری)
قبلاً جریمه‌ی متخصص **بدون قید زمانی** بود (حتی لغو یک ماه قبل هم جریمه می‌خورد) — نامتقارن با مشتری که یک آستانه‌ی زمانی (`cancellation_before_hours`) داشت. ستون جدید و **جدا** (نه مشترک، چون بازه‌ی معقول برای مشتری/متخصص لزوماً یکی نیست) اضافه شد: `specialist_cancellation_before_hours` (پیش‌فرض ۲۴ ساعت). `WalletSetting::calculateSpecialistCancellationPenalty()` حالا `$bookingTime` هم می‌گیره و دقیقاً همون منطق مشتری (`hoursUntilBooking > threshold` → جریمه صفر) رو اعمال می‌کنه.

### ✅ ۲) سقف روی درصدها — از قبل برآورده بود
بررسی نشون داد `MaxPercentage` rule از قبل روی هر دو فیلد (`customer_cancellation_fee_percentage`, `specialist_cancellation_penalty_percentage`) در `UpdateWalletSettingsRequest` اعمال شده بود — نیازی به فیکس نبود، فقط تأیید شد. سه فیلد جدید این جلسه (`specialist_cancellation_before_hours`, `specialist_repeat_cancellation_threshold`, `specialist_repeat_cancellation_window_days`, `specialist_repeat_cancellation_extra_percentage`) هم به همین قاعده (`MaxPercentage` روی فیلدهای درصدی) اضافه شدن.

### ✅ ۳) شفافیت مبلغ جریمه در پیامک
قبلاً پیامک لغو به مشتری فقط «پیش‌پرداخت: X تومان» رو نشون می‌داد (مبلغ اصلی نوبت، نه مبلغ واقعی برگشتی) — مشتری نمی‌فهمید چقدر جریمه شده. حالا اگه جریمه‌ای اعمال شده باشه، پیامک صراحتاً سه‌خط نشون می‌ده: «مبلغ نوبت»، «جریمه لغو»، «مبلغ بازگشتی». همچنین به متخصص هم — وقتی خودش لغو می‌کنه و جریمه می‌خوره — یک خط اضافه با مبلغ دقیق جریمه اطلاع داده می‌شه (قبلاً هیچ اطلاعی از جریمه‌اش نداشت، فقط می‌دید موجودیش کم شده).

### ✅ ۴) جریمه‌ی تشدیدی برای لغو مکرر متخصص
سه ستون جدید اضافه شد: `specialist_repeat_cancellation_threshold` (پیش‌فرض ۰ = غیرفعال)، `specialist_repeat_cancellation_window_days` (پیش‌فرض ۳۰)، `specialist_repeat_cancellation_extra_percentage` (پیش‌فرض ۰). اگه متخصصی در بازه‌ی زمانی مشخص به تعداد آستانه یا بیشتر نوبت لغو کنه (شمارش با `Booking::where('specialist_id', ...)->where('cancelled_by', 'specialist')->where('cancelled_at', '>=', now()->subDays(...))->count()`، شامل همین لغو)، درصد جریمه‌ی همون لغو (نه لغوهای قبلی) به‌اندازه‌ی درصد اضافه افزایش پیدا می‌کنه. سقف نهایی همیشه با `min(percentage, 100)` محدود می‌شه.

**⚠️ اصلاح یک اشتباه قبلی من:** در بخش قبلی این سند نوشته بودم «پیش‌فرض دیتابیس صفره تا وقتی ادمین صریحاً درصدی وارد نکنه» — این **اشتباه بود**. طبق migration اصلی (`2025_12_24_182656_create_wallet_tables.php`)، `specialist_cancellation_penalty_percentage` از روز اول پیش‌فرض **۱۰٪** داشته (نه صفر) و `customer_cancellation_fee_percentage` پیش‌فرض **۲۰٪** داشته. یعنی وایر کردن جریمه‌ی متخصص در بخش قبلی این سند، از همون لحظه با یک جریمه‌ی واقعی ۱۰٪ فعال شد، نه یک قابلیت خاموش تا تنظیم دستی ادمین. فیلدهای *جدید* این بخش (آستانه‌ی زمانی متخصص، جریمه‌ی تشدیدی) پیش‌فرض‌های امن دارن (`specialist_cancellation_before_hours=24` مشابه مشتری، `specialist_repeat_cancellation_threshold=0` یعنی غیرفعال).

**فایل‌های این بخش:**
- `database/migrations/2026_07_29_000000_add_specialist_cancellation_rules_to_wallet_settings.php` (جدید)
- `app/Models/WalletSetting.php`
- `app/Http/Requests/Admin/Wallet/UpdateWalletSettingsRequest.php`
- `resources/views/admin/wallet/settings.blade.php` (فیلدهای جدید + رفع ابهام برچسب «بازه زمانی جریمه لغو» که قبلاً مشخص نمی‌کرد فقط مخصوص مشتریه)
- `app/Observers/Booking/BookingObserver.php`

⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` باید اجرا بشه تا ستون‌های جدید `wallet_settings` ساخته بشن.

---

## ⭐ رفع مستقل: دو باگ کشف‌شده حین تست دستی واقعی (Laravel Telescope)

کاربر با تست دستی لغو نوبت (هم از پنل مشتری هم از پنل متخصص) و بررسی Telescope، دو باگ جدا پیدا کرد:

**۱) خطای ۴۰۳ «This action is unauthorized» هنگام لغو نوبت توسط مشتری**
`BookingPolicy::cancel()` یک قانون واقعی داره (نوبت باید حداقل ۲۴ ساعت تا زمان برگزاریش مونده باشه)، ولی دکمه‌ی «لغو نوبت» در `bookings/show.blade.php` فقط وضعیت (`pending`/`confirmed`) رو چک می‌کرد، نه این قانون — یعنی مشتری دکمه رو می‌دید، می‌زد، و با ۴۰۳ غافلگیر می‌شد. **فیکس:** دکمه از `@can('cancel', $booking)` (خود Policy) استفاده می‌کنه؛ در غیر این صورت یک توضیح کوتاه («کمتر از ۲۴ ساعت به نوبت مونده») نشون داده می‌شه.

**۲) پیام مبهم «نوبت توسط ادمین یا متخصص لغو شد» — حتی وقتی خودِ متخصص لغو کرده بود**
`SpecialistBookingCancelledNotification` فقط `'user'`/`'system'` رو می‌شناخت؛ برای `'specialist'`/`'admin'` (که واقعاً استفاده می‌شن) به یک fallback مبهم می‌رفت. **فیکس:** هرکدوم لیبل شفاف خودشون رو گرفتن (مشتری/متخصص/مدیر سالن/سیستم).

**۳) 🔴 باگ اضافه‌ی کشف‌شده حین بررسی مورد ۲: ریسک پیامک تکراری**
`SpecialistBookingCancelledNotification::via()` هنوز `'sms'` رو اعلام می‌کرد — درست برخلاف کامنت خود `SendBookingCancellationNotifications` که می‌گفت «پیامک عمداً از اینجا فرستاده نمی‌شه چون `BookingObserver::sendCancellationSMS()` از قبل می‌فرسته». یعنی متخصص برای هر لغو نوبت ۲ پیامک می‌گرفت (فعلاً هر دو تلاش به‌خاطر یک مشکل حساب Kavenegar بی‌صدا fail می‌شدن که همین موضوع رو پنهان کرده بود). **فیکس:** `via()` فقط `'database'` برمی‌گردونه.

> 🟡 نکته‌ی جانبی (خارج از کد، نیاز به اقدام کاربر): خطای «استفاده از این خط نیازمند ایجاد سطح دسترسی می‌باشد» مربوط به پنل Kavenegar است، نه کد — باید خط پیامکی مورد استفاده برای همون API Key در پنل Kavenegar فعال/تأیید بشه.

**فایل‌های این نشست:**
- `resources/views/bookings/show.blade.php`
- `app/Notifications/Booking/SpecialistBookingCancelledNotification.php`

---

### ✅ R-DB-Transaction — بررسی سراسری، بدون یافته

**روش بررسی:** جستجوی سراسری `grep -rn "DB::transaction(" app` (تأیید شد جای دیگه‌ای در پروژه، خارج از `app/`، از این الگو استفاده نمی‌شه) → **۳۶ مورد** پیدا شد (۱۶ مورد با `return DB::transaction(...)` بیرونی، ۲۰ مورد بدون `return` بیرونی). هر ۳۶ مورد تک‌تک باز و محتوای واقعی closure خونده شد، نه فقط امضای متد.

**نتیجه: هیچ نمونه‌ای از الگوی خطرناک (`return DB::transaction(fn() => ...)` بدون `return` صریح داخل closure) در کل کدبیس پیدا نشد.**
- تمام ۱۶ موردی که `return` بیرونی داشتن (`LoyaltyService::redeemReward`, `BookingService::applyDiscountCode/createBooking/cancelBooking`, `AdminSpecialistService::create/update`, `BlogPostService::store/update/togglePublish`, `BlogCategoryService::store/update`, `CategoryService::create/update/delete`, `UserWalletController::chargeCallback`, `DatabaseTransaction` trait) داخل closure‌شون هم صریحاً `return` دارن.
- تمام ۲۰ موردی که بدون `return` بیرونی بودن (`BookingObserver` ×۲, `WalletAdminService` ×۵, `SpecialistWalletService` ×۲, `PaymentController` ×۳, `AdminSpecialistService::delete`, `BlogPostService::destroy`, `ProcessWithdrawalJob`, `CancelUnpaidBookings`, `SpecialistBookingManagementController` ×۲, `SpecialistProfileController`, `BookingRescheduleController`) واقعاً هم به مقدار برگشتی نیازی نداشتن (side-effect محض داخل تراکنش) — نبود `return` بیرونی در این موارد بدون مشکل است.

یعنی نکته‌ی مستندشده‌ی قبلی («این سرویس‌ها از ابتدا درست نوشته شدن») برای همه‌ی موارد ذکرشده تأیید شد، و علاوه بر اون‌ها `CategoryService` و `UserWalletController::chargeCallback` (که در اون لیست نبودن) هم بررسی و تأیید شدن.

**⭐ نکته‌ی جانبی روشن‌شده حین بررسی (سوءتفاهم رفع‌شده، نه یک باگ جدید)**: در `BookingObserver::handleCancellation()` یک `$settings = WalletSetting::get();` وجود داره که در نگاه اول با الگوی مستندشده‌ی «`WalletSetting::get()` یک Collection برمی‌گردونه، نه Model» مطابقت داشت و نگران‌کننده به نظر می‌رسید (چون بعدش متدهایی مثل `$settings->calculateSpecialistCancellationPenalty(...)` روش صدا زده می‌شه). با چک مستقیم `app/Models/WalletSetting.php` تأیید شد این نگرانی بی‌مورده: خود مدل الان یک متد استاتیک اختصاصی داره که نام متد پیش‌فرض Eloquent رو عمداً override کرده:
```php
public static function get(): self
{
    return self::first() ?? self::create([]);
}
```
یعنی `WalletSetting::get()` در این پروژه الان کاملاً امن و درسته و همیشه یک instance واقعی از مدل (نه Collection) برمی‌گردونه. تنها جای دیگه‌ای که از الگوی قدیمی `WalletSetting::get()->prop` استفاده می‌کنه، فایل کد مرده‌ی `app/Http/Requests/Specialist/StoreWithdrawalRequest.php` (root-level، کاندید حذف در `R-Cleanup-DeadCode`) است که چون هیچ کنترلری بهش وصل نیست، بی‌اثره. **⚠️ یادآوری برای فازهای بعدی: این خط رو دوباره به‌اشتباه «فیکس» نکنید** — این دقیقاً معکوس الگوی مستندشده‌ی همیشگیه (این‌بار مستندسازی قدیمی‌تر از کد بود، نه برعکس).

**نتیجه‌گیری فاز:** هیچ فایلی نیاز به تغییر نداشت؛ فاز بدون هیچ کامیتی بسته شد.

### ✅ R-TypeHints — Type Hinting یکدست (return types + constructor promotion)

**دامنه‌ی بررسی:** تمام متدهای `public` در `app/Http/Controllers` و `app/Services` (طبق تعریف اولیه‌ی فاز). دسترسی شبکه به GitHub در این جلسه کاملاً باز بود — کلون مستقیم برنچ `develop` (نه فایل آپلودی) انجام شد.

**۱) Return type صریح:**
اسکن سراسری نشون داد **۱۹۲ متد public در ۵۱ فایل** فاقد return type بودن. یک ابزار تحلیل نوشته شد که بدنه‌ی هر متد رو واقعاً می‌خوند (نه فقط امضا) و بر اساس الگوی `return`های واقعی (`view(`, `redirect(`/`back(`, `response()->json(`, مقدار مدل/Collection/آرایه و غیره) نوع رو استنتاج می‌کرد؛ نتیجه برای ۱۶۳ متد (الگوهای ساده و یک‌دست) خودکار اعمال و با اسکن مجدد تأیید شد، و ۲۹ متد باقی‌مونده (فراخوانی‌های delegate‌شده، ترکیب view/json شرطی، Collection/Paginator) تک‌تک با خوندن کد واقعی دستی تعیین و اعمال شدن. اسکن نهایی: **صفر متد بدون return type باقی مونده.**

علاوه بر این، چند متدی که از قبل return type داشتن ولی به‌صورت FQCN خام (`\Illuminate\View\View` به‌جای `View` با `use` وارد‌شده) نوشته شده بودن، برای یکدستی با بقیه‌ی پروژه به نام کوتاه import‌شده تغییر کردن (۵ فایل، از جمله افزودن `use Illuminate\Http\Resources\Json\AnonymousResourceCollection` در `Api\V1\LoyaltyController`).

**⭐ باگ جانبی کشف/رفع‌شده (حین تعیین نوع بازگشتی `ReportService`):** constructor این کلاس `DiscountCodeObserver $cacheService` رو inject می‌کرد، ولی متدهاش `$this->cacheService->remember(...)` صدا می‌زدن — متدی که فقط روی `ReportCacheService` وجود داره (تأیید شد با خوندن هر دو کلاس)، نه روی `DiscountCodeObserver`. یعنی یک wiring اشتباه کلاسیک (شبیه الگوهای قبلی «کلاس اشتباه inject شده») که در صورت فراخوانی فتال ارور «Call to undefined method» می‌داد. **تأیید شد `ReportService` در کل `app/`/`routes/` هیچ‌جا استفاده نمی‌شه (کاملاً کد مرده)** — پس این باگ فعلاً بی‌اثره، ولی چون فیکسش (تغییر type-hint constructor از `DiscountCodeObserver` به `ReportCacheService`) کاملاً بی‌خطر و بدون تغییر رفتار بود، همون‌جا اصلاح شد. کاندید حذف کامل در `R-Cleanup-DeadCode` باقی می‌مونه (کلاس کلاً بلااستفاده‌ست).

**۲) استانداردسازی constructor property promotion:**
اسکن جدا برای الگوی قدیمی (`protected Type $prop;` + `public function __construct(Type $prop) { $this->prop = $prop; }`) در همون دو دایرکتوری، **۲۴ فایل** رو پیدا کرد. برای هرکدوم قبل از تبدیل تأیید شد که پراپرتی هیچ‌جای دیگه‌ای (بیرون از constructor) دوباره assign نمی‌شه؛ همه به پراپرتی promoted با `readonly` تبدیل شدن (مثلاً `public function __construct(private readonly ReviewService $reviewService) {}`)، با حفظ visibility اصلی (`protected`/`private`). یک مورد استثنا (`Auth\VerificationController`) constructor بدنه‌ی ترکیبی داشت (`$this->middleware('auth')` + assignment) که اسکنر خودکار درست تشخیص داد نیاز به بررسی دستی داره؛ به‌صورت دستی promote شد با نگه‌داشتن فراخوانی `middleware()`.

بعد از اعمال، بررسی brace/paren balance روی تمام ۵۹ فایل تغییریافته انجام شد (بدون دسترسی به PHP CLI برای lint واقعی در این جلسه) — بدون ناهماهنگی. کاربر بعداً با `php -l` روی تمام فایل‌های تغییریافته (لوکال، Windows/XAMPP) این رو مستقل تأیید کرد: **بدون خطای syntax در هیچ‌کدوم از ۵۹ فایل.**

**⚠️ خارج از scope اولیه‌ی این فاز بود، ولی در همون نشست به‌عنوان تکمیل مجزا انجام شد:** همین الگوی constructor قدیمی در ۱۵ فایل دیگه هم دیده شد: `app/Events` (۲ فایل)، `app/Observers` (۲ فایل)، `app/Notifications` (۷ فایل)، `app/Http/Middleware` (۱ فایل)، `app/Exports` (۲ فایل). چون تعریف اولیه‌ی فاز صریحاً «کنترلرها و Service ها» بود، این ۱۵ فایل ابتدا فقط مستند شدن و دست نخوردن؛ کاربر در همون نشست درخواست تکمیلشون رو داد — نتیجه در بخش «۳» پایین.

**۳) تکمیل بعدی (commit جدا): پوشش کامل ۱۵ فایل باقی‌مونده (Events/Observers/Notifications/Middleware/Exports)**

قبل از تبدیل، دو ریسک واقعی که در نکته‌ی بالا بهشون اشاره شده بود، عملاً بررسی شدن:

- **ریسک queued serialization**: `BookingCreated`/`NewUserRegistered` (Events) از `SerializesModels` استفاده می‌کنن و listenerهاشون (`SendAdminBookingNotifications`, `SendNewUserNotifications`) واقعاً `ShouldQueue` هستن — یعنی این ایونت‌ها موقع صف‌شدن سریالایز/دی‌سریالایز می‌شن. برای اطمینان از سازگاری `readonly` با این چرخه، یک بازتولید خالص PHP (بدون نیاز به کل فریم‌ورک) از دقیقاً همون الگوی `__serialize()`/`__unserialize()` که `SerializesModels` پیاده می‌کنه نوشته و اجرا شد؛ نتیجه تجربی: پراپرتی‌های `readonly` این چرخه رو بدون خطا رد می‌کنن (چون اولین نوشتن روی یک شیء تازه‌ست، از داخل scope کلاس از طریق trait — طبق قوانین رسمی PHP برای readonly مجازه). سه Notification صف‌شده‌ی دیگه (`AdminNewBookingNotification`, `AdminNewWithdrawalRequestNotification`, `NewUserRegisteredNotification`) تأیید شد از `SerializesModels` استفاده نمی‌کنن، پس فقط سریالایز خام PHP روشون اعمال می‌شه — اونم با `readonly` بدون مشکل.
- **🔴 باگ واقعی کشف‌شده در همین بررسی**: سه فایل (`ReportsExport`, `SpecialistBookingsExport`, `BookingNotification`) پراپرتی‌هایی داشتن که از اول اصلاً type نداشتن (نه صرفاً بدون type-hint در constructor، بلکه هیچ‌جای کلاس تایپی براشون تعریف نشده بود). تبدیل خودکار این‌ها به `readonly` بدون type یک فتال ارور واقعی PHP تولید می‌کرد (`Readonly property must have type`) — این با نصب واقعی PHP 8.3 روی محیط کار (که قبلش در دسترس نبود) و اجرای `php -l` واقعی قبل از تحویل کشف و جلوش گرفته شد، نه بعد از تحویل به کاربر. با خوندن نحوه‌ی مصرف واقعی هرکدوم در بدنه‌ی کلاس، type درست تعیین و صریحاً اعمال شد:
  - `ReportsExport`: `array $data`, `string $type`
  - `SpecialistBookingsExport`: `Collection $bookings` (تأیید شد از `$query->get()` که واقعاً Eloquent Collection برمی‌گردونه تغذیه می‌شه)، `float $commissionRate`
  - `BookingNotification`: `Booking $booking` (مدل واقعی، با import اضافه‌شده)، `bool $needsApproval`

بعد از این فیکس، `php -l` واقعی روی هر ۷۴ فایل کل diff این فاز (نه فقط ۱۵ تای جدید) اجرا و صفر خطای syntax تأیید شد. بررسی duplicate imports هم روی کل diff تکرار شد — چیزی پیدا نشد.

**فایل‌های commit دوم:** `app/Channels/SmsChannel.php`, `app/Events/Booking/BookingCreated.php`, `app/Events/User/NewUserRegistered.php`, `app/Exports/ReportsExport.php`, `app/Exports/SpecialistBookingsExport.php`, `app/Http/Middleware/SecurityMiddleware.php`, `app/Notifications/Admin/Withdrawal/Request/AdminNewWithdrawalRequestNotification.php`, `app/Notifications/Booking/AdminNewBookingNotification.php`, `app/Notifications/Booking/BookingNotification.php`, `app/Notifications/Review/{NegativeReviewNotification,NewReviewReceivedNotification,SpecialistRespondedNotification}.php`, `app/Notifications/User/NewUserRegisteredNotification.php`, `app/Observers/Booking/BookingObserver.php`, `app/Observers/DiscountCodeObserver.php`.

**نتیجه‌ی نهایی فاز:** الگوی constructor قدیمی الان در **کل `app/`** یکدست شده، نه فقط Controllers/Services — دو commit جدا روی همون برنچ `refactor/r-typehints` (اولی برای Controllers/Services، دومی برای بقیه‌ی لایه‌ها)، هر دو با `git am` روی محیط لوکال کاربر (Windows/XAMPP) اعمال و با `php -l` واقعی روی تمام فایل‌ها تأیید شدن.

**روش تحویل:** چون دسترسی شبکه فقط خواندنی بود (push مستقیم به ریپو با ۴۰۳ رد شد)، به‌جای تحویل تک‌تک فایل، یک کامیت واحد روی برنچ جدید ساخته شد و به‌صورت فایل patch (`git format-patch`) تحویل داده شد؛ قبل از تحویل، اعمال‌شدنش بدون conflict روی آخرین کامیت `develop` تست شد. کاربر با `git am` روی محیط لوکال (Windows/XAMPP) اعمال کرد و با `php -l` روی تمام فایل‌های تغییریافته syntax رو تأیید کرد.

**فایل‌های commit اول:** ۵۹ فایل (۵۱ فایل فقط return type + همپوشانی با ۲۴ فایلی که هم return type هم constructor promotion گرفتن؛ فهرست کامل در commit موجوده). مجموع کل فاز (هر دو commit): ۷۴ فایل.

**نکته‌ی مهم برای فازهای بعدی**: R-TypeHints فقط return type + constructor pattern رو پوشش داد؛ type hint پارامترهای ورودی متدها (که در بعضی فایل‌های قدیمی‌تر پروژه مثل `LoyaltyService` هنوز بدون type هستن، مثل `$userId`, `$startDate`) عمداً بررسی نشد — این تعریف اولیه‌ی فاز نبود و اگه لازم باشه باید به‌عنوان یک زیرفاز جدا مطرح بشه. (⚠️ `ReportService` که در همین فاز به‌عنوان مثال نام برده شده بود، در فاز بعدی — R-Cleanup-DeadCode — کلاً به‌عنوان کد مرده حذف شد؛ دیگه موضوعیت نداره.)

---

### ✅ R-Cleanup-DeadCode — پاک‌سازی نهایی کد مرده (بخش اول، تکمیل‌شده)

**روش بررسی:** دسترسی شبکه به GitHub کاملاً باز بود؛ کلون مستقیم برنچ `develop` (نه فایل زیپ آپلودی) انجام شد. قبل از حذف هر کاندید مستندشده در بخش «فازهای باقی‌مانده» (نسخه‌ی قبلی این سند)، هرکدوم با `grep` دقیق (`import ... from` واقعی برای JS، `use App\...`/فراخوانی مستقیم کلاس برای PHP — نه صرف تطابق نام فایل) تأیید شد که واقعاً هیچ مصرف‌کننده‌ای نداره.

**۹ فایل/بلوک مستندشده‌ی قبلی، تأیید و حذف شدن:**
1. `resources/js/Components/Admin/loyalty/LoyaltyAdmin.jsx`, `Announcement/AnnouncementAdmin.jsx`, `gallery/GalleryAdmin.jsx` — importشون از قبل از `admin.jsx` پاک شده بود؛ فقط فایل فیزیکی مونده بود.
2. `routes/api/admin/loyalty.php` — **کشف جانبی هنگام تأیید**: نام روت‌های `rewards.store`/`rewards.show`/`rewards.update`/`rewards.destroy` این فایل عیناً با نام روت‌های وب واقعی (`routes/admin/loyalty.php`, که فرم‌های Blade واقعی بهشون وابسته‌ن) تصادم داشت. چون `api.php` بعد از `web.php` لود می‌شه، این تصادم بی‌صدا متدهای JSON (`storeReward`/`showReward`/...) رو جایگزین متدهای Blade-محور (`store`/`show`/...) می‌کرد — یک باگ بالقوه‌ی کاملاً جدید و قبلاً مستندنشده، که با حذف همین فایل رفع شد.
3. `app/Http/Requests/Specialist/StoreWithdrawalRequest.php` (root، جایگزین‌شده در R-SpecialistWallet) — صفر ارجاع در کل `app/`.
4. `app/Services/RefundService.php` — صفر ارجاع واقعی (فقط یک اشاره‌ی متنی در کامنت `BookingCancelled.php`).
5. `app/Services/ReportService.php` — صفر ارجاع در کل پروژه؛ کشف‌شده به‌عنوان کد مرده در R-TypeHints.
6. `app/Models/SpecialistLeave.php` + `database/factories/SpecialistLeaveFactory.php` — جایگزین‌شده با `Leave` از فاز Leave-Migration. تنها ارجاع باقی‌مونده (نگاشت Policy در `AuthServiceProvider`) حذف شد. **جانبی**: `database/seeders/SpecialistSeeder.php` (تنها مصرف‌کننده‌ی واقعی مدل قدیمی) به `Leave::factory()` سوییچ شد؛ چون مدل `Leave` تریت `HasFactory` نداشت (با وجود این‌که `LeaveFactory.php` از قبل ساخته شده بود)، این تریت اضافه شد.

**کاندیدهای دیگه‌ی لیست قبلی (`app/Http/Requests/StoreBlogPostRequest.php` روت، `App\Http\Controllers\User\DiscountCodeController`/`DiscountCodeService`، `resources/app/Services/Admin/Specialist/AdminSpecialistService.php`) در ریپوی واقعی اصلاً پیدا نشدن — قبلاً در فازهای دیگه (R-AdminBlog, R-DiscountLogic) یا مستقیماً روی دیسک حذف شده بودن؛ کاری لازم نبود.**

**⭐ کشف کاملاً جدید (خارج از لیست قبلی): یک زیردرخت کامل React یتیم**
با بررسی سراسری `import ... from` معلوم شد ۱۴ فایل (~۲۱۰۰ خط) هیچ‌وقت به دو entry واقعی Vite (`resources/js/admin.jsx`, `app.jsx` طبق `vite.config.js`) وصل نبودن — فقط به همدیگه import داشتن (زنجیره‌ی خودبسته):
```
resources/js/layouts/AdminLayout.jsx
resources/js/Components/Admin/AdminDashboard.jsx
resources/js/Components/Admin/Sidebar.jsx
resources/js/Components/Admin/Reports/{ReportDashboard,FinancialReports,SpecialistReports}.jsx
resources/js/Components/Admin/Dashboard/{CustomerReports,PopularServices,RevenueCharts,SpecialistStats}.jsx
resources/js/Components/Admin/Schedule/{Index,ManageHolidays,ManageLeaves,ManageWorkSchedule}.jsx
```
هیچ Blade ای هم root element (`getElementById`) مخصوص این‌ها نداشت. **نکته‌ی جانبی**: `AdminLayout.jsx` با مسیر اشتباه `@/components/admin/Sidebar` (حروف کوچک) به `Sidebar` اشاره می‌کرد؛ مسیر واقعی `Components/Admin/Sidebar.jsx` (حروف بزرگ) بود — همون الگوی مستندشده‌ی «باگ حساسیت به حروف»، ولی چون خود `AdminLayout` هم یتیم بود، بی‌اثر مونده بود. بعد از تأیید کاربر، هر ۱۴ فایل حذف شدن.

**⚠️ در آن لحظه دست‌نخورده ماند (به تصمیم صریح کاربر در همون جلسه):** `WorkSchedule` (بک‌اند PHP). با وجود این‌که یکی از ۱۴ فایل یتیم بالا `Schedule/ManageWorkSchedule.jsx` نام داشت، این فقط یک فرانت‌اند React رهاشده و کاملاً بی‌ربط به فیچر فعلی WorkSchedule (PHP) بود — حذفش هیچ تأثیری روی تصمیم اون جلسه («WorkSchedule دست‌نخورده بمونه») نداشت. ✅ **بعداً در ۲۰۲۶-۰۸-۰۷ خود بک‌اند PHP هم کاملاً حذف شد** — به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کامل فیچر WorkSchedule» پایین‌تر نگاه کن.

**⭐ نکته‌ی عملیاتی جدید برای تحویل patch چندجلسه‌ای**: وقتی یک فاز چند کامیت داره و کاربر یک patch حاوی چند کامیت رو در یک جلسه apply/push می‌کنه، جلسه‌ی بعدی نباید دوباره یک patch حاوی *همون* کامیت‌های قبلاً apply‌شده + کامیت‌های جدید بفرسته — `git am` تلاش می‌کنه فایل‌های از قبل حذف‌شده رو دوباره حذف کنه و با خطای «does not exist in index» شکست می‌خوره. **درس**: همیشه اول از کاربر بپرس/چک کن (`git log --oneline -1` روی برنچ فیچر) کدوم کامیت‌ها از قبل apply شدن، و فقط دلتای واقعی جدید رو patch کن.

**فایل‌های این فاز (commit اول):** `app/Http/Requests/Specialist/StoreWithdrawalRequest.php` (حذف)، `app/Services/RefundService.php` (حذف)، `app/Services/ReportService.php` (حذف)، `app/Models/SpecialistLeave.php` (حذف)، `database/factories/SpecialistLeaveFactory.php` (حذف)، `app/Models/Leave.php` (افزودن `HasFactory`)، `app/Providers/AuthServiceProvider.php` (حذف نگاشت قدیمی)، `database/seeders/SpecialistSeeder.php` (سوییچ به `Leave::factory()`)، `routes/api.php` (حذف require)، `routes/api/admin/loyalty.php` (حذف)، سه فایل `.jsx` قدیمی لویالتی/اطلاعیه/گالری (حذف).

**فایل‌های commit دوم:** ۱۴ فایل زیردرخت React یتیم (لیست بالا، همه حذف).

**روش تحویل:** مثل R-TypeHints، چون push مستقیم رد شد (۴۰۳)، از طریق `git format-patch` + `git am`. یک بار تصادم patch (به دلیل نکته‌ی عملیاتی بالا) رخ داد که با ساخت patch جدا برای فقط کامیت دوم رفع شد.

**نتیجه‌ی این بخش از فاز:** ۲۳ فایل حذف‌شده (۹ آیتم مستندشده‌ی قبلی + ۱۴ فایل تازه‌کشف‌شده)، ۴ فایل ویرایش‌شده (`Leave.php`, `AuthServiceProvider.php`, `SpecialistSeeder.php`, `routes/api.php`). `WorkSchedule` در همون لحظه عمداً دست‌نخورده موند (✅ بعداً در ۲۰۲۶-۰۸-۰۷ کاملاً حذف شد).

✅ **بازبینی جدول کنترلرها (همین جلسه انجام شد)**: جدول «نیازی به split ندارند» و بخش Leave-Migration هر دو یک عبارت گمراه‌کننده داشتن («کاندید حذف» برای `AdminSpecialistLeaveController») — در واقع این کنترلر یک فایل جدای قدیمی نبود که باید حذف بشه، بلکه همون فایل *در جا* بازنویسی شده بود. هر دو جا تصحیح شدن؛ چیزی برای حذف فیزیکی این کنترلر باقی نمونده.

---

### ✅ رفع مستقل (۲۰۲۶-۰۸-۰۱): تکمیل مسیر «پرداخت امن / ۲FA» + پاک‌سازی کد مرده‌ی React مرتبط

**زمینه:** جلسه‌ی قبلی (ممیزی فایل‌های `.jsx` باقی‌مانده حین R-Cleanup-DeadCode) کشف کرده بود که کل مسیر «پرداخت امن/۲FA» (`SecurePaymentController`, `payments.secure.*`) کاملاً غیرقابل‌دسترسی و شکسته بود، و تصمیمش («کامل‌کردن» یا «حذف کامل») به این جلسه موکول شده بود. کاربر گزینه‌ی **الف (کامل کردن)** رو انتخاب کرد؛ برای فرانت‌اند هم صراحتاً خواست از React استفاده نشه و به‌جاش Blade + vanilla JS (هم‌راستا با کل پروژه) پیاده‌سازی بشه.

دسترسی شبکه به GitHub این جلسه هم کاملاً باز بود؛ کلون مستقیم `develop` انجام شد.

**🔴 کشفیات تازه، فراتر از آنچه جلسه‌ی قبل مستند کرده بود:**

1. **ستون `two_factor_enabled` روی جدول `users` اصلاً وجود نداشت.** `TwoFactorAuthService::isEnabled()/enable()/disable()` و `SecurityController::dashboard()` از روز اول این ستون رو می‌خوندن/می‌نوشتن، ولی هیچ migration ای هیچ‌وقت اضافه‌اش نکرده بود. خوندنش فقط `null` (یعنی همیشه «غیرفعال») برمی‌گردوند (magic getter Eloquent، نه خطا)، ولی نوشتنش (`enable()`/`disable()`) یک `SQLSTATE... Unknown column` فتال می‌داد — یعنی حتی اگه بقیه‌ی مسیر درست می‌شد، هیچ‌کس هیچ‌وقت نمی‌تونست واقعاً ۲FA رو روشن کنه.
2. **هر سه ویوی `auth/2fa/{index,setup,confirm}.blade.php` اصلاً وجود نداشتن** — یعنی حتی صفحه‌ی تنظیمات روشن/خاموش کردن ۲FA هم هیچ‌وقت رندر نمی‌شد؛ کاربر عادی هیچ راهی برای فعال‌سازی ۲FA نداشت، صرف‌نظر از اینکه مسیر پرداخت امن رو فیکس کنیم یا نه. این یک وابستگی مسدودکننده‌ی سخت بود (بدون این، فیکس بقیه‌ی مسیر عملاً بی‌فایده می‌موند) که در ممیزی جلسه‌ی قبل اصلاً کشف نشده بود.
3. **میدل‌ور با دو نام مختلف و هیچ‌کدوم ثبت‌نشده**: `routes/web/payments.php` از `2fa.enabled` استفاده می‌کرد، `routes/api/user/payments.php` از `verified.2fa` — نه در `bootstrap/app.php` نه هیچ‌جای دیگه، هیچ‌کدوم به‌عنوان alias ثبت نشده بودن؛ فراخوانی هر روت پشت این میدل‌ورها فتال ارور «Target class does not exist» می‌داد.
4. **روت `initiate` بدون پارامتر `{booking}` تعریف شده بود** (`POST /initiate`) در حالی که خود متد کنترلر `initiate(Request $request, Booking $booking)` امضا داشت — فراخوانی‌اش تضمین‌شده فتال ارور «too few arguments» می‌داد؛ یعنی این مسیر حتی اگه میدل‌ور/ستون هم درست بودن، باز کار نمی‌کرد.
5. **`SecurePaymentService::verifyPayment(string $referenceId, $amount)` مبلغ رو مستقیم از `$request->all()` کنترلر می‌گرفت** — یک باگ امنیتی مالی واقعی (اعتماد به مبلغی که کاربر می‌تونست در body درخواست خودش دستکاری کنه)، درست وسط مسیری که قراره «امن‌تر» از پرداخت معمولی باشه.
6. **روت POST `verify` (که ریدایرکت برمی‌گردوند) اشتباهاً در فایل API/JSON بود** — یک fetch/AJAX از اونجا فقط یک Response شیء ریدایرکت می‌گرفت، نه ناوبری واقعی مرورگر؛ باید مثل بقیه‌ی مسیرهای پرداخت پروژه (`payment.process`/`payment.wallet`) یک فرم HTML واقعی با POST باشه.
7. **هیچ چک مالکیتی (`authorize('pay', $booking)`) روی هیچ‌کدوم از متدهای کنترلر نبود** — رفرنس‌ها (`reference_id`) رشته‌های ۳۲ کاراکتری تصادفی با آنتروپی بالان (عملاً غیرقابل‌حدس)، ولی نبود چک مالکیت هنوز یک شکاف واقعی امنیتی بود که باید بسته می‌شد.

**راه‌حل‌ها (Backend):**
- Migration جدید: `users.two_factor_enabled` (boolean, default false) + به‌روزرسانی `$fillable`/`$casts` مدل `User`.
- میدل‌ور جدید `App\Http\Middleware\EnsureTwoFactorVerifiedForPayment` (alias یکسان `2fa.enabled`، حالا هم در وب هم در API استفاده می‌شه): کاربر بدون ۲FA فعال رو به `security.2fa` هدایت می‌کنه؛ کاربر با ۲FA فعال ولی هنوز تایید‌نشده در همین session رو (با فرستادن یک کد تازه از طریق `TwoFactorAuthService` موجود) به صفحه‌ی OTP جدید می‌فرسته. بعد از تایید موفق (که از همون روت‌های از قبل درست `security.2fa.verify`/`resend` میاد)، همون فلگ session (`2fa_verified`) که `TwoFactorController::verify()` از قبل درست ست می‌کرد، حالا واقعاً مصرف می‌شه (قبلاً هیچ‌جا خونده نمی‌شد).
- `SecurePaymentController`: `authorize('pay', $booking)` به تمام اکشن‌ها اضافه شد؛ متد جدید `showOtp()`؛ حذف متد مرده‌ی `handlePaymentError()`؛ بعد از تایید موفق، `payment_reference`/`payment_details` حالا روی خود `Booking` هم ست می‌شن (قبلاً فقط روی مدل `Payment` ست می‌شدن — دقیقاً همون گپی که در R-Observers هم به‌صورت جداگانه کشف شده بود).
- `SecurePaymentService::verifyPayment()`: امضا به `verifyPayment(string $referenceId): array` تغییر کرد — مبلغ دیگه هیچ‌وقت از کلاینت گرفته نمی‌شه، همیشه از رکورد سرور-ساخته‌ی `Payment` خونده می‌شه؛ بلاب رمزنگاری‌شده‌ی `card_data` فقط به‌عنوان یک امضای ضدِدستکاری استفاده می‌شه، نه منبع مبلغ. `expired_at` هم موقع ساخت ست می‌شه.
- Routes: `verify` (POST، ریدایرکت‌محور) از فایل API به فایل وب منتقل شد؛ `initiate` پارامتر `{booking}` گرفت.

**راه‌حل‌ها (Frontend — Blade + vanilla JS، بدون React):**
- `resources/views/payments/secure/{checkout,otp,verify,result}.blade.php` — چهار صفحه‌ی جدید، تم طلایی مشتری، هماهنگ با کانوانسیون `payment/show.blade.php` (فرم واقعی + `fetch` ساده، بدون کتابخونه‌ی خارجی).
- `resources/views/auth/2fa/{index,setup,confirm}.blade.php` — سه ویوی از پیش‌گم‌شده که وجودشون پیش‌نیاز سخت کل قابلیت بود؛ عمداً کوچک و محدود به toggle نگه داشته شدن (نه یک داشبورد امنیتی کامل که هیچ‌جا درخواست نشده بود).
- ورودی‌های تازه‌اضافه‌شده به UI: لینک ثانویه‌ی «پرداخت امن با تایید دو مرحله‌ای» کنار دکمه‌ی پرداخت معمولی در `bookings/show.blade.php`، و کارت وضعیت/لینک تنظیمات ۲FA در `profile/edit.blade.php`.

**پاک‌سازی کد مرده‌ی React مرتبط (دستور صریح کاربر):**
- `resources/js/Components/Common/SecureForm.jsx` حذف شد — فرانت‌اند React قدیمی همین مسیر پرداخت امن، از قبل کاملاً جایگزین‌شده با ویوهای Blade بالا؛ هیچ Blade ای دیگه `id="secure-form"` نداشت.
- `resources/js/Components/BookingActions.jsx` حذف شد — mount-point مرده از فاز R-DiscountLogic (وقتی `bookings/show.blade.php` به Blade خالص مهاجرت کرد)، importش در `app.jsx` فراموش شده بود.
- `resources/js/app.jsx`: هر دو mount مرده بالا حذف شدن؛ ضمناً یک باگ **فعال** کشف/فیکس شد — import مسیر `AnnouncementBanner` با حروف کوچک (`./components/...`) نوشته شده بود در حالی که فایل واقعی زیر `./Components/...` (حرف بزرگ) است؛ روی Windows/XAMPP بی‌صدا کار می‌کرد، روی Linux/production ۴۰۴ می‌داد — دقیقاً همون الگوی همیشگی حساسیت به حروف، این‌بار در یک مسیر import جاوااسکریپت نه نام فایل Blade/PHP. `AnnouncementBanner` واقعاً استفاده می‌شه (`layouts/app.blade.php` نقطه‌ی mount داره)، پس فیکس شد نه حذف.
- **عمداً دست‌نخورده موند** (در همین جلسه): ~~`Components/Auth/TwoFactorAuth.jsx`~~ ✅ **حذف شد (۲۰۲۶-۰۸-۰۲)** — به بخش «⭐ رفع مستقل ... تکمیل ۹ کاندید» پایین‌تر، آیتم ۲، نگاه کن.

**تحویل:** به‌دلیل push مستقیم مسدود (۴۰۳)، دو کامیت جدا (یکی برای تکمیل فیچر، یکی برای پاک‌سازی کد مرده‌ی مرتبط) روی برنچ `feat/secure-payment-2fa-complete` ساخته و به‌صورت patch تحویل داده شدن؛ اعمال‌شدن بدون conflict روی یک کلون تازه‌ی `develop` تست شد و `php -l` روی تمام فایل‌های PHP تغییریافته صفر خطا نشون داد.

⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` باید اجرا بشه تا ستون جدید `users.two_factor_enabled` ساخته بشه.

**فایل‌های commit اول (تکمیل فیچر):**
- `database/migrations/2026_08_01_000000_add_two_factor_enabled_to_users_table.php` (جدید)
- `app/Models/User.php`
- `app/Http/Middleware/EnsureTwoFactorVerifiedForPayment.php` (جدید)
- `bootstrap/app.php`
- `routes/web/payments.php`, `routes/api/user/payments.php`
- `app/Http/Controllers/User/SecurePaymentController.php`
- `app/Services/SecurePaymentService.php`
- `resources/views/payments/secure/{checkout,otp,verify,result}.blade.php` (جدید)
- `resources/views/auth/2fa/{index,setup,confirm}.blade.php` (جدید)
- `resources/views/bookings/show.blade.php`, `resources/views/profile/edit.blade.php`

**فایل‌های commit دوم (پاک‌سازی):**
- `resources/js/Components/BookingActions.jsx` (حذف)، `resources/js/Components/Common/SecureForm.jsx` (حذف)
- `resources/js/app.jsx`

**کاندید جدید برای `R-Cleanup-DeadCode`:** ~~`resources/js/Components/Auth/TwoFactorAuth.jsx` + `resources/views/auth/two-factor-auth.blade.php`~~ ✅ **حذف شد (۲۰۲۶-۰۸-۰۲)**.

---

### ✅ رفع مستقل (۲۰۲۶-۰۸-۰۱، بلافاصله بعد از تکمیل مسیر بالا): باگ بحرانی — کد OTP هیچ‌وقت واقعاً تایید نمی‌شد + تاخیر ۲۱-۲۹ ثانیه‌ای هر ارسال کد

**کشف:** بلافاصله بعد از تکمیل مسیر پرداخت امن/۲FA بالا، کاربر تست دستی واقعی انجام داد و `laravel.log` واقعی فرستاد. لاگ نشون داد **هیچ‌کدوم از تلاش‌های تایید کد ۲FA موفق نمی‌شدن** — همیشه `2FA code expired or not found`، حتی چند ثانیه بعد از تولید کد (خیلی زودتر از انقضای ۲ دقیقه‌ای مستندشده در کد). علاوه بر این، هر بار تولید/ارسال‌مجدد کد، ۲۱ تا ۲۹ ثانیه طول می‌کشید.

**ریشه‌یابی (هر دو در `App\Services\TwoFactorAuthService`، کد از قبل در پروژه بود ولی تا این لحظه هیچ‌جا استفاده نمی‌شد، پس هیچ‌وقت این باگ‌ها لو نرفته بودن):**

1. **🔴 بحرانی‌ترین: کد در `Cache::put()` ذخیره می‌شد.** `.env.example` پروژه `CACHE_STORE=array` داره — این درایور فقط **در حافظه‌ی همون یک درخواست** زندگی می‌کنه و به‌محض پایان درخواست از بین می‌ره. چون تولید کد و تایید کد همیشه دو درخواست HTTP جدا هستن، کش هر بار خالی بود؛ این عملاً همون الگوی «باید یک worker/process پابرجا در پس‌زمینه باشه» بود که قبلاً برای صف (`queue:work`) و شیدول (`schedule:work`) هم مستند شده بود، این‌بار برای کش.
2. **🔴 باگ نوع داده‌ی پنهان (حتی اگه کش هم کار می‌کرد، بازم شکست می‌خورد):** کد با `rand()` به‌صورت عدد صحیح تولید/ذخیره می‌شد، ولی با `$request->code` (همیشه رشته) با عملگر سخت‌گیرانه‌ی `===` مقایسه می‌شد — `123456 === "123456"` در PHP همیشه `false`ه.
3. **🔴 تاخیر ۲۱-۲۹ ثانیه‌ای:** `generateCode()` تماس با Kavenegar (`SMSService::sendTemplate()`) رو مستقیم و **synchronous** انجام می‌داد — دقیقاً همون کلاس باگی که قبلاً باعث کندی ۳۰+ ثانیه‌ای لاگین شده بود و با `SendLoginVerificationCodeJob` فیکس شده بود؛ چون این سرویس تا حالا orphan بود، هیچ‌وقت این فیکس روش اعمال نشده بود.

**فیکس:**
- ذخیره‌ی کد از `Cache` به دو ستون اختصاصی و پایدار روی جدول `users` (`two_factor_code`, `two_factor_code_expires_at`) منتقل شد — دقیقاً هم‌الگو با مکانیزم از قبل اثبات‌شده‌ی OTP لاگین (`login_verification_code`/`login_verification_code_expire_at`)، ولی در ستون‌های **جدا** تا این دو مسیر کد همدیگه رو overwrite نکنن.
- مقایسه‌ی کد به `hash_equals()` (timing-safe) با هر دو طرف رشته تغییر کرد.
- `App\Jobs\Send2faVerificationCodeJob` (جدید، هم‌الگو با `SendLoginVerificationCodeJob`) ساخته شد؛ `generateCode()` حالا فقط کد رو سریع در دیتابیس ذخیره می‌کنه و ارسال SMS رو صف می‌کنه.
- هیچ تغییری در `TwoFactorController` یا کد مسیر پرداخت امن لازم نبود — امضای `generateCode()`/`verify()` عیناً حفظ شد.

⚠️ **دو یافته‌ی جانبی در همون لاگ، غیرقابل‌رفع از سمت کد:**
- خطای Kavenegar `501: امکان ارسال پیامک فقط به شماره صاحب حساب داده شده است` — محدودیت حساب/سندباکس Kavenegar، نیاز به بررسی در پنل Kavenegar داره.
- `Failed to connect to api.kavenegar.com` مکرر — همون مشکل قبلاً مستندشده‌ی عدم دسترسی شبکه‌ی لوکال به Kavenegar.

⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` (ستون‌های جدید) + `php artisan queue:work` باید باز باشه تا SMS واقعاً ارسال بشه (وگرنه Job فقط توی جدول `jobs` می‌مونه) — دقیقاً همون پیش‌نیاز مستندشده‌ی `wallet:settle-pending`/SMS لاگین/یادآوری نوبت.

**فایل‌های این نشست:**
- `database/migrations/2026_08_01_010000_add_two_factor_code_to_users_table.php` (جدید)
- `app/Models/User.php`
- `app/Jobs/Send2faVerificationCodeJob.php` (جدید)
- `app/Services/TwoFactorAuthService.php`

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۰۲ تا ۰۸-۰۳): تکمیل ۹ کاندید/کار باز پراکنده‌ی این سند + بازطراحی کامل پیش‌پرداخت/تخفیف

**زمینه:** کاربر تمام کاندیدهای پراکنده‌ی «کار باز»/«کاندید حذف»/«فیکس نشد» که در بخش‌های مختلف همین سند مستند شده بودن رو در یک لیست ۱۰تایی جمع کرد و به ترتیب اولویت انجام شدن. دسترسی شبکه به GitHub این جلسه هم کاملاً باز بود؛ کلون مستقیم `develop` انجام شد. برخلاف جلسات قبلی که PHP CLI معمولاً در دسترس نبود، این‌بار **PHP 8.3 + Composer (با تمام وابستگی‌ها، شامل dev) + یک دیتابیس SQLite واقعی نصب و راه‌اندازی شد** — یعنی برای اولین بار در این پروژه، به‌جای صرفاً `php -l` (فقط syntax)، کد واقعاً از طریق کل پایپ‌لاین لاراول (روتینگ، میدل‌ور، کنترلر، سرویس، ویو) با درخواست‌های HTTP واقعی و کوئری‌های واقعی دیتابیس تست شد، برای هر ۹ آیتم این لیست.

### ۱) ✅ جداول «عملکرد متخصصین»/«خدمات پرطرفدار» + نمودار روند درآمد در اکسل
از بخش R-Observers مونده بود که این دو بلوک (که فقط توی PDF بودن) به شیت‌های *موجود* اکسل اضافه بشن (نه شیت جدا) + یک نمودار.
- `App\Exports\Concerns\AppliesReportSheetStyle`: متد `styleReportSheet()` پارامتر اختیاری `$headerRow` گرفت (برای استایل‌دهی جدول‌هایی که ردیف ۱ شروع نمی‌شن؛ backward-compatible چون پیش‌فرضش ۱ هست) + متد جدید `writeTitledSubTable()` — یک بلوک عنوان‌دار (تیتر merge‌شده + هدر + ردیف‌ها یا پیام «داده‌ای وجود ندارد») می‌سازه و همون استایل جدول اصلی رو بهش اعمال می‌کنه.
- `App\Exports\ReportsExport` (شیت «روند درآمد»): حالا `specialists`/`services` (همون Collectionهایی که از قبل برای PDF ساخته می‌شدن، توسط `AdminReportService::buildExportData()`) رو می‌گیره و در `registerEvents()` دقیقاً زیر جدول روند درآمد، با یک ردیف خالی فاصله، دو جدول «عملکرد متخصصین» و «خدمات پرطرفدار» رو می‌نویسه (همون فیلتر `bookings>0`/`bookings_count>0` که PDF داره، برای هماهنگی دو فرمت).
- `implements WithCharts`: یک نمودار میله‌ای روند درآمد اضافه شد، با `DataSeriesValues`ی که به رنج واقعی سلول‌های همون شیت (`'روند درآمد'!$A$2:$A$N`/`$C$2:$C$N`) اشاره می‌کنه؛ انکر نمودار در `F2:N22` (ستون F به بعد) قرار گرفت تا هیچ‌وقت با جدول‌های عمودی (فقط ستون‌های A-D) تداخل نکنه. وقتی هیچ ردیف روند درآمدی وجود نداره (بازه‌ی خالی)، نمودار اصلاً ساخته نمی‌شه (رنج معکوس/نامعتبر جلوگیری شد).
- `App\Exports\AdminReportExport` و `GeneratePdfReportJob::generateExcel()` برای پاس دادن `specialists`/`services` به‌روزرسانی شدن.

**تست واقعی:** با `composer install` کامل (پکیج‌های `maatwebsite/excel`/`phpoffice/phpspreadsheet` واقعی نصب شدن)، یک خروجی xlsx واقعی ساخته و بررسی شد — چیدمان ردیف‌ها، فیلترشدن ردیف‌های صفر، و XML واقعی نمودار (رفرنس شیت، انکر F2:N22) همه با باز کردن دستی فایل تأیید شدن؛ حالت داده‌ی خالی هم بدون کرش/نمودار نامعتبر مدیریت شد.

### 🔴 باگ جانبی کشف‌شده حین این آیتم: رگرسیون فایل‌نام حساس به حروف (چندمین نمونه‌ی همین الگو)
حین `composer install`، اخطار PSR-4 نشون داد `app/Http/Requests/User/Security/Checkpasswordstrengthrequest.php` (حروف اشتباه) وجود داره در حالی که کلاس داخلش `CheckPasswordStrengthRequest` است و `SecurityController` بهش وابسته‌ست — دقیقاً همون فایلی که یک‌بار قبل‌تر (R-Events) با نام درست فیکس شده بود، ولی ظاهراً بعداً که به namespace تودرتوی `User\Security` منتقل شده، دوباره با نام غلط ساخته شده. با `git mv` به نام درست تغییر کرد. **روی Windows بی‌صدا کار می‌کنه، روی Linux فتال ارور «Class not found» می‌ده** — دقیقاً همون الگوی همیشگی.

### 🔴 دو باگ بحرانی، کشف‌شده از یک `laravel.log` واقعی که کاربر بعد از اعمال پچ آیتم ۱ فرستاد
1. **اکسل اصلاً ساخته نمی‌شد**: `ReportsExport::__construct(array $data, ...)` تایپ‌هینت `array` داشت، ولی `AdminReportService::getRowsForType()`/`dailyRevenue()`/`weeklyRevenue()`/`monthlyRevenue()` همه `Collection` برمی‌گردونن و `AdminReportExport` هم همون رو دست‌نخورده پاس می‌ده — یک **باگ از قبل موجود** (نه چیزی که در همین جلسه ایجاد شده باشه؛ در بررسی تأیید شد قبل از این جلسه هم همین تایپ‌هینت وجود داشت). هر export اکسل با `TypeError` همون لحظه‌ی اول fail می‌شد و `report_export` روی `failed` می‌موند. فیکس: تایپ‌هینت به `Collection` تغییر کرد؛ `collection()`/`headings()` متناسب به‌روزرسانی شدن (`$this->data->first()` به‌جای `$this->data[0]`).
2. **دانلود PDF/اکسل خطای فتال می‌داد** (با اینکه PDF درست ساخته شده بود): `AdminReportExportController::download(): RedirectResponse` تایپ‌هینت خروجی فقط `RedirectResponse` بود، ولی مسیر موفقیت واقعی `Storage::disk('local')->download(...)` یک `StreamedResponse` برمی‌گردونه (نه ریدایرکت). خروجی متد به `RedirectResponse|StreamedResponse` تغییر کرد.

هر دو با نصب واقعی composer و ساخت واقعی یک `ReportsExport` با یک Collection واقعی (مطابق همون چیزی که production پاس می‌ده) تأیید و فیکس شدن.

### ۲) ✅ حذف کد مرده‌ی React یتیم `TwoFactorAuth.jsx`
قبل از حذف تأیید شد (نه فقط فرض قبلی مستندشده): هیچ روتی به `resources/views/auth/two-factor-auth.blade.php` اشاره نمی‌کنه، هیچ کنترلری این ویو رو return نمی‌کنه، و تنها جایی که `id="two-factor-auth"` (نقطه‌ی mount) وجود داشت همون فایل Blade یتیم بود. `resources/js/Components/Auth/TwoFactorAuth.jsx`، `resources/views/auth/two-factor-auth.blade.php`، و mount مربوطه در `app.jsx` حذف شدن (mount واقعی `announcement-banner` دست‌نخورده موند).

### ۳) ✅ فیکس فیلتر دسته‌بندی در `ServiceController::index()`
تأیید شد `resources/views/services/index.blade.php` لینک‌های pill دسته‌بندی رو با `route('services.index', ['category' => $category->id])` واقعی می‌سازه و از `request('category')` فقط برای استایل «فعال» استفاده می‌کنه — ولی `index()` این مقدار رو اصلاً نمی‌خوند. فیکس:
```php
$services = BeautyService::with('category')
    ->when(request('category'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
    ->paginate(12)
    ->withQueryString();
```
`withQueryString()` هم اضافه شد تا فیلتر دسته‌بندی موقع pagination هم حفظ بشه (که خودش هم قبلاً وجود نداشت). با یک دیتابیس SQLite واقعی چهار حالت (بدون فیلتر، دو دسته‌بندی مختلف، رشته‌ی خالی) تست و تأیید شد.

### ۴) ✅ بررسی Storage Symlink روی Windows/XAMPP — کد سالم بود، مشکل محیطی — رفع شد توسط کاربر (۲۰۲۶-۰۸-۰۷)
بررسی کد نشون داد **هیچ باگ کدی وجود نداره**: مسیر seed‌شده (`gallery/imageN.jpg`) و مسیر آپلود واقعی هر دو دقیقاً از یک ساختار (دیسک `public`، پوشه‌ی `gallery/`) استفاده می‌کنن؛ `config/filesystems.php` استاندارده؛ `public/storage` در `.gitignore` هست (طبق معمول لاراول، یعنی این symlink هیچ‌وقت commit نمی‌شه و باید روی هر محیط جدا ساخته بشه). چون خطای گزارش‌شده `403` بود (نه `404`)، یعنی فایل فیزیکی وجود داره ولی دسترسی رد می‌شه — دقیقاً الگوی symlink خراب/permission ناسازگار. **این آیتم کد نداره، باید دستی توسط کاربر انجام بشه:**
```powershell
# ترمینال Administrator:
cd C:\xampp\htdocs\Beauty-Salon
rmdir public\storage   # اگه symlink قبلی خراب/ناقصه
php artisan storage:link
```
و بعد تأیید کنه `public\storage` واقعاً symbolic link باشه + permission پوشه‌ی `storage\app\public\gallery` با بقیه‌ی زیرپوشه‌ها یکی باشه. **✅ رفع شد (۲۰۲۶-۰۸-۰۷)** — کاربر این دستورات رو با ترمینال Administrator اجرا کرد؛ تصاویر seed‌شده‌ی قدیمی گالری (`gallery/image2.jpg` تا `image8.jpg`) الان بدون خطای ۴۰۳ لود می‌شن.

### ۵) ✅ حذف `SpecialistWallet::deductCancellationFee()`
با `grep -rn` تأیید شد هیچ‌جای دیگه‌ای در `app/` صدا زده نمی‌شه (طبق تصمیم نهایی معماری جریمه‌ی لغو، جریمه‌ی متخصص از مبلغ برگشتی به مشتری کم می‌شه، نه کسر جدا از کیف‌پول متخصص). حذف شد.

### ۶) بررسی نسخه‌ی تکراری `AdminSpecialistService.php` در `resources/app/...` — چیزی برای حذف نبود
با `find`/`git log --all` تأیید شد این مسیر (`resources/app/Services/Admin/Specialist/AdminSpecialistService.php`) **هیچ‌وقت در گیت commit نشده** و پوشه‌ی `resources/app` اصلاً در ریپو وجود نداره (فقط `css/js/lang/vendor/views`). یعنی این فایل تکراری فقط یک باقی‌مانده‌ی محلی روی دیسک کاربر بوده (از یک zip قدیمی، جلسه‌ی ۲۰۲۶-۰۷-۲۵)، نه چیزی که گیت ردیابی کنه — نیازی به پچ نبود.

### ۷) ✅ مهاجرت `persian-date` در `bookings/reschedule.blade.php` به jcal خودکفا
آخرین صفحه‌ی باقی‌مونده‌ای که هنوز از `unpkg.com/persian-date` استفاده می‌کرد (همونی که در بخش «رفع مستقل: سه باگ عملکردی» به‌عنوان کاندید فیکس آینده ذکر شده بود). `formatPersianDate()`/`formatDayLabel()` حالا از همون الگوریتم `gregorianToJalali()`ی خودکفایی استفاده می‌کنن که در `bookings/create.blade.php` (و بقیه‌ی صفحات پروژه) از قبل استفاده می‌شه — فقط منطق فرمت تاریخ عوض شد، fetch/date-grid/time-grid/submit دست‌نخورده موندن. با Node، تبدیل روی نقاط مرجع نوروز (۲۰۲۶-۰۳-۲۱ → ۱۴۰۵/۰۱/۰۱، ۲۰۲۵-۰۳-۲۱ → ۱۴۰۴/۰۱/۰۱) و روز هفته تست و تأیید شد.

### ۸) 🟡 محدودیت‌های Kavenegar (کد ۴۲۷/۵۰۱) — کاملاً عملیاتی، بدون تغییر کد
- **کد ۴۲۷** («نیازمند سرویس پیشرفته»): مخصوص متد Lookup/Verify (که پروژه برای OTP استفاده می‌کنه)؛ نیاز به فعال‌سازی «سرویس پیشرفته» در پنل کاوه‌نگار + درخواست شماره‌ی بین‌المللی/اختصاصی از واحد فروش کاوه‌نگار.
- **کد ۵۰۱** («فقط شماره‌ی صاحب حساب»): محدودیت معمول حساب‌های آزمایشی/تست‌نشده‌ی کاوه‌نگار؛ با کامل‌کردن/شارژ/تأیید حساب برطرف می‌شه.
هیچ‌کدوم به کد پروژه ربطی ندارن؛ `SMSService` از قبل مدیریت خطای این موارد رو درست انجام می‌ده (catch + log، بدون کرش اپ).

### ۹) ✅ `R-AdminDiscountCode` — پنل مدیریت مستقل کد تخفیف توسط ادمین
طبق پلن مستندشده در بخش «فیچرهای برنامه‌ریزی‌شده» (بالای این سند)، کامل پیاده‌سازی شد:
- `Admin\DiscountCode\AdminDiscountCodeController` (index/create/store/edit/update/destroy/preview) — نازک، همه‌چیز به سرویس واگذار می‌شه.
- `Services\Admin\DiscountCode\AdminDiscountCodeService` — pagination، stats، store/update/destroy، و `preview()`. تمام محاسبات تخفیف از همون `App\Services\Discount\DiscountCalculator` موجود (R-DiscountLogic) استفاده می‌کنه — طبق الزام صریح پلن، فرمول دوباره بازنویسی نشد.
- `Requests\Admin\DiscountCode\PreviewDiscountCodeRequest` (جدید) پشت یک ویجت پیش‌نمایش زنده در فرم ساخت: ادمین نوع/مقدار/سقف + یک مبلغ نمونه وارد می‌کنه، یک GET کوچیک واقعاً از `DiscountCalculator` جواب می‌گیره. `StoreDiscountCodeRequest`/`UpdateDiscountCodeRequest` (که در R-AdminForms ساخته شده بودن) عیناً استفاده شدن.
- Views: `admin/discount-codes/{index,create,edit}.blade.php`. فرم ویرایش **عمداً فقط** `is_active`/`expires_at`/`max_uses` رو باز می‌ذاره (چون `UpdateDiscountCodeRequest` از قبل همینو محدود کرده — کد/نوع/مقدار/سقف/کاربر بعد از ساخت غیرقابل‌تغییرن تا فرمول محاسبه‌شده‌ی نوبت‌های قبلی هیچ‌وقت پس‌رونده عوض نشه) + یک پیش‌نمایش ثابت سمت سرور؛ فرم ساخت پیش‌نمایش زنده‌ی AJAX داره. هر دو از همون jcal خودکفای پروژه برای `expires_at` استفاده می‌کنن (فقط تاریخ؛ معتبر تا ۲۳:۵۹:۵۹ همون روز، بدون نیاز به ورودی ساعت جدا چون برخلاف اطلاعیه‌ها، ساعت دقیق انقضای کد تخفیف مهم نیست).
- `destroy()` از حذف فیزیکی کد استفاده‌شده (`used_count > 0`) امتناع می‌کنه (سابقه‌ی مالی/audit) — تنها مسیر بازنشستگی، غیرفعال‌سازی (`is_active=false`) هست.
- Routes: `routes/admin/discount-codes.php`، wire شده در گروه ادمین `routes/web.php`.
- لینک سایدبار در `layouts/admin.blade.php` (بخش «سیستم‌ها»، بعد از «اطلاعیه»)، پشت `@permission('view-discount-codes')` — هم‌الگو با لویالتی/وبلاگ/گالری/اطلاعیه‌ها. کاربر `is_admin=true` این چک رو دور می‌زنه و بلافاصله می‌بینتش؛ برای نقش‌های staff، این permission باید از `/admin/permissions` ساخته و assign بشه.

**تست واقعی و کامل:** با composer کامل (dev هم) + یک SQLite واقعی + migration های واقعی، کل مسیر HTTP (auth+permission+routing+controller+service+view) برای `index`/`create`/`edit`/`preview` (همه ۲۰۰)، `store`/`update`/`destroy` (ریدایرکت درست + وضعیت واقعی دیتابیس)، گارد حذف کد استفاده‌شده (exception درست)، و اعتبارسنجی کد تکراری (ریدایرکت با خطا، نه ۵۰۰) تأیید شد.

---

## ⭐⭐ فیچر بزرگ (۲۰۲۶-۰۸-۰۳): بازطراحی کامل پیش‌پرداخت نوبت + منطق تخفیف (بحث چندمرحله‌ای با کاربر)

**زمینه‌ی کشف:** حین بررسی لاگ کاربر برای آیتم ۱، کاربر گزارش داد در صفحه‌ی «تأیید و پرداخت نوبت» (`/bookings/confirm`)، اعمال کد تخفیف باعث می‌شد مبلغ پیش‌پرداخت **افزایش** پیدا کنه (۵۰,۰۰۰ → ۶۷,۵۰۰) به‌جای کاهش.

### مرحله‌ی ۱ — ریشه‌یابی اولیه: دو باگ ترکیبی
1. **🔴 باگ قیمت‌گذاری بحرانی، مستقل از مشکل تخفیف (روی همه‌ی نوبت‌های تا الان اثر گذاشته)**: `BookingService::createBooking()` این خط رو داشت: `$this->calculatePrepayment(self::MINIMUM_PREPAYMENT, $discountCode)` — یعنی به‌جای قیمت واقعی خدمت، عدد ثابت `۵۰,۰۰۰` رو *به‌جای قیمت خدمت* پاس می‌داد! چون فرمول `calculatePrepayment` بود `max(۵۰۰۰۰, قیمت×۳۰٪)`، پاس دادن ۵۰,۰۰۰ به‌عنوان «قیمت» همیشه `max(۵۰۰۰۰, ۱۵۰۰۰)=۵۰۰۰۰` می‌داد — یعنی **پیش‌پرداخت واقعی هر نوبتی که تا حالا ثبت شده، همیشه دقیقاً ۵۰,۰۰۰ بوده، فارغ از قیمت واقعی خدمت.** `BeautyService` اصلاً توی این متد fetch نمی‌شد.
2. `BookingReservationController::confirm()` هم `$prepaymentAmount = 50000` رو مستقیم هاردکد کرده بود (تصادفاً با باگ ۱ همخونی داشت). Endpoint پیش‌نمایش تخفیف (`resolveBaseAmount()`، از R-DiscountLogic) از قبل درست قیمت واقعی خدمت رو حساب می‌کرد (۷۵,۰۰۰ برای خدمت ۲۵۰,۰۰۰) — یک عدد درست که با نمایش اشتباه‌هاردکدشده‌ی ۵۰,۰۰۰ ناهماهنگ بود، پس اعمال ۱۰٪ تخفیف روی ۷۵,۰۰۰ (=۶۷,۵۰۰) نسبت به ۵۰,۰۰۰ افزایش به‌نظر می‌رسید.

فیکس اولیه: `BeautyService` به `BookingService` inject شد (هم‌الگوی inject-model-as-repository که برای Specialist/Booking/DiscountCode از قبل هست)، قیمت واقعی خدمت fetch و پاس داده شد؛ `confirm()` هم به همون فرمول وصل شد.

### مرحله‌ی ۲ — بحث بیزینسی: آیا پیش‌پرداخت باید همیشه ثابت (۵۰,۰۰۰) بمونه؟
کاربر پرسید: مگه قرار نبود مشتری‌ها همیشه یک پیش‌پرداخت ثابت بدن (نه درصدی)، و باقیش رو موقع نوبت نقد به متخصص بدن؟ در پاسخ، این تحلیل مقایسه‌ای انجام شد:
- **مدل ثابت**: برای خدمات گرون، پیش‌پرداخت فقط چند درصد کوچیک از قیمته → تعهد مشتری کمه، و **مهم‌تر**: چون جریمه‌ی لغو (`customer_cancellation_fee_percentage`/`specialist_cancellation_penalty_percentage`) خودش درصدی از همون پیش‌پرداخته، برای نوبت‌های گرون جریمه عملاً بی‌اثر می‌مونه (حداکثر جریمه‌ی ممکن همیشه یک عدد کوچیک ثابت می‌مونه، فارغ از اینکه چقدر وقت متخصص رزرو شده بوده).
- **مدل درصدی**: این مشکل رو نداره (جریمه هم متناسب با ارزش واقعی نوبت بزرگ‌تر می‌شه)، ولی برای خدمات ارزون (که ۳۰٪ش کمتر از حداقله) خودکار به همون رفتار ثابت می‌افته.
- **مشکل مشترک هر دو مدل که کشف شد**: بدون یک سقف، برای خدمتی که قیمتش از حداقل پیش‌پرداخت کمتره (مثلاً خدمت ۳۰,۰۰۰ با حداقل ۵۰,۰۰۰)، پیش‌پرداخت می‌تونست از کل قیمت خدمت بیشتر بشه!

**نتیجه‌ی توافق‌شده:** مدل **درصدی** با یک **سقف** (هیچ‌وقت از قیمت کل خدمت بیشتر نشه) + **کاملاً قابل‌تنظیم توسط ادمین** (نه فقط درصد، حداقل هم).

### مرحله‌ی ۳ — بحث دوم: آیا تخفیف باید از پیش‌پرداخت کم بشه یا از باقی‌مانده؟
بعد از پیاده‌سازی اولیه‌ی مدل درصدی+سقف، کاربر پرسید تخفیف چطور باید کار کنه (پیش‌پرداخت آنلاین رو کم کنه، یا مبلغی که موقع نوبت نقد به متخصص می‌دن؟). با یک مثال عددی مشخص (خدمت ۲۵۰,۰۰۰، پیش‌پرداخت ۷۵,۰۰۰، باقی‌مانده=قیمت−پیش‌پرداخت) نشون داده شد رفتار قبلی (تخفیف از پیش‌پرداخت) یک **باگ منطقی پنهان** داشت: چون `باقی‌مانده = قیمت − پیش‌پرداخت`، کم‌کردن پیش‌پرداخت باعث می‌شد باقی‌مانده دقیقاً به همون اندازه زیاد بشه — یعنی **جمع کل پرداختی مشتری (پیش‌پرداخت+باقی‌مانده) هیچ‌وقت با تخفیف عوض نمی‌شد** (همیشه دقیقاً قیمت کامل خدمت می‌موند)! تخفیف صرفاً محل پرداخت رو جابه‌جا می‌کرد، نه مبلغ واقعی.

**تصمیم نهایی توافق‌شده:** تخفیف از «باقی‌مانده» (مبلغ نقدی موقع نوبت) کم بشه، نه از پیش‌پرداخت آنلاین. پیش‌پرداخت (و در نتیجه مالی/کمیسیون/کیف‌پول) کاملاً دست‌نخورده و بدون تغییر می‌مونه؛ تخفیف واقعاً جمع کل پرداختی مشتری رو کم می‌کنه.

### پیاده‌سازی نهایی

**تنظیمات جدید (Migration + `WalletSetting`):**
- ستون‌های جدید `wallet_settings.prepayment_percentage` (پیش‌فرض ۳۰) و `wallet_settings.minimum_prepayment_amount` (پیش‌فرض ۵۰۰۰۰) — دقیقاً معادل رفتار قبلی، پس اعمال migration به‌تنهایی هیچ رفتاری رو عوض نمی‌کنه تا ادمین صریحاً تغییرش بده.
- `WalletSetting::calculatePrepaymentAmount(float $servicePrice): float` — `min(قیمت_کل, max(حداقل, قیمت×درصد/۱۰۰))`؛ سقف قیمت کل، اولین‌بار اضافه شد.
- فرم جدید «پیش‌پرداخت نوبت» در `admin/wallet/settings.blade.php` (دو فیلد، درصد + حداقل)؛ `UpdateWalletSettingsRequest` قوانین جدید گرفت (هر دو با `MaxPercentage`/`numeric`).

**`BookingService`:**
- `calculatePrepayment()` حالا از `WalletSetting::get()->calculatePrepaymentAmount()` استفاده می‌کنه (فرمول هاردکد ۳۰٪/۵۰۰۰۰ حذف شد).
- `createBooking()`: `BeautyService` واقعاً fetch می‌شه، `prepayment_amount` ذخیره‌شده همیشه **مبلغ اصلی/بدون‌تخفیف** (`original_amount`) هست؛ `discount_amount` جدا ذخیره می‌شه ولی دیگه از `prepayment_amount` کم نمی‌شه.
- `applyDiscountCode()` (اعمال تخفیف روی نوبت از‌قبل‌ساخته‌شده، از صفحه‌ی پرداخت/جزئیات نوبت): همون تغییر — `prepayment_amount` دست‌نخورده می‌مونه، فقط `discount_code`/`discount_amount` ست می‌شن. خروجی متد هم `prepayment_amount`/`remaining_amount` رو برمی‌گردونه (به‌جای `final_amount` قبلی که دیگه معنی نداشت).

**`Booking` model:** accessor جدید `getRemainingAmountAttribute()` = `max(0, قیمت_خدمت − prepayment_amount − discount_amount)` — **فقط نمایشی**؛ تأیید شد `BookingObserver::addIncomeAndCommission()` (سهم متخصص/کمیسیون ادمین) از قبل و همچنان فقط روی `prepayment_amount` کار می‌کنه، هیچ‌جا به این accessor یا `discount_amount` وصل نیست — یعنی هیچ ریسک منفی‌شدن حساب کیف‌پول از این تغییر وجود نداره.

**نمایش (قیمت کل خدمت / پیش‌پرداخت / باقی‌مانده)، هم برای مشتری هم متخصص:**
- `bookings/confirm.blade.php` (قبل از ساخت نوبت): سه ردیف + یک نوار اطلاع‌رسانی («فقط پیش‌پرداخت از درگاه پرداخت می‌شه؛ مابقی موقع حضور در سالن»). JS پیش‌نمایش کد تخفیف (`check-discount`) بازنویسی شد: دیگه پیش‌پرداخت رو overwrite نمی‌کنه (ثابت می‌مونه)، فقط «باقی‌مانده» رو با `discount_amount` برگشتی آپدیت می‌کنه.
- `payment/show.blade.php`: همون سه رقم؛ بلوک تخفیف (چه استاتیک، چه دینامیک از دکمه‌ی «اعمال کد تخفیف» همین صفحه) به «از باقی‌مانده کسر شد» تغییر کرد؛ `bookingAmount` (متغیر JS که مبنای محاسبه‌ی سهم کیف‌پول/درگاهه) از `let` به `const` تغییر کرد چون دیگه هیچ‌وقت نباید توسط تخفیف بازنویسی بشه؛ بلوک اسکریپت انتهایی که قبلاً سعی می‌کرد این مقدار رو دوباره‌نویسی کنه (و با `const` جدید دیگه یک خطای جاوااسکریپت بود) حذف شد.
- `bookings/show.blade.php` (مشتری) و `specialist/booking-show.blade.php` (متخصص، که قبلاً **اصلاً هیچ اطلاعات مالی‌ای نداشت**): ردیف «باقی‌مانده» اضافه شد. برای متخصص، عبارت‌بندی عمداً روشن نوشته شد («پیش‌پرداخت مشتری از طریق سایت» / «باقی‌مانده — موقع نوبت مستقیماً از مشتری دریافت کنید») تا با سهم خالص متخصص از کیف‌پول (که یک مفهوم کاملاً جدا و بعد از کسر کمیسیونه) اشتباه گرفته نشه.

**پیامک/نوتیفیکیشن (برای هر دو نقش، طبق درخواست صریح کاربر):**
- `CustomerBookingNotification` (هم SMS هم `toArray()` دیتابیسی)، `BookingObserver::sendCustomerConfirmationSMS()`/`sendCustomerPendingSMS()` — هر سه رقم (کل/پیش‌پرداخت/باقی‌مانده) اضافه شد.
- `BookingNotification` (نوتیفیکیشن نوبت جدید به متخصص، هم SMS هم دیتابیس) — همون سه رقم، با عبارت‌بندی مخصوص متخصص («پیش‌پرداخت دریافتی از مشتری از طریق سایت» / «باقی‌مانده که باید مستقیماً از مشتری دریافت کنید»).

### تست واقعی (نه فقط خوندن کد) — با SQLite واقعی + کل مسیر HTTP
- فرمول `calculatePrepaymentAmount()`: پیش‌فرض (۳۰٪/۵۰۰۰۰)، override توسط ادمین به درصد دیگه، و حالت مرزی سقف (خدمت ۳۰,۰۰۰ → پیش‌پرداخت ۳۰,۰۰۰، نه ۵۰,۰۰۰).
- `createBooking()` بدون کد: پیش‌پرداخت=۷۵,۰۰۰، باقی‌مانده=۱۷۵,۰۰۰، جمع دقیقاً برابر قیمت خدمت (۲۵۰,۰۰۰).
- `createBooking()` با کد ۱۰٪: پیش‌پرداخت **بدون تغییر** (۷۵,۰۰۰)، تخفیف (۷,۵۰۰) از باقی‌مانده کم شد (۱۶۷,۵۰۰) — جمع کل واقعاً به ۲۴۲,۵۰۰ کاهش پیدا کرد (نه صفر مثل رفتار قبلی).
- `applyDiscountCode()` روی نوبت از‌قبل‌ساخته‌شده: همون نتیجه، هم در پاسخ برگشتی هم در دیتابیس.
- حالت مرزی (خدمت ارزون که پیش‌پرداخت=قیمت کامل + تخفیف بزرگ ثابت): باقی‌مانده هیچ‌وقت منفی نشد (۰ کلمپ شد).
- درخواست‌های HTTP واقعی: `/bookings/confirm` سه رقم درست رو رندر می‌کنه؛ `/bookings/check-discount` مقدار درست `discount_amount` رو برمی‌گردونه؛ یک نوبت واقعی از `/bookings` ساخته و بعد در `/payment/{id}` دیده شد (پیش‌پرداخت دست‌نخورده در «مبلغ قابل پرداخت»)؛ `POST .../apply-discount` روی همون نوبت واقعی، پیش‌پرداخت رو در دیتابیس دست‌نخورده گذاشت و باقی‌مانده رو درست کم کرد؛ `PUT /admin/wallet/settings` هر دو فیلد جدید رو درست از طریق کل پایپ‌لاین (Form Request → Service → دیتابیس) ذخیره کرد.

**فایل‌های این فیچر:**
`database/migrations/2026_08_03_000000_add_prepayment_percentage_to_wallet_settings.php` (جدید)، `app/Models/WalletSetting.php`، `app/Models/Booking.php`، `app/Services/Booking/BookingService.php`، `app/Http/Controllers/User/BookingReservationController.php`، `app/Http/Controllers/User/BookingDiscountController.php`، `app/Http/Requests/Admin/Wallet/UpdateWalletSettingsRequest.php`، `app/Notifications/Booking/CustomerBookingNotification.php`، `app/Notifications/Booking/BookingNotification.php`، `app/Observers/Booking/BookingObserver.php`، `resources/views/bookings/confirm.blade.php`، `resources/views/bookings/show.blade.php`، `resources/views/payment/show.blade.php`، `resources/views/specialist/booking-show.blade.php`، `resources/views/admin/wallet/settings.blade.php`.

⚠️ **یادآوری مهم درباره‌ی ترتیب کامیت‌ها**: قبل از این بازطراحی، یک فیکس میانی (`fix/booking-confirm-discount-amount-increases`) تحویل داده شده بود که فقط باگ قیمت‌گذاری (مرحله‌ی ۱ بالا) رو درست می‌کرد، بدون بحث مراحل ۲/۳. اون کامیت **کاملاً جایگزین شد** — نباید هم اون و هم نسخه‌ی نهایی (`feat/configurable-prepayment-percentage`) هر دو اعمال/commit بشن؛ فقط نسخه‌ی نهایی باید روی `develop` بمونه.

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۰۴): سه باگ کشف‌شده حین تست واقعی فیچر بازطراحی پیش‌پرداخت/تخفیف

کاربر بلافاصله بعد از اعمال پچ فیچر بالا، تست دستی واقعی انجام داد (اسکرین‌شات‌های صفحات `bookings/create`، `bookings/confirm`، `bookings/success`) و سه مشکل گزارش کرد:

### ۱) صفحه‌ی «رزرو نوبت جدید» (`bookings/create.blade.php`) هنوز مبلغ پیش‌پرداخت هاردکد قدیمی (۵۰,۰۰۰) رو نشون می‌داد
یک متن هاردکد `۵۰٬۰۰۰ تومان` در HTML بود که هیچ‌وقت به JS پویای صفحه (که بقیه‌ی فیلدهای خلاصه رو آپدیت می‌کنه) وصل نشده بود — باقی‌مانده از قبل از بازطراحی. **فیکس ریشه‌ای**: `ServiceController::list()` (که `/api/services`، منبع داده‌ی این صفحه، رو تغذیه می‌کنه) حالا `prepayment_amount` هر خدمت رو با `WalletSetting::calculatePrepaymentAmount()` محاسبه می‌کنه — **بعد از** خوندن از کش ۳۰ دقیقه‌ای (نه داخل خود کش)، پس همیشه با تنظیمات فعلی ادمین هماهنگه، بدون نیاز به invalidation دستی موقع تغییر تنظیمات. «قیمت کل خدمت» و «باقی‌مانده» هم به این صفحه اضافه شد (تنها صفحه‌ی باقی‌مانده‌ای بود که این دو رو نداشت، برخلاف confirm/payment/show).

### ۲) 🔴 باگ واقعی: متن پیامک تخفیف رو از باقی‌مانده کم نمی‌کرد
`BookingNotification` (نوتیفیکیشن متخصص) و `CustomerBookingNotification` هر دو، در `toArray()` **و** `toSms()`، به‌جای استفاده از `Booking::remaining_amount` (accessor که تخفیف رو درست کم می‌کنه)، فرمول رو دستی و **بدون کسر تخفیف** دوباره حساب کرده بودن (`totalPrice - prepayment`، بدون `- discount_amount`). نتیجه دقیقاً همونی که کاربر گزارش داد: نمایش داخل اپ (که از منبع دیگه‌ای، درست، میومد) درست بود، ولی متن پیامک همیشه باقی‌مانده‌ی **بدون تخفیف** رو نشون می‌داد — مثلاً برای نوبت ۲۵۰,۰۰۰ با ۱۰٪/۷,۵۰۰ تخفیف، پیامک می‌گفت متخصص باید ۱۷۵,۰۰۰ نقدی بگیره، در حالی که عدد درست ۱۶۷,۵۰۰ بود — یعنی متخصص ۷,۵۰۰ بیشتر از حق واقعی‌ش از مشتری می‌گرفت و حساب نقدی به‌هم می‌ریخت، دقیقاً همون ریسکی که کاربر نگرانش بود. هر ۴ محل (`BookingNotification::toArray/toSms`، `CustomerBookingNotification::toArray/toSms`، به‌علاوه‌ی `BookingObserver::sendCustomerConfirmationSMS`/`sendCustomerPendingSMS` که دقیقاً همین باگ رو داشتن) به `$booking->remaining_amount` وصل شدن.

### ۳) افزودن «باقی‌مانده» به صفحه‌ی «پرداخت با موفقیت انجام شد» (`bookings/success.blade.php`)
درخواست صریح کاربر — این صفحه تنها جای باقی‌مونده‌ای بود که این رقم رو نداشت؛ از همون accessor استفاده می‌کنه، پس خودکار با فیکس بالا هماهنگه.

**تست واقعی:** با دیتابیس واقعی، یک نوبت واقعی با کد ۱۰٪ ساخته شد؛ تأیید شد `/api/services` مقدار درست `prepayment_amount` رو برمی‌گردونه، صفحه‌ی `bookings/create` دیگه رشته‌ی هاردکد قدیمی رو نداره، و **متن واقعی پیامک** (نه فقط داده‌ی نمایشی — از لاگ واقعی `SMSService::send()` استخراج شد) برای هم متخصص هم مشتری درست ۱۶۷,۵۰۰ رو نشون می‌ده، نه ۱۷۵,۰۰۰ قدیمی؛ صفحه‌ی موفقیت هم همین عدد درست رو رندر می‌کنه.

**فایل‌های این نشست:** `app/Http/Controllers/User/ServiceController.php`، `app/Notifications/Booking/BookingNotification.php`، `app/Notifications/Booking/CustomerBookingNotification.php`، `app/Observers/Booking/BookingObserver.php`، `resources/views/bookings/create.blade.php`، `resources/views/bookings/success.blade.php`.

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۰۶): تکمیل داشبورد امنیتی حساب کاربری + پنل امنیت ادمین + رفع حفره‌ی auth:sanctum شرطی

**زمینه:** آیتم شماره‌ی ۱۰ از فهرست کارهای باز پراکنده («تکمیل داشبورد امنیتی حساب کاربری») شروع شد. دسترسی شبکه به GitHub این جلسه هم کاملاً باز بود؛ کلون مستقیم `develop` انجام شد.

### کشف اولیه: این فیچر «نیمه‌کاره» نبود — کاملاً روت‌شده و در دسترس بود، ولی هر بخشش فتال ارور می‌داد
بررسی نشون داد `SecurityController` (کاربر) و روت‌های `web/security.php`/`admin/security.php` از قبل commit شده بودن و پشت `auth+verified` (وب) / `admin+permission` (ادمین) **واقعاً reachable** بودن — برخلاف مسیر «پرداخت امن/۲FA» قبلی که پشت میدل‌ور مسدود بود، این یکی برای هر کاربر لاگین‌شده‌ای که مستقیم URL رو باز کنه بلافاصله کرش می‌کرد:

1. **`security.sessions`/`security.activity`** روت‌شده بودن ولی متدهای `sessions()`/`activity()` اصلاً روی `SecurityController` وجود نداشتن.
2. **`dashboard()`** قبل از رسیدن به View هم فتال SQL می‌داد، چون `getRecentActivities()`/`getLoginAttempts()`/`calculateSecurityScore()` از `DB::table('security_logs')` می‌خوندن ولی **این جدول اصلاً migration نداشت**.
3. **حتی با ساخت جدول، همیشه خالی می‌موند**: `SecurityLogService` (نویسنده‌ی لاگ‌ها) فقط به `Log::channel('security')` (فایل) می‌نوشت، هیچ‌وقت به DB — یعنی reader/writer از دو منبع کاملاً جدا کار می‌کردن.
4. ویوهای `security.dashboard`/`security.logs` اصلاً وجود نداشتن.
5. **بخش ادمین** (`admin.security.logs/users/settings`) هم روت‌شده و فتال بود: متدهای `adminLogs`/`adminUsers`/`adminSettings`/`updateSettings` هیچ‌کدوم روی `SecurityController` وجود نداشتن؛ ضمناً این روت‌های ادمین به‌اشتباه به `User\SecurityController` وصل بودن (برخلاف قرارداد namespace پروژه `Admin\{Domain}`).
6. `password_changed_at` نه ستون داشت نه هیچ‌جا ست می‌شد.
7. هیچ لینک UI‌ای (نه Blade نه JS) به هیچ‌کدوم از این روت‌ها اشاره نمی‌کرد — فعلاً فقط با ورود مستقیم URL قابل کشف بود.

قبل از پیاده‌سازی، دو تصمیم معماری با کاربر مشخص شد: (۱) لاگ‌های امنیتی هم در فایل هم در DB ذخیره بشن (dual-write)، (۲) بخش ادمین هم همین جلسه، با یک `Admin\Security\...` جدا طبق قرارداد namespace پروژه، کامل بشه.

### پیاده‌سازی

**زیرساخت DB:**
- Migration جدید `security_logs` (user_id nullable، event، level، ip_address، user_agent، context json، created_at) + ایندکس روی user_id/created_at.
- `SecurityLogService`: حالا dual-write واقعی (فایل + DB) از طریق یک متد خصوصی مشترک `persist()`.
- **⭐ باگ واقعی جانبی رفع شد**: `logLogin()` قبلاً امضای دو-پارامتری داشت (`$success, $username`) در حالی که سه جا در `AuthenticatedSessionController` با آرگومان سوم (`$user`) صداش می‌زدن — PHP این آرگومان اضافه رو بی‌صدا نادیده می‌گرفت، یعنی هیچ لاگ ورودی (چه موفق چه ناموفق) هیچ‌وقت `user_id` واقعی نداشت (چون `Auth::id()` قبل از احراز هویت کامل، null‌ه). امضا به `logLogin($success, $username, ?User $user = null)` تغییر کرد و `user_id` به `context` اضافه شد — الان حتی تلاش‌های ناموفق لاگین هم درست به کاربر مربوطه نسبت داده می‌شن (تست شد: لاگین با رمز غلط، `user_id` درست در `security_logs` ثبت می‌شه).
- Migration جدید: `password_changed_at`/`password_strength_score` روی `users`؛ `PasswordController::update()` و `RegisteredUserController::store()` این‌ها رو ست می‌کنن.
- **`App\Services\PasswordStrengthService`** (جدید): امتیاز رمز روی **رمز خام** در همون لحظه‌ای که هنوز در دسترسه محاسبه می‌شه (نه با بازسازی از روی هش بعداً) — باگ واقعی: منطق قبلی داخل `SecurityController` امتیاز رو از روی هش bcrypt بازسازی می‌کرد، که چون هش‌ها همیشه طولانی/مختلط‌کاراکترن، همیشه امتیاز نزدیک به حداکثر می‌داد، فارغ از قدرت واقعی رمز کاربر.

**کنترلرها/سرویس‌ها:**
- `SecurityController` (کاربر): `sessions()`/`activity()` اضافه شدن، به ویوهای واقعی Blade وصل شدن.
- `App\Http\Controllers\Admin\Security\AdminSecurityController` + `App\Services\Admin\Security\AdminSecurityService` (جدید، طبق قرارداد namespace `Admin\{Domain}`): `logs`/`users`/`settings`/`updateSettings`؛ `routes/admin/security.php` به این کلاس درست وصل شد.
- Migration جدید `security_settings` (تک‌ردیفی، هم‌الگو با `WalletSetting`) — فقط `password_expiry_days` (تنها فیلدی که واقعاً در محاسبه‌ی امتیاز امنیتی مصرف می‌شه؛ عمداً از اضافه‌کردن فیلدهای دیگه‌ای مثل «حداکثر تلاش ناموفق ورود» که در این جلسه واقعاً enforce نمی‌شدن پرهیز شد — دقیقاً همون الگوی «تنظیمات واردنشده در منطق واقعی» که چندبار در این پروژه به‌عنوان باگ کشف شده، این‌بار پیشگیرانه رعایت شد).

**Views:**
- کاربر: `security/{dashboard,sessions,activity}.blade.php` (تم طلایی مشتری) — امتیاز امنیتی، وضعیت ۲FA، آخرین تغییر رمز، نشست‌های فعال (با دکمه‌ی پایان‌دادن)، تاریخچه‌ی فعالیت.
- ادمین: `admin/security/{logs,users,settings}.blade.php` — لاگ‌های خام با فیلتر (نوع رخداد/سطح/بازه‌ی تاریخ)، وضعیت امنیتی هر کاربر (فعالیت مشکوک ۳۰ روز اخیر، وضعیت ۲FA، آخرین ورود موفق)، تنظیمات.

**لینک‌های UI (قبلاً صفر لینک وجود داشت):**
- کارت «امنیت حساب کاربری» در `profile/edit.blade.php` → `security.dashboard`.
- آیتم سایدبار «امنیت» در `layouts/admin.blade.php` (بخش سیستم‌ها، پشت `@permission('view-security-logs')`، هم‌الگو با کدهای تخفیف/لویالتی/وبلاگ).

### ⭐ کشف جانبی حین تست: حفره‌ی امنیتی واقعی در `routes/api.php`
```php
Route::middleware(app()->environment('production') ? 'auth:sanctum' : [])->group(function () {
```
یعنی در هر محیطی که دقیقاً `APP_ENV=production` نباشه (شامل یک تایپوی احتمالی روی سرور واقعی)، گروه بزرگی از روت‌های API (`/api/security/*`, `/api/loyalty/*`, و نسته‌ی `/api/admin/*`) **هیچ میدل‌ور احراز هویتی در سطح روتینگ نداشتن** — فقط به null-check دستی و ناهماهنگ داخل هر متد کنترلر متکی بودن.

**بررسی دقیق میزان آسیب واقعی (نه فقط نظری):**
- `/api/security/*` (`routes/api/auth/security.php`): **کاملاً یتیم** — هیچ Blade/JS ای صداش نمی‌زنه؛ معادل واقعی‌ش همون `/security/*` هست که همین جلسه ساخته شد (session+CSRF محافظت‌شده). `terminateSession()`/`getActiveSessions()` با `auth()->id()` اسکوپ می‌شن، پس فراخوانی ناشناس فقط `WHERE user_id IS NULL` می‌زد (no-op بی‌ضرر)، نه هایجک نشست — ولی همچنان دسترسی بی‌دلیل به endpointهای زنده‌ی لمس‌کننده‌ی DB بود.
- `/api/loyalty/*` (redeem-reward, redeem-gift, transfer-points و...): **این‌ام کاملاً یتیمه** — تنها مصرف‌کننده‌هاش (`Loyalty/{PointsOverview,PointsHistory,RewardsList}.jsx`) هیچ‌وقت در `app.jsx`/`admin.jsx` mount نشدن (همون الگوی React یتیم مستندشده‌ی پروژه، این‌بار نمونه‌ی پنجم/ششم).
- `/api/user/bookings.php` و `/api/user/payments.php`: مستقل و امن بودن — اولی خودش `->middleware('auth:sanctum')` صریح داره، دومی پشت `2fa.enabled` (`EnsureTwoFactorVerifiedForPayment`) که خودش `auth()->check()` رو چک می‌کنه.
- نسته‌ی `admin` داخل همین گروه: مستقل امن بود — `AdminMiddleware` خودش `auth()->check() + is_admin` رو چک می‌کنه، صرف‌نظر از این‌که میدل‌ور بیرونی چی resolve بشه.

یعنی هیچ فیچر واقعاً کارکنی از این حفره آسیب واقعی نمی‌دید، ولی خود الگوی «رشته‌ی نام محیط به‌عنوان گیت امنیتی» ذاتاً شکننده‌ست.

**فیکس:** شرط حذف شد، `auth:sanctum` بدون قید محیط همیشه اعمال می‌شه. با درخواست HTTP واقعی تأیید شد: `GET /api/loyalty/points` و `POST /api/security/check-password-strength` بدون لاگین حالا ۴۰۱ می‌دن (قبلاً ۲۰۰ بودن)؛ داشبورد امنیتی/پنل ادمین امنیت (که این جلسه ساخته شدن، از `/security/*`/`/admin/security/*` وب استفاده می‌کنن نه از این گروه API) کاملاً دست‌نخورده و سالم موندن.

**🟡 یافته‌ی جانبی، این جلسه فقط مستند شد — ✅ در جلسه‌ی بعدی (۲۰۲۶-۰۸-۰۷) بررسی/رفع شد**: حین تست همین فیکس، `GET /api/admin/bookings/stats` با یک سشن واقعی به «Maximum execution time of 30 seconds exceeded» خورد. بررسی همون لحظه (بدون بازخوانی کامل کد سرویس) به‌اشتباه نتیجه گرفت `AdminBookingController::getStats()` متد ناموجودی روی `BookingService` رو صدا می‌زنه. **این تشخیص در جلسه‌ی بعدی رد شد**: `AdminBookingService::getStats()` (نه `BookingService`) واقعاً وجود داشت و کاملاً سالم/سریع بود (کوئری‌های ساده‌ی `whereDate`/`count`، بدون N+1 یا حلقه). ریشه‌ی واقعی: کل زنجیره (`BookingStats.jsx` → mount شرطی `#booking-stats` در `admin.jsx` → هر دو روت وب/API → کنترلر/سرویس) کاملاً یتیم بود — هیچ Blade‌ای `id="booking-stats"` نداشت — یعنی صرفاً کد مرده روی کد مرده، نه یک باگ فعال. به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کد مرده‌ی ویجت آمار نوبت‌های ادمین» پایین‌تر نگاه کن.

**فایل‌های این نشست:**
- `database/migrations/2026_08_06_000000_create_security_logs_table.php` (جدید)
- `database/migrations/2026_08_06_000001_add_password_security_columns_to_users_table.php` (جدید)
- `database/migrations/2026_08_06_000002_create_security_settings_table.php` (جدید)
- `app/Models/SecurityLog.php`, `app/Models/SecuritySetting.php` (جدید)
- `app/Services/SecurityLogService.php`, `app/Services/PasswordStrengthService.php` (جدید)
- `app/Http/Controllers/User/SecurityController.php`
- `app/Http/Controllers/Auth/PasswordController.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Models/User.php`
- `app/Http/Controllers/Admin/Security/AdminSecurityController.php` (جدید)
- `app/Services/Admin/Security/AdminSecurityService.php` (جدید)
- `app/Http/Requests/Admin/Security/UpdateSecuritySettingsRequest.php` (جدید)
- `routes/admin/security.php`, `routes/api/auth/security.php`, `routes/api.php`
- `resources/views/security/{dashboard,sessions,activity}.blade.php` (جدید)
- `resources/views/admin/security/{logs,users,settings}.blade.php` (جدید)
- `resources/views/profile/edit.blade.php`, `resources/views/layouts/admin.blade.php`
- `app/Http/Requests/User/Security/CheckPasswordStrengthRequest.php` (rename، رفع رگرسیون حساسیت به حروف — پنجمین/ششمین نمونه‌ی همین الگوی تکراری پروژه)

⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` (سه migration جدید).

**تحویل:** سه کامیت جدا (طبق استاندارد پروژه) روی برنچ `feat/complete-security-dashboard` + `fix/api-auth-sanctum-always-required`، به‌صورت `git format-patch` تحویل داده شدن؛ هر سه روی کلون تازه‌ی `develop` بدون conflict تست شدن (`git am`) و `php -l` روی همه‌ی فایل‌های PHP تغییریافته تمیز بود. برخلاف اکثر جلسات قبلی، این‌بار کل مسیر (نه فقط syntax) با نصب واقعی composer + PHP 8.3 + یک دیتابیس SQLite واقعی و درخواست‌های HTTP واقعی (فلوی کامل لاگین، بازدید از هر ۶ صفحه‌ی جدید، ذخیره‌ی تنظیمات، ترمینیت نشست، تست حفره‌ی auth) تأیید شد.

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کد مرده‌ی ویجت آمار نوبت‌های ادمین (`booking-stats`)

**زمینه:** در جلسه‌ی قبلی (تکمیل داشبورد امنیتی) حین تست جانبی حفره‌ی `auth:sanctum`، یک تایم‌اوت ۳۰ ثانیه‌ای روی `GET /api/admin/bookings/stats` دیده شده بود و به‌اشتباه به‌عنوان «`AdminBookingController::getStats()` متد ناموجود `BookingService::getStats()` رو صدا می‌زنه» مستند شده بود، بدون بررسی کامل. این آیتم به این جلسه موکول شد.

دسترسی شبکه به GitHub این جلسه هم کاملاً باز بود؛ کلون مستقیم `develop` (آخرین کامیت، از قبل هم‌تراز با کار جلسه‌ی قبل) انجام شد.

**بررسی واقعی، نه فرض قبلی:**
1. `AdminBookingService::getStats(?string $date): array` **واقعاً وجود داشت** و کاملاً سالم بود — سه کوئری ساده‌ی `Booking::whereDate('booking_time', $date)->count()`/`where('status', ...)->count()`، بدون N+1، بدون حلقه، بدون هیچ چیزی که بتونه واقعاً تایم‌اوت بده. یادداشت قبلی سند (که ادعا می‌کرد این متد ناموجوده) **اشتباه/کهنه بود**.
2. با `grep -rn` سراسری تأیید شد **تنها مصرف‌کننده‌ی این endpoint** کامپوننت `resources/js/Components/booking/BookingStats.jsx` بود، که خودش فقط با یک شرط `if (document.getElementById('booking-stats'))` در `admin.jsx` mount می‌شد.
3. با `grep -rn "booking-stats" resources/views/` تأیید شد **هیچ فایل Blade‌ای، هیچ‌جا، عنصری با `id="booking-stats"` نداشت** — یعنی این شرط در `admin.jsx` همیشه `false` بود و این ویجت از روز اول هیچ‌وقت واقعاً در مرورگر رندر نمی‌شد.
4. با `grep -rn "bookings.stats\|admin/bookings/stats"` تأیید شد هیچ کد/route/JS دیگه‌ای (خارج از همین زنجیره) به این endpoint وابسته نیست.

**نتیجه:** این یک باگ فعال (متد ناموجود/کوئری سنگین) نبود؛ **کل زنجیره‌ی این فیچر کد مرده‌ی کامل بود** — کامپوننت React یتیم (نمونه‌ی هفتم/هشتم همین الگوی تکراری پروژه: React mount بدون هیچ Blade مصرف‌کننده)، که هیچ‌وقت کسی متوجه یتیم‌بودنش نشده بود چون خطای مرورگر (fetch به یک endpoint موجود ولی کند/بی‌فایده) در کنسول اصلاً دیده نمی‌شد — چون کامپوننت اصلاً mount نمی‌شد.

⚠️ **علت واقعی تایم‌اوت مشاهده‌شده در جلسه‌ی قبل هنوز مشخص نیست** (احتمالاً یک مشکل محیطی لحظه‌ای در تست دستی کاربر — دیتابیس قفل‌شده توسط یک migration/seed هم‌زمان، یا مشابه — نه یک باگ کد، چون خود کوئری‌ها با بررسی مستقیم کد کاملاً ساده و سریع‌ان). اگه در آینده دوباره چنین تایم‌اوتی روی یک endpoint مشابه دیده شد، باید با یک تست HTTP واقعی (نه فقط خوندن کد) بررسی بشه، نه با فرض صرف از روی امضای متد.

**پاک‌سازی انجام‌شده:**
- `resources/js/Components/booking/BookingStats.jsx` — حذف کامل فایل.
- `resources/js/admin.jsx` — mount شرطی `#booking-stats` حذف شد (تنها mount باقی‌مونده‌ی این فایل بود؛ فایل الان فقط یک entry-point خالی برای Vite‌ه، با کامنت توضیحی برای جلوگیری از سردرگمی آینده).
- `routes/admin/bookings.php` — روت `admin.bookings.stats` حذف شد.
- `routes/api/admin/bookings.php` — کل فایل حذف شد (تنها محتواش همین یک روت بود)؛ `require` مربوطه در `routes/api.php` هم حذف شد (دقیقاً هم‌الگو با حذف قبلی `routes/api/admin/loyalty.php` در `R-Cleanup-DeadCode`).
- `AdminBookingController::getStats()` و `AdminBookingService::getStats()` — حذف شدن؛ import اضافی `JsonResponse` هم از کنترلر پاک شد.

**تست:** چون این جلسه هم PHP CLI در دسترس نبود (خطای ۴۰۴ روی پکیج‌های `php8.3-*` از mirror اوبونتو)، مرور دستی دقیق فایل‌های نهایی جایگزین `php -l` شد؛ برای اطمینان از عدم شکستن syntax/route، patch روی یک کلون کاملاً تازه‌ی `develop` با `git am` تست و بدون conflict اعمال شد.

**فایل‌های این نشست:**
- `resources/js/Components/booking/BookingStats.jsx` (حذف)
- `resources/js/admin.jsx`
- `routes/admin/bookings.php`
- `routes/api.php`
- `routes/api/admin/bookings.php` (حذف)
- `app/Http/Controllers/Admin/Booking/AdminBookingController.php`
- `app/Services/Admin/Booking/AdminBookingService.php`

**Branch:** `chore/remove-dead-booking-stats-widget`
**Commit:** `chore(booking): remove dead admin bookings-stats widget`

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کامل فیچر WorkSchedule

**زمینه:** طبق تصمیم مستندشده در بخش «✅ بررسی‌شده، رد شد، و در نهایت کاملاً حذف شد: WorkSchedule» بالاتر، این فیچر یک بار قبلاً (در همون فاز) کامل پیاده‌سازی و باگ‌هاش رفع شده بود، ولی تصمیم گرفته شده بود بدون استفاده‌ی فعال نگه داشته بشه چون زیرمجموعه‌ی کم‌ارزش `SpecialistSchedule` بود. این جلسه، قبل از هر اقدامی، یک سوال مستقیم مطرح شد: «اگه `SpecialistSchedule` رو کامل با `WorkSchedule` جایگزین کنیم، بازم می‌شه ساعت جدا برای هر روز تعریف کرد؟»

**بررسی migration واقعی (نه فرض)** نشون داد جواب **نه**‌ست:
```php
Schema::create('work_schedules', function (Blueprint $table) {
    $table->foreignId('specialist_id')->constrained('specialists')->onDelete('cascade');
    $table->json('work_days');      // آرایه‌ی روزها
    $table->time('start_time');     // فقط یک بازه
    $table->time('end_time');       // مشترک بین همه‌ی روزهای بالا
    $table->unique('specialist_id'); // فقط یک ردیف در کل، برای هر متخصص
});
```
یعنی `WorkSchedule` ذاتاً فقط «یک بازه‌ی ساعتی مشترک برای چند روز انتخابی» رو مدل می‌کنه، نه ساعت جدا برای هر روز — جایگزینی کامل `SpecialistSchedule` با همین ساختار، یک عقب‌گرد قابلیت واقعی بود (از دست دادن توانایی «شنبه ۹ تا ۱۷، یکشنبه ۱۰ تا ۱۴»).

بعد از این کشف، سه گزینه مطرح شد: (الف) جایگزینی کامل با تغییر schema (که عملاً یعنی بازسازی `WorkSchedule` به شکل `SpecialistSchedule`، بدون فایده‌ی واقعی)، (ب) `WorkSchedule` فقط به‌عنوان یک فرم/قالب سریع UI بالای `SpecialistSchedule` (نگه‌داشتن هر دو، با یک لایه‌ی ترجمه)، (ج) حذف کامل. **کاربر گزینه‌ی (ج) رو انتخاب کرد.**

**بررسی پیش از حذف (طبق قانون همیشگی «قبل از حذف با grep تأیید کن»):**
- تأیید شد `Specialist::getAvailableSlots()` (متد واقعی مصرف‌شده در فلوی رزرو مشتری) فقط از `$this->schedules()` (`SpecialistSchedule`) می‌خونه — **هیچ‌وقت** از `workSchedule()`. یعنی این حذف صفر اثر روی فلوی زنده‌ی رزرو مشتری داره.
- تأیید شد `SpecialistPolicy::manageSchedule` بین هر دو مسیر (قدیمی/جدید schedule) مشترکه، نه اختصاصی `WorkSchedule` — نیازی به تغییر Policy نبود.
- با grep سراسری (`app/`, `database/`, `routes/`, `resources/views/`) همه‌ی نقاط مصرف‌کننده لیست شدن: مدل، سرویس، کنترلر ادمین، دو Form Request، فکتوری، دو Blade اختصاصی، relation روی مدل `Specialist`، سه متد `SpecialistProfileController`، سه گروه روت (`routes/admin/schedule.php`، `routes/web/specialistprofile.php`، `routes/api/admin/specialists.php` — هر سه فایل حاوی روت‌های `WorkSchedule` **در کنار** روت‌های دیگه‌ای بودن که باید دست‌نخورده می‌موندن، مثل `holidays`)، و دو لینک UI (`admin/specialists/show.blade.php`, `layouts/specialist.blade.php`).

**حذف انجام‌شده:**
- `app/Models/WorkSchedule.php`، `app/Services/Specialist/WorkScheduleService.php`، `app/Http/Controllers/Admin/Specialist/AdminSpecialistsWorkScheduleController.php`، `app/Http/Requests/{Specialist,Admin/Specialist}/UpdateWorkScheduleRequest.php`، `database/factories/WorkScheduleFactory.php`
- `resources/views/admin/specialists/schedules/work-edit.blade.php`، `resources/views/specialist/work-schedule.blade.php`
- `Specialist::workSchedule()` relation
- `SpecialistProfileController::workSchedule()`/`updateWorkSchedule()`/`destroyWorkSchedule()` + inject نشدن `WorkScheduleService` از constructor
- روت‌های `admin.specialists.work-schedule.*` (از `routes/admin/schedule.php` — گروه `holidays` همون فایل دست‌نخورده موند)
- روت‌های `specialist.work-schedule*` (از `routes/web/specialistprofile.php`)
- دو endpoint API `/{specialist}/schedule/check` و `/{specialist}/schedule/slots` (از `routes/api/admin/specialists.php` — endpoint `holidays/check` همون فایل دست‌نخورده موند)
- لینک/کارت «برنامه کاری (تکی)» در `admin/specialists/show.blade.php`
- لینک سایدبار «ساعات کاری (تکی)» در `layouts/specialist.blade.php`
- migration جدید: `DROP TABLE work_schedules` (با `down()` کامل برای بازگشت‌پذیری در صورت نیاز)

**تست:** چون این جلسه هم PHP CLI در دسترس نبود، مرور دستی دقیق تمام فایل‌های نهایی + یک grep سراسری نهایی (که صفر ارجاع فعال باقی‌مونده رو تأیید کرد، فقط کامنت‌های توضیحی در migration جدید و `routes/admin/schedule.php`) جایگزین `php -l` شد. patch روی یک کلون کاملاً تازه‌ی `develop` با `git am` تست و بدون conflict اعمال شد.

⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` باید اجرا بشه تا جدول `work_schedules` هم از دیتابیس حذف بشه.

**فایل‌های این نشست:**
- `app/Models/WorkSchedule.php` (حذف)
- `app/Services/Specialist/WorkScheduleService.php` (حذف)
- `app/Http/Controllers/Admin/Specialist/AdminSpecialistsWorkScheduleController.php` (حذف)
- `app/Http/Requests/Specialist/UpdateWorkScheduleRequest.php` (حذف)
- `app/Http/Requests/Admin/Specialist/UpdateWorkScheduleRequest.php` (حذف)
- `database/factories/WorkScheduleFactory.php` (حذف)
- `resources/views/admin/specialists/schedules/work-edit.blade.php` (حذف)
- `resources/views/specialist/work-schedule.blade.php` (حذف)
- `app/Models/Specialist.php`
- `app/Http/Controllers/Specialist/Profile/SpecialistProfileController.php`
- `routes/admin/schedule.php`
- `routes/web/specialistprofile.php`
- `routes/api/admin/specialists.php`
- `resources/views/admin/specialists/show.blade.php`
- `resources/views/layouts/specialist.blade.php`
- `database/migrations/2026_08_07_000000_drop_work_schedules_table.php` (جدید)

**Branch:** `chore/remove-workschedule-dead-feature`
**Commit:** `chore(specialist): remove the entire WorkSchedule feature`

---

## ⭐⭐ فاز تست‌نویسی — نشست اول (۲۰۲۶-۰۸-۰۹): راه‌اندازی محیط + ۱۸۷ تست هسته‌ی مالی/رزرو/احراز هویت

**دسترسی:** این نشست دسترسی شبکه به GitHub بسته بود (`api.github.com` → ۴۰۳)؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد.

**محیط، برای اولین بار کاملاً از صفر ساخته و پابرجا شد (نه فقط `php -l`، بلکه اجرای واقعی سوییت):**
- PHP 8.3 + تمام extension های لازم (`mbstring`, `xml`, `curl`, `sqlite3`, `zip`, `bcmath`, `gd`, `intl`) نصب شد.
- Composer از GitHub Release مستقیم (`composer.phar`) نصب شد؛ `composer install --no-interaction --prefer-dist` کامل (شامل dev) بدون خطا اجرا شد.
- ⭐ **باگ محیطی کشف/رفع‌شده**: فایل `config/view.php` اصلاً در زیپ وجود نداشت (فایل استاندارد لاراول که مسیر کامپایل Blade رو تعریف می‌کنه) — بدون اون، **هر** درخواستی (نه فقط تست) با فتال ارور «Please provide a valid cache path» می‌شکست. فایل استاندارد بازسازی شد.
- `phpunit.xml`: خطوط از قبل موجود ولی کامنت‌شده‌ی `DB_CONNECTION=sqlite`/`DB_DATABASE=:memory:` فعال شدن (طبق تک‌استک مستندشده‌ی پروژه در بخش «ابزارها و منابع» بالای این سند).
- **همه‌ی ۳۶ migration پروژه بدون هیچ خطایی روی SQLite اجرا شدن** — تأیید مستقل سلامت کامل schema فعلی (بعد از تمام فازهای رفکتور/حذف WorkSchedule/...).
- `tests/TestCase.php`: یک Mockery double سراسری از `SMSService` بایند شد تا هیچ تستی واقعاً به Kavenegar وصل نشه — هم چون شبکه‌ی تست به `api.kavenegar.com` دسترسی نداره، هم چون طبق مستندات همین پروژه یک تماس synchronous واقعی می‌تونه ۲۰-۳۰ ثانیه طول بکشه (که کل سوییت رو عملاً غیرقابل‌اجرا می‌کرد). تست‌هایی که نیاز به assert محتوای واقعی پیامک دارن می‌تونن این mock رو per-test override کنن.

**نتیجه‌ی نهایی این نشست: ۱۸۷ تست، ۳۴۱ assertion، همگی PASS (~۹.۴ ثانیه اجرا).** تأیید نهایی **نه فقط در محیط کاری**، بلکه با یک `git am` کامل روی یک کلون کاملاً تازه‌ی زیپ اصلی + اجرای مجدد کل سوییت (نتیجه‌ی یکسان: ۱۸۷/۱۸۷) — یعنی patch های تحویلی واقعاً تست شدن، نه فقط ادعا.

### ⭐ ۵ باگ واقعی کشف و رفع‌شده حین تست‌نویسی

1. **`ConfirmablePasswordController::store()`** (روت `/confirm-password`) با `Auth::guard('web')->validate(['email' => $request->user()->email, ...])` کار می‌کرد — ولی جدول `users` اصلاً ستون `email` نداره (کل پروژه بر پایه‌ی `phone`ه). هر submit این فرم با خطای SQL «no such column: email» کرش می‌کرد. **فیکس:** به `'phone' => $request->user()->phone` تغییر کرد. این روت جایی در UI لینک نشده (کد یتیم‌مانند)، ولی مستقیماً reachable بود.

2. **`NewPasswordController`** (بازمانده‌ی Breeze، همون باگ email/phone) تأیید شد **کاملاً کد مرده‌ست** — هیچ route‌ای بهش اشاره نمی‌کنه (مسیر واقعی بازیابی رمز از `PasswordResetController`، که درست و بر پایه‌ی phone/OTP کار می‌کنه). حذف شد.

3. **🔴 بحرانی**: `ProfileUpdateRequest::rules()` فیلد `email` را `required`/`email`/`unique` می‌خواست — ولی نه ستون `email` روی جدول `users` وجود داره، نه فرم Blade واقعی (`profile/edit.blade.php`) چنین فیلدی داره. یعنی **هر submit فرم ویرایش پروفایل با خطای «email الزامی است» رد می‌شد و کاربران هیچ‌وقت نمی‌تونستن حتی نامشون رو آپدیت کنن** — این دقیقاً همون الگوی مستندشده‌ی «فرم مقدار می‌گیره، بی‌صدا دورش می‌ریزه»، این‌بار به شکل معکوس (فرم اصلاً submit نمی‌شد). **فیکس:** قانون `email` حذف شد؛ کد مرده‌ی همراه (`isDirty('email')`/`email_verified_at` در `ProfileController::update()`) هم که به ستون‌های ناموجود ارجاع می‌داد پاک شد.

4. **باگ نهفته (کد مرده، بدون فراخواننده)**: `LoyaltyService::earnPointsFromBooking()` از `auth()->user()->notify(...)` به‌جای نوتیف کردن کاربر هدف (`$userId`) استفاده می‌کرد — دقیقاً همون کلاس باگی که قبلاً در `redeemReward` همین فایل (طی R-AdminLoyalty) کشف/رفع شده بود. این متد فعلاً **هیچ فراخواننده‌ای در کدبیس نداره** (مسیر واقعی امتیازدهی از `BookingObserver::addLoyaltyPoints()` می‌گذره، نه این متد)، ولی چون بخشی از API عمومی سرویسه و فیکسش بی‌خطر/ساده بود، برای اطمینان اصلاح شد.

5. **`Specialist::getAvailableSlots()`**: چک تداخل با مرخصی (`leaves.start_date <= $date` / `end_date >= $date`) با مقایسه‌ی رشته‌ای خام (`where(...)`) انجام می‌شد. چون کست `date` در Eloquent روی *ذخیره‌سازی* هنوز فرمت کامل `Y-m-d H:i:s` می‌نویسه (نه فقط `Y-m-d`)، مقایسه‌ی رشته‌ای `'2026-09-05 00:00:00' <= '2026-09-05'` به‌صورت لغوی **غلط** (`false`) ارزیابی می‌شه — یعنی **مرخصی‌ای که دقیقاً همون روز شروع می‌شه، توسط موتور محاسبه‌ی در‌دسترس‌بودن نادیده گرفته می‌شد**. روی MySQL production این مشکل به‌خاطر نوع واقعی ستون `DATE` (که مقایسه رو در سطح نوع داده، نه رشته، انجام می‌ده) پنهان می‌مونه، ولی تکیه به این رفتار ضمنی و وابسته به درایور شکننده بود. **فیکس:** به `whereDate('start_date', '<=', $date)`/`whereDate('end_date', '>=', $date)` تغییر کرد — مستقل از فرمت ذخیره‌سازی، روی هر دو درایور درست کار می‌کنه. این باگ فقط حین تست روی SQLite (که تایپ‌گذاری ضعیف‌تری داره) لو رفت.

### 🟡 یافته‌ی جانبی (مستندسازی، نه باگ): منطق «زمان استراحت» در `getAvailableSlots()` کاملاً inert است
حین نوشتن تست breaking-time مشخص شد `getAvailableSlots()` به `$schedule->break_start`/`$schedule->break_end` ارجاع می‌ده، ولی این دو ستون **هیچ‌جای schema پروژه** (نه در migration جدول `specialist_schedules`، نه در مدل، نه در فرم‌های ادمین) وجود ندارن — با grep سراسری تأیید شد. یعنی این شاخه از کد همیشه با مقدار `null` کار می‌کنه و عملاً هیچ‌وقت زمان استراحتی رو مسدود نمی‌کنه؛ نه یک باگ فعال (چون هیچ داده‌ای برای مسدود کردن غلط وجود نداره)، بلکه کد کاملاً غیرقابل‌دسترسی/بلااثر. تست‌پذیر نیست بدون تغییر schema، پس تستی براش نوشته نشد. **کاندید جدید برای `R-Cleanup-DeadCode`** (حذف این دو ارجاع، یا تکمیل schema اگه قابلیت واقعاً لازمه — تصمیم بیزنسی باز).

### فایل‌های تست ساخته/بازنویسی‌شده (۱۸ فایل، ۱۸۷ تست)

| دسته | فایل‌ها | نکات کلیدی |
|---|---|---|
| **تخفیف** | `Unit/Services/Discount/DiscountCalculatorTest`, `Unit/Models/DiscountCodeTest`, `Feature/Observers/DiscountCodeObserverTest`, `Feature/Admin/AdminDiscountCodeTest` | تمام شاخه‌های فرمول (درصدی/ثابت/سقف/کلمپ)، isValid/canBeUsedBy، auto-deactivation دقیقاً در max_uses، پنل کامل ادمین از مسیر HTTP واقعی (رگرسیون‌گارد MaxPercentage فقط-درصدی، گارد حذف کد استفاده‌شده، محدودیت فیلدهای قابل‌ویرایش) |
| **کیف‌پول** | `Unit/Models/WalletSettingTest`, `Feature/Wallet/SpecialistWithdrawalTest`, `Feature/Wallet/WalletAdminServiceSettlementTest`, `Feature/Wallet/WalletAdminServiceWithdrawalTest` | فرمول پیش‌پرداخت (سقف روی قیمت کل خدمت)، جریمه‌ی لغو مشتری/متخصص (آستانه‌ی زمانی، جریمه‌ی تشدیدی، سقف ۱۰۰٪)، حداقل/حداکثر برداشت (رگرسیون‌گارد `WalletSetting::get()`/`first()`)، تسویه‌ی pending (ignore-delay هنوز منتظر گذشتن `booking_time` می‌مونه، تراکنش‌های reversed هیچ‌وقت settle نمی‌شن)، approve/reject با idempotency guard |
| **رزرو (حیاتی‌ترین)** | `Feature/Models/BookingModelTest`, `Feature/Models/SpecialistCommissionRateTest`, `Feature/Booking/BookingServiceTest`, `Feature/Observers/BookingObserverTest`, `Feature/Policies/BookingPolicyTest` | `remaining_amount`/`canBeRescheduled`؛ کمیسیون اختصاصی/سراسری (۰٪ باید محترم شمرده بشه، نه معادل «تنظیم‌نشده»)؛ `createBooking` (پیش‌پرداخت از قیمت واقعی خدمت — رگرسیون‌گارد باگ هاردکد قدیمی)، `applyDiscountCode` (مالکیت/عدم تکرار/عدم اعمال بعد پرداخت)؛ **`BookingObserver`**: تقسیم کمیسیون، idempotency پرداخت، امتیاز وفاداری، بازگشت‌وجه/جریمه به‌ازای هر نقش لغوکننده، جریمه‌ی تشدیدی، idempotency لغو؛ تمام ability های Policy |
| **وفاداری** | `Feature/Loyalty/LoyaltyServiceTest` | `redeemReward`/`earnPointsFromBooking` — رگرسیون‌گارد «عملیات روی کاربر هدف، نه کاربر لاگین‌شده» |
| **احراز هویت** | `Feature/Auth/RegistrationTest`, `AuthenticationTest`, `PasswordResetTest`, `Feature/ProfileTest`, `Feature/Auth/SecurityLogLoginTest` | فلوی کامل phone+OTP (ثبت‌نام/ورود/بازیابی رمز)؛ حذف تست‌های کهنه‌ی Breeze (`EmailVerificationTest`, `Feature/ExampleTest`) که روی سیستم ایمیلی/توکنی مفروض بودن که هیچ‌وقت این پروژه نداشته؛ رگرسیون‌گارد `user_id` در لاگ‌های امنیتی لاگین ناموفق |
| **متخصص** | `Feature/Models/SpecialistAvailabilityTest`, `Feature/Admin/AdminSpecialistPhoneNormalizationTest` | `getAvailableSlots` (ساعت کاری، تعطیلی هفتگی غیرفعال، مرخصی تاییدشده/در‌انتظار، تعطیلی رسمی، تداخل نوبت، نوبت لغوشده مسدود نمی‌کنه، تاریخ گذشته)؛ نرمالایز شماره تلفن ادمین (فرمت +98/0098) از مسیر HTTP واقعی |

### روش تحویل و تأیید
مثل فازهای قبلی رفکتور، به‌خاطر محدودیت push مستقیم، کار به‌صورت **۱۵ کامیت جدا و منطقی** (۵ تا fix، ۱۰ تا test/chore) روی برنچ `test/comprehensive-test-suite-phase-1` سازمان‌دهی و به‌صورت فایل‌های `git format-patch` تحویل داده شد. هر ۱۵ فایل patch روی یک کلون کاملاً تازه‌ی زیپ اصلی (نه محیط کاری) با `git am` بدون conflict اعمال و کل سوییت (۱۸۷ تست) مجدداً و با نتیجه‌ی یکسان اجرا شد — طبق همون استاندارد «قبل از تحویل، اعمال‌شدنش روی fresh clone تست بشه» که در فازهای قبلی هم رعایت شده بود.

### ⭐⭐ فاز تست‌نویسی — نشست دوم (۲۰۲۶-۰۸-۰۹، ادامه‌ی همون روز): کشف باگ بحرانی recursion میدل‌ور + رفع کندی ۲۰۳ ثانیه‌ای + ۲۴ تست دیگر

**زمینه:** کاربر ۱۵ پچ نشست اول رو با موفقیت روی محیط Windows/XAMPP خودش اعمال کرد (`git am`، بدون conflict) و `php artisan test` رو اجرا کرد — نتیجه ۱۸۷/۱۸۷ PASS بود، ولی زمان اجرا **۲۰۳ ثانیه** بود (در مقابل ~۹ ثانیه در محیط تست Claude) — یک اختلاف ~۲۰ برابری که ارزش بررسی داشت.

#### ⭐ کشف ۱: رفع کندی ۲۰۳ ثانیه‌ای — گارد محیطی `SMSService` فقط `local` رو استثنا می‌کرد، نه `testing`
`SMSService::send()`/`sendTemplate()` هر دو این گارد رو داشتن:
```php
if (app()->environment('local') && ! config('services.kavenegar.send_in_local', false)) {
    return true;
}
```
چون `phpunit.xml` پیش‌فرض `APP_ENV=testing` تنظیم می‌کنه (استاندارد پیش‌فرض لاراول، نه چیزی که این نشست عوض کرده باشه)، این گارد **هیچ‌وقت** در `testing` فعال نمی‌شد. یعنی **هر اجرای `php artisan test`، روی هر ماشینی با دسترسی شبکه‌ی واقعی و یک `KAVENEGAR_API_KEY` واقعی تنظیم‌شده، بی‌صدا تلاش می‌کرد پیامک واقعی بفرسته** — دقیقاً همون چیزی که روی محیط Windows/XAMPP کاربر (با دسترسی اینترنت واقعی) رخ داد، در حالی که در sandbox این نشست (بدون دسترسی شبکه به Kavenegar) این تلاش‌ها سریع fail می‌شدن و کندی محسوس نبود.

**فیکس:** گارد به `app()->environment(['local', 'testing'])` تغییر کرد — مسیر فرار `send_in_local` قبلی (برای تست دستی و آگاهانه در برابر sandbox واقعی Kavenegar) دست‌نخورده موند.

**نتیجه‌ی اندازه‌گیری‌شده:** بعد از این فیکس، حتی با ۳۷ تست بیشتر (۲۲۴ در مقابل ۱۸۷)، کل سوییت در **~۷.۵ ثانیه** اجرا شد — سریع‌تر از قبل، نه فقط برابر.

**تست رگرسیون:** به‌جای تکیه به side-effect شبکه (که بین محیط‌ها متفاوته)، از reflection برای جایگزینی کلاینت داخلی `KavenegarApi` با یک Mockery spy استفاده شد — تست صراحتاً assert می‌کنه که `Send()`/`VerifyLookup()` هیچ‌وقت صدا زده نمی‌شن وقتی `APP_ENV=testing`.

#### 🔴 کشف ۲ (بحرانی‌ترین یافته‌ی کل فاز تست‌نویسی تا این لحظه): بازگشت بی‌نهایت در تعریف گروه میدل‌ور `admin` — از قبل در پروژه‌ی اصلی وجود داشت
حین بررسی چند فایل تست که از یک بخش قبلی همین نشست روی دیسک باقی مونده بودن (توضیح کامل در پاراگراف بعد)، یک باگ فوق‌العاده جدی کشف شد که **در همون زیپ اصلی آپلودی کاربر از قبل وجود داشت**، نه چیزی که این نشست ایجاد کرده باشه:

`bootstrap/app.php`:
```php
$middleware->alias([
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ...
]);

$middleware->group('admin', [
    'auth',
    'admin',   // ⚠️ همون اسم گروه، داخل خودش!
]);
```
گروه میدل‌ور به اسم `'admin'` تعریف شده، ولی عضو دومش هم رشته‌ی `'admin'`ه. `Illuminate\Routing\MiddlewareNameResolver::parseMiddlewareGroup()` قبل از مراجعه به alias map، اول چک می‌کنه آیا این اسم یک **گروه** شناخته‌شده است — و چون هست (خودشه!)، دوباره recursively وارد همون گروه می‌شه → **حلقه‌ی بی‌نهایت واقعی**.

**تأیید مستقل و دقیق (نه فقط نظری):**
- اجرای مستقیم `php vendor/bin/phpunit` روی یک تست که به `/api/admin/*` می‌زد، حدود **۴ گیگابایت رم مصرف کرد قبل از OOM-kill شدن توسط سیستم‌عامل**.
- حتی با `memory_limit=512M` صریح، پروسه دقیقاً داخل خود `MiddlewareNameResolver.php` کرش می‌کرد.
- با بررسی مستقیم baseline اصلی (کامیت pristine قبل از هر تغییری)، تأیید شد این باگ **از روز اول در زیپ آپلودی کاربر حضور داشته**.

**دامنه‌ی تأثیر:** این گروه توسط `routes/api.php` برای **تمام** روت‌های `/api/admin/*` استفاده می‌شه (`dashboard.php`, `reports.php`, `services.php`, `specialists.php` — هر ۴ فایل روت زیر `routes/api/admin/`) — یعنی هر درخواستی به هرکدوم از این مسیرها تضمین‌شده کرش می‌کرد.

**بررسی جدی بودن واقعی (نه فقط نظری):** با grep سراسری روی `resources/js/` و `resources/views/` تأیید شد **در حال حاضر هیچ مصرف‌کننده‌ی زنده‌ای** به هیچ‌کدوم از این روت‌ها وصل نیست — طبق تاریخچه‌ی مستندشده‌ی خود همین سند (مهاجرت کامل SPA به Blade در فازهای R-AdminBlog/R-AdminLoyalty/R-AdminAnnouncement-Gallery/...)، این روت‌های JSON بازمانده‌ی همون پنل‌های React قدیمی هستن که جایگزین Blade شدن. یعنی این یک «بمب خاموش» بود، نه یک incident فعال در حال رخ‌دادن — ولی همچنان یک باگ واقعی و جدی برای هر مصرف‌کننده‌ی آینده (مثلاً یک اپ موبایل جدید که بخواد از همین API های JSON استفاده کنه) و همچنین **کل این سطح از روت‌ها رو کاملاً غیرقابل‌تست می‌کرد**.

**فیکس:** نام گروه به `admin-api` تغییر کرد (هم در `bootstrap/app.php` هم تنها مصرف‌کننده‌ش در `routes/api.php`) تا دیگه با alias تک‌میدل‌وری `admin` که داخلشه تصادم نداشته باشه. خود alias `admin` و هرجای دیگه‌ای که ازش استفاده می‌کنه (`AdminMiddleware`) دست‌نخورده موند.

#### 🔴 کشف ۳ (در همون فایل، مستقل): `AdminMiddleware` با نام نقش اشتباه چک می‌کرد
```php
if ($user->hasRole('specialists')) {  // ❌ جمع — هیچ‌وقت مطابقت نداره
```
نقش واقعی seed‌شده و چک‌شده در **همه‌جای دیگه‌ی پروژه** (RoleSeeder، هر Policy، هر Form Request متخصص) `specialist` (مفرد) است. یعنی یک حساب که هم `is_admin=true` باشه هم نقش `specialist` داشته باشه، هیچ‌وقت به داشبورد متخصص ریدایرکت نمی‌شد و به‌اشتباه دسترسی کامل پنل ادمین رو نگه می‌داشت. علاوه بر این، نام روت مقصد ریدایرکت هم اشتباه بود (`specialist.dashboard` که وجود نداره؛ نام واقعی `specialist.my-dashboard`ه) — یعنی حتی بعد از فیکس اول، این شاخه با `RouteNotFoundException` کرش می‌کرد. هر دو فیکس شدن.

⚠️ **وابستگی مهم بین این دو کشف**: تا وقتی باگ recursion (کشف ۲) فیکس نشه، این مسیر (کشف ۳) اصلاً reachable/تست‌پذیر نیست — یعنی فیکس کشف ۳ بدون فیکس کشف ۲ عملاً بی‌فایده بود.

#### ⭐ درباره‌ی «کار گم‌شده»: سه فایل تست که از context قبلی همین نشست روی دیسک باقی مونده بودن
حین بررسی، مشخص شد سه فایل تست کامل (`LoyaltyAdminServiceTest.php`, `PaymentControllerTest.php`, و یک نسخه‌ی اولیه از تست AdminMiddleware در مسیر `tests/Feature/Middleware/`) از یک بخش قبلی‌تر همین نشست کاری روی دیسک وجود داشتن، بدون این‌که در پچ‌های تحویلی نشست اول یا در مستندسازی اون نشست ذکر شده باشن — به‌احتمال زیاد به‌خاطر یک وقفه‌ی context در همون نشست. **هر سه فایل مستقلاً و از نو بازبینی، تأیید صحت، و اجرا شدن** (نه صرفاً پذیرفته‌شدن کورکورانه) قبل از قرار گرفتن در پچ‌های نهایی؛ نسخه‌ی تکراری/قدیمی‌تر تست AdminMiddleware با نسخه‌ی جدیدتر (که مستقلاً و از نو، بدون اطلاع از وجود نسخه‌ی قبلی، در همین نشست دوم نوشته شده بود) ادغام شد — جالب اینکه هر دو تلاش مستقل دقیقاً همون دو باگ (کشف ۲ و ۳) رو پیدا کرده بودن، که خودش یک تأیید مضاعف بر صحت این یافته‌هاست.

**۱۳ تست `LoyaltyAdminServiceTest`**: CRUD کامل پاداش (با گارد حذف پاداش استفاده‌شده)، `redeemRewardForUser` (عملیات روی کاربر هدف نه ادمین لاگین‌شده — همون کلاس باگ قبلاً مستندشده)، `addPoints`/`deductPoints` (پاک‌سازی کش نوار ناوبری، انقضا در پایان روز، گارد موجودی ناکافی)، `getDashboardStats` (میانگین امتیاز، حالت لبه‌ی صفر کاربر).

**۱۱ تست `PaymentControllerTest`**: مسیر تخفیف کامل ۱۰۰٪ (رگرسیون‌گارد باگ صفحه‌سفید تاریخی مستندشده — `DB::transaction()` باید `Response` واقعی برگردونه نه `null`)، مسیر gateway واقعی با `Http::fake()`، و کل ماتریس پرداخت کیف‌پولی (کیف‌پول کامل بدون لمس gateway، هیچ‌وقت بیشتر از پیش‌پرداخت یا موجودی کسر نمی‌شه، ترکیب کیف‌پول+gateway، برگشت کیف‌پول در صورت شکست gateway بعد از کسر جزئی).

### نتیجه‌ی نهایی این نشست (تجمیعی با نشست اول)
**۲۲۰ تست، ۴۰۲ assertion، همگی PASS، در ~۷.۸ ثانیه** — تأیید نهایی روی یک کلون کاملاً تازه و مستقل از زیپ اصلی کاربر (نه محیط کاری)، با هر ۲۱ کامیت (۱۵ نشست اول + ۶ نشست دوم) به‌صورت `git am` بدون conflict اعمال‌شده.

### ۶ کامیت جدید این نشست (روی همون برنچ `test/comprehensive-test-suite-phase-1`)
```
fix(sms): skip real Kavenegar API calls in the testing environment, not just local
fix(routing): rename the self-referential 'admin' middleware group to prevent infinite recursion
fix(middleware): AdminMiddleware checks the real 'specialist' role, not 'specialists'
test(middleware): merge in the non-JSON redirect regression case for AdminMiddleware
test(loyalty): LoyaltyAdminService — reward CRUD, admin-redeem, points, dashboard stats
test(payment): PaymentController — full-discount, gateway, and wallet payment paths
```

### ⭐ درس عملیاتی مهم برای فازهای بعدی
دو یافته‌ی این نشست (کندی SMS، recursion میدل‌ور) **هیچ‌کدوم با صرفاً خوندن کد یا اجرای تست در یک sandbox بدون دسترسی شبکه‌ی واقعی قابل کشف نبودن** — کندی SMS فقط با دیدن زمان واقعی اجرا روی محیط واقعی کاربر (با اینترنت) لو رفت، و باگ OOM میدل‌ور فقط با اجرای واقعی PHPUnit (نه صرفاً `php -l`) روی یک روت واقعی لو رفت. این دقیقاً همون الگوی مستندشده‌ی «فرضیات باید با نمونه‌ی واقعی داده/محیط تأیید بشن، نه با خوندن امضای متد» است، این‌بار در سطح خود زیرساخت تست.

### قدم‌های باز (وضعیت پیش از نشست سوم — به‌روزرسانی کامل در بخش زیر)
- کنترلرهای ادمین دیگر (Reports, Blog, Announcement, Gallery, User management)
- `RegisteredUserController`/`AuthenticatedSessionController` مسیرهای resend/edge-case
- `SecurePaymentController`، مسیر ۲FA پرداخت
- Export گزارشات (Excel/PDF)
- `AdminReportService` (محاسبات مالی گزارش‌گیری)
- `EnsureTwoFactorVerifiedForPayment` middleware
- Jobs (`SendBookingReminderJob`, `ProcessWithdrawalJob`, `GeneratePdfReportJob` — با `Queue::fake()`)
- کاندید حذف: ارجاعات inert به `break_start`/`break_end` در `Specialist::getAvailableSlots()`
- ⭐ **کاندید جدید**: بررسی اینکه آیا `routes/api/admin/*` (که این نشست تأیید کرد کاملاً یتیمه) باید طبق الگوی مستندشده‌ی `R-Cleanup-DeadCode` حذف بشه یا نگه داشته بشه (تصمیم بیزنسی، نه فنی) — اگه نگه داشته می‌شه، حداقل حالا (بعد از فیکس این نشست) واقعاً usable و تست‌پذیره.

### ⭐⭐ فاز تست‌نویسی — نشست سوم (۲۰۲۶-۰۸-۱۱): تکمیل ۲FA/پرداخت امن + Jobs + AdminReportService — ۲۷۳ تست

**دسترسی:** این نشست دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۲۱ کامیت نشست اول+دوم بود — با اجرای کامل سوییت تأیید شد: ۲۲۰/۲۲۰ PASS، دقیقاً هم‌تراز مستندسازی قبلی).

**محیط:** از صفر بازسازی شد (PHP 8.3 + extensionها + Composer از GitHub Release + SQLite in-memory)؛ نیازی به رفع باگ محیطی جدیدی نبود (مشکل `config/view.php` از نشست‌های قبلی از قبل حل بود).

**یافته‌ی جانبی درباره‌ی مدت اجرا**: کاربر گزارش داد اجرای واقعی روی Windows/XAMPP این ۲۲۰ تست رو در ۴۲.۸۹ ثانیه (نه ~۷.۸ ثانیه‌ی مستندشده‌ی نشست دوم) تمام کرده. بررسی نشد که آیا این علامت یک ریگرسیون کندی مشابه باگ SMS نشست دوم است یا صرفاً overhead معمول دیسک/فایل‌سیستم Windows در برابر SQLite in-memory این‌بار — **کاندید بررسی برای نشست بعد** اگر این عدد در جلسات بعدی بازم بزرگ یا رو به رشد بود.

**۵۳ تست جدید در ۷ فایل، همگی روی محیط sandbox و بعداً روی fresh-clone مستقل تأیید شدن (نه فقط ادعا):**

| فایل | تعداد | پوشش |
|---|---|---|
| `Feature/Middleware/EnsureTwoFactorVerifiedForPaymentTest` | ۸ | گارد کامل میدل‌ور ۲FA پرداخت: guest→login، بدون ۲FA→ریدایرکت وب/۴۰۳ JSON، ارسال خودکار OTP + عدم ارسال دوباره روی درخواست تکراری، ۴۲۸+hint در مسیر JSON، عبور session تاییدشده، ریدایرکت صفحه‌ی OTP به intended URL |
| `Feature/Payment/SecurePaymentControllerTest` | ۹ | `initiate`/`verify`/`checkStatus`/`showCheckout` کامل: ساخت Payment، مالکیت، نوبت‌ازقبل‌پرداخت‌شده، ست‌شدن `payment_reference`/`payment_details` روی Booking، انقضا، idempotency تایید مجدد |
| `Feature/Jobs/ProcessWithdrawalJobTest` | ۶ | Payout موفق/ناموفق واقعی (mock شده) + دیسپچ `WithdrawalApproved`/`WithdrawalRejected` + race condition (status دیگه processing نیست) + رکورد گم‌شده + `failed()` hook |
| `Feature/Jobs/NotificationJobsTest` | ۶ | `SendBookingReminderJob` (هر دو گیرنده، booking گم‌شده، لاگ خطا بدون throw روی شکست جزئی) + `Send2faVerificationCodeJob`/`SendLoginVerificationCodeJob` (توکن/شماره‌ی درست، کاربر گم‌شده) |
| `Feature/Console/SendBookingRemindersTest` | ۶ | پنجره‌ی ۵۵-۶۵ دقیقه (مرز دقیق، خارج از پنجره)، idempotency `reminder_sent`، فیلتر وضعیت (فقط `confirmed`)، ست‌شدن synchronous فلگ قبل از اجرای واقعی Job |
| `Feature/Admin/Report/AdminReportServiceTest` | ۱۸ | `parseDateRange` (معتبر/نامعتبر/پیش‌فرض)، `getFinancialSummary` (تفکیک کیف‌پول/گیت‌وی/ثبت‌دستی — گارد ریگرسیون فیکس R-Observers)، `paymentBreakdown` (درصدها، بازه‌ی خالی بدون خطای تقسیم)، `dailyRevenue`، سهم متخصص پس از کمیسیون (هم در `specialistPerformance` هم `getRawBookingsForExport`، مستقل از هم)، نوع تخفیف در خروجی خام، لیبل «ثبت دستی ادمین»، شکل کلی `buildExportData`، `calcCompletionRate`/`calcReturnRate` |

**۱ باگ کوچک کشف/رفع‌شده:** `Booking::$casts` فیلد `reminder_sent` رو `boolean` نداشت — در کوئری‌های فعلی (`->where('reminder_sent', false)`, `->update(['reminder_sent' => true])`) بی‌اثر بود چون هیچ‌جا strict-boolean مقایسه نمی‌شد، ولی حین نوشتن `SendBookingRemindersTest` (که `assertTrue`/`assertFalse` سخت‌گیرانه می‌زد) خودش رو نشون داد. فیکس بی‌خطر: `'reminder_sent' => 'boolean'` به `$casts` اضافه شد.

**یافته‌ی مهم (محدودیت تست، نه باگ زنده):** `AdminReportService::weeklyRevenue()`/`monthlyRevenue()`/`monthlyBreakdown()` از توابع مخصوص MySQL (`YEARWEEK`, `YEAR`, `MONTH`) استفاده می‌کنن که روی SQLite (درایور تست پروژه) اصلاً وجود ندارن — با اجرای مستقیم این متدها روی SQLite تأیید شد (`SQLSTATE[HY000]: General error: 1 no such function: YEARWEEK` / `YEAR`). چون production این پروژه از اول MySQL-only بوده (تصمیم مستند تک‌استک)، این یک باگ زنده نیست، فقط یک gap تست‌پذیری روی SQLite برای این دو نوع دوره‌ی گزارش (هفتگی/ماهانه) است؛ `dailyRevenue()` (که از `DATE()` استفاده می‌کنه، پشتیبانی‌شده در هر دو درایور) و بقیه‌ی متدها کامل تست شدن. **تصمیم آگاهانه**: کوئری‌ها بازنویسی نشدن تا روی هر دو درایور کار کنن — این scope creep بی‌درخواست روی کد مالی حساس و کارکن بود، نه چیزی که کاربر خواسته باشه. اگر در آینده تست این دو مسیر لازم شد، راه‌حل احتمالی MySQL واقعی در CI (نه SQLite) است، نه تغییر کوئری.

**۷ کامیت این نشست (روی برنچ `test/comprehensive-test-suite-phase-2`، بر پایه‌ی `test/comprehensive-test-suite-phase-1`):**
```
fix(booking): cast reminder_sent to a real boolean
test(payment): EnsureTwoFactorVerifiedForPayment middleware full coverage
test(payment): SecurePaymentController full checkout/verify/status flow
test(jobs): ProcessWithdrawalJob success/failure/race-condition paths
test(jobs): SendBookingReminderJob, Send2fa/LoginVerificationCodeJob
test(console): SendBookingReminders 55-65 minute window + idempotency
test(admin): AdminReportService financial calculations
```

**نتیجه‌ی نهایی این نشست (تجمیعی با نشست اول+دوم): ۲۷۳ تست، ۵۳۶ assertion، همگی PASS، در ~۹.۵ ثانیه** — تأیید نهایی روی یک کلون کاملاً تازه و مستقل (نه محیط کاری)، هر ۷ پچ به‌صورت `git am` بدون conflict روی نوک برنچ نشست اول (کامیت `a9f7dbc`... در سند قبلی به اسم دیگه، اینجا معادل همون baseline پس از ۲۱ کامیت قبلی) اعمال و کل سوییت مجدداً اجرا شد.

⚠️ **نکته‌ی عملیاتی برای تحویل**: این ۷ پچ **باید بعد از هر ۲۱ پچ قبلی این فاز (نشست اول+دوم)** اعمال بشن، نه به‌جاشون — این نشست از همون baseline ادامه داده، پچ جدیدی برای فایل‌های نشست‌های قبلی نساخته.

### ⭐⭐ فاز تست‌نویسی — نشست چهارم (۲۰۲۶-۰۸-۱۲): CRUD ادمین (Blog/Announcement/Gallery/User) + export گزارش + edge caseهای OTP — ۳۴۷ تست

**دسترسی:** این نشست دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد. **تفاوت مهم با نشست‌های قبلی**: این زیپ برخلاف زیپ‌های قبلی این فاز، هیچ تاریخچه‌ی گیت (`.git/`) نداشت — یعنی همه‌ی ۲۸ کامیت نشست‌های اول/دوم/سوم از قبل به‌صورت فایل‌های ساده (نه کامیت‌های گیت) داخل زیپ ادغام شده بودن. محیط (PHP 8.3 + Composer + SQLite) از صفر بازسازی شد؛ باگ محیطی قبلاً مستندشده‌ی `config/view.php` این‌بار وجود نداشت (فایل داخل زیپ بود).

**تأیید baseline**: قبل از هر تغییری، کل سوییت روی زیپ خام اجرا شد → **۲۷۳ تست، ۵۳۶ assertion، همگی PASS** — دقیقاً هم‌تراز مستندسازی نشست سوم، تأیید شد چیزی بین نشست‌ها گم/خراب نشده.

**۷۴ تست جدید در ۸ فایل:**

| فایل | تعداد | پوشش |
|---|---|---|
| `Feature/Admin/AdminBlogTest` | ۹ | ساخت مقاله، رگرسیون‌گارد چک‌باکس `is_published` نزده‌شده، regeneration اسلاگ روی تغییر عنوان، بازیابی‌پذیری soft-delete، toggle-publish، جایگزینی تصویر روی آپدیت، ۴۰۳ غیرادمین، ساخت/رد نام تکراری دسته‌بندی |
| `Feature/Admin/AdminAnnouncementTest` | ۷ | ساخت، رگرسیون‌گارد چک‌باکس `is_active`، رد انقضای زودتر از انتشار، رد نوع نامعتبر، آپدیت/حذف، شمارش صحیح آمار index (فعال/در‌انتظار/منقضی)، ۴۰۳ غیرادمین |
| `Feature/Admin/AdminGalleryTest` | ۸ | آپلود، شماره‌ی ترتیب خودکار، حذف فایل+رکورد هم‌زمان، جابه‌جایی بالا/پایین با no-op در مرز، رد آپلود بدون تصویر، ۴۰۳ غیرادمین |
| `Feature/Admin/AdminUserManagementTest` | ۱۳ | ساخت کاربر با نقش، رد شماره‌تکراری (با self-exclusion روی ویرایش)، جلوگیری از حذف کاربر دارای نوبت، toggle وضعیت، ریست رمز عبور، sync نقش، فیلتر جستجو/نقش/وضعیت |
| `Feature/Admin/AdminReportExportTest` | ۱۲ | ساخت رکورد pending + دیسپچ Job، **تولید واقعی یک فایل اکسل** (غیرخالی، واقعاً باز می‌شه)، مسیر شکست واقعی PDF (نه mock — فونت‌های mPDF در این sandbox نصب نیستن، پس این مسیر واقعاً catch/failed می‌شه)، idempotency روی export تمام‌شده، `download()` برای حالت‌های ready/not-ready/فایل‌گم‌شده |
| `Feature/Admin/AdminReportRevenueApiTest` | ۷ | endpointهای daily/today/week/month/specialist-performance/satisfaction/popular-services — همه روی SQLite قابل‌دسترسن چون به کوئری‌های MySQL-only دست نمی‌زنن |
| `Feature/Auth/AuthResendAndEdgeCaseTest` | ۱۶ | resend ثبت‌نام/ورود بدون session، کد اشتباه/منقضی، ورود موفق + پاکسازی session، ریدایرکت متفاوت ادمین/کاربر عادی، لاگ صحیح `security_logs` (هم موفق هم ناموفق، با `user_id` درست) |
| `Unit/SecurityLogChannelConfigTest` | ۲ | رگرسیون‌گارد فیکس زیر |

### 🔴 باگ‌های واقعی کشف/رفع‌شده

**۱) سه مدل دیگر بدون `HasFactory`، دقیقاً همون الگوی مستندشده‌ی مدل `Leave`:**
`BlogCategory`, `Announcement`, `GalleryImage` — هر سه فایل فکتوری واقعی داشتن (`BlogCategoryFactory`, `AnnouncementFactory`, `GalleryImageFactory`) ولی خود مدل‌ها تریت `HasFactory` رو نداشتن؛ فراخوانی `Model::factory()` با `BadMethodCallException` کرش می‌کرد. **کشف جانبی مهم‌تر**: چون `BlogCategory` فاقد این تریت بود، فال‌بک داخلی خودِ `BlogPostFactory::definition()` (`BlogCategory::inRandomOrder()->first()?->id ?? BlogCategory::factory()`) هم روی هر دیتابیس خالی از کار می‌افتاد — یعنی این باگ حتی تست‌های خودِ فکتوری وبلاگ رو (نه فقط تست‌های مستقیم دسته‌بندی) در معرض خطر می‌ذاشت. هر سه مدل تریت رو گرفتن.

**۲) دو مدل بدون هیچ فایل فکتوری:**
`Review` و `ReportExport` تریت `HasFactory` داشتن ولی هیچ فایل `*Factory.php` متناظری در `database/factories` وجود نداشت — کاملاً غیرقابل‌استفاده برای تست. `ReviewFactory` و `ReportExportFactory` از صفر ساخته شدن.

**۳) 🔴 `SECURITY_LOG_LEVEL=`/`PAYMENTS_LOG_LEVEL=` خالی در `.env` → کانال‌های لاگ بی‌صدا به emergency logger تنزل پیدا می‌کردن:**
`config/logging.php` این دو خط رو داشت:
```php
'level' => env('SECURITY_LOG_LEVEL', 'warning'),   // کانال security
'level' => env('PAYMENTS_LOG_LEVEL', 'info'),      // کانال payments
```
`.env` این دو متغیر رو **موجود ولی خالی** تعریف کرده (`SECURITY_LOG_LEVEL=`، بدون مقدار). تابع `env('KEY', 'default')` در لاراول فقط برای متغیر **کاملاً تعریف‌نشده** به مقدار پیش‌فرض برمی‌گرده، نه برای رشته‌ی خالی — یعنی این دو کانال همیشه `level: ''` resolve می‌شدن، که یک سطح PSR-3 معتبر نیست.

**بررسی دقیق رفتار واقعی (نه فقط فرض نظری)**: تصور اولیه این بود که این باعث کرش کل درخواست (۵۰۰) می‌شه؛ بررسی عمیق‌تر (چند بار تست مستقیم با تینکر + خوندن سورس `LogManager::get()`) نشون داد **این‌طور نیست** — خود `Illuminate\Log\LogManager::get()` این خطا رو داخلی با `catch (Throwable $e)` می‌گیره و بی‌صدا به یک «emergency logger» (نوشتن در `storage/logs/laravel.log` با سطح `debug`) سوییچ می‌کنه، بدون این‌که درخواست HTTP کرش کنه. یعنی اثر واقعی این باگ **نه یک کرش قابل‌مشاهده، بلکه یک انحراف کاملاً بی‌صدای مسیر لاگ‌ها**ست: هر رخداد امنیتی (تلاش ورود موفق/ناموفق) و هر رخداد مالی، به‌جای رفتن به `security.log`/`payments.log` با retention و سطح فیلتر مخصوص خودشون، بی‌سروصدا در `laravel.log` عمومی با سطح `debug` گم می‌شن — دقیقاً همون چیزی که سیستم لاگ‌گذاری مجزای امنیتی/مالی این پروژه قرار بود جلوش رو بگیره.

**فیکس:** هر دو به `env('KEY') ?: 'default'` تغییر کردن (عملگر `?:` رشته‌ی خالی رو هم مثل `null` فال‌بک می‌زنه).

⚠️ **درس برای فازهای بعدی**: الگوی `env('KEY', 'default')` در سراسر `config/logging.php` (و احتمالاً بقیه‌ی فایل‌های config پروژه) باید با احتیاط بازبینی بشه — هر جا `.env` کلید رو *تعریف‌شده ولی خالی* نگه داشته (که برای متغیرهای اختیاری الگوی رایجیه)، فال‌بک `env()` عملاً بی‌اثره. فقط دو مورد بالا در این نشست چک شدن (چون مستقیماً باعث fail شدن تست‌های auth شدن)؛ بقیه‌ی کلیدهای `env(...,'default')` پروژه بررسی نشدن.

### ⭐ یافته‌ی جانبی: `AdminReportsController::index()`/`AdminReportRevenueController::financial()` هم از باگ تست‌پذیری SQLite نشست سوم رنج می‌برن
نشست سوم مستند کرده بود که `weeklyRevenue()`/`monthlyRevenue()`/`monthlyBreakdown()` (متدهای سرویس) روی SQLite با `SQLSTATE[HY000]: no such function: YEAR` fail می‌شن. این نشست با یک تست probe مستقیم HTTP تأیید کرد که این محدودیت به **سطح کنترلر/endpoint هم می‌رسه**: `AdminReportsController::index()` (یعنی خودِ صفحه‌ی وب `/admin/reports`) بدون قید `type`، همیشه `monthlyBreakdown()` رو **بدون شرط** صدا می‌زنه (نه فقط وقتی `type=monthly` باشه) — یعنی `GET /admin/reports` روی SQLite همیشه ۵۰۰ می‌ده، مستقل از پارامترها. طبق تصمیم آگاهانه‌ی نشست سوم (عدم بازنویسی کوئری‌های MySQL-only بدون درخواست صریح کاربر، چون روی production واقعی این پروژه که MySQL-only است این یک باگ زنده نیست)، این نشست هم کوئری رو دست نزد؛ به‌جاش تست‌ها روی endpointهای دیگه‌ای متمرکز شدن که این مسیر رو لمس نمی‌کنن (`AdminReportRevenueController::daily/today/week/month`, `AdminReportSpecialistController::performance/satisfaction`, `AdminReportExportController`).

### ⭐ نکته‌ی عملیاتی جدید: بازسازی baseline گیت وقتی زیپ تاریخچه‌ی گیت نداره
برخلاف نشست‌های قبلی این فاز (که زیپ آپلودی شامل کامیت‌های نشست‌های قبل بود و می‌شد مستقیم `git log`/`git am` روش کار کرد)، این زیپ هیچ پوشه‌ی `.git` نداشت. برای تولید پچ‌های قابل‌تحویل، روش زیر به کار رفت: (۱) یک کپی مجزا و کاملاً تازه از همون زیپ اصلی مستقیماً `git init` و به‌عنوان یک کامیت واحد `baseline` ثبت شد؛ (۲) فایل‌های تغییریافته‌ی این نشست (که در محیط کاری اصلی از قبل ساخته/ویرایش شده بودن) روی همین کپی baseline کپی و در ۷ کامیت منطقی جدا (۲ فیکس + ۵ تست) کامیت شدن؛ (۳) `git format-patch` روی این ۷ کامیت اجرا شد؛ (۴) پچ‌ها روی یک کلون *سوم*، کاملاً مستقل و تازه از همون زیپ اصلی، با `git am --keep-cr` تست شدن (بدون conflict) و کل سوییت (۳۴۷ تست) دوباره و با نتیجه‌ی یکسان اجرا شد. **درس برای آینده**: وقتی زیپ ورودی تاریخچه‌ی گیت نداره، تحویل مستقیم فایل (بدون پچ) کافی نیست چون کاربر روال تثبیت‌شده‌ی `git am` رو می‌خواد — باید حتماً این روش دو-کپی (یکی baseline تمیز، یکی محیط کاری با تغییرات) طی بشه، نه تلاش برای `git init` مستقیم روی پوشه‌ای که از قبل توش تغییر داده شده (چون اون‌وقت baseline و تغییرات توی یک کامیت قاطی می‌شن و دیگه قابل تفکیک به کامیت‌های جدا نیستن).

### ۷ کامیت این نشست (روی برنچ `test/comprehensive-test-suite-phase-3`، بر پایه‌ی زیپ خام قبل از هر تغییر این نشست)
```
fix(models): add missing HasFactory trait and create missing factories
fix(logging): fall back to a valid level when SECURITY_LOG_LEVEL/PAYMENTS_LOG_LEVEL are empty
test(admin): blog, announcement, and gallery CRUD
test(admin): user management CRUD
test(admin): async report export flow and revenue/specialist API endpoints
test(auth): registration/login OTP resend and session edge cases
test: regression guard for the empty-env log-level config fix
```

**نتیجه‌ی نهایی این نشست (تجمیعی با نشست اول+دوم+سوم): ۳۴۷ تست، ۷۱۶ assertion، همگی PASS، در ~۱۶-۱۹ ثانیه** — تأیید نهایی روی یک کلون سوم و کاملاً مستقل از زیپ اصلی (نه محیط کاری، نه حتی همون کپی baseline که پچ‌ها ازش ساخته شدن).

⚠️ **نکته‌ی عملیاتی برای تحویل**: چون این نشست هیچ گیت‌تاریخچه‌ی قبلی نداشت، این ۷ پچ **از صفر روی زیپ خام** ساخته شدن (نه ادامه‌ی ۲۸ کامیت قبلی این فاز). ترتیب اعمال درست: اول محتوای هر ۲۸ پچ نشست‌های اول/دوم/سوم (که از قبل با موفقیت commit شدن، طبق تأیید کاربر)، بعد این ۷ پچ جدید.

⚠️ **پیش‌نیاز عملیاتی برای اجرای تست روی محیط لوکال**: خطوط کامنت‌شده‌ی `phpunit.xml` (`<!-- <env name="DB_CONNECTION" value="sqlite"/> --> `/`<!-- <env name="DB_DATABASE" value=":memory:"/> -->`) باید دستی از حالت کامنت خارج بشن تا سوییت SQLite اجرا بشه — این خط عمداً داخل هیچ‌کدوم از پچ‌ها نبوده (طبق الگوی مستندشده‌ی نشست اول: این یک تنظیم موقت اجرای تست لوکاله، نه بخشی که باید در کد commit بمونه).

### ⭐ رفع مستقل (همون نشست، بلافاصله بعد از تحویل ۷ پچ بالا): پچ ۸ام — فیکس تست وابسته به محیط + تأیید نهایی ۳۴۷/۳۴۷ روی Windows/XAMPP واقعی

کاربر ۷ پچ بالا رو روی محیط واقعی Windows/XAMPP اعمال کرد و `php artisan test` رو اجرا کرد؛ نتیجه **۳۴۶ از ۳۴۷ تست PASS**، با یک شکست دقیقاً همون‌جایی که پیش‌بینی مستندشده‌ی این سند («این نشست فقط دو مورد لاگینگ رو بررسی کرد») صراحتاً هشدار داده بود می‌تونه محیط‌وابسته باشه:

```
FAILED  AdminReportExportTest > job marks export as failed and notifies when generation throw…
Failed asserting that two strings are identical.
-'failed'
+'ready'
```

**ریشه:** تست `job_marks_export_as_failed_and_notifies_when_generation_throws` روی این فرض نوشته شده بود که چون فایل‌های فونت mPDF (`storage/fonts/Vazirmatn-*.ttf`) در sandbox این نشست نصب نبودن، تولید PDF همیشه fail می‌شه — و همین fail شدن طبیعی رو به‌عنوان مسیر تست شکست Job استفاده کرده بود. ولی روی محیط واقعی کاربر این فونت‌ها واقعاً دیپلوی شدن، پس PDF واقعاً و با موفقیت تولید شد (`status='ready'`) — دقیقاً برخلاف چیزی که تست انتظار داشت. این یک باگ در کد پروژه **نبود**؛ یک تست بود که به‌جای رفتار قطعی، به یک شرط محیطی وابسته شده بود.

**فیکس:** تست بازنویسی شد تا به‌جای تکیه به نبود فونت، مستقیماً `AdminReportService::buildExportData()` رو با یک Mockery mock مجبور به `throw` کنه — این‌طوری مسیر `catch`/`failed`/نوتیفیکیشن `GeneratePdfReportJob` مستقل از وجود/عدم فونت‌های mPDF، همیشه و روی هر محیطی یکسان تست می‌شه.

**پچ ۸ام** (`0008-fix-test-make-the-PDF-generation-failure-test-enviro.patch`) روی یک کلون کاملاً تازه (شامل هر ۷ پچ قبلی، اعمال‌شده با `git am`) تست شد — بدون conflict، و کل سوییت مجدداً اجرا شد: **۳۴۷/۳۴۷ PASS**.

**تأیید نهایی روی Windows/XAMPP واقعی کاربر** (بعد از اعمال پچ ۸ام): **۳۴۷ تست، ۷۱۶ assertion، همگی PASS**، مدت کل اجرا **۸۹۸.۵۲ ثانیه (~۱۵ دقیقه)**.

### ⭐ بررسی مستقل: اختلاف زمان اجرا روی Windows (بسته شد — علت محیطی تأیید شد، نه باگ کد)

این آیتم از نشست سوم به بعد به‌عنوان «باز» فهرست شده بود. این نشست با دو گزارش کامل و متوالی از کاربر (یکی قبل و یکی بعد از پچ ۸ام، هر دو روی همون محیط Windows/XAMPP) بررسی و **بسته شد**:

**داده‌ی کلیدی**: تک‌تک زمان‌های هر تست بین این دو اجرا مقایسه شد. الگو **کاملاً نامنظم و غیرقابل‌پیش‌بینی** بود — نه فقط مجموع کل، بلکه این‌که *کدوم* کلاس تست کند بود هم بین دو ران عوض شد:
- ران اول: `PaymentControllerTest`/`SecurePaymentControllerTest` به‌شدت کند (تا ۹۳.۸۹ ثانیه برای یک تست تکی)؛ `BookingObserverTest`/`LoyaltyServiceTest` نسبتاً سریع.
- ران دوم (بعد از پچ ۸): دقیقاً برعکس — `PaymentControllerTest`/`SecurePaymentControllerTest` همه زیر ۱ ثانیه؛ `BookingObserverTest` تا ۶۷.۹۶ ثانیه برای یک تست، `LoyaltyServiceTest` تا ۴۱ ثانیه.

**رد فرضیه‌ی HTTP timeout واقعی**: تنها دو نقطه در کل کدبیس `app/` واقعاً `Http::` صدا می‌زنن (`PaymentService`, `ZarinpalPayoutService`، هر دو با `Http::timeout(30)`) — فرضیه‌ی اولیه این بود که یک تست بدون `Http::fake()` می‌تونه واقعاً منتظر تایم‌اوت ۳۰ ثانیه‌ای به زرین‌پال بمونه. این فرضیه با بررسی مستقیم کد رد شد: کندترین تست‌های ران اول (`test_process_with_a_full_discount_marks_the_booking_paid...`, ۷۱.۵۰ ثانیه) دقیقاً مسیر تخفیف ۱۰۰٪ رو تست می‌کنن که طبق کد **اصلاً به `Http::` نمی‌رسه** (پیش‌پرداخت صفره، درگاه کلاً بای‌پس می‌شه) — یعنی این تست هیچ تماس شبکه‌ای، نه واقعی نه فیک، نداره. همچنین `SecurePaymentControllerTest` (کندترین تست ران اول، ۳۸.۰۷ ثانیه) اصلاً به `PaymentService`/`Http::` وصل نیست — `SecurePaymentService` طبق معماری مستندشده‌ی این پروژه (بخش «رفع مستقل: تکمیل مسیر پرداخت امن/۲FA») فقط از یک بلاب رمزنگاری‌شده‌ی داخلی (نه گیت‌وی واقعی) استفاده می‌کنه.

**نتیجه‌گیری**: چون همون تست‌ها، با همون کد، بدون هیچ تماس شبکه‌ای، بین دو اجرای متوالی از چند ثانیه به ده‌ها ثانیه (و برعکس) نوسان می‌کنن، این کندی **قطعاً محیطی‌ست، نه یک باگ در کد یا تست‌ها** — امضای کلاسیک تداخل سطح سیستم‌عامل روی Windows (محتمل‌ترین مظنون: اسکن real-time آنتی‌ویروس/Windows Defender روی فایل‌های PHP لمس‌شده حین اجرا، یا رقابت منابع دیسک/CPU با پردازش‌های پس‌زمینه‌ی دیگه). **پیشنهاد عملی (فقط برای سرعت لوکال، بدون تأثیر بر صحت تست‌ها)**: پوشه‌ی پروژه (خصوصاً `vendor/` و `storage/`) به exclusion list آنتی‌ویروس اضافه بشه؛ در صورت تمایل، حین یک اجرا Task Manager باز بمونه تا پروسه‌ی واقعی مصرف‌کننده‌ی منابع شناسایی بشه. این آیتم دیگه به‌عنوان «باز» فهرست نمی‌شه — نتیجه‌ی تحقیق (نه یک فیکس کد) نهایی و مستند شد.

### قدم‌های باز برای نشست پنجم این فاز
- کنترلرهای ادمین باقی‌مانده: Role/Permission management، Review management (تأیید/رد نظر، پاسخ متخصص)، Notification controllers (Admin + Specialist + User)، Loyalty admin (reward CRUD از مسیر HTTP کامل، نه فقط Service که در نشست دوم پوشش داده شد)
- Excel/PDF export — خود فایل تولیدشده در سطح serialize واقعی (`AdminReportExport::registerEvents`، محتوای واقعی سلول‌های اکسل، نه فقط «فایل غیرخالیه») — این نشست فقط تأیید کرد فایل تولید و غیرخالیه، نه محتوای دقیق سلول‌ها
- `weeklyRevenue`/`monthlyRevenue`/`monthlyBreakdown` + `AdminReportsController::index()`/`AdminReportRevenueController::financial()` — یا با یک دیتابیس MySQL واقعی (نه SQLite) پوشش داده بشن، یا آگاهانه به‌عنوان «تست‌ناپذیر روی این استک تست» نهایی مستند بمونن (این نشست فقط دامنه‌ی مشکل رو به سطح کنترلر گسترش داد، فیکس/راه‌حل جدیدی ارائه نکرد)
- کاندید حذف: ارجاعات inert به `break_start`/`break_end` در `Specialist::getAvailableSlots()` (هنوز باز)
- کاندید بررسی: آیا `routes/api/admin/*` باید حذف بشه یا نگه داشته بشه (هنوز باز، تصمیم بیزنسی)
- ⭐ **کاندید جدید**: بازبینی سراسری الگوی `env('KEY', 'default')` در بقیه‌ی فایل‌های `config/*.php` پروژه برای یافتن نمونه‌های مشابه (کلید موجود ولی خالی در `.env`) — این نشست فقط دو مورد لاگینگ رو که مستقیماً باعث fail شدن تست شدن بررسی کرد، نه کل پروژه را

**نتیجه‌ی نهایی نشست چهارم (تجمیعی با نشست اول+دوم+سوم، شامل پچ ۸ام): ۳۴۷ تست، ۷۱۶ assertion، همگی PASS — تأیید نهایی هم روی sandbox (کلون تازه، ~۱۴ ثانیه) هم روی Windows/XAMPP واقعی کاربر (~۹۰۰ ثانیه، صرفاً به دلایل محیطی مستندشده‌ی بالا، نه صحت).**

### ⭐⭐ فاز تست‌نویسی — نشست پنجم (۲۰۲۶-۰۸-۱۶): کنترلرهای ادمین باقی‌مانده + کنترلرهای عمومی کاربر — ۵۵۱ تست

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۲۹ کامیت نشست‌های اول تا چهارم بود — با اجرای کامل سوییت تأیید شد: **۳۴۷ تست، ۷۱۶ assertion، همگی PASS**، دقیقاً هم‌تراز مستندسازی نشست چهارم؛ محیط از صفر با PHP 8.3 + Composer + SQLite in-memory بازسازی شد، بدون نیاز به رفع باگ محیطی تازه‌ای).

این نشست از لیست «قدم‌های باز برای نشست پنجم» شروع شد و علاوه بر اون آیتم‌ها، به سراغ کنترلرهای عمومی/غیر-ادمین (Home/Service/Specialist/BookingAvailability) هم رفت.

**۲۵ فایل تست جدید/commit، سازمان‌دهی‌شده در ۲۵ کامیت جدا** (طبق استاندارد «هر باگ‌فیکس/گروه تست منطقی، کامیت مستقل خودش») روی برنچ `test/comprehensive-test-suite-phase-4`، بر پایه‌ی همون ۲۹ کامیت قبلی این فاز:

| دسته | فایل‌ها | نکات کلیدی |
|---|---|---|
| **Excel واقعی** | `AdminReportExcelCellContentTest` | برخلاف نشست‌های قبلی («فایل غیرخالیه»)، این‌بار خود فایل `.xlsx` واقعی با PhpSpreadsheet باز و مقدار داخل سلول‌های مشخص (هدرها، ارقام درآمد، صفرهای واقعی نه سلول خالی) هر سه شیت assert شد + یک تست end-to-end کامل از مسیر `GeneratePdfReportJob` |
| **نقش/دسترسی** | `AdminRoleTest`, `AdminPermissionTest` | CRUD کامل، sync دسترسی‌ها (شامل پاک‌شدن کامل دسترسی‌ها وقتی کلید `permissions` اصلاً ارسال نشه)، گارد حذف دسترسی حیاتی، فیلتر گروه/جستجو، دسترسی از طریق نقش نه فقط `is_admin` |
| **نظرات** | `AdminReviewTest`, `SpecialistReviewControllerTest` | تایید/رد/ویژه/soft-delete/trashed/restore/force-delete + پاسخ متخصص (با گارد «یک بار پاسخ») — دقیقاً همین‌جا بود که دو باگ واقعی Review views کشف شد |
| **نوتیفیکیشن** | `AdminNotificationControllerTest`, `SpecialistNotificationControllerTest` | مورد دوم مشخصاً چون نوتیفیکیشن متخصص از دو منبع (`User` و `Specialist`، هر دو matched با phone) ادغام می‌شه، این ادغام رو صریح تست کرد |
| **لویالتی HTTP** | `AdminLoyaltyRewardHttpTest` | مکمل `LoyaltyAdminServiceTest` نشست دوم (که فقط Service بود) — این‌بار از مسیر کامل HTTP؛ همینجا باگ MaxPercentage کشف شد |
| **امنیت ادمین** | `AdminSecurityTest` | اولین پوشش تستی برای کل داشبورد امنیتی که در جلسه‌ی ۲۰۲۶-۰۸-۰۶ ساخته شده بود ولی هیچ‌وقت تست نشده بود — لاگ‌ها (فیلتر/آمار)، کاربران (فعالیت مشکوک با آستانه‌ی ۳۰ روز)، تنظیمات |
| **متخصص/دسته‌بندی/خدمت** | `AdminSpecialistTest`, `AdminCategoryTest`, `AdminServiceTest` | CRUD کامل هر سه (قبلاً فقط نرمالایز شماره تلفن پوشش داده شده بود) |
| **تعطیلات/مرخصی** | `AdminHolidayTest`, `AdminSpecialistLeaveTest`, `AdminLeaveTest` | تداخل با مرخصی/نوبت، مسیر گلوبال، رگرسیون‌گارد `Leave::$fillable` (باگ کشف‌شده‌ی همین نشست) |
| **برنامه کاری** | `AdminSpecialistScheduleTest` | جایگزینی کامل (نه merge) + رگرسیون‌گارد swallow شدن `ValidationException` |
| **جستجو/پروفایل ادمین** | `AdminSearchTest`, `AdminProfileTest` | جستجوی گروه‌بندی‌شده‌ی فارسی + تغییر رمز/نام ادمین |
| **کنترلرهای عمومی کاربر** | `HomeControllerTest`, `ServiceControllerTest`, `SpecialistControllerTest`, `BookingAvailabilityControllerTest` | اولین پوشش تستی این چهار کنترلر در کل پروژه؛ همینجا دو باگ مهم (`getSpecialistsByService`، import گمشده‌ی `Exception`) و یک یافته‌ی بزرگ (روت‌های نیمه‌کاره‌ی `ServiceController`) کشف شد |

### ⭐ ۷ باگ واقعی کشف/رفع‌شده

**۱) `MaxPercentage` روی `discount_amount` پاداش‌های لویالتی، بدون قید نوع** — همون رگرسیونی که در R-AdminForms برای `StoreDiscountCodeRequest` فیکس شده بود (فقط وقتی `type === percentage` اعمال بشه)، هیچ‌وقت به فرم‌های خواهر `Store/UpdateLoyaltyRewardRequest` منتقل نشده بود. نتیجه: هر پاداش نوع «مبلغ ثابت» با رقم واقعی (ده‌ها هزار تومان) همیشه رد می‌شد چون >۱۰۰ بود. کشف‌شده حین نوشتن `AdminLoyaltyRewardHttpTest`؛ هر دو Form Request فیکس شدن.

**۲) `admin/reviews/trashed.blade.php` اصلاً وجود نداشت** — روت و متد کنترلر (`AdminReviewController::trashed()`) کاملاً سالم و کار می‌کردن، ولی رندر همیشه ۵۰۰ می‌داد چون خود فایل Blade نبود. از صفر ساخته شد (هم‌سبک `index.blade.php`).

**۳) `admin/reviews/show.blade.php` به یک route name ناموجود اشاره می‌کرد** — `route('admin.reviews.feature', ...)` در حالی که نام واقعی روت `admin.reviews.toggle-featured`ه؛ دکمه‌ی «ویژه‌کردن» در صفحه‌ی جزئیات هر نظر همیشه فتال بود. فیکس شد.

**۴) `admin/loyalty/create.blade.php` اصلاً وجود نداشت** — دقیقاً همون الگوی مورد ۲؛ روت/کنترلر سالم، فقط فایل view نبود. از صفر ساخته شد (هم‌سبک `edit.blade.php`، با پیش‌نمایش JS نوع تخفیف).

**۵) `Leave::$fillable` فاقد `approved_at`/`rejected_at` بود** — `Leave::approve()`/`reject()` هر دو یک `update()` ساده‌ی mass-assignment هستن، ولی این دو ستون تایم‌استمپ در `$fillable` نبودن؛ یعنی `status` درست عوض می‌شد ولی `approved_at`/`rejected_at` برای همیشه `null` می‌موند — دقیقاً همون الگوی تکراری «فرم/آپدیت مقدار می‌گیره، mass-assignment بی‌صدا دورش می‌ریزه» (مثل `admin_commission_percentage`، `description`/`order` در `BlogCategory`)، این‌بار روی مدل `Leave`. کشف‌شده حین نوشتن `AdminSpecialistLeaveTest`؛ هر دو ستون به `$fillable` اضافه شدن.

**۶) `AdminSpecialistScheduleController::update()` یک `catch (\Exception $e)` گسترده داشت که `ValidationException` رو هم قورت می‌داد** — چون `ValidationException` از `\Exception` ارث‌بری می‌کنه، خطای معمولی «ساعت پایان قبل از ساعت شروع» به‌جای ریدایرکت استاندارد لاراول (با `$errors` در session، قابل نمایش با `@error`)، به یک پیام کلی‌شده‌ی ترجمه‌نشده تبدیل می‌شد: `"خطا در ذخیره اطلاعات: validation.after"` — کلید خام i18n، نه متن واقعی. فیکس: `catch (ValidationException $e) { throw $e; }` قبل از catch گسترده اضافه شد تا رفتار پیش‌فرض لاراول (که در بقیه‌ی پروژه با Form Request همه‌جا استفاده می‌شه) دوباره کار کنه.

**۷) 🔴 باگ ظریف‌تر: `BookingAvailabilityController::getSpecialistsByService()` همیشه شکست می‌خورد** — پروژه یک `Route::bind('service', ...)` سراسری در `RouteServiceProvider` داره که هر پارامتر روت با نام دقیقاً `{service}` رو (فارغ از اینکه متد کنترلر مقصد type-hint مدل داشته باشه یا نه) از قبل به یک نمونه‌ی resolve‌شده‌ی `BeautyService` تبدیل می‌کنه. این متد مستقیم `BeautyService::findOrFail($serviceId)` صدا می‌زد، در حالی که `$serviceId` از قبل یک **آبجکت مدل** بود، نه یک ID خام — یعنی `findOrFail()` همیشه با «No query results» شکست می‌خورد، حتی برای یک سرویس کاملاً معتبر و موجود. این باگ روی هر دو روت ثبت‌شده با `{service}` (`routes/web/bookings.php`, `routes/api/user/bookings.php`) فعال بود؛ روت سوم (`routes/api/public/specialists.php`) که با نام متفاوت `{serviceId}` ثبت شده بود، از این باگ در امان بود (چون فقط نام دقیق `service` باعث فعال‌شدن binder سراسری می‌شه). جالب اینکه همین فایل از قبل یک الگوی دفاعی دقیقاً برای همین مشکل (با پارامتر `{specialist}`) داشت: متد خصوصی `resolveSpecialist()` که چک می‌کنه آیا مقدار از قبل یک نمونه‌ی مدله یا نه. فیکس با همون الگو: یک `resolveService()` جدید اضافه شد و جایگزین فراخوانی مستقیم `findOrFail()` شد.

**۸) `SpecialistController::getAvailableDates()` بدون `use Exception;`** — `catch (Exception $e)` بدون import، در namespace `App\Http\Controllers\User` به‌صورت `App\Http\Controllers\User\Exception` (کلاس ناموجود) resolve می‌شه؛ اگه try body واقعاً throw می‌کرد، خود catch clause فتال می‌داد به‌جای گرفتن exception و برگردوندن JSON ۵۰۰ مورد نظر. دقیقاً معکوس همون باگی که قبلاً در R-Events کشف/رفع شده بود (اونجا یک `use` اشتباه اضافه بود؛ اینجا یک `use` لازم غایب بود). فیکس: `use Exception;` اضافه شد.

### ⭐ یافته‌ی بزرگ، فقط مستند شد (خارج از scope تست‌نویسی)

`routes/web/services.php` به حدود ۱۴ متد روی `ServiceController` اشاره می‌کنه (`search`, `filter`, `byCategory`, `popular`, `newest`, `discounted`, `compare`, `favorites`, `addToFavorites`, `removeFromFavorites`, `history`, `similar`, `addReview`, `getReviews`) که **هیچ‌کدوم اصلاً روی کلاس تعریف نشدن** — یک فیچر کامل نیمه‌کاره (جستجو/فیلتر پیشرفته‌ی خدمات، سیستم علاقه‌مندی‌ها، نظر‌دهی سطح خدمت) که هیچ‌وقت پیاده‌سازی نشده. با grep سراسری روی `resources/views`/`resources/js` تأیید شد **هیچ‌جای پروژه به هیچ‌کدوم از این روت‌ها لینک نمی‌ده** — یعنی این یافته کاملاً کد مرده/نیمه‌کاره‌ست، نه یک باگ فعال که کاربری رو تحت تأثیر بذاره.

دو مکانیزم شکست هم‌زمان وجود داره:
- هر چیزی به فرم `/services/{x}` (search, filter, popular, newest, discounted, compare, history) قبل از رسیدن به مشکل «متد ناموجود»، توسط یک روت قدیمی‌تر و عمومی‌تر (`/services/{service}`، ثبت‌شده در `routes/web/public.php`، که زودتر لود می‌شه) قاپیده می‌شه — implicit route-model-binding سعی می‌کنه "search" رو به‌عنوان یک `BeautyService` resolve کنه، شکست می‌خوره، و یک ۴۰۴ ساده برمی‌گرده (نه فتال).
- `/favorites` و مسیرهای مشابه (که این تصادم نام رو ندارن) واقعاً با «Call to undefined method» ۵۰۰ می‌دن.

دو تست مستقل (`test_the_documented_dead_favorites_route_currently_fatals`, `test_the_documented_shadowed_search_route_currently_404s`) دقیقاً همین رفتار فعلی (نه ایده‌آل) رو pin کردن — تا اگه در آینده تصمیم به تکمیل یا حذف این فیچر گرفته شد، تغییر رفتار آشکار و عمدی باشه، نه یک شکست خاموش تست. **این یافته عمداً فیکس نشد** — پیاده‌سازی ~۱۴ متد ناموجود (که نیازمند تصمیمات معماری/schema جدید مثل جدول favorites هست) کاملاً خارج از scope یک نشست تست‌نویسیه؛ کاندید یک فاز فیچر جدا یا حذف کامل این روت‌های مرده در `R-Cleanup-DeadCode`.

### ⭐ تست skip شده (نه fail) — همون الگوی MySQL-only مستندشده‌ی نشست سوم

`SpecialistController::topRated()` از یک `HAVING` روی ستون‌های alias‌شده‌ی subquery (`bookings_avg_rating`, `rating_count`) بدون `GROUP BY` استفاده می‌کنه. MySQL (تنها دیتابیس production این پروژه) این رو مجاز می‌دونه؛ SQLite (استک تست) نه — `SQLSTATE[HY000]: HAVING clause on a non-aggregate query`. دقیقاً همون الگوی مستندشده‌ی `weeklyRevenue`/`monthlyRevenue` (توابع `YEAR`/`YEARWEEK` مخصوص MySQL). طبق تصمیم آگاهانه‌ی نشست‌های قبلی، کوئری (که روی production واقعی درست کار می‌کنه) بازنویسی نشد؛ تست با `markTestSkipped()` و توضیح کامل مستند شد، نه silently نادیده گرفته یا به‌غلط fail شده.

### نتیجه‌ی نهایی نشست پنجم (تجمیعی با هر ۴ نشست قبلی)
**۵۵۱ تست، ۱۲۲۸ assertion، همگی PASS + ۱ skip مستند** — تأیید نهایی روی یک کلون کاملاً تازه و مستقل از زیپ اصلی (نه محیط کاری، نه baseline)، هر ۲۵ پچ این نشست با `git am` بدون conflict روی نوک ۲۹ کامیت قبلی این فاز اعمال و کل سوییت مجدداً و با همون نتیجه اجرا شد.

⚠️ **نکته‌ی عملیاتی برای تحویل**: این ۲۵ پچ باید **بعد از** هر ۲۹ پچ نشست‌های اول تا چهارم اعمال بشن، نه به‌جاشون.

### قدم‌های باز برای نشست ششم این فاز
- کنترلرهای کاربر باقی‌مانده: `UserWalletController`، `ReviewController` (کاربر عادی)، `ProfileController` (کاربر عادی)، `Specialist\Wallet\Iban`، `Specialist\Profile`، `Specialist\Report`
- `Api\V1\LoyaltyController` (مسیر API جدای لویالتی، هنوز پوشش تستی نداره)
- تصمیم بیزنسی باز: `routes/web/services.php` — ~۱۴ متد نیمه‌کاره‌ی `ServiceController` (فیچر جستجو/فیلتر/علاقه‌مندی/نظر خدمات) کامل بشه یا حذف بشه؟
- دو کنترلر ادمین دیگه که این نشست کشف کرد کاملاً یتیمن (بدون هیچ روت): `AdminLoyaltySettingsController`, `AdminLoyaltyPointsController` — کاندید حذف در `R-Cleanup-DeadCode` یا wire‌کردن به روت واقعی
- `App\Http\Controllers\User\NotificationController` هم کاملاً یتیمه (بدون روت) — همون تصمیم بالا
- کاندید حذف قدیمی هنوز باز: ارجاعات inert به `break_start`/`break_end` در `Specialist::getAvailableSlots()`
- کاندید بررسی قدیمی هنوز باز: آیا `routes/api/admin/*` باید حذف بشه یا نگه داشته بشه
- بازبینی سراسری الگوی `env('KEY', 'default')` در بقیه‌ی فایل‌های `config/*.php` (نشست چهارم فقط دو مورد لاگینگ رو بررسی کرد)

## ⭐⭐ فاز تست‌نویسی — نشست ششم (۲۰۲۶-۰۸-۱۶): پنل خودِ متخصص + کیف‌پول/نظرات کاربر — ۶۰۵ تست

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۲۹ کامیت نشست‌های اول تا پنجم بود — با اجرای کامل سوییت تأیید شد: **۵۵۱ تست، ۱۲۲۸ اسرشن، همگی PASS + ۱ skip مستند**، دقیقاً هم‌تراز مستندسازی نشست پنجم). محیط از صفر با PHP 8.3 + Composer + SQLite بازسازی شد.

این نشست از لیست «قدم‌های باز برای نشست ششم» شروع شد.

### 🔴 مهم‌ترین کشف: کل پنل خودِ متخصص عملاً همیشه ۴۰۳ می‌داد
`SpecialistPolicy` (تمام ability ها: `view`, `update`, `manageBookings`, `manageWallet`, `manageSchedule`, `manageLeaves`, `deleteLeave`) و `ReviewPolicy` (`view`, `respond`)، به‌همراه ۴ FormRequest (`UpdateIbanRequest`, `StoreLeaveRequest`, `UpdateScheduleRequest`, و `RespondReviewRequest` بلااستفاده)، همگی `$user->hasRole('specialist')` چک می‌کردن — دقیقاً همون باگی که یک‌بار قبلاً فقط برای `StoreWithdrawalRequest` کشف/فیکس شده بود («فیچر تسویه‌ی دستی کیف‌پول توسط ادمین»)، ولی این‌بار مشخص شد ریشه‌ی اصلی مشکل (خودِ `SpecialistPolicy`/`ReviewPolicy`) هیچ‌وقت فیکس نشده بود.

**تأیید با تست HTTP واقعی (بدون اساین کردن هیچ نقشی، دقیقاً مثل production واقعی)**: ویرایش شبا، `GET`/`POST` صفحه‌ی مرخصی، ویرایش برنامه‌ی کاری، ویرایش پروفایل، مشاهده/پاسخ به نظر، و لیست نوبت‌های متخصص — همه ۴۰۳ می‌دادن. فقط `SpecialistWalletPolicy` (که از قبل درست، فقط بر پایه‌ی تطابق شماره‌تلفن نوشته شده بود) سالم بود.

**فیکس:** هر دو Policy به همون الگوی درست `SpecialistWalletPolicy` (فقط `$user->specialist?->id === $specialist->id`، بدون هیچ چک نقشی) هم‌راستا شدن؛ FormRequest ها به `auth()->check()` ساده تغییر کردن (چک واقعی به لایه‌ی Policy در کنترلر واگذار شد).

### 🔴 باگ‌های واقعی دیگر کشف/رفع‌شده حین همین بررسی

1. **`UpdateSpecialistProfileRequest` روی ستون ناموجود `email` validate می‌کرد**: جدول `users` هیچ ستون `email` نداره (کل پروژه بر پایه‌ی phone هست — همون یافته‌ی قبلی برای `ProfileUpdateRequest` مشتری، این‌بار در نسخه‌ی متخصص). ارسال هر مقداری در فیلد اختیاری «ایمیل» فرم، کرش SQL واقعی می‌داد. حذف شد (rule + کد مرده‌ی `isDirty('email')`/`email_verified_at` در کنترلر + خودِ فیلد در Blade).

2. **`HasJalaliDates::parseJalaliOrFail()` نوع بازگشتی اشتباه داشت**: `Jalalian::toCarbon()` واقعاً `Carbon\Carbon` (کلاس پایه) برمی‌گردونه، نه `Illuminate\Support\Carbon` (که این کلاس ازش ارث‌بری می‌کنه). این باعث `TypeError` واقعی روی **هر** پارس موفق می‌شد — نه فقط شکست. در `parseJalali()` (نسخه‌ی غیر-throw) توسط `catch(\Throwable)` خودش بی‌صدا قورت داده می‌شد؛ ولی در `SpecialistLeaveController::store()` باعث ۵۰۰ فتال می‌شد، و در `BlogPostService::resolvePublishedAt()` (ویرایش تاریخ انتشار وبلاگ) توسط `catch(Throwable)` یک لایه بالاتر (خودِ کنترلر) قورت داده می‌شد — یعنی **ویرایش تاریخ انتشار مقاله همیشه با خطای کلی fail می‌شد و تاریخ هیچ‌وقت واقعاً اعمال نمی‌شد**. فیکس: `Carbon::instance(...)`.

3. **۶ فایل Blade دیگه با نام حروف‌بزرگ** (`admin/blog/Edit.blade.php`, سه فایل `admin/blog/categories/*`, `blog/Index.blade.php`, `blog/Show.blade.php`) — رگرسیون همون الگوی همیشگی. تأیید شد **در همین sandbox لینوکس فعال بود** (نه فقط ریسک نظری روی production): `GET admin.blog.edit` مستقیماً با «View not found» فتال می‌داد. `admin/notifications/Show.blade.php` هم یک stale git-index entry بود (محتوای درست از قبل روی دیسک بود، فقط گیت هنوز نسخه‌ی قدیمی رو track می‌کرد) — به‌جای rename دوباره، ایندکس گیت اصلاح شد.

4. **`UserWalletController::processCharge()` — مقدار پاک‌سازی‌شده هیچ‌وقت به request مرج نمی‌شد**: کد ارقام فارسی/کاما رو تبدیل و پاک می‌کرد (`$amountInput`) ولی بعدش `$request->validate()` رو مستقیم روی مقدار خام اصلی اجرا می‌کرد. نتیجه: شارژ کیف‌پول با هر مبلغ فرمت‌شده‌ی فارسی (که یک ورودی کاملاً طبیعی و مورد انتظاره، دقیقاً هم‌الگو با فیلد برداشت متخصص) همیشه رد می‌شد. فیکس: `$request->merge(['amount' => $amountInput])` قبل از validate.

5. **`ReviewService::createReview()` بدون کامنت کرش می‌کرد**: `'review' => $data['comment']` بدون null-coalescing، در حالی که `comment` طبق `StoreReviewRequest` کاملاً اختیاریه (وقتی ارسال نشه، کلید اصلاً در آرایه‌ی validated وجود نداره، نه اینکه null باشه). نتیجه: «Undefined array key» که توسط `catch(\Exception)` بیرونی قورت داده می‌شد — ولی این اتفاق **بعد از** ساخت موفق ردیف `Review` می‌افتاد، یعنی یک وضعیت نیمه‌کاره‌ی خطرناک باقی می‌موند: ردیف `Review` ساخته شده بود، ولی `booking->reviewed_at` هیچ‌وقت ست نمی‌شد، متخصص نوتیف نمی‌گرفت، و امتیاز وفاداری هم اضافه نمی‌شد.

6. **`Booking::$fillable` فاقد `reviewed_at` بود** — همون الگوی تکراری «فرم/آپدیت مقدار می‌گیره، mass-assignment بی‌صدا دورش می‌ریزه» (مثل `admin_commission_percentage`، `description`/`order` در `BlogCategory`، `approved_at`/`rejected_at` در `Leave`). یعنی حتی بدون باگ #۵، این فیلد هیچ‌وقت واقعاً persist نمی‌شد و گارد «قبلاً نظر داده شده» عملاً همیشه بی‌اثر بود. فیکس: به `$fillable`/`$casts` اضافه شد.

7. **`ReviewController::specialistReviews()` — تصادم با global route-model-binder**: `RouteServiceProvider::configureModelBindings()` هر پارامتر روت با نام دقیقاً `{specialist}` رو خودکار به یک نمونه‌ی `Specialist` resolve می‌کنه، فارغ از type-hint متد مقصد — دقیقاً همون الگوی مستندشده‌ی نشست پنجم برای `{service}`. این متد مستقیم `Specialist::findOrFail($specialistId)` صدا می‌زد در حالی که `$specialistId` از قبل یک آبجکت مدل بود → همیشه «No query results» → **صفحه‌ی نظرات هر متخصص برای هر بازدیدکننده‌ای ۴۰۴ می‌داد**. فیکس: همون الگوی `resolveSpecialist()`/`resolveService()`ی نشست پنجم.

8. **ویوی `reviews/specialist-reviews.blade.php` اصلاً وجود نداشت** — حتی بعد از فیکس بالا، این صفحه هیچ‌وقت رندر نمی‌شد. از صفر ساخته شد.

9. **`SpecialistReportController::index()` — نوع بازگشتی اشتباه**: `View` اعلام شده بود، ولی خودِ متد هم `Excel::download()` (یک `BinaryFileResponse`) هم خروجی `$mpdf->Output(..., 'D')` (که `null` برمی‌گردونه) رو هم return می‌کرد — هر دو `TypeError` واقعی می‌دادن. یعنی **دکمه‌های خروجی اکسل/PDF گزارش خودِ متخصص همیشه فتال می‌دادن**. فیکس: نوع بازگشتی broaden شد به union type متناسب با هر سه مسیر واقعی.

### فایل‌های گم‌شده‌ی دیگر ساخته‌شده
- `database/factories/UserWalletFactory.php`, `UserWalletTransactionFactory.php` — همون الگوی تکراری فکتوری‌های گم‌شده (`Review`, `ReportExport`, `BlogCategory`, `Announcement`, `GalleryImage` قبلاً در نشست‌های ۴/۵ کشف شده بودن).

### تأیید شد کاملاً یتیم (بدون هیچ روت) — تصمیم بیزنسی باز، اقدامی انجام نشد
`App\Http\Controllers\Api\V1\LoyaltyController`، `Admin\Loyalty\AdminLoyaltySettingsController`، `Admin\Loyalty\Point\AdminLoyaltyPointsController`، `App\Http\Controllers\User\NotificationController` — هر چهار کلاس با `grep` سراسری روی `routes/` تأیید شدن که هیچ‌جا route نشدن. مثل نمونه‌های مشابه قبلی این پروژه، تصمیم حذف/wire‌کردن به کاربر واگذار شد.

### فایل‌های تست جدید/تکمیل‌شده این نشست (۹ کامیت، ۵۴ تست جدید)

| فایل | تعداد | پوشش |
|---|---|---|
| `SpecialistSelfServiceAuthorizationTest` (جدید) | ۹ | تمام endpoint های نوشتنی پنل متخصص، **بدون** اساین کردن نقش |
| `HasJalaliDatesTest` (جدید، Unit) | ۴ | رفتار صحیح `parseJalali`/`parseJalaliOrFail` بعد از فیکس نوع |
| `AdminBlogTest` (افزوده) | ۳ | رندر صفحه‌ی ویرایش، رندر صفحات دسته‌بندی، ویرایش واقعی تاریخ انتشار جلالی |
| `BlogControllerTest` (جدید، عمومی) | ۶ | اولین پوشش کنترلر وبلاگ عمومی |
| `UserWalletControllerTest` (جدید) | ۱۰ | index/transactions/show/charge/callback، شامل رگرسیون‌گارد باگ #۴ |
| `ReviewControllerTest` (جدید) | ۱۲ | فلوی کامل token-based، شامل رگرسیون‌گارد باگ‌های #۵/#۶ |
| `SpecialistReportControllerTest` (جدید) | ۹ | فیلترها، محاسبه‌ی سهم پس از کمیسیون، رگرسیون‌گارد باگ #۹ |

### روش تحویل و تأیید
۹ کامیت جدا (طبق استاندارد پروژه) روی برنچ `V2` (کاربر تأیید کرد پایه‌ی کار همین برنچه، نه `develop`)، به‌صورت `git format-patch` تحویل داده شد. تأیید نهایی روی یک کلون **کاملاً مستقل و تازه** از همون زیپ اصلی کاربر (نه محیط کاری) با `git apply --reject --whitespace=fix` (بدون هیچ `.rej`) انجام شد؛ محیط از صفر (composer install، storage dirs) بازسازی و کل سوییت اجرا شد:

**نتیجه‌ی نهایی این نشست (تجمیعی با هر ۵ نشست قبلی، روی کلون کاملاً مستقل): ۶۰۵ تست، ۱۳۴۲ اسرشن، همگی PASS + ۱ skip مستند.**

⚠️ **نکته‌ی عملیاتی برای تحویل**: این ۹ پچ باید **بعد از** هر ۲۹ پچ نشست‌های اول تا پنجم اعمال بشن. اگه `git am` روی هر پچی fail کرد (مثلاً به‌خاطر یک rename قبلی که `git mv` واقعی نشده)، طبق تجربه‌ی این نشست `git apply --reject --whitespace=fix` معمولاً موفق می‌شه چون به تاریخچه/ایندکس گیت کمتر حساسه.

### قدم‌های باز برای نشست هفتم
- `Specialist\Wallet\Iban` — پوشش HTTP مستقیم (فعلاً فقط از دل `SpecialistSelfServiceAuthorizationTest` پوشش گرفته، نه یک فایل تست اختصاصی با edge caseهای بیشتر مثل شبای نامعتبر)
- تصمیم بیزنسی: چهار کنترلر کاملاً یتیم (`Api\V1\LoyaltyController`, `AdminLoyaltySettingsController`, `AdminLoyaltyPointsController`, `User\NotificationController`) — حذف یا wire کردن؟
- `break_start`/`break_end` inert در `Specialist::getAvailableSlots()` (هنوز باز، از نشست‌های قبل)
- تصمیم بیزنسی: `routes/api/admin/*` (هنوز باز)
- بازبینی سراسری الگوی `env('KEY', 'default')` در بقیه‌ی فایل‌های `config/*.php` (نشست چهارم فقط دو مورد لاگینگ رو بررسی کرد)
- ⭐ **کاندید جدید**: بررسی اینکه آیا الگوی `RouteServiceProvider::configureModelBindings()` (`{specialist}`, `{service}`, `{booking}`, `{user}`) در جاهای دیگه‌ای هم که هنوز کشف نشده، همین تصادم رو ایجاد می‌کنه — این نشست سومین نمونه‌ی این باگ رو پیدا کرد (بعد از `{service}` در نشست ۵، این‌بار `{specialist}` در `ReviewController`)؛ یک بررسی سراسری `grep -rn "findOrFail(\$specialistId)\|findOrFail(\$bookingId)\|findOrFail(\$userId)"` در کل `app/Http/Controllers` می‌تونه نمونه‌های باقی‌مونده رو زودتر شکار کنه.

## ⭐⭐ فاز تست‌نویسی — نشست هفتم (۲۰۲۶-۰۸-۱۸): تکمیل Iban + کیف‌پول/برداشت متخصص + بازبینی env خالی — ۶۳۶ تست

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۳۸ کامیت نشست‌های اول تا ششم بود — با اجرای کامل سوییت تأیید شد: **۶۰۵ تست، ۱۳۴۲ اسرشن، همگی PASS + ۱ skip مستند**، دقیقاً هم‌تراز مستندسازی نشست ششم). محیط از صفر با PHP 8.3 + Composer + SQLite بازسازی شد.

این نشست از لیست «قدم‌های باز برای نشست هفتم» کار کرد.

### ۱) پوشش HTTP اختصاصی `Specialist\Wallet\Iban` (۱۳ تست جدید)
`SpecialistIbanControllerTest` ساخته شد: رندر صفحه‌ی ویرایش + حالت profile-not-found، پیشوند `IR`، حذف فاصله از شبا، ریست `iban_verified` به false روی هر آپدیت، رد شبای کوتاه‌تر از ۲۴ رقم، رد شبایی که از قبل پیشوند `IR` داره (چون rule فقط فاصله رو حذف می‌کنه، نه پیشوند)، اعتبارسنجی نام صاحب حساب/بانک، ریدایرکت مهمان.

**کشف/رفع کد مرده**: تأیید شد `App\Http\Requests\Specialist\Wallet\Iban\UpdateIbanRequest` (نسخه‌ی تودرتو) هیچ‌جا به کنترلر وصل نیست — کنترلر فقط نسخه‌ی root-level رو import می‌کنه (که از session 6 با فیکس `auth()->check()` درسته). **حذف شد** (کاندید حذف تبدیل به اقدام واقعی).

### ۲) 🔴 باگ واقعی کشف/رفع‌شده: کلیدهای Kavenegar در `.env.example` خالی — دقیقاً همون الگوی SECURITY_LOG_LEVEL/PAYMENTS_LOG_LEVEL
طبق درخواست «بازبینی سراسری الگوی `env('KEY', 'default')`» (باز مونده از نشست چهارم)، تمام کلیدهای config که در `.env.example` خالی تعریف شده بودن (۲۴ کلید) بررسی شدن. پنج‌تاشون (`KAVENEGAR_SENDER`, `KAVENEGAR_TEMPLATE_LOGIN`, `KAVENEGAR_TEMPLATE_REGISTER`, `KAVENEGAR_TEMPLATE_RESET`, `KAVENEGAR_TEMPLATE_2FA`) با الگوی خطرناک `env('KEY', 'default')` در `config/services.php` مصرف می‌شدن — یعنی یک دیپلوی تازه که `.env.example` رو کپی کنه بدون پرکردن این کلیدها، بی‌صدا پیامک واقعی با sender/template **خالی** به Kavenegar می‌فرسته، نه با مقدار پیش‌فرض صحیح. **فیکس:** به `env('KEY') ?: 'default'` تغییر کردن. یک تست جدید (`KavenegarConfigFallbackTest`) این حالت رو با `putenv()` مستقیم شبیه‌سازی می‌کنه (چون `.env` واقعی این sandbox این کلیدها رو پر داره، نه خالی) تا مستقل از محتوای `.env` این محیط، رفتار فال‌بک تضمین بشه.

بقیه‌ی کلیدهای خالی `.env.example` (`TELESCOPE_PATH`, `CACHE_PREFIX`, `AWS_*`, `VITE_APP_NAME`، و کلیدهای بدون مصرف در config مثل `TWO_FACTOR_TIMEOUT`) یا اصلاً در config مصرف نمی‌شن یا خالی‌بودنشون بی‌خطره (مسیر/پیشوند اختیاری) — فیکس نشدن.

### ۳) بررسی الگوی `RouteServiceProvider::configureModelBindings()` — چیزی جدید پیدا نشد
طبق کاندید نشست ششم، هر ۴ نام bind شده (`specialist`, `service`, `booking`, `user`) با grep سراسری بررسی شدن. `{booking}` همه‌جا با type-hint واقعی (`Booking $booking`) مصرف می‌شه، پس ایمنه. تنها دو نمونه‌ی قبلاً کشف‌شده (`{service}` در `BookingAvailabilityController`، `{specialist}` در `ReviewController`) وجود داشتن؛ هیچ نمونه‌ی جدیدی از این باگ پیدا نشد. **این آیتم بسته می‌شه.**

### ۴) پوشش HTTP کامل `SpecialistWalletController` + `SpecialistWithdrawalController` (۱۷ تست جدید)
بررسی نشون داد `SpecialistWithdrawalTest` موجود فقط لایه‌ی `SpecialistWalletService` رو مستقیم صدا می‌زنه — یعنی روت‌ها، میدل‌ورها، Policy، و Form Request این دو کنترلر (`index`/`transactions`/`calculateFee` و `create`/`store`/`cancel`) هیچ‌وقت از مسیر HTTP واقعی تست نشده بودن.

`SpecialistWalletControllerTest` (۷ تست): رندر index/profile-not-found، فیلتر تراکنش‌ها، `calculateFee` (که عمداً از `resolveSpecialist()` غیر-throw استفاده می‌کنه چون endpoint فقط با fetch صدا زده می‌شه — مستند در کد)، عدم دسترسی به کیف‌پول متخصص دیگر، ریدایرکت مهمان.

`SpecialistWithdrawalControllerTest` (۱۰ تست): ریدایرکت به فرم شبا وقتی شبا ثبت نشده، رندر فرم برداشت، ثبت موفق با کد پیگیری، رد مبلغ زیر حداقل (Form Request)، پیام خطای سرویس برای موجودی ناکافی، لغو با برگشت وجه به کیف‌پول، رد لغو درخواست تکمیل‌شده، عدم امکان لغو درخواست متخصص دیگر، ریدایرکت مهمان.

**🔴 دو نمونه‌ی دیگر از باگ تکراری «HasFactory بدون فایل فکتوری»** (قبلاً برای `Review`/`ReportExport` در نشست چهارم و `UserWallet`/`UserWalletTransaction` در نشست ششم کشف شده بود) پیدا شد: `SpecialistWallet` و `WithdrawalRequest` هر دو تریت `HasFactory` دارن ولی فایل فکتوری‌شون وجود نداشت — فراخوانی `WithdrawalRequest::factory()` با «Class not found» کرش می‌کرد. هر دو فکتوری از صفر ساخته شدن.

### نتیجه‌ی نهایی نشست هفتم (تجمیعی با هر ۶ نشست قبلی)
**۶۳۶ تست، ۱۴۲۰ اسرشن، همگی PASS + ۱ skip مستند** — تأیید نهایی هم روی سه کلون کاملاً مستقل و تازه (نه محیط کاری؛ آخری دقیقاً با اسم فایل‌های تحویلی نهایی) هم روی محیط واقعی کاربر (Windows/XAMPP)، هر ۵ پچ با `git am --keep-cr` بدون conflict روی نوک ۳۸ کامیت قبلی این فاز اعمال شدن.

### ۵ کامیت این نشست (روی برنچ `V2`، طبق تصمیم نشست ششم که پایه‌ی کار همین برنچه، نه `develop`)
```
test(specialist): dedicated IBAN update coverage with edge cases
fix(config): Kavenegar sender/template keys fall back safely when .env defines them empty
chore(cleanup): remove dead nested Specialist\Wallet\Iban\UpdateIbanRequest
test(specialist): HTTP coverage for wallet overview and withdrawal controllers + missing factories
chore: update Iban dead-code regression test wording + ignore phpunit cache
```

⚠️ **نکته‌ی عملیاتی این نشست، برای تحویل‌های آینده**: در جریان ساخت پچ‌ها، `.phpunit.result.cache` (فایل build artifact که هر بار اجرای تست عوض می‌شه) به‌اشتباه در baseline محلی commit شده بود و باعث conflict بین محیط‌ها در `git am` شد؛ رفع شد با حذف دستی همون hunk از پچ متأثر. **درس**: قبل از `git init` baseline در هر نشست، حتماً `.phpunit.result.cache` به `.gitignore` اضافه بشه و اگر از قبل tracked بود، `git rm --cached` بشه.

### قدم‌های باز برای نشست هشتم
- تصمیم بیزنسی هنوز باز: چهار کنترلر کاملاً یتیم (`Api\V1\LoyaltyController`, `AdminLoyaltySettingsController`, `AdminLoyaltyPointsController`, `User\NotificationController`) — این نشست دوباره تأیید کرد هنوز هیچ روتی ندارن؛ حذف یا wire کردن؟
- `break_start`/`break_end` inert در `Specialist::getAvailableSlots()` (هنوز باز)
- تصمیم بیزنسی: `routes/api/admin/*` (هنوز باز)
- ✅ بسته شد این نشست: بازبینی سراسری `env('KEY','default')` در `config/*.php`
- ✅ بسته شد این نشست: بررسی گسترش‌یافته‌ی الگوی `RouteServiceProvider::configureModelBindings()`
- ✅ بسته شد این نشست: پوشش HTTP کامل `Specialist\Wallet\Iban`/`SpecialistWalletController`/`SpecialistWithdrawalController`
- کنترلرهای ادمین/کاربر باقی‌مانده‌ای که هنوز هیچ تست HTTP ندارن — نیاز به یک بررسی سراسری منظم‌تر (نه صرف grep نام کلاس، چون بیشتر تست‌ها با `route()` نه با نام کلاس کار می‌کنن) در نشست بعد

## ⭐⭐ فاز تست‌نویسی — نشست هشتم (۲۰۲۶-۰۸-۲۰): پوشش سراسری کنترلرهای بدون تست + ۳ باگ واقعی — ۷۶۵ تست

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۴۳ کامیت نشست‌های اول تا هفتم بود — با اجرای کامل سوییت تأیید شد: **۶۳۶ تست، ۱۴۲۰ اسرشن، همگی PASS + ۱ skip مستند**، دقیقاً هم‌تراز مستندسازی نشست هفتم). محیط از صفر با PHP 8.3 + Composer + SQLite بازسازی شد.

این نشست از آخرین آیتم باز نشست هفتم شروع شد: **«کنترلرهای ادمین/کاربر باقی‌مانده‌ای که هنوز هیچ تست HTTP ندارن — نیاز به بررسی سراسری منظم‌تر»**. روش این‌بار دقیق‌تر از حدس‌زدن روی اسم فایل بود: برای هر کنترلر با `grep -rl "$ClassName" routes/` تأیید شد آیا واقعاً روت‌شده، سپس اگه روت‌شده و تست نداشت، پوشش کامل HTTP نوشته شد.

**۱۰ فایل تست جدید، سازمان‌دهی‌شده در ۱۶ کامیت جدا** (۳ فیکس + ۱۳ کامیت تست) روی برنچ `test/comprehensive-test-suite-phase-5`، بر پایه‌ی همون ۴۳ کامیت قبلی این فاز:

| فایل | تعداد | پوشش |
|---|---|---|
| `Admin/AdminBookingControllerTest` | ۱۳ | CRUD کامل، فرق status-only vs full-update، برگشت‌وجه کامل موقع لغو ادمین، گارد حذف نوبت پرداخت‌شده |
| `Admin/AdminWalletControllerTest` | ۱۴ | index/search/verify-iban/adjust + `AdminWalletSettingsController` (بروزرسانی/سقف درصد) |
| `Admin/AdminWithdrawalControllerTest` | ۱۱ | index/show/approve(نیازمند payment_reference)/reject(برگشت‌وجه)/auto-payout(دیسپچ Job) |
| `Admin/AdminDashboardControllerTest` | ۶ | داشبورد اصلی + سه endpoint API (`data`/`popular-services`/`active-specialists`) |
| `Admin/AdminPaymentControllerTest` | ۶ | ثبت پرداخت دستی، اتصال به مسیر Observer عادی (اعتبار کیف‌پول متخصص)، رگرسیون‌گارد `payment_details->method` |
| `User/AnnouncementControllerTest` | ۹ | endpoint عمومی JSON `active`/`top`/`index`/`show` — تأیید شد واقعاً توسط `AnnouncementBanner.jsx` مصرف می‌شه (برخلاف GalleryController JSON که کاملاً یتیمه) |
| `User/LoyaltyControllerTest` | ۱۰ | داشبورد وفاداری، redeem، points/history/rewards/progress/discount-codes |
| `User/DashboardControllerTest` | ۷ | نوبت‌های پیش‌رو، اطلاعیه‌های فعال، پیشنهادهای بر پایه‌ی دسته‌بندی |
| `User/BookingReservationControllerTest` | ۱۱ | create/confirm/store/cancel، گارد ۲۴ساعته، مالکیت |
| `User/BookingRescheduleControllerTest` | ۹ | تغییر زمان با auto-confirm/pending، رد اسلات ناموجود (هم وب هم JSON) |
| `User/BookingDiscountControllerTest` | ۱۳ | check/apply/applyApi روی هر سه مسیر روت (`/bookings/check-discount` وب، `/api/check-discount` عمومی، `/api/bookings/{id}/apply-discount` با Sanctum) |
| `Auth/TwoFactorControllerTest` | ۱۲ | نمایش/setup/enable/disable/verify/resend، اولین پوشش این کنترلر |
| `User/SecurityControllerTest` | ۸ | داشبورد امنیتی، لیست نشست‌ها، پایان‌دادن به نشست تکی/همه |
| `Admin/AdminReportsControllerTest` | ۶ | آخرین کنترلر ادمین بدون پوشش مستقیم؛ `monthlyBreakdown()` (MySQL-only، YEAR/MONTH) با `partialMock` جایگزین شد تا بقیه‌ی index() (بازه‌ی پیش‌فرض، summary واقعی) واقعاً تست بشه |
| `User/BookingControllerTest` | ۱۴ | مرتب‌سازی وضعیت‌محور «نوبت‌های من» (اولین تست HTTP واقعی این فیچر مستندشده)، show/success/failed/rate، رگرسیون‌گارد باگ صفحه‌ی موفقیت |

### 🔴 سه باگ واقعی کشف/رفع‌شده

**۱) `bookings.notes` اصلاً ستونی در schema نداشت.** هر دو `StoreAdminBookingRequest`/`UpdateAdminBookingRequest` فیلد `notes` رو validate می‌کردن و هر سه Blade فرم ادمین (create/edit/show) نمایشش می‌دادن، ولی هیچ migration ای این ستون رو نساخته بود و در `$fillable` هم نبود — دقیقاً همون الگوی «فرم مقدار می‌گیره، بی‌صدا دورش می‌ریزه» (مثل `admin_commission_percentage`، `description`/`order` در `BlogCategory`، `approved_at`/`rejected_at` در `Leave`)، این‌بار با این تفاوت که خودِ ستون هیچ‌وقت وجود نداشته، نه فقط `$fillable`. کشف‌شده حین نوشتن `AdminBookingControllerTest` (تست `notes` واقعاً persist نمی‌شد). فیکس: migration جدید (`text nullable`) + افزودن به `$fillable`.

**۲) `TwoFactorController` خطاهای اعتبارسنجی رو قورت می‌داد و ۵۰۰ عمومی برمی‌گردوند.** هر سه متد `enable()`/`disable()`/`verify()` یک `catch (\Exception $e)` گسترده دور `$request->validate()` داشتن — چون `ValidationException` هم یک `\Exception` است، این کچ اون رو هم می‌گرفت و به‌جای پاسخ ۴۲۲ استاندارد لاراول (با فیلد خطاها)، یک پیام ۵۰۰ کلی («خطا در تایید کد») برمی‌گردوند. دقیقاً همون الگوی مستندشده‌ی نشست پنجم (`AdminSpecialistScheduleController::update()`)، این‌بار در یک کنترلر دیگه که هنوز هیچ تستی نداشت. فیکس: `catch (ValidationException $e) { throw $e; }` قبل از catch گسترده در هر سه متد اضافه شد.

**۳) 🔴 خاتمه‌ی نشست کاربر روی داشبورد امنیتی برای تقریباً همه‌ی نشست‌های واقعی از کار افتاده بود.** `RouteServiceProvider::configureModelBindings()` یک قید سراسری داره: `Route::pattern('id', '[0-9a-f-]+')` (طراحی‌شده برای UUID های حروف‌کوچک، مثل uuid نوتیفیکیشن‌ها). این قید **روی هر روتی که پارامترش دقیقاً `{id}` نام‌گذاری شده** اعمال می‌شه، از جمله `security/sessions/{id}/terminate`. شناسه‌ی واقعی نشست لاراول با `Str::random(40)` ساخته می‌شه — رشته‌ای مختلط بزرگ/کوچک، نه فقط hex — یعنی تقریباً هر شناسه‌ی واقعی نشست حداقل یک حرف بزرگ یا یک حرف کوچک خارج از بازه‌ی a-f داره و هیچ‌وقت با این pattern مچ نمی‌شه. نتیجه: روت همیشه ۴۰۴ می‌داد و کاربر **هیچ‌وقت نمی‌تونست یک نشست فعال خاص رو از پنل امنیتی خودش پایان بده** — این باگ از همون لحظه‌ی ساخت این فیچر (جلسه‌ی ۲۰۲۶-۰۸-۰۶) وجود داشته و تا این جلسه کشف نشده بود، چون آن جلسه فقط سرویس/بک‌اند رو تست کرده بود، نه مسیر واقعی HTTP این یک اکشن خاص. فیکس: نام پارامتر در هر دو `routes/web/security.php` و `routes/api/auth/security.php` از `{id}` به `{sessionId}` تغییر کرد (خارج از قید سراسری)؛ چون Blade مربوطه URL رو دستی می‌سازه (نه با `route()`)، این تغییر برای فرانت‌اند کاملاً شفافه.

**۴) 🔴 صفحه‌ی «پرداخت با موفقیت انجام شد» با booking خالی/متعلق‌به‌دیگری فتال می‌داد.** کنترلر (`BookingController::success()`) عمداً اجازه می‌ده `$booking` هم `null` باشه (وقتی نه `session('booking_id')` نه `?id=` مچی پیدا نکنن، یا id متعلق به کاربر دیگه‌ای باشه — چون کوئری همیشه با `where('user_id', auth()->id())` اسکوپ می‌شه). ولی `resources/views/bookings/success.blade.php` در تمام بلوک «اطلاعات پرداخت»/«جزئیات نوبت» و دکمه‌ی «مشاهده جزئیات نوبت» مستقیم `$booking->id`/`$booking->payment_reference`/... رو بدون چک null می‌خوند — یعنی هر بازدید مستقیم/بوکمارک‌شده/بعد از انقضای session، یا حتی صرفاً یک `id` نامعتبر در query string، به‌جای پیام مناسب، فتال ارور «Attempt to read property on null» می‌داد. کشف‌شده حین نوشتن `BookingControllerTest` (سناریوی booking متعلق به کاربر دیگه). فیکس: کل بلوک اطلاعات داخل `@if($booking) ... @endif` قرار گرفت؛ دکمه‌ی «مشاهده جزئیات» هم شرطی شد؛ پیام «رزرو شما با موفقیت ثبت شد» و دکمه‌ی «بازگشت به صفحه اصلی» (که به booking وابسته نیستن) بدون تغییر باقی موندن.

### یافته‌های جانبی مستندشده (بدون نیاز به فیکس فوری)
- **`CheckDiscountRequest::authorize()`** خودش `auth()->check()` می‌خواد، با اینکه روت `POST /api/check-discount` در `routes/api/public/bookings.php` (بدون هیچ میدل‌ور auth) ثبت شده — یعنی این «روت عمومی» در عمل برای مهمان‌ها fail-closed (۴۰۳) هست، نه واقعاً بدون‌نیاز-به-لاگین. رفتار واقعی مستند و تست شد، بدون تغییر کد (چون معلوم نیست این عمدیه یا سهوی، نیاز به تصمیم بیزنسی داره).
- سه مسیر ثبت‌شده‌ی `GalleryController` (وب/API) تأیید شد کاملاً یتیمن (بدون هیچ مصرف‌کننده در `resources/js`/`resources/views`) — کد مرده‌ی قدیمی از قبل از مهاجرت به `Admin\Gallery\AdminGalleryController`، برخلاف `AnnouncementController` که واقعاً توسط `AnnouncementBanner.jsx` مصرف می‌شه. کاندید حذف در `R-Cleanup-DeadCode`، فیکس/تست نشد چون خارج از scope این نشست بود.

### قدم‌های باز، به‌روزرسانی‌شده
- تصمیم بیزنسی هنوز باز: چهار کنترلر کاملاً یتیم (`Api\V1\LoyaltyController`, `AdminLoyaltySettingsController`, `AdminLoyaltyPointsController`, `User\NotificationController`) — این نشست هم دوباره تأیید کرد هیچ روتی ندارن.
- ⭐ **کاندید جدید**: `resources/js/Components/*Gallery*` + `User\GalleryController` JSON endpoints — تأیید شد کاملاً یتیمن؛ کاندید حذف در `R-Cleanup-DeadCode`.
- `break_start`/`break_end` inert در `Specialist::getAvailableSlots()` (هنوز باز، بدون تغییر این نشست)
- تصمیم بیزنسی: `routes/api/admin/*` (هنوز باز)
- ⭐ **کاندید جدید**: `CheckDiscountRequest::authorize()` نیاز به auth داره با اینکه روتش در فایل «public» ثبت شده — تصمیم بیزنسی: آیا این عمدیه (نیاز واقعی به لاگین) یا باید واقعاً بدون لاگین کار کنه؟
- ✅ بسته شد این نشست (ادامه): `AdminReportsController::index` مستقیماً پوشش HTTP گرفت (با mock کردن فقط `monthlyBreakdown()` که MySQL-only است).
- ✅ بسته شد این نشست (ادامه): `User\BookingController` (index/show/success/failed/rate) پوشش کامل گرفت + باگ واقعی صفحه‌ی موفقیت با booking خالی رفع شد.

### نتیجه‌ی نهایی نشست هشتم (تجمیعی با هر ۷ نشست قبلی، شامل ادامه‌ی همین نشست)
**۷۸۵ تست، ۱۷۸۰ اسرشن، همگی PASS + ۱ skip مستند** — تأیید نهایی روی یک کلون کاملاً تازه و مستقل از زیپ اصلی (نه محیط کاری)، هر ۱۹ پچ این نشست با `git am --keep-cr` بدون conflict روی نوک ۴۳ کامیت قبلی این فاز اعمال و کل سوییت مجدداً و با همون نتیجه اجرا شد.

### ۱۹ کامیت این نشست (روی برنچ `test/comprehensive-test-suite-phase-5`)
```
fix(bookings): add missing notes column to bookings table
test(admin): AdminBookingController full HTTP CRUD coverage
test(admin): AdminWalletController + AdminWalletSettingsController HTTP coverage
test(admin): AdminWithdrawalController HTTP coverage (approve/reject/auto-payout)
test(user): public AnnouncementController JSON API coverage
test(user): web LoyaltyController HTTP coverage
fix(auth): TwoFactorController no longer swallows ValidationException into a 500
test(auth): TwoFactorController full HTTP coverage
fix(security): rename session-terminate route param to avoid the global hex-only id pattern
test(user): SecurityController dashboard/sessions/activity HTTP coverage
test(user): BookingReservationController full HTTP coverage
test(user): BookingRescheduleController full HTTP coverage
test(user): DashboardController HTTP coverage
test(admin): AdminDashboardController + AdminDashboardAnalyticsController HTTP coverage
test(admin): AdminPaymentController HTTP coverage
test(user): BookingDiscountController full HTTP coverage
test(admin): AdminReportsController::index() HTTP coverage
fix(bookings): success view no longer fatals when the booking is null
test(user): BookingController full HTTP coverage (status-priority sort, show, success, failed, rate)
```

⚠️ **نکته‌ی عملیاتی برای تحویل**: این ۱۹ پچ باید **بعد از** هر ۴۳ پچ نشست‌های اول تا هفتم اعمال بشن، نه به‌جاشون.

### قدم‌های باز برای نشست نهم (وضعیت نهایی: هر ۵ مورد در همون نشست بعدی بسته شدن — به بخش «نشست نهم» پایین‌تر نگاه کن)
- ✅ بسته شد (نشست ۹): چهار کنترلر یتیم حذف شدن.
- ✅ بسته شد (نشست ۹): `GalleryController` JSON یتیم حذف شد.
- ✅ بسته شد (نشست ۹): `CheckDiscountRequest` — روت گمراه‌کننده‌ی «عمومی» حذف شد، فقط نسخه‌ی auth-gated موند.
- ✅ بسته شد (نشست ۹): `break_start`/`break_end` — schema تکمیل و end-to-end وایر شد.
- ✅ بسته شد (نشست ۹): `routes/api/admin/*` حذف شد (+ رفع تبعات زنجیره‌ای روی کنترلرها/تست‌های وابسته).
- ✅ بسته شد: `AdminReportsController::index()` و `User\BookingController` هر دو در ادامه‌ی همین نشست پوشش HTTP گرفتن.
- با این نشست، طبق بررسی سیستماتیک (`grep -rl` روی هر کلاس کنترلر واقعاً ارجاع‌شده در `routes/`)، تمام کنترلرهای route‌شده‌ی پروژه الان حداقل یک فایل تست HTTP دارن.

## ⭐⭐ فاز تست‌نویسی — نشست نهم (۲۰۲۶-۰۸-۲۱): رفع هر ۵ تصمیم بیزنسی باز نشست هشتم — ۷۹۲ تست

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۴۷ کامیت نشست‌های اول تا هشتم بود — با اجرای کامل سوییت تأیید شد: **۷۸۵ تست، ۱۷۸۰ اسرشن، همگی PASS + ۱ skip مستند**، دقیقاً هم‌تراز مستندسازی نشست هشتم). محیط از صفر با PHP 8.3 + Composer + SQLite بازسازی شد.

کاربر هر ۵ تصمیم بیزنسی باز نشست هشتم رو صریحاً مشخص کرد: حذف ۴ کنترلر یتیم، حذف `GalleryController` یتیم، اجباری‌کردن auth روی `CheckDiscountRequest`، تکمیل schema برای `break_start`/`break_end`، و حذف `routes/api/admin/*`. این نشست هر ۵ مورد رو پیاده‌سازی کرد، به ترتیب **۵ کامیت جدا**.

### ۱) ✅ حذف چهار کنترلر کاملاً یتیم
`Api\V1\LoyaltyController`, `Admin\Loyalty\AdminLoyaltySettingsController`, `Admin\Loyalty\Point\AdminLoyaltyPointsController`, `User\NotificationController` — قبل از حذف، هر Form Request/Resource اختصاصی هرکدوم (`LoyaltyPointResource`, `RewardResource`, `DiscountCodeResource`, `UpdateLoyaltySettingsRequest`, `AddUserPointsRequest`, `DeductUserPointsRequest`) با `grep` تأیید شد که فقط توسط همون کنترلر مصرف می‌شه (نه جای دیگه‌ای) و همراهش حذف شد. سوییت کامل (۷۸۵ تست) بدون تغییر باقی موند — هیچ تستی به این کد وابسته نبود.

⚠️ **نکته‌ی مهم برای آینده**: `LoyaltyAdminService::addPoints()`/`deductPoints()` (که فقط توسط `AdminLoyaltyPointsController` حذف‌شده صدا زده می‌شدن) و `InsufficientLoyaltyPointsException` (که هم توسط اون کنترلر هم توسط خود سرویس استفاده می‌شه) **عمداً حذف نشدن** — این‌ها منطق تجاری واقعی و تست‌شده‌ای هستن (`LoyaltyAdminServiceTest`) که فعلاً هیچ مصرف‌کننده‌ی HTTP ندارن، ولی حذفشون خارج از دامنه‌ی تصمیم («۴ کنترلر حذف بشه») بود. اگه در آینده تصمیم به حذف این متدهای سرویس هم گرفته شد، تست مربوطه هم باید حذف/اصلاح بشه.

### ۲) ✅ حذف `User\GalleryController` (JSON، غیر-ادمین) یتیم
کنترلر + هر ۵ محل روت‌ثبت (`routes/web/gallery.php`, خط `Route::get('/gallery', ...)` در `routes/web/public.php`, `routes/api/admin/services.php` که کلاً فقط همین بلوک گالری رو داشت, `routes/api/public/gallery.php`) حذف شدن. `require __DIR__.'/web/gallery.php'` از `routes/web.php` هم پاک شد (بقیه‌ی `require`های همون گروه auth دست‌نخورده موندن). تأیید شد `admin.gallery.*` (نسخه‌ی زنده‌ی Blade، `Admin\Gallery\AdminGalleryController`) و هیچ Blade دیگه‌ای به این روت‌های حذف‌شده اشاره نداره. سوییت سبز موند.

### ۳) ✅ `CheckDiscountRequest::authorize()` — نیاز به لاگین رسمی و صریح شد
بررسی دقیق‌تر نشون داد این endpoint (پیش‌نمایش تخفیف) **دوبار** ثبت شده بود: یک‌بار در `routes/api/public/bookings.php` (بدون میدل‌ور، ولی `authorize()` خودش `auth()->check()` می‌خواست و بی‌صدا برای مهمان ۴۰۳ می‌داد)، و یک‌بار در `routes/api/user/bookings.php` (زیر `auth:sanctum`، درست و صریح). طبق تصمیم کاربر، ثبت گمراه‌کننده‌ی «عمومی» (که در عمل عمومی نبود) حذف شد؛ فقط نسخه‌ی صریحاً auth-gated (`/api/bookings/check-discount`) باقی موند. `authorize()` خودش بدون تغییر موند (به‌عنوان لایه‌ی دفاع اضافه، هم‌راستا با بقیه‌ی الگوهای پروژه). دو تست نشست ۸ که به روت حذف‌شده اشاره داشتن، retarget شدن.

### ۴) ✅ تکمیل schema برای `break_start`/`break_end` در `Specialist::getAvailableSlots()`
بزرگ‌ترین آیتم این نشست — یک فیچر واقعی، نه صرفاً پاک‌سازی:
- Migration جدید: دو ستون `break_start`/`break_end` (`time`, nullable) به جدول `specialist_schedules` اضافه شد.
- مدل `SpecialistSchedule::$fillable` به‌روزرسانی شد.
- هر دو مسیر مدیریت برنامه‌ی کاری — ادمین (`AdminSpecialistScheduleController` + validation درون‌خطی) و خود متخصص (`SpecialistProfileController::updateSchedule()` + `UpdateScheduleRequest`) — قوانین اعتبارسنجی یکسان گرفتن: `break_start`/`break_end` هر دو اختیاری، ولی `required_with` هم‌دیگه (نمی‌شه فقط یکی رو داد)، و باید کاملاً داخل بازه‌ی `start_time`/`end_time` باشن (`after`/`before`).
- هر دو Blade (فرم ادمین و فرم خود متخصص) فیلدهای «شروع/پایان استراحت (اختیاری)» گرفتن.
- منطقی که از قبل در `getAvailableSlots()` نوشته شده بود (و همیشه inert بود چون ستون‌ها وجود نداشتن) **بدون هیچ تغییری** الان واقعاً فعال شد — یعنی این یک فیکس معماری نبود، صرفاً تکمیل داده‌ای که منطقش از قبل درست نوشته شده بود.

**۱۹ تست جدید/تکمیل‌شده، همگی در تلاش اول pass شدن:**
- `SpecialistAvailabilityTest` (۳ تست جدید): یک بازه‌ی استراحت اسلات‌های همپوشان رو درست حذف می‌کنه، یک نوبت که فقط جزئی با شروع استراحت همپوشانی داره هم درست حذف می‌شه (نه فقط تطابق دقیق زمانی)، و رفتار قبلی (بدون break) دقیقاً بدون تغییر باقی می‌مونه.
- `AdminSpecialistScheduleTest` (۴ تست جدید): ذخیره‌ی زمان استراحت، اختیاری‌بودنش، رد `break_end` بدون `break_start`، رد بازه‌ی استراحت خارج از ساعت کاری.
- `SpecialistScheduleSelfServiceTest` (۶ تست، فایل کاملاً جدید — قبلاً این endpoint فقط یک تست authorization ساده داشت، نه تست رفتار واقعی): همون سناریوهای بالا + تست جایگزینی کامل مجموعه‌ی برنامه‌ی کاری.

### ۵) ✅ حذف `routes/api/admin/*` — پیچیده‌ترین آیتم، با پیامد زنجیره‌ای
این مورد ساده‌تر از چیزی نبود که به‌نظر می‌رسید: بخشی از کنترلرهایی که این روت‌ها صداشون می‌زدن (`AdminDashboardAnalyticsController`, `AdminReportRevenueController`, `AdminReportSpecialistController`) دقیقاً همون کنترلرهایی بودن که **در همین فاز تست‌نویسی** (نشست‌های ۳ و ۸) براشون تست HTTP نوشته شده بود، چون تنها راه تستشون همین روت‌ها بودن.

بررسی دقیق نشون داد سه سرنوشت متفاوت برای متدهای این کنترلرها:
- بعضی متدها (`getData`, `today/week/month`) از قبل معادل وب هم داشتن (در `routes/admin/dashboard.php`/`routes/admin/reports.php`) → این متدها موندن، فقط روت API-شون حذف شد.
- بعضی متدها (`daily/weekly/monthly/financial` در `AdminReportRevenueController`، `getPopularServices`/`getActiveSpecialists` در `AdminDashboardAnalyticsController`) **فقط** از طریق این روت‌های حذف‌شده در دسترس بودن → این متدها هم حذف شدن (منطق واقعی‌شون در `AdminReportService`/`AdminDashboardService` جای دیگه‌ای هنوز استفاده و تست می‌شه، پس چیزی از دست نرفت).
- `AdminReportSpecialistController` **کاملاً** (هر ۳ متدش) فقط از این روت‌ها در دسترس بود → کل کلاس حذف شد.

**تأثیر روی تست‌های موجود**: `AdminReportRevenueApiTest` (نشست ۳) و `AdminDashboardControllerTest` (نشست ۸) هر دو نیاز به اصلاح داشتن (حذف تست‌هایی که به routeهای حذف‌شده اشاره داشتن، retarget کردن بقیه به معادل وب).

**⭐ مهم‌ترین یافته‌ی این بخش**: حذف کامل گروه میدل‌ور `admin-api` (چون تنها مصرف‌کننده‌اش همین روت‌های حذف‌شده بودن) باعث شد `tests/Feature/AdminMiddlewareTest.php` — که یک **باگ بحرانی تاریخی** (recursion میدل‌ور که کل `/api/admin/*` رو OOM می‌کرد، کشف‌شده در نشست ۲) رو پوشش می‌داد — دیگه هیچ route واقعی‌ای برای تست نداشته باشه. به‌جای حذف این پوشش رگرسیون ارزشمند، تست‌ها به یک **روت آزمایشی موقت** (ثبت‌شده داخل خود `setUp()` تست، با همون میدل‌ور خام `['auth', 'admin']`) retarget شدن — یک تکنیک استاندارد و رایج در تست‌نویسی لاراول برای تست ایزوله‌ی رفتار میدل‌ور، مستقل از اینکه کدوم روت production واقعاً ازش استفاده می‌کنه. این‌طوری پوشش این باگ بحرانی (recursion + باگ redirect متخصص/ادمین) بدون نیاز به نگه‌داشتن روت‌های بی‌مصرف واقعی حفظ شد.

**فایل‌های این بخش**: حذف `routes/api/admin/{dashboard,reports,specialists}.php` (و پیش‌تر `services.php` در بخش ۲)، پاک‌سازی `routes/api.php` (حذف گروه `admin-api`) و `bootstrap/app.php` (حذف تعریف گروه میدل‌ور)، ویرایش `AdminReportRevenueController.php` (حذف ۴ متد) و `AdminDashboardAnalyticsController.php` (حذف ۲ متد)، حذف `AdminReportSpecialistController.php`، اصلاح `AdminReportRevenueApiTest.php`/`AdminDashboardControllerTest.php`/`AdminMiddlewareTest.php`.

### نتیجه‌ی نهایی نشست نهم (تجمیعی با هر ۸ نشست قبلی)
**۷۹۲ تست، ۱۷۹۷ اسرشن، همگی PASS + ۱ skip مستند** — تأیید نهایی روی یک کلون کاملاً تازه و مستقل از زیپ اصلی (نه محیط کاری)، هر ۲۴ پچ کل فاز (۱۹ پچ نشست ۸ + ۵ پچ این نشست) با `git am --keep-cr` بدون conflict روی همون زیپ اصلی اعمال شد.

### ۵ کامیت این نشست (روی برنچ `test/comprehensive-test-suite-phase-5`)
```
chore(cleanup): remove 4 fully orphaned controllers (Api\V1\LoyaltyController, Admin\Loyalty\AdminLoyaltySettingsController, Admin\Loyalty\Point\AdminLoyaltyPointsController, User\NotificationController) and their exclusive Form Requests/Resources
chore(cleanup): remove orphaned User\GalleryController JSON endpoints and their dead routes
chore(cleanup): remove routes/api/admin/* and now-orphaned controller methods (daily/weekly/monthly/financial, AdminReportSpecialistController, getPopularServices/getActiveSpecialists) + retarget AdminMiddlewareTest to a throwaway route so its regression coverage survives
fix(bookings): remove the misleading unauthenticated check-discount route; only the auth:sanctum-guarded /api/bookings/check-discount remains
feat(specialist): complete schema for break_start/break_end and wire it end-to-end (model, form requests, admin+specialist controllers, views) + full test coverage
```

⚠️ **نکته‌ی عملیاتی برای تحویل**: این ۵ پچ باید **بعد از** هر ۱۹ پچ نشست هشتم اعمال بشن (مجموعاً ۲۴ پچ، همه بر پایه‌ی همون ۴۳ پچ نشست‌های اول تا هفتم).

### قدم‌های باز برای نشست دهم (وضعیت نهایی: هر دو مورد در همون نشست بسته شدن)
با این نشست، هر ۵ تصمیم بیزنسی که در انتهای نشست هشتم فهرست شده بودن بسته شدن. موارد باز جدید/باقی‌مانده در آن لحظه:
- ~~تست‌های `LoyaltyAdminService::addPoints()`/`deductPoints()` هنوز موجودن ولی الان هیچ مصرف‌کننده‌ی HTTP ندارن~~ ✅ **بسته شد (نشست دهم، گزینه ب)** — `AdminLoyaltyPointsController` جدید ساخته شد؛ به بخش «⭐⭐ فاز تست‌نویسی — نشست دهم» نگاه کن.
- عمق پوشش تست‌های موجود (edge caseهای بیشتر) — یک استاندارد باز و بی‌پایانه (نه یک آیتم با نقطه‌ی پایان مشخص)؛ در نشست دهم دوباره بررسی و چند مورد مشخص (سراسری `env('KEY','default')`، کد مرده‌ی `RouteServiceProvider`) کشف/بسته شد.

---

## ⭐⭐ فاز تست‌نویسی — نشست دهم (۲۰۲۶-۰۸-۲۲): رفع باگ بحرانی no-op میدل‌ور 'verified' (گزینه الف) + پاکسازی کد مرده + سراسری env fallback — ۸۳۵ تست

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد (که شامل هر ۴۸ کامیت نشست‌های اول تا نهم بود — با اجرای کامل سوییت تأیید شد: **۷۹۲ تست، ۱۷۹۷ اسرشن، همگی PASS + ۱ skip مستند**، دقیقاً هم‌تراز مستندسازی نشست نهم). محیط از صفر با PHP 8.3 + Composer + SQLite بازسازی شد.

این نشست از آخرین آیتم باز نشست نهم شروع شد («تصمیم من درباره‌ی `LoyaltyAdminService::addPoints()`/`deductPoints()`: **گزینه ب**») و به‌تدریج به کشف/رفع چند باگ جدی دیگر گسترش پیدا کرد.

### ۱) ✅ گزینه ب: بازسازی پنل مدیریت امتیاز کاربران
`AdminLoyaltyPointsController` (جدید) + `AddUserPointsRequest`/`DeductUserPointsRequest` (جدید) + روت‌های `admin.loyalty.points.{index,add,deduct}` + ویو Blade کامل (جستجوی کاربر با نام/تلفن، نمایش موجودی+تاریخچه، فرم افزودن/کسر) + لینک قابل‌کشف در صفحه‌ی اصلی امتیازات. `LoyaltyAdminService::addPoints()`/`deductPoints()` (که در نشست نهم بدون مصرف‌کننده مونده بودن) الان کاملاً reachable و تست‌شده‌ان.

**۱۴ تست جدید** (`AdminLoyaltyPointsControllerTest`): جستجو، نمایش موجودی/تاریخچه، افزودن (موفق/انقضای آینده/رد انقضای گذشته/اعتبارسنجی)، کسر (موفق+پاکسازی کش نوار ناوبری/گارد insufficient-balance با `InsufficientLoyaltyPointsException`/اعتبارسنجی)، ۴۰۳ غیرادمین، ریدایرکت مهمان.

### ۲) 🔴 رفع یک تست flaky واقعی در `BookingObserverTest`
کاربر یک اجرای واقعی از `php artisan test` فرستاد که در آن `test_cancellation_processing_is_idempotent_and_never_double_refunds` fail شده بود (انتظار ۷۵۰۰۰، دریافت ۶۰۰۰۰). ریشه‌یابی: `BookingFactory` مقدار `booking_time` رو با `fake()->dateTimeBetween('+1 day', '+2 months')` می‌ساخت و بعد **ساعت** اون رو جداگانه با عددی تصادفی بین ۹ تا ۱۷ overwrite می‌کرد — این override می‌تونست فاصله‌ی واقعی تا نوبت رو به کمتر از ۲۴ ساعت (آستانه‌ی `cancellation_before_hours`) برسونه (مثلاً اگه ساعت اجرا ۱۸:۰۰ باشه، «+۱ روز» فردا می‌شه ولی بعد ساعتش به ۰۹:۰۰ ست می‌شه — فقط ۱۵ ساعت فاصله). نتیجه: تستی که هیچ `booking_time` صریحی ست نمی‌کرد، بسته به ساعت اجرای سوییت، گاهی وارد بازه‌ی جریمه‌ی ۲۰٪ می‌شد. بررسی شد بقیه‌ی تست‌های همین helper امن‌ان (تست‌های پرداخت به منطق لغو نمی‌رسن؛ لغو ادمین هیچ‌وقت جریمه نمی‌گیره؛ نوبت پرداخت‌نشده قبل از محاسبه‌ی جریمه early-return می‌کنه). **فیکس:** پیش‌فرض `booking_time` در `makePaidBooking()` به `now()->addDays(10)` پین شد (کاملاً خارج از بازه‌ی جریمه)، در حالی که تست‌های خودِ مرز بازه همچنان override صریح خودشون رو دارن.

### ۳) 🔴 حذف ۸ کنترلر کاملاً مرده‌ی Laravel Breeze
حین ممیزی کامل استک auth برای پوشش تست، کشف شد: `LoginController`, `RegisterController`, `EmailVerificationNotificationController`, `EmailVerificationPromptController`, `PasswordResetLinkController`, `PhoneVerificationController` (نسخه‌ی قدیمی)، `VerificationController`, `VerifyEmailController`. همه بازمانده‌ی دست‌نخورده‌ی اسکفولد Breeze بودن — دقیقاً همون رده‌ی `NewPasswordController` که قبلاً حذف شده بود. با grep سراسری روی `app/`, `routes/`, `resources/views/`, `tests/` تأیید شد هیچ‌کدوم routed نیستن. چندتاشون اگه صدا زده می‌شدن هم فتال می‌دادن (پروژه ستون `email` نداره، `hasVerifiedEmail()` روی مدل `User` وجود نداره؛ ویوهای `auth/verify.blade.php`/`auth/verify-phone.blade.php` که `VerificationController`/`PhoneVerificationController` (قدیمی) بهشون ارجاع می‌دادن اصلاً روی دیسک نبودن). `resources/views/auth/verify-email.blade.php` (تنها ویوی اختصاصی این کنترلرهای مرده) هم حذف شد.

### ۴) 🔴 حذف `routes/api/user/loyalty.php` + ۳ کامپوننت React یتیم
بررسی این فایل route نشون داد **۱۰ از ۱۷ روت** به متدهای کاملاً ناموجود روی `App\Http\Controllers\User\LoyaltyController` اشاره می‌کردن (`history`, `rewardsHistory`, `getExpiringPoints`, `getTransactions`, `getUserLevel`, `getSpecialOffers`, `getNextGoal`, `getEarningOptions`, `redeemGift`, `transferPoints`, `export`) — فراخوانی هر کدوم فتال «Call to undefined method» می‌داد. کل فایل هم مصرف‌کننده‌ی واقعی نداشت: تنها ۳ کامپوننت React (`Loyalty/{PointsOverview,PointsHistory,RewardsList}.jsx`) که هیچ‌جا mount نمی‌شدن (همون الگوی یتیم‌بودن مستندشده‌ی چندبارِ پروژه). `routes/web/loyalty.php` معادل کامل و تست‌شده‌ش رو از قبل داره. فایل route + ۳ کامپوننت React حذف شدن؛ `require` مربوطه در `routes/api.php` هم پاک شد.

### ۵) 🔴 باگ بحرانی (تصمیم کاربر: **گزینه الف**): میدل‌ور `'verified'` همیشه no-op بود
کشف شد alias میدل‌ور `'verified'` (اعمال‌شده روی یک گروه روت بزرگ در `routes/web.php` که specialistprofile/profiles/services/bookings/payments/loyalty/security/wallet رو در بر می‌گیره) به `Illuminate\Auth\Middleware\EnsureEmailIsVerified` بایند شده بود — که فقط وقتی `$request->user() instanceof MustVerifyEmail` باشه واقعاً `hasVerifiedEmail()` رو چک می‌کنه. چون `App\Models\User` این اینترفیس رو هیچ‌وقت implement نکرده (پروژه اصلاً ستون `email` نداره)، این میدل‌ور **همیشه** بی‌صدا pass-through می‌کرد، فارغ از وضعیت واقعی تایید شماره تلفن.

**تأثیر عملی واقعی:** در فلوی عادی UI این مشکلی ایجاد نمی‌کرد چون ثبت‌نام/ورود هر دو `Auth::login()` رو پشت یک مرحله‌ی OTP اجباری می‌گذارن. **گپ واقعی:** کاربران/متخصصین ساخته‌شده توسط ادمین (`AdminUserController::store()`, `AdminSpecialistController::store()`) می‌تونن از مسیر ورود OTP رد بشن (که فقط `login_verification_code` رو پاک می‌کنه، هیچ‌وقت `markPhoneAsVerified()` رو صدا نمی‌زنه) در حالی که `phone_verified_at` برای همیشه `null` می‌مونه — بدون این فیکس، این حساب‌ها هیچ‌وقت واقعاً «تایید شماره تلفن» نمی‌شدن.

یک میدل‌ور درست و کامل از قبل در پروژه وجود داشت (`App\Http\Middleware\EnsurePhoneIsVerified`، چک `hasVerifiedPhone()`) ولی هیچ‌جا alias نشده بود و مسیر ریدایرکت شکستش (`verification.notice`) هم به یک روت ناموجود می‌رفت (چون کنترلرهایی که این روت رو ثبت می‌کردن، همون ۸ کنترلر مرده‌ی بند ۳ بودن).

**پیاده‌سازی گزینه الف (کامل):**
- `bootstrap/app.php`: alias `'verified'` به `EnsurePhoneIsVerified::class` تغییر کرد.
- `app/Http/Middleware/EnsurePhoneIsVerified.php` (بازنویسی کامل): مهمان → `login`؛ کاربر تایید‌نشده → URL مقصد در session ذخیره و ریدایرکت به `verification.notice`؛ درخواست JSON یک `428` با `redirect_url`/`phone_verification_required` می‌گیره (هم‌الگو با `EnsureTwoFactorVerifiedForPayment` موجود). ارسال خودِ کد عمداً به کنترلر واگذار شده (نه خود میدل‌ور) تا یک نقطه‌ی واحد تصمیم بگیره کد لازمه یا نه.
- `App\Http\Controllers\Auth\PhoneVerificationController` (جدید — **نه احیای نسخه‌ی مرده‌ی Breeze همون اسم که در بند ۳ حذف شد**): `notice()` (ارسال کد فقط یک‌بار در هر session با یک فلگ debounce، رندر فرم، یا ریدایرکت کاربر ازقبل‌تاییدشده به intended/خانه‌ی نقش)، `verify()` (JSON، هم‌الگو دقیق با `TwoFactorController` شامل re-throw کردن `ValidationException` تا خطا واقعاً ۴۲۲ بشه نه ۵۰۰ قورت‌داده‌شده)، `resend()`.
- **تصمیم آگاهانه**: برای مسیر ریدایرکت بعد از تایید، از متد مرده‌ی `RouteServiceProvider::getHomeForUser()` استفاده نشد — چون خودش باگ داشت (فقط `hasRole('specialists')` جمع، بدون fallback مفرد `hasRole('specialist')` که `AuthenticatedSessionController::redirectPath()` درست داره) و بازفرستادن این باگ به کد تازه‌زنده‌شده اشتباه بود؛ منطق درست مستقیماً تکرار شد.
- `resources/views/auth/verify-phone.blade.php` (جدید): فرم OTP، هم‌الگو با `payments/secure/otp.blade.php` موجود (باکس‌های رقمی، تایمر، fetch).
- روت‌های `verification.{notice,verify,resend}` در `routes/web/auth.php`، داخل گروه فقط-`auth` (عمداً خارج از هر گروه `'verified'`-gated، که circular می‌شد).

**🔴 باگ جانبی کشف/رفع‌شده:** `PhoneVerificationService::sendCode()` (استفاده‌شده هم توسط ثبت‌نام هم توسط این فلوی جدید) هنوز SMS رو **synchronous** می‌فرستاد — همون کلاس باگی که برای ورود قبلاً فیکس شده بود (`SendLoginVerificationCodeJob`) ولی هیچ‌وقت اینجا اعمال نشده بود. `App\Jobs\SendPhoneVerificationCodeJob` (جدید، هم‌الگو دقیق) ساخته و `sendCode()` بهش وصل شد.

**۱۸ تست جدید** (`PhoneVerificationNoticeFlowTest`): گیت میدل‌ور (ریدایرکت + ذخیره‌ی intended URL، عبور کاربر تاییدشده، ریدایرکت مهمان، ۴۲۸ JSON)، `notice()` (ارسال کد+رندر، عدم ارسال دوباره در بازدید تکراری، ریدایرکت کاربر ازقبل‌تاییدشده)، `verify()` (کد درست+ریدایرکت به intended+پاکسازی session، کد غلط، کد منقضی، خطای اعتبارسنجی واقعی نه ۵۰۰ قورت‌شده)، `resend()`، و یک سناریوی end-to-end کامل مطابق مورد واقعی محرک (کاربر ادمین‌ساخته که هیچ‌وقت OTP ثبت‌نام نداشته). + ۲ تست جدید برای `SendPhoneVerificationCodeJob` در `NotificationJobsTest`.

### ۶) 🔴 سه مورد دیگر از الگوی `env('KEY','default')` با مقدار خالی + یک کلید config کاملاً wire‌نشده
طبق درخواست بازبینی سراسری، هر کلید بلانک `.env.example` که در `config/*.php` مصرف می‌شه بررسی شد:
- `config/cache.php`: `CACHE_PREFIX` خالی → پیشوند کش عملاً خالی می‌شد (ریسک تصادم روی هر بک‌اند کش مشترک). فیکس با الگوی استاندارد `?: default`.
- `config/telescope.php`:
  - `TELESCOPE_PATH` خالی → داشبورد Telescope روی خودِ ریشه‌ی سایت (`/`) mount می‌شد. فیکس با `?: default`.
  - `TELESCOPE_ENABLED` خالی → **عمداً** با `?: default` ساده فیکس نشد (بولین‌ها تنها موردی هستن که این عملگر واقعاً خطرناکه: یک `TELESCOPE_ENABLED=false` صریح خودش falsy‌ست، پس `?:` بی‌صدا اون رو نادیده می‌گرفت و به پیش‌فرض local برمی‌گشت). فیکس با چک صریح `null`/رشته‌ی خالی که هنوز اجازه می‌ده یک true/false صریح برنده بشه.
- `config/auth.php`: **باگ جدا و مهم‌تر** — کلید `verification_code_expire_minutes` اصلاً در هیچ config ای تعریف نشده بود، با اینکه `PhoneVerificationService` در دو جا `config('auth.verification_code_expire_minutes', 2)` صداش می‌زد؛ یعنی `VERIFICATION_CODE_EXPIRE_MINUTES` در `.env` **هیچ‌وقت هیچ اثری نداشت**، همیشه بی‌صدا به هاردکد `2` برمی‌گشت. کلید به `config/auth.php` اضافه و واقعاً wire شد.

**۹ تست جدید** (`ConfigEnvFallbackTest`): برای هر ۴ کلید هم حالت خالی هم حالت مقدار صریح، شامل موردی که یک فیکس ساده‌ی `?:` اونو غلط می‌گرفت (`TELESCOPE_ENABLED=false` صریح در محیط `local`). **نکته‌ی فنی کشف‌شده حین نوشتن این تست‌ها**: `Illuminate\Support\Env` یک Dotenv Repository **immutable** رو کش می‌کنه که اول از `$_SERVER`/`$_ENV` می‌خونه، نه از `putenv()`/`getenv()` — چون `.env` واقعی پروژه این کلیدها رو از قبل (خالی) تعریف کرده، `$_SERVER`/`$_ENV` از لحظه‌ی boot پر شدن، پس صرفاً `putenv()` در تست بی‌اثر بود؛ تست باید مستقیماً `$_SERVER`/`$_ENV` رو ست کنه و `Env::enablePutenv()` رو قبل از هر خوندن صدا بزنه تا کش rebuild بشه.

### ۷) 🔴 پاکسازی کد مرده‌ی `RouteServiceProvider`
حین بررسی مسیر ریدایرکت بعد از تایید تلفن (بند ۵)، چند بلوک کاملاً مرده کشف شد:
- **`protected $routes` (آرایه)**: لیست کهنه‌ای از مسیرهای route file که هیچ‌جا خونده نمی‌شد — کلاس والد (`Illuminate\Foundation\Support\Providers\RouteServiceProvider`) فقط از طریق `loadRoutesUsing`/متد `map()` مصرفش می‌کنه که هیچ‌کدوم در این کلاس تعریف نشدن (بارگذاری واقعی روت‌ها بالکل از طریق `bootstrap/app.php -> withRouting()` انجام می‌شه، طبق کامنت مجاور که از قبل هم بود). حذف شد.
- **`configureMiddlewareGroups()`** (+ فراخوانیش در `boot()`): سه گروه میدل‌ور (`authenticated`, `admin-access`, `enhanced-security`) ثبت می‌کرد که هیچ روتی در کل پروژه بهشون اشاره نمی‌کرد؛ `enhanced-security` هم شامل یک alias `'2fa'` بود که اصلاً در `bootstrap/app.php` ثبت نشده — استفاده از این گروه (اگه کسی اشتباهاً می‌کرد) فتال «middleware not found» می‌داد. حذف شد.
- **`getHomeForUser()`** (استاتیک): تأیید شد صفر call site (فقط تعریف خودش) — و خودش هم باگ داشت (`hasRole('specialists')` جمع، بدون fallback مفرد که `AuthenticatedSessionController::redirectPath()` درست داره). حذف شد.
- **`loadRouteFile()`** (استاتیک): هم صفر call site — حذف شد.

هیچ تستی برای این حذف لازم نبود (کد کاملاً مرده بود، کل سوییت موجود بدون تغییر سبز موند).

### ⭐ موارد بررسی‌شده در این نشست که از قبل حل بودن (تأیید مجدد، بدون نیاز به اقدام)
طبق درخواست کاربر برای «بررسی کن» روی فهرست کامل قدم‌های باز، این موارد هم چک شدن و همه تأیید شدن که در نشست نهم یا زودتر کاملاً بسته شده بودن:
- کنترلرهای یتیم لویالتی دیگر (`Api\V1\LoyaltyController`, `AdminLoyaltySettingsController`, `AdminLoyaltyPointsController` قدیمی, `User\NotificationController`) — حذف‌شده در نشست نهم، تأیید شد.
- `break_start`/`break_end` inert در `Specialist::getAvailableSlots()` — schema کامل شده و end-to-end تست‌شده در نشست نهم، تأیید شد.
- `routes/api/admin/*` — کاملاً حذف‌شده در نشست نهم، تأیید شد.
- `GalleryController` JSON یتیم (نسخه‌ی غیر-ادمین) — حذف‌شده در نشست نهم، تأیید شد.
- `CheckDiscountRequest::authorize()` — روت گمراه‌کننده‌ی «عمومی» در نشست نهم حذف شده، فقط نسخه‌ی auth-gated باقیه، تأیید شد.

### نتیجه‌ی نهایی نشست دهم
**۸۳۵ تست، ۱۸۹۱ اسرشن، همگی PASS + ۱ skip مستند** — تأیید نهایی روی چند کلون کاملاً مستقل و تازه (نه محیط کاری)، هر ۱۱ پچ کل نشست (۹ پچ بخش اول + ۲ پچ بخش دوم) به‌ترتیب با `git am` بدون conflict روی زیپ اصلی اعمال و کل سوییت با همون نتیجه اجرا شد.

### ۱۱ کامیت این نشست
```
feat(admin): rebuild manual loyalty points panel (option B for the open item from session 9)
test(admin): HTTP coverage for the rebuilt AdminLoyaltyPointsController
fix(test): pin booking_time in BookingObserverTest::makePaidBooking() to avoid a flaky cancellation-fee test
chore(cleanup): remove 8 orphaned Laravel Breeze auth controllers
chore(cleanup): remove orphaned /api/loyalty/* route file and its dead React consumers
test: document that the 'verified' middleware is a no-op for phone verification (open business decision)
chore(test): remove the now-superseded 'verified is a no-op' pinning test
feat(auth): wire up real phone verification behind the 'verified' middleware (option A)
test(auth): full HTTP coverage for the real phone-verification flow
fix(config): close 3 more env('KEY','default')-with-blank-value gaps + wire up a never-actually-read config key
test: regression guard for the config/*.php env fallback fixes
```

⚠️ **نکته‌ی فنی این نشست**: در جریان کار، محیط کاری یک وضعیت ناسازگار (باقی‌مانده از یک مسیر تولید متوقف‌شده‌ی قبلی که ظاهراً یک تصمیم دیگر — گزینه ب برای همین میدل‌ور — رو نیمه‌کاره پیاده کرده بود) نشون داد. کل محیط از صفر و از روی زیپ اصلی + پچ‌های واقعاً تحویل‌داده‌شده بازسازی شد تا اطمینان حاصل بشه کاری که تحویل داده می‌شه دقیقاً روی همون چیزی سوار می‌شه که واقعاً قبلاً تحویل داده شده؛ یک فایل پچ باقی‌مانده و اشتباه هم از پوشه‌ی خروجی پاک شد. **درس برای آینده**: اگه یک جلسه‌ی طولانی/چندمرحله‌ای دچار وقفه یا rollback ناگهانی شد، قبل از ادامه، همیشه باید کل وضعیت (کد + git log) از صفر و از روی آخرین پچ‌های واقعاً تأییدشده بازسازی و re-verify بشه، نه اینکه به state فعلی فایل‌سیستم اعتماد بشه.

### قدم‌های باز برای نشست یازدهم
- عمق پوشش تست‌ها همچنان یک استاندارد باز و بی‌پایانه‌ست (نه یک آیتم با نقطه‌ی پایان مشخص) — هیچ کنترلر route‌شده‌ی بدون تست باقی نمونده، ولی edge caseهای بیشتر همیشه قابل‌اضافه‌ست.
- ⭐ **کاندید جدید**: ۶ کلید کاملاً بلااستفاده‌ی `.env.example` که هیچ‌جای کد (نه در config، نه در app) خونده نمی‌شن، صرفاً placeholder‌های تزئینی‌ان: `TWO_FACTOR_TIMEOUT`, `TWO_FACTOR_CODE_LENGTH`, `MAX_LOGIN_ATTEMPTS`, `LOGIN_THROTTLE_MINUTES`, `RESET_CODE_EXPIRE_MINUTES`, `PAYMENT_EXPIRY_MINUTES` — معادل‌های واقعی‌شون در کد به‌صورت ثابت هاردکد شدن (`TwoFactorAuthService::CODE_EXPIRY_MINUTES`, `SecurePaymentService::EXPIRY_MINUTES`, rate limiter `'auth'` با `Limit::perMinute(5)` هاردکد در `RouteServiceProvider`). تصمیم بیزنسی باز: این env varها به‌عنوان فیچر واقعی wire بشن (تغییر رفتار rate-limiting/timeout حساس، نیاز به تصمیم آگاهانه)، یا از `.env.example` حذف بشن چون گمراه‌کننده‌ان؟ این نشست عمداً هیچ‌کدوم انجام نشد چون خارج از دامنه‌ی «رفع باگ خاموش» و در حوزه‌ی «تغییر رفتار امنیتی/زمانی حساس بدون درخواست صریح» بود.

**فایل‌های این نشست:**
- `app/Http/Controllers/Admin/Loyalty/Point/AdminLoyaltyPointsController.php` (جدید)
- `app/Http/Requests/Admin/Loyalty/Point/{Add,Deduct}UserPointsRequest.php` (جدید)
- `routes/admin/loyalty.php`
- `resources/views/admin/loyalty/points/index.blade.php` (جدید)
- `resources/views/admin/loyalty/index.blade.php`
- `tests/Feature/Admin/AdminLoyaltyPointsControllerTest.php` (جدید)، `tests/Feature/Admin/AdminLoyaltyRewardHttpTest.php`
- `tests/Feature/Observers/BookingObserverTest.php`
- `app/Http/Controllers/Auth/{EmailVerificationNotificationController,EmailVerificationPromptController,LoginController,RegisterController,PasswordResetLinkController,PhoneVerificationController(قدیمی),VerificationController,VerifyEmailController}.php` (حذف)، `resources/views/auth/verify-email.blade.php` (حذف)
- `routes/api/user/loyalty.php` (حذف)، `resources/js/Components/Loyalty/{PointsOverview,PointsHistory,RewardsList}.jsx` (حذف)، `routes/api.php`
- `tests/Feature/PhoneVerificationMiddlewareGapTest.php` (حذف، جایگزین‌شده)
- `app/Http/Middleware/EnsurePhoneIsVerified.php`، `app/Http/Controllers/Auth/PhoneVerificationController.php` (جدید، نسخه‌ی واقعی)، `app/Jobs/SendPhoneVerificationCodeJob.php` (جدید)، `app/Services/PhoneVerificationService.php`، `bootstrap/app.php`، `routes/web/auth.php`، `resources/views/auth/verify-phone.blade.php` (جدید)
- `tests/Feature/Auth/PhoneVerificationNoticeFlowTest.php` (جدید)، `tests/Feature/Jobs/NotificationJobsTest.php`
- `config/cache.php`، `config/telescope.php`، `config/auth.php`، `app/Providers/RouteServiceProvider.php`
- `tests/Unit/ConfigEnvFallbackTest.php` (جدید)

---

## ⭐⭐ فاز تست‌نویسی — نشست یازدهم (۲۰۲۶-۰۸-۲۳): وایر کردن ۶ کلید بلااستفاده‌ی .env.example + بررسی نهایی سراسری فاز — ۸۶۲ تست + اعلام رسمی پایان فاز

**دسترسی:** این نشست هم دسترسی شبکه به GitHub بسته بود؛ طبق روال مستندشده، مستقیم روی زیپ آپلودی کاربر کار شد. محیط از صفر با PHP 8.3 + Composer + SQLite بازسازی شد.

### ⚠️ کشف مهم قبل از شروع کار اصلی: بین جلسه‌ی دهم و آپلود این جلسه، دو فیکس مستند «گم» شده بودن

قبل از شروع به وایر کردن ۶ کلید، طبق روال همیشگی این پروژه (تأیید baseline قبل از هر کاری)، تست کامل روی زیپ آپلودی اجرا شد: نتیجه **۸۲۶ تست/۱۸۸۱ assertion + ۱ skip**، نه ۸۳۵ تست/۱۸۹۱ assertion که نشست دهم به‌عنوان نتیجه‌ی نهایی خودش مستند کرده بود — دقیقاً به اندازه‌ی ۹ تست/۱۰ assertion کمتر.

بررسی نشون داد **دو آیتم از نشست دهم که «انجام‌شده» مستند شده بودن، در این آپلود روی دیسک وجود نداشتن** — دقیقاً همون الگوی «فیکس تأییدشده در گفتگو ≠ ذخیره‌شده روی دیسک» که چندین بار دیگه هم در این پروژه اتفاق افتاده:
1. **بازبینی سراسری env fallback (آیتم ۶ نشست دهم)**: `CACHE_PREFIX`/`TELESCOPE_PATH` هنوز `env('KEY', default)` ساده بودن (نه `?: default`)، `TELESCOPE_ENABLED` هنوز فاقد چک صریح null/خالی بود، و `config/auth.php` اصلاً کلید `verification_code_expire_minutes` رو نداشت (یعنی `PhoneVerificationService` هنوز بی‌صدا به هاردکد `2` برمی‌گشت). فایل تست `ConfigEnvFallbackTest` (۹ تست) هم غایب بود — دقیقاً همون ۹ تستی که در شمارش بالا کم بود.
2. **پاکسازی کد مرده‌ی `RouteServiceProvider` (آیتم ۷ نشست دهم)**: `$routes` property (هنوز به فایل‌های حذف‌شده مثل `api/admin/dashboard.php` اشاره می‌کرد)، `configureMiddlewareGroups()`، `getHomeForUser()` (با همون باگ `hasRole('specialists')` جمع)، و `loadRouteFile()` هنوز روی دیسک بودن — با `grep` سراسری دوباره صفر call site براشون تأیید شد.

هر دو مورد **دوباره اعمال شدن** (به‌عنوان بخشی از همون بررسی سراسری که کاربر برای این جلسه خواسته بود)، قبل از رفتن سراغ کار اصلی جلسه. تمام آیتم‌های دیگه‌ی نشست‌های قبلی (۵ تصمیم بیزنسی نشست نهم، ۸ کنترلر Breeze حذف‌شده، `/api/loyalty/*` حذف‌شده، `break_start`/`break_end` وایرشده، `AdminLoyaltyPointsController` بازسازی‌شده، فیکس تست فلیکی) با grep/بررسی مستقیم دوباره تأیید شدن و همه سالم روی دیسک بودن — فقط همین دو مورد از نشست دهم گم شده بودن.

### وایر کردن ۶ کلید کاملاً بلااستفاده‌ی `.env.example` (کار اصلی این نشست)

هر شش کلید — که تا این لحظه هیچ‌جای کد (نه در `config/`، نه در `app/`) خونده نمی‌شدن و معادل واقعی‌شون همیشه به‌صورت ثابت هاردکد بود — به فیچر واقعی وایر شدن، هرکدوم با همون مقدار پیش‌فرض قبلی حفظ‌شده (یعنی بدون تنظیم صریح در `.env`، هیچ رفتاری تغییر نمی‌کنه):

| کلید | محل قبلی (هاردکد) | محل جدید (config) | پیش‌فرض حفظ‌شده |
|---|---|---|---|
| `TWO_FACTOR_TIMEOUT` | `TwoFactorAuthService::CODE_EXPIRY_MINUTES = 2` | `services.two_factor.timeout_minutes` | ۲ دقیقه |
| `TWO_FACTOR_CODE_LENGTH` | `random_int(100000, 999999)` (۶ رقم ثابت) | `services.two_factor.code_length` (کف ۴ رقم) | ۶ رقم |
| `MAX_LOGIN_ATTEMPTS` | rate limiter `'auth'`: `Limit::perMinute(5)` | `auth.max_login_attempts` | ۵ تلاش |
| `LOGIN_THROTTLE_MINUTES` | همون rate limiter، decay ضمنی ۱ دقیقه‌ای لاراول | `auth.login_throttle_minutes` | ۱ دقیقه |
| `RESET_CODE_EXPIRE_MINUTES` | `PasswordResetController::sendCode()`: `now()->addMinutes(2)` | `auth.reset_code_expire_minutes` | ۲ دقیقه |
| `PAYMENT_EXPIRY_MINUTES` | `SecurePaymentService::EXPIRY_MINUTES = 15` | `services.secure_payment.expiry_minutes` | ۱۵ دقیقه |

**نکته‌ی معماری (`RESET_CODE_EXPIRE_MINUTES` در برابر `VERIFICATION_CODE_EXPIRE_MINUTES`)**: `PasswordResetController::sendCode()` از همون ستون‌های `verification_code`/`verification_code_expire_at` استفاده می‌کنه که `PhoneVerificationService` هم برای OTP ثبت‌نام/ورود ازشون استفاده می‌کنه — این یک الگوی از‌قبل‌موجود در کدبیس بود (نه چیزی که این نشست ایجاد کرده)، چون این دو فلوی هم‌زمان برای یک کاربر اجرا نمی‌شن (هرکدوم مقدار قبلی رو overwrite می‌کنه). نگه‌داشتن `RESET_CODE_EXPIRE_MINUTES` مستقل از `VERIFICATION_CODE_EXPIRE_MINUTES` (به‌جای یکی‌کردنشون) دقیقاً همون استقلال قبلی رو حفظ می‌کنه — قبل از این تغییر هم `sendCode()` مستقل و بی‌ربط به `config('auth.verification_code_expire_minutes')` بود (هاردکد `2` جدا).

⚠️ **یادداشت مستند (بدون تغییر رفتار)**: rate limiter `'auth'` از قبل ثبت و در دسترسه (`RateLimiter::for('auth', ...)`) ولی هیچ روتی فعلاً با `throttle:auth` بهش وصل نیست — یعنی خودِ محدودیت نرخ هنوز به هیچ روتی اعمال نمی‌شه. وایر کردن `MAX_LOGIN_ATTEMPTS`/`LOGIN_THROTTLE_MINUTES` فقط این تعریف موجود رو قابل‌تنظیم می‌کنه، نه اینکه محدودیت نرخ واقعی رو به مسیر ورود اضافه کنه — اتصال `throttle:auth` به یک روت واقعی یک تصمیم رفتاری/امنیتی جدا و بزرگ‌تره که خارج از دامنه‌ی این درخواست (فقط «وایر کردن کلیدهای env») بود.

### پوشش تست

۳۶ تست جدید در ۶ فایل، همگی روی محیط sandbox و بعداً روی سه محیط مستقل دیگه (fresh clone در همین sandbox، fresh clone دوم بعد از اعمال patch ها، و تأیید نهایی) تکرار و تأیید شدن:

| فایل | تعداد | پوشش |
|---|---|---|
| `tests/Unit/ConfigEnvFallbackTest.php` (بازسازی‌شده‌ی آیتم ۶ نشست دهم) | ۹ | `CACHE_PREFIX`/`TELESCOPE_PATH`/`TELESCOPE_ENABLED`/`verification_code_expire_minutes` — خالی→پیش‌فرض، مقدار صریح→همون مقدار |
| `tests/Unit/SessionElevenEnvKeysConfigTest.php` (جدید) | ۱۴ | هر ۶ کلید جدید در سطح config: خالی→پیش‌فرض مستندشده، مقدار صریح→override |
| `tests/Feature/Auth/TwoFactorAuthServiceConfigTest.php` (جدید) | ۶ | طول کد پیش‌فرض/override، کف ۴ رقمی، انقضای پیش‌فرض/override، verify() موفق با طول غیرپیش‌فرض |
| `tests/Unit/AuthRateLimiterConfigTest.php` (جدید) | ۴ | حد پیش‌فرض ۵/۶۰ثانیه، override هر دو کلید، اسکوپ‌بندی بر اساس IP |
| `tests/Feature/Auth/PasswordResetTest.php` (افزوده) | ۱ | انقضای کد بازیابی رمز با مقدار config شده |
| `tests/Feature/Payment/SecurePaymentServiceConfigTest.php` (جدید) | ۴ | انقضای پرداخت پیش‌فرض/override، `verifyPayment()` هم در پنجره‌ی کوتاه‌شده‌ی معتبر هم بعد از انقضا |

**۱ باگ واقعی کشف/رفع‌شده حین نوشتن تست‌ها (نه در کد اصلی، در خودِ تست)**: اولین نسخه‌ی `ConfigEnvFallbackTest` دو تست داشت که `$_SERVER['APP_ENV']`/`$_ENV['APP_ENV']` رو مستقیم به `'local'`/`'production'` تغییر می‌دادن (برای تست فال‌بک `TELESCOPE_ENABLED` بر پایه‌ی محیط) بدون اینکه `APP_ENV` رو در لیست کلیدهای save/restore خودش داشته باشه — یعنی این mutation بعد از هر دو تست تا آخر همون پروسه‌ی PHPUnit نشت می‌کرد. نتیجه: اجرای کل سوییت بعد از این دو تست با ۷۸۰ شکست روبه‌رو شد (چون `APP_ENV` دیگه `testing` نبود، پس تنظیمات SQLite in-memory از `phpunit.xml` دیگه اعمال نمی‌شد). فیکس: `APP_NAME`/`APP_ENV` هم به لیست کلیدهای ردیابی/بازگردانی‌شده‌ی این تست اضافه شدن.

### نتیجه‌ی نهایی این نشست
**۸۶۲ تست، ۱۹۲۳ assertion، همگی PASS + ۱ skip مستند** — تأیید نهایی روی یک کلون کاملاً تازه و مستقل از زیپ اصلی آپلودی (نه محیط کاری)، با اعمال هر ۷ پچ واقعی این نشست (`git am --keep-cr`، بدون conflict) روی یک کلون سوم و کاملاً جدا.

### ۷ کامیت این نشست (روی برنچ `test/comprehensive-test-suite-phase-6`)
```
chore(cleanup): re-apply session 10's RouteServiceProvider dead-code removal
fix(config): re-apply session 10's env fallback sweep + regression test
feat(2fa): wire TWO_FACTOR_TIMEOUT/TWO_FACTOR_CODE_LENGTH into TwoFactorAuthService
feat(auth): wire MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES into the 'auth' rate limiter
feat(auth): wire RESET_CODE_EXPIRE_MINUTES into PasswordResetController::sendCode()
feat(payment): wire PAYMENT_EXPIRY_MINUTES into SecurePaymentService
test: consolidated config-resolution regression guard for all 6 session-11 env keys
```

⚠️ **نکته‌ی عملیاتی برای اعمال روی محیط لوکال**: این ۷ پچ روی یک کلون تازه از همین زیپ (که خودش هر ۴۹ کامیت نشست‌های اول تا دهم رو نداره — این آپلود بدون تاریخچه‌ی گیت بود) به‌ترتیب با `git am --keep-cr` تست شدن. اگه محیط کاربر تاریخچه‌ی گیت جدایی داره، این ۷ پچ باید بعد از هر تغییری که تا پایان نشست دهم مستند شده اعمال بشن؛ دو پچ اول این‌ها (`RouteServiceProvider`/config env fallback) دقیقاً همون دو آیتمی هستن که این جلسه کشف کرد از نشست دهم گم شده بودن — اگه محیط کاربر این دو رو از قبل داره، `git am` این دو پچ رو با «already applied»/no-op مواجه می‌کنه که باید دستی بررسی و رد بشه، نه اینکه به‌زور دوباره اعمال بشه.

### ⭐ بررسی نهایی و سراسری فاز تست‌نویسی (طبق درخواست صریح کاربر)

با بسته‌شدن آخرین آیتم باز مستندشده (۶ کلید env)، یک بررسی سراسری و مستقل از تصمیمات قبلی روی کل پروژه انجام شد تا مطمئن بشیم واقعاً چیز دیگه‌ای از فاز تست‌نویسی جا نمونده:

1. **پوشش HTTP کامل کنترلرها**: هر ۶۹ کنترلر واقعاً route‌شده (استخراج‌شده با `grep -rhoE "[A-Za-z_\\\\]+Controller::class" routes/`) به‌صورت مستقل بررسی شدن. روش اولیه (grep نام کلاس در فایل‌های تست) برای ~۱۵ مورد نتیجه‌ی نادرست «بدون تست» داد، چون تست‌ها با نام route/URL کار می‌کنن نه رشته‌ی نام کلاس؛ هر کدوم از این موارد (`ConfirmablePasswordController`, `AuthenticatedSessionController`, `RegisteredUserController`, `PhoneVerificationController`, `SpecialistBookingManagementController`, و بقیه‌ی موارد با نام فایل تست غیرمستقیم مثل `AdminBlogTest`/`AdminCategoryTest`/...) به‌صورت دستی با گرفتن route/URL واقعی‌شون و جست‌وجوی مستقیم در `tests/` بررسی و **تأیید شد که همگی پوشش HTTP واقعی دارن**.
2. **صحت تمام تصمیمات بیزنسی قبلی (نشست نهم)**: هر ۵ مورد (حذف ۴ کنترلر یتیم لویالتی/نوتیفیکیشن، حذف `GalleryController` JSON یتیم، حذف روت گمراه‌کننده‌ی `CheckDiscountRequest`، تکمیل schema برای `break_start`/`break_end`، حذف `routes/api/admin/*`) با بررسی مستقیم فایل‌های روی دیسک دوباره تأیید شدن — همگی سالم و به‌جا.
3. **صحت آیتم‌های نشست دهم**: حذف ۸ کنترلر Breeze، حذف `/api/loyalty/*` + کامپوننت‌های React یتیم مربوطه، وایر شدن واقعی میدل‌ور `'verified'` به `EnsurePhoneIsVerified`، فیکس تست فلیکی `BookingObserverTest`، و بازسازی `AdminLoyaltyPointsController` — همگی با grep/بررسی مستقیم تأیید شدن که روی دیسک هستن (فقط دو آیتم config/RouteServiceProvider گم بودن، که در همین نشست دوباره اعمال شدن — به بالا نگاه کن).
4. **سویپ نهایی کلیدهای بلانک `.env.example`**: هر ۲۴ کلیدی که در `.env.example` به‌صورت `KEY=` (بدون مقدار) تعریف شده بودن دوباره بررسی شدن. غیر از ۶ کلید همین نشست و موارد قبلاً فیکس‌شده، بقیه یا اعتبارنامه‌های واقعاً محرمانه‌ان که باید در `.env.example` خالی بمونن (`APP_KEY`, `AWS_*`, `DB_PASSWORD`, `KAVENEGAR_API_KEY`) یا رفتارشون با رشته‌ی خالی هم بی‌خطره (`KAVENEGAR_SEND_IN_LOCAL` — چون مصرفش به‌صورت `if (config(...))` است و رشته‌ی خالی هم falsy است، دقیقاً هم‌رفتار با `false`). یک کلید کاملاً بلااستفاده (`VITE_APP_NAME`) هم پیدا شد که هیچ‌جای کد PHP/JS پروژه خونده نمی‌شه — این یک کاندید بی‌خطر و کم‌اهمیت برای فاز پاک‌سازی آینده است (نه یک باگ)، فیکس نشد چون خارج از دامنه‌ی «۶ کلید مشخص‌شده» و بدون اثر عملی واقعی بود.
5. **بازآزمایی کامل سوییت روی fresh clone مستقل**: هم قبل هم بعد از این نشست، سوییت کامل روی کلون‌های کاملاً جدا و مستقل (نه محیط کاری) اجرا و نتیجه یکسان تأیید شد.

**نتیجه: هیچ آیتم باقی‌مانده‌ی واقعی دیگه‌ای از فاز تست‌نویسی پیدا نشد.** تنها موارد کاملاً غیرعملیاتی و بی‌اهمیت که آگاهانه فیکس نشدن (چون یا تصمیم بیزنسی باز نیازمند کاربرن، یا اصلاً بی‌خطرن): کلید کاملاً بلااستفاده‌ی `VITE_APP_NAME`، و «عمق پوشش تست‌ها» که ذاتاً یک استاندارد باز و بی‌پایانه است نه یک آیتم قابل‌بستن.

## ✅✅ اعلام رسمی: فاز تست‌نویسی کامل و سخت‌گیرانه به پایان رسید

با بسته‌شدن آخرین آیتم مستندشده و تأیید نشدن هیچ آیتم جدیدی در بررسی سراسری این نشست، **فاز تست‌نویسی رسماً کامل و بسته اعلام می‌شود.**

**آمار نهایی فاز (۱۱ نشست، ۲۰۲۶-۰۸-۰۹ تا ۲۰۲۶-۰۸-۲۳):**
- **۸۶۲ تست، ۱۹۲۳ assertion، همگی PASS + ۱ skip مستند** (skip: `SpecialistController::topRated()` — کوئری `HAVING` روی ستون‌های alias‌شده که فقط روی MySQL معتبره، نه SQLite؛ روی production واقعی این پروژه که MySQL-only است هیچ مشکلی نداره)
- **~۵۰+ باگ واقعی کشف/رفع‌شده** در طول فاز (فهرست کامل در خط وضعیت پروژه، بالای این سند)
- تمام ۶۹ کنترلر route‌شده‌ی پروژه پوشش تست HTTP واقعی دارن
- هر تصمیم بیزنسی که در طول فاز مطرح شد (حذف کد مرده، وایر کردن کلید config، تکمیل schema) یا اجرا شده یا صراحتاً به‌عنوان تصمیم آینده مستند شده — چیزی مبهم/نیمه‌کاره باقی نمونده



## ⭐ رفع مستقل (خارج از فاز تست‌نویسی، ۲۰۲۶-۰۸-۲۴): اتصال واقعی throttle:auth به مسیر ورود

**زمینه:** در بررسی نهایی فاز تست‌نویسی مستند شده بود که rate limiter 'auth' (وایرشده در نشست یازدهم با MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES) تعریف شده ولی به هیچ روتی وصل نیست — یعنی خودِ محدودیت نرخ عملاً هیچ‌جا اعمال نمی‌شد. این عمداً به‌عنوان یک تصمیم امنیتی جدا و نیازمند درخواست صریح کاربر کنار گذاشته شده بود. کاربر در همین جلسه درخواست پیاده‌سازی رو داد.

### تصمیم دامنه (Scope)

throttle:auth فقط به **سه روت واقعی مسیر ورود** وصل شد:
- POST /login (AuthenticatedSessionController::store — چک رمز عبور)
- POST /login/verify (AuthenticatedSessionController::verify — حدس کد ۶ رقمی OTP)
- POST /login/resend (AuthenticatedSessionController::resendCode — بردار حمله‌ی اسپم پیامک)

⚠️ **دلیل عدم گسترش به ثبت‌نام/بازیابی رمز/تایید شماره تلفن**: rate limiter نام‌دار لاراول (RateLimiter::for('auth', ...)) یک کلید کش مشترک بر اساس نام limiter + مقدار ->by() می‌سازه، **مستقل از اینکه کدوم روت باعث فراخوانیش شده**. یعنی اگه همین limiter رو به روت‌های نامرتبط (مثل ثبت‌نام) هم وصل می‌کردیم، تلاش‌های ناموفق لاگین و تلاش‌های ثبت‌نام **بی‌صدا یک بودجه‌ی مشترک** رو باهم مصرف می‌کردن (مثلاً چند لاگین ناموفق می‌تونست باعث بشه یک تلاش ثبت‌نام کاملاً بی‌ربط هم throttle بشه) — چیزی که اسم MAX_LOGIN_ATTEMPTS توصیفش نمی‌کنه و می‌تونست گیج‌کننده باشه. برای محافظت مشابه از ثبت‌نام/بازیابی رمز/تایید شماره تلفن، باید یک rate limiter **جدا و با نام/تنظیمات مستقل خودش** تعریف بشه — این تصمیم به آینده موکول شد (کاندید جدید پایین‌تر).

### پیاده‌سازی

**۱) روت‌ها (routes/web/auth.php)**: ->middleware('throttle:auth') روی هر سه روت بالا اضافه شد.

**۲) مدیریت خطا (bootstrap/app.php)**: قبلاً هیچ handler اختصاصی برای ThrottleRequestsException وجود نداشت — فقط یک handler عمومی HttpException بود که فقط برای درخواست‌های JSON پاسخ می‌داد (برای فرم‌های معمولی هیچی برنمی‌گردوند، یعنی صفحه‌ی پیش‌فرض/انگلیسی لاراول نمایش داده می‌شد). یک renderable اختصاصی برای ThrottleRequestsException اضافه شد:
- برای درخواست‌های JSON: پاسخ ۴۲۹ با پیام فارسی
- برای فرم‌های معمولی: back()->with('error', 'تعداد تلاش‌های شما بیش از حد مجاز است...')

⚠️ **نکته‌ی فنی مهم**: این handler باید **قبل از** handler عمومی HttpException ثبت بشه — لاراول renderable ها رو به ترتیب ثبت امتحان می‌کنه و چون ThrottleRequestsException خودش یک HttpException هم هست، اگه handler عمومی زودتر ثبت شده بود، برای درخواست‌های JSON پیام انگلیسی پیش‌فرض («Too Many Attempts.») همون‌جا برمی‌گشت و هیچ‌وقت به handler اختصاصی نمی‌رسید.

**۳) نمایش پیام (resources/views/layouts/guest.blade.php)**: کشف شد این layout (مورد استفاده‌ی صفحات لاگین/ثبت‌نام/بازیابی رمز) **تنها لایوت پروژه بود که بنر session('error') رو نداشت** — layouts/app.blade.php, layouts/admin.blade.php, layouts/specialist.blade.php هر سه از قبل این الگو رو داشتن. بدون این بنر، پیام throttle (که به هیچ فیلد خاصی مثل شماره تلفن یا رمز مرتبط نیست) هیچ‌جا نمایش داده نمی‌شد. بنر با همون استایل قرمز پروژه اضافه شد.

### پوشش تست

tests/Feature/Auth/LoginThrottlingTest.php (۹ تست جدید): تلاش‌های زیر سقف مجاز عادی رد می‌شن، تلاش فراتر از سقف throttle می‌شه (بدون اینکه لو بده رمز واقعاً درست بوده یا نه)، پاسخ JSON صحیح برای درخواست‌های API، اشتراک بودجه بین /login+/login/verify+/login/resend، انقضای صحیح پنجره‌ی زمانی (با travel())، عدم تأثیر روی مسیر ثبت‌نام (اثبات مستقل‌بودن bucket ها)، و نمایش واقعی پیام در لایوت.

### نتیجه
**۸۷۱ تست، ۱۹۵۳ assertion، همگی PASS + ۱ skip مستند** — تأیید نهایی روی کلون کاملاً مستقل و تازه (اعمال هر ۸ پچ این سند تا این لحظه، git am --keep-cr، بدون conflict، از یک کپی خام زیپ اصلی).

**فایل‌های این بخش:**
- routes/web/auth.php
- bootstrap/app.php
- resources/views/layouts/guest.blade.php
- tests/Feature/Auth/LoginThrottlingTest.php (جدید)

**✅ به‌روزرسانی (۲۰۲۶-۰۸-۲۴): این کاندید پیاده‌سازی شد.** به بخش «⭐ رفع مستقل: سه rate limiter مستقل برای ثبت‌نام/بازیابی رمز/تایید تلفن» بلافاصله پایین‌تر نگاه کن.


## ⭐ رفع مستقل (خارج از فاز تست‌نویسی، ۲۰۲۶-۰۸-۲۴): سه rate limiter مستقل برای ثبت‌نام/بازیابی رمز/تایید تلفن + حذف کلید بلااستفاده VITE_APP_NAME

**زمینه:** بلافاصله بعد از پیاده‌سازی throttle:auth (بخش بالا)، کاربر پرسید آیا محافظت مشابه برای ثبت‌نام/بازیابی رمز/تایید شماره تلفن هم واقعاً لازمه. جواب صادقانه: بله — نه صرفاً برای تقارن، بلکه به دو دلیل مشخص: (۱) هر دو مسیر forgot-password و resend یک پیامک واقعی از Kavenegar می‌فرستن؛ بدون throttle، یک مهاجم می‌تونه شماره‌ی یک قربانی رو با کد تایید بمباران کنه (آزار یا اتلاف اعتبار پیامکی) بدون نیاز به حدس‌زدن هیچی. (۲) reset-password و verify-phone/verify هر دو یک کد ۶ رقمی رو با یک مقایسه‌ی ساده چک می‌کنن، بدون هیچ محدودیت تلاش روی خودِ کد — در پنجره‌ی زمانی اعتبار کد، بدون throttle می‌شه با اسکریپت حدسش زد.

### پیاده‌سازی

**سه rate limiter کاملاً جدا و مستقل** (نه مشترک با 'auth' لاگین، نه با هم — دقیقاً همون منطق قبلی: چون کلید کش لاراول بر اساس نام limiter + IP هست، نه روت، اشتراک‌گذاری بین فلوهای نامرتبط باعث cross-throttle گیج‌کننده می‌شد) به `RouteServiceProvider::configureRateLimiting()` اضافه شد:

| Limiter | سقف پیش‌فرض | روت‌های متصل |
|---|---|---|
| `registration` | ۵ تلاش/دقیقه | `POST /register`, `/register/verify`, `/register/resend` |
| `password-reset` | ۳ تلاش/دقیقه (محافظه‌کارانه‌تر — هر دو تهدید بالا رو داره) | `POST /forgot-password`, `/reset-password` |
| `phone-verification` | ۵ تلاش/دقیقه | `POST /verify-phone/verify`, `/verify-phone/resend` |

⚠️ **تصمیم آگاهانه: بدون کلید env جدید.** برخلاف `MAX_LOGIN_ATTEMPTS`/`LOGIN_THROTTLE_MINUTES`، این سه limiter مقدار ثابت (hardcode) دارن — دقیقاً هم‌الگو با limiter از‌قبل‌موجود `'sensitive'` (که خودش `Limit::perMinute(10)` ثابت داره). دلیل: اضافه‌کردن ۶ کلید دیگه به `.env.example` برای مقادیری که نیاز عملیاتی واقعی برای تنظیم‌شدن نداشتن (و کسی درخواستش نکرده بود)، دقیقاً برخلاف روحیه‌ی هم‌زمانِ درخواست دوم همین جلسه (حذف یک کلید env بلااستفاده) بود.

**مدیریت خطا و نمایش پیام**: از همون handler مشترک `ThrottleRequestsException` که برای throttle:auth ساخته شده بود استفاده می‌شه (پیام فارسی + بنر `session('error')` در `layouts/guest.blade.php`) — نیازی به هیچ تغییری در `bootstrap/app.php`/`layouts/guest.blade.php` نبود.

### پوشش تست

`tests/Feature/Auth/RegistrationAndPasswordResetThrottlingTest.php` (۱۰ تست جدید): هر سه limiter بعد از سقف تنظیم‌شده‌اش throttle می‌شه، اشتراک بودجه بین روت‌های خواهر هر فلو (مثلاً `/register` + `/register/resend`)، اسکوپ‌بندی بر اساس IP نه شماره تلفن (اثبات با دو کاربر مختلف)، و مهم‌تر — عدم تأثیر هیچ‌کدوم روی مسیر لاگین یا روی هم.

### حذف کلید بلااستفاده VITE_APP_NAME

با grep سراسری روی `app/`, `config/`, `resources/js/`, `vite.config.js` تأیید شد این کلید **هیچ‌جای کد پروژه خونده نمی‌شه** (نه حتی به‌صورت implicit با `import.meta.env` در یک فایل jsx). از `.env.example` و `.env` حذف شد؛ یک تست کوچک (`tests/Unit/ViteAppNameRemovedTest.php`) این وضعیت رو pin کرد.

### نتیجه
**۸۸۳ تست، ۱۹۷۳ assertion، همگی PASS + ۱ skip مستند** — تأیید نهایی روی کلون کاملاً مستقل و تازه، با اعمال هر ۱۰ پچ این سند تا این لحظه (`git am --keep-cr`، بدون conflict، از یک کپی خام زیپ اصلی).

**فایل‌های این بخش:**
- `app/Providers/RouteServiceProvider.php`
- `routes/web/auth.php`
- `tests/Feature/Auth/RegistrationAndPasswordResetThrottlingTest.php` (جدید)
- `.env.example` (+ `.env` محلی، خارج از گیت)
- `tests/Unit/ViteAppNameRemovedTest.php` (جدید)

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۲۶): فیلتر تاریخ jcal در امنیت + دو باگ پیامک تکراری + پنل تنظیمات کانال اطلاع‌رسانی

**زمینه:** کاربر چهار درخواست مستقل مطرح کرد که در یک نشست انجام شدند: (۱) فیلتر تاریخ صفحه‌ی لاگ‌های امنیتی ادمین باید از تقویم شمسی خودکفای پروژه استفاده کند نه `input[type=date]` مرورگر، (۲و۳) دو مورد پیامک تکراری مستند با لاگ واقعی Kavenegar، (۴) نیاز به پنل کنترل کانال اطلاع‌رسانی (پیامک/نوتیفیکیشن/ربات) به‌ازای هر رویداد.

### ۱) فیلتر تاریخ امنیت → jcal
`admin/security/logs.blade.php` (که تنها صفحه‌ی باقی‌مانده در کل پنل ادمین بود که هنوز از `input[type=date]` مرورگر استفاده می‌کرد، برخلاف بقیه‌ی صفحات) به همان الگوی دقیق jcal (کپی‌شده از `admin/reports/index.blade.php`) مهاجرت کرد — دو ورودی نمایشی جلالی + فیلد مخفی میلادی؛ بک‌اند (`AdminSecurityService`) بدون تغییر.

### ۲) 🔴 باگ واقعی: پیامک تکراری زمان ثبت نوبت (هزینه‌ی اضافه)
با بررسی لاگ واقعی Kavenegar کاربر تأیید شد: هر نوبت جدید باعث ارسال **۲ پیامک به مشتری** می‌شد — یکی از `CustomerBookingNotification` (که در `BookingService::createBooking()` بلافاصله موقع ساخت نوبت، **قبل از هرگونه پرداخت**، صدا زده می‌شد و متن نادرست «نوبت شما با موفقیت ثبت و **پرداخت شد**» داشت، با شماره‌پیگیری خالی چون هنوز پرداختی صورت نگرفته)، و دیگری از `BookingObserver::sendCustomerPendingSMS()`/`sendCustomerConfirmationSMS()` (که بعد از پرداخت واقعی، با متن درست، ارسال می‌شود). متخصص فقط ۱ پیامک درست می‌گرفت (رفتار درست، دست‌نخورده ماند).

**فیکس:** `CustomerBookingNotification::via()` از `['database','sms']` به یک via() تنظیمات‌محور (از طریق سیستم جدید بخش ۴ پایین‌تر) تغییر کرد که پیش‌فرضش فقط `['database']` است — پیامک این کلاس به‌صورت پیش‌فرض غیرفعال شد (ادمین می‌تواند دوباره فعالش کند).

### ۳) 🔴 باگ واقعی: پیامک تکراری زمان تکمیل نوبت
هنگام تکمیل نوبت توسط متخصص، `SendBookingCompletionNotifications` (لیسنر رویداد `BookingCompleted`) **هر دو** `ReviewService::sendReviewRequest()` (پیامک لینک نظرسنجی — درست و مطلوب) **و** `BookingStatusUpdated($booking,'completed')` (پیامک تشکر اضافه) را ارسال می‌کرد — یعنی مشتری ۲ پیامک پشت‌سرهم برای یک اقدام می‌گرفت. فیکس: `BookingStatusUpdated` برای `status='completed'` هم به همان مکانیزم تنظیمات‌محور وصل شد، با پیش‌فرض SMS خاموش.

**⭐ کشف جانبی (خارج از درخواست اولیه، همان الگو): پیامک تکراری زمان لغو نوبت.**
حین بررسی `BookingStatusUpdated`، مشخص شد کامنت خود پروژه در `SendBookingCancellationNotifications` صراحتاً می‌گفت «این‌جا عمداً پیامک فرستاده نمی‌شود چون `BookingObserver::sendCancellationSMS()` قبلاً پیامک لغو را فرستاده — فرستادن پیامک این‌جا هم باعث تکرار می‌شود»، ولی کد واقعی `via()` این ادعا را رعایت نمی‌کرد و برای `status='cancelled'` هم `sms` را برمی‌گرداند — یعنی هر لغو نوبت باعث **۲ پیامک لغو با متن متفاوت** به مشتری می‌شد. **فیکس:** برخلاف مورد «completed» (که تنظیمات‌محور و قابل‌فعال‌سازی مجدد است)، شاخه‌ی `cancelled` به‌صورت **ساختاری و همیشگی** فقط `['database']` برمی‌گرداند — چون فعال‌کردنش دوباره (حتی با یک ردیف تنظیمات اشتباه) همیشه همان تکرار قدیمی را بازتولید می‌کند؛ پیامک لغو واقعی منحصراً از طریق `BookingObserver::sendCustomerCancellationSMS()` (که خودش الان تنظیمات‌محور است، با کلید مستقل `BOOKING_CANCELLED_CUSTOMER`) کنترل می‌شود.

### ۴) ⭐⭐ فیچر جدید: پنل تنظیمات اطلاع‌رسانی (کنترل مستقل پیامک/نوتیفیکیشن/ربات به‌ازای هر رویداد)

**درخواست کاربر:** ادمین باید بتواند برای هر رویداد پروژه مستقل تعیین کند که آیا پیامک ارسال شود، آیا نوتیفیکیشن داخل‌برنامه‌ای ثبت شود، و/یا آیا از طریق ربات (تلگرام/بله) اطلاع‌رسانی شود.

**زیرساخت ساخته‌شده:**
- Migration + مدل `NotificationSetting` — هر ردیف یک رویداد (`event_key` یکتا) با سه فلگ بولین (`sms_enabled`, `database_enabled`, `telegram_enabled`)؛ ردیف‌ها به‌صورت lazy (اولین‌بار که رویداد رخ می‌دهد یا ادمین صفحه‌ی تنظیمات را باز می‌کند) با مقادیر پیش‌فرض ساخته می‌شوند.
- `App\Support\Notifications\NotificationEvents` — رجیستری ~۲۰ رویداد، گروه‌بندی‌شده (رزرو نوبت، برداشت وجه متخصص، مرخصی، نظرات، مالی/سیستمی ادمین، وفاداری) با برچسب فارسی، برای رندر صفحه‌ی تنظیمات.
- `App\Services\Notification\NotificationSettingService` — تنها نقطه‌ی تصمیم‌گیری مرکزی (`isEnabled()`/`channels()`)، با کش (`Cache::rememberForever` + `flush()` روی هر ذخیره)، و یک نقشه‌ی پیش‌فرض‌های آگاهانه (`DEFAULT_OVERRIDES`) که دقیقاً همان رفتار «درست»ی که در بخش‌های ۲/۳ بالا فیکس شد را به‌عنوان پیش‌فرض واقعی سیستم تثبیت می‌کند (نه صرفاً یک فیکس موقت در کد).
- `App\Channels\TelegramChannel` (کانال جدید Notification) — سازگار هم با Bot API تلگرام هم بله (endpoint یکسان، فقط دامنه فرق دارد؛ `config('services.telegram.*')`/`config('services.bale.*')` هر دو مستقل قابل‌تنظیم در `.env`). محتوای پیام: اگر کلاس Notification متد `toTelegram()` داشته باشد از همان استفاده می‌شود؛ در غیر این صورت به‌صورت خودکار از کلید `'message'` در خروجی `toDatabase()`/`toArray()` همان کلاس fallback می‌گیرد — بدون نیاز به دست‌کاری تک‌تک کلاس‌ها. بدون توکن/چت‌آیدی تنظیم‌شده، بی‌صدا نادیده گرفته می‌شود (نه خطا).
- `App\Traits\RespectsNotificationSettings` — متد کمکی `gatedChannels()` برای وایر کردن یک‌خطی `via()` در هر کلاس Notification.

**وایر شدن کامل (تأیید شده با grep سراسری، بدون هیچ مورد باقی‌مانده):** هر ۱۸ کلاس Notification پروژه (`CustomerBookingNotification`, `BookingNotification`, `BookingStatusUpdated`, `SpecialistBookingCancelledNotification`, `BookingRescheduledNotification`, `WithdrawalApprovedNotification`, `WithdrawalRejectedNotification`, `AdminNewWithdrawalRequestNotification`, `LeaveStatusNotification`, `NewReviewNotification`, `NegativeReviewNotification`, `NewReviewReceivedNotification`, `SpecialistRespondedNotification`, `AdminPaymentReceivedNotification`, `AdminNewBookingNotification`, `NewUserRegisteredNotification`, `ReportExportReadyNotification`, `PointsEarned`, `RewardRedeemed`) + تمام ارسال‌های مستقیم پیامک بدون سیستم Notification در `BookingObserver` (چهار متد: `sendCustomerCancellationSMS`, `sendSpecialistCancellationSMS`, `sendCustomerConfirmationSMS`, `sendCustomerPendingSMS`) و `ReviewService::sendReviewRequest()` همگی به این سیستم مرکزی وصل شدند — یعنی این فاز کامل و بدون آیتم باز تحویل داده شد، برخلاف یک برداشت اولیه‌ی اشتباه در وسط همین نشست («۱۴ کلاس باقی مانده») که با بررسی دقیق‌تر رد شد.

**پنل ادمین:** `Admin\Notification\AdminNotificationSettingController` (index/update) + `routes/admin/notification-settings.php` + ویو جدید `admin/notification-settings/index.blade.php` (جدول‌های گروه‌بندی‌شده با چک‌باکس مستقل برای هر ستون پیامک/نوتیفیکیشن/ربات) + لینک سایدبار جدید در `layouts/admin.blade.php`. اگر توکن ربات تنظیم نشده باشد، یک بنر هشدار در بالای صفحه نشان داده می‌شود.

⚠️ **نکته‌ی معماری مهم برای فازهای بعدی**: کلاس‌های Notification پروژه (`toSms()`دارها) همگی از الگوی قدیمی `new SMSService` **مستقیم در سازنده** استفاده می‌کنند (نه container injection) — این باعث شد در تست‌نویسی این فیچر کشف شود که mock سراسری `SMSService::class` در `tests/TestCase.php` (که برای جلوگیری از تماس واقعی به Kavenegar در سوییت تست بایند شده) فقط جایی اثر دارد که SMSService از طریق container resolve شود (مثل `BookingObserver`/`ReviewService` که constructor-injected هستند)، نه جاهایی که مستقیم `new SMSService()` ساخته می‌شود. این مسئله چیزی نیست که این جلسه فیکس کرده باشد (رفتار از قبل پروژه بود و برای امنیت تست‌ها هنوز هم لازم است دو مسیر متفاوت شمارش شوند)، فقط باید حین نوشتن تست‌های آینده برای هر پیامکی که از یک کلاس Notification می‌آید، مدنظر قرار بگیرد.

**تست:** ۲۶ تست جدید — `BookingSmsDuplicationTest` (۱۳ تست: via()-level برای هر سه مورد فیکس‌شده + end-to-end با `$this->mock(SMSService::class, ...)` برای شمارش دقیق پیامک‌های خام)، `NotificationSettingServiceTest` (۶ تست)، `AdminNotificationSettingControllerTest` (۵ تست)، و ۲ تست jcal در `AdminSecurityTest`. کل سوییت پروژه (۹۰۹ تست، ۲۰۲۲ اسرشن) بدون هیچ regression سبز شد؛ تأیید نهایی روی یک کلون کاملاً مستقل و تازه از زیپ اصلی (نه محیط کاری) با `git am`.

⚠️ **پیش‌نیاز عملیاتی**: `php artisan migrate` باید اجرا شود (جدول جدید `notification_settings`). برای فعال‌سازی واقعی کانال ربات، `.env` باید حداقل یکی از این دو جفت را داشته باشد: `TELEGRAM_BOT_TOKEN`+`TELEGRAM_CHAT_ID` یا `BALE_BOT_TOKEN`+`BALE_CHAT_ID`.

**فایل‌های این نشست:**
- `database/migrations/2026_08_24_000000_create_notification_settings_table.php` (جدید)
- `app/Models/NotificationSetting.php` (جدید)
- `database/factories/NotificationSettingFactory.php` (جدید)
- `app/Support/Notifications/NotificationEvents.php` (جدید)
- `app/Services/Notification/NotificationSettingService.php` (جدید)
- `app/Channels/TelegramChannel.php` (جدید)
- `app/Traits/RespectsNotificationSettings.php` (جدید)
- `app/Http/Controllers/Admin/Notification/AdminNotificationSettingController.php` (جدید)
- `routes/admin/notification-settings.php` (جدید)
- `resources/views/admin/notification-settings/index.blade.php` (جدید)
- `app/Providers/AppServiceProvider.php` (ثبت کانال telegram)
- `config/services.php` (بلوک‌های telegram/bale)
- `resources/views/layouts/admin.blade.php` (لینک سایدبار)
- `routes/web.php` (require فایل روت جدید)
- `resources/views/admin/security/logs.blade.php` (jcal)
- ۱۸ کلاس در `app/Notifications/**` (لیست کامل بالا)
- `app/Observers/Booking/BookingObserver.php`
- `app/Services/Review/ReviewService.php`
- `tests/Feature/Notification/BookingSmsDuplicationTest.php` (جدید)
- `tests/Unit/Services/Notification/NotificationSettingServiceTest.php` (جدید)
- `tests/Feature/Admin/AdminNotificationSettingControllerTest.php` (جدید)
- `tests/Feature/Admin/AdminSecurityTest.php` (۲ تست افزوده)

**Branch:** `fix/notification-duplicate-sms-and-settings-panel`
**Commit:** `fix(notifications): stop duplicate SMS + add jcal to security filter + notification-channel settings`

---

## ⭐ رفع مستقل (۲۰۲۶-۰۸-۲۷): ۸ باگ واقعی دیگر کشف‌شده حین بازبینی کاربر (کد تخفیف، پیامک/امتیاز نظرات، لینک نوتیفیکیشن، برگشت‌وجه برداشت)

**زمینه:** بلافاصله بعد از نشست قبلی (پنل تنظیمات اطلاع‌رسانی)، کاربر با تست دستی واقعی و لاگ/اسکرین‌شات واقعی هشت مورد دیگر گزارش کرد. دو مورد از این‌ها (آیتم ۱ و ۲) در واقع بازنگری در تصمیم‌های همان نشست قبلی بودند؛ بقیه باگ‌های تازه‌کشف‌شده بودند.

### ۱) بازنگری: «ثبت نوبت جدید — اطلاع به مشتری» کلاً حذف شد
نشست قبلی این پیامک را فقط پیش‌فرض خاموش کرده بود (قابل‌فعال‌سازی توسط ادمین). کاربر صریحاً گفت این گزینه اصلاً نباید وجود داشته باشد چون پیامکش نادرست است (ادعای «پرداخت شد» قبل از پرداخت واقعی). `CustomerBookingNotification` کاملاً بازنویسی شد — دیگر هیچ `toSms()`/قابلیت تنظیمات‌محور پیامک ندارد؛ `via()` همیشه فقط `['database']`. کلید رویداد `BOOKING_CREATED_CUSTOMER` از رجیستری `NotificationEvents` (و در نتیجه از پنل تنظیمات) حذف شد.

### ۲) بازنگری: پیام «تشکر» زمان تکمیل نوبت با «لینک نظرسنجی» ادغام شد
کاربر توضیح داد این باید ادغام شود، نه صرفاً پیش‌فرض خاموش، چون خودِ متن پیامک لینک نظرسنجی (`ReviewService::sendReviewRequest`) از قبل صریحاً می‌گوید «با موفقیت انجام شد» + لینک — یعنی هر دو واقعیت (اتمام کار + دعوت به نظردهی) را پوشش می‌دهد. `BookingStatusUpdated` برای `status='completed'` دقیقاً مثل `status='cancelled'` (که از فاز قبلی الگویش وجود داشت) **ساختاری و همیشگی** فقط `['database']` برمی‌گرداند — نه یک تنظیمات قابل‌فعال‌سازی مجدد. کلید `BOOKING_COMPLETED_CUSTOMER` هم از رجیستری حذف شد.

### ۳) راهنمایی عملیاتی: توکن ربات تلگرام تنظیم شد ولی chat_id هنوز پیدا نشده
کاربر توکن/نام/آیدی ربات (`@Beauty_Salon_Rasta_Bot`) را فرستاد، ولی `getUpdates` نتیجه‌ی خالی (`"result":[]`) داد — یعنی هنوز هیچ پیامی به ربات ارسال نشده. راهنمایی داده شد: باید اول به ربات در تلگرام پیام (`/start`) فرستاده شود، سپس دوباره `getUpdates` چک شود تا `chat.id` به‌دست بیاید. این یک اقدام عملیاتی کاربر است، نه یک باگ کد.

### ۴) 🔴 باگ واقعی: کد تخفیف در صفحه‌ی تأیید نوبت (`/bookings/confirm`) کار نمی‌کرد
کاربر گزارش داد اعمال کد تخفیف در صفحه‌ی `confirm` با خطای «خطا در بررسی کد تخفیف» مواجه می‌شود، در حالی که همان کد در صفحه‌ی پرداخت (`/payment/{id}`) درست کار می‌کند. ریشه‌یابی: `resources/views/bookings/confirm.blade.php` هنوز جاوااسکریپتش به مسیر `/api/check-discount` اشاره می‌کرد — همان مسیری که **در نشست نهم فاز تست‌نویسی** (به‌عنوان یک روت گمراه‌کننده‌ی «عمومی» که در واقع auth می‌خواست) عمداً حذف شده بود، ولی این صفحه‌ی خاص هیچ‌وقت به روت جایگزین (`bookings.check-discount`، همان چیزی که صفحه‌ی پرداخت هم استفاده می‌کند) وصل نشده بود — یک باگ خودمانی از یک نشست قبلی که تا این لحظه کشف نشده بود. فیکس: مسیر fetch در Blade به `route('bookings.check-discount')` تغییر کرد.

### ۵) 🔴 باگ واقعی: نبود پیامک اطلاع کسب امتیاز بعد از ثبت نظر
کاربر گزارش داد وقتی مشتری نظر ثبت می‌کند، هیچ پیامکی مبنی بر کسب امتیاز دریافت نمی‌کند. ریشه‌یابی: امتیازدهیِ ثبت نظر (و در واقع هر سه مسیر واقعی امتیازدهی پروژه — نوبت عادی از طریق `BookingObserver`، امتیازدهی سریع از طریق `BookingController::rate()`، و نظر کامل از طریق `ReviewService::createReview()`) همگی از طریق یک نقطه‌ی مشترک خام (`User::addLoyaltyPoints()`) عمل می‌کنند که **هیچ‌وقت** نوتیفیکیشن `PointsEarned` را دیسپچ نمی‌کرد؛ متد جداگانه‌ای (`LoyaltyService::earnPointsFromBooking()`) این نوتیفیکیشن را داشت ولی خودش کاملاً کد مرده و بدون هیچ فراخواننده‌ای در کل پروژه بود. **فیکس:** دیسپچ `PointsEarned` مستقیماً داخل `User::addLoyaltyPoints()` اضافه شد — یک فیکس واحد که هر سه مسیر واقعی را هم‌زمان درست می‌کند.

### ۶) 🔴 باگ واقعی: لینک نظرسنجی در پیامک `localhost` نشان می‌داد
`ReviewService::sendReviewRequest()` لینک را با `route('reviews.create', [...])` (absolute، بر پایه‌ی `config('app.url')`) می‌ساخت؛ `.env` پروژه `APP_URL=http://localhost` دارد در حالی که سرور واقعی کاربر روی `http://127.0.0.1:8000` اجرا می‌شود — دقیقاً همان الگوی مستندشده‌ی قبلی این پروژه («APP_URL vs هاست واقعی»، این‌بار برای یک SMS نه یک نوتیفیکیشن داخل‌برنامه‌ای، پس راه‌حل «لینک نسبی» قبلی این‌جا کار نمی‌کرد چون پیامک نیاز به URL کاملاً absolute دارد). **فیکس:** چون این متد همیشه در دل همان درخواست HTTP واقعی (لحظه‌ی تکمیل نوبت توسط متخصص) اجرا می‌شود، هاست واقعی از `request()->getSchemeAndHttpHost()` خوانده می‌شود (با فال‌بک به `config('app.url')` برای زمینه‌های console/queue). توصیه‌ی جانبی: `APP_URL` در `.env` هم به `http://127.0.0.1:8000` اصلاح شود.

### ۷) 🔴 باگ واقعی: متخصص با پیامک از نظر جدید خبردار نمی‌شد
ریشه‌یابی نشان داد پروژه **دو** کلاس Notification موازی برای «نظر جدید» دارد: `NewReviewNotification` (پیامک دارد، ولی متعلق به مسیر جدای امتیازدهی سریع `BookingController::rate()` است) و `NewReviewReceivedNotification` (کلاسی که واقعاً در مسیر اصلی نظردهی، `ReviewService::createReview()`، صدا زده می‌شود) — این دومی اصلاً `toSms()` نداشت و `via()`اش فقط `['database']` بود، یعنی حتی با فعال‌بودن پیامک در پنل تنظیمات، هیچ‌وقت پیامکی وجود خارجی نداشت که ارسال شود. **فیکس:** `toSms()` به `NewReviewReceivedNotification` اضافه شد و `via()` به `['database','sms']` تغییر کرد؛ کلاس خواهرش دست‌نخورده ماند چون به مسیر دیگری تعلق دارد.

### ۸) پاسخ متخصص به نظر — نصف باگ همان چیزی بود که در آیتم ۹ کشف شد
بررسی اولیه (روت/کنترلر/Policy/مدل/Blade + یک تست HTTP واقعی موجود) نشان داد فرم پاسخ‌دهی خودش کاملاً درست کار می‌کند. کاربر با اسکرین‌شات دقیق مشخص کرد مشکل واقعی این بود: **کلیک روی اعلان «نظر جدید» متخصص را به `/my-dashboard` هدایت می‌کرد، نه به صفحه‌ی واقعی نظر** — یعنی متخصص اصلاً به صفحه‌ای که بتواند پاسخ بدهد نمی‌رسید. رفعش دقیقاً همان فیکس آیتم ۹ زیر بود (`review_id` در `resolveNotificationLink()`).

### ۹) ⭐ فیچر جدید + کشف جانبی: مشاهده‌ی جداگانه‌ی اعلانات متخصص + باگ لینک `review_id`
**فیچر درخواستی:** صفحه‌ی `specialist/notifications` تب‌های فیلتر گرفت (همه/نوبت‌ها/برداشت وجه متخصص/مرخصی/نظرات/وفاداری) بر اساس یک نگاشت ثابت از نام کلاس Notification به دسته (`SpecialistNotificationController::CATEGORY_MAP`)، با شمارنده‌ی تعداد کنار هر تب.

**🔴 کشف جانبی حین همین کار:** `resolveNotificationLink()` (متد خصوصی کنترلر، مسئول تعیین مقصد کلیک روی هر اعلان، هم در `latest()`/dropdown هدر هم در `showAndRedirect()`) کلید `review_id` (که `NewReviewReceivedNotification::toArray()` تولید می‌کند) را اصلاً نمی‌شناخت — فقط `booking_id`/`leave_id` را چک می‌کرد، پس هر کلیک روی اعلان «نظر جدید» به `specialist.my-dashboard` فال‌بک می‌شد. **بدتر از این:** خود Blade (`specialist/notifications.blade.php`) هم یک **کپی کاملاً جداگانه و تکراری** از همین منطق داشت (با یک باگ کپی‌پیست اضافه — یک آرایه‌ی دو-عضوی که هر دو عضوش یکی بودن) که همین مشکل را دوباره تکرار می‌کرد. **فیکس:** شاخه‌ی `review_id` به متد اضافه شد؛ متد از `private` به `public` تغییر کرد و Blade به‌جای نگه‌داشتن کپی خودش، مستقیماً همین متد را از کنترلر صدا می‌زند — رفع کامل الگوی تکرار منطق که باعث این نوع واگرایی می‌شود.

### ۱۰) 🔴 باگ مالی واقعی: موجودی متخصص بعد از شکست auto-payout برداشت وجه هرگز برنمی‌گشت
کاربر پرسید: «وقتی درخواست برداشت رد می‌شه، پول متخصص چی می‌شه؟ الان موجودیش صفره با اینکه از طریق زرین‌پال رد شد.» بررسی `ProcessWithdrawalJob::handle()` (مسیر auto-payout آنلاین، از فاز R-Jobs) نشان داد: وقتی `ZarinpalPayoutService::payout()` شکست می‌خورد، این شاخه فقط وضعیت درخواست را `failed` می‌کرد و `WithdrawalRejected` را دیسپچ می‌کرد (که SMS آن صراحتاً به متخصص می‌گوید «مبلغ به کیف پول شما بازگشت») — ولی **هیچ‌وقت واقعاً موجودی کیف‌پول را برنمی‌گرداند**. چون مبلغ از لحظه‌ی *ثبت درخواست* (نه لحظه‌ی تایید نهایی) از کیف‌پول کسر می‌شود (`SpecialistWalletService::createWithdrawal()` → `SpecialistWallet::recordWithdrawal()`)، هر شکست auto-payout باعث از‌دست‌رفتن دائمی آن مبلغ می‌شد — دقیقاً برخلاف مسیر رد دستی ادمین (`WalletAdminService::rejectWithdrawal()`) که از قبل این برگشت‌وجه را درست انجام می‌داد. **فیکس:** همان منطق برگشت‌وجه (افزایش `balance`، کاهش `total_withdrawn`، ثبت تراکنش `refund`) به شاخه‌ی شکست `ProcessWithdrawalJob` اضافه شد.

**تست:** ۱۱ تست جدید/تکمیل‌شده — `BookingSmsDuplicationTest` (بازسازی‌شده برای حذف کامل به‌جای پیش‌فرض‌خاموش، آیتم ۱/۲)، دو تست جدید در `ReviewControllerTest` (آیتم ۵/۷)، یک تست جدید در `BookingReservationControllerTest` (آیتم ۴، رگرسیون‌گارد رندر صفحه)، یک تست جدید در `ProcessWithdrawalJobTest` (آیتم ۱۰، شبیه‌سازی دقیق جریان واقعی کسر-سپس-برگشت)، دو تست جدید در `SpecialistNotificationControllerTest` (آیتم ۸/۹). کل سوییت پروژه (۹۱۴ تست، ۲۰۳۳ اسرشن) بدون هیچ regression سبز شد؛ تأیید نهایی روی یک کلون کاملاً مستقل و تازه از زیپ اصلی (نه محیط کاری) با اعمال متوالی هر دو پچ این دو نشست از طریق `git am`.

⚠️ **پیش‌نیاز عملیاتی**: تنظیمات پیامکی که قبلاً برای دو رویداد حذف‌شده (`booking.created.customer`, `booking.completed.customer`) در پنل تنظیمات ذخیره شده بودن، دیگر هیچ اثری ندارن (رویداد از رجیستری حذف شده) — نیازی به migration جدید یا پاک‌سازی دستی جدول `notification_settings` نیست، ردیف‌های یتیم صرفاً بی‌اثر می‌مونن.

**فایل‌های این نشست:**
- `app/Support/Notifications/NotificationEvents.php`
- `app/Services/Notification/NotificationSettingService.php`
- `app/Notifications/Booking/CustomerBookingNotification.php` (بازنویسی کامل)
- `app/Notifications/Booking/BookingStatusUpdated.php`
- `app/Notifications/Review/NewReviewReceivedNotification.php`
- `app/Models/User.php`
- `app/Services/Review/ReviewService.php`
- `app/Http/Controllers/Specialist/Notification/SpecialistNotificationController.php`
- `app/Jobs/ProcessWithdrawalJob.php`
- `resources/views/bookings/confirm.blade.php`
- `resources/views/specialist/notifications.blade.php`
- `tests/Unit/Services/Notification/NotificationSettingServiceTest.php`
- `tests/Feature/Admin/AdminNotificationSettingControllerTest.php`
- `tests/Feature/Notification/BookingSmsDuplicationTest.php`
- `tests/Feature/Jobs/ProcessWithdrawalJobTest.php`
- `tests/Feature/Specialist/SpecialistNotificationControllerTest.php`
- `tests/Feature/User/BookingReservationControllerTest.php`
- `tests/Feature/User/ReviewControllerTest.php`

**Branch:** `fix/notification-duplicate-sms-and-settings-panel` (کامیت دوم، ادامه‌ی همان برنچ نشست قبلی)
**Commit:** `fix(notifications): 8 more real bugs from the follow-up review (discount code, review SMS/points, notification links, withdrawal refund)`

---


## ⭐⭐ نشست بزرگ (۲۰۲۶-۰۹-۱۰ تا ۰۹-۱۷): تأیید کامل migrate:fresh --seed روی MySQL واقعی + رفع ۴۸۲ شکست تست + باگ شدید معماری روت + باگ کامل از کار افتادن صفحه‌ی لاگین

**زمینه:** کاربر سه سوال ساده پرسید («سیدرها الان کامل شدن؟»، «نیاز به بررسی دارن؟»، «الان `migrate` بدون مشکل اجرا می‌شه؟») که به یکی از طولانی‌ترین و پرثمرترین رشته‌بررسی‌های این پروژه منجر شد — نه فقط چند فیکس کوچیک، بلکه کشف یک باگ **شدید در سطح معماری روت‌بندی لاراول** که کل مهاجرت `/s/{slug}` رو زیر سؤال می‌برد، و یک باگ که **کل صفحه‌ی لاگین رو ۵۰۰ می‌کرد**. برخلاف اکثر جلسات قبلی که فقط با SQLite کار می‌شد، این نشست **MariaDB واقعی رو مستقیماً در sandbox نصب کرد** (`apt-get install mariadb-server`) و هر فیکس مهاجرتی رو مستقیماً روی یک دیتابیس MySQL/MariaDB واقعی (نه فقط SQLite) تأیید کرد — دقیقاً همون چیزی که چندین باگ این نشست رو کشف کرد که SQLite هیچ‌وقت نشونشون نمی‌داد.

### ۱) رفع گپ‌های واقعی سیدر/فکتوری (کشف‌شده با اجرای واقعی، نه صرفاً خواندن کد)

- **🔴 باگ واقعی**: `UserSeeder` ادمین seed‌شده رو هیچ‌وقت به `salon_admins` وصل نمی‌کرد — با شبیه‌سازی یک درخواست HTTP واقعی (`Auth::login()` + دیسپچ مستقیم کرنل) تأیید شد که رفتن به `/admin/dashboard` بلافاصله logout می‌شد با پیام «اشتراک سالن شما پایان یافته یا غیرفعال شده است». فیکس با همون رابطه‌ی `Salon::admins()->attach()` که `SuperAdminService` استفاده می‌کنه (نه `DB::table` خام).
- **🔴 باگ واقعی و متناوب (~۴۰٪ نرخ کرش)**: `BookingSeeder` با تصادم `active_slot_key` (قید یکتای فاز `fix/admin-booking-slot-conflict`) گاهی کرش می‌کرد چون تولید تصادفی booking_time هیچ آگاهی‌ای از اسلات‌های قبلاً گرفته‌شده نداشت. با اجرای `migrate:fresh --seed` ۲۰ بار پشت‌سرهم تأیید شد. فیکس در دو لایه: خودِ `BookingSeeder` (ردیابی اسلات‌های اشغال‌شده + رد نمونه‌گیری با تلاش محدود) **و** ریشه‌ای‌تر در خودِ `BookingFactory` (چون `LoyaltySimulationSeeder` هم به‌صورت ضمنی از طریق `LoyaltyPointFactory`'s nested `Booking::factory()` همین باگ رو داشت).
- **🔴 باگ واقعی**: `SecurityLogService::logPaymentAttempt(string $paymentId, ...)` بدون `?` تایپ‌هینت بود؛ `SecurePaymentController::initiate()` عمداً با `null` صداش می‌زنه وقتی خود `createPayment()` fail می‌شه — یعنی **خود لاگ‌گذاری خطا** با TypeError کرش می‌کرد، به‌جای پیام مناسب «پرداخت با مشکل مواجه شد».

### ۲) 🔴🔴 کشف مهم‌ترین باگ این نشست: پارامترهای روت به‌صورت موقعیتی (نه بر اساس نام) به کنترلر پاس داده می‌شن

با ریشه‌یابی یک خطای به‌ظاهر ساده (`UserWalletController::showTransaction(): Argument #1 must be of type UserWalletTransaction, string given` — با اینکه یک لاگ دیباگ مستقیم تأیید کرد مدل درست resolve شده بود یک میدل‌ور قبل‌تر) به یک باگ **بنیادین در خودِ فریم‌ورک لاراول وقتی با این ساختار prefix ترکیب می‌شه** رسیدیم: `ControllerDispatcher` پارامترهای resolve‌شده‌ی روت رو با `array_values($parameters)` + spread عملگر صدا می‌زنه — یعنی **بر اساس ترتیب، نه اسم**. چون `salon_slug` همیشه اولین پارامتر URI بود ولی هیچ کنترلری واقعاً `$salon_slug` رو در امضاش نداره، **هر کنترلری زیر `/s/{slug}` که پارامتر implicit-bound داشت و تعداد پارامترهای متد کمتر از تعداد پارامترهای روت بود، مقدار اشتباه می‌گرفت** — رشته‌ی `"rasta"` به‌جای مدل واقعی به اولین پارامتر متد پاس داده می‌شد و مقدار درست بی‌صدا دور ریخته می‌شد.

**تأیید با یک تست کاملاً ایزوله** (یک Closure route ساده، بدون هیچ کد پروژه) قبل از فیکس. **فیکس**: در `ResolveSalonFromRoute` (جایی که `salon_slug` قبلاً resolve و استفاده شده)، با متد رسمی خود لاراول `$route->forgetParameter('salon_slug')` این پارامتر از لیست پارامترهای روت حذف می‌شه — این تک‌خط، به‌تنهایی، ده‌ها شکست تست رو حل کرد.

### ۳) رفع سیستماتیک باقی‌مونده‌ی ۱۴۹ شکست تست (تا صفر)

- `EnsureSpecialistSalonActive` بدون قید `abort(404)` می‌زد و مانع نمایش صفحه‌ی «profile-not-found» می‌شد که خودِ کنترلرها (با الگوی `resolveSpecialist()` غیر-throw) برایش طراحی شده بودن.
- 🔴 **باگ واقعی و جدی در روت‌بندی**: `reviews.create/store/thank-you` (که باید بدون لاگین، فقط با توکن پیامکی SMS در دسترس باشن — چون اکثر مشتری‌ها لحظه‌ی کلیک روی لینک SMS اصلاً لاگین نیستن) در یک commit قبلی به‌اشتباه داخل گروه احراز هویت گذاشته شده بودن — یعنی **کل قابلیت «ثبت نظر بعد از نوبت» عملاً از کار افتاده بود**. از گروه authenticated خارج و کنار بقیه‌ی روت‌های واقعاً guest-accessible جابه‌جا شد.
- `TestCase.php`: `URL::defaults(['salon_slug' => ...])` اضافه شد (میدل‌ور واقعی این رو برای هر درخواست HTTP واقعی ست می‌کنه، ولی تستی که مستقیم `route()` صدا می‌زنه بدون درخواست قبلی، این پیش‌فرض رو نداره).
- الگوی تکراری کشف‌شده در چند فایل Auth: `/login` سراسری فقط `user_type='staff'` قبول می‌کنه (طبق «بازطراحی هویت مشتری») ولی چند تست با `User::factory()->create(['password' => ...])` ساده (بدون `user_type`) کاربر می‌ساختن.
- ~۲۵ فایل تست دیگه (Booking*, Home, Service, Specialist, Dashboard, Loyalty, Security و غیره) URL هارد‌کد بدون پیشوند `/s/{slug}` داشتن؛ همه به `route()` تبدیل شدن.
- چند باگ کوچک مستقل تست (`Notification::fake()` قبل از خود setup که notification تولید می‌کرد، `AdminPermissionTest` سناریوی دسترسی از طریق permission نه `is_admin`).

**نتیجه: از ۹۴۰ تست با ۴۸۲ شکست/خطا → ۹۴۰ تست، همگی سبز (+۱ skip مستند).**

### ۴) رفع کامل ناسازگاری package.json + حذف کامل React (طبق تأیید صریح کاربر که دیگه از React استفاده نمی‌کنه)

- `@vitejs/plugin-react` (^4.2.0) و `laravel-vite-plugin` (^1.0.0) هر دو فقط تا `vite ^6` رو پشتیبانی می‌کردن؛ `package.json` پروژه `vite: ^8.0.10` پین شده بود — یعنی `npm install` ساده (بدون `--legacy-peer-deps`) از اول اصلاً fail می‌شد.
- حذف کامل بسته‌های اکوسیستم React/shadcn (`@headlessui/react`, `@radix-ui/*`, `lucide-react`, `recharts`, `tailwindcss-animate`, و غیره — با grep کامل تأیید شد صفر استفاده‌ی واقعی) + آپدیت `laravel-vite-plugin` به `^3.2.0` (اولین نسخه‌ی سازگار با vite ۸).
- `app.jsx` بازنویسی شد (mount مربوط به `AnnouncementBanner` حذف شد چون قرار بود معادل Blade داشته باشه — به بخش ⑦ پایین‌تر نگاه کن که این فرض غلط از آب دراومد و در همین نشست تکمیل شد).
- `tailwind.config.cjs`: فقط چیزهای واقعاً بلااثر حذف شدن (`tailwindcss-animate` + کیف‌فریم‌های accordion/fade-in/slide-in که Blade واقعاً ازشون استفاده نمی‌کرد، چون یک `.fade-in` دست‌نویس در `<style>` هر لایوت جدا وجود داشت)؛ `colors`/`borderRadius` عمداً **دست‌نخورده موند** چون `app.css` واقعاً از طریق `@apply border-border`/`@apply bg-background text-foreground` بهشون وابسته‌ست — این با `npm run build` مستقیم کشف و تصحیح شد (اول با overreach همه‌ی رنگ‌ها حذف شدن، build شکست، بعد دقیق‌تر فقط چیز واقعاً بلااستفاده حذف شد).

نتیجه: `npm install` (بدون هیچ workaround) و `npm run build` کاملاً تمیز.

### ۵) merge دو نسخه‌ی موازی `UserFactory.php`

کاربر یک نسخه‌ی جدا از `UserFactory.php` (از یک thread کاری موازی/جدا، با شماره‌گذاری پچ متفاوت — `0008`) به اشتراک گذاشت که `user_type`/`salon_id` رو در `definition()` و در یک state جدید `admin()` صریح می‌کرد — ولی هوک حیاتی `afterCreating()` (که ادمین رو به `salon_admins` وصل می‌کنه و باگ کش رابطه‌ی نقش رو دور می‌زنه) رو نداشت. هر دو merge شدن؛ تست کامل (۹۴۰ تست) تأیید کرد چیزی رگرس نشده.

### ۶) ⭐⭐ سه باگ migration که **فقط روی MySQL/MariaDB واقعی** رخ می‌داد (نه SQLite) — با نصب MariaDB واقعی در sandbox بازتولید و تأیید شد

این پروژه همیشه با SQLite تست شده؛ SQLite هیچ‌کدوم از این سه محدودیت رو enforce نمی‌کنه، پس این باگ‌ها تا حالا هیچ‌وقت کشف نشده بودن.

1. **`create_salons_table`** (خطای ۱۰۶۷ «Invalid default value»): دو ستون `timestamp NOT NULL` بدون default پشت‌سرهم — تحت `sql_mode` سخت‌گیر، دومی سعی می‌کنه یک default صفر ضمنی بگیره که رد می‌شه. فیکس: هر دو `nullable()` شدن (برنامه همیشه صریح مقدارشون رو ست می‌کنه).
2. **`add_salon_id_and_user_type_to_users_table`** (خطای ۱۹۰۱): ستون `salon_id` با `nullOnDelete()` تعریف شده بود؛ MySQL/MariaDB اجازه نمی‌ده یک ستون generated (`customer_salon_phone_key`) به ستونی ارجاع بده که FKـش `ON DELETE SET NULL` داره. فیکس: به RESTRICT ساده تغییر کرد.
3. **`backfill_default_salon_and_salon_id`** (خطای ۱۸۳۲ «Cannot change column»): تلاش برای `->change()` روی `salon_id` که در ۱۲ جدول همزمان درگیر FK بود. **کاربر خودش این رو مستقل فیکس کرد** (drop FK → modify column → rebuild FK با همون `cascadeOnDelete()` اصلی — تأیید شد این دقیقاً همون رفتار FK اصلی `add_salon_id_to_owned_tables` بود، نه یک تغییر معنایی) — روی MySQL واقعی خودش تست و تأیید کرد، من هم مستقل روی MariaDB واقعی sandbox (۳ بار پشت‌سرهم) دوباره تأیید کردم.

### ۷) 🔴🔴 باگ بحرانی: کل صفحه‌ی لاگین با ۵۰۰ کرش می‌کرد + رفع دو مسیر ریدایرکت به URL مرده

کاربر گزارش داد «توی لاگین کردن مشکل داشتم» و دو فایل اصلاح‌شده‌ی خودش رو فرستاد:

- **`AppServiceProvider.php`**: یک fallback سراسری `URL::defaults(['salon_slug' => ...])` بر پایه‌ی قدیمی‌ترین سالن اضافه کرد. **ریشه‌ی واقعی مشکل**: `layouts/guest.blade.php` (لایوت صفحه‌ی `/login`) یک لینک لوگو با `route('home')` داره — نامی که فقط زیر `/s/{salon_slug}` وجود داره. بدون این fallback، همین یک `route()` باعث `UrlGenerationException` می‌شد و **کل صفحه‌ی لاگین اصلاً رندر نمی‌شد**. تأیید مستقیم با grep که این لینک واقعاً وجود داره.
  - 🔴 **باگ کشف‌شده در همین فیکس کاربر و رفع‌شده توسط من**: نسخه‌ی اولیه‌ی این fallback بدون قید `Schema::hasTable('salons')` بود — یعنی در هر request/boot ای که جدول `salons` هنوز وجود نداشت (مثلاً قبل از migrate، یا هر تست قبل از `RefreshDatabase`)، کل اپ با `QueryException` کرش می‌کرد. با اجرای کامل سوییت تست تأیید شد (از ۹۴۰ تست سبز به ۹۳۹ خطای سخت). فیکس: قید + کش با `Cache::rememberForever` (چون قدیمی‌ترین سالن هیچ‌وقت عوض نمی‌شه، نیازی به invalidation نیست).
- **`AuthenticatedSessionController::destroy()`** (خروج از حساب): قبلاً `redirect('/')` — که اصلاً یک روت ثبت‌شده نیست (هر `/` زیر یک prefix زندگی می‌کنه: `/s/{slug}/` یا `/admin`) — یعنی بعد از هر logout کاربر مستقیم به یک ۴۰۴ می‌رفت. کاربر به `route('login')` تغییرش داد.
  - **باگ دوقلوی مشابه که در همین بررسی پیدا و رفع شد**: `ProfileController::destroy()` (حذف حساب کاربری) دقیقاً همون `Redirect::to('/')` رو داشت — همون باگ، رفع شد. **یک کامنت اشتباه از خودِ همین نشست هم اصلاح شد**: قبلاً در یک تست (`ProfileTest`) نوشته بودم «ریدایرکت به `/` منطقیه» بدون اینکه چک کنم این روت اصلاً resolve می‌شه یا نه — تصحیح شد.

### ۸) ⭐ ساخت معادل Blade واقعی برای `AnnouncementBanner.jsx` حذف‌شده + کشف/رفع یک نشتی داده‌ی بین‌سالنی واقعی

کاربر مستقیم پرسید «آیا معادل بلید ساخته شده یا نه؟» — بررسی مستقیم zip نشون داد **نه**: فقط یک `<div id="announcement-banner"></div>` خالی باقی مونده بود (React حذف شده بود، ولی چیزی جایگزینش نشده بود). یک معادل کامل Blade + vanilla JS ساخته شد (همون رفتار: چهار سطح اولویت با رنگ/آیکون، دکمه‌ی dismiss با localStorage به‌جز اولویت≥۱۰۰، برچسب نوع اطلاعیه).

**کشف جانبی مهم حین ساخت این کامپوننت**: `routes/api.php`'s گروه public (`services.php`, `specialists.php`, `bookings.php`, `gallery.php`, `announcements.php`) کاملاً خارج از پیشوند `/s/{slug}` بودن — یعنی `ResolveSalonFromRoute` هیچ‌وقت براشون اجرا نمی‌شد و `CurrentSalon` هیچ‌وقت bind نمی‌شد. چون `BelongsToSalon` وقتی `CurrentSalon` نال باشه فیلتری اعمال نمی‌کنه (عمداً، برای تست/سیدر/سوپرادمین)، این یعنی **یک نشتی داده‌ی واقعی بین سالن‌ها**: با ساخت یک اطلاعیه در هر کدوم از دو سالن مختلف و صدا زدن `/api/announcements/active` بدون هیچ context سالنی، **هر دو** اطلاعیه با هم در یک پاسخ برمی‌گشتن. همین تست مستقیم برای `/api/services` هم تکرار و تأیید شد (یک `BeautyService` در سالن B ساخته شد و در پاسخ `/api/services` سالن A هم ظاهر شد).

**فیکس (فقط برای announcements، طبق دامنه‌ی سؤال اصلی)**: `announcements.php` زیر `/s/{salon_slug}` منتقل شد (با `salon.resolve` میدل‌ور)؛ با تست مستقیم (همون سناریوی بالا) تأیید شد نشتی بسته شده.

**⚠️ عمداً فیکس نشد (باز مونده، دامنه‌ی بزرگ‌تری داره)**: همون نشتی برای `services.php`, `specialists.php`, `bookings.php`, `gallery.php` هنوز باز مونده — چون رفعشون یعنی پیدا کردن و آپدیت‌کردن هر consumer جاوااسکریپت/Blade در کل اپ که به این آدرس‌ها fetch می‌زنه، که blast radius خیلی بزرگ‌تری از یک سؤال جانبی درباره‌ی اطلاعیه‌هاست.

### روش تحویل و تأیید (طبق استاندارد همیشگی، این‌بار با یک لایه‌ی تأیید اضافه)

برخلاف اکثر جلسات قبلی، **هر مرحله‌ی این نشست (۲۱ پچ، در ۴ دور تحویل) روی یک کپی کاملاً تازه و مستقل از zip اصلی، هم با کل سوییت تست (SQLite) هم با `migrate:fresh --seed` واقعی روی MariaDB نصب‌شده در sandbox (۳ بار پشت‌سرهم برای هر دور) تأیید شد** — نه فقط `php -l`، نه فقط SQLite. این دقیقاً همون چیزی بود که باگ‌های #۶ (MySQL-only) رو کشف کرد؛ اگه فقط SQLite تست می‌شد، این سه باگ migration هیچ‌وقت لو نمی‌رفتن.

⚠️ **نکته‌ی عملیاتی مهم برای فازهای بعدی**: از این نشست به بعد، **هر migration جدید باید حداقل یک‌بار روی یک MySQL/MariaDB واقعی هم تست بشه**, نه فقط SQLite — این نشست به‌تنهایی سه باگ migration کاملاً متفاوت پیدا کرد که هرکدوم فقط روی MySQL/MariaDB رخ می‌دادن.

**فایل‌های این نشست (۲۱ کامیت، به ترتیب):**
`database/seeders/UserSeeder.php`, `database/seeders/BookingSeeder.php`, `database/factories/BookingFactory.php`, `app/Services/SecurityLogService.php`, `app/Http/Middleware/ResolveSalonFromRoute.php`, `app/Http/Middleware/EnsureSpecialistSalonActive.php`, `routes/web.php` (reviews)، `database/factories/UserFactory.php`, `package.json`, `vite.config.js`, `tailwind.config.cjs`, `resources/js/app.jsx` (+ حذف `AnnouncementBanner.jsx`)، ~۲۵ فایل تست (`tests/Feature/Auth/*`, `tests/Feature/User/*`, `tests/Feature/Payment/*`, `tests/TestCase.php`, و غیره)، `database/migrations/2026_08_29_000100_create_salons_table.php`, `database/migrations/2026_08_30_000000_add_salon_id_and_user_type_to_users_table.php`, `database/migrations/2026_08_29_000103_backfill_default_salon_and_salon_id.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `app/Http/Controllers/User/ProfileController.php`, `routes/api.php`, `resources/views/layouts/app.blade.php`, `tests/Feature/User/AnnouncementControllerTest.php`.

**Branch:** `test/comprehensive-test-suite-phase-6` (پچ‌های ۱ تا ۱۷) و `fix/saas-mysql-production-readiness` (پچ‌های ۱۸ تا ۲۱)
**نتیجه‌ی نهایی: ۹۴۰ تست (+۱ skip مستند)، `npm install`/`npm run build` تمیز، `migrate:fresh --seed` روی MySQL/MariaDB واقعی پایدار — همه به‌صورت مستقل و تکرارپذیر تأیید شد.**

---
## 📋 فازهای باقی‌مانده



### R-Cleanup-DeadCode — پاک‌سازی نهایی کد مرده (بخش اول ✅ تکمیل شد — به بخش تفصیلی بالا نگاه کن)
همه‌ی آیتم‌های زیر تأیید و حذف شدن؛ جزئیات کامل (شامل کشف جانبی تصادم نام روت لویالتی و کشف زیردرخت React یتیم) در بخش «✅ R-Cleanup-DeadCode — پاک‌سازی نهایی کد مرده (بخش اول)» بالای همین سند مستند شده:
- ✅ `LoyaltyAdmin.jsx`, `AnnouncementAdmin.jsx`, `GalleryAdmin.jsx` (حذف فیزیکی)
- ✅ `routes/api/admin/loyalty.php` (حذف + رفع تصادم نام روت به‌عنوان بونوس)
- ✅ `app/Http/Requests/Specialist/StoreWithdrawalRequest.php` (root، حذف)
- ✅ `app/Services/RefundService.php` (حذف)
- ✅ `app/Services/ReportService.php` (حذف)
- ✅ `app/Models/SpecialistLeave.php` + فکتوری‌ش (حذف؛ نگاشت Policy قدیمی هم پاک شد)
- ✅ زیردرخت React یتیم (۱۴ فایل، کشف‌شده در همین بررسی — `AdminLayout.jsx`, `AdminDashboard.jsx`, `Sidebar.jsx`, `Reports/*`, `Dashboard/*`, `Schedule/*`)
- ✅ `BookingActions.jsx`, `SecureForm.jsx` (حذف — به بخش «✅ رفع مستقل: تکمیل مسیر پرداخت امن / ۲FA» نگاه کن)
- ~~`app/Http/Requests/StoreBlogPostRequest.php` (root)~~ / ~~`DiscountCodeController`/`DiscountCodeService` قدیمی~~ / ~~`resources/app/Services/Admin/Specialist/AdminSpecialistService.php`~~ — در بررسی این فاز هیچ‌کدوم در ریپوی واقعی پیدا نشدن؛ ظاهراً قبلاً در فازهای دیگه حذف شده بودن.
- ✅ **حذف شد (۲۰۲۶-۰۸-۰۷)**: `WorkSchedule` (بک‌اند PHP) — در همون فاز R-Cleanup-DeadCode به تصمیم صریح کاربر دست‌نخورده مونده بود، ولی در جلسه‌ی جدا (۲۰۲۶-۰۸-۰۷) تصمیم نهایی عوض شد؛ به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کامل فیچر WorkSchedule» نگاه کن.
- ✅ **حذف شد (۲۰۲۶-۰۸-۰۲)**: ~~`Components/Auth/TwoFactorAuth.jsx` + `resources/views/auth/two-factor-auth.blade.php` + mount مربوطه~~ — تلاش React یتیم و جدای قدیمی برای ۲FA، بدون هیچ روتی که بهش لینک بده.
- ✅ **حذف شد (۲۰۲۶-۰۸-۰۷)**: `BookingStats.jsx` + mount `#booking-stats` در `admin.jsx` + هر دو روت وب/API + `AdminBookingController::getStats()`/`AdminBookingService::getStats()` — کشف‌شده حین تست جانبی جلسه‌ی امنیتی، بررسی‌شده و حذف‌شده در جلسه‌ی بعدی؛ به بخش «⭐ رفع مستقل (۲۰۲۶-۰۸-۰۷): حذف کد مرده‌ی ویجت آمار نوبت‌های ادمین» نگاه کن.

✅ جدول کنترلرها و بخش Leave-Migration بازبینی و تصحیح شدن (به بخش تفصیلی بالا نگاه کن) — `AdminSpecialistLeaveController` یک فایل جدا برای حذف نداشت، فقط عبارت گمراه‌کننده‌ی مستندسازی تصحیح شد.

> ⚠️ **تصحیح مستندسازی (این جلسه)**: بخش قبلی این سند یک فاز جدا به‌نام «R-SpecialistLeave-Upgrade» (مهاجرت SpecialistLeave→Leave) رو به‌عنوان کار باز فهرست کرده بود. بررسی نشون داد این مهاجرت از قبل، در فاز **Leave-Migration** (که در همین سند به‌عنوان ✅ تکمیل‌شده ثبت شده) کاملاً انجام شده بود — `AdminLeaveController` از قبل به Blade کامل + Form Request بازنویسی شده، `SpecialistLeaveController` به مدل `Leave` سوییچ شده، و در همین فاز R-Cleanup-DeadCode مدل قدیمی `SpecialistLeave` هم حذف شد. بخش «R-SpecialistLeave-Upgrade» صرفاً مستندسازی کهنه/جاافتاده بود (دقیقاً همون الگوی تکراری «مستند شدن قبل از این‌که کار واقعاً تموم بشه، و فراموش شدن به‌روزرسانیش بعد از اتمام کار») — از این سند حذف شد.

### ✅ R-Pint — اجرای Laravel Pint (فاز نهایی رفکتور، تکمیل‌شده)

**زمینه:** یک نشست قبلی برای این فاز شروع شده بود (PHP 8.3 CLI نصب شد، نصب Composer در حال انجام بود) ولی ناتمام مونده بود. این جلسه از صفر با یک زیپ تازه شروع شد.

**دسترسی:** این جلسه دسترسی شبکه به GitHub بسته بود (`api.github.com` → ۴۰۳)؛ طبق روال مستندشده، کار مستقیم روی زیپ آپلودی کاربر انجام شد (`find . -iname "*.blade.php" | wc -l` → ۱۶۴، تأیید شد `resources/views` کامل داخل زیپه).

**محیط:** برخلاف بیشتر جلسات قبلی، این‌بار PHP CLI و Composer واقعاً از صفر نصب و راه‌اندازی شدن (نه فقط بررسی دستی کد):
- `apt-get install php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-sqlite3 php8.3-zip php8.3-bcmath php8.3-gd` — موفق (برخلاف تلاش‌های قبلی که PHP در دسترس نبود).
- نصب Composer: `getcomposer.org` در allowlist شبکه نبود؛ به‌جاش `composer.phar` مستقیم از یک GitHub Release (`github.com/composer/composer/releases/download/2.8.9/composer.phar`) دانلود و به `/usr/local/bin/composer` (با یک wrapper script) نصب شد.
- `composer install --no-interaction --prefer-dist` — تمام ۱۴۹ پکیج (dev شامل) با موفقیت نصب شدن (`laravel/pint` هم جزوشون). فقط هوک post-autoload-dump (`php artisan package:discover`) fail شد چون فایل `artisan` اصلاً داخل این زیپ خاص نبود — بی‌ربط به Pint، نیازی به رفع نداشت (Pint به artisan/bootstrap نیازی نداره، فقط روی فایل‌های PHP فرمت اعمال می‌کنه).
- هیچ `pint.json` سفارشی در پروژه وجود نداشت — پریست پیش‌فرض Laravel استفاده شد (که استاندارد و امنه، فقط قوانین سبک‌نویسی PHP-CS-Fixer، بدون تغییر منطق).

**روش اجرا:**
1. یک `git init` موقت روی همون پوشه‌ی استخراج‌شده از زیپ زده شد (با `.gitignore` برای `vendor/`, `node_modules/`, `.env*`) و یک کامیت baseline از وضعیت فعلی (همون چیزی که در زیپ بود) گرفته شد — این‌طوری خروجی نهایی به‌صورت یک diff/patch قابل تحویل و قابل‌بررسی شد، نه صرفاً «فایل‌ها عوض شدن، باور کن».
2. `php vendor/bin/pint --test` (dry-run) اول اجرا شد تا دامنه‌ی کار قبل از اعمال واقعی مشخص بشه: **۳۱۰ فایل** (کل `app/`, `database/`, `routes/`, `config/`, `lang/`) قانون‌های سبکی نقض‌شده داشتن (import ordering، quote style، trailing comma، phpdoc alignment، blank-line rules، operator spacing و غیره).
3. `php vendor/bin/pint` (بدون `--test`) واقعاً اجرا شد.
4. تأیید نهایی: `php vendor/bin/pint --test` دوباره اجرا شد → **PASS، ۴۸۷/۴۸۷ فایل** (یعنی همه‌ی فایل‌های اسکن‌شده‌ی پروژه، نه فقط ۳۱۰ تای تغییریافته، الان با پریست هماهنگن).
5. **تأیید ایمنی (مهم‌تر از خود اجرا)**: `php -l` (syntax lint) روی تک‌تک هر ۳۰۳ فایل PHP واقعاً تغییریافته (از ۳۱۰ کاندید اولیه، ۳۰۳ فایل واقعاً diff گرفتن — بقیه فقط فرمتشون از قبل مرزی/idempotent بودن) اجرا شد → **صفر خطای syntax در هر ۳۰۳ فایل**.
6. `git diff --stat` نهایی: **۳۰۳ فایل تغییر، ۱۸۶۲ خط اضافه، ۲۰۰۹ خط حذف** — فقط `app/`, `bootstrap/`, `config/`, `database/`, `lang/`, `routes/` (تأیید شد `resources/views/*.blade.php` و `tests/` هیچ‌کدوم توسط Pint اسکن/تغییر نشدن — پریست Laravel به‌صورت پیش‌فرض Blade رو هدف نمی‌گیره).

**بررسی محتوایی (نه فقط syntax) برای اطمینان از عدم تغییر منطق/محتوا:**
- چند diff پرریسک (فایل‌هایی با رشته‌های فارسی/تمپلیت پیامک، مثل `app/Services/SMSService.php`) به‌صورت دستی خط‌به‌خط بررسی شدن — تمام تغییرات صرفاً سبکی بودن: ترتیب `use` imports، تبدیل quote دوتایی بدون interpolation به تکی، فاصله‌گذاری اپراتور تجمیع رشته (`.`)، اضافه/حذف trailing comma، خط خالی قبل از `return`، فاصله‌ی `!` (`!$x` → `! $x`). **هیچ رشته‌ی فارسی/محتوای متنی/مقدار پیکربندی تغییر نکرد.**
- نمونه‌ی این تغییرات (از `SMSService.php`): `"Kavenegar API Error (Send): " . $e->getMessage()` → `'Kavenegar API Error (Send): '.$e->getMessage()` — فقط سبک، معنا و مقدار رشته عیناً یکسان.

**نتیجه‌گیری فاز:** پروژه از این لحظه کاملاً با استاندارد سبک‌نویسی Laravel Pint یکدست شده. این آخرین آیتم چک‌لیست «ترتیب اجرای فازها»ی رفکتور بود — **کل چرخه‌ی رفکتور (از R1 تا R-Pint) الان به‌طور کامل تکمیل شده.**

⚠️ **پیش‌نیاز عملیاتی برای کاربر**: هیچ migration/queue/schedule‌ای لازم نیست (این فاز فقط فرمت کد رو عوض کرده، نه دیتابیس/رفتار runtime). فقط لازمه patch اعمال و commit بشه.

**فایل‌های این فاز:** ۳۰۳ فایل PHP در `app/` (بیشترین سهم: Controllers, Services, Requests, Notifications, Observers, Providers, Traits, Models)، `database/factories`, `database/seeders`, `database/migrations` (فقط یکی)، `routes/*.php` (چند فایل)، `config/app.php`، `lang/fa/pagination.php`، `bootstrap/app.php`. فهرست کامل در خروجی `git diff --stat` کامیت مربوطه موجوده (حجم زیاد، همه‌شون صرفاً فرمت).

**Branch:** `chore/pint-format-project`
**Commit:** `chore(pint): apply Laravel Pint formatting across app/, database/, routes/, config/, lang/`

✅ **کامیت شد توسط کاربر (تأیید شده).**

**⭐ پنجمین نمونه‌ی الگوی حساسیت به حروف — این‌بار یک زیرگونه‌ی جدید: نه فایل روی دیسک، بلکه خودِ ایندکس گیت**
موقع اعمال patch با `git am --keep-cr`، یک خطا داد: `app/Http/Requests/User/Security/CheckPasswordStrengthRequest.php: does not exist in index`. بررسی نشون داد این‌بار برخلاف ۴ نمونه‌ی قبلی (که خودِ فایل روی دیسک با حروف غلط ذخیره شده بود)، فایل روی دیسک کاربر **کاملاً درست** بود (`CheckPasswordStrengthRequest.php`)؛ مشکل فقط در **ایندکس گیت** بود — گیت هنوز اسم قدیمی/غلط (`Checkpasswordstrengthrequest.php`) رو برای این فایل ردیابی می‌کرد، احتمالاً چون rename قبلی (در جلسه‌ی ۲۰۲۶-۰۸-۰۲/۰۳، به بخش «تکمیل ۹ کاندید» نگاه کن) با `git mv` واقعی انجام نشده بود، فقط فایل مستقیم روی دیسک (Windows، case-insensitive) rename شده بود بدون `git add`/`git mv`، پس گیت هیچ‌وقت این تغییر نام رو track نکرده بود.

**راه‌حل عملیاتی (چون `git am` روی مسیرهای rename‌نشده در ایندکس حساسه، ولی `git apply` نه):**
1. `git am --abort` + `git apply --reject --whitespace=fix patch.patch` — این کل ۳۰۳ فایل رو (شامل همین یکی) **بدون هیچ `.rej`** اعمال کرد، چون `git apply` (برخلاف `git am`) فقط با working tree کار می‌کنه، نه با git index.
2. کامیت اول (`chore(pint): ...`) با `git add -A && git commit` گرفته شد.
3. رفع خودِ مشکل ایندکس (کامیت جدا، بی‌ربط به Pint): چون روی Windows (فایل‌سیستم case-insensitive) `git mv OldCase.php NewCase.php` مستقیم بین دو اسمی که فقط تو حروف فرق دارن معمولاً بی‌اثره، از یک نام موقط رد شدیم:
   ```powershell
   git mv Checkpasswordstrengthrequest.php CheckPasswordStrengthRequest_tmp.php
   git mv CheckPasswordStrengthRequest_tmp.php CheckPasswordStrengthRequest.php
   ```
   `git status` این‌بار درست `renamed:` نشون داد (نه یک delete+add جدا)؛ کامیت جدا گرفته شد: `fix(security): correct stale case-only git index entry for CheckPasswordStrengthRequest.php`.

**درس کلی برای فازهای بعدی**: وقتی یک فایل قبلاً به‌خاطر حساسیت به حروف rename شده (چه توسط Claude چه توسط کاربر با ابزار دیگه)، **کافی نیست فقط بررسی کنیم فایل روی دیسک اسم درستی داره** — باید با `git ls-files | findstr /i <نام‌فایل>` هم تأیید کرد که خودِ **ایندکس گیت** هم همون اسم درست رو ردیابی می‌کنه، وگرنه دفعه‌ی بعد که یک patch مبتنی بر `git am` (که به تاریخچه/ایندکس گیت حساسه) تحویل داده بشه، دوباره fail می‌کنه — حتی با اینکه ظاهراً «قبلاً فیکس شده بود». **راه‌حل میانی مطمئن برای تحویل patch در آینده**: وقتی `git am` رو runtime fail می‌ده ولی محتوای فایل درسته، اول `git apply --reject` رو امتحان کن (کمتر به تاریخچه/ایندکس حساسه) قبل از فرض کردن که patch خراب یا زیپ قدیمیه.

---
## ترتیب اجرای فازها
```
✅ R-Reports       → تکمیل AdminReportsController (۴ کنترلر)
✅ R-AdminLoyalty  → بزرگ‌ترین God Class باقی‌مانده (+ رفع باگ‌های واقعی فرمول امتیاز/تنظیمات + رفع بعدی: متدهای گم‌شده‌ی LoyaltyAdminService/auth() در LoyaltyService)
✅ R-AdminDashboard → DashboardService (+ رفع باگ نرخ تکمیل متخصصین/N+1 query)
✅ R-AdminWallet   → تجزیه‌ی Wallet ادمین (+ رفع باگ‌های admin_commission_percentage/PUT-POST/rejection_reason + هشدار autoPayout mock)
✅ R-SpecialistWallet → تجزیه‌ی Wallet متخصص (+ رفع باگ WalletSetting::get()/first() + فعال‌سازی Policy)
✅ R-AdminBlog     → تجزیه‌ی Blog ادمین (+ کشف/رفع soft-delete غیرفعال، ۴ view کاملاً غیرموجود، route های خراب، description/order دور ریخته می‌شدن + رفع مستقل صفحات عمومی وبلاگ)
✅ R-AdminForms    → Form Requests کامل Admin (Specialist/User/Booking/Security/DiscountCode) + رفع الگوی تکراری is_admin در ۵ فایل + رفع رگرسیون MaxPercentage
✅ R-AdminSpecialist/User/Booking → Service سه‌گانه — هم‌زمان با R-AdminForms
✅ R-DiscountLogic → تحکیم منطق تکراری محاسبه‌ی تخفیف + رفع زنجیره‌ی ۶ باگ در مسیر اعمال تخفیف (Kimi+Claude) + حذف DiscountCodeController مرده
✅ R-AdminAnnouncement-Gallery → مهاجرت نهایی Announcement/Gallery از SPA به Blade + رفع کرش admin.jsx + رفع باگ پاداش لویالتی
✅ WorkSchedule    → فیچر کامل پیاده‌سازی و باگ‌هاش رفع شد؛ تصمیم اولیه (نگه‌داری بدون استفاده) بعداً عوض شد → ۲۰۲۶-۰۸-۰۷ کاملاً حذف شد (schema نمی‌تونست ساعت جدا برای هر روز رو پشتیبانی کنه، جایگزینی SpecialistSchedule عقب‌گرد قابلیت بود)
✅ Leave-Migration → مهاجرت کامل SpecialistLeave→Leave + صفحه‌ی سراسری مرخصی‌های ادمین + رفع ۳ باگ واقعی (فرم ثبت خراب، TypeError بالقوه در Policy، تصادم نام route)
✅ R-Events        → فعال‌سازی کامل زیرساخت Event/Listener (تا قبلش هیچ‌وقت واقعاً کار نمی‌کرد) + رفع کرش/برگشت‌وجه لغو نوبت مشتری + چرخه‌ی نوتیفیکیشن برداشت وجه + ۲ فایل با نام اشتباه که فقط روی Linux می‌شکستن + ششمین نمونه‌ی باگ is_admin (PaymentSucceeded/BookingCompleted عمداً پیاده نشدن، به توضیحات فاز نگاه کن)
✅ (مستقل) تسویه‌ی دستی کیف‌پول → فیچر جدید ادمین (همه/تک‌متخصص، با/بدون نادیده‌گرفتن مهلت) + رفع شیدول دوگانه‌ی wallet:settle-pending + رفع باگ ۴۰۳ دائمی درخواست برداشت متخصص (FormRequest کهنه/اشتباه)
✅ (مستقل) نوتیفیکیشن/پیامک تکراری برداشت وجه → رفع auto-discovery خاموش‌نشدنی EventServiceProvider (کل پروژه، نه فقط برداشت) + پاکسازی provider دوبار ثبت‌شده + حذف بارگذاری تکراری route + lockForUpdate دفاعی + ۲ باگ صفحه‌ی نوتیفیکیشن متخصص + رفع idempotency guard با جدول اشتباه
✅ R-Jobs          → یادآوری نوبت + بولک‌نوتیفیکیشن جنریک + برداشت وجه با Payout واقعی زرین‌پال (async) + خروجی گزارش async (جدول report_exports)
✅ (مستقل) رفع باگ‌های صفحه‌ی گزارشات ادمین → دکمه‌های امروز/هفته/ماه + پیش‌فرض امروز + ستون payment_method ناموجود (کل exportها fail می‌شدن) + خروجی اکسل کاملاً خالی + ستون تعداد نوبت همیشه صفر در PDF + لینک نوتیفیکیشن به هاست اشتباه (APP_URL vs هاست واقعی، در ۶ نوتیفیکیشن) + بج نوتیفیکیشن هدر
✅ (مستقل) باگ specialists.user_id NOT NULL → migration nullable برای پشتیبانی از ساخت متخصص پیش از ثبت‌نام خود شخص
✅ (مستقل) مرتب‌سازی هوشمند «نوبت‌های من» → اولویت وضعیت (تایید/تکمیل → در انتظار → لغو) + جدیدترین در هر گروه بالاتر
✅ (مستقل) یادآوری پیامکی دقیق‌تر → از یک‌بار در روز (۱۸:۰۰، کل نوبت‌های فردا) به هر ۱۰ دقیقه چک/~۱ ساعت قبل از هر نوبت
✅ (مستقل) بازبینی نکته‌ی امنیتی BlogController عمومی → حذف روت‌های مرده‌ی اشاره‌کننده به متدهای ناموجود + رفع سومین/چهارمین نمونه‌ی باگ نام‌فایل حساس به حروف
✅ R-Observers     → تحکیم دیسپچ PaymentSucceeded در BookingObserver (رفع gap مسیر SecurePaymentController) + رفع race condition واقعی used_count کد تخفیف + تصمیم آگاهانه علیه PaymentObserver/WithdrawalObserver عمومی (ریسک بازتولید باگ نوتیف تکراری) + رفع gap نوتیف auto-payout ناموفق + رفع رگرسیون payment_method در AdminPaymentController + رفع/وایر شدن دسته‌بندی gateway_payments در گزارشات + شیت سوم جزئیات خام + شکیل‌سازی اکسل
✅ R-Traits        → استخراج HasJalaliDates (۱۴ فایل) + HandlesApiResponse (۴ فایل، عمداً محدود به الگوی واقعاً یکسان)
✅ (مستقل) رفع باگ بحرانی برگشت وجه لغو نوبت → مسیر کیف‌پولی برای لغو ادمین (نه گیت‌وی) + پس‌گرفتن سهم متخصص/کمیسیون ادمین + رفع تسویه‌ی مضاعف + پیشگیری تسویه‌ی زودهنگام + ۲ باگ Blade/Notification لغو نوبت
✅ R-DB-Transaction → بررسی سراسری هر ۳۶ مورد DB::transaction در app/؛ هیچ نمونه‌ای از الگوی خطرناک پیدا نشد؛ بدون تغییر فایل (+ رفع سوءتفاهم WalletSetting::get() که در BookingObserver امن است)
✅ R-TypeHints     → ۱۹۲ متد public در ۵۱ فایل بدون return type، اضافه/تأیید شد + استانداردسازی ۳۹ constructor (۲۴ در Controllers/Services + ۱۵ تکمیلی در Events/Observers/Notifications/Middleware/Exports) به property promotion با readonly + رفع wiring bug ReportService (کد مرده) + رفع باگ readonly-بدون-type در ۳ فایل (ReportsExport/SpecialistBookingsExport/BookingNotification)
✅ R-Cleanup-DeadCode (بخش اول) → حذف ۲۳ فایل کد مرده (۹ آیتم مستندشده‌ی قبلی + کشف زیردرخت React یتیم ۱۴ فایل هیچ‌وقت به Vite وصل نبوده) + رفع تصادم نام روت لویالتی به‌عنوان بونوس + رفع فکتوری Leave غیرفعال + تصحیح مستندسازی فاز کهنه‌ی «R-SpecialistLeave-Upgrade» (از قبل در Leave-Migration انجام شده بود). WorkSchedule در همون لحظه به تصمیم صریح کاربر دست‌نخورده موند (بعداً در ۲۰۲۶-۰۸-۰۷ کاملاً حذف شد)
✅ (مستقل) تکمیل مسیر «پرداخت امن/۲FA» → گزینه‌ی الف (کامل‌کردن) انتخاب شد: migration two_factor_enabled + میدل‌ور یکسان 2fa.enabled + ۳ ویوی گم‌شده‌ی تنظیمات ۲FA + ۴ ویوی جدید Blade+vanilla-JS پرداخت امن + رفع باگ مبلغ قابل‌دستکاری کلاینت + رفع نبود authorize + حذف BookingActions.jsx/SecureForm.jsx + رفع باگ فعال مسیر import حروف کوچک AnnouncementBanner
✅ (مستقل) باگ بحرانی OTP دو مرحله‌ای → کد هیچ‌وقت واقعاً تایید نمی‌شد (Cache::put با درایور array) + تاخیر ۲۱-۲۹ ثانیه‌ای هر ارسال کد (تماس synchronous به Kavenegar)
✅ (مستقل) تکمیل ۹ کاندید/کار باز پراکنده (۲۰۲۶-۰۸-۰۲/۰۳) → جداول اکسل گزارشات+نمودار، حذف TwoFactorAuth.jsx یتیم، فیکس فیلتر دسته‌بندی خدمات، بررسی Storage Symlink (عملیاتی — ✅ خود اقدام دستی در ۲۰۲۶-۰۸-۰۷ توسط کاربر انجام شد)، حذف deductCancellationFee، بررسی AdminSpecialistService تکراری (پیدا نشد)، مهاجرت persian-date→jcal در reschedule، بررسی محدودیت‌های Kavenegar (عملیاتی)، فیچر R-AdminDiscountCode + کشف/رفع دو باگ بحرانی export گزارش (Excel هیچ‌وقت ساخته نمی‌شد + دانلود PDF/Excel فتال ارور) + چهارمین رگرسیون نام‌فایل حساس به حروف (CheckPasswordStrengthRequest)
✅⭐ فیچر بزرگ: بازطراحی پیش‌پرداخت + منطق تخفیف → پیش‌پرداخت درصدی+سقف+قابل‌تنظیم توسط ادمین (بود: هاردکد ۳۰٪/۵۰۰۰۰ بدون کنترل ادمین) + تخفیف از «باقی‌مانده» کم می‌شه نه پیش‌پرداخت (رفع باگ منطقی مخفی که تخفیف رو بی‌اثر می‌کرد) + نمایش شفاف در تمام صفحات + پیامک/نوتیفیکیشن هر دو نقش
✅ (مستقل، ۲۰۲۶-۰۸-۰۴) سه باگ کشف‌شده حین تست واقعی فیچر بالا → پیش‌پرداخت هاردکد قدیمی در bookings/create + پیامک تخفیف رو از باقی‌مانده کم نمی‌کرد (نمایش داخل اپ درست بود ولی متن پیامک نه) + افزودن باقی‌مانده به صفحه‌ی موفقیت
✅ (مستقل، ۲۰۲۶-۰۸-۰۶) تکمیل داشبورد امنیتی حساب کاربری + پنل امنیت ادمین → security_logs (dual-write فایل+DB) + رفع باگ user_id گم‌شده در لاگ‌های ورود ناموفق + password_changed_at/password_strength_score واقعی (نه از روی هش) + Admin\Security\... جدید طبق قرارداد namespace + رفع حفره‌ی auth:sanctum شرطی در routes/api.php (کشف شد /api/security/* و /api/loyalty/* هر دو کاملاً یتیم بودن) + پنجمین/ششمین رگرسیون نام‌فایل حساس به حروف
✅ (مستقل، ۲۰۲۶-۰۸-۰۷) حذف کد مرده‌ی ویجت آمار نوبت‌های ادمین → BookingStats.jsx + mount #booking-stats + هر دو روت وب/API + getStats() کنترلر/سرویس؛ رفع/تصحیح یک یافته‌ی جانبی نادرست از جلسه‌ی قبل (ادعای "متد ناموجود/تایم‌اوت" غلط بود، مشکل واقعی یتیم‌بودن کامل زنجیره بود)
✅ (مستقل، ۲۰۲۶-۰۸-۰۷) حذف کامل فیچر WorkSchedule → تصمیم اولیه (نگه‌داری بدون استفاده) عوض شد؛ schema (unique per specialist + یک بازه‌ی مشترک) ذاتاً نمی‌تونست ساعت جدا برای هر روز رو پشتیبانی کنه، پس جایگزینی SpecialistSchedule عقب‌گرد قابلیت بود → کل فیچر (مدل/سرویس/کنترلر/Form Requestها/Blade‌ها/روت‌ها/relation) حذف شد + migration DROP TABLE
✅ R-Pint          → اجرای php vendor/bin/pint (پریست Laravel) روی کل پروژه — ۳۰۳ فایل PHP در app/database/routes/config/lang فرمت شدن؛ صرفاً سبکی، بدون تغییر منطق؛ تأیید با pint --test تمیز (۴۸۷/۴۸۷) + php -l روی همه‌ی ۳۰۳ فایل. **آخرین آیتم چک‌لیست — رفکتور کامل تکمیل شد.**
```
> نکته: `R-AdminDiscountCode` (فیچر جدید، نه رفکتور) ✅ در ۲۰۲۶-۰۸-۰۲/۰۳ تکمیل شد؛ جزئیات در بخش «⭐ رفع مستقل ... تکمیل ۹ کاندید» بالاتر، عمداً در این لیست فازبندی رفکتور نیست.

## ⭐ دستورالعمل دائمی: استاندارد کامیت کردن (الزامی، هر جلسه)
**بعد از هر بخش از کار (چه یک باگ، چه چند باگ مرتبط، چه یک فاز رفکتور)، باید همیشه مشخص بشه:**
1. **نام شاخه (branch)** پیشنهادی برای اون کار:
   - باگ‌فیکس: `fix/<شرح-کوتاه>`
   - فیچر جدید: `feat/<شرح-کوتاه>`
   - رفکتور: `refactor/<شرح-کوتاه>`
   - معمولاً یک شاخه‌ی جدید از روی `V3` (⚠️ نه `develop` — طبق تصمیم ۲۰۲۶-۰۹-۲۱، `V3` پایه‌ی کار فعلیه)
2. **پیام کامیت دقیق** طبق Conventional Commits، با prefix های قبلی: `fix:`, `feat:`, `refactor:`, `perf:`, `chore:`, `build(docker):`، فرمت `type(scope): توضیح کوتاه`
3. اگه چند تغییر نامرتبط در یک پاسخ انجام شده، باید برای هرکدوم یک کامیت جدا پیشنهاد بشه (نه یک کامیت بزرگ)
4. دستورات `git add`, `git commit -m "..."` رو دقیق و آماده‌ی کپی بده

این دستورالعمل باید **بدون نیاز به یادآوری مجدد از طرف کاربر**، در پایان هر بخش از کار رعایت بشه.

نام‌های شاخه‌ی استفاده‌شده تا الان (مرجع):
```
refactor/r-reports-admin-split
refactor/r-admin-loyalty-split
refactor/r-admin-dashboard-split
refactor/r-admin-wallet-split
refactor/r-specialist-wallet-split
refactor/r-admin-blog-split
refactor/r-admin-forms
refactor/r-admin-specialist-service
refactor/r-admin-user-service
refactor/r-admin-booking-service
feat/admin-specialist-form-requests
feat/work-schedule-complete
fix/work-schedule-model-cast-bug
feat/leave-migration
fix/leave-route-name-collision
refactor/r-discount-logic
fix/admin-loyalty-reward-missing-methods
refactor/r-admin-announcement-gallery-blade
refactor/r-events
fix/login-sms-timeout-async
fix/booking-create-vue-cdn-offline
perf/services-and-loyalty-nav-cache
feat/centralized-sms-logging
feat/admin-manual-wallet-settlement
fix/duplicate-wallet-settle-schedule
fix/specialist-withdrawal-wrong-form-request
fix/specialist-dashboard-str-not-found
fix/specialist-withdrawal-amount-validation
fix/duplicate-event-listener-auto-discovery
refactor/providers-single-source
fix/withdrawal-double-submit
fix/specialist-notifications-page-bugs
fix/withdrawal-notification-idempotency-guard-wrong-table
feat/booking-reminder-job
feat/bulk-notification-job
feat/real-zarinpal-payout-job
feat/async-report-export
fix/report-type-buttons-no-daterange
feat/reports-default-today
fix/monthly-quick-range-includes-previous-month
fix/booking-payment-method-column
fix/report-exports-and-notification-links
fix/admin-notification-header-badge-refresh
fix/specialist-user-id-not-null
feat/customer-bookings-status-priority-sort
feat/hourly-precise-booking-reminders
fix/remove-dead-blog-controller-routes
fix/blade-filename-case-sensitivity-blog-and-notifications
fix/r-observers-payment-discount-withdrawal
feat/reports-raw-bookings-sheet-and-pdf-appendix
fix/excel-export-zero-values-blank-cells
feat/excel-export-styling
refactor/r-traits-jalali-dates-and-api-response
fix/booking-cancellation-refund-wallet-path-and-payout-reversal
fix/double-settlement-and-early-settlement-prevention
fix/booking-cancel-403-and-notification-labels
refactor/r-typehints
refactor/r-cleanup-deadcode
feat/secure-payment-2fa-complete
fix/2fa-otp-never-persisted-and-sms-delay
feat/excel-specialist-service-tables-and-chart
fix/security-checkpasswordstrengthrequest-case-regression
fix/reports-excel-export-collection-typeerror-and-download
chore/remove-orphaned-two-factor-auth-react
fix/service-category-filter-not-applied
chore/remove-dead-deductCancellationFee
fix/reschedule-persian-date-cdn-to-jcal
feat/admin-discount-code-panel
feat/configurable-prepayment-percentage
fix/prepayment-display-and-sms-discount-consistency
feat/complete-security-dashboard
fix/api-auth-sanctum-always-required
chore/remove-dead-booking-stats-widget
chore/remove-workschedule-dead-feature
chore/pint-format-project
fix/security-checkpasswordstrengthrequest-index-case
test/comprehensive-test-suite-phase-1
test/comprehensive-test-suite-phase-2
test/comprehensive-test-suite-phase-3
test/comprehensive-test-suite-phase-4
test/comprehensive-test-suite-phase-5
feat/rebuild-loyalty-points-admin-panel
fix/booking-observer-test-flaky-cancellation-fee
chore/remove-8-orphaned-breeze-auth-controllers
chore/remove-orphaned-api-loyalty-routes-and-react
fix/wire-up-real-phone-verification-middleware
fix/config-env-fallback-sweep-and-routeserviceprovider-cleanup
...
fix/notification-duplicate-sms-and-settings-panel
```

⚠️ **نکته‌ی نشست ششم**: کار این نشست مستقیماً روی برنچ `V2` (برنچی که زیپ آپلودی کاربر بر پایه‌اش بود) کامیت شد، نه یک برنچ فیچر جدا — چون کاربر تأیید کرد پایه‌ی کار فعلی همین برنچه، نه `develop`. برای فازهای بعدی، اگه قرار باشه به کانوانسیون «هر فاز روی برنچ خودش» برگردیم، نام پیشنهادی همون الگوی قبلی است (مثلاً `test/comprehensive-test-suite-phase-5`).

## ⭐ دستورالعمل بررسی GLM
بعد از هر فاز، خروجی GLM5.2 را به‌عنوان second opinion بررسی کن. اگر چیزی جا مانده بود، در همان جلسه تکمیل شود قبل از رفتن به فاز بعدی. (نمونه‌ی واقعی: در R-AdminLoyalty، پیاده‌سازی GLM مدل اشتباه `LoyaltyReward` و منبع تنظیمات ناهماهنگ داشت که در بررسی مشترک کشف و رفع شد. نمونه‌ی دوم: در R-DiscountLogic، مقایسه با خروجی Kimi Agent سه کشف حیاتی اضافه کرد — باگ مالی صفحه‌ی پرداخت، import شکسته‌ی فتال در BookingDiscountController، و gap واقعی در BookingPolicy — که در بررسی مستقل Claude از قلم افتاده بودن.)
---

## ⭐⭐ فیچر برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی): SuperAdmin + سالن‌ها + اشتراک

> **وضعیت: 🟡 در انتظار پیاده‌سازی.** این نسخه‌ی نهایی و جایگزین تمام طرح‌های قبلی سوپرادمین است (طرح‌های تک‌سالنی/چندادمین‌روی‌یک‌سالن باطل شدند). هنوز هیچ کدی commit نشده.
> **درخواست اصلی کاربر (۲۰۲۶-۰۸-۲۸)**: «ادمین‌هایی که سوپرادمین تعریف می‌کنه هر کدوم باید مختص یک سالن باشن... هر سالن یک اشتراک ۱/۳/۶/۱۲ ماهه داره... آدرس هر سالن باید یکتا و جدا از بقیه باشه.»

### هدف کلی
تبدیل معماری فعلی (یک برند واحد «راستا») به یک **پلتفرم SaaS چند-مستأجری (multi-tenant)**:
1. هر «سالن» (tenant) یک نام نمایشی (قابل‌تغییر توسط ادمینش)، یک آدرس یکتا و ثابت (slug)، یک اشتراک با تاریخ انقضا، یک سقف تعداد متخصص، و **دقیقاً یک ادمین** دارد (v1).
2. سوپر ادمین در پنل مجزای `/superadmin` سالن + اشتراک + ادمین + سقف + دسترسی‌های ماژولار را در یک فرم واحد می‌سازد.
3. داده‌های هر سالن (متخصص، نوبت، خدمت، وبلاگ، گالری، کد تخفیف، تنظیمات مالی/وفاداری) کاملاً از سالن‌های دیگر ایزوله است — هیچ overlap ممکن نیست.
4. با پایان اشتراک، دسترسی ادمین و امکان رزرو جدید مسدود می‌شود؛ داده‌ها حذف نمی‌شوند، فقط سوپر ادمین می‌تواند تمدید کند.
5. داده‌ی فعلی پروژه («سالن زیبایی راستا») بدون شکستن چیزی، به یک سالن پیش‌فرض منتقل می‌شود.

### تصمیمات معماری کلیدی

| موضوع | تصمیم | دلیل |
|-------|-------|------|
| **مدل چندمستأجری** | Single-database, shared-schema (نه دیتابیس جدا به‌ازای هر سالن) | نگهداری/بکاپ/دیپلوی ساده‌تر؛ ایزولاسیون از طریق `salon_id` + Global Scope تضمین می‌شود. |
| **نسبت سالن به ادمین** | جدول pivot `salon_admins` (`salon_id`, `user_id`, `role`) از همون فاز ۱ — نه ستون یکتا روی `salons` | future-proof: فاز ۲ («چند ادمین روی یک سالن») فقط قانون «حداکثر ۱ ادمین در سطح اپلیکیشن» رو برمی‌داره؛ نیازی به migration دردسرساز روی داده‌ی زنده نیست. v1 رفتارش با درخواست کاربر (ابوالفضل=ابول، پارسا=الماس، نگار=دیاموند، هرکدام یک ادمین) یکسانه. |
| **شناسایی سوپر ادمین** | `hasRole('super-admin')` (نه `hasPermission()`، نه ستون مستقیم) | هم‌راستا با anti-pattern مستندشده‌ی `is_admin` (۶ بار در فازهای قبلی فیکس شد) — و یک لایه‌ی عمیق‌تر: خودِ `hasPermission()` به‌خاطر bypass داخلی‌اش (`is_admin=true` → true) قابل‌اعتماد نیست؛ جزئیات کامل در بخش Middleware. |
| **آدرس یکتای سالن** | مسیر `/s/{slug}` برای صفحات عمومی مشتری؛ `slug` بعد از ساخت **immutable** است | ساده‌تر از ساب‌دامین (نیاز به DNS wildcard ندارد)؛ ثابت‌بودن slug از شکستن لینک‌های قدیمی/پیامک‌های ارسال‌شده جلوگیری می‌کند. |
| **نام نمایشی سالن** | `salons.name` — **قابل‌تغییر توسط خود ادمین** از پنلش | درخواست صریح کاربر؛ در هدر سایت، عنوان پنل، پیامک‌ها جایگزین «راستا» می‌شود. جدا از `slug` نگه‌داشته می‌شود تا تغییر اسم لینک را نشکند. |
| **ایزولاسیون داده** | ستون `salon_id` روی تمام مدل‌های صاحب‌داده + `BelongsToSalon` trait با Global Scope خودکار | تنها راه مطمئن برای «هیچ overlap نداشته باشند»؛ فراموش‌کردن دستی WHERE در یک کنترلر، دقیقاً همون کلاس باگ `is_admin`/route-model-binding قبلی این پروژه رو تکرار می‌کنه. |
| **اشتراک** | `subscription_type` enum(`1m`,`3m`,`6m`,`12m`) + `subscription_ends_at` (تاریخ محاسبه‌شده) | خرید بیرون از سیستم انجام می‌شود (v1: کارت‌به‌کارت)؛ سوپر ادمین فقط نتیجه را ثبت/تمدید می‌کند — درگاه خودکار فاز بعدی. |
| **رفتار بعد از انقضا** | مسدودسازی لاگین ادمین + مسدودسازی رزرو جدید مشتری؛ داده‌ها دست‌نخورده باقی می‌مانند | نه حذف داده (غیرقابل‌برگشت و خطرناک)، نه دسترسی کامل (بی‌معنی‌کردن مدل اشتراک). |
| **مهاجرت داده‌ی فعلی** | یک رکورد `salons` پیش‌فرض (id=1، نام «سالن زیبایی راستا»، slug=`rasta`، اشتراک ۱۲ ماهه از تاریخ deploy) + backfill تمام جدول‌های موجود با `salon_id=1` | تضمین می‌کند production فعلی با این migration نمی‌شکند. |
| **اولین سوپر ادمین** | `php artisan superadmin:create` (Command) — بدون فرم ثبت‌نام عمومی | حفره‌ی امنیتی رو نمی‌بندیم اگه فرم عمومی باز بمونه. |
| **کاهش سقف متخصص** | Validation error اگر سقف جدید کمتر از تعداد فعلی متخصصین آن سالن باشد | جلوگیری از وضعیت ناسازگار. |
| **خودِ سوپر ادمین** | بدون سقف، بدون `salon_id`، دسترسی کامل به همه‌ی سالن‌ها از طریق پنل `/superadmin` (و در صورت نیاز، امکان ورود اضطراری به هر پنل ادمین) | «دسترسی کامل» طبق درخواست اولیه‌ی کاربر. |

---

### ساختار پایگاه‌داده

**Migration 1 — جدول `salons` (هسته‌ی چندمستأجری):**
```php
Schema::create('salons', function (Blueprint $table) {
    $table->id();
    $table->string('name');                          // نام نمایشی، قابل‌تغییر توسط ادمین
    $table->string('slug')->unique();                 // آدرس یکتا (/s/{slug})، immutable بعد از ساخت
    // admin_user_id حذف شد — رابطه‌ی ادمین↔سالن از طریق جدول pivot salon_admins زیر تعریف می‌شود (future-proof برای فاز ۲: چند ادمین)
    $table->unsignedInteger('max_specialists_count')->default(0); // 0 = هیچ
    $table->json('module_permissions')->nullable();   // ["manage_blog", "manage_gallery", ...]
    $table->enum('subscription_type', ['1m', '3m', '6m', '12m']);
    $table->timestamp('subscription_started_at');
    $table->timestamp('subscription_ends_at');
    $table->boolean('is_suspended')->default(false);  // تعلیق دستی توسط سوپر ادمین (جدا از انقضای اشتراک)
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // سوپر ادمین سازنده
    $table->timestamps();
});
```

**Migration 1.5 — جدول pivot `salon_admins` (رابطه‌ی ادمین↔سالن، از فاز ۱ آماده‌ی چندادمینی):**
```php
Schema::create('salon_admins', function (Blueprint $table) {
    $table->id();
    $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->enum('role', ['owner', 'staff'])->default('owner'); // owner = ادمین اصلی سالن، staff = منشی/ادمین دوم (فاز ۲)
    $table->timestamps();
    $table->unique(['salon_id', 'user_id']);
});
```
⚠️ **قانون فاز ۱ (فقط در سطح اپلیکیشن، نه دیتابیس)**: `SuperAdminService::createSalonWithAdmin()` اجازه نمی‌دهد بیش از یک ردیف `role='owner'` برای هر `salon_id` ثبت شود. این یک validation در Service است، نه یک قید دیتابیسی — دقیقاً همین انتخاب باعث می‌شود فاز ۲ («چند ادمین») فقط با حذف این validation و اضافه‌کردن UI انجام شود، بدون هیچ migration دیگری روی داده‌ی زنده.

**Migration 2 — افزودن `salon_id` به جدول‌های صاحب‌داده (✅ پیاده‌سازی شده — `2026_08_29_000102_add_salon_id_to_owned_tables.php`):**
```php
// specialists, beauty_services, categories, blog_posts, blog_categories,
// gallery_images, announcements, discount_codes, loyalty_settings,
// wallet_settings, admin_wallet, bookings (denormalized برای سرعت query)
Schema::table('{table}', function (Blueprint $table) {
    $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->cascadeOnDelete();
});
```
⚠️ **کشف حین پیاده‌سازی (نه در طرح اولیه بود)**: `admin_wallet` یک singleton واقعیه — `AdminWallet::getWallet()` دقیقاً `self::first()` می‌زنه با یک ردیف seed‌شده در migration. بدون `salon_id`، کمیسیون همه‌ی سالن‌ها توی یک ردیف مشترک جمع می‌شد. `wallet_settings` هم دقیقاً همین الگو رو داره (`WalletSetting::first()`). هر دو به لیست بالا اضافه شدن؛ تبدیل خودِ `::first()` به salon-aware، کار کامیت ۲ (لایه‌ی ایزولاسیون/`BelongsToSalon`) است، نه این کامیت. `discount_usages`, `loyalty_points`, `rewards`, `loyalties` عمداً `salon_id` مستقل نگرفتن — همیشه از طریق parent صاحب‌داده (که خودش scoped شده) قابل‌دسترسین.

ستون نالیبل (`nullable()`) گذاشته شده — migration بعدی (`2026_08_29_000103_backfill_default_salon_and_salon_id.php`، ✅ پیاده‌سازی شده) یک سالن پیش‌فرض («سالن زیبایی راستا»، slug=`rasta`) می‌سازه، تمام رکوردهای موجود رو بهش وصل می‌کنه، هر کاربر `is_admin=true` فعلی رو در `salon_admins` با نقش `owner` ثبت می‌کنه، و در آخر ستون رو `NOT NULL` می‌کنه — همه در یک migration، امن روی دیتابیس واقعیِ پر از داده.

⚠️ **قبل از نوشتن این migration باید با `grep`/بررسی دستی روی زیپ واقعی، فهرست کامل و دقیق مدل‌های صاحب‌داده تأیید بشه** — طبق الگوی تکراری این پروژه («مستند شدن قبل از تکمیل واقعی کار»، مستند در بخش «روال دسترسی به GitHub»)، فهرست بالا نقطه‌ی شروعه نه فهرست نهایی قطعی.

---

### ایزولاسیون داده — `BelongsToSalon` Trait (✅ پیاده‌سازی شده — `app/Traits/BelongsToSalon.php`)

```php
trait BelongsToSalon
{
    protected static function bootBelongsToSalon(): void
    {
        static::addGlobalScope('salon', function (Builder $builder) {
            if ($salonId = app(\App\Support\CurrentSalon::class)->id()) {
                $builder->where($builder->getModel()->getTable() . '.salon_id', $salonId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->salon_id) && $salonId = app(\App\Support\CurrentSalon::class)->id()) {
                $model->salon_id = $salonId;
            }
        });
    }
}
```
`App\Support\CurrentSalon` (✅ پیاده‌سازی شده، ثبت‌شده به‌عنوان singleton در `AppServiceProvider::register()`) در ابتدای request (توسط middleware کامیت بعدی) بر اساس **کاربر لاگین‌کرده‌ی ادمین** یا **slug مسیر عمومی** (`/s/{slug}`) پر می‌شود. سوپر ادمین نیازی به `withoutGlobalScope()` دستی ندارد — چون این middleware هیچ‌وقت برای مسیرهای `/superadmin` چیزی روی `CurrentSalon` ست نمی‌کنه، `id()` همیشه `null` برمی‌گرده و scope خودش فیلتری اضافه نمی‌کنه؛ یعنی سوپر ادمین به‌صورت خودکار همه‌ی سالن‌ها رو می‌بینه.

روی هر ۱۲ مدل صاحب‌داده اعمال شد: `Specialist`, `BeautyService`, `Category`, `BlogPost`, `BlogCategory`, `GalleryImage`, `Announcement`, `DiscountCode`, `LoyaltySetting`, `WalletSetting`, `AdminWallet`, `Booking`.

⚠️ **مشکل سیستمیک کشف‌شده حین پیاده‌سازی (نه چیزی که در طرح اولیه دیده شده بود)**: چون `salon_id` روی این ۱۲ جدول حالا `NOT NULL`ه و trait بالا فقط وقتی پرش می‌کنه که `CurrentSalon` از قبل ست شده باشه (یعنی وسط یک HTTP request با middleware سالن)، **تقریباً هیچ‌کدوم از ~۹۰۰ تست موجود پروژه** (که مستقیم فکتوری/سرویس صدا می‌زنن، نه از مسیر HTTP کامل) نمی‌تونستن یک ردیف بسازن — قید NOT NULL رد می‌شد. راه‌حل: `Tests\TestCase::setUp()` (پایه‌ی مشترک همه‌ی تست‌ها) حالا همون سالن پیش‌فرضی که migration بک‌فیل ساخته رو (`firstOrCreate` با `slug=rasta`، نه یک سالن تازه هر تست — چون با ردیف ساخته‌شده توسط migration در unique constraint تصادم می‌کرد) توی `CurrentSalon` ست می‌کنه؛ تست‌هایی که می‌خوان ایزولاسیون بین‌سالنی رو تست کنن (مثل `AdminBookingSlotConflictTest` و `SalonManagementTest` آینده) خودشون سالن اضافه می‌سازن و `CurrentSalon::set()`/`clear()` رو صریح صدا می‌زنن.

⚠️ **شکاف شناخته‌شده، باقی‌مانده تا قبل از آماده‌بودن این برنچ برای deploy**: seederهای پروژه (`SpecialistSeeder` و غیره) از طریق CLI اجرا می‌شن، جایی که `CurrentSalon` هیچ‌وقت ست نمی‌شه — پس با همون قید NOT NULL شکست می‌خورن. چون هیچ تست خودکاری seeder رو صدا نمی‌زنه، تست‌ها امن موندن، ولی یک اجرای واقعی `php artisan db:seed` روی این مشکل می‌خوره؛ رفع نشده (نیاز به عبور دستی از هر seeder و تعیین `salon_id` صریح یا bind کردن `CurrentSalon` در `DatabaseSeeder::run()`).

---

### Middleware (✅ پیاده‌سازی شده — کامیت ۳)

⚠️ **کشف حیاتی پیش از نوشتن کد (تغییر نسبت به طرح اولیه)**: `User::hasPermission()` یک bypass دارد — `if ($this->is_admin) return true;` قبل از این‌که اصلاً به permissionها نگاه کند. یعنی اگر `EnsureSuperAdmin` بر پایه‌ی `hasPermission('super_admin')` ساخته می‌شد (همان‌طور که طرح اولیه می‌گفت)، **هر ادمین معمولی فعلی (`is_admin=true`) خودکار سوپر ادمین هم می‌شد** — دقیقاً همان کلاس باگ `is_admin` که این پروژه چند بار دیگر (R-AdminLoyalty, R-AdminForms, R-Events) فیکس کرده. `hasRole()` این مشکل را ندارد (فقط `$this->roles->contains('name', $role)`، بدون bypass)، پس هر سه middleware زیر از `hasRole('super-admin')` استفاده می‌کنند، نه `hasPermission('super_admin')`. **پیش‌نیاز**: باید یک Role با نام `super-admin` وجود داشته باشد (کار کامیت ۷ — `superadmin:create`).

**`App\Http\Middleware\ResolveSalonFromRoute`** (برای صفحات عمومی `/s/{slug}/...`):
```php
public function handle(Request $request, Closure $next): Response
{
    $salon = Salon::where('slug', $request->route('salon_slug'))->first();

    if (! $salon || $salon->is_suspended || $salon->subscription_ends_at->isPast()) {
        abort(404);
    }

    app(CurrentSalon::class)->set($salon);

    return $next($request);
}
```
⚠️ **تغییر امنیتی نسبت به طرح اولیه**: به‌جای ۴۰۳، حالا ۴۰۴ برمی‌گردونه — چون ۴۰۳ به یک بازدیدکننده‌ی بیرونی تأیید می‌کنه که «این slug وجود داره ولی مسدوده» (مثلاً برای رقیبی که می‌خواد بفهمه یک سالن خاص تعلیق شده یا نه). ۴۰۴ چنین سیگنالی نمی‌ده.

**`App\Http\Middleware\EnsureAdminSalonActive`** (برای پنل `/admin/*` ادمین معمولی؛ سیم‌کشی واقعی روی route group در کامیت ۴):
```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->hasRole('super-admin')) {
        return $next($request); // بدون CurrentSalon — همه‌ی سالن‌ها را بدون فیلتر می‌بیند
    }

    $salon = $user->salons()->first(); // belongsToMany(Salon::class, 'salon_admins') — فاز ۱: همیشه فقط یک ردیف

    if (! $salon || $salon->is_suspended || $salon->subscription_ends_at->isPast()) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'phone' => 'اشتراک سالن شما پایان یافته یا غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.',
        ]);
    }

    app(CurrentSalon::class)->set($salon);

    return $next($request);
}
```
⚠️ کلید خطای `withErrors` طبق قرارداد واقعی پروژه `'phone'` است، نه `'email'` (پروژه اصلاً ستون email روی users ندارد — طرح اولیه اشتباه نوشته بود؛ در `PasswordResetController` همین‌جوری تأیید شد).

**`App\Http\Middleware\EnsureSuperAdmin`** (برای `/superadmin/*`):
```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth()->user();

    if (! $user || ! $user->hasRole('super-admin')) {
        abort(403, 'دسترسی فقط برای سوپر ادمین.');
    }

    return $next($request);
}
```

ثبت هر سه در `bootstrap/app.php` (Laravel 11، نه `Kernel.php` — ✅ انجام شد، کنار middleware alias های موجود پروژه):
```php
$middleware->alias([
    // ...بقیه‌ی alias های موجود پروژه...
    'super_admin'   => \App\Http\Middleware\EnsureSuperAdmin::class,
    'salon.active'  => \App\Http\Middleware\EnsureAdminSalonActive::class,
    'salon.resolve' => \App\Http\Middleware\ResolveSalonFromRoute::class,
]);
```
⚠️ فقط alias ها ثبت شدن؛ اتصال واقعی `salon.active` به route group موجود `/admin` و `salon.resolve` به روت‌های عمومی، کار کامیت ۴ است (تغییر خودِ `routes/web.php` عمداً به این کامیت موکول شد تا سیم‌کشی middleware از تغییر واقعی روت‌ها جدا بمونه).
```

---

### مسیرها (Routes)

```php
// routes/public-salon.php — صفحات عمومی مشتری، هر سالن زیر آدرس یکتای خودش
Route::middleware('salon.resolve')->prefix('s/{salon_slug}')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('salon.home');
    Route::get('/services', [ServiceController::class, 'index'])->name('salon.services');
    Route::get('/specialists/{specialist}', [SpecialistController::class, 'show'])->name('salon.specialists.show');
    Route::post('/bookings', [BookingController::class, 'store'])->name('salon.bookings.store');
    // ... بقیه‌ی روت‌های عمومی فعلی، همه زیر همین prefix منتقل می‌شن
});

// routes/admin.php (موجود) — فقط middleware اضافه می‌شه، ساختار فعلی دست‌نخورده
Route::middleware(['auth', 'salon.active'])->prefix('admin')->name('admin.')->group(function () {
    // ... تمام روت‌های فعلی admin بدون تغییر
});

// routes/super-admin.php — جدید
Route::middleware(['auth', 'super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/salons', [SuperAdminController::class, 'index'])->name('salons.index');
    Route::get('/salons/create', [SuperAdminController::class, 'create'])->name('salons.create');
    Route::post('/salons', [SuperAdminController::class, 'store'])->name('salons.store');
    Route::get('/salons/{salon}/edit', [SuperAdminController::class, 'edit'])->name('salons.edit');
    Route::put('/salons/{salon}', [SuperAdminController::class, 'update'])->name('salons.update');
    Route::post('/salons/{salon}/renew', [SuperAdminController::class, 'renewSubscription'])->name('salons.renew');
    Route::post('/salons/{salon}/toggle-suspend', [SuperAdminController::class, 'toggleSuspend'])->name('salons.toggle-suspend');
});
```

⚠️ **مهاجرت روت‌های عمومی موجود زیر `/s/{slug}` یک تغییر گسترده است** — هر لینک hardcode شده در Blade (`route('home')`, `route('services.index')` و غیره) باید بازبینی بشه تا `salon_slug` جاری رو منتقل کنه؛ دقیقاً همون کلاس ریسکی که در فاز تست‌نویسی این پروژه (نشست‌های ۱ تا ۱۱) بارها کشف شد («لینک هاردکد که به هاست/مسیر اشتباه اشاره می‌کرد»). این migration باید با پوشش تست کامل قبل/بعد انجام بشه.

---

### پنل سوپر ادمین — فرم ساخت سالن (فرم واحد)

`resources/views/superadmin/salons/create.blade.php` یک فرم است، نه چند مرحله:
- نام سالن (نمایشی)
- Slug (آدرس یکتا) — validation: `unique:salons,slug`, `alpha_dash`, پیش‌نمایش زنده‌ی `/s/{slug}`
- مدت اشتراک: رادیو ۱/۳/۶/۱۲ ماه → `subscription_ends_at` خودکار محاسبه می‌شود
- مشخصات ادمین: نام، موبایل، رمز عبور
- سقف تعداد متخصص (عدد)
- چک‌باکس دسترسی‌های ماژولار: وبلاگ، گالری، اطلاعیه‌ها، وفاداری، کد تخفیف، گزارشات، تنظیمات کیف‌پول

`SuperAdminService::createSalonWithAdmin()` همه‌ی این‌ها را در یک `DB::transaction` انجام می‌دهد: ساخت `User` ادمین → ساخت `Salon` → ثبت ردیف در `salon_admins` (`role='owner'`) → assign کردن نقش/دسترسی‌های ماژولار.

---

### Design Tokens — پنل سوپر ادمین (تمایز بصری از admin معمولی)

```css
--superadmin-bg: #0F172A;        /* slate-900 */
--superadmin-surface: #1E293B;   /* slate-800 */
--superadmin-border: #334155;    /* slate-700 */
--superadmin-accent: #C9A24B;    /* gold برند پروژه، برای تمایز */
--superadmin-accent-hover: #E6CD8A;
--superadmin-danger: #EF4444;
--superadmin-success: #22C55E;
```
لایوت مستقل `@extends('layouts.super-admin')` (نه `layouts.admin`) چون سوپر ادمین مفهوماً بیرون از هر سالن خاصی می‌ایسته.

---

### داشبورد سوپر ادمین

- کارت‌ها: تعداد سالن‌های فعال، تعداد کل متخصصین (همه‌ی سالن‌ها)، تعداد سالن‌هایی که اشتراکشون تا ۷ روز آینده تموم می‌شه (هشدار)، تعداد سالن‌های منقضی‌شده.
- جدول سالن‌ها: نام، slug، ادمین، سقف/مصرف متخصص، نوع اشتراک، تاریخ پایان (رنگ قرمز اگر گذشته/نزدیک)، وضعیت، عملیات (ویرایش/تمدید/تعلیق).
- دکمه‌ی «تمدید سریع» روی هر ردیف: انتخاب مدت جدید → `subscription_ends_at` از `max(now(), subscription_ends_at)` جمع زده می‌شه.

---

### تست‌های الزامی (Feature)

`tests/Feature/SuperAdmin/SalonManagementTest.php`:
- سوپر ادمین سالن+ادمین+اشتراک را در یک فرم می‌سازد و همه‌ی رکوردها درست لینک می‌شوند.
- ادمین سالن A نمی‌تواند داده‌ی سالن B را از هیچ endpoint ای ببیند (حتی با دستکاری مستقیم ID در URL — تست IDOR).
- متخصص سالن A در نتایج جستجو/رزرو سالن B ظاهر نمی‌شود.
- ادمین با اشتراک منقضی نمی‌تواند لاگین کند.
- رزرو جدید روی سالن منقضی مسدود می‌شود؛ رزروهای قبلی همچنان قابل مشاهده‌اند.
- کاهش سقف متخصص به زیر تعداد فعلی رد می‌شود.
- سوپر ادمین بدون محدودیت سالن به همه‌ی داده‌ها دسترسی دارد.

---

### ⚠️ نکات فنی و امنیتی حیاتی

1. **هرگز فراموش نشود که `salon_id` روی هر query جدید (raw query، Excel export، PDF report) هم اعمال بشه** — Global Scope فقط روی Eloquent کار می‌کند؛ در `AdminReportExport`/`ChartExportService` (که با query builder خام کار می‌کنن) باید صریحاً `where('salon_id', ...)` اضافه بشه.
2. **IDOR (Insecure Direct Object Reference) تست بشه** — چون Global Scope خودش برای همه‌ی مسیرها کافی نیست اگه یک کنترلر مستقیم `Specialist::withoutGlobalScopes()->find($id)` بزنه یا route-model-binding سراسری (همون باگ مستندشده‌ی `{specialist}`/`{service}` این پروژه) رو دور بزنه.
3. **Slug immutable** — فرم ویرایش سالن فیلد slug را غیرقابل‌ویرایش (readonly) نشان می‌دهد؛ فقط از طریق دستور Artisan خاص (با تأیید دستی) قابل‌تغییره، نه از UI معمولی.
4. **سالن پیش‌فرض (`rasta`, id=1) هرگز نباید تعلیق/حذف بشه** — یک validation در `SuperAdminController` مانع تعلیق سالن id=1 می‌شود (یا هر سالنی که به‌عنوان «سالن سیستم» علامت‌گذاری شده).
5. **Password hashing در Service** — نه در Controller.
6. **UUID/رشته در attribute inline** — طبق قانون تکراری پروژه، همیشه با کوتیشن تک.

---

## 🗂️ فاز ۱ از ۲ — SaaS Multi-Tenant + Super Admin (محدوده‌ی این پیاده‌سازی)

**Branch:** `feat/saas-multi-tenant-salons`

### کامیت‌های پیشنهادی (به ترتیب اعمال)
1. `feat(database): add salons table + salon_id to owned models + backfill default salon`
2. `feat(auth): BelongsToSalon trait, CurrentSalon service, salon middleware trio`
3. `feat(routes): migrate public routes under /s/{slug} prefix`
4. `feat(superadmin): SuperAdminController/Service, salon creation form, dashboard`
5. `fix(admin): scope reports/exports (raw queries) to current salon`
6. `test(saas): IDOR + isolation + subscription-expiry feature tests`
7. `chore: superadmin:create artisan command + seeder`

### 📊 پیگیری پیشرفت فاز ۱ (این چک‌لیست حین کار به‌روز می‌شود)

> قانون: بعد از هر نشست/کامیت، آیتم مربوطه ✅ می‌شود + یک خط تاریخ‌دار طبق قرارداد همیشگی این سند («✅ رفع مستقل (تاریخ): ...») به پایین این چک‌لیست اضافه می‌شود — دقیقاً همون الگویی که در فاز تست‌نویسی (نشست اول تا یازدهم) و بقیه‌ی فازهای رفکتور استفاده شده.

- [x] ۱. دیتابیس — migration `salons` + `salon_admins` (pivot) + `salon_id` روی مدل‌های صاحب‌داده + بک‌فیل سالن پیش‌فرض (`id=1`, `slug=rasta`, owner=ادمین فعلی) — ✅ پیاده‌سازی شده (۴ فایل migration)
- [x] ۲. ایزولاسیون داده — `App\Traits\BelongsToSalon` + `App\Support\CurrentSalon` — ✅ پیاده‌سازی شده (روی هر ۱۲ مدل صاحب‌داده اعمال شد؛ برای جزئیات مشکل NOT NULL/تست‌ها که کشف و رفع شد، به بخش بالا نگاه کن)
- [x] ۳. Middleware — `salon.resolve`, `salon.active`, `super_admin` + ثبت در `bootstrap/app.php` — ✅ پیاده‌سازی شده (فقط alias ها؛ سیم‌کشی واقعی روی route group موجود، کار کامیت ۴)
- [x] ۴a. روت‌های سوپر ادمین + وصل `salon.active` به route group موجود `/admin` — ✅ پیاده‌سازی شده (`routes/super-admin.php` + تغییر `routes/web.php`)
- [x] ۴b-۱. `web/public.php` (۵ روت: `home`, `services.index/show`, `blog.index/show`) زیر `/s/{salon_slug}` — ✅ پیاده‌سازی شده. کاملاً guest-accessible، بدون هیچ تداخلی با بحث هویت مشتری.
- [x] ۴b-۲. ۷ فایل authenticated (بدون مخلوط بودن) — ✅ پیاده‌سازی شده: `profiles.php`, `services.php`, `bookings.php`, `payments.php`, `loyalty.php`, `security.php`, `wallet.php` زیر `/s/{salon_slug}` با میدل‌ور جدید `salon.customer` (`EnsureCustomerBelongsToSalon`) منتقل شدن.
  ⚠️ **یک شکاف امنیتی که قبل از انتقال کشف و رفع شد**: `auth()` فقط می‌گفت «لاگینه»، نه «مال همین سالنه». مشتری لاگین‌شده‌ی سالن A می‌تونست `/s/salon-B/dashboard` رو باز کنه و با سشن سالن A علیه سالن B عمل کنه. میدل‌ور جدید `salon.customer` این عدم‌تطابق رو با logout کامل + هدایت به لاگین همون سالن می‌بنده.
  ⚠️ **یک باگ از‌قبل‌موجود کشف و ضمناً رفع شد**: `specialistprofile.php` یک بخش با کامنت «PUBLIC ROUTES (no auth required)» داشت که در web.php قدیم اشتباهاً داخل گروه `['auth','verified']` تودرتو بود — یعنی هیچ‌وقت واقعاً public نبوده. حالا که این فایل کاملاً بیرون از گروه‌بندی جدید قرار گرفت (چون میدل‌ورش رو خودش داخلی مدیریت می‌کنه)، این بخش بالاخره واقعاً public شد — یک تغییر رفتاری مثبت، نه چیزی که SaaS خواسته باشه.
- [x] ۴b-۳. تفکیک `specialistprofile.php`/`reviews.php` — ✅ پیاده‌سازی شده:
  - `routes/web/public-specialists.php` (جدید): بخش `specialists.*` (مرور عمومی پروفایل) + `reviews.specialist` (مشاهده‌ی نظرات) — هر دو زیر `/s/{slug}`.
  - `web/specialistprofile.php`: فقط بخش `specialist.*` (داشبورد خودِ متخصص) موند — سراسری، مثل `/admin`.
  - `web/reviews.php`: فقط `reviews.create/store/thank-you` (نوشتن نظر، نیاز به مشتری لاگین‌شده) موند — این سه‌تا هم به گروه authenticated زیر `/s/{slug}` منتقل شدن.
  ⚠️ **یک شکاف دیگه‌ی کشف‌شده حین این تفکیک، رفع شد**: داشبورد خودِ متخصص (`specialist.*`) از اول SaaS هیچ middleware ای برای ست‌کردن `CurrentSalon` نداشت! چون اکثر کوئری‌ها صریح `where('specialist_id', ...)` می‌زنن، خطرش کم بود، ولی `SpecialistWalletService` دقیقاً همون الگوی `WalletSetting::first()` رو داره (همون singletonی که در کامیت ۱ کشف شد) — بدون `CurrentSalon`، تنظیمات کیف‌پول یک سالن تصادفی می‌خوند. میدل‌ور جدید `salon.specialist` (`EnsureSpecialistSalonActive`) این‌رو بست؛ رابطه‌ی `Specialist::salon()` هم همین‌جا اضافه شد (قبلاً وجود نداشت).
  ⚠️ نام روت `reviews.specialist` عمداً حفظ شد (نه rename) — با یک تعریف route جدا بیرون از گروه نام‌دار `specialists.` که وگرنه بهش پیشوند اضافه می‌کرد.

**نتیجه: تمام ۱۷۶ روت عمومی/authenticated مشتری، به‌جز داشبورد خودِ متخصص (که درست هم هست بمونه سراسری)، الان زیر `/s/{slug}` هستن. فاز ۱ SaaS از نظر روت‌بندی کامل شد.**

---
- [x] ۵. `SuperAdminController` + `SuperAdminService::createSalonWithAdmin()` — ✅ پیاده‌سازی شده. `SuperAdminService` از `AdminUserService::create()` برای ساخت خودِ کاربر ادمین استفاده می‌کنه (DRY، طبق طرح مستندشده)؛ منطق خودش فقط سالن/اشتراک/سهمیه/pivot owner است.
- [x] ۶. ویوها — `layouts/superadmin.blade.php`, فرم ساخت سالن, داشبورد — ✅ پیاده‌سازی شده (تم تیره+طلایی طبق design tokens مستندشده).
  ⚠️ چک‌باکس‌های «دسترسی‌های ماژولار» فقط **ذخیره** می‌شن؛ اعمال واقعی‌شون روی منوی پنل ادمین (بستن حفره‌ی دومی که در طرح اولیه‌ی SaaS اشاره شده بود: «هر ادمینی می‌تونه ادمین تمام‌دسترس بسازه») هنوز پیاده‌سازی نشده — یک آیتم مجزا برای بعد از این فاز.
- [x] ۶.۵ (فرعی، بین کامیت ۶ و ۷) — `chore(seeders)`: رفع شکاف مستندشده‌ی seederهای CLI — ✅ پیاده‌سازی شده. `DatabaseSeeder` حالا `CurrentSalon` رو به سالن `rasta` وصل می‌کنه (وگرنه هر seederی که `Specialist`/`BeautyService`/کاربر می‌ساخت با NOT NULL می‌شکست). `UserSeeder`: ادمین seed‌شده `user_type='staff'` گرفت (وگرنه با لاگین جدید staff-only پیدا نمی‌شد) + یک **سوپر ادمین موقت تستی** (`09399999999`/`superadmin`) اضافه شد تا `/superadmin` قبل از وجود دستور واقعی artisan قابل‌تست باشه. `RoleSeeder`: نقش `super-admin` هم اضافه شد.
- [x] ۷. `php artisan superadmin:create` — ✅ پیاده‌سازی شده (۲۰۲۶-۰۹-۱۸، `App\Console\Commands\CreateSuperAdmin`). آرگومان/گزینه‌ها (`phone`, `name`, `--password`) با fallback به `ask()`/`secret()` تعاملی وقتی داده نشن؛ همون قوانین validation فارسی `StoreSalonRequest` (regex شماره، یکتایی در بین staff، حداقل ۸ کاراکتر رمز). ⚠️ نکته‌ی مهم کشف‌شده: نقش super-admin رو نمی‌شه از طریق پارامتر `roles` روی `AdminUserService::create()` ست کرد — `filterAssignableRoles()` چون `auth()->user()` در کانتکست کنسول همیشه `null`ه، هر نقش super-admin رو حذف می‌کنه. فیکس: sync مستقیم نقش با `$user->roles()->syncWithoutDetaching([...])`، دقیقاً همون الگوی موجود در `UserSeeder` برای حساب موقت. حساب موقت seed‌شده (`09399717435`) عمداً هنوز حذف نشده — یک کار جدای بعدی، نه پیش‌نیاز این کامیت.
- [x] ۸. تست‌ها — ✅ پیاده‌سازی و **واقعاً اجرا شده** (۲۰۲۶-۰۹-۱۸): `tests/Feature/SuperAdmin/SalonManagementTest.php` (۱۷ تست: ساخت کامل سالن+ادمین، رد slug/شماره تکراری، IDOR/دسترسی — ادمین معمولی و مهمان به هیچ روت `/superadmin/*`ای نمی‌رسن، انقضا/تمدید از تاریخ فعلی در برابر از الان، تعلیق/رفع‌تعلیق، سقف متخصص، مصونیت سالن پیش‌فرض + گارد در برابر چک بیش‌ازحد‌کلی روی slug/name) + `tests/Feature/Console/CreateSuperAdminTest.php` (۸ تست: موفقیت، ساخت خودکار نقش، رد شماره/رمز نامعتبر، فلوی تعاملی prompts، تست ریگرسیون مخصوص باگ بالا).
  ⚠️ **باگ واقعی کشف/رفع‌شده حین نوشتن این تست‌ها (نه فقط بررسی کد — با اجرای واقعی پیدا شد)**: `SuperAdminService::updateSalon()` و `remainingSpecialistQuota()` هر دو با `Specialist::where('salon_id', $salon->id)->count()` تعداد متخصص رو می‌شمردن — این کوئری همچنان از global scope `BelongsToSalon` رد می‌شه؛ اگه `CurrentSalon` به یک سالن *دیگه* بایند شده باشه (دقیقاً چیزی که `TestCase::setUp()` پیش‌فرض برای هر تست انجام می‌ده)، دو فیلتر `salon_id` هرگز با هم match نمی‌شن و شمارش بی‌صدا صفر برمی‌گرده — یعنی گارد «کاهش سقف زیر تعداد فعلی ممنوع» هیچ‌وقت واقعاً فایر نمی‌شد. فیکس: `withoutGlobalScope('salon')` روی هر دو کوئری (همون الگوی تکراری «global scope با فیلتر دستی روی همون ستون تداخل می‌کنه» که قبلاً با `WalletSetting`/`AdminWallet` هم دیده شده بود). این باگ روی ترافیک واقعی `/superadmin` فعلاً بی‌اثره (چون `EnsureSuperAdmin` هیچ‌وقت `CurrentSalon::set()` صدا نمی‌زنه)، ولی خودِ کوئری واقعاً غلط بود — یک تست ریگرسیون مخصوص (با bind دستی `CurrentSalon` به یک سالن نامرتبط) هم اضافه شد.

**وضعیت کلی فاز ۱: ✅✅ کامل — هر ۸ آیتم چک‌لیست انجام شد و کل سوییت (۹۶۶ تست، ۲۱۶۰ assertion، ۱ skip، صفر fail/error) روی PHP 8.3 + SQLite واقعی pass شد؛ regression صفر. پچ‌ها (۳ کامیت شماره‌گذاری‌شده) روی یک clone تازه و مستقل از همون zip با `git am --keep-cr` بدون خطا اعمال و دوباره کل سوییت روی همون clone هم اجرا و pass شد.**

⭐ **قابلیت جدید (۲۰۲۶-۰۹-۰۲): کل تاریخچه‌ی این کار به‌صورت git واقعی بازسازی و به فرمت `.patch` (قابل‌اعمال با `git am`) صادر شد** — ۴ کامیت برنچ `fix/admin-booking-slot-conflict` + ۷ کامیت برنچ `feat/saas-multi-tenant-salons`، هر دو روی یک کپی تازه از zip اصلی ساخته و با `git am` تست شدن (بدون خطا اعمال شدن). اگه نسخه‌ی جدیدتری از پروژه لازم شد patch بشه، همین روش (repo تازه از zip → کامیت‌ها با فایل‌لیست هر کامیت که در این سند مستنده → `git format-patch`) قابل‌تکراره.

<!-- لاگ نشست‌ها؛ هر نشست یک خط اینجا اضافه می‌کنه -->
- ✅✅ (۲۰۲۶-۰۹-۱۸): **این نشست فاز ۱ رو کامل کرد + برای اولین بار محیط PHP/SQLite واقعی ساخته شد.** ابتدا سه فایل تست باگ فوری تداخل نوبت (`AdminBookingSlotConflictTest`/`AdminBookingControllerTest`/`AdminBookingCustomerControllerTest` — کدشون از نشست‌های قبلی از قبل توی zip بود ولی هیچ‌وقت روی PHP واقعی اجرا نشده بود) واقعاً اجرا شدن: ۳۸ تست pass. بعد کل سوییت (۹۴۱ تست) هم اجرا شد: pass. سپس کامیت ۷ (`superadmin:create`) و کامیت ۸ (`SalonManagementTest` + `CreateSuperAdminTest`) پیاده‌سازی شدن — حین نوشتن تست‌های کامیت ۸، یک باگ واقعی در `SuperAdminService` (شمارش متخصص از global scope رد می‌شد، نه فقط از فیلتر دستی) کشف و رفع شد. فاز ۱ SaaS از نظر همه‌ی ۸ آیتم چک‌لیست الان ✅ کامله. پچ‌ها (۳ کامیت شماره‌گذاری‌شده روی یک clone تازه) با `git am --keep-cr` تست شدن؛ کل سوییت روی همون clone هم دوباره اجرا و pass شد: ۹۶۶ تست، ۲۱۶۰ assertion، ۱ skip، صفر fail/error.
- ✅ (۲۰۲۶-۰۹-۱۷): تأیید کامل end-to-end این فاز روی MySQL/MariaDB واقعی (نه فقط SQLite) — migrate:fresh --seed سه بار پشت‌سرهم بدون خطا، لاگین ادمین/سوپرادمین واقعی روی داده‌ی واقعی تست شد. حین این تأیید سه باگ migration مخصوص MySQL/MariaDB (۱۰۶۷/۱۹۰۱/۱۸۳۲) و یک باگ بحرانی «کل صفحه‌ی لاگین ۵۰۰ می‌شد» (route('home') در layouts/guest.blade.php بدون URL::defaults سراسری) پیدا و رفع شد؛ جزئیات کامل در بخش «⭐⭐ نشست بزرگ» بالای فایل، بعد از تاریخچه‌ی باگ‌های نوتیفیکیشن.
- ✅ (۲۰۲۶-۰۹-۰۲): رفع شکاف seederهای CLI (`DatabaseSeeder`/`UserSeeder`/`RoleSeeder`) + یک سوپر ادمین موقت تستی اضافه شد تا `/superadmin` قبل از دستور واقعی artisan قابل‌تست باشه. همچنین کل کار این جلسات (۱۱ کامیت، دو برنچ) به‌صورت فایل‌های `.patch` واقعی (با `git format-patch`) صادر و با `git am` تست شد.
- ✅ (۲۰۲۶-۰۸-۳۰): کامیت ۵+۶ — `SuperAdminService`/`SuperAdminController` (ساخت سالن+ادمین در یک تراکنش، تمدید اشتراک با منطق max(now, ends_at)، تعلیق با محافظت سالن پیش‌فرض، رد کاهش سقف زیر تعداد فعلی) + لایوت و ۴ ویوی پنل سوپر ادمین (تم تیره+طلایی). چک‌باکس‌های دسترسی ماژولار فقط ذخیره می‌شن، اعمال واقعی‌شون هنوز کار بعدیه.
- ✅ (۲۰۲۶-۰۸-۳۰): کامیت ۴b-۳ — `specialistprofile.php`/`reviews.php` تفکیک شدن (`public-specialists.php` جدید). حین کار، شکاف دیگه‌ای هم کشف شد: داشبورد خودِ متخصص هیچ‌وقت `CurrentSalon` نداشت (میدل‌ور `salon.specialist` جدید بستش) — `SpecialistWalletService`، دقیقاً مثل `AdminWallet`/`WalletSetting` قبلی، الگوی singleton داشت. با این، روت‌بندی کامل فاز ۱ تمام شد.
- ✅ (۲۰۲۶-۰۸-۳۰): کامیت ۴b-۲ — ۷ فایل authenticated (`profiles`, `services`, `bookings`, `payments`, `loyalty`, `security`, `wallet`) زیر `/s/{slug}` منتقل شدن. قبلش یک شکاف امنیتی واقعی (مشتری سالن A می‌تونست وارد `/s/salon-B` بشه) با میدل‌ور جدید `EnsureCustomerBelongsToSalon` بسته شد. حین کار، یک باگ ازقبل‌موجود هم کشف شد: بخش «PUBLIC» فایل `specialistprofile.php` هیچ‌وقت واقعاً public نبوده (به‌اشتباه داخل گروه auth تودرتو بود) — با ساختار جدید این بالاخره درست شد. `specialistprofile.php` و `reviews.php` چون فایل‌های مخلوط (دو مخاطب متفاوت در یک فایل) هستن، جدا (۴b-۳) گذاشته شدن برای تفکیک درون‌فایلی.
- ✅ (۲۰۲۶-۰۸-۳۰): تکمیل بازطراحی هویت مشتری — فراموشی رمز مشتری (`CustomerPasswordResetController`، با راه‌حل composite-key برای collision جدول `password_reset_tokens`) + رفع `AdminSpecialistService` (تبدیل مشتری موجود به متخصص، `user_type`/`salon_id` را طبق تصمیم کاربر به‌روز می‌کند، فقط اگر مشتریِ همون سالن باشد). بازطراحی هویت مشتری از نظر عملکردی کامل است.
- ✅ (۲۰۲۶-۰۸-۳۰): پیاده‌سازی بازطراحی هویت مشتری — migration + `CustomerRegisteredController`/`CustomerAuthenticatedController` + ۴ ویو + `routes/salon-auth.php`. حین کار، دو محل دیگه‌ی `User::create()` که از قلم افتاده بودن پیدا و فیکس شدن (`AdminUserService`, `AdminBookingCustomerController`)، و مسیر سراسری `/register`+`/login` هم برای سازگاری با مدل جدید اصلاح شدن (جزئیات کامل بالا). بند «فراموشی رمز» طرح عمداً پیاده‌سازی نشد.
- ✅ (۲۰۲۶-۰۸-۳۰): کامیت ۴a — `routes/super-admin.php` ساخته شد (طبق قرارداد موجود پروژه، بدون تکرار prefix/name داخل فایل — همون الگوی `routes/admin/dashboard.php`؛ یک باگ double-prefix که خودم موقع نوشتن ساختم همون‌جا پیدا و رفع شد) + `salon.active` به گروه `/admin` موجود در `routes/web.php` اضافه شد.
- ✅ (۲۰۲۶-۰۸-۳۰): کامیت ۴b-۱ — `web/public.php` (تنها فایل کاملاً guest) زیر `/s/{salon_slug}` منتقل شد؛ با `URL::defaults()` روی `ResolveSalonFromRoute`، هیچ‌کدوم از ۵۲ فایل ارجاع‌دهنده نیاز به تغییر نداشتن. باقی ۱۰ فایل (۱۷۱ روت) قفل شدن، چون همه پشت `auth` هستن و دقیقاً به بازطراحی هویت مشتری (بخش زیر) گره خوردن — تصمیم گرفته شد جدا مهاجرت نشن تا اون طرح تموم بشه.
- ✅ (۲۰۲۶-۰۸-۳۰): طرح بازطراحی هویت مشتری کامل نوشته و تأیید شد — `users.salon_id` (nullable)، دو قید یکتای جدا (ادمین/متخصص سراسری، مشتری per-salon)، مسیر جدا `/s/{slug}/register`+`/s/{slug}/login` بدون fallback سراسری، فراموشی رمز و نوتیفیکیشن ثبت‌نام هم salon-aware می‌شن. آماده‌ی پیاده‌سازی.
- ✅ (۲۰۲۶-۰۸-۳۰): کامیت ۳ (Middleware) — هر سه middleware ساخته و alias هاشون ثبت شد. حین کار، یک حفره‌ی امنیتی حیاتی کشف شد: `User::hasPermission()` به‌خاطر bypass داخلی‌اش (`is_admin=true` → همیشه true) برای چک سوپر ادمین قابل‌اعتماد نبود؛ طرح از `hasPermission('super_admin')` به `hasRole('super-admin')` تغییر کرد (که این bypass رو نداره). همچنین `ResolveSalonFromRoute` از ۴۰۳ به ۴۰۴ تغییر کرد (دلیل امنیتی: عدم افشای وجود/عدم‌وجود slug به بازدیدکننده‌ی بیرونی) و کلید خطای session از `'email'` (که اصلاً روی users وجود نداره) به `'phone'` تصحیح شد.
- ✅ (۲۰۲۶-۰۸-۲۹): کامیت ۱ (دیتابیس) — ۴ migration ساخته شد. حین کار، دو جدول singleton (`admin_wallet`, `wallet_settings`) که در طرح اولیه نبودن کشف و به لیست `salon_id` اضافه شدن (وگرنه کمیسیون همه‌ی سالن‌ها در یک ردیف مشترک جمع می‌شد).
- ✅ (۲۰۲۶-۰۸-۲۹): کامیت ۲ (ایزولاسیون) — `BelongsToSalon` + `CurrentSalon` ساخته و روی هر ۱۲ مدل اعمال شد. حین کار، یک مشکل سیستمیک کشف شد: تقریباً همه‌ی تست‌های موجود پروژه (مستقیم فکتوری/سرویس، نه HTTP) با قید NOT NULL شکست می‌خوردن؛ با bind کردن یک سالن پیش‌فرض در `Tests\TestCase::setUp()` رفع شد. شکاف باقی‌مانده: seederهای CLI هنوز با همین مشکل روبه‌رو می‌شن (مستند شد، رفع نشد).

### ✅ معیار پایان فاز ۱ (Definition of Done)
تمام ۸ آیتم بالا ✅ + `SalonManagementTest` کامل سبز + تست‌های موجود پروژه همچنان سبز (رگرسیون صفر) — **این معیار الان برقراره (۲۰۲۶-۰۹-۱۸، تأیید شده با اجرای واقعی روی PHP 8.3 + SQLite: ۹۶۶ تست، صفر fail/error، هم روی محیط کاری هم روی یک fresh clone مستقل).** فاز ۱ از نظر چک‌لیست بالا کامله، **ولی** طی استفاده‌ی واقعی ابوالفضل از پنل سوپر ادمین روی XAMPP واقعی، ۹ مورد باگ/کاستی گزارش شد (۲۰۲۶-۰۹-۱۸) که قبل از merge نهایی باید بررسی/رفع شوند — بخش زیر را ببینید.

---

## ✅ باگ‌ها و کاستی‌های گزارش‌شده در پنل سوپر ادمین (ابوالفضل، ۲۰۲۶-۰۹-۱۸ — بعد از استفاده‌ی واقعی روی XAMPP)

> این ۹ مورد ابتدا با خواندن مستقیم کد و تست HTTP واقعی تشخیص/طبقه‌بندی شدند (نشست تشخیص). در نشست بعدی (۲۰۲۶-۰۹-۱۸ب) محیط از صفر ساخته شد (PHP 8.3 + Composer + npm، SQLite برای تست)، baseline روی ۹۶۶ تست تأیید شد، و ۷ مورد رفع و با probe واقعی HTTP + تست رگرسیون دائمی تأیید شدند. پچ‌ها (`git format-patch`، ۵ فایل شماره‌گذاری‌شده) روی یک clone کاملاً مستقل با `git am --keep-cr` بدون خطا اعمال شدند و سوییت کامل (۹۷۶ تست) روی همان clone مستقل هم از صفر (composer install + npm run build) سبز تأیید شد. مورد ۵ بعداً همان‌روز توسط ابوالفضل روی محیط واقعی XAMPP تست و تأیید شد که دیگر رخ نمی‌دهد (بدون نیاز به فیکس کد). **جمعاً ۸ مورد از ۹ مورد بسته‌اند؛ فقط مورد ۹ (مرچنت آیدی مجزا) باز می‌ماند، آن هم عمداً چون به فاز ۲ موکول شده.**

### ۱. نمایش تاریخ «از چه تاریخی فعال» سالن
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب).** ستون «از تاریخ» (`subscription_started_at`، با `jalali_date()`) به هر دو ویو `superadmin/salons/index.blade.php` و `superadmin/dashboard.blade.php` اضافه شد. تست رگرسیون: `SalonDateDisplayTest::test_salons_index_shows_jalali_dates_and_started_at` / `test_dashboard_shows_jalali_dates_and_started_at`.
**فاز: ۱ (پرداخت‌شده، صرفاً UI پنلی که خودِ فاز ۱ ساخته).**

### ۲. نمایش تاریخ‌ها به شمسی با همان تقویم پروژه
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب).** هر دو ویو (`index`, `dashboard`) حالا `jalali_date($date)` را به‌جای `format('Y-m-d')` خام میلادی استفاده می‌کنند — دقیقاً همان تست رگرسیون بالا این را هم پوشش می‌دهد. **نکته‌ی مهم که در همین رفع تأیید شد:** این دو ویو فقط تاریخ *نمایش* می‌دهند (نه ورودی)، پس نیازی به ویجت ورودی jcal (که در این پروژه partial/component قابل‌استفاده‌ی مجدد نیست) نبود — فقط `jalali_date()` کافی بود.
**فاز: ۱.**

### ۳. آمار «سالن‌های فعال» غلط برای سالن‌های منقضی‌شده
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب).** `SuperAdminController::dashboard()` حالا از `$salon->hasActiveSubscription()` استفاده می‌کند به‌جای `is_suspended === false` تنها. تست رگرسیون: `DashboardStatsTest::test_active_salons_excludes_expired_but_unsuspended_salon` — با probe واقعی روی کد قدیم fail بودنش (۴ به‌جای ۳ سالن فعال) تأیید شد.
**فاز: ۱ (باگ در چیزی که خودِ فاز ۱ ساخته).**

### ۴. کادر «نزدیک به انقضا» آمار غلط نشان می‌دهد
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب) — همان علت ریشه‌ای که قبلاً تشخیص داده شده بود.** `diffInDays(now())` با علامت مبهم جایگزین مقایسه‌ی مستقیم تاریخ شد:
```php
'expiring_soon' => $salons->filter(fn ($salon) => $salon->hasActiveSubscription()
    && $salon->subscription_ends_at->lessThanOrEqualTo(now()->addDays(7)))->count(),
```
تست رگرسیون: `DashboardStatsTest::test_expiring_soon_only_counts_salons_within_seven_days` — با probe واقعی روی کد قدیم fail بودنش (هر ۴ سالن غیرمنقضی، حتی با ۶۰+ روز باقی‌مانده، به اشتباه «نزدیک به انقضا» شمرده می‌شدند) تأیید شد.
**فاز: ۱ (باگ در چیزی که خودِ فاز ۱ ساخته).**

### ۵. خروج سوپر ادمین با خطای ۴۰۴
**وضعیت: ✅ تأیید شد رفع است (۲۰۲۶-۰۹-۱۸ب، تأیید ابوالفضل روی محیط واقعی XAMPP).** با تست HTTP مستقیم هم از قبل قابل بازتولید نبود (فرم خروج و روت `logout` دقیقاً `302 → /login` برمی‌گرداندند). ابوالفضل اکنون تأیید کرد که خروج سوپر ادمین به‌درستی به `http://127.0.0.1:8000/login` هدایت می‌شود — یعنی ۴۰۴ گزارش‌شده‌ی اولیه به‌احتمال زیاد ناشی از یک وضعیت گذرای محیط (کش مرورگر، یک vhost/APP_URL موقتاً ناسازگار، یا نسخه‌ی قدیمی‌تر کد پیش از فیکس‌های این نشست) بوده، نه یک باگ کد پایدار. نیازی به فیکس کد نبود.
**فاز: ۱ — بسته شد، بدون نیاز به تغییر کد.**

### ۶. لاگین سوپر ادمین به پنل ادمین سالن هدایت می‌شود
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب).** `AuthenticatedSessionController::verify()` حالا مستقیم `redirect()->to($this->redirectPath())` می‌زند به‌جای `redirect()->intended($this->redirectPath())` — چون این پروژه سیستم ریدایرکت مبتنی‌بر-نقش دارد که با معنای intended در تناقض بنیادی است. تست رگرسیون: `AuthenticationTest::test_super_admin_login_ignores_stashed_intended_url_and_goes_to_superadmin_panel` — با probe واقعی (سوپر ادمین ابتدا `/admin/dashboard` را باز می‌کند، بعد لاگین کامل می‌کند و قبلاً به `/admin/dashboard` نه `/superadmin/dashboard` می‌رفت) تأیید شد.
**فاز: ۱ (باگ در چیزی که خودِ فاز ۱ - در واقع پیش از فاز ۱ - ساخته، ولی روی مسیر ورود سوپر ادمین اثر مستقیم دارد).**

### ۷. نقش سوپر ادمین نباید در پنل ادمین قابل مدیریت/مشاهده باشد
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب).** مسیرهای escalation واقعی از قبل بسته بودند (بدون تغییر). نشتی اطلاعاتی رفع شد: `AdminRoleController::index()` حالا نقش super-admin را برای غیر-سوپر-ادمین‌ها با `where('name', '!=', 'super-admin')` فیلتر می‌کند؛ `show()` هم حالا `guardSuperRole()` دارد (تنها متد این کنترلر که این چک را نداشت). تست‌های رگرسیون: `AdminRoleTest::test_regular_admin_cannot_see_super_admin_role_in_index_or_show` / `test_super_admin_can_still_see_the_super_admin_role`.
**فاز: ۱ (سخت‌کاری امنیتی روی چیزی که خودِ فاز ۱ ساخته).**

### ۸. سقف تعداد متخصص هیچ‌جا واقعاً اعمال نمی‌شود
**وضعیت: ✅ رفع شد (۲۰۲۶-۰۹-۱۸ب) — طبق فیکس پیشنهادی خودِ گزارش باگ.** `App\Exceptions\SpecialistQuotaExceededException` اضافه شد (هم‌الگو با `BookingNotAvailableException` موجود: `DomainException`، پیام کاربرپسند فارسی، `context()` برای log). چک سقف در `AdminSpecialistService::create()` قرار گرفت (نه کنترلر، طبق الگوی همیشگی پروژه)، قبل از `Specialist::create()`: اگر `Specialist::count() >= $salon->max_specialists_count` (که به‌خاطر `BelongsToSalon` خودکار به سالن جاری scope شده، نیازی به `withoutGlobalScope` نبود). `AdminSpecialistController::store()` هم این exception را جدا از `catch(\Exception)` عمومی می‌گیرد تا پیام واقعی («سقف تعداد متخصصین تکمیل شده») به کاربر نشان داده شود. تست‌های رگرسیون: `AdminSpecialistQuotaTest::test_store_is_rejected_once_salon_quota_is_reached` / `test_store_succeeds_while_under_salon_quota` / `test_a_zero_quota_blocks_every_new_specialist` (سقف ۰ یعنی هیچ متخصصی مجاز نیست، طبق کامنت migration `default(0); // 0 = هیچ`).
**فاز: ۱ — مهم‌ترین مورد از نظر کارکردی/بیزینسی چون سقف متخصص یکی از ویژگی‌های اصلیِ تعریف‌شده‌ی خودِ فاز ۱ است.**

### ۹. مرچنت آیدی مجزا برای هر سالن (زرین‌پال)
**وضعیت: ✅ پیاده شد (۲۰۲۶-۰۹-۱۹، همراه با شروع فاز ۲).** ستون `zarinpal_merchant_id` (nullable) روی `salons` اضافه شد. `PaymentService::resolveMerchantId()` حالا اول `CurrentSalon`→`zarinpal_merchant_id` را می‌خواند و فقط در نبود آن (سالن تازه‌ساخته که هنوز خودش را ثبت نکرده) به `config('services.zarinpal.merchant_id')` سراسری fallback می‌کند. سوپر ادمین می‌تواند این مقدار را از فرم ویرایش سالن تنظیم/پاک کند.
⚠️ **`SecurePaymentService` عمداً دست‌نخورده ماند** — بررسی کد نشان داد این سرویس اصلاً هیچ‌وقت به زرین‌پال وصل نمی‌شود (فقط یک blob رمزنگاری‌شده‌ی داخلی برای فلوی ۲FA است)، پس merchant_id اصلاً به آن مربوط نیست؛ نوشته‌ی قبلی این بخش (که رفرنس به بازنویسی SecurePaymentService داشت) بر این اساس نادرست بود.
⚠️ این resolve فقط برای `PaymentService` (پول مشتری→سالن، پیش‌پرداخت نوبت/شارژ کیف‌پول) است. `SubscriptionPaymentService` جدید (خرید/تمدید اشتراک، پول سالن→پلتفرم) عمداً همیشه merchant_id سراسری را می‌خواند و هرگز از CurrentSalon تبعیت نمی‌کند — به بخش «🗂️ فاز ۲ از ۲» زیر نگاه کن.

### 📋 باقی‌مانده برای نشست بعدی
تمام موارد این لیست (۱ تا ۸) رفع/بسته شدند. تنها مورد ۹ (مرچنت آیدی مجزا) باز می‌ماند — و آن هم عمداً، چون به فاز ۲ موکول شده و در جلسات فاز ۱ نباید پیاده‌سازی شود.


---

## 🗂️ فاز ۲ از ۲ — Billing + Multi-Admin + Onboarding (بعد از merge کامل فاز ۱)

**Branch:** `feat/saas-billing-and-onboarding` (بعد از merge شدن فاز ۱ به شاخه‌ی اصلی)
**پیش‌نیاز شروع:** معیار پایان فاز ۱ بالا برقرار باشه؛ ساختار `salon_id`/سالن پیش‌فرض دست‌نخورده بمونه.

سه محور مستقل — می‌تونن جدا یا به ترتیب زیر پیش برن:

### ۱. پرداخت آنلاین و صورتحساب — ✅ تکمیل شد (۲۰۲۶-۰۹-۱۹)
- ✅ اتصال درگاه زرین‌پال برای خرید/تمدید اشتراک — `SubscriptionPaymentService` + `AdminBillingController` (خرید توسط خودِ ادمین سالن؛ مسیر دستی سوپر ادمین هم به‌عنوان مسیر موازی باقی ماند، نه حذف کامل چون برای توافق‌های آفلاین/تخفیف دستی هنوز لازم است)
- ✅ کال‌بک تأیید پرداخت (GET، نه webhook جدا — زرین‌پال از همان الگوی callback_url موجود در PaymentService استفاده می‌کند) → به‌روزرسانی خودکار `subscription_ends_at` از طریق `InvoiceService::markPaidFromGateway()`
- ✅ جدول `invoices` (سالن، دوره [period_start/period_end]، مبلغ، وضعیت، روش پرداخت online/manual) + تاریخچه در پنل ادمین سالن (`admin/billing`) و سوپر ادمین (`superadmin/salons/{salon}/invoices`)
- ⭐ تصمیم بیزنسی مهم تأییدشده حین کار: `EnsureAdminSalonActive` قبلاً سالن *منقضی‌شده* را دقیقاً مثل سالن *suspend‌شده* کامل logout می‌کرد — که خودِ فیچر تمدید آنلاین را برای دقیقاً همان حالتی که بیشتر لازمش دارد (سالن منقضی) غیرقابل‌استفاده می‌کرد. رفع شد: انقضای تاریخ فقط دسترسی به `admin.billing.*` را باز نگه می‌دارد (بدون logout)؛ suspend دستی دقیقاً مثل قبل کامل logout می‌کند.
- ⭐ قیمت‌گذاری اشتراک (`config/billing.php`) placeholder است — هیچ منبع قیمت واقعی در پروژه موجود نبود؛ باید قبل از production با `SUBSCRIPTION_PRICE_1M/3M/6M/12M` در `.env` جایگزین شود.

### ۲. چند ادمین برای یک سالن — ✅ تکمیل شد (۲۰۲۶-۰۹-۱۹)
- جدول pivot `salon_admins` از فاز ۱ همین الان آماده‌ست؛ فاز ۲ فقط محدودیت «حداکثر ۱ ردیف per سالن» رو در سطح اپلیکیشن (نه دیتابیس) برمی‌داره و UI افزودن نفر دوم رو می‌سازه
- سطوح دسترسی داخلی سالن با ستون `salon_admins.role` (`owner` در برابر `staff`/منشی) — منشی می‌تونه دسترسی محدودتر (مثلاً فقط ثبت نوبت دستی، بدون مالی/کیف‌پول) داشته باشه؛ بدون تغییر مدل `super_admin` سراسری
- جزئیات کامل پیاده‌سازی (شامل دو باگ واقعی کشف‌شده — نشتی بین‌سالنی `AdminUserController` و عدم اتصال ادمین تازه‌ساز به `salon_admins`) در بخش «⭐⭐ نشست (۲۰۲۶-۰۹-۱۹): محور «۲. چند ادمین برای یک سالن»» در پایین همین فایل.

### ۳. ساب‌دامین اختصاصی — 🟡 شروع شد (۲۰۲۶-۰۹-۱۹)
- Middleware تشخیص تننت از ساب‌دامین، با fallback به `/s/{slug}` فعلی (بدون شکستن فاز ۱)
- نیازمند Wildcard DNS روی سرور — خارج از کنترل کد؛ باید از قبل با کاربر هماهنگ بشه
- **راهنمای کامل DNS/وب‌سرور/SSL (هم لوکال هم production واقعی) در فایل جداگانه‌ی
  `docs/WILDCARD_SUBDOMAIN_DEPLOYMENT.md` مستند شده — این فایل باید کنار
  `Rasta_unified_prompt.md` نگه‌داری و هر بار همراه zip آپلود بشه تا قابل‌خوندن بمونه.**
- تصمیم گرفته‌شده (۲۰۲۶-۰۹-۱۹): چون فعلاً فقط روی لوکال XAMPP کار می‌کنیم (نه هنوز دامنه/سرور
  واقعی)، تست محلی محور ۳ با سرویس رایگان **nip.io** انجام می‌شه (مثلاً
  `rasta.127.0.0.1.nip.io`) — نیازی به تنظیم `hosts` یا DNS واقعی روی ویندوز نیست.
- ✅ دو تصمیم بیزنسی که قبل از شروع کدنویسی باز بودن، در همین نشست با ابوالفضل حل شدن:
  ۱) `SESSION_DOMAIN` **ایزوله** برای هر ساب‌دامین (نه مشترک) — مقدار پیش‌فرض فعلی پروژه
  (`SESSION_DOMAIN=null` در `.env`) همین رفتار رو می‌ده، پس هیچ تغییر کدی لازم نبود.
  ۲) دامنه‌ی اصلی بدون ساب‌دامین فعلاً فقط یک **صفحه‌ی placeholder ساده** نشون می‌ده (نه یک
  لندینگ کامل) — لندینگ واقعی با محور «۴. ثبت‌نام عمومی سالن» میاد، وقتی واقعاً یک فرم/CTA
  برای لینک‌کردن بهش وجود داره.
- ✅ `/s/{slug}` و ساب‌دامین **هم‌زمان زنده‌ن** (نه یکی جای اون یکی) — طبق تصمیم مستندشده در
  `docs/WILDCARD_SUBDOMAIN_DEPLOYMENT.md` («لینک قدیمی نباید بشکنه») و تأیید صریح ابوالفضل.
  ریسک شناخته‌شده‌ی این تصمیم (قطعی‌شدن سشن هنگام navigate بین دو دامنه، چون SESSION_DOMAIN
  ایزوله است) صراحتاً پذیرفته شده — جزئیات در بخش «ادامه‌ی سوم» پایین همین فایل.
- جزئیات کامل پیاده‌سازی (طراحی config-toggle سازگار با `route:cache`، ماجرای تست‌نویسی، و
  تصحیح تصمیم fallback) در بخش‌های «⭐⭐ نشست (۲۰۲۶-۰۹-۱۹، ادامه‌ی دوم)» و «ادامه‌ی سوم» در
  پایین همین فایل.

### ۴. ثبت‌نام عمومی سالن (self-service)
- فرم عمومی ایجاد سالن توسط صاحب کسب‌وکار، بدون دخالت اولیه‌ی سوپر ادمین
- جریان تأیید (ایمیل/OTP) + وضعیت `pending_approval` قبل از فعال‌سازی
- تأیید/رد نهایی توسط سوپر ادمین

### کامیت‌های پیشنهادی فاز ۲
1. `feat(payments): online gateway integration for subscription purchase/renewal (Zarinpal)`
2. `feat(billing): invoice generation + subscription history per salon`
3. `feat(admin): multi-admin per salon (roles/permissions within a tenant)`
4. `feat(routing): subdomain resolution (wildcard) alongside /s/{slug} fallback`
5. `feat(onboarding): public salon signup form + verification flow`
6. `test(billing+onboarding): payment webhook, invoice, multi-admin permission, subdomain resolution`

**وضعیت کلی فاز ۲: 🟡 در حال انجام (۲۰۲۶-۰۹-۱۹) — محور «۱. پرداخت آنلاین و صورتحساب» + مورد ۹ + محور «۲. چند ادمین برای یک سالن» تکمیل شدند؛ محور «۳. ساب‌دامین» شروع شد (routing + fallback هم‌زمان کار می‌کنن، production cutover هنوز نه)؛ محور «۴. ثبت‌نام عمومی سالن» هنوز شروع نشده. ⚠️ حین تست محور ۳، چند باگ نشتی داده‌ی بین‌سالنی (برندینگ هاردکد، کش HomeController، و مهم‌تر از همه یک آسیب‌پذیری امنیتی واقعی در implicit route-model-binding) پیدا و بخشی فیکس شدن — یک ممیزی گسترده‌تر روی کل پنل ادمین هنوز باز و اولویت اول نشست بعدیه. به بخش‌های «⭐⭐ نشست (۲۰۲۶-۰۹-۱۹)» در پایین همین فایل نگاه کن.**
---

## \U0001F6A8 باگ فوری (مستقل از فازهای SaaS): تداخل نوبت دستی ادمین با نوبت آنلاین

> **وضعیت: \U0001F534 فوری — تأییدشده، آماده‌ی رفع.** این باگ روی **کدبیس فعلی راستا** (قبل و مستقل از SaaS چندسالنی) وجود دارد و باید زودتر از فاز ۱ SaaS رفع شود. تصمیمات زیر در گفتگوی ۲۰۲۶-۰۸-۲۸ تأیید شدند.

### ریشه‌ی باگ (تأییدشده با خواندن کد واقعی)
مسیر آنلاین (`BookingService::createBooking()`) قبل از ساخت نوبت، `Specialist::getAvailableSlots()` را چک می‌کند و اگر ساعت پر باشد `BookingNotAvailableException` می‌اندازد. اما `AdminBookingController::store()` این‌طور است:
```php
public function store(StoreAdminBookingRequest $request): RedirectResponse
{
    $booking = Booking::create($request->validated());   // هیچ چک تداخلی ندارد
    ...
}
```
نتیجه: اگر ادمین/منشی یک نوبت تلفنی یا حضوری را دستی ثبت کند، هیچ‌چیز جلوی تداخل با یک نوبت آنلاینِ همان ساعت را نمی‌گیرد (و برعکس).

### طرح رفع (۴ بخش، هر چهار مورد تأییدشده)

**۱) اشتراک منطق چک اسلات بین مسیر آنلاین و دستی**
منطق مشترک به `BookingService` منتقل/اضافه می‌شود (نه تکرار در `AdminBookingService`):
```php
// App\Services\Booking\BookingService

/**
 * @throws BookingNotAvailableException
 */
public function createManualBooking(array $data): Booking
{
    $specialist = $this->specialist->findOrFail($data['specialist_id']);
    $service    = $this->beautyService->findOrFail($data['service_id']);
    $bookingDate     = date('Y-m-d', strtotime($data['booking_time']));
    $bookingTimeOnly = date('H:i', strtotime($data['booking_time']));

    $availableSlots = $specialist->getAvailableSlots($bookingDate, $service->duration_minutes ?? null);

    if (! in_array($bookingTimeOnly, $availableSlots)) {
        throw BookingNotAvailableException::slotTaken(
            "Slot {$data['booking_time']} is not available for specialist {$data['specialist_id']}.",
            $data
        );
    }

    return DB::transaction(function () use ($data) {
        try {
            return Booking::create([
                'service_id'     => $data['service_id'],
                'specialist_id'  => $data['specialist_id'],
                'user_id'        => $data['user_id'],
                'booking_time'   => $data['booking_time'],
                'status'         => $data['status'],
                'payment_status' => $data['payment_status'],
                'source'         => $data['source'],   // 'phone' | 'walk_in'
                'notes'          => $data['notes'] ?? null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDuplicateActiveSlot($e)) {
                throw BookingNotAvailableException::slotTaken(
                    'Race condition: slot taken concurrently.', $data
                );
            }
            throw $e;
        }
    });
}
```
`AdminBookingController::store()` این متد را صدا می‌زند و `BookingNotAvailableException` را می‌گیرد:
```php
try {
    $booking = $this->bookingService->createManualBooking($request->validated());
} catch (BookingNotAvailableException $e) {
    return back()->withInput()
        ->with('error', 'این ساعت برای این متخصص قبلاً رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.');
}
```

**۲) قید سخت‌گیر سطح دیتابیس (تصمیم تأییدشده: علاوه‌بر چک نرم‌افزاری)**
چون بین چک و درج، دو درخواست هم‌زمان (یکی آنلاین، یکی از فرم ادمین) هنوز می‌توانند هر دو از چک عبور کنند، یک **generated column + unique index** (سازگار با MySQL، چون partial unique index مثل Postgres را ندارد) اضافه می‌شود:
```php
Schema::table('bookings', function (Blueprint $table) {
    $table->string('active_slot_key', 191)
        ->storedAs("CASE WHEN status <> 'cancelled' THEN CONCAT(specialist_id, '_', booking_time) ELSE NULL END")
        ->nullable();
    $table->unique('active_slot_key', 'bookings_active_slot_unique');
});
```
در MySQL مقادیر `NULL` در ایندکس یکتا تکراری محسوب نمی‌شوند، پس نوبت‌های لغوشده مانع نمی‌شوند؛ فقط دو نوبت *فعال* روی دقیقاً همان `specialist_id`+`booking_time` رد می‌خورند. خطای احتمالی `QueryException` (کد ۱۰۶۲) در `createManualBooking()` بالا گرفته و به همان پیام فارسی کاربرپسند تبدیل می‌شود — نه یک خطای خام SQL.

**۳) ستون `source` (فقط برای گزارش‌گیری، تأثیری روی منطق تداخل ندارد)**
```php
Schema::table('bookings', function (Blueprint $table) {
    $table->enum('source', ['online', 'phone', 'walk_in'])->default('online')->after('status');
});
```
Backfill: تمام رکوردهای موجود `source = 'online'` (چون قبل از این فیکس فقط مسیر آنلاین وجود داشت).

**۴) جستجو/ساخت سریع مشتری بدون حساب کاربری (تصمیم تأییدشده)**
چون `bookings.user_id` اجباری و به `users` وصل است، فرم `admin/bookings/create.blade.php` یک ویجت جستجو اضافه می‌کند (نه `User::all()` کامل که الان هست):
- `GET /admin/customers/search?phone=...` → جستجوی `User::where('phone', 'like', ...)`, حداکثر ۵ نتیجه
- اگر پیدا نشد: فرم مینیمال «ساخت سریع مشتری» (نام + موبایل) همان‌جا، بدون خروج از صفحه‌ی ثبت نوبت — `POST /admin/customers/quick-create` (AJAX)، پاسخ `{id, name, phone}` که `user_id` مخفی فرم نوبت را پر می‌کند.

### Branch و کامیت‌ها
`fix/admin-booking-slot-conflict` (مستقل، merge می‌شود **قبل از** شروع فاز ۱ SaaS):
1. ✅ `fix(bookings): add source column + active_slot_key generated unique index` — پیاده‌سازی شده، **driver-aware** (نه فقط MySQL): چون تست‌های پروژه روی SQLite اجرا می‌شن (`phpunit.xml`: `DB_CONNECTION=sqlite`) نه MySQL production، migration بر اساس `Schema::getConnection()->getDriverName()` سه حالت رو جدا مدیریت می‌کنه (mysql/sqlite/pgsql). قبل از نوشتن، هر دو نحو مستقیماً با یک اسکریپت پایتون روی SQLite واقعی تست شد.
2. ✅ `fix(bookings): BookingService::createManualBooking() with shared availability check` — پیاده‌سازی شده.
3. ✅ `fix(admin): AdminBookingController uses createManualBooking(), friendly conflict error` — پیاده‌سازی شده.
4. ✅ `feat(admin): quick customer search/create widget for manual bookings` — پیاده‌سازی شده (`AdminBookingCustomerController` + ویجت جستجو در `create.blade.php`).
5. ✅ `fix(bookings): same conflict-check + paid-lock + reschedule-SMS extended to AdminBookingController::update() (updateFull)` — کامیت اضافه‌شده بعد از کشف اینکه دقیقاً همون باگ تداخل، مسیر ویرایش رو هم داشت. شامل: قابل‌تغییرنبودن مشتری روی نوبت پرداخت‌شده (`UpdateAdminBookingRequest::withValidator`)، و پیامک `BookingRescheduledNotification` به مشتری (نه متخصص — رفع ضمنی یک ناهماهنگی گیرنده‌ی از‌قبل‌موجود در `BookingRescheduleController`، بدون دست‌زدن به خودِ آن کنترلر که خارج از دامنه‌ی این برنچه) وقتی ادمین از فرم ویرایش ساعت/متخصص رو عوض کنه.
6. ✅ `test(bookings): manual-vs-online conflict, self-exclusion on edit, race condition, quick-customer flow, paid-lock, reschedule notification` — پیاده‌سازی شده در سه فایل: `AdminBookingSlotConflictTest.php` (سطح سرویس + قید دیتابیس)، `AdminBookingControllerTest.php` (بازنویسی‌شده — سه تست قبلی که ناخواسته به همین باگ متکی بودن هم اصلاح شدن)، `AdminBookingCustomerControllerTest.php` (ویجت جستجو/ساخت سریع).

**وضعیت کلی: ✅✅ کامل + اجرای واقعی تأیید شد (۲۰۲۶-۰۹-۱۸).** این نشست برای اولین بار یک محیط PHP 8.3 + SQLite واقعی (نه فقط sandbox بدون PHP) ساخته شد (نصب PHP/Composer داخل sandbox، دور زدن چند مشکل base-config: مقادیر فارسی جایگزین‌شده در `.env.example` که parser محیط رو می‌شکست، پوشه‌های `storage/framework/*` که در zip نبودن، `APP_KEY` خالی). هر سه فایل تست این بخش (`AdminBookingSlotConflictTest`, `AdminBookingControllerTest`, `AdminBookingCustomerControllerTest`) واقعاً اجرا شدن: **۳۸ تست، ۹۰ assertion، صفر fail.** کل سوییت پروژه هم قبل از ادامه‌ی کار فاز SaaS اجرا شد: **۹۴۱ تست، ۲۰۸۳ assertion، ۱ skip، صفر fail/error.**

### تست‌های الزامی
- ✅ ثبت نوبت دستی روی ساعتی که آنلاین قبلاً گرفته شده → رد می‌شود، پیام فارسی مناسب.
- ✅ ثبت نوبت آنلاین/دستی روی ساعتی که قبلاً دستی (تلفنی/حضوری) ثبت شده → رد می‌شود.
- ✅ قید دیتابیسی مستقیماً (بدون عبور از سرویس) تست شد — هم رد دو نوبت فعال هم‌زمان، هم مجاز بودن نوبت لغوشده در همون اسلات.
- ✅ ویرایش نوبت به همون ساعت خودش (بدون تغییر واقعی) خودش‌رو مسدود نمی‌کنه (self-exclusion).
- ✅ ویرایش نوبت به ساعت/متخصصی که نوبت دیگری اونجاست → رد می‌شود.
- ✅ تغییر ساعت/متخصص از فرم ویرایش → پیامک به مشتری می‌ره؛ بدون تغییر واقعی → پیامکی نمی‌ره.
- ✅ تغییر مشتری روی نوبت پرداخت‌شده رد می‌شود؛ روی نوبت پرداخت‌نشده مجازه.
- ✅ جستجوی مشتری با موبایل/نام و ساخت سریع، هر دو مسیر (موجود/جدید) + رد شماره تکراری/نامعتبر.

⚠️ این فیکس **پیش‌نیاز فاز ۲ SaaS («چند ادمین روی یک سالن»)** هم هست — چون منشی/ادمین دومی که فاز ۲ اضافه می‌کند دقیقاً از همین فرم نوبت دستی استفاده می‌کند؛ اگر این باگ رفع نشود، اضافه‌کردن منشی فقط تعداد نفراتی که می‌توانند تداخل ایجاد کنند را زیاد می‌کند.

⚠️ **خارج از دامنه‌ی این برنچ (مستند شده، عمداً دست‌نخورده)**:
- ویجت جستجوی مشتری روی `admin/bookings/edit.blade.php` وصل نشد (فقط قفل UI ساده‌ی «نوبت پرداخت‌شده» اضافه شد) — چون تغییر مشتری روی نوبت موجود سناریوی نادرتر و پرریسک‌تریه.
- ناهماهنگی گیرنده‌ی اعلان در خودِ `BookingRescheduleController` (مسیر self-service مشتری) — می‌فرسته به `$booking->specialist` درحالی‌که event key و متن گیت صراحتاً «اطلاع به مشتری» می‌گه؛ در مسیر جدید ادمین درست پیاده‌سازی شد، ولی مسیر قدیمی خودش دست‌نخورده موند.

## ⭐ نشست (۲۰۲۶-۰۹-۱۸): رفع دو باگ مستقل گزارش‌شده توسط ابوالفضل — ریدایرکت اشتباه سوپر ادمین + پاک‌شدن ناخواسته‌ی نقش‌ها

این نشست مستقل از فاز SaaS و باگ تداخل نوبت (بخش‌های بالا) است. ابوالفضل دو نگرانی مطرح کرد:
۱) وقتی کاربر با نقش سوپر ادمین لاگین می‌کند، به‌جای `/superadmin/` وارد `/admin/dashboard` می‌شود.
۲) امکان اینکه یک ادمین معمولی از طریق مدیریت نقش‌ها/کاربران به خودش نقش سوپر ادمین بدهد.

### بررسی و یافته‌ها
- **مورد ۱ (تأیید شد، ریشه‌یابی شد)**: `AuthenticatedSessionController::redirectPath()` از قبل درست بود (اول `hasRole('super-admin')` چک می‌شود). باگ واقعی در `App\Http\Middleware\RedirectIfAuthenticated` (میدل‌ور `guest` روی `/login`) بود: چک `hasRole('super-admin')` را نداشت و مستقیم به چک `is_admin` می‌رسید؛ چون سوپر ادمین هم `is_admin=true` دارد، هر کاربر سوپر ادمین *از‌قبل لاگین‌شده*‌ای که دوباره `/login` را باز کند به `RouteServiceProvider::HOME` ('/admin/dashboard') فرستاده می‌شد.
- **مورد ۲ (بررسی شد — از قبل درست محافظت‌شده)**: `AdminRoleController::guardSuperRole()` مدیریت نقش super-admin را برای غیر سوپر ادمین می‌بندد؛ `AdminUserService::filterAssignableRoles()` هم در create/update شناسه‌ی نقش super-admin را حذف می‌کند مگر کاربر لاگین‌شده خودش سوپر ادمین باشد. **هیچ فیکسی برای این مورد لازم نبود.**
- **باگ جانبی کشف‌شده حین بررسی مورد ۲**: `AdminUserService::syncRoles(User $user, array $roles)` به متغیر تعریف‌نشده `$data['roles']` رجوع می‌کرد (نه پارامتر `$roles`) — نتیجه: اندپوینت `POST /admin/users/{user}/roles` همیشه تمام نقش‌های کاربر را پاک می‌کرد، صرف‌نظر از ورودی. این باگ از قبل توسط تست موجود `test_admin_can_sync_roles_independently` پوشش داده می‌شد و روی کد پایه fail می‌شد (تأیید شده با اجرای واقعی قبل از فیکس).

### فیکس‌ها (۲ کامیت مستقل، روی برنچ جدا از کار فاز SaaS)
1. `RedirectIfAuthenticated`: چک `hasRole('super-admin')` قبل از چک specialist/is_admin اضافه شد (هم‌راستا با `redirectPath()`). تست ریگرسیون جدید: `AuthenticationTest::test_already_authenticated_super_admin_visiting_login_is_sent_to_superadmin_panel` — با یک HTTP probe واقعی روی کد قدیم fail بودنش تأیید شد، بعد از فیکس pass شد.
2. `AdminUserService::syncRoles()`: ارجاع به `$data['roles']` به `$roles` تصحیح شد. نیازی به تست جدید نبود — تست موجود `test_admin_can_sync_roles_independently` این باگ را از قبل پوشش می‌داد.

### تأیید محیط
محیط PHP 8.3 + Composer + SQLite از صفر ساخته شد (طبق پروتکل). کل سوییت تست روی محیط sandbox واقعاً اجرا شد (نه فقط بررسی دستی کد): **۹۴۰ تست پاس، ۱ skip، صفر fail**. پچ‌ها روی یک `git clone` مستقل و تازه با `git am --keep-cr` بدون خطا اعمال و دوباره روی همون clone تست‌های مرتبط اجرا و pass شدند.

### تحویل
دو پچ شماره‌گذاری‌شده:
- `0001-...-guest-middleware-sends-al.patch`
- `0002-...-AdminUserService-syncRol.patch`

### قدم‌های باز
هیچ‌کدام از این دو فیکس ربطی به فاز SaaS در حال انجام (بخش‌های بالای همین فایل) ندارد و روی برنچ جدایی از آن‌ها اعمال شده؛ هنگام merge نهایی توجه شود که این دو کامیت مستقل، قابل rebase/merge روی هر برنچ پایه‌ای هستند چون فقط دو فایل نامرتبط را لمس می‌کنند (`RedirectIfAuthenticated.php`, `AdminUserService.php`) + یک فایل تست.

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹): شروع فاز ۲ — محور «۱. پرداخت آنلاین و صورتحساب» + مورد ۹

این نشست طبق پروتکل معمول شروع شد: محیط PHP 8.3 + Composer + npm از صفر ساخته شد (apt برای PHP/gd، composer.phar از GitHub releases چون packagist این بار باز بود)، baseline روی **۹۷۶ تست (۹۷۵ پاس + ۱ skip)** تأیید شد — دقیقاً برابر آخرین وضعیت مستندشده.

### درخواست ابوالفضل
از محور «۱. پرداخت آنلاین و صورتحساب» فاز ۲ شروع بشه: هم اتصال واقعی زرین‌پال به خرید/تمدید اشتراک، هم مورد ۹ (مرچنت آیدی مجزا هر سالن) — چون هر دو به همان `PaymentService` مربوط بودند.

### یافته‌های معماری مهم (پیش از نوشتن کد)
1. **`SecurePaymentService` اصلاً به زرین‌پال وصل نیست** — فقط یک blob رمزنگاری‌شده‌ی داخلی برای فلوی ۲FA (تأیید با خواندن مستقیم کد). پس مورد ۹ فقط `PaymentService` را لمس می‌کند؛ نوشته‌ی قدیمی‌تر این بخش که به بازنویسی `SecurePaymentService` هم اشاره داشت، نادرست بود.
2. **مورد ۹ باید فقط روی پرداخت‌های نوبت/کیف‌پول اعمال بشه (مشتری→سالن)، نه پرداخت اشتراک (سالن→پلتفرم)** — چون این دو جهت پول کاملاً برعکس هم‌اند. یک سرویس جدا (`SubscriptionPaymentService`، هم‌الگو با `ZarinpalPayoutService` موجود) ساخته شد که همیشه merchant_id سراسری پلتفرم را می‌خواند و هرگز از `CurrentSalon`/`salons.zarinpal_merchant_id` تبعیت نمی‌کند.
3. **تصمیم بیزنسی (تأییدشده با ابوالفضل)**: `EnsureAdminSalonActive` قبلاً سالن منقضی‌شده را دقیقاً مثل suspend‌شده کامل logout می‌کرد — که فیچر تمدید آنلاین را برای همان حالتی که بیشترین نیاز را دارد (سالن منقضی) غیرقابل‌دسترس می‌کرد. راه‌حل تأییدشده: انقضای تاریخ فقط دسترسی به `admin.billing.*` را باز نگه می‌دارد (ادمین logout نمی‌شود، ولی بقیه‌ی `/admin/*` مسدود و ریدایرکت به صفحه‌ی تمدید می‌شود)؛ suspend دستی سوپر ادمین دقیقاً مثل قبل کامل logout می‌کند (تمدید آنلاین نباید بتواند یک suspend دستی را دور بزند).

### کارهای انجام‌شده
1. `feat(payments): per-salon Zarinpal merchant_id with global fallback (Phase 2, item 9)` — migration `zarinpal_merchant_id` روی `salons`، `PaymentService::resolveMerchantId()`، فرم ویرایش سوپر ادمین.
2. `feat(billing): invoices table + SubscriptionPaymentService + InvoiceService (Phase 2, axis 1)` — جدول `invoices` (BelongsToSalon، هم‌الگو با بقیه‌ی جدول‌های salon-owned)، `config/billing.php` (قیمت‌های placeholder، env-overridable)، دو سرویس جدید.
3. `feat(admin): online subscription purchase/renewal for salon admins (Phase 2, axis 1)` — `AdminBillingController` (index/purchase/callback) + `routes/admin/billing.php` + ویو `admin/billing/index.blade.php` + آیتم نویگیشن. `callback()` قبل از تأیید با زرین‌پال، authority را با رکورد سمت سرور خودِ فاکتور مطابقت می‌دهد (همان الگوی ضدِدستکاری موجود در `PaymentService`) و idempotent است (فاکتور پردازش‌شده دوباره پردازش نمی‌شود).
4. `feat(admin): grace access to billing page for expired (not suspended) salons` — تغییر `EnsureAdminSalonActive` طبق تصمیم بند ۳ بالا.
5. `feat(superadmin): unify manual renewal with the invoices table + invoice history page` — تمدید دستی سوپر ادمین حالا هم در `invoices` ثبت می‌شود (`payment_method='manual'`)؛ صفحه‌ی جدید `superadmin/salons/{salon}/invoices`. **⭐ باگ واقعی کشف/رفع‌شده حین نوشتن این کامیت**: کوئری اولیه‌ی `invoices()` در `SuperAdminController` بدون `withoutGlobalScope('salon')` نوشته شده بود — دقیقاً همان الگوی مستندشده در `SuperAdminService::updateSalon()`/`remainingSpecialistQuota()` (global scope `BelongsToSalon` با `CurrentSalon` تست هارنس AND می‌شود و کوئری همیشه خالی برمی‌گردد). با یک تست واقعی fail-then-pass تأیید و رفع شد.

### تست‌ها
۱۶ تست جدید (Http::fake برای مسیرهای واقعی گیت‌وی، هم مسیر موفق هم ناموفق، هم تفکیک merchant_id پلتفرم/سالن، هم idempotency کال‌بک، هم grace-access میدل‌ور، هم فاکتور دستی سوپر ادمین). کل سوییت نهایی: **۹۹۱ پاس، ۱ skip، صفر fail** — هم روی محیط کاری هم روی یک `git clone` کاملاً مستقل از صفر (composer install + npm install + npm run build + migrate:fresh، بدون هیچ فایل به‌اشتراک‌گذاشته‌شده با محیط اصلی) با `git am --keep-cr` روی ۵ پچ شماره‌گذاری‌شده، بدون خطا.

⚠️ **نکته‌ی محیطی برای نشست بعدی**: تأیید fresh-clone این‌بار به `npm install && npm run build` هم نیاز داشت (نه فقط composer install) — یک بار `php artisan test` روی کلون تازه با ده‌ها fail غیرمرتبط (`ViteManifestNotFoundException`) مواجه شد که ربطی به این پچ‌ها نداشت، فقط چون assets ساخته نشده بودند.

### تحویل
پنج پچ شماره‌گذاری‌شده:
- `0001-feat-payments-per-salon-Zarinpal-merchant_id-with-gl.patch`
- `0002-feat-billing-invoices-table-SubscriptionPaymentServi.patch`
- `0003-feat-admin-online-subscription-purchase-renewal-for-.patch`
- `0004-feat-admin-grace-access-to-billing-page-for-expired-.patch`
- `0005-feat-superadmin-unify-manual-renewal-with-the-invoic.patch`

### قدم‌های باز برای نشست بعدی
- محور «۳. ساب‌دامین اختصاصی» (نیازمند هماهنگی Wildcard DNS با ابوالفضل قبل از شروع)
- محور «۴. ثبت‌نام عمومی سالن (self-service)»
- قیمت‌های واقعی `config/billing.php` باید قبل از production جایگزین placeholder فعلی شوند

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹): محور «۲. چند ادمین برای یک سالن» (فاز ۲ از ۲)

این نشست طبق پروتکل معمول شروع شد: محیط PHP 8.3 + Composer (از GitHub releases، چون packagist این بار بسته بود) + npm install + npm run build از صفر ساخته شد. baseline روی **۹۹۱ تست (۹۹۰ پاس + ۱ skip)** تأیید شد — دقیقاً برابر آخرین وضعیت مستندشده.

### درخواست ابوالفضل
شروع فاز ۲ از محور «۲. چند ادمین برای یک سالن».

### یافته‌های معماری مهم (پیش از نوشتن کد، با خواندن کد واقعی نه فقط این سند)
1. **`AdminUserController::index()` قبل از این نشست یک نشت کامل بین‌سالنی بود** — روی `User::query()` بدون هیچ فیلتری کار می‌کرد (چون `User` عضو ۱۲ مدل صاحب‌داده‌ی `BelongsToSalon` نیست)؛ هر ادمین هر سالنی همه‌ی کاربران همه‌ی سالن‌ها رو می‌دید/ویرایش/حذف می‌کرد.
2. **باگ عملکردی همراه**: `AdminUserService::create()` کاربر جدید رو هیچ‌وقت به `salon_admins` وصل نمی‌کرد — یعنی هر ادمینی که از این فرم ساخته می‌شد، با اولین لاگین بلافاصله توسط `EnsureAdminSalonActive` logout می‌شد (چون `$user->salons()->first()` خالی بود).
3. **`authorize()` این Form Requestها فقط `hasPermission('access_admin_panel')` چک می‌کرد** — یک پرمیشن عمومی که همه‌ی ادمین‌ها دارن، نه یک پرمیشن مخصوص «مدیریت ادمین‌های این سالن»؛ هیچ نقش/پرمیشن «منشی محدود» یا «مالی/کیف‌پول» هم در `RoleSeeder`/`PermissionSeeder` قبلی وجود نداشت.
4. **⭐ کشف حین نوشتن اولین تست واقعی روی نقش «منشی» (نه فرض)**: permissionهای پایه‌ای که نقش «منشی» بهشون نیاز داره (`access_admin_panel`, `view-bookings`, ...) فقط توسط `PermissionSeeder` ساخته می‌شن — یک Seeder، نه migration. `RefreshDatabase` تست‌ها هیچ‌وقت seeder صدا نمی‌زنه (دقیقاً به همین دلیل تست‌های قبلی این پروژه همیشه از `is_admin=true`/bypass استفاده می‌کردن، نه یک permission واقعی). روی یک DB تازه (چه تست، چه یک production که هنوز `db:seed` نشده)، این permissionهای پایه اصلاً وجود نداشتن. فیکس: migration جدید خودش هر permission لازم رو idempotent (`firstOrCreate`) می‌سازه، نه فقط `manage-wallet` رو.

### تصمیمات بیزنسی تأییدشده با ابوالفضل (پیش از پیاده‌سازی)
۱. افزودن ادمین/منشی جدید فقط برای `owner` سالن مجاز است.
۲. `owner` می‌تواند یک `owner` دوم هم بسازد (مثلاً برای شریک کسب‌وکار)، نه فقط `staff`.
۳. محدودیت دسترسی مالی/کیف‌پول برای `staff` با زیرساخت کامل Role/Permission پیاده‌سازی شود (نه یک if ساده).
۴. سالن باید همیشه حداقل یک `owner` فعال داشته باشد — حذف/تنزل‌به‌staff/غیرفعال‌سازی آخرین owner مسدود می‌شود.
۵. صفحه‌ی مدیریت ادمین‌ها (`/admin/users`) فقط برای `owner` نمایش داده شود (نه `staff`) — به‌خاطر ریسک سوءاستفاده از دسترسی.

### کارهای انجام‌شده
1. `feat(admin): salon-scoped admin management + owner-only access (Phase 2, axis 2)` — نشتی بین‌سالنی `AdminUserController::index()` رفع شد (اسکوپ روی `Salon::admins()` وقتی `CurrentSalon` ست باشه؛ سوپر ادمین مستقیم از `/admin` طبق کانونشن قبلی همه‌چیز رو بدون فیلتر می‌بینه). `AdminUserService::create()` حالا کاربر تازه‌ساز رو به `salon_admins` وصل می‌کنه. Middleware جدید `EnsureSalonOwner` (`salon.owner`) کل گروه `admin/users.php` رو owner-only می‌کنه. فیلد جدید `salon_role` (owner/staff) روی Store/UpdateAdminUserRequest. `LastSalonOwnerException` + `AdminUserService::guardNotLastOwner()` برای تصمیم ۴. `authorizeSalonMembership()` جلوی دسترسی owner به کاربر یک سالن دیگه (با حدس id) رو می‌گیره.
2. `feat(billing): manage-wallet permission + staff/finance-access roles (Phase 2, axis 2)` — Permission جدید `manage-wallet` (گیت روی `admin/wallet.php` + `admin/billing.php`)، نقش `staff` (دسترسی محدود: نوبت دستی + مشاهده)، نقش افزودنی `finance-access` (فقط `manage-wallet`، قابل‌ترکیب با `staff` برای یک منشی خاص). Migration idempotent و خودکفا (طبق یافته‌ی ۴ بالا).
3. `feat(admin): owner/staff UI for adding a second admin (Phase 2, axis 2)` — فرم‌های create/edit به رادیوباتن «مالک/منشی» + چک‌باکس «دسترسی مالی» تغییر کردن (فقط وقتی `CurrentSalon` ست باشه؛ مسیر قدیمی سوپر ادمین دست‌نخورده موند). بج «نقش در سالن» در index/show. لینک «کاربران» در نویگیشن فقط برای owner/سوپر ادمین؛ بخش «امور مالی» پشت `@permission('manage-wallet')`.
4. `test(admin): multi-admin ownership, last-owner guard, wallet permission (Phase 2, axis 2)` — `AdminUserManagementTest` بازنویسی شد (۲۱ تست: ساخت owner/staff دوم، اسکوپ سالن، محافظت آخرین owner، دسترسی cross-salon مسدود، رندر صفحات). `SalonStaffFinancePermissionTest` جدید (۷ تست: گیت `manage-wallet`، دسترسی owner-only به `/admin/users`). دو تست قدیمی (`AdminPermissionTest`, `AdminRoleTest`) برای baseline جدید permission/role اصلاح شدن.

### تست‌ها
۱۶ تست جدید/بازنویسی‌شده در دو فایل. کل سوییت نهایی: **۱۰۰۷ پاس، ۱ skip، صفر fail** — هم روی محیط کاری هم روی یک `git clone` کاملاً مستقل از صفر (composer install + npm install + npm run build + هیچ فایل مشترک با محیط اصلی) با `git am --keep-cr` روی ۴ پچ شماره‌گذاری‌شده، بدون خطا.

### تحویل
چهار پچ شماره‌گذاری‌شده:
- `0001-feat-admin-salon-scoped-admin-management-owner-only-.patch`
- `0002-feat-billing-manage-wallet-permission-staff-finance-.patch`
- `0003-feat-admin-owner-staff-UI-for-adding-a-second-admin-.patch`
- `0004-test-admin-multi-admin-ownership-last-owner-guard-wa.patch`

### خارج از دامنه‌ی این نشست (عمداً دست‌نخورده)
- محدودیت دسترسی مالی فقط روی `admin/wallet.php` + `admin/billing.php` اعمال شد؛ `admin/reports.php` (که چارت درآمد هم داره) عمداً گیت نشد — تصمیم‌گیری روی این مورد به نشست بعدی موکول شد.
- staff نمی‌تونه staff/owner دیگه‌ای بسازه یا حتی صفحه‌ی `/admin/users` رو ببینه (طبق تصمیم ۵)؛ اگه بعداً لازم شد یک staff بتونه staff محدودتر دیگه‌ای اضافه کنه، این قانون باید صریحاً بازنگری بشه.
- فیلد `finance_access` فقط یک نقش افزودنی (`finance-access`) می‌سازه؛ دسترسی‌های مالی دقیق‌تر (مثلاً فقط مشاهده بدون تسویه) پیاده‌سازی نشده.

### قدم‌های باز برای نشست بعدی
- محور «۳. ساب‌دامین اختصاصی» (نیازمند هماهنگی Wildcard DNS با ابوالفضل قبل از شروع)
- محور «۴. ثبت‌نام عمومی سالن (self-service)»
- قیمت‌های واقعی `config/billing.php` باید قبل از production جایگزین placeholder فعلی شوند

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹، ادامه): گیت‌کردن `reports.php` + رفع نشتی درآمد در داشبورد اصلی

بعد از تحویل ۴ پچ اول محور «۲»، ابوالفضل درباره‌ی یک نکته‌ی باز پرسید: آیا `admin/reports.php` (که چارت درآمد و صادرات Excel/PDF مالی هم داره) باید مثل `wallet`/`billing` پشت `manage-wallet` گیت بشه یا نه؟

### بررسی و پیشنهاد
با خوندن کد واقعی (`AdminReportsController`, `AdminReportService`, خودِ `dashboard.blade.php`) تأیید شد:
1. محتوای `reports.php` کاملاً مالیه (خلاصه‌ی مالی، نمودار درآمد، تفکیک پرداخت، درآمد به‌تفکیک خدمت/متخصص، صادرات Excel/PDF از همین داده‌ها) — هیچ گزارش خنثی مجزایی نداره.
2. ریسک شکستن داشبورد وجود نداره: نمودار درآمد روی خودِ `/admin` با داده‌ی سمت سرور (نه fetch) رندر می‌شه؛ فقط دکمه‌های فیلتر «امروز/هفته/ماه» به `reports.today/week/month` fetch می‌زنن و از قبل یک `.catch()` سالم دارن.

پیشنهاد شد `reports.php` هم پشت `manage-wallet` بره — ابوالفضل تأیید کرد.

### ⭐ یافته‌ی مهم‌تر (کشف‌شده حین همین بررسی، نه فرض اولیه)
خودِ **صفحه‌ی اصلی `/admin`** (اولین صفحه‌ای که هر ادمین از جمله «منشی» موقع لاگین می‌بینه) از `AdminDashboardService::getOverviewData()` مستقیم `totalRevenue`/`weeklyRevenue` رو محاسبه و نمایش می‌داد — کاملاً مستقل از `manage-wallet`، فقط پشت `access_admin_panel` عمومی که منشی هم داره. یعنی محدودیت «منشی بدون مالی» برای wallet/billing/reports برقرار بود ولی برای همین دو رقم روی خودِ داشبورد اصلاً وجود نداشت. ابوالفضل تأیید کرد این هم فیکس بشه.

### کارهای انجام‌شده
`fix(admin): gate reports + hide dashboard revenue for non-finance staff (Phase 2, axis 2)`:
1. `routes/web.php`: `admin/reports.php` به همون گروه `permission:manage-wallet` (کنار wallet/billing) منتقل شد.
2. `AdminDashboardService::getOverviewData()`: `totalRevenue`/`weeklyRevenue` فقط وقتی `auth()->user()->hasPermission('manage-wallet')` محاسبه می‌شن (bypass همیشگی `is_admin` همچنان برقراره) — هم مقدار هم هزینه‌ی کوئری، برای منشی بدون این دسترسی حذف شد.
3. `dashboard.blade.php`: کارت «درآمد سالن»، دکمه‌های فیلتر «امروز/هفته/ماه»، و کل کارت نمودار درآمد پشت `@permission('manage-wallet')` رفتن؛ «خدمات محبوب» وقتی نمودار نیست کل عرض ردیف رو می‌گیره؛ فراخوانی اولیه‌ی رندر چارت در جاوااسکریپت هم روی وجود واقعی عنصر در DOM گارد شد (وگرنه `null.innerHTML` خطا می‌داد).

### ⭐ باگ واقعی خودم، کشف‌وحل‌شده حین همین کار (نه فرضی)
یک کامنت جاوااسکریپت داخل `dashboard.blade.php` از متن لفظی `@permission` برای اشاره به خودِ directive استفاده کرده بود — دقیقاً همون anti-pattern مستندشده در بالای همین فایل («هرگز اسم یک Blade directive واقعی رو داخل کامنت HTML/JS ننویس»، کشف‌شده اولین بار در R-AdminLoyalty). Blade این رو داخل `<script>` هم اسکن کرد و به‌عنوان یک directive واقعی بدون `@endpermission` جفت‌شده تفسیر کرد → خطای «unexpected end of file, expecting elseif or else or endif». **نکته‌ی روش‌شناسی**: این باگ با `php artisan view:cache` کشف *نشد* (این دستور موفق برگشت با وجود فایل خراب — ظاهراً واقعاً PHP تولیدشده رو اجرا/lint نمی‌کنه)؛ فقط با کامپایل دستی ویو (`Blade::compileString`) و `php -l` روی خروجی، خطا و شماره‌خط واقعی پیدا شد. فیکس با escape مستندشده‌ی پروژه (`@@permission`).

### تست‌ها
۵ تست جدید در `SalonStaffFinancePermissionTest`: مسدودشدن/بازبودن `/admin/reports` برای منشی بدون/با finance-access (با `partialMock` روی `AdminReportService::monthlyBreakdown()` — طبق سیاست مستندشده‌ی پروژه در `AdminReportsControllerTest` برای دور زدن ناسازگاری `YEAR()`/`MONTH()` با SQLite، نه بازنویسی کوئری)، و سه تست تأیید نمایش/عدم‌نمایش درآمد روی داشبورد برای owner/منشیِ بدون دسترسی/منشیِ با finance-access.

کل سوییت نهایی: **۱۰۱۲ پاس، ۱ skip، صفر fail** — هم روی محیط کاری هم روی یک `git clone` کاملاً مستقل و تازه (composer install + npm install + npm run build از صفر) با `git am --keep-cr` روی مجموع ۵ پچ شماره‌گذاری‌شده، بدون خطا.

### تحویل
پنج پچ شماره‌گذاری‌شده (۴ پچ قبلی + این یکی):
- `0001-feat-admin-salon-scoped-admin-management-owner-only-.patch`
- `0002-feat-billing-manage-wallet-permission-staff-finance-.patch`
- `0003-feat-admin-owner-staff-UI-for-adding-a-second-admin-.patch`
- `0004-test-admin-multi-admin-ownership-last-owner-guard-wa.patch`
- `0005-fix-admin-gate-reports-hide-dashboard-revenue-for-no.patch`

### قدم‌های باز برای نشست بعدی
- محور «۳. ساب‌دامین اختصاصی» (نیازمند هماهنگی Wildcard DNS با ابوالفضل قبل از شروع)
- محور «۴. ثبت‌نام عمومی سالن (self-service)»
- Endpoint مرده‌ی `/admin/dashboard/data` (`AdminDashboardAnalyticsController::getData()`) هنوز بدون گیت `manage-wallet` مقدار `totalRevenue` برمی‌گردونه — عمداً دست‌نخورده موند چون هیچ view/JS فعلی صداش نمی‌زنه (طبق کامنت مستندشده‌ی خودِ `routes/admin/dashboard.php`)، ولی اگه یک‌بار دیگه استفاده بشه باید همین محدودیت روش هم اعمال بشه.
- قیمت‌های واقعی `config/billing.php` باید قبل از production جایگزین placeholder فعلی شوند

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹، ادامه): مستندسازی Wildcard DNS قبل از شروع محور ۳

قبل از شروع کدنویسی محور «۳. ساب‌دامین اختصاصی»، ابوالفضل پرسید Wildcard DNS چطور هماهنگ می‌شه؛
مشخص شد فعلاً پروژه فقط روی XAMPP لوکاله (نه دامنه/سرور واقعی)، پس این نشست صرفاً مستندسازی بود
تا اطلاعات production گم نشه، بدون شروع کدنویسی واقعی محور ۳.

### فایل جدید: `docs/WILDCARD_SUBDOMAIN_DEPLOYMENT.md`
راهنمای کامل، مستقل از کد، شامل:
- **تست لوکال (وضعیت فعلی)**: استفاده از سرویس رایگان **nip.io** (مثل
  `rasta.127.0.0.1.nip.io`) به‌جای DNS واقعی — چون Wildcard واقعی روی `hosts` ویندوز ممکن نیست.
- **راهنمای production واقعی** (برای وقتی دامنه/سرور واقعی آماده شد):
  ۱. رکورد Wildcard DNS (`A *` → IP سرور، یا Cloudflare Proxied)
  ۲. تنظیم وب‌سرور (`ServerAlias *.domain` در Apache، `server_name *.domain` در Nginx)
  ۳. گواهی SSL wildcard (DNS-01 challenge با certbot — گواهی معمولی روی wildcard کار نمی‌کنه؛
     یا ساده‌تر، SSL رایگان خودِ Cloudflare)
  ۴. تنظیمات `.env` (`APP_URL`, `SESSION_DOMAIN`) + هشدار امنیتی صریح درباره‌ی
     `SESSION_DOMAIN=.domain` (کوکی سشن مشترک بین همه‌ی ساب‌دامین‌ها/سالن‌ها — باید قبل از
     پیاده‌سازی واقعی با ابوالفضل تصمیم‌گیری بشه، نه فرض پیش‌فرض)
  ۵. چک‌لیست نهایی قبل از deploy

⚠️ **این فایل باید همیشه کنار `Rasta_unified_prompt.md` نگه‌داری بشه و هر بار همراه zip پروژه
آپلود بشه** — چون بخشی از حافظه‌ی بلندمدت پروژه‌ست و توی خودِ کد یا git commit ذخیره نمی‌شه مگر
اینکه ابوالفضل صریح به ریپو اضافه‌اش کنه (پیشنهاد می‌شه توی پوشه‌ی `docs/` کنار پروژه commit بشه).

### تصمیم گرفته‌شده برای ادامه
تست‌های محور ۳ (وقتی واقعاً کدنویسی شروع بشه) روی لوکال با nip.io انجام می‌شه، نه یک mock/فرض
دیگه. دو تصمیم بیزنسی باز (`SESSION_DOMAIN` مشترک یا نه، رفتار دامنه‌ی اصلی بدون ساب‌دامین) هنوز
حل نشده — این دو باید همون اول شروع محور ۳ از ابوالفضل پرسیده بشه.

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹، ادامه‌ی دوم): شروع کدنویسی محور ۳ (ساب‌دامین اختصاصی)

### تصمیمات بیزنسی حل‌شده در ابتدای نشست
دو تصمیم باز نشست قبلی از ابوالفضل پرسیده شد:
۱. `SESSION_DOMAIN`: **ایزوله برای هر ساب‌دامین** (نه مشترک) — ریسک امنیتی نشتی بین‌سالنی رو رد کرد.
۲. دامنه‌ی اصلی بدون ساب‌دامین: بین «لندینگ کامل» و «placeholder ساده»، ابوالفضل نظر خواست؛
   پیشنهاد Claude (و انتخاب نهایی): **placeholder ساده الان**، چون لندینگ واقعی نیاز به یک
   CTA/فرم واقعی داره که هنوز وجود نداره (اون با محور ۴ میاد) — ساختن لندینگ کامل الان یعنی
   دوباره‌کاری وقتی محور ۴ برسه.

### طراحی فنی — چرا config-toggle، نه per-request branching
اولین ایده (شاخه‌بندی مسیرها بر اساس Host هر request، داخل خودِ `routes/web.php`) رد شد چون این
پروژه در production از `php artisan route:cache` استفاده می‌کنه (`docker/entrypoint.sh`) —
route:cache فقط **یک‌بار** فایل routes رو اجرا می‌کنه (زمان build کش)، نه به‌ازای هر بازدیدکننده؛
یک شاخه‌بندی مبتنی بر request در همون فایل زیر route:cache خراب می‌شد.

راه‌حل نهایی: `config('app.central_domain')` (از `env('CENTRAL_DOMAIN')`) یک **تصمیم سطح-boot**
است — دقیقاً یک‌بار در لحظه‌ی ثبت مسیرها انتخاب می‌شه:
- خالی (پیش‌فرض؛ `.env.example` هم همینه) → دقیقاً همون `Route::prefix('s/{salon_slug}')` فاز ۱،
  بدون هیچ تغییر رفتاری. تمام ۱۰۱۳ تست موجود این حالت رو می‌بینن.
- مقداردهی‌شده (مثلاً `127.0.0.1.nip.io` لوکال) → همون مسیرها به‌جاش زیر
  `Route::domain('{salon_slug}.'.central_domain)` ثبت می‌شن.

نکته‌ی مهم: `ResolveSalonFromRoute` (middleware موجود) **بدون هیچ تغییر منطقی** برای هر دو حالت
کار می‌کنه، چون `Route::domain('{salon_slug}.'...)` پارامتر `salon_slug` رو دقیقاً مثل یک بخش
URI پر می‌کنه — فقط docblockش به‌روز شد.

### فایل‌های تغییریافته/جدید (پچ ۰۰۰۱)
- `config/app.php`: کلید `central_domain` + docblock کامل توضیح‌دهنده‌ی طراحی بالا
- `routes/web.php`: بلوک مسیرهای مشتری بین دو حالت (بالا) سوییچ می‌کنه
- `app/Http/Middleware/ResolveSalonFromRoute.php`: فقط docblock
- `resources/views/central/placeholder.blade.php`: صفحه‌ی جدید placeholder
- `.env.example`: `CENTRAL_DOMAIN=` (خالی) + راهنمای nip.io در کامنت

### محدودیت اولیه (رفع‌شده در همین نشست — به «ادامه‌ی سوم» پایین نگاه کن)
نسخه‌ی اول پیاده‌سازی (پچ ۰۰۰۱) یک toggle «یا این یا اون» بود — وقتی `CENTRAL_DOMAIN` ست
می‌شد، `/s/{slug}` دیگه ثبت نمی‌شد. این با `docs/WILDCARD_SUBDOMAIN_DEPLOYMENT.md` (که صراحتاً
می‌گفت لینک قدیمی نباید بشکنه) در تناقض بود — ابوالفضل همون فایل رو دوباره فرستاد، تناقض کشف
و در همین نشست با پچ ۰۰۰۳ تصحیح شد (به «ادامه‌ی سوم» پایین نگاه کن).

### ماجرای تست‌نویسی — چرا phpunit.subdomain.xml جدا لازم شد
چون `central_domain` تصمیم سطح-boot است نه per-test، اول امتحان شد که `CENTRAL_DOMAIN` رو با
`putenv()`/`$_ENV` داخل `setUp()` یک کلاس تست عوض کنیم. در اجرای **تکی** همون فایل درست کار کرد،
ولی در اجرای **کل سوییت** (که همه‌ی تست‌ها در یک پردازش PHP مشترک اجرا می‌شن) مقدار درست اعمال
نشد — `Illuminate\Support\Env` یک `Repository` استاتیک/immutable برای کل عمر پردازش می‌سازه که
به تغییرات بعدی env حساس نیست. راه‌حل نهایی: یک `phpunit.subdomain.xml` جدا (کپی از
`phpunit.xml` + `<env name="CENTRAL_DOMAIN" value="rasta-test.local"/>` در `<php>`، دقیقاً مثل
`DB_CONNECTION`)، اجرا در یک پردازش PHP کاملاً جدا: `vendor/bin/phpunit -c phpunit.subdomain.xml`.
`phpunit.xml` اصلی هم یک `<exclude>` برای همین فایل تست گرفت تا سوییت اصلی بدون تغییر بمونه.

### تست‌ها (پچ ۰۰۰۲)
`tests/Feature/Middleware/SubdomainRoutingTest.php` — ۷ تست: تشخیص درست سالن از ساب‌دامین،
ساخت URL ساب‌دامینی توسط `route()`، ۴۰۴ برای سالن suspended/expired/ناموجود، نمایش
`central.placeholder` روی دامنه‌ی مرکزی، و قفل‌کردن محدودیت `/s/{slug}` بالا.

### نتیجه‌ی verify (روی یک `git clone` کاملاً مستقل، `composer install` از صفر)
- `phpunit.xml` (سوییت اصلی): **۱۰۱۳ pass، ۱ skip، صفر fail** — دقیقاً بدون تغییر نسبت به قبل
- `phpunit.subdomain.xml`: **۷ pass، صفر fail**
- پچ‌ها با `git am --keep-cr` روی یک clone مستقل دیگه هم verify شدن (بدون conflict)

### تحویل
سه پچ شماره‌گذاری‌شده (به «ادامه‌ی سوم» پایین برای پچ ۰۰۰۳ نگاه کن):
- `0001-feat-routing-config-toggled.patch`
- `0002-test-routing-phpunit.subdomain.xml.patch`
- `0003-fix-routing-s-slug-toggle.patch`

برای تست دستی روی XAMPP لوکال با nip.io: در `.env` (نه `.env.example`) دستی اضافه کن
`CENTRAL_DOMAIN=127.0.0.1.nip.io`، بعد یک سالن با یک slug واقعی بساز و از مرورگر به
`http://<slug>.127.0.0.1.nip.io:8000` سر بزن. دامنه‌ی مرکزی بدون ساب‌دامین:
`http://127.0.0.1.nip.io:8000` (باید placeholder رو نشون بده). مسیر قدیمی هم باید هنوز کار کنه:
`http://127.0.0.1.nip.io:8000/s/<slug>/`.

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹، ادامه‌ی سوم): تصحیح تصمیم fallback — /s/{slug} و ساب‌دامین هم‌زمان زنده

### چی شد
ابوالفضل `docs/WILDCARD_SUBDOMAIN_DEPLOYMENT.md` (که در نشست قبل از این نوشته شده بود) رو
دوباره آپلود کرد تا `.env.example` تحویل‌داده‌شده رو چک کنه. همون فایل رو کامل خوندم و یک تناقض
واقعی پیدا شد: بخش «قدم ۵» اون صراحتاً می‌گفت **«مسیر `/s/{slug}` فعلی طبق تصمیم مستندشده حذف
نمی‌شود، به‌عنوان fallback می‌ماند (کسی که لینک قدیمی دارد نباید بشکند)»** — در حالی که پچ ۰۰۰۱
همین نشست یک toggle «یا این یا اون» پیاده کرده بود (وقتی `CENTRAL_DOMAIN` ست بود، `/s/{slug}`
دیگه اصلاً ثبت نمی‌شد، ۴۰۴ می‌داد).

⭐ **درسِ خودِ همین موضوع**: این فایل deployment رو در نشست قبلی فقط از خلاصه‌ی حافظه‌ی پروژه
می‌شناختم، نه از خوندن مستقیم محتوای کاملش — دقیقاً همون الگوی «فرض بدون تأیید مستقیم فایل» که
این پروژه بارها روش تأکید کرده. تناقض رو صادقانه به ابوالفضل گفتم (به‌همراه ریسک فنی هر دو
گزینه)، و طبق تصمیم صریح خودش، حالت «هر دو هم‌زمان زنده» پیاده‌سازی شد.

### طراحی تصحیح‌شده
`routes/web.php`: `Route::prefix('s/{salon_slug}')` حالا **همیشه** (بدون هیچ قید `if`) ثبت
می‌شه — دقیقاً مثل فاز ۱. وقتی `central_domain` ست باشه، `Route::domain('{salon_slug}.'...)`
**علاوه بر** اون (نه به‌جاش) ثبت می‌شه، و عمداً **بعد از** گروه prefix — چون Laravel برای هر نام
route فقط آخرین ثبت رو در جدول نام‌ها نگه می‌داره
(`Illuminate\Routing\RouteCollection::addToNamedRoutes`). نتیجه:
- `/s/{slug}/...` هنوز ۲۰۰ می‌ده (لینک قدیمی نمی‌شکنه) ✅
- ولی `route('services.index')` و مشابه‌ها، حتی از یک request که از همون مسیر قدیمی رسیده،
  همیشه یک URL مطلق **ساب‌دامینی** می‌سازن — یعنی هر لینک داخلی جدیدی که از یک صفحه ساخته
  می‌شه، طبیعتاً کاربر رو به شکل مدرن (ساب‌دامین) هدایت می‌کنه.

⚠️ **ریسک شناخته‌شده و صریحاً پذیرفته‌شده با ابوالفضل**: چون `SESSION_DOMAIN` ایزوله است
(تصمیم قبلی، هنوز پابرجا)، کوکی سشن به دامنه‌ی دقیق اسکوپ می‌شه. کاربری که از لینک قدیمی
`/s/{slug}` وارد شده و بعد روی یک لینک داخلی (که حالا به ساب‌دامین اشاره می‌کنه) کلیک می‌کنه،
عملاً به یک هاست دیگه navigate می‌شه — کوکی سشنش اونجا موجود نیست و ممکنه logout به نظر برسه.
این یک محدودیت شناخته‌شده‌ست، نه یک باگ ناخواسته. اگه در آینده مزاحم عملکرد واقعی شد، دو راه‌حل
احتمالی (هیچ‌کدوم هنوز تصمیم‌گیری نشده): `SESSION_DOMAIN` مشترک (با ریسک امنیتی خودش، دقیقاً
همون چیزی که در نشست‌های قبلی رد شد) یا یک صفحه‌ی میانی «داری به آدرس جدید سالن منتقل می‌شی»
قبل از redirect نهایی.

### فایل‌های تغییریافته (پچ ۰۰۰۳)
- `routes/web.php`: `Route::prefix(...)` بدون قید همیشه ثبت می‌شه؛ `Route::domain(...)` بعدش
  و فقط وقتی `central_domain` ست باشه اضافه می‌شه؛ docblock کامل توضیح می‌ده.
- `config/app.php`: docblock کنار `central_domain` به‌روز شد تا همین طراحی رو منعکس کنه.
- `tests/Feature/Middleware/SubdomainRoutingTest.php`:
  - تست قبلی (انتظار ۴۰۴ برای `/s/{slug}` وقتی `central_domain` ست بود) معکوس شد — حالا انتظار
    ۲۰۰ + `CurrentSalon` درست داره (`test_path_based_slash_s_slug_url_still_works_as_a_fallback_when_central_domain_is_set`).
  - یک تست جدید اضافه شد که رفتار «آخرین ثبت برنده‌ست» رو قفل می‌کنه: حتی وقتی یک request از
    مسیر قدیمی `/s/{slug}` می‌رسه، `route()` بعدش همچنان URL ساب‌دامینی می‌سازه
    (`test_route_helper_generates_subdomain_urls_even_from_a_request_reached_via_the_legacy_path`).

### نتیجه‌ی verify (روی یک `git clone` کاملاً مستقل دیگه، از صفر)
- سه پچ (۰۰۰۱ تا ۰۰۰۳) با `git am --keep-cr` بدون conflict روی هم اعمال شدن
- `phpunit.xml` (سوییت اصلی): **۱۰۱۳ pass، ۱ skip، صفر fail** — دقیقاً بدون تغییر
- `phpunit.subdomain.xml`: **۸ pass، صفر fail**

### تحویل
یک پچ جدید، روی همون دو پچ قبلی:
- `0003-fix-routing-s-slug-toggle.patch`

### قدم‌های باز برای نشست بعدی
- محور «۳» تکمیل نشده: ریسک قطعی‌شدن سشن (بالا) هنوز بدون راه‌حل نهایی مونده — فقط شناسایی و
  پذیرفته شده؛ اگه در تست دستی واقعی (XAMPP + nip.io) مزاحم شد، باید حل بشه.
- محور «۴. ثبت‌نام عمومی سالن (self-service)»
- Endpoint مرده‌ی `/admin/dashboard/data` هنوز بدون گیت `manage-wallet` — به یادداشت نشست قبلی نگاه کن
- قیمت‌های واقعی `config/billing.php` باید قبل از production جایگزین placeholder فعلی شوند
- ⭐ **ممیزی امنیتی فوری/جدا (کشف‌شده در ادامه‌ی چهارم، پایین این فایل)**: implicit route-model-binding
  روی مدل‌های `BelongsToSalon` در کل پنل ادمین (booking/service/specialist/...) به‌احتمال زیاد از
  همون الگوی امنیتی‌ای رنج می‌بره که در `AdminReportExportController::download()` پیدا و فیکس شد —
  global scope در لحظه‌ی binding محافظت نمی‌کنه چون `SubstituteBindings` قبل از `salon.active` اجرا
  می‌شه. هیچ کنترلر دیگه‌ای هنوز واقعاً تست نشده؛ این باید اولین کار نشست بعدی باشه.

---

## ⭐⭐ نشست (۲۰۲۶-۰۹-۱۹، ادامه‌ی چهارم): برندینگ هاردکد + نشتی داده‌ی بین‌سالنی (کش + گزارش‌گیری)

### چی شد
ابوالفضل بعد از تست موفق محور ۳ (هر ۵ آدرس nip.io/hosts درست کار کردن)، یک مشاهده‌ی درست داد:
وقتی به دو سالن مختلف (`rasta` و `n`) سر می‌زد، هر دو دقیقاً همون برندینگ «راستا» رو نشون
می‌دادن. بررسی این مشاهده به‌تدریج به چند باگ واقعی و جدی‌تر منجر شد:

### ۱) برندینگ هاردکد (تأیید شد، تحلیل ابوالفضل درست بود)
نام سالن در ~۱۳ فایل ویو به‌صورت متن ثابت «راستا» نوشته شده بود، نه از دیتابیس — مونده از فاز ۱
(تک‌سالنی)، هیچ‌وقت پاک نشده بود. یک `ViewComposer` جدید یک متغیر امن `$currentSalonName` به همه‌ی
ویوها share می‌کنه (نام واقعی سالن وقتی `CurrentSalon` ست باشه، وگرنه نام پلتفرم). همه‌ی ویوهای
مشتری + `specialist/reports/pdf.blade.php` + `admin/reports/pdf-report.blade.php` فیکس شدن.
⚠️ متن‌های بازاریابی اطراف نام (مثل «با سال‌ها تجربه») دست‌نخورده موندن — چون `Salon` model هیچ
ستون description/bio نداره؛ این محتوا هنوز واقعاً generic می‌مونه، فقط نام درست شد.

### ۲) نشتی کش بین‌سالنی در `HomeController` (باگ واقعی، مستقل از برندینگ)
حین بررسی کد برای فیکس بالا، مشخص شد `HomeController` سرویس‌ها/متخصصان رو زیر کلیدهای کش **ثابت**
(`'home_services'`, `'home_specialists'` — بدون شناسه‌ی سالن) کش می‌کرد، با اینکه هر دو مدل
salon-scoped هستن. هر سالنی که اول کش می‌شد، تا ۳۰ دقیقه روی همه‌ی سالن‌های دیگه هم تحمیل می‌شد.
فیکس شد (`"home_services:{salonId}"`). یک نشتی مشابه (فعلاً غیرفعال چون remember() این کلاس هنوز
هیچ‌جا صدا زده نمی‌شه) در `ReportCacheService::generateCacheKey()` هم preventive فیکس شد.

### ۳) نشتی داده + دسترسی غیرمجاز در گزارش‌گیری ادمین (جدی‌ترین کشف امروز)
بررسی همه‌ی `Cache::remember` های کدبیس یک الگوی مشابه رو در `routes/api.php` نشون داد (که خودِ
پروژه از قبل مستند کرده بود: `services.php`/`specialists.php`/`bookings.php`/`gallery.php` بیرون
از `/s/{slug}` ثبت شدن، پس `CurrentSalon` هیچ‌وقت ست نمی‌شه — دیده شد ولی **دست‌زده نشد**، چون
خودِ پروژه این رو «blast radius بزرگ‌تر، نیاز به pass جدا» مستند کرده بود).

ولی یک کشف جدید و جدی‌تر: جدول `report_exports` از اول اصلاً `salon_id` نداشت. سه مشکل واقعی:
1. `GeneratePdfReportJob` یک **queued job** است — بدون HTTP context، `CurrentSalon` هیچ‌وقت ست
   نمی‌شد → فایل PDF/Excel خروجی، داده‌ی همه‌ی سالن‌ها رو قاطی برمی‌گردوند.
2. `AdminReportExportController::index()` هیچ فیلتر سالنی نداشت — همه‌ی سالن‌های پلتفرم مخلوط.
3. `download()` هیچ چک مالکیتی نداشت — یک ادمین سالن A می‌تونست گزارش مالی سالن B رو دانلود کنه.

**فیکس**: migration جدید (`salon_id` روی `report_exports`) + تریت `BelongsToSalon` روی مدل
(مشکل ۱ و ۲ رو یک‌جا حل می‌کنه) + `GeneratePdfReportJob` صریحاً `CurrentSalon` رو از
`salon_id` ذخیره‌شده ست می‌کنه قبل از هر query.

⭐ **کشف حین نوشتن تست، مهم‌ترین یافته‌ی امروز**: صرفاً اضافه‌کردن `BelongsToSalon` مشکل ۳
(دانلود) رو حل نکرد — یک تست واقعی (نه فرض) نشون داد یک ادمین هنوز می‌تونست گزارش سالن دیگه رو
دانلود کنه. دلیل: `{reportExport}` با implicit route-model-binding resolve می‌شه، که توسط
`SubstituteBindings` انجام می‌شه — این middleware بخشی از گروه **global** `'web'` در Laravel ـه و
همیشه **قبل از** middleware سطح-route (`salon.active`، که `CurrentSalon` رو ست می‌کنه) اجرا
می‌شه. یعنی لحظه‌ی binding، `CurrentSalon` هنوز ست نشده (در یک request واقعی و تازه، null است) →
global scope هیچ فیلتری اعمال نمی‌کنه → هر id قابل bind شدنه. این یک محدودیت ساختاری Laravel ـه،
نه یک باگ خاص این پروژه — ولی نتیجه‌اش یک آسیب‌پذیری واقعی بود. فیکس: یک چک صریح
(`abort_unless($reportExport->salon_id === app(CurrentSalon::class)->id(), 404)`) داخل خودِ متد
`download()` — نه تکیه بر global scope برای implicit-bound پارامترها.

⚠️ **این الگو احتمالاً در جاهای دیگه‌ی پنل ادمین هم تکرار شده** — هر implicit route parameter که
مدلش `BelongsToSalon` داره (`{booking}`, `{specialist}`, `{service}`, و مشابه‌ها در
`AdminBookingController`, `AdminSpecialistController`, `AdminServiceController`, ...) به همین شکل
ممکنه در معرض همین کلاس آسیب‌پذیری باشن. **هیچ‌کدوم این نشست تست/فیکس نشدن** — فقط الگوی کد مشابه
دیده شد؛ باید اولین کار نشست بعدی، یک ممیزی متمرکز و تست‌محور (نه فرض‌محور) روی این‌ها باشه.

### نتیجه‌ی verify (روی یک `git clone` کاملاً مستقل دیگه، از صفر — composer install کامل)
- شش پچ (۰۰۰۱ تا ۰۰۰۶) با `git am --keep-cr` بدون conflict به ترتیب اعمال شدن
- `php artisan migrate:fresh` — همه‌ی migration ها (شامل migration جدید salon_id) بدون خطا
- `phpunit.xml` (سوییت اصلی): **۱۰۲۱ pass، ۱ skip، صفر fail** (۱۰۱۳ قبلی + ۸ تست جدید امروز)
- `phpunit.subdomain.xml`: **۸ pass، صفر fail** (بدون تغییر)

### تحویل
سه پچ جدید، روی سه پچ قبلی محور ۳:
- `0004-fix-branding.patch`
- `0005-fix-cache-ReportCacheService.patch`
- `0006-fix-security.patch`

### ✅ ممیزی implicit-route-model-binding روی کل پنل ادمین (کامل شد — اولویت اول نشست قبل)
با یک probe تست HTTP واقعی (نه فرضی — با `CurrentSalon::clear()` قبل از هر request برای
شبیه‌سازی دقیق لحظه‌ی `SubstituteBindings` در یک request تازه‌ی واقعی؛ نسخه‌ی اول probe
اشتباهاً `CurrentSalon` رو به سالن ادمین reset می‌کرد که آسیب‌پذیری رو ماسک می‌کرد و false-negative
می‌داد) روی همه‌ی implicit-bound route parameter های زیر `/admin/*` که مدلشون `BelongsToSalon`
داره یا از طریق `specialist_id` به سالن وصله، ثابت شد **۱۱ نقطه‌ی مجزا** دقیقاً همون کلاس باگ
`AdminReportExportController::download()` (نشست قبل) رو داشتن — یعنی یک ادمین سالن A می‌تونست
رکورد سالن B رو ببینه/ویرایش/حذف کنه، چون `SubstituteBindings` (گروه global middleware `web`)
implicit route parameter رو *قبل از* `salon.active` (که `CurrentSalon` رو ست می‌کنه) resolve
می‌کنه. یکی از این ۱۱ مورد (`AdminGalleryController::destroy`) حتی رکورد سالن دیگه رو واقعاً
**حذف** می‌کرد، نه فقط لو می‌داد.

⭐ **کشف اضافه، فراتر از چیزی که نشست قبل مستند کرده بود**: `SpecialistWallet`، `WithdrawalRequest`
و `Leave` اصلاً trait `BelongsToSalon` ندارن (فقط از طریق `specialist_id` به سالن وصل‌اند، بدون
ستون `salon_id` خودشون) — یعنی نه یک باگ زمان‌بندی binding، بلکه فقدان کامل scope سالنی در هر
لحظه‌ای، حتی داخل بدنه‌ی کنترلر. مسیرهای `{wallet}`/`{withdrawalRequest}` در `routes/admin/wallet.php`
و `{specialist}/leaves/{leave}` هم به همین شکل باز بودن.

⭐ **کنترل جالب**: تنها مسیر implicit-looking که واقعاً امن از آب دراومد `AdminCategoryController::show($id)`
بود — چون برخلاف ظاهرش implicit binding نداره (پارامتر خام `$id` می‌گیره و خودش داخل بدنه‌ی متد
`Category::findOrFail($id)` صدا می‌زنه، یعنی بعد از اجرای کامل `salon.active`). این خودش تأیید
مستقیم مکانیزم دقیق باگه: هرجا کوئری داخل بدنه‌ی کنترلر (بعد از میدل‌ور) اجرا بشه امنه؛ هرجا
implicit-bound تو امضای متد باشه (قبل از میدل‌ور resolve می‌شه)، آسیب‌پذیره.

**فیکس**: یک متد کمکی مشترک `Controller::ensureSalonOwnership(?int $modelSalonId)` که به‌عنوان
اولین خط هر متد آسیب‌دیده صدا زده می‌شه — نه جایگزین global scope، بلکه چون global scope در لحظه‌ی
binding قابل‌اعتماد نیست؛ تا این لحظه (داخل بدنه‌ی کنترلر) `salon.active` قبلاً اجرا شده. برای
مدل‌های بدون ستون `salon_id` (`SpecialistWallet`, `WithdrawalRequest`, `Leave`)، `salon_id` متخصص
با `Specialist::withoutGlobalScopes()->whereKey(...)->value('salon_id')` resolve می‌شه (نه رابطه‌ی
معمولی `->specialist` که خودش scoped هست و برای متخصص سالن دیگه `null` برمی‌گردونه).

کنترلرهای فیکس‌شده: `AdminAnnouncementController`, `AdminBlogCategoryController`,
`AdminBlogController`, `AdminBlogPostActionController`, `AdminBookingController`,
`AdminDiscountCodeController`, `AdminGalleryController`, `AdminServiceController`,
`AdminSpecialistController`, `AdminSpecialistScheduleController`, `AdminSpecialistLeaveController`,
`AdminWalletController`, `AdminWithdrawalController`, `AdminLeaveController`, `AdminHolidayController`.

### نتیجه‌ی verify (روی یک `git clone` کاملاً مستقل دیگه از `develop` واقعی — commit `40c58eb`، از صفر)
- سه پچ (۰۰۰۱ تا ۰۰۰۳) با `git am` بدون conflict به ترتیب اعمال شدن
- `composer install` + `npm install && npm run build` (لازم برای manifest.json که در کلون خام وجود نداره)
- `phpunit.xml` (سوییت اصلی): **۱۰۳۶ pass، ۱ skip، صفر fail** (۱۰۲۱ قبلی + ۱۵ تست جدید امروز)
- `phpunit.subdomain.xml`: **۸ pass، صفر fail** (بدون تغییر)

### تحویل
سه پچ جدید، روی commit `40c58eb` (develop بعد از مرج V2):
- `0001-fix-admin-guard-implicit-bound-BelongsToSalon-models.patch`
- `0002-fix-admin-guard-no-trait-cross-salon-models-wallet-w.patch`
- `0003-test-admin-lock-cross-salon-implicit-binding-fixes-w.patch`

### ✅ پچ‌های implicit-binding از قبل روی develop بودن — کشف حین این نشست
حین ساخت پچ‌های بعدی، `git fetch` نشون داد `develop` از `40c58eb` به `dd3e34c` جلو رفته — یعنی
ابوالفضل خودش سه پچ implicit-route-model-binding (بالا) رو قبلاً apply و push کرده بود.
`git diff` بین کامیت‌های محلی و نسخه‌ی pushed‌شده صفر بود (کد کاملاً یکسان)، فقط پیام کامیت‌ها این
بار به‌جای فارسی، انگلیسی regenerate شدن (طبق درخواست صریح ابوالفضل — کامیت subject/body باید
انگلیسی باشه، کامنت‌های داخل کد طبق قرارداد پروژه فارسی می‌مونن).

### ✅ tagline/bio سالن (پیگیری «متن‌های بازاریابی generic»)
migration جدید دو ستون nullable به `salons` اضافه کرد: `tagline` (کوتاه، مثل subtitle صفحه‌ی
خدمات) و `bio` (پاراگراف، مثل بخش «درباره‌ی ما»). `StoreSalonRequest`/`UpdateSalonRequest`،
`SuperAdminService::createSalonWithAdmin()`/`updateSalon()` (همون الگوی array_key_exists که
برای `zarinpal_merchant_id` استفاده شده بود — فرستادن مقدار خالی واقعاً پاک می‌کنه)، و فرم‌های
ساخت/ویرایش سالن در پنل سوپرادمین وصل شدن.

`ViewComposer` دو متغیر جدید share می‌کنه: `$currentSalonTagline`/`$currentSalonBio` — با
fallback امن به همون متن عمومی قبلی، تا یک سالن تازه‌ساخته که این فیلدها رو پر نکرده صفحه‌ی
خالی/شکسته نبینه. سه متن هاردکد به این متغیرها وصل شدن: فوتر (`layouts/app.blade.php`)، subtitle
صفحه‌ی خدمات (`services/index.blade.php`)، و پاراگراف «درباره‌ی ما» در صفحه‌ی اصلی
(`home.blade.php`). ۹ تست جدید (`SalonManagementTest`: ساخت/آپدیت/پاک‌کردن/دست‌نخورده‌موندن؛
`HomeControllerTest`: نمایش محتوای اختصاصی وقتی ست شده + fallback وقتی نشده).

### ✅ Endpoint مرده‌ی `/admin/dashboard/data` — گیت `manage-wallet` اضافه شد
`AdminDashboardAnalyticsController::getData()` — بدون هیچ consumer فعلی (نمودار از
`/admin/reports/{period}` می‌خونه) ولی طبق تصمیم قبلی زنده نگه داشته شده — `totalRevenue` رو
بدون هیچ گیت مالی برمی‌گردوند. یعنی یک staff بدون `finance-access` (که `dashboard.blade.php` طبق
فیکس قبلی ازش مخفی می‌شه) می‌تونست با یک درخواست مستقیم JSON این محدودیت رو دور بزنه.
`routes/admin/dashboard.php`: روت `dashboard.data` حالا مثل `wallet.php`/`billing.php`/
`reports.php` زیر `Route::middleware(['permission:manage-wallet'])` قرار گرفت. ۲ تست جدید در
`SalonStaffFinancePermissionTest` (staff بدون finance-access → ۴۰۳؛ staff با finance-access →
۲۰۰؛ owner با `is_admin=true` طبق bypass مستندشده از قبل پوشش داده شده بود).

### نتیجه‌ی verify (روی `git clone` کاملاً مستقل دیگه از `develop` — commit `dd3e34c`، از صفر)
- دو پچ جدید (روی develop به‌روزشده) با `git am` بدون conflict اعمال شدن
- `composer install` + `npm install && npm run build`
- `phpunit.xml`: **۱۰۴۶ pass، ۱ skip، صفر fail**
- `phpunit.subdomain.xml`: **۸ pass، صفر fail**

### تحویل
دو پچ جدید، روی `develop` به‌روزشده (بعد از پچ‌های implicit-binding که خودتون قبلاً apply کردید):
- `0004-feat-salon-per-salon-tagline-bio-to-replace-generic-.patch`
- `0005-fix-admin-gate-the-dead-admin-dashboard-data-endpoin.patch`

### ✅ ریسک قطعی‌شدن سشن — تست دستی واقعی انجام شد و تصمیم قبلی معکوس شد
تست دستی روی لوکال (nip.io + `php artisan serve` روی `127.0.0.1:8000` + DevTools، طبق مراحلی که
تو همین نشست داده شد) نشون داد این ریسک خیلی جدی‌تر از چیزی بود که مستند شده بود: با
`SESSION_DOMAIN=null` (ایزوله)، فرم لاگین روی `/s/{slug}` خودش با **۴۱۹ Page Expired** می‌شکست —
نه فقط یک navigate بعد از لاگین موفق. دلیل: `<form action>` این صفحه (رندرشده با `route()`) از
همون اول به ساب‌دامین اشاره می‌کنه، ولی کوکی سشن/CSRF token مال هاست `/s/{slug}` بودن.

بررسی کد `EnsureCustomerBelongsToSalon` و `EnsureAdminSalonActive` نشون داد مرز امنیتی واقعی
پروژه (که دلیل رد شدن کوکی مشترک بود) اصلاً به دامنه‌ی کوکی متکی نیست — هر دو `salon_id` کاربر رو
با `CurrentSalon` (resolve‌شده از خودِ URL هر request، نه از سشن) مقایسه می‌کنن. یعنی کوکی مشترک
هیچ سوراخ امنیتی جدیدی باز نمی‌کنه.

**تصمیم قبلی معکوس شد**: `SESSION_DOMAIN` حالا مشترک بین ساب‌دامین‌ها توصیه می‌شه
(`.yourdomain.com` روی production، `.127.0.0.1.nip.io` روی لوکال)، نه ایزوله. بدون تغییر کد PHP —
فقط `WILDCARD_SUBDOMAIN_DEPLOYMENT.md` (قدم ۴، قدم ۵، چک‌لیست، بخش لوکال) و `.env.example` آپدیت
شدن تا با تصمیم جدید هم‌خونی داشته باشن. ابوالفضل باید `.env` واقعی لوکال و production خودش رو هم
دستی به همین شکل آپدیت کنه (این فایل‌ها در git نیستن).

### نتیجه‌ی verify (روی `git clone` کاملاً مستقل دیگه از `develop` — commit `dd3e34c`، از صفر)
- سه پچ (۰۰۰۴ تا ۰۰۰۶) با `git am` پشت‌سرهم بدون conflict اعمال شدن
- `composer install` + `npm install && npm run build`
- `phpunit.xml`: **۱۰۴۶ pass، ۱ skip، صفر fail** (پچ ۰۰۰۶ فقط مستندات/config-example‌ست، تست
  جدیدی نداشت — چون رفتار دامنه‌ی کوکی مرورگر واقعی چیزی نیست که PHPUnit بتونه معنادار شبیه‌سازی
  کنه؛ تأیید واقعی همون تست دستی بالا بود)

### تحویل
پچ ششم، روی همون سه پچ قبلی (۰۰۰۴ و ۰۰۰۵):
- `0006-docs-switch-SESSION_DOMAIN-to-shared-across-subdomai.patch`

## ✅ الگوی implicit-binding در routes/api.php — بسته شد (services.php + specialists.php)

پیرو ممیزی قبلی (که فقط `/api/announcements/active` رو فیکس کرد و بقیه‌ی فایل رو عمداً باز
گذاشت)، این نشست دو فایل باقی‌مانده‌ی `routes/api/public/` — `services.php`
(`ServiceController::list()`) و `specialists.php` (هر سه روت `BookingAvailabilityController`) —
رو بست. هر دو دقیقاً همون الگو داشتن: کاملاً بیرون از `/s/{salon_slug}`، یعنی `CurrentSalon` هیچ‌وقت
bind نمی‌شد و `BelongsToSalon` هیچ فیلتری اعمال نمی‌کرد — نشتی کامل لیست بین همه‌ی سالن‌ها، نه فقط
IDOR روی یک id خاص. با دو سالن و بدون هیچ salon context مستقیماً تأیید شد.

**فیکس واقعی، نه صرفاً جابه‌جایی زیر `/s/{slug}`:**
- سه روت `specialists.php` (`getSpecialistsByService`/`getAvailableDates`/`getAvailableTimeSlots`)
  عین duplicate روت‌های از قبل scoped-شده و auth-protected‌ی `routes/web/bookings.php`
  (`bookings.service-specialists`/`available-dates`/`available-slots`) بودن — به‌جای دوباره‌سازی
  زیر `/s/{slug}` در `api.php`، این فایل کلاً **حذف** شد و مصرف‌کننده‌ها به همون روت‌های موجود وصل
  شدن.
- روت `services.php` (`ServiceController::list()`) duplicate scoped نداشت، پس واقعاً منتقل شد: حالا
  `bookings.services-list` در `routes/web/bookings.php`، داخل همون گروه
  `auth+verified+salon.customer` موجود.
- یک باگ دوم و ظریف‌تر هم توی همون متد پیدا شد: کلید کش ۳۰ دقیقه‌ای (`Cache::remember`) رشته‌ی
  سراسری `'all_beauty_services'` بود — یعنی حتی بعد از فیکس query، اولین سالنی که این روت رو
  می‌زد ۳۰ دقیقه کل کش رو برای همه‌ی سالن‌های دیگه پر می‌کرد (دقیقاً همون کلاس باگ کلیدهای
  `home_services`/`home_specialists` در `HomeController` که قبلاً فیکس شده بود). کلید حالا
  با `salon_id` suffix می‌شه.

**مصرف‌کننده‌های واقعی (با grep کامل روی `resources/`)**: فقط `bookings/create.blade.php` و
`bookings/reschedule.blade.php` — هر دو الان به‌جای URLهای hardcode‌شده‌ی `/api/...`، از
`@json(route('bookings.services-list'/'service-specialists'/'available-dates'/'available-slots'))`
رندرشده در سمت سرور استفاده می‌کنن (همون مکانیزم `URL::defaults(['salon_slug' => ...])` که برای
۵۲ `route()` call site دیگه‌ی پروژه از قبل کار می‌کرد). چون هر دو صفحه از قبل پشت
`auth+verified+salon.customer` بودن، این تغییر هیچ نیاز auth جدیدی اضافه نکرد.

`admin/schedule/index.blade.php` هم یک `/api/specialists` (بدون id) داشت، ولی این dead markup‌ه:
mount ری‌اکتی که `window.initialData.routes.specialists` رو می‌خوند در یک پاکسازی قبلی از
`resources/js/admin.jsx` حذف شده (اون فایل الان کاملاً خالیه) — یعنی هیچ‌وقت واقعاً fetch نمی‌شه.
این یک باگ صفحه‌ی مرده‌ی از قبل موجوده، نه نشتی؛ دست نخورده باقی موند (به لیست «قدم‌های باز»
پایین اضافه شد).

### ⭐ یافته‌ی جدید، جدا نگه‌داشته‌شده: implicit-binding race روی خودِ `bookings.*`
حین بررسی مصرف‌کننده‌ها، یک باگ **متفاوت ولی هم‌خانواده** روی خودِ روت‌های scoped
(`bookings.service-specialists`/`available-dates`/`available-slots`) پیدا شد — دقیقاً همون کلاس
باگی که قبلاً کل پنل ادمین رو گرفته بود (`SubstituteBindings`، بخشی از گروه global middleware
`web`، پارامترهای `{service}`/`{specialist}` رو از طریق `Route::bind()` سراسری
(`RouteServiceProvider::configureModelBindings()`) resolve می‌کنه **قبل از** اینکه `salon.resolve`
اجرا بشه و `CurrentSalon` رو ست کنه). با یک probe مستقیم تأیید شد: یک specialist متعلق به سالن
دیگه، از طریق `bookings.available-dates` (با کاربر لاگین‌شده‌ی سالن پیش‌فرض) با status ۲۰۰ (نه
۴۰۴) resolve می‌شه — یعنی implicit binding موفق می‌شه، و چون `resolveSpecialist()` در
`BookingAvailabilityController` یک آبجکت از قبل resolve‌شده رو مستقیم مصرف می‌کنه (نه یک کوئری
جدید که scope روش اعمال بشه)، برنامه‌ریزی/تعطیلات/مرخصی واقعی اون specialist از سالن دیگه قابل
مشاهده‌ست — صرف‌نظر از این‌که از طریق کدوم سالن URL بهش رسیدی.

**این یافته‌ی این نشست نیست، از قبل توی `routes/web/bookings.php` وجود داشت** — جابه‌جایی
مصرف‌کننده‌ها از `/api/...` قدیمی به `bookings.*` نه این باگ رو ایجاد کرد نه بدترش کرد (روی نسخه‌ی
قدیمی هم، چون `CurrentSalon` اصلاً bind نمی‌شد، همین داده کاملاً در دسترس بود). عمداً همین‌جا فقط
مستند شد، نه فیکس — دقیقاً همون الگوی «کشف → flag → ممیزی اختصاصی بعدی» که برای implicit-binding
ادمین قبلاً طی شد؛ فیکس درست (احتمالاً یک `abort_unless` مالکیت صریح در
`resolveSpecialist()`/`resolveService()`، مثل الگوی `AdminReportExportController::download()`)
نیاز به ممیزی جدا داره چون این الگوی binding (`Route::bind('specialist'/'service'/'booking'/
'user', ...)` سراسری در `RouteServiceProvider`) احتمالاً روت‌های دیگه‌ای غیر از `bookings.php` رو
هم تحت تأثیر قرار می‌ده که هنوز چک نشدن.

### تست‌ها و تحویل
- Probe واقعی HTTP (سه سناریو: لیست کامل خدمات، specialists-by-service، available-dates) قبل از
  فیکس تأیید کرد نشتی واقعیه؛ probe کلید کش هم جداگانه تأیید کرد (فایل‌های probe قبل از commit پاک
  شدن، طبق روال پروژه).
- `CrossSalonServiceLeakTest` (جدید، `tests/Feature/User/`): دو سالن، هر کدوم یک مشتری — تأیید
  می‌کنه `bookings.services-list` فقط خدمات سالن جاری رو برمی‌گردونه، کش بین سالن‌ها مشترک نیست، و
  روت قدیمی `/api/services` واقعاً حذف شده (۴۰۴، نه صرفاً جایگزین‌شده).
  `ServiceControllerTest::test_api_list_computes_prepayment_amount_from_current_admin_settings`
  به روت جدید (با auth) آپدیت شد.
- سه کامیت جدا: (۱) فیکس روت/کنترلر بک‌اند + حذف دو فایل نشتی، (۲) آپدیت دو مصرف‌کننده‌ی JS، (۳)
  تست‌ها.

### نتیجه‌ی verify (روی `git am` کاملاً مستقل و از صفر، جدا از هر working copy)
- سه پچ پشت‌سرهم بدون conflict اعمال شدن
- `composer install` + migration روی SQLite
- `phpunit.xml`: **۱۰۴۹ pass، ۱ skip، صفر fail** (۳ تست جدید نسبت به baseline ۱۰۴۶)

### تحویل
سه پچ:
- `0001-Fix-test-writing-session-cross-tenant-leak-audit-con.patch`
- `0002-Fix-same-leak-close-continued-repoint-the-two-live-J.patch`
- `0003-test-cross-salon-regression-coverage-for-the-service.patch`

## ✅ چهار قدم باز نشست قبلی — همگی همین نشست انجام شدن

### ۱. ممیزی implicit-binding مشتری/customer-facing — ۸ متد فیکس شد
با probe مستقیم HTTP (نه فرض) روی هر ۴ نوع `Route::bind()` سراسری (`specialist`/`service`/
`booking`/`user` در `RouteServiceProvider`) تأیید شد که همون race قبلاً برای پنل ادمین فیکس‌شده
روی ۸ متد کنترلر مشتری هم وجود داره — `SubstituteBindings` (بخشی از گروه global middleware
`web`) این پارامترها رو **قبل از** اجرای `salon.resolve` resolve می‌کنه. `{booking}` تحت تأثیر
نبود، چون `check.booking.ownership` middleware و `BookingPolicy::pay()` هر دو مستقل از
`CurrentSalon`، مالکیت رو با `user_id === auth()->id()` چک می‌کنن.

فیکس‌شده (با همون `Controller::ensureSalonOwnership($model->salon_id)` که برای پنل ادمین ساخته
شده بود):
- `ServiceController::show`
- `SpecialistController::show`/`availability`/`availableSlots`/`byService`
- `ReviewController::specialistReviews`
- `BlogController::show`
- `BookingAvailabilityController::getAvailableTimeSlots`/`getAvailableDates`/
  `getSpecialistsByService` (چک مالکیت عمداً بیرون از try/catch اصلی، چون `HttpException` خودش
  از `\Exception` ارث می‌بره و catch عمومی متد بی‌صدا قورتش می‌داد)

تست: `CrossSalonCustomerFacingImplicitBindingTest` (۱۰ تست، الگوی
`CrossSalonImplicitBindingTest` ادمین).

**یافته‌ی جانبی، عمداً فیکس‌نشده**: `routes/web/services.php` یک روت هم‌URI دقیقاً تکراری با
`routes/web/public-specialists.php` برای `specialists.availability`/`available-slots` ثبت
می‌کنه — چون `RouteCollection` لاراول دومی رو کاملاً جایگزین اولی می‌کنه، نسخه‌ی عمومی/بدون‌auth
این دو مسیر مرده‌ست (به `/login` ریدایرکت می‌شه). فیکس امنیتی بالا مستقل از این باگه، ولی خودِ
shadowing نیاز به بررسی جدا داره.

### ۲. رفع صفحه‌ی مرده — حذف شد، نه «رفع»
با جست‌وجوی کامل (`route:list`، grep روی `app/` و `resources/views/`) ثابت شد
`admin/schedule/index.blade.php` **هیچ‌وقت اصلاً reachable نبوده** — نه روتی بهش می‌رسه، نه
کنترلری صداش می‌زنه، نه لینکی بهش اشاره می‌کنه. نام روت `admin.schedule.index` واقعاً وجود داره و
استفاده می‌شه (`admin/specialists/show.blade.php` بهش لینک می‌ده)، ولی به
`AdminSpecialistScheduleController@edit` می‌ره — یک صفحه‌ی کاملاً متفاوت، زنده و درست‌کار
(`admin.specialists.schedules.edit`). فایل مرده حذف شد (نه بازسازی، چون رفتار زنده‌ای برای حفظ
کردن وجود نداشت).

### ۳. بررسی ۴۰۴ دامنه‌ی اصلی — علتش پیدا شد: تنظیمات، نه باگ
با تست دستی واقعی (`php artisan serve` + `curl` با `Host` header، نه فقط شبیه‌سازی) بازتولید و
تأیید شد: با `CENTRAL_DOMAIN` درست تنظیم‌شده روی هاست درخواستی، `http://rasta-app.test:8000/`
صفحه‌ی placeholder رو با ۲۰۰ درست نشون می‌ده. ۴۰۴ قبلی مال حالتیه که `CENTRAL_DOMAIN` خالیه
(پیش‌فرض `.env`/`.env.example`) — در اون حالت، طبق کامنت خودِ `routes/web.php`
(«همه‌ی تست‌های اصلی این حالت رو می‌بینن»)، نه روت placeholder نه روت ساب‌دامین اصلاً ثبت
می‌شن، فقط `Route::prefix('s/{salon_slug}')`. یعنی زدن دامنه‌ی خام بدون `/s/{slug}` واقعاً هیچ
روتی نداره — طراحی‌شده، نه باگ. تست مستندکننده: `BareDomainWithoutCentralDomainTest` (توی سوییت
اصلی، برخلاف `SubdomainRoutingTest` که فقط با `phpunit.subdomain.xml` اجرا می‌شه).

### ۴. جایگزینی قیمت‌های placeholder + فیچر جدید «سقف/قطع پیامک ماهانه»
ابوالفضل به‌جای یک عدد مستقیم، منطق قیمت‌گذاری رو داد: هزینه‌ی پیامک هر نوبت + پیامک‌های ورود +
زیرساخت رو حساب کن. ممیزی مستقیم کد نشون داد هر نوبت کامل‌شده ≈ ۵ پیامک منطقی (auto-confirm) یا
۶ تا (غیر auto-confirm)، و چون پیامک‌های فارسی واقعی طولانی‌ان (چندخطی + ایموجی)، هر کدوم معمولاً
۳-۵ «قطعه»ی واقعی یونیکد حساب می‌شه (اندازه‌گیری مستقیم روی متن‌های کد: میانگین ~۲۳ قطعه/نوبت).
قیمت هر قطعه (چون صفحه‌ی تعرفه‌ی Kavenegar دسترسی خودکار رو مسدود می‌کنه) از میانگین بازار پنل‌های
پیامکی ایران (۱۵۰-۲۹۰ تومان) تخمین زده شد. هزینه‌ی زیرساخت هم چون عدد واقعی نبود، از نرخ فعلی
بازار VPS ایران تخمین زده شد. منطق کامل (همه‌ی فرض‌ها صریح مستندن) توی docblock بالای
`config/billing.php`.

نتیجه: `۷۲۰,۰۰۰` / `۱,۹۹۰,۰۰۰` / `۳,۶۷۰,۰۰۰` / `۶,۶۱۰,۰۰۰` تومان (۱/۳/۶/۱۲ماهه) — جایگزین
placeholderهای قبلی (`۴۹۰,۰۰۰`/`۱,۳۵۰,۰۰۰`/`۲,۵۰۰,۰۰۰`/`۴,۵۰۰,۰۰۰`).

**فیچر جدید (تصمیم صریح ابوالفضل، همون لحظه که گفته شد پیاده‌سازی بشه)**: هر سالن یک سقف پیامک
ماهانه داره (`config('billing.sms_quota_per_month')`، پیش‌فرض ۱۵۰۰ — مشتق از ۲۰۰ نوبت×۶ +
۳۰۰ لاگین). بعد از اون، پیامک برای اون سالن (هر نوعی) قطع می‌شه و به ادمین‌های سالن + سوپرادمین
اطلاع داده می‌شه:
- `migrations`: `salons.sms_quota_per_month` (nullable، override دستی)، `salon_sms_usages`
  (salon_id + period 'Y-m' + used_count + notified_at)
- `SmsQuotaService`: منطق چک/ثبت/نوتیفای (فقط یک‌بار در هر ماه نوتیفای، نه هر پیامک بعدیِ
  مسدودشده)
- `SMSService::send()`/`sendTemplate()`: پارامتر جدید و اختیاری `$salonId` (انتهای امضا، سازگار
  با عقب) — قبل از تماس واقعی با Kavenegar چک می‌کنه؛ سهمیه تمام → API واقعی اصلاً صدا زده
  نمی‌شه
- `SmsQuotaExhaustedNotification`: به `Salon::admins()` + `role='super-admin'` می‌ره؛ SMS خودش
  عمداً بیرون از مسیر گیت‌شده‌ی سهمیه ارسال می‌شه
- `$salonId` به بالاترین‌ارزش‌ترین call siteها وصل شد: ۴ پیامک `BookingObserver`،
  `BookingNotification`، `BookingStatusUpdated`، `SendBookingReminderJob` (هر دو گیرنده)،
  `ReviewService::sendReviewRequest`، و هر ۳ Job پیامک ورود/تایید (Login/Phone/2fa)

تست: `SmsQuotaTest` (۸ تست، با Mockery spy روی کلاینت Kavenegar — تضمین می‌کنه بعد از اتمام
سهمیه API واقعی اصلاً لمس نمی‌شه، نه فقط پاسخش false می‌مونه).

⚠️ همه‌ی این اعداد (قیمت پیامک، هزینه‌ی زیرساخت) تخمینن، نه فاکتور واقعی — باید قبل از production
با صورت‌حساب واقعی Kavenegar/هاستینگ بازبینی بشن.

### نتیجه‌ی verify (روی `git am` کاملاً مستقل و از صفر، جدا از هر working copy)
- هفت پچ پشت‌سرهم بدون conflict اعمال شدن (شامل ۲ migration جدید)
- `composer install` + migration روی SQLite
- سوییت اصلی: **۱۰۶۹ pass، ۱ skip، صفر fail**
- `phpunit.subdomain.xml`: **۸ pass، صفر fail**

### تحویل
هفت پچ:
- `0001` تا `0003`: بستن نشتی `services.php`/`specialists.php` (ادامه‌ی نشست قبلی)
- `0004`: ممیزی implicit-binding مشتری
- `0005`: حذف صفحه‌ی مرده
- `0006`: مستندسازی ۴۰۴ دامنه‌ی اصلی
- `0007`: قیمت‌های جدید + فیچر سقف پیامک

### ⭐⭐ نشست ۲۰۲۶-۰۹-۲۰ (ادامه): پاک‌سازی روت تکراری + ممیزی بایندینگ + محور «۴. ثبت‌نام عمومی سالن»

⚠️ **محدودیت شبکه‌ی این نشست**: هم `repo.packagist.org` (۴۰۳) هم GitHub API (۴۰۳ rate-limit
برای بدون‌توکن؛ توکن ثبت‌شده در همین فایل «Bad credentials» — منقضی/باطل شده، باید جایگزین بشه)
مسدود بودن. یعنی نه `composer install` ممکن بود نه اجرای واقعی سوییت تست. هر سه مورد این نشست
فقط با خواندن دقیق کد + تحلیل رفتار داخلی Laravel (نه HTTP واقعی) تایید شدن — حتماً موقع اعمال
پچ‌ها روی XAMPP، کل سوییت (نه فقط تست‌های جدید) اجرا بشه.

**۱. روت هم‌URI تکراری specialists.availability/available-slots (یافته‌ی نشست قبل) — رفع شد.**
علت واقعی: `Illuminate\Routing\RouteCollection::addToCollections()` روت‌ها رو با کلید
`$method.$domainAndUri` نگه می‌داره؛ چون این دو روت با دقیقاً همون method+URI هم در
`public-specialists.php` (بدون auth) هم در `services.php` (auth-gated) ثبت می‌شدن، و
public-specialists.php زودتر لود می‌شه، ثبت دوم (services.php) بی‌صدا نسخه‌ی اول رو در جدول
dispatch overwrite می‌کرد — یعنی نسخه‌ی «عمومی» از اول واقعاً reachable نبود. تایید شد هیچ
Blade/JS واقعی این route name‌ها رو صدا نمی‌زنه (بوکینگ از `bookings.available-slots` جدا
استفاده می‌کنه) و Blade fallback (`view('specialists.availability')`) هم اصلاً وجود نداره.
نسخه‌ی مرده از public-specialists.php حذف شد؛ services.php تنها تعریف باقی موند (هم‌راستا با
تست‌های موجود که همه actingAs دارن). تست رگرسیون جدید:
`tests/Feature/Routing/SpecialistsAvailabilityRouteCollisionTest.php`.

**۲. ممیزی بایندینگ سراسری جدید — چیزی برای رفع نبود.** `RouteServiceProvider::configureModelBindings()`
با grep سراسری پروژه چک شد: هنوز دقیقاً همون ۴ تا `Route::bind()` قبلی
(`specialist`, `service`, `booking`, `user`)، هیچ بایندینگ جدیدی اضافه نشده. نیازی به تکرار
ممیزی implicit-binding قبلی نبود.

**۳. محور «۴. ثبت‌نام عمومی سالن (self-service)» — پیاده‌سازی اولیه.**

تصمیم معماری کلیدی: به‌جای migration/ستون جدید برای «سالن در انتظار پرداخت»، از رفتار از قبل
موجود `EnsureAdminSalonActive` سوءاستفاده‌ی عمدی شد — `SalonSignupService` سالن تازه‌ثبت‌شده رو
با `subscription_ends_at = now()->subSecond()` می‌سازه (از قبل «منقضی»، ولی `is_suspended=false`).
همون middleware موجود، بدون هیچ کد جدیدی، ادمین رو مستقیم به `admin.billing.index` هدایت می‌کنه
و اجازه‌ی هیچ بخش دیگه‌ای از پنل رو تا پرداخت واقعی نمی‌ده — و چون
`InvoiceService::markPaidFromGateway`/`SuperAdminService::renewSubscription` از قبل فالبک
«اگه subscription_ends_at گذشته، دوره از `now()` شروع می‌شه» رو دارن، اولین خرید آنلاین هم بدون
هیچ تغییری در کد billing موجود درست کار می‌کنه.

فایل‌های جدید/تغییریافته:
- `app/Http/Requests/SalonSignup/StoreSalonSignupRequest.php` — قوانین تقریباً کپی
  `StoreSalonRequest` سوپرادمین (slug/phone)، با `authorize()` همیشه true (فرم عمومیه) و
  `Rules\Password::defaults()` به‌جای صرفاً min:8.
- `app/Services/SalonSignup/SalonSignupService.php` — ساخت Salon + owner (is_admin=true،
  phone_verified_at=null) در یک تراکنش. `max_specialists_count` از
  `config('billing.default_max_specialists_count')` (پیش‌فرض ۳، env
  `DEFAULT_MAX_SPECIALISTS_COUNT`) گرفته می‌شه، نه از کاربر پرسیده می‌شه — یک مفهوم داخلی SaaS
  که یک بازدیدکننده‌ی عادی معنی‌اش رو نمی‌دونه؛ تغییرش بعداً هنوز فقط از طریق سوپرادمین
  (`SuperAdminController::update`) ممکنه، محدودیت از قبل موجود پروژه‌ست.
- `app/Http/Controllers/SalonSignup/SalonSignupController.php` — الگوی ثبت‌نام دو-مرحله‌ای
  (ثبت → OTP → ورود) کپی مستقیم از `CustomerRegisteredController` (همون
  `PhoneVerificationService`، همون کلیدهای session-based)؛ سه فرق: سالن هم همراه owner ساخته
  می‌شه، بعد از OTP موفق مستقیم به `admin.billing.index` هدایت می‌شه (نه صفحه‌ی خانه‌ی سالن)، و
  `owner_phone` چون هنوز سالنی وجود نداره سراسری بین `user_type='staff'` چک می‌شه (نه per-salon).
- `routes/web/salon-signup.php` — روت global (نه زیر `s/{salon_slug}`، نه پشت `central_domain`
  — این فیچر باید حتی بدون CENTRAL_DOMAIN هم در دسترس باشه)، هم‌تراز با `web/auth.php`، require
  شده مستقیم در `web.php`. `throttle:registration` روی هر سه POST (هم‌الگو با
  `routes/salon-auth.php`).
- `config/billing.php` + `.env.example`: کلید جدید `default_max_specialists_count` /
  `DEFAULT_MAX_SPECIALISTS_COUNT`.
- دو ویوی کاملاً self-contained (`resources/views/salon-signup/create.blade.php`,
  `verify.blade.php`) — بدون `@vite`، بدون `x-guest-layout` (اون به `route('home')` و
  `$currentSalonName` وابسته‌ست که هر دو فقط داخل یک درخواست `/s/{slug}` معنی دارن؛ این صفحه
  global و بیرون از هر سالنه) — دقیقاً هم‌الگو با `central/placeholder.blade.php`.
- `resources/views/central/placeholder.blade.php`: بج «به‌زودی: ثبت‌نام آنلاین سالن جدید» با یک
  CTA واقعی به `salon-signup.create` جایگزین شد.
- تست: `tests/Feature/SalonSignup/SalonSignupTest.php` — مسیر کامل موفق (تا لندینگ روی
  `admin.billing.index` با ریدایرکت‌شدن از بقیه‌ی پنل)، یکتایی slug/owner_phone، کد OTP نادرست،
  ارسال‌مجدد کد، و گارد صفحه‌ی verify بدون session در انتظار.

⚠️ **هنوز تصمیم‌گیری/کار نشده (برای نشست بعدی)، آگاهانه به تعویق افتاده، نه فراموش‌شده:**
- هیچ محدودیت نرخ (rate limit) روی خود `salon-signup.create`/GET نیست (فقط سه POST ثبت‌نام
  محدود شدن، دقیقاً هم‌الگو با salon-auth.php؛ `check-slug`/`check-phone` هم با `throttle:60,1`
  عمومی محدودن، نه یکی از rate limiterهای نام‌دار) — اگه spam واقعی دیده شد، باید بازبینی بشه.
- قیمت‌های config/billing.php و SMS_QUOTA_PER_MONTH همچنان تخمینی‌ان — بعد از اولین دوره‌ی
  واقعی فاکتور Kavenegar/هاستینگ حتماً بازبینی بشن.

### ⭐⭐ ادامه‌ی همون نشست (۲۰۲۶-۰۹-۲۰): توکن تازه + مورد ۴ (چک یکتایی زنده)

توکن GitHub PAT بالای این فایل با یک توکن تازه جایگزین و تست شد (`api.github.com` واقعاً جواب
داد) — GitHub API این‌بار در طول نشست کاملاً باز بود، برخلاف تلاش‌های قبلی همین نشست. با کلون
مستقیم `develop` (نه فقط zip آپلودی) تایید شد:
- پچ `0001` (پاک‌سازی روت تکراری) از قبل توسط ابوالفضل روی `develop` واقعی commit/push شده بود
  (`ff0c11c`) — یعنی همون پچی که این نشست تحویل داده بود، مستقیم و بدون تغییر اعمال شده.
- پچ‌های `0002` و `0003` (زیر) هم مستقیم روی همون HEAD واقعی `develop` (نه فقط baseline محلی
  این نشست) با `git am` تست و بدون conflict verify شدن.

**مورد ۴ («اگه لازم شد slug/owner_phone هم AJAX زنده بگیرن») انجام شد** — یادداشت نشست قبل
دقیقاً پیش‌بینی کرده بود که اگه این کار بشه، فرم سوپرادمین هم باید هم‌زمان آپدیت بشه تا دو الگوی
متفاوت توی پروژه نمونه؛ همین‌جا رعایت شد:
- دو endpoint عمومی جدید (بدون auth، چون فرم self-service اصلاً کاربر لاگین‌شده نداره):
  `GET salon-signup/check-slug`، `GET salon-signup/check-phone` — روی
  `SalonSignupController::checkSlug()`/`checkPhone()`. افشای «این slug/phone گرفته شده یا نه»
  چیز جدیدی رو لو نمی‌ده — همون چیزیه که خطای submit کامل فرم از قبل نشون می‌داد.
  `throttle:60,1` عمومی (نه SMS-related) چون این‌ها هیچ پیامکی نمی‌فرستن.
- **هر دو فرم** (`salon-signup/create.blade.php` self-service و
  `superadmin/salons/create.blade.php`) از همین دو endpoint مشترک استفاده می‌کنن — نه دو
  پیاده‌سازی جدا. Vanilla JS بدون جی‌کوئری/فریمورک؛ روی فرم سوپرادمین از `@push('scripts')`/
  `@push('styles')` داخل `@stack`های از قبل موجود `layouts/superadmin.blade.php` استفاده شد
  (اون layout هیچ JS bundle‌ای لود نمی‌کنه — فقط `@vite(['resources/css/app.css'])` — پس هیچ
  دام Blade @vite-timing‌ای اینجا مطرح نیست).
- ۷ تست جدید در همون `SalonSignupTest.php` (available/taken/invalid برای هر دو endpoint، +
  تایید اینکه شماره‌ی یک مشتری هیچ‌وقت مانع ثبت‌نام یک ادمین جدید با همون شماره نمی‌شه — طبق
  قانون scope=staff همون فایل).

### تحویل این نشست
سه پچ (هر سه هم روی baseline محلی این نشست هم مستقیم روی `develop` واقعی GitHub verify شدن):
- `0001`: پاک‌سازی روت تکراری specialists.availability/available-slots + تست رگرسیون (از قبل
  توسط ابوالفضل push شده، اینجا فقط برای تاریخچه)
- `0002`: فیچر کامل «ثبت‌نام عمومی سالن (self-service)»
- `0003`: چک یکتایی زنده‌ی slug/phone روی هر دو فرم (مورد ۴)

### قدم‌های باز برای نشست بعدی

✅ **محور «۴. ثبت‌نام عمومی سالن (self-service)» کاملاً تکمیل و end-to-end تایید شد**
(۲۰۲۶-۰۹-۲۰، توسط خودِ ابوالفضل روی محیط واقعی محلی، با `hosts` دستی — نه nip.io که روی شبکه‌ی
او resolve نمی‌شد، ربطی به کد پروژه نداشت):
- مسیر اصلی: `rasta-app.test:8000` (دامنه‌ی مرکزی + CTA) → `/salon-signup` (فرم، هدایت درست) →
  کد OTP از `laravel.log` (چون `KAVENEGAR_SEND_IN_LOCAL=true`) → ثبت‌نام کامل → ورود مستقیم به
  `admin.billing.index` (نه داشبورد کامل) → پرداخت واقعی روی سندباکس زرین‌پال → تمدید اشتراک →
  دسترسی کامل به پنل. یعنی هم `SalonSignupService` (`subscription_ends_at` از قبل منقضی) هم
  `EnsureAdminSalonActive` هم `AdminBillingController`/`SubscriptionPaymentService` سندباکس،
  هر سه با هم دقیقاً طبق طراحی کار کردن — بدون هیچ نیاز به composer/تست خودکار برای این تایید.
- چک زنده‌ی slug/phone (پچ `0003`) هم تایید شد — با یک تاخیر آموزنده: اولین‌بار هیچ پیامی
  ظاهر نمی‌شد؛ علت **پچ `0003` هنوز apply نشده بود** (فقط `0001`/`0002` زده شده بودن)، نه باگی
  توی کد. بعد از `git am --keep-cr 0003-feat-salon-signup-live-availability-checks.patch`،
  پیام «✓ آزاده»/«✗ گرفته شده» زیر هم slug هم شماره موبایل روی فرم self-service درست کار کرد
  (تستِ خودِ ابوالفضل؛ فرم سوپرادمین جدا تست نشده ولی از همون دو endpoint مشترک استفاده می‌کنه).

⚠️ **درس این نشست، برای همه‌ی نشست‌های بعدی**: وقتی چند پچ پشت‌سر هم تحویل داده می‌شه (مثلاً
۰۰۰۱/۰۰۰۲/۰۰۰۳)، اعمال‌نشدنِ یکی از وسط هیچ خطای واضحی نمی‌ده — بقیه‌ی سیستم عادی کار می‌کنه،
فقط همون تیکه‌ی خاص «هیچی نشون نمی‌ده». قبل از گزارش «فلان فیچر کار نمی‌کنه»، اول با
`git log --oneline` چک کن همه‌ی پچ‌های اون نشست واقعاً apply شدن یا نه.

- سوییت تست خودکار (`php artisan test` / `vendor/bin/phpunit`) هنوز خود ابوالفضل روی XAMPP
  اجرا نکرده — تست دستی بالا جایگزین کامل این مورد نیست، فقط confidence بالایی می‌ده.
- قیمت‌های config/billing.php و SMS_QUOTA_PER_MONTH تخمینی‌ان — بعد از اولین دوره‌ی واقعی
  فاکتور Kavenegar/هاستینگ حتماً بازبینی بشن.

### ⭐⭐ ادامه‌ی همون نشست: اجرای واقعی سوییت (`php artisan test`) — ۱۰۸۶ pass، ۱ fail، ۱ skip

ابوالفضل کل سوییت رو روی XAMPP خودش اجرا کرد. نتیجه: **۱۰۸۶ pass، ۱ fail، ۱ skip (۲۴۳۳
assertion)** — شامل تمام تست‌های تازه‌ی این نشست:
- `SpecialistsAvailabilityRouteCollisionTest`: هر ۴ تست ✓ (پچ ۰۰۰۱)
- `SalonSignupTest`: هر ۱۵ تست ✓ (پچ‌های ۰۰۰۲/۰۰۰۳، شامل هر ۷ تست check-slug/check-phone)

**۱ fail — محیطی بود، نه باگ کد** — رفع شد (پچ `0004`): `BareDomainWithoutCentralDomainTest`
با `assertEmpty(config('app.central_domain'), ...)` شروع می‌شه، ولی `phpunit.xml` هیچ‌وقت
`CENTRAL_DOMAIN` رو override نمی‌کرد، پس مقدار واقعی `.env` محلی ابوالفضل
(`CENTRAL_DOMAIN=rasta-app.test`، که خودش برای تست دستی محور ۴ در همین نشست لازم بود) رو
می‌خوند و تست fail می‌شد — دقیقاً یک وابستگی پنهان سوییت اصلی به `.env` محلی توسعه‌دهنده، نه یک
رگرسیون واقعی. فیکس: `<env name="CENTRAL_DOMAIN" value=""/>` به بخش `<php>` در `phpunit.xml`
اضافه شد — دقیقاً هم‌الگو با کاری که `phpunit.subdomain.xml` از قبل در جهت عکس انجام می‌ده.

**۱ skip** — از قبل و بی‌ربط به این نشست: `SpecialistControllerTest::top rated only includes
specialists meeting the rating and count threshold` چون `topRated()` از یک تابع MySQL-only
استفاده می‌کنه که روی SQLite (سوییت تست) کار نمی‌کنه — مستند و قصدی، نه چیزی که این نشست باز
کرده باشه.

### تحویل این نشست (تکمیلی)
- `0004`: پین‌کردن `CENTRAL_DOMAIN=""` در `phpunit.xml` برای سوییت اصلی

### قدم‌های باز برای نشست بعدی (به‌روز نهایی)
✅ **بعد از اعمال پچ `0004`، ابوالفضل دوباره کل سوییت رو اجرا کرد: `1087 passed، 1 skipped،
صفر fail` (۲۴۳۴ assertion، ۱۹۷ ثانیه)** — یعنی همون fail محیطی `BareDomainWithoutCentralDomainTest`
هم رفع شد، هیچ fail واقعی باقی نمونده. با این، کل این نشست (محور ۴ کامل + پاک‌سازی روت تکراری +
ممیزی بایندینگ + چک زنده‌ی slug/phone) هم با تست خودکار هم با تست دستی end-to-end صد-در-صد
تایید شده.

تنها مورد باز باقی‌مانده:
- قیمت‌های config/billing.php و SMS_QUOTA_PER_MONTH تخمینی‌ان — بعد از اولین دوره‌ی واقعی
  فاکتور Kavenegar/هاستینگ حتماً بازبینی بشن (آگاهانه به تعویق افتاده، خودِ ابوالفضل).

---

## ⭐⭐⭐ فاز جدید بزرگ (تصمیم ۲۰۲۶-۰۹-۲۰): معرفی Repository Pattern + حذف کامل کامنت‌ها

### زمینه و تصمیم
ابوالفضل تشخیص داد که پروژه با اینکه از FormRequest برای ولیدیشن و از لایه‌ی Service برای
منطق تجاری استفاده می‌کنه (که با اصول SOLID/separation-of-concerns هم‌راستاست)، هیچ لایه‌ی
Repository نداره — کوئری‌های واقعی دیتابیس (`Model::where/find/create/update/paginate/...` و
`DB::table`/`DB::raw`) مستقیم داخل کنترلرها و سرویس‌ها نوشته شدن. تصمیم: این کوئری‌ها به یک
لایه‌ی Repository جدا منتقل بشن (Repository ← Service ← Controller). **هم‌زمان**، تصمیم دوم:
تمام کامنت‌های فایل‌ها (فارسی و انگلیسی) حذف بشن.

⚠️ **نکته‌ی مهم برای آگاهی (نه مخالفت، فقط یادداشت‌شده تا آگاهانه باشه)**: بخش زیادی از کامنت‌های
این پروژه (علامت ⭐، در ۱۴۲ فایل) دقیقاً همون «حافظه‌ی نهادی» بین نشست‌هاست — مستندسازی باگ‌های
واقعی پیدا‌شده، چرایی تصمیم‌های غیربدیهی، و هشدارهای «این‌کارو نکن چون قبلاً امتحان شده و باگ
داده». حذف کاملشون از کد یعنی این تاریخچه فقط همین‌جا (`Rasta_unified_prompt.md`) باقی می‌مونه،
نه کنار خودِ کدی که به اون دلیل نوشته شده. تصمیم نهایی با ابوالفضله؛ این فقط یادداشت شد که در
آینده اگه یک باگ مشابه گذشته دوباره تکرار شد، دلیلش این حذف کامنت‌ها نباشه.

### ممیزی اولیه‌ی پروژه (انجام‌شده همین نشست، برای این فازبندی)
- `app/Http/Controllers`: ۷۸ فایل — `app/Services`: ۳۶ فایل — `app/Models`: ۴۴ فایل —
  `app/Http/Requests`: ۶۲ فایل — کل `app/`: ۳۳۷ فایل PHP
- کوئری‌های مستقیم Eloquent (`::where/find/create/update/first/all/paginate/withCount/with(/
  orderBy/whereHas/latest/withoutGlobalScope`) در کنترلرها: **۴۳ فایل، ۱۲۷ occurrence**؛ در
  سرویس‌ها: **۲۴ فایل، ۱۰۹ occurrence**
- `DB::table`/`DB::raw`/`DB::select`: **۴۹ occurrence** در کل `app/`
- کامنت (خط شروع‌شده با `//`، `#`، `*`، `/*`) در `app/`: **~۲۸۰۷ خط**، در **۱۴۲ فایل** علامت ⭐
- هیچ پوشه/الگوی Repository از قبل در پروژه وجود نداره (بررسی شد — صفر نتیجه)
- `SupportTicket`/`SupportTicketMessage` مدل دارن ولی هیچ Controller/route فعالی بهشون وصل
  نیست — یا فیچر ناتمام رهاشده‌ست یا کاملاً مرده؛ باید در فاز جاروب نهایی بررسی و تصمیم‌گیری بشه
  (حذف کامل، یا واقعاً پیاده‌سازی، یا نادیده گرفتن).

### قرارداد Repository (باید در فاز ۰ دقیقاً همین‌طور اجرا بشه)
- **داخل Repository**: هر چیزی که مستقیم روی Model کوئری می‌زنه و به دیتابیس می‌رسه —
  `where/find/create/update/delete/paginate/with/withCount/orderBy/whereHas/latest/
  withoutGlobalScope`، `DB::table`/`DB::raw`.
- **داخل Model می‌مونه** (منتقل نمی‌شه): تعریف relationship methods (`hasMany`/`belongsTo`/...)،
  accessor/mutator، scope‌های تعریف‌شده‌ی خودِ مدل (`scopeActive()` و مشابه — چون این‌ها رفتار
  خودِ مدلن، نه یک کوئری بیرونی روی مدل)، cast‌ها، `$fillable`.
- **داخل Service می‌مونه** (منتقل نمی‌شه): منطق تجاری، `DB::transaction`، هماهنگی چند
  Repository/Model با هم، dispatch کردن Job/Event/Notification، محاسبات (مثلاً
  `WalletSetting::calculatePrepaymentAmount`، `SuperAdminService::addSubscriptionPeriod`).
- هر Repository یک Interface (`App\Repositories\Contracts\XRepositoryInterface`) و یک
  پیاده‌سازی (`App\Repositories\Eloquent\XRepository`) داره؛ bind شدن در یک
  `RepositoryServiceProvider` جدید. Serviceها به Interface تزریق می‌شن (constructor injection)،
  نه به پیاده‌سازی مستقیم — دقیقاً همون الگوی موجود پروژه برای Serviceها.
- یک `BaseRepository` عمومی (متدهای مشترک: `find`, `create`, `update`, `delete`, `paginate`)
  که Repositoryهای خاص از اون extend می‌کنن و متدهای دامنه‌محورِ خودشون رو اضافه می‌کنن (مثلاً
  `BookingRepository::findOverlapping(...)`).

### فازبندی (هر فاز = یک یا چند پچ مستقل، verify‌شده، تحویل‌شده — دقیقاً همون روال patch delivery
همیشگی پروژه). هر فاز هم‌زمان کوئری‌های همون دامنه رو به Repository منتقل می‌کنه **و** کامنت‌های
همون فایل‌های لمس‌شده رو حذف می‌کنه — تا هر فایل فقط یک‌بار دست بخوره.

| فاز | نام | دامنه (مدل‌ها) | کنترلر/سرویس‌های اصلی درگیر | وضعیت |
|---|---|---|---|---|
| ۰ | R-Repo-Foundation | — (زیرساخت) | `BaseRepository`, Contracts, `RepositoryServiceProvider`, pilot روی `Category` | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۰) |
| ۱ | R-Repo-Services | BeautyService (Category قبلاً در فاز ۰ کامل شد) | ServiceController, AdminServiceController | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۰) |
| ۲ | R-Repo-Specialists | Specialist, SpecialistSchedule, Leave, Holiday | SpecialistController (user+admin), AdminSpecialistScheduleController, AdminLeaveController, AdminHolidayController, SpecialistScheduleSelfService*, SpecialistLeave* | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۰) |
| ۳ | R-Repo-Bookings | Booking | BookingController, BookingReservationController, BookingRescheduleController, BookingAvailabilityController, AdminBookingController, BookingService | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۰) |
| ۴ | R-Repo-Payments | Payment, Invoice | PaymentController, SecurePaymentController, AdminBillingController, PaymentService, SecurePaymentService, InvoiceService, SubscriptionPaymentService | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۰) |
| ۵ | R-Repo-Wallet | AdminWallet(+Transaction), SpecialistWallet, UserWallet(+Transaction), WithdrawalRequest, WalletSetting | AdminWalletController, SpecialistWalletController, SpecialistWithdrawalController, AdminWithdrawalController, SpecialistIbanController, UserWalletController, WalletAdminService, SpecialistWalletService | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۰) |
| ۶ | R-Repo-Loyalty | LoyaltyPoint, LoyaltySetting, Reward, Loyalty, DiscountCode, DiscountUsage | LoyaltyController, AdminLoyaltyPointsController, AdminLoyaltyRewardController, AdminDiscountCodeController, BookingDiscountController, LoyaltyService, LoyaltyAdminService, DiscountCalculator | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۷ | R-Repo-Content | BlogPost, BlogCategory, GalleryImage, Announcement, Review, ReviewToken | BlogController, AdminBlogController, AdminBlogCategoryController, AdminGalleryController, AnnouncementController, AdminAnnouncementController, ReviewController, AdminReviewController, SpecialistReviewController | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۸ | R-Repo-Users-Auth | User, Role, Permission, salon_admins | RegisteredUserController, AuthenticatedSessionController, PasswordReset*, PhoneVerification*, TwoFactor*, CustomerRegistered/Authenticated*, AdminUserManagementController, AdminRoleController, AdminPermissionController, AdminUserService | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۹ | R-Repo-Security | SecurityLog, SecuritySetting | SecurityController (user), AdminSecurityController, SecurityLogService | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۱۰ | R-Repo-Salon | Salon, SalonSmsUsage | SuperAdminController, SuperAdminService, SalonSignupController/Service | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۱۱ | R-Repo-Reports-Notif | ReportExport, ScheduledReport(+Run), NotificationSetting, UserNotification, UserReportSetting | AdminReportsController, AdminReportExportController, AdminNotificationController, AdminNotificationSettingController, SpecialistNotificationController, ReportCacheService, SmsQuotaService | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۱۲ | R-Repo-Sweep | SupportTicket(+Message) + هر مدل/فایل جامانده | تصمیم‌گیری در مورد SupportTicket + هر Controller/Service که در فازهای بالا نیومده | ✅ انجام‌شده جزئی (۲۰۲۶-۰۹-۲۱) — به یادداشت پایان این فاز نگاه کن |
| ۱۲b | R-Repo-BookingSweep (فاز جدید، کشف‌شده در فاز ۱۲) | Specialist, Booking, BeautyService | هر `Controller`/`Service`ای که این سه مدل رو مستقیم کوئری می‌زنه — فهرست کامل در یادداشت پایان فاز ۱۲ | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۱۳ | R-Repo-CommentSweep | — | حذف کامنت از فایل‌های لمس‌نشده در فازهای بالا: `routes/*`, `config/*`, `database/migrations/*`, `app/Providers/*`, factories, seeders | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |
| ۱۴ | R-Repo-Final | — | اجرای کامل `php artisan test` بعد از همه‌ی فازها + گزارش نهایی + جمع‌بندی این جدول در پرامپت | ✅ انجام‌شده (۲۰۲۶-۰۹-۲۱) |

### روال هر فاز (مو‌به‌مو، تکرار همون روالی که این نشست‌ها همیشه داشتن)
۱. برای اون دامنه، هر فایل کنترلر/سرویس رو دقیق بخون و هر کوئری مستقیم رو شناسایی کن.
۲. Repository (+ Interface) مربوطه رو بساز، کوئری‌ها رو منتقل کن، Controller/Service رو با
تزریق Repository (نه Model مستقیم) بازنویسی کن.
۳. کامنت‌های همون فایل‌های لمس‌شده رو حذف کن (فارسی و انگلیسی، `//`، `#`، `/* */`، از جمله
داک‌بلاک‌های PHPDoc).
۴. تست‌های موجود همون دامنه رو اجرا کن (بدون تغییر رفتار — رفکتور خالص، نه تغییر فیچر) و مطمئن
شو هیچ‌کدوم fail نشدن. اگه لازم شد (مثلاً برای متد جدید Repository) تست واحد جدید اضافه کن.
۵. پچ (`git format-patch`) بساز، روی یک `git am` مستقل verify کن، تحویل بده.
۶. وضعیت اون سطر جدول بالا رو از ⬜ به ✅ تغییر بده و این فایل رو دوباره تحویل بده.

### شروع در نشست/چت بعدی
چون این یک رفکتور بزرگ و چندفازه‌ست، هر نشست باید دقیقاً روی **یک فاز** (نه بیشتر) تمرکز کنه —
دقیقاً مثل قدم‌های محور‌های قبلی این پروژه.

---

## نشست تکمیل‌شده: فاز ۰ (R-Repo-Foundation) — ۲۰۲۶-۰۹-۲۰

### وضعیت دسترسی GitHub این نشست
توکن GitHub موجود در این فایل با `401 Unauthorized` مواجه شد (منقضی/نامعتبر) — طبق روال
بخش «روال دسترسی به GitHub»، مستقیماً از فایل زیپ آپلودی کار شد. توکن نیاز به تمدید داره.

### محیط
PHP 8.3 + Composer از صفر روی سندباکس نصب شد (composer.phar از
`github.com/composer/composer/releases`، نه getcomposer.org — طبق نکته‌ی مستندشده‌ی قبلی).
پوشه‌ی `storage/*` کلاً در زیپ آپلودی وجود نداشت (نه در `.gitignore` هم نبود) — از صفر با
ساختار استاندارد لاراول بازسازی شد. سوییت تست کامل با `phpunit.xml` (SQLite in-memory) قبل از
شروع اجرا شد: **۱۰۸۷ passed / ۱ skipped / ۱ error محیطی-تصادفی (bookings.active_slot_key
UNIQUE collision در `AdminReportExportTest`، به‌خاطر داده‌ی رندوم فکتوری، نامرتبط با این نشست و
غیرقابل تکرار در اجرای بعدی)** — این baseline قبل از هر تغییری ثبت شد.

### تصمیم دامنه (نکته‌ی مهم برای فاز ۱)
درخواست این نشست هم زیرساخت Repository Pattern و هم پیاده‌سازی pilot روی **کل دامنه‌ی
Category** رو خواسته بود — یعنی `AdminCategoryController` و `CategoryService` هر دو در همین فاز
۰ به‌طور کامل به Repository منتقل شدن (نه فقط نمونه‌ی جزئی). جدول فازبندی بالا به‌روز شد: فاز ۱
(`R-Repo-Services`) دیگه نیازی به دست‌زدن به `AdminCategoryController`/`CategoryService` نداره و
فقط باید روی دامنه‌ی `BeautyService` (`ServiceController`, `AdminServiceController`) تمرکز کنه.

### کار انجام‌شده
- `app/Repositories/Contracts/RepositoryInterface.php` — قرارداد عمومی
  (`all/find/findOrFail/create/update/delete/paginate`)
- `app/Repositories/Eloquent/BaseRepository.php` — پیاده‌سازی عمومی همون متدها روی
  `protected Model $model`
- `app/Repositories/Contracts/CategoryRepositoryInterface.php` — extends `RepositoryInterface`
  + `getMaxOrder`, `updateOrder`, `getTree`, `getActiveTree`, `paginateWithFilters`,
  `getParentOptions`, `getOptionsExcept`
- `app/Repositories/Eloquent/CategoryRepository.php` — pilot، extends `BaseRepository`
- `app/Providers/RepositoryServiceProvider.php` — bind `CategoryRepositoryInterface` →
  `CategoryRepository`؛ در `bootstrap/providers.php` ثبت شد
- `app/Services/CategoryService.php` — بازنویسی کامل: تمام کوئری‌های مستقیم `Category::`
  حذف و با `CategoryRepositoryInterface` (constructor injection) جایگزین شدن؛ منطق تجاری
  (`DB::transaction`, `DB::beginTransaction/commit/rollBack`, `Log::info/error`) دست‌نخورده موند
  (طبق قرارداد Repository). کامنت‌ها حذف شدن (این فایل از قبل کامنت نداشت).
- `app/Http/Controllers/Admin/Category/AdminCategoryController.php` — بازنویسی کامل:
  کوئری‌های خواندنی (`index`, `create`, `show`, `edit`, `toggleStatus`, `destroy`) از
  `CategoryRepositoryInterface` استفاده می‌کنن؛ عملیات نوشتن (`store`, `update`, `toggleStatus`,
  `destroy`) همچنان از طریق `CategoryService` می‌رن. کامنت‌ها حذف شدن (این فایل هم از قبل
  کامنت نداشت).
- **عمداً دست‌نخورده موند**: `AdminSpecialistController.php` (خط‌های `Category::with('services')`)
  و `app/Http/Controllers/User/ServiceController.php` (خط `Category::all()`) — این دو فایل به
  فازهای ۲ و ۱ تعلق دارن و طبق قانون «هر فایل فقط یک‌بار دست بخوره در فاز خودش»، اینجا لمس
  نشدن؛ در فازهای مربوطه‌شون باید Repository تزریق بشه.

### تست و وریفای
- `tests/Feature/Admin/AdminCategoryTest.php`: ۱۵/۱۵ سبز، قبل و بعد یکسان
- Laravel Pint: `PASS` روی همه‌ی فایل‌های جدید/لمس‌شده
- کل سوییت بعد از تغییر: **۱۰۸۷ passed / ۱ skipped / صفر fail** (همون خطای تصادفی محیطی این بار
  اصلاً رخ نداد — تأیید می‌کنه که به این نشست ربطی نداشت)
- پچ (`0001-feat-repo-R-Repo-Foundation-Repository-Pattern-infra.patch`) روی یک `git am`
  مستقل (کلون جدا از commit پایه) با `--keep-cr` تست شد — بدون conflict اعمال شد، و
  `AdminCategoryTest` روی همون کلون هم ۱۵/۱۵ سبز بود.

### قدم‌های باز برای نشست بعدی
فاز ۱ (`R-Repo-Services`) رو شروع کن — این‌بار فقط دامنه‌ی `BeautyService`: یک
`BeautyServiceRepositoryInterface` + `BeautyServiceRepository` بساز (از همون `BaseRepository`
extend کن)، `ServiceController` (کاربر) و `AdminServiceController` رو بازنویسی کن، و همون‌جا
خط‌های باقی‌مانده‌ی `Category::` در این دو فایل (اگه بودن) رو هم با تزریق
`CategoryRepositoryInterface` موجود جایگزین کن. `AdminSpecialistController.php` رو دست نزن —
اون فاز ۲ (`R-Repo-Specialists`) هست.

---

## نشست تکمیل‌شده: فاز ۱ (R-Repo-Services) — ۲۰۲۶-۰۹-۲۰

### وضعیت دسترسی GitHub این نشست
ابوالفضل توکن جدید داد؛ `api.github.com` تست شد (`200 OK`) و کل نشست مستقیماً از `develop`
(commit پایه `0ad76e8`) کلون و کار شد — طبق روال، نه از زیپ. پچ فاز ۰ (که قبلاً فقط روی زیپ
verify شده بود) اینجا مجدداً روی `develop` واقعی با `git am` اعمال و verify شد (بدون conflict)،
و پچ خروجی فاز ۰ هم با base واقعی `develop` بازتولید شد (محتوا یکسان، فقط parent commit درست).
دیف کامل بین زیپ قبلی و `develop` واقعی چک شد — هیچ تفاوت مرتبطی نبود (فقط دو تا پوشه‌ی زبدی
اشتباهی از نشست قبل و فایل‌های build/vendor که gitignore شدن).

### کار انجام‌شده
- `app/Repositories/Contracts/BeautyServiceRepositoryInterface.php` — extends
  `RepositoryInterface` + `paginateForIndex`, `paginateWithCategory`, `getRelated`
- `app/Repositories/Eloquent/BeautyServiceRepository.php` — extends `BaseRepository`
- `RepositoryServiceProvider` به‌روز شد: `BeautyServiceRepositoryInterface` →
  `BeautyServiceRepository` هم bind شد
- `ServiceController` (کاربر): `index`/`list`/`show` دیگه مستقیم روی `BeautyService::`/
  `Category::` کوئری نمی‌زنن — از `BeautyServiceRepositoryInterface` و
  `CategoryRepositoryInterface` (فاز ۰، از قبل موجود) استفاده می‌کنن
- `AdminServiceController`: `index`/`store`/`update`/`destroy` از
  `BeautyServiceRepositoryInterface` استفاده می‌کنن؛ `CategoryService::getCategorySelectOptions()`
  دست‌نخورده موند (از قبل از طریق `CategoryRepository` فاز ۰ می‌ره)
- کامنت‌های هر دو کنترلر حذف شدن

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- `$service->specialists()->with(...)->get()` در `ServiceController::show()` — کوئری روی
  نمونه‌ی از قبل route-model-bind شده، نه کلاس استاتیک `Model`؛ دقیقاً همون مرزی که در فاز ۰ برای
  `$category->load(...)` رعایت شد
- `app/Models/BeautyService.php` (متدهای استاتیک `latest()`/`paginate()` که خودشون Eloquent رو
  override می‌کنن) — هیچ‌کدوم از دو کنترلر این فاز صداشون نمی‌زنن؛ تنها مصرف‌کننده‌هاشون
  (`DashboardController`, `HomeController`) به فاز‌های دیگه تعلق دارن و اینجا لمس نشدن
- `AdminSpecialistController.php` (`Category::with('services')`) — فاز ۲

### تست و وریفای
- تست‌های دامنه (`AdminServiceTest`, `ServiceControllerTest`, `CrossSalonServiceLeakTest`,
  `AdminCategoryTest`): ۳۳/۳۳ سبز، قبل و بعد یکسان
- Laravel Pint: `PASS` روی همه‌ی فایل‌های جدید/لمس‌شده (`--test` روی کل ریپو هم چک شد — بقیه‌ی
  ایرادها همه از قبل و در فایل‌های لمس‌نشده‌ی این فاز بودن)
- کل سوییت بعد و قبل: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر رفتاری
- هر دو پچ (۰۰۰۱ فاز ۰ + ۰۰۰۲ فاز ۱) پشت‌سرهم روی یک `git am` مستقل (کلون جدا از `0ad76e8`)
  تست شدن — بدون conflict اعمال شدن، و کل سوییت روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۲ (`R-Repo-Specialists`) رو شروع کن: دامنه‌ی `Specialist`, `SpecialistSchedule`, `Leave`,
`Holiday` — `SpecialistController` (کاربر+ادمین)، `AdminSpecialistScheduleController`،
`AdminLeaveController`، `AdminHolidayController`، `SpecialistScheduleSelfService*`،
`SpecialistLeave*`. توجه: `AdminSpecialistController.php` دو خط `Category::with('services')`
داره — با تزریق `CategoryRepositoryInterface` موجود (فاز ۰) جایگزینش کن، نیازی به متد جدید در
Repository نیست چون `with('services')->get()` فقط یک `all()` با eager-load سادست (یا در صورت
نیاز یک متد کوچیک به `CategoryRepositoryInterface` اضافه کن).

---

## نشست تکمیل‌شده: فاز ۲ (R-Repo-Specialists) — ۲۰۲۶-۰۹-۲۰

### دامنه‌ی فایل‌ها (فراتر از لیست جدول)
جدول فازبندی صراحتاً فقط `SpecialistController` (کاربر+ادمین)،
`AdminSpecialistScheduleController`، `AdminLeaveController`، `AdminHolidayController`،
`SpecialistScheduleSelfService*` (=`SpecialistProfileController::schedule/updateSchedule`) و
`SpecialistLeave*` (=`SpecialistLeaveController`) رو نام برده بود. در عمل، `AdminSpecialistLeaveController`
(کنترلر جدای ادمین برای مرخصیِ per-specialist، مسیرش `admin/specialists/{id}/leaves`) هم چون
مستقیماً به مدل `Leave` (دامنه‌ی همین فاز) کوئری می‌زد، به همین فاز اضافه شد — جدول جدا اسمش رو
نیاورده بود ولی از نظر دامنه‌ی مدل دقیقاً همین‌جا تعلق داره.

### قاعده‌ی مرز نهایی‌شده (برای فازهای بعدی هم صادقه)
دو تا نکته‌ی مهم درباره‌ی مرز «کجا Repository، کجا نه» که این فاز نهایی‌شون کرد:
1. **متدهای کسب‌وکاری خودِ Model که روی `$this` عمل می‌کنن** (مثل
   `Specialist::getAvailableSlots/getMonthAvailability`, `Leave::approve/reject`) — این‌ها رفتار
   خودِ مدلن (analogous به scope)، نه یک کوئری کنترلر/سرویس؛ دست‌نخورده می‌مونن حتی اگه داخلشون
   `$this->schedules()->where(...)` بزنن.
2. **کوئری‌های رابطه‌ای (`$parent->relation()->...`) که مدل مقصدشون دامنه‌ی همین فاز یا فازهای
   قبلی تکمیل‌شده‌ست** (اینجا: `SpecialistSchedule`, `Leave`, `Holiday` که همین فاز Repository
   گرفتن) — این‌ها هم به Repository منتقل می‌شن، نه فقط کوئری‌هایی که از `Model::` استاتیک شروع
   می‌شن؛ در غیر این صورت دقیقاً همون کوئری‌های تکراری (مثل الگوی delete+recreate شیفت که در ۲
   کنترلر جدا تکرار شده بود) دوباره تکرار می‌مونن. اما اگه مدل مقصد دامنه‌ی یک فاز **آینده**‌ست
   (اینجا: `Booking` فاز ۳، `User` فاز ۸)، دست‌نخورده می‌مونه — همون فاز خودش حلش می‌کنه.

### کار انجام‌شده
- `SpecialistRepositoryInterface` + `SpecialistRepository`: `paginateWithFilters`,
  `searchPaginated`, `getTopRated`, `findByPhone`, `getSalonIdIgnoringScopes`
- `SpecialistScheduleRepositoryInterface` + `SpecialistScheduleRepository`:
  `getGroupedBySpecialist`, `replaceForSpecialist` (الگوی delete+recreate که بین
  `AdminSpecialistScheduleController` و `SpecialistProfileController::updateSchedule` عیناً
  تکرار شده بود، حالا در یک متد repository)
- `LeaveRepositoryInterface` + `LeaveRepository`: `paginateWithFilters` (لیست سراسری ادمین)،
  `paginateForSpecialist` (بین ادمین per-specialist و خودِ متخصص مشترک)، `getPending`،
  `createForSpecialist`، `hasOverlappingApprovedLeave`، `hasApprovedLeaveOnDate`
- `HolidayRepositoryInterface` + `HolidayRepository`: `getForSpecialist`,
  `getUpcomingForSpecialist`, `findOnDate`, `existsOnDate`, `createForSpecialist`
- `RepositoryInterface`/`BaseRepository`: متد عمومی `count()` اضافه شد (برای چک سقف تعداد متخصص)
- `CategoryRepositoryInterface`/`CategoryRepository`: متد `getWithServices()` اضافه شد — رفع
  بدهی باقی‌مانده از فاز ۰/۱ (`Category::with('services')->get()` در `AdminSpecialistController`)
- همه‌ی ۴ binding جدید در `RepositoryServiceProvider` ثبت شدن
- کنترلرها/سرویس‌های بازنویسی‌شده: `SpecialistController` (کاربر)، `AdminSpecialistController`،
  `AdminSpecialistService`، `AdminSpecialistScheduleController`، `AdminLeaveController`،
  `LeaveService`، `SpecialistLeaveController`، `AdminSpecialistLeaveController`،
  `AdminHolidayController`، `SpecialistProfileController` (`update`/`schedule`/`updateSchedule`)
- کامنت‌های همه‌ی فایل‌های لمس‌شده حذف شدن — شامل دو تا PHPDoc بلاک `@return array{...}` در
  `AdminSpecialistService`/`LeaveService` (طبق سیاست پروژه: حذف کامنت یعنی حتی PHPDoc)

### باگ رفتاری که موقع رفکتور کشف و جلوگیری شد (نه از قبل بوده، حین همین فاز)
`SpecialistController::search()` (کاربر) از `$request->has('name'/'service_id'/'sort')` استفاده
می‌کرد (چک وجود کلید، نه truthiness). نسخه‌ی اول Repository من اشتباهاً از `!empty($filters[...])`
استفاده کرده بود که یک تغییر رفتار ظریف می‌بود (مثلاً `?name=` با مقدار خالی دیگه فیلتر نمی‌شد).
قبل از commit با دقت چک و به `array_key_exists`/`has()`-معادل اصلاح شد تا رفتار عیناً یکی بمونه.

### تست و وریفای
- تست‌های دامنه (۱۳ فایل: `AdminHolidayTest`, `AdminLeaveTest`, `AdminSpecialistLeaveTest`,
  `AdminSpecialistPhoneNormalizationTest`, `AdminSpecialistQuotaTest`, `AdminSpecialistScheduleTest`,
  `AdminSpecialistTest`, `SpecialistAvailabilityTest`, `SpecialistCommissionRateTest`,
  `SpecialistsAvailabilityRouteCollisionTest`, `SpecialistScheduleSelfServiceTest`,
  `SpecialistSelfServiceAuthorizationTest`, `ServiceControllerTest` کاربر): **۱۰۷/۱۰۷ (۱ skip)** سبز
- Laravel Pint: `PASS` روی همه‌ی ۲۵ فایل جدید/لمس‌شده
- کل سوییت: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر رفتاری، قبل و بعد یکسان
- هر سه پچ (۰۰۰۱+۰۰۰۲+۰۰۰۳) پشت‌سرهم روی یک `git am` مستقل (کلون جدا از `0ad76e8`) تست شدن —
  بدون conflict، و کل سوییت روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۳ (`R-Repo-Bookings`) رو شروع کن: دامنه‌ی `Booking` — `BookingController`,
`BookingReservationController`, `BookingRescheduleController`, `BookingAvailabilityController`,
`AdminBookingController`, `BookingService`. طبق قاعده‌ی نهایی‌شده‌ی بالا (بخش «قاعده‌ی مرز
نهایی‌شده»)، این فاز باید هر `$specialist->bookings()->...` باقی‌مونده در فایل‌های فاز ۱ و ۲ رو هم
که عمداً دست‌نخورده مونده بودن جارو کنه — مشخصاً:
- `SpecialistController::show/byService` (کاربر، فاز ۲) — چند خط `$specialist->bookings()->...`
- `LeaveService::findConflictReason` (فاز ۲) — `$specialist->bookings()->whereBetween(...)`
- `AdminHolidayController::store` (فاز ۲) — `$specialist->bookings()->whereDate(...)`
این‌ها موقع نوشتن `BookingRepository` باید شناسایی و به همون Repository جدید وصل بشن، نه
دست‌نخورده رها بشن.

---

## نشست تکمیل‌شده: فاز ۳ (R-Repo-Bookings) — ۲۰۲۶-۰۹-۲۰

### دامنه‌ی فایل‌ها (فراتر از لیست جدول)
`BookingDiscountController` در جدول فازبندی اسمش نیومده بود، ولی مستقیم به `Booking::`/
`BeautyService::` کوئری می‌زد — دقیقاً همون الگوی `AdminSpecialistLeaveController` در فاز ۲ —
پس به همین فاز اضافه شد.

در طرف مقابل، این کنترلرها/فایل‌ها که به Booking مربوطن ولی در جدول نیومده بودن، **عمداً دست‌نخورده
موندن** و به فاز جاروب نهایی (`R-Repo-Sweep`, فاز ۱۲) واگذار شدن:
`SpecialistBookingManagementController`, `AdminBookingCustomerController`,
`CleanupPendingBookings` (command), `CancelUnpaidBookings` (job).

### بدهی‌های باقی‌مونده از فاز ۲ که این فاز جارو کرد
هر سه موردی که در یادداشت پایان فاز ۲ فهرست شده بودن، اینجا رفع شدن:
- `SpecialistController::show()` (کاربر) — چهار کوئری `$specialist->bookings()->...` (میانگین/شمارش
  امتیاز، تعداد تکمیل‌شده، نظرات اخیر) → دو متد جدید `BookingRepository::getRatingStatsForSpecialist`
  و `getRecentReviewsForSpecialist`
- `SpecialistController::byService()` (کاربر) — `$service->specialists()->withCount/withAvg` که در
  فاز ۱ عمداً دست‌نخورده مونده بود → متد جدید `SpecialistRepository::paginateByService` (چون این
  کوئری در واقع لیست‌کردن Specialistهاست، نه Bookingها؛ به همون Repository دامنه‌ش تعلق داره)
- `LeaveService::findConflictReason` → `BookingRepository::hasBookingInRange`
- `AdminHolidayController::store` (validation closure) → `BookingRepository::hasBookingOnDate`

### کار انجام‌شده
- `BookingRepositoryInterface` + `BookingRepository`: `paginateForUser`, `findForUserWithDetails`,
  `findForUser`, `getAllForUser`, `getUpcomingExcludingCancelledForUser`, `getPastForUserApi`,
  `getLatestSuccessfulForUser` (کاربر)؛ `paginateWithFilters`, `getStats` (ادمین)؛
  `getUpcomingForUser`, `getPastForUser` (سرویس/داشبورد — نام‌گذاری جدا از نسخه‌ی API چون فیلتر/
  limit متفاوتی دارن)؛ `getRatingStatsForSpecialist`, `getRecentReviewsForSpecialist`,
  `hasBookingInRange`, `hasBookingOnDate` (رفع بدهی فاز ۲)
- `SpecialistScheduleRepository`: متد `findActiveForDay` اضافه شد (برای
  `BookingAvailabilityController`)
- `SpecialistRepository`: متد `paginateByService` اضافه شد
- binding جدید (`BookingRepositoryInterface`) در `RepositoryServiceProvider` ثبت شد
- کنترلرها/سرویس‌های بازنویسی‌شده: `BookingController`, `BookingReservationController`,
  `BookingRescheduleController`, `BookingAvailabilityController`, `BookingDiscountController`,
  `AdminBookingController`, `BookingService`, `AdminBookingService`
- کامنت‌های همه‌ی فایل‌های لمس‌شده حذف شدن (شامل چند PHPDoc بلاک بزرگ در
  `AdminBookingController`/`AdminBookingService`/`BookingDiscountController`)

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- منطق کسب‌وکار خودِ `Booking` model (`getRemainingAmountAttribute`, `canBeRescheduled`)
- `$this->discountCode->where(...)` در `BookingService` — دامنه‌ی `DiscountCode` متعلق به فاز ۶
  (`R-Repo-Loyalty`) است
- `User::all()`/`User::where(...)` در `AdminBookingController`/`AdminSpecialistService`/
  `LeaveService` — دامنه‌ی `User` متعلق به فاز ۸ است
- `$service->specialists()->select(...)->get()` در `BookingAvailabilityController::getSpecialistsByService`
  — کوئری رابطه‌ای ساده‌ی خواندنی روی نمونه‌ی از قبل resolve‌شده، دقیقاً همون الگوی
  `ServiceController::show` در فاز ۱

### تست و وریفای
- تست‌های دامنه (۱۸ فایل: `AdminBookingControllerTest`, `AdminBookingCustomerControllerTest`,
  `AdminBookingSlotConflictTest`, `BookingServiceTest`, `SendBookingRemindersTest`,
  `BookingModelTest`, `BookingSmsDuplicationTest`, `BookingObserverTest`, `BookingPolicyTest`,
  `BookingAvailabilityControllerTest`, `BookingControllerTest`, `BookingDiscountControllerTest`,
  `BookingRescheduleControllerTest`, `BookingReservationControllerTest`, کاربر
  `SpecialistControllerTest`, `AdminHolidayTest`, `AdminLeaveTest`, `AdminSpecialistLeaveTest`):
  **۲۰۶/۲۰۶ (۱ skip)** سبز، بار اول بدون هیچ اصلاحی
- Laravel Pint: `PASS` روی همه‌ی ۲۸ فایل جدید/لمس‌شده
- کل سوییت: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر رفتاری، قبل و بعد یکسان
- هر چهار پچ (۰۰۰۱ تا ۰۰۰۴) پشت‌سرهم روی یک `git am` مستقل (کلون جدا از `0ad76e8`) تست شدن —
  بدون conflict، و کل سوییت روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### نکته‌ی تحویل پچ (بازخورد ابوالفضل، همین نشست)
دیگه لازم نیست پچ‌های قبلی (۰۰۰۱-۰۰۰۳) که تغییری نکردن دوباره تحویل داده بشن — فقط پچ فاز جدید
(این‌بار ۰۰۰۴) به‌تنهایی ساخته و ارائه شد (`git format-patch -1 HEAD --start-number=N`، نه کل
بازه‌ی `base..HEAD`).

### قدم‌های باز برای نشست بعدی
فاز ۴ (`R-Repo-Payments`) رو شروع کن: دامنه‌ی `Payment`, `Invoice` — `PaymentController`,
`SecurePaymentController`, `AdminBillingController`, `PaymentService`, `SecurePaymentService`,
`InvoiceService`, `SubscriptionPaymentService`. طبق همون قاعده‌ی مرز، هر `Booking::`/
`$booking->...` که تو این فایل‌ها پیدا بشه باید از طریق `BookingRepositoryInterface` موجود (فاز
۳) بره، نه مستقیم.

---

## نشست تکمیل‌شده: فاز ۴ (R-Repo-Payments) — ۲۰۲۶-۰۹-۲۰

### دامنه‌ی فایل‌ها (فراتر از لیست جدول)
`AdminPaymentController` در جدول فازبندی اسمش نیومده بود — نه Payment می‌سازه نه Invoice، فقط
مستقیم `Booking::findOrFail`/`$booking->update()` می‌زنه (ثبت دستی پرداخت توسط ادمین، بدون رکورد
Payment واقعی). دقیقاً همون الگوی `BookingDiscountController` در فاز ۳ — پس به همین فاز اضافه شد
و از `BookingRepositoryInterface` موجود (فاز ۳) استفاده کرد.

### کار انجام‌شده
- `PaymentRepositoryInterface` + `PaymentRepository`: `findByReference` (بدون eager-load، برای
  `SecurePaymentService::verifyPayment`)، `findByReferenceWithBooking` (`first()`، برای
  `showResult`)، `findByReferenceWithBookingOrFail` (`firstOrFail()`، برای `showVerification`/
  `verify`/`checkStatus`)
- `InvoiceRepositoryInterface` + `InvoiceRepository`: `paginateForSalon`
- binding‌های جدید در `RepositoryServiceProvider` ثبت شدن
- کنترلرها/سرویس‌های بازنویسی‌شده: `PaymentController`, `SecurePaymentController`,
  `AdminBillingController`, `AdminPaymentController`, `PaymentService`, `SecurePaymentService`,
  `InvoiceService`, `SubscriptionPaymentService`
- تمام `$booking->update(...)`/`Booking::findOrFail(...)` باقی‌مونده در این فایل‌ها (که به دامنه‌ی
  Payment مربوط بودن ولی مدلشون Booking بود) از `BookingRepositoryInterface` موجود (فاز ۳)
  استفاده کردن — نه فقط از repositoryهای تازه‌ساخته‌ی همین فاز
- کامنت‌های همه‌ی فایل‌های لمس‌شده حذف شدن (چند PHPDoc/⭐ بلاک بزرگ در
  `PaymentService::resolveMerchantId`, `InvoiceService`, `SubscriptionPaymentService`,
  `AdminBillingController`, `AdminPaymentController`)

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- منطق کسب‌وکار خودِ `Payment`/`Invoice` model (`markAsCompleted`, `markAsFailed`, `isPending`,
  `isPaid`, ...)
- `$wallet->increment('balance', ...)`/`$wallet->transactions()->create(...)` در
  `PaymentController` — دامنه‌ی `Wallet` متعلق به فاز ۵ (`R-Repo-Wallet`) است
- `Salon::lockForUpdate()->findOrFail(...)` در `InvoiceService::markPaidFromGateway` — دامنه‌ی
  `Salon` متعلق به فاز ۱۰ (`R-Repo-Salon`) است
- `app/Providers/PaymentServiceProvider.php` — یک provider مرده که هیچ‌جا register نشده (نه در
  `bootstrap/providers.php`)؛ `new PaymentService(...)` داخلش حتی با امضای فعلی (قبل از این فاز)
  هم ناسازگار بود. تأیید شد بی‌اثره و دست‌نخورده موند

### تست و وریفای
- تست‌های دامنه (۶ فایل: `AdminPaymentControllerTest`, `AdminBillingControllerTest`,
  `EnsureTwoFactorVerifiedForPaymentTest`, `PaymentControllerTest`,
  `SecurePaymentControllerTest`, `SecurePaymentServiceConfigTest`): **۴۸/۴۸** سبز، بار اول بدون
  هیچ اصلاحی
- Laravel Pint: `PASS` روی همه‌ی ۲۹ فایل جدید/لمس‌شده، بدون هیچ fix
- کل سوییت: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر رفتاری، قبل و بعد یکسان
- هر پنج پچ (۰۰۰۱ تا ۰۰۰۵) پشت‌سرهم روی یک `git am` مستقل (کلون جدا از `0ad76e8`) تست شدن —
  بدون conflict، و کل سوییت روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۵ (`R-Repo-Wallet`) رو شروع کن: دامنه‌ی `AdminWallet(+Transaction)`, `SpecialistWallet`,
`UserWallet(+Transaction)`, `WithdrawalRequest`, `WalletSetting` — `AdminWalletController`,
`SpecialistWalletController`, `SpecialistWithdrawalController`, `AdminWithdrawalController`,
`SpecialistIbanController`, `UserWalletController`, `WalletAdminService`,
`SpecialistWalletService`. توجه: `PaymentController::processWithWallet`/`callback` (فاز ۴) و
`Booking` model's معادل، هر دو `$wallet->increment/transactions()->create` مستقیم می‌زنن — این
فاز باید این‌ها رو هم جارو کنه.

---

## نشست تکمیل‌شده: فاز ۵ (R-Repo-Wallet) — ۲۰۲۶-۰۹-۲۰

### قاعده‌ی مرز تازه (برای فازهای بعدی هم صادقه): «مالکیت اتمیک موجودی» می‌مونه، «کوئری/CRUD خالص» می‌ره
این فاز یک نوع جدید از تصمیم مرزی رو معرفی کرد که در فازهای قبل پیش نیومده بود: عملیات
`$wallet->increment('balance', ...)`/`decrement(...)` + `$wallet->transactions()->create(...)`
که همیشه با هم، داخل یک `DB::transaction` می‌آن (مثل `adjustWallet`, `rejectWithdrawal`,
`cancelWithdrawal`, `chargeCallback`) — این‌ها **عمداً دست‌نخورده موندن**، چون این الگو خودِ
business logic هست (دقیقاً همون کاری که `AdminWallet::addCommission()` به‌عنوان متد خودِ Model
انجام می‌ده)، نه یک کوئری/لیست ساده. در مقابل، `->update()` خالص روی نمونه‌ی top-level (مثل
`$wallet->update(['iban_verified'=>true])` یا `$locked->update(['status'=>'processing'])`)
همیشه از Repository رفت — این تمایز («تغییر اتمیک موجودی+ثبت تراکنش» در برابر «CRUD خالص»)
از این فاز به بعد قاعده‌ی رسمیه.

### دامنه‌ی فایل‌ها (فراتر از لیست جدول)
`AdminWalletSettingsController` در جدول فازبندی اسمش نیومده بود ولی مستقیم
`WalletSetting::first()` می‌زد — چون `WalletSetting` توی ستون دامنه‌ی همین ردیف جدول هست، اضافه
شد (همون الگوی تکراری این چند فاز آخر).

### کار انجام‌شده
- `WalletSettingRepositoryInterface` + `WalletSettingRepository`: `first()`
- `SpecialistWalletRepositoryInterface` + `SpecialistWalletRepository`: `paginateWithFilters`,
  `getTotals`, `lockById`
- `WithdrawalRequestRepositoryInterface` + `WithdrawalRequestRepository`: `paginateWithFilters`
  (ادمین سراسری)، `getStats`، `lockById`، `paginateForWallet` (مشترک بین wallet overview
  خودِ متخصص و ادمین per-specialist)
- `WalletTransactionRepositoryInterface` + `WalletTransactionRepository` (کیف‌پول متخصص):
  `paginateForWalletWithFilters`, `getRecentForWallet`, `sumForWalletByTypeAndMonth`,
  `getPendingIncomeForSettlement`
- `UserWalletTransactionRepositoryInterface` + `UserWalletTransactionRepository` (کیف‌پول
  کاربر — مدل جدا از `WalletTransaction`): همون ۳ متد اول
- ۵ binding جدید در `RepositoryServiceProvider` ثبت شدن
- کنترلرها/سرویس‌های بازنویسی‌شده: `AdminWalletController`, `AdminWalletSettingsController`,
  `AdminWithdrawalController`, `SpecialistWalletController`, `SpecialistWithdrawalController`,
  `SpecialistIbanController`, `UserWalletController`, `WalletAdminService`,
  `SpecialistWalletService`
- `Specialist::withoutGlobalScopes()->whereKey(...)->value('salon_id')` در دو کنترلر ادمین
  (`AdminWalletController`, `AdminWithdrawalController`) با
  `SpecialistRepositoryInterface::getSalonIdIgnoringScopes` موجود (فاز ۲) جایگزین شد
- کامنت‌های همه‌ی فایل‌های لمس‌شده حذف شدن

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- منطق کسب‌وکار خودِ Model‌ها (`SpecialistWallet::addIncome/settlePendingAmount/canWithdraw/
  calculateWithdrawalFee/recordWithdrawal`, `WithdrawalRequest::markAsCompleted/markAsFailed`,
  `UserWallet::addRefund`, `Specialist::getOrCreateWallet`, `User::getOrCreateWallet`)
- `$wallet->increment/decrement` + `$wallet->transactions()->create` (بالا توضیح داده شد)
- `AdminWallet.php`/`AdminWalletTransaction.php` — هیچ فایل این فاز بهشون کوئری نمی‌زد (فقط
  `BookingObserver.php` که در جدول فازبندی نیست و به فاز جاروب (`R-Repo-Sweep`) واگذار شد)؛
  کاملاً دست‌نخورده موندن
- `User::findOrFail` در `UserWalletController::chargeCallback` — دامنه‌ی `User` متعلق به فاز ۸
  (`R-Repo-Users-Auth`) است

### تست و وریفای
- تست‌های دامنه (۱۱ فایل: `AdminWalletControllerTest`, `AdminWithdrawalControllerTest`,
  `ProcessWithdrawalJobTest`, `SpecialistIbanControllerTest`, `SpecialistWalletControllerTest`,
  `SpecialistWithdrawalControllerTest`, `UserWalletControllerTest`, `SpecialistWithdrawalTest`,
  `WalletAdminServiceSettlementTest`, `WalletAdminServiceWithdrawalTest`, `WalletSettingTest`):
  **۱۰۵/۱۰۵** سبز، بار اول بدون هیچ اصلاحی
- Laravel Pint: `PASS` روی همه‌ی ۴۰ فایل جدید/لمس‌شده، بدون هیچ fix
- کل سوییت: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر رفتاری، قبل و بعد یکسان
- هر شش پچ (۰۰۰۱ تا ۰۰۰۶) پشت‌سرهم روی یک `git am` مستقل (کلون جدا از `0ad76e8`) تست شدن —
  بدون conflict، و کل سوییت روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۶ (`R-Repo-Loyalty`) رو شروع کن: دامنه‌ی `LoyaltyPoint`, `LoyaltySetting`, `Reward`,
`Loyalty`, `DiscountCode`, `DiscountUsage` — `LoyaltyController`, `AdminLoyaltyPointsController`,
`AdminLoyaltyRewardController`, `AdminDiscountCodeController`, `BookingDiscountController`,
`LoyaltyService`, `LoyaltyAdminService`, `DiscountCalculator`. توجه: `BookingDiscountController`
از فاز ۳ همین حالا Booking/BeautyService رو از Repository می‌گیره — این فاز فقط باید
`DiscountCode`-محورهای همون فایل (اگه بودن) رو اضافه کنه. همچنین `BookingService`/
`AdminBillingController` (فاز‌های ۳/۴) هنوز مستقیم `$this->discountCode->where('code',...)`
می‌زنن — طبق قاعده‌ی همیشگی («فایل‌های فاز قبل که به دامنه‌ی تازه‌تکمیل‌شده وابسته بودن رو جارو
کن») این فاز باید این‌ها رو هم به `DiscountCodeRepositoryInterface` تازه وصل کنه.

---

## نشست تکمیل‌شده: فاز ۶ (R-Repo-Loyalty) — ۲۰۲۶-۰۹-۲۱

### وضعیت دسترسی GitHub این نشست
توکن GitHub موجود در این فایل باز هم `401 Unauthorized` داد. طبق روال، مستقیماً از فایل زیپ
آپلودی کار شد (یک گیت‌ریپوی محلی از صفر با یک کامیت baseline از همون زیپ ساخته شد). توکن
هنوز نیاز به تمدید داره؛ نکته‌ی برنچ `V3` (نه `develop`، طبق تصمیم ۲۰۲۶-۰۹-۲۱ بالا) برای وقتی
که دسترسی GitHub برقرار بشه یادداشت موند ولی این نشست عملاً ازش استفاده نکرد.

### محیط
PHP 8.3 + تمام extensionهای لازم (gd, mysql, sqlite, intl, bcmath, ...) از آرشیو استاندارد
اوبونتو ۲۴.۰۴ نصب شد (نه PPA — `add-apt-repository ppa:ondrej/php` با شبکه‌ی sandbox کار
نکرد، ولی خودِ اوبونتو ۲۴.۰۴ از قبل PHP 8.3 داره). Composer از
`github.com/composer/composer/releases` نصب شد. پوشه‌ی `storage/*` از صفر بازسازی شد.
MariaDB با `mysqld_safe` و `setsid nohup` بالا آورده شد (پراسس‌های bash بین فراخوانی‌های ابزار
sandbox پاک می‌شن، پس هر دفعه که ارتباط قطع شد باید MariaDB دوباره استارت بشه — این نکته برای
نشست‌های بعدی هم صادقه). سوییت تست کامل قبل از شروع اجرا شد: **baseline تأیید شد: ۱۰۸۷
passed / ۱ skipped / صفر fail** — دقیقاً منطبق با آخرین وضعیت مستندشده‌ی فاز ۵.

### کار انجام‌شده
- `app/Repositories/Contracts/DiscountCodeRepositoryInterface.php` +
  `app/Repositories/Eloquent/DiscountCodeRepository.php`: `paginateWithUser`, `getStats`
  (total/active/expired/used_up)، `findByCode`, `lockByCode` (نسخه‌ی `lockForUpdate`)،
  `getActiveForUser`
- `app/Repositories/Contracts/RewardRepositoryInterface.php` +
  `app/Repositories/Eloquent/RewardRepository.php`: `getActive`,
  `allOrderedByRequiredPoints`, `getNextForPoints`, `sumUsedCount`, `countActive`
- `app/Repositories/Contracts/LoyaltyPointRepositoryInterface.php` +
  `app/Repositories/Eloquent/LoyaltyPointRepository.php`: ۱۲ متد — `sumForUser`,
  `sumExpiringForUser`, `sumExpiringSoonForUser` (دو نسخه‌ی کمی متفاوتِ «امتیاز در حال انقضا»
  که در کد اصلی هم جدا بودن، عمداً یکی نشدن تا رفتار دقیقاً حفظ بشه)، `sumForUserByType`,
  `sumByType`, `countDistinctUsers`, `countByType`, `paginateForUserWithBooking`,
  `paginateForUser`, `paginateWithFilters`, `topUsersByPoints`, `recentByType`
- `app/Repositories/Contracts/LoyaltySettingRepositoryInterface.php` +
  `app/Repositories/Eloquent/LoyaltySettingRepository.php`: `getValue` (همون منطق
  `LoyaltySetting::getValue()` مدل، فقط پشت Repository)
- ۴ binding جدید در `RepositoryServiceProvider` ثبت شد
- `LoyaltyController`, `AdminLoyaltyPointsController`, `AdminLoyaltyRewardController`,
  `AdminDiscountCodeController`, `AdminDiscountCodeService`, `BookingDiscountController`,
  `LoyaltyService`, `LoyaltyAdminService`, `DiscountCalculator`: بازنویسی کامل — کوئری‌های
  مستقیم این دامنه با تزریق Repository جایگزین شدن؛ کامنت‌ها (فارسی/انگلیسی، PHPDoc شامل)
  حذف شدن. `BookingDiscountController` و `DiscountCalculator` از قبل هیچ کوئری مستقیمی
  نداشتن (فقط کامنت حذف شد)
- `BookingService.php` (فاز ۳، طبق یادداشت پایان فاز ۵): فقط ۵ occurrence از
  `$this->discountCode->where('code', ...)`/`->lockForUpdate()->first()` به
  `DiscountCodeRepositoryInterface::findByCode()`/`lockByCode()` وصل شدن؛ `WalletSetting::get()`
  همون فایل (دامنه‌ی فاز ۵) طبق قاعده‌ی «هر فایل فقط یک‌بار دست بخوره در فاز خودش» دست‌نخورده
  موند
- کامنت‌های ۵ فایل FormRequest اختصاصی همین کنترلرها هم حذف شدن (`PreviewDiscountCodeRequest`,
  `StoreLoyaltyRewardRequest`, `UpdateLoyaltyRewardRequest`, `AddUserPointsRequest`,
  `DeductUserPointsRequest`) — این فایل‌ها در جدول فازبندی اسم برده نشده بودن ولی چون
  منحصراً کنترلرهای همین فاز رو سرویس می‌دن و هیچ کوئری‌ای نداشتن، حذف کامنتشون بی‌خطر بود

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- `LoyaltySetting::getValue()`, `LoyaltyPoint::calculatePointsForBooking()`,
  `LoyaltyPoint::getCurrentBalance()`, `LoyaltyPoint::getExpiringPoints()` — متدهای استاتیک
  روی مدل که `BookingObserver.php`, `SpecialistProfileController.php`, و نوتیفیکیشن
  `PointsEarned` مستقیماً صداشون می‌زنن؛ هیچ‌کدوم از این سه فایل جزو دامنه‌ی این فاز نیستن
  (`BookingObserver` طبق تصمیم فاز ۵ به `R-Repo-Sweep` واگذار شده؛ دو تای دیگه اصلاً در هیچ
  فازی اسم برده نشدن) — دقیقاً همون الگوی `WalletSetting::get()` در فاز ۵
- `User::orderBy('name')->get(...)` در `AdminDiscountCodeController::create()` و
  `User::query()->where(...)->get()`/`User::find(...)` در
  `AdminLoyaltyPointsController::index()` — دامنه‌ی `User` متعلق به فاز ۸
  (`R-Repo-Users-Auth`) است؛ دقیقاً مثل `Salon::lockForUpdate()->findOrFail(...)` که در
  `InvoiceService` (فاز ۴) به‌خاطر تعلق به فاز ۱۰ دست‌نخورده موند
- `Reward::incrementUsage()`/`DiscountCode::incrementUsage()` (هر دو `$this->increment(...)`)
  — منطق نوشتنیِ خودِ Model، مثل `SpecialistWallet::addIncome` در فاز ۵
- `DiscountCodeObserver.php` — بدون هیچ کوئری مستقیم (فقط `$discountCode->save()`/متدهای
  نمونه‌ی از قبل bind‌شده)، در جدول فازبندی اسم برده نشده، دست‌نخورده موند (مثل
  `BookingObserver` در فاز ۳/۵)
- `Loyalty::rewards()` (relationship) و `DiscountCode::user()`/`bookings()` و مشابه — تعریف
  رابطه، طبق قرارداد داخل Model می‌مونه
- **مدل `Loyalty` (سطح‌بندی/tier) هیچ کوئری مستقیمی در کل پروژه نداره** (فقط
  `Reward::loyalty()` relationship) — پس هیچ Repository ای براش لازم نبود؛ اگه در آینده
  واقعاً استفاده بشه، این نکته باید بازبینی بشه
- **مدل `DiscountUsage` هم هیچ کوئری مستقیمی در کل پروژه نداره** — هیچ Repository ای براش
  ساخته نشد

### ⚠️ آیتم‌های باز برای تصمیم تو
1. ~~**`AdminLoyaltyController::index()`**~~ ✅ **بسته شد (همین نشست، طبق تصمیم ابوالفضل «الان
   بهش رسیدگی کن»)** — این کنترلر (که هندلر واقعی `GET /admin/loyalty` است، طبق
   `routes/admin/loyalty.php`) به‌جای کوئری مستقیم `LoyaltyPoint::`/`Reward::`، حالا از
   `LoyaltyAdminService::getDashboardStats()` استفاده می‌کنه — دقیقاً همون منطقی که
   `AdminLoyaltyRewardController::index()` (که خودش، معلوم شد، هیچ روتی نداره و کاملاً مرده‌ست)
   از قبل داشت. هیچ تغییر رفتاری/View‌ای نداد؛ پچ دوم (`0002`) تحویل داده شد.
2. ~~**مغایرت با یادداشت پایان فاز ۵**~~ ✅ **بررسی مجدد انجام شد** — با یک grep سراسری روی کل
   `app/` برای تمام مدل‌های این دامنه (`DiscountCode`, `LoyaltyPoint`, `Reward`,
   `LoyaltySetting`, `Loyalty`, `DiscountUsage`)، تأیید شد `AdminBillingController` واقعاً هیچ
   ارجاعی نداره (یادداشت قبلی اشتباه یا قدیمی بوده). **یک مورد واقعی جدید پیدا شد که به این
   فاز تعلق نداره**: `App\Services\Admin\Report\AdminReportService.php` یک
   `DiscountCode::whereIn('code', ...)` مستقیم داره (برای batch-fetch نوع کد تخفیف در گزارش
   نوبت‌ها) — این فایل دامنه‌ی `R-Repo-Reports-Notif` (فاز ۱۱، هنوز شروع‌نشده) است و در فهرست
   فایل‌های فاز ۶ اسم برده نشده بود، پس طبق قاعده‌ی «هر فایل فقط یک‌بار دست بخوره در فاز خودش»
   دست‌نخورده موند. **باید در فاز ۱۱ یا یک sweep جدا رسیدگی بشه.**

### تست و وریفای (بعد از پچ دوم)
- `AdminLoyaltyRewardHttpTest` (شامل `test_index_shows_dashboard_stats_and_rewards_list` که
  دقیقاً همین کنترلر رو از مسیر HTTP کامل تست می‌کنه)، `AdminLoyaltyPointsControllerTest`،
  `Loyalty*` — **۴۸/۴۸** سبز
- کل سوییت: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر
- Laravel Pint: `PASS`
- هر دو پچ (`0001`, `0002`) پشت‌سرهم روی یک `git am` مستقل (کلون جدا از baseline) تست شدن —
  بدون conflict، و کل سوییت روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۷ (`R-Repo-Content`) رو شروع کن: دامنه‌ی `BlogPost`, `BlogCategory`, `GalleryImage`,
`Announcement`, `Review`, `ReviewToken` — `BlogController`, `AdminBlogController`,
`AdminBlogCategoryController`, `AdminGalleryController`, `AnnouncementController`,
`AdminAnnouncementController`, `ReviewController`, `AdminReviewController`,
`SpecialistReviewController`. یک نکته‌ی جدید از این نشست: `AdminReportService.php` یک
`DiscountCode::whereIn(...)` مستقیم داره که به فاز ۶ تعلق نداشت و به فاز ۱۱
(`R-Repo-Reports-Notif`) واگذار شد — وقتی به اون فاز رسیدیم یادت باشه.

---

## نشست تکمیل‌شده: فاز ۷ (R-Repo-Content) — ۲۰۲۶-۰۹-۲۱

### دامنه‌ی فایل‌ها (فراتر از لیست جدول)
`AdminBlogPostActionController` و سرویس‌های `BlogPostService`, `BlogCategoryService`,
`ReviewService` در جدول فازبندی/پرامپت شروع نشست اسم برده نشده بودن، ولی منحصراً همین ۹
کنترلر رو سرویس می‌دن (`AdminBlogPostActionController` واقعاً CRUD مقاله‌ست — `create`/
`store`/`edit`/`update`/`destroy`/`togglePublish` — و `AdminBlogController` فقط `index`/`show`
داره). طبق تصمیم صریح ابوالفضل در پایان فاز ۶ («check thoroughly, fix real gaps»)، این‌بار
خودم این‌ها رو بدون توقف برای تأیید، جزو دامنه در نظر گرفتم و رفکتور کردم.

### کار انجام‌شده
- Repository های جدید (Interface + Eloquent، بایند در `RepositoryServiceProvider`):
  - `BlogPostRepository`: `paginateWithCategory`, `sumViews`, `paginatePublished`,
    `getRelatedPublished`
  - `BlogCategoryRepository`: `getAllOrdered` (بدون count، برای dropdown فرم‌ها)،
    `getWithPostsCount` (با count، برای صفحه‌ی عمومی وبلاگ)، `paginateWithPostsCount`،
    `getMaxOrder`، `hasPosts`
  - `GalleryImageRepository`: `getAllOrdered`, `findPreviousByOrder`, `findNextByOrder`
  - `AnnouncementRepository`: `getActive`, `getTopActive`, `paginateActive`,
    `findActiveOrFail`, `paginateAllOrdered`, `countActive`, `countPending`, `countExpired`
    — این چهارتای اول به‌جای بازنویسی همون where-chain برای چهارمین‌بار، از
    `scopeActive()`/`scopeByPriority()` خودِ Model استفاده می‌کنن (رفتار عیناً یکسان، فقط
    منبع کد یکی شد)
  - `ReviewRepository`: ۱۳ متد — شامل `paginateWithFilters` (فیلتر ادمین) و
    `paginateForSpecialistWithFilters` (فیلتر پنل متخصص، با تاریخ‌های شمسی که در Controller
    از قبل به Carbon تبدیل می‌شن، نه داخل Repository)
  - `ReviewTokenRepository`: `findByToken`, `findValidToken`
- Repository های فازهای قبل (بدون ساخت Repository جدید، طبق قاعده‌ی «متد کوچیک اضافه کن»):
  - `SpecialistRepositoryInterface` (فاز ۲): `findByPhoneOrFail`, `getNameOptions`,
    `getTopRatedByApprovedReviews`
  - `BookingRepositoryInterface` (فاز ۳): `findOrFailWithReviewDetails`
- همه‌ی کنترلرها/سرویس‌های دامنه بازنویسی و کامنت‌زدایی شدن (۳۴ فایل — شامل
  `StoreAnnouncementRequest`, `RespondReviewRequest` که منحصراً این کنترلرها رو سرویس می‌دن)

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- **`Review::calculateSpecialistAverage()`/`getSpecialistStats()` و
  `ReviewToken::findValidToken()`**: هیچ فراخواننده‌ای خارج از فایل‌های این فاز نداشتن (نه در
  تست، نه در کد) → کامل به Repository منتقل و از Model **حذف** شدن (مثل الگوی
  `CategoryRepository` که هیچ متد استاتیک کوئری‌زنی روی `Category` باقی نموند)
- **`ReviewToken::createForBooking()`**: برخلاف بقیه، **در تست مستقیم استفاده می‌شه**
  (۱۱ بار در `ReviewControllerTest.php` به‌عنوان setup) → دقیقاً طبق الگوی `WalletSetting::get()`
  از فاز ۵، روی Model دست‌نخورده موند و `ReviewService::sendReviewRequest()` هم همچنان
  مستقیم صداش می‌زنه
- `User::where('is_admin', true)->...` در `ReviewService::notifyAdminAboutNegativeReview()` —
  دامنه‌ی `User` متعلق به فاز ۸ است
  - **`Announcement::getActiveAnnouncements()`** — کد کاملاً مرده (هیچ فراخواننده‌ای در کل
  پروژه نداره)، در فهرست فایل‌های این فاز اسم برده نشده، دست‌نخورده موند
- **`App\Http\Requests\Specialist\RespondReviewRequest`** — کد مرده
  (`SpecialistReviewController::respond()` این Request رو استفاده نمی‌کنه، validation رو
  inline انجام می‌ده)؛ فقط کامنتش حذف شد چون دامنه‌ی همین فاز رو سرویس می‌ده، ولی منطقی برای
  migrate کردن نداشت

### تست و وریفای
- تست‌های دامنه (۸ فایل، ۷۶ تست): `AdminAnnouncementTest`, `AdminBlogTest`, `AdminGalleryTest`,
  `AdminReviewTest`, `SpecialistReviewControllerTest`, `AnnouncementControllerTest`,
  `BlogControllerTest`, `ReviewControllerTest` — **۷۶/۷۶** سبز (فقط یک باگ import
  فراموش‌شده در `RepositoryServiceProvider` پیدا و فیکس شد؛ منطق کوئری‌ها بار اول درست بود)
- کل سوییت: **۱۰۸۷ passed / ۱ skipped / صفر fail** — بدون تغییر
- Laravel Pint: `PASS` روی هر ۳۴ فایل
- پچ (`0001-feat-repo-R-Repo-Content-...patch`) روی یک `git am` مستقل — کلون جدا که فقط تا
  آخر فاز ۶ (`cc53d27`) رفته، سپس فقط همین یک پچ روش اعمال شد — بدون conflict، و کل سوییت
  روی همون کلون هم ۱۰۸۷/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۹ تمام شد — به بخش «✅ نشست تکمیل‌شده: فاز ۹ (R-Repo-Security)» در انتهای همین سند نگاه
کن. فاز ۱۰ (`R-Repo-Salon`) رو شروع کن: دامنه‌ی `Salon`, `SalonSmsUsage` —
`SuperAdminController`, `SuperAdminService`, `SalonSignupController`/`SalonSignupService`. توجه:
`SalonSignupController`/`SalonSignupService` در فاز ۸ فقط برای بخش‌های `User::` ریفکتور شدن —
بخش‌های `Salon::` همون دو فایل (مثل `Salon::create()`, `Salon::where('slug', $slug)->exists()`)
دست‌نخورده موندن چون این فاز هنوز شروع نشده بود. قبل از شروع، یک grep سراسری `Salon::`/
`SalonSmsUsage::` در کل `app/` بزن، نه فقط چیزهایی که در این سند یادداشت شدن. همچنین دو آیتم
باز جامونده از فازهای قبل رو یادت باشه: `AdminReportService.php` (`DiscountCode::whereIn`،
فاز ۱۱) و `Announcement::getActiveAnnouncements()`/
`App\Http\Requests\Specialist\RespondReviewRequest` (کد مرده، بدون فاز مشخص — تصمیم با
ابوالفضل).
## نشست تکمیل‌شده: فاز ۸ (R-Repo-Users-Auth) — ۲۰۲۶-۰۹-۲۱

طبق تصمیم صریح پایان فاز ۷، این فاز بار سنگینی از جاروب داشت: هر جا در کل `app/` مستقیم
`User::` کوئری زده می‌شد (نه فقط جاهایی که در سند یادداشت شده بودن)، جزو دامنه بود. یک grep
سراسری `User::` در کل `app/` زده شد و همه‌ی نتایج (غیر از تعریف رابطه‌های `belongsTo`/
`belongsToMany` و کامنت‌ها) بررسی و ریفکتور شدن — ۳ فایل Job، ۹ فایل Service، ۱۳ کنترلر و ۴
Listener.

### کار انجام‌شده
- `UserRepository` جدید (Interface + Eloquent، بایند در `RepositoryServiceProvider`) — علاوه بر
  متدهای پایه‌ی `RepositoryInterface` (`find`, `findOrFail`, `create`, `count`, ...)، ۱۱ متد
  اختصاصی: `query()` (escape hatch برای فیلترسازی پویا)، `findByPhone`, `findStaffByPhone`,
  `findCustomerByPhoneInSalon`, `staffPhoneExists`, `searchCustomersInSalon`,
  `getAdminRecipients` (کوئری تکراری «is_admin=true یا permission=access_admin_panel» که عیناً
  در ۴ Listener مختلف کپی شده بود، حالا یک‌جا)، `getSuperAdmins`, `getUsersWithoutRole`,
  `getOptionsOrderedByName`, `countWithTwoFactorEnabled`
- همه‌ی کنترلرها/سرویس‌ها/Jobها/Listenerها بازنویسی شدن تا به‌جای `User::` مستقیم، از
  `UserRepositoryInterface` (تزریق‌شده در constructor یا، برای Jobها، به‌عنوان پارامتر دوم
  `handle()`) استفاده کنن. فایل‌های دست‌خورده: `SendLoginVerificationCodeJob`,
  `SendPhoneVerificationCodeJob`, `Send2faVerificationCodeJob`, `LoyaltyService`,
  `AdminSecurityService`, `AdminDashboardService`, `LoyaltyAdminService`, `SalonSignupService`,
  `LeaveService`, `SMSService`, `ReviewService`, `AdminSpecialistService`, `AdminUserService`,
  `AdminRoleController`, `AdminBookingController`, `AdminBookingCustomerController`,
  `AdminDiscountCodeController`, `AdminSearchController`, `AdminUserController`,
  `AdminLoyaltyPointsController`, `UserWalletController`, `RegisteredUserController`,
  `AuthenticatedSessionController`, `PasswordResetController`, `SalonSignupController`,
  `CustomerRegisteredController`, `CustomerAuthenticatedController`,
  `CustomerPasswordResetController`, و ۴ Listener (`SendAdminBookingNotifications`,
  `SendAdminPaymentNotification`, `SendAdminWithdrawalNotification`, `SendNewUserNotifications`)

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- **`RouteServiceProvider::configureModelBindings()`** — `Route::bind('user', fn ($value) =>
  User::findOrFail($value))` عمداً دست‌نخورده موند، هم‌الگو با بایندهای `specialist`/`service`/
  `booking` که در فازهای قبلی هم به همین شکل باقی موندن — این زیرساخت route model binding است،
  نه یک کوئری دامنه‌ای
- `AdminSecurityService::paginatedLogs()`/`stats()` قسمت‌های `SecurityLog::` (نه `User::`)
  دست‌نخورده موندن — دامنه‌ی فاز ۹ (`R-Repo-Security`) است؛ فقط دو خط `User::` همون فایل
  (`paginatedUsers()` و `stats()['users_with_2fa']`) در این فاز ریفکتور شدن
- `AdminSearchController` بخش‌های `Specialist::`/`BeautyService::`/`Booking::`/`BlogPost::`
  دست‌نخورده موندن — این کنترلر جستجوی سراسریه و از هیچ Repository دیگه‌ای هم استفاده نمی‌کنه؛
  فقط ۳ بخش `User::` (که دامنه‌ی این فازه) به `UserRepositoryInterface::query()` منتقل شدن

### باگ واقعی کشف و رفع‌شده (نه بخشی از دامنه‌ی اصلی فاز، ولی توسط تزریق‌کردن Repository به
`SMSService` آشکار شد)
`SMSService` در ۱۳ کلاس Notification مختلف با `new SMSService` (بدون container) ساخته می‌شه —
یک الگوی از قبل موجود در کل پروژه. اضافه‌کردن یک پارامتر اجباری به constructor این کلاس همه‌ی
این ۱۳ جا رو می‌شکست (خطای `ArgumentCountError`، تست‌ها ۳۷۶ error دادن). به‌جای
constructor injection، از همون الگوی موجود خودِ فایل (`app(SmsQuotaService::class)` داخل
`consumeQuotaOrNotify()`) استفاده شد — `app(UserRepositoryInterface::class)` فقط داخل همون یک
متدی که واقعاً نیازش داره (`notifyQuotaExhausted()`)، بدون تغییر constructor. یک
`SMSServiceProvider` قدیمی/کاملاً منسوخ هم در `app/Providers/` پیدا شد
(`new SMSService(config('services.sms.api_key'), ...)` — سیگنیچر کاملاً متفاوت با نسخه‌ی فعلی
کلاس) که هیچ‌جا register نشده (نه در `bootstrap/providers.php`، نه در `config/app.php`) —
کد کاملاً مرده، دست‌نخورده موند (تصمیم‌گیری درباره‌ی حذفش با ابوالفضل، هم‌الگو با آیتم‌های مرده‌ی
یادداشت‌شده‌ی قبلی).

همچنین: `findCustomerByPhoneInSalon()`/`searchCustomersInSalon()` عمداً `?int $salonId`
می‌گیرن نه `int` — چون `CurrentSalon::id()` می‌تونه `null` برگردونه (مثلاً برای سوپرادمین بدون
سالن فعال)؛ اگه پارامتر non-nullable بود، یک null اونجا به‌جای رفتار بی‌خطر قبلی
(`WHERE salon_id IS NULL`) باعث `TypeError` می‌شد — یک رگرسیون واقعی که قبل از commit پیدا و
رفع شد.

### تست و وریفای
- کل سوییت: **۱۰۸۸ passed / ۱ skipped / صفر fail** — بدون تغییر نسبت به baseline
- تنها تغییر لازم در تست‌های موجود: `tests/Feature/Jobs/NotificationJobsTest.php` — ۶ فراخوانی
  مستقیم `->handle(app(SMSService::class))` روی ۴ Job (`Send2faVerificationCodeJob`,
  `SendLoginVerificationCodeJob`, `SendPhoneVerificationCodeJob` — هر کدوم ۱ یا ۲ بار) باید
  پارامتر دوم (`app(UserRepositoryInterface::class)`) هم می‌گرفتن، چون این تست‌ها مستقیم
  `handle()` رو صدا می‌زنن (نه از طریق queue/dispatch که تزریق پارامتر دوم رو خودکار انجام
  می‌ده)
- Laravel Pint: `PASS` روی هر ۳۶ فایل تغییریافته
- پچ (`0001-refactor-repo-Phase-8-R-Repo-Users-Auth-extract-User.patch`) روی یک `git am`
  مستقل — کلون جدا که فقط تا آخر فاز ۷ رفته، سپس فقط همین یک پچ روش اعمال شد — بدون conflict،
  و کل سوییت روی همون کلون هم ۱۰۸۸/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۹ (`R-Repo-Security`) رو شروع کن: دامنه‌ی `SecurityLog`, `SecuritySetting` —
`SecurityController` (کاربر)، `AdminSecurityController`، `SecurityLogService`.
`AdminSecurityService::paginatedLogs()`/`stats()` مستقیم `SecurityLog::` کوئری می‌زنن (به بخش
«مرزهای رعایت‌شده» بالا نگاه کن). قبل از شروع، یک grep سراسری `SecurityLog::`/
`SecuritySetting::` در کل `app/` بزن.

⚠️ یادآوری مهم: توکن GitHub PAT ذخیره‌شده در این سند دوباره منقضی شده بود (۴۰۱ در ابتدای این
نشست) — طبق الگوی مستندشده در بخش «روال دسترسی به GitHub»، این کل نشست از روی زیپ آپلودی کار
کرد (نه از GitHub مستقیم) و وریفای پچ روی یک کلون محلی (نه یک کلون واقعی از GitHub) انجام شد.
یک PAT تازه لازمه تا نشست بعدی بتونه مستقیم از GitHub بخونه/وریفای کنه.

## نشست تکمیل‌شده: فاز ۹ (R-Repo-Security) — ۲۰۲۶-۰۹-۲۱

یک grep سراسری `SecurityLog::`/`SecuritySetting::` در کل `app/` زده شد — دامنه‌ی این فاز به‌مراتب
کوچیک‌تر از فاز ۸ بود: فقط ۴ فایل، حدود ۱۳ محل `SecurityLog::`.

### کار انجام‌شده
- `SecurityLogRepository` جدید (Interface + Eloquent، بایند در `RepositoryServiceProvider`) —
  ۱۱ متد اختصاصی: `query()` (escape hatch برای `AdminSecurityService::paginatedLogs()` با ۵
  فیلتر پویا)، `paginateForUser`, `paginateForUserWithFilters`, `getRecentForUser`,
  `getLoginHistoryForUser`, `getLastSuccessfulLoginAt`, `countSince`, `countWarningsSince`,
  `countFailedLoginAttemptsSince`, `countWarningsForUserSince`, `countLoginAttemptsForUserSince`
- فایل‌های ریفکتورشده: `SecurityLogService` (`persist()` → `create()` از طریق ریپازیتوری)،
  `AdminSecurityService` (`paginatedLogs()`, `paginatedUsers()`'s `last_successful_login_at`,
  `stats()`'s سه شمارنده)، و `SecurityController` (کاربر — هر ۷ محل: `activity()`,
  `getSecurityLogs()`, `getLoginHistory()`, `calculateSecurityScore()`, `getRecentActivities()`,
  `getLoginAttempts()`)

### مرزهای رعایت‌شده (عمداً دست‌نخورده موندن)
- **`SecuritySetting::get()`** — در هر سه محلش (`AdminSecurityService::updateSettings()`,
  `AdminSecurityController::settings()`, `SecurityController::calculateSecurityScore()`) عمداً
  دست‌نخورده موند. این متد استاتیک سفارشیه (`first() ?? create([])`)، دقیقاً هم‌الگو با
  `WalletSetting::get()` که در learnings مستند شده («این خط رو "فیکس" نکن») — و بعد از فاز ۵
  (`R-Repo-Wallet`) هم `WalletSetting::get()` همچنان مستقیم روی Model صدا زده می‌شه، نه از طریق
  Repository (`BookingObserver`, `BookingService`, `ServiceController` چک شدن، همه هنوز مستقیم).
  قبل از شروع این فاز، این الگو صریحاً چک شد تا همون اشتباه دوباره تکرار نشه.
- قبل از تزریق `SecurityLogRepositoryInterface` به constructor `SecurityLogService`، طبق درسی
  که فاز ۸ با `SMSService` یاد داد، یک grep برای `new SecurityLogService` زده شد — هیچ محل
  دستی‌ای پیدا نشد (همیشه از طریق container resolve می‌شه)، پس constructor injection ساده و
  بی‌خطر بود.

### تست و وریفای
- کل سوییت: **۱۰۸۸ passed / ۱ skipped / صفر fail** — بدون تغییر نسبت به baseline، بدون هیچ
  رگرسیونی (برخلاف فاز ۸، این‌بار سوییت از همون تلاش اول سبز شد)
- Laravel Pint: `PASS` روی هر ۶ فایل تغییریافته
- پچ (`0001-refactor-repo-Phase-9-R-Repo-Security-extract-Securi.patch`) روی یک `git am`
  مستقل — کلون جدا که فقط تا آخر فاز ۸ رفته، سپس فقط همین یک پچ روش اعمال شد — بدون conflict،
  و کل سوییت روی همون کلون هم ۱۰۸۸/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۱۰ تمام شد — به بخش «✅ نشست تکمیل‌شده: فاز ۱۰ (R-Repo-Salon)» در انتهای همین سند نگاه کن.
فاز ۱۱ (`R-Repo-Reports-Notif`) رو شروع کن: دامنه‌ی `ReportExport`, `ScheduledReport(+Run)`,
`NotificationSetting`, `UserNotification`, `UserReportSetting` — `AdminReportsController`,
`AdminReportExportController`, `AdminNotificationController`, `AdminNotificationSettingController`,
`SpecialistNotificationController`, `ReportCacheService`, `SmsQuotaService`. یادآوری از قبل:
`AdminReportService.php` یک `DiscountCode::whereIn` مستقیم داره که جزو دامنه‌ی این فازه (دامنه‌ی
`DiscountCode` نیست، ولی همون فایلیه که این فاز قراره روش کار کنه). قبل از شروع، یک grep سراسری
`ReportExport::`/`ScheduledReport::`/`NotificationSetting::`/`UserNotification::`/
`UserReportSetting::` در کل `app/` بزن. همچنین یک آیتم باز دیگه از فازهای قبل رو یادت باشه:
`Announcement::getActiveAnnouncements()`/`App\Http\Requests\Specialist\RespondReviewRequest`
(کد مرده، بدون فاز مشخص — تصمیم با ابوالفضل).

⚠️ یادآوری برای فاز بعد از ۱۱ (یا هر فاز sweep نهایی): دو مورد «جامونده‌ی عمدی» از فاز ۱۰ رو
یادت باشه — `SuperAdminService`'s دو `Specialist::withoutGlobalScope('salon')->where('salon_id',
...)->count()` (دامنه‌ی Specialist، نه Salon) و `SuperAdminController::invoices()`'s
`Invoice::withoutGlobalScope('salon')->where('salon_id', ...)` (دامنه‌ی Invoice، نه Salon) —
هیچ‌کدوم دامنه‌ی فاز ۱۰ نبودن، عمداً دست‌نخورده موندن.

⚠️ یادآوری: توکن GitHub PAT همچنان تازه نشده — این نشست هم مثل فازهای ۸ و ۹ از روی همون زیپ
اولیه‌ی آپلودشده و کلون‌های محلی کار کرد.

## نشست تکمیل‌شده: فاز ۱۰ (R-Repo-Salon) — ۲۰۲۶-۰۹-۲۱

یک grep سراسری `Salon::`/`SalonSmsUsage::` در کل `app/` زده شد. برخلاف فازهای ۸ و ۹، این‌بار
چند محل واقعاً حساس هم جزو دامنه بودن — نه فقط کنترلر/سرویس معمولی.

### کار انجام‌شده
- `SalonRepository` جدید (Interface + Eloquent) — ۶ متد اختصاصی: `findBySlug`, `slugExists`,
  `lockForUpdateFindOrFail` (برای قفل بدبینانه‌ی `InvoiceService::markPaidFromGateway`),
  `getOldestSlug`, `getAllWithSpecialistCountAndAdmins`, `paginateWithSpecialistCountAndAdmins`
- `SalonSmsUsageRepository` جدید (Interface + Eloquent) — ۱ متد اختصاصی: `firstOrCreateForPeriod`
  (چون `SalonSmsUsage` مدل جدایی از `Salon` است، طبق قرارداد این پروژه یک ریپازیتوری مستقل گرفت،
  نه متدی روی `SalonRepository`)
- هر دو در `RepositoryServiceProvider` بایند شدن
- فایل‌های ریفکتورشده: `SmsQuotaService`, `InvoiceService`, `SuperAdminService`,
  `SuperAdminController`, `SalonSignupController`, `SalonSignupService`

### دو محل حساس، با احتیاط بیشتر ریفکتور شدن
- **`AppServiceProvider::boot()`** — کش سراسری `default_salon_slug` (`Salon::query()->oldest('id')
  ->value('slug')`) به `app(SalonRepositoryInterface::class)->getOldestSlug()` تبدیل شد
  (service-locator، نه constructor injection، چون این یک ServiceProvider است). گارد
  `Schema::hasTable('salons')` قبل از این فراخوانی دست‌نخورده موند — همون گاردی که قبلاً یک
  کرش واقعی (خطای «no such table» روی هر بوت، پیش از migrate) رو حل کرده بود.
- **`ResolveSalonFromRoute`** — تنها جایی که یک سالن با slug پیدا می‌شه، برای کل مسیرهای عمومی
  چندسالنی (`/s/{slug}` و ساب‌دامین). این فایل تاریخچه‌ی مستند از باگ‌های واقعی و جدی داره
  (باگ positional-argument که قبلاً کشف شده). قبل از دست‌زدن، یک grep برای `new
  ResolveSalonFromRoute(` زده شد تا مطمئن بشیم constructor injection امن است (هیچ‌جا دستی
  ساخته نمی‌شه). فقط خط `Salon::where('slug', ...)->first()` به
  `$this->salonRepository->findBySlug((string) $request->route('salon_slug'))` تبدیل شد —
  بقیه‌ی منطق (چک تعلیق/انقضا، `CurrentSalon::set()`, `URL::defaults()`, فیکس
  `forgetParameter('salon_slug')`) کاملاً دست‌نخورده موند. `(string)` cast روی پارامتر route
  عمداً اضافه شد تا با سیگنیچر `findBySlug(string $slug)` جور شه — در عمل بی‌خطره چون این
  پارامتر همیشه توسط تعریف روت پر می‌شه، هیچ‌وقت واقعاً null نیست.

### مرزهای رعایت‌شده (طبق درسِ فاز ۸ با SMSService)
- قبل از تزریق `SalonRepositoryInterface`/`SalonSmsUsageRepositoryInterface` به هر سرویس، یک
  grep برای `new <ServiceName>(` زده شد — هیچ محل ساخت دستی‌ای برای `SmsQuotaService`,
  `InvoiceService`, `SalonSignupService`, یا `SuperAdminService` پیدا نشد، پس constructor
  injection ساده و بی‌خطر بود
- `SMSService::consumeQuotaOrNotify()`'s `Salon::find($salonId)` — طبق همون محدودیت مستندشده‌ی
  فاز ۸ (constructor این کلاس عمداً بدون پارامتره چون ۱۳ کلاس Notification با `new SMSService`
  می‌سازنش) — به‌جای constructor injection، `app(SalonRepositoryInterface::class)->find(...)`
  استفاده شد
- دو مورد `Specialist::withoutGlobalScope('salon')` در `SuperAdminService` و یک مورد
  `Invoice::withoutGlobalScope('salon')` در `SuperAdminController::invoices()` عمداً دست‌نخورده
  موندن — دامنه‌ی این دو مدل جزو این فاز نیست (به بخش «قدم‌های باز» زیر نگاه کن)

### تست و وریفای
- کل سوییت: **۱۰۸۸ passed / ۱ skipped / صفر fail** — بدون تغییر نسبت به baseline، سبز از تلاش
  اول، حتی بعد از دست‌زدن به `ResolveSalonFromRoute`
- Laravel Pint: `PASS` روی هر ۱۴ فایل تغییریافته/جدید
- پچ (`0001-refactor-repo-Phase-10-R-Repo-Salon-extract-Salon-an.patch`) روی یک `git am`
  مستقل — کلون جدا که فقط تا آخر فاز ۹ رفته، سپس فقط همین یک پچ روش اعمال شد — بدون conflict،
  و کل سوییت روی همون کلون هم ۱۰۸۸/۱ (skip) سبز بود

### قدم‌های باز برای نشست بعدی
فاز ۱۱ تمام شد — به بخش «✅ نشست تکمیل‌شده: فاز ۱۱ (R-Repo-Reports-Notif)» در انتهای همین سند
نگاه کن؛ دستور کار کامل فاز ۱۲ هم همون‌جا (در انتهای اون بخش) نوشته شده.

## نشست تکمیل‌شده: فاز ۱۱ (R-Repo-Reports-Notif) — ۲۰۲۶-۰۹-۲۱

یک grep سراسری `ReportExport::`/`ScheduledReport::`/`ScheduledReportRun::`/`NotificationSetting::`/
`UserNotification::`/`UserReportSetting::` در کل `app/` زده شد. نتیجه‌ی مهم: دامنه‌ی واقعی این
فاز خیلی کوچیک‌تر از چیزی بود که جدول فازبندی نشون می‌داد.

### کار انجام‌شده
- `ReportExportRepository` جدید (Interface + Eloquent) — ۳ متد: `paginateWithAdminUser`,
  `getOlderThanWithStatuses`, `deleteByIds`
- `NotificationSettingRepository` جدید (Interface + Eloquent) — ۴ متد: `getAllKeyedByEventKey`,
  `firstOrCreateForEvent`, `getByEventKeys`, `updateOrCreateForEvent`
- یک متد جدید (`getTypesByCodes`) به `DiscountCodeRepository` موجود (از فاز ۶) اضافه شد، برای
  رفع `DiscountCode::whereIn` مستقیم در `AdminReportService.php` — دامنه‌ی این فایل جزو فاز ۱۱
  بود (طبق یادداشت نشست قبل)، هرچند مدلش (`DiscountCode`) نه
- فایل‌های ریفکتورشده: `NotificationSettingService`, `AdminNotificationSettingController`,
  `AdminReportExportController`, `GeneratePdfReportJob` (هم `handle()` هم `failed()`),
  `CleanupReportExports` (Artisan Command)، `AdminReportService`

### یافته‌ی مهم: دامنه‌ی واقعی این فاز کوچیک‌تر از جدول فازبندی بود
- `ScheduledReport`, `ScheduledReportRun`, `UserReportSetting`: هر سه مدل وجود دارن، ولی هیچ‌جای
  `app/` مستقیم کوئری نمی‌شن — فقط یک رابطه‌ی `belongsTo` بینشون تعریف شده. یا فیچر برنامه‌ریزی‌شده
  و هنوز پیاده‌نشده‌ست، یا کاملاً کد مرده. **هیچ کاری برای این سه مدل لازم نبود**
- `UserNotification`: `AdminNotificationController` و `SpecialistNotificationController` هیچ‌کدوم
  مستقیم `UserNotification::` صدا نمی‌زنن — `User::notifications()`/`Specialist::notifications()`
  یک رابطه‌ی سفارشی (`morphMany(UserNotification::class, ...)`) است، نه یک static call. طبق
  همون قرارداد که رابطه‌ها (`belongsTo` و مشابه) همیشه در تمام فازهای قبلی هم مستثنا بودن، این
  دو کنترلر **هیچ تغییری نیاز نداشتن**

### مرزهای رعایت‌شده (طبق درسِ فاز ۸/۹/۱۰)
- قبل از تزریق constructor به هر سرویس (`NotificationSettingService`، `AdminReportService`)، یک
  grep برای `new <ServiceName>(` زده شد — هیچ‌کدوم دستی ساخته نمی‌شن
- `GeneratePdfReportJob::failed()` — طبق تحقیق مستقیم رفتار Laravel (queue worker مستقیم
  `$job->failed($e)` صدا می‌زنه، نه از طریق `app()->call()` مثل `handle()`)، اینجا از
  `app(ReportExportRepositoryInterface::class)` (service-locator) استفاده شد، نه یک پارامتر
  دوم روی امضای `failed()`
- **این‌بار قبل از commit، طبق درس فاز ۸ (که اون‌موقع رگرسیون واقعی ایجاد کرد)، یک grep برای
  `->handle(` روی `GeneratePdfReportJob` زده شد قبل از نهایی‌کردن امضاش** — ۷ محل فراخوانی مستقیم
  `handle()` در `AdminReportExportTest.php` و `AdminReportExcelCellContentTest.php` پیدا و همون
  لحظه (نه بعد از دیدن fail) اصلاح شدن. سوییت از تلاش اول سبز شد — برخلاف فاز ۸ که این الگو رو
  فقط بعد از دیدن ۳۷۶ error کشف کرد

### یک نکته‌ی جانبی (نه بخشی از این فاز، رفع‌شده در همین commit برای تمیزی)
یک فیکس Pint از فاز ۱۰ (ترتیب import در `AppServiceProvider.php`) به working tree اعمال شده بود
ولی قبل از commit فاز ۱۰ دوباره `git add` نشده بود — یعنی هیچ‌وقت واقعاً commit نشد (صرفاً
کازمتیک، ترتیب import، بدون اثر عملکردی). توی همین فاز، به‌عنوان یک نکته‌ی جانبی، دوباره اضافه و
commit شد.

### تست و وریفای
- کل سوییت روی محیط اصلی: **۱۰۸۸ passed / ۱ skipped / صفر fail** (دو بار اجرا شد، هر دو سبز)
- Laravel Pint: `PASS` روی هر ۱۶ فایل تغییریافته/جدید
- پچ (`0001-refactor-repo-Phase-11-R-Repo-Reports-Notif-extract-.patch`) روی یک `git am` مستقل
  اعمال شد (کلون جدا، فقط تا آخر فاز ۱۰). سوییت کامل روی این کلون **سه بار** اجرا شد: بار اول
  یک fail غیرمرتبط داد (`BookingServiceTest`، به بخش «قدم‌های باز» زیر نگاه کن)، دو بار بعدی
  کاملاً سبز — تأیید شد که یک flaky test بود، نه رگرسیون این پچ

### قدم‌های باز برای نشست بعدی
فاز ۱۲ تمام شد (جزئی — به بخش «✅ نشست تکمیل‌شده: فاز ۱۲» در انتهای همین سند نگاه کن). دستور کار
کامل نشست بعدی هم همون‌جا (در انتهای اون بخش) نوشته شده — شامل یک فاز کاملاً جدید و بزرگ
(`R-Repo-BookingSweep`) که همون نشست کشف کرد.

⚠️ یادآوری: توکن GitHub PAT همچنان تازه نشده — این نشست هم از روی زیپ اولیه‌ی آپلودشده و
کلون‌های محلی کار کرد.

## نشست تکمیل‌شده: فاز ۱۲ (R-Repo-Sweep) — ۲۰۲۶-۰۹-۲۱

این فاز طبق تعریف خودش یک ممیزی نهایی بود، و همین موضوع باعث شد از حجم پیش‌بینی‌شده‌ش خیلی بزرگ‌تر
دربیاد — یک فاز کامل جدید (بزرگ‌تر از فاز ۸!) وسطش کشف شد.

### کار انجام‌شده
- **`SupportTicket`/`SupportTicketMessage`**: بررسی کامل شد — مدل‌های کاملاً پیاده‌شده (با
  `Spatie\Activitylog`، scope، متدهای کمکی) + یک migration که هر دو جدول رو می‌سازه، ولی **هیچ**
  Controller/route/view/Job/تستی بهشون اشاره نمی‌کنه. یک فیچر کاملاً ساخته‌شده ولی هیچ‌جا وصل‌نشده.
  دست‌نخورده موند — تصمیم با ابوالفضل (ساخت UI براش، یا حذف کامل)
- **`app/Traits/HasRoles.php`**: کشف شد که کد مرده‌ست — تعریف شده ولی هیچ مدلی `use`ش نمی‌کنه؛
  `User` به‌جاش یک نسخه‌ی دستی و تکراری از `assignRole()`/`removeRole()` داره. ۳ تا `Role::` داخلش
  دست‌نخورده موندن چون غیرقابل‌اجرا هستن
- **یافته‌ی واقعی**: `Role` و `Permission` صراحتاً جزو دامنه‌ی فاز ۸ بودن («User, Role, Permission,
  salon_admins») ولی توی اون نشست فقط `User::` ریفکتور شد — `Role::`/`Permission::` کاملاً
  جاافتاده بودن. این دقیقاً همون چیزیه که یک فاز sweep باید پیدا کنه، پس همین‌جا رفع شد:
  - `RoleRepository` جدید (۸ متد) و `PermissionRepository` جدید (۵ متد)، هر دو بایند در
    `RepositoryServiceProvider`
  - همه‌ی محل‌های واقعی `Role::`/`Permission::` ریفکتور شدن: `app/Models/Role.php` و
    `app/Models/User.php` با `app()` (چون Model نمی‌تونه constructor injection بگیره)،
    `CreateSuperAdmin` (Console Command) با method injection (طبق درس فاز ۸/۱۱، اول برای
    `->handle(` مستقیم در تست‌ها گرپ زدم — چیزی پیدا نشد)، بقیه با constructor injection معمولی
  - **یک باگ واقعی همین‌جا پیدا و رفع شد**: `PermissionRepository::getDistinctGroups()`
    امضاش `Illuminate\Database\Eloquent\Collection` بود ولی `Builder::pluck()` واقعاً
    `Illuminate\Support\Collection` برمی‌گردونه — یک `TypeError` واقعی که سوییت تست
    (`AdminPermissionTest`) گرفت، نه بازبینی دستی کد
- **دو مورد «جامونده‌ی عمدی» از پایان فاز ۱۰** بسته شدن: `SuperAdminService`'s دو
  `Specialist::withoutGlobalScope('salon')->count()` → متد جدید
  `SpecialistRepositoryInterface::countBySalonIgnoringScope()`؛ و
  `SuperAdminController::invoices()`'s `Invoice::withoutGlobalScope('salon')` → متد جدید
  `InvoiceRepositoryInterface::paginateForSalonIgnoringScope()`. حین دست‌زدن به
  `SuperAdminController`، یک مورد جانبی دیگه هم توی همون متد `dashboard()` پیدا و رفع شد:
  `Specialist::count()` → `SpecialistRepositoryInterface::count()` (متد پایه) — کشفش شد چون
  حذف import بی‌استفاده‌ی `Specialist` می‌خواست این فراخوانی زنده رو هم بشکنه

### یافته‌ی بزرگ، **رفع‌نشده در این نشست** (خارج از ظرفیت یک نشست)
یک grep گسترده‌تر برای `Specialist::`، `Booking::`، و `BeautyService::` در کل `app/` زده شد
(بعد از این‌که رفع `Specialist::count()` بالا نشون داد ممکنه جاهای دیگه هم جامونده باشن) —
نتیجه: **~۲۰ محل واقعی `Specialist::`، ~۴۷ محل `Booking::`، و ~۱۴ محل `BeautyService::`**
(غیر از تعریف رابطه‌ها)، پخش‌شده در فایل‌های زیاد، که همه‌شون از قبل از تاریخچه‌ی قابل‌مشاهده‌ی
این گفتگو باقی موندن (هر «فاز ۲/۳»ی که قبلاً این مدل‌ها رو پوشش داده، شفاف نبوده). این از نظر
حجم قابل‌مقایسه با جاروب `User::` فاز ۸ است — در واقع `Booking::` به‌تنهایی بزرگ‌تر از `User::`
بود. **عمداً در همین نشست به این دست نزدم** — جدول فازبندی رو با یک ردیف جدید (`۱۲b —
R-Repo-BookingSweep`) به‌روز کردم؛ فهرست کامل فایل‌ها رو باید نشست بعدی از نو با grep دربیاره
(اینجا فقط شمارش خام گزارش شد، نه فهرست فایل‌به‌فایل).

### تست و وریفای
- کل سوییت روی محیط اصلی: **۱۰۸۸ passed / ۱ skipped / صفر fail**
- Laravel Pint: `PASS` روی هر ۱۹ فایل تغییریافته/جدید
- پچ (`0001-refactor-repo-Phase-12-R-Repo-Sweep-Role-Permission-.patch`) روی یک `git am` مستقل
  اعمال شد (کلون جدا، فقط تا آخر فاز ۱۱). سوییت کامل **دو بار** روی این کلون اجرا شد (به‌خاطر
  flaky test فاز قبل، این‌بار برای اطمینان بیشتر) — هر دو بار کاملاً سبز

### یادداشت باقی‌مانده از فاز ۱۱ (برای رکورد)
Flaky test که در وریفای فاز ۱۱ یک‌بار دیده شد
(`BookingServiceTest::test_cancel_booking_on_a_paid_booking_triggers_the_wallet_refund`) توی
وریفای این فاز (دو اجرای کامل) دیگه دیده نشد — الگوی flaky بودن (نه رگرسیون) با اطمینان بیشتری
تأیید شد.

### قدم‌های باز برای نشست بعدی
فاز ۱۲b تمام شد — به بخش «✅ نشست تکمیل‌شده: فاز ۱۲b (R-Repo-BookingSweep)» در انتهای همین سند
نگاه کن؛ دستور کار فاز ۱۳ هم همون‌جا نوشته شده.

⚠️ یادآوری: توکن GitHub PAT همچنان تازه نشده — این نشست هم از روی زیپ اولیه‌ی آپلودشده و
کلون‌های محلی کار کرد.

## نشست تکمیل‌شده: فاز ۱۲b (R-Repo-BookingSweep) — ۲۰۲۶-۰۹-۲۱

فاز کاملاً جدیدی که در پایان فاز ۱۲ کشف شد — از نظر حجم قابل‌مقایسه با فاز ۸ (`Booking::` به‌تنهایی
حدود ۲۱ محل بیشتر از `User::` فاز ۸ داشت).

### کار انجام‌شده
- هر سه ریپازیتوری (`Specialist`, `Booking`, `BeautyService`) از فازهای اولیه از قبل وجود داشتن
  با متدهای substantial — این فاز اون‌ها رو گسترش داد، نه از صفر ساخت: یک متد `query(): Builder`
  (escape hatch، هم‌الگو با تمام فازهای قبلی برای کوئری‌های گزارش‌گیری/جستجوی ad-hoc) به هر سه
  اضافه شد، به‌علاوه یک متد اختصاصی کوچیک (`BeautyServiceRepositoryInterface::getLatest()`)
- همه‌ی محل‌های واقعی سه مدل در حدود ۲۰ فایل ریفکتور شدن — بیشترشون بیش از یک مدل رو با هم مخلوط
  داشتن (`DashboardController`, `HomeController`, `AdminSearchController`,
  `AdminDashboardService` هر سه مدل رو داشتن؛ `AdminReportService` تنهایی ۲۱ محل از هر سه مدل
  داشت — با یک token-replace مکانیکیِ تأییدشده انجام شد، چون هر رخداد واقعاً یک static call بود،
  نه بخشی از comment/type-hint)
- یک الگوی تکراری واقعی پیدا شد: `Specialist::where('phone', $user->phone)->first()` — دقیقاً
  همون متدی که `SpecialistRepository::findByPhone()` از یک فاز قبلی از قبل داشت. به‌جای
  `query()`، از همون متد موجود استفاده شد — در `SpecialistReportController` و ۶ بار عیناً تکراری
  در `SpecialistNotificationController`
- Jobs/Commands (`SendBookingReminderJob`, `CancelUnpaidBookings`, `SendBookingReminders`,
  `CleanupPendingBookings`): طبق درس فازهای ۸/۱۱، اول برای `->handle(` مستقیم در تست‌ها گرپ زده
  شد — ۳ محل در `NotificationJobsTest.php` برای `SendBookingReminderJob` پیدا و همون لحظه اصلاح
  شدن
- `RouteServiceProvider`'s سه بایندینگ (`Specialist::`, `Booking::`, `BeautyService::`) عمداً
  دست‌نخورده موندن، هم‌الگو با هر فاز قبلی

### دو باگ واقعی، هر دو با تست کشف شدن نه بازبینی دستی
1. `AdminReportService`'s token-replace درست `use App\Models\{BeautyService,Booking,Specialist}`
   رو حذف کرد چون دیگه به‌صورت static استفاده نمی‌شدن — ولی `calcSpecialistScore(Specialist
   $specialist)` هنوز به همون type hint نیاز داشت. PHP این رو در زمان parse خطا نداد (چون
   `Specialist` بدون import رو نسبت به namespace فعلی resolve کرد، به یک کلاس ناموجود) — فقط در
   زمان اجرا (`AdminReportsControllerTest`) ترکید. `use App\Models\Specialist;` برگردونده شد.
2. دو تست (`AdminReportsControllerTest`, `SalonStaffFinancePermissionTest`) با
   `$this->partialMock(AdminReportService::class, ...)` کار می‌کنن — که Mockery رو مستقیم روی
   نام کلاس می‌سازه، **بدون صدازدن constructor واقعی**. چون این فاز پراپرتی‌های
   readonly جدیدی (repository interface ها) به constructor این کلاس اضافه کرد، هر متد
   unstubbed (مثل `getSummary()`، که این دو تست واقعاً صداش می‌زنن) با خطای «must not be accessed
   before initialization» می‌ترکید. راه‌حل: یک نمونه‌ی واقعی از طریق container ساخته شد
   (`$this->app->make(...)`) و بعد همون نمونه (نه نام کلاس) با `Mockery::mock($real)
   ->makePartial()` پوشونده شد — این‌طوری constructor واقعی صدا زده می‌شه و همه‌ی dependency ها
   واقعی می‌مونن، فقط متد مشخص‌شده (`monthlyBreakdown()`) stub می‌شه.

### تست و وریفای
- کل سوییت روی محیط اصلی: **۱۰۸۸ passed / ۱ skipped / صفر fail** — **دو بار** اجرا شد (با توجه
  به حجم بزرگ این فاز)، هر دو بار کاملاً سبز
- Laravel Pint: `PASS` روی هر ۲۶ فایل تغییریافته
- پچ (`0001-refactor-repo-Phase-12b-R-Repo-BookingSweep-extract-.patch`) روی یک `git am` مستقل
  اعمال شد (کلون جدا، فقط تا آخر فاز ۱۲). سوییت کامل **دو بار** روی این کلون هم اجرا شد — هر دو
  بار کاملاً سبز

### یادداشت مهم برای فازهای بعدی (درس این فاز)
وقتی یک کلاس رو به constructor injection مجهز می‌کنی، همیشه چک کن آیا اون کلاس جایی توی تست‌ها با
`partialMock()`/`mock()` روی نام کلاس (نه نمونه) mock می‌شه — این الگو constructor واقعی رو صدا
نمی‌زنه و با پراپرتی‌های readonly جدید می‌ترکه. `grep -rn "partialMock(<ClassName>::class\|mock(<ClassName>::class" tests/`
قبل از نهایی‌کردن امضای constructor، هم‌تراز با چک همیشگی `new <ClassName>(` که از فاز ۸ به بعد
انجام می‌شه.

### قدم‌های باز برای نشست بعدی
فاز ۱۳ تمام شد — به بخش «✅ نشست تکمیل‌شده: فاز ۱۳ (R-Repo-CommentSweep)» در انتهای همین سند نگاه
کن. فاز ۱۴ (`R-Repo-Final`، آخرین فاز جدول) رو شروع کن: اجرای کامل `php artisan test` بعد از
همه‌ی فازها + گزارش نهایی + جمع‌بندی جدول فازبندی در همین سند.

آیتم‌های باز باقی‌مانده (بدون فاز مشخص، تصمیم با ابوالفضل):
- `SupportTicket`/`SupportTicketMessage` (فاز ۱۲: مدل‌های کاملاً ساخته‌شده، هیچ‌جا وصل‌نشده)
- `Announcement::getActiveAnnouncements()`/`App\Http\Requests\Specialist\RespondReviewRequest`
  (کد مرده، فازهای قدیمی‌تر)

⚠️ یادآوری: توکن GitHub PAT همچنان تازه نشده — این نشست هم از روی زیپ اولیه‌ی آپلودشده و
کلون‌های محلی کار کرد.

## نشست تکمیل‌شده: فاز ۱۳ (R-Repo-CommentSweep) — ۲۰۲۶-۰۹-۲۱

برخلاف فازهای ۸ تا ۱۲b (ریفکتور منطق)، این فاز صرفاً کامنت‌زدایی از فایل‌های دست‌نخورده بود —
بدون پوشش تستی برای این نوع تغییر (کامنت‌ها روی رفتار اثر ندارن)، پس ریسک اصلی «از دست‌رفتن
مستندات باارزش» بود، نه «باگ». به همین خاطر رویکرد کاملاً محتاطانه و marker-aware بود، نه یک
حذف کورکورانه.

### روش کار
هر بلوک کامنت قبل از حذف، برای وجود مارکر `⭐`/`⚠️`/متن فارسی چک شد — فقط بلوک‌های کاملاً عمومی
و تولیدشده‌ی خودِ Laravel (یکسان با نصب پیش‌فرض) حذف شدن. مستندات تصمیم/باگ‌فیکس مخصوص این
پروژه کاملاً دست‌نخورده موند — با شمارش تعداد مارکر قبل/بعد هر فایل تأیید شد (دقیقاً یکسان).

### کار انجام‌شده
- `config/*.php`: بلوک‌های استاندارد `/* |----| Title |----| ... */` از ۱۴ فایل از ۱۶ حذف شدن.
  `config/billing.php` (مستندات مفصل منطق قیمت‌گذاری) و `config/middleware-aliases.php` هیچ
  تغییری نیاز نداشتن
- `database/migrations/*.php`: داک‌بلاک‌های عمومی `/** Run the migrations. */` و `/** Reverse
  the migrations. */` از ۲۰ فایل از ۵۳ حذف شدن (قبل از اعمال، تأیید شد که هیچ‌کدوم از ۱۸ فایل
  دارای مارکر این الگوی عمومی رو نداشتن — یعنی حذف mechanical امن بود)
- `database/factories/` + `database/seeders/` (۵۹ فایل): تقریباً هیچی لازم نبود — فقط یک
  داک‌بلاک عمومی (`UserNotificationSeeder.php`) در کل دو پوشه وجود داشت. فکتوری/سیدرهای این
  پروژه از اول تمیز نوشته شده بودن
- `app/Providers/` (۶ فایل دست‌نخورده از فازهای ۸ تا ۱۲b): دو بلوک `@var string` تکراری
  (`RouteServiceProvider`) و داک‌بلاک‌های تولیدشده‌ی پکیج Telescope
  (`TelescopeServiceProvider`) حذف شدن. `AuthServiceProvider`, `EventServiceProvider`,
  `PaymentServiceProvider`, `SMSServiceProvider` هیچ تغییری نیاز نداشتن (صفر کامنت عمومی).
  کامنت‌های توضیحی واقعی `RouteServiceProvider` (یادداشت باگ ثبت تکراری روت، منطق rate limiter)
  عمداً دست‌نخورده موندن، حتی بدون `⭐` — چون واقعاً تاریخچه‌ی پروژه‌ن، نه نویز عمومی

### `routes/*` (۴۹ فایل) — بررسی‌شده، عمداً کاملاً دست‌نخورده
۱۳ فایل مارکر `⭐`/`⚠️` متراکم دارن، و از ۳۶ فایل بی‌مارکر باقی‌مونده، فقط ۵ تا اصلاً کامنت دارن —
هرکدوم توضیح واقعی و مخصوص پروژه‌ست (یک باگ اجرای دوباره‌ی واقعی در `console.php`، یک یادداشت
واقعی جلوگیری از حلقه‌ی بی‌نهایت ریدایرکت در `web/payments.php`، یک یادداشت تاریخچه‌ی فیچر
حذف‌شده در `admin/schedule.php`) — نه بویلرپلیت. ۳۱ فایل باقی‌مونده صفر کامنت دارن. چیزی برای حذف
امن پیدا نشد.

### تست و وریفای
- کل سوییت روی محیط اصلی: **۱۰۸۸ passed / ۱ skipped / صفر fail**
- Laravel Pint: `PASS` روی هر ۱۳۶ فایل پنج پوشه‌ی دست‌خورده
- پچ (`0001-chore-Phase-13-R-Repo-CommentSweep-strip-generic-boi.patch`) روی یک `git am` مستقل
  اعمال شد (کلون جدا، فقط تا آخر فاز ۱۲b). سوییت کامل روی این کلون هم ۱۰۸۸/۱ (skip) کاملاً سبز
  بود — به‌علاوه یک چک اضافی مخصوص این فاز: `php artisan config:cache` روی همون کلون بدون خطا
  اجرا شد (یک اعتبارسنجی سخت‌گیرانه‌تر از تست معمولی، چون واقعاً هر فایل config رو parse و
  serialize می‌کنه)

## نشست تکمیل‌شده: فاز ۱۴ (R-Repo-Final) — ۲۰۲۶-۰۹-۲۱

آخرین فاز جدول فازبندی — گزارش نهایی کل پروژه‌ی Repository Pattern (فازهای ۰ تا ۱۳).

### وضعیت نهایی
- کل سوییت تست: **۱۰۸۸ passed / ۱ skipped / صفر fail** — بدون تغییر نسبت به baseline شروع فاز ۸،
  در طول ۷ فاز پیاپی (۸، ۹، ۱۰، ۱۱، ۱۲، ۱۲b، ۱۳) هرکدوم با وریفای مستقل روی یک کلون جدا بعد از
  `git am`
- تعداد Repository جدید ساخته‌شده در این هفت فاز: `UserRepository`, `SecurityLogRepository`,
  `SalonRepository`, `SalonSmsUsageRepository`, `ReportExportRepository`,
  `NotificationSettingRepository`, `RoleRepository`, `PermissionRepository` — به‌علاوه گسترش
  چند Repository موجود از فازهای اولیه (`DiscountCodeRepository`, `InvoiceRepository`,
  `SpecialistRepository`, `BookingRepository`, `BeautyServiceRepository`)
- تعداد کل commit این هفت فاز: ۷ commit مجزا (یکی به‌ازای هر فاز)، هرکدوم با پیام commit کامل و
  مستند
- دو رگرسیون واقعی در طول این فازها پیدا و رفع شدن (هر دو با تست، نه بازبینی دستی): باگ
  `SMSService` constructor injection (فاز ۸) و باگ Mockery partial-mock constructor (فاز ۱۲b)؛
  به‌علاوه چند باگ کوچک‌تر type/nullable (فازهای ۸، ۱۰، ۱۲)

### آیتم‌های باز که با تصمیم صریح ابوالفضل نیاز به بسته‌شدن داشتن (نه بخشی از این فازبندی)
✅ هر چهار آیتم این فهرست حالا بسته شدن:
- **`app/Traits/HasRoles.php`**، **`app/Providers/SMSServiceProvider.php`**: بعد از پایان فاز
  ۱۴، همون نشست — کد مرده‌ی بدون‌ابهام (صفر مصرف‌کننده، دوباره تأیید شد)، بدون نیاز به تصمیم
  جداگانه حذف شدن
- **`Announcement::getActiveAnnouncements()`**: در نشست بعدی، بعد از بررسی مجدد به درخواست
  ابوالفضل، تأیید شد کاملاً بی‌استفاده‌ست (grep در کل `app/`, `routes/`, `resources/`, `tests/`
  — صفر فراخواننده؛ scopeهای `active()`/`byPriority()` زیرینش جای دیگه مستقیم استفاده می‌شن) —
  حذف شد
- **`App\Http\Requests\Specialist\RespondReviewRequest`**: بررسی مجدد نشون داد این کد مرده
  نبود، بلکه یک FormRequest درست‌نوشته‌شده بود که هیچ‌وقت واقعاً به کنترلر وصل نشده بود —
  `SpecialistReviewController::respond()`/`updateResponse()` هر دو یک کپی عیناً‌تکراری از همون
  اعتبارسنجی رو inline داشتن. به‌جای حذف، در همون کنترلر wire شد (تکرار کد حذف شد، بدون تغییر
  رفتار)
- **`SupportTicket`/`SupportTicketMessage`**: طبق تصمیم صریح ابوالفضل («UI براش بساز»)، یک UI
  کامل ساخته شد — به بخش «✅ نشست تکمیل‌شده: SupportTicket UI» در انتهای همین سند نگاه کن

### پیشنهاد برای بعد از این جدول
جدول فازبندی رسماً تمام شد (فاز ۰ تا ۱۴)، و هر ۴ آیتم کد مرده‌ی باز هم بسته شدن (بالا رو نگاه
کن). یک حوزه هنوز باز مونده که ارزش یک فاز/نشست جداگانه داره:
- اگه بعداً معلوم شد جای دیگه‌ای هم Repository pattern جا افتاده (شبیه کشف `Role`/`Permission`
  در فاز ۱۲ یا `Booking`/`Specialist`/`BeautyService` در فاز ۱۲b)، یک sweep نهایی و جامع‌تر —
  شاید با ابزار خودکارتر (مثل یک اسکریپت PHP-Parser-based به‌جای grep دستی) قابل‌اطمینان‌تر باشه

⚠️ یادآوری برای نشست بعدی: توکن GitHub PAT دوباره چک بشه — این کل زنجیره‌ی فازهای ۸ تا ۱۴ از روی
همون زیپ اولیه‌ی آپلودشده در ابتدای این گفتگو و کلون‌های محلی کار کردن، نه از GitHub مستقیم.

## نشست تکمیل‌شده: بستن آیتم‌های باز (RespondReviewRequest + SupportTicket UI) — ۲۰۲۶-۰۹-۲۲

بعد از پایان فاز ۱۴، ابوالفضل صریحاً برای دو آیتم باز باقی‌مونده تصمیم داد: «برای `SupportTicket`
UI بساز» و «برای `RespondReviewRequest` بیشتر بررسی کن، شاید واقعاً کد مرده نباشه».

### `RespondReviewRequest` — یافته‌ی مهم: کد مرده نبود
بررسی مجدد نشون داد `SpecialistReviewController::respond()` و `updateResponse()` هر دو یک نسخه‌ی
inline و عیناً‌تکراری از همون قوانین اعتبارسنجی (`response: required|string|max:1000`) داشتن —
`RespondReviewRequest` یک FormRequest درست‌نوشته‌شده (با پیام خطای فارسی) بود که ساخته شده ولی
هیچ‌وقت واقعاً به کنترلر متصل نشده بود. به‌جای حذف، در هر دو متد wire شد (`Request $request` →
`RespondReviewRequest $request`, `$request->validate([...])` → `$request->validated()`) — تکرار
کد حذف شد، هیچ تغییر رفتاری‌ای نداد (همون قوانین، همون authorization جریان داخل بدنه‌ی متد).
`Announcement::getActiveAnnouncements()` بعد از بررسی مجدد (grep در `app/`, `routes/`,
`resources/`, `tests/`) واقعاً بدون فراخواننده تأیید شد — حذف شد.

### `SupportTicket`/`SupportTicketMessage` — UI کامل ساخته شد
**طراحی**: جدول `support_tickets` هیچ ستون `salon_id` نداره — این یک سیستم تیکت سراسری
(غیرسالن‌محور) است. با توجه به معماری موجود پروژه (Admin = اپراتور سالن، SuperAdmin = اپراتور
پلتفرم)، طراحی شد: ادمین‌های سالن از پنل `/admin` تیکت ثبت می‌کنن، SuperAdmin پلتفرم از پنل
`/superadmin` بهشون رسیدگی می‌کنه.

**دو باگ واقعی در مدل‌های «کاملاً ساخته‌شده»، پیدا و رفع شدن قبل از ساخت هر چیز روی اون‌ها**:
1. `SupportTicketMessage::getAttachmentUrlsAttribute()` بدون `use
   Illuminate\Support\Facades\Storage` — هر پیامی با پیوست، خطای fatal می‌داد
2. `SupportTicket::logActivity()` از هِلپر `activity()` پکیج Spatie استفاده می‌کرد، ولی **هیچ
   migration جدولی `activity_log` در کل پروژه وجود نداشت**، و مدل تِریت `LogsActivity` رو هم
   اصلاً apply نمی‌کرد (یعنی `getActivitylogOptions()` تعریف شده بود ولی کاملاً بی‌اثر). رفع شد:
   یک migration جدید (دقیقاً منطبق با stub رسمی خودِ پکیج — جدول پایه + ستون `event` + ستون
   `batch_uuid`، همه در یک migration چون نصب تازه‌ست)، انتشار `config/activitylog.php`، اضافه‌شدن
   تِریت. کل چرخه‌ی حیات مدل (ساخت، `assignTo()`، `markAsResolved()`، `addMessage()`، ثبت
   activity log) مستقیم روی یک دیتابیس واقعی از طریق tinker تست شد، قبل از هر کار دیگه.

**ساخته‌شده**:
- `SupportTicketRepository` + `SupportTicketMessageRepository` (بایند در
  `RepositoryServiceProvider`)
- `SupportTicketService` (createTicket, addReply, assign, markResolved/Closed, reopen —
  `addReply()` خودکار وضعیت `open`→`in_progress` رو با پاسخ staff عوض می‌کنه، و یک تیکت
  حل‌شده/بسته‌شده رو با پاسخ مشتری دوباره باز می‌کنه)
- `SupportTicketPolicy` (bypass سوپرادمین، صاحب تیکت می‌تونه ببینه/پاسخ بده)، ثبت در
  `AuthServiceProvider`
- ۳ FormRequest — دسته‌بندی‌های `StoreSupportTicketRequest` (`booking, payment, service,
  technical, other`) عمداً با دسته‌بندی‌های از‌قبل‌موجودِ `SupportTicketFactory` هم‌تراز شدن، نه
  یک taxonomy عمومی ابداعی
- دو کنترلر: `Admin\SupportTicket\SupportTicketController` (تیکت‌های خودِ ادمین) و
  `SuperAdmin\SupportTicketController` (صندوق پلتفرم با فیلتر وضعیت/اولویت/واگذاری)
- ۱۲ روت (۵ ادمین، ۷ سوپرادمین)، لینک‌های نویگیشن در هر دو `layouts/admin.blade.php` و
  `layouts/superadmin.blade.php`
- ۵ ویو Blade، دقیقاً هم‌سبک با قراردادهای موجود (`--admin-*`/`sa-card`/`sa-btn`)
- ۱۲ تست ویژگی جدید (ایجاد تیکت، ایزوله‌بودن بین ادمین‌ها با ۴۰۳، بازشدن خودکار تیکت حل‌شده با
  پاسخ، درستی فیلتر، واگذاری، چرخه‌ی کامل resolve/close/reopen)

### تست و وریفای
- سوییت کامل: **۱۱۰۰ passed (۱۰۸۸ baseline + ۱۲ جدید) / ۱ skipped / صفر fail** — دو بار روی
  محیط اصلی
- Laravel Pint: `PASS` روی هر ۲۹ فایل PHP تغییریافته/جدید
- پچ (`0001-feat-build-UI-for-SupportTicket-SupportTicketMessage.patch`) روی یک `git am` مستقل
  اعمال شد. سوییت کامل روی این کلون هم ۱۱۰۰/۱ (skip) سبز بود؛ به‌علاوه یک migration تازه از صفر
  (`migrate --force` روی دیتابیس خالی) و `route:list` هر دو بدون خطا روی همون کلون تأیید شدن

### قدم‌های باز
هیچ آیتم بازی از فهرست «تصمیم با ابوالفضل» باقی نمونده. یک sweep نهایی و جامع‌تر (به بخش «✅
نشست تکمیل‌شده: sweep نهایی» در انتهای همین سند نگاه کن) هم انجام و تموم شد.

⚠️ یادآوری: توکن GitHub PAT همچنان تازه نشده.

## نشست تکمیل‌شده: sweep نهایی — کشف Model:: در Blade و route closures — ۲۰۲۶-۰۹-۲۲

طبق درخواست صریح ابوالفضل («یک sweep نهایی و جامع‌تر انجام بده»)، این نشست دقیقاً همون چیزی رو
پیدا کرد که پیشنهاد شده بود: **هر ۷ فاز repository (۸ تا ۱۲b) فقط `app/` رو grep می‌زدن، هیچ‌وقت
`resources/views/` یا `routes/Channels.php` رو چک نکرده بودن.**

### یافته‌های واقعی، همه رفع‌شدن
- **`admin/specialists/show.blade.php`**: یک `User::where('phone', ...)->first()` داخل یک بلوک
  `@php` — منتقل شد به `AdminSpecialistController::show()`، با استفاده از متد
  `UserRepository::findByPhone()` که از فاز ۸ از قبل وجود داشت
- **`layouts/app.blade.php`** (لایوت مشترک همه‌ی صفحات): یک کوئری `LoyaltyPoint::` sum، همراه یک
  کش ۵دقیقه‌ای که قبلاً یک فیکس واقعی performance بوده (کامنتش کاملاً حفظ شد) — به
  `app(LoyaltyPointRepositoryInterface::class)->sumForUser()` تبدیل شد؛ دقیقاً منطبق با متد
  موجودش
- **`loyalty/my-codes.blade.php`**: دو کوئری `DiscountCode::` — منتقل شدن به
  `LoyaltyController::myCodes()`. یکی (`getActiveForUser`) دقیقاً با متد موجود مطابقت داشت؛
  برای دیگری یک متد جدید (`getExpiredForUser`) اضافه شد
- **سه فایل `admin/bookings/show.blade.php`, `specialists/create.blade.php`,
  `specialists/edit.blade.php`**: هر سه `WalletSetting::first()` (یک static call واقعی،
  متفاوت از قرارداد جاافتاده‌ی `WalletSetting::get()` که دست‌نخورده می‌مونه) — به
  `app(WalletSettingRepositoryInterface::class)->first()` تبدیل شدن
- **`routes/Channels.php`**: دو closure احرازهویت broadcast-channel (`Payment::find()`،
  `Booking::find()`). **قبل از فیکس، سورس خودِ `Illuminate\Broadcasting\Broadcasters\Broadcaster`
  چک شد** — این closure ها مستقیم با `$handler($user, ...$parameters)` صدا زده می‌شن، **نه** از
  طریق container. یعنی تلاش اول من (اضافه‌کردن یک پارامتر تایپ‌شده‌ی سوم به closure) یک
  `TypeError` واقعی در زمان اجرا می‌داد، چون فقط مقادیر wildcard مسیر channel پاس داده می‌شن، نه
  چیز دیگه‌ای. فیکس درست: `app(...)` داخل بدنه‌ی closure، بدون تغییر امضای closure

### مرزهای رعایت‌شده
- `app/View/Composers/ViewComposer.php` چک و کاملاً تمیز تأیید شد (از abstraction
  `CurrentSalon` استفاده می‌کنه، نه کوئری خام)
- تمام seeder/factory ها (که مدل رو مستقیم صدا می‌زنن) عمداً دست‌نخورده موندن — همون قرارداد
  همیشگی این پروژه که زیرساخت تست/seed جزو دامنه‌ی این فازها نیست
- `config/auth.php` و `ReviewFactory.php`'s match فقط رفرنس `::class` بودن (نه فراخوانی متد)،
  خارج از دامنه

### تست و وریفای
- ۲ تست رگرسیون واقعی اضافه شد (پوشش تست قبلی این دو صفحه فقط «۲۰۰ برمی‌گردونه» بود، نه چک
  محتوا) — یکی تأیید می‌کنه کدهای فعال/منقضی درست تفکیک می‌شن، یکی تأیید می‌کنه وضعیت حساب
  لینک‌شده/نشده‌ی متخصص درست گزارش می‌شه
- فیکس `Channels.php` تست اختصاصی نداره — `BROADCAST_CONNECTION=log` هست و هیچ‌جا
  `Broadcast::routes()` صدا زده نشده، پس هیچ endpoint واقعی‌ای برای تست HTTP وجود نداره؛ به‌جاش
  resolve‌شدن درست هر دو Repository Interface از طریق container با tinker تأیید شد
- کل سوییت: **۱۱۰۲ passed (۱۱۰۰ + ۲ جدید) / ۱ skipped / صفر fail** — هم روی محیط اصلی، هم روی
  کلون مستقل بعد از `git am`
- Laravel Pint: `PASS` روی هر ۱۳ فایل تغییریافته

### قدم‌های باز
هیچ. این sweep دقیقاً همون چیزی بود که در انتهای فاز ۱۴ پیشنهاد شده بود، و چیزی که پیدا کرد
(کوئری‌های پنهان در Blade views و route closures) دقیقاً نشون‌دهنده‌ی ارزش انجامش بود. اگه در
آینده باز هم دلیلی برای شک به جامانده‌گی repository pattern پیدا شد، این sweep یادآوریه که محدوده
باید فراتر از `app/` باشه — `resources/views/`, `routes/`, و هر closure دیگه‌ای که مدل رو مستقیم
صدا می‌زنه.

⚠️ یادآوری: توکن GitHub PAT همچنان تازه نشده.

## نشست تکمیل‌شده: یکی‌سازی Migration ها (۵۴ → ۳۶ فایل) — ۲۰۲۶-۰۹-۲۲

طبق درخواست صریح ابوالفضل، بعد از یک بحث دقیق درباره‌ی ایندکس‌گذاری و ریسک‌های ادغام، هر ۲۰
migration ای که فقط جدول‌های ساخته‌شده در migration دیگه رو تغییر می‌دادن (۱۷ تای «ساده» + ۳ تای
«پیچیده‌تر») در همون migration اصلی‌شون ادغام شدن.

### بخش A: ۱۷ مورد ساده (بدون پیچیدگی داده/ترتیب)
- `wallet_settings`: ۳ migration (admin_commission_percentage، prepayment_percentage +
  minimum_prepayment_amount، ۴ ستون specialist_cancellation_*) ادغام شدن
- `specialists`: commission_rate + nullable‌شدن user_id ادغام شدن
- `users` (بخش schema-only): two_factor_enabled/code/expires_at و
  password_changed_at/password_strength_score ادغام شدن — به‌علاوه یک ایندکس جدید روی
  `two_factor_enabled` (طبق درخواست صریح)
- `bookings`: notes، source، و ستون تولیدی پیچیده‌ی active_slot_key (با شاخه‌بندی
  MySQL/SQLite/Postgres) ادغام شدن
- `specialist_schedules`: break_start/break_end ادغام شدن
- `work_schedules`: یک جدول که در یک migration ساخته و در migration دیگه‌ای کامل حذف شده بود
  (فیچر هیچ‌وقت واقعاً استفاده نشد) — هر دو فایل (بلوک ساخت + migration حذف) کاملاً حذف شدن
- `salons`: zarinpal_merchant_id، tagline/bio، sms_quota_per_month ادغام شدن
- همه‌ی مستندات ⭐ عیناً توی فایل‌های ادغام‌شده حفظ شدن

وریفای بخش A: سوییت کامل روی SQLite سبز، به‌علاوه یک `migrate --force` کاملاً تازه روی MySQL واقعی.

### بخش B: ۳ مورد پیچیده‌تر (به‌هم‌وابسته از نظر ترتیب ساخت جدول)
`add_salon_id_to_owned_tables`، `backfill_default_salon_and_salon_id`، و
`add_salon_id_and_user_type_to_users_table` — هر سه کاملاً جذب شدن، هر سه فایل حذف شدن.

**مانع اصلی**: `salons` باید قبل از ۱۲ جدولی که بهش وابسته‌ن وجود داشته باشه، ولی
`salons.created_by` به `users` وابسته‌ست و `users.salon_id` (ستونی که داشتیم ادغام می‌کردیم) به
`salons` وابسته‌ست — یک وابستگی دوری واقعی. راه‌حل: `create_salons_table` به قبل از
`create_users_table` منتقل شد (تایم‌استمپ `0000_01_01_000000`، بدون created_by)، یک migration
پیگیری ۳خطی (`0001_01_01_000001_add_created_by_to_salons_table`) بعد از ساخت users اضافه شد که
created_by رو برمی‌گردونه، و `create_salon_admins_table` هم به بلافاصله بعدش منتقل شد.

با وجود `salons` از همون اول، `salon_id NOT NULL` مستقیم توی migration اصلی هر کدوم از ۱۲ جدول
(specialists، beauty_services، categories، blog_posts، blog_categories، gallery_images،
announcements، discount_codes، loyalty_settings، wallet_settings، admin_wallet، bookings) اضافه
شد — بدون نیاز به رقص nullable-سپس-NOT-NULL و بدون نیاز به دور زدن باگ MySQL 1832، چون این حالا
schema تازه‌ست نه ALTER روی جدول با داده‌ی موجود. `salon_id` (nullable) در `report_exports` هم
همین‌طوری ادغام شد؛ تایپوی فاصله‌ی نام فایلش (`2026_07_21 _000001_...`) هم موقع دست‌زدن بهش رفع شد.

منطق `salon_id`/`user_type`/سیستم generated-column برای تفکیک unique constraint (که پیچیده‌ترین
بخش هر سه فایل بود، با یک راه‌حل دور زدن باگ واقعی MySQL 1901 که قبلاً روی یک دیپلوی واقعی
تأییدشده) کامل، با تمام کامنت‌هاش، داخل `create_users_table.php` ادغام شد.

### دو اشتباه واقعی، هر دو با تست پیدا و رفع شدن — نه بازبینی دستی
1. **باگ خودم**: اولین ویرایش `create_users_table.php` به‌طور تصادفی کل ستون `phone` رو حذف
   کرد (یک `str_replace` که بیشتر از چیزی که قصد داشتم match کرد). علامتش: ۱۰۴۳ خطای تست («no
   such column: phone»). بلافاصله رفع شد.
2. **تصحیح مهم‌تر**: فرض اولیه‌ام این بود که ساخت سالن پیش‌فرض «راستا» رو می‌شه از migration به
   `DatabaseSeeder` منتقل کرد (چون یک دیتابیس کاملاً تازه چیزی برای backfill نداره). این فرض
   اشتباه بود: `wallet_settings`، `admin_wallet`، و `loyalty_settings` هرکدوم یک ردیف پایه
   داشتن که بدون قید‌وشرط توسط خودِ migration ساختشون درج می‌شد (نه توسط یک seeder)، و **۸ فایل
   تست** در حوزه‌های Wallet/Booking/Specialist/Loyalty صریحاً به وجود همون ردیف وابسته بودن —
   چون `RefreshDatabase` فقط migration ها رو اجرا می‌کنه، هیچ‌وقت seeder صدا نمی‌زنه. رفع شد: سالن
   پیش‌فرض مستقیم داخل `create_salons_table.php` ساخته می‌شه (یک `DB::table()->insert()`
   بدون‌قید‌وشرط، نه `firstOrCreate` — این داده‌ی bootstrap ـه، نه seed داده‌ی idempotent)، و ردیف‌های
   پیش‌فرض wallet_settings/admin_wallet/loyalty_settings هم توی همون migration های خودشون
   برگردونده شدن، این‌بار با salon_id درست. `DatabaseSeeder`'s خودش (`firstOrCreate`) یک لایه‌ی
   محافظتی اضافه‌ست برای جریان seed داده‌ی نمایشی، نه منبع اصلی این ردیف.

### تست و وریفای نهایی
- سوییت کامل: **۱۱۰۲ passed / ۱ skipped / صفر fail** — دو بار روی محیط اصلی، یک بار روی کلون مستقل
- یک `migrate --force` کاملاً تازه روی MySQL واقعی (هم بعد از بخش A، هم بعد از بخش B، هم روی کلون
  مستقل) — هر سه بار بدون خطا
- یک `db:seed --force` کامل روی همون MySQL تازه (هم محیط اصلی، هم کلون مستقل) — بررسی دستی بعدش:
  صفر `salon_id` تهی روی هر جدول salon-owned، کاربرهای staff درست `salon_id=NULL` (سراسری)،
  کاربرهای customer درست `salon_id=<راستا>` (محدود به سالن)، و `SHOW CREATE TABLE users` تأیید
  کرد ستون‌های generated و unique index ها دقیقاً همون‌طور که باید هستن
- Laravel Pint: `PASS` روی هر ۹۵ فایل ردیابی‌شده‌ی `database/`

### تعداد نهایی
**۵۴ → ۳۶ فایل migration** (۱۸ فایل خالص کمتر، با احتساب ۳ فایل جدید کوچیک که برای حل وابستگی
دوری لازم بودن).

### قدم‌های باز
هیچ. یک اصلاح مهم بعد از این نشست انجام شد — به بخش «✅ نشست تکمیل‌شده: بازگردوندن migration ها
به pure-schema» در انتهای همین سند نگاه کن.

## نشست تکمیل‌شده: بازگردوندن migration ها به pure-schema — ۲۰۲۶-۰۹-۲۲

بعد از نشست قبلی (یکی‌سازی migration ها)، ابوالفضل یک سؤال کلیدی پرسید: به‌جای نگه‌داشتن منطق
bootstrap (ساخت سالن پیش‌فرض + ردیف‌های پیش‌فرض تنظیمات) داخل migration ها، نمی‌شه اون ۸ فایل
تستِ وابسته رو اصلاح کرد تا migration ها بتونن کاملاً pure-schema بمونن و `DatabaseSeeder` تنها
مسئول داده باشه؟ جواب: بله، و این کار یک رده‌ی کامل از باگ واقعی رو هم آشکار کرد.

### چرا این تصمیم درست بود
موقع بررسی، مشخص شد `base TestCase::setUp()` از قبل و **مستقل از migration ها** سالن پیش‌فرض
«راستا» رو می‌سازه و `CurrentSalon` رو ست می‌کنه — یعنی نیازی نبود این منطق تو migration بمونه؛
فقط باید تست‌هایی که مستقیم به یک ردیف از پیش‌موجود در `WalletSetting`/`LoyaltySetting` وابسته
بودن (نه به خودِ سالن) اصلاح می‌شدن.

### یافته‌ی مهم: یک رده کامل از باگ واقعی، قبلاً پنهان‌شده
با برداشتن ردیف‌های bootstrap از migration، معلوم شد **۵ فایل کد واقعیِ اپلیکیشن** (نه فقط تست)
همیشه فرض می‌کردن یک ردیف `WalletSetting` از قبل وجود داره — چون migration قبلی همیشه دقیقاً یک
ردیف تضمین می‌کرد، این فرض هیچ‌وقت واقعاً امتحان نشده بود:
- `SpecialistWithdrawalController::create()` — برای هر سالن بدون تنظیمات کیف پول، صفحه‌ی برداشت
  ۵۰۰ می‌داد
- `SpecialistWalletService::getWalletOverview()` — همون ریسک، صفحه‌ی کیف پول متخصص
- `WalletAdminService::updateSettings()` — اولین باری که یک ادمین برای یک سالن تازه تنظیمات
  کیف پول رو ذخیره می‌کرد، کرش می‌کرد
- `AdminWalletSettingsController::index()` — همون ریسک، سمت ادمین
- `SpecialistWallet::addIncome()`/`canWithdraw()`/`calculateWithdrawalFee()` — سه محل دیگه توی
  خودِ مدل
- `Specialist::getEffectiveCommissionRate()` و **`StoreWithdrawalRequest::walletSettings()`**
  (این یکی جدی‌ترین بود — چون return type غیر-nullable `: WalletSetting` داره، یک `first()`
  تهی باعث `TypeError` واقعی می‌شد، نه فقط یک warning قابل‌چشم‌پوشی)

همه‌ی این ۱۰ محل به `WalletSetting::get()` (متد ایمنِ `first() ?? create([])` که خودِ مدل داره،
از قبل درست‌استفاده‌شده جاهای دیگه مثل AdminSecurityService) تبدیل شدن.

### کار انجام‌شده
- ۲۲ رخداد در ۵ فایل تست: `WalletSetting::first()->update(...)` → `WalletSetting::get()->update(...)`
- `LoyaltyServiceTest.php`: `LoyaltySetting::where(...)->update()` (یک no-op اگه ردیف نباشه) →
  `updateOrCreate(...)`
- migration ها (`create_salons_table`, `create_wallet_tables`, migration مربوط به `admin_wallet`,
  `create_loyalty_system_tables`) به pure-schema برگردونده شدن — بدون هیچ insert داده‌ای
- یک کامنت گمراه‌کننده در `StoreWithdrawalRequest.php` هم اصلاح شد (کامنت قبلی هشدار می‌داد از
  `WalletSetting::get()` که با `Model::get()` عمومی Eloquent اشتباه گرفته می‌شد؛ توضیح داده شد
  که این دو متفاوتن و `get()` سفارشیِ خودِ این مدل کاملاً امنه)

### تست و وریفای
- سوییت کامل: **۱۱۰۲ passed / ۱ skipped / صفر fail** — دو بار روی محیط اصلی، یک بار روی کلون مستقل
- `migrate --force` کاملاً تازه روی MySQL واقعی — تأیید شد بلافاصله بعدش صفر ردیف توی
  `salons`/`wallet_settings`/`loyalty_settings` (یعنی واقعاً pure-schema شده)
- `db:seed --force` کامل روی همون MySQL — تأیید شد دقیقاً یک سالن، یک `wallet_settings`، یک
  `admin_wallet`، و `salon_id` درست روی همه‌ی جدول‌های salon-owned
- Laravel Pint: `PASS` روی هر ۱۸ فایل تغییریافته

### یک نکته‌ی جانبی (مستندشده، بدون نیاز به اقدام)
حین وریفای، یک ناسازگاری از‌قبل‌موجود (نه چیزی که این نشست ایجاد کرد) دیده شد:
`LoyaltyBasicDataSeeder` کلیدهای `points_per_currency`/`min_points_redemption` می‌سازه، درحالی‌که
`LoyaltyService` واقعاً کلیدهای `points_per_amount`/`points_expiry_months` رو می‌خونه — دو seeder/
consumer با نام‌گذاری متفاوت. چون `LoyaltySettingRepository::getValue()` یک `$default` امن داره،
این هیچ کرشی ایجاد نمی‌کنه (فقط یعنی مقدار پیش‌فرض کد همیشه استفاده می‌شه، نه مقدار seed‌شده) —
خارج از دامنه‌ی این کار، فقط برای رکورد مستند شد.

### قدم‌های باز
هیچ.

---

## نشست ۲۰۲۶-۰۹-۲۳ — صفحه‌ی فروش دامنه‌ی مرکزی + دوره‌ی آزمایشی + نمایش آدرس سالن به مالک

### درخواست ابوالفضل
کسی که هنوز آدرس هیچ سالنی رو نداره، با `http://127.0.0.1:8000/` یا `rasta-app.test` باید به یک
صفحه‌ی اصلی کامل برسه که همه‌ی امکانات رو (بر اساس پلن‌ها) با پیش‌نمایش توضیح بده و بشه ازش اشتراک
گرفت؛ بعد از خرید، مالک سالن باید آدرس اختصاصی سالنش رو بگیره تا به مشتری‌هاش بده. سؤال جانبی:
چند روز دوره‌ی آزمایشی؟ → پیشنهاد **۱۴ روز** (یک چرخه‌ی کامل مشتری هفتگی/دوهفتگی؛ ۷ کم، ۳۰ هزینه‌ی
پیامک و تأخیر تصمیم)، ابوالفضل با «ادامه بده» تأیید کرد. قابل تغییر با `SUBSCRIPTION_TRIAL_DAYS`
(۰ = خاموش).

### یافته‌ها (قبل از کد)
- دامنه‌ی خام بدون `CENTRAL_DOMAIN` عمداً ۴۰۴ می‌داد (`BareDomainWithoutCentralDomainTest`) و با
  `CENTRAL_DOMAIN` فقط یک placeholder بود — درخواست امروز همون «تصمیم صریح» لازم برای عوض‌کردنشه.
- **آدرس عمومی سالن هیچ‌جای پنل به مالکش نشون داده نمی‌شد** — نه داشبورد، نه billing، نه پیام
  پرداخت موفق. یعنی سالن self-service عملاً لینکی برای مشتری‌ها دریافت نمی‌کرد.
- «پلن‌ها» فقط در مدت/قیمت فرق دارن؛ `module_permissions` فقط ذخیره می‌شه و هیچ‌جا اعمال نمی‌شه.
  صفحه‌ی فروش عمداً امکانات متفاوت وعده نمی‌ده. اگه روزی پلن‌های پایه/حرفه‌ای لازم شد، اول باید
  اعمال `module_permissions` ساخته بشه.
- ⚠️ `.env` لوکال ابوالفضل هنوز قیمت‌های placeholder قدیمی رو داره
  (`SUBSCRIPTION_PRICE_1M=490000` …)، که config محاسبه‌شده (۷۲۰٬۰۰۰ …) رو override می‌کنه —
  صفحه‌ی فروش و پنل خرید هر دو همین اعداد رو نشون می‌دن. باید دستی اصلاح/حذف بشه.
- ⚠️ **باگ از‌قبل‌موجود (رفع‌شده در ادامه‌ی همین نشست، پچ ۰۰۰۵)**: با `CENTRAL_DOMAIN` پر،
  `php artisan route:cache` روی `develop` (`afb3764`) با
  `LogicException: Another route has already been assigned name [home]` شکست می‌خورد — چون
  `$tenantRoutes` دو بار (prefix و domain) با نام‌های یکسان ثبت می‌شد. `docker/entrypoint.sh` روی
  production از `route:cache` استفاده می‌کنه، پس deploy ساب‌دامینی بالا نمیومد.

### سه پچ (به ترتیب)
1. `feat(onboarding): free trial period for self-service salon signup`
   - migration جدید `2026_09_23_000001_add_trial_ends_at_to_salons_table` (ستون nullable؛ بدون
     enum/وضعیت جدید — دسترسی آزمایشی کاملاً با همون `subscription_ends_at` کار می‌کنه)
   - `config/billing.php`: `trial_days` (پیش‌فرض ۱۴) و `trial_sms_quota` (پیش‌فرض ۳۰۰، از طریق
     override موجود `sms_quota_per_month`)
   - `Salon::isOnTrial()`/`trialDaysLeft()`/`hasPaidInvoice()` (با `withoutGlobalScope('salon')`،
     چون scope سراسری Invoice بر پایه‌ی CurrentSalon اینجا فقط جواب غلط می‌ده)/`isTrialSmsQuotaInEffect()`
   - `SuperAdminService::renewSubscription` (تنها نقطه‌ی تمدید، آنلاین و دستی): اولین خرید سقف
     پیامک آزمایشی رو null می‌کنه — override دستی سوپرادمین (هر عددی غیر از عدد آزمایشی) هرگز —
     و روزهای باقی‌مونده‌ی آزمایشی رو دور نمی‌ریزه
   - مالک سالن آزمایشی بعد از OTP به `admin.home` می‌ره نه billing؛ فرم ثبت‌نام `?plan=` رو پیش‌انتخاب می‌کنه
   - بعد از پایان آزمایشی همون `EnsureAdminSalonActive`/`ResolveSalonFromRoute` بدون کد جدید می‌بندن
2. `feat(admin): show the salon's public booking URL to its owner`
   - `Salon::publicUrl()` = `route('home', ['salon_slug' => slug])` (با CENTRAL_DOMAIN ساب‌دامین،
     وگرنه `/s/{slug}`) و `legacyPublicUrl()` = `/s/{slug}` روی هاست جاری
   - partial جدید `admin.partials.salon-public-link` (لینک + کپی با fallback برای http غیرامن +
     مشاهده + وضعیت فعال/آزمایشی/غیرفعال) روی داشبورد و billing؛ پیام پرداخت موفق هم آدرس رو داره
   - billing: وضعیت «دوره‌ی آزمایشی رایگان»، نام فارسی نوع اشتراک، پیش‌انتخاب پلن فعلی سالن
3. `feat(central): full sales landing page on the bare central domain`
   - `Route::get('/')->name('central.home')` بدون `Route::domain` و **بعد از** گروه ساب‌دامین —
     ترتیب ثبت تضمین می‌کنه `{slug}.central/` همچنان خانه‌ی سالنه (تست شده)
   - `CentralLandingController` (invokable) — همه‌ی اعداد از config/billing.php، تاریخ‌ها شمسی
   - `central/landing.blade.php` (self-contained): انتخاب آدرس با چک زنده (`check-slug`)، ماکت
     موبایل، روند سه‌مرحله‌ای، امکانات سه پنل، پیش‌نمایش تب‌دار سه پنل با tokenهای واقعی،
     کارت پلن‌ها + dialog «پیش‌نمایش پلن»، سؤالات رایج؛ فرم ثبت‌نام `?slug=` رو پیش‌پر می‌کنه
   - `central/placeholder.blade.php` حذف شد

### تست و وریفای
- baseline روی `develop` (`afb3764`): ۱۱۰۲ passed / ۱ skipped (یادآوری: بدون `npm run build`،
  ۱۷۸ تست فقط به‌خاطر نبودن manifest ویت fail می‌شن — خطای محیطی، نه کد)
- بعد از سه پچ، روی کلون مستقل با `git am`: **۱۱۲۱ passed / ۱ skipped / صفر fail**؛
  `phpunit.subdomain.xml`: **۱۰ passed**
- تست‌های جدید: `SalonTrialTest` (۸)، `SalonPublicLinkTest` (۶)، `CentralLandingPageTest` (۵)،
  ۲ تست در `SubdomainRoutingTest`؛ `BareDomainWithoutCentralDomainTest` بازنویسی و
  `SalonSignupTest` روی `trial_days=0` پین شد (مسیر «بدون آزمایشی» مستند بمونه)
- دستی (بدون route:cache): `127.0.0.1` و `rasta-app.test` → صفحه‌ی فروش، `almas.rasta-app.test`
  → خانه‌ی سالن، `/s/almas` → ۲۰۰؛ اسکرین‌شات دسکتاپ/موبایل/dialog بررسی شد
- Laravel Pint: `PASS` روی ۱۵ فایل PHP تغییریافته

### ادامه‌ی نشست (همون روز) — درخواست ابوالفضل: «۱. .env رو به‌روز کن ۲. باگ رو رفع کن»

**۱. `.env` لوکال** (فایل gitignore‌شده؛ به‌صورت فایل جدا تحویل داده شد، نه پچ): فقط بلوک قیمت
عوض شد (`490000/1350000/2500000/4500000` → `720000/1990000/3670000/6610000`، همون مقادیر
config و `.env.example`) و چهار کلید جاافتاده اضافه شد: `SMS_QUOTA_PER_MONTH=1500`،
`DEFAULT_MAX_SPECIALISTS_COUNT=3`، `SUBSCRIPTION_TRIAL_DAYS=14`، `TRIAL_SMS_QUOTA=300`. بقیه‌ی
فایل (DB، کلیدها، زرین‌پال، کاوه‌نگار، `CENTRAL_DOMAIN`، `SESSION_DOMAIN`) بایت‌به‌بایت دست‌نخورده.
بعد از جایگزینی: `php artisan config:clear`. حالا مجموعه‌ی کلیدهای `.env` با `.env.example` یکیه.

**۲. پچ ۰۰۰۵ — `fix(routing): make route:cache work when CENTRAL_DOMAIN is set`**
- `routes/web.php`: گروه `/s/{slug}` فقط وقتی `CENTRAL_DOMAIN` پره `->name('legacy.')` می‌گیره.
  نام‌های بی‌پیشوند دقیقاً مثل قبل متعلق به نسخه‌ی ساب‌دامینی‌ان (رفتار «آخرین ثبت برنده‌ست»،
  حالا صریح)؛ بدون `CENTRAL_DOMAIN` هیچ تغییری نیست. URI/middleware/controller دست‌نخورده.
- جایگزین رد‌شده: حذف گروه `/s/{slug}` در حالت ساب‌دامینی و ریدایرکت ۳۰۱ به ساب‌دامین — تمیزتره
  ولی تصمیم تأییدشده‌ی ۲۰۲۶-۰۹-۱۹ («لینک قدیمی زنده بمونه، نه فقط ریدایرکت») رو عوض می‌کرد.
- اثر جانبی بی‌خطر: روی مسیر قدیمی `request()->routeIs('dashboard')` (رنگ فعال منوی
  `layouts/navigation`) false می‌شه؛ `SecurityMiddleware` هم نام `legacy.*` لاگ می‌کنه.
- ⚠️ **قانون جدید**: هر کدی که از این به بعد `routeIs('xxx')` روی routeهای تننت می‌نویسه، باید
  `routeIs('xxx', 'legacy.xxx')` بنویسه اگه باید روی مسیر `/s/{slug}` هم درست کار کنه.
- ⚠️ بعد از هر تغییر `CENTRAL_DOMAIN`: `php artisan route:clear` (کش routes تصمیم سطح-boot رو منجمد می‌کنه).
- تست: `SubdomainRoutingTest` +۲ (compile کل RouteCollection — همون مسیر route:cache؛ **بدون رفع
  با همون LogicException fail می‌شه، وریفای شد**) و `BareDomainWithoutCentralDomainTest` +۱.
- `WILDCARD_SUBDOMAIN_DEPLOYMENT.md` هم به‌روز شد.
- وریفای: سوییت کامل **۱۱۲۲ passed / ۱ skipped**، ساب‌دامین **۱۲ passed**، Pint PASS؛ دستی با
  `route:cache` واقعی: دامنه‌ی خام و 127.0.0.1 → صفحه‌ی فروش، `almas.rasta-app.test` → خانه‌ی
  سالن، `/s/almas` و زیرمسیرهاش → ۲۰۰، ساب‌دامین ناموجود → ۴۰۴.

### دور سوم همین نشست (۲۰۲۶-۰۹-۲۳) — بازخورد ابوالفضل روی صفحه‌ی فروش و فرم ساخت سالن

⚠️ **سری پچ بازسازی شد**: هیچ‌کدوم از پچ‌های قبلی این نشست هنوز روی `develop` اعمال نشده بود، پس کل
سری با پیام‌های انگلیسی از نو ساخته شد و دو commit جدای «docs: update prompt» قبلی حذف و در یک
commit نهایی docs ادغام شدن. سری نهایی روی `afb3764` (به ترتیب):
1. `feat(onboarding): free trial period for self-service salon signup`
2. `feat(admin): show the salon's public booking URL to its owner`
3. `feat(central): full sales landing page on the bare central domain`
4. `fix(routing): make route:cache work when CENTRAL_DOMAIN is set`
5. `style(central): single typeface on the landing page and drop the mock salon URLs`
6. `chore(billing): raise subscription prices, one-month plan starts at 1,500,000 toman`
7. `feat(billing): a purchase during the free trial starts the paid plan immediately`
8. `feat(signup): remove the plan picker from the create-salon form`
9. `feat(salon): per-salon address, phone, experience and working hours`
10. `feat(signup): let a new salon skip the free trial and pay right away`
11. `docs: update Rasta unified prompt (...)`

**تصمیم‌ها و تغییرات این دور:**
- **فونت (۱):** صفحه‌ی فروش فقط Vazirmatn (Reem Kufi حذف شد، خواندن رو سخت می‌کرد)؛ سلسله‌مراتب با
  اندازه/وزن. صفحه‌های ثبت‌نام و تایید OTP هم از Noto Naskh Arabic به Vazirmatn رفتن.
- **آدرس‌های نمایشی (۲):** آدرس ساختگی `salon-e-shoma...` از ماکت موبایل و نوار مرورگر پیش‌نمایش حذف شد.
- **قیمت‌ها (۳):** یک‌ماهه ۱٬۵۰۰٬۰۰۰ / سه‌ماهه ۴٬۱۵۰٬۰۰۰ / شش‌ماهه ۷٬۶۵۰٬۰۰۰ / یک‌ساله ۱۳٬۸۵۰٬۰۰۰
  تومان (همون نردبان تخفیف ≈۸٪/۱۵٪/۲۳٪). عدد ۷۲۰ هزار قبلی «کف هزینه» بود، نه قیمت فروش.
  `.env` لوکال ابوالفضل (فایل `rasta.env` تحویلی) هم با همین اعداد.
- **خرید وسط آزمایشی (۴) — جایگزین رفتار قبلی:** اشتراک پولی از **همون لحظه‌ی خرید** شروع می‌شه و
  `trial_ends_at` به «الان» می‌آد (پاک نمی‌شه، تا سابقه‌ی «آزمایشی گرفته» بمونه). منبع واحد:
  `Salon::subscriptionPeriodBase()` — هم `SuperAdminService::renewSubscription` هم `period_start`
  فاکتور در `InvoiceService` از همین می‌خونن. خارج از آزمایشی، تمدید مثل قبل روی دوره‌ی جاری سوار می‌شه.
- **حذف انتخاب پلن از فرم ساخت سالن (۵) — برای همه:** پلن فقط در `admin.billing.index` انتخاب می‌شه.
  `?plan=` صفحه‌ی فروش فقط hidden می‌مونه و در `salons.subscription_type` ذخیره می‌شه (پیش‌انتخاب
  صفحه‌ی خرید). `subscription_type` در request حالا nullable، پیش‌فرض `1m`.
- **اطلاعات تماس و فعالیت هر سالن (۶ و ۷):** migration `2026_09_23_000002`: `salons.address`،
  `salons.phone`، `salons.established_year` (سال شروع فعالیت — فرم «سابقه به سال» می‌گیره تا
  `experienceYears()` هر سال خودکار زیاد بشه)، `salons.working_hours` (JSON با کلید روز Carbon،
  ۰=یکشنبه، هم‌قرارداد `specialist_schedules.day_of_week`؛ `{open,close}` یا null=تعطیل).
  - `App\Support\SalonWorkingHours` (defaults / fromInput / errors / lines با ادغام روزهای یکسان)
  - `App\Http\Requests\Concerns\ValidatesSalonContactDetails`: در ثبت‌نام عمومی **اجباری**، در فرم
    سوپرادمین **اختیاری**؛ ارقام فارسی و خط‌تیره در تلفن/سابقه پذیرفته می‌شن.
  - نمایش (ViewComposer: `currentSalonAddress/Phone/ExperienceYears/Hours`): فوتر `layouts/app`،
    شمارنده‌ی «سال تجربه» صفحه‌ی اصلی، تلفن `reviews/thank-you`. **برخلاف tagline/bio هیچ متن
    ساختگی پیش‌فرضی نداره** — خالی = اون خط نمایش داده نمی‌شه. ایمیل ساختگی مشترک فوتر حذف شد.
  - فرم سوپرادمین: partial `superadmin/salons/partials/contact-fields`؛ ساعات کاری در fieldset
    غیرفعال مگر تیک «ثبت ساعات کاری» — ذخیره‌ی فرم ویرایش یک سالن قدیمی هیچ‌وقت ساعات پیش‌فرض
    رو بی‌صدا روش نمی‌نویسه.
  - ⚠️ ساعات کاری سالن **فقط نمایشی‌ان**؛ زمان‌های قابل‌رزرو همچنان از برنامه‌ی هر متخصص میان.
  - seeder: سالن دمو `rasta` همون مقادیری رو گرفت که قبلاً در فوتر هاردکد بود.
- **خرید فوری بدون دوره‌ی رایگان (سؤال جدید ابوالفضل):** کارت هر پلن در صفحه‌ی فروش: دکمه‌ی اصلی
  «شروع ۱۴ روز رایگان» + لینک «خرید فوری، بدون دوره‌ی رایگان» → `?plan=..&intent=buy`. فرم ساخت
  سالن در این حالت فقط خلاصه‌ی فقط‌خواندنی پلن + قیمت + «تغییر پلن» نشون می‌ده. بعد از تایید OTP
  و لاگین، مستقیم به زرین‌پال — **همون مسیر پرداخت صفحه‌ی خرید پنل**
  (`createPendingOnlinePurchase` + `SubscriptionPaymentService::createPayment`، مرچنت سراسری،
  callback همون `admin.billing.callback`)؛ مسیر پرداخت دومی ساخته نشد. پرداخت موفق → اشتراک از همون
  لحظه (هیچ روز آزمایشی مصرف نمی‌شه). درگاه ناموفق/نیمه‌کاره → سالن از بین نمی‌ره، فاکتور failed،
  صاحب سالن با پیام «دوباره پرداخت کنید» به صفحه‌ی خرید می‌ره و تا اون موقع آزمایشی‌اش فعاله. با
  `SUBSCRIPTION_TRIAL_DAYS=0` کارت‌ها فقط دکمه‌ی «خرید و پرداخت آنلاین» (همین مسیر) دارن.

**تست و وریفای این دور:** SalonTrialTest (بازنویسی مورد ۴)، SalonContactDetailsTest (۱۱)،
SalonBuyNowTest (۸)، payload مشترک `Tests\Concerns\SalonContactPayload`. سوییت کامل
**۱۱۴۶ passed / ۱ skipped**، ساب‌دامین **۱۲ passed**، Pint PASS؛ اسکرین‌شات کارت‌های پلن و فرم
ساخت سالن در حالت خرید فوری بررسی شد.

### قدم‌های باز
- صفحه‌ی ویرایش «اطلاعات سالن» برای خودِ مدیر سالن در پنل ادمین (فعلاً مثل name فقط سوپرادمین
  می‌تونه ویرایش کنه) — پیشنهاد، تصمیم با ابوالفضل
- (اختیاری) پیامک خوش‌آمد به مالک سالن با آدرس سالن بعد از ثبت‌نام/اولین خرید — هزینه‌ی پیامک داره
- `php artisan migrate` + `php artisan config:clear` + `php artisan route:clear` روی سیستم لوکال

### دور چهارم (۲۰۲۶-۰۹-۲۳) — باگ 419 Page Expired روی 127.0.0.1

- **گزارش ابوالفضل (بعد از اعمال ۱۱ پچ):** روی `http://127.0.0.1:8000` هر فرم POST (ساخت سالن،
  خرید فوری، ورود) خطای **419 Page Expired** می‌داد.
- **علت (در Chromium headless بازتولید شد):** `.env` لوکال `SESSION_DOMAIN=.rasta-app.test` داره (برای
  حالت ساب‌دامینی لازمه). روی هر هاست دیگه (127.0.0.1، localhost) کوکی‌های session و XSRF-TOKEN با
  `Domain=.rasta-app.test` فرستاده می‌شدن و مرورگر اصلاً ذخیره‌شون نمی‌کرد → بدون session، CSRF رد
  می‌شد. باگ از خودِ `.env` میومد، نه از پچ‌های این نشست؛ با آوردن صفحه‌ی فروش روی 127.0.0.1 آشکار شد.
- **رفع — پچ `fix(session): stop 419 Page Expired on 127.0.0.1 when SESSION_DOMAIN is set`:**
  `App\Http\Middleware\MatchSessionCookieDomainToHost`، prepend به گروه web (قبل از EncryptCookies و
  StartSession). فقط برای همون درخواست، اگه هاست زیرمجموعه‌ی `session.domain` نباشه (قاعده‌ی
  domain-match RFC 6265)، `session.domain` رو null می‌کنه → کوکی host-only. `rasta-app.test` و همه‌ی
  ساب‌دامین‌ها دقیقاً مثل قبل. هر دو آدرس هم‌زمان کار می‌کنن (session جدا). تغییر `.env` لازم نیست.
- ⚠️ **قانون:** هر وقت SESSION_DOMAIN تنظیم شده و 419 دیدی، اول چک کن مرورگر اصلاً کوکی ذخیره کرده یا نه
  (DevTools → Application → Cookies)؛ 419 همیشه به معنی «توکن منقضی شده» نیست.
- تست: `MatchSessionCookieDomainToHostTest` (۴؛ تست اول بدون middleware fail می‌شه — وریفای شد).
  سوییت کامل **۱۱۵۰ passed / ۱ skipped**، ساب‌دامین ۱۲ passed، Pint PASS. دستی در Chromium با سرور
  واقعی: ثبت‌نام آزمایشی → OTP → داشبورد؛ خرید فوری → OTP → مرحله‌ی پرداخت.

### ۲۰۲۶-۰۹-۲۴ — لوگوی اختصاصی سالن + جداسازی فایل‌های آپلودی

**درخواست/سؤال ابوالفضل:** (۱) هر سالن باید بتونه لوگوی خودش رو آپلود و استفاده کنه، مثل بقیه‌ی
اطلاعات per-salon. (۲) آیا هر عکسی که ادمین یک سالن آپلود می‌کنه (گالری/نمونه‌کار، خدمات، …) فقط در
همون سالن دیده می‌شه؟

**پچ‌ها (روی `c5bca24` / همون HEAD بعد از رفع 419):**
1. `feat(salon): per-salon logo, uploaded at signup and shown across the salon`
   - migration `2026_09_24_000001`: `salons.logo_path` (nullable).
   - `App\Support\SalonStorage` (پوشه‌ی `salons/{id}/{kind}`)، `App\Services\Salon\SalonLogoService`
     (replace فایل قبلی رو پاک می‌کنه، remove). اعتبارسنجی: اختیاری، PNG/JPG/WEBP، حداکثر ۲MB،
     حداقل ۶۴×۶۴. **SVG عمداً رد می‌شه** (دیسک public مستقیم سرو می‌کنه و SVG می‌تونه اسکریپت داشته باشه).
   - فرم ثبت‌نام عمومی (multipart، پیش‌نمایش زنده) و فرم ساخت/ویرایش سوپرادمین (آپلود، پیش‌نمایش، «حذف لوگو»).
   - نمایش (`currentSalonLogoUrl` در ViewComposer): هدر و فوتر سایت مشتری، هدر ورود/ثبت‌نام، favicon
     سایت مشتری و guest، سایدبار پنل ادمین (لوگو + نام سالن)، سایدبار پنل متخصص. بدون لوگو = آیکون قبلی.
2. `feat(storage): keep every salon's uploads in its own folder; fix gallery usage leak`
   - **جواب سؤال ۲:** نمایش از قبل جدا بود (BelongsToSalon روی GalleryImage/BeautyService/Category/
     BlogPost/BlogCategory + `CrossSalonImplicitBindingTest`). ولی فایل‌ها در پوشه‌های مشترک بودن، و
     **نشت واقعی:** «فضای مصرفی گالری» با `allFiles('gallery')` مجموع فایل‌های همه‌ی سالن‌ها رو نشون می‌داد.
   - آپلودهای جدید گالری/خدمات/دسته‌بندی/بلاگ → `salons/{id}/{kind}/`. فایل‌های قدیمی جابه‌جا نشدن (مسیرشون در DB).
   - فضای مصرفی فقط از رکوردهای گالری همین سالن.
3. `fix(central): stop claiming specialists can upload a portfolio` — صفحه‌ی فروش ادعا می‌کرد متخصص
   «پروفایل عمومی و نمونه‌کارها» رو ویرایش می‌کنه؛ پروفایل متخصص فقط نام/موبایل/رمز داره.

**نکته:** متخصص‌ها اصلاً عکس پروفایل ندارن (نه ستون، نه آپلود). اگه لازمه، فیچر جداست.

**تست:** `SalonLogoTest` (۷)، `SalonUploadIsolationTest` (۴؛ دو تست بدون رفع fail می‌شن — وریفای شد).
سوییت کامل **۱۱۶۱ passed / ۱ skipped**، Pint PASS.

### ۲۰۲۶-۰۹-۲۴ (ادامه) — کیف پول سوپرادمین، فیلتر سالن‌ها، اطلاعات سالن، عکس متخصص، مرچنت سالن

**درخواست‌های ابوالفضل:** (۱) بخش کیف پول سوپرادمین برای همه‌ی واریزهای خرید اشتراک با جستجو/فیلتر روی
همه‌ی جزئیات. (۲) جستجو/فیلتر لیست سالن‌ها روی همه‌ی ستون‌ها. (۳) دو پیشنهاد قبلی: صفحه‌ی «اطلاعات
سالن» برای مالک + عکس متخصص. (۴) سؤال: مرچنت آیدی هر سالن کجا وارد می‌شه؟ اگه نیست بساز؛ در ساخت سالن
اشاره بشه، و توضیح داده بشه که بدون مرچنت هیچ پرداختی ممکن نیست.

**پچ‌ها (روی `8658718`):**
1. `feat(superadmin): subscription payments ("wallet") section with search, filters and CSV` —
   `SubscriptionPaymentReport` (همیشه withoutGlobalScope('salon'))، `FilterSubscriptionPaymentsRequest`،
   `SuperAdminPaymentController` (index/show/export)، `App\Support\JalaliDateInput` (ورودی شمسی با
   ارقام فارسی و ماه/روز تک‌رقمی — Jalalian::fromFormat تک‌رقمی رو قبول نمی‌کنه). کارت‌های کلی، خلاصه‌ی
   فیلترشده، جدول، صفحه‌ی جزئیات، CSV با BOM.
2. `feat(superadmin): search and filter the salons list on every column` — `SalonListFilter` +
   `FilterSalonsRequest`؛ وضعیت trial/expiring_soon/…، سقف متخصص، بازه‌ی شمسی هر دو ستون تاریخ؛ نشان «آزمایشی».
3. `feat(admin): "salon information" page so the owner can edit their own salon` —
   `admin.salon-settings.*` داخل گروه `salon.owner`؛ slug عمداً غیرقابل‌تغییر.
4. `feat(specialist): profile photo, set by the salon admin or the specialist` — migration
   `2026_09_24_000002` (`specialists.photo_path`)، `SpecialistPhotoService` (کش home_specialists سالن رو
   پاک می‌کنه). **رفع حریم خصوصی:** کارت‌های «متخصصین ما» موبایل شخصی متخصص رو نشون می‌داد.
5. `feat(payments): every salon enters its own Zarinpal merchant id; no merchant, no online payment`
   - **جواب سؤال:** ستون `salons.zarinpal_merchant_id` بود ولی فقط سوپرادمین (بدون اعتبارسنجی) می‌تونست
     پرش کنه؛ و بدتر، `PaymentService` بی‌صدا به `ZARINPAL_MERCHANT_ID` پلتفرم برمی‌گشت → **پول مشتری‌های
     سالن بدون مرچنت به حساب پلتفرم می‌رفت.**
   - ⚠️ **قانون جدید:** مرچنت پلتفرم فقط برای خرید اشتراک سالن‌ها (`SubscriptionPaymentService`). پول
     مشتری‌های هر سالن فقط به مرچنت خود سالن؛ بدون مرچنت → `createPayment`/`createWalletChargePayment`
     قبل از هر درخواست `{success:false, reason:merchant_missing}`.
   - ورود مرچنت: فرم ثبت‌نام (اختیاری + توضیح)، صفحه‌ی «اطلاعات سالن» مالک (وضعیت فعال/غیرفعال)،
     فرم سوپرادمین. `App\Support\ZarinpalMerchant` (UUID ۳۶ کاراکتری، normalize به lower-case).
   - هشدار قرمز در داشبورد و صفحه‌ی اشتراک؛ بنر در صفحه‌ی رزرو مشتری؛ `BookingReservationController`
     confirm/store رو برای خدمات نیازمند پیش‌پرداخت رد می‌کنه (خدمت بدون پیش‌پرداخت همچنان قابل رزرو).
   - `SalonFactory` پیش‌فرض یک مرچنت fake داره (تست‌های پرداخت قبلی)؛ seeder مرچنت .env رو به سالن دمو می‌ده.
   - تست قدیمی «fallback به مرچنت سراسری» با «هرگز fallback نمی‌کنه» جایگزین شد.
6. `fix(public): add the missing public specialist profile page (was a 500)` — `specialists.show`
   وجود نداشت (۵۰۰ تأییدشده). ⚠️ routeهای search/top-rated/by-service/availability در
   `routes/web/public-specialists.php` هم view ندارن ولی هیچ لینکی بهشون نیست — تصمیم باز.

**تست:** SubscriptionPaymentsTest (۱۰)، SalonListFilterTest (۶)، AdminSalonSettingsTest (۸)،
SpecialistPhotoTest (۷)، SalonMerchantIdTest (۹)، PublicSpecialistProfileTest (۳). سوییت کامل
**۱۲۰۳ passed / ۱ skipped**، Pint PASS.

### ۲۰۲۶-۰۹-۲۴ (ادامه ۲) — صفحه‌های عمومی متخصص‌ها + بررسی «چند درگاه پرداخت»

**تصمیم ابوالفضل:** چهار route عمومی بدون view باید صفحه‌ی واقعی بگیرن (نه حذف).

**پچ `feat(public): specialist search, top-rated, by-service and availability pages`:**
- viewهای `specialists.search` / `top-rated` / `by-service` / `availability` + partialهای `card` و `nav`؛
  لینک «متخصص‌ها» در هدر سایت مشتری و «مشاهده‌ی همه» در صفحه‌ی اصلی.
- ⚠️ `/specialists/search` و `/top-rated` دو بار ثبت بودن (public-specialists.php و گروه auth در
  services.php) → دومی overwrite می‌کرد و صفحه‌های «عمومی» لاگین می‌خواستن. از services.php حذف شدن.
- sort/direction جستجو whitelist شد (قبلاً مستقیم به orderBy → ۵۰۰)؛ month/year تقویم clamp شد.
- `SpecialistRepository::getTopRated`: HAVING بدون GROUP BY فقط روی MySQL کار می‌کرد (SQLite خطا) → فیلتر در PHP.
- تست: `PublicSpecialistPagesTest` (۱۰). سوییت کامل **۱۲۱۳ passed / ۱ skipped**.

**سؤال ابوالفضل: چند درگاه پرداخت؟** — تحلیل کامل در همین نشست داده شد و سؤال‌هایی پرسیده شد؛
**هیچ کدی نوشته نشده، منتظر تصمیم.** یافته‌های کلیدی کد فعلی:
- زرین‌پال در سه سرویس جدا هاردکد شده: `PaymentService` (پیش‌پرداخت نوبت، باقی‌مانده، شارژ کیف پول
  مشتری — مرچنت سالن)، `Payment\SubscriptionPaymentService` (خرید اشتراک — مرچنت پلتفرم)،
  `Payment\ZarinpalPayoutService` (تسویه‌ی خودکار کیف پول متخصص).
- نام ستون‌ها/پارامترها زرین‌پالی: `invoices.authority`/`ref_id`، callbackها `Authority`/`Status` رو می‌خونن،
  `salons.zarinpal_merchant_id`، تبدیل تومان→ریال (×۱۰) داخل هر سرویس.
- ⚠️ **یافته‌ی جدی (اصلاح‌نشده):** `ZarinpalPayoutService` با مرچنت و API key **پلتفرم** تسویه می‌کنه،
  در حالی که درآمد متخصص از پرداخت مشتری‌ها به حساب **سالن** اومده → برداشت متخصص‌های هر سالن از
  موجودی زرین‌پال پلتفرم پرداخت می‌شه. نیاز به تصمیم.
- `App\Providers\PaymentServiceProvider` کد مرده‌ست (ثبت نشده در bootstrap/providers.php و با
  آرگومان‌های اشتباه به constructor می‌ده) — در بازطراحی درگاه‌ها حذف بشه.

### ۲۰۲۶-۰۹-۲۴ (ادامه ۳) — تصمیم‌های چند درگاه + تسویه از حساب سالن

**جواب‌های ابوالفضل:**
- سناریو: **هر سالن چند درگاه فعال** + انتخاب درگاه توسط مشتری یا جایگزینی خودکار (failover).
- درگاه‌ها (علاوه بر زرین‌پال): **زیبال، آیدی‌پی، نکست‌پی / پی‌پینگ، درگاه مستقیم بانکی (سامان/ملت/پارسیان)**.
- تسویه‌ی کیف پول متخصص: **از حساب درگاه خود سالن** (API هر سالن) + مدیر سالن باید **دستی هم** بتونه تسویه کنه.

**پچ `fix(payout): settle specialist withdrawals from the salon's own Zarinpal account` (انجام‌شده):**
migration `2026_09_24_000003` (`salons.zarinpal_payout_api_key`، cast `encrypted`، در `$hidden`)،
`Salon::canAutoPayout()`، `ZarinpalPayoutService` از مرچنت+توکن سالن (از طریق specialist، بدون scope چون در Job
اجرا می‌شه)، `WalletAdminService::autoPayout` قبل از processing چک می‌کنه، فیلد توکن در «اطلاعات سالن»
(password، هرگز نمایش داده نمی‌شه، خالی = بدون تغییر)، دکمه‌ی تسویه‌ی خودکار فقط وقتی سالن پیکربندی شده.
تسویه‌ی دستی (approve با کد پیگیری) از قبل وجود داشت و دست‌نخورده‌ست. تست: `SalonPayoutAccountTest` (۵).
سوییت کامل **۱۲۱۸ passed / ۱ skipped**.

**طرح پیاده‌سازی چند درگاه (هنوز کدی نوشته نشده — ترتیب مراحل):**
1. **مرحله‌ی ۰ (پیش‌نیاز، بدون تغییر رفتار):**
   - `App\Payments\Contracts\PaymentGateway`: `start(PaymentIntent): GatewayStart` (redirect GET یا فرم POST
     خودکار برای بانک‌ها)، `verify(Request, PaymentTransaction): GatewayVerification`، `supportsPayout()`، `refund()`.
   - `App\Payments\GatewayManager`: درگاه‌های فعال سالن به ترتیب priority؛ اگه start درگاه اول خطای اتصال داد →
     درگاه بعدی (failover)؛ اگه مشتری انتخاب کرده → همون.
   - جدول `salon_payment_gateways` (salon_id, driver, label, credentials json **encrypted**, is_active, priority,
     sandbox, supports_payout). migration داده: `salons.zarinpal_merchant_id` → یک ردیف zarinpal.
   - جدول واحد `payment_transactions` (payable morph: booking / invoice / wallet charge، gateway_id, driver,
     amount_rial, token/authority, ref_id, card_pan_masked, status, request/verify raw json, verified_at) +
     **یک callback مشترک** `/payments/return/{transaction}` که driver رو از تراکنش می‌خونه (نه از query).
   - تبدیل تومان→ریال فقط در یک جا (`PaymentIntent`)، مبلغ همیشه با خود درگاه در verify چک می‌شه، idempotent.
   - `PaymentService` / `SubscriptionPaymentService` روی manager؛ `ZarinpalPayoutService` → `PayoutGateway`.
   - حذف `App\Providers\PaymentServiceProvider` (مرده، ثبت نشده، آرگومان اشتباه).
2. **مرحله‌ی ۱:** driverهای درگاه‌های واسط (زیبال، آیدی‌پی، نکست‌پی، پی‌پینگ) + UI مدیریت درگاه‌ها در «اطلاعات
   سالن» (افزودن/ترتیب/فعال‌سازی/تست اتصال) + انتخاب درگاه در صفحه‌ی پرداخت مشتری.
3. **مرحله‌ی ۲:** درگاه‌های مستقیم بانکی (سامان: توکن + فرم POST + verify؛ ملت/به‌پرداخت و پارسیان: SOAP،
   تأیید/settle در مهلت، reverse) — نیاز به قرارداد شاپرک و ثبت IP سرور.
4. **مرحله‌ی ۳:** payout برای درگاه‌هایی که API تسویه دارن؛ بقیه فقط دستی.

⚠️ **پیش از نوشتن هر driver:** مستندات به‌روز همون سرویس از پنل/سایت رسمی‌اش بررسی بشه (endpointها عوض
می‌شن — مثلاً مستندات فعلی زرین‌پال آدرس `payment.zarinpal.com/pg/v4/payment/...` رو نشون می‌ده در حالی که
کد ما `api.zarinpal.com` رو صدا می‌زنه؛ PayPing نسخه‌ی v3 با callback به‌صورت POST داره؛ وضعیت فعلی سرویس
وب‌سرویس آیدی‌پی هم باید تأیید بشه).
⚠️ هر سالن باید دامنه/ساب‌دامین خودش رو در پنل درگاهش ثبت کنه (بررسی دامنه‌ی callback توسط درگاه‌ها).

### ۲۰۲۶-۰۹-۲۵ — چند درگاه: مرحله‌ی ۰ (بخش اول) + دو تصمیم جدید

**تصمیم‌های ابوالفضل:** (۱) رفتار پیش‌فرض: **مشتری درگاه رو انتخاب می‌کنه + اگه قطع بود خودکار سراغ بعدی**.
(۲) **کارمزد درگاه به مبلغ مشتری اضافه می‌شه** (نه اینکه سالن بده).

⚠️ **درس تحویل:** فایل‌های پچ نوبت قبل (۰۰۰۱/۰۰۰۲ صفحه‌های متخصص) از outputs پاک شده بودن چون پچ‌های بعدی
با همون شماره‌ها خروجی گرفته شدن و `rm outputs/*.patch` قبلی‌ها رو حذف کرد → لینک‌ها دانلود نمی‌شدن.
**از این به بعد:** هر نوبت در یک پوشه‌ی جدا با نام توصیفی (`outputs/batch-.../`) و هیچ‌وقت فایل تحویل‌داده‌شده‌ی
قبلی پاک نمی‌شه.

**پچ `refactor(payments): gateway driver layer, per-salon gateways table, Zarinpal as the first driver`:**
- `App\Payments\Contracts\PaymentGatewayDriver` + value objectها (`GatewayStartRequest` با تنها تبدیل
  تومان→ریال، `GatewayStartResult` با `retryable` و پشتیبانی فرم POST برای بانک‌ها، `GatewayVerify*`).
- `App\Payments\Drivers\ZarinpalDriver` (کد PaymentService بدون تغییر رفتار منتقل شد).
- `App\Payments\GatewayManager`: ترتیب priority، اول درگاه انتخابی مشتری، failover **فقط** روی
  «در دسترس نبودن» (اتصال/۵xx)، نه خطای منطقی.
- جدول `salon_payment_gateways` (credentials با `encrypted:array`، `fee_percent`/`fee_fixed_toman` که فعلاً ۰
  هستن تا مرحله‌ی ۱). migration برای هر سالن دارای مرچنت یک ردیف zarinpal می‌سازه (rollback+migrate وریفای شد).
- تا UI مرحله‌ی ۱، فیلد «کد پذیرنده‌ی زرین‌پال» ردیف zarinpal رو sync می‌کنه (`Salon::booted`).
  `acceptsOnlinePayments()` = «حداقل یک درگاه فعال».
- `PaymentService` روی manager؛ درگاهِ شروع در session، و verify فقط درگاه‌های خودِ سالن رو قبول می‌کنه.
- تست: `GatewayManagerTest` (۷). سوییت کامل **۱۲۲۵ passed / ۱ skipped**.

**باقی‌مانده‌ی مرحله‌ی ۰:** جدول واحد `payment_transactions` + callback مشترک؛ `SubscriptionPaymentService`
و `ZarinpalPayoutService` روی لایه‌ی درگاه؛ حذف `App\Providers\PaymentServiceProvider` (مرده)؛ بررسی آدرس
production زرین‌پال (`api.zarinpal.com` در کد در برابر `payment.zarinpal.com` در مستندات فعلی).

### ۲۰۲۶-۰۹-۲۵ — چند درگاه: مرحله‌ی ۰ بخش ۲ (مرحله‌ی ۰ کامل شد)

**پچ `feat(payments): unified transaction ledger, shared gateway return URL, subscriptions on the driver`:**
- ⚠️ **دلیل طراحی (مهم برای هر driver بعدی):** بانک‌ها و PayPing v3 نتیجه رو با **POST cross-site** برمی‌گردونن؛
  کوکی session با `SameSite=Lax` در اون POST فرستاده **نمی‌شه**. پس «کدوم درگاه/چه مبلغی» هرگز نباید از
  session خونده بشه، و callback کسب‌وکار (که پشت auth هست) نمی‌تونه مستقیم آدرس بازگشت درگاه باشه.
- جدول `payment_transactions` (public uuid، salon، gateway، driver، purpose `booking|wallet_charge|subscription`،
  payable morph، amount_rial، fee_rial، token، ref_id، card_pan، status، callback_url کسب‌وکار، پاسخ‌های خام).
- مسیر `payments.return` = `/payments/return/{uuid}`: GET+POST، بدون auth، بدون CSRF، throttle، بدون domain.
  **هیچ منطق مالی نداره**: فقط 303 به callback کسب‌وکار همون تراکنش + پارامترهای درگاه + tx (GET → کوکی Lax
  برمی‌گرده). مقادیر booking/invoice/tx از درگاه هرگز منتقل نمی‌شن.
- `PaymentService`: هر شروع = یک تراکنش + آدرس بازگشت مشترک به درگاه. در callback، tx فقط برای همین سالن/همین
  purpose/همین نوبت قبول می‌شه؛ verify با درگاه و **مبلغ ثبت‌شده در تراکنش**. callback تکراری: نوبت → همون نتیجه‌ی
  ذخیره‌شده؛ شارژ کیف پول → رد (جلوگیری از شارژ دوباره). callback بدون tx همون رفتار قبلی (session).
- `SubscriptionPaymentService` روی `ZarinpalDriver` با مرچنت پلتفرم + تراکنش + بازگشت مشترک.
- آدرس production زرین‌پال طبق مستندات رسمی فعلی: `payment.zarinpal.com/pg/v4/payment/...` و
  `payment.zarinpal.com/pg/StartPay/...` (قبلاً api/www). override: `ZARINPAL_PAYMENT_API_URL` / `ZARINPAL_START_PAY_URL`.
- حذف `App\Providers\PaymentServiceProvider` (مرده).
- تست: `PaymentTransactionLedgerTest` (۷) + تست end-to-end در `PaymentControllerTest` (شروع → return → 303 → callback
  → نوبت paid). سوییت کامل **۱۲۳۳ passed / ۱ skipped**.
- تست ناپایدار گزارش‌شده‌ی نوبت قبل: در ۹ اجرای کامل پشت‌سرهم دیگه بازتولید نشد — زیر نظر.

**هنوز روی لایه‌ی درگاه نیست:** Payout (`ZarinpalPayoutService`) — با مرحله‌ی ۳.

### ۲۰۲۶-۰۹-۲۵ — چند درگاه: مرحله‌ی ۱ (درایورها، صفحه‌ی مدیریت درگاه‌ها، انتخاب درگاه مشتری، کارمزد)

**تصمیم ابوالفضل (این نشست):** فهرست درگاه‌ها عوض شد → **زرین‌پال، زیبال، آسان پرداخت، وندار**.
آیدی‌پی، نکست‌پی و پی‌پینگ از طرح حذف شدن (آیدی‌پی: گزارش راه‌پرداخت اسفند ۱۴۰۲ از بازداشت مدیرعامل و مسدودی
حساب‌ها، شکایت کاربران تا ۱۴۰۴، و 502 دائمی `api.idpay.ir` در مرداد ۲۰۲۶).

**وضعیت توکن GitHub:** توکن داخل همین سند **401** می‌ده (منقضی/باطل). کلون عمومی با git کار کرد، push نه.
توکن هم در این سند و هم در چت اومده — باید در GitHub revoke و توکن تازه ساخته بشه.

**محیط:** زیپ آپلودی = develop `21f2e54` (فقط فایل‌های محلی مثل .env و public/build اضافه). ⚠️ بدون `public/build`
۲۲۳ تست با `ViteManifestNotFoundException` fail می‌شن — در sandbox از زیپ کپی می‌شه (فایل commit‌شده نیست).
پایه: **۱۲۳۳ passed / ۱ skipped**.

**مستندات (پیش از هر driver):**
- زیبال: مستند رسمی help.zibal.ir/ipg (نسخه‌ی ایندکس‌شده‌ی context7). `gateway.zibal.ir/v1/request|verify`،
  شروع `/start/{trackId}`، ریال، مرچنت تست `zibal`، verify مبلغ نمی‌گیره (۱۰۰/۲۰۱ موفق).
- وندار: docs.vandar.io (IPG **v4**؛ آینه‌ی قدیمی vandarpay.github.io هنوز v3 است — منسوخ). `ipg.vandar.io/api/v4/send|verify`،
  انتقال `/v4/{token}`، بازگشت `token` + `payment_status=OK|FAILED`، ریال، callback و Referer باید روی دامنه‌ی ثبت‌شده باشن.
- آسان پرداخت: **مستند رسمی (IPG REST 1.9.3) عمومی نیست** و فقط به پذیرنده داده می‌شه؛ طبق دو پیاده‌سازی نگهداری‌شده‌ی
  هم‌خوان (shetabit/multipay و افزونه‌ی Pars Kit): `ipgrest.asanpardakht.ir/v1/Time|Token|TranResult|Verify|Settlement`،
  هدر `usr`/`pwd`، `localInvoiceId` عددی یکتا، انتقال با فرم POST به `asan.shaparak.ir` (RefId)، خطاها = کد HTTP
  (۴۷۱–۴۹۷، ۵۷۱–۵۸۰). ⚠️ وقتی قرارداد آسان پرداخت گرفته شد، با PDF رسمی مقایسه بشه.

**پچ‌ها (روی `21f2e54`، برنچ پیشنهادی `feat/multi-gateway-stage-1`):**
1. `feat(payments): Zibal, Vandar and Asan Pardakht drivers; gateway fee on top of the customer's amount`
   - `App\Payments\Drivers\{Zibal,Vandar,AsanPardakht}Driver`؛ `GatewayCatalog` (برچسب + فیلدهای credentials هر درگاه).
   - verify همیشه با **توکن ذخیره‌شده در تراکنش** (نه مقدار callback) — زرین‌پال هم همین‌طور شد؛ `GatewayVerifyRequest`
     توکن و id تراکنش رو داره. زیبال/وندار مبلغ پاسخ verify رو با تراکنش مقایسه می‌کنن.
   - `GatewayStartResult::postForm` + `App\Payments\GatewayRedirect` + view `payments.gateway-redirect` (فرم POST خودکار).
   - آسان پرداخت: failover فقط روی قطع اتصال یا ۵۰۰/۵۰۲/۵۰۳/۵۰۴ واقعی؛ ۵۷x خطای پیکربندیه. Verify ناموفق → Settlement نمی‌زنه.
   - **کارمزد:** `SalonPaymentGateway::feeRialFor()` = درصد + ثابت، رو به بالا تا تومان کامل. `GatewayManager::start`
     کارمزد هر درگاهی که امتحان می‌کنه رو جدا اضافه می‌کنه (بعد از failover دوباره حساب می‌شه) و `[result, gateway, feeRial]`
     برمی‌گردونه. تراکنش: `amount_rial` = پایه + کارمزد (verify با همین)، `fee_rial`. پیش‌پرداخت نوبت و شارژ کیف پول
     فقط مبلغ پایه؛ کارمزد و driver در `payment_details` / metadata کیف پول.
2. `feat(admin): payment gateways page for the salon owner, replacing the single Zarinpal merchant field`
   - `admin.payment-gateways.*` (فقط owner): افزودن (هر نوع حداکثر یکی)، ویرایش (نام نمایشی، credentials، فعال، کارمزد
     درصدی ≤۱۰ و ثابت ≤۱۰۰٬۰۰۰ تومان، ارقام فارسی)، ترتیب ▲▼ (= ترتیب failover، اولی پیش‌فرض مشتری)، حذف، **تست اتصال**
     (درخواست پرداخت واقعی بدون انتقال مشتری؛ throttle). secretها رمزشده، هرگز نمایش داده نمی‌شن، خالی = بدون تغییر.
     درگاه سالن دیگه = ۴۰۴ (همیشه از `$salon->paymentGateways()`).
   - `SalonGatewayService`: تغییر ردیف zarinpal با `saveQuietly` به `salons.zarinpal_merchant_id` برمی‌گرده (ثبت‌نام/سوپرادمین
     هنوز این ستون رو می‌نویسن و Payout هنوز ازش می‌خونه؛ Salon::booted هم‌چنان ستون → ردیف).
   - «اطلاعات سالن»: فیلد تکی مرچنت حذف شد (مقدار ارسالی نادیده گرفته می‌شه) → وضعیت + دکمه‌ی صفحه‌ی جدید. فیلد توکن
     Payout زرین‌پال همون‌جا مونده تا مرحله‌ی ۳. لینک سایدبار + لینک هشدار داشبورد/اشتراک.
3. `feat(payments): customer picks the gateway on the payment and wallet top-up pages, with the fee shown`
   - partial `payments._gateway-picker` داخل فرم پرداخت نوبت و شارژ کیف پول؛ ردیف «کارمزد درگاه» و مبلغ نهایی با JS
     (همون فرمول PHP — روی نمونه‌ها مقایسه شد)؛ پرداخت کامل از کیف پول picker رو مخفی می‌کنه. متن «درگاه امن زرین‌پال»
     در صفحه‌های مشتری → «درگاه امن شاپرکی».

**تست‌ها:** GatewayDriversTest (۱۲)، GatewayFeeLedgerTest (۶؛ شامل آسان پرداخت end-to-end با فرم POST و بازگشت POST بدون
کوکی)، AdminPaymentGatewayTest (۸)، CustomerGatewayChoiceTest (۳)؛ SalonMerchantIdTest بازنویسی شد. سوییت کامل
**۱۲۶۲ passed / ۱ skipped**. اسکریپت‌های رندرشده‌ی سه صفحه با `node --check` بدون خطا.
⚠️ `pint --test` روی کل پروژه ۲۹ فایل قدیمی رو گزارش می‌ده (روی `21f2e54` هم همین ۲۹ تا — مال این نشست نیست)؛ فایل‌های
این نشست Pint-clean هستن. ادعای «Pint PASS» نشست‌های قبل احتمالاً `--dirty` بوده.

**تحویل:** پوشه‌ی `outputs/batch-2026-09-25-multigateway-stage1/` (۴ پچ + این سند)، وریفای روی کلون تازه با `git am`.

### ۲۰۲۶-۰۹-۲۵ — نام و لوگوی پلتفرم: «ماهرو» (Mahru)

**تصمیم‌های ابوالفضل:** نام پلتفرم **ماهرو / Mahru** (دامنه‌های `mahru.ir` و `mahru.app` آزاد بودن — خودش چک کرد)؛
لوگو جهت **«الف»** (هلال طلایی + ستاره‌ی درخشان، «ماهرو» نستعلیق، MAHRU با سریف لاتین) + برگه‌ی چهارم (آیکون‌ها).
⚠️ «راستا» از این به بعد فقط نام **سالن دمو** است (slug `rasta`)، نه نام پلتفرم.
نام‌های رد‌شده به‌خاطر رقیب: رخساره، موآرا، من‌آرای، رزرور، پلنوین (و در نتیجه «رخسار»/«آرایه»).
بوم طراحی سه جهت: https://claude.ai/artifact/TgeTFwfBCgiGyG3yWrQqwZ

**پچ `feat(brand): Mahru (ماهرو) platform name and logo ...` (روی `5c61789`، برنچ پیشنهادی `feat/mahru-brand`):**
- `public/brand/`: نشان (روی تیره / روی روشن با ستاره‌ی تیره‌تر / mono)، قفل عمودی و افقی روی تیره و روشن
  (افقی عمداً بدون MAHRU — دم «ر» بهش می‌خورد)، آیکون اپ، PNGهای PWA (۱۹۲، ۵۱۲، maskable با ناحیه‌ی امن ۸۰٪).
  نوشته‌ها با HarfBuzz به outline تبدیل شدن (هیچ SVG به فونت وابسته نیست)؛ هلال یک path واقعی است نه mask.
- favicon.svg/ico/32 (هلال پررنگ‌تر، بدون ستاره تا در ۱۶px خوانا بمونه)، apple-touch-icon (۱۸۰)، logo-512 —
  جایگزین گل صورتی قبلی. این‌ها fallback صفحه‌های سالنی هم هستن که لوگو ندارن.
- سازنده‌ی همه‌ی فایل‌ها: `docs/brand/generate_brand_assets.py` (فونت‌ها از google/fonts، OFL).
- `config/brand.php` (`BRAND_NAME` و …). ⚠️ `APP_NAME` عمداً دست‌نخورده: اسم کوکی session و پیشوند cache از
  APP_NAME ساخته می‌شن و عوض‌کردنش همه رو logout می‌کرد.
- صفحه‌ی فروش (هدر/فوتر/متن‌ها)، ثبت‌نام سالن، لایوت سوپرادمین → لوگو و نام ماهرو + `partials.platform-icons`
  + `public/site.webmanifest`. fallbackهای `config('app.name','راستا')` → `config('brand.name')`.
- تست: `PlatformBrandTest` (۵). سوییت کامل **۱۲۶۷ passed / ۱ skipped**.

### ۲۰۲۶-۰۹-۲۵ — پیگیری‌های برند، تیکت‌ها، تسویه روی لایه‌ی درگاه (مرحله‌ی ۳)، امنیت پیامک

روی `6ae0f2f`، برنچ پیشنهادی `fix/brand-followups`، ۷ کامیت:
1. `fix(brand): Mahru mark or the salon's own logo on login/register and in every browser tab` — «آرم قدیمی» آیکون قلب
   بود (fallback لایوت‌ها + badge صفحه‌های ورود). `<x-brand-emblem>` = لوگوی سالن یا نشان ماهرو؛ `partials.favicons`
   در همه‌ی لایوت‌های سالن (پنل مدیر/متخصص قبلاً لوگوی سالن رو نادیده می‌گرفتن). `welcome.blade.php` و
   `layouts/navigation.blade.php` یتیم بودن → حذف. قلب‌های «امتیازات/خدمات» آیکون محتوا هستن و موندن.
2. `fix(seed): realistic demo support tickets ...` — ⚠️ تیکت = «مدیر سالن ← تیم پلتفرم» (مشتری مسیر تیکت نداره)؛
   سوپرادمین جواب‌دهنده است. seeder قبلی ۳۱ تیکت جعلی از مشتری‌های تصادفی می‌ساخت → حالا ۵ تیکت از مالک سالن.
3. `style: apply Laravel Pint ...` — ۲۹ مورد قدیمی؛ `pint --test` کل پروژه تمیز.
4. `feat(brand): share-preview image and Open Graph tags` — `public/brand/mahru-og.png` (۱۲۰۰×۶۳۰).
5. `fix(security): tests never send real SMS; remove the Kavenegar API key from .env.example` — ⚠️ `.env.example`
   (ریپوی عمومی) کلید واقعی کاوه‌نگار + `KAVENEGAR_SEND_IN_LOCAL=true` داشت و phpunit override نمی‌کرد → اجرای کامل
   تست‌ها ۵۹۰ تلاش ارسال پیامک واقعی لاگ می‌کرد. `phpunit.xml` حالا `force="true"` false. **کلید باید در پنل
   کاوه‌نگار باطل و عوض بشه** (در تاریخچه‌ی git می‌مونه).
6. `feat(signup): welcome SMS to the owner of a newly registered salon` — `SalonWelcomeNotification` (صف‌دار، فقط sms)،
   یک‌بار بعد از تایید موبایل ثبت‌نام؛ بدون salon_id → از سهمیه‌ی سالن کم نمی‌شه. URLها در constructor ساخته می‌شن.
7. `feat(payments): payouts on the gateway layer; payout token moves to the gateways page; drop salons.zarinpal_* columns`
   - `App\Payments\{Contracts\PayoutDriver, PayoutRequest, PayoutResult, PayoutManager, Drivers\ZarinpalPayoutDriver}`؛
     `ZarinpalPayoutService` → `SalonPayoutService`. درگاه تسویه = اولین ردیف با `payout` در GatewayCatalog و اطلاعات
     کامل، **مستقل از is_active**.
   - توکن Payout = فیلد اختیاری secret درگاه زرین‌پال (`clear[payout_api_key]` برای حذف).
   - migration `2026_09_25_000100`: انتقال به ردیف zarinpal + حذف دو ستون؛ down() برمی‌گردونه.
   - ⚠️ `Salon::zarinpal_merchant_id` / `zarinpal_payout_api_key` حالا **ویژگی مجازی** روی ردیف zarinpal هستن (نه ستون):
     در `toArray()` نمیان؛ نوشتن بعد از save و فقط با تغییر واقعی اعمال می‌شه (ویرایش سوپرادمین دیگه درگاه خاموش‌شده
     رو روشن نمی‌کنه). `->where('zarinpal_merchant_id', …)` / `->value(...)` دیگه کار نمی‌کنه.
   - `tests/TestCase.php`: سالن پیش‌فرض با `factory()->create()` (نسخه‌ی قبلی با `make()->toArray()` درگاهش گم می‌شد).
سوییت کامل: **۱۲۷۸ passed / ۱ skipped**.

### ۲۰۲۶-۰۹-۲۵ — درایور تسویه‌ی زیبال

`feat(payments): Zibal payout driver` (روی `913f101`): `ZibalPayoutDriver` طبق docs.zibal.ir/platform —
`POST api.zibal.ir/v1/wallet/checkout`، `Authorization: Bearer <access token>` (پنل ← توسعه‌دهندگان ← API Tokenها)،
`{amount, id (کیف پول), bankAccount, description}` → `result: 1`. `checkoutDelay` عمداً فرستاده نمی‌شه.
⚠️ واحد مبلغ: ریال فرستاده می‌شه (کل اکوسیستم زیبال ریاله) ولی مستند این پایانه واحد رو صریح ننوشته → اولین
تسویه‌ی واقعی با مبلغ کم. فیلدهای اختیاری زیبال: `payout_access_token` (secret)، `payout_wallet_id`. ارقام فارسی
در اطلاعات اتصال درگاه به لاتین تبدیل می‌شن. تست: `ZibalPayoutTest` (۵). سوییت: **۱۲۸۳ passed / ۱ skipped**.

**وندار (تسویه) عمداً ساخته نشد:** (۱) access token وندار ۵ روزه است و باید با `/v3/refreshtoken` تمدید و ذخیره بشه؛
(۲) مستند قدیمی رسمی (vandarpay.github.io) مبلغ `settlement/store` رو **تومان** (حداقل ۵۰۰۰) می‌گه و مستند جدید در
دسترس ما واحد درخواست رو صریح نمی‌گه. منتظر تأیید واحد از پنل/پشتیبانی وندار.

### ۲۰۲۶-۰۹-۲۶ — چند درگاه: مرحله‌ی ۲ — درگاه مستقیم بانک سامان (سپ) + reconcile + بلوپی

روی `896fe24` (develop)، ۳ کامیت کد + این سند. محیط: کلون develop = زیپ آپلودی (به‌جز .env / public/build).

**مستندات (پیش از کد):** «راهنمای استفاده از درگاه پرداخت اینترنتی» رسمی سپ نگارش ۳٫۲ (تیر ۱۴۰۲، پیوست issue
در GitHub پروژه‌ی Parbad)؛ نگارش ۳٫۳ هم پیدا شد (endpointها یکسان). یک پیاده‌سازی تست‌شده با ترمینال واقعی
(`django-iranian-payment`) به نگارش ۳٫۶ (دی ۱۴۰۴) ارجاع می‌ده و در همه‌ی فیلدها با ۳٫۲ یکیه؛ خود ۳٫۶ رو مستقیم ندیدیم.
- توکن: `POST sep.shaparak.ir/OnlinePG/OnlinePG` `{action:"token", TerminalId, Amount (ریال), ResNum, RedirectUrl,
  CellNumber}` → `{status:1, token}` / `{status:-1, errorCode}`. IP سرور باید نزد سپ ثبت باشه (کد ۸).
- انتقال: فرم POST با `Token` از سایت خودمون (مستند: Referer لازمه — `Referrer-Policy: strict-origin-when-cross-origin`
  پروژه origin رو می‌فرسته). بازگشت: POST با `State/Status/RefNum/ResNum/TerminalId/MID/TraceNo/Rrn/SecurePan/Token`؛ Status=2 موفق.
- تایید: `…/verifyTxnRandomSessionkey/ipg/VerifyTransaction {RefNum, TerminalNumber}` ظرف ۳۰ دقیقه؛ ResultCode ۰
  موفق، ۲ تکراری. Reverse: `…/ReverseTransaction` تا ۵۰ دقیقه.
- ⚠️ **یافته‌ی کلیدی:** سپ هر RefNum رو هر چند بار تایید می‌کنه و پاسخ verify شماره‌ی خرید (ResNum) نداره؛ مستند
  جلوگیری از مصرف دوباره‌ی رسید رو صریحاً به عهده‌ی پذیرنده گذاشته.

**کامیت‌ها:**
1. `feat(payments): Saman (SEP) direct bank gateway with single-use receipts`
   - `App\Payments\Drivers\SamanDriver`؛ `GatewayCatalog['saman']` (فقط `terminal_id`، ارقام فارسی → لاتین؛ سپ رمز نمی‌خواد).
   - قبل از هر verify: Token/ResNum/TerminalId/MID بازگشت باید مال همین تراکنش/ترمینال باشه، Status=2؛ لغو/timeout هرگز verify نمی‌شه.
   - **رسید یک‌بارمصرف:** ستون `payment_transactions.gateway_receipt` + `unique(driver, gateway_receipt)` (migration
     `2026_09_25_000200`)؛ `App\Payments\GatewayReceipt::claim()` اتمیک (تصمیم با index دیتابیس، امن در برابر callback هم‌زمان).
   - verify: RefNum/ترمینال پاسخ + `OrginalAmount` = مبلغ تراکنش؛ نبودِ پاسخ → ۳ بار تلاش. **مبلغ ناهمخوان → Reverse + رد.**
2. `feat(payments): payments:reconcile — …` (هر ۵ دقیقه در `bootstrap/app.php`)
   - pending بیش از ۶۰ دقیقه → `expired` (مانع تایید دیرهنگام نیست). ⚠️ مشتری‌ای که برنگشته **با هیچ API سپ قابل تایید
     نیست** (RefNum فقط با بازگشت می‌رسه)؛ سپ خودش بعد از ۳۰ دقیقه برگشت می‌زنه.
   - **پول گیرکرده:** مشتری برگشت ولی پاسخ verify نرسید (`unanswered=true`). اگه سپ واقعاً تایید کرده باشه دیگه خودش
     برگشت نمی‌زنه → Reverse در پنجره‌ی ۳۱ تا ۴۵ دقیقه بعد از آخرین تلاش (`updated_at`)؛ یعنی بعد از بسته شدن مهلت
     verify مشتری (refresh صفحه) و با حاشیه از سقف ۵۰ دقیقه. `failed → reversing` شرطی؛ `claim` و `PaymentService`
     وضعیت‌های `reversing/reversed` (`PaymentTransaction::REVERSAL_STATUSES`) رو محترم می‌شمارن. Reverse ناموفق → اجرای بعدی.
   - 🐞 باگ پیداشده با تست قبل از commit: به‌روزرسانی وضعیت `updated_at` رو جلو می‌برد و تراکنش از پنجره‌ی خودش خارج
     می‌شد → `toBase()->update` (بدون timestamp).
3. `feat(payments): optional BluPay (neo-pg) page for Saman terminals` — فیلد انتخابی `redirect_mode` (classic پیش‌فرض |
   blupay؛ partial فیلدها نوع `options` یاد گرفت). فرم به هدر `X-IPG-Url` پاسخ توکن می‌ره فقط اگه https روی sep.ir /
   shaparak.ir باشه؛ وگرنه (یا نبودِ هدر) صفحه‌ی کلاسیک. ⚠️ این رفتار در مستند ۳٫۲ نیست (از پیاده‌سازی تست‌شده‌ی ۳٫۶).

**تست:** `SamanGatewayTest` (۱۶، شامل مسیر کامل نوبت: فرم POST → بازگشت POST بدون کوکی → 303 → paid، و ردِ پرداخت نوبت
دوم با رسید مصرف‌شده)، `SamanReconcileTest` (۷، شامل مسیر کامل: پاسخ گم‌شده → Reverse → refresh دیرهنگام نه نوبت رو paid
می‌کنه نه دفتر رو بازنویسی)، `SamanBlupayTest` (۳). Mutation: بدون `claim` ۴ تست و بدون گارد `PaymentService` تست
مسیر کامل fail می‌شن. سوییت کامل **۱۳۰۹ passed / ۱ skipped** (پایه ۱۲۸۳). `pint --test` کل پروژه PASS (۷۹۴ فایل).
**MariaDB 10.11:** migration + rollback، رفتار `claim` روی index یکتا، و ۱۱۶ تست پرداخت/درگاه روی MySQL واقعی PASS.

**نکته‌ی جانبی:** ~~`bookings:cleanup` در scheduler ثبت نیست~~ — **اشتباه بود** (فقط `bootstrap/app.php` دیده شده بود).
لغو خودکار نوبت‌های پرداخت‌نشده از قبل با job `CancelUnpaidBookings` در `routes/console.php` هر ۵ دقیقه فعال بود. ⚠️ درس:
scheduler این پروژه در **دو** جا تعریف شده (`bootstrap/app.php` و `routes/console.php`) — همیشه `php artisan schedule:list` بگیر.
رفع تداخلش در بخش بعدی.

⚠️ **توکن GitHub:** توکن جدیدی که در چت اومد کار می‌کرد؛ عمداً در این سند نوشته نشد (ریپو عمومیه). توکن داخل همین
سند (بالای فایل) منقضی است و باید پاک بشه.

### ۲۰۲۶-۰۹-۲۶ (ادامه) — لغو خودکار نوبت وسط پرداخت + برگشت پول وقتی ساعت از دست رفته

**سؤال‌های ابوالفضل:** (۱) کرون `schedule:run` دستیه؟ → نه؛ همه‌ی کارها در کد زمان‌بندی شدن و فقط **یک خط کرون یک‌باره**
روی سرور لازمه (~~Docker: کانتینر `scheduler` از قبل انجامش می‌ده~~ — **غلط بود**: در Docker نه scheduler اجرا می‌شد نه
queue؛ رفع در بخش بعد؛ DirectAdmin: همون خط بخش «معماری کلیدی»؛ لوکال: `schedule:work`). ⚠️ `CancelUnpaidBookings` با `Schedule::job` صف‌دار است → با `QUEUE_CONNECTION` غیر sync، `queue:work` هم لازمه.
(۲) تداخل لغو ۳۰ دقیقه‌ای با پرداخت دیرهنگام — با دو probe واقعی بازتولید شد (فایل‌ها پاک شدن):
- ساعت هنوز آزاد → پرداخت دیرهنگام نوبت لغوشده رو بی‌صدا `confirmed/paid` می‌کرد (بعد از پیامک «نوبت شما لغو شد»).
- ساعت رو نفر دیگه گرفته → `UNIQUE bookings.active_slot_key` (درست جلوی رزرو دوبل رو گرفت) ولی callback اون رو خطای عمومی
  گرفت: **پول در حساب درگاه سالن، نوبت لغو، مشتری «پرداخت ناموفق»**.

**تصمیم‌های ابوالفضل:** (۱) نوبتِ با پرداخت در جریان لغو نشه؛ (۲) در حالت دوم پول برگرده: سامان → Reverse به کارت، بقیه → کیف پول.

**کامیت‌ها:**
1. `fix(booking): do not auto-cancel an unpaid booking while its customer is at the bank` — `Booking::paymentTransactions()`
   (morph) + `scopeWithoutPaymentInProgress()` (تراکنش pending جوان‌تر از `PaymentTransaction::PENDING_LIFETIME_MINUTES` = ۶۰)؛
   هم `CancelUnpaidBookings` هم `bookings:cleanup`. `payments:reconcile` هم از همین ثابت برای expired استفاده می‌کنه.
2. `fix(payments): refund the customer when the booking's slot was taken …` — `App\Services\Payment\LostSlotRefundService`:
   قفل `paid → reversing` (بدون برگشت دوباره با refresh)؛ سامان: Reverse به کارت → `reversed`، اگه نشد کیف پول؛ بقیه: کیف پول
   (کل مبلغ با کارمزد) → وضعیت جدید `refunded` (در `REVERSAL_STATUSES`)؛ بخش کیف پولی پرداخت ترکیبی همیشه کیف پول؛ دلیل در
   `verify_response.refund` و `cancellation_reason` نوبت. `PaymentController::callback` فقط خطای یکتای `active_slot` رو می‌گیره
   (بقیه rethrow) و به مشتری می‌گه پول کجا رفت. ساعتِ هنوز آزاد → مثل قبل تایید می‌شه.

**تست:** `CancelUnpaidBookingsInFlightPaymentTest` (۳)، `LostSlotRefundTest` (۵). Mutation: بدون scope تست اول، بدون
برگشت ۴ از ۵ fail. سوییت کامل **۱۳۱۷ passed / ۱ skipped**؛ Pint کل پروژه PASS؛ ۱۳۹ تست پرداخت/job روی MariaDB 10.11 PASS.

### ۲۰۲۶-۰۹-۲۶ (ادامه ۲) — پیامک برگشت پول + راه‌اندازی واقعی scheduler/queue (Docker و DirectAdmin)

**تصمیم‌ها:** امنیت (کلید کاوه‌نگار، توکن‌ها) → خود ابوالفضل. سرور → در فایل‌های پروژه تنظیم + فایل راهنما. پیامک برگشت پول → الزامی.

**کامیت‌ها:**
1. `feat(payments): SMS the customer whenever their money is sent back` — `App\Notifications\Payment\PaymentRefundedNotification`
   (صف‌دار، sms) از هر سه جا: `slot_taken` (LostSlotRefundService)، `verify_unanswered` (reconcile)، `amount_mismatch` (Reverse
   فوری سامان، مبلغ از OrginalAmount). متن در سازنده ساخته می‌شه (سالن، شماره‌ی نوبت، هر مبلغ و مقصدش، ارقام فارسی).
   ⚠️ عمداً **بدون salon_id** (از سهمیه‌ی پیامک سالن کم نمی‌شه): پیام مالی نباید با تمام شدن سهمیه نرسه. تکراری نمی‌ره.
2. `fix(deploy): make the scheduler and the queue actually run …` — **در Docker هیچ‌وقت نه scheduler اجرا شده بود نه queue:**
   (۱) `.dockerignore` پوشه‌ی `docker/` رو ignore می‌کرد در حالی که Dockerfile ازش COPY می‌کنه → build شکست؛ (۲) healthcheck
   دستور ناموجود `health:check` + `||` در فرم exec → app هرگز healthy نمی‌شد و queue/scheduler بالا نمی‌اومدن؛ (۳)
   `entrypoint.sh` آرگومان‌ها رو نادیده می‌گرفت و همیشه وب‌سرور اجرا می‌کرد (+ migrate هم‌زمان ۳ container)؛ (۴) حلقه‌ی
   `schedule:run && sleep 60`؛ (۵) env ناقص worker. رفع: entrypoint دو نقشی (web: migrate+supervisord؛ worker: exec دستور
   با `su-exec www`)، anchorهای مشترک compose (یک `.env` با `env_file`)، healthcheck با curl، `schedule:work`، Makefile
   (APP_KEY روی host، `logs-scheduler`، `queue-restart`). **DirectAdmin:** `QUEUE_WORK_VIA_SCHEDULER=true` → همون یک خط کرون
   هر دقیقه `queue:work --stop-when-empty --max-time=50` (پس‌زمینه). فایل‌ها: `deploy/cron/mahru.cron`،
   `deploy/supervisor/mahru-worker.conf`، **راهنما: `docs/deployment/SCHEDULER_AND_QUEUE.md`** (با پرامپت آماده).
   ⚠️ `CACHE_STORE=array` (در `.env.example` و `.env` لوکال) در production قفل‌های scheduler و throttle ورود رو بی‌اثر می‌کنه —
   راهنما `database`/`file` رو الزامی کرده.

**بررسی:** entrypoint در هر دو نقش واقعاً اجرا شد (root، production، MariaDB، کاربر www، su-exec): web → symlink + ۴۵
migration + cache + supervisord؛ worker → بدون migrate، دستور با www، فایل‌های cache مال www. shellcheck تمیز. با
`QUEUE_WORK_VIA_SCHEDULER=true` یک `schedule:run` دو job صف‌شده رو در ۲ ثانیه اجرا کرد (۰ شکست) و worker خودش بسته شد.
**بررسی‌نشده:** build واقعی image (Docker در محیط Claude نبود). مشکل جدای باز: سرویس `nginx` در compose یک volume خالی
(`beauty_public`) رو سرو می‌کنه و به سوکت php-fpm داخل app دسترسی نداره.

**تست:** `PaymentRefundedNotificationTest` (۳) + پیامک در `LostSlotRefundTest`/`SamanReconcileTest`؛ `SchedulerQueueSetupTest` (۳).
سوییت کامل **۱۳۲۳ passed / ۱ skipped**؛ Pint کل پروژه PASS (۸۰۰ فایل).

### ۲۰۲۶-۰۹-۲۶ (ادامه ۳) — چند درگاه: مرحله‌ی ۲ — درگاه مستقیم بانک ملت (به‌پرداخت، SOAP)

روی `9f4da9e` (develop)، ۳ کامیت کد + این سند. محیط: کلون develop = زیپ آپلودی (به‌جز .env / public/build / storage).
پایه‌ی واقعی: **۱۳۲۲ passed / ۱ skipped** (سند قبلی ۱۳۲۳ نوشته بود؛ روی `9f4da9e` از صفر ۱۳۲۲ است).
⚠️ درس محیط: `key:generate` قبل از تنظیم DB اجرا شد و بی‌صدا شکست خورد (AppServiceProvider موقع boot به DB وصل می‌شه) →
APP_KEY خالی و همه‌ی تست‌ها `MissingAppKeyException`. ترتیب درست: `.env` → DB (sqlite) → `key:generate` → `package:discover`.

**مستندات (پیش از کد):** آخرین راهنمای عمومی رسمی به‌پرداخت نگارش ۱٫۱ (۲۰۱۳) است؛ نسخه‌های بعدی فقط به پذیرنده داده می‌شن.
با سه پیاده‌سازی نگهداری‌شده (Parbad، shetabit/multipay، Pars Kit — آخری چند روز پیش به‌روز شده) مقایسه شد؛ endpointها یکسان.
- وب‌سرویس `POST bpm.shaparak.ir/pgwchannel/services/pgw` (SOAP 1.1، namespace `http://interfaces.core.sw.bps.com/`)؛
  انتقال: فرم POST با `RefId` به `…/pgwchannel/startpay.mellat`.
- `bpPayRequest` → `"0,RefId"`؛ بازگشت POST با `RefId/ResCode/SaleOrderId/SaleReferenceId/CardHolderInfo` (+ `CardHolderPan`،
  `FinalAmount` در نسخه‌های جدید). ResCode ۱۷ = انصراف.
- `bpVerifyRequest` ظرف **۱۵ دقیقه** (وگرنه خود بانک برگشت می‌زنه؛ ۴۳ = قبلاً verify شده) → `bpSettleRequest` (۴۵ = قبلاً settle)؛
  `bpReversalRequest` فقط قبل از settle، تا **۲ ساعت** (۴۸ = قبلاً برگشت خورده).
- ⚠️ **یافته‌ی کلیدی:** پاسخ verify هیچ مبلغی نداره؛ بانک مبلغ رو به orderId ما گره زده. پس امنیت = چک RefId و SaleOrderId
  بازگشت با همین تراکنش + verify همیشه با id خودمون.
- ⚠️ WSDL سخت‌گیره (المنت ناشناخته = Unmarshalling Error) → `mobileNo` فرستاده نمی‌شه (در مستند عمومی bpPayRequest نیست).

**کامیت‌ها:**
1. `refactor(payments): ReversibleGateway contract for reconcile and lost-slot refunds` — `Contracts\ReversibleGateway::reverseTransaction()`؛
   سامان پیاده‌اش کرد؛ `LostSlotRefundService` و `payments:reconcile` دیگه به `SamanDriver` وابسته نیستن؛ reconcile پنجره‌ی هر
   درگاه رو از `reverseWindows()` می‌خونه. بدون تغییر رفتار.
2. `feat(payments): Behpardakht Mellat direct bank gateway (SOAP) …` — `App\Payments\Drivers\MellatDriver`:
   - XML خام روی `Http` (نه ext-soap: بدون دانلود WSDL و بدون افزونه‌ی جدید روی سرور؛ تست با `Http::fake`).
   - orderId = id تراکنش؛ RefId = توکن ذخیره‌شده؛ SaleReferenceId با `GatewayReceipt::claim` قفل می‌شه (مثل سامان).
   - verify بی‌پاسخ → `unanswered` → reconcile در پنجره‌ی **۱۶ تا ۹۰ دقیقه** برگشت می‌زنه.
   - verify شد ولی settle نشد → همون لحظه Reverse: ۰/۴۸ → رد + پیامک با دلیل جدید `settle_failed`؛ ۴۵ → یعنی settle انجام شده
     بوده → موفق؛ بی‌پاسخ → reconcile.
   - failover فقط روی قطع اتصال، ۵xx بدون SOAP Fault، و ResCode ۳۴/۱۱۳.
   - ساعت نوبت از دست رفته → **کیف پول** (پرداخت موفق ملت همیشه settle‌شده و قابل Reverse نیست؛ بدون تماس با بانک).
   - `GatewayCatalog['mellat']`: شماره ترمینال، نام کاربری، رمز (secret).
3. `fix(payments): an instantly reversed payment is recorded as reversed …` — 🐞 **باگ واقعی (سامان هم)، با probe بازتولید شد:**
   پولی که همون لحظه‌ی بازگشت برگشت خورده بود (سامان: مبلغ ناهمخوان) `failed` ثبت می‌شد → refresh صفحه‌ی نتیجه دوباره verify
   و Reverse می‌زد و **پیامک دوم** می‌رفت (probe: ۲ پیامک، ۲ Reverse). حالا `reversed` ثبت می‌شه (REVERSAL_STATUSES)؛ مسیر
   شارژ کیف پول هم همون گارد رو گرفت.

**تست:** `MellatGatewayTest` (۱۷)، `MellatReconcileTest` (۵)، `ImmediateReversalRefreshTest` (۲). Mutation: بدون claim ۶ تست،
بدون گارد settled ۲، بدون Reverse بعد از settle ناموفق ۳، بدون چک SaleOrderId ۱، بدون پنجره‌ی ملت در reconcile ۲، و بدون فیکس
کامیت ۳ هر دو تستش fail می‌شن. سوییت کامل **۱۳۴۶ passed / ۱ skipped** (پایه ۱۳۲۲)؛ `pint --test` کل پروژه PASS (۸۰۵ فایل).
**MariaDB 10.11:** ۱۴۳ تست پرداخت/job PASS. وریفای روی کلون تازه با `git am`.

### ۲۰۲۶-۰۹-۲۶ (ادامه ۴) — Docker: stack هرگز یک صفحه سرو نکرده بود + برنامه بدون پکیج‌های dev boot نمی‌شد

روی بچ ملت (`batch-2026-09-26-mellat-gateway`)، ۳ کامیت کد + این سند. تحویل: `batch-2026-09-26-docker/`.

**محدودیت:** `make setup` واقعی ممکن نشد — Docker در sandbox نصب و daemon اجرا شد، ولی همه‌ی registryها (Docker Hub،
mirror.gcr.io، ghcr.io) از پروکسی شبکه‌ی sandbox ۴۰۳ دادن. به‌جایش: شبیه‌سازی native نقش web همون container (nginx +
php-fpm + supervisord با **همین فایل‌های پروژه** و چیدمان pool رسمی `php:*-fpm-alpine`)، مرحله‌ی composer با افزونه‌های
image `composer`، و همه‌ی قدم‌های entrypoint روی نصب `--no-dev` با `APP_ENV=production`. `docker compose config` معتبر.
⚠️ درس sandbox: `pkill -f php-fpm` / `pgrep -f "…"` خود shell رو (که همین رشته در command lineشه) می‌کشه → از `pkill -x` استفاده کن.

**یافته‌ها (همه قبل از اصلاح بازتولید شدن):**
- 🔴 **boot:** `laravel/telescope` در require-dev ولی `App\Providers\TelescopeServiceProvider` در `bootstrap/providers.php` بی‌قید →
  با `--no-dev` هیچ درخواست و هیچ artisanی (حتی migrate) اجرا نمی‌شد. ⚠️ برای **هر** سرور production با `--no-dev` هم صادقه.
- 🔴 **build:** مرحله‌ی `composer:2.7` افزونه‌ی gd نداره (mpdf) → `docker build` در مرحله‌ی ۲ می‌شکست.
- 🔴 **php-fpm:** `www.conf`/`zz-docker.conf` رسمی بعد از فایل ما خونده می‌شدن → `listen = 9000`، `user = www-data`، سوکت ساخته نمی‌شد.
- 🔴 **nginx داخل app:** vhost در `http.d/` ولی `nginx.conf` ما `conf.d/` رو include می‌کنه → هیچ پورتی باز نبود. کاربر nginx هم به سوکت 0660 دسترسی نداشت.
- 🔴 **سرویس جداگانه‌ی nginx:** volume خالی + سوکت غیرقابل‌دسترس؛ تنها پورت منتشرشده‌ی سایت همین بود.
- 🔴 **امنیت:** MySQL/Redis (بدون رمز)/phpMyAdmin روی همه‌ی interfaceها (Docker ufw رو دور می‌زنه).
- 🟡 `/up` یک 200 ثابت nginx بود (PHP خاموش هم «healthy»)؛ `.dockerignore` vendor/build/کش provider محلی رو روی build می‌نشوند؛
  Makefile artisan رو با root اجرا می‌کرد (laravel.log مال root → php-fpm نمی‌نوشت).

**کامیت‌ها:**
1. `fix(app): boot without dev dependencies — register Telescope only when it is installed` — ثبت از `AppServiceProvider` با
   `class_exists`؛ روی XAMPP بدون تغییر (۴۴ route تلسکوپ).
2. `fix(docker): make the image build and the app container actually serve` — `--ignore-platform-req='ext-*'` + `check-platform-reqs`
   در image نهایی؛ `zzz-beauty-salon.conf`؛ vhost در `conf.d` + `addgroup nginx www`؛ `/up` به route سلامت Laravel؛
   `mkdir /var/log/supervisor`؛ `.dockerignore`.
3. `fix(docker): serve from the app container, drop the broken nginx service, keep databases private` — حذف سرویس nginx و
   `beauty_public`؛ `app` پورت `${APP_PORT:-80}` رو منتشر می‌کنه؛ HTTPS بیرون از stack؛ پورت‌های DB/Redis/PMA روی `127.0.0.1`؛
   Makefile: `ARTISAN` با `-u www`، `up -d --wait`، `make superadmin`؛ `DockerSetupTest` (۸)؛ راهنمای deployment.

**بررسی:** قبل از اصلاح هیچ پورتی باز نبود. بعد: `/up`، صفحه‌ی اصلی، `/login`، assetهای Vite و فایل آپلودی ۲۰۰؛ `/.env` ۴۰۴؛ `/up`
بدون php-fpm ۵۰۲؛ workerها با `www`. Mutation: برگردوندن هر مقدار قدیمی یک تست `DockerSetupTest` رو fail می‌کنه.
سوییت کامل **۱۳۵۴ passed / ۱ skipped**؛ Pint PASS. وریفای روی کلون تازه با `git am` (بچ ملت + همین بچ).
⚠️ روی کلون تازه اجرای اول **۱ fail** داد و ۷ اجرای بعدی همه ۱۳۵۴ پاس — نام تست ثبت نشد (خروجی اجرای اول ذخیره نشده بود).
همون الگوی «تست ناپایدار زیر نظر» نشست‌های قبل؛ دفعه‌ی بعد خروجی هر اجرا در فایل ذخیره بشه تا اسمش پیدا بشه.

### قدم‌های باز
- ⚠️ باطل کردن کلید کاوه‌نگار و توکن‌های GitHub (توکن قبلی که تا ۲۰۲۶-۰۹-۲۶ در این سند بود و در تاریخچه‌ی git می‌مونه، و
  توکن‌هایی که در چت‌ها اومدن، از جمله چت درگاه ملت) — **خود ابوالفضل**.
- تست دستی درگاه‌ها: زیبال با مرچنت `zibal`؛ وندار و آسان پرداخت با حساب واقعی؛ IP سرور در پنل آسان پرداخت؛ تطبیق با
  PDF رسمی IPG REST. ثبت دامنه/ساب‌دامین هر سالن در پنل هر درگاه.
- درایور تسویه‌ی وندار (بعد از تأیید واحد مبلغ؛ با تمدید خودکار توکن ۵ روزه).
- اولین تسویه‌ی واقعی زیبال با مبلغ کم.
- **مرحله‌ی ۲ ادامه:** پارسیان. سامان و ملت انجام شدن (۲۰۲۶-۰۹-۲۶).
- ملت در محیط واقعی: قرارداد + ثبت IP سرور و دامنه/ساب‌دامین هر سالن نزد به‌پرداخت؛ اولین پرداخت با مبلغ کم؛ **مقایسه با
  راهنمای جدیدی که به‌پرداخت به پذیرنده می‌ده** (به‌خصوص معنی `FinalAmount` که فعلاً فقط ذخیره می‌شه، و قالب `MobileNo`).
- سامان در محیط واقعی: قرارداد + ثبت IP سرور نزد سپ؛ اولین پرداخت با مبلغ کم؛ اگه ترمینال بلوپی داره، تست `redirect_mode=blupay`.
- **روی سرور:** اجرای `docs/deployment/SCHEDULER_AND_QUEUE.md` (DirectAdmin: `.env` → `QUEUE_WORK_VIA_SCHEDULER=true`، `CACHE_STORE=database`، یک خط کرون) — کد آماده است، فقط تنظیم سرور مونده.
- Docker: اولین `make setup` واقعی روی سرور (کد و config اصلاح و native بررسی شدن؛ image در sandbox ساخته نشد) → بعد
  `make superadmin`. نامطمئن‌ها: `docker-php-ext-install mbstring xml` (در image رسمی از قبل هستن — شاید فقط هشدار «already
  loaded»)، و پوشه‌ی `/var/log/supervisor` در بسته‌ی alpine (entrypoint حالا خودش می‌سازه).
- پشت Cloudflare Proxied / هر TLS proxy: `trustProxies` در `bootstrap/app.php` تنظیم نشده → Laravel آدرس‌ها رو `http://` می‌سازه
  (callback درگاه‌ها، لینک‌ها). نیاز به تصمیم: به کدوم IPها اعتماد بشه (رنج‌های Cloudflare یا فقط proxy خود سرور).
- برند: خوشنویسی اختصاصی «ماهرو» (کار خوشنویس) + ثبت علامت تجاری + خرید دامنه‌ها.
