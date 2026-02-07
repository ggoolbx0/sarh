<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SarhInstallCommand extends Command
{
    protected $signature = 'sarh:install';

    protected $description = 'Install SARH AL-ITQAN: seed roles, badges, traps, and create the initial Super Admin (Level 10)';

    public function handle(): int
    {
        $this->components->info(__('install.banner'));
        $this->newLine();

        // ── Step 1: Verify Environment ──────────────────────
        $this->components->task(__('install.verifying_env'), function () {
            return $this->verifyEnvironment();
        });

        // ── Step 2: Run Migrations ──────────────────────────
        $this->components->task(__('install.running_migrations'), function () {
            return $this->runMigrations();
        });

        // ── Step 3: Seed Core Data ──────────────────────────
        $this->components->task(__('install.seeding_roles'), function () {
            $this->callSilently('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder', '--force' => true]);
            return true;
        });

        $this->components->task(__('install.seeding_badges'), function () {
            $this->callSilently('db:seed', ['--class' => 'Database\\Seeders\\BadgesSeeder', '--force' => true]);
            return true;
        });

        $this->components->task(__('install.seeding_traps'), function () {
            $this->callSilently('db:seed', ['--class' => 'Database\\Seeders\\TrapsSeeder', '--force' => true]);
            return true;
        });

        // ── Step 4: Create Super Admin ──────────────────────
        $this->newLine();

        if (!User::where('is_super_admin', true)->exists()) {
            $this->components->info(__('install.creating_admin'));
            $this->createSuperAdmin();
        } else {
            $this->components->warn(__('install.admin_exists'));
        }

        // ── Step 5: Finalize ────────────────────────────────
        $this->newLine();

        $this->components->task(__('install.storage_link'), function () {
            $this->callSilently('storage:link');
            return true;
        });

        if (app()->environment('production')) {
            $this->components->task(__('install.caching_config'), function () {
                $this->callSilently('config:cache');
                $this->callSilently('route:cache');
                $this->callSilently('view:cache');
                return true;
            });
        }

        $this->newLine();
        $this->components->info(__('install.complete'));
        $this->components->bulletList([
            __('install.summary_roles', ['count' => Role::count()]),
            __('install.summary_admin'),
            __('install.summary_url', ['url' => config('app.url') . '/admin']),
        ]);

        return self::SUCCESS;
    }

    private function verifyEnvironment(): bool
    {
        $requiredExtensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];

        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->components->error(__('install.missing_ext', ['ext' => $ext]));
                return false;
            }
        }

        if (empty(config('app.key'))) {
            $this->components->error(__('install.no_app_key'));
            return false;
        }

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->components->error(__('install.db_failed', ['error' => $e->getMessage()]));
            return false;
        }

        return true;
    }

    private function runMigrations(): bool
    {
        $this->callSilently('migrate', ['--force' => true]);
        return true;
    }

    private function createSuperAdmin(): void
    {
        $nameAr = $this->ask(__('install.prompt_name_ar'));
        $nameEn = $this->ask(__('install.prompt_name_en'));
        $email  = $this->ask(__('install.prompt_email'));
        $password = $this->secret(__('install.prompt_password'));

        $user = User::create([
            'employee_id'           => 'SARH-' . now()->format('y') . '-0001',
            'name_ar'               => $nameAr,
            'name_en'               => $nameEn,
            'email'                 => $email,
            'password'              => Hash::make($password),
            'email_verified_at'     => now(),
            'phone'                 => '0000000000',
            'national_id'           => '0000000000',
            'hire_date'             => now()->toDateString(),
            'status'                => 'active',
            'basic_salary'          => 0,
            'working_days_per_month'=> 22,
            'working_hours_per_day' => 8,
        ]);

        $user->promoteToSuperAdmin();
        $user->setSecurityLevel(10);

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            $user->update(['role_id' => $superAdminRole->id]);
        }

        $this->components->info(__('install.admin_created', ['email' => $email]));
    }
}
