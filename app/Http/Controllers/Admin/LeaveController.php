<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Specialist;
use Illuminate\Http\Request;
use App\Notifications\LeaveStatusNotification;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index(Specialist $specialist)
    {
        $leaves = $specialist->leaves()
            ->orderBy('start_date')
            ->get();
        return response()->json($leaves);
    }

    public function store(Request $request, Specialist $specialist)
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d|after:yesterday',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        $hasOverlap = $specialist->leaves()
            ->where('status', 'approved')
            ->overlapping($validated['start_date'], $validated['end_date'])
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'message' => 'این بازه زمانی با مرخصی دیگری تداخل دارد.'
            ], 422);
        }

        $hasBooking = $specialist->bookings()
            ->whereBetween('booking_time', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59'
            ])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($hasBooking) {
            return response()->json([
                'message' => 'در این بازه زمانی نوبت‌هایی ثبت شده است.'
            ], 422);
        }

        $leave = $specialist->leaves()->create($validated);

        // ارسال نوتیفیکیشن به مدیران
        // Notify admin users about new leave request
        // TODO: Implement admin notification

        return response()->json($leave, 201);
    }

    public function update(Request $request, Specialist $specialist, Leave $leave)
    {
        if ($leave->specialist_id !== $specialist->id) {
            return response()->json([
                'message' => 'شما اجازه ویرایش این درخواست را ندارید.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'reject_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        if ($validated['status'] === 'approved') {
            $hasOverlap = $specialist->leaves()
                ->where('id', '!=', $leave->id)
                ->where('status', 'approved')
                ->overlapping($leave->start_date, $leave->end_date)
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'message' => 'این بازه زمانی با مرخصی دیگری تداخل دارد.'
                ], 422);
            }

            $leave->approve();
        } else {
            $leave->reject($validated['reject_reason']);
        }

        $specialist->notify(new LeaveStatusNotification($leave));

        return response()->json($leave);
    }

    public function pendingLeaves()
    {
        $leaves = Leave::with('specialist')
            ->pending()
            ->orderBy('start_date')
            ->get();

        return response()->json($leaves);
    }

    public function checkAvailability(Request $request, Specialist $specialist)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $isOnLeave = $specialist->leaves()
            ->where('status', 'approved')
            ->where('start_date', '<=', $request->date)
            ->where('end_date', '>=', $request->date)
            ->exists();

        return response()->json([
            'is_on_leave' => $isOnLeave
        ]);
    }
}
