<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loyalty;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLoyaltyController extends Controller
{
    public function index()
    {
        $totalActivePoints = LoyaltyPoint::where('type', 'earned')->sum('points');
        $totalPointUsers = LoyaltyPoint::distinct('user_id')->count('user_id');
        $averageUserPoints = $totalPointUsers > 0 ? $totalActivePoints / $totalPointUsers : 0;
        $totalRedeemedRewards = Reward::sum('used_count');

        return view('admin.loyalty.index', compact(
            'totalActivePoints',
            'totalPointUsers',
            'averageUserPoints',
            'totalRedeemedRewards'
        ));
    }

    public function create()
    {
        return view('admin.loyalty.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        Loyalty::create($validated);

        return redirect()->route('admin.loyalty.index')
            ->with('success', 'برنامه وفاداری با موفقیت ایجاد شد.');
    }

    public function show(Loyalty $loyalty)
    {
        return view('admin.loyalty.show', compact('loyalty'));
    }

    public function edit(Loyalty $loyalty)
    {
        return view('admin.loyalty.edit', compact('loyalty'));
    }

    public function update(Request $request, Loyalty $loyalty)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $loyalty->update($validated);

        return redirect()->route('admin.loyalty.index')
            ->with('success', 'برنامه وفاداری با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Loyalty $loyalty)
    {
        $loyalty->delete();

        return redirect()->route('admin.loyalty.index')
            ->with('success', 'برنامه وفاداری با موفقیت حذف شد.');
    }

    public function redeemReward(Request $request, Reward $reward)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $loyaltyService = app()->make(\App\Services\LoyaltyService::class);
            $result = $loyaltyService->redeemReward($validatedData['user_id'], $reward);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت برای کاربر فعال شد.');
        } catch (\Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در فعال‌سازی پاداش: ' . $e->getMessage());
        }
    }

    public function getPoints()
    {
        $points = LoyaltyPoint::select(
            DB::raw('SUM(CASE WHEN type = "earned" THEN points ELSE 0 END) as earned'),
            DB::raw('SUM(CASE WHEN type = "spent" THEN points ELSE 0 END) as spent')
        )->first();

        return response()->json([
            'total' => $points->earned - $points->spent,
            'earned' => $points->earned,
            'spent' => $points->spent
        ]);
    }

    public function getRewards()
    {
        $rewards = Reward::where('is_active', true)
            ->orderBy('required_points')
            ->get();

        return response()->json($rewards);
    }

    public function getHistory()
    {
        $history = LoyaltyPoint::with(['user:id,name', 'booking'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($history);
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'points');

        if ($type === 'points') {
            $data = LoyaltyPoint::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($type === 'rewards') {
            $data = Reward::withCount('redemptions')
                ->orderBy('required_points')
                ->get();
        } else {
            $data = [];
        }

        return response()->json($data);
    }
}
