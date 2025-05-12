<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loyalty;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'required_points' => 'required|integer|min:1',
                'discount_type' => 'required|in:fixed,percentage',
                'discount_amount' => [
                    'required',
                    'numeric',
                    'min:1',
                    function($attribute, $value, $fail) use ($request) {
                        if ($request->discount_type === 'percentage' && $value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                        }
                    }
                ],
                'max_uses' => 'required|integer|min:1',
                'is_active' => 'boolean'
            ]);

            $reward = Reward::create($validated);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت ایجاد شد');
        } catch (\Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در ایجاد پاداش: ' . $e->getMessage());
        }
    }

    public function show(Reward $reward)
    {
        return view('admin.loyalty.show', compact('reward'));
    }

    public function edit(Reward $reward)
    {
        try {
            return view('admin.loyalty.edit', [
                'reward' => $reward
            ]);
        } catch (\Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در بارگذاری اطلاعات پاداش: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Reward $reward)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'required_points' => 'required|integer|min:1',
                'discount_type' => 'required|in:fixed,percentage',
                'discount_amount' => [
                    'required',
                    'numeric',
                    'min:1',
                    function($attribute, $value, $fail) use ($request) {
                        if ($request->discount_type === 'percentage' && $value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                        }
                    }
                ],
                'max_uses' => 'required|integer|min:' . $reward->used_count,
                'is_active' => 'boolean'
            ]);

            $reward->update($validated);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت به‌روزرسانی شد.');
        } catch (\Exception $e) {
            return redirect()->route('admin.loyalty.edit', $reward)
                ->with('error', 'خطا در به‌روزرسانی پاداش: ' . $e->getMessage())
                ->withInput();
        }
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
        try {
            $rewards = Reward::where('is_active', true)
                ->orderBy('required_points')
                ->get();

            return response()->json($rewards);
        } catch (\Exception $e) {
            Log::error('خطا در دریافت پاداش‌ها: ' . $e->getMessage());

            return response()->json([
                'message' => 'خطا در دریافت لیست پاداش‌ها',
                'error' => $e->getMessage()
            ], 500);
        }
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
            $data = Reward::orderBy('required_points')
                ->get();
        } else {
            $data = [];
        }

        return response()->json($data);
    }

    public function storeReward(Request $request)
    {
        try {
            Log::info('Creating new reward', $request->all());

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'required_points' => 'required|integer|min:1',
                'discount_type' => 'required|in:fixed,percentage',
                'discount_amount' => [
                    'required',
                    'numeric',
                    'min:1',
                    function($attribute, $value, $fail) use ($request) {
                        if ($request->discount_type === 'percentage' && $value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                        }
                    }
                ],
                'max_uses' => 'required|integer|min:1',
                'is_active' => 'boolean'
            ]);

            $reward = Reward::create($validated);

            Log::info('Reward created successfully', ['id' => $reward->id]);

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت ایجاد شد',
                'data' => $reward
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating reward', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد پاداش: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showReward(Reward $reward)
    {
        return response()->json($reward);
    }

    public function updateReward(Request $request, Reward $reward)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'required_points' => 'sometimes|integer|min:1',
                'discount_type' => 'sometimes|in:fixed,percentage',
                'discount_amount' => [
                    'sometimes',
                    'numeric',
                    'min:1',
                    function($attribute, $value, $fail) use ($request) {
                        if ($request->has('discount_type') &&
                            $request->discount_type === 'percentage' &&
                            $value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                        }
                    }
                ],
                'max_uses' => 'sometimes|integer|min:' . $reward->used_count,
                'is_active' => 'sometimes|boolean'
            ]);

            $reward->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت بروزرسانی شد',
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بروزرسانی پاداش: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyReward(Reward $reward)
    {
        try {
            if ($reward->used_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'پاداش‌هایی که استفاده شده‌اند قابل حذف نیستند'
                ], 422);
            }

            $reward->delete();

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت حذف شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف پاداش: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics()
    {
        $totalActivePoints = LoyaltyPoint::where('type', 'earned')->sum('points');
        $totalPointUsers = LoyaltyPoint::distinct('user_id')->count('user_id');
        $averageUserPoints = $totalPointUsers > 0 ? round($totalActivePoints / $totalPointUsers) : 0;
        $totalRedeemedRewards = Reward::sum('used_count');

        $topUsers = LoyaltyPoint::select('user_id', DB::raw('SUM(points) as total_points'))
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit(5)
            ->with('user:id,name,phone')
            ->get();

        $recentRedemptions = LoyaltyPoint::where('type', 'spent')
            ->with(['user:id,name', 'booking'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'total_active_points' => $totalActivePoints,
            'total_point_users' => $totalPointUsers,
            'average_user_points' => $averageUserPoints,
            'total_redeemed_rewards' => $totalRedeemedRewards,
            'top_users' => $topUsers,
            'recent_redemptions' => $recentRedemptions
        ]);
    }

    public function getUserPoints(User $user)
    {
        $points = LoyaltyPoint::where('user_id', $user->id)
            ->select(
                DB::raw('SUM(CASE WHEN type = "earned" THEN points ELSE 0 END) as earned'),
                DB::raw('SUM(CASE WHEN type = "spent" THEN points ELSE 0 END) as spent')
            )
            ->first();

        $history = LoyaltyPoint::where('user_id', $user->id)
            ->with('booking')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone
            ],
            'points' => [
                'current' => $points->earned - $points->spent,
                'earned' => $points->earned,
                'spent' => $points->spent
            ],
            'history' => $history
        ]);
    }

    public function addUserPoints(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'points' => 'required|integer|min:1',
                'description' => 'required|string',
                'expires_at' => 'nullable|date|after:today'
            ]);

            $point = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $validated['points'],
                'type' => 'earned',
                'description' => $validated['description'],
                'expires_at' => $validated['expires_at'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'امتیاز با موفقیت به کاربر اضافه شد',
                'data' => $point
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در افزودن امتیاز: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deductUserPoints(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'points' => 'required|integer|min:1',
                'description' => 'required|string'
            ]);

            $currentPoints = LoyaltyPoint::where('user_id', $user->id)
                ->selectRaw('SUM(points) as total')
                ->first()
                ->total;

            if ($currentPoints < $validated['points']) {
                return response()->json([
                    'success' => false,
                    'message' => 'امتیاز کاربر کافی نیست'
                ], 422);
            }

            $point = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => -$validated['points'],
                'type' => 'spent',
                'description' => $validated['description']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'امتیاز با موفقیت از کاربر کسر شد',
                'data' => $point
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در کسر امتیاز: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSettings()
    {
        $settings = DB::table('settings')
            ->where('group', 'loyalty')
            ->pluck('value', 'key');

        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'points_per_purchase' => 'sometimes|numeric|min:0',
                'points_expiry_days' => 'sometimes|integer|min:0',
                'min_points_for_redeem' => 'sometimes|integer|min:0',
                'enable_referral_points' => 'sometimes|boolean',
                'referral_points' => 'sometimes|integer|min:0',
                'welcome_points' => 'sometimes|integer|min:0'
            ]);

            foreach ($validated as $key => $value) {
                DB::table('settings')
                    ->updateOrInsert(
                        ['group' => 'loyalty', 'key' => $key],
                        ['value' => $value]
                    );
            }

            return response()->json([
                'success' => true,
                'message' => 'تنظیمات با موفقیت بروزرسانی شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بروزرسانی تنظیمات: ' . $e->getMessage()
            ], 500);
        }
    }
}
