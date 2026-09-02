<?php

namespace App\Traits;

use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Builder;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2): applied to every model
 * backing a table that got salon_id in migration 2026_08_29_000102_add_salon_id_to_owned_tables
 * (Specialist, BeautyService, Category, BlogPost, BlogCategory, GalleryImage, Announcement,
 * DiscountCode, LoyaltySetting, WalletSetting, AdminWallet, Booking).
 *
 * Two things happen automatically for any model using this trait:
 *   1. Every query is scoped to app(CurrentSalon::class)->id() — when no current salon is set
 *      (super-admin routes; see CurrentSalon's own docblock), no filter is added at all, so the
 *      super-admin panel sees every salon's rows without ever calling withoutGlobalScope().
 *   2. On create, salon_id is auto-filled from the current salon if not already given — this is
 *      what makes AdminWallet::getWallet()'s existing `self::first() ?? self::create([...])`
 *      fallback correctly create a NEW per-salon row instead of resurrecting the old global
 *      singleton, with zero changes needed to that method itself.
 *
 * ⚠️ This scope only reaches Eloquent queries. Raw DB::table()/DB::select() calls (e.g. in
 * AdminReportExport's spreadsheet/PDF export queries) do NOT go through Eloquent and must be
 * scoped explicitly by hand — documented as a standing risk in the SaaS section of
 * Rasta_unified_prompt.md ("هرگز فراموش نشود که salon_id روی هر query جدید هم اعمال بشه").
 */
trait BelongsToSalon
{
    protected static function bootBelongsToSalon(): void
    {
        static::addGlobalScope('salon', function (Builder $builder) {
            $salonId = app(CurrentSalon::class)->id();

            if ($salonId !== null) {
                $builder->where($builder->getModel()->getTable().'.salon_id', $salonId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->salon_id)) {
                $salonId = app(CurrentSalon::class)->id();

                if ($salonId !== null) {
                    $model->salon_id = $salonId;
                }
            }
        });
    }
}
