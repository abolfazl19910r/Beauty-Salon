<?php

namespace App\Services\Admin\Specialist;

use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\DB;

class AdminSpecialistService
{
    public function __construct(protected readonly CurrentSalon $currentSalon) {}

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

            $matchedUser = $this->matchAndPromoteUser($validated['phone']);
            $validated['user_id'] = $matchedUser?->id;

            $specialist = Specialist::create($validated);
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

    /**
     * ⭐ Customer identity redesign, follow-up (confirmed 2026-08-30): matching a specialist to
     * an existing User by phone is pre-existing behavior — what's new is that phone is no longer
     * globally unique for customers (only per-salon), so a plain global `User::where('phone',
     * ...)->first()` could non-deterministically grab a customer belonging to a completely
     * different, unrelated salon. Per the confirmed decision, when the match is an existing
     * customer, they're being promoted to staff of THIS salon (the one currently being
     * administered, from CurrentSalon) — user_type flips to 'staff' and salon_id is set to
     * match. This does NOT touch their historical data (past bookings, wallet balance) — those
     * stay tied to their user_id exactly as before; only their account's own salon_id/user_type
     * change going forward, matching how they'll authenticate from now on (globally, like any
     * other staff member, not through their old salon's /s/{slug}/login).
     *
     * A 'staff' match (already globally unique) needs no promotion — it's simply linked as-is,
     * same as before this redesign existed.
     */
    private function matchAndPromoteUser(string $phone): ?User
    {
        $currentSalonId = $this->currentSalon->id();

        // Staff match: phone is globally unique for user_type='staff', so this is unambiguous
        // regardless of which salon is currently active.
        $matchedUser = User::where('phone', $phone)->where('user_type', 'staff')->first();

        if ($matchedUser) {
            return $matchedUser;
        }

        // Customer match: phone is only unique PER salon now, so a bare where('phone', ...)
        // could match a completely unrelated customer of a different salon who happens to share
        // this number. Scoped to the current salon specifically — if this phone belongs to a
        // customer of some OTHER salon, that's a coincidence, not this specialist's account, and
        // is deliberately left unmatched (user_id stays null) rather than guessed at.
        $matchedUser = User::where('phone', $phone)
            ->where('user_type', 'customer')
            ->where('salon_id', $currentSalonId)
            ->first();

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
