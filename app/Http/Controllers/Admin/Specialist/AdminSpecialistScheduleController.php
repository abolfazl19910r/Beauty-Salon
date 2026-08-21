<?php

namespace App\Http\Controllers\Admin\Specialist;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSpecialistScheduleController extends Controller
{
    public function index(Specialist $specialist): View
    {
        return $this->edit($specialist);
    }

    public function edit(Specialist $specialist): View
    {
        $schedules = $specialist->schedules()
            ->get()
            ->groupBy('day_of_week');

        return view('admin.specialists.schedules.edit', [
            'specialist' => $specialist,
            'schedules' => $schedules,
        ]);
    }

    public function update(Request $request, Specialist $specialist): RedirectResponse
    {
        try {
            $request->validate([
                'schedules.*.day_of_week' => 'required|integer|between:0,6',
                'schedules.*.is_active' => 'nullable',
                'schedules.*.start_time' => 'nullable|required_if:schedules.*.is_active,1',
                'schedules.*.end_time' => 'nullable|required_if:schedules.*.is_active,1|after:schedules.*.start_time',
                'schedules.*.break_start' => 'nullable|required_with:schedules.*.break_end|after:schedules.*.start_time|before:schedules.*.end_time',
                'schedules.*.break_end' => 'nullable|required_with:schedules.*.break_start|after:schedules.*.break_start|before_or_equal:schedules.*.end_time',
            ]);

            DB::beginTransaction();

            $specialist->schedules()->delete();

            if ($request->has('schedules')) {
                foreach ($request->schedules as $dayNumber => $schedule) {
                    if (isset($schedule['is_active']) && $schedule['is_active']) {
                        $specialist->schedules()->create([
                            'day_of_week' => $schedule['day_of_week'],
                            'start_time' => $schedule['start_time'],
                            'end_time' => $schedule['end_time'],
                            'break_start' => $schedule['break_start'] ?? null,
                            'break_end' => $schedule['break_end'] ?? null,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.specialists.show', $specialist)
                ->with('success', 'برنامه کاری با موفقیت بروزرسانی شد.');

        } catch (ValidationException $e) {
            // ValidationException extends \Exception, so the broad catch below would otherwise
            // swallow it too — turning a normal per-field validation redirect into a confusing
            // flash message containing the raw, untranslated rule key (e.g. "validation.after")
            // instead of the actual translated error. It must be re-thrown so Laravel's default
            // handling (redirect back with $errors in session) takes over, same as it would for
            // any Form Request-based validation elsewhere in the project.
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'خطا در ذخیره اطلاعات: '.$e->getMessage());
        }
    }
}
