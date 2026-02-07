<?php

namespace Tests\Feature;

use App\Filament\Resources\AttendanceLogResource;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ─── Helper ────────────────────────────────────

    private function createBranchWithLogs(string $code, int $logCount = 2): array
    {
        $branch = Branch::create([
            'name_ar'              => 'فرع ' . $code,
            'name_en'              => 'Branch ' . $code,
            'code'                 => $code,
            'latitude'             => 24.7136,
            'longitude'            => 46.6753,
            'geofence_radius'      => 20,
            'default_shift_start'  => '08:00',
            'default_shift_end'    => '16:00',
            'grace_period_minutes' => 5,
            'is_active'            => true,
        ]);

        $user = User::factory()->create([
            'branch_id'    => $branch->id,
            'is_super_admin' => false,
        ]);

        for ($i = 0; $i < $logCount; $i++) {
            AttendanceLog::create([
                'user_id'         => $user->id,
                'branch_id'       => $branch->id,
                'attendance_date' => Carbon::today()->subDays($i),
                'check_in_time'   => '08:30',
                'status'          => 'late',
                'delay_minutes'   => 30,
                'cost_per_minute' => 2.0,
                'delay_cost'      => 60.0,
            ]);
        }

        return ['branch' => $branch, 'user' => $user];
    }

    // ──────────────────────────────────────────────
    // TC-HARD-001: BranchScope — non-super-admin sees own branch only
    // ──────────────────────────────────────────────

    public function test_branch_scope_limits_non_super_admin_to_own_branch(): void
    {
        $branchA = $this->createBranchWithLogs('SCOPE-A', 3);
        $branchB = $this->createBranchWithLogs('SCOPE-B', 2);

        // Act as user from Branch A
        $this->actingAs($branchA['user']);

        $query = AttendanceLogResource::getEloquentQuery();
        $results = $query->get();

        // User from Branch A should only see Branch A's 3 logs
        $this->assertCount(3, $results);
        $results->each(function ($log) use ($branchA) {
            $this->assertEquals($branchA['branch']->id, $log->branch_id);
        });
    }

    // ──────────────────────────────────────────────
    // TC-HARD-002: BranchScope — super-admin sees all branches
    // ──────────────────────────────────────────────

    public function test_branch_scope_allows_super_admin_to_see_all(): void
    {
        $this->createBranchWithLogs('ALL-A', 2);
        $this->createBranchWithLogs('ALL-B', 3);

        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $query = AttendanceLogResource::getEloquentQuery();
        $results = $query->get();

        // Super admin should see all 5 logs from both branches
        $this->assertCount(5, $results);
    }

    // ──────────────────────────────────────────────
    // TC-HARD-003: Caching — getDailyLoss returns cached value
    // ──────────────────────────────────────────────

    public function test_caching_returns_cached_daily_loss(): void
    {
        $data = $this->createBranchWithLogs('CACHE-A', 1);
        $today = Carbon::today();

        $service = new FinancialReportingService();

        // First call — populates cache (1 log on today = 60.0)
        $firstResult = $service->getDailyLoss($today);
        $this->assertEquals(60.0, $firstResult);

        // Manually override cache to verify it's being used
        $cacheKey = 'sarh.loss.' . $today->format('Y-m-d') . '.all';
        Cache::put($cacheKey, 999.99, 300);

        $cachedResult = $service->getDailyLoss($today);
        $this->assertEquals(999.99, $cachedResult);
    }

    // ──────────────────────────────────────────────
    // TC-HARD-004: Caching — cache bust returns fresh data
    // ──────────────────────────────────────────────

    public function test_cache_flush_returns_fresh_data(): void
    {
        $data = $this->createBranchWithLogs('FRESH-A', 1);
        $today = Carbon::today();

        $service = new FinancialReportingService();

        $first = $service->getDailyLoss($today);
        $this->assertEquals(60.0, $first);

        // Add another log with a different user (unique constraint is user_id + date)
        $user2 = User::factory()->create([
            'branch_id' => $data['branch']->id,
        ]);

        AttendanceLog::create([
            'user_id'         => $user2->id,
            'branch_id'       => $data['branch']->id,
            'attendance_date' => $today,
            'check_in_time'   => '09:00',
            'status'          => 'late',
            'delay_minutes'   => 15,
            'cost_per_minute' => 2.0,
            'delay_cost'      => 30.0,
        ]);

        // Still cached — should return old value
        $cached = $service->getDailyLoss($today);
        $this->assertEquals(60.0, $cached);

        // Flush cache
        Cache::flush();

        // Now returns fresh
        $fresh = $service->getDailyLoss($today);
        $this->assertEquals(90.0, $fresh);
    }

    // ──────────────────────────────────────────────
    // TC-HARD-005: sarh:install command exists and is registered
    // ──────────────────────────────────────────────

    public function test_install_command_is_registered(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('sarh:install', $commands);
    }

    // ──────────────────────────────────────────────
    // TC-HARD-006: Bilingual keys — all lang files return arrays
    // ──────────────────────────────────────────────

    public function test_all_lang_files_return_valid_arrays(): void
    {
        $langFiles = [
            'app', 'attendance', 'traps', 'pwa', 'command', 'install',
        ];

        foreach (['ar', 'en'] as $locale) {
            foreach ($langFiles as $file) {
                $translations = __($file, [], $locale);
                // If a single key is returned as string, check for that; but file-level should be array
                $path = lang_path("{$locale}/{$file}.php");
                $this->assertFileExists($path, "Missing lang file: {$locale}/{$file}.php");

                $data = require $path;
                $this->assertIsArray($data, "Lang file {$locale}/{$file}.php must return an array");
                $this->assertNotEmpty($data, "Lang file {$locale}/{$file}.php should not be empty");
            }
        }
    }

    // ──────────────────────────────────────────────
    // TC-HARD-007: Navigation groups are localized
    // ──────────────────────────────────────────────

    public function test_navigation_groups_are_localized(): void
    {
        // Verify lang keys exist and return non-empty
        $this->assertNotEmpty(__('traps.navigation_group'));
        $this->assertNotEmpty(__('attendance.navigation_group'));
        $this->assertNotEmpty(__('command.navigation_group'));

        // Verify Arabic translations differ from English
        app()->setLocale('ar');
        $arSecurity = __('traps.navigation_group');

        app()->setLocale('en');
        $enSecurity = __('traps.navigation_group');

        $this->assertNotEquals($arSecurity, $enSecurity);
    }

    // ──────────────────────────────────────────────
    // TC-HARD-008: Production indexes migration can run
    // ──────────────────────────────────────────────

    public function test_production_indexes_migration_runs_successfully(): void
    {
        // RefreshDatabase already ran all migrations including our indexes migration.
        // Verify the attendance_logs table exists (proves migration ran).
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('attendance_logs'),
            'attendance_logs table should exist after running all migrations'
        );

        // Verify trap_interactions table also exists
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('trap_interactions'),
            'trap_interactions table should exist after running all migrations'
        );
    }
}
