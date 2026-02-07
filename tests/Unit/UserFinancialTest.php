<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFinancialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-FIN-001: Cost Per Minute — Standard Calculation
     * Formula: 8000 / (22 × 8 × 60) = 0.7576
     */
    public function test_cost_per_minute_standard_calculation(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $this->assertEquals(0.7576, $user->cost_per_minute);
    }

    /**
     * TC-FIN-002: Cost Per Minute — Zero Salary
     */
    public function test_cost_per_minute_zero_salary(): void
    {
        $user = new User([
            'basic_salary'           => 0,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $this->assertEquals(0.0, $user->cost_per_minute);
    }

    /**
     * TC-FIN-003: Cost Per Minute — Zero Working Days (Division Guard)
     */
    public function test_cost_per_minute_zero_working_days(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 0,
            'working_hours_per_day'  => 8,
        ]);

        $this->assertEquals(0.0, $user->cost_per_minute);
    }

    /**
     * TC-FIN-004: Cost Per Minute — Zero Working Hours (Division Guard)
     */
    public function test_cost_per_minute_zero_working_hours(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 0,
        ]);

        $this->assertEquals(0.0, $user->cost_per_minute);
    }

    /**
     * TC-FIN-005: Total Salary Accessor
     */
    public function test_total_salary_accessor(): void
    {
        $user = new User([
            'basic_salary'        => 8000,
            'housing_allowance'   => 2500,
            'transport_allowance' => 500,
            'other_allowances'    => 300,
        ]);

        $this->assertEquals(11300.0, $user->total_salary);
    }

    /**
     * TC-FIN-006: Monthly Working Minutes
     */
    public function test_monthly_working_minutes(): void
    {
        $user = new User([
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $this->assertEquals(10560, $user->monthly_working_minutes);
    }

    /**
     * TC-FIN-007: Delay Cost Calculation
     */
    public function test_calculate_delay_cost(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $this->assertEquals(11.36, $user->calculateDelayCost(15));
    }

    /**
     * TC-FIN-008: Daily Rate
     */
    public function test_daily_rate(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
        ]);

        $this->assertEquals(363.64, $user->daily_rate);
    }

    /**
     * TC-FIN-EXTRA: Total Cost Per Minute (full compensation)
     */
    public function test_total_cost_per_minute(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'housing_allowance'      => 2500,
            'transport_allowance'    => 500,
            'other_allowances'       => 0,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        // 11000 / 10560 = 1.0417
        $this->assertEquals(1.0417, $user->total_cost_per_minute);
    }
}
