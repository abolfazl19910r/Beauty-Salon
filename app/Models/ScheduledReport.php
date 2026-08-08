<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledReport extends Model
{
    protected $fillable = [
        'user_id',
        'report_type',
        'parameters',
        'frequency',
        'next_run',
        'recipients',
        'is_active',
    ];

    protected $casts = [
        'parameters' => 'array',
        'recipients' => 'array',
        'next_run' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ScheduledReportRun::class);
    }

    public function lastRun(): BelongsTo
    {
        return $this->belongsTo(ScheduledReportRun::class, 'id', 'scheduled_report_id')
            ->latest();
    }

    public function isReadyToRun(): bool
    {
        return $this->is_active && now()->gte($this->next_run);
    }

    public function getLastRunStatusAttribute(): ?string
    {
        return $this->lastRun?->status;
    }

    public function getSuccessfulRunsCountAttribute(): int
    {
        return $this->runs()->where('status', 'success')->count();
    }

    public function getFailedRunsCountAttribute(): int
    {
        return $this->runs()->where('status', 'failed')->count();
    }

    public function disable(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function enable(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function updateNextRun(\DateTime $nextRun): bool
    {
        return $this->update(['next_run' => $nextRun]);
    }

    public function addRecipient(string $email): bool
    {
        $recipients = $this->recipients ?? [];
        if (! in_array($email, $recipients)) {
            $recipients[] = $email;

            return $this->update(['recipients' => $recipients]);
        }

        return true;
    }

    public function removeRecipient(string $email): bool
    {
        $recipients = $this->recipients ?? [];
        $recipients = array_diff($recipients, [$email]);

        return $this->update(['recipients' => array_values($recipients)]);
    }
}
