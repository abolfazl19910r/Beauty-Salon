<?php

use App\Logging\MaskPersonalData;
use App\Logging\MirrorToDefaultChannel;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    | بررسی لاگ‌ها (۲۰۲۶-۱۱-۰۵؛ docs/deployment/LOGGING.md): لاگ عمومی (laravel.log، stderr) شماره‌ی موبایل و شبا را نصفه می‌نویسد.
    | security و payments خام‌اند (مدرک نگهداری ماده‌ی ۶۶۸ و تطبیق پرداخت). فقط برای عیب‌یابی لوکال: LOG_MASK_PERSONAL_DATA=false
    */
    // خالی یا نبودن = روشن (خط خالی «LOG_MASK_PERSONAL_DATA=» قبلاً خاموشش می‌کرد)؛ فقط false/0/off خاموش
    'mask_personal_data' => in_array(env('LOG_MASK_PERSONAL_DATA'), [null, ''], true)
        || (filter_var(env('LOG_MASK_PERSONAL_DATA'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'daily')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'tap' => [MaskPersonalData::class],
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'tap' => [MaskPersonalData::class],
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'tap' => [MaskPersonalData::class],
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'tap' => [MaskPersonalData::class],
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'tap' => [MaskPersonalData::class],
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            // نام کانال در هر خط (security.WARNING) — نسخه‌ی تکراری‌اش در laravel.log معلوم باشد از کجا آمده
            'name' => 'security',
            // پیش‌تر warning بود: «ورود موفق» و «تغییر پروفایل» (info) هیچ‌وقت در فایل نمی‌نشست (بررسی لاگ‌ها ۲۰۲۶-۱۱-۰۵)
            'level' => env('SECURITY_LOG_LEVEL') ?: 'info',
            // هشدار و خطا در لاگ عمومی (laravel.log، docker logs) هم — ماسک‌شده؛ نسخه‌ی کامل همین‌جا
            'tap' => [MirrorToDefaultChannel::class.':warning'],
            // ⭐ بسته‌ی مجوز 5د: پیش‌تر ۹۰ روز؛ IP و هویت دست‌کم شش ماه (config/retention.php، ماده‌ی ۶۶۸ آیین دادرسی کیفری)
            'days' => max(184, is_numeric(env('SECURITY_LOG_DAYS')) ? (int) env('SECURITY_LOG_DAYS') : (is_numeric(env('RETENTION_DAYS')) ? (int) env('RETENTION_DAYS') : 190)),
        ],
        // گزارش‌های CSP مرحله‌ی Report-Only (بسته‌ی مجوز 5د) — CspReportController
        'csp' => [
            'driver' => 'daily',
            'path' => storage_path('logs/csp.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'payments' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payments.log'),
            'name' => 'payments',
            'level' => env('PAYMENTS_LOG_LEVEL') ?: 'info',
            'days' => 365,
            'tap' => [MirrorToDefaultChannel::class.':warning'],
        ],
    ],

];
