<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private Auth $auth) {}

    public function handle(Request $request): ?Response
    {
        return $this->auth->check() ? Response::redirect(url('/tickets')) : null;
    }
}
