# لاگ‌ها: کجا، چقدر، با چه داده‌ای

> بررسی کامل لاگ‌ها ۲۰۲۶-۱۱-۰۵ (پچ‌های `0357`–`0362`). هر `Log::`/`logger()` در `app/` یک بار خوانده و دسته‌بندی شد؛ فهرست کامل آخر همین فایل.
> مرجع قانونی: `claude/Licensing_requirements.md` در Project (ماده‌ی ۶۶۸ آیین دادرسی کیفری = ماده‌ی ۳۳ جرایم رایانه‌ای؛ ماده‌ی ۵۹ تجارت الکترونیکی).

## ۱. کانال‌ها

| کانال | فایل | نگهداری | محتوا | داده‌ی شخصی |
|---|---|---|---|---|
| عمومی (`LOG_CHANNEL`/`LOG_STACK`: `daily`، `single`، `stderr`) | `storage/logs/laravel-*.log`، `docker logs` | `LOG_DAILY_DAYS` (پیش‌فرض ۱۴) | خطاها و هشدارهای برنامه، عیب‌یابی | **ماسک:** موبایل `0912***4567`، شبا `IR06******************3456` (پیام، context، متن exception و bindingهای SQL) |
| `security` | `storage/logs/security-*.log` | ≥ ۱۸۴، پیش‌فرض ۱۹۰ روز (`SECURITY_LOG_DAYS`/`RETENTION_DAYS`) | ورود، کد غلط، قفل، 2FA، IP ابرمدیر، نظارت نظرها، پیوند حساب متخصص | خام (مدرک ۶۶۸) |
| `payments` | `storage/logs/payments-*.log` | ۳۶۵ روز | درگاه، تأیید، تطبیق، برگشت پول، تسویه، کیف پول | خام (تطبیق با بانک) |
| `csp` | `storage/logs/csp-*.log` | ۱۴ روز | گزارش تخلف CSP | IP |

- هر خط **warning یا بالاتر** در `security` و `payments` در لاگ عمومی هم تکرار می‌شود (ماسک‌شده، با نام کانال: `payments.WARNING: …`) — مشکل پرداخت از
  `docker logs` پنهان نمی‌ماند؛ نسخه‌ی کامل در فایل خودش است. `info` فقط در فایل خودش.
- سطح پیش‌فرض `security` حالا `info` است (`SECURITY_LOG_LEVEL`). پیش‌تر `warning` بود و «ورود موفق» و «تغییر پروفایل» هرگز نوشته نمی‌شد.
- `LOG_MASK_PERSONAL_DATA=false` ماسک لاگ عمومی را خاموش می‌کند — فقط برای عیب‌یابی لوکال، هرگز روی سرور.

## ۲. نگهداری و ماده‌ی ۶۶۸

ماده‌ی ۶۶۸ (میزبانی داخلی): **اطلاعات کاربر (از جمله IP) ≥ ۶ ماه پس از پایان اشتراک؛ محتوا و داده‌ی ترافیک تغییرات ≥ ۱۵ روز.**

| داده | کجا نگه داشته می‌شود | مدت |
|---|---|---|
| هویت، موبایل، IP و user agent ورودها و کارهای کلیدی (نوبت، نظر، تیکت، سالن، رمز، موبایل، پرداخت) | جدول `security_logs` (`KeyActionAuditObserver`، `SecurityLogService`) | ≥ ۱۸۴ روز، `retention:prune` |
| همان رویدادها به‌صورت متن، به‌علاوه‌ی کد غلط، قفل، 2FA و … | `security.log` | ≥ ۱۸۴ روز |
| هویت و IP حساب حذف‌شده | `deleted_account_archives` (رمزنگاری‌شده) | ۶ ماه پس از حذف |
| ترافیک (IP واقعی، مسیر، زمان) | لاگ دسترسی nginx `storage/logs/nginx/access-*.log` | ≥ ۱۸۴ روز |
| رویدادهای پول | `payments.log` + جدول‌های `payment_transactions` و … | ۳۶۵ روز |
| عیب‌یابی | `laravel.log` | ۱۴ روز، ماسک‌شده |

`laravel.log` هیچ تکلیف نگهداری‌ای را حمل نمی‌کند؛ به همین دلیل کوتاه و ماسک‌شده است (ماده‌ی ۵۹ ب: «فقط به اندازه‌ی ضرورت»). پیش از این بررسی ۹۰
خط پرداخت و ۱۹ خط امنیتی فقط در همین فایل ۱۴ روزه بودند.

## ۳. دسته‌بندی (۲۶۰ فراخوانی پیش از بررسی ← ۲۵۴: هفت حذف، خط «پیامک در حال ارسال» دو شاخه شد)

- **لازم، به کانال نگهداری‌شده رفت:** ۹۰ پرداخت/تسویه/کیف پول ← `payments`؛ ۱۹ امنیتی ← `security` (`LogRoutingTest`).
- **لازم، همان‌جا ماند:** خطاهای catch کنترلرها و سرویس‌ها، شکست ارسال پیامک/پوش/اعلان، هشدار tenancy و N+1 (فقط غیر production).
- **حذف (۷):** چهار info ‏`CategoryService` (ساخت/ویرایش/حذف/مرتب‌سازی دسته)؛ سه خط تکراری `TwoFactorController` («2FA enabled/disabled/verification
  successful» که سرویس هم می‌نوشت).
