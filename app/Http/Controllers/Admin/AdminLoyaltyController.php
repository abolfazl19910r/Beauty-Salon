<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loyalty;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use Illuminate\Http\Request;

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
}
