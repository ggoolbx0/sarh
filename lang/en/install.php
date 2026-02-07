<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SARH Installation Command — English
    |--------------------------------------------------------------------------
    */

    'banner'              => '══════════════════════════════════════════',
    'app_title'           => 'Installing SARH AL-ITQAN',
    'verifying_env'       => 'Verifying environment',
    'running_migrations'  => 'Running database migrations',
    'seeding_roles'       => 'Seeding roles & permissions (10 levels)',
    'seeding_badges'      => 'Seeding badges (8 badges)',
    'seeding_traps'       => 'Seeding psychological traps (4 traps)',
    'creating_admin'      => 'Creating Super Admin account (Level 10)',
    'admin_exists'        => 'Super Admin already exists — skipping',
    'admin_created'       => 'Super Admin created successfully: :email',
    'storage_link'        => 'Creating storage symlink',
    'caching_config'      => 'Caching config, routes & views',
    'complete'            => '✅ Installation complete!',
    'summary_roles'       => 'Roles created: :count',
    'summary_admin'       => 'Super Admin account ready (Level 10)',
    'summary_url'         => 'Admin panel: :url',
    'missing_ext'         => '❌ Missing PHP extension: :ext',
    'no_app_key'          => '❌ APP_KEY not set — run: php artisan key:generate',
    'db_failed'           => '❌ Database connection failed: :error',
    'prompt_name_ar'      => 'Full Name (Arabic)',
    'prompt_name_en'      => 'Full Name (English)',
    'prompt_email'        => 'Email Address',
    'prompt_password'     => 'Password',

];
