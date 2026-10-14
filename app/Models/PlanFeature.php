<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** یک امکان روشن در یک پلن (وجود ردیف = روشن). مقدار feature یکی از App\Support\Features\Feature است. */
class PlanFeature extends Model
{
    protected $fillable = ['plan_id', 'feature'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
