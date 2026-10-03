<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $input,
        public readonly array $files,
        public readonly array $headers
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base   = base_path_url();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = '/' . trim(preg_replace('#^/index\.php#', '', $path), '/');

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        // POST ปกติใช้ $_POST แต่ PATCH/PUT/DELETE PHP ไม่ parse ให้ ต้องอ่านจาก php://input เอง
        $input = $_POST;
        if (in_array($method, ['PATCH', 'PUT', 'DELETE'], true) || $input === []) {
            $raw = file_get_contents('php://input') ?: '';
            if ($raw !== '' && str_contains($headers['content-type'] ?? '', 'application/json')) {
                $input = json_decode($raw, true) ?: [];
            } elseif ($raw !== '' && in_array($method, ['PATCH', 'PUT', 'DELETE'], true)) {
                parse_str($raw, $input);
            }
        }

        return new self($method, $path, $_GET, $input, $_FILES, $headers);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function wantsJson(): bool
    {
        return str_starts_with($this->path, '/api/')
            || str_contains($this->header('accept') ?? '', 'application/json');
    }
}
