<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-MA-001: Cannot Mass-Assign is_super_admin
     */
    public function test_cannot_mass_assign_is_super_admin(): void
    {
        $user = User::factory()->create();

        // Attempt mass-assignment via update
        $user->update(['is_super_admin' => true]);
        $user->refresh();

        $this->assertFalse($user->is_super_admin);
    }

    /**
     * TC-MA-002: Cannot Mass-Assign security_level
     */
    public function test_cannot_mass_assign_security_level(): void
    {
        $user = User::factory()->create();

        $user->update(['security_level' => 10]);
        $user->refresh();

        // Should remain at default value (1) since security_level is NOT in $fillable
        $this->assertEquals(1, $user->security_level);
    }

    /**
     * TC-MA-003: Cannot Mass-Assign is_trap_target
     */
    public function test_cannot_mass_assign_is_trap_target(): void
    {
        $user = User::factory()->create();

        $user->update(['is_trap_target' => true]);
        $user->refresh();

        $this->assertFalse($user->is_trap_target);
    }

    /**
     * Security setter methods SHOULD work (forceFill bypass)
     */
    public function test_security_setters_work_via_force_fill(): void
    {
        $user = User::factory()->create();

        $user->setSecurityLevel(7);
        $user->refresh();
        $this->assertEquals(7, $user->security_level);

        $user->promoteToSuperAdmin();
        $user->refresh();
        $this->assertTrue($user->is_super_admin);
        $this->assertEquals(10, $user->security_level);

        $user->enableTrapMonitoring();
        $user->refresh();
        $this->assertTrue($user->is_trap_target);
    }

    /**
     * Security level clamping: values outside 1-10 get clamped
     */
    public function test_security_level_clamp(): void
    {
        $user = User::factory()->create();

        $user->setSecurityLevel(15);
        $user->refresh();
        $this->assertEquals(10, $user->security_level);

        $user->setSecurityLevel(-5);
        $user->refresh();
        $this->assertEquals(1, $user->security_level);
    }
}
