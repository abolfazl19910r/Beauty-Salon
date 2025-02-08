<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ScheduledReportRun extends Model
{
    protected $fillable = [
        'scheduled_report_id',
        'status',
        'result_file',
        'error_message'
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class, 'scheduled_report_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function getResultFilePath(): ?string
    {
        return $this->result_file ? storage_path('app/public/' . $this->result_file) : null;
    }

    public function deleteResultFile(): bool
    {
        if ($this->result_file && Storage::exists('public/' . $this->result_file)) {
            Storage::delete('public/' . $this->result_file);
            return $this->update(['result_file' => null]);
        }
        return true;
    }
}
