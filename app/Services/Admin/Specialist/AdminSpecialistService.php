<?php

namespace App\Services\Admin\Specialist;

use App\Exceptions\SpecialistQuotaExceededException;
use App\Models\Specialist;
use App\Models\User;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\DB;

class AdminSpecialistService
{
    public function __construct(
        protected readonly CurrentSalon $currentSalon,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function create(array $validated, ?string $rawCommissionRate): array
    {
        return DB::transaction(function () use ($validated, $rawCommissionRate) {
            $services = $validated['services'];
            unset($validated['services']);

            $salon = $this->currentSalon->get();
            $currentCount = $this->specialistRepository->count();

            if ($currentCount >= $salon->max_specialists_count) {
                throw SpecialistQuotaExceededException::quotaReached(
                    'Specialist quota reached for salon.',
                    [
                        'salon_id' => $salon->id,
                        'current_count' => $currentCount,
                        'max_specialists_count' => $salon->max_specialists_count,
                    ]
                );
            }

            $validated['phone'] = $this->normalizePhone($validated['phone']);
            $validated['commission_rate'] = $this->parseCommissionRate($rawCommissionRate);

            $matchedUser = $this->matchAndPromoteUser($validated['phone']);
            $validated['user_id'] = $matchedUser?->id;

            $specialist = $this->specialistRepository->create($validated);
            $specialist->services()->attach($services);

            return [
                'specialist' => $specialist,
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

            $matchedUser = $this->matchAndPromoteUser($validated['phone']);
            $validated['user_id'] = $matchedUser?->id;

            $specialist = $this->specialistRepository->update($specialist, $validated);
            $specialist->services()->sync($services);

            return $specialist;
        });
    }

    public function delete(Specialist $specialist): void
    {
        DB::transaction(function () use ($specialist) {
            $specialist->services()->detach();
            $this->specialistRepository->delete($specialist);
        });
    }

    private function matchAndPromoteUser(string $phone): ?User
    {
        $currentSalonId = $this->currentSalon->id();

        $matchedUser = $this->userRepository->findStaffByPhone($phone);

        if ($matchedUser) {
            return $matchedUser;
        }

        $matchedUser = $this->userRepository->findCustomerByPhoneInSalon($phone, $currentSalonId);

        if ($matchedUser) {
            $matchedUser->update([
                'user_type' => 'staff',
                'salon_id' => $currentSalonId,
            ]);
        }

        return $matchedUser;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0098') && strlen($digits) === 14) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    private function parseCommissionRate(?string $raw): ?float
    {
        return $raw !== null && $raw !== '' ? (float) $raw : null;
    }
}
