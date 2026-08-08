<?php

namespace Database\Seeders;

use App\Models\ScheduledReport;
use App\Models\User;
use App\Models\UserReportSetting;
use Illuminate\Database\Seeder;

class ScheduledReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', true)->first();

        if (! $admin) {
            return;
        }

        UserReportSetting::create([
            'user_id' => $admin->id,
            'settings' => [
                'dashboard_layout' => 'grid',
                'default_report_type' => 'daily',
                'default_date_range' => '30',
                'chart_colors' => [
                    'primary' => '#4299e1',
                    'secondary' => '#48bb78',
                    'accent' => '#ed8936',
                ],
                'notifications' => [
                    'email' => true,
                    'browser' => true,
                ],
                'export_settings' => [
                    'include_charts' => true,
                    'preferred_format' => 'excel',
                ],
            ],
        ]);

        $scheduledReports = [
            [
                'report_type' => 'daily',
                'parameters' => [
                    'type' => 'revenue',
                    'start_date' => now()->subDays(30)->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ],
                'frequency' => 'daily',
                'next_run' => now()->addDay()->startOfDay(),
                'recipients' => [$admin->email],
            ],
            [
                'report_type' => 'weekly',
                'parameters' => [
                    'type' => 'specialist_performance',
                    'start_date' => now()->startOfWeek()->format('Y-m-d'),
                    'end_date' => now()->endOfWeek()->format('Y-m-d'),
                ],
                'frequency' => 'weekly',
                'next_run' => now()->addWeek()->startOfWeek(),
                'recipients' => [$admin->email],
            ],
            [
                'report_type' => 'monthly',
                'parameters' => [
                    'type' => 'financial',
                    'start_date' => now()->startOfMonth()->format('Y-m-d'),
                    'end_date' => now()->endOfMonth()->format('Y-m-d'),
                ],
                'frequency' => 'monthly',
                'next_run' => now()->addMonth()->startOfMonth(),
                'recipients' => [$admin->email],
            ],
        ];

        foreach ($scheduledReports as $report) {
            ScheduledReport::create([
                'user_id' => $admin->id,
                'report_type' => $report['report_type'],
                'parameters' => $report['parameters'],
                'frequency' => $report['frequency'],
                'next_run' => $report['next_run'],
                'recipients' => $report['recipients'],
                'is_active' => true,
            ]);
        }
    }
}
