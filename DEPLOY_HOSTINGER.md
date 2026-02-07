# SARH AL-ITQAN — Hostinger Deployment Playbook (sarh.online)
> **Date:** 2026-02-08 | **DB State:** Empty — Fresh Install

---

## Quick Reference

| Item | Value |
|------|-------|
| Domain | `sarh.online` |
| SSH | `ssh -p 65002 u850419603@145.223.119.139` |
| Project Path | `/home/u850419603/sarh` |
| Web Root | `/home/u850419603/public_html` → symlink to `sarh/public` |
| Database | `u850419603_sarh` @ `127.0.0.1:3306` |
| DB User | `u850419603_sarh` |
| Admin Panel | `https://sarh.online/admin` |

---

## Step-by-Step Deployment

### 1. SSH into the Server

```bash
ssh -p 65002 u850419603@145.223.119.139
```

### 2. Upload the Project

**Option A — Git Clone (if repo exists):**
```bash
cd /home/u850419603
git clone <YOUR_REPO_URL> sarh
cd sarh
```

**Option B — Upload via File Manager / SFTP:**
Upload the entire `sarh/` folder to `/home/u850419603/sarh`.

> **Security:** The project lives ABOVE `public_html`, so only the `public/` folder is web-accessible.

### 3. Run the Deploy Script

```bash
cd /home/u850419603/sarh
chmod +x deploy.sh
bash deploy.sh
```

This single command will:
1. ✅ `composer install --no-dev --optimize-autoloader`
2. ✅ Copy `.env.production` → `.env` and generate `APP_KEY`
3. ✅ `php artisan migrate --force --seed` (creates all 16 tables + seeds RBAC, Traps, Badges)
4. ✅ `npm install && npm run build` (compile Vite/Tailwind assets)
5. ✅ `php artisan storage:link`
6. ✅ Delete `public_html/` and symlink it → `sarh/public/`
7. ✅ `php artisan optimize` (cache config, routes, views)
8. ✅ Fix permissions on `storage/` and `bootstrap/cache/`

### 4. Create the Super Admin

After deploy.sh completes:

```bash
php artisan sarh:install
```

> This is the `SarhInstallCommand`. Since migrations already ran, it will skip to creating the Super Admin (Level 10).  
> Enter: **name_ar**, **name_en**, **email**, **password** when prompted.

### 5. Verify

Open `https://sarh.online/admin` and log in with the Super Admin credentials.

---

## If npm Is Not Available on Hostinger

Hostinger shared hosting may not include Node.js. In that case:

1. Build locally on your machine:
```bash
cd sarh
npm install
npm run build
```

2. Upload the generated `public/build/` folder to `/home/u850419603/sarh/public/build/` via SFTP.

The deploy script detects this and warns you — it won't fail.

---

## Manual Commands Reference

If you prefer running steps individually instead of `deploy.sh`:

```bash
# Navigate to project
cd /home/u850419603/sarh

# Copy env
cp .env.production .env
php artisan key:generate --force

# PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Database (empty → full schema + seed data)
php artisan migrate --force --seed

# Frontend (if npm available)
npm install --no-audit --no-fund && npm run build

# Storage link
php artisan storage:link --force

# Symlink public_html
rm -rf /home/u850419603/public_html
ln -s /home/u850419603/sarh/public /home/u850419603/public_html

# Production cache
php artisan optimize

# Permissions
chmod -R 775 storage bootstrap/cache

# Create Super Admin
php artisan sarh:install
```

---

## Permissions Fix (if 500 errors)

```bash
cd /home/u850419603/sarh
chmod -R 775 storage bootstrap/cache
chmod -R 644 storage/logs/*.log 2>/dev/null || true
```

If Hostinger uses a different web server user:
```bash
# Check the web server user
ps aux | grep -E 'apache|nginx|lsws' | head -1

# If needed, set group ownership
chgrp -R www-data storage bootstrap/cache
```

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| 500 Internal Server Error | `chmod -R 775 storage bootstrap/cache` |
| "Vite manifest not found" | Upload `public/build/` — npm build was missing |
| "SQLSTATE Connection refused" | Verify DB host is `127.0.0.1` in `.env`, not `localhost` |
| CSS/JS not loading | Confirm symlink: `ls -la /home/u850419603/public_html` should show `→ sarh/public` |
| "No application encryption key" | `php artisan key:generate --force` |
| 404 on all routes except `/` | Check `.htaccess` exists in `public/`, Hostinger may need Apache mod_rewrite |
| Admin login page blank | `php artisan filament:optimize` then `php artisan view:clear` |

---

## Post-Deploy Checklist

- [ ] `https://sarh.online` loads without errors
- [ ] `https://sarh.online/admin` shows Filament login
- [ ] Super Admin can log in (Level 10)
- [ ] Arabic RTL layout renders correctly
- [ ] Sidebar shows: الحضور، الأمان، غرفة القيادة
- [ ] `APP_DEBUG=false` confirmed in `.env`