- **پایین به debug:** خط «پیامک در حال ارسال» و «کد … صف شد» با ارسال واقعی (`OtpLog::level()`: در حالت «فقط لاگ» info)، «ارسال دوباره‌ی کد» (2FA،
  موبایل)، سه خط «قبلاً انجام شده، رد شد»؛ «انصراف کاربر در بانک» از warning به info.
- **ماسک/کمینه:** ماسک خودکار لاگ عمومی؛ متن پیامک فقط در حالت «فقط لاگ»؛ خطای Jobهای کد بی شماره (فقط `user_id`)؛ «2FA code generated» بی شماره؛
  توکن لینک نظر فقط اثر ۱۲ نویسه‌ای (`PersonalData::fingerprint`).
- **بی‌تغییر و درست از قبل:** کد ورود فقط در حالت «فقط لاگ» (M2)، `OtpSendGuard` شماره‌ی نصفه، `PasswordLockout` کلید نصفه، خطای شارژ کیف پول فقط مبلغ (M6).

## ۴. قاعده برای کد تازه

1. رویداد پول (درگاه، تأیید، برگشت، تسویه، کیف پول) ← `Log::channel('payments')`. رویداد دسترسی/حساب/نظارت ← `Log::channel('security')`. هر دو خام.
2. کار روزمره‌ی موفق در لاگ عمومی ← `debug` یا هیچ. info فقط اگر روی سرور واقعاً لازم است.
3. کاربر را با `user_id` بنویسید نه شماره. توکن/رمز هرگز؛ برای تطبیق `PersonalData::fingerprint()`. کد تأیید فقط با `OtpLog::code()`.
4. متن کامل پیامک یا پاسخ کامل درگاه فقط در کانال `payments` یا حالت «فقط لاگ».

## ۵. فهرست کامل (کد فعلی)

«عمومی» = کانال پیش‌فرض (همیشه ماسک‌شده). «· ماسک» = داده‌ی شخصی همان خط در این بررسی کم یا نصفه شد.

