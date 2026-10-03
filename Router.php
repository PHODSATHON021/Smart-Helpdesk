<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Middleware\MiddlewareInterface;

final class Router
{
    private array $routes = [];
    /** @var MiddlewareInterface[] */
    private array $globalMiddleware = [];

    public function __construct(private Container $container) {}

    public function addGlobalMiddleware(MiddlewareInterface $mw): void { $this->globalMiddleware[] = $mw; }

    public function get(string $path, array|callable $handler, array $mw = []): void   { $this->add('GET', $path, $handler, $mw); }
    public function post(string $path, array|callable $handler, array $mw = []): void  { $this->add('POST', $path, $handler, $mw); }
    public function patch(string $path, array|callable $handler, array $mw = []): void { $this->add('PATCH', $path, $handler, $mw); }

    private function add(string $method, string $path, array|callable $handler, array $mw): void
    {
        // {id} = ตัวเลขเท่านั้น, {ชื่ออื่น} = ข้อความที่ไม่มี "/"
        $regex = preg_replace_callback('#\{(\w+)\}#', fn($m) =>
            $m[1] === 'id' ? '(?P<id>\d+)' : '(?P<' . $m[1] . '>[^/]+)', $path);
        $this->routes[] = compact('method', 'regex', 'handler', 'mw');
    }

    public function dispatch(Request $request): Response
    {
        try {
            $pathMatched = false;
            foreach ($this->routes as $route) {
                if (!preg_match('#^' . $route['regex'] . '$#', $request->path, $m)) {
                    continue;
                }
                $pathMatched = true;
                if ($route['method'] !== $request->method) {
                    continue;
                }
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ([...$this->globalMiddleware, ...$route['mw']] as $mw) {
                    if ($short = $mw->handle($request)) {
                        return $short;   // middleware ตัดจบ (redirect / 401)
                    }
                }
                return $this->invoke($route['handler'], $request, $params);
            }
            throw new HttpException($pathMatched ? 405 : 404, $pathMatched ? 'Method Not Allowed' : 'ไม่พบหน้าที่ต้องการ');
        } catch (HttpException $e) {
            return $this->errorResponse($request, $e);
        }
    }

    private function invoke(array|callable $handler, Request $request, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            return $this->container->get($class)->$method($request, $params);
        }
        return $handler($request, $params);
    }

    private function errorResponse(Request $request, HttpException $e): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'message' => $e->getMessage(), 'errors' => $e->errors], $e->status);
        }
        return $this->container->get(View::class)->render('errors/error', [
            'code' => $e->status, 'message' => $e->getMessage(),
        ], $e->status);
    }
}
