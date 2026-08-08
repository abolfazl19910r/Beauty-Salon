<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReportSetting extends Model
{
    protected $fillable = [
        'user_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, $value): bool
    {
        $settings = $this->settings;
        $settings[$key] = $value;

        return $this->update(['settings' => $settings]);
    }

    public function removeSetting(string $key): bool
    {
        $settings = $this->settings;
        unset($settings[$key]);

        return $this->update(['settings' => $settings]);
    }

    public function hasSetting(string $key): bool
    {
        return isset($this->settings[$key]);
    }

    public static function getDefaultSettings(): array
    {
        return [
            'dashboard_layout' => 'grid',
            'default_report_type' => 'daily',
            'default_date_range' => '30',
            'chart_colors' => [
                'primary' => '#4299e1',
                'secondary' => '#48bb78',
                'accent' => '#ed8936',
            ],
            'notifications' => [
                'email' => true,
                'browser' => true,
            ],
            'export_settings' => [
                'include_charts' => true,
                'preferred_format' => 'excel',
            ],
            'scheduled_reports' => [
                'send_empty' => false,
                'include_comparison' => true,
            ],
        ];
    }

    public function resetToDefault(): bool
    {
        return $this->update(['settings' => self::getDefaultSettings()]);
    }

    public function mergeSettings(array $newSettings): bool
    {
        $settings = array_merge($this->settings ?? [], $newSettings);

        return $this->update(['settings' => $settings]);
    }

    public function saveMultipleSettings(array $settings): bool
    {
        return $this->update(['settings' => array_merge($this->settings ?? [], $settings)]);
    }

    public function getChartSettings(): array
    {
        return $this->settings['chart_colors'] ?? [
            'primary' => '#4299e1',
            'secondary' => '#48bb78',
            'accent' => '#ed8936',
        ];
    }

    public function getNotificationSettings(): array
    {
        return $this->settings['notifications'] ?? [
            'email' => true,
            'browser' => true,
        ];
    }

    public function getExportSettings(): array
    {
        return $this->settings['export_settings'] ?? [
            'include_charts' => true,
            'preferred_format' => 'excel',
        ];
    }
}
