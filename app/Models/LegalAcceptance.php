<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * پذیرش قوانین / حریم خصوصی با نسخه و زمان (بسته‌ی مجوز 5د) — App\Services\Legal\LegalAcceptanceRecorder. سطح پلتفرم.
 */
class LegalAcceptance extends Model
{
    public const UPDATED_AT = null;

    public const DOCUMENTS = ['terms' => 'قوانین و شرایط (مشتری)', 'privacy' => 'حریم خصوصی', 'salon_terms' => 'قوانین و شرایط (سالن)'];

    protected $fillable = ['user_id', 'salon_id', 'document', 'version', 'source', 'app_version', 'ip_address', 'user_agent', 'accepted_at'];

    protected $casts = ['accepted_at' => 'datetime'];
}
