<?php

namespace Tests\Unit;

use App\Models\AttendanceLog;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceEvaluationTest extends TestCase
{
    /**
     * TC-ATT-001: On-Time Check-in (within grace period)
     */
    public function test_on_time_checkin(): void
    {
        $log = new AttendanceLog([
            'attendance_date' => Carbon::parse('2026-02-07'),
            'check_in_at'     => Carbon::parse('2026-02-07 08:03:00'),
        ]);

        $log->evaluateAttendance('08:00', gracePeriod: 5);

        $this->assertEquals('present', $log->status);
        $this->assertEquals(0, $log->delay_minutes);
    }

    /**
     * TC-ATT-002: Late Check-in (beyond grace)
     */
    public function test_late_checkin(): void
    {
        $log = new AttendanceLog([
            'attendance_date' => Carbon::parse('2026-02-07'),
            'check_in_at'     => Carbon::parse('2026-02-07 08:20:00'),
        ]);

        $log->evaluateAttendance('08:00', gracePeriod: 5);

        $this->assertEquals('late', $log->status);
        $this->assertEquals(20, $log->delay_minutes);
    }

    /**
     * TC-ATT-003: Exact Grace Period Boundary (inclusive)
     */
    public function test_exact_grace_boundary(): void
    {
        $log = new AttendanceLog([
            'attendance_date' => Carbon::parse('2026-02-07'),
            'check_in_at'     => Carbon::parse('2026-02-07 08:05:00'),
        ]);

        $log->evaluateAttendance('08:00', gracePeriod: 5);

        $this->assertEquals('present', $log->status);
        $this->assertEquals(0, $log->delay_minutes);
    }

    /**
     * TC-ATT-004: Missing Check-in (Absent)
     */
    public function test_absent_when_no_checkin(): void
    {
        $log = new AttendanceLog([
            'attendance_date' => Carbon::parse('2026-02-07'),
            'check_in_at'     => null,
        ]);

        $log->evaluateAttendance('08:00');

        $this->assertEquals('absent', $log->status);
    }

    /**
     * TC-ATT-005: Financial snapshot captures cost_per_minute correctly
     */
    public function test_financial_snapshot(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $log = new AttendanceLog([
            'delay_minutes'       => 15,
            'early_leave_minutes' => 0,
            'overtime_minutes'    => 0,
        ]);

        // Simulate relationship
        $log->setRelation('user', $user);
        $log->calculateFinancials();

        $this->assertEquals(0.7576, $log->cost_per_minute);
        $this->assertEquals(11.36, $log->delay_cost);
    }

    /**
     * TC-ATT-006: Overtime at 1.5x Rate
     */
    public function test_overtime_at_1_5x_rate(): void
    {
        $user = new User([
            'basic_salary'           => 8000,
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,
        ]);

        $log = new AttendanceLog([
            'delay_minutes'       => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes'    => 60,
        ]);

        $log->setRelation('user', $user);
        $log->calculateFinancials();

        // 60 × 0.7576 × 1.5 = 68.18
        $this->assertEquals(68.18, $log->overtime_value);
    }
}
