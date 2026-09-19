<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Specialist;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(protected CurrentSalon $currentSalon) {}

    public function index(): View
    {
        // ⭐ Fix (real, confirmed cross-tenant data leak — found while testing محور «۳» with two
        // salons side by side): these cache keys used to be the literal strings 'home_services'/
        // 'home_specialists' with NO salon identifier in them, even though BeautyService and
        // Specialist are both salon-scoped models (BelongsToSalon). Whichever salon's homepage
        // was visited FIRST populated the cache for 30 minutes, and every OTHER salon's homepage
        // served that same cached result during that window — confirmed directly: salon A's
        // services showing up on salon B's homepage. This route is always reached through
        // 'salon.resolve' middleware (routes/web.php), so CurrentSalon is reliably bound here;
        // suffixing the key with its id is enough to give every salon its own cache bucket.
        $salonId = $this->currentSalon->id();

        $services = Cache::remember("home_services:{$salonId}", 1800, function () {
            return BeautyService::latest()
                ->select('id', 'name', 'slug', 'description', 'price', 'duration', 'image', 'category_id')
                ->take(6)
                ->get();
        });

        $specialists = Cache::remember("home_specialists:{$salonId}", 1800, function () {
            return Specialist::latest()
                ->with(['schedules' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')])
                ->select('id', 'name', 'email', 'phone', 'user_id')
                ->take(4)
                ->get();
        });

        return view('home', compact('services', 'specialists'));
    }
}
