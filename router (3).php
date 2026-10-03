<?php
// ใช้เฉพาะตอน dev ด้วย PHP built-in server:  php -S localhost:8000 -t public public/router.php
// (Apache/Nginx ใช้ .htaccess / try_files แทน ไม่ต้องใช้ไฟล์นี้)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;   // เสิร์ฟไฟล์ static (css/js) ตามปกติ
}
// built-in server ตั้ง SCRIPT_NAME เป็น URL ที่ร้องขอ ต้องแก้ให้ชี้ index.php เพื่อให้ base path ถูกต้อง
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
