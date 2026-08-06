/**
 * ⚠️ Cleanup (R-Cleanup-DeadCode, continued): This file previously mounted 7 admin/customer
 * components total across two cleanup passes — the first pass (AdminDashboard, ReportDashboard,
 * LoyaltyAdmin, AnnouncementAdmin, BlogAdmin, GalleryAdmin) removed because all 6 had been
 * converted to Blade. The one remaining mount, `booking-stats` (BookingStats.jsx), was kept at
 * the time under the assumption it served a client-side page — but a full grep across
 * `resources/views` confirmed no Blade template anywhere ever rendered an element with
 * `id="booking-stats"`, and no other route/controller/service referenced the
 * `admin.bookings.stats` / `/api/admin/bookings/stats` endpoints it called. It was 100% dead
 * code (a widget nothing ever rendered, calling an endpoint nothing else used). Removed along
 * with `BookingStats.jsx`, both routes, and `AdminBookingController::getStats()` /
 * `AdminBookingService::getStats()`.
 *
 * This file is now empty of any mount logic. It's kept as the Vite entry point referenced by
 * `layouts/admin.blade.php`'s `@vite(['resources/js/admin.jsx'])` in case a future admin-panel
 * React component needs one — do not re-add mounts here speculatively; only wire up a real,
 * rendered mount point.
 */
