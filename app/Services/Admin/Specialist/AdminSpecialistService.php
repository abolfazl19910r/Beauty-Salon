<?php

namespace App\Services\Admin\Specialist;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminSpecialistService
{
    /**
     * @return array{specialist: Specialist, matched_user: ?User}
     */
    public function create(array $validated, ?string $rawCommissionRate): array
    {
        return DB::transaction(function () use ($validated, $rawCommissionRate) {
            $services = $validated['services'];
            unset($validated['services']);

            $validated['phone'] = $this->normalizePhone($validated['phone']);
            $validated['commission_rate'] = $this->parseCommissionRate($rawCommissionRate);

            $matchedUser = User::where('phone', $validated['phone'])->first();
            $validated['user_id'] = $matchedUser?->id;

            $specialist = Specialist::create($validated);
            $specialist->services()->attach($services);

            return [
                'specialist'   => $specialist,
                'matched_user' => $matchedUser,
            ];
        });
    }

    public function update(Specialist $specialist, array $validated, ?string $rawCommissionRate): Specialist
    {
        return DB::transaction(function () use ($specialist, $validated, $rawCommissionRate) {
            $services = $validated['services'];
            unset($validated['services']);

            $validated['phone'] = $this->normalizePhone($validated['phone']);
            $validated['commission_rate'] = $this->parseCommissionRate($rawCommissionRate);

            $matchedUser = User::where('phone', $validated['phone'])->first();
            $validated['user_id'] = $matchedUser?->id;

            $specialist->update($validated);
            $specialist->services()->sync($services);

            return $specialist;
        });
    }

    public function delete(Specialist $specialist): void
    {
        DB::transaction(function () use ($specialist) {
            $specialist->services()->detach();
            $specialist->delete();
        });
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0098') && strlen($digits) === 14) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function parseCommissionRate(?string $raw): ?float
    {
        return $raw !== null && $raw !== '' ? (float) $raw : null;
    }
}
