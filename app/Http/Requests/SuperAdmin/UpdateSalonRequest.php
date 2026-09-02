<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ⭐ slug is deliberately NOT editable here — immutable by design (see the "Migration 1 —
 * جدول salons" section of Rasta_unified_prompt.md: renaming a salon's display name shouldn't
 * ever break a bookmarked/SMS'd link to its slug).
 */
class UpdateSalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'max_specialists_count' => ['required', 'integer', 'min:0'],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*' => ['string'],
        ];
    }
}
