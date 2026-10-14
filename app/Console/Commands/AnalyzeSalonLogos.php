<?php

namespace App\Console\Commands;

use App\Models\Salon;
use App\Services\Salon\SalonLogoService;
use Illuminate\Console\Command;

/**
 * ⭐ بسته‌ی د — یک بار پس از migrate: لحن روشنایی لوگوهایی که پیش از این بسته آپلود شده‌اند.
 * پیش‌فرض فقط لوگوهای سنجیده‌نشده؛ --all همه را دوباره می‌سنجد. چند بار اجرا بی‌خطر است.
 */
class AnalyzeSalonLogos extends Command
{
    protected $signature = 'salons:analyze-logos {--all : سنجش دوباره‌ی همه‌ی لوگوها، حتی سنجیده‌شده‌ها}';

    protected $description = 'Measure the brightness tone of uploaded salon logos (for the contrast plaque)';

    public function handle(SalonLogoService $logos): int
    {
        $query = Salon::query()->where(function ($q) {
            $q->whereNotNull('logo_path')->orWhereNotNull('logo_dark_path');
        });

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->where(fn ($q) => $q->whereNotNull('logo_path')->whereNull('logo_tone'))
                    ->orWhere(fn ($q) => $q->whereNotNull('logo_dark_path')->whereNull('logo_dark_tone'));
            });
        }

        $done = 0;
        $query->orderBy('id')->chunkById(100, function ($salons) use ($logos, &$done) {
            foreach ($salons as $salon) {
                if ($logos->analyzeStored($salon)) {
                    $done++;
                }
            }
        });

        $this->info("لوگوی {$done} سالن سنجیده شد.");

        return self::SUCCESS;
    }
}
