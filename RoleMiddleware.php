<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

final class RoleMiddleware implements MiddlewareInterface
{
    /** @param string[] $roles */
    public function __construct(private Auth $auth, private array $roles) {}

    public function handle(Request $request): ?Response
    {
        if (!in_array($this->auth->role(), $this->roles, true)) {
            throw new HttpException(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
        return null;
    }
}
