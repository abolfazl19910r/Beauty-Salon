<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\SuperAdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** ⭐ «گزارش کارهای ابرمدیر» (۲۰۲۶-۱۰-۱۵): چه کسی، کی، روی کدام سالن، چه چیزی را دستی عوض کرد. */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = array_key_exists((string) $request->query('action'), SuperAdminAuditLog::ACTIONS) ? (string) $request->query('action') : null;
        $salonId = $request->integer('salon') ?: null;

        $logs = SuperAdminAuditLog::query()
            ->with('salon')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($salonId, fn ($q) => $q->where('salon_id', $salonId))
            ->latest('created_at')->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('superadmin.audit-log.index', [
            'logs' => $logs,
            'actions' => SuperAdminAuditLog::ACTIONS,
            'salons' => Salon::withoutGlobalScopes()->orderBy('name')->pluck('name', 'id'),
            'filters' => ['action' => $action, 'salon' => $salonId],
        ]);
    }
}
