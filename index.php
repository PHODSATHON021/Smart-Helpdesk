<?php
declare(strict_types=1);

// Front Controller: ทุก request เข้าที่นี่ (ผ่าน .htaccess หรือ php -S)
$c = require __DIR__ . '/../src/bootstrap.php';
$router = require __DIR__ . '/../src/routes.php';

try {
    $router->dispatch(\App\Core\Request::capture())->send();
} catch (\Throwable $e) {
    \App\Core\Logger::error($e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)) {
        echo '<pre>' . e($e) . '</pre>';
    } else {
        echo 'เกิดข้อผิดพลาดภายในระบบ';
    }
}
