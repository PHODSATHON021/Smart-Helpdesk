<?php
declare(strict_types=1);

use App\Core\Env;

/** HTML escape */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

/** Base path ของแอป (รองรับกรณีรันใน sub-folder เช่น /smart-helpdesk/public) */
function base_path_url(): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $dir === '.' ? '' : $dir;
}

function url(string $path = '/'): string
{
    return base_path_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function with_old(array $input): void
{
    unset($input['password'], $input['_csrf']);
    $_SESSION['_old'] = $input;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old_current'][$key] ?? $default;
}

function format_dt(?string $dt): string
{
    return $dt ? date('d/m/Y H:i', strtotime($dt)) : '-';
}
