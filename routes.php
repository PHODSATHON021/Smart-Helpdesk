<?php
declare(strict_types=1);

use App\Controllers\{AdminController, AuthController, DashboardController, TicketController};
use App\Core\{Auth, Container, Csrf, Request, Response, Router};
use App\Core\Middleware\{AuthMiddleware, CsrfMiddleware, GuestMiddleware, RoleMiddleware};

/** @var Container $c */
$router = new Router($c);
$auth   = $c->get(Auth::class);

$router->addGlobalMiddleware(new CsrfMiddleware($c->get(Csrf::class)));

$authed = new AuthMiddleware($auth);
$guest  = new GuestMiddleware($auth);
$admin  = new RoleMiddleware($auth, ['admin']);
$user   = new RoleMiddleware($auth, ['user']);

// --- Auth ---
$router->get('/', fn(Request $r) => Response::redirect(url($auth->check() ? '/tickets' : '/login')));
$router->get('/login',  [AuthController::class, 'showLogin'], [$guest]);
$router->post('/login', [AuthController::class, 'login'],     [$guest]);
$router->post('/logout', [AuthController::class, 'logout'],   [$authed]);

// --- Tickets (UC1, UC2, UC10) ---
$router->get('/tickets',            [TicketController::class, 'index'],  [$authed]);
$router->get('/tickets/create',     [TicketController::class, 'create'], [$authed, $user]);
$router->post('/tickets',           [TicketController::class, 'store'],  [$authed, $user]);
$router->get('/tickets/{id}',       [TicketController::class, 'show'],   [$authed]);
$router->get('/uploads/{file}',     [TicketController::class, 'image'],  [$authed]);

// --- AJAX API (UC3, UC4, UC5, UC6, UC7) ---
// PATCH ใช้ส่ง JSON, POST ใช้ส่ง multipart (แนบรูป) เพราะ PHP ไม่ parse multipart ของ PATCH ให้
$router->patch('/api/tickets/{id}/status', [TicketController::class, 'updateStatus'], [$authed]);
$router->post('/api/tickets/{id}/status',  [TicketController::class, 'updateStatus'], [$authed]);
$router->post('/api/tickets/{id}/comments', [TicketController::class, 'addComment'],  [$authed]);

// --- Admin (UC8, UC9) ---
$router->get('/admin/dashboard', [DashboardController::class, 'index'], [$authed, $admin]);
$router->get('/admin/users',                 [AdminController::class, 'users'],          [$authed, $admin]);
$router->post('/admin/users',                [AdminController::class, 'storeUser'],      [$authed, $admin]);
$router->post('/admin/users/{id}',           [AdminController::class, 'updateUser'],     [$authed, $admin]);
$router->get('/admin/categories',            [AdminController::class, 'categories'],     [$authed, $admin]);
$router->post('/admin/categories',           [AdminController::class, 'storeCategory'],  [$authed, $admin]);
$router->post('/admin/categories/{id}/delete', [AdminController::class, 'deleteCategory'], [$authed, $admin]);

return $router;
