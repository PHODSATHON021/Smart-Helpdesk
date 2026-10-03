<?php
declare(strict_types=1);

/**
 * รัน: php database/seed.php
 * สร้างผู้ใช้ทดลอง + หมวดหมู่เริ่มต้น (รันซ้ำได้ ไม่สร้างซ้ำ)
 */
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();

$users = [
    ['Admin ระบบ',     'admin@example.com', 'admin1234', 'admin'],
    ['ช่างสมชาย',       'tech1@example.com', 'tech1234',  'technician'],
    ['ช่างสมหญิง',      'tech2@example.com', 'tech1234',  'technician'],
    ['สมศรี ผู้ใช้งาน', 'user@example.com',  'user1234',  'user'],
];
foreach ($users as [$name, $email, $pass, $role]) {
    $exists = $db->query('SELECT id FROM users WHERE email = ?', [$email])->fetchColumn();
    if (!$exists) {
        $db->query('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)',
            [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
        echo "created user: $email\n";
    }
}

$cats = [
    ['คอมพิวเตอร์/โน้ตบุ๊ก', 'เครื่องเปิดไม่ติด ช้า ค้าง จอฟ้า'],
    ['เครือข่าย/อินเทอร์เน็ต', 'Wi-Fi หลุด เน็ตช้า เชื่อมต่อไม่ได้'],
    ['เครื่องพิมพ์/สแกนเนอร์', 'ปริ้นไม่ออก กระดาษติด หมึกหมด'],
    ['โปรแกรม/ระบบงาน', 'ติดตั้งโปรแกรม ระบบใช้งานไม่ได้'],
    ['อีเมล/บัญชีผู้ใช้', 'ลืมรหัสผ่าน เข้าอีเมลไม่ได้'],
];
foreach ($cats as [$name, $desc]) {
    $db->query('INSERT IGNORE INTO categories (name, description) VALUES (?,?)', [$name, $desc]);
}
echo "seed complete\n";