| فایل:خط | کانال | سطح | پیام | دسته |
|---|---|---|---|---|
| `Console/Commands/ReconcilePaymentTransactions.php:137` | payments | info/warning | Bank payment reversed after unanswered verify / reverse failed | لازم — پرداخت (۳۶۵ روز) |
| `Console/Commands/ReconcilePaymentTransactions.php:279` | payments | warning | Payment needs a human: automatic handling window ended | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Blog/AdminBlogCategoryController.php:38` | عمومی | error | خطا در ایجاد دسته‌بندی وبلاگ | لازم |
| `Http/Controllers/Admin/Blog/AdminBlogCategoryController.php:60` | عمومی | error | خطا در به‌روزرسانی دسته‌بندی وبلاگ | لازم |
| `Http/Controllers/Admin/Blog/AdminBlogPostActionController.php:37` | عمومی | error | خطا در ایجاد مقاله وبلاگ | لازم |
| `Http/Controllers/Admin/Blog/AdminBlogPostActionController.php:62` | عمومی | error | خطا در به‌روزرسانی مقاله وبلاگ | لازم |
| `Http/Controllers/Admin/Blog/AdminBlogPostActionController.php:77` | عمومی | error | خطا در حذف مقاله وبلاگ | لازم |
| `Http/Controllers/Admin/Blog/AdminBlogPostActionController.php:93` | عمومی | error | خطا در تغییر وضعیت مقاله وبلاگ | لازم |
| `Http/Controllers/Admin/PaymentGateway/AdminPaymentAttentionController.php:73` | payments | info | Payment needing attention marked resolved | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Review/AdminReviewController.php:56` | security | info | Review approved by admin | لازم — امنیت (۱۹۰ روز) |
| `Http/Controllers/Admin/Review/AdminReviewController.php:64` | عمومی | error | خطا در تایید نظر | لازم |
| `Http/Controllers/Admin/Review/AdminReviewController.php:80` | security | info | Review rejected by admin | لازم — امنیت (۱۹۰ روز) |
| `Http/Controllers/Admin/Review/AdminReviewController.php:117` | security | warning | Review soft deleted by admin | لازم — امنیت (۱۹۰ روز) |
| `Http/Controllers/Admin/Review/AdminReviewController.php:125` | عمومی | error | خطا در حذف نظر | لازم |
| `Http/Controllers/Admin/Review/AdminReviewController.php:153` | security | warning | Review permanently deleted by admin | لازم — امنیت (۱۹۰ روز) |
| `Http/Controllers/Admin/Specialist/AdminSpecialistController.php:81` | عمومی | error | Error storing specialist: | لازم |
| `Http/Controllers/Admin/Specialist/AdminSpecialistController.php:124` | عمومی | error | Error in destroy method: | لازم |
| `Http/Controllers/Admin/Wallet/AdminWalletController.php:71` | payments | error | خطا در تایید شماره شبا | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWalletController.php:104` | payments | error | خطا در تعدیل کیف پول | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWalletController.php:124` | payments | error | خطا در تسویه‌ی دستی کیف‌پول‌ها | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWalletController.php:145` | payments | error | خطا در تسویه‌ی دستی کیف‌پول متخصص | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWalletSettingsController.php:34` | payments | error | خطا در به‌روزرسانی تنظیمات کیف پول | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWithdrawalController.php:61` | payments | error | خطا در تایید درخواست برداشت | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWithdrawalController.php:87` | payments | error | خطا در رد درخواست برداشت | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Admin/Wallet/AdminWithdrawalController.php:116` | payments | error | Zarinpal Payout Error: | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Auth/AuthenticatedSessionController.php:66` | عمومی | error | Failed to send login code | لازم |
| `Http/Controllers/Auth/PhoneVerificationController.php:62` | security | info | Phone verification successful (post-auth notice flow) | لازم — امنیت (۱۹۰ روز) |
| `Http/Controllers/Auth/PhoneVerificationController.php:78` | عمومی | error | Phone verification failed | لازم |
| `Http/Controllers/Auth/PhoneVerificationController.php:94` | عمومی | debug | Phone verification code resent | debug |
| `Http/Controllers/Auth/PhoneVerificationController.php:102` | عمومی | error | Phone verification code resend failed | لازم |
| `Http/Controllers/Auth/TwoFactorController.php:64` | عمومی | error | 2FA enable failed | لازم |
| `Http/Controllers/Auth/TwoFactorController.php:97` | عمومی | error | 2FA disable failed | لازم |
| `Http/Controllers/Auth/TwoFactorController.php:130` | عمومی | error | 2FA verification failed | لازم |
| `Http/Controllers/Auth/TwoFactorController.php:146` | عمومی | debug | 2FA code resent | debug |
| `Http/Controllers/Auth/TwoFactorController.php:155` | عمومی | error | 2FA code resend failed | لازم |
| `Http/Controllers/Central/AssetLinksController.php:46` | عمومی | warning | assetlinks: ignored invalid CUSTOMER_APP_SHA256_FINGERPRINTS entry | لازم |
| `Http/Controllers/Payment/AppPaymentController.php:56` | payments | error | App payment callback failed | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Payment/AppPaymentController.php:111` | payments | error | App course payment callback failed | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Salon/Auth/CustomerAuthenticatedController.php:80` | عمومی | error | Failed to send customer login code | لازم |
| `Http/Controllers/Security/CspReportController.php:53` | csp | info | csp-violation | لازم — CSP (۱۴ روز) |
| `Http/Controllers/Specialist/Booking/SpecialistBookingManagementController.php:132` | عمومی | error | booking_id | لازم |
| `Http/Controllers/Specialist/Leave/SpecialistLeaveController.php:78` | عمومی | error | خطا در ثبت مرخصی | لازم |
| `Http/Controllers/Specialist/Profile/SpecialistProfileController.php:136` | عمومی | error | خطا در بروزرسانی برنامه کاری | لازم |
| `Http/Controllers/Specialist/Review/SpecialistReviewController.php:88` | عمومی | error | خطا در ثبت پاسخ به نظر | لازم |
| `Http/Controllers/Specialist/Review/SpecialistReviewController.php:117` | عمومی | error | خطا در ویرایش پاسخ | لازم |
| `Http/Controllers/Specialist/Wallet/Withdrawal/SpecialistWithdrawalController.php:69` | payments | error | خطا در ثبت درخواست برداشت | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/Specialist/Wallet/Withdrawal/SpecialistWithdrawalController.php:97` | payments | error | خطا در لغو درخواست برداشت | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/SuperAdmin/PlatformOperationsController.php:82` | payments | info | Platform payment needing attention marked resolved | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/BookingAvailabilityController.php:34` | عمومی | error | متخصص یافت نشد در getAvailableTimeSlots | لازم |
| `Http/Controllers/User/BookingAvailabilityController.php:82` | عمومی | error | خطا در دریافت اسلات‌های زمانی | لازم |
| `Http/Controllers/User/BookingAvailabilityController.php:98` | عمومی | error | متخصص یافت نشد در getAvailableDates | لازم |
| `Http/Controllers/User/BookingAvailabilityController.php:133` | عمومی | error | خطا در دریافت تاریخ‌ها | لازم |
| `Http/Controllers/User/BookingAvailabilityController.php:147` | عمومی | error | خطا در دریافت متخصصین | لازم |
| `Http/Controllers/User/BookingAvailabilityController.php:171` | عمومی | error | خطا در دریافت متخصصین | لازم |
| `Http/Controllers/User/BookingController.php:68` | عمومی | error | خطا در نمایش جزئیات نوبت | لازم |
| `Http/Controllers/User/BookingDiscountController.php:43` | عمومی | error | خطا در بررسی کد تخفیف | لازم |
| `Http/Controllers/User/BookingDiscountController.php:73` | عمومی | error | خطا در اعمال کد تخفیف | لازم |
| `Http/Controllers/User/BookingRescheduleController.php:64` | عمومی | error | خطا در تغییر زمان نوبت | لازم |
| `Http/Controllers/User/BookingReservationController.php:94` | عمومی | error | خطا در تأیید نوبت | لازم |
| `Http/Controllers/User/BookingReservationController.php:153` | عمومی | error | خطا در ثبت نوبت | لازم |
| `Http/Controllers/User/BookingReservationController.php:171` | عمومی | error | خطا در لغو نوبت | لازم |
| `Http/Controllers/User/LoyaltyController.php:48` | عمومی | error | خطا در بارگذاری پنل امتیازات | لازم |
| `Http/Controllers/User/LoyaltyController.php:80` | عمومی | error | خطا در تبدیل امتیاز به پاداش | لازم |
| `Http/Controllers/User/PaymentController.php:45` | payments | error | ❌ Payment Gateway Error | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/PaymentController.php:53` | payments | error | 💥 Payment Process Exception | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/PaymentController.php:101` | payments | error | 💥 خطا در پرداخت با کیف پول | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/PaymentController.php:152` | payments | error | 💥 Payment Callback Exception | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/ReviewController.php:41` | security | warning | ❌ Token not found or invalid | لازم — امنیت (۱۹۰ روز) · ماسک |
| `Http/Controllers/User/ReviewController.php:64` | عمومی | error | ❌ Booking not found | لازم · ماسک |
| `Http/Controllers/User/ReviewController.php:73` | عمومی | error | ❌ Error loading review form | لازم |
| `Http/Controllers/User/ReviewController.php:112` | عمومی | error | Error storing review | لازم |
| `Http/Controllers/User/SecurePaymentController.php:86` | payments | error | Secure payment initiation failed | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/SecurePaymentController.php:198` | payments | error | Secure payment verification failed | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/UserWalletController.php:153` | payments | error | ❌ خطا در دریافت URL درگاه | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/UserWalletController.php:163` | payments | warning | ⚠️ خطای اعتبارسنجی شارژ | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/UserWalletController.php:172` | payments | error | 💥 خطا در پردازش شارژ کیف پول | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/UserWalletController.php:239` | payments | warning | ⚠️ شارژ کیف پول ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Http/Controllers/User/UserWalletController.php:251` | payments | error | 💥 خطا در callback شارژ کیف پول | لازم — پرداخت (۳۶۵ روز) |
| `Http/Middleware/RestrictSuperAdminIp.php:22` | security | warning | Super admin panel request from a non-allowed IP | لازم — امنیت (۱۹۰ روز) |
| `Jobs/CancelUnpaidBookings.php:70` | عمومی | warning | Failed to send SMS for cancelled booking | لازم |
| `Jobs/CancelUnpaidBookings.php:82` | عمومی | error | Error cancelling unpaid booking | لازم |
| `Jobs/CancelUnpaidBookings.php:93` | عمومی | error | CancelUnpaidBookings job failed completely | لازم |
| `Jobs/GeneratePdfReportJob.php:67` | عمومی | warning | GeneratePdfReportJob: رکورد ReportExport یافت نشد | لازم |
| `Jobs/GeneratePdfReportJob.php:83` | عمومی | error | GeneratePdfReportJob: ReportExport بدون salon_id — پردازش متوقف شد | لازم |
| `Jobs/GeneratePdfReportJob.php:108` | عمومی | debug | GeneratePdfReportJob: وضعیت دیگر pending نیست، از پردازش صرف‌نظر شد | debug |
| `Jobs/GeneratePdfReportJob.php:143` | عمومی | error | GeneratePdfReportJob: تولید فایل گزارش ناموفق بود | لازم |
| `Jobs/GeneratePdfReportJob.php:202` | عمومی | error | GeneratePdfReportJob: خود Job با خطا متوقف شد | لازم |
| `Jobs/ProcessWithdrawalJob.php:69` | payments | warning | ProcessWithdrawalJob: درخواست برداشت یافت نشد | لازم — پرداخت (۳۶۵ روز) |
| `Jobs/ProcessWithdrawalJob.php:79` | payments | debug | ProcessWithdrawalJob: درخواست دیگر processing نیست، از پردازش صرف‌نظر  | debug — payments |
| `Jobs/ProcessWithdrawalJob.php:97` | payments | warning | ProcessWithdrawalJob: تسویه‌ی خودکار مسدود شد — شبای تأییدنشده | لازم — پرداخت (۳۶۵ روز) |
| `Jobs/ProcessWithdrawalJob.php:137` | payments | error | ProcessWithdrawalJob: نتیجه‌ی تسویه نامعلوم — بررسی دستی لازم است | لازم — پرداخت (۳۶۵ روز) |
| `Jobs/ProcessWithdrawalJob.php:172` | payments | error | ProcessWithdrawalJob: تسویه‌ی آنلاین ناموفق بود | لازم — پرداخت (۳۶۵ روز) |
| `Jobs/ProcessWithdrawalJob.php:194` | payments | error | ProcessWithdrawalJob: خود Job با خطا متوقف شد | لازم — پرداخت (۳۶۵ روز) |
| `Jobs/Send2faVerificationCodeJob.php:42` | عمومی | warning | Send2faVerificationCodeJob: user not found, skipping SMS | لازم |
| `Jobs/Send2faVerificationCodeJob.php:55` | عمومی | error | Send2faVerificationCodeJob: failed to send 2FA verification code | لازم |
| `Jobs/SendBookingReminderJob.php:54` | عمومی | warning | SendBookingReminderJob: booking/related model not found, skipping | لازم |
| `Jobs/SendBookingReminderJob.php:98` | عمومی | error | SendBookingReminderJob: یکی از دو پیامک یادآوری ارسال نشد | لازم |
| `Jobs/SendBulkNotificationJob.php:92` | عمومی | error | SendBulkNotificationJob: کلاس نوتیفیکیشن نامعتبر است | لازم |
| `Jobs/SendBulkNotificationJob.php:104` | عمومی | warning | SendBulkNotificationJob: هیچ گیرنده‌ای برای این chunk پیدا نشد | لازم |
| `Jobs/SendLoginVerificationCodeJob.php:42` | عمومی | warning | SendLoginVerificationCodeJob: user not found, skipping SMS | لازم |
| `Jobs/SendLoginVerificationCodeJob.php:54` | عمومی | error | SendLoginVerificationCodeJob: failed to send login verification code | لازم |
| `Jobs/SendPasswordResetCodeJob.php:51` | عمومی | warning | SendPasswordResetCodeJob: user not found, skipping SMS | لازم |
| `Jobs/SendPasswordResetCodeJob.php:64` | عمومی | error | SendPasswordResetCodeJob: failed to send password reset code | لازم |
| `Jobs/SendPhoneVerificationCodeJob.php:50` | عمومی | warning | SendPhoneVerificationCodeJob: user not found, skipping SMS | لازم |
| `Jobs/SendPhoneVerificationCodeJob.php:62` | عمومی | error | SendPhoneVerificationCodeJob: failed to send phone verification code | لازم |
| `Jobs/SendPushJob.php:112` | عمومی | warning | Push send failed | لازم |
| `Jobs/SendRegistrationCodeJob.php:45` | عمومی | error | SendRegistrationCodeJob: failed to send the registration code | لازم · ماسک |
| `Listeners/Booking/Cancellation/SendBookingCancellationNotifications.php:35` | عمومی | warning | ❌ خطا در ارسال نوتیفیکیشن لغو نوبت به مشتری | لازم |
| `Listeners/Booking/Cancellation/SendBookingCancellationNotifications.php:47` | عمومی | warning | ❌ خطا در ارسال نوتیفیکیشن لغو نوبت به متخصص | لازم |
| `Listeners/Booking/Completion/SendBookingCompletionNotifications.php:32` | عمومی | error | ❌ خطا در ارسال درخواست نظرسنجی | لازم |
| `Listeners/Booking/Completion/SendBookingCompletionNotifications.php:41` | عمومی | warning | ❌ خطا در ارسال نوتیفیکیشن تکمیل نوبت به مشتری | لازم |
| `Listeners/Withdrawal/Approved/SendWithdrawalApprovedNotification.php:48` | عمومی | debug | نوتیفیکیشن تایید برداشت قبلاً برای این درخواست ارسال شده بود؛ از ارسال | debug |
| `Listeners/Withdrawal/Approved/SendWithdrawalApprovedNotification.php:59` | عمومی | warning | ❌ خطا در ارسال نوتیفیکیشن تایید برداشت | لازم |
| `Listeners/Withdrawal/Rejected/SendWithdrawalRejectedNotification.php:22` | عمومی | warning | ❌ خطا در ارسال نوتیفیکیشن رد برداشت | لازم |
| `Models/Specialist.php:294` | عمومی | error | Slot calculation error: | لازم |
| `Models/User.php:437` | عمومی | error | ❌ Failed to add loyalty points | لازم |
| `Observers/Booking/BookingObserver.php:47` | عمومی | error | ❌ Failed to dispatch BookingCreated event | لازم |
| `Observers/Booking/BookingObserver.php:85` | عمومی | error | 💥 Critical Error in BookingObserver | لازم |
| `Observers/Booking/BookingObserver.php:118` | payments | error | ❌ Failed to dispatch PaymentSucceeded event | لازم — پرداخت (۳۶۵ روز) |
| `Observers/Booking/BookingObserver.php:131` | عمومی | error | ❌ Failed to add loyalty points | لازم |
| `Observers/Booking/BookingObserver.php:155` | عمومی | error | ❌ Failed to send payment notifications | لازم |
| `Observers/Booking/BookingObserver.php:187` | عمومی | warning | ⚠️ Specialist not found | لازم |
| `Observers/Booking/BookingObserver.php:215` | payments | error | ❌ Failed to add income and commission | لازم — پرداخت (۳۶۵ روز) |
| `Observers/Booking/BookingObserver.php:231` | عمومی | warning | ⚠️ Specialist not found for cancellation | لازم |
| `Observers/Booking/BookingObserver.php:246` | payments | warning | ⚠️ Refund already exists | لازم — پرداخت (۳۶۵ روز) |
| `Observers/Booking/BookingObserver.php:376` | payments | error | ❌ Failed to handle cancellation | لازم — پرداخت (۳۶۵ روز) |
| `Observers/Booking/BookingObserver.php:401` | payments | warning | ⚠️ سهم متخصص از موجودی واقعی (نه در انتظار) کسر شد — اگر قبلاً برداشت  | لازم — پرداخت (۳۶۵ روز) |
| `Observers/Booking/BookingObserver.php:446` | عمومی | error | ❌ Failed to send cancellation SMS | لازم |
| `Observers/Booking/BookingObserver.php:559` | عمومی | warning | Bot message for booking failed | لازم |
| `Observers/Booking/BookingObserver.php:583` | عمومی | error | ❌ Loyalty Points Error | لازم |
| `Observers/Booking/BookingObserver.php:608` | عمومی | error | ❌ No-prepayment loyalty points failed | لازم |
| `Observers/Booking/BookingObserver.php:617` | عمومی | error | ❌ Error in deleted observer | لازم |
| `Observers/SalonPaymentGatewayObserver.php:39` | عمومی | error | Booking payment mode check after gateway change failed | لازم |
| `Payments/Drivers/AsanPardakhtDriver.php:177` | payments | error | AsanPardakht: Verify succeeded but Settlement did not — settle it from | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/MellatDriver.php:301` | payments | error | Mellat payment is settled but recorded as not paid — needs a manual ch | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/ParsianDriver.php:271` | payments | warning | Parsian reversal window has passed | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/ParsianDriver.php:273` | payments | error | Parsian reversal returned no result — check the ReversalService namesp | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/VandarPayoutDriver.php:90` | payments | error | VandarPayoutDriver: نتیجه‌ی تسویه نامعلوم — بررسی دستی در داشبورد وندا | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/VandarPayoutDriver.php:114` | payments | error | VandarPayoutDriver: درخواست تسویه ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/VandarPayoutDriver.php:130` | payments | warning | VandarPayoutDriver: خطای اتصال | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/VandarPayoutDriver.php:161` | payments | warning | VandarPayoutDriver: تمدید توکن — خطای اتصال | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/VandarPayoutDriver.php:168` | payments | error | VandarPayoutDriver: تمدید توکن ناموفق — توکن‌های تازه را از داشبورد ون | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/ZibalPayoutDriver.php:51` | payments | error | ZibalPayoutDriver: خطای اتصال | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/ZibalPayoutDriver.php:68` | payments | error | ZibalPayoutDriver: درخواست تسویه ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Payments/Drivers/ZibalPayoutDriver.php:82` | payments | error | ZibalPayoutDriver: نتیجه‌ی تسویه نامعلوم — بررسی دستی در پنل زیبال | لازم — پرداخت (۳۶۵ روز) |
| `Providers/AppServiceProvider.php:197` | عمومی | warning | N+1: lazy loading | لازم |
| `Services/Admin/Wallet/WalletAdminService.php:287` | payments | error | خطا در تسویه‌ی تراکنش کیف‌پول | لازم — پرداخت (۳۶۵ روز) |
| `Services/Api/ApiLoginService.php:229` | عمومی | error | Failed to send API login code | لازم |
| `Services/Booking/AttendanceCheckService.php:57` | عمومی | error | Attendance check failed | لازم |
| `Services/Booking/BookingModeGuard.php:48` | عمومی | warning | Booking payment mode fell back to none | لازم |
| `Services/Booking/BookingModeGuard.php:65` | عمومی | error | Booking payment mode enforcement failed | لازم |
| `Services/Booking/BookingModeSwitcher.php:136` | عمومی | warning | Prepayment-waived message to customer failed | لازم |
| `Services/Booking/BookingModeSwitcher.php:143` | عمومی | warning | Prepayment-waived notice to specialist failed | لازم |
| `Services/Booking/BookingService.php:326` | عمومی | warning | ⚠️ کد تخفیف بین محاسبه و رزرو نهایی به حداکثر استفاده رسید | لازم |
| `Services/Booking/BookingService.php:338` | عمومی | warning | خطا در ارسال نوتیفیکیشن ثبت نوبت | لازم |
| `Services/Bot/BotClient.php:56` | عمومی | warning | Bot sendMessage failed ({$messenger}) | لازم |
| `Services/Bot/BotClient.php:70` | عمومی | warning | Bot sendMessage rejected ({$messenger}) | لازم |
| `Services/CategoryService.php:86` | عمومی | error | خطا در مرتب‌سازی دسته‌بندی‌ها | لازم |
| `Services/Courses/CourseService.php:221` | عمومی | warning | Course loyalty points failed | لازم |
| `Services/Customer/CustomerRegistrationService.php:44` | عمومی | متغیر | Queued customer registration code | info فقط در حالت «فقط لاگ»، وگرنه debug |
| `Services/Customer/CustomerRegistrationService.php:120` | security | info | Unverified customer account claimed by verified registration | لازم — امنیت (۱۹۰ روز) |
| `Services/Payment/BookingPaymentFinalizer.php:153` | payments | warning | Gateway payment arrived for a booking that can no longer be kept | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/BookingWalletShare.php:75` | payments | error | Wallet share could not be returned: the customer no longer exists | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/HeldBookingPayment.php:77` | payments | warning | Booking payment held: gateway verify did not complete | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/HeldBookingPayment.php:119` | payments | warning | Held booking payment: verify threw | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/HeldBookingPayment.php:141` | payments | info | Held booking payment confirmed by the gateway | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/HeldBookingPayment.php:190` | payments | warning | Held booking payment released after the hold window | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/InvoiceInstallments.php:149` | payments | info | Unfinished invoice closed; paid installments became platform credit | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/LostSlotRefundService.php:110` | payments | warning | Booking payment refunded: slot was taken while the customer was at the | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SalonPayoutService.php:41` | payments | warning | SalonPayoutService: تسویه‌ی خودکار برای این سالن پیکربندی نشده | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SmsPackPaymentService.php:58` | payments | error | SmsPackPaymentService: شروع پرداخت بسته‌ی پیامک ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SmsPackPaymentService.php:71` | payments | warning | SmsPackPaymentService: عدم تطابق authority در کال‌بک | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SmsPackPaymentService.php:109` | payments | warning | SmsPackPaymentService: تأیید پرداخت بسته‌ی پیامک ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SubscriptionPaymentService.php:71` | payments | error | SubscriptionPaymentService: خطای اتصال به درگاه پرداخت اشتراک | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SubscriptionPaymentService.php:77` | payments | error | SubscriptionPaymentService: درخواست پرداخت اشتراک ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SubscriptionPaymentService.php:97` | payments | warning | SubscriptionPaymentService: عدم تطابق authority در کال‌بک | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SubscriptionPaymentService.php:143` | payments | error | SubscriptionPaymentService: خطای اتصال هنگام تأیید پرداخت اشتراک | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/SubscriptionPaymentService.php:149` | payments | warning | SubscriptionPaymentService: تأیید پرداخت اشتراک ناموفق | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:55` | payments | warning | Unanswered verify recovery: gateway row is gone | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:64` | payments | warning | Unanswered verify recovery: verify threw | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:130` | payments | warning | Abandoned payment verify threw | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:235` | payments | warning | Zarinpal lists a closed payment as paid but unverified; needs a human | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:257` | payments | warning | Unverified-list payment verify threw | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:293` | payments | warning | Abandoned payment needs a human | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/UnansweredVerifyRecovery.php:382` | payments | warning | Unanswered payment credited to the customer wallet | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/Zarinpal/ZarinpalConnectService.php:84` | payments | warning | Zarinpal connect: token request failed | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/Zarinpal/ZarinpalConnectService.php:91` | payments | warning | Zarinpal connect: no access token | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/Zarinpal/ZarinpalConnectService.php:238` | payments | warning | Zarinpal connect: GraphQL request failed | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/Zarinpal/ZarinpalConnectService.php:245` | payments | warning | Zarinpal connect: GraphQL error | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/ZarinpalUnverifiedReconciler.php:47` | payments | warning | Zarinpal unVerified call failed | لازم — پرداخت (۳۶۵ روز) |
| `Services/Payment/ZarinpalUnverifiedReconciler.php:60` | payments | info | Zarinpal unVerified lists an authority we do not own | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:139` | payments | error | ❌ Payment Request Failed | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:251` | payments | error | ❌ Booking ID not found in callback | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:300` | payments | info | ⚠️ Payment Cancelled by User | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:330` | payments | warning | ⚠️ Payment Verification Failed | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:339` | payments | error | 💥 Payment Verification Exception | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:401` | payments | error | 💥 Course Payment Verification Exception | لازم — پرداخت (۳۶۵ روز) |
| `Services/PaymentService.php:476` | payments | error | 💥 Wallet Charge Verification Exception | لازم — پرداخت (۳۶۵ روز) |
| `Services/PhoneVerificationService.php:31` | عمومی | متغیر | Queued phone verification code for sending | info فقط در حالت «فقط لاگ»، وگرنه debug |
| `Services/PhoneVerificationService.php:42` | عمومی | متغیر | Queued login verification code for sending | info فقط در حالت «فقط لاگ»، وگرنه debug |
| `Services/PhoneVerificationService.php:53` | security | warning | Invalid or expired verification code | لازم — امنیت (۱۹۰ روز) |
| `Services/PhoneVerificationService.php:64` | security | warning | Invalid or expired login verification code | لازم — امنیت (۱۹۰ روز) |
| `Services/Push/AppMessenger.php:277` | عمومی | warning | Push inbox row failed | لازم |
| `Services/Qr/QrScanCounter.php:34` | عمومی | warning | QrScanCounter: record failed | لازم |
| `Services/ReportCacheService.php:95` | عمومی | warning | Report cache flush failed | لازم |
| `Services/Review/ReviewService.php:75` | عمومی | error | ❌ Failed to send review request | لازم |
| `Services/Review/ReviewService.php:121` | عمومی | warning | Failed to add loyalty points for review | لازم |
| `Services/Review/ReviewService.php:130` | عمومی | error | ❌ Failed to create review | لازم |
| `Services/Review/ReviewService.php:151` | عمومی | error | ❌ Failed to respond to review | لازم |
| `Services/Review/ReviewService.php:183` | عمومی | error | Failed to notify admin about negative review | لازم |
| `Services/Review/ReviewService.php:195` | security | warning | ❌ Invalid or expired token | لازم — امنیت (۱۹۰ روز) · ماسک |
| `Services/SMSService.php:65` | عمومی | info | SMS: در حال ارسال | info فقط در حالت «فقط لاگ» · ماسک |
| `Services/SMSService.php:67` | عمومی | debug | SMS: در حال ارسال | debug · ماسک |
| `Services/SMSService.php:84` | عمومی | error | Kavenegar API Error (Send): | لازم · ماسک |
| `Services/SMSService.php:92` | عمومی | error | Kavenegar HTTP Error (Send): | لازم · ماسک |
| `Services/SMSService.php:96` | عمومی | error | General SMS Send Error: | لازم · ماسک |
| `Services/SMSService.php:136` | عمومی | متغیر | SMS Template: در حال ارسال | info فقط در حالت «فقط لاگ»، وگرنه debug · ماسک |
| `Services/SMSService.php:164` | عمومی | error | Kavenegar API Error (Lookup): | لازم · ماسک |
| `Services/SMSService.php:174` | عمومی | error | Kavenegar HTTP Error (Lookup): | لازم · ماسک |
| `Services/SMSService.php:182` | عمومی | error | General SMS Lookup Error: | لازم · ماسک |
| `Services/SMSService.php:233` | عمومی | warning | Kavenegar provider alert failed | لازم |
| `Services/SMSService.php:243` | عمومی | info | SMS: feature_blocked | لازم · ماسک |
| `Services/SMSService.php:253` | عمومی | warning | SmsQuotaService: salon not found for quota check, allowing send | لازم |
| `Services/SMSService.php:265` | عمومی | warning | SmsQuotaService: monthly SMS quota exhausted, send blocked | لازم · ماسک |
| `Services/Salon/SalonVerificationService.php:108` | عمومی | warning | salon verification SMS failed | لازم |
| `Services/SalonSignup/SalonSignupChallenge.php:48` | عمومی | متغیر | Queued salon signup code | info فقط در حالت «فقط لاگ»، وگرنه debug |
| `Services/SecurePaymentService.php:33` | payments | info | Secure payment initiated | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurePaymentService.php:47` | payments | warning | Secure payment verification failed - payment not found | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurePaymentService.php:69` | payments | warning | Secure payment verification failed - expired | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurePaymentService.php:79` | payments | warning | Secure payment verification failed - could not decrypt reference data | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurePaymentService.php:87` | payments | warning | Secure payment verification failed - amount mismatch | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurePaymentService.php:99` | payments | warning | Secure payment verification failed - time expired | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurePaymentService.php:106` | payments | info | Secure payment verified successfully | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurityLogService.php:34` | security | warning | Failed login attempt | لازم — امنیت (۱۹۰ روز) |
| `Services/SecurityLogService.php:36` | security | info | Successful login | لازم — امنیت (۱۹۰ روز) |
| `Services/SecurityLogService.php:57` | security | warning | Suspicious activity detected | لازم — امنیت (۱۹۰ روز) |
| `Services/SecurityLogService.php:85` | payments | info/warning | Bank payment reversed after unanswered verify / reverse failed | لازم — پرداخت (۳۶۵ روز) |
| `Services/SecurityLogService.php:110` | security | info | Profile information changed | لازم — امنیت (۱۹۰ روز) |
| `Services/Sms/OtpSendGuard.php:99` | security | warning | OTP send blocked | لازم — امنیت (۱۹۰ روز) · ماسک |
| `Services/Sms/OtpSendGuard.php:115` | عمومی | warning | OTP guard alert failed | لازم |
| `Services/Specialist/SpecialistAccountLinker.php:56` | security | info | Specialist records linked to staff account | لازم — امنیت (۱۹۰ روز) |
| `Services/Specialist/SpecialistAccountLinker.php:77` | security | info | Customer account promoted to specialist | لازم — امنیت (۱۹۰ روز) |
| `Services/Specialist/SpecialistActivationService.php:45` | عمومی | متغیر | Queued specialist activation code | info فقط در حالت «فقط لاگ»، وگرنه debug |
| `Services/SuperAdmin/SuperAdminAudit.php:32` | security | warning | SuperAdminAudit: record failed | لازم — امنیت (۱۹۰ روز) |
| `Services/SuperAdmin/SuperAdminNotifier.php:26` | عمومی | warning | SuperAdminNotifier failed | لازم |
| `Services/TwoFactorAuthService.php:25` | عمومی | متغیر | 2FA code generated | info فقط در حالت «فقط لاگ»، وگرنه debug |
| `Services/TwoFactorAuthService.php:44` | security | info | 2FA verified successfully | لازم — امنیت (۱۹۰ روز) |
| `Services/TwoFactorAuthService.php:48` | security | warning | 2FA verification failed | لازم — امنیت (۱۹۰ روز) |
| `Services/TwoFactorAuthService.php:67` | security | info | 2FA enabled | لازم — امنیت (۱۹۰ روز) |
| `Services/TwoFactorAuthService.php:78` | security | info | 2FA disabled | لازم — امنیت (۱۹۰ روز) |
| `Support/Auth/PasswordLockout.php:47` | security | warning | Password login locked after too many wrong passwords | لازم — امنیت (۱۹۰ روز) |
| `Support/Auth/SmsCode.php:84` | security | warning | SMS code burned after too many wrong attempts | لازم — امنیت (۱۹۰ روز) |
| `Support/CurrentSalon.php:115` | عمومی | warning | Salon-scoped query without a current salon | لازم |
| `Traits/HasJalaliDates.php:72` | عمومی | warning | خطا در تبدیل تاریخ شمسی ({$context}): | لازم |
