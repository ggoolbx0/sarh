<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |----------------------------------------------------------------------
        | 10-Level RBAC Roles
        |----------------------------------------------------------------------
        */
        $roles = [
            ['slug' => 'intern',              'level' => 1,  'name_ar' => 'متدرب',                'name_en' => 'Intern',              'description_ar' => 'متدرب أو موظف تحت التجربة',         'description_en' => 'Trainee or probation employee'],
            ['slug' => 'employee',             'level' => 2,  'name_ar' => 'موظف',                 'name_en' => 'Employee',            'description_ar' => 'موظف عادي',                          'description_en' => 'Regular employee'],
            ['slug' => 'senior_employee',      'level' => 3,  'name_ar' => 'موظف أول',             'name_en' => 'Senior Employee',     'description_ar' => 'موظف أول ذو خبرة',                   'description_en' => 'Experienced senior employee'],
            ['slug' => 'team_leader',          'level' => 4,  'name_ar' => 'قائد فريق',            'name_en' => 'Team Leader',         'description_ar' => 'قائد فريق عمل',                      'description_en' => 'Team captain / lead'],
            ['slug' => 'supervisor',           'level' => 5,  'name_ar' => 'مشرف',                 'name_en' => 'Supervisor',          'description_ar' => 'مشرف قسم',                           'description_en' => 'Section supervisor'],
            ['slug' => 'department_manager',   'level' => 6,  'name_ar' => 'مدير قسم',             'name_en' => 'Department Manager',  'description_ar' => 'مدير إدارة أو قسم',                  'description_en' => 'Department / division manager'],
            ['slug' => 'branch_manager',       'level' => 7,  'name_ar' => 'مدير فرع',             'name_en' => 'Branch Manager',      'description_ar' => 'مدير فرع بكامل الصلاحيات',           'description_en' => 'Full branch manager'],
            ['slug' => 'regional_director',    'level' => 8,  'name_ar' => 'مدير إقليمي',          'name_en' => 'Regional Director',   'description_ar' => 'مدير منطقة أو إقليم',                'description_en' => 'Regional / area director'],
            ['slug' => 'executive',            'level' => 9,  'name_ar' => 'مدير تنفيذي',          'name_en' => 'Executive',           'description_ar' => 'مدير تنفيذي أعلى',                   'description_en' => 'C-level executive'],
            ['slug' => 'super_admin',          'level' => 10, 'name_ar' => 'مدير النظام',          'name_en' => 'Super Admin',         'description_ar' => 'مدير النظام بكامل الصلاحيات المطلقة', 'description_en' => 'Full system administrator', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }

        /*
        |----------------------------------------------------------------------
        | Permissions (grouped by system module)
        |----------------------------------------------------------------------
        */
        $permissions = [
            // --- Attendance ---
            ['slug' => 'attendance.view_own',      'group' => 'attendance', 'name_ar' => 'عرض الحضور الشخصي',   'name_en' => 'View Own Attendance'],
            ['slug' => 'attendance.view_team',      'group' => 'attendance', 'name_ar' => 'عرض حضور الفريق',     'name_en' => 'View Team Attendance'],
            ['slug' => 'attendance.view_branch',    'group' => 'attendance', 'name_ar' => 'عرض حضور الفرع',      'name_en' => 'View Branch Attendance'],
            ['slug' => 'attendance.view_all',       'group' => 'attendance', 'name_ar' => 'عرض كل الحضور',       'name_en' => 'View All Attendance'],
            ['slug' => 'attendance.check_in',       'group' => 'attendance', 'name_ar' => 'تسجيل الحضور',        'name_en' => 'Check In'],
            ['slug' => 'attendance.manual_entry',   'group' => 'attendance', 'name_ar' => 'إدخال يدوي',          'name_en' => 'Manual Entry'],
            ['slug' => 'attendance.approve',        'group' => 'attendance', 'name_ar' => 'اعتماد الحضور',       'name_en' => 'Approve Attendance'],
            ['slug' => 'attendance.export',         'group' => 'attendance', 'name_ar' => 'تصدير بيانات الحضور',  'name_en' => 'Export Attendance Data'],

            // --- Finance ---
            ['slug' => 'finance.view_own',          'group' => 'finance',    'name_ar' => 'عرض التقرير المالي الشخصي', 'name_en' => 'View Own Financial Report'],
            ['slug' => 'finance.view_team',         'group' => 'finance',    'name_ar' => 'عرض مالية الفريق',         'name_en' => 'View Team Finance'],
            ['slug' => 'finance.view_branch',       'group' => 'finance',    'name_ar' => 'عرض مالية الفرع',          'name_en' => 'View Branch Finance'],
            ['slug' => 'finance.view_all',          'group' => 'finance',    'name_ar' => 'عرض كل المالية',           'name_en' => 'View All Finance'],
            ['slug' => 'finance.generate_reports',  'group' => 'finance',    'name_ar' => 'إنشاء تقارير مالية',       'name_en' => 'Generate Financial Reports'],
            ['slug' => 'finance.manage_salaries',   'group' => 'finance',    'name_ar' => 'إدارة الرواتب',            'name_en' => 'Manage Salaries'],
            ['slug' => 'finance.dashboard',         'group' => 'finance',    'name_ar' => 'لوحة القيادة المالية',     'name_en' => 'Financial Dashboard'],

            // --- Users ---
            ['slug' => 'users.view',               'group' => 'users',      'name_ar' => 'عرض الموظفين',       'name_en' => 'View Users'],
            ['slug' => 'users.create',             'group' => 'users',      'name_ar' => 'إنشاء موظف',         'name_en' => 'Create User'],
            ['slug' => 'users.edit',               'group' => 'users',      'name_ar' => 'تعديل موظف',         'name_en' => 'Edit User'],
            ['slug' => 'users.delete',             'group' => 'users',      'name_ar' => 'حذف موظف',           'name_en' => 'Delete User'],
            ['slug' => 'users.manage_roles',       'group' => 'users',      'name_ar' => 'إدارة الأدوار',       'name_en' => 'Manage Roles'],

            // --- Branches ---
            ['slug' => 'branches.view',            'group' => 'branches',   'name_ar' => 'عرض الفروع',         'name_en' => 'View Branches'],
            ['slug' => 'branches.create',          'group' => 'branches',   'name_ar' => 'إنشاء فرع',          'name_en' => 'Create Branch'],
            ['slug' => 'branches.edit',            'group' => 'branches',   'name_ar' => 'تعديل فرع',          'name_en' => 'Edit Branch'],
            ['slug' => 'branches.delete',          'group' => 'branches',   'name_ar' => 'حذف فرع',            'name_en' => 'Delete Branch'],

            // --- Leaves ---
            ['slug' => 'leaves.request',           'group' => 'leaves',     'name_ar' => 'طلب إجازة',          'name_en' => 'Request Leave'],
            ['slug' => 'leaves.approve',           'group' => 'leaves',     'name_ar' => 'اعتماد الإجازات',     'name_en' => 'Approve Leaves'],
            ['slug' => 'leaves.view_all',          'group' => 'leaves',     'name_ar' => 'عرض كل الإجازات',     'name_en' => 'View All Leaves'],

            // --- Whistleblower ---
            ['slug' => 'whistleblower.submit',     'group' => 'whistleblower', 'name_ar' => 'تقديم بلاغ',        'name_en' => 'Submit Report'],
            ['slug' => 'whistleblower.view',       'group' => 'whistleblower', 'name_ar' => 'عرض البلاغات',       'name_en' => 'View Reports'],
            ['slug' => 'whistleblower.investigate','group' => 'whistleblower', 'name_ar' => 'التحقيق في البلاغات', 'name_en' => 'Investigate Reports'],

            // --- Traps ---
            ['slug' => 'traps.view',               'group' => 'traps',      'name_ar' => 'عرض سجل المصائد',     'name_en' => 'View Trap Logs'],
            ['slug' => 'traps.manage',             'group' => 'traps',      'name_ar' => 'إدارة المصائد',       'name_en' => 'Manage Traps'],

            // --- Messaging ---
            ['slug' => 'messaging.chat',           'group' => 'messaging',  'name_ar' => 'المحادثات',           'name_en' => 'Chat'],
            ['slug' => 'messaging.circulars',      'group' => 'messaging',  'name_ar' => 'إنشاء تعاميم',       'name_en' => 'Create Circulars'],
            ['slug' => 'messaging.broadcast',      'group' => 'messaging',  'name_ar' => 'إرسال جماعي',        'name_en' => 'Broadcast Messages'],

            // --- Gamification ---
            ['slug' => 'gamification.view_own',    'group' => 'gamification', 'name_ar' => 'عرض النقاط الشخصية',  'name_en' => 'View Own Points'],
            ['slug' => 'gamification.view_all',    'group' => 'gamification', 'name_ar' => 'عرض كل النقاط',       'name_en' => 'View All Points'],
            ['slug' => 'gamification.manage',      'group' => 'gamification', 'name_ar' => 'إدارة التلعيب',       'name_en' => 'Manage Gamification'],

            // --- System ---
            ['slug' => 'system.settings',          'group' => 'system',     'name_ar' => 'إعدادات النظام',      'name_en' => 'System Settings'],
            ['slug' => 'system.audit_logs',        'group' => 'system',     'name_ar' => 'سجل المراجعة',        'name_en' => 'Audit Logs'],
            ['slug' => 'system.manage_holidays',   'group' => 'system',     'name_ar' => 'إدارة العطل',         'name_en' => 'Manage Holidays'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
        }

        /*
        |----------------------------------------------------------------------
        | Assign Permissions to Roles (cascading — higher levels inherit lower)
        |----------------------------------------------------------------------
        */
        $rolePermissionMap = [
            // Level 1: Intern — minimal
            'intern' => [
                'attendance.view_own', 'attendance.check_in',
                'gamification.view_own',
            ],

            // Level 2: Employee — standard
            'employee' => [
                'attendance.view_own', 'attendance.check_in',
                'finance.view_own',
                'leaves.request',
                'whistleblower.submit',
                'messaging.chat',
                'gamification.view_own',
            ],

            // Level 3: Senior Employee — + export
            'senior_employee' => [
                'attendance.view_own', 'attendance.check_in', 'attendance.export',
                'finance.view_own',
                'leaves.request',
                'whistleblower.submit',
                'messaging.chat',
                'gamification.view_own',
            ],

            // Level 4: Team Leader — + team view
            'team_leader' => [
                'attendance.view_own', 'attendance.view_team', 'attendance.check_in', 'attendance.export',
                'finance.view_own', 'finance.view_team',
                'leaves.request', 'leaves.approve',
                'whistleblower.submit',
                'messaging.chat',
                'gamification.view_own',
            ],

            // Level 5: Supervisor — + manual entry
            'supervisor' => [
                'attendance.view_own', 'attendance.view_team', 'attendance.check_in',
                'attendance.manual_entry', 'attendance.approve', 'attendance.export',
                'finance.view_own', 'finance.view_team',
                'users.view',
                'leaves.request', 'leaves.approve',
                'whistleblower.submit',
                'messaging.chat',
                'gamification.view_own', 'gamification.view_all',
            ],

            // Level 6: Department Manager — + branch finance
            'department_manager' => [
                'attendance.view_own', 'attendance.view_team', 'attendance.view_branch',
                'attendance.check_in', 'attendance.manual_entry', 'attendance.approve', 'attendance.export',
                'finance.view_own', 'finance.view_team', 'finance.view_branch', 'finance.generate_reports', 'finance.dashboard',
                'users.view', 'users.create', 'users.edit',
                'branches.view',
                'leaves.request', 'leaves.approve', 'leaves.view_all',
                'whistleblower.submit',
                'messaging.chat', 'messaging.circulars',
                'gamification.view_own', 'gamification.view_all',
            ],

            // Level 7: Branch Manager — + user management + whistleblower viewing
            'branch_manager' => [
                'attendance.view_own', 'attendance.view_team', 'attendance.view_branch',
                'attendance.check_in', 'attendance.manual_entry', 'attendance.approve', 'attendance.export',
                'finance.view_own', 'finance.view_team', 'finance.view_branch', 'finance.generate_reports', 'finance.dashboard',
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'branches.view', 'branches.edit',
                'leaves.request', 'leaves.approve', 'leaves.view_all',
                'whistleblower.submit', 'whistleblower.view',
                'messaging.chat', 'messaging.circulars', 'messaging.broadcast',
                'gamification.view_own', 'gamification.view_all',
                'traps.view',
            ],

            // Level 8: Regional Director — + all branches
            'regional_director' => [
                'attendance.view_own', 'attendance.view_team', 'attendance.view_branch', 'attendance.view_all',
                'attendance.check_in', 'attendance.manual_entry', 'attendance.approve', 'attendance.export',
                'finance.view_own', 'finance.view_team', 'finance.view_branch', 'finance.view_all',
                'finance.generate_reports', 'finance.dashboard',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles',
                'branches.view', 'branches.create', 'branches.edit',
                'leaves.request', 'leaves.approve', 'leaves.view_all',
                'whistleblower.submit', 'whistleblower.view', 'whistleblower.investigate',
                'messaging.chat', 'messaging.circulars', 'messaging.broadcast',
                'gamification.view_own', 'gamification.view_all', 'gamification.manage',
                'traps.view', 'traps.manage',
                'system.manage_holidays',
            ],

            // Level 9: Executive — everything except system settings
            'executive' => [
                'attendance.view_own', 'attendance.view_team', 'attendance.view_branch', 'attendance.view_all',
                'attendance.check_in', 'attendance.manual_entry', 'attendance.approve', 'attendance.export',
                'finance.view_own', 'finance.view_team', 'finance.view_branch', 'finance.view_all',
                'finance.generate_reports', 'finance.manage_salaries', 'finance.dashboard',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles',
                'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
                'leaves.request', 'leaves.approve', 'leaves.view_all',
                'whistleblower.submit', 'whistleblower.view', 'whistleblower.investigate',
                'messaging.chat', 'messaging.circulars', 'messaging.broadcast',
                'gamification.view_own', 'gamification.view_all', 'gamification.manage',
                'traps.view', 'traps.manage',
                'system.manage_holidays', 'system.audit_logs',
            ],

            // Level 10: Super Admin — ALL permissions
            'super_admin' => Permission::pluck('slug')->toArray(),
        ];

        foreach ($rolePermissionMap as $roleSlug => $permSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                // Resolve to actual permission slugs (super_admin uses dynamic pluck)
                $permIds = is_array($permSlugs)
                    ? Permission::whereIn('slug', $permSlugs)->pluck('id')
                    : $permSlugs;

                $role->permissions()->sync($permIds);
            }
        }
    }
}
