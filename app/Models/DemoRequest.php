<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * درخواست دموی رایگان از صفحه‌ی فروش (۲۰۲۶-۱۰-۱۴). سطح پلتفرم، بدون BelongsToSalon (TenantModelClassificationTest).
 */
class DemoRequest extends Model
{
    use HasFactory;

    public const TYPES = [
        'hair' => 'آرایشگاه و سالن مو',
        'nails' => 'ناخن و زیبایی',
        'skin' => 'پوست، مو و لیزر',
        'multi' => 'مجموعه چندشعبه',
    ];

    protected $fillable = ['name', 'salon_name', 'phone', 'salon_type', 'ip', 'handled_at', 'handled_by', 'admin_note'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('handled_at');
    }

    public function typeLabel(): ?string
    {
        return self::TYPES[$this->salon_type] ?? null;
    }
}
