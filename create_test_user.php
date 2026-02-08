#!/usr/bin/env php
<?php

/**
 * SARH Test User Creator
 * Creates a test user with proper credentials for admin panel access
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;

echo "=== SARH Test User Creator ===\n\n";

// Get first active branch
$branch = Branch::first();
if (!$branch) {
    echo "ERROR: No branches found. Run ProjectDataSeeder first.\n";
    exit(1);
}

// Create test user with ALL required conditions
$testUser = User::updateOrCreate(
    ['email' => 'test@sarh.app'],
    [
        'name_ar'                => 'مستخدم اختبار',
        'name_en'                => 'Test User',
        'employee_id'            => 'test001',
        'password'               => Hash::make('Test123!'),  // Simple password for testing
        'basic_salary'           => 5000,
        'housing_allowance'      => 1250,
        'transport_allowance'    => 500,
        'branch_id'              => $branch->id,
        'working_days_per_month' => 22,
        'working_hours_per_day'  => 8,
        'status'                 => 'active',  // CRITICAL: Must be active
        'employment_type'        => 'full_time',
        'locale'                 => 'ar',
        'timezone'               => 'Asia/Riyadh',
        'total_points'           => 50,
    ]
);

// Set security level (guarded field requires forceFill)
$testUser->forceFill(['security_level' => 10])->save();

echo "✓ Test user created successfully!\n\n";
echo "Email: test@sarh.app\n";
echo "Password: Test123!\n";
echo "Security Level: 10\n";
echo "Status: active\n";
echo "Branch: {$branch->name_en}\n\n";

// Verify abdullah exists
$abdullah = User::where('email', 'abdullah@sarh.app')->first();
if ($abdullah) {
    echo "✓ Abdullah account exists:\n";
    echo "  Email: abdullah@sarh.app\n";
    echo "  Password: P@ssw0rd!SARH\n";
    echo "  Status: {$abdullah->status}\n";
    echo "  Security Level: {$abdullah->security_level}\n";
    echo "  Super Admin: " . ($abdullah->is_super_admin ? 'YES' : 'NO') . "\n";
    echo "  Can Access Panel: " . ($abdullah->canAccessPanel(filament()->getCurrentPanel()) ? 'YES' : 'NO') . "\n";
} else {
    echo "WARNING: Abdullah account not found. Run ProjectDataSeeder.\n";
}

echo "\n=== Done ===\n";
