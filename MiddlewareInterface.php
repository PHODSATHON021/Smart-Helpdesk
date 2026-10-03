<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;

interface MiddlewareInterface
{
    /** คืน Response เพื่อหยุดการทำงาน หรือ null เพื่อไปต่อ */
    public function handle(Request $request): ?Response;
}
