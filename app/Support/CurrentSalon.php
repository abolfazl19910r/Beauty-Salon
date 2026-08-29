<?php

namespace App\Support;

use App\Models\Salon;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2): registered as a
 * singleton (see AppServiceProvider::register()), so it lives for exactly one request and is
 * shared by every class that resolves it during that request — the salon middleware trio
 * (ResolveSalonFromRoute / EnsureAdminSalonActive) sets it once near the start of the request;
 * everything downstream (BelongsToSalon's global scope, controllers, views) just reads it.
 *
 * Deliberately NOT set at all for super-admin routes — see EnsureSuperAdmin middleware, which
 * never calls set(). BelongsToSalon::bootBelongsToSalon() below only adds its WHERE clause when
 * id() returns a value, so an unset CurrentSalon means "no salon filter" rather than "filter to
 * nothing" — this is what lets the super-admin panel see every salon's data without a single
 * withoutGlobalScope() call scattered through its controllers.
 */
class CurrentSalon
{
    protected ?Salon $salon = null;

    public function set(Salon $salon): void
    {
        $this->salon = $salon;
    }

    public function get(): ?Salon
    {
        return $this->salon;
    }

    public function id(): ?int
    {
        return $this->salon?->id;
    }

    public function clear(): void
    {
        $this->salon = null;
    }
}
