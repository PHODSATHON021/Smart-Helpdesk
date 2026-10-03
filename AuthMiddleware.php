<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Auth $auth) {}

    public function handle(Request $request): ?Response
    {
        if ($this->auth->check()) {
            return null;
        }
        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
        }
        flash('error', 'กรุณาเข้าสู่ระบบก่อน');
        return Response::redirect(url('/login'));
    }
}
