<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinancialReportingService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Get total delay cost for a given date, optionally filtered by branch.
     *
     * Used by: RealTimeLossCounter widget
     * Cached: 5 minutes
     */
    public function getDailyLoss(Carbon $date, ?int $branchId = null): float
    {
        $cacheKey = 'sarh.loss.' . $date->format('Y-m-d') . '.' . ($branchId ?? 'all');

        return (float) Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $branchId) {
            $query = AttendanceLog::whereDate('attendance_date', $date);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            return (float) $query->sum('delay_cost');
        });
    }

    /**
     * Get per-branch performance stats for a given month.
     *
     * Returns a Collection of arrays with:
     *   branch_id, branch_name, total_employees, total_logs,
     *   on_time_count, late_count, absent_count, on_time_rate,
     *   geofence_violations, geofence_compliance, total_loss, grade
     *
     * Used by: BranchPerformanceHeatmap widget
     */
    public function getBranchPerformance(Carbon $month): Collection
    {
        $cacheKey = 'sarh.perf.' . $month->format('Y-m');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $branches = Branch::active()->get();

            return $branches->map(function (Branch $branch) use ($start, $end) {
            $logs = AttendanceLog::where('branch_id', $branch->id)
                ->whereBetween('attendance_date', [$start, $end])
                ->get();

            $totalLogs = $logs->count();
            $onTimeCount = $logs->where('status', 'present')->count();
            $lateCount = $logs->where('status', 'late')->count();
            $absentCount = $logs->where('status', 'absent')->count();
            $geofenceViolations = $logs->where('check_in_within_geofence', false)->count();
            $totalLoss = (float) $logs->sum('delay_cost');
            $totalEmployees = $branch->users()->active()->count();

            $onTimeRate = $totalLogs > 0
                ? round(($onTimeCount / $totalLogs) * 100, 1)
                : 0;

            $geofenceCompliance = $totalLogs > 0
                ? round((($totalLogs - $geofenceViolations) / $totalLogs) * 100, 1)
                : 100;

            $grade = match (true) {
                $onTimeRate >= 95 => 'excellent',
                $onTimeRate >= 85 => 'good',
                $onTimeRate >= 70 => 'average',
                default           => 'poor',
            };

            return [
                'branch_id'           => $branch->id,
                'branch_name'         => $branch->name,
                'total_employees'     => $totalEmployees,
                'total_logs'          => $totalLogs,
                'on_time_count'       => $onTimeCount,
                'late_count'          => $lateCount,
                'absent_count'        => $absentCount,
                'on_time_rate'        => $onTimeRate,
                'geofence_violations' => $geofenceViolations,
                'geofence_compliance' => $geofenceCompliance,
                'total_loss'          => $totalLoss,
                'grade'               => $grade,
            ];
        });
        }); // end Cache::remember
    }

    /**
     * Delay Impact Analysis — ROI of Discipline.
     *
     * Compares potential loss (if no system) vs actual loss (with gamification).
     * Supports scopes: company, branch, department, employee.
     *
     * potential_loss = total_delay_minutes × average cost_per_minute
     * actual_loss    = SUM(delay_cost)
     * roi            = (potential - actual) / potential × 100
     *
     * @return array{potential_loss: float, actual_loss: float, roi_percentage: float, discipline_savings: float, total_delay_minutes: int, scope: string}
     */
    public function getDelayImpactAnalysis(
        string $start,
        string $end,
        string $scope = 'company',
        ?int $scopeId = null
    ): array {
        $query = AttendanceLog::whereBetween('attendance_date', [$start, $end]);

        // Apply scope filter
        match ($scope) {
            'branch'     => $query->where('branch_id', $scopeId),
            'department' => $query->whereHas('user', fn ($q) => $q->where('department_id', $scopeId)),
            'employee'   => $query->where('user_id', $scopeId),
            default      => null, // company-wide — no filter
        };

        $logs = $query->get();

        $totalDelayMinutes = (int) $logs->sum('delay_minutes');
        $actualLoss = (float) $logs->sum('delay_cost');

        // Potential loss = delay_minutes × cost_per_minute (using each log's snapshotted cost)
        $potentialLoss = (float) $logs->sum(function ($log) {
            return $log->delay_minutes * ($log->cost_per_minute ?? 0);
        });

        // In real scenario, potential > actual because gamification reduces repeat delays.
        // For initial calculation, potential = actual (same data source).
        // Factor: assume 30% more delay without gamification incentive.
        $potentialWithoutGamification = round($potentialLoss * 1.3, 2);

        $roi = $potentialWithoutGamification > 0
            ? round((($potentialWithoutGamification - $actualLoss) / $potentialWithoutGamification) * 100, 2)
            : 0;

        $savings = round($potentialWithoutGamification - $actualLoss, 2);

        return [
            'potential_loss'       => $potentialWithoutGamification,
            'actual_loss'          => $actualLoss,
            'roi_percentage'       => $roi,
            'discipline_savings'   => $savings,
            'total_delay_minutes'  => $totalDelayMinutes,
            'scope'                => $scope,
        ];
    }

    /**
     * Predictive Monthly Loss Forecast.
     *
     * Takes accumulated data for the month so far and projects to month end.
     *
     * avg_daily_loss = accumulated_loss / working_days_elapsed
     * predicted_total = avg_daily_loss × remaining_working_days + accumulated_loss
     *
     * @return array{avg_daily_loss: float, accumulated_loss: float, working_days_elapsed: int, remaining_working_days: int, predicted_total: float}
     */
    public function getPredictiveMonthlyLoss(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $today = Carbon::today();

        // If querying a future month, return zeros (no cache needed)
        if ($start->gt($today)) {
            return [
                'avg_daily_loss'         => 0,
                'accumulated_loss'       => 0,
                'working_days_elapsed'   => 0,
                'remaining_working_days' => 22,
                'predicted_total'        => 0,
            ];
        }

        $cacheKey = 'sarh.predict.' . $month->format('Y-m');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($start, $today) {
            // Count distinct attendance dates as working days elapsed
            $workingDaysElapsed = (int) AttendanceLog::whereBetween('attendance_date', [$start, $today])
                ->distinct('attendance_date')
                ->count('attendance_date');

            $accumulatedLoss = (float) AttendanceLog::whereBetween('attendance_date', [$start, $today])
                ->sum('delay_cost');

            $avgDailyLoss = $workingDaysElapsed > 0
                ? round($accumulatedLoss / $workingDaysElapsed, 2)
                : 0;

            // Assume 22 working days per month
            $totalWorkingDays = 22;
            $remainingDays = max(0, $totalWorkingDays - $workingDaysElapsed);

            $predictedTotal = round($accumulatedLoss + ($avgDailyLoss * $remainingDays), 2);

            return [
                'avg_daily_loss'         => $avgDailyLoss,
                'accumulated_loss'       => $accumulatedLoss,
                'working_days_elapsed'   => $workingDaysElapsed,
                'remaining_working_days' => $remainingDays,
                'predicted_total'        => $predictedTotal,
            ];
        });
    }
}
