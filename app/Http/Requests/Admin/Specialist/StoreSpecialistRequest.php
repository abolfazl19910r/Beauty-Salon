<?php

namespace App\Http\Requests\Admin\Specialist;

use App\Rules\MaxPercentage;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('access_admin_panel');
    }

    /**
     * Normalize the phone number before running the max:11/unique rules.
     * If this normalization is done after validate (e.g. inside Service),
     * +98/0098/space formats (which are longer than 11 characters)
     * will not reach that code at all and will be rejected with a max:11 error — i.e. normalizePhone
     * effectively becomes a dead code.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => $this->normalizePhone((string) $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:11', 'unique:specialists,phone'],
            'email' => ['required', 'email', 'unique:specialists,email'],
            'services' => ['required', 'array'],
            'services.*' => ['exists:beauty_services,id'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', new MaxPercentage],
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '0098') && strlen($digits) === 14) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }
}
