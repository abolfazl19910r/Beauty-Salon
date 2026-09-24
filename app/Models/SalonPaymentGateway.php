<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⭐ درگاه پرداخت یک سالن (لایه‌ی چند درگاه، ۲۰۲۶-۰۹-۲۵). عمداً BelongsToSalon نداره: همیشه از طریق
 * رابطه‌ی $salon->paymentGateways() خونده می‌شه (از جمله در Jobها و callbackها که CurrentSalon ندارن).
 */
class SalonPaymentGateway extends Model
{
    public const DRIVER_LABELS = [
        'zarinpal' => 'زرین‌پال',
    ];

    protected $fillable = ['salon_id', 'driver', 'label', 'credentials', 'is_active', 'priority', 'fee_percent', 'fee_fixed_toman'];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'fee_percent' => 'decimal:2',
        'fee_fixed_toman' => 'integer',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function displayName(): string
    {
        return $this->label ?: (self::DRIVER_LABELS[$this->driver] ?? $this->driver);
    }
}
