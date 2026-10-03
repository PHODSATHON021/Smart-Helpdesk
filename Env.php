<?php
declare(strict_types=1);

namespace App\Core;

final class Env
{
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            self::$vars[trim($k)] = trim($v, " \t\"'");
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $v = self::$vars[$key] ?? $_ENV[$key] ?? getenv($key);
        return ($v === false || $v === null || $v === '') ? $default : $v;
    }
}
