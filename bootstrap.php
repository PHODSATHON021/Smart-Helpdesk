<?php
declare(strict_types=1);

use App\Controllers\{AdminController, AuthController, DashboardController, TicketController};
use App\Core\{Auth, Container, Csrf, Database, EventDispatcher, FileUploader, View};
use App\Notifications\{LineMessagingService, NotificationChannelInterface};
use App\Observers\TicketObserver;
use App\Repositories\{CategoryRepository, CommentRepository, RatingRepository, StatusLogRepository, TicketRepository, UserRepository};
use App\Services\{DashboardService, TicketService, TicketStatusService};

$root = __DIR__ . '/..';

// ---- Autoload: ใช้ Composer ถ้ามี ไม่งั้นใช้ PSR-4 autoloader สำรอง ----
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    require __DIR__ . '/helpers.php';
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

\App\Core\Env::load($root . '/.env');

$debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Bangkok');

// ---- Session ที่ปลอดภัย ----
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// ---- DI Container ----
$c = new Container();
$c->bind(Database::class, fn() => Database::getInstance());
$c->bind(Csrf::class, fn() => new Csrf());
$c->bind(EventDispatcher::class, fn() => new EventDispatcher());
$c->bind(FileUploader::class, fn() => new FileUploader($root . '/storage/uploads/ticket-images'));

foreach ([UserRepository::class, CategoryRepository::class, TicketRepository::class,
          CommentRepository::class, StatusLogRepository::class, RatingRepository::class] as $repo) {
    $c->bind($repo, fn(Container $c) => new $repo($c->get(Database::class)));
}

$c->bind(Auth::class, fn(Container $c) => new Auth($c->get(UserRepository::class)));
$c->bind(View::class, fn(Container $c) => new View($root . '/views', $c->get(Auth::class), $c->get(Csrf::class)));

$c->bind(NotificationChannelInterface::class, fn() => new LineMessagingService(
    (string) env('LINE_CHANNEL_TOKEN', ''), (string) env('LINE_IT_TEAM_ID', '')
));

$c->bind(TicketService::class, fn(Container $c) => new TicketService(
    $c->get(TicketRepository::class), $c->get(CommentRepository::class), $c->get(StatusLogRepository::class),
    $c->get(Database::class), $c->get(EventDispatcher::class), $c->get(FileUploader::class)
));
$c->bind(TicketStatusService::class, fn(Container $c) => new TicketStatusService(
    $c->get(TicketRepository::class), $c->get(CommentRepository::class), $c->get(StatusLogRepository::class),
    $c->get(RatingRepository::class), $c->get(UserRepository::class), $c->get(Database::class),
    $c->get(EventDispatcher::class), $c->get(FileUploader::class)
));
$c->bind(DashboardService::class, fn(Container $c) => new DashboardService($c->get(TicketRepository::class)));

// ---- Controllers ----
$c->bind(AuthController::class, fn(Container $c) => new AuthController($c->get(Auth::class), $c->get(View::class)));
$c->bind(DashboardController::class, fn(Container $c) => new DashboardController($c->get(DashboardService::class), $c->get(View::class)));
$c->bind(AdminController::class, fn(Container $c) => new AdminController(
    $c->get(UserRepository::class), $c->get(CategoryRepository::class), $c->get(View::class)
));
$c->bind(TicketController::class, fn(Container $c) => new TicketController(
    $c->get(TicketRepository::class), $c->get(CategoryRepository::class), $c->get(UserRepository::class),
    $c->get(CommentRepository::class), $c->get(StatusLogRepository::class), $c->get(RatingRepository::class),
    $c->get(TicketService::class), $c->get(TicketStatusService::class), $c->get(FileUploader::class),
    $c->get(Auth::class), $c->get(View::class)
));

// ---- Observer: ผูก TicketObserver เข้ากับ EventDispatcher ----
(new TicketObserver($c->get(NotificationChannelInterface::class), (string) env('LINE_IT_TEAM_ID', '')))
    ->register($c->get(EventDispatcher::class));

return $c;
