<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AttendanceOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $todayLogs = AttendanceLog::whereDate('attendance_date', $today);

        $totalEmployees  = (clone $todayLogs)->count();
        $presentCount    = (clone $todayLogs)->where('status', 'present')->count();
        $lateCount       = (clone $todayLogs)->where('status', 'late')->count();
        $absentCount     = (clone $todayLogs)->where('status', 'absent')->count();
        $totalDelayCost  = (clone $todayLogs)->sum('delay_cost');
        $totalOvertimeVal = (clone $todayLogs)->sum('overtime_value');

        return [
            Stat::make(__('attendance.today_present'), $presentCount)
                ->description(__('attendance.out_of_total', ['total' => $totalEmployees]))
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make(__('attendance.today_late'), $lateCount)
                ->description($lateCount > 0
                    ? __('attendance.late_warning')
                    : __('attendance.no_late'))
                ->color($lateCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),

            Stat::make(__('attendance.today_absent'), $absentCount)
                ->color($absentCount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),

            Stat::make(__('attendance.today_delay_losses'), number_format($totalDelayCost, 2) . ' ' . __('attendance.sar'))
                ->description(__('attendance.financial_impact_today'))
                ->color($totalDelayCost > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-banknotes'),

            Stat::make(__('attendance.today_overtime_value'), number_format($totalOvertimeVal, 2) . ' ' . __('attendance.sar'))
                ->description(__('attendance.overtime_at_1_5x'))
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),
        ];
    }
}
