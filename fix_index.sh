#!/bin/bash
# Write index.php with absolute paths for Hostinger
cat > /home/u850419603/domains/sarh.online/public_html/index.php << 'PHPEOF'
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$m = '/home/u850419603/sarh/storage/framework/maintenance.php';
if (file_exists($m)) { require $m; }
require '/home/u850419603/sarh/vendor/autoload.php';
(require_once '/home/u850419603/sarh/bootstrap/app.php')->handleRequest(Request::capture());
PHPEOF
echo "index.php written OK"
