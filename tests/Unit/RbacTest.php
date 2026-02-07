<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-RBAC-001: Super Admin Bypasses All Permission Checks
     */
    public function test_super_admin_bypasses_all(): void
    {
        $user = new User();
        $user->forceFill(['is_super_admin' => true]);

        $this->assertTrue($user->hasPermission('nonexistent.permission'));
        $this->assertTrue($user->hasPermission('finance.view_all'));
    }

    /**
     * TC-RBAC-002: Employee Has Only Assigned Permissions
     */
    public function test_employee_has_basic_permissions(): void
    {
        $role = Role::create([
            'name_ar' => 'موظف',
            'name_en' => 'Employee',
            'slug'    => 'employee_test',
            'level'   => 2,
        ]);

        $perm = Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $role->permissions()->attach($perm);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('attendance.view_own'));
        $this->assertFalse($user->hasPermission('finance.view_all'));
    }

    /**
     * TC-RBAC-003: Security Level Check
     */
    public function test_security_level_check(): void
    {
        $user = new User();
        $user->forceFill(['security_level' => 5]);

        $this->assertTrue($user->hasSecurityLevel(5));
        $this->assertTrue($user->hasSecurityLevel(3));
        $this->assertFalse($user->hasSecurityLevel(6));
    }

    /**
     * TC-RBAC-004: canManage — Higher Level Manages Lower
     */
    public function test_can_manage_lower_level(): void
    {
        $manager = new User();
        $manager->forceFill(['security_level' => 7, 'is_super_admin' => false]);

        $employee = new User();
        $employee->forceFill(['security_level' => 4]);

        $this->assertTrue($manager->canManage($employee));
    }

    /**
     * TC-RBAC-005: canManage — Same Level Cannot Manage Peer
     */
    public function test_cannot_manage_same_level(): void
    {
        $userA = new User();
        $userA->forceFill(['security_level' => 5, 'is_super_admin' => false]);

        $userB = new User();
        $userB->forceFill(['security_level' => 5]);

        $this->assertFalse($userA->canManage($userB));
    }

    /**
     * TC-RBAC-006: canManage — Lower Cannot Manage Higher
     */
    public function test_lower_cannot_manage_higher(): void
    {
        $junior = new User();
        $junior->forceFill(['security_level' => 3, 'is_super_admin' => false]);

        $senior = new User();
        $senior->forceFill(['security_level' => 7]);

        $this->assertFalse($junior->canManage($senior));
    }

    /**
     * TC-RBAC-007: Super Admin canManage Everyone
     */
    public function test_super_admin_can_manage_anyone(): void
    {
        $admin = new User();
        $admin->forceFill(['is_super_admin' => true, 'security_level' => 10]);

        $target = new User();
        $target->forceFill(['security_level' => 10]);

        $this->assertTrue($admin->canManage($target));
    }
}
