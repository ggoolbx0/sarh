<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceLog;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RealTimeLossCounter extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $service = app(FinancialReportingService::class);
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayLoss = $service->getDailyLoss($today);
        $yesterdayLoss = $service->getDailyLoss($yesterday);

        $todayLogs = AttendanceLog::whereDate('attendance_date', $today);
        $lateCount = (clone $todayLogs)->where('status', 'late')->count();
        $absentCount = (clone $todayLogs)->where('status', 'absent')->count();

        // Calculate loss trend
        $lossDiff = $todayLoss - $yesterdayLoss;
        $trendDescription = $lossDiff >= 0
            ? '+' . number_format(abs($lossDiff), 2) . ' ' . __('command.sar') . ' ' . __('command.vs_yesterday')
            : '-' . number_format(abs($lossDiff), 2) . ' ' . __('command.sar') . ' ' . __('command.vs_yesterday');

        // Predictive insight
        $predictive = $service->getPredictiveMonthlyLoss(Carbon::now());

        return [
            Stat::make(
                __('command.today_total_loss'),
                number_format($todayLoss, 2) . ' ' . __('command.sar')
            )
                ->description($trendDescription)
                ->descriptionIcon($lossDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayLoss > 0 ? 'danger' : 'success')
                ->chart([$yesterdayLoss, $todayLoss]),

            Stat::make(
                __('command.today_late_count'),
                $lateCount . ' ' . __('command.employees')
            )
                ->color($lateCount > 5 ? 'danger' : ($lateCount > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-clock'),

            Stat::make(
                __('command.today_absent_count'),
                $absentCount . ' ' . __('command.employees')
            )
                ->color($absentCount > 3 ? 'danger' : ($absentCount > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-x-circle'),

            Stat::make(
                __('command.predictive_title'),
                number_format($predictive['predicted_total'], 2) . ' ' . __('command.sar')
            )
                ->description(
                    __('command.remaining_days') . ': ' . $predictive['remaining_working_days']
                )
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
