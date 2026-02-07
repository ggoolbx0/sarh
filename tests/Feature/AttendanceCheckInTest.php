<?php

namespace Tests\Feature;

use App\Exceptions\OutOfGeofenceException;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GeofencingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService(new GeofencingService());
    }

    private function createBranchWithUser(array $branchOverrides = [], array $userOverrides = []): User
    {
        $branch = Branch::create(array_merge([
            'name_ar'              => 'الفرع الرئيسي',
            'name_en'              => 'Main Branch',
            'code'                 => 'HQ',
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 17,
            'default_shift_start'  => '08:00',
            'default_shift_end'    => '16:00',
            'grace_period_minutes' => 5,
            'is_active'            => true,
        ], $branchOverrides));

        $user = User::create(array_merge([
            'name_ar'               => 'أحمد محمد',
            'name_en'               => 'Ahmed Mohammed',
            'email'                 => 'ahmed@sarh.test',
            'password'              => bcrypt('password'),
            'phone'                 => '0500000001',
            'national_id'           => '1234567890',
            'branch_id'             => $branch->id,
            'basic_salary'          => 8000.00,
            'working_days_per_month'=> 22,
            'working_hours_per_day' => 8,
            'status'                => 'active',
            'hire_date'             => '2025-01-01',
        ], $userOverrides));

        return $user;
    }

    /**
     * TC-SVC-001: Successful check-in within geofence.
     */
    public function test_successful_check_in_within_geofence(): void
    {
        $user = $this->createBranchWithUser();

        $log = $this->service->checkIn(
            user:   $user,
            lat:    24.71362, // within ~5m
            lng:    46.67533,
            ip:     '192.168.1.1',
            device: 'Chrome PWA',
        );

        $this->assertInstanceOf(AttendanceLog::class, $log);
        $this->assertTrue($log->exists);
        $this->assertTrue($log->check_in_within_geofence);
        $this->assertGreaterThan(0, $log->cost_per_minute);
        $this->assertEquals($user->cost_per_minute, $log->cost_per_minute);
    }

    /**
     * TC-SVC-002: Rejected check-in outside geofence.
     */
    public function test_rejected_check_in_outside_geofence(): void
    {
        $user = $this->createBranchWithUser();

        $this->expectException(OutOfGeofenceException::class);

        $this->service->checkIn(
            user: $user,
            lat:  24.7200, // far away (~700m)
            lng:  46.6800,
        );

        // No log should exist
        $this->assertDatabaseCount('attendance_logs', 0);
    }

    /**
     * TC-SVC-003: Financial snapshot immutability.
     */
    public function test_financial_snapshot_immutability(): void
    {
        $user = $this->createBranchWithUser();
        $originalCostPerMinute = $user->cost_per_minute;

        // Check in
        $log = $this->service->checkIn(
            user: $user,
            lat:  24.71362,
            lng:  46.67533,
        );

        // Snapshot should match original
        $this->assertEquals($originalCostPerMinute, $log->cost_per_minute);

        // Change salary
        $user->update(['basic_salary' => 12000.00]);
        $user->refresh();

        // Original log should still have the old cost_per_minute
        $log->refresh();
        $this->assertEquals($originalCostPerMinute, $log->cost_per_minute);
        $this->assertNotEquals($user->cost_per_minute, $log->cost_per_minute);
    }

    /**
     * TC-SVC-004: Duplicate check-in same day is rejected.
     */
    public function test_duplicate_check_in_same_day_rejected(): void
    {
        $user = $this->createBranchWithUser();

        // First check-in succeeds
        $this->service->checkIn($user, 24.71362, 46.67533);

        // Second check-in should fail (unique constraint)
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->service->checkIn($user, 24.71362, 46.67533);
    }

    /**
     * TC-SVC-005: Late check-in calculates delay correctly.
     */
    public function test_late_check_in_calculates_delay(): void
    {
        $user = $this->createBranchWithUser();

        // Travel to 08:20 (20 minutes late, shift starts at 08:00, grace = 5)
        $this->travelTo(now()->setTime(8, 20, 0));

        $log = $this->service->checkIn($user, 24.71362, 46.67533);

        $this->assertEquals('late', $log->status);
        $this->assertEquals(20, $log->delay_minutes);
        $this->assertGreaterThan(0, $log->delay_cost);

        // delay_cost = 20 * cost_per_minute
        $expectedCost = round(20 * $user->cost_per_minute, 2);
        $this->assertEquals($expectedCost, $log->delay_cost);
    }

    /**
     * TC-SVC-006: On-time check-in within grace period.
     */
    public function test_on_time_check_in_within_grace(): void
    {
        $user = $this->createBranchWithUser();

        // Travel to 08:04 (within 5-minute grace period)
        $this->travelTo(now()->setTime(8, 4, 0));

        $log = $this->service->checkIn($user, 24.71362, 46.67533);

        $this->assertEquals('present', $log->status);
        $this->assertEquals(0, $log->delay_minutes);
        $this->assertEquals(0, (float) $log->delay_cost);
    }

    /**
     * TC-SVC-008: No shift assigned falls back to branch defaults.
     */
    public function test_no_shift_falls_back_to_branch_defaults(): void
    {
        $user = $this->createBranchWithUser([
            'default_shift_start'  => '09:00',
            'grace_period_minutes' => 15,
        ]);

        // No shift assigned to user
        // Travel to 09:10 (within branch's 15-min grace)
        $this->travelTo(now()->setTime(9, 10, 0));

        $log = $this->service->checkIn($user, 24.71362, 46.67533);

        $this->assertEquals('present', $log->status);
        $this->assertEquals(0, $log->delay_minutes);
    }
}
