<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function write(string $level, string $message): void
    {
        $file = dirname(__DIR__, 2) . '/storage/logs/app.log';
        $line = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $m): void { self::write('error', $m); }
    public static function info(string $m): void  { self::write('info', $m); }
}
