<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public function token(): string
    {
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public function validate(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals($this->token(), $token);
    }
}
