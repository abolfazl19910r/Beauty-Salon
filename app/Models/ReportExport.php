<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'format',
        'report_type',
        'filters',
        'status',
        'file_path',
        'error_message',
        'ready_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'ready_at' => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function isDownloadable(): bool
    {
        return $this->status === 'ready'
            && $this->file_path
            && Storage::disk('local')->exists($this->file_path);
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'در صف انتظار',
            'processing' => 'در حال آماده‌سازی',
            'ready' => 'آماده دانلود',
            'failed' => 'ناموفق',
            default => 'نامشخص',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending', 'processing' => 'blue',
            'ready' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getReportTypeTextAttribute(): string
    {
        return match ($this->report_type) {
            'weekly' => 'هفتگی',
            'monthly' => 'ماهانه',
            default => 'روزانه',
        };
    }
}
