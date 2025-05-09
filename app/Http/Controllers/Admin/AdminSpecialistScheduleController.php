<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSpecialistScheduleController extends Controller
{
    public function index($specialistId)
    {
        try {
            $specialist = Specialist::findOrFail($specialistId);
            return $this->edit($specialistId);
        } catch (\Exception $e) {
            return redirect()->route('admin.specialists.index')->with('error', 'متخصص مورد نظر یافت نشد.');
        }
    }

    {
        }

    }
}
