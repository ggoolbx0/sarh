<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportingTest extends TestCase
{
    use RefreshDatabase;

    private FinancialReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->service = new FinancialReportingService();
    }

    /**
     * Helper: create a branch + user + attendance logs.
     */
    private function seedBranchWithLogs(
        string $branchCode,
        float $delayCost,
        int $logCount = 1,
        ?Carbon $date = null,
        string $status = 'late',
        bool $withinGeofence = true,
    ): array {
        $branch = Branch::create([
            'name_ar'              => 'فرع ' . $branchCode,
            'name_en'              => 'Branch ' . $branchCode,
            'code'                 => $branchCode,
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 20,
            'default_shift_start'  => '08:00',
            'default_shift_end'    => '16:00',
            'grace_period_minutes' => 5,
            'is_active'            => true,
        ]);

        $user = User::factory()->create([
            'branch_id'   => $branch->id,
            'basic_salary' => 8000,
        ]);

        $date ??= Carbon::today();

        $logs = [];
        for ($i = 0; $i < $logCount; $i++) {
            $logs[] = AttendanceLog::create([
                'user_id'                 => $user->id,
                'branch_id'               => $branch->id,
                'attendance_date'         => $date,
                'check_in_at'             => $date->copy()->setTime(8, 15),
                'check_in_within_geofence'=> $withinGeofence,
                'status'                  => $status,
                'delay_minutes'           => $status === 'late' ? 15 : 0,
                'cost_per_minute'         => $user->cost_per_minute,
                'delay_cost'              => $delayCost,
            ]);
        }

        return ['branch' => $branch, 'user' => $user, 'logs' => $logs];
    }

    // ──────────────────────────────────────────────
    // TC-FIN-001: Daily Loss Calculation Accuracy
    // ──────────────────────────────────────────────

    public function test_daily_loss_returns_exact_sum_of_delay_costs(): void
    {
        $today = Carbon::today();

        // 3 employees with known delay_cost values
        $this->seedBranchWithLogs('B1', 50.00, date: $today);
        $this->seedBranchWithLogs('B2', 75.50, date: $today);
        $this->seedBranchWithLogs('B3', 24.50, date: $today);

        $result = $this->service->getDailyLoss($today);

        $this->assertEquals(150.00, $result);
    }

    public function test_daily_loss_filters_by_branch(): void
    {
        $today = Carbon::today();

        $seedA = $this->seedBranchWithLogs('AA', 100.00, date: $today);
        $this->seedBranchWithLogs('BB', 200.00, date: $today);

        $result = $this->service->getDailyLoss($today, $seedA['branch']->id);

        $this->assertEquals(100.00, $result);
    }

    public function test_daily_loss_excludes_other_dates(): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $this->seedBranchWithLogs('X1', 100.00, date: $today);
        $this->seedBranchWithLogs('X2', 999.00, date: $yesterday);

        $result = $this->service->getDailyLoss($today);

        $this->assertEquals(100.00, $result);
    }

    // ──────────────────────────────────────────────
    // TC-FIN-002: Branch Performance Aggregation
    // ──────────────────────────────────────────────

    public function test_branch_performance_returns_correct_metrics(): void
    {
        $month = Carbon::now()->startOfMonth();

        $branch = Branch::create([
            'name_ar'              => 'فرع الأداء',
            'name_en'              => 'Performance Branch',
            'code'                 => 'PB',
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 20,
            'default_shift_start'  => '08:00',
            'default_shift_end'    => '16:00',
            'grace_period_minutes' => 5,
            'is_active'            => true,
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);

        // 10 logs: 8 present, 1 late, 1 absent
        for ($i = 1; $i <= 8; $i++) {
            AttendanceLog::create([
                'user_id'                  => $user->id,
                'branch_id'                => $branch->id,
                'attendance_date'          => $month->copy()->addDays($i),
                'check_in_at'              => $month->copy()->addDays($i)->setTime(7, 55),
                'check_in_within_geofence' => true,
                'status'                   => 'present',
                'delay_minutes'            => 0,
                'delay_cost'               => 0,
            ]);
        }

        AttendanceLog::create([
            'user_id'                  => $user->id,
            'branch_id'                => $branch->id,
            'attendance_date'          => $month->copy()->addDays(9),
            'check_in_at'              => $month->copy()->addDays(9)->setTime(8, 20),
            'check_in_within_geofence' => true,
            'status'                   => 'late',
            'delay_minutes'            => 15,
            'delay_cost'               => 11.36,
        ]);

        AttendanceLog::create([
            'user_id'                  => $user->id,
            'branch_id'                => $branch->id,
            'attendance_date'          => $month->copy()->addDays(10),
            'check_in_within_geofence' => false,
            'status'                   => 'absent',
            'delay_minutes'            => 0,
            'delay_cost'               => 0,
        ]);

        $result = $this->service->getBranchPerformance($month);

        $this->assertCount(1, $result);

        $branchData = $result->first();

        $this->assertEquals(8, $branchData['on_time_count']);
        $this->assertEquals(1, $branchData['late_count']);
        $this->assertEquals(1, $branchData['absent_count']);
        $this->assertEquals(80.0, $branchData['on_time_rate']); // 8/10 = 80%
        $this->assertEquals('average', $branchData['grade']);    // 70 <= 80 < 85
        $this->assertEquals(90.0, $branchData['geofence_compliance']); // 9/10 = 90%
        $this->assertEquals(11.36, $branchData['total_loss']);
    }

    // ──────────────────────────────────────────────
    // TC-FIN-003: Delay Impact ROI Calculation
    // ──────────────────────────────────────────────

    public function test_delay_impact_analysis_calculates_roi_correctly(): void
    {
        // Use a date in the middle of the range to avoid boundary issues
        $logDate = Carbon::today()->subDays(2);
        $start = Carbon::today()->subDays(5)->toDateString();
        $end = Carbon::today()->toDateString();

        $branch = Branch::create([
            'name_ar'              => 'فرع ROI',
            'name_en'              => 'ROI Branch',
            'code'                 => 'ROI',
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 20,
            'default_shift_start'  => '08:00',
            'default_shift_end'    => '16:00',
            'grace_period_minutes' => 5,
            'is_active'            => true,
        ]);

        $user = User::factory()->create([
            'branch_id'              => $branch->id,
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $costPerMinute = $user->cost_per_minute;
        $delayCost = round(20 * $costPerMinute, 2);

        // Create attendance log with known values on a date inside the range
        AttendanceLog::create([
            'user_id'                  => $user->id,
            'branch_id'               => $branch->id,
            'attendance_date'          => $logDate->format('Y-m-d'),
            'check_in_at'              => $logDate->copy()->setTime(8, 20),
            'check_in_within_geofence' => true,
            'status'                   => 'late',
            'delay_minutes'            => 20,
            'cost_per_minute'          => $costPerMinute,
            'delay_cost'               => $delayCost,
        ]);

        $result = $this->service->getDelayImpactAnalysis($start, $end);

        $expectedPotentialLoss = round($delayCost * 1.3, 2);
        $expectedSavings = round($expectedPotentialLoss - $delayCost, 2);
        $expectedRoi = round(($expectedSavings / $expectedPotentialLoss) * 100, 2);

        $this->assertEquals($delayCost, $result['actual_loss']);
        $this->assertEquals($expectedPotentialLoss, $result['potential_loss']);
        $this->assertEquals($expectedRoi, $result['roi_percentage']);
        $this->assertEquals($expectedSavings, $result['discipline_savings']);
        $this->assertEquals(20, $result['total_delay_minutes']);
        $this->assertEquals('company', $result['scope']);
    }

    // ──────────────────────────────────────────────
    // TC-FIN-004: Predictive Monthly Loss Forecast
    // ──────────────────────────────────────────────

    public function test_predictive_monthly_loss_extrapolates_correctly(): void
    {
        $month = Carbon::now();

        // Create logs for 5 distinct working days this month
        $branch = Branch::create([
            'name_ar'              => 'فرع تنبؤي',
            'name_en'              => 'Predictive Branch',
            'code'                 => 'PRD',
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 20,
            'default_shift_start'  => '08:00',
            'default_shift_end'    => '16:00',
            'grace_period_minutes' => 5,
            'is_active'            => true,
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);

        $start = $month->copy()->startOfMonth();

        // 5 days × 100 SAR each = 500 accumulated
        for ($i = 1; $i <= 5; $i++) {
            AttendanceLog::create([
                'user_id'                  => $user->id,
                'branch_id'                => $branch->id,
                'attendance_date'          => $start->copy()->addDays($i),
                'check_in_at'              => $start->copy()->addDays($i)->setTime(8, 15),
                'check_in_within_geofence' => true,
                'status'                   => 'late',
                'delay_minutes'            => 10,
                'delay_cost'               => 100.00,
            ]);
        }

        $result = $this->service->getPredictiveMonthlyLoss($month);

        $this->assertEquals(5, $result['working_days_elapsed']);
        $this->assertEquals(500.00, $result['accumulated_loss']);
        $this->assertEquals(100.00, $result['avg_daily_loss']); // 500/5

        $expectedRemaining = max(0, 22 - 5);
        $this->assertEquals($expectedRemaining, $result['remaining_working_days']);

        $expectedTotal = round(500 + (100 * $expectedRemaining), 2);
        $this->assertEquals($expectedTotal, $result['predicted_total']);
    }

    public function test_predictive_returns_zeros_for_future_month(): void
    {
        $futureMonth = Carbon::now()->addMonths(3);

        $result = $this->service->getPredictiveMonthlyLoss($futureMonth);

        $this->assertEquals(0, $result['avg_daily_loss']);
        $this->assertEquals(0, $result['accumulated_loss']);
        $this->assertEquals(0, $result['working_days_elapsed']);
        $this->assertEquals(22, $result['remaining_working_days']);
        $this->assertEquals(0, $result['predicted_total']);
    }
}
