<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * گزارش نقض CSP مرحله‌ی Report-Only (بسته‌ی مجوز 5د) — دو قالب مرورگرها: report-uri (application/csp-report، یک گزارش)
 * و Reporting API (application/reports+json، آرایه). فقط چند فیلد کوتاه‌شده در storage/logs/csp-*.log؛ پاسخ همیشه ۲۰۴.
 */
class CspReportController extends Controller
{
    private const FIELDS = ['document-uri', 'blocked-uri', 'violated-directive', 'effective-directive', 'source-file', 'line-number', 'disposition'];

    /** نام‌های Reporting API ← نام‌های report-uri */
    private const CAMEL = [
        'documentURL' => 'document-uri', 'blockedURL' => 'blocked-uri', 'effectiveDirective' => 'effective-directive',
        'sourceFile' => 'source-file', 'lineNumber' => 'line-number', 'disposition' => 'disposition',
    ];

    public function __invoke(Request $request): Response
    {
        $payload = json_decode((string) $request->getContent(), true);

        $reports = [];
        if (is_array($payload) && isset($payload['csp-report']) && is_array($payload['csp-report'])) {
            $reports[] = $payload['csp-report'];
        } elseif (is_array($payload) && array_is_list($payload)) {
            foreach (array_slice($payload, 0, 10) as $item) {
                if (is_array($item) && ($item['type'] ?? null) === 'csp-violation' && is_array($item['body'] ?? null)) {
                    $body = [];
                    foreach (self::CAMEL as $camel => $name) {
                        $body[$name] = $item['body'][$camel] ?? null;
                    }
                    $reports[] = $body;
                }
            }
        }

        foreach ($reports as $report) {
            $entry = [];
            foreach (self::FIELDS as $field) {
                $value = $report[$field] ?? null;
                if ($value !== null && $value !== '') {
                    $entry[$field] = Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 300, '…');
                }
            }
            if ($entry !== []) {
                Log::channel('csp')->info('csp-violation', $entry + ['ip' => $request->ip()]);
            }
        }

        return response()->noContent();
    }
}
