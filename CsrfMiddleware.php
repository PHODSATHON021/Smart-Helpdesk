<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** ตรวจ CSRF token กับทุก request ที่ไม่ใช่ GET (จาก field _csrf หรือ header X-CSRF-Token) */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private Csrf $csrf) {}

    public function handle(Request $request): ?Response
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }
        $token = $request->header('x-csrf-token') ?? $request->input('_csrf');
        if (!$this->csrf->validate($token)) {
            throw new HttpException(419, 'CSRF token ไม่ถูกต้องหรือหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่');
        }
        return null;
    }
}
