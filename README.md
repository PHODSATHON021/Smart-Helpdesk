# Smart IT Helpdesk & Notification System
Custom OOP PHP 8.1+ (No Framework) · PDO · MySQL · LINE Messaging API

## ติดตั้งและรัน (Dev)
```bash
# 1) สร้างฐานข้อมูล
mysql -u root -p < database/schema.sql

# 2) ตั้งค่า .env (แก้ DB_USER / DB_PASS ให้ตรงเครื่อง)
cp .env.example .env

# 3) สร้างผู้ใช้ทดลอง + หมวดหมู่
php database/seed.php

# 4) (ไม่บังคับ) composer dump-autoload   -- ไม่ติดตั้ง ก็รันได้ เพราะมี PSR-4 autoloader สำรองใน bootstrap

# 5) รันเซิร์ฟเวอร์
php -S localhost:8000 -t public public/router.php
```
เปิด http://localhost:8000

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | admin1234 |
| Technician | tech1@example.com | tech1234 |
| User | user@example.com | user1234 |

**Apache/XAMPP:** ชี้ DocumentRoot ไปที่โฟลเดอร์ `public/` (เปิด `mod_rewrite`, `AllowOverride All`)
**Production:** ดู Security Checklist ใน Diagram 9 (HTTPS, `.env` chmod 600, `APP_DEBUG=false`, storage นอก DocRoot)

## เปิดใช้ LINE Notification
ใส่ `LINE_CHANNEL_TOKEN` และ `LINE_IT_TEAM_ID` (groupId/userId ของทีม IT) ใน `.env`
และบันทึก `line_user_id` ของ Technician/User ที่หน้า Admin → ผู้ใช้
หากเว้นว่าง ระบบจะเขียนข้อความลง `storage/logs/app.log` แทน (ไม่ทำให้ระบบล่ม)

## Mapping: Diagram → โค้ด
| Diagram | ไฟล์หลัก |
|---|---|
| 1 Architecture | `public/index.php` → `Router` → `Middleware` → `Controller` → `Service` → `Repository` → PDO |
| 2 ER | `database/schema.sql`, `src/Repositories/*` |
| 3 State Machine | `src/Enums/TicketStatus.php`, `src/Services/TicketStatusService.php` |
| 4 Use Case (RBAC) | `src/routes.php` (RoleMiddleware), `TicketService::canView/canComment` |
| 5 Class | `src/Core`, `src/Repositories`, `src/Services`, `src/Observers`, `src/Notifications` |
| 6.1 สร้าง Ticket | `TicketController::store` → `TicketService::createTicket` → event `ticket.created` |
| 6.2 อัปเดตสถานะ | `TicketController::updateStatus` → `TicketStatusService::transition` |
| 6.3 Dashboard | `DashboardController` → `DashboardService` → `TicketRepository` (aggregate) |
| 7 Activity | `TicketObserver` (L1/L2/L3 LINE) + `views/tickets/show.php` (Action Panel) |
| 8 Component | โครงสร้างโฟลเดอร์ทั้งหมด |
| 9 Deployment | `public/.htaccess`, `.env`, `storage/` นอก DocRoot |
